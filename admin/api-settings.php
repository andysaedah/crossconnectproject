<?php
/**
 * CrossConnect MY - Admin API Settings
 * Manage third-party API configurations including email providers
 */

$currentPage = 'api-settings';
$pageTitle = 'API Integration';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

requireAdmin();

// Get current settings
$emailSettings = getSettingsByGroup('email');
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2>API Integration</h2>
        <p>Manage third-party API keys and configurations</p>
    </div>
</div>

<!-- Settings Cards -->
<div class="settings-grid">
    <!-- Email Configuration -->
    <div class="settings-card email-settings">
        <div class="settings-card-header">
            <div class="settings-card-icon email">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
            </div>
            <div class="settings-card-title">
                <h3>Email Service</h3>
                <p>Configure email providers for transactional emails</p>
            </div>
        </div>
        <form id="emailSettingsForm" class="settings-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="group" value="email">

            <!-- Fallback Chain Configuration -->
            <div class="fallback-chain-section">
                <label class="form-label">Email Fallback Chain</label>
                <p class="form-hint" style="margin-bottom: 16px;">Configure which providers to use. SMTP2GO is always the primary provider.</p>
                
                <div class="fallback-chain">
                    <!-- SMTP2GO - Always Primary -->
                    <div class="fallback-item primary">
                        <div class="fallback-number">1</div>
                        <div class="fallback-content">
                            <span class="provider-badge smtp2go">SMTP2GO</span>
                            <span class="fallback-label">Primary (Always)</span>
                        </div>
                    </div>
                    
                    <!-- Brevo - Optional Fallback -->
                    <div class="fallback-arrow" id="brevo_arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M19 12l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="fallback-item optional" id="brevo_fallback_item">
                        <div class="fallback-checkbox">
                            <input type="checkbox" name="settings[enable_brevo_fallback]" id="enable_brevo_fallback" value="1"
                                <?php echo ($emailSettings['enable_brevo_fallback'] ?? '0') === '1' ? 'checked' : ''; ?>
                                onchange="updateFallbackChain()">
                            <label for="enable_brevo_fallback"></label>
                        </div>
                        <div class="fallback-number">2</div>
                        <div class="fallback-content">
                            <span class="provider-badge brevo">Brevo</span>
                            <span class="fallback-label">Fallback #1</span>
                        </div>
                    </div>
                    
                    <!-- PHP Mail - Optional Last Resort -->
                    <div class="fallback-arrow" id="phpmail_arrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M19 12l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="fallback-item optional" id="phpmail_fallback_item">
                        <div class="fallback-checkbox">
                            <input type="checkbox" name="settings[enable_phpmail_fallback]" id="enable_phpmail_fallback" value="1"
                                <?php echo ($emailSettings['enable_phpmail_fallback'] ?? '0') === '1' ? 'checked' : ''; ?>
                                onchange="updateFallbackChain()">
                            <label for="enable_phpmail_fallback"></label>
                        </div>
                        <div class="fallback-number">3</div>
                        <div class="fallback-content">
                            <span class="provider-badge phpmail">PHP Mail</span>
                            <span class="fallback-label">Last Resort</span>
                        </div>
                    </div>
                </div>
                
                <div class="fallback-summary" id="fallback_summary">
                    <!-- Updated by JavaScript -->
                </div>
            </div>

            <!-- Admin Notification Email -->
            <div class="form-group">
                <label class="form-label">Admin Notification Email</label>
                <input type="email" name="settings[admin_notification_email]" class="form-input"
                    value="<?php echo htmlspecialchars($emailSettings['admin_notification_email'] ?? ''); ?>"
                    placeholder="admin@yourdomain.com">
                <p class="form-hint">Email address to receive system notifications (amendment reports, etc.)</p>
            </div>

            <div class="provider-divider"></div>

            <!-- SMTP2GO Settings -->
            <div class="provider-section" id="smtp2go_settings">
                <h4 class="provider-title">
                    <span class="provider-badge smtp2go">SMTP2GO</span>
                    Settings
                    <span class="provider-status primary-status">Primary</span>
                </h4>
                <div class="form-group">
                    <label class="form-label">API Key <span class="required">*</span></label>
                    <div class="api-key-input">
                        <input type="password" name="settings[smtp2go_api_key]" id="smtp2go_api_key" class="form-input"
                            value="<?php echo htmlspecialchars($emailSettings['smtp2go_api_key'] ?? ''); ?>"
                            placeholder="Enter your SMTP2GO API key">
                        <button type="button" class="btn-icon" onclick="toggleApiKey('smtp2go_api_key')"
                            title="Show/Hide">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <p class="form-hint">Get your API key from <a href="https://app.smtp2go.com/settings/api_keys/"
                            target="_blank">SMTP2GO Dashboard</a></p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Sender Email</label>
                        <input type="email" name="settings[smtp2go_sender_email]" class="form-input"
                            value="<?php echo htmlspecialchars($emailSettings['smtp2go_sender_email'] ?? 'noreply@crossconnect.my'); ?>"
                            placeholder="noreply@yourdomain.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sender Name</label>
                        <input type="text" name="settings[smtp2go_sender_name]" class="form-input"
                            value="<?php echo htmlspecialchars($emailSettings['smtp2go_sender_name'] ?? 'CrossConnect MY'); ?>"
                            placeholder="Your App Name">
                    </div>
                </div>
            </div>

            <div class="provider-divider"></div>

            <!-- Brevo Settings -->
            <div class="provider-section" id="brevo_settings">
                <h4 class="provider-title">
                    <span class="provider-badge brevo">Brevo</span>
                    Settings
                    <span class="provider-status fallback-status" id="brevo_status">Disabled</span>
                </h4>
                <div class="form-group">
                    <label class="form-label">API Key</label>
                    <div class="api-key-input">
                        <input type="password" name="settings[brevo_api_key]" id="brevo_api_key" class="form-input"
                            value="<?php echo htmlspecialchars($emailSettings['brevo_api_key'] ?? ''); ?>"
                            placeholder="Enter your Brevo API key">
                        <button type="button" class="btn-icon" onclick="toggleApiKey('brevo_api_key')"
                            title="Show/Hide">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <p class="form-hint">Get your API key from <a href="https://app.brevo.com/settings/keys/api"
                            target="_blank">Brevo Dashboard</a></p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Sender Email</label>
                        <input type="email" name="settings[brevo_sender_email]" class="form-input"
                            value="<?php echo htmlspecialchars($emailSettings['brevo_sender_email'] ?? 'noreply@crossconnect.my'); ?>"
                            placeholder="noreply@yourdomain.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sender Name</label>
                        <input type="text" name="settings[brevo_sender_name]" class="form-input"
                            value="<?php echo htmlspecialchars($emailSettings['brevo_sender_name'] ?? 'CrossConnect MY'); ?>"
                            placeholder="Your App Name">
                    </div>
                </div>
            </div>

            <div class="provider-divider"></div>

            <!-- PHP Mail Info -->
            <div class="provider-section" id="phpmail_settings">
                <h4 class="provider-title">
                    <span class="provider-badge phpmail">PHP Mail</span>
                    Info
                    <span class="provider-status fallback-status" id="phpmail_status">Disabled</span>
                </h4>
                <div class="info-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <div>
                        <strong>Server's Built-in Mail</strong>
                        <p>Uses your server's PHP mail() function. No configuration needed, but delivery may be less
                            reliable than API providers. Only used when enabled as fallback.</p>
                    </div>
                </div>
            </div>

            <div class="settings-card-footer">
                <button type="button" class="btn btn-secondary" onclick="testEmail()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13"></path>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    Send Test Email
                </button>
                <button type="submit" class="btn btn-primary" id="saveEmailBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Future API Integrations Placeholder -->
    <div class="settings-card add-new">
        <div class="add-new-content">
            <div class="add-new-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
            </div>
            <h3>More Integrations Coming Soon</h3>
            <p>Additional API integrations will be available in future updates</p>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal-overlay" id="testEmailModalOverlay" onclick="closeTestEmailModal()"></div>
