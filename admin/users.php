<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
$currentAdmin = require_admin();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $targetUserId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

    if ($targetUserId <= 0) {
        set_flash('error', 'Nieprawidłowy identyfikator użytkownika.');
        redirect('admin/users.php');
    }

    // Ochrona przed zablokowaniem lub usunięciem własnego konta administratora
    if ($targetUserId === (int) $currentAdmin['id']) {
        set_flash('error', 'Nie możesz zablokować ani usunąć własnego konta administratora.');
        redirect('admin/users.php');
    }

    if ($action === 'toggle_block') {
        $stmt = $pdo->prepare('SELECT id, is_blocked FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $targetUserId]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            set_flash('error', 'Użytkownik nie istnieje.');
        } else {
            $newStatus = ((int) $targetUser['is_blocked'] === 1) ? 0 : 1;
            $update = $pdo->prepare('UPDATE users SET is_blocked = :status WHERE id = :id');
            $update->execute([
                ':status' => $newStatus,
                ':id' => $targetUserId,
            ]);
            set_flash('success', $newStatus === 1 ? 'Konto użytkownika zostało zablokowane.' : 'Konto użytkownika zostało odblokowane.');
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $targetUserId]);
        if ($stmt->rowCount() > 0) {
            set_flash('success', 'Konto użytkownika zostało usunięte.');
        } else {
            set_flash('error', 'Nie znaleziono konta użytkownika.');
        }
    }

    redirect('admin/users.php');
}

$users = $pdo->query(
    'SELECT u.id, u.username, u.email, u.role, u.is_blocked, u.created_at,
            COUNT(r.id) AS reviews_count
     FROM users u
     LEFT JOIN reviews r ON r.user_id = u.id
     GROUP BY u.id, u.username, u.email, u.role, u.is_blocked, u.created_at
     ORDER BY u.created_at DESC'
)->fetchAll();

$pageTitle = 'Zarządzanie użytkownikami — Panel Administratora';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-section">
    <h1>Zarządzanie użytkownikami</h1>
    <p><a href="<?= e(base_url('admin/index.php')) ?>">&larr; Powrót do pulpitu</a></p>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Login</th>
                <th>E-mail</th>
                <th>Rola</th>
                <th>Status</th>
                <th>Recenzje</th>
                <th>Data rejestracji</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <?php $isSelf = ((int) $u['id'] === (int) $currentAdmin['id']); ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['role']) ?></td>
                    <td><?= (int) $u['is_blocked'] === 1 ? '<span class="status-blocked">Zablokowany</span>' : '<span class="status-active">Aktywny</span>' ?></td>
                    <td><?= (int) $u['reviews_count'] ?></td>
                    <td><?= e(date('d.m.Y', strtotime($u['created_at']))) ?></td>
                    <td class="table-actions">
                        <?php if ($isSelf): ?>
                            <span class="muted">Twoje konto</span>
                        <?php else: ?>
                            <form method="post" action="<?= e(base_url('admin/users.php')) ?>" class="inline-delete-form js-confirm" data-confirm="<?= (int) $u['is_blocked'] === 1 ? 'Odblokować konto ' . e($u['username']) . '?' : 'Zablokować konto ' . e($u['username']) . '?' ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_block">
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <button type="submit" class="btn btn-secondary">
                                    <?= (int) $u['is_blocked'] === 1 ? 'Odblokuj' : 'Zablokuj' ?>
                                </button>
                            </form>

                            <form method="post" action="<?= e(base_url('admin/users.php')) ?>" class="inline-delete-form js-confirm" data-confirm="Czy na pewno chcesz trwale usunąć konto <?= e($u['username']) ?>?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <button type="submit" class="btn btn-danger">Usuń</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
