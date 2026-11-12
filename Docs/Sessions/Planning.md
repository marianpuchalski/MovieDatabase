# Sesja planowania — Projekt „Baza filmów” (MovieDatabase)

## Informacje o sesji
- **Data:** 2026-11-12
- **Rola prowadząca:** Planning Agent
- **Właściciel projektu:** Uczeń (właściciel projektu)
- **Cel sesji:** Zebranie wymagań funkcjonalnych i niefunkcjonalnych, zaprojektowanie architektury, bazy danych, przepływów i struktury katalogów dla szkolnej aplikacji internetowej „Baza filmów” (czysty PHP, MySQL, HTML, CSS, Vanilla JS w środowisku XAMPP, bez frameworków).

---

## 1. Przebieg rozmowy (pytania, odpowiedzi i decyzje)

### Krok 1: Role użytkowników i uprawnienia
- **Pytanie:** Jakie role użytkowników i uprawnienia powinny znaleźć się w aplikacji?
- **Odpowiedź ucznia:** Podział na Gościa (przeglądanie), Zalogowanego użytkownika (dodawanie recenzji i ocen) oraz Administratora (zarządzanie filmami, aktorami, obsadą i moderacja recenzji).
- **Decyzja:** Przyjęto 3 role: Gość, Zalogowany użytkownik (`user`), Administrator (`admin`).

### Krok 2: Informacje o filmach i wyszukiwanie
- **Pytanie:** Jakie dane powinien zawierać każdy film oraz jak użytkownicy mają przeglądać i szukać filmów?
- **Odpowiedź ucznia:** Uwzględniamy wszystko z listy (tytuł, rok produkcji, gatunek, reżyser, czas trwania, opis, plakat, wyszukiwarka po tytule, filtrowanie po gatunku/roku, sortowanie).
- **Decyzja:** Uwzględniono pełny zestaw pól informacyjnych o filmie oraz wyszukiwanie, filtrowanie i sortowanie katalogu.

### Krok 3: Doprecyzowanie filmów oraz Aktorzy i obsada
- **Pytanie:** Czy film może mieć wiele gatunków, w jaki sposób dodajemy plakaty, jakie dane zawiera aktor, czy w obsadzie zapisujemy nazwę postaci i czy aktorzy mają osobną podstronę?
- **Odpowiedź ucznia:** Film może mieć wiele gatunków; plakat początkowo planowany jako plik na serwer; aktor zawiera imię, nazwisko, datę urodzenia, kraj pochodzenia, biografię i zdjęcie; w obsadzie zapisujemy nazwę granej postaci; aktorzy mają osobną podstronę z filmografią.
- **Decyzja:** Zastosowano relację N:M dla filmów i gatunków (`movie_genres`), tabelę obsady `movie_cast` z kolumną `character_name` oraz dedykowaną stronę profilu aktora (`actor.php`). *(Sposób przechowywania grafik został zmodyfikowany w Kroku 7).*

### Krok 4: Recenzje i oceny
- **Pytanie:** Jaka skala ocen, czy wyliczamy średnią, czy tekst recenzji jest wymagany, jakie są limity i uprawnienia do edycji/usuwania?
- **Odpowiedź ucznia:** Skala gwiazdki (1–5), średnia ocena liczona automatycznie, recenzja może być dodana bez tekstu (sama ocena), jedna ocena do filmu od jednego użytkownika, autor może edytować i usuwać swoją recenzję, administrator może usuwać recenzje.
- **Decyzja:** Skala **1–5 gwiazdek** (`TINYINT` z `CHECK (rating BETWEEN 1 AND 5)`), pole `comment` opcjonalne (`NULL`), ograniczenie `UNIQUE (movie_id, user_id)` w tabeli `reviews`, edycja/usuwanie przez autora oraz moderacja przez administratora.

### Krok 5: Konta użytkowników i Panel Administratora
- **Pytanie:** Czy użytkownik ma mieć listę swoich recenzji, czy administrator ma mieć podgląd listy użytkowników i czy konto admina jest w bazie danych?
- **Odpowiedź ucznia:** Użytkownik ma mieć listę swoich recenzji; administrator powinien mieć podgląd listy użytkowników; konto administratora w bazie danych (wspólny formularz logowania).
- **Decyzja:** Podstrona `profile.php` z listą recenzji użytkownika, rola `admin` w tabeli `users`, wspólny formularz `login.php` odblokowujący link do Panelu Administratora.

