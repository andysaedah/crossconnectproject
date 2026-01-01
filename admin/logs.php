<?php
/**
 * CrossConnect MY - Admin Activity Logs
 */

$currentPage = 'logs';
$pageTitle = 'Activity Logs';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

requireAdmin();

// Helper function to format action names
function formatActionName($action)
{
    // Convert underscore to spaces and capitalize each word
    return ucwords(str_replace('_', ' ', $action));
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$filter = $_GET['action'] ?? '';

$pdo = getDbConnection();
try {
    // Get total
    if ($filter) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE action = ?");
        $stmt->execute([$filter]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs");
    }
    $total = $stmt->fetchColumn();
    $totalPages = ceil($total / $perPage);

    // Get logs
    if ($filter) {
        $stmt = $pdo->prepare("
            SELECT al.*, u.name as user_name, u.email as user_email, u.avatar_color
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.action = ?
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$filter, $perPage, $offset]);
    } else {
        $stmt = $pdo->prepare("
            SELECT al.*, u.name as user_name, u.email as user_email, u.avatar_color
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$perPage, $offset]);
    }
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get action types for filter
    $actionTypes = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    error_log("Admin logs error: " . $e->getMessage());
    $logs = [];
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2>Activity Logs</h2>
        <p><?php echo number_format($total); ?> total entries</p>
    </div>

    <form method="GET" class="page-header-filters">
        <select name="action" class="form-select" id="statusFilter" onchange="this.form.submit()">
            <option value="">All Actions</option>
            <?php foreach ($actionTypes as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter === $type ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(formatActionName($type)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($filter): ?>
            <a href="<?php echo url('admin/logs.php'); ?>" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="dashboard-card">
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th class="hide-mobile">Description</th>
                        <th class="hide-mobile">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--color-text-light);">
                                No activity logs found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="white-space: nowrap; font-size: 0.875rem;">
                                    <div><?php echo date('M j, Y', strtotime($log['created_at'])); ?></div>
                                    <div style="color: var(--color-text-light);">
                                        <?php echo date('g:i:s A', strtotime($log['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($log['user_name']): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div class="user-avatar"
                                                style="width: 28px; height: 28px; font-size: 0.7rem; background: <?php echo htmlspecialchars($log['avatar_color'] ?? '#0891b2'); ?>">
                                                <?php echo getUserInitials($log['user_name']); ?>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.875rem;"><?php echo htmlspecialchars($log['user_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--color-text-light); font-size: 0.875rem;">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span
                                        class="badge badge-info"><?php echo htmlspecialchars(formatActionName($log['action'])); ?></span>
                                </td>
                                <td class="hide-mobile" style="max-width: 300px;">
                                    <div style="font-size: 0.875rem; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($log['description'] ?? '-'); ?>
                                    </div>
                                </td>
                                <td class="hide-mobile" style="font-size: 0.8rem; color: var(--color-text-light);">
                                    <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo $filter ? '&action=' . urlencode($filter) : ''; ?>"
                class="btn btn-secondary btn-sm">← Previous</a>
        <?php endif; ?>

        <span style="padding: 8px 12px; color: var(--color-text-light);">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
        </span>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo $filter ? '&action=' . urlencode($filter) : ''; ?>"
                class="btn btn-secondary btn-sm">Next →</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>