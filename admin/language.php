<?php
/**
 * CrossConnect MY - Admin Language Manager
 * Edit EN/BM translations from admin panel
 */

$currentPage = 'language';
$pageTitle = 'Language Manager';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

requireAdmin();

// Load both language files
$enStrings = [];
$bmStrings = [];

$enFile = __DIR__ . '/../config/lang/en.php';
$bmFile = __DIR__ . '/../config/lang/bm.php';

if (file_exists($enFile)) {
    $enStrings = require $enFile;
}
if (file_exists($bmFile)) {
    $bmStrings = require $bmFile;
}

// Merge all keys
$allKeys = array_unique(array_merge(array_keys($enStrings), array_keys($bmStrings)));
sort($allKeys);

// Group keys by category (based on prefix before underscore)
$grouped = [];
foreach ($allKeys as $key) {
    $parts = explode('_', $key);
    $category = $parts[0] ?? 'other';
    if (!isset($grouped[$category])) {
        $grouped[$category] = [];
    }
    $grouped[$category][] = $key;
}
ksort($grouped);
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2>Language Manager</h2>
        <p>Manage EN/BM translations for the website</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="saveAllChanges()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                style="width: 18px; height: 18px;">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Save All Changes
        </button>
    </div>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-number"><?php echo count($allKeys); ?></div>
        <div class="stat-label">Total Keys</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo count($enStrings); ?></div>
        <div class="stat-label">English</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo count($bmStrings); ?></div>
        <div class="stat-label">Bahasa Malaysia</div>
    </div>
    <div class="stat-card">
        <?php
        $missing = 0;
        foreach ($allKeys as $key) {
            if (!isset($enStrings[$key]) || !isset($bmStrings[$key]))
                $missing++;
        }
        ?>
        <div class="stat-number <?php echo $missing > 0 ? 'text-warning' : ''; ?>"><?php echo $missing; ?></div>
        <div class="stat-label">Missing</div>
    </div>
</div>

