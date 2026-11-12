/**
 * Podgląd na żywo plakatu filmu lub zdjęcia aktora z wpisanego pola input[type="url"]
 * (FR-28). W razie pustego pola, niepoprawnego URL lub błędu ładowania przywraca grafikę domyślną.
 *
 * Oczekiwane atrybuty na elemencie <input>:
 * - data-url-preview="#preview-img" (selektor docelowego elementu <img>)
 * - data-preview-status="#preview-status" (opcjonalny selektor komunikatu statusu)
 * - data-default-src="assets/img/default-poster.png" (ścieżka obrazka zastępczego)
 */

document.addEventListener('DOMContentLoaded', () => {
    const urlInputs = document.querySelectorAll('input[data-url-preview]');

    urlInputs.forEach((input) => {
        const previewImg = document.querySelector(input.dataset.urlPreview || '');
        if (!previewImg) {
            return;
        }

        const statusEl = input.dataset.previewStatus
            ? document.querySelector(input.dataset.previewStatus)
            : null;
        const defaultSrc = input.dataset.defaultSrc
            || previewImg.getAttribute('data-fallback')
            || 'assets/img/default-poster.png';

        let timer = null;

        const updateStatus = (text) => {
            if (statusEl) {
                statusEl.textContent = text;
            }
        };

        const isValidHttpUrl = (value) => {
            try {
                const parsed = new URL(value);
                return parsed.protocol === 'http:' || parsed.protocol === 'https:';
            } catch (err) {
                return false;
            }
        };

        const showDefault = (statusMessage) => {
            previewImg.dataset.isExternalPreview = '0';
            previewImg.src = defaultSrc;
            updateStatus(statusMessage);
        };

        const updatePreview = () => {
            const value = input.value.trim();

            if (value === '') {
                showDefault('Brak adresu URL — wyświetlono grafikę domyślną.');
                return;
            }

            if (!isValidHttpUrl(value)) {
                showDefault('Adres musi zaczynać się od http:// lub https:// — wyświetlono grafikę domyślną.');
                return;
            }

            previewImg.dataset.isExternalPreview = '1';
            previewImg.src = value;
            updateStatus('Ładowanie podglądu obrazka...');
        };

        previewImg.addEventListener('load', () => {
            if (previewImg.dataset.isExternalPreview === '1') {
                updateStatus('Podgląd obrazka załadowany poprawnie.');
            }
        });

        previewImg.addEventListener('error', () => {
            if (previewImg.dataset.isExternalPreview === '1') {
                showDefault('Nie udało się pobrać obrazka z podanego URL — wyświetlono grafikę domyślną.');
            }
        });

        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(updatePreview, 300);
        });

        input.addEventListener('change', updatePreview);

        // Inicjalizacja dla trybu edycji
        updatePreview();
    });
});