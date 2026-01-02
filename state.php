<?php
/**
 * CrossConnect MY - State Page
 * Churches by state
 */

require_once 'config/paths.php';
require_once 'config/database.php';

// Get state slug
$stateSlug = isset($_GET['s']) ? trim($_GET['s']) : '';

if (empty($stateSlug)) {
    header('Location: ' . url('/'));
    exit;
}

// Try database first
$state = dbQuerySingle("SELECT * FROM states WHERE slug = ?", [$stateSlug]);

$useDemoData = false;
$churches = [];
$denominationCounts = [];

// Fall back to demo data
if (!$state) {
    require_once 'config/demo_data.php';
    $demoStates = getDemoStates();

    foreach ($demoStates as $s) {
        if ($s['slug'] === $stateSlug) {
            $state = $s;
            $useDemoData = true;
            break;
        }
    }
}

if (!$state) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = 'State Not Found';
    require_once 'includes/header.php';
    echo '<div class="empty-state"><h3>State not found</h3><p>The state you are looking for does not exist.</p><a href="' . url('/') . '" class="church-card-btn" style="display:inline-block;width:auto;">Back to Home</a></div>';
    require_once 'includes/footer.php';
    exit;
}

// Page meta
$pageTitle = "Churches in {$state['name']}, Malaysia";
$pageDescription = "Find churches in {$state['name']}, Malaysia. Browse our directory of Christian churches, contact information, and locations.";

// Structured data
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => "Churches in {$state['name']}",
    'description' => $pageDescription,
    'url' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mychurchfind.my') . '/state.php?s=' . $state['slug']
];

// Get churches
if ($useDemoData || !isset($state['id'])) {
    require_once 'config/demo_data.php';
    $allChurches = getDemoChurches();

    $churches = array_filter($allChurches, function ($c) use ($stateSlug) {
        return $c['state_slug'] === $stateSlug;
    });

    // Sort: featured first, then by name
    usort($churches, function ($a, $b) {
        if ($a['is_featured'] != $b['is_featured']) {
            return $b['is_featured'] - $a['is_featured'];
        }
        return strcmp($a['name'], $b['name']);
    });

    // Count denominations
    $denomCounts = [];
    foreach ($churches as $c) {
        if (!empty($c['denomination_name'])) {
            $key = $c['denomination_slug'];
            if (!isset($denomCounts[$key])) {
                $denomCounts[$key] = ['name' => $c['denomination_name'], 'slug' => $c['denomination_slug'], 'count' => 0];
            }
            $denomCounts[$key]['count']++;
        }
    }
    $denominationCounts = array_values($denomCounts);
    usort($denominationCounts, function ($a, $b) {
        return $b['count'] - $a['count'];
    });

    $churches = array_values($churches);
} else {
    // Fetch churches from database
    $churches = dbQuery("
        SELECT c.*, s.name as state_name, d.name as denomination_name
        FROM churches c
        LEFT JOIN states s ON c.state_id = s.id
        LEFT JOIN denominations d ON c.denomination_id = d.id
        WHERE c.state_id = ? AND c.status = 'active'
        ORDER BY c.is_featured DESC, c.name ASC
    ", [$state['id']]) ?: [];

    // Count by denomination
    $denominationCounts = dbQuery("
        SELECT d.name, d.slug, COUNT(*) as count
        FROM churches c
        LEFT JOIN denominations d ON c.denomination_id = d.id
        WHERE c.state_id = ? AND c.status = 'active' AND d.id IS NOT NULL
        GROUP BY d.id
        ORDER BY count DESC
    ", [$state['id']]) ?: [];
}

require_once 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo url('/'); ?>"><?php _e('nav_home'); ?></a>
    <span class="breadcrumb-separator">›</span>
    <span><?php echo __('churches_in', ['state' => htmlspecialchars($state['name'])]); ?></span>
</nav>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?php echo __('churches_in', ['state' => htmlspecialchars($state['name'])]); ?></h1>
    <p class="page-subtitle"><?php echo __('churches_count', ['count' => count($churches)]); ?></p>
</div>

<!-- Denomination Filter (if any) -->
<?php if ($denominationCounts && count($denominationCounts) > 0): ?>
    <section class="state-filter">
        <p class="state-filter-label"><?php _e('filter_by_denomination'); ?></p>
        <div class="state-pills">
            <a href="<?php echo url('state.php?s=' . htmlspecialchars($state['slug'])); ?>"
                class="state-pill active"><?php _e('all'); ?></a>
            <?php foreach ($denominationCounts as $denom): ?>
                <a href="<?php echo url('state.php?s=' . htmlspecialchars($state['slug']) . '&d=' . htmlspecialchars($denom['slug'])); ?>"
                    class="state-pill">
                    <?php echo htmlspecialchars($denom['name']); ?> (<?php echo $denom['count']; ?>)
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
                            <span class="featured-badge"><?php _e('featured'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="church-card-content">
                        <h3 class="church-card-name">
                            <a
                                href="<?php echo churchUrl($church['slug']); ?>"><?php echo htmlspecialchars($church['name']); ?></a>
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
                            <span><?php echo htmlspecialchars(($church['city'] ?? '') . ', ' . ($church['state_name'] ?? $state['name'])); ?></span>
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
            <p><?php echo __('no_churches_desc', ['state' => htmlspecialchars($state['name'])]); ?></p>
            <a href="<?php echo url('/'); ?>" class="church-card-btn"
                style="display:inline-block;width:auto;margin-top:1rem;"><?php _e('browse_all_churches'); ?></a>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>