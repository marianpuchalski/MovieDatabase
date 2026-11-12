/**
 * Interaktywny wybór oceny 1–5 gwiazdek w formularzach dodawania i edycji recenzji.
 */

document.addEventListener('DOMContentLoaded', () => {
    const widgets = document.querySelectorAll('[data-star-rating]');

    widgets.forEach((widget) => {
        const options = Array.from(widget.querySelectorAll('.star-option'));
        const statusEl = widget.querySelector('[data-rating-status]');

        const getSelectedValue = () => {
            const checked = widget.querySelector('input[name="rating"]:checked');
            return checked ? parseInt(checked.value, 10) : 0;
        };

        const renderStars = (activeCount, hoverCount = 0) => {
            options.forEach((option) => {
                const val = parseInt(option.dataset.starValue || '0', 10);
                option.classList.toggle('is-active', val <= activeCount);
                option.classList.toggle('is-hovered', hoverCount > 0 && val <= hoverCount);
            });

            if (statusEl) {
                const displayVal = hoverCount > 0 ? hoverCount : activeCount;
                statusEl.textContent = displayVal > 0
                    ? `Wybrana ocena: ${displayVal} / 5`
                    : 'Wybierz liczbę gwiazdek';
            }
        };

        options.forEach((option) => {
            const val = parseInt(option.dataset.starValue || '0', 10);
            const radio = option.querySelector('input[type="radio"]');

            option.addEventListener('mouseenter', () => {
                renderStars(getSelectedValue(), val);
            });

            if (radio) {
                radio.addEventListener('change', () => {
                    renderStars(getSelectedValue(), 0);
                    const form = widget.closest('form');
                    const errEl = form ? form.querySelector('[data-rating-error]') : null;
                    if (errEl) {
                        errEl.textContent = '';
                        errEl.classList.add('is-hidden');
                    }
                });
            }
        });

        widget.addEventListener('mouseleave', () => {
            renderStars(getSelectedValue(), 0);
        });

        renderStars(getSelectedValue(), 0);
    });
});