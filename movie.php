<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$movieId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$movieId || $movieId <= 0) {
    http_response_code(404);
    $pageTitle = 'Nie znaleziono filmu — Baza filmów';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="empty-state"><h1>404 — Nie znaleziono filmu</h1><p>Podany adres filmu jest nieprawidłowy.</p><a class="btn btn-primary" href="' . e(base_url('index.php')) . '">Wróć do katalogu</a></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pdo = get_db();

// Pobranie danych filmu wraz ze średnią ocen i liczbą recenzji
$stmt = $pdo->prepare(
    'SELECT
        m.id, m.title, m.release_year, m.director, m.duration_minutes, m.description, m.poster_url,
        COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
        COUNT(r.id) AS review_count
     FROM movies m
     LEFT JOIN reviews r ON r.movie_id = m.id
     WHERE m.id = :id
     GROUP BY m.id, m.title, m.release_year, m.director, m.duration_minutes, m.description, m.poster_url
     LIMIT 1'
);
$stmt->execute([':id' => $movieId]);
$movie = $stmt->fetch();

if (!$movie) {
    http_response_code(404);
    $pageTitle = 'Nie znaleziono filmu — Baza filmów';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="empty-state"><h1>404 — Film nie istnieje</h1><p>Wybrany film nie został odnaleziony w bazie.</p><a class="btn btn-primary" href="' . e(base_url('index.php')) . '">Wróć do katalogu</a></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Gatunki filmu
$genreStmt = $pdo->prepare(
    'SELECT g.id, g.name
     FROM movie_genres mg
     INNER JOIN genres g ON g.id = mg.genre_id
     WHERE mg.movie_id = :movie_id
     ORDER BY g.name ASC'
);
$genreStmt->execute([':movie_id' => $movieId]);
$genres = $genreStmt->fetchAll();

// Obsada filmu
$castStmt = $pdo->prepare(
    'SELECT mc.id AS cast_id, mc.character_name, a.id AS actor_id, a.first_name, a.last_name, a.photo_url
     FROM movie_cast mc
     INNER JOIN actors a ON a.id = mc.actor_id
     WHERE mc.movie_id = :movie_id
     ORDER BY mc.id ASC'
);
$castStmt->execute([':movie_id' => $movieId]);
$cast = $castStmt->fetchAll();

// Recenzje filmu
$reviewStmt = $pdo->prepare(
    'SELECT r.id, r.user_id, r.rating, r.comment, r.created_at, r.updated_at, u.username
     FROM reviews r
     INNER JOIN users u ON u.id = r.user_id
     WHERE r.movie_id = :movie_id
     ORDER BY r.created_at DESC'
);
$reviewStmt->execute([':movie_id' => $movieId]);
$reviews = $reviewStmt->fetchAll();

// Sprawdzenie, czy zalogowany użytkownik ocenił już ten film
$currentUser = current_user();
$userReview = null;
if ($currentUser !== null) {
    foreach ($reviews as $rev) {
        if ((int) $rev['user_id'] === (int) $currentUser['id']) {
            $userReview = $rev;
            break;
        }
    }
}

$pageTitle = $movie['title'] . ' (' . $movie['release_year'] . ') — Baza filmów';
$extraScripts = ['assets/js/rating.js'];

require_once __DIR__ . '/includes/header.php';
?>

<article class="movie-detail">
    <div class="movie-detail-header">
        <div class="movie-detail-poster-wrap">
            <img
                src="<?= e(poster_url($movie['poster_url'])) ?>"
                alt="Plakat filmu <?= e($movie['title']) ?>"
                class="movie-detail-poster js-img-fallback"
                data-fallback="<?= e(base_url('assets/img/default-poster.png')) ?>"
            >
        </div>

        <div class="movie-detail-info">
            <h1><?= e($movie['title']) ?> <span class="year-muted">(<?= (int) $movie['release_year'] ?>)</span></h1>

            <div class="movie-detail-rating">
                <span class="star-icon">&#9733;</span>
                <strong><?= e(format_rating($movie['avg_rating'], (int) $movie['review_count'])) ?></strong>
                <?php if ((int) $movie['review_count'] > 0): ?>
                    <span>/ 5</span>
                <?php endif; ?>
                <span class="review-count">(Liczba ocen: <?= (int) $movie['review_count'] ?>)</span>
            </div>

            <ul class="movie-meta-list">
                <li><strong>Reżyser:</strong> <?= e($movie['director']) ?></li>
                <li><strong>Czas trwania:</strong> <?= e(format_duration((int) $movie['duration_minutes'])) ?></li>
                <li>
                    <strong>Gatunki:</strong>
                    <?php if ($genres === []): ?>
                        <span>Brak przypisanych gatunków</span>
                    <?php else: ?>
                        <?php foreach ($genres as $g): ?>
                            <a href="<?= e(base_url('index.php?genre=' . (int) $g['id'])) ?>" class="badge"><?= e($g['name']) ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </li>
            </ul>

            <div class="movie-description">
                <h2>Opis fabuły</h2>
                <p><?= nl2br(e($movie['description'])) ?></p>
            </div>
        </div>
    </div>

    <section class="movie-cast-section">
        <h2>Obsada</h2>
        <?php if ($cast === []): ?>
            <p class="muted">Brak informacji o obsadzie tego filmu.</p>
        <?php else: ?>
            <div class="cast-grid">
                <?php foreach ($cast as $member): ?>
                    <div class="cast-card">
                        <a href="<?= e(base_url('actor.php?id=' . (int) $member['actor_id'])) ?>">
                            <img
                                src="<?= e(actor_photo_url($member['photo_url'])) ?>"
                                alt="<?= e($member['first_name'] . ' ' . $member['last_name']) ?>"
                                class="cast-photo js-img-fallback"
                                data-fallback="<?= e(base_url('assets/img/default-actor.png')) ?>"
                                loading="lazy"
                            >
                        </a>
                        <div class="cast-info">
                            <a href="<?= e(base_url('actor.php?id=' . (int) $member['actor_id'])) ?>" class="cast-actor-name">
                                <?= e($member['first_name'] . ' ' . $member['last_name']) ?>
                            </a>
                            <span class="cast-character">jako <?= e($member['character_name']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="movie-reviews-section" id="reviews">
        <h2>Recenzje i oceny</h2>

        <?php if ($currentUser === null): ?>
            <div class="info-box">
                <p><a href="<?= e(base_url('login.php')) ?>">Zaloguj się</a>, aby dodać ocenę (1–5 gwiazdek) i opcjonalną recenzję.</p>
            </div>
        <?php elseif ($userReview === null): ?>
            <form method="post" action="<?= e(base_url('review_action.php')) ?>" class="review-form js-validate-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="movie_id" value="<?= (int) $movie['id'] ?>">

                <h3>Dodaj swoją ocenę</h3>

                <div class="form-group">
                    <label>Twoja ocena (1–5 gwiazdek) *</label>
                    <div class="star-rating-input" data-rating-widget>
                        <input type="hidden" name="rating" value="5" required>
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <button type="button" class="star-btn" data-value="<?= $s ?>" aria-label="Oceń na <?= $s ?>">&#9733;</button>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="review-comment">Treść recenzji (opcjonalna)</label>
                    <textarea id="review-comment" name="comment" rows="4" maxlength="2000" placeholder="Napisz kilka słów o filmie (możesz też wystawić samą ocenę)..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Zapisz ocenę</button>
            </form>
        <?php else: ?>
            <div class="user-review-panel">
                <h3>Twoja recenzja</h3>
                <form method="post" action="<?= e(base_url('review_action.php')) ?>" class="review-form js-validate-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="movie_id" value="<?= (int) $movie['id'] ?>">
                    <input type="hidden" name="review_id" value="<?= (int) $userReview['id'] ?>">

                    <div class="form-group">
                        <label>Zmień ocenę (1–5 gwiazdek) *</label>
                        <div class="star-rating-input" data-rating-widget>
                            <input type="hidden" name="rating" value="<?= (int) $userReview['rating'] ?>" required>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <button type="button" class="star-btn <?= $s <= (int) $userReview['rating'] ? 'selected' : '' ?>" data-value="<?= $s ?>" aria-label="Oceń na <?= $s ?>">&#9733;</button>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit-review-comment">Treść recenzji (opcjonalna)</label>
                        <textarea id="edit-review-comment" name="comment" rows="4" maxlength="2000"><?= e($userReview['comment'] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions-inline">
                        <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                    </div>
                </form>

                <form method="post" action="<?= e(base_url('review_action.php')) ?>" class="inline-delete-form js-confirm" data-confirm="Czy na pewno chcesz usunąć swoją recenzję?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="movie_id" value="<?= (int) $movie['id'] ?>">
                    <input type="hidden" name="review_id" value="<?= (int) $userReview['id'] ?>">
                    <button type="submit" class="btn btn-danger">Usuń moją recenzję</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="reviews-list">
            <?php if ($reviews === []): ?>
                <p class="muted">Ten film nie ma jeszcze żadnych recenzji.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <article class="review-card">
                        <header class="review-card-header">
                            <strong><?= e($review['username']) ?></strong>
                            <span class="review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="<?= $i <= (int) $review['rating'] ? 'star-filled' : 'star-empty' ?>">&#9733;</span>
                                <?php endfor; ?>
                                (<?= (int) $review['rating'] ?>/5)
                            </span>
                            <time datetime="<?= e($review['created_at']) ?>"><?= e(date('d.m.Y H:i', strtotime($review['created_at']))) ?></time>
                        </header>
                        <?php if ($review['comment'] !== null && trim($review['comment']) !== ''): ?>
                            <p class="review-comment"><?= nl2br(e($review['comment'])) ?></p>
                        <?php else: ?>
                            <p class="review-comment muted"><em>Użytkownik wystawił ocenę bez tekstu recenzji.</em></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</article>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
