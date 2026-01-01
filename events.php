<?php
/**
 * CrossConnect MY - Events Listing Page
 * Upcoming Christian events in Malaysia
 */

require_once 'config/language.php';

$pageTitle = __('upcoming_christian_events');
$pageDescription = __('discover_events_desc');

// Structured data
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => __('upcoming_christian_events'),
    'description' => $pageDescription,
    'url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mychurchfind.my') . '/events.php'
];

require_once 'includes/header.php';
?>

<!-- Page Header with Search -->
<div class="page-header events-header">
    <div class="page-header-info">
        <h1 class="page-title"><?php _e('upcoming_events'); ?></h1>
        <p class="page-subtitle"><?php _e('upcoming_events_subtitle'); ?></p>
    </div>
    <div class="page-header-filters">
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="M21 21l-4.35-4.35"></path>
            </svg>
            <input type="text" id="eventSearchInput" placeholder="<?php _e('search_events'); ?>" autocomplete="off">
        </div>
    </div>
</div>

<style>
    .events-header {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 24px;
    }

    .events-header .page-header-filters {
        width: 100%;
    }

    .events-header .search-box {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .events-header .search-box svg {
        position: absolute;
        left: 14px;
        width: 18px;
        height: 18px;
        color: var(--color-text-light);
        pointer-events: none;
    }

    .events-header .search-box input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 1px solid var(--color-border);
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.2s;
    }

    .events-header .search-box input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--color-primary-bg);
    }

    @media (min-width: 768px) {
        .events-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }

        .events-header .page-header-filters {
            width: auto;
        }

        .events-header .search-box input {
            width: 280px;
        }
    }

    /* Event Format Badge */
    .event-format-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 16px;
        font-size: 0.75rem;
        font-weight: 600;
        margin: 8px 0 4px;
    }

    .event-format-badge svg {
        width: 12px;
        height: 12px;
    }

    .event-format-badge.format-in-person {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
    }

    .event-format-badge.format-online {
        background: rgba(59, 130, 246, 0.12);
        color: #2563eb;
    }

    .event-format-badge.format-hybrid {
        background: rgba(139, 92, 246, 0.12);
        color: #7c3aed;
    }
</style>

<!-- Events Grid -->
<section class="events-section">
    <div class="events-grid" id="eventsGrid">
        <!-- Events loaded via AJAX -->
        <div class="loading-state">
            <div class="spinner"></div>
        </div>
    </div>
</section>

