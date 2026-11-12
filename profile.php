<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$user = require_login();

$stmt = get_db()->prepare(
    'SELECT r.id, r.movie_id, r.rating, r.comment, r.created_at, r.updated_at,
            m.title AS movie_title, m.release_year, m.poster_url
     FROM reviews r
     INNER JOIN movies m ON m.id = r.movie_id
     WHERE r.user_id = :user_id
     ORDER BY r.updated_at DESC, r.created_at DESC'
);
$stmt->execute([':user_id' => (int) $user['id']]);
$myReviews = $stmt->fetchAll();

$pageTitle = 'Mój profil — Baza filmów';
require_once __DIR__ . '/includes/header.php';
?>

<section class="profile-section">
    <h1>Profil użytkownika</h1>

    <div class="profile-summary card">
        <p><strong>Nazwa użytkownika:</strong> <?= e($user['username']) ?></p>
        <p><strong>Adres e-mail:</strong> <?= e($user['email']) ?></p>
        <p><strong>Rola:</strong> <?= $user['role'] === 'admin' ? 'Administrator' : 'Użytkownik' ?></p>
        <p><strong>Data rejestracji:</strong> <?= e(date('d.m.Y H:i', strtotime($user['created_at']))) ?></p>
        <p><strong>Liczba wystawionych recenzji:</strong> <?= count($myReviews) ?></p>
    </div>

    <section class="profile-reviews">
        <h2>Twoje recenzje i oceny</h2>
        <?php if ($myReviews === []): ?>
            <p class="muted">Nie dodałeś jeszcze żadnej oceny ani recenzji.</p>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($myReviews as $rev): ?>
                    <article class="review-card">
                        <header class="review-card-header">
                            <a href="<?= e(base_url('movie.php?id=' . (int) $rev['movie_id'])) ?>">
                                <strong><?= e($rev['movie_title']) ?> (<?= (int) $rev['release_year'] ?>)</strong>
                            </a>
                            <span class="review-stars"><?= (int) $rev['rating'] ?>/5 &#9733;</span>
                            <time><?= e(date('d.m.Y H:i', strtotime($rev['updated_at']))) ?></time>
                        </header>
                        <?php if ($rev['comment'] !== null && trim($rev['comment']) !== ''): ?>
                            <p class="review-comment"><?= nl2br(e($rev['comment'])) ?></p>
                        <?php else: ?>
                            <p class="review-comment muted"><em>Sama ocena bez tekstu recenzji.</em></p>
                        <?php endif; ?>
                        <div class="review-actions">
                            <a href="<?= e(base_url('movie.php?id=' . (int) $rev['movie_id'] . '#reviews')) ?>" class="btn btn-secondary">Edytuj na stronie filmu</a>
                            <form method="post" action="<?= e(base_url('review_action.php')) ?>" class="inline-delete-form js-confirm" data-confirm="Czy na pewno chcesz usunąć tę recenzję?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="review_id" value="<?= (int) $rev['id'] ?>">
                                <input type="hidden" name="movie_id" value="<?= (int) $rev['movie_id'] ?>">
                                <input type="hidden" name="redirect_to" value="profile">
                                <button type="submit" class="btn btn-danger">Usuń</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