<!-- Search -->
<div class="search-filter-row">
    <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="M21 21l-4.35-4.35"></path>
        </svg>
        <input type="text" id="searchInput" placeholder="Search keys or text..." autocomplete="off">
    </div>
    <select id="categoryFilter" class="form-select">
        <option value="">All Categories</option>
        <?php foreach (array_keys($grouped) as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo ucfirst($cat); ?></option>
        <?php endforeach; ?>
    </select>
    <select id="statusFilter" class="form-select">
        <option value="">All Status</option>
        <option value="complete">Complete</option>
        <option value="missing">Missing Translation</option>
    </select>
</div>

<!-- Add New Key -->
<div class="dashboard-card" style="margin-bottom: 20px;">
    <div class="card-header">
        <h3>Add New Key</h3>
    </div>
    <div class="card-body">
        <div class="add-key-form">
            <input type="text" id="newKey" class="form-input" placeholder="key_name (lowercase with underscores)">
            <input type="text" id="newEn" class="form-input" placeholder="English text">
            <input type="text" id="newBm" class="form-input" placeholder="Bahasa Malaysia text">
            <button class="btn btn-primary" onclick="addNewKey()">Add</button>
        </div>
    </div>
</div>

<!-- Language Strings Table -->
<div class="dashboard-card">
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="langTable">
                <thead>
                    <tr>
                        <th style="width: 25%;">Key</th>
                        <th style="width: 35%;">English (EN)</th>
                        <th style="width: 35%;">Bahasa Malaysia (BM)</th>
                        <th style="width: 5%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($grouped as $category => $keys): ?>
                        <tr class="category-header">
                            <td colspan="4">
                                <strong><?php echo ucfirst($category); ?></strong>
                                <span class="badge"><?php echo count($keys); ?> keys</span>
                            </td>
                        </tr>
                        <?php foreach ($keys as $key):
                            $en = $enStrings[$key] ?? '';
                            $bm = $bmStrings[$key] ?? '';
                            $isMissing = empty($en) || empty($bm);
                            ?>
                            <tr class="lang-row <?php echo $isMissing ? 'missing' : ''; ?>"
                                data-key="<?php echo htmlspecialchars($key); ?>"
                                data-category="<?php echo htmlspecialchars($category); ?>">
                                <td>
                                    <code class="key-code"><?php echo htmlspecialchars($key); ?></code>
                                    <?php if ($isMissing): ?>
                                        <span class="badge badge-warning" style="margin-left: 6px;">Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <textarea class="lang-input en-input" data-key="<?php echo htmlspecialchars($key); ?>"
                                        data-lang="en" rows="2"><?php echo htmlspecialchars($en); ?></textarea>
                                </td>
                                <td>
                                    <textarea class="lang-input bm-input" data-key="<?php echo htmlspecialchars($key); ?>"
                                        data-lang="bm" rows="2"><?php echo htmlspecialchars($bm); ?></textarea>
                                </td>
                                <td>
                                    <button class="action-btn delete"
                                        onclick="deleteKey('<?php echo htmlspecialchars($key); ?>')" title="Delete">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path
                                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                            </path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--color-primary);
    }

    .stat-number.text-warning {
        color: #f59e0b;
    }

    .stat-label {
        color: var(--color-text-light);
        font-size: 0.875rem;
        margin-top: 4px;
    }

    .search-filter-row {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .search-filter-row .search-box {
        flex: 1;
        min-width: 200px;
        position: relative;
    }

    .search-filter-row .search-box svg {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        color: var(--color-text-light);
        pointer-events: none;
    }

    .search-filter-row .search-box input {
        width: 100%;
        padding: 10px 14px 10px 40px;
        border: 1px solid var(--color-border);
        border-radius: 8px;
        font-size: 0.9rem;
    }

    .search-filter-row .search-box input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--color-primary-bg);
    }

    .search-filter-row .form-select {
        min-width: 150px;
    }

    .add-key-form {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .add-key-form .form-input {
        flex: 1;
        min-width: 150px;
    }

    .category-header {
        background: var(--color-bg) !important;
    }

    .category-header td {
        padding: 12px 16px !important;
    }

    .lang-row.missing {
        background: #fef3c7;
    }

    .key-code {
        font-family: monospace;
        background: var(--color-bg);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.8rem;
    }

    .lang-input {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid var(--color-border);
        border-radius: 6px;
        font-size: 0.875rem;
        resize: vertical;
        min-height: 60px;
        font-family: inherit;
    }

    .lang-input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--color-primary-bg);
    }

    .lang-input.changed {
        border-color: #10b981;
        background: #ecfdf5;
    }

    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }

        .add-key-form {
            flex-direction: column;
        }

        .add-key-form .form-input {
            width: 100%;
        }
    }
</style>

