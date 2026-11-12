# Sesja Data Analyst — Baza danych „Baza filmów”

## Faza 1: Analiza i projekt bazy danych
1. Przeanalizowano `Specs.md` (FR-01–FR-29, sekcje 6, 8 i 9).
2. Przygotowano model relacyjny w 3NF dla MySQL/MariaDB (`InnoDB`, `utf8mb4_unicode_ci`):
   - `users`, `movies`, `genres`, `movie_genres`, `actors`, `movie_cast`, `reviews`.
3. Zdefiniowano klucze główne (`INT UNSIGNED`), klucze obce z `ON DELETE CASCADE ON UPDATE CASCADE`, ograniczenia unikalności i domenowe (`UNIQUE (movie_id, user_id)`, `CHECK (rating BETWEEN 1 AND 5)`) oraz indeksy wspierające wyszukiwanie (`title`, `release_year`, `last_name`, `genre_id`).
4. Projekt został zatwierdzony przed implementacją SQL.

## Faza 2: Implementacja SQL i dane testowe
1. Przygotowano kompletny skrypt `sql/schema.sql` tworzący bazę `baza_filmow` oraz wszystkie 7 tabel wraz z relacjami, kluczami obcymi, ograniczeniami `CHECK`/`UNIQUE` i indeksami.
2. Przygotowano skrypt `sql/seed.sql` zawierający:
   - 4 konta testowe (`admin`, 2 aktywnych użytkowników `user`, 1 konto zablokowane `is_blocked = 1`), hasło testowe `password` (hash bcrypt zgodny z `password_verify()`),
   - 10 gatunków filmowych,
   - 16 filmów oraz 15 aktorów (umożliwiających test paginacji 12 pozycji na stronę),
   - 23 wpisy obsady (`movie_cast`) z nazwami postaci,
   - 17 recenzji w skali 1–5 (w tym oceny bez komentarza oraz filmy bez recenzji).
3. Zweryfikowano poprawność typów kluczy głównych/obcych, brak naruszeń `UNIQUE` i `CHECK` w danych testowych oraz poprawność zapytań agregujących średnią ocen i liczbę recenzji.