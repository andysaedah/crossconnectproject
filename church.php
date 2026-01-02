<?php
/**
 * CrossConnect MY - Church Detail Page
 * Individual church profile
 */

require_once 'config/paths.php';
require_once 'config/database.php';

// Get church slug
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: ' . url('/'));
    exit;
}

// Try database first
$church = dbQuerySingle("
    SELECT 
        c.*,
        s.name as state_name, s.slug as state_slug,
        d.name as denomination_name, d.slug as denomination_slug
    FROM churches c
    LEFT JOIN states s ON c.state_id = s.id
    LEFT JOIN denominations d ON c.denomination_id = d.id
    WHERE c.slug = ? AND c.status = 'active'
", [$slug]);

$relatedChurches = [];
$useDemoData = false;

// Fall back to demo data
if (!$church) {
    require_once 'config/demo_data.php';
    $allChurches = getDemoChurches();

    foreach ($allChurches as $c) {
        if ($c['slug'] === $slug) {
            $church = $c;
            $useDemoData = true;
            break;
        }
    }

    // Get related churches from demo data
    if ($church) {
        foreach ($allChurches as $c) {
            if ($c['state_slug'] === $church['state_slug'] && $c['slug'] !== $church['slug']) {
                $relatedChurches[] = $c;
                if (count($relatedChurches) >= 4)
                    break;
            }
        }
    }
}

if (!$church) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = 'Church Not Found';
    require_once 'includes/header.php';
    echo '<div class="empty-state"><h3>Church not found</h3><p>The church you are looking for does not exist.</p><a href="' . url('/') . '" class="church-card-btn" style="display:inline-block;width:auto;">Back to Home</a></div>';
    require_once 'includes/footer.php';
    exit;
}

// Page meta
$pageTitle = $church['name'];
$pageDescription = $church['description'] ?: "Find contact information, location, and service times for {$church['name']} in {$church['city']}, {$church['state_name']}, Malaysia.";
$ogImage = $church['image_url'] ?: '/images/og-default.jpg';

// Structured data for SEO (LocalBusiness schema)
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Church',
    'name' => $church['name'],
    'description' => $church['description'],
    'url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mychurchfind.my') . '/church.php?slug=' . $church['slug'],
    'telephone' => $church['phone'],
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $church['address'],
        'addressLocality' => $church['city'],
        'addressRegion' => $church['state_name'],
        'addressCountry' => 'MY'
    ]
];

if (!empty($church['latitude']) && !empty($church['longitude'])) {
    $structuredData['geo'] = [
        '@type' => 'GeoCoordinates',
        'latitude' => $church['latitude'],
        'longitude' => $church['longitude']
    ];
}

// Fetch related churches from database if not using demo data
if (!$useDemoData && isset($church['state_id'])) {
    $relatedChurches = dbQuery("
        SELECT c.*, s.name as state_name, d.name as denomination_name
        FROM churches c
        LEFT JOIN states s ON c.state_id = s.id
        LEFT JOIN denominations d ON c.denomination_id = d.id
        WHERE c.state_id = ? AND c.id != ? AND c.status = 'active'
        ORDER BY RAND()
        LIMIT 4
    ", [$church['state_id'], $church['id']]) ?: [];
}

require_once 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo url('/'); ?>"><?php _e('nav_home'); ?></a>
    <span class="breadcrumb-separator">›</span>
    <a
        href="<?php echo url('state.php?s=' . htmlspecialchars($church['state_slug'])); ?>"><?php echo htmlspecialchars($church['state_name']); ?></a>
    <span class="breadcrumb-separator">›</span>
    <span><?php echo htmlspecialchars($church['name']); ?></span>
</nav>

<!-- Mobile Title (visible only on mobile) -->
<h1 class="church-mobile-title mobile-only"><?php echo htmlspecialchars($church['name']); ?></h1>

<style>
    .church-mobile-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--color-primary);
        margin: 0 0 16px 0;
        line-height: 1.3;
    }

    @media (min-width: 900px) {
        .church-mobile-title {
            display: none !important;
        }
    }
</style>

