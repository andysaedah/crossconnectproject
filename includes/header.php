<?php
/**
 * Header Include
 * CrossConnect MY - Malaysia Church Directory
 */

// Include path configuration for portable URLs (language.php is loaded automatically)
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/auth.php';

// Get current page for active nav state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentLang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang === 'bm' ? 'ms' : 'en'; ?>" class="notranslate" translate="no">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Disable Google Translate prompt (we have our own translations) -->
    <meta name="google" content="notranslate">

    <!-- App Configuration for JavaScript -->
    <?php outputJsConfig(); ?>

    <?php
    // Generate canonical URL (without lang parameter for consistency)
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $canonicalUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mychurchfind.my') . $currentPath;

    // Generate alternate language URLs for hreflang
    $separator = strpos($currentPath, '?') !== false ? '&' : '?';
    $enUrl = $canonicalUrl . $separator . 'lang=en';
    $bmUrl = $canonicalUrl . $separator . 'lang=bm';

    // Translated site title suffix and keywords
    $siteTitleSuffix = $currentLang === 'bm' ? 'Cari Gereja di Malaysia' : 'Find Churches in Malaysia';
    $metaKeywords = $currentLang === 'bm'
        ? 'gereja Malaysia, gereja Kristian, direktori gereja, pencari gereja Malaysia, Methodist, Katolik, Presbyterian, Pentecostal, Baptist'
        : 'churches Malaysia, Christian churches, church directory, Malaysia church finder, Methodist, Catholic, Presbyterian, Pentecostal, Baptist';
    ?>

    <!-- SEO Meta Tags -->
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?>CrossConnect MY -
        <?php echo $siteTitleSuffix; ?>
    </title>
    <meta name="description"
        content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : __('site_tagline'); ?>">
    <meta name="keywords" content="<?php echo $metaKeywords; ?>">
    <meta name="author" content="CrossConnect MY">
    <meta name="robots" content="index, follow">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

    <!-- Hreflang for Multi-language SEO -->
    <link rel="alternate" hreflang="en" href="<?php echo htmlspecialchars($enUrl); ?>">
    <link rel="alternate" hreflang="ms" href="<?php echo htmlspecialchars($bmUrl); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($enUrl); ?>">

    <!-- Open Graph -->
    <meta property="og:title"
        content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?>CrossConnect MY">
    <meta property="og:description"
        content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : __('site_tagline'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <meta property="og:locale" content="<?php echo $currentLang === 'bm' ? 'ms_MY' : 'en_US'; ?>">
    <meta property="og:locale:alternate" content="<?php echo $currentLang === 'bm' ? 'en_US' : 'ms_MY'; ?>">
    <meta property="og:site_name" content="CrossConnect MY">
    <meta property="og:image"
        content="<?php echo isset($ogImage) ? htmlspecialchars($ogImage) : asset('images/og-default.jpg'); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title"
        content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?>CrossConnect MY">
    <meta name="twitter:description"
        content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : __('site_tagline'); ?>">
    <meta name="twitter:image"
        content="<?php echo isset($ogImage) ? htmlspecialchars($ogImage) : asset('images/og-default.jpg'); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo asset('images/favicon.svg'); ?>">

    <!-- Google Fonts (preloaded for performance) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
            rel="stylesheet">
    </noscript>

    <!-- Font Awesome (preloaded for performance) -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css"
        as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css">
    </noscript>

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo asset('css/styles.min.css'); ?>">

    <!-- Structured Data -->
    <?php if (isset($structuredData)): ?>
        <script type="application/ld+json">
                                                                                                                <?php echo json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
                                                                                                                </script>
    <?php endif; ?>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <!-- Logo -->
            <a href="<?php echo url('/'); ?>" class="logo">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z" />
                </svg>
                <span class="logo-text">CrossConnect <span class="logo-accent">MY</span></span>
            </a>

            <!-- Search Bar (Desktop) -->
            <div class="search-container desktop-only">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" />
                    <path d="M16 16L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                <input type="text" id="searchInput" class="search-input"
                    placeholder="<?php _e('search_placeholder'); ?>">
            </div>

            <!-- Add Church/Event Button (Desktop) or Dashboard if logged in -->
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo url('dashboard/'); ?>" class="btn-add-listing desktop-only">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2" />
                        <path d="M9 9H15M9 13H15M9 17H12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <?php _e('nav_my_dashboard'); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo url('add-listing.php'); ?>" class="btn-add-listing desktop-only">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <?php _e('nav_add_listing'); ?>
                </a>
            <?php endif; ?>

            <!-- Language Switcher (Desktop) -->
            <div class="lang-switcher desktop-only">
                <a href="<?php echo getLanguageSwitchUrl('en'); ?>"
                    class="lang-btn <?php echo isCurrentLanguage('en') ? 'active' : ''; ?>">EN</a>
                <span class="lang-divider">|</span>
                <a href="<?php echo getLanguageSwitchUrl('bm'); ?>"
                    class="lang-btn <?php echo isCurrentLanguage('bm') ? 'active' : ''; ?>">BM</a>
            </div>

            <!-- Filter Dropdown (Desktop) -->
            <div class="filter-dropdown desktop-only">
                <select id="stateFilter" class="filter-select">
                    <option value="all"><?php _e('filter_by_state'); ?></option>
                </select>
                <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>

            <!-- Mobile Header Actions (Language Only) -->
            <div class="mobile-header-actions mobile-only">
                <div class="mobile-lang-switcher">
                    <a href="<?php echo getLanguageSwitchUrl('en'); ?>"
                        class="lang-btn <?php echo isCurrentLanguage('en') ? 'active' : ''; ?>">EN</a>
                    <span class="lang-divider">|</span>
                    <a href="<?php echo getLanguageSwitchUrl('bm'); ?>"
                        class="lang-btn <?php echo isCurrentLanguage('bm') ? 'active' : ''; ?>">BM</a>
                </div>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>

        <!-- Mobile Search & Filter -->
        <div class="mobile-search-container mobile-only">
            <div class="search-container">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" />
                    <path d="M16 16L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                <input type="text" id="searchInputMobile" class="search-input"
                    placeholder="<?php _e('search_placeholder'); ?>">
            </div>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay">
        <div class="mobile-menu">
            <div class="mobile-menu-header">
                <span class="mobile-menu-title"><?php _e('nav_menu'); ?></span>
                <button class="mobile-menu-close" id="mobileMenuClose">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 6L18 18M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <!-- Primary Navigation -->
            <nav class="mobile-nav-section">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo url('dashboard/'); ?>" class="mobile-nav-link mobile-nav-primary">
                        <i class="fas fa-tachometer-alt"></i>
                        <span><?php _e('nav_my_dashboard'); ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo url('auth/login.php'); ?>" class="mobile-nav-link mobile-nav-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        <span><?php _e('nav_login'); ?></span>
                    </a>
                <?php endif; ?>
            </nav>

            <!-- State Filter Section -->
            <div class="mobile-nav-section">
                <div class="mobile-nav-section-title">
                    <i class="fas fa-filter"></i>
                    <span><?php _e('filter_by_state'); ?></span>
                </div>
                <div class="mobile-menu-content" id="mobileMenuContent">
                    <!-- States will be loaded here -->
                </div>
            </div>

            <?php if (isLoggedIn()): ?>
                <!-- Logout -->
                <nav class="mobile-nav-section mobile-nav-footer">
                    <a href="<?php echo url('logout.php'); ?>" class="mobile-nav-link mobile-nav-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span><?php _e('nav_logout'); ?></span>
                    </a>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">