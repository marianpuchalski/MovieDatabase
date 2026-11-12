<?php
declare(strict_types=1);

/**
 * Zwraca współdzielone połączenie PDO z bazą MySQL/MariaDB.
 * Dane dostępowe pobierane są ze zmiennych środowiskowych lub lokalnego pliku config/db.local.php.
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = null;
    $localConfigPath = __DIR__ . '/db.local.php';

    if (is_file($localConfigPath)) {
        $loaded = require $localConfigPath;
        if (is_array($loaded)) {
            $config = $loaded;
        }
    }

    $host = getenv('DB_HOST') ?: ($config['host'] ?? null);
    $port = getenv('DB_PORT') ?: ($config['port'] ?? '3306');
    $dbname = getenv('DB_NAME') ?: ($config['dbname'] ?? null);
    $username = getenv('DB_USER') ?: ($config['username'] ?? null);
    $password = getenv('DB_PASS');
    if ($password === false) {
        $password = $config['password'] ?? null;
    }
    $charset = getenv('DB_CHARSET') ?: ($config['charset'] ?? 'utf8mb4');

    if (!$host || !$dbname || !$username || $password === null) {
        error_log('Brak konfiguracji połączenia z bazą danych (config/db.local.php lub zmienne środowiskowe).');
        throw new RuntimeException('Błąd konfiguracji bazy danych.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $host,
        $port,
        $dbname,
        $charset
    );

    try {
        $pdo = new PDO($dsn, (string) $username, (string) $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        error_log('Błąd połączenia PDO: ' . $e->getMessage());
        throw new RuntimeException('Nie udało się połączyć z bazą danych.');
    }

    return $pdo;
}
