<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

$pdo = get_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create' || $action === 'update') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $genreId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Nazwa gatunku jest wymagana i nie może przekraczać 100 znaków.';
        }

        if ($errors === []) {
            try {
                if ($action === 'create') {
                    $stmt = $pdo->prepare('INSERT INTO genres (name) VALUES (:name)');
                    $stmt->execute([':name' => $name]);
                    set_flash('success', 'Dodano nowy gatunek.');
                } else {
                    if ($genreId <= 0) {
                        set_flash('error', 'Nieprawidłowy identyfikator gatunku.');
                        redirect('admin/genres.php');
                    }
                    $stmt = $pdo->prepare('UPDATE genres SET name = :name WHERE id = :id');
                    $stmt->execute([':name' => $name, ':id' => $genreId]);
                    set_flash('success', 'Zaktualizowano gatunek.');
                }
                redirect('admin/genres.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors[] = 'Gatunek o podanej nazwie już istnieje.';
                } else {
                    error_log('Błąd CRUD gatunków: ' . $e->getMessage());
                    $errors[] = 'Wystąpił błąd podczas zapisu gatunku.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $genreId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($genreId > 0) {
            $stmt = $pdo->prepare('DELETE FROM genres WHERE id = :id');
            $stmt->execute([':id' => $genreId]);
            set_flash('success', 'Usunięto gatunek.');
        }
        redirect('admin/genres.php');
    }
}

$editGenre = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId && $editId > 0) {
    $stmt = $pdo->prepare('SELECT id, name FROM genres WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $editGenre = $stmt->fetch() ?: null;
}

$genres = $pdo->query(
    'SELECT g.id, g.name, COUNT(mg.movie_id) AS movies_count
     FROM genres g
     LEFT JOIN movie_genres mg ON mg.genre_id = g.id
     GROUP BY g.id, g.name
     ORDER BY g.name ASC'
)->fetchAll();

$pageTitle = 'Zarządzanie gatunkami — Panel Administratora';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Zarządzanie gatunkami</h1>
    <p><a href="<?= e(base_url('admin/index.php')) ?>">&larr; Powrót do pulpitu</a></p>

    <?php if ($errors !== []): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('admin/genres.php')) ?>" class="admin-form js-validate-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editGenre ? 'update' : 'create' ?>">
        <?php if ($editGenre): ?>
            <input type="hidden" name="id" value="<?= (int) $editGenre['id'] ?>">
        <?php endif; ?>

        <h2><?= $editGenre ? 'Edytuj gatunek' : 'Dodaj gatunek' ?></h2>
        <div class="form-group">
            <label for="genre-name">Nazwa gatunku *</label>
            <input type="text" id="genre-name" name="name" value="<?= e($editGenre['name'] ?? '') ?>" required maxlength="100">
        </div>
        <button type="submit" class="btn btn-primary"><?= $editGenre ? 'Zapisz zmiany' : 'Dodaj gatunek' ?></button>
        <?php if ($editGenre): ?>
            <a href="<?= e(base_url('admin/genres.php')) ?>" class="btn btn-secondary">Anuluj</a>
        <?php endif; ?>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nazwa</th>
                <th>Liczba filmów</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($genres as $g): ?>
                <tr>
                    <td><?= (int) $g['id'] ?></td>
                    <td><?= e($g['name']) ?></td>
                    <td><?= (int) $g['movies_count'] ?></td>
                    <td class="table-actions">
                        <a href="<?= e(base_url('admin/genres.php?edit=' . (int) $g['id'])) ?>" class="btn btn-secondary">Edytuj</a>
                        <form method="post" action="<?= e(base_url('admin/genres.php')) ?>" class="inline-delete-form js-confirm" data-confirm="Czy na pewno chcesz usunąć gatunek <?= e($g['name']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                            <button type="submit" class="btn btn-danger">Usuń</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
