<?php
/**
 * Wspólny nagłówek HTML5 i nawigacja aplikacji „Baza filmów”.
 * Oczekiwane zmienne opcjonalne (ustawiane przez warstwę backendową):
 * - $pageTitle (string)
 * - $activePage (string: 'movies'|'actors'|'login'|'register'|'profile'|'admin')
 * - $currentUser (array|null: ['id', 'username', 'role', 'is_blocked'])
 * - $flashMessages (array: [['type' => 'success|error|warning|info', 'text' => '...']])
 * - $basePath (string: '' dla katalogu głównego, '../' dla panelu admin/)
 */

$basePath = $basePath ?? (function_exists('base_url') ? base_url('') : '');
$pageTitle = $pageTitle ?? 'Baza filmów';
$activePage = $activePage ?? '';
$currentUser = $currentUser ?? (function_exists('current_user') ? current_user() : null);
$flashMessages = $flashMessages ?? [];

if (function_exists('get_flashes')) {
    foreach (get_flashes() as $fl) {
        $flashMessages[] = [
            'type' => $fl['type'] ?? 'info',
            'text' => $fl['message'] ?? $fl['text'] ?? '',
        ];
    }
}

if (!empty($flashMessage) && is_array($flashMessage)) {
    $flashMessages[] = $flashMessage;
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('resolve_image_url')) {
    /**
     * Zwraca bezpieczny adres URL obrazka (http/https) lub ścieżkę domyślną.
     */
    function resolve_image_url(?string $url, string $fallbackPath): string
    {
        $url = trim((string) $url);
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false) {
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            if ($scheme === 'http' || $scheme === 'https') {
                return $url;
            }
        }

        return $fallbackPath;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e($basePath) ?>assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= e($basePath) ?>index.php" class="brand-logo" aria-label="Baza filmów — strona główna">
            <span class="brand-badge">BF</span>
            <span class="brand-name">Baza filmów</span>
        </a>

        <button
            type="button"
            id="nav-toggle"
            class="nav-toggle"
            aria-expanded="false"
            aria-controls="main-nav"
            aria-label="Otwórz lub zamknij menu nawigacji"
        >
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
        </button>

        <nav id="main-nav" class="main-nav" aria-label="Nawigacja główna">
            <ul class="nav-list">
                <li>
                    <a
                        href="<?= e($basePath) ?>index.php"
                        class="nav-link<?= $activePage === 'movies' ? ' is-active' : '' ?>"
                    >Filmy</a>
                </li>
                <li>
                    <a
                        href="<?= e($basePath) ?>actors.php"
                        class="nav-link<?= $activePage === 'actors' ? ' is-active' : '' ?>"
                    >Aktorzy</a>
                </li>
            </ul>

            <ul class="nav-list nav-list--auth">
                <?php if ($currentUser !== null): ?>
                    <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
                        <li>
                            <a
                                href="<?= e($basePath) ?>admin/index.php"
                                class="nav-link nav-link--admin<?= $activePage === 'admin' ? ' is-active' : '' ?>"
                            >Panel Admina</a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a
                            href="<?= e($basePath) ?>profile.php"
                            class="nav-link<?= $activePage === 'profile' ? ' is-active' : '' ?>"
                        >Profil (<?= e($currentUser['username'] ?? 'Użytkownik') ?>)</a>
                    </li>
                    <li>
                        <a href="<?= e($basePath) ?>logout.php" class="btn btn-secondary btn-sm">Wyloguj</a>
                    </li>
                <?php else: ?>
                    <li>
                        <a
                            href="<?= e($basePath) ?>login.php"
                            class="nav-link<?= $activePage === 'login' ? ' is-active' : '' ?>"
                        >Zaloguj się</a>
                    </li>
                    <li>
                        <a
                            href="<?= e($basePath) ?>register.php"
                            class="btn btn-primary btn-sm<?= $activePage === 'register' ? ' is-active' : '' ?>"
                        >Rejestracja</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
        <?php if (!empty($flashMessages)): ?>
            <div class="flash-container" role="status" aria-live="polite">
                <?php foreach ($flashMessages as $msg): ?>
                    <?php
                    $type = (string) ($msg['type'] ?? 'info');
                    if (!in_array($type, ['success', 'error', 'warning', 'info'], true)) {
                        $type = 'info';
                    }
                    ?>
                    <div class="alert alert-<?= e($type) ?>">
                        <?= e((string) ($msg['text'] ?? '')) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>