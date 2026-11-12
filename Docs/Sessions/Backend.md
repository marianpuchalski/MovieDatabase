# Sesja Backend Agenta — „Baza filmów”

## Zakres zrealizowanych prac
1. **Połączenie z bazą danych (PDO / MySQL / MariaDB):**
   - `config/.htaccess`, `config/db.example.php`, `config/database.php`.
   - Połączenie PDO z kodowaniem `utf8mb4`, `ERRMODE_EXCEPTION` i `ATTR_EMULATE_PREPARES => false`.
   - Brak haseł w kodzie publicznym (konfiguracja ze zmiennych środowiskowych lub lokalnego pliku `config/db.local.php`).
2. **Warstwa wspólna i bezpieczeństwo (`includes/`):**
   - `includes/.htaccess`, `includes/init.php`, `includes/csrf.php`, `includes/functions.php`, `includes/auth.php`, `includes/movie_queries.php`.
   - Tokeny CSRF (`hash_equals`) dla żądań `POST`.
   - Escapowanie danych przez `htmlspecialchars` (`e()`), walidacja URL obrazków (`http://` / `https://`, max 500 znaków), bezpieczne fallbacki obrazków domyślnych.
   - Weryfikacja sesji, roli oraz bieżącego statusu `is_blocked` w bazie przy każdym chronionym żądaniu.
   - Globalna obsługa wyjątków z logowaniem przez `error_log()`.
3. **Warstwa publiczna i konta użytkowników:**
   - `index.php` i `api/movies_filter.php`: katalog filmów (12/str.), wyszukiwanie po tytule, filtrowanie po gatunku i roku, sortowanie z białą listą oraz odpowiedź JSON dla `fetch`.
   - `movie.php`: szczegóły filmu, gatunki, obsada, średnia ocena, lista recenzji oraz formularz oceny 1–5.
   - `actors.php` i `actor.php`: katalog aktorów (12/str.) z wyszukiwarką oraz profil aktora z filmografią i nazwami granych postaci.
   - `register.php`, `login.php`, `logout.php`, `profile.php`, `review_action.php`: rejestracja, wspólne logowanie (`password_hash`/`password_verify`), profil użytkownika oraz dodawanie/edycja/usuwanie recenzji (1 recenzja na film, opcjonalna treść).
4. **Panel Administratora (`admin/`):**
   - `admin/index.php`, `admin/movies.php` (transakcyjny zapis film + gatunki N:M), `admin/genres.php`, `admin/actors.php`, `admin/cast.php`, `admin/reviews.php`, `admin/users.php` (blokowanie/odblokowywanie/usuwanie z ochroną przed samoblokadą i samousunięciem).

## Zgodność ze schematem bazy danych
- Zachowano 100% zgodności z `sql/schema.sql` bez modyfikacji struktury tabel.
