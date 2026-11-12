<?php
declare(strict_types=1);

/**
 * Pobiera listę wszystkich gatunków do filtrów i formularzy.
 */
function get_all_genres(): array
{
    $stmt = get_db()->query('SELECT id, name FROM genres ORDER BY name ASC');
    return $stmt->fetchAll();
}

/**
 * Pobiera unikalne lata produkcji istniejących filmów (do selecta filtrowania).
 */
function get_movie_release_years(): array
{
    $stmt = get_db()->query('SELECT DISTINCT release_year FROM movies ORDER BY release_year DESC');
    return array_map(static fn(array $row): int => (int) $row['release_year'], $stmt->fetchAll());
}

/**
 * Wyszukuje, filtruje, sortuje i paginuje katalog filmów (12 na stronę).
 */
function search_movies(array $filters): array
{
    $pdo = get_db();

    $search = trim((string) ($filters['q'] ?? ''));
    $genreId = isset($filters['genre']) && ctype_digit((string) $filters['genre']) ? (int) $filters['genre'] : 0;
    $year = isset($filters['year']) && ctype_digit((string) $filters['year']) ? (int) $filters['year'] : 0;
    $sort = (string) ($filters['sort'] ?? 'newest');
    $requestedPage = isset($filters['page']) && ctype_digit((string) $filters['page']) ? (int) $filters['page'] : 1;

    $whereClauses = [];
    $params = [];

    if ($search !== '') {
        $whereClauses[] = "m.title LIKE :search ESCAPE '\\\\'";
        $params[':search'] = '%' . escape_like($search) . '%';
    }

    if ($genreId > 0) {
        $whereClauses[] = 'EXISTS (SELECT 1 FROM movie_genres mg WHERE mg.movie_id = m.id AND mg.genre_id = :genre_id)';
        $params[':genre_id'] = $genreId;
    }

    if ($year > 0) {
        $whereClauses[] = 'm.release_year = :release_year';
        $params[':release_year'] = $year;
    }

    $whereSql = $whereClauses !== [] ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

    // Biała lista dozwolonych opcji sortowania
    $orderBy = match ($sort) {
        'rating_desc' => 'avg_rating DESC, review_count DESC, m.title ASC',
        'title_asc' => 'm.title ASC, m.release_year DESC',
        'newest' => 'm.release_year DESC, m.created_at DESC, m.id DESC',
        default => 'm.release_year DESC, m.created_at DESC, m.id DESC',
    };

    // Zliczenie łącznej liczby dopasowanych filmów
    $countSql = "SELECT COUNT(*) FROM movies m $whereSql";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $val) {
        $countStmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalItems = (int) $countStmt->fetchColumn();

    $pagination = calculate_pagination($totalItems, $requestedPage, ITEMS_PER_PAGE);

    // Pobranie strony wyników ze średnią oceną i liczbą recenzji
    $sql = "
        SELECT
            m.id,
            m.title,
            m.release_year,
            m.director,
            m.duration_minutes,
            m.poster_url,
            m.created_at,
            COALESCE(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
            COUNT(r.id) AS review_count
        FROM movies m
        LEFT JOIN reviews r ON r.movie_id = m.id
        $whereSql
        GROUP BY m.id, m.title, m.release_year, m.director, m.duration_minutes, m.poster_url, m.created_at
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();

    $movies = $stmt->fetchAll();

    // Pobranie gatunków przypisanych do filmów na bieżącej stronie
    if ($movies !== []) {
        $movieIds = array_map(static fn(array $m): int => (int) $m['id'], $movies);
        $placeholders = implode(',', array_fill(0, count($movieIds), '?'));

        $genreStmt = $pdo->prepare(
            "SELECT mg.movie_id, g.id, g.name
             FROM movie_genres mg
             INNER JOIN genres g ON g.id = mg.genre_id
             WHERE mg.movie_id IN ($placeholders)
             ORDER BY g.name ASC"
        );
        $genreStmt->execute($movieIds);

        $genresByMovie = [];
        foreach ($genreStmt->fetchAll() as $row) {
            $mId = (int) $row['movie_id'];
            $genresByMovie[$mId][] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
            ];
        }

        foreach ($movies as &$movie) {
            $mId = (int) $movie['id'];
            $movie['id'] = $mId;
            $movie['release_year'] = (int) $movie['release_year'];
            $movie['duration_minutes'] = (int) $movie['duration_minutes'];
            $movie['avg_rating'] = (float) $movie['avg_rating'];
            $movie['review_count'] = (int) $movie['review_count'];
            $movie['resolved_poster_url'] = poster_url($movie['poster_url']);
            $movie['genres'] = $genresByMovie[$mId] ?? [];
        }
        unset($movie);
    }

    return [
        'movies' => $movies,
        'pagination' => $pagination,
    ];
}