<script>
    // Path helpers (needed before main.js loads)
    function getBasePath() {
        return (window.AppConfig && window.AppConfig.basePath) || '/';
    }
    function apiUrl(endpoint) {
        return getBasePath() + 'api/' + endpoint.replace(/^\//, '');
    }
    function assetUrl(path) {
        return getBasePath() + path.replace(/^\//, '');
    }
    function pageUrl(path) {
        return getBasePath() + path.replace(/^\//, '');
    }
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Translation strings for JavaScript
    const translations = {
        featured: '<?php echo addslashes(__('featured')); ?>',
        viewDetails: '<?php echo addslashes(__('view_details')); ?>',
        register: '<?php echo addslashes(__('register')); ?>',
        noUpcomingEvents: '<?php echo addslashes(__('no_upcoming_events')); ?>',
        noEventsMessage: '<?php echo addslashes(__('no_events_message')); ?>',
        unableToLoad: '<?php echo addslashes(__('unable_to_load')); ?>',
        tryAgainLater: '<?php echo addslashes(__('try_again_later')); ?>',
        formatInPerson: '<?php echo addslashes(__('format_in_person')); ?>',
        formatOnline: '<?php echo addslashes(__('format_online')); ?>',
        formatHybrid: '<?php echo addslashes(__('format_hybrid')); ?>'
    };

    let searchTimeout = null;
    let eventAbortController = null;

    document.addEventListener('DOMContentLoaded', function () {
        loadEvents();

        // Search with debounce
        const searchInput = document.getElementById('eventSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadEvents(e.target.value.trim());
                }, 300);
            });
        }
    });

    async function loadEvents(search = '') {
        const grid = document.getElementById('eventsGrid');

        // Cancel pending request
        if (eventAbortController) {
            eventAbortController.abort();
        }
        eventAbortController = new AbortController();

        try {
            const params = new URLSearchParams({
                upcoming: 1,
                limit: 20
            });

            if (search) {
                params.append('search', search);
            }

            const response = await fetch(apiUrl('events.php?' + params), {
                signal: eventAbortController.signal
            });
            const data = await response.json();

            grid.innerHTML = '';

            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(event => {
                    const card = createEventCard(event);
                    grid.appendChild(card);
                });
            } else {
                grid.innerHTML = `
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M16 2V6M8 2V6M3 10H21" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <h3>${translations.noUpcomingEvents}</h3>
                    <p>${translations.noEventsMessage}</p>
                </div>
            `;
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Failed to load events:', error);
            grid.innerHTML = `
            <div class="empty-state">
                <h3>${translations.unableToLoad}</h3>
                <p>${translations.tryAgainLater}</p>
            </div>
        `;
        }
    }

    function createEventCard(event) {
        const card = document.createElement('article');
        card.className = 'event-card' + (event.is_featured ? ' featured' : '');

        const posterUrl = event.poster_url || assetUrl('images/event-placeholder.svg');
        const eventDate = new Date(event.event_date);
        const day = eventDate.getDate();
        const month = eventDate.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();

        let dateDisplay = `${day} ${month}`;
        if (event.event_end_date && event.event_end_date !== event.event_date) {
            const endDate = new Date(event.event_end_date);
            dateDisplay += ` - ${endDate.getDate()} ${endDate.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()}`;
        }

        // Event format badge
        const eventFormat = event.event_format || 'in_person';
        let formatBadge = '';
        if (eventFormat === 'online') {
            formatBadge = `<span class="event-format-badge format-online">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                ${translations.formatOnline}
            </span>`;
        } else if (eventFormat === 'hybrid') {
            formatBadge = `<span class="event-format-badge format-hybrid">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                ${translations.formatHybrid}
            </span>`;
        } else {
            formatBadge = `<span class="event-format-badge format-in-person">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                ${translations.formatInPerson}
            </span>`;
        }

        card.innerHTML = `
        <div class="event-card-poster">
            <img src="${posterUrl}" alt="${escapeHtml(event.name)}" loading="lazy">
            <div class="event-date-badge">
                <span class="event-date-day">${day}</span>
                <span class="event-date-month">${month}</span>
            </div>
            ${event.is_featured ? `<span class="featured-badge">${translations.featured}</span>` : ''}
        </div>
        <div class="event-card-content">
            <h3 class="event-card-title">
                <a href="${pageUrl('event.php?slug=' + event.slug)}">${escapeHtml(event.name)}</a>
            </h3>
            ${formatBadge}
            <div class="event-card-meta">
                <div class="event-meta-item">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M16 2V6M8 2V6M3 10H21" stroke="currentColor" stroke-width="2"/></svg>
                    <span>${dateDisplay}${event.event_time ? ' • ' + escapeHtml(event.event_time) : ''}</span>
                </div>
                ${event.venue ? `
                <div class="event-meta-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/></svg>
                    <span>${escapeHtml(event.venue)}${event.state_name ? ', ' + escapeHtml(event.state_name) : ''}</span>
                </div>
                ` : ''}
                ${event.organizer ? `
                <div class="event-meta-item">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2"/></svg>
                    <span>${escapeHtml(event.organizer)}</span>
                </div>
                ` : ''}
            </div>
            <div class="event-card-actions">
                <a href="${pageUrl('event.php?slug=' + event.slug)}" class="btn-event-details">${translations.viewDetails}</a>
                ${event.registration_url ? `<a href="${event.registration_url}" target="_blank" rel="noopener" class="btn-event-register">${translations.register}</a>` : ''}
            </div>
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
</script>

<?php require_once 'includes/footer.php'; ?>