<script>
    let hasChanges = false;
    const changedKeys = new Set();

    // Mark inputs as changed
    document.querySelectorAll('.lang-input').forEach(input => {
        input.addEventListener('input', function () {
            this.classList.add('changed');
            changedKeys.add(this.dataset.key);
            hasChanges = true;
        });
    });

    // Search and filter
    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('categoryFilter').addEventListener('change', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);

    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const category = document.getElementById('categoryFilter').value;
        const status = document.getElementById('statusFilter').value;

        document.querySelectorAll('.lang-row').forEach(row => {
            const key = row.dataset.key.toLowerCase();
            const cat = row.dataset.category;
            const enText = row.querySelector('.en-input').value.toLowerCase();
            const bmText = row.querySelector('.bm-input').value.toLowerCase();
            const isMissing = row.classList.contains('missing');

            let show = true;

            // Search filter
            if (search && !key.includes(search) && !enText.includes(search) && !bmText.includes(search)) {
                show = false;
            }

            // Category filter
            if (category && cat !== category) {
                show = false;
            }

            // Status filter
            if (status === 'missing' && !isMissing) show = false;
            if (status === 'complete' && isMissing) show = false;

            row.style.display = show ? '' : 'none';
        });

        // Show/hide category headers
        document.querySelectorAll('.category-header').forEach(header => {
            const cat = header.nextElementSibling?.dataset?.category;
            if (category && cat !== category) {
                header.style.display = 'none';
            } else {
                header.style.display = '';
            }
        });
    }

    function addNewKey() {
        const key = document.getElementById('newKey').value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');
        const en = document.getElementById('newEn').value.trim();
        const bm = document.getElementById('newBm').value.trim();

        if (!key) {
            showToast('<?php _e('error_enter_key_name'); ?>', 'error');
            return;
        }

        // Check if key exists
        if (document.querySelector(`[data-key="${key}"]`)) {
            showToast('<?php _e('error_key_exists'); ?>', 'error');
            return;
        }

        // Add to table (will be saved with saveAllChanges)
        const tbody = document.getElementById('tableBody');
        const category = key.split('_')[0] || 'other';

        const row = document.createElement('tr');
        row.className = 'lang-row';
        row.dataset.key = key;
        row.dataset.category = category;
        row.innerHTML = `
            <td>
                <code class="key-code">${escapeHtml(key)}</code>
                <span class="badge badge-success" style="margin-left: 6px;">New</span>
            </td>
            <td>
                <textarea class="lang-input en-input changed" data-key="${escapeHtml(key)}" data-lang="en" rows="2">${escapeHtml(en)}</textarea>
            </td>
            <td>
                <textarea class="lang-input bm-input changed" data-key="${escapeHtml(key)}" data-lang="bm" rows="2">${escapeHtml(bm)}</textarea>
            </td>
            <td>
                <button class="action-btn delete" onclick="deleteKey('${escapeHtml(key)}')" title="Delete">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </td>
        `;
        tbody.appendChild(row);

        // Add event listeners
        row.querySelectorAll('.lang-input').forEach(input => {
            input.addEventListener('input', function () {
                this.classList.add('changed');
                changedKeys.add(this.dataset.key);
                hasChanges = true;
            });
        });

        changedKeys.add(key);
        hasChanges = true;

        // Clear form
        document.getElementById('newKey').value = '';
        document.getElementById('newEn').value = '';
        document.getElementById('newBm').value = '';

        showToast('<?php _e('key_added_save_reminder'); ?>', 'success');
    }

    function deleteKey(key) {
        if (!confirm(`Delete the key "${key}"? This will remove it from both language files.`)) {
            return;
        }

        const row = document.querySelector(`tr[data-key="${key}"]`);
        if (row) {
            row.remove();
        }

        // Mark for deletion
        changedKeys.add('__delete__' + key);
        hasChanges = true;

        showToast('<?php _e('key_deleted_save_reminder'); ?>', 'success');
    }

    async function saveAllChanges() {
        // Collect all strings
        const enStrings = {};
        const bmStrings = {};
        const deletedKeys = [];

        document.querySelectorAll('.lang-row').forEach(row => {
            const key = row.dataset.key;
            const enInput = row.querySelector('.en-input');
            const bmInput = row.querySelector('.bm-input');

            if (enInput && bmInput) {
                enStrings[key] = enInput.value;
                bmStrings[key] = bmInput.value;
            }
        });

        // Get deleted keys
        changedKeys.forEach(k => {
            if (k.startsWith('__delete__')) {
                deletedKeys.push(k.replace('__delete__', ''));
            }
        });

        try {
            const response = await fetch(basePath + 'api/admin/language.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: '<?php echo generateCsrfToken(); ?>',
                    en: enStrings,
                    bm: bmStrings,
                    deleted: deletedKeys
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast('<?php _e('success_translations_saved'); ?>', 'success');

                // Clear changed states
                document.querySelectorAll('.lang-input.changed').forEach(el => {
                    el.classList.remove('changed');
                });
                changedKeys.clear();
                hasChanges = false;

                // Reload after short delay
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || 'Failed to save', 'error');
            }
        } catch (error) {
            console.error('Save error:', error);
            showToast('<?php _e('dash_error_occurred'); ?>', 'error');
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', (e) => {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>