<div class="modal" id="testEmailModal">
    <div class="modal-header">
        <h3>Send Test Email</h3>
        <button class="modal-close" onclick="closeTestEmailModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <form id="testEmailForm" onsubmit="sendTestEmail(event)">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Recipient Email</label>
                <input type="email" name="email" id="testEmailAddress" class="form-input" required
                    placeholder="Enter email to receive test">
            </div>
            <div class="form-group">
                <label class="form-label">Test With Provider</label>
                <select name="provider" id="testEmailProvider" class="form-select">
                    <option value="">Use Primary (with fallback)</option>
                    <option value="smtp2go">SMTP2GO Only</option>
                    <option value="brevo">Brevo Only</option>
                    <option value="phpmail">PHP Mail Only</option>
                </select>
                <p class="form-hint">Leave blank to test the full fallback chain</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeTestEmailModal()">Cancel</button>
            <button type="submit" class="btn btn-primary" id="sendTestBtn">Send Test</button>
        </div>
    </form>
</div>

<style>
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
        gap: 24px;
    }

    @media (max-width: 560px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }
    }

    .settings-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .settings-card.email-settings {
        grid-column: 1 / -1;
        max-width: 800px;
    }

    .settings-card-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--color-border);
        background: linear-gradient(to right, var(--color-bg), white);
    }

    .settings-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .settings-card-icon.email {
        background: linear-gradient(135deg, #0891b2, #0e7490);
    }

    .settings-card-icon svg {
        width: 24px;
        height: 24px;
        color: white;
    }

    .settings-card-title h3 {
        margin: 0 0 4px;
        font-size: 1.1rem;
    }

    .settings-card-title p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--color-text-light);
    }

    .settings-form {
        padding: 24px;
    }

    .api-key-input {
        display: flex;
        gap: 8px;
    }

    .api-key-input input {
        flex: 1;
        font-family: monospace;
    }

    .btn-icon {
        width: 42px;
        height: 42px;
        border: 1px solid var(--color-border);
        background: white;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .btn-icon:hover {
        background: var(--color-bg);
        border-color: var(--color-primary);
    }

    .btn-icon svg {
        width: 18px;
        height: 18px;
        color: var(--color-text-light);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    @media (max-width: 500px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    .provider-divider {
        height: 1px;
        background: var(--color-border);
        margin: 24px 0;
    }

    .provider-section {
        margin-bottom: 8px;
    }

    .provider-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 16px;
        font-size: 0.95rem;
        font-weight: 600;
    }

    .provider-badge {
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .provider-badge.smtp2go {
        background: #e0f2fe;
        color: #0369a1;
    }

    .provider-badge.brevo {
        background: #f0fdf4;
        color: #15803d;
    }

    .provider-badge.phpmail {
        background: #fef3c7;
        color: #b45309;
    }

    .provider-status {
        margin-left: auto;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 500;
    }

    .primary-status {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .fallback-status {
        background: #f3f4f6;
        color: #6b7280;
    }

    .fallback-status.enabled {
        background: #dcfce7;
        color: #15803d;
    }

    /* Fallback Chain Styles */
    .fallback-chain-section {
        margin-bottom: 20px;
    }

    .fallback-chain {
        display: flex;
        flex-direction: column;
        gap: 0;
        background: var(--color-bg);
        border-radius: 10px;
        padding: 16px;
        border: 1px solid var(--color-border);
    }

    .fallback-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: white;
        border-radius: 8px;
        border: 1px solid var(--color-border);
    }

    .fallback-item.primary {
        border-color: #0891b2;
        background: linear-gradient(to right, #ecfeff, white);
    }

    .fallback-item.optional {
        opacity: 0.6;
        transition: opacity 0.2s;
    }

    .fallback-item.optional.enabled {
        opacity: 1;
    }

    .fallback-number {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--color-border);
        color: var(--color-text-light);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        flex-shrink: 0;
    }

    .fallback-item.primary .fallback-number {
        background: #0891b2;
        color: white;
    }

    .fallback-item.optional.enabled .fallback-number {
        background: #10b981;
        color: white;
    }

    .fallback-content {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
    }

    .fallback-label {
        font-size: 0.85rem;
        color: var(--color-text-light);
    }

    .fallback-checkbox {
        position: relative;
    }

    .fallback-checkbox input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #10b981;
    }

    .fallback-arrow {
        display: flex;
        justify-content: center;
        padding: 4px 0;
        opacity: 0.3;
        transition: opacity 0.2s;
    }

    .fallback-arrow.enabled {
        opacity: 1;
    }

    .fallback-arrow svg {
        width: 20px;
        height: 20px;
        color: #10b981;
    }

    .fallback-summary {
        margin-top: 12px;
        padding: 12px 16px;
        background: #f0f9ff;
        border-radius: 8px;
        border: 1px solid #bae6fd;
        font-size: 0.85rem;
        color: #0369a1;
    }

    .fallback-summary strong {
        color: #0c4a6e;
    }

    .info-box {
        display: flex;
        gap: 12px;
        padding: 16px;
        background: #fef3c7;
        border-radius: 8px;
        border: 1px solid #fcd34d;
    }

    .info-box svg {
        width: 20px;
        height: 20px;
        color: #b45309;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .info-box strong {
        display: block;
        color: #92400e;
        margin-bottom: 4px;
    }

    .info-box p {
        margin: 0;
        font-size: 0.85rem;
        color: #a16207;
        line-height: 1.5;
    }

    .settings-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 24px;
        margin-top: 8px;
        border-top: 1px solid var(--color-border);
    }

    .settings-card.add-new {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 300px;
        border: 2px dashed var(--color-border);
        background: var(--color-bg);
    }

    .add-new-content {
        text-align: center;
        padding: 40px;
    }

    .add-new-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        background: var(--color-border);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .add-new-icon svg {
        width: 32px;
        height: 32px;
        color: var(--color-text-light);
    }

    .add-new-content h3 {
        margin: 0 0 8px;
        color: var(--color-text-light);
        font-size: 1rem;
    }

    .add-new-content p {
        margin: 0;
        color: var(--color-text-light);
        font-size: 0.875rem;
    }

    .required {
        color: #ef4444;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 200;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.95);
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 450px;
        z-index: 201;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s;
    }

    .modal.show {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--color-border);
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
        color: var(--color-text-light);
    }

    .modal-close:hover {
        background: var(--color-bg);
    }

    .modal-close svg {
        width: 20px;
        height: 20px;
    }

    .modal-body {
        padding: 24px;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--color-border);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
