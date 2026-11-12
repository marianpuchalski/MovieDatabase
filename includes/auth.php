<?php
declare(strict_types=1);

/**
 * Zwraca dane aktualnie zalogowanego użytkownika (weryfikując w bazie m.in. is_blocked).
 * Jeśli konto nie istnieje lub zostało zablokowane, natychmiast niszczy sesję.
 */
function current_user(bool $refresh = false): ?array
{
    static $cachedUser = null;
    static $loaded = false;

    if ($loaded && !$refresh) {
        return $cachedUser;
    }

    $loaded = true;
    $userId = $_SESSION['user_id'] ?? null;

    if (!is_int($userId) && !ctype_digit((string) $userId)) {
        $cachedUser = null;
        return null;
    }

    $stmt = get_db()->prepare(
        'SELECT id, username, email, role, is_blocked, created_at
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => (int) $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        logout_session();
        $cachedUser = null;
        return null;
    }

    if ((int) $user['is_blocked'] === 1) {
        logout_session();
        set_flash('error', 'Twoje konto zostało zablokowane przez administratora.');
        $cachedUser = null;
        return null;
    }

    $cachedUser = $user;
    return $cachedUser;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

/**
 * Zapisuje użytkownika w sesji po poprawnym logowaniu.
 */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    // Odświeżenie tokenu CSRF po zmianie kontekstu sesji
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    current_user(true);
}

/**
 * Bezpiecznie kończy sesję użytkownika (zachowując kompatybilność z komunikatami flash).
 */
function logout_session(): void
{
    unset($_SESSION['user_id'], $_SESSION['csrf_token']);
    session_regenerate_id(true);
}

/**
 * Całkowite zniszczenie sesji przy wylogowaniu użytkownika.
 */
function destroy_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Wymaga zalogowanego i niezablokowanego użytkownika.
 */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        set_flash('warning', 'Musisz się zalogować, aby uzyskać dostęp do tej sekcji.');
        redirect('login.php');
    }
    return $user;
}

/**
 * Wymaga roli administratora.
 */
function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        set_flash('error', 'Brak uprawnień do Panelu Administratora.');
        redirect('index.php');
    }
    return $user;
}
