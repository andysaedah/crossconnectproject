/**
 * CrossConnect MY - Main JavaScript
 * AJAX functionality and interactions
 */

// ========================================
// Path Helper - Uses AppConfig from PHP
// ========================================
function getBasePath() {
    return (window.AppConfig && window.AppConfig.basePath) || '/';
}

function apiUrl(endpoint) {
    return getBasePath() + 'api/' + endpoint.replace(/^\//, '');
}

function pageUrl(path) {
    return getBasePath() + path.replace(/^\//, '');
}

function assetUrl(path) {
    return getBasePath() + path.replace(/^\//, '');
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize components
    initMobileMenu();
    initStateFilter();
    initSearch();
    initBackToTop();
    loadChurches();
    loadStates();
});

// ========================================
// Variables
// ========================================
let currentState = 'all';
let currentDenomination = 'all';
let currentSearch = '';
let currentPage = 1;
let isLoading = false;
let hasMorePages = true;
let searchTimeout = null;
let abortController = null; // For cancelling pending requests

// ========================================
// Mobile Menu
// ========================================
function initMobileMenu() {
    const toggle = document.getElementById('mobileMenuToggle');
    const overlay = document.getElementById('mobileMenuOverlay');
    const close = document.getElementById('mobileMenuClose');

    if (!toggle || !overlay) return;

    toggle.addEventListener('click', () => {
        toggle.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = overlay.classList.contains('active') ? 'hidden' : '';
    });

    if (close) {
        close.addEventListener('click', () => {
            toggle.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            toggle.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

// ========================================
// Load States
// ========================================
async function loadStates() {
    try {
        const response = await fetch(apiUrl('states.php'));
        const data = await response.json();

        if (data.success && data.data) {
            populateStateFilter(data.data);
            populateStatePills(data.data);
            populateMobileMenu(data.data);
        }
    } catch (error) {
        console.error('Failed to load states:', error);
    }
}

function populateStateFilter(states) {
    const select = document.getElementById('stateFilter');
    if (!select) return;

    states.forEach(state => {
        const option = document.createElement('option');
        option.value = state.slug;
        option.textContent = state.name;
        select.appendChild(option);
    });
}

function populateStatePills(states) {
    const container = document.getElementById('statePills');
    if (!container) return;

    // Keep only first 7 states visible, rest in "View All"
    const visibleStates = states.slice(0, 7);

    visibleStates.forEach(state => {
        const pill = document.createElement('button');
        pill.className = 'state-pill';
        pill.dataset.state = state.slug;
        pill.textContent = state.name;
        pill.addEventListener('click', () => selectState(state.slug));
        container.appendChild(pill);
    });

    // Add "View All" button if there are more states
    if (states.length > 7) {
        const viewAll = document.createElement('button');
        viewAll.className = 'state-pill state-pill-more';
        viewAll.textContent = window.AppConfig?.translations?.viewAll || 'View All';
        viewAll.addEventListener('click', () => showAllStates(states));
        container.appendChild(viewAll);
    }
}

function populateMobileMenu(states) {
    const container = document.getElementById('mobileMenuContent');
    if (!container) return;

    // All States option
    const allItem = document.createElement('a');
    allItem.href = '#';
    allItem.className = 'mobile-menu-item active';
    allItem.dataset.state = 'all';
    allItem.textContent = window.AppConfig?.translations?.allStates || 'All States';
    allItem.addEventListener('click', (e) => {
        e.preventDefault();
        selectState('all');
        closeMobileMenu();
    });
    container.appendChild(allItem);

    states.forEach(state => {
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'mobile-menu-item';
        item.dataset.state = state.slug;
        item.textContent = state.name;
        item.addEventListener('click', (e) => {
            e.preventDefault();
            selectState(state.slug);
            closeMobileMenu();
        });
        container.appendChild(item);
    });
}

function closeMobileMenu() {
    const toggle = document.getElementById('mobileMenuToggle');
    const overlay = document.getElementById('mobileMenuOverlay');

    if (toggle) toggle.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// ========================================
// State Filter
// ========================================
function initStateFilter() {
    const select = document.getElementById('stateFilter');
    if (select) {
        select.addEventListener('change', (e) => {
            selectState(e.target.value);
        });
    }

    // Handle initial "All States" pill
    const allPill = document.querySelector('.state-pill[data-state="all"]');
    if (allPill) {
        allPill.addEventListener('click', () => selectState('all'));
    }
}

function selectState(state) {
    currentState = state;
    currentPage = 1;

    // Update UI
    updateStatePillsUI(state);
    updateMobileMenuUI(state);
    updateSelectUI(state);

    // Reload churches
    loadChurches(true);
}

function updateStatePillsUI(state) {
    const pills = document.querySelectorAll('.state-pill');
    pills.forEach(pill => {
        pill.classList.toggle('active', pill.dataset.state === state);
    });
}

function updateMobileMenuUI(state) {
    const items = document.querySelectorAll('.mobile-menu-item');
    items.forEach(item => {
        item.classList.toggle('active', item.dataset.state === state);
    });
}

function updateSelectUI(state) {
    const select = document.getElementById('stateFilter');
    if (select) {
        select.value = state;
    }
}

function showAllStates(states) {
    // For now, just redirect to a state listing or expand all
    const container = document.getElementById('statePills');
    if (!container) return;

    container.innerHTML = '';

    // Add "All States" first
    const allPill = document.createElement('button');
    allPill.className = 'state-pill' + (currentState === 'all' ? ' active' : '');
    allPill.dataset.state = 'all';
    allPill.textContent = window.AppConfig?.translations?.allStates || 'All States';
    allPill.addEventListener('click', () => selectState('all'));
    container.appendChild(allPill);

    states.forEach(state => {
        const pill = document.createElement('button');
        pill.className = 'state-pill' + (currentState === state.slug ? ' active' : '');
        pill.dataset.state = state.slug;
        pill.textContent = state.name;
        pill.addEventListener('click', () => selectState(state.slug));
        container.appendChild(pill);
    });
}

// ========================================
// Search
// ========================================
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchInputMobile = document.getElementById('searchInputMobile');

    const handleSearch = (e) => {
        const value = e.target.value.trim();

        // Debounce search
        if (searchTimeout) clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            currentSearch = value;
            currentPage = 1;
            loadChurches(true);
        }, 300);
    };

    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
    }

    if (searchInputMobile) {
        searchInputMobile.addEventListener('input', handleSearch);

        // Sync with desktop input
        searchInputMobile.addEventListener('input', (e) => {
            if (searchInput) searchInput.value = e.target.value;
        });
    }
}

// ========================================
// Load Churches
// ========================================
async function loadChurches(reset = false) {
    if (isLoading) return;

    const grid = document.getElementById('churchesGrid');
    if (!grid) return;

    if (reset) {
        grid.innerHTML = '';
        currentPage = 1;
        hasMorePages = true;
    }

    if (!hasMorePages) return;

    // Cancel any pending request
    if (abortController) {
        abortController.abort();
    }
    abortController = new AbortController();

    isLoading = true;
    showLoading(grid, reset);

    try {
        const params = new URLSearchParams({
            page: currentPage,
            limit: 12
        });

        if (currentState && currentState !== 'all') {
            params.append('state', currentState);
        }

        if (currentDenomination && currentDenomination !== 'all') {
            params.append('denomination', currentDenomination);
        }

        if (currentSearch) {
            params.append('search', currentSearch);
        }

        const response = await fetch(`${apiUrl('churches.php')}?${params}`, {
            signal: abortController.signal
        });
        const data = await response.json();

        hideLoading(grid);

        if (data.success && data.data) {
            if (data.data.length === 0 && currentPage === 1) {
                showEmptyState(grid);
            } else {
                renderChurches(grid, data.data);

                // Check if there are more pages
                if (data.pagination) {
                    hasMorePages = currentPage < data.pagination.total_pages;
                    currentPage++;

                    if (hasMorePages) {
                        showLoadMoreButton(grid);
                    }
                }
            }
        }
    } catch (error) {
        // Ignore abort errors (intentional cancellation)
        if (error.name === 'AbortError') {
            isLoading = false;
            return;
        }
        console.error('Failed to load churches:', error);
        hideLoading(grid);
        showErrorState(grid);
    }

    isLoading = false;
}

function showLoading(grid, reset) {
    if (reset) {
        // Show skeleton cards for better perceived loading
        const skeletonCount = window.innerWidth >= 1280 ? 8 : window.innerWidth >= 1024 ? 6 : window.innerWidth >= 640 ? 4 : 2;
        let skeletons = '';
        for (let i = 0; i < skeletonCount; i++) {
            skeletons += `
                <div class="skeleton-card">
                    <div class="skeleton-card__image"></div>
                    <div class="skeleton-card__content">
                        <div class="skeleton skeleton-card__title"></div>
                        <div class="skeleton skeleton-card__text"></div>
                        <div class="skeleton skeleton-card__text skeleton-card__text--short"></div>
                    </div>
                </div>
            `;
        }
        grid.innerHTML = skeletons;
    }
}

function hideLoading(grid) {
    // Remove skeleton cards
    const skeletons = grid.querySelectorAll('.skeleton-card');
    skeletons.forEach(s => s.remove());
    // Also remove legacy spinner if present
    const loader = grid.querySelector('.loading-state');
    if (loader) loader.remove();
}

function showEmptyState(grid) {
    const t = window.AppConfig?.translations || {};
    grid.innerHTML = `
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L14 8H20L15 12L17 18L12 14L7 18L9 12L4 8H10L12 2Z" stroke="currentColor" stroke-width="2"/>
            </svg>
            <h3>${t.noChurchesFound || 'No churches found'}</h3>
            <p>${t.noChurchesDesc || 'Try adjusting your search or filter to find churches.'}</p>
        </div>
    `;
}

function showErrorState(grid) {
    const t = window.AppConfig?.translations || {};
    grid.innerHTML = `
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <h3>${t.somethingWentWrong || 'Something went wrong'}</h3>
            <p>${t.tryAgainLater || 'Please try again later.'}</p>
        </div>
    `;
}

function showLoadMoreButton(grid) {
    const existing = grid.querySelector('.load-more-container');
    if (existing) existing.remove();

    const t = window.AppConfig?.translations || {};
    const container = document.createElement('div');
    container.className = 'load-more-container';
    container.innerHTML = `<button class="load-more-btn">${t.loadMoreChurches || 'Load More Churches'}</button>`;

    container.querySelector('.load-more-btn').addEventListener('click', () => {
        container.remove();
        loadChurches();
    });

    grid.appendChild(container);
}

function renderChurches(grid, churches) {
    // Remove load more button if exists
    const loadMore = grid.querySelector('.load-more-container');
    if (loadMore) loadMore.remove();

    churches.forEach(church => {
        const card = createChurchCard(church);
        grid.appendChild(card);
    });
}

function createChurchCard(church) {
    const card = document.createElement('article');
    card.className = 'church-card';

    const imageUrl = church.image_url || assetUrl('images/placeholder.jpg');
    const socialLinks = [];

    if (church.phone) {
        socialLinks.push(`
            <a href="tel:${church.phone}" class="social-link" title="Call">
                <svg viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="2"/></svg>
            </a>
        `);
    }

    if (church.website) {
        socialLinks.push(`
            <a href="${church.website}" target="_blank" rel="noopener" class="social-link" title="Website">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z" stroke="currentColor" stroke-width="2"/></svg>
            </a>
        `);
    }

    if (church.facebook) {
        socialLinks.push(`
            <a href="https://facebook.com/${church.facebook}" target="_blank" rel="noopener" class="social-link" title="Facebook">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
            </a>
        `);
    }

    if (church.instagram) {
        socialLinks.push(`
            <a href="https://instagram.com/${church.instagram}" target="_blank" rel="noopener" class="social-link" title="Instagram">
                <svg viewBox="0 0 24 24" fill="none"><rect x="2" y="2" width="20" height="20" rx="5" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="6" r="1" fill="currentColor"/></svg>
            </a>
        `);
    }

    if (church.youtube) {
        socialLinks.push(`
            <a href="https://youtube.com/@${church.youtube}" target="_blank" rel="noopener" class="social-link" title="YouTube">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="white"/></svg>
            </a>
        `);
    }

    // Generate service language badges
    let languageBadges = '';
    if (church.service_languages) {
        const langs = church.service_languages.split(',').filter(l => l.trim());
        const langLabels = {
            'bm': 'BM',
            'en': 'EN',
            'chinese': '中文',
            'tamil': 'தமிழ்',
            'other': '+'
        };
        languageBadges = langs.map(lang => {
            const l = lang.trim();
            const label = langLabels[l] || l.toUpperCase();
            return `<span class="church-lang-badge church-lang-${l}">${escapeHtml(label)}</span>`;
        }).join('');

        if (languageBadges) {
            languageBadges = `<div class="church-card-languages">${languageBadges}</div>`;
        }
    }

    card.innerHTML = `
        <div class="church-card-image">
            <img src="${imageUrl}" alt="${escapeHtml(church.name)}" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\\'church-card-image-placeholder\\'><svg viewBox=\\'0 0 24 24\\' fill=\\'none\\'><path d=\\'M18 21H6V12L3 12L12 3L21 12L18 12V21Z\\' stroke=\\'currentColor\\' stroke-width=\\'2\\' stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\'/><path d=\\'M12 3V8\\' stroke=\\'currentColor\\' stroke-width=\\'2\\' stroke-linecap=\\'round\\'/><path d=\\'M9 21V15H15V21\\' stroke=\\'currentColor\\' stroke-width=\\'2\\' stroke-linecap=\\'round\\' stroke-linejoin=\\'round\\'/></svg></div>'">
        </div>
        <div class="church-card-content">
            <h3 class="church-card-name">
                <a href="${pageUrl('church.php?slug=' + church.slug)}">${escapeHtml(church.name)}</a>
            </h3>
            ${church.denomination_name ? `<div class="church-card-denomination">${escapeHtml(church.denomination_name)}</div>` : ''}
            <div class="church-card-location">
                <svg viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/></svg>
                <span>${escapeHtml(church.city || '')}${church.state_name ? ', ' + escapeHtml(church.state_name) : ''}</span>
            </div>
            ${languageBadges}
            ${socialLinks.length > 0 ? `<div class="church-card-social">${socialLinks.join('')}</div>` : ''}
        </div>
    `;

    return card;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========================================
// Back to Top
// ========================================
function initBackToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;

    window.addEventListener('scroll', () => {
        btn.classList.toggle('visible', window.scrollY > 400);
    });

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// ========================================
// Utility: URL params
// ========================================
function getUrlParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

// Initialize from URL params
document.addEventListener('DOMContentLoaded', () => {
    const stateParam = getUrlParam('state');
    const searchParam = getUrlParam('search');
    const denominationParam = getUrlParam('denomination');

    if (stateParam) {
        currentState = stateParam;
    }

    if (denominationParam) {
        currentDenomination = denominationParam;
    }

    if (searchParam) {
        currentSearch = searchParam;
        const searchInput = document.getElementById('searchInput');
        const searchInputMobile = document.getElementById('searchInputMobile');
        if (searchInput) searchInput.value = searchParam;
        if (searchInputMobile) searchInputMobile.value = searchParam;
    }
});
