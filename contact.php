<?php
/**
 * CrossConnect MY - Contact Page
 * Redesigned with actionable sections
 */

require_once 'config/database.php';
require_once 'config/language.php';
require_once 'config/auth.php';
require_once 'config/settings.php';

$pageTitle = __('contact_title');
$pageDescription = __('contact_intro');

// Check login status
$user = getCurrentUser();
$isLoggedIn = $user !== null;

// Get admin email from settings
$adminEmail = getSetting('admin_notification_email', 'hello@crossconnect.my');

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title"><?php _e('contact_title'); ?></h1>
    <p class="page-subtitle"><?php _e('contact_subtitle'); ?></p>
</div>

<!-- Contact Content -->
<section class="contact-section">
    <div class="contact-grid">

        <!-- General Enquiries -->
        <div class="contact-card">
            <div class="contact-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
            </div>
            <h3><?php _e('general_enquiries'); ?></h3>
            <p><?php _e('general_enquiries_desc'); ?></p>
            <a href="mailto:hello@crossconnect.my" class="contact-email">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                hello@crossconnect.my
            </a>
        </div>

        <!-- Add Church/Event -->
        <div class="contact-card add-listing">
            <div class="contact-card-icon church">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
            </div>
            <h3><?php _e('add_your_church'); ?></h3>
            <p><?php _e('add_church_login_required'); ?></p>

            <a href="<?php echo $isLoggedIn ? url('dashboard/') : url('auth/login.php'); ?>"
                class="btn btn-primary btn-action">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <?php _e('add_church_event'); ?>
            </a>
        </div>

        <!-- Report Bug/Issue -->
        <div class="contact-card bug-report">
            <div class="contact-card-icon bug">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <h3><?php _e('report_bug_title'); ?></h3>
            <p><?php _e('report_bug_desc'); ?></p>

            <form id="bugReportForm" class="bug-report-form">
                <!-- Honeypot field for bot detection - hidden from users -->
                <div style="position: absolute; left: -9999px;">
                    <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="form-label"><?php _e('subject'); ?></label>
                    <input type="text" name="subject" value="Bugs / Issues" readonly class="form-input readonly">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('message'); ?> *</label>
                    <textarea name="message" class="form-textarea" rows="4" maxlength="5000"
                        placeholder="<?php _e('describe_the_issue'); ?>" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('your_email'); ?> (<?php _e('optional'); ?>)</label>
                    <input type="email" name="email" class="form-input" placeholder="your@email.com">
                </div>
                <button type="submit" class="btn btn-primary btn-action" id="submitBugBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    <?php _e('submit_report'); ?>
                </button>
            </form>
        </div>

        <!-- Partnership -->
        <div class="contact-card">
            <div class="contact-card-icon partnership">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <h3><?php _e('partnership'); ?></h3>
            <p><?php _e('partnership_desc'); ?></p>
            <p class="partnership-contact">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <?php _e('email_us'); ?>: <a href="mailto:partnership@crossconnect.my">partnership@crossconnect.my</a>
            </p>
        </div>

    </div>
</section>

