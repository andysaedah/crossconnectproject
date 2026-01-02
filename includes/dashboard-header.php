<?php
/**
 * CrossConnect MY - Dashboard Header Include
 * Common header for dashboard pages
 */

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/language.php';

// Require authentication for dashboard pages
requireAuth();
requireVerifiedEmail();

$user = getCurrentUser();
$isAdminPage = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;

// Page title
$pageTitle = $pageTitle ?? __('dashboard');
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage() === 'bm' ? 'ms' : 'en'; ?>" translate="no">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title><?php echo htmlspecialchars($pageTitle); ?> - CrossConnect MY</title>
    <link rel="stylesheet" href="<?php echo asset('css/styles.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/dashboard.min.css'); ?>">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset('images/favicon.svg'); ?>">
</head>

<body class="dashboard-body">
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?php echo url('/'); ?>" class="sidebar-logo">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z" />
                    </svg>
                    <span>CrossConnect</span>
                </a>
                <button class="sidebar-close" id="sidebarClose">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav">
                <?php if ($isAdminPage || isAdmin()): ?>
                    <!-- Admin Navigation -->
                    <a href="<?php echo url('admin/'); ?>"
                        class="sidebar-link <?php echo $currentPage === 'index' && $isAdminPage ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span><?php _e('dashboard'); ?></span>
                    </a>
                    <a href="<?php echo url('dashboard/'); ?>"
                        class="sidebar-link <?php echo $currentPage === 'index' && !$isAdminPage ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span><?php _e('dash_my_profile'); ?></span>
                    </a>

                    <div class="sidebar-divider"></div>

                    <a href="<?php echo url('admin/users.php'); ?>"
                        class="sidebar-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span><?php _e('admin_users'); ?></span>
                    </a>
                    <a href="<?php echo url('admin/churches.php'); ?>"
                        class="sidebar-link <?php echo $currentPage === 'churches' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 21H6V12L3 12L12 3L21 12L18 12V21Z"></path>
                            <path d="M9 21V15H15V21"></path>
                        </svg>
                        <span><?php _e('admin_churches'); ?></span>
                    </a>
                    <a href="<?php echo url('admin/events.php'); ?>"
                        class="sidebar-link <?php echo $currentPage === 'events' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <path d="M16 2V6M8 2V6M3 10H21"></path>
                        </svg>
                        <span><?php _e('admin_events'); ?></span>
                    </a>
                    <!-- Logs Dropdown -->
                    <div
                        class="sidebar-dropdown <?php echo in_array($currentPage, ['logs', 'email-logs']) ? 'open' : ''; ?>">
                        <button
                            class="sidebar-link sidebar-dropdown-toggle <?php echo in_array($currentPage, ['logs', 'email-logs']) ? 'active' : ''; ?>"
                            onclick="toggleDropdown(this)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <span><?php _e('admin_logs'); ?></span>
                            <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="sidebar-dropdown-menu">
                            <a href="<?php echo url('admin/logs.php'); ?>"
                                class="sidebar-sublink <?php echo $currentPage === 'logs' ? 'active' : ''; ?>">
                                <?php _e('admin_activity_log'); ?>
                            </a>
                            <a href="<?php echo url('admin/email-logs.php'); ?>"
                                class="sidebar-sublink <?php echo $currentPage === 'email-logs' ? 'active' : ''; ?>">
                                <?php _e('admin_email_log'); ?>
                            </a>
                        </div>
                    </div>
                    <!-- Settings Dropdown -->
                    <div
                        class="sidebar-dropdown <?php echo in_array($currentPage, ['site-config', 'language', 'api-settings']) ? 'open' : ''; ?>">
                        <button
                            class="sidebar-link sidebar-dropdown-toggle <?php echo in_array($currentPage, ['site-config', 'language', 'api-settings']) ? 'active' : ''; ?>"
                            onclick="toggleDropdown(this)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path
                                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                                </path>
                            </svg>
                            <span><?php _e('admin_settings'); ?></span>
                            <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="sidebar-dropdown-menu">
                            <a href="<?php echo url('admin/site-config.php'); ?>"
                                class="sidebar-sublink <?php echo $currentPage === 'site-config' ? 'active' : ''; ?>">
                                <?php _e('admin_site_config'); ?>
                            </a>
                            <a href="<?php echo url('admin/language.php'); ?>"
                                class="sidebar-sublink <?php echo $currentPage === 'language' ? 'active' : ''; ?>">
                                <?php _e('admin_languages'); ?>
                            </a>
                            <a href="<?php echo url('admin/api-settings.php'); ?>"
                                class="sidebar-sublink <?php echo $currentPage === 'api-settings' ? 'active' : ''; ?>">
                                <?php _e('admin_api_integration'); ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- User Navigation -->
                    <a href="<?php echo url('dashboard/'); ?>"
                        class="sidebar-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span><?php _e('dash_my_profile'); ?></span>
                    </a>
                    <a href="<?php echo url('dashboard/my-churches.php'); ?>"
                        class="sidebar-link <?php echo $currentPage === 'my-churches' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 21H6V12L3 12L12 3L21 12L18 12V21Z"></path>
                            <path d="M9 21V15H15V21"></path>
                        </svg>
                        <span><?php _e('dash_my_churches'); ?></span>
                    </a>
                    <a href="<?php echo url('dashboard/my-events.php'); ?>"
                        class="sidebar-link <?php echo $currentPage === 'my-events' ? 'active' : ''; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <path d="M16 2V6M8 2V6M3 10H21"></path>
                        </svg>
                        <span><?php _e('dash_my_events'); ?></span>
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo url('/'); ?>" class="sidebar-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span><?php _e('dash_view_site'); ?></span>
                </a>
                <a href="<?php echo url('auth/logout.php'); ?>" class="sidebar-link logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span><?php _e('dash_logout'); ?></span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="dashboard-main">
            <!-- Top Bar -->
            <header class="dashboard-topbar">
                <button class="menu-toggle" id="menuToggle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <h1 class="topbar-title"><?php echo htmlspecialchars($pageTitle); ?></h1>

                <div class="topbar-actions">
                    <div class="lang-switch">
                        <a href="<?php echo getLanguageSwitchUrl('en'); ?>"
                            class="<?php echo isCurrentLanguage('en') ? 'active' : ''; ?>">EN</a>
                        <a href="<?php echo getLanguageSwitchUrl('bm'); ?>"
                            class="<?php echo isCurrentLanguage('bm') ? 'active' : ''; ?>">BM</a>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <button class="user-trigger">
                            <div class="user-avatar"
                                style="background: <?php echo htmlspecialchars($user['avatar_color']); ?>">
                                <?php echo getUserInitials($user['name']); ?>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu">
                            <div class="dropdown-header">
                                <div class="user-avatar large"
                                    style="background: <?php echo htmlspecialchars($user['avatar_color']); ?>">
                                    <?php echo getUserInitials($user['name']); ?>
                                </div>
                                <div>
                                    <div class="dropdown-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <div class="dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo url('dashboard/'); ?>"
                                class="dropdown-item"><?php _e('dash_my_profile'); ?></a>
                            <a href="<?php echo url('dashboard/change-password.php'); ?>"
                                class="dropdown-item"><?php _e('change_password'); ?></a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo url('auth/logout.php'); ?>"
                                class="dropdown-item logout"><?php _e('dash_logout'); ?></a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="dashboard-content">