<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

$pdo = get_db();
$errors = [];

$selectedMovieId = filter_input(INPUT_GET, 'movie_id', FILTER_VALIDATE_INT) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $postMovieId = filter_input(INPUT_POST, 'movie_id', FILTER_VALIDATE_INT) ?: 0;

    if ($action === 'add') {
        $actorId = filter_input(INPUT_POST, 'actor_id', FILTER_VALIDATE_INT) ?: 0;
        $characterName = trim((string) ($_POST['character_name'] ?? ''));
        $selectedMovieId = $postMovieId;

        if ($postMovieId <= 0) {
            $errors[] = 'Wybierz film.';
        }
        if ($actorId <= 0) {
            $errors[] = 'Wybierz aktora.';
        }
        if ($characterName === '' || mb_strlen($characterName) > 150) {
            $errors[] = 'Nazwa granej postaci jest wymagana i nie może przekraczać 150 znaków.';
        }

        if ($errors === []) {
            $movieExists = $pdo->prepare('SELECT id FROM movies WHERE id = :id LIMIT 1');
            $movieExists->execute([':id' => $postMovieId]);
            $actorExists = $pdo->prepare('SELECT id FROM actors WHERE id = :id LIMIT 1');
            $actorExists->execute([':id' => $actorId]);

            if (!$movieExists->fetch() || !$actorExists->fetch()) {
                $errors[] = 'Wybrany film lub aktor nie istnieje w bazie.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO movie_cast (movie_id, actor_id, character_name)
                         VALUES (:movie_id, :actor_id, :character_name)'
                    );
                    $stmt->execute([
                        ':movie_id' => $postMovieId,
                        ':actor_id' => $actorId,
                        ':character_name' => $characterName,
                    ]);
                    set_flash('success', 'Dodano aktora do obsady filmu.');
                    redirect('admin/cast.php?movie_id=' . $postMovieId);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $errors[] = 'Ten aktor ma już przypisaną dokładnie taką samą rolę w wybranym filmie.';
                    } else {
                        error_log('Błąd dodawania obsady: ' . $e->getMessage());
                        $errors[] = 'Nie udało się zapisać pozycji w obsadzie.';
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $castId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($castId > 0) {
            $stmt = $pdo->prepare('DELETE FROM movie_cast WHERE id = :id');
            $stmt->execute([':id' => $castId]);
            set_flash('success', 'Usunięto pozycję z obsady.');
        }
        redirect('admin/cast.php' . ($postMovieId > 0 ? '?movie_id=' . $postMovieId : ''));
    }
}

$movies = $pdo->query('SELECT id, title, release_year FROM movies ORDER BY title ASC')->fetchAll();
$actors = $pdo->query('SELECT id, first_name, last_name FROM actors ORDER BY last_name ASC, first_name ASC')->fetchAll();

if ($selectedMovieId > 0) {
    $castStmt = $pdo->prepare(
        'SELECT mc.id, mc.movie_id, mc.character_name, m.title AS movie_title, m.release_year,
                a.id AS actor_id, a.first_name, a.last_name
         FROM movie_cast mc
         INNER JOIN movies m ON m.id = mc.movie_id
         INNER JOIN actors a ON a.id = mc.actor_id
         WHERE mc.movie_id = :movie_id
         ORDER BY mc.id ASC'
    );
    $castStmt->execute([':movie_id' => $selectedMovieId]);
    $castList = $castStmt->fetchAll();
} else {
    $castList = $pdo->query(
        'SELECT mc.id, mc.movie_id, mc.character_name, m.title AS movie_title, m.release_year,
                a.id AS actor_id, a.first_name, a.last_name
         FROM movie_cast mc
         INNER JOIN movies m ON m.id = mc.movie_id
         INNER JOIN actors a ON a.id = mc.actor_id
         ORDER BY m.title ASC, mc.id ASC'
    )->fetchAll();
}

$pageTitle = 'Zarządzanie obsadą — Panel Administratora';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Zarządzanie obsadą filmów</h1>
    <p><a href="<?= e(base_url('admin/index.php')) ?>">&larr; Powrót do pulpitu</a></p>

    <?php if ($errors !== []): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="get" action="<?= e(base_url('admin/cast.php')) ?>" class="filter-form">
        <div class="filter-group">
            <label for="filter-cast-movie">Filtruj po filmie</label>
            <select id="filter-cast-movie" name="movie_id">
                <option value="">Wszystkie filmy</option>
                <?php foreach ($movies as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= (int) $m['id'] === $selectedMovieId ? 'selected' : '' ?>>
                        <?= e($m['title']) ?> (<?= (int) $m['release_year'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Pokaż obsadę</button>
        </div>
    </form>

    <form method="post" action="<?= e(base_url('admin/cast.php')) ?>" class="admin-form js-validate-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">

        <h2>Dodaj aktora do obsady</h2>

        <div class="form-group">
            <label for="cast-movie">Film *</label>
            <select id="cast-movie" name="movie_id" required>
                <option value="">-- Wybierz film --</option>
                <?php foreach ($movies as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= (int) $m['id'] === $selectedMovieId ? 'selected' : '' ?>>
                        <?= e($m['title']) ?> (<?= (int) $m['release_year'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="cast-actor">Aktor *</label>
            <select id="cast-actor" name="actor_id" required>
                <option value="">-- Wybierz aktora --</option>
                <?php foreach ($actors as $a): ?>
                    <option value="<?= (int) $a['id'] ?>">
                        <?= e($a['first_name'] . ' ' . $a['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="cast-character">Nazwa granej postaci *</label>
            <input type="text" id="cast-character" name="character_name" required maxlength="150" placeholder="np. Andy Dufresne">
        </div>

        <button type="submit" class="btn btn-primary">Dodaj do obsady</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Film</th>
                <th>Aktor</th>
                <th>Postać</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($castList === []): ?>
                <tr><td colspan="4">Brak wpisów w obsadzie.</td></tr>
            <?php else: ?>
                <?php foreach ($castList as $item): ?>
                    <tr>
                        <td>
                            <a href="<?= e(base_url('movie.php?id=' . (int) $item['movie_id'])) ?>">
                                <?= e($item['movie_title']) ?> (<?= (int) $item['release_year'] ?>)
                            </a>
                        </td>
                        <td>
                            <a href="<?= e(base_url('actor.php?id=' . (int) $item['actor_id'])) ?>">
                                <?= e($item['first_name'] . ' ' . $item['last_name']) ?>
                            </a>
                        </td>
                        <td><?= e($item['character_name']) ?></td>
                        <td class="table-actions">
                            <form method="post" action="<?= e(base_url('admin/cast.php')) ?>" class="inline-delete-form js-confirm" data-confirm="Czy na pewno chcesz usunąć tę pozycję z obsady?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="movie_id" value="<?= (int) $selectedMovieId ?>">
                                <button type="submit" class="btn btn-danger">Usuń</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
