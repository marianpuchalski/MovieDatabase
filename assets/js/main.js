/**
 * Główny skrypt interakcji:
 * - Menu mobilne (RWD)
 * - Fallback grafik URL przy błędzie ładowania
 * - Potwierdzenia confirm() dla akcji usuwania/blokowania
 * - Walidacja formularzy po stronie klienta (wymagane, e-mail, URL http/https, hasła, ocena)
 */

document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initImageFallbacks();
    initConfirmActions();
    initFormValidation();
});

function initMobileNav() {
    const toggleBtn = document.getElementById('nav-toggle');
    const mainNav = document.getElementById('main-nav');

    if (!toggleBtn || !mainNav) {
        return;
    }

    toggleBtn.addEventListener('click', () => {
        const isOpen = mainNav.classList.toggle('is-open');
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
}

function initImageFallbacks() {
    const images = document.querySelectorAll('img[data-fallback]');

    images.forEach((img) => {
        const fallbackSrc = img.getAttribute('data-fallback');
        if (!fallbackSrc) {
            return;
        }

        const applyFallback = () => {
            if (img.dataset.fallbackApplied === '1') {
                return;
            }
            img.dataset.fallbackApplied = '1';
            img.src = fallbackSrc;
        };

        img.addEventListener('error', applyFallback);

        if (img.complete && img.naturalWidth === 0) {
            applyFallback();
        }
    });
}

function initConfirmActions() {
    document.addEventListener('click', (event) => {
        const target = event.target.closest('[data-confirm]');
        if (!target) {
            return;
        }

        const message = target.getAttribute('data-confirm') || 'Czy na pewno chcesz wykonać tę akcję?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
}

function isValidHttpUrl(value) {
    try {
        const parsed = new URL(value);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
    } catch (err) {
        return false;
    }
}

function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');

    forms.forEach((form) => {
        form.setAttribute('novalidate', 'novalidate');

        form.addEventListener('submit', (event) => {
            let isValid = true;

            clearFormErrors(form);

            // 1. Walidacja oceny 1-5 gwiazdek (jeśli formularz jej wymaga)
            if (form.dataset.requireRating === 'true') {
                const checkedRating = form.querySelector('input[name="rating"]:checked');
                const ratingErrorEl = form.querySelector('[data-rating-error]');

                if (!checkedRating) {
                    isValid = false;
                    if (ratingErrorEl) {
                        ratingErrorEl.textContent = 'Wybierz ocenę od 1 do 5 gwiazdek.';
                        ratingErrorEl.classList.remove('is-hidden');
                    }
                }
            }

            // 2. Walidacja pól tekstowych, e-mail, URL i haseł
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach((field) => {
                if (field.type === 'hidden' || field.type === 'radio' || field.disabled) {
                    return;
                }

                const value = field.value.trim();
                const isRequired = field.hasAttribute('required') || field.dataset.required === 'true';

                if (isRequired && value === '') {
                    setFieldError(field, 'To pole jest wymagane.');
                    isValid = false;
                    return;
                }

                if (value === '') {
                    return;
                }

                if (field.type === 'email') {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(value)) {
                        setFieldError(field, 'Podaj poprawny adres e-mail.');
                        isValid = false;
                        return;
                    }
                }

                if (field.type === 'url' || field.dataset.validateUrl === 'true') {
                    if (!isValidHttpUrl(value)) {
                        setFieldError(field, 'Podaj poprawny adres URL zaczynający się od http:// lub https://.');
                        isValid = false;
                        return;
                    }
                }

                const minLength = parseInt(field.dataset.minLength || '0', 10);
                if (minLength > 0 && value.length < minLength) {
                    setFieldError(field, `Wpisz co najmniej ${minLength} znaków.`);
                    isValid = false;
                    return;
                }

                if (field.dataset.match) {
                    const matchTarget = form.querySelector(field.dataset.match);
                    if (matchTarget && field.value !== matchTarget.value) {
                        setFieldError(field, 'Wpisane hasła nie są identyczne.');
                        isValid = false;
                    }
                }
            });

            if (!isValid) {
                event.preventDefault();
            }
        });
    });
}

function clearFormErrors(form) {
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    form.querySelectorAll('.field-error[data-generated="true"]').forEach((el) => el.remove());

    const ratingErrorEl = form.querySelector('[data-rating-error]');
    if (ratingErrorEl) {
        ratingErrorEl.textContent = '';
        ratingErrorEl.classList.add('is-hidden');
    }
}

function setFieldError(field, message) {
    field.classList.add('is-invalid');
    const errorEl = document.createElement('p');
    errorEl.className = 'field-error';
    errorEl.dataset.generated = 'true';
    errorEl.textContent = message;
    field.insertAdjacentElement('afterend', errorEl);
}