<div class="church-detail">
    <!-- Main Content -->
    <div class="church-detail-main">
        <!-- Hero Image -->
        <?php if (!empty($church['image_url'])): ?>
            <div class="church-hero-image">
                <img src="<?php echo htmlspecialchars($church['image_url']); ?>" loading="lazy"
                    alt="<?php echo htmlspecialchars($church['name']); ?>"
                    onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'church-hero-placeholder\'><svg viewBox=\'0 0 24 24\' fill=\'none\'><path d=\'M18 21H6V12L3 12L12 3L21 12L18 12V21Z\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/><path d=\'M12 3V8\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\'/><path d=\'M9 21V15H15V21\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/></svg></div>';">
            </div>
        <?php endif; ?>

        <?php
        // Check if current user owns this church
        $currentUser = getCurrentUser();
        $isOwner = $currentUser && !$useDemoData && isset($church['created_by']) && $church['created_by'] == $currentUser['id'];
        $isAdmin = $currentUser && $currentUser['role'] === 'admin';
        $canEdit = $isOwner || $isAdmin;

        // Fetch states and denominations if user can edit
        $editStates = [];
        $editDenominations = [];
        if ($canEdit) {
            try {
                $pdo = getDbConnection();
                $editStates = $pdo->query("SELECT id, name FROM states ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                $editDenominations = $pdo->query("SELECT id, name FROM denominations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Edit modal data error: " . $e->getMessage());
            }
        }
        ?>

        <!-- Title with Edit Button -->
        <div class="church-title-row">
            <h1 class="church-title"><?php echo htmlspecialchars($church['name']); ?></h1>
            <?php if ($canEdit): ?>
                <button type="button" class="edit-church-btn" onclick="openEditModal()"
                    title="<?php _e('edit_this_listing'); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    <span><?php _e('edit'); ?></span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Meta -->
        <div class="church-meta">
            <?php if (!empty($church['denomination_name'])): ?>
                <span class="church-meta-item">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L14 8H20L15 12L17 18L12 14L7 18L9 12L4 8H10L12 2Z" stroke="currentColor"
                            stroke-width="2" />
                    </svg>
                    <?php echo htmlspecialchars($church['denomination_name']); ?>
                </span>
            <?php endif; ?>
            <span class="church-meta-item">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="currentColor" stroke-width="2" />
                    <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" />
                </svg>
                <?php echo htmlspecialchars($church['city'] . ', ' . $church['state_name']); ?>
            </span>
        </div>

        <!-- Description -->
        <?php if (!empty($church['description'])): ?>
            <div class="church-description">
                <?php
                $desc = htmlspecialchars($church['description']);
                // Convert markdown-style links [text](url) to HTML links
                $desc = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $desc);
                // Convert plain URLs to clickable links
                $desc = preg_replace('/(https?:\/\/[^\s<]+)/', '<a href="$1" target="_blank" rel="noopener">$1</a>', $desc);
                // Bold text **text**
                $desc = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $desc);
                // Newlines
                echo nl2br($desc);
                ?>
            </div>
        <?php endif; ?>

        <!-- Map -->
        <?php if (!empty($church['latitude']) && !empty($church['longitude'])): ?>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=<?php echo $church['latitude']; ?>,<?php echo $church['longitude']; ?>&zoom=15"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        <?php elseif (!empty($church['address'])): ?>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=<?php echo urlencode($church['address'] . ', ' . $church['city'] . ', Malaysia'); ?>&zoom=15"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="church-detail-sidebar">
        <div class="contact-card">
            <h2 class="contact-card-title"><?php _e('contact_information'); ?></h2>

            <div class="contact-list">
                <?php if (!empty($church['phone'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"
                                    stroke="currentColor" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('phone'); ?></div>
                            <div class="contact-list-value">
                                <a
                                    href="tel:<?php echo htmlspecialchars($church['phone']); ?>"><?php echo htmlspecialchars($church['phone']); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($church['email'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"
                                    stroke="currentColor" stroke-width="2" />
                                <polyline points="22,6 12,13 2,6" stroke="currentColor" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('email'); ?></div>
                            <div class="contact-list-value">
                                <a
                                    href="mailto:<?php echo htmlspecialchars($church['email']); ?>"><?php echo htmlspecialchars($church['email']); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($church['website'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                <path
                                    d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"
                                    stroke="currentColor" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('website'); ?></div>
                            <div class="contact-list-value">
                                <a href="<?php echo htmlspecialchars($church['website']); ?>" target="_blank"
                                    rel="noopener"><?php echo htmlspecialchars(preg_replace('#^https?://#', '', $church['website'])); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($church['address'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="currentColor"
                                    stroke-width="2" />
                                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('address'); ?></div>
                            <div class="contact-list-value"><?php echo htmlspecialchars($church['address']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($church['service_times'])): ?>
                    <div class="contact-list-item">
                        <div class="contact-list-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                <polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="contact-list-content">
                            <div class="contact-list-label"><?php _e('service_times'); ?></div>
                            <div class="contact-list-value"><?php echo nl2br(htmlspecialchars($church['service_times'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            // Display service languages as badges
            $serviceLanguages = !empty($church['service_languages']) ? explode(',', $church['service_languages']) : [];
            if (!empty($serviceLanguages)):
                ?>
                <div class="service-languages-section">
                    <h3 class="service-languages-title"><?php _e('service_languages'); ?></h3>
                    <div class="service-language-badges">
                        <?php foreach ($serviceLanguages as $lang):
                            $lang = trim($lang);
                            if (empty($lang))
                                continue;
                            $langKey = 'service_lang_' . $lang;
                            $langLabel = __($langKey);
                            // Fallback to raw value if translation not found
                            if ($langLabel === $langKey)
                                $langLabel = ucfirst($lang);
                            ?>
                            <span class="service-lang-badge service-lang-<?php echo htmlspecialchars($lang); ?>">
                                <?php echo htmlspecialchars($langLabel); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Social Links -->
            <?php if (!empty($church['facebook']) || !empty($church['instagram']) || !empty($church['youtube'])): ?>
                <div class="social-links-large">
                    <?php if (!empty($church['facebook'])): ?>
                        <a href="https://facebook.com/<?php echo htmlspecialchars($church['facebook']); ?>" target="_blank"
                            rel="noopener" class="social-link-large" title="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($church['instagram'])): ?>
                        <a href="https://instagram.com/<?php echo htmlspecialchars($church['instagram']); ?>" target="_blank"
                            rel="noopener" class="social-link-large" title="Instagram">
                            <svg viewBox="0 0 24 24" fill="none">
                                <rect x="2" y="2" width="20" height="20" rx="5" stroke="currentColor" stroke-width="2" />
                                <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2" />
                                <circle cx="18" cy="6" r="1" fill="currentColor" />
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($church['youtube'])): ?>
                        <a href="https://youtube.com/@<?php echo htmlspecialchars($church['youtube']); ?>" target="_blank"
                            rel="noopener" class="social-link-large" title="YouTube">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z" />
                                <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="white" />
                            </svg>
                        </a>
                    <?php endif; ?>

                    <?php /* Twitter hidden for now
              <?php if (!empty($church['twitter'])): ?>
                  <a href="https://twitter.com/<?php echo htmlspecialchars($church['twitter']); ?>" target="_blank"
                      rel="noopener" class="social-link-large" title="Twitter">
                      <svg viewBox="0 0 24 24" fill="currentColor">
                          <path
                              d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z" />
                      </svg>
                  </a>
              <?php endif; ?>
              */ ?>
                </div>
            </div>
        <?php endif; ?>
</div>

<!-- Report Incorrect Info Card -->
<div class="report-card">
    <div class="report-card-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
    </div>
    <h3 class="report-card-title"><?php _e('see_something_wrong'); ?></h3>
    <p class="report-card-text"><?php _e('report_incorrect_info_desc'); ?></p>
    <button type="button" class="report-btn" onclick="openReportModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
        <?php _e('report_incorrect_info'); ?>
    </button>
</div>
</aside>
</div>

<!-- Report Amendment Modal -->
<div class="modal-overlay" id="reportModalOverlay" onclick="closeReportModal()"></div>
<div class="modal report-modal" id="reportModal">
    <div class="modal-header">
        <h3><?php _e('report_incorrect_info'); ?></h3>
        <button class="modal-close" onclick="closeReportModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <form id="reportForm" onsubmit="submitReport(event)">
        <input type="hidden" name="church_id" value="<?php echo $church['id'] ?? 0; ?>">

        <!-- Honeypot field for bot detection - hidden from users -->
        <div style="position: absolute; left: -9999px;">
            <input type="text" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <div class="modal-body">
            <div class="church-name-display">
                <strong><?php _e('church'); ?>:</strong> <?php echo htmlspecialchars($church['name']); ?>
            </div>

            <div class="form-group">
                <label class="form-label"><?php _e('what_needs_correction'); ?> <span class="required">*</span></label>
                <textarea name="notes" id="reportNotes" class="form-textarea" rows="4" maxlength="5000"
                    placeholder="<?php _e('amendment_notes_placeholder'); ?>"></textarea>
                <p class="form-hint"><?php _e('amendment_notes_hint'); ?></p>
            </div>

            <div class="form-group">
                <label class="form-label"><?php _e('your_email_optional'); ?></label>
                <input type="email" name="email" class="form-input" placeholder="email@example.com">
                <p class="form-hint"><?php _e('your_email_hint'); ?></p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeReportModal()"><?php _e('cancel'); ?></button>
            <button type="submit" class="btn btn-primary" id="submitReportBtn"><?php _e('submit_report'); ?></button>
        </div>
    </form>
</div>

<?php if ($canEdit): ?>
    <!-- Edit Church Modal -->
    <div class="modal-overlay" id="editModalOverlay" onclick="closeEditModal()"></div>
    <div class="modal edit-modal" id="editModal">
        <div class="modal-header">
            <h3><?php _e('edit_church'); ?></h3>
            <button class="modal-close" onclick="closeEditModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <form id="editChurchForm" onsubmit="saveChurchEdit(event)" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="id" value="<?php echo $church['id']; ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><?php _e('church_name'); ?> *</label>
                    <input type="text" name="name" id="editChurchName" class="form-input" required
                        value="<?php echo htmlspecialchars($church['name']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php _e('state'); ?> *</label>
                        <select name="state_id" id="editChurchState" class="form-select" required>
                            <option value=""><?php _e('select_state'); ?></option>
                            <?php foreach ($editStates as $state): ?>
                                <option value="<?php echo $state['id']; ?>" <?php echo ($church['state_id'] ?? '') == $state['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($state['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php _e('denomination'); ?></label>
                        <select name="denomination_id" id="editChurchDenomination" class="form-select">
                            <option value=""><?php _e('select_denomination'); ?></option>
                            <?php foreach ($editDenominations as $denom): ?>
                                <option value="<?php echo $denom['id']; ?>" <?php echo ($church['denomination_id'] ?? '') == $denom['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($denom['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php _e('city'); ?></label>
                        <input type="text" name="city" id="editChurchCity" class="form-input"
                            value="<?php echo htmlspecialchars($church['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php _e('phone'); ?></label>
                        <input type="text" name="phone" id="editChurchPhone" class="form-input"
                            value="<?php echo htmlspecialchars($church['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php _e('address'); ?></label>
                    <textarea name="address" id="editChurchAddress" class="form-textarea"
                        rows="2"><?php echo htmlspecialchars($church['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php _e('email'); ?></label>
                        <input type="email" name="email" id="editChurchEmail" class="form-input"
                            value="<?php echo htmlspecialchars($church['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php _e('website'); ?></label>
                        <input type="url" name="website" id="editChurchWebsite" class="form-input" placeholder="https://"
                            value="<?php echo htmlspecialchars($church['website'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Facebook</label>
                        <input type="text" name="facebook" id="editChurchFacebook" class="form-input" placeholder="mychurch"
                            value="<?php echo htmlspecialchars($church['facebook'] ?? ''); ?>">
                        <p class="form-hint"><?php _e('facebook_hint'); ?></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Instagram</label>
                        <input type="text" name="instagram" id="editChurchInstagram" class="form-input"
                            placeholder="mychurch" value="<?php echo htmlspecialchars($church['instagram'] ?? ''); ?>">
                        <p class="form-hint"><?php _e('instagram_hint'); ?></p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">YouTube</label>
                    <input type="text" name="youtube" id="editChurchYoutube" class="form-input" placeholder="mychurch"
                        value="<?php echo htmlspecialchars($church['youtube'] ?? ''); ?>">
                    <p class="form-hint"><?php _e('youtube_hint'); ?></p>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php _e('description'); ?></label>
                    <textarea name="description" id="editChurchDescription" class="form-textarea"
                        rows="3"><?php echo htmlspecialchars($church['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php _e('service_times'); ?></label>
                    <textarea name="service_times" id="editChurchServiceTimes" class="form-textarea" rows="2"
                        placeholder="<?php _e('service_times_placeholder'); ?>"><?php echo htmlspecialchars($church['service_times'] ?? ''); ?></textarea>
                </div>

                <!-- Service Languages Checkboxes -->
                <?php
                $currentLangs = !empty($church['service_languages']) ? explode(',', $church['service_languages']) : [];
                ?>
                <div class="form-group">
                    <label class="form-label"><?php _e('service_languages'); ?></label>
                    <p class="form-hint" style="margin-top: 0; margin-bottom: 8px;"><?php _e('service_languages_hint'); ?>
                    </p>
                    <div class="checkbox-grid">
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_languages[]" value="bm" <?php echo in_array('bm', $currentLangs) ? 'checked' : ''; ?>>
                            <span class="checkbox-text"><?php _e('service_lang_bm'); ?></span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_languages[]" value="en" <?php echo in_array('en', $currentLangs) ? 'checked' : ''; ?>>
                            <span class="checkbox-text"><?php _e('service_lang_en'); ?></span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_languages[]" value="chinese" <?php echo in_array('chinese', $currentLangs) ? 'checked' : ''; ?>>
                            <span class="checkbox-text"><?php _e('service_lang_chinese'); ?></span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_languages[]" value="tamil" <?php echo in_array('tamil', $currentLangs) ? 'checked' : ''; ?>>
                            <span class="checkbox-text"><?php _e('service_lang_tamil'); ?></span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="service_languages[]" value="other" <?php echo in_array('other', $currentLangs) ? 'checked' : ''; ?>>
                            <span class="checkbox-text"><?php _e('service_lang_other'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="form-group">
                    <label class="form-label"><?php _e('church_photo'); ?></label>
                    <div class="image-upload-edit" id="editImageUploadContainer"
                        onclick="document.getElementById('editChurchPhoto').click()">
                        <?php if (!empty($church['image_url'])): ?>
                            <div class="current-image-preview" id="editCurrentImagePreview">
                                <img id="editCurrentImageImg" src="<?php echo htmlspecialchars($church['image_url']); ?>"
                                    alt="Current image">
                                <div class="image-overlay">
                                    <span><?php _e('click_to_change'); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="current-image-preview" id="editCurrentImagePreview" style="display: none;">
                                <img id="editCurrentImageImg" src="" alt="Current image">
                                <div class="image-overlay">
                                    <span><?php _e('click_to_change'); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="upload-placeholder" id="editUploadPlaceholder" <?php echo !empty($church['image_url']) ? 'style="display: none;"' : ''; ?>>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <span><?php _e('click_to_upload'); ?></span>
                        </div>
                        <input type="file" name="photo" id="editChurchPhoto" accept="image/*" hidden>
                    </div>
                    <p class="form-hint"><?php _e('keep_current_image'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()"><?php _e('cancel'); ?></button>
                <button type="submit" class="btn btn-primary" id="saveEditBtn"><?php _e('save_changes'); ?></button>
            </div>
        </form>
    </div>
<?php endif; ?>

<style>
    /* Title Row with Edit Button */
    .church-title-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .church-title-row .church-title {
        flex: 1;
        min-width: 0;
    }

    .edit-church-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark, #7c3aed) 100%);
        color: white;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
        flex-shrink: 0;
    }

    .edit-church-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
    }

    .edit-church-btn svg {
        width: 16px;
        height: 16px;
    }

    @media (max-width: 480px) {
        .edit-church-btn span {
            display: none;
        }

        .edit-church-btn {
            padding: 10px;
            border-radius: 50%;
        }
    }

    .report-card {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 1px solid #f59e0b;
        border-radius: 12px;
        padding: 20px;
        margin-top: 16px;
        text-align: center;
    }

    .report-card-icon {
        width: 40px;
        height: 40px;
        margin: 0 auto 12px;
        background: #f59e0b;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .report-card-icon svg {
        width: 20px;
        height: 20px;
        color: white;
    }

    .report-card-title {
        margin: 0 0 8px;
        font-size: 1rem;
        color: #92400e;
    }

    .report-card-text {
        margin: 0 0 16px;
        font-size: 0.875rem;
        color: #a16207;
        line-height: 1.5;
    }

    .report-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #f59e0b;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }

    .report-btn:hover {
        background: #d97706;
    }

    .report-btn svg {
        width: 16px;
        height: 16px;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .report-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.95);
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow: hidden;
        z-index: 1001;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s;
    }

    .report-modal.show {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.125rem;
    }

    .modal-close {
        width: 32px;
        height: 32px;
        border: none;
        background: none;
        cursor: pointer;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
    }

    .modal-close:hover {
        background: #f3f4f6;
    }

    .modal-close svg {
        width: 20px;
        height: 20px;
    }

    .modal-body {
        padding: 24px;
        max-height: calc(90vh - 160px);
        overflow-y: auto;
    }

    .church-name-display {
        background: #f3f4f6;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .form-label .required {
        color: #ef4444;
    }

    .form-textarea,
    .form-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: border-color 0.2s;
    }

    .form-textarea:focus,
    .form-input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .form-hint {
        margin: 6px 0 0;
        font-size: 0.8rem;
        color: #6b7280;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }

    .btn-secondary:hover {
        background: #e5e7eb;
    }

    .btn-primary {
        background: var(--color-primary);
        color: white;
    }

    .btn-primary:hover {
        opacity: 0.9;
    }

    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Edit Modal Styles */
    .modal.edit-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.95);
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 650px;
        max-height: 90vh;
        overflow: hidden;
        z-index: 1001;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s;
    }

    .modal.edit-modal.show {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    @media (max-width: 600px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    .form-select {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.9rem;
        background: white;
        cursor: pointer;
    }

    .form-select:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    /* Service Languages Section */
    .service-languages-section {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }

    .service-languages-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin: 0 0 10px;
    }

    .service-language-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .service-lang-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .service-lang-bm {
        background: #dbeafe;
        color: #1e40af;
    }

    .service-lang-en {
        background: #dcfce7;
        color: #166534;
    }

    .service-lang-chinese {
        background: #fef3c7;
        color: #92400e;
    }

    .service-lang-tamil {
        background: #fce7f3;
        color: #9d174d;
    }

    .service-lang-other {
        background: #f3f4f6;
        color: #4b5563;
    }

    /* Checkbox Grid */
    .checkbox-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.875rem;
    }

    .checkbox-label:hover {
        border-color: var(--color-primary);
        background: #f5f3ff;
    }

    .checkbox-label input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: var(--color-primary);
        cursor: pointer;
    }

    .checkbox-label input[type="checkbox"]:checked+.checkbox-text {
        color: var(--color-primary);
        font-weight: 500;
    }

    /* Image Upload Edit */
    .image-upload-edit {
        border: 2px dashed #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s;
        background: #f9fafb;
    }

    .image-upload-edit:hover {
        border-color: var(--color-primary);
    }

    .current-image-preview {
        position: relative;
        width: 100%;
        height: 150px;
    }

    .current-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .current-image-preview .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .current-image-preview:hover .image-overlay {
        opacity: 1;
    }

    .current-image-preview .image-overlay span {
        color: white;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 32px;
        color: #6b7280;
    }

    .upload-placeholder svg {
        width: 40px;
        height: 40px;
        margin-bottom: 8px;
    }

    .upload-placeholder span {
        font-size: 0.875rem;
    }
</style>

<script>
    function openReportModal() {
        document.getElementById('reportModalOverlay').classList.add('show');
        document.getElementById('reportModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeReportModal() {
        document.getElementById('reportModalOverlay').classList.remove('show');
        document.getElementById('reportModal').classList.remove('show');
        document.body.style.overflow = '';
        document.getElementById('reportForm').reset();
    }

    async function submitReport(e) {
        e.preventDefault();

        // Validate notes field (since we removed HTML required to avoid form conflicts)
        const notes = document.getElementById('reportNotes').value.trim();
        if (!notes) {
            showToast('<?php _e('please_fill_required_fields'); ?>', 'error');
            document.getElementById('reportNotes').focus();
            return;
        }

        const btn = document.getElementById('submitReportBtn');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '<?php _e('submitting'); ?>...';

        try {
            const formData = new FormData(document.getElementById('reportForm'));

            const response = await fetch('<?php echo url('api/report-amendment.php'); ?>', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                closeReportModal();
                showToast(data.message || '<?php _e('amendment_reported_success'); ?>', 'success');
            } else {
                showToast(data.error || '<?php _e('something_went_wrong'); ?>', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            }
        } catch (error) {
            showToast('<?php _e('something_went_wrong'); ?>', 'error');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    // Close modal on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeReportModal();
    });

    // Simple toast notification
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            border-radius: 8px;
            font-size: 0.9rem;
            z-index: 9999;
            animation: slideUp 0.3s ease;
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideDown 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    <?php if ($canEdit): ?>
        // Edit Modal Functions
        function openEditModal() {
            document.getElementById('editModalOverlay').classList.add('show');
            document.getElementById('editModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModalOverlay').classList.remove('show');
            document.getElementById('editModal').classList.remove('show');
            document.body.style.overflow = '';
        }

        // Image preview on file select
        document.getElementById('editChurchPhoto').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('editCurrentImageImg').src = e.target.result;
                    document.getElementById('editCurrentImagePreview').style.display = 'block';
                    document.getElementById('editUploadPlaceholder').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        async function saveChurchEdit(e) {
            e.preventDefault();

            const btn = document.getElementById('saveEditBtn');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = '<?php _e('saving'); ?>...';

            try {
                const formData = new FormData(document.getElementById('editChurchForm'));
                formData.append('action', 'update');

                const response = await fetch('<?php echo url('api/user/churches.php'); ?>', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    closeEditModal();
                    showToast('<?php _e('church_saved'); ?>', 'success');
                    // Reload page after short delay to show updated info
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || '<?php _e('something_went_wrong'); ?>', 'error');
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            } catch (error) {
                showToast('<?php _e('something_went_wrong'); ?>', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }

        // Also close edit modal on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeEditModal();
        });
    <?php endif; ?>
</script>

<!-- Related Churches -->
<?php if ($relatedChurches && count($relatedChurches) > 0): ?>
    <section class="related-churches">
        <h2 class="section-title">
            <?php echo str_replace('{state}', htmlspecialchars($church['state_name']), __('other_churches_in')); ?>
        </h2>
        <div class="churches-grid">
            <?php foreach ($relatedChurches as $related): ?>
                <article class="church-card">
                    <div class="church-card-image">
                        <?php if (!empty($related['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($related['image_url']); ?>" loading="lazy"
                                alt="<?php echo htmlspecialchars($related['name']); ?>" loading="lazy"
                                onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'church-card-image-placeholder\'><svg viewBox=\'0 0 24 24\' fill=\'none\'><path d=\'M18 21H6V12L3 12L12 3L21 12L18 12V21Z\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/><path d=\'M12 3V8\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\'/><path d=\'M9 21V15H15V21\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/></svg></div>'">
                        <?php else: ?>
                            <div class="church-card-image-placeholder">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M18 21H6V12L3 12L12 3L21 12L18 12V21Z" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M12 3V8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <path d="M9 21V15H15V21" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="church-card-content">
                        <h3 class="church-card-name">
                            <a
                                href="<?php echo churchUrl($related['slug']); ?>"><?php echo htmlspecialchars($related['name']); ?></a>
                        </h3>
                        <?php if (!empty($related['denomination_name'])): ?>
                            <div class="church-card-denomination"><?php echo htmlspecialchars($related['denomination_name']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="church-card-location">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="currentColor" stroke-width="2" />
                                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" />
                            </svg>
                            <span><?php echo htmlspecialchars(($related['city'] ?? '') . ', ' . ($related['state_name'] ?? '')); ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>