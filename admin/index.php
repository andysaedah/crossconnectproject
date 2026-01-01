<?php
/**
 * CrossConnect MY - Admin Dashboard
 */

$currentPage = 'index';
$pageTitle = 'Admin Dashboard';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

// Require admin
requireAdmin();

// Get stats
$stats = [];
$pdo = getDbConnection();
try {
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['churches'] = $pdo->query("SELECT COUNT(*) FROM churches")->fetchColumn();
    $stats['events'] = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $stats['pending_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE email_verified_at IS NULL")->fetchColumn();

    // Recent activity
    $recentActivity = $pdo->query("
        SELECT al.*, u.name as user_name, u.avatar_color
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Recent users
    $recentUsers = $pdo->query("
        SELECT * FROM users
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
}
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo $stats['users'] ?? 0; ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 21H6V12L3 12L12 3L21 12L18 12V21Z"></path>
                <path d="M9 21V15H15V21"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo $stats['churches'] ?? 0; ?></div>
        <div class="stat-label">Total Churches</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <path d="M16 2V6M8 2V6M3 10H21"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo $stats['events'] ?? 0; ?></div>
        <div class="stat-label">Total Events</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 6v6l4 2"></path>
            </svg>
        </div>
        <div class="stat-value"><?php echo $stats['pending_users'] ?? 0; ?></div>
        <div class="stat-label">Pending Verification</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr; gap: 24px;">
    <?php if (isset($recentActivity) && !empty($recentActivity)): ?>
        <!-- Recent Activity -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
                <a href="<?php echo url('admin/logs.php'); ?>" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td>
                                    <?php if ($activity['user_name']): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div class="user-avatar"
                                                style="width: 28px; height: 28px; font-size: 0.7rem; background: <?php echo htmlspecialchars($activity['avatar_color'] ?? '#0891b2'); ?>">
                                                <?php echo getUserInitials($activity['user_name']); ?>
                                            </div>
                                            <?php echo htmlspecialchars($activity['user_name']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--color-text-light);">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($activity['action']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($activity['description'] ?? '-'); ?></td>
                                <td style="color: var(--color-text-light); font-size: 0.875rem;">
                                    <?php echo date('M j, g:i a', strtotime($activity['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($recentUsers) && !empty($recentUsers)): ?>
        <!-- Recent Users -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3 class="card-title">Recent Users</h3>
                <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="user-avatar"
                                            style="width: 32px; height: 32px; font-size: 0.75rem; background: <?php echo htmlspecialchars($u['avatar_color']); ?>">
                                            <?php echo getUserInitials($u['name']); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 500;"><?php echo htmlspecialchars($u['name']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--color-text-light);">
                                                @<?php echo htmlspecialchars($u['username']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-info' : 'badge-success'; ?>">
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!$u['email_verified_at']): ?>
                                        <span class="badge badge-warning">Unverified</span>
                                    <?php elseif (!$u['is_active']): ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--color-text-light); font-size: 0.875rem;">
                                    <?php echo date('M j, Y', strtotime($u['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>