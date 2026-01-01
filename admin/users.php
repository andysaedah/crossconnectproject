<?php
/**
 * CrossConnect MY - Admin User Management
 */

$currentPage = 'users';
$pageTitle = 'Users';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

requireAdmin();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2>Users</h2>
        <p id="totalCount">Loading...</p>
    </div>
    <div class="page-header-filters">
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="M21 21l-4.35-4.35"></path>
            </svg>
            <input type="text" id="searchInput" placeholder="Search users..." autocomplete="off">
        </div>
        <select id="statusFilter" class="form-select">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="unverified">Unverified</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
</div>

<div class="dashboard-card">
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="dataTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th class="hide-mobile">Email</th>
                        <th>Role</th>
                        <th class="hide-mobile">Content</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="pagination" id="pagination"></div>

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

        #statusFilter {
            width: auto;
            min-width: 120px;
        }
    }
</style>

<script>
    let currentPage = 1;
    let searchTimeout = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadData();

        document.getElementById('searchInput').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                loadData();
            }, 300);
        });

        // Status filter change handler
        document.getElementById('statusFilter').addEventListener('change', () => {
            currentPage = 1;
            loadData();
        });
    });

    async function loadData() {
        const query = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;

        const params = new URLSearchParams({
            type: 'users',
            q: query,
            page: currentPage,
            status: status
        });

        try {
            const response = await fetch(basePath + 'api/admin/search.php?' + params);
            const result = await response.json();

            if (result.success) {
                renderTable(result.data.data);
                renderPagination(result.data);
                document.getElementById('totalCount').textContent =
                    `${result.data.total.toLocaleString()} total users`;
            }
        } catch (error) {
            console.error('Load error:', error);
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('tableBody');

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--color-text-light);">No users found</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(user => {
            const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const joinedDate = new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            let statusBadge;
            if (!user.email_verified_at) {
                statusBadge = '<span class="badge badge-warning">Unverified</span>';
            } else if (!user.is_active) {
                statusBadge = '<span class="badge badge-danger">Inactive</span>';
            } else {
                statusBadge = '<span class="badge badge-success">Active</span>';
            }

            return `
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.8rem; background: ${escapeHtml(user.avatar_color)}">
                                ${initials}
                            </div>
                            <div>
                                <div style="font-weight: 500;">${escapeHtml(user.name)}</div>
                                <div style="font-size: 0.8rem; color: var(--color-text-light);">@${escapeHtml(user.username)}</div>
                            </div>
                        </div>
                    </td>
                    <td class="hide-mobile">${escapeHtml(user.email)}</td>
                    <td>
                        <span class="badge ${user.role === 'admin' ? 'badge-info' : 'badge-success'}">
                            ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                        </span>
                    </td>
                    <td class="hide-mobile" style="font-size: 0.8rem;">
                        <span style="color: var(--color-text-light);">Joined ${joinedDate}</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="table-actions">
                            ${!user.email_verified_at ? `
                                <button class="action-btn" onclick="userAction(${user.id}, 'verify')" title="Verify Email">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"></path>
                                    </svg>
                                </button>
                            ` : ''}
                            ${user.is_active ? `
                                <button class="action-btn delete" onclick="toggleUser(${user.id}, 'deactivate')" title="Deactivate">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                                    </svg>
                                </button>
                            ` : `
                                <button class="action-btn edit" onclick="toggleUser(${user.id}, 'activate')" title="Activate">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"></path>
                                    </svg>
                                </button>
                            `}
                            <button class="action-btn" onclick="resetPassword(${user.id}, '${escapeHtml(user.name)}')" title="Reset Password">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
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

    async function toggleUser(id, action) {
        const msg = action === 'deactivate'
            ? 'Deactivate this user? They will not be able to login.'
            : 'Activate this user?';
        if (!confirm(msg)) return;
        await userAction(id, action);
    }

    async function resetPassword(id, name) {
        if (!confirm(`Reset password for ${name}? A temporary password will be generated.`)) return;
        await userAction(id, 'reset_password');
    }

    async function userAction(id, action) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', action);
            formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');

            const response = await fetch(basePath + 'api/admin/users.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message || 'Action completed', 'success');
                loadData();
            } else {
                showToast(data.error || 'Action failed', 'error');
            }
        } catch (error) {
            showToast('An error occurred', 'error');
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>