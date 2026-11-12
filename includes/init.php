<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('ITEMS_PER_PAGE', 12);

/**
 * Wyznacza bazowy adres URL projektu (np. '/baza-filmow' w XAMPP).
 */
function detect_base_url(): string
{
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $docRootReal = $docRoot !== '' ? realpath($docRoot) : false;
    $appRootReal = realpath(APP_ROOT);

    if ($docRootReal === false || $appRootReal === false) {
        return '';
    }

    $docRootNorm = rtrim(str_replace('\\', '/', $docRootReal), '/');
    $appRootNorm = rtrim(str_replace('\\', '/', $appRootReal), '/');

    if (str_starts_with($appRootNorm, $docRootNorm)) {
        $relative = substr($appRootNorm, strlen($docRootNorm));
        return $relative === false ? '' : rtrim($relative, '/');
    }

    return '';
}

define('BASE_URL', detect_base_url());

// Globalna obsługa nieprzechwyconych wyjątków bez wycieku szczegółów technicznych
set_exception_handler(static function (Throwable $e): void {
    error_log('Nieobsłużony wyjątek: ' . $e->getMessage() . ' w ' . $e->getFile() . ':' . $e->getLine());

    if (!headers_sent()) {
        http_response_code(500);
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $isApi = str_contains($requestUri, '/api/');

    if ($isApi) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['error' => 'Wystąpił wewnętrzny błąd serwera.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo '<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8"><title>Błąd aplikacji</title></head>';
    echo '<body style="background:#121212;color:#ffffff;font-family:sans-serif;padding:2rem;">';
    echo '<h1>Wystąpił błąd aplikacji</h1>';
    echo '<p>Przepraszamy, nie można teraz przetworzyć żądania. Spróbuj ponownie później.</p>';
    echo '</body></html>';
    exit;
});

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_URL !== '' ? BASE_URL : '/',
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/csrf.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/movie_queries.php';