### Krok 6: Zarządzanie użytkownikami, design, JS i wymagania niefunkcjonalne
- **Pytanie:** Czy administrator może blokować/usuwać użytkowników, czy strona ma być RWD, jaki motyw, czy wprowadzamy paginację, jakie funkcje JS, jakie standardy bezpieczeństwa i preferencje wizualne?
- **Odpowiedź ucznia:** Administrator ma mieć możliwość blokowania i usuwania użytkowników; strona ma być RWD; ciemny motyw; wprowadzamy paginację; wszystkie wymienione funkcje w JS (klikalne gwiazdki, walidacja formularzy, `confirm` przed usuwaniem, podgląd obrazka, dynamiczne filtrowanie `fetch`); przyjmujemy standardy bezpieczeństwa (PDO, `password_hash`, XSS, CSRF); design jak **IMDb** — bez żadnych gradientów, bez zbędnych cieni (`drop-shadow` / `box-shadow`), funkcjonalny, profesjonalny, **sleek**.
- **Decyzja:**
  - Pełne zarządzanie użytkownikami w `admin/users.php` (blokowanie, odblokowywanie i usuwanie z ochroną przed samoblokadą/samousunięciem).
  - Ciemny motyw **IMDb-style Flat Dark UI** (RWD, zero gradientów, zero zbędnych cieni, akcent `#f5c518`).
  - Paginacja po 12 pozycji na stronę dla filmów i aktorów.
  - Komplet funkcji w czystym JS (`rating.js`, `filter.js`, `preview.js`, `main.js`).
  - Pełen zestaw zabezpieczeń PHP 8+ / PDO.

### Krok 7: Korekta decyzji — Grafiki jako linki URL zamiast uploadu plików
- **Zgłoszenie ucznia:** Zamiast przesyłania plików graficznych na serwer, plakaty filmów i zdjęcia aktorów mają być podawane jako adresy URL do istniejących zasobów z internetu.
- **Decyzja:** Zastąpiono upload plików (`includes/upload.php`, katalog `uploads/`) kolumnami `poster_url` i `photo_url` (`VARCHAR(500) NULL`), walidacją adresów wyłącznie dla protokołów `http://` i `https://`, podglądem na żywo z pola URL w `preview.js` oraz fallbackiem do lokalnych grafik zastępczych (`assets/img/default-poster.png`, `assets/img/default-actor.png`).

### Krok 8: Ujednolicenie struktury katalogów z agentami FE i BE
- **Kontekst:** Uczeń przekazał dokument `Specs.md` agentom FE i BE, podczas gdy lokalne repozytorium `MovieDatabase` miało początkowo foldery `backend/`, `database/`, `frontend/`.
- **Decyzja ucznia:** Ujednolicenie lokalnego repozytorium na dysku do dokładnej struktury ze `Specs.md` (`sql/`, `config/`, `includes/`, `api/`, `admin/`, `assets/`), przeniesienie `database/schema.sql` do `sql/schema.sql` oraz dodanie `.gitkeep` w pustych katalogach w celu wypchnięcia spójnego szkieletu przez Git.

---

## 2. Rezultaty sesji planowania

1. Przygotowano kompletną, skonsolidowaną specyfikację wymagań funkcjonalnych i niefunkcjonalnych, diagramy Mermaid (Architektura, ERD, Workflow), strukturę katalogów oraz listę zadań dla 5 agentów w pliku **`Docs/Specs.md`**.
2. Przygotowano szczegółową dokumentację architektury systemu i podziału odpowiedzialności w pliku **`Docs/Architecture.md`**.
3. Przygotowano szczegółowy opis procesów, przepływu danych, obsługi błędów oraz uwierzytelniania i autoryzacji w pliku **`Docs/Workflow.md`**.
4. Uporządkowano strukturę katalogów projektu `MovieDatabase` pod środowisko XAMPP oraz zsynchronizowano ją z repozytorium Git.