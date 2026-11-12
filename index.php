<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$genres = get_all_genres();
$years = get_movie_release_years();

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'genre' => (string) ($_GET['genre'] ?? ''),
    'year' => (string) ($_GET['year'] ?? ''),
    'sort' => (string) ($_GET['sort'] ?? 'newest'),
    'page' => (string) ($_GET['page'] ?? '1'),
];

$result = search_movies($filters);
$movies = $result['movies'];
$pagination = $result['pagination'];

$pageTitle = 'Katalog filmów — Baza filmów';
$extraScripts = ['assets/js/filter.js'];

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <h1>Katalog filmów</h1>
    <p class="subtitle">Przeglądaj filmy, sprawdzaj obsadę i dodawaj własne oceny.</p>
</section>

<section class="filters-panel">
    <form id="movies-filter-form" method="get" action="<?= e(base_url('index.php')) ?>" class="filter-form">
        <div class="filter-group">
            <label for="filter-q">Szukaj tytułu</label>
            <input type="text" id="filter-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Wpisz tytuł filmu...">
        </div>

        <div class="filter-group">
            <label for="filter-genre">Gatunek</label>
            <select id="filter-genre" name="genre">
                <option value="">Wszystkie gatunki</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?= (int) $genre['id'] ?>" <?= ((string) $genre['id'] === $filters['genre']) ? 'selected' : '' ?>>
                        <?= e($genre['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="filter-year">Rok produkcji</label>
            <select id="filter-year" name="year">
                <option value="">Wszystkie lata</option>
                <?php foreach ($years as $yr): ?>
                    <option value="<?= $yr ?>" <?= ((string) $yr === $filters['year']) ? 'selected' : '' ?>>
                        <?= $yr ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="filter-sort">Sortowanie</label>
            <select id="filter-sort" name="sort">
                <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Najnowsze</option>
                <option value="rating_desc" <?= $filters['sort'] === 'rating_desc' ? 'selected' : '' ?>>Najwyżej oceniane</option>
                <option value="title_asc" <?= $filters['sort'] === 'title_asc' ? 'selected' : '' ?>>Alfabetycznie (A–Z)</option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Filtruj</button>
            <a href="<?= e(base_url('index.php')) ?>" id="filter-reset" class="btn btn-secondary">Wyczyść</a>
        </div>
    </form>
</section>

<section id="movies-results" data-api-url="<?= e(base_url('api/movies_filter.php')) ?>" data-default-poster="<?= e(base_url('assets/img/default-poster.png')) ?>">
    <?php if ($movies === []): ?>
        <div class="empty-state">
            <p>Nie znaleziono filmów spełniających podane kryteria.</p>
        </div>
    <?php else: ?>
        <div class="movies-grid" id="movies-grid">
            <?php foreach ($movies as $movie): ?>
                <article class="movie-card">
                    <a href="<?= e(base_url('movie.php?id=' . $movie['id'])) ?>" class="movie-card-poster-link">
                        <img
                            src="<?= e($movie['resolved_poster_url']) ?>"
                            alt="Plakat filmu <?= e($movie['title']) ?>"
                            class="movie-card-poster js-img-fallback"
                            data-fallback="<?= e(base_url('assets/img/default-poster.png')) ?>"
                            loading="lazy"
                        >
                    </a>
                    <div class="movie-card-body">
                        <div class="movie-card-rating">
                            <span class="star-icon">&#9733;</span>
                            <strong><?= e(format_rating($movie['avg_rating'], $movie['review_count'])) ?></strong>
                            <span class="review-count">(<?= (int) $movie['review_count'] ?>)</span>
                        </div>
                        <h2 class="movie-card-title">
                            <a href="<?= e(base_url('movie.php?id=' . $movie['id'])) ?>"><?= e($movie['title']) ?></a>
                        </h2>
                        <p class="movie-card-meta">
                            <?= (int) $movie['release_year'] ?> &bull; <?= e(format_duration((int) $movie['duration_minutes'])) ?>
                        </p>
                        <?php if ($movie['genres'] !== []): ?>
                            <div class="movie-card-genres">
                                <?php foreach ($movie['genres'] as $g): ?>
                                    <span class="badge"><?= e($g['name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <nav class="pagination" id="movies-pagination" aria-label="Paginacja filmów">
                <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                    <?php
                    $query = http_build_query([
                        'q' => $filters['q'],
                        'genre' => $filters['genre'],
                        'year' => $filters['year'],
                        'sort' => $filters['sort'],
                        'page' => $p,
                    ]);
                    ?>
                    <a
                        href="<?= e(base_url('index.php?' . $query)) ?>"
                        data-page="<?= $p ?>"
                        class="page-link <?= $p === $pagination['current_page'] ? 'active' : '' ?>"
                    >
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php else: ?>
            <nav class="pagination" id="movies-pagination" aria-label="Paginacja filmów"></nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
