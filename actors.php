<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pdo = get_db();
$search = trim((string) ($_GET['q'] ?? ''));
$requestedPage = isset($_GET['page']) && ctype_digit((string) $_GET['page']) ? (int) $_GET['page'] : 1;

$whereSql = '';
$params = [];

if ($search !== '') {
    $likeValue = '%' . escape_like($search) . '%';
    $whereSql = "WHERE a.first_name LIKE :q1 ESCAPE '\\\\'
                 OR a.last_name LIKE :q2 ESCAPE '\\\\'
                 OR CONCAT(a.first_name, ' ', a.last_name) LIKE :q3 ESCAPE '\\\\'";
    $params = [
        ':q1' => $likeValue,
        ':q2' => $likeValue,
        ':q3' => $likeValue,
    ];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM actors a $whereSql");
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();

$pagination = calculate_pagination($totalItems, $requestedPage, ITEMS_PER_PAGE);

$sql = "
    SELECT a.id, a.first_name, a.last_name, a.country, a.photo_url,
           COUNT(DISTINCT mc.movie_id) AS movies_count
    FROM actors a
    LEFT JOIN movie_cast mc ON mc.actor_id = a.id
    $whereSql
    GROUP BY a.id, a.first_name, a.last_name, a.country, a.photo_url
    ORDER BY a.last_name ASC, a.first_name ASC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$actors = $stmt->fetchAll();

$pageTitle = 'Katalog aktorów — Baza filmów';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <h1>Katalog aktorów</h1>
    <p class="subtitle">Poznaj obsadę i filmografię aktorów z naszej bazy.</p>
</section>

<section class="filters-panel">
    <form method="get" action="<?= e(base_url('actors.php')) ?>" class="filter-form">
        <div class="filter-group">
            <label for="actor-q">Szukaj aktora</label>
            <input type="text" id="actor-q" name="q" value="<?= e($search) ?>" placeholder="Imię lub nazwisko...">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Szukaj</button>
            <a href="<?= e(base_url('actors.php')) ?>" class="btn btn-secondary">Wyczyść</a>
        </div>
    </form>
</section>

<?php if ($actors === []): ?>
    <div class="empty-state">
        <p>Nie znaleziono aktorów spełniających podane kryteria.</p>
    </div>
<?php else: ?>
    <div class="actors-grid">
        <?php foreach ($actors as $actor): ?>
            <article class="actor-card">
                <a href="<?= e(base_url('actor.php?id=' . (int) $actor['id'])) ?>">
                    <img
                        src="<?= e(actor_photo_url($actor['photo_url'])) ?>"
                        alt="<?= e($actor['first_name'] . ' ' . $actor['last_name']) ?>"
                        class="actor-card-photo js-img-fallback"
                        data-fallback="<?= e(base_url('assets/img/default-actor.png')) ?>"
                        loading="lazy"
                    >
                </a>
                <div class="actor-card-body">
                    <h2>
                        <a href="<?= e(base_url('actor.php?id=' . (int) $actor['id'])) ?>">
                            <?= e($actor['first_name'] . ' ' . $actor['last_name']) ?>
                        </a>
                    </h2>
                    <?php if (!empty($actor['country'])): ?>
                        <p class="muted"><?= e($actor['country']) ?></p>
                    <?php endif; ?>
                    <p class="actor-movies-count">Filmy w bazie: <?= (int) $actor['movies_count'] ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <nav class="pagination" aria-label="Paginacja aktorów">
            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                <?php $query = http_build_query(['q' => $search, 'page' => $p]); ?>
                <a href="<?= e(base_url('actors.php?' . $query)) ?>" class="page-link <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
