<?php
/**
 * CrossConnect MY - Event Detail Page
 * Individual event information
 */

require_once 'config/paths.php';
require_once 'config/database.php';

// Get event slug
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: ' . url('events.php'));
    exit;
}

// Try database first
$event = dbQuerySingle("
    SELECT e.*, s.name as state_name, s.slug as state_slug
    FROM events e
    LEFT JOIN states s ON e.state_id = s.id
    WHERE e.slug = ?
", [$slug]);

$useDemoData = false;

// Fall back to demo data
if (!$event) {
    require_once 'config/demo_data.php';

    if (function_exists('getDemoEvents')) {
        $allEvents = getDemoEvents();

        foreach ($allEvents as $e) {
            if ($e['slug'] === $slug) {
                $event = $e;
                $useDemoData = true;
                break;
            }
        }
    }
}

if (!$event) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = 'Event Not Found';
    require_once 'includes/header.php';
    echo '<div class="empty-state"><h3>Event not found</h3><p>The event you are looking for does not exist.</p><a href="' . url('events.php') . '" class="church-card-btn" style="display:inline-block;width:auto;">View All Events</a></div>';
    require_once 'includes/footer.php';
    exit;
}

// Page meta
$pageTitle = $event['name'];
$pageDescription = strip_tags(substr($event['description'] ?? '', 0, 160));
$ogImage = $event['poster_url'] ?: '/images/og-default.png';

// Format dates
$eventDate = new DateTime($event['event_date']);
$formattedDate = $eventDate->format('l, F j, Y');

if (!empty($event['event_end_date']) && $event['event_end_date'] !== $event['event_date']) {
    $endDate = new DateTime($event['event_end_date']);
    $formattedDate = $eventDate->format('F j') . ' - ' . $endDate->format('j, Y');
}

// Structured data
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => $event['name'],
    'description' => $event['description'],
    'startDate' => $event['event_date'],
    'endDate' => $event['event_end_date'] ?? $event['event_date'],
    'eventStatus' => 'https://schema.org/EventScheduled',
    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
    'location' => [
        '@type' => 'Place',
        'name' => $event['venue'] ?? '',
        'address' => $event['venue_address'] ?? ''
    ],
    'organizer' => [
        '@type' => 'Organization',
        'name' => $event['organizer'] ?? ''
    ]
];

if (!empty($event['poster_url'])) {
    $structuredData['image'] = $event['poster_url'];
}

require_once 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo url('/'); ?>"><?php _e('home'); ?></a>
    <span class="breadcrumb-separator">›</span>
    <a href="<?php echo url('events.php'); ?>"><?php _e('events'); ?></a>
    <span class="breadcrumb-separator">›</span>
    <span><?php echo htmlspecialchars($event['name']); ?></span>
</nav>

<!-- Mobile Title (visible only on mobile) -->
<h1 class="event-mobile-title mobile-only"><?php echo htmlspecialchars($event['name']); ?></h1>