<style>
    .contact-section {
        max-width: 1000px;
        margin: 0 auto;
        padding: 40px 20px 80px;
    }

    .contact-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }

    @media (max-width: 768px) {
        .contact-grid {
            grid-template-columns: 1fr;
        }
    }

    .contact-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .contact-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    .contact-card.bug-report {
        grid-column: span 2;
    }

    @media (max-width: 768px) {
        .contact-card.bug-report {
            grid-column: span 1;
        }
    }

    .contact-card-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }

    .contact-card-icon svg {
        width: 28px;
        height: 28px;
        color: white;
    }

    .contact-card-icon.church {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .contact-card-icon.bug {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .contact-card-icon.partnership {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    }

    .contact-card h3 {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0 0 8px;
        color: var(--color-text);
    }

    .contact-card p {
        color: var(--color-text-light);
        font-size: 0.95rem;
        line-height: 1.6;
        margin: 0 0 20px;
    }

    .contact-email {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        background: var(--color-bg);
        border-radius: 10px;
        color: var(--color-primary);
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .contact-email:hover {
        background: var(--color-primary);
        color: white;
    }

    .contact-email svg {
        width: 18px;
        height: 18px;
    }

    .contact-email.partnership {
        color: #7c3aed;
    }

    .contact-email.partnership:hover {
        background: #7c3aed;
        color: white;
    }

    /* Action Buttons */
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 28px;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
        color: white;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.25s ease;
        box-shadow: 0 4px 14px rgba(8, 145, 178, 0.3);
    }

    .btn-action svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
        color: white;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(8, 145, 178, 0.45);
        background: linear-gradient(135deg, var(--color-primary-dark), #065f73);
        color: white;
    }

    .btn-action:active {
        transform: translateY(0);
    }

    /* Partnership Contact */
    .partnership-contact {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 16px !important;
        margin-bottom: 0 !important;
        font-size: 0.95rem;
        color: var(--color-text);
    }

    .partnership-contact svg {
        width: 20px;
        height: 20px;
        color: #7c3aed;
        flex-shrink: 0;
    }

    .partnership-contact a {
        color: #7c3aed;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s;
    }

    .partnership-contact a:hover {
        color: #6d28d9;
        text-decoration: underline;
    }

    /* Action Tiles (Logged In) */
    .action-tiles {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .action-tile {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 20px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border: 1px solid var(--color-border);
        border-radius: 14px;
        text-decoration: none;
        transition: all 0.25s ease;
    }

    .action-tile:hover {
        transform: translateX(6px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .action-tile.church-tile:hover {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border-color: #10b981;
    }

    .action-tile.event-tile:hover {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border-color: #3b82f6;
    }

    .tile-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .church-tile .tile-icon {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .event-tile .tile-icon {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
    }

    .tile-icon svg {
        width: 24px;
        height: 24px;
        color: white;
    }

    .tile-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .tile-label {
        font-weight: 600;
        font-size: 1rem;
        color: var(--color-text);
    }

    .tile-hint {
        font-size: 0.85rem;
        color: var(--color-text-light);
    }

    .tile-arrow {
        width: 20px;
        height: 20px;
        color: var(--color-text-light);
        transition: transform 0.2s, color 0.2s;
    }

    .action-tile:hover .tile-arrow {
        transform: translateX(4px);
        color: var(--color-primary);
    }

    /* Auth Actions (Not Logged In) */
    .auth-actions {
        display: flex;
        align-items: stretch;
        gap: 16px;
    }

    .auth-btn {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 20px;
        border-radius: 14px;
        text-decoration: none;
        transition: all 0.25s ease;
    }

    .auth-btn.primary {
        background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
        color: white;
        box-shadow: 0 4px 14px rgba(8, 145, 178, 0.35);
    }

    .auth-btn.primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(8, 145, 178, 0.45);
    }

    .auth-btn.secondary {
        background: white;
        border: 2px solid var(--color-border);
        color: var(--color-text);
    }

    .auth-btn.secondary:hover {
        border-color: var(--color-primary);
        background: linear-gradient(135deg, #ecfeff, #cffafe);
        transform: translateY(-3px);
    }

    .auth-btn-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .auth-btn.primary .auth-btn-icon {
        background: rgba(255, 255, 255, 0.2);
    }

    .auth-btn.secondary .auth-btn-icon {
        background: var(--color-bg);
    }

    .auth-btn-icon svg {
        width: 22px;
        height: 22px;
    }

    .auth-btn.primary .auth-btn-icon svg {
        color: white;
    }

    .auth-btn.secondary .auth-btn-icon svg {
        color: var(--color-primary);
    }

    .auth-btn-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .auth-btn-label {
        font-weight: 700;
        font-size: 1rem;
    }

    .auth-btn-hint {
        font-size: 0.8rem;
        opacity: 0.85;
    }

    .auth-btn.primary .auth-btn-hint {
        color: rgba(255, 255, 255, 0.85);
    }

    .auth-btn.secondary .auth-btn-hint {
        color: var(--color-text-light);
    }

    .auth-divider {
        display: flex;
        align-items: center;
        color: var(--color-text-light);
        font-size: 0.85rem;
        font-weight: 500;
    }

    @media (max-width: 600px) {
        .auth-actions {
            flex-direction: column;
        }

        .auth-divider {
            justify-content: center;
            padding: 8px 0;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--color-border);
            margin: 0 12px;
        }
    }

    /* Bug Report Form */
    .bug-report-form {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--color-border);
    }

    .bug-report-form .form-group {
        margin-bottom: 16px;
    }

    .bug-report-form .form-label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        font-size: 0.9rem;
        color: var(--color-text);
    }

    .bug-report-form .form-input,
    .bug-report-form .form-textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid var(--color-border);
        border-radius: 10px;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .bug-report-form .form-input:focus,
    .bug-report-form .form-textarea:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--color-primary-bg);
    }

    .bug-report-form .form-input.readonly {
        background: var(--color-bg);
        color: var(--color-text-light);
        cursor: not-allowed;
    }

    .bug-report-form .form-textarea {
        resize: vertical;
        min-height: 100px;
    }

    .bug-report-form .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .bug-report-form .btn svg {
        width: 18px;
        height: 18px;
    }
</style>

<script>
    document.getElementById('bugReportForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const form = this;
        const btn = document.getElementById('submitBugBtn');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="60" stroke-dashoffset="20"></circle></svg> Sending...';

        try {
            const formData = new FormData(form);

            console.log('Submitting bug report...');

            const response = await fetch('<?php echo url("api/report-bug.php"); ?>', {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', response.status);

            const text = await response.text();
            console.log('Response text:', text);

            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('Server returned invalid response');
            }

            if (data.success) {
                // Replace form with success message
                form.innerHTML = '<div class="success-message"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg><p>Thank you! Your report has been submitted successfully.</p></div>';
            } else {
                showFormError(data.error || 'Something went wrong. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Submit error:', error);
            showFormError('Connection error. Please check your internet and try again.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    function showFormError(message) {
        // Remove any existing error
        const existingError = document.querySelector('.form-error-toast');
        if (existingError) existingError.remove();

        // Create error toast
        const toast = document.createElement('div');
        toast.className = 'form-error-toast';
        toast.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><span>' + message + '</span>';

        document.querySelector('.bug-report-form').prepend(toast);

        // Auto-remove after 5 seconds
        setTimeout(() => toast.remove(), 5000);
    }
</script>

<style>
    .success-message {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        padding: 30px;
        text-align: center;
    }

    .success-message svg {
        width: 48px;
        height: 48px;
        color: #10b981;
    }

    .success-message p {
        color: var(--color-text);
        font-weight: 500;
        margin: 0;
    }

    .spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .form-error-toast {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 18px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        color: #dc2626;
        margin-bottom: 16px;
        animation: slideIn 0.3s ease;
    }

    .form-error-toast svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<?php require_once 'includes/footer.php'; ?>