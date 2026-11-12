<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Niedozwolona metoda HTTP.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$allowedSorts = ['newest', 'rating_desc', 'title_asc'];
$sort = (string) ($_GET['sort'] ?? 'newest');
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}

$filters = [
    'q' => $_GET['q'] ?? '',
    'genre' => $_GET['genre'] ?? '',
    'year' => $_GET['year'] ?? '',
    'sort' => $sort,
    'page' => $_GET['page'] ?? '1',
];

$result = search_movies($filters);

$moviesFormatted = array_map(static function (array $movie): array {
    return [
        'id' => $movie['id'],
        'title' => $movie['title'],
        'release_year' => $movie['release_year'],
        'director' => $movie['director'],
        'duration_minutes' => $movie['duration_minutes'],
        'poster_url' => $movie['resolved_poster_url'],
        'avg_rating' => $movie['avg_rating'],
        'avg_rating_formatted' => format_rating($movie['avg_rating'], $movie['review_count']),
        'review_count' => $movie['review_count'],
        'genres' => array_column($movie['genres'], 'name'),
        'url' => base_url('movie.php?id=' . $movie['id']),
    ];
}, $result['movies']);

echo json_encode([
    'success' => true,
    'movies' => $moviesFormatted,
    'pagination' => $result['pagination'],
], JSON_UNESCAPED_UNICODE);
