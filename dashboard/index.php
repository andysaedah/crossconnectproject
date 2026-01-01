<?php
/**
 * CrossConnect MY - User Dashboard / Profile Page
 */

require_once __DIR__ . '/../config/language.php';

$currentPage = 'index';
$pageTitle = __('dash_my_profile');

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../config/database.php';

$user = getCurrentUser();

// Get user stats
$stats = [
    'churches' => 0,
    'events' => 0
];

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM churches WHERE created_by = ?");
    $stmt->execute([$user['id']]);
    $stats['churches'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE created_by = ?");
    $stmt->execute([$user['id']]);
    $stats['events'] = $stmt->fetchColumn();
} catch (Exception $e) {
    // Silently fail
}
?>

<!-- Profile Header -->
<div class="profile-header">
    <div class="profile-avatar" style="background: <?php echo htmlspecialchars($user['avatar_color']); ?>">
        <?php echo getUserInitials($user['name']); ?>
    </div>
    <div class="profile-info">
        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
        <span
            class="role"><?php echo $user['role'] === 'admin' ? __('dash_administrator') : __('dash_member'); ?></span>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 21H6V12L3 12L12 3L21 12L18 12V21Z"></path>
                <path d="M9 21V15H15V21"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo $stats['churches']; ?></div>
        <div class="stat-label"><?php _e('churches_added'); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <path d="M16 2V6M8 2V6M3 10H21"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo $stats['events']; ?></div>
        <div class="stat-label"><?php _e('events_created'); ?></div>
    </div>
</div>

<!-- Profile Details -->
<div class="dashboard-card">
    <div class="card-header">
        <h3 class="card-title"><?php _e('dash_profile_info'); ?></h3>
        <button class="btn btn-secondary btn-sm" onclick="toggleEdit()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            <?php _e('dash_edit'); ?>
        </button>
    </div>
    <div class="card-body">
        <form id="profileForm" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('dash_full_name'); ?></label>
                    <input type="text" name="name" class="form-input"
                        value="<?php echo htmlspecialchars($user['name']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('auth_username'); ?></label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['username']); ?>"
                        disabled readonly>
                    <div class="form-hint"><?php _e('dash_username_cannot_change'); ?></div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('dash_email'); ?></label>
                    <input type="email" name="email" class="form-input"
                        value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('dash_church_name_optional'); ?></label>
                    <input type="text" name="church_name" class="form-input"
                        value="<?php echo htmlspecialchars($user['church_name'] ?? ''); ?>" disabled>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?php _e('dash_preferred_language'); ?></label>
                <select name="preferred_language" class="form-select" disabled>
                    <option value="en" <?php echo $user['preferred_language'] === 'en' ? 'selected' : ''; ?>>
                        <?php _e('english'); ?>
                    </option>
                    <option value="bm" <?php echo $user['preferred_language'] === 'bm' ? 'selected' : ''; ?>>
                        <?php _e('bahasa_malaysia'); ?></option>
                </select>
            </div>

            <div class="form-actions" id="formActions" style="display: none; margin-top: 24px;">
                <button type="submit" class="btn btn-primary"><?php _e('dash_save_changes'); ?></button>
                <button type="button" class="btn btn-secondary"
                    onclick="cancelEdit()"><?php _e('dash_cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Actions -->
<div style="margin-top: 24px;">
    <h3 style="margin-bottom: 16px; font-weight: 600;"><?php _e('dash_quick_actions'); ?></h3>
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="<?php echo url('dashboard/my-churches.php'); ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 21H6V12L3 12L12 3L21 12L18 12V21Z"></path>
            </svg>
            <?php _e('dash_manage_churches'); ?>
        </a>
        <a href="<?php echo url('dashboard/my-events.php'); ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <path d="M16 2V6M8 2V6M3 10H21"></path>
            </svg>
            <?php _e('dash_manage_events'); ?>
        </a>
        <a href="<?php echo url('dashboard/change-password.php'); ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <?php _e('change_password'); ?>
        </a>
    </div>
</div>

<script>
    let isEditing = false;
    const translations = {
        profileUpdated: <?php echo json_encode(__('dash_profile_updated')); ?>,
        updateFailed: <?php echo json_encode(__('dash_update_failed')); ?>,
        errorOccurred: <?php echo json_encode(__('dash_error_occurred')); ?>
    };

    function toggleEdit() {
        isEditing = !isEditing;
        const inputs = document.querySelectorAll('#profileForm input:not([readonly]), #profileForm select');
        inputs.forEach(input => input.disabled = !isEditing);
        document.getElementById('formActions').style.display = isEditing ? 'flex' : 'none';
    }

    function cancelEdit() {
        isEditing = false;
        const inputs = document.querySelectorAll('#profileForm input, #profileForm select');
        inputs.forEach(input => input.disabled = true);
        document.getElementById('formActions').style.display = 'none';
        document.getElementById('profileForm').reset();
    }

    document.getElementById('profileForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        try {
            const response = await fetch(basePath + 'api/user/profile.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(translations.profileUpdated, 'success');
                cancelEdit();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || translations.updateFailed, 'error');
            }
        } catch (error) {
            showToast(translations.errorOccurred, 'error');
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>