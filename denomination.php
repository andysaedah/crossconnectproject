<?php
/**
 * CrossConnect MY - Denomination Page
 * Churches by denomination
 */

require_once 'config/paths.php';
require_once 'config/database.php';
require_once 'config/language.php';

// Get denomination slug
$denominationSlug = isset($_GET['d']) ? trim($_GET['d']) : '';

if (empty($denominationSlug)) {
    header('Location: ' . url('/'));
    exit;
}

// Try database first
$denomination = dbQuerySingle("SELECT * FROM denominations WHERE slug = ?", [$denominationSlug]);

$churches = [];
$stateCounts = [];

// If not in database, try to find denomination info from demo churches
if (!$denomination) {
    require_once 'config/demo_data.php';
    $allChurches = getDemoChurches();

    // Find a church with this denomination to get the name
    foreach ($allChurches as $c) {
        if (isset($c['denomination_slug']) && $c['denomination_slug'] === $denominationSlug) {
            $denomination = [
                'name' => $c['denomination_name'] ?? ucwords(str_replace('-', ' ', $denominationSlug)),
                'slug' => $denominationSlug
            ];
            break;
        }
    }

    // If still not found, create denomination from slug
    if (!$denomination) {
        $denomination = [
            'name' => ucwords(str_replace('-', ' ', $denominationSlug)),
            'slug' => $denominationSlug
        ];
    }
}

// Page meta
$pageTitle = "{$denomination['name']} Churches in Malaysia";
$pageDescription = "Find {$denomination['name']} churches in Malaysia. Browse our directory of {$denomination['name']} churches, contact information, and locations.";

// Structured data
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => "{$denomination['name']} Churches",
    'description' => $pageDescription,
    'url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'crossconnect.my') . '/denomination.php?d=' . $denomination['slug']
];

