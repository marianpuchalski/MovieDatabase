<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $reviewId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
        if ($reviewId > 0) {
            $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = :id');
            $stmt->execute([':id' => $reviewId]);
            set_flash('success', 'Recenzja została usunięta przez administratora.');
        }
    }

    redirect('admin/reviews.php');
}

$reviews = $pdo->query(
    'SELECT r.id, r.movie_id, r.rating, r.comment, r.created_at,
            m.title AS movie_title, u.username
     FROM reviews r
     INNER JOIN movies m ON m.id = r.movie_id
     INNER JOIN users u ON u.id = r.user_id
     ORDER BY r.created_at DESC'
)->fetchAll();

$pageTitle = 'Moderacja recenzji — Panel Administratora';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Moderacja recenzji</h1>
    <p><a href="<?= e(base_url('admin/index.php')) ?>">&larr; Powrót do pulpitu</a></p>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Film</th>
                <th>Użytkownik</th>
                <th>Ocena</th>
                <th>Treść</th>
                <th>Data</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($reviews === []): ?>
                <tr><td colspan="7">Brak recenzji w bazie.</td></tr>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                    <tr>
                        <td><?= (int) $rev['id'] ?></td>
                        <td>
                            <a href="<?= e(base_url('movie.php?id=' . (int) $rev['movie_id'])) ?>">
                                <?= e($rev['movie_title']) ?>
                            </a>
                        </td>
                        <td><?= e($rev['username']) ?></td>
                        <td><?= (int) $rev['rating'] ?>/5</td>
                        <td><?= $rev['comment'] !== null && trim($rev['comment']) !== '' ? e($rev['comment']) : '<em>Brak tekstu</em>' ?></td>
                        <td><?= e(date('d.m.Y H:i', strtotime($rev['created_at']))) ?></td>
                        <td class="table-actions">
                            <form method="post" action="<?= e(base_url('admin/reviews.php')) ?>" class="inline-delete-form js-confirm" data-confirm="Czy na pewno chcesz usunąć tę recenzję?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $rev['id'] ?>">
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
