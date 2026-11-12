# Sesja Frontend Agent — „Baza filmów”

## Cel sesji
Zaprojektowanie i wdrożenie warstwy interfejsu użytkownika (HTML5, CSS3, Vanilla JS, widoki kompatybilne z PHP 8+) zgodnie z dokumentem `Specs.md`.

## Zrealizowane decyzje projektowe
1. **IMDb-style Sleek Flat Dark UI (`assets/css/style.css`):**
   - Ciemna paleta: `#121212` (tło), `#1a1a1a` / `#1f1f1f` (panele i karty), `#2c2c2c` (ramki `1px`), `#ffffff` i `#a3a3a3` (typografia), `#f5c518` (gwiazdki i główne przyciski).
   - Zero gradientów (`linear-gradient`, `radial-gradient`) oraz zero cieni (`box-shadow`, `drop-shadow`).
   - Pełne RWD z punktami przerwania `992px`, `768px`, `480px` oraz zwijanym menu mobilnym.
2. **Bezpieczne wyświetlanie grafik z adresów URL (`poster_url`, `photo_url`):**
   - Funkcja pomocnicza `resolve_image_url()` w `includes/header.php` oraz walidacja w `filter.js` i `preview.js` akceptują wyłącznie protokoły `http://` i `https://`.
   - Automatyczny fallback na `assets/img/default-poster.png` oraz `assets/img/default-actor.png` przy braku adresu URL lub błędzie ładowania zewnętrznego obrazu (`data-fallback`).
3. **Warstwa widoków PHP bez logiki backendowej:**
   - Widoki (`index.php`, `movie.php`, `actors.php`, `actor.php`) korzystają z bezpiecznych wartości domyślnych (`??`), escapowania `e()` (`htmlspecialchars`) oraz gotowych ukrytych pól `csrf_token`.
4. **Moduły Vanilla JS:**
   - `main.js`: menu mobilne, obsługa fallbacku obrazków, potwierdzenia `data-confirm`, walidacja formularzy (wymagane, e-mail, URL `http/https`, min. długość, zgodność haseł, wymóg oceny 1–5).
   - `rating.js`: interaktywny wybór 1–5 gwiazdek przy dodawaniu i edycji recenzji.
   - `filter.js`: dynamiczne wyszukiwanie, filtrowanie (gatunek, rok), sortowanie i paginacja (12/str.) przez `fetch` do `api/movies_filter.php` z bezpiecznym budowaniem DOM (bez `innerHTML` na danych użytkownika).
   - `preview.js`: podgląd na żywo obrazka z wpisanego pola `input[type="url"]` w Panelu Admina.

## Utworzone pliki
- `includes/header.php`
- `includes/footer.php`
- `index.php`
- `movie.php`
- `actors.php`
- `actor.php`
- `assets/css/style.css`
- `assets/js/main.js`
- `assets/js/rating.js`
- `assets/js/filter.js`
- `assets/js/preview.js`
- Instrukcja/generator dla `assets/img/default-poster.png` i `assets/img/default-actor.png`
- `Docs/Sessions/Frontend.md`

## Kontrakt integracji z Backendem
Szczegółowy opis zmiennych PHP i formatu JSON dla `api/movies_filter.php` znajduje się w komentarzach nagłówkowych plików widoków oraz w instrukcji integracji poniżej.