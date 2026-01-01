<?php
/**
 * CrossConnect MY - Change Password Page
 */

require_once __DIR__ . '/../config/language.php';

$currentPage = 'change-password';
$pageTitle = __('change_password');

require_once __DIR__ . '/../includes/dashboard-header.php';

$user = getCurrentUser();
?>

<div style="max-width: 500px;">
    <!-- Page Header -->
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0 0 4px; font-size: 1.5rem;"><?php _e('change_password'); ?></h2>
        <p style="margin: 0; color: var(--color-text-light);"><?php _e('change_password_subtitle'); ?></p>
    </div>

    <div class="dashboard-card">
        <div class="card-body">
            <form id="passwordForm" onsubmit="changePassword(event)">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="form-group">
                    <label class="form-label"><?php _e('current_password'); ?> *</label>
                    <input type="password" name="current_password" id="currentPassword" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php _e('new_password'); ?> *</label>
                    <input type="password" name="new_password" id="newPassword" class="form-input" required
                        minlength="8">
                    <div class="form-hint"><?php _e('min_8_characters'); ?></div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php _e('confirm_new_password'); ?> *</label>
                    <input type="password" name="confirm_password" id="confirmPassword" class="form-input" required>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary" id="saveBtn"><?php _e('update_password'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div
        style="margin-top: 24px; padding: 16px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
        <div style="font-weight: 600; color: #92400e; margin-bottom: 4px;"><?php _e('security_tips'); ?></div>
        <ul style="margin: 0; padding-left: 20px; color: #78350f; font-size: 0.875rem;">
            <li><?php _e('security_tip_1'); ?></li>
            <li><?php _e('security_tip_2'); ?></li>
            <li><?php _e('security_tip_3'); ?></li>
        </ul>
    </div>
</div>

<script>
    const translations = {
        passwordsNoMatch: <?php echo json_encode(__('passwords_no_match')); ?>,
        passwordMinLength: <?php echo json_encode(__('password_min_length')); ?>,
        passwordUpdated: <?php echo json_encode(__('password_updated')); ?>,
        passwordUpdateFailed: <?php echo json_encode(__('password_update_failed')); ?>,
        errorOccurred: <?php echo json_encode(__('dash_error_occurred')); ?>,
        updating: <?php echo json_encode(__('updating')); ?>,
        updatePassword: <?php echo json_encode(__('update_password')); ?>
    };

    async function changePassword(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('saveBtn');

        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword !== confirmPassword) {
            showToast(translations.passwordsNoMatch, 'error');
            return;
        }

        if (newPassword.length < 8) {
            showToast(translations.passwordMinLength, 'error');
            return;
        }

        btn.disabled = true;
        btn.textContent = translations.updating;

        try {
            const formData = new FormData(form);
            const response = await fetch(basePath + 'api/user/change-password.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(translations.passwordUpdated, 'success');
                form.reset();
            } else {
                showToast(data.error || translations.passwordUpdateFailed, 'error');
            }
        } catch (error) {
            showToast(translations.errorOccurred, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = translations.updatePassword;
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>