<div class="church-detail">
    <!-- Main Content -->
    <div class="church-detail-main">
        <!-- Hero Image (Event Poster) -->
        <?php if (!empty($event['poster_url'])): ?>
            <div class="church-hero-image">
                <img src="<?php echo htmlspecialchars($event['poster_url']); ?>" loading="eager" decoding="async"
                    fetchpriority="high" alt="<?php echo htmlspecialchars($event['name']); ?>">
            </div>
        <?php endif; ?>

        <!-- Title -->
        <h1 class="church-title"><?php echo htmlspecialchars($event['name']); ?></h1>

        <!-- Meta -->
        <div class="church-meta">
            <span class="church-meta-item">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"></rect>
                    <path d="M16 2V6M8 2V6M3 10H21" stroke="currentColor" stroke-width="2"></path>
                </svg>
                <?php echo $formattedDate; ?>
                <?php if (!empty($event['event_time'])): ?>
                    • <?php echo htmlspecialchars($event['event_time']); ?>
                <?php endif; ?>
            </span>
            <?php if (!empty($event['organizer'])): ?>
                <span class="church-meta-item">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2"></path>
                        <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"></circle>
                    </svg>
                    <?php _e('organized_by'); ?>: <?php echo htmlspecialchars($event['organizer']); ?>
                </span>
            <?php endif; ?>
            <?php if (!empty($event['venue'])): ?>
                <span class="church-meta-item">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="currentColor" stroke-width="2">
                        </path>
                        <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"></circle>
                    </svg>
                    <?php echo htmlspecialchars($event['venue']); ?>     <?php if (!empty($event['state_name'])): ?>,
                        <?php echo htmlspecialchars($event['state_name']); ?>     <?php endif; ?>
                </span>
            <?php endif; ?>
            <?php
            // Event Format Badge
            $eventFormat = $event['event_format'] ?? 'in_person';
            $formatClass = 'format-' . str_replace('_', '-', $eventFormat);
            $formatIcon = '';
            $formatLabel = '';

            switch ($eventFormat) {
                case 'online':
                    $formatIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>';
                    $formatLabel = __('format_online');
                    break;
                case 'hybrid':
                    $formatIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>';
                    $formatLabel = __('format_hybrid');
                    break;
                default: // in_person
                    $formatIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>';
                    $formatLabel = __('format_in_person');
            }
            ?>
            <span class="event-format-badge <?php echo $formatClass; ?>">
                <?php echo $formatIcon; ?>
                <?php echo $formatLabel; ?>
            </span>
        </div>

        <!-- Description -->
        <?php if (!empty($event['description'])): ?>
            <div class="church-description">
                <?php
                $desc = htmlspecialchars($event['description']);
                $desc = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $desc);
                $desc = preg_replace('/(https?:\/\/[^\s<]+)/', '<a href="$1" target="_blank" rel="noopener">$1</a>', $desc);
                $desc = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $desc);
                echo nl2br($desc);
                ?>
            </div>
        <?php endif; ?>

        <!-- Map (if venue has address) -->
        <?php if (!empty($event['venue_address'])): ?>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=<?php echo urlencode($event['venue_address'] . ', Malaysia'); ?>&zoom=15"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="church-detail-sidebar">
        <div class="contact-card">
            <h2 class="contact-card-title"><?php _e('event_details'); ?></h2>

            <div class="contact-list">
                <!-- Date & Time -->
                <div class="contact-list-item">
                    <div class="contact-list-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2">
                            </rect>
                            <path d="M16 2V6M8 2V6M3 10H21" stroke="currentColor" stroke-width="2"></path>
                        </svg>
                    </div>
                    <div class="contact-list-content">
                        <div class="contact-list-label"><?php _e('date_and_time'); ?></div>
                        <div class="contact-list-value">
                            <?php echo $formattedDate; ?>
                            <?php if (!empty($event['event_time'])): ?>
                                <br><?php echo htmlspecialchars($event['event_time']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Venue -->
                <?php if (!empty($event['venue'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="currentColor"
                                    stroke-width="2"></path>
                                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"></circle>
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('venue'); ?></div>
                            <div class="contact-list-value">
                                <?php echo htmlspecialchars($event['venue']); ?>
                                <?php if (!empty($event['venue_address'])): ?>
                                    <br><small
                                        style="color: var(--color-text-light);"><?php echo htmlspecialchars($event['venue_address']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Organizer -->
                <?php if (!empty($event['organizer'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2">
                                </path>
                                <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"></circle>
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('organized_by'); ?></div>
                            <div class="contact-list-value"><?php echo htmlspecialchars($event['organizer']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- WhatsApp -->
                <?php if (!empty($event['whatsapp'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon whatsapp-icon">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('whatsapp'); ?></div>
                            <div class="contact-list-value">
                                <a href="https://wa.me/<?php echo htmlspecialchars($event['whatsapp']); ?>" target="_blank"
                                    rel="noopener">+<?php echo htmlspecialchars($event['whatsapp']); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Email -->
                <?php if (!empty($event['email'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"
                                    stroke="currentColor" stroke-width="2"></path>
                                <polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2"></polyline>
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('email'); ?></div>
                            <div class="contact-list-value">
                                <a
                                    href="mailto:<?php echo htmlspecialchars($event['email']); ?>"><?php echo htmlspecialchars($event['email']); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Phone -->
                <?php if (!empty($event['phone'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"
                                    stroke="currentColor" stroke-width="2"></path>
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('phone'); ?></div>
                            <div class="contact-list-value">
                                <a
                                    href="tel:<?php echo htmlspecialchars($event['phone']); ?>"><?php echo htmlspecialchars($event['phone']); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Online Links (only for online or hybrid events) -->
            <?php
            $eventFormat = $event['event_format'] ?? 'in_person';
            $showOnlineLinks = ($eventFormat === 'online' || $eventFormat === 'hybrid') && (!empty($event['meeting_url']) || !empty($event['livestream_url']));
            if ($showOnlineLinks):
                ?>
                <div class="online-links-section">
                    <h3 class="service-languages-title"><?php _e('join_online'); ?></h3>
                    <div class="online-links-buttons">
                        <?php if (!empty($event['meeting_url'])): ?>
                            <a href="<?php echo htmlspecialchars($event['meeting_url']); ?>" target="_blank" rel="noopener"
                                class="online-link-btn meeting">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M23 7l-7 5 7 5V7z"></path>
                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                </svg>
                                <?php _e('join_meeting'); ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($event['livestream_url'])): ?>
                            <a href="<?php echo htmlspecialchars($event['livestream_url']); ?>" target="_blank" rel="noopener"
                                class="online-link-btn livestream">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z" />
                                </svg>
                                <?php _e('watch_live'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Register Button -->
            <?php if (!empty($event['registration_url'])): ?>
                <div class="register-section">
                    <a href="<?php echo htmlspecialchars($event['registration_url']); ?>" target="_blank" rel="noopener"
                        class="btn-register-large">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php _e('register_now'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Website Button -->
            <?php if (!empty($event['website_url'])): ?>
                <a href="<?php echo htmlspecialchars($event['website_url']); ?>" target="_blank" rel="noopener"
                    class="btn-website" style="margin-top: 12px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path
                            d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z">
                        </path>
                    </svg>
                    <?php _e('visit_website'); ?>
                </a>
            <?php endif; ?>

            <!-- Share Links -->
            <?php
            // Prepare share content - use absolute URL
            $shareUrl = getBaseUrl(false) . eventUrl($event['slug']);
            $shareTitle = $event['name'];
            $shareDescription = strip_tags(substr($event['description'] ?? '', 0, 120));
            if (strlen($event['description'] ?? '') > 120) {
                $shareDescription .= '...';
            }
            $shareText = $shareTitle . ' - ' . $shareDescription;

            // WhatsApp: title + description + URL
            $whatsappText = $shareTitle . "\n\n" . $shareDescription . "\n\n" . $shareUrl;

            // X/Twitter: title + URL (has character limit)
            $twitterText = $shareTitle;
            ?>
            <div class="social-links-large">
                <span
                    style="font-size: 13px; color: var(--color-text-light); margin-right: auto;"><?php _e('share_this_event'); ?></span>

                <!-- WhatsApp -->
                <a href="https://wa.me/?text=<?php echo urlencode($whatsappText); ?>" target="_blank" rel="noopener"
                    class="social-link-large" title="Share on WhatsApp" style="background: #25D366; color: white;">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                    </svg>
                </a>

                <!-- Facebook -->
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($shareUrl); ?>&quote=<?php echo urlencode($shareText); ?>"
                    target="_blank" rel="noopener" class="social-link-large" title="Share on Facebook"
                    style="background: #1877F2; color: white;">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
                    </svg>
                </a>

                <!-- X (Twitter) -->
                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($twitterText); ?>&url=<?php echo urlencode($shareUrl); ?>"
                    target="_blank" rel="noopener" class="social-link-large" title="Share on X"
                    style="background: #000000; color: white;">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                    </svg>
                </a>
            </div>
        </div>
    </aside>
</div>

<!-- CTA Banner - Bottom of Page -->
<div class="event-cta-banner">
    <div class="event-cta-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
    </div>
    <div class="event-cta-text">
        <h3><?php _e('share_your_event'); ?></h3>
        <p><?php _e('share_your_event_desc'); ?></p>
    </div>
    <a href="<?php echo url('dashboard/add-event.php'); ?>" class="event-cta-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <?php _e('add_your_event'); ?>
    </a>
</div>

<div class="back-to-events">
    <a href="<?php echo url('events.php'); ?>" class="btn-back">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"></path>
        </svg>
        <?php _e('back_to_all_events'); ?>
    </a>
</div>

<style>
    /* Event Page Specific Styles */

    /* Mobile-only title */
    .event-mobile-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--color-primary);
        margin: 0 0 16px 0;
        line-height: 1.3;
    }

    @media (min-width: 900px) {
        .event-mobile-title {
            display: none !important;
        }
    }

    .church-hero-image .featured-badge {
        position: absolute;
        top: 16px;
        left: 16px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
    }

    .online-links-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--color-border);
    }

    .online-links-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 12px;
    }

    .online-link-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        color: white;
        text-decoration: none;
        transition: all 0.2s;
    }

    .online-link-btn.meeting {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    }

    .online-link-btn.meeting:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
        transform: translateY(-2px);
    }

    .online-link-btn.livestream {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .online-link-btn.livestream:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        transform: translateY(-2px);
    }

    .register-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--color-border);
    }

    .btn-register-large {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 16px 24px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        font-weight: 700;
        font-size: 16px;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .btn-register-large:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }

    .btn-website {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 20px;
        background: var(--color-bg);
        color: var(--color-text);
        font-weight: 600;
        border-radius: 10px;
        border: 1px solid var(--color-border);
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-website:hover {
        background: var(--color-primary-bg);
        border-color: var(--color-primary);
        color: var(--color-primary);
    }

    .back-to-events {
        margin-top: 32px;
        text-align: center;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: var(--color-bg);
        color: var(--color-text-light);
        font-weight: 500;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-back:hover {
        background: var(--color-primary-bg);
        color: var(--color-primary);
    }

    /* Event Format Badge */
    .event-format-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-top: 4px;
    }

    .event-format-badge svg {
        width: 14px;
        height: 14px;
    }

    .event-format-badge.format-in-person {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .event-format-badge.format-online {
        background: rgba(59, 130, 246, 0.1);
        color: #2563eb;
    }

    .event-format-badge.format-hybrid {
        background: rgba(139, 92, 246, 0.1);
        color: #7c3aed;
    }
</style>

<?php require_once 'includes/footer.php'; ?>