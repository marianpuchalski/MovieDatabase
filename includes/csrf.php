<?php
declare(strict_types=1);

/**
 * Zwraca token CSRF przypisany do bieżącej sesji.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generuje ukryte pole formularza z tokenem CSRF.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Weryfikuje token CSRF z żądania POST. Zwraca false w razie niezgodności.
 */
function verify_csrf_token(?string $token): bool
{
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Wymusza poprawny token CSRF przy żądaniach POST (kończy żądanie kodem 403 przy błędzie).
 */
function require_valid_csrf(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        http_response_code(403);
        set_flash('error', 'Nieprawidłowy lub wygasły token bezpieczeństwa formularza (CSRF). Odśwież stronę i spróbuj ponownie.');
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && str_starts_with($referer, (is_https() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? ''))) {
            header('Location: ' . $referer);
            exit;
        }
        redirect('index.php');
    }
}
