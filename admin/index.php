<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

$pdo = get_db();

$stats = [
    'movies' => (int) $pdo->query('SELECT COUNT(*) FROM movies')->fetchColumn(),
    'actors' => (int) $pdo->query('SELECT COUNT(*) FROM actors')->fetchColumn(),
    'genres' => (int) $pdo->query('SELECT COUNT(*) FROM genres')->fetchColumn(),
    'reviews' => (int) $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'blocked_users' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_blocked = 1')->fetchColumn(),
];

$pageTitle = 'Panel Administratora — Baza filmów';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="admin-dashboard">
    <h1>Panel Administratora</h1>

    <nav class="admin-nav">
        <a href="<?= e(base_url('admin/movies.php')) ?>" class="btn btn-primary">Filmy</a>
        <a href="<?= e(base_url('admin/genres.php')) ?>" class="btn btn-secondary">Gatunki</a>
        <a href="<?= e(base_url('admin/actors.php')) ?>" class="btn btn-secondary">Aktorzy</a>
        <a href="<?= e(base_url('admin/cast.php')) ?>" class="btn btn-secondary">Obsada filmów</a>
        <a href="<?= e(base_url('admin/reviews.php')) ?>" class="btn btn-secondary">Recenzje</a>
        <a href="<?= e(base_url('admin/users.php')) ?>" class="btn btn-secondary">Użytkownicy</a>
    </nav>

    <div class="stats-grid">
        <div class="stat-card"><h2><?= $stats['movies'] ?></h2><p>Filmów</p></div>
        <div class="stat-card"><h2><?= $stats['actors'] ?></h2><p>Aktorów</p></div>
        <div class="stat-card"><h2><?= $stats['genres'] ?></h2><p>Gatunków</p></div>
        <div class="stat-card"><h2><?= $stats['reviews'] ?></h2><p>Recenzji</p></div>
        <div class="stat-card"><h2><?= $stats['users'] ?></h2><p>Użytkowników</p></div>
        <div class="stat-card"><h2><?= $stats['blocked_users'] ?></h2><p>Zablokowanych kont</p></div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
