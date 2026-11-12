<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

$pdo = get_db();
$errors = [];

$formData = [
    'id' => 0,
    'title' => '',
    'release_year' => date('Y'),
    'director' => '',
    'duration_minutes' => '',
    'description' => '',
    'poster_url' => '',
    'genre_ids' => [],
];
$isEditMode = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $movieId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($movieId > 0) {
            $stmt = $pdo->prepare('DELETE FROM movies WHERE id = :id');
            $stmt->execute([':id' => $movieId]);
            set_flash('success', 'Film został usunięty.');
        }
        redirect('admin/movies.php');
    }

    if ($action === 'create' || $action === 'update') {
        $isEditMode = ($action === 'update');
        $movieId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

        $title = trim((string) ($_POST['title'] ?? ''));
        $releaseYear = filter_input(INPUT_POST, 'release_year', FILTER_VALIDATE_INT);
        $director = trim((string) ($_POST['director'] ?? ''));
        $durationMinutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
        $description = trim((string) ($_POST['description'] ?? ''));
        $rawPosterUrl = (string) ($_POST['poster_url'] ?? '');

        $rawGenres = $_POST['genre_ids'] ?? [];
        $selectedGenreIds = [];
        if (is_array($rawGenres)) {
            foreach ($rawGenres as $gId) {
                if (ctype_digit((string) $gId) && (int) $gId > 0) {
                    $selectedGenreIds[] = (int) $gId;
                }
            }
            $selectedGenreIds = array_values(array_unique($selectedGenreIds));
        }

        $formData = [
            'id' => $movieId,
            'title' => $title,
            'release_year' => (string) ($_POST['release_year'] ?? ''),
            'director' => $director,
            'duration_minutes' => (string) ($_POST['duration_minutes'] ?? ''),
            'description' => $description,
            'poster_url' => trim($rawPosterUrl),
            'genre_ids' => $selectedGenreIds,
        ];

        if ($title === '' || mb_strlen($title) > 255) {
            $errors[] = 'Tytuł filmu jest wymagany i nie może przekraczać 255 znaków.';
        }
        if ($releaseYear === false || $releaseYear === null || $releaseYear < 1888 || $releaseYear > 2100) {
            $errors[] = 'Rok produkcji musi mieścić się w przedziale 1888–2100.';
        }
        if ($director === '' || mb_strlen($director) > 150) {
            $errors[] = 'Reżyser jest wymagany i nie może przekraczać 150 znaków.';
        }
        if ($durationMinutes === false || $durationMinutes === null || $durationMinutes <= 0 || $durationMinutes > 2000) {
            $errors[] = 'Czas trwania musi być dodatnią liczbą minut (1–2000).';
        }
        if ($description === '') {
            $errors[] = 'Opis fabuły jest wymagany.';
        }

        $urlError = null;
        $posterUrl = validate_image_url($rawPosterUrl, $urlError);
        if ($urlError !== null) {
            $errors[] = $urlError;
        }

        // Weryfikacja, czy przekazane gatunki istnieją w bazie
        if ($selectedGenreIds !== []) {
            $placeholders = implode(',', array_fill(0, count($selectedGenreIds), '?'));
            $gCheck = $pdo->prepare("SELECT id FROM genres WHERE id IN ($placeholders)");
            $gCheck->execute($selectedGenreIds);
            $validIds = array_map(static fn(array $r): int => (int) $r['id'], $gCheck->fetchAll());
            $selectedGenreIds = $validIds;
        }

        if ($errors === []) {
            try {
                $pdo->beginTransaction();

                if ($action === 'create') {
                    $stmt = $pdo->prepare(
                        'INSERT INTO movies (title, release_year, director, duration_minutes, description, poster_url)
                         VALUES (:title, :release_year, :director, :duration_minutes, :description, :poster_url)'
                    );
                    $stmt->execute([
                        ':title' => $title,
                        ':release_year' => $releaseYear,
                        ':director' => $director,
                        ':duration_minutes' => $durationMinutes,
                        ':description' => $description,
                        ':poster_url' => $posterUrl,
                    ]);
                    $movieId = (int) $pdo->lastInsertId();
                } else {
                    if ($movieId <= 0) {
                        throw new RuntimeException('Nieprawidłowy identyfikator filmu.');
                    }
                    $stmt = $pdo->prepare(
                        'UPDATE movies
                         SET title = :title, release_year = :release_year, director = :director,
                             duration_minutes = :duration_minutes, description = :description, poster_url = :poster_url
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':title' => $title,
                        ':release_year' => $releaseYear,
                        ':director' => $director,
                        ':duration_minutes' => $durationMinutes,
                        ':description' => $description,
                        ':poster_url' => $posterUrl,
                        ':id' => $movieId,
                    ]);

                    $delGenres = $pdo->prepare('DELETE FROM movie_genres WHERE movie_id = :movie_id');
                    $delGenres->execute([':movie_id' => $movieId]);
                }

                if ($selectedGenreIds !== []) {
                    $insGenre = $pdo->prepare('INSERT INTO movie_genres (movie_id, genre_id) VALUES (:movie_id, :genre_id)');
                    foreach ($selectedGenreIds as $gId) {
                        $insGenre->execute([
                            ':movie_id' => $movieId,
                            ':genre_id' => $gId,
                        ]);
                    }
                }

                $pdo->commit();
                set_flash('success', $action === 'create' ? 'Dodano nowy film.' : 'Zaktualizowano dane filmu.');
                redirect('admin/movies.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Błąd zapisu filmu: ' . $e->getMessage());
                $errors[] = 'Wystąpił błąd podczas zapisywania filmu.';
            }
        }
    }
} elseif (isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId && $editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM movies WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $editId]);
        $movieRow = $stmt->fetch();
        if ($movieRow) {
            $isEditMode = true;
            $mgStmt = $pdo->prepare('SELECT genre_id FROM movie_genres WHERE movie_id = :movie_id');
            $mgStmt->execute([':movie_id' => $editId]);
            $genreIds = array_map(static fn(array $r): int => (int) $r['genre_id'], $mgStmt->fetchAll());

            $formData = [
                'id' => (int) $movieRow['id'],
                'title' => $movieRow['title'],
                'release_year' => (string) $movieRow['release_year'],
                'director' => $movieRow['director'],
                'duration_minutes' => (string) $movieRow['duration_minutes'],
                'description' => $movieRow['description'],
                'poster_url' => (string) ($movieRow['poster_url'] ?? ''),
                'genre_ids' => $genreIds,
            ];
        }
    }
}