</style>

<script>
    function toggleApiKey(inputId) {
        const input = document.getElementById(inputId);
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    function updateFallbackChain() {
        const brevoEnabled = document.getElementById('enable_brevo_fallback').checked;
        const phpmailEnabled = document.getElementById('enable_phpmail_fallback').checked;
        
        // Update Brevo UI
        const brevoItem = document.getElementById('brevo_fallback_item');
        const brevoArrow = document.getElementById('brevo_arrow');
        const brevoStatus = document.getElementById('brevo_status');
        
        if (brevoEnabled) {
            brevoItem.classList.add('enabled');
            brevoArrow.classList.add('enabled');
            brevoStatus.textContent = 'Fallback #1';
            brevoStatus.classList.add('enabled');
        } else {
            brevoItem.classList.remove('enabled');
            brevoArrow.classList.remove('enabled');
            brevoStatus.textContent = 'Disabled';
            brevoStatus.classList.remove('enabled');
        }
        
        // Update PHP Mail UI
        const phpmailItem = document.getElementById('phpmail_fallback_item');
        const phpmailArrow = document.getElementById('phpmail_arrow');
        const phpmailStatus = document.getElementById('phpmail_status');
        
        if (phpmailEnabled) {
            phpmailItem.classList.add('enabled');
            phpmailArrow.classList.add('enabled');
            phpmailStatus.textContent = brevoEnabled ? 'Fallback #2' : 'Fallback #1';
            phpmailStatus.classList.add('enabled');
        } else {
            phpmailItem.classList.remove('enabled');
            phpmailArrow.classList.remove('enabled');
            phpmailStatus.textContent = 'Disabled';
            phpmailStatus.classList.remove('enabled');
        }
        
        // Update summary
        const summary = document.getElementById('fallback_summary');
        let chain = ['SMTP2GO'];
        if (brevoEnabled) chain.push('Brevo');
        if (phpmailEnabled) chain.push('PHP Mail');
        
        if (chain.length === 1) {
            summary.innerHTML = '<strong>Current Chain:</strong> SMTP2GO only (no fallback)';
        } else {
            summary.innerHTML = '<strong>Current Chain:</strong> ' + chain.join(' → ');
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', updateFallbackChain);

    // Save email settings
    document.getElementById('emailSettingsForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = document.getElementById('saveEmailBtn');
        btn.disabled = true;
        btn.innerHTML = '<span>Saving...</span>';

        try {
            const formData = new FormData(this);
            formData.append('action', 'save');

            const response = await fetch(basePath + 'api/admin/settings.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message || 'Settings saved successfully', 'success');
            } else {
                showToast(data.error || 'Failed to save settings', 'error');
            }
        } catch (error) {
            showToast('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Save Settings';
        }
    });

    function testEmail() {
        document.getElementById('testEmailModalOverlay').classList.add('show');
        document.getElementById('testEmailModal').classList.add('show');
    }

    function closeTestEmailModal() {
        document.getElementById('testEmailModalOverlay').classList.remove('show');
        document.getElementById('testEmailModal').classList.remove('show');
    }

    async function sendTestEmail(e) {
        e.preventDefault();

        const btn = document.getElementById('sendTestBtn');
        btn.disabled = true;
        btn.textContent = 'Sending...';

        try {
            const formData = new FormData(document.getElementById('testEmailForm'));
            formData.append('action', 'test_email');

            const response = await fetch(basePath + 'api/admin/settings.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                let message = data.message || 'Test email sent!';
                if (data.provider) {
                    message += ' (via ' + data.provider + ')';
                }
                showToast(message, 'success');
                closeTestEmailModal();
            } else {
                showToast(data.error || 'Failed to send test email', 'error');
            }
        } catch (error) {
            showToast('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Send Test';
        }
    }

    // Close modal on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeTestEmailModal();
    });
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>