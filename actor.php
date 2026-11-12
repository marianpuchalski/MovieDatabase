<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$actorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$actorId || $actorId <= 0) {
    http_response_code(404);
    $pageTitle = 'Nie znaleziono aktora — Baza filmów';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="empty-state"><h1>404 — Nie znaleziono aktora</h1><p>Podany adres profilu jest nieprawidłowy.</p><a class="btn btn-primary" href="' . e(base_url('actors.php')) . '">Wróć do katalogu aktorów</a></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pdo = get_db();

$stmt = $pdo->prepare(
    'SELECT id, first_name, last_name, birth_date, country, biography, photo_url
     FROM actors
     WHERE id = :id
     LIMIT 1'
);
$stmt->execute([':id' => $actorId]);
$actor = $stmt->fetch();

if (!$actor) {
    http_response_code(404);
    $pageTitle = 'Nie znaleziono aktora — Baza filmów';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="empty-state"><h1>404 — Aktor nie istnieje</h1><p>Wybrany aktor nie został odnaleziony w bazie.</p><a class="btn btn-primary" href="' . e(base_url('actors.php')) . '">Wróć do katalogu aktorów</a></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$filmographyStmt = $pdo->prepare(
    'SELECT m.id AS movie_id, m.title, m.release_year, m.poster_url, mc.character_name
     FROM movie_cast mc
     INNER JOIN movies m ON m.id = mc.movie_id
     WHERE mc.actor_id = :actor_id
     ORDER BY m.release_year DESC, m.title ASC'
);
$filmographyStmt->execute([':actor_id' => $actorId]);
$filmography = $filmographyStmt->fetchAll();

$fullName = $actor['first_name'] . ' ' . $actor['last_name'];
$pageTitle = $fullName . ' — Baza filmów';
require_once __DIR__ . '/includes/header.php';
?>

<article class="actor-detail">
    <div class="actor-detail-header">
        <div class="actor-detail-photo-wrap">
            <img
                src="<?= e(actor_photo_url($actor['photo_url'])) ?>"
                alt="<?= e($fullName) ?>"
                class="actor-detail-photo js-img-fallback"
                data-fallback="<?= e(base_url('assets/img/default-actor.png')) ?>"
            >
        </div>

        <div class="actor-detail-info">
            <h1><?= e($fullName) ?></h1>
            <ul class="actor-meta-list">
                <li>
                    <strong>Data urodzenia:</strong>
                    <?= !empty($actor['birth_date']) ? e(date('d.m.Y', strtotime($actor['birth_date']))) : 'Brak danych' ?>
                </li>
                <li>
                    <strong>Kraj pochodzenia:</strong>
                    <?= !empty($actor['country']) ? e($actor['country']) : 'Brak danych' ?>
                </li>
            </ul>

            <div class="actor-biography">
                <h2>Biografia</h2>
                <?php if (!empty($actor['biography'])): ?>
                    <p><?= nl2br(e($actor['biography'])) ?></p>
                <?php else: ?>
                    <p class="muted">Brak biografii dla tego aktora.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="actor-filmography">
        <h2>Filmografia</h2>
        <?php if ($filmography === []): ?>
            <p class="muted">Aktor nie ma jeszcze przypisanych filmów w bazie.</p>
        <?php else: ?>
            <div class="filmography-list">
                <?php foreach ($filmography as $role): ?>
                    <div class="filmography-item">
                        <a href="<?= e(base_url('movie.php?id=' . (int) $role['movie_id'])) ?>" class="filmography-thumb-link">
                            <img
                                src="<?= e(poster_url($role['poster_url'])) ?>"
                                alt="Plakat <?= e($role['title']) ?>"
                                class="filmography-thumb js-img-fallback"
                                data-fallback="<?= e(base_url('assets/img/default-poster.png')) ?>"
                                loading="lazy"
                            >
                        </a>
                        <div class="filmography-info">
                            <a href="<?= e(base_url('movie.php?id=' . (int) $role['movie_id'])) ?>" class="filmography-title">
                                <?= e($role['title']) ?> (<?= (int) $role['release_year'] ?>)
                            </a>
                            <span class="filmography-role">Rola: <strong><?= e($role['character_name']) ?></strong></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</article>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
