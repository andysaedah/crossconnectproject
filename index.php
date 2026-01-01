<?php
/**
 * CrossConnect MY - Home Page
 * Malaysia Church Directory
 */

$pageTitle = 'Find Churches in Malaysia';
$pageDescription = 'Discover churches across all states in Malaysia. Find contact information, locations, service times, and connect with Christian communities near you.';

// Structured data for SEO
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'CrossConnect MY',
    'description' => $pageDescription,
    'url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mychurchfind.my'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mychurchfind.my') . '/?search={search_term_string}',
        'query-input' => 'required name=search_term_string'
    ]
];

require_once 'includes/header.php';
?>

<!-- Church Directory Section -->
<section class="homepage-section">
    <div class="section-header">
        <h2><i class="fa-solid fa-church" aria-hidden="true"></i> <?php _e('church_directory'); ?></h2>
    </div>

    <!-- State Filter Pills -->
    <div class="state-filter">
        <p class="state-filter-label"><?php _e('state'); ?></p>
        <div class="state-pills" id="statePills">
            <button class="state-pill active" data-state="all"><?php _e('all_states'); ?></button>
            <!-- More pills loaded via AJAX -->
        </div>
    </div>
</section>

<!-- Churches Grid -->
<section class="churches-section">
    <div class="churches-grid" id="churchesGrid">
        <!-- Churches loaded via AJAX -->
        <div class="loading-state">
            <div class="spinner"></div>
        </div>
    </div>
</section>

<!-- Scripture Quote -->
<section class="scripture-quote-section" style="padding: 32px 16px; background: var(--color-bg, #f8fafc);">
    <div class="scripture-card"
        style="max-width: 700px; margin: 0 auto; background: white; border-radius: 16px; padding: 32px 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid rgba(8, 145, 178, 0.1); position: relative; overflow: hidden;">
        <!-- Decorative accent bar -->
        <div
            style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #0891b2, #06b6d4);">
        </div>

        <!-- Quote marks decoration -->
        <div
            style="position: absolute; top: 16px; left: 20px; font-size: 4rem; color: rgba(8, 145, 178, 0.08); font-family: Georgia, serif; line-height: 1;">
            "</div>

        <div style="text-align: center; position: relative; z-index: 1;">
            <blockquote
                style="font-size: 1.125rem; font-weight: 500; color: #374151; line-height: 1.9; margin: 0 0 20px; font-style: italic; padding: 0 16px;">
                "<?php _e('scripture_galatians_6_10'); ?>"
            </blockquote>
            <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                <div style="width: 28px; height: 2px; background: linear-gradient(90deg, transparent, #0891b2);"></div>
                <cite
                    style="font-size: 0.875rem; font-weight: 600; color: #0891b2; font-style: normal; letter-spacing: 0.5px;"><?php _e('scripture_galatians_6_10_ref'); ?></cite>
                <div style="width: 28px; height: 2px; background: linear-gradient(90deg, #0891b2, transparent);"></div>
            </div>
        </div>
    </div>
</section>

<!-- Event Highlights Carousel -->
<section class="event-highlights-section" id="eventHighlightsSection">
    <div class="section-header">
        <h2><i class="fa-solid fa-star" aria-hidden="true"></i> <?php _e('event_highlights'); ?></h2>
        <a href="<?php echo url('events.php'); ?>" class="view-all-link">
            <?php _e('view_all'); ?>
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M5 12H19M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </a>
    </div>
    <div class="carousel-container">
        <div class="carousel-track" id="eventCarouselTrack">
            <!-- Events loaded via AJAX -->
            <div class="carousel-loading">
                <div class="spinner"></div>
            </div>
        </div>
        <div class="carousel-dots" id="carouselDots"></div>
    </div>
</section>

<!-- Upcoming Events Link Section -->
<section class="upcoming-events-cta" id="upcomingEventsCta">
    <div class="cta-content">
        <div class="cta-icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></div>
        <div class="cta-text">
            <h3><?php _e('upcoming_events'); ?></h3>
            <p><?php _e('upcoming_events_desc'); ?></p>
        </div>
        <a href="<?php echo url('events.php'); ?>" class="cta-button">
            <?php _e('browse_all_events'); ?>
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M5 12H19M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </a>
    </div>
</section>