// Get churches - from database if denomination has ID, otherwise filter demo data
if (isset($denomination['id'])) {
    // Fetch churches from database
    $churches = dbQuery("
        SELECT c.*, s.name as state_name, s.slug as state_slug, d.name as denomination_name
        FROM churches c
        LEFT JOIN states s ON c.state_id = s.id
        LEFT JOIN denominations d ON c.denomination_id = d.id
        WHERE c.denomination_id = ? AND c.status = 'active'
        ORDER BY c.is_featured DESC, c.name ASC
    ", [$denomination['id']]) ?: [];

    // Count by state
    $stateCounts = dbQuery("
        SELECT s.name, s.slug, COUNT(*) as count
        FROM churches c
        LEFT JOIN states s ON c.state_id = s.id
        WHERE c.denomination_id = ? AND c.status = 'active' AND s.id IS NOT NULL
        GROUP BY s.id
        ORDER BY count DESC
    ", [$denomination['id']]) ?: [];
} else {
    // Use demo data
    if (!function_exists('getDemoChurches')) {
        require_once 'config/demo_data.php';
    }
    $allChurches = getDemoChurches();

    $churches = array_filter($allChurches, function ($c) use ($denominationSlug) {
        return $c['denomination_slug'] === $denominationSlug;
    });

    // Sort: featured first, then by name
    usort($churches, function ($a, $b) {
        if ($a['is_featured'] != $b['is_featured']) {
            return $b['is_featured'] - $a['is_featured'];
        }
        return strcmp($a['name'], $b['name']);
    });

    // Count by state
    $stateCountsTemp = [];
    foreach ($churches as $c) {
        if (!empty($c['state_name'])) {
            $key = $c['state_slug'];
            if (!isset($stateCountsTemp[$key])) {
                $stateCountsTemp[$key] = ['name' => $c['state_name'], 'slug' => $c['state_slug'], 'count' => 0];
            }
            $stateCountsTemp[$key]['count']++;
        }
    }
    $stateCounts = array_values($stateCountsTemp);
    usort($stateCounts, function ($a, $b) {
        return $b['count'] - $a['count'];
    });

    $churches = array_values($churches);
}

require_once 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo url('/'); ?>"><?php _e('nav_home'); ?></a>
    <span class="breadcrumb-separator">›</span>
    <span><?php echo __('denomination_churches', ['denomination' => htmlspecialchars($denomination['name'])]); ?></span>
</nav>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <?php echo __('denomination_churches', ['denomination' => htmlspecialchars($denomination['name'])]); ?>
    </h1>
    <p class="page-subtitle"><?php echo __('denomination_churches_count', ['count' => count($churches)]); ?></p>
</div>

<!-- State Filter (if any) -->
<?php if ($stateCounts && count($stateCounts) > 0): ?>
    <section class="state-filter">
        <p class="state-filter-label"><?php _e('filter_by_state'); ?></p>
        <div class="state-pills">
            <a href="<?php echo url('denomination.php?d=' . htmlspecialchars($denomination['slug'])); ?>"
                class="state-pill active"><?php _e('all_states'); ?></a>
            <?php foreach ($stateCounts as $state): ?>
                <a href="<?php echo url('denomination.php?d=' . htmlspecialchars($denomination['slug']) . '&s=' . htmlspecialchars($state['slug'])); ?>"
                    class="state-pill">
                    <?php echo htmlspecialchars($state['name']); ?> (<?php echo $state['count']; ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- Churches Grid -->
<section class="churches-section">
    <?php if ($churches && count($churches) > 0): ?>
        <div class="churches-grid">
            <?php foreach ($churches as $church): ?>
                <article class="church-card<?php echo !empty($church['is_featured']) ? ' featured' : ''; ?>">
                    <div class="church-card-image">
                        <?php if (!empty($church['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($church['image_url']); ?>" loading="lazy"
                                alt="<?php echo htmlspecialchars($church['name']); ?>" loading="lazy"
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
                        <?php if (!empty($church['is_featured'])): ?>
                            <span class="featured-badge">Featured</span>
                        <?php endif; ?>
                    </div>
                    <div class="church-card-content">
                        <h3 class="church-card-name">
                            <a
                                href="<?php echo url('church.php?slug=' . htmlspecialchars($church['slug'])); ?>"><?php echo htmlspecialchars($church['name']); ?></a>
                        </h3>
                        <?php if (!empty($church['denomination_name'])): ?>
                            <div class="church-card-denomination"><?php echo htmlspecialchars($church['denomination_name']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="church-card-location">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="currentColor" stroke-width="2" />
                                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2" />
                            </svg>
                            <span><?php echo htmlspecialchars(($church['city'] ?? '') . ', ' . ($church['state_name'] ?? '')); ?></span>
                        </div>

                        <div class="church-card-social">
                            <?php if (!empty($church['phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($church['phone']); ?>" class="social-link" title="Call">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"
                                            stroke="currentColor" stroke-width="2" />
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($church['website'])): ?>
                                <a href="<?php echo htmlspecialchars($church['website']); ?>" target="_blank" rel="noopener"
                                    class="social-link" title="Website">
                                    <svg viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                        <path
                                            d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"
                                            stroke="currentColor" stroke-width="2" />
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($church['facebook'])): ?>
                                <a href="https://facebook.com/<?php echo htmlspecialchars($church['facebook']); ?>" target="_blank"
                                    rel="noopener" class="social-link" title="Facebook">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L14 8H20L15 12L17 18L12 14L7 18L9 12L4 8H10L12 2Z" stroke="currentColor" stroke-width="2" />
            </svg>
            <h3><?php _e('no_churches_found'); ?></h3>
            <p><?php echo __('denomination_no_churches', ['denomination' => htmlspecialchars($denomination['name'])]); ?>
            </p>
            <a href="<?php echo url('/'); ?>" class="church-card-btn"
                style="display:inline-block;width:auto;margin-top:1rem;"><?php _e('browse_all_churches'); ?></a>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>