<?php
/**
 * CrossConnect MY - Admin Email Logs
 * View email delivery history and status
 */

$currentPage = 'email-logs';
$pageTitle = 'Email Logs';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

requireAdmin();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Filters
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$filterProvider = isset($_GET['provider']) ? trim($_GET['provider']) : '';
$filterSearch = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = [];
$params = [];

if ($filterStatus) {
    $where[] = "status = ?";
    $params[] = $filterStatus;
}

if ($filterProvider) {
    $where[] = "provider = ?";
    $params[] = $filterProvider;
}

if ($filterSearch) {
    $where[] = "(recipient LIKE ? OR subject LIKE ?)";
    $params[] = "%$filterSearch%";
    $params[] = "%$filterSearch%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $pdo = getDbConnection();

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM email_logs $whereClause");
    $countStmt->execute($params);
    $totalLogs = $countStmt->fetchColumn();
    $totalPages = ceil($totalLogs / $perPage);

    // Get logs
    $stmt = $pdo->prepare("
        SELECT * FROM email_logs 
        $whereClause 
        ORDER BY created_at DESC 
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get stats
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(status = 'sent') as sent,
            SUM(status = 'delivered') as delivered,
            SUM(status = 'opened') as opened,
            SUM(status = 'bounced') as bounced,
            SUM(status = 'failed') as failed
        FROM email_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $logs = [];
    $totalLogs = 0;
    $totalPages = 0;
    $stats = ['total' => 0, 'sent' => 0, 'delivered' => 0, 'opened' => 0, 'bounced' => 0, 'failed' => 0];
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2>Email Logs</h2>
        <p>Track email delivery status and history</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['total'] ?? 0); ?></div>
        <div class="stat-label">Total (30 days)</div>
    </div>
    <div class="stat-card delivered">
        <div class="stat-value"><?php echo number_format($stats['delivered'] ?? 0); ?></div>
        <div class="stat-label">Delivered</div>
    </div>
    <div class="stat-card opened">
        <div class="stat-value"><?php echo number_format($stats['opened'] ?? 0); ?></div>
        <div class="stat-label">Opened</div>
    </div>
    <div class="stat-card bounced">
        <div class="stat-value"><?php echo number_format($stats['bounced'] ?? 0); ?></div>
        <div class="stat-label">Bounced</div>
    </div>
</div>

<!-- Filters -->
<div class="filters-bar">
    <form method="GET" class="filters-form">
        <div class="filter-group">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="queued" <?php echo $filterStatus === 'queued' ? 'selected' : ''; ?>>Queued</option>
                <option value="sent" <?php echo $filterStatus === 'sent' ? 'selected' : ''; ?>>Sent</option>
                <option value="delivered" <?php echo $filterStatus === 'delivered' ? 'selected' : ''; ?>>Delivered
                </option>
                <option value="opened" <?php echo $filterStatus === 'opened' ? 'selected' : ''; ?>>Opened</option>
                <option value="clicked" <?php echo $filterStatus === 'clicked' ? 'selected' : ''; ?>>Clicked</option>
                <option value="bounced" <?php echo $filterStatus === 'bounced' ? 'selected' : ''; ?>>Bounced</option>
                <option value="failed" <?php echo $filterStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
                <option value="spam" <?php echo $filterStatus === 'spam' ? 'selected' : ''; ?>>Spam</option>
            </select>
        </div>
        <div class="filter-group">
            <select name="provider" class="form-select">
                <option value="">All Providers</option>
                <option value="smtp2go" <?php echo $filterProvider === 'smtp2go' ? 'selected' : ''; ?>>SMTP2GO</option>
                <option value="brevo" <?php echo $filterProvider === 'brevo' ? 'selected' : ''; ?>>Brevo</option>
                <option value="phpmail" <?php echo $filterProvider === 'phpmail' ? 'selected' : ''; ?>>PHP Mail</option>
            </select>
        </div>
        <div class="filter-group search-group">
            <input type="text" name="search" class="form-input" placeholder="Search recipient or subject..."
                value="<?php echo htmlspecialchars($filterSearch); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if ($filterStatus || $filterProvider || $filterSearch): ?>
            <a href="<?php echo url('admin/email-logs.php'); ?>" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Logs Table -->
<div class="table-container">
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            <h3>No email logs found</h3>
            <p>Email logs will appear here when emails are sent.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Recipient</th>
                        <th class="hide-mobile">Subject</th>
                        <th class="hide-mobile">Provider</th>
                        <th class="hide-mobile">Opens</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <span class="status-badge status-<?php echo $log['status']; ?>">
                                    <?php echo ucfirst($log['status']); ?>
                                </span>
                            </td>
                            <td class="email-cell">
                                <?php echo htmlspecialchars($log['recipient']); ?>
                                <div class="show-mobile-only"
                                    style="font-size: 0.75rem; color: var(--color-text-light); margin-top: 2px;">
                                    <?php echo htmlspecialchars(substr($log['subject'] ?? '-', 0, 30)); ?>
                                </div>
                            </td>
                            <td class="subject-cell hide-mobile"><?php echo htmlspecialchars($log['subject'] ?? '-'); ?></td>
                            <td class="hide-mobile">
                                <span class="provider-badge <?php echo $log['provider']; ?>">
                                    <?php echo strtoupper($log['provider']); ?>
                                </span>
                            </td>
                            <td class="opens-cell hide-mobile">
                                <?php if ($log['opened_count'] > 0): ?>
                                    <span class="opens-count"><?php echo $log['opened_count']; ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="date-cell">
                                <?php echo date('M j', strtotime($log['created_at'])); ?>
                                <div class="show-mobile-only" style="font-size: 0.7rem; color: var(--color-text-light);">
                                    <?php echo date('H:i', strtotime($log['created_at'])); ?>
                                </div>
                                <span class="hide-mobile"><?php echo date(' H:i', strtotime($log['created_at'])); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($filterStatus); ?>&provider=<?php echo urlencode($filterProvider); ?>&search=<?php echo urlencode($filterSearch); ?>"
                        class="pagination-btn">
                        ← Previous
                    </a>
                <?php endif; ?>

                <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($filterStatus); ?>&provider=<?php echo urlencode($filterProvider); ?>&search=<?php echo urlencode($filterSearch); ?>"
                        class="pagination-btn">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Webhook Info -->
<div class="webhook-info-card">
    <h3>📡 Webhook Configuration</h3>
    <p>Configure these URLs in your email provider dashboard to receive real-time delivery updates:</p>
    <div class="webhook-urls">
        <div class="webhook-url">
            <span class="provider-badge smtp2go">SMTP2GO</span>
            <code><?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . url('api/webhook/smtp2go.php'); ?></code>
        </div>
        <div class="webhook-url">
            <span class="provider-badge brevo">Brevo</span>
            <code><?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . url('api/webhook/brevo.php'); ?></code>
        </div>
    </div>
</div>

<style>
    /* Stats Grid - Mobile First: 2 per row */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    @media (min-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 16px 12px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border-left: 4px solid var(--color-primary);
    }

    @media (min-width: 768px) {
        .stat-card {
            padding: 20px;
        }
    }

    .stat-card.delivered {
        border-left-color: #10b981;
    }

    .stat-card.opened {
        border-left-color: #3b82f6;
    }

    .stat-card.bounced {
        border-left-color: #ef4444;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--color-text);
    }

    @media (min-width: 768px) {
        .stat-value {
            font-size: 1.75rem;
        }
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--color-text-light);
        margin-top: 4px;
    }

    @media (min-width: 768px) {
        .stat-label {
            font-size: 0.85rem;
        }
    }

    /* Filters Bar - Mobile First */
    .filters-bar {
        background: white;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    @media (min-width: 768px) {
        .filters-bar {
            padding: 16px;
            margin-bottom: 24px;
        }
    }

    /* Filters Form - Mobile: Full width stacked */
    .filters-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .filter-group {
        width: 100%;
    }

    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 12px;
        font-size: 0.9rem;
    }

    .filters-form .btn {
        width: 100%;
        padding: 12px;
    }

    /* Desktop: Row layout */
    @media (min-width: 768px) {
        .filters-form {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }

        .filter-group {
            flex-shrink: 0;
            width: auto;
        }

        .filter-group select,
        .filter-group input {
            width: auto;
            padding: 10px 14px;
        }

        .search-group {
            flex: 1;
            min-width: 200px;
        }

        .search-group input {
            width: 100%;
        }

        .filters-form .btn {
            width: auto;
            padding: 10px 20px;
        }
    }

    .table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        background: var(--color-bg);
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--color-text-light);
        border-bottom: 1px solid var(--color-border);
    }

    .data-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--color-border);
        font-size: 0.9rem;
    }

    .data-table tr:hover {
        background: var(--color-bg);
    }

    .email-cell {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .subject-cell {
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .date-cell {
        white-space: nowrap;
        color: var(--color-text-light);
        font-size: 0.85rem;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-queued {
        background: #f3f4f6;
        color: #6b7280;
    }

    .status-sent {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .status-delivered {
        background: #dcfce7;
        color: #15803d;
    }

    .status-opened {
        background: #e0e7ff;
        color: #4338ca;
    }

    .status-clicked {
        background: #fae8ff;
        color: #a21caf;
    }

    .status-bounced {
        background: #fee2e2;
        color: #dc2626;
    }

    .status-failed {
        background: #fee2e2;
        color: #dc2626;
    }

    .status-spam {
        background: #fef3c7;
        color: #b45309;
    }

    .provider-badge {
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
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

    .opens-count {
        background: #e0e7ff;
        color: #4338ca;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .empty-state {
        padding: 60px 24px;
        text-align: center;
    }

    .empty-state svg {
        width: 48px;
        height: 48px;
        color: var(--color-text-light);
        margin-bottom: 16px;
    }

    .empty-state h3 {
        margin: 0 0 8px;
        color: var(--color-text);
    }

    .empty-state p {
        margin: 0;
        color: var(--color-text-light);
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
        padding: 16px;
        border-top: 1px solid var(--color-border);
    }

    .pagination-btn {
        padding: 8px 16px;
        background: var(--color-bg);
        border-radius: 6px;
        text-decoration: none;
        color: var(--color-text);
        font-size: 0.9rem;
        transition: background 0.2s;
    }

    .pagination-btn:hover {
        background: var(--color-border);
    }

    .pagination-info {
        color: var(--color-text-light);
        font-size: 0.9rem;
    }

    /* Webhook Card - Mobile First */
    .webhook-info-card {
        margin-top: 20px;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 10px;
        padding: 16px;
    }

    @media (min-width: 768px) {
        .webhook-info-card {
            margin-top: 24px;
            padding: 20px;
        }
    }

    .webhook-info-card h3 {
        margin: 0 0 8px;
        font-size: 0.9rem;
        color: #0c4a6e;
    }

    @media (min-width: 768px) {
        .webhook-info-card h3 {
            font-size: 1rem;
            margin-bottom: 12px;
        }
    }

    .webhook-info-card p {
        margin: 0 0 12px;
        color: #0369a1;
        font-size: 0.8rem;
    }

    @media (min-width: 768px) {
        .webhook-info-card p {
            font-size: 0.9rem;
            margin-bottom: 16px;
        }
    }

    .webhook-urls {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    @media (min-width: 768px) {
        .webhook-urls {
            gap: 10px;
        }
    }

    .webhook-url {
        display: flex;
        flex-direction: column;
        gap: 6px;
        background: white;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid var(--color-border);
    }

    @media (min-width: 768px) {
        .webhook-url {
            flex-direction: row;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
        }
    }

    .webhook-url code {
        font-size: 0.75rem;
        color: var(--color-text);
        word-break: break-all;
    }

    @media (min-width: 768px) {
        .webhook-url code {
            font-size: 0.85rem;
        }
    }
</style>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>