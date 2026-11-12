<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    destroy_session();
    session_start();
    set_flash('success', 'Zostałeś wylogowany.');
    redirect('index.php');
}

// Bezpieczny fallback przy wejściu przez GET (ochrona przed wylogowaniem przez CSRF GET)
if (!is_logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Wylogowanie — Baza filmów';
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <h1>Wylogowanie</h1>
    <p>Czy na pewno chcesz się wylogować?</p>
    <form method="post" action="<?= e(base_url('logout.php')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary">Tak, wyloguj mnie</button>
        <a href="<?= e(base_url('index.php')) ?>" class="btn btn-secondary">Anuluj</a>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
