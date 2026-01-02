<?php
/**
 * CrossConnect MY - Admin Site Configuration
 * Manage general site settings including URL configuration
 */

$currentPage = 'site-config';
$pageTitle = 'Site Config'; // Set before include, will be overridden after

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

requireAdmin();

// Update page title with translation (after language is loaded)
$pageTitle = __('admin_site_config');

// Get current settings
$cleanUrls = getSetting('clean_urls', '0');
$forceHttps = getSetting('force_https', '1');
$debugMode = getSetting('debug_mode', '0');
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2><?php _e('admin_site_config'); ?></h2>
        <p><?php _e('site_config_desc'); ?></p>
    </div>
</div>

<!-- Settings Form -->
<div class="dashboard-card" style="max-width: 800px;">
    <div class="card-header">
        <h3><?php _e('url_settings'); ?></h3>
    </div>
    <form id="siteConfigForm" class="card-body">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="group" value="general">

        <!-- Clean URLs Toggle -->
        <div class="setting-item">
            <div class="setting-info">
                <label class="setting-label"><?php _e('clean_urls'); ?></label>
                <p class="setting-description"><?php _e('clean_urls_desc'); ?></p>
                <div class="setting-examples">
                    <div class="example">
                        <span class="example-label"><?php _e('disabled'); ?>:</span>
                        <code>church.php?slug=example-church</code>
                    </div>
                    <div class="example">
                        <span class="example-label"><?php _e('enabled'); ?>:</span>
                        <code>church/example-church</code>
                    </div>
                </div>
            </div>
            <div class="setting-control">
                <label class="toggle-switch">
                    <input type="hidden" name="settings[clean_urls]" value="0">
                    <input type="checkbox" name="settings[clean_urls]" id="clean_urls" value="1"
                        <?php echo $cleanUrls === '1' ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- Requirements Notice -->
        <div class="requirements-notice">
            <div class="notice-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>
            <div class="notice-content">
                <strong><?php _e('requirements'); ?></strong>
                <ul>
                    <li><?php _e('clean_urls_req_1'); ?></li>
                    <li><?php _e('clean_urls_req_2'); ?></li>
                </ul>
            </div>
        </div>

        <div class="form-divider"></div>

        <!-- Force HTTPS Toggle -->
        <div class="setting-item">
            <div class="setting-info">
                <label class="setting-label"><?php _e('force_https'); ?></label>
                <p class="setting-description"><?php _e('force_https_desc'); ?></p>
            </div>
            <div class="setting-control">
                <label class="toggle-switch">
                    <input type="hidden" name="settings[force_https]" value="0">
                    <input type="checkbox" name="settings[force_https]" id="force_https" value="1"
                        <?php echo $forceHttps === '1' ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <div class="form-divider"></div>

        <!-- Debug Mode Toggle -->
        <div class="setting-item">
            <div class="setting-info">
                <label class="setting-label"><?php _e('debug_mode'); ?></label>
                <p class="setting-description"><?php _e('debug_mode_desc'); ?></p>
            </div>
            <div class="setting-control">
                <label class="toggle-switch">
                    <input type="hidden" name="settings[debug_mode]" value="0">
                    <input type="checkbox" name="settings[debug_mode]" id="debug_mode" value="1"
                        <?php echo $debugMode === '1' ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- Debug Warning -->
        <div class="requirements-notice" style="background: #fef3c7; border-color: #f59e0b;">
            <div class="notice-icon" style="color: #d97706;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div class="notice-content">
                <strong><?php _e('for_developers'); ?></strong>
                <p style="margin: 4px 0 0; font-size: 0.85rem;"><?php _e('debug_mode_warning'); ?></p>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary" id="saveBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <?php _e('save_settings'); ?>
            </button>
        </div>
    </form>
</div>

<style>
    .setting-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 24px;
        padding: 20px 0;
    }

    .setting-item:first-child {
        padding-top: 0;
    }

    .setting-info {
        flex: 1;
    }

    .setting-label {
        font-size: 1rem;
        font-weight: 600;
        color: var(--color-text);
        display: block;
        margin-bottom: 4px;
    }

    .setting-description {
        font-size: 0.875rem;
        color: var(--color-text-light);
        margin: 0 0 12px;
        line-height: 1.5;
    }

    .setting-examples {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .example {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
    }

    .example-label {
        color: var(--color-text-light);
        min-width: 70px;
    }

    .example code {
        background: var(--color-bg);
        padding: 4px 8px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.8rem;
        color: var(--color-primary);
    }

    .setting-control {
        flex-shrink: 0;
    }

    /* Toggle Switch */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 52px;
        height: 28px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.3s;
        border-radius: 28px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .toggle-switch input:checked+.toggle-slider {
        background-color: var(--color-primary);
    }

    .toggle-switch input:checked+.toggle-slider:before {
        transform: translateX(24px);
    }

    /* Requirements Notice */
    .requirements-notice {
        display: flex;
        gap: 12px;
        padding: 16px;
        background: #fef3c7;
        border: 1px solid #fcd34d;
        border-radius: 8px;
        margin-top: 16px;
    }

    .notice-icon {
        flex-shrink: 0;
    }

    .notice-icon svg {
        width: 20px;
        height: 20px;
        color: #b45309;
    }

    .notice-content strong {
        color: #92400e;
        display: block;
        margin-bottom: 8px;
        font-size: 0.875rem;
    }

    .notice-content ul {
        margin: 0;
        padding-left: 18px;
        font-size: 0.8rem;
        color: #a16207;
    }

    .notice-content li {
        margin-bottom: 4px;
    }

    .form-divider {
        height: 1px;
        background: var(--color-border);
        margin: 8px 0;
    }

    .form-actions {
        padding-top: 24px;
        border-top: 1px solid var(--color-border);
        margin-top: 20px;
    }

    @media (max-width: 600px) {
        .setting-item {
            flex-direction: column;
            gap: 16px;
        }

        .setting-control {
            align-self: flex-start;
        }
    }
</style>

<script>
    document.getElementById('siteConfigForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> <?php echo addslashes(__("saving")); ?>';

        const formData = new FormData(this);
        
        // Debug: Log what we're sending
        console.log('Sending form data:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        try {
            const response = await fetch('<?php echo url("api/admin/settings.php"); ?>', {
                method: 'POST',
                body: formData
            });

            // Debug: Log raw response
            const responseText = await response.text();
            console.log('Raw API response:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response was:', responseText);
                showToast('Server error: ' + responseText.substring(0, 100), 'error');
                return;
            }

            if (data.success) {
                showToast('<?php echo addslashes(__("success_settings_saved")); ?>', 'success');
            } else {
                showToast(data.error || '<?php echo addslashes(__("error_saving_settings")); ?>', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('<?php echo addslashes(__("error_saving_settings")); ?>', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg> <?php echo addslashes(__("save_settings")); ?>`;
        }
    });

    function showToast(message, type = 'success') {
        // Remove existing toast
        const existingToast = document.querySelector('.toast');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'success' ? '#10b981' : '#ef4444'};
            color: white;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
