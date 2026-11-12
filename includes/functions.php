<?php
declare(strict_types=1);

/**
 * Bezpieczne escapowanie danych wyjściowych przed XSS.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

/**
 * Buduje pełny adres ścieżki w obrębie projektu.
 */
function base_url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return BASE_URL !== '' ? BASE_URL . '/' : '/';
    }
    return (BASE_URL !== '' ? BASE_URL : '') . '/' . $cleanPath;
}

/**
 * Przekierowuje na podaną ścieżkę względną w aplikacji.
 */
function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

/**
 * Zapisuje komunikat flash w sesji.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * Pobiera i czyści komunikaty flash z sesji.
 */
function get_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return is_array($flashes) ? $flashes : [];
}

/**
 * Waliduje opcjonalny adres URL obrazka (wyłącznie http:// lub https://, max 500 znaków).
 */
function validate_image_url(?string $rawUrl, ?string &$error = null): ?string
{
    $url = trim((string) $rawUrl);
    if ($url === '') {
        return null;
    }

    if (mb_strlen($url) > 500) {
        $error = 'Adres URL obrazka nie może przekraczać 500 znaków.';
        return null;
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        $error = 'Podany adres URL obrazka ma nieprawidłowy format.';
        return null;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    $host = parse_url($url, PHP_URL_HOST);

    if (!in_array($scheme, ['http', 'https'], true) || empty($host)) {
        $error = 'Dozwolone są wyłącznie adresy URL z protokołem http:// lub https://.';
        return null;
    }

    return $url;
}

/**
 * Sprawdza, czy URL z bazy jest poprawnym linkiem http/https (ochrona dodatkowa przy renderowaniu).
 */
function is_safe_http_url(?string $url): bool
{
    if ($url === null || trim($url) === '') {
        return false;
    }
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

/**
 * Zwraca bezpieczny adres plakatu filmu albo domyślny obrazek zastępczy.
 */
function poster_url(?string $url): string
{
    return is_safe_http_url($url) ? (string) $url : base_url('assets/img/default-poster.png');
}

/**
 * Zwraca bezpieczny adres zdjęcia aktora albo domyślny obrazek zastępczy.
 */
function actor_photo_url(?string $url): string
{
    return is_safe_http_url($url) ? (string) $url : base_url('assets/img/default-actor.png');
}

/**
 * Escapuje znaki specjalne dla operatora LIKE w SQL.
 */
function escape_like(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

/**
 * Oblicza metadane paginacji.
 */
function calculate_pagination(int $totalItems, int $requestedPage, int $perPage = ITEMS_PER_PAGE): array
{
    $totalItems = max(0, $totalItems);
    $perPage = max(1, $perPage);
    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    $currentPage = max(1, min($requestedPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total_items' => $totalItems,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
    ];
}

/**
 * Formatuje średnią ocenę do postaci np. "4,5" lub "Brak ocen".
 */
function format_rating(float|int|string|null $avgRating, int $reviewCount): string
{
    if ($reviewCount <= 0) {
        return 'Brak ocen';
    }
    return number_format((float) $avgRating, 1, ',', '');
}

/**
 * Formatuje czas trwania w minutach na postać czytelną (np. "142 min").
 */
function format_duration(int $minutes): string
{
    return $minutes . ' min';
}
