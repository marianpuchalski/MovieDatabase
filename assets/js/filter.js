/**
 * Dynamiczne wyszukiwanie, filtrowanie, sortowanie i paginacja katalogu filmów
 * za pomocą fetch API (endpoint: api/movies_filter.php).
 */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('movies-filter-form');
    const grid = document.getElementById('movies-grid');
    const emptyBox = document.getElementById('movies-empty');
    const paginationNav = document.getElementById('movies-pagination');
    const counterEl = document.getElementById('movies-results-count');
    const statusBanner = document.getElementById('movies-status');
    const resetLink = document.getElementById('filter-reset');

    if (!form || !grid || !paginationNav) {
        return;
    }

    const DEFAULT_POSTER = 'assets/img/default-poster.png';
    let searchTimer = null;
    let activeController = null;

    const getSafePosterUrl = (rawUrl) => {
        if (typeof rawUrl !== 'string') {
            return DEFAULT_POSTER;
        }
        const trimmed = rawUrl.trim();
        if (!trimmed) {
            return DEFAULT_POSTER;
        }
        try {
            const parsed = new URL(trimmed);
            if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
                return trimmed;
            }
        } catch (err) {
            return DEFAULT_POSTER;
        }
        return DEFAULT_POSTER;
    };

    const setStatus = (message, isError = false) => {
        if (!statusBanner) {
            return;
        }
        if (!message) {
            statusBanner.textContent = '';
            statusBanner.classList.add('is-hidden');
            statusBanner.classList.remove('is-error');
            return;
        }
        statusBanner.textContent = message;
        statusBanner.classList.remove('is-hidden');
        statusBanner.classList.toggle('is-error', isError);
    };

    const buildParams = (page = 1) => {
        const formData = new FormData(form);
        const params = new URLSearchParams();

        const q = (formData.get('q') || '').toString().trim();
        const genre = (formData.get('genre') || '').toString().trim();
        const year = (formData.get('year') || '').toString().trim();
        const sort = (formData.get('sort') || 'rating_desc').toString().trim();

        if (q !== '') params.set('q', q);
        if (genre !== '') params.set('genre', genre);
        if (year !== '') params.set('year', year);
        if (sort !== '') params.set('sort', sort);
        if (page > 1) params.set('page', String(page));

        return params;
    };

    const createMovieCard = (movie) => {
        const movieId = parseInt(movie.id, 10) || 0;
        const movieUrl = `movie.php?id=${movieId}`;
        const titleText = String(movie.title || 'Bez tytułu');

        const article = document.createElement('article');
        article.className = 'media-card movie-card';

        // Plakat
        const posterLink = document.createElement('a');
        posterLink.href = movieUrl;
        posterLink.className = 'media-card-poster-link';

        const posterFrame = document.createElement('div');
        posterFrame.className = 'poster-frame';

        const img = document.createElement('img');
        img.className = 'poster-img';
        img.loading = 'lazy';
        img.alt = `Plakat filmu ${titleText}`;
        img.src = getSafePosterUrl(movie.poster_url);
        img.addEventListener('error', () => {
            if (img.dataset.fallbackApplied === '1') return;
            img.dataset.fallbackApplied = '1';
            img.src = DEFAULT_POSTER;
        });

        posterFrame.appendChild(img);
        posterLink.appendChild(posterFrame);
        article.appendChild(posterLink);

        // Treść karty
        const body = document.createElement('div');
        body.className = 'media-card-body';

        // Ocena
        const ratingBox = document.createElement('div');
        ratingBox.className = 'movie-card-rating';
        const reviewsCount = parseInt(movie.reviews_count, 10) || 0;

        if (reviewsCount > 0) {
            const star = document.createElement('span');
            star.className = 'star-icon';
            star.setAttribute('aria-hidden', 'true');
            star.textContent = '\u2605';

            const val = document.createElement('span');
            val.className = 'rating-value';
            val.textContent = Number(movie.avg_rating || 0).toFixed(1);

            const count = document.createElement('span');
            count.className = 'rating-count';
            count.textContent = `(${reviewsCount})`;

            ratingBox.append(star, val, count);
        } else {
            const emptyRating = document.createElement('span');
            emptyRating.className = 'rating-empty';
            emptyRating.textContent = 'Brak ocen';
            ratingBox.appendChild(emptyRating);
        }
        body.appendChild(ratingBox);

        // Tytuł
        const h2 = document.createElement('h2');
        h2.className = 'media-card-title';
        const titleLink = document.createElement('a');
        titleLink.href = movieUrl;
        titleLink.textContent = titleText;
        h2.appendChild(titleLink);
        body.appendChild(h2);

        // Metadane
        const meta = document.createElement('p');
        meta.className = 'media-card-meta';
        const yearSpan = document.createElement('span');
        yearSpan.textContent = String(movie.release_year || '');
        meta.appendChild(yearSpan);

        if (movie.duration_minutes) {
            const dot = document.createElement('span');
            dot.setAttribute('aria-hidden', 'true');
            dot.textContent = '\u2022';

            const dur = document.createElement('span');
            dur.textContent = `${parseInt(movie.duration_minutes, 10)} min`;
            meta.append(dot, dur);
        }
        body.appendChild(meta);

        // Gatunki
        if (Array.isArray(movie.genres) && movie.genres.length > 0) {
            const ul = document.createElement('ul');
            ul.className = 'genre-tags';
            ul.setAttribute('aria-label', 'Gatunki');

            movie.genres.forEach((genreName) => {
                const li = document.createElement('li');
                li.className = 'genre-tag';
                li.textContent = String(genreName);
                ul.appendChild(li);
            });
            body.appendChild(ul);
        }

        article.appendChild(body);
        return article;
    };

    const renderPagination = (pagination, baseParams) => {
        paginationNav.textContent = '';
        const currentPage = parseInt(pagination.page, 10) || 1;
        const totalPages = parseInt(pagination.total_pages, 10) || 1;

        if (totalPages <= 1) {
            return;
        }

        const createPageLink = (pageNum, label, isActive = false) => {
            const link = document.createElement('a');
            const pageParams = new URLSearchParams(baseParams.toString());
            if (pageNum > 1) {
                pageParams.set('page', String(pageNum));
            } else {
                pageParams.delete('page');
            }
            const qs = pageParams.toString();
            link.href = qs ? `index.php?${qs}` : 'index.php';
            link.className = `page-link${isActive ? ' is-active' : ''}`;
            link.dataset.page = String(pageNum);
            link.textContent = label;
            if (isActive) {
                link.setAttribute('aria-current', 'page');
            }
            return link;
        };

        if (currentPage > 1) {
            paginationNav.appendChild(createPageLink(currentPage - 1, '\u00AB Poprzednia'));
        }

        for (let p = 1; p <= totalPages; p += 1) {
            paginationNav.appendChild(createPageLink(p, String(p), p === currentPage));
        }

        if (currentPage < totalPages) {
            paginationNav.appendChild(createPageLink(currentPage + 1, 'Następna \u00BB'));
        }
    };

    const fetchMovies = async (page = 1) => {
        if (activeController) {
            activeController.abort();
        }
        activeController = new AbortController();

        const params = buildParams(page);
        const apiParams = new URLSearchParams(params.toString());
        if (!apiParams.has('page')) {
            apiParams.set('page', String(page));
        }

        grid.classList.add('is-loading');
        grid.setAttribute('aria-busy', 'true');
        setStatus('Ładowanie listy filmów...');

        try {
            const response = await fetch(`api/movies_filter.php?${apiParams.toString()}`, {
                headers: { Accept: 'application/json' },
                signal: activeController.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            const movies = Array.isArray(data.movies) ? data.movies : [];
            const pagination = data.pagination || {
                page,
                total_items: movies.length,
                total_pages: 1,
            };

            grid.textContent = '';
            movies.forEach((movie) => {
                grid.appendChild(createMovieCard(movie));
            });

            if (emptyBox) {
                emptyBox.classList.toggle('is-hidden', movies.length > 0);
            }

            if (counterEl) {
                counterEl.innerHTML = '';
                counterEl.append('Liczba filmów: ');
                const strong = document.createElement('strong');
                strong.textContent = String(pagination.total_items ?? movies.length);
                counterEl.appendChild(strong);
            }

            renderPagination(pagination, params);

            const urlQuery = params.toString();
            window.history.replaceState(null, '', urlQuery ? `index.php?${urlQuery}` : 'index.php');
            setStatus('');
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            setStatus('Nie udało się pobrać listy filmów. Spróbuj odświeżyć stronę.', true);
        } finally {
            grid.classList.remove('is-loading');
            grid.setAttribute('aria-busy', 'false');
        }
    };

    const searchInput = form.querySelector('input[name="q"]');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => fetchMovies(1), 300);
        });
    }

    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => fetchMovies(1));
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        fetchMovies(1);
    });

    if (resetLink) {
        resetLink.addEventListener('click', (event) => {
            event.preventDefault();
            form.reset();
            if (searchInput) searchInput.value = '';
            form.querySelectorAll('select').forEach((sel) => {
                if (sel.name === 'sort') {
                    sel.value = 'rating_desc';
                } else {
                    sel.value = '';
                }
            });
            fetchMovies(1);
        });
    }

    paginationNav.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-page]');
        if (!link) {
            return;
        }
        event.preventDefault();
        const targetPage = parseInt(link.dataset.page || '1', 10);
        fetchMovies(targetPage);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});