$allGenres = get_all_genres();

$movies = $pdo->query(
    'SELECT m.id, m.title, m.release_year, m.director, m.duration_minutes, m.poster_url,
            GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ", ") AS genres_list
     FROM movies m
     LEFT JOIN movie_genres mg ON mg.movie_id = m.id
     LEFT JOIN genres g ON g.id = mg.genre_id
     GROUP BY m.id, m.title, m.release_year, m.director, m.duration_minutes, m.poster_url
     ORDER BY m.id DESC'
)->fetchAll();

$pageTitle = 'Zarządzanie filmami — Panel Administratora';
$extraScripts = ['assets/js/preview.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Zarządzanie filmami</h1>
    <p><a href="<?= e(base_url('admin/index.php')) ?>">&larr; Powrót do pulpitu</a></p>

    <?php if ($errors !== []): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('admin/movies.php')) ?>" class="admin-form js-validate-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $isEditMode ? 'update' : 'create' ?>">
        <?php if ($isEditMode): ?>
            <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <?php endif; ?>

        <h2><?= $isEditMode ? 'Edytuj film' : 'Dodaj nowy film' ?></h2>

        <div class="form-group">
            <label for="movie-title">Tytuł *</label>
            <input type="text" id="movie-title" name="title" value="<?= e($formData['title']) ?>" required maxlength="255">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="movie-year">Rok produkcji *</label>
                <input type="number" id="movie-year" name="release_year" value="<?= e($formData['release_year']) ?>" required min="1888" max="2100">
            </div>

            <div class="form-group">
                <label for="movie-duration">Czas trwania (minuty) *</label>
                <input type="number" id="movie-duration" name="duration_minutes" value="<?= e($formData['duration_minutes']) ?>" required min="1" max="2000">
            </div>
        </div>

        <div class="form-group">
            <label for="movie-director">Reżyser *</label>
            <input type="text" id="movie-director" name="director" value="<?= e($formData['director']) ?>" required maxlength="150">
        </div>

        <div class="form-group">
            <label>Gatunki</label>
            <div class="checkbox-grid">
                <?php foreach ($allGenres as $genre): ?>
                    <label class="checkbox-label">
                        <input
                            type="checkbox"
                            name="genre_ids[]"
                            value="<?= (int) $genre['id'] ?>"
                            <?= in_array((int) $genre['id'], $formData['genre_ids'], true) ? 'checked' : '' ?>
                        >
                        <?= e($genre['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="movie-poster-url">Adres URL plakatu (opcjonalny, http/https)</label>
            <input
                type="url"
                id="movie-poster-url"
                name="poster_url"
                value="<?= e($formData['poster_url']) ?>"
                maxlength="500"
                placeholder="https://example.com/plakat.jpg"
                class="js-url-preview-input"
                data-preview-target="poster-preview"
                data-default-src="<?= e(base_url('assets/img/default-poster.png')) ?>"
            >
            <div class="url-preview-box">
                <img
                    id="poster-preview"
                    src="<?= e(poster_url($formData['poster_url'])) ?>"
                    alt="Podgląd plakatu"
                    class="preview-poster"
                >
            </div>
        </div>

        <div class="form-group">
            <label for="movie-description">Opis fabuły *</label>
            <textarea id="movie-description" name="description" rows="5" required><?= e($formData['description']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary"><?= $isEditMode ? 'Zapisz zmiany' : 'Dodaj film' ?></button>
        <?php if ($isEditMode): ?>
            <a href="<?= e(base_url('admin/movies.php')) ?>" class="btn btn-secondary">Anuluj</a>
        <?php endif; ?>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tytuł</th>
                <th>Rok</th>
                <th>Reżyser</th>
                <th>Gatunki</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movies as $m): ?>
                <tr>
                    <td><?= (int) $m['id'] ?></td>
                    <td><a href="<?= e(base_url('movie.php?id=' . (int) $m['id'])) ?>"><?= e($m['title']) ?></a></td>
                    <td><?= (int) $m['release_year'] ?></td>
                    <td><?= e($m['director']) ?></td>
                    <td><?= e($m['genres_list'] ?? '—') ?></td>
                    <td class="table-actions">
                        <a href="<?= e(base_url('admin/movies.php?edit=' . (int) $m['id'])) ?>" class="btn btn-secondary">Edytuj</a>
                        <a href="<?= e(base_url('admin/cast.php?movie_id=' . (int) $m['id'])) ?>" class="btn btn-secondary">Obsada</a>
                        <form method="post" action="<?= e(base_url('admin/movies.php')) ?>" class="inline-delete-form js-confirm" data-confirm="Czy na pewno chcesz usunąć film <?= e($m['title']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <button type="submit" class="btn btn-danger">Usuń</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