<script>
    // Carousel state
    let carouselEvents = [];
    let currentSlide = 0;
    let carouselInterval = null;
    const SLIDE_INTERVAL = 5000; // 5 seconds

    // Load event highlights
    document.addEventListener('DOMContentLoaded', function () {
        loadEventHighlights();
    });

    async function loadEventHighlights() {
        const track = document.getElementById('eventCarouselTrack');
        if (!track) return;

        try {
            // Fetch events within the next 1 month
            const response = await fetch(apiUrl('events.php?upcoming=1&limit=6'));
            const data = await response.json();

            if (data.success && data.data && data.data.length > 0) {
                carouselEvents = data.data;
                renderCarousel(track);
                startCarousel();
                window.addEventListener('resize', handleResize);
            } else {
                // Hide section if no events
                document.getElementById('eventHighlightsSection').style.display = 'none';
            }
        } catch (error) {
            console.error('Failed to load event highlights:', error);
            document.getElementById('eventHighlightsSection').style.display = 'none';
        }
    }

    function getVisibleSlides() {
        return window.innerWidth >= 768 ? 2 : 1;
    }

    function getTotalSlides() {
        const visibleSlides = getVisibleSlides();
        return Math.ceil(carouselEvents.length / visibleSlides);
    }

    function renderCarousel(track) {
        track.innerHTML = '';

        carouselEvents.forEach((event, index) => {
            const slide = createCarouselSlide(event, index);
            track.appendChild(slide);
        });

        renderDots();
        updateCarouselPosition();
    }

    function createCarouselSlide(event, index) {
        const slide = document.createElement('div');
        slide.className = 'carousel-slide';

        const posterUrl = event.poster_url || assetUrl('images/event-placeholder.svg');
        const eventDate = new Date(event.event_date);
        const day = eventDate.getDate();
        const month = eventDate.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();

        // Check if event has end date
        let dateDisplay = `${day} ${month}`;
        if (event.event_end_date && event.event_end_date !== event.event_date) {
            const endDate = new Date(event.event_end_date);
            const endDay = endDate.getDate();
            const endMonth = endDate.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
            dateDisplay = `${day} - ${endDay} ${endMonth}`;
        }

        slide.innerHTML = `
            <a href="${pageUrl('event.php?slug=' + event.slug)}" class="carousel-slide-link">
                <div class="carousel-poster">
                    <img src="${posterUrl}" alt="${escapeHtml(event.name)}" loading="lazy">
                    <div class="carousel-overlay">
                        <div class="carousel-date-badge">${dateDisplay}</div>
                        <h3 class="carousel-title">${escapeHtml(event.name)}</h3>
                        ${event.venue ? `<p class="carousel-venue">📍 ${escapeHtml(event.venue)}</p>` : ''}
                    </div>
                </div>
            </a>
        `;

        return slide;
    }

    function renderDots() {
        const dotsContainer = document.getElementById('carouselDots');
        if (!dotsContainer) return;

        dotsContainer.innerHTML = '';
        const totalSlides = getTotalSlides();

        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('button');
            dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        }
    }

    function updateCarouselPosition() {
        const track = document.getElementById('eventCarouselTrack');
        const dotsContainer = document.getElementById('carouselDots');
        if (!track) return;

        const visibleSlides = getVisibleSlides();
        const slideWidth = 100 / visibleSlides;
        const offset = currentSlide * visibleSlides * slideWidth;

        track.style.transform = `translateX(-${offset}%)`;

        // Update dots
        if (dotsContainer) {
            const dots = dotsContainer.querySelectorAll('.carousel-dot');
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }
    }

    function goToSlide(index) {
        currentSlide = index;
        updateCarouselPosition();
        resetCarouselTimer();
    }

    function nextSlide() {
        const totalSlides = getTotalSlides();
        currentSlide = (currentSlide + 1) % totalSlides;
        updateCarouselPosition();
    }

    function startCarousel() {
        if (carouselInterval) clearInterval(carouselInterval);
        carouselInterval = setInterval(nextSlide, SLIDE_INTERVAL);
    }

    function resetCarouselTimer() {
        startCarousel();
    }

    function handleResize() {
        // Recalculate on resize
        currentSlide = 0;
        renderDots();
        updateCarouselPosition();
    }

    // Pause carousel on hover
    document.addEventListener('DOMContentLoaded', () => {
        const carousel = document.querySelector('.carousel-container');
        if (carousel) {
            carousel.addEventListener('mouseenter', () => {
                if (carouselInterval) clearInterval(carouselInterval);
            });
            carousel.addEventListener('mouseleave', () => {
                startCarousel();
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>