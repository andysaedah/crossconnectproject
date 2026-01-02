<?php
/**
 * CrossConnect MY - Admin Churches Management
 */

$currentPage = 'churches';
$pageTitle = 'Churches';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

requireAdmin();

$pdo = getDbConnection();

// Get states and denominations for form
$states = [];
$denominations = [];
try {
    $states = $pdo->query("SELECT id, name FROM states ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $denominations = $pdo->query("SELECT id, name FROM denominations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Admin churches error: " . $e->getMessage());
}
?>

<?php
// Get churches with needs_amendment status
$amendmentChurches = [];
try {
    $amendmentChurches = $pdo->query("
        SELECT id, name, amendment_notes, amendment_date, amendment_reporter_email 
        FROM churches 
        WHERE status = 'needs_amendment' 
        ORDER BY amendment_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Amendment churches error: " . $e->getMessage());
}
?>

<?php if (!empty($amendmentChurches)): ?>
    <!-- Amendment Requests Alert Card -->
    <div class="amendment-alert-card">
        <div class="amendment-alert-header">
            <div class="amendment-alert-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div class="amendment-alert-title">
                <h3>Amendment Requests</h3>
                <p><?php echo count($amendmentChurches); ?> church<?php echo count($amendmentChurches) > 1 ? 'es' : ''; ?>
                    need<?php echo count($amendmentChurches) == 1 ? 's' : ''; ?> attention</p>
            </div>
        </div>
        <div class="amendment-list">
            <?php foreach ($amendmentChurches as $ac): ?>
                <div class="amendment-item">
                    <div class="amendment-info">
                        <strong><?php echo htmlspecialchars($ac['name']); ?></strong>
                        <p class="amendment-notes">
                            <?php echo htmlspecialchars(substr($ac['amendment_notes'], 0, 100)); ?>
                            <?php echo strlen($ac['amendment_notes']) > 100 ? '...' : ''; ?>
                        </p>
                        <?php if ($ac['amendment_date']): ?>
                            <span class="amendment-date">Reported:
                                <?php echo date('M j, Y', strtotime($ac['amendment_date'])); ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="editChurch(<?php echo $ac['id']; ?>)">Review</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
        .amendment-alert-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .amendment-alert-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            background: rgba(245, 158, 11, 0.1);
            border-bottom: 1px solid rgba(245, 158, 11, 0.3);
        }

        .amendment-alert-icon {
            width: 40px;
            height: 40px;
            background: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .amendment-alert-icon svg {
            width: 20px;
            height: 20px;
            color: white;
        }

        .amendment-alert-title h3 {
            margin: 0;
            font-size: 1rem;
            color: #92400e;
        }

        .amendment-alert-title p {
            margin: 2px 0 0;
            font-size: 0.8rem;
            color: #a16207;
        }

        .amendment-list {
            padding: 12px;
        }

        .amendment-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 16px;
            background: white;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .amendment-item:last-child {
            margin-bottom: 0;
        }

        .amendment-info strong {
            display: block;
            color: #1f2937;
            font-size: 0.9rem;
        }

        .amendment-notes {
            margin: 4px 0;
            font-size: 0.8rem;
            color: #6b7280;
        }

        .amendment-date {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
            border: none;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .amendment-modal-alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .amendment-modal-alert-icon {
            width: 32px;
            height: 32px;
            background: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .amendment-modal-alert-icon svg {
            width: 16px;
            height: 16px;
            color: white;
        }

        .amendment-modal-alert-content {
            flex: 1;
        }

        .amendment-modal-alert-content strong {
            display: block;
            color: #92400e;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .amendment-modal-alert-content p {
            margin: 0;
            font-size: 0.85rem;
            color: #a16207;
            line-height: 1.4;
        }

        .amendment-date-text {
            display: block;
            margin-top: 6px;
            font-size: 0.75rem;
            color: #92400e;
        }
    </style>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2>Churches</h2>
        <p id="totalCount">Loading...</p>
    </div>
    <div class="page-header-filters">
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="M21 21l-4.35-4.35"></path>
            </svg>
            <input type="text" id="searchInput" placeholder="Search churches..." autocomplete="off">
        </div>
        <select id="statusFilter" class="form-select">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="inactive">Inactive</option>
            <option value="needs_amendment">To Amend</option>
        </select>
    </div>
</div>

<!-- Table Header with Add Button -->
<div class="table-header">
    <span class="table-header-info" id="showingInfo"></span>
    <a href="<?php echo url('admin/add-church.php'); ?>" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Add Church</span>
    </a>
</div>

<div class="dashboard-card">
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="dataTable">
                <thead>
                    <tr>
                        <th>Church</th>
                        <th class="hide-mobile">Location</th>
                        <th class="hide-mobile">Created By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="pagination" id="pagination"></div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()"></div>
<div class="modal" id="churchModal">
    <div class="modal-header">
        <h3 id="modalTitle">Add Church</h3>
        <button class="modal-close" onclick="closeModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <form id="churchForm" onsubmit="saveChurch(event)" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="id" id="churchId">

        <div class="modal-body">
            <!-- Amendment Notes Alert (shown only when church needs amendment) -->
            <div class="amendment-modal-alert" id="amendmentAlert" style="display: none;">
                <div class="amendment-modal-alert-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="amendment-modal-alert-content">
                    <strong>Amendment Requested</strong>
                    <p id="amendmentNotesDisplay"></p>
                    <span id="amendmentDateDisplay" class="amendment-date-text"></span>
                </div>
                <button type="button" class="btn btn-sm btn-warning" onclick="clearAmendment()">
                    Mark as Resolved
                </button>
            </div>

            <div class="form-group">
                <label class="form-label">Church Name *</label>
                <input type="text" name="name" id="churchName" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">State *</label>
                    <select name="state_id" id="churchState" class="form-select" required>
                        <option value="">Select State</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Denomination</label>
                    <select name="denomination_id" id="churchDenomination" class="form-select">
                        <option value="">Select Denomination</option>
                        <?php foreach ($denominations as $denom): ?>
                            <option value="<?php echo $denom['id']; ?>"><?php echo htmlspecialchars($denom['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" id="churchCity" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" id="churchPhone" class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" id="churchAddress" class="form-textarea" rows="2"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="churchEmail" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" id="churchWebsite" class="form-input" placeholder="https://">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Facebook</label>
                    <input type="text" name="facebook" id="churchFacebook" class="form-input" placeholder="mychurch">
                    <p class="form-hint">Just the username, not the full URL</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram</label>
                    <input type="text" name="instagram" id="churchInstagram" class="form-input" placeholder="mychurch">
                    <p class="form-hint">Just the username without @</p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">YouTube</label>
                <input type="text" name="youtube" id="churchYoutube" class="form-input" placeholder="mychurch">
                <p class="form-hint">Channel handle without @</p>
            </div>

            <!-- Twitter hidden for now
            <div class="form-group">
                <label class="form-label">Twitter / X</label>
                <input type="text" name="twitter" id="churchTwitter" class="form-input" placeholder="@mychurch">
            </div>
            -->

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="churchDescription" class="form-textarea" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Service Times</label>
                <textarea name="service_times" id="churchServiceTimes" class="form-textarea" rows="2"
                    placeholder="e.g., Sunday 9:00 AM, 11:00 AM"></textarea>
            </div>

            <!-- Service Languages Checkboxes -->
            <div class="form-group">
                <label class="form-label">Service Languages</label>
                <p class="form-hint" style="margin-top: 0; margin-bottom: 8px;">Select the languages used in services
                </p>
                <div class="checkbox-grid">
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="bm" id="langBm">
                        <span class="checkbox-text">BM Service</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="en" id="langEn">
                        <span class="checkbox-text">English Service</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="chinese" id="langChinese">
                        <span class="checkbox-text">Chinese Service</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="tamil" id="langTamil">
                        <span class="checkbox-text">Tamil Service</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="other" id="langOther">
                        <span class="checkbox-text">Other Languages</span>
                    </label>
                </div>
            </div>

            <!-- Image Upload -->
            <div class="form-group">
                <label class="form-label">Church Photo</label>
                <div class="image-upload-edit" id="imageUploadContainer">
                    <div class="current-image-preview" id="currentImagePreview" style="display: none;">
                        <img id="currentImageImg" src="" alt="Current image">
                        <div class="image-overlay">
                            <span>Click to change</span>
                        </div>
                    </div>
                    <div class="upload-placeholder" id="uploadPlaceholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <span>Click to upload</span>
                    </div>
                    <input type="file" name="photo" id="churchPhoto" accept="image/*" hidden>
                </div>
                <p class="form-hint">Leave empty to keep current image</p>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveBtn">Save Church</button>
        </div>
    </form>
</div>

<style>
    /* Page Header - Mobile First */
    .page-header {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        margin-bottom: 16px;
        gap: 12px;
    }

    .page-header-info h2 {
        margin: 0 0 2px;
        font-size: 1.25rem;
    }

    .page-header-info p {
        margin: 0;
        color: var(--color-text-light);
        font-size: 0.875rem;
    }

    .page-header-filters {
        display: flex;
        gap: 8px;
        align-items: center;
        width: 100%;
    }

    .search-box {
        position: relative;
        display: flex;
        align-items: center;
        flex: 2;
        min-width: 0;
    }

    .search-box svg {
        position: absolute;
        left: 12px;
        width: 18px;
        height: 18px;
        color: var(--color-text-light);
        pointer-events: none;
    }

    .search-box input {
        padding: 10px 14px 10px 40px;
        border: 1px solid var(--color-border);
        border-radius: 8px;
        font-size: 0.9rem;
        width: 100%;
        transition: all 0.2s;
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--color-primary-bg);
    }

    #statusFilter {
        flex-shrink: 0;
        width: 110px;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .table-header-info {
        font-size: 0.875rem;
        color: var(--color-text-light);
    }

    .table-header .btn span {
        display: none;
    }

    .table-responsive {
        overflow-x: auto;
    }

    /* Desktop enhancements */
    @media (min-width: 768px) {
        .page-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-info h2 {
            font-size: 1.5rem;
        }

        .page-header-filters {
            width: auto;
        }

        .search-box {
            flex: none;
        }

        .search-box input {
            width: 220px;
        }

        .table-header .btn span {
            display: inline;
        }
    }

    /* Modal styles */
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
        max-width: 600px;
        max-height: 90vh;
        overflow: hidden;
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
        max-height: calc(90vh - 160px);
        overflow-y: auto;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--color-border);
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    /* Checkbox Grid */
    .checkbox-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        background: var(--color-bg);
        border: 1px solid var(--color-border);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.875rem;
    }

    .checkbox-label:hover {
        border-color: var(--color-primary);
        background: var(--color-primary-bg);
    }

    .checkbox-label input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: var(--color-primary);
        cursor: pointer;
    }

    .checkbox-label input[type="checkbox"]:checked+.checkbox-text {
        color: var(--color-primary);
        font-weight: 500;
    }

    /* Image Upload Edit */
    .image-upload-edit {
        border: 2px dashed var(--color-border);
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s;
        background: var(--color-bg);
    }

    .image-upload-edit:hover {
        border-color: var(--color-primary);
    }

    .current-image-preview {
        position: relative;
        width: 100%;
        height: 150px;
    }

    .current-image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .current-image-preview .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .current-image-preview:hover .image-overlay {
        opacity: 1;
    }

    .current-image-preview .image-overlay span {
        color: white;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .upload-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 32px;
        color: var(--color-text-light);
    }

    .upload-placeholder svg {
        width: 40px;
        height: 40px;
        margin-bottom: 8px;
    }

    .upload-placeholder span {
        font-size: 0.875rem;
    }
</style>

<script>
    // Debug logging (fallback if outputJsConfig not called)
    if (typeof debugLog === 'undefined') {
        window.debugLog = function (...args) {
            if (window.AppConfig && window.AppConfig.debug) {
                console.log(...args);
            }
        };
    }

    let currentPage = 1;
    let searchTimeout = null;

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        loadData();

        // AJAX search with debounce
        document.getElementById('searchInput').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                loadData();
            }, 300);
        });

        document.getElementById('statusFilter').addEventListener('change', () => {
            currentPage = 1;
            loadData();
        });
    });

    async function loadData() {
        const query = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;

        const params = new URLSearchParams({
            type: 'churches',
            q: query,
            status: status,
            page: currentPage
        });

        try {
            const response = await fetch(basePath + 'api/admin/search.php?' + params);
            const result = await response.json();

            if (result.success) {
                renderTable(result.data.data);
                renderPagination(result.data);
                document.getElementById('totalCount').textContent =
                    `${result.data.total.toLocaleString()} churches`;
            }
        } catch (error) {
            console.error('Load error:', error);
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('tableBody');

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--color-text-light);">No churches found</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(church => `
            <tr>
                <td>
                    <div style="font-weight: 500;">${escapeHtml(church.name)}</div>
                    <div style="font-size: 0.8rem; color: var(--color-text-light);">
                        ${escapeHtml(church.denomination_name || 'No denomination')}
                    </div>
                </td>
                <td class="hide-mobile">
                    <div>${escapeHtml(church.city || '-')}</div>
                    <div style="font-size: 0.8rem; color: var(--color-text-light);">
                        ${escapeHtml(church.state_name || '')}
                    </div>
                </td>
                <td class="hide-mobile" style="font-size: 0.875rem;">
                    ${escapeHtml(church.creator_name || 'System')}
                </td>
                <td>
                    <span class="badge ${church.status === 'active' ? 'badge-success' :
                (church.status === 'pending' ? 'badge-warning' :
                    (church.status === 'needs_amendment' ? 'badge-warning' : 'badge-danger'))}">
                        ${getStatusLabel(church.status)}
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        <button class="action-btn edit" onclick="editChurch(${church.id})" title="Edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                        <a href="${basePath}church.php?slug=${church.slug}" class="action-btn" title="View" target="_blank">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </a>
                        ${church.status === 'pending' ? `
                            <button class="action-btn" onclick="updateStatus(${church.id}, 'active')" title="Approve" style="color: #10b981;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                            </button>
                        ` : ''}
                        ${church.status !== 'inactive' ? `
                            <button class="action-btn delete" onclick="updateStatus(${church.id}, 'inactive')" title="Deactivate">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                </svg>
                            </button>
                        ` : `
                            <button class="action-btn" onclick="updateStatus(${church.id}, 'active')" title="Activate" style="color: #10b981;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                            </button>
                        `}
                        <button class="action-btn delete" onclick="deleteChurch(${church.id}, '${church.name.replace(/'/g, "\\'")}')" title="Delete Permanently" style="color: #dc2626;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(data) {
        const container = document.getElementById('pagination');
        if (data.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = `
            ${data.page > 1 ? `<button class="btn btn-secondary btn-sm" onclick="goToPage(${data.page - 1})">← Prev</button>` : ''}
            <span style="padding: 8px 12px; color: var(--color-text-light);">
                Page ${data.page} of ${data.total_pages}
            </span>
            ${data.has_more ? `<button class="btn btn-secondary btn-sm" onclick="goToPage(${data.page + 1})">Next →</button>` : ''}
        `;
    }

    function goToPage(page) {
        currentPage = page;
        loadData();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getStatusLabel(status) {
        const labels = {
            'active': 'Active',
            'inactive': 'Inactive',
            'pending': 'Pending',
            'needs_amendment': 'To Amend'
        };
        return labels[status] || status.charAt(0).toUpperCase() + status.slice(1);
    }

    function openModal() {
        document.getElementById('modalOverlay').classList.add('show');
        document.getElementById('churchModal').classList.add('show');
        document.getElementById('modalTitle').textContent = 'Add Church';
        document.getElementById('churchForm').reset();
        document.getElementById('churchId').value = '';
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('show');
        document.getElementById('churchModal').classList.remove('show');
    }

    async function saveChurch(e) {
        e.preventDefault();
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const formData = new FormData(e.target);
            const response = await fetch(basePath + 'api/user/churches.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message || 'Church saved!', 'success');
                closeModal();
                loadData();
            } else {
                showToast(data.error || 'Failed to save', 'error');
            }
        } catch (error) {
            showToast('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save Church';
        }
    }

    async function updateStatus(id, status) {
        const action = status === 'active' ? 'approve' : 'deactivate';
        if (!confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} this church?`)) return;

        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            formData.append('type', 'church');
            formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');

            const response = await fetch(basePath + 'api/admin/content.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message || 'Status updated', 'success');
                loadData();
            } else {
                showToast(data.error || 'Failed', 'error');
            }
        } catch (error) {
            showToast('An error occurred', 'error');
        }
    }

    async function editChurch(id) {
        try {
            const response = await fetch(basePath + 'api/admin/search.php?type=churches&id=' + id);
            const result = await response.json();

            if (result.success && result.data.data.length > 0) {
                const church = result.data.data[0];

                document.getElementById('modalTitle').textContent = 'Edit Church';
                document.getElementById('churchId').value = church.id;
                document.getElementById('churchName').value = church.name || '';
                document.getElementById('churchDenomination').value = church.denomination_id || '';
                document.getElementById('churchState').value = church.state_id || '';
                document.getElementById('churchCity').value = church.city || '';
                document.getElementById('churchAddress').value = church.address || '';
                document.getElementById('churchPhone').value = church.phone || '';
                document.getElementById('churchEmail').value = church.email || '';
                document.getElementById('churchWebsite').value = church.website || '';
                document.getElementById('churchFacebook').value = church.facebook || '';
                document.getElementById('churchInstagram').value = church.instagram || '';
                document.getElementById('churchYoutube').value = church.youtube || '';
                // Note: Twitter field is currently hidden in the form
                document.getElementById('churchDescription').value = church.description || '';
                document.getElementById('churchServiceTimes').value = church.service_times || '';

                // Handle amendment notes display
                const amendmentAlert = document.getElementById('amendmentAlert');
                if (church.status === 'needs_amendment' && church.amendment_notes) {
                    document.getElementById('amendmentNotesDisplay').textContent = church.amendment_notes;
                    document.getElementById('amendmentDateDisplay').textContent = church.amendment_date
                        ? 'Reported: ' + new Date(church.amendment_date).toLocaleDateString()
                        : '';
                    amendmentAlert.style.display = 'flex';
                } else {
                    amendmentAlert.style.display = 'none';
                }

                // Handle service languages checkboxes
                const languages = (church.service_languages || '').split(',').filter(l => l.trim());
                document.getElementById('langBm').checked = languages.includes('bm');
                document.getElementById('langEn').checked = languages.includes('en');
                document.getElementById('langChinese').checked = languages.includes('chinese');
                document.getElementById('langTamil').checked = languages.includes('tamil');
                document.getElementById('langOther').checked = languages.includes('other');

                // Handle image preview
                const imagePreview = document.getElementById('currentImagePreview');
                const uploadPlaceholder = document.getElementById('uploadPlaceholder');
                const currentImageImg = document.getElementById('currentImageImg');

                if (church.image_url) {
                    currentImageImg.src = church.image_url;
                    imagePreview.style.display = 'block';
                    uploadPlaceholder.style.display = 'none';
                } else {
                    imagePreview.style.display = 'none';
                    uploadPlaceholder.style.display = 'flex';
                }

                // Reset file input
                document.getElementById('churchPhoto').value = '';

                document.getElementById('modalOverlay').classList.add('show');
                document.getElementById('churchModal').classList.add('show');
            } else {
                showToast('Failed to load church data', 'error');
            }
        } catch (error) {
            console.error('Edit error:', error);
            showToast('Failed to load church data', 'error');
        }
    }

    async function clearAmendment() {
        const id = document.getElementById('churchId').value;
        if (!id || !confirm('Mark this amendment as resolved? The church status will be set to active.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'clear_amendment');
            formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');

            const response = await fetch(basePath + 'api/admin/churches.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Amendment marked as resolved', 'success');
                document.getElementById('amendmentAlert').style.display = 'none';
                closeModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || 'Failed to clear amendment', 'error');
            }
        } catch (error) {
            showToast('An error occurred', 'error');
        }
    }

    // Image upload click handler
    document.getElementById('imageUploadContainer').addEventListener('click', function () {
        document.getElementById('churchPhoto').click();
    });

    // Image preview on file select
    document.getElementById('churchPhoto').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('currentImageImg').src = e.target.result;
                document.getElementById('currentImagePreview').style.display = 'block';
                document.getElementById('uploadPlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    // Delete church permanently
    async function deleteChurch(id, name) {
        debugLog('=== DELETE CHURCH DEBUG ===');
        debugLog('Church ID:', id);
        debugLog('Church Name:', name);

        if (!confirm(`Are you sure you want to permanently delete "${name}"?\n\nThis action cannot be undone.`)) {
            debugLog('User cancelled delete');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');
            formData.append('id', id);
            formData.append('action', 'delete');

            debugLog('Sending to:', basePath + 'api/admin/churches.php');

            const response = await fetch(basePath + 'api/admin/churches.php', {
                method: 'POST',
                body: formData
            });

            const responseText = await response.text();
            console.log('=== DELETE CHURCH RESPONSE ===');
            console.log('Status:', response.status);
            console.log('Response:', responseText);
            debugLog('Raw response:', responseText);
            debugLog('Response status:', response.status);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError, 'Response:', responseText);
                debugLog('JSON parse error:', parseError);
                debugLog('Response was:', responseText);
                showToast('Server error: ' + responseText.substring(0, 100), 'error');
                return;
            }

            if (data.success) {
                showToast('Church deleted successfully', 'success');
                loadData();
            } else {
                console.error('API error:', data.error);
                debugLog('API returned error:', data.error);
                showToast(data.error || 'Failed to delete church', 'error');
            }
        } catch (error) {
            console.error('Delete fetch error:', error);
            debugLog('Delete error:', error);
            showToast('Failed to delete church', 'error');
        }
    }

    // Close modal on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>