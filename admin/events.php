<?php
/**
 * CrossConnect MY - Admin Events Management
 */

$currentPage = 'events';
$pageTitle = 'Events';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

requireAdmin();

$pdo = getDbConnection();

// Get states for form
$states = [];
try {
    $states = $pdo->query("SELECT id, name FROM states ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Admin events error: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2>Events</h2>
        <p id="totalCount">Loading...</p>
    </div>
    <div class="page-header-filters">
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="M21 21l-4.35-4.35"></path>
            </svg>
            <input type="text" id="searchInput" placeholder="Search events..." autocomplete="off">
        </div>
        <select id="statusFilter" class="form-select">
            <option value="">All Status</option>
            <option value="upcoming">Upcoming</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
</div>

<!-- Tabs for Active/Past -->
<div class="event-tabs">
    <button class="tab-btn active" id="tabActive" onclick="switchTab('active')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        Active & Upcoming
    </button>
    <button class="tab-btn" id="tabPast" onclick="switchTab('past')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
            <path d="M3 3v5h5"></path>
            <path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"></path>
        </svg>
        Past Events
    </button>
</div>

<!-- Table Header with Add Button -->
<div class="table-header">
    <span class="table-header-info" id="showingInfo"></span>
    <a href="<?php echo url('admin/add-event.php'); ?>" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Add Event</span>
    </a>
</div>

<div class="dashboard-card">
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="dataTable">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th class="hide-mobile">Location</th>
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
<div class="modal" id="eventModal">
    <div class="modal-header">
        <h3 id="modalTitle">Add Event</h3>
        <button class="modal-close" onclick="closeModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <form id="eventForm" onsubmit="saveEvent(event)">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="id" id="eventId">

        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Event Name *</label>
                <input type="text" name="name" id="eventName" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="event_date" id="eventDate" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" name="event_end_date" id="eventEndDate" class="form-input">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Time</label>
                    <input type="text" name="event_time" id="eventTime" class="form-input"
                        placeholder="e.g., 9:00 AM - 5:00 PM">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Organizer</label>
                    <input type="text" name="organizer" id="eventOrganizer" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Event Type</label>
                    <select name="event_type" id="eventType" class="form-select">
                        <option value="">Select Type</option>
                        <option value="conference">Conference</option>
                        <option value="worship">Worship Night</option>
                        <option value="prayer">Prayer Meeting</option>
                        <option value="seminar">Seminar</option>
                        <option value="retreat">Retreat</option>
                        <option value="concert">Concert</option>
                        <option value="outreach">Outreach</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <!-- Event Format -->
            <div class="form-group">
                <label class="form-label">Event Format</label>
                <div class="format-selector-compact">
                    <label class="format-option-compact">
                        <input type="radio" name="event_format" id="formatInPerson" value="in_person" checked
                            onchange="toggleEventFormatModal()">
                        <span>In-person</span>
                    </label>
                    <label class="format-option-compact">
                        <input type="radio" name="event_format" id="formatOnline" value="online"
                            onchange="toggleEventFormatModal()">
                        <span>Online</span>
                    </label>
                    <label class="format-option-compact">
                        <input type="radio" name="event_format" id="formatHybrid" value="hybrid"
                            onchange="toggleEventFormatModal()">
                        <span>Hybrid</span>
                    </label>
                </div>
            </div>

            <!-- Online Event Fields -->
            <div class="form-row" id="onlineFieldsSection" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Meeting Link</label>
                    <input type="url" name="meeting_url" id="eventMeetingUrl" class="form-input"
                        placeholder="https://zoom.us/j/... or https://meet.google.com/...">
                </div>
                <div class="form-group">
                    <label class="form-label">Livestream URL</label>
                    <input type="url" name="livestream_url" id="eventLivestreamUrl" class="form-input"
                        placeholder="https://youtube.com/live/...">
                </div>
            </div>

            <!-- Venue Fields -->
            <div id="venueFieldsSection">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Venue</label>
                        <input type="text" name="venue" id="eventVenue" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <select name="state_id" id="eventState" class="form-select">
                            <option value="">Select State</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Venue Address</label>
                    <textarea name="venue_address" id="eventVenueAddress" class="form-textarea" rows="2"></textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="eventDescription" class="form-textarea" rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Website URL</label>
                    <input type="url" name="website_url" id="eventWebsite" class="form-input" placeholder="https://">
                </div>
                <div class="form-group">
                    <label class="form-label">Registration URL</label>
                    <input type="url" name="registration_url" id="eventRegistration" class="form-input"
                        placeholder="https://">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="eventEmail" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="whatsapp" id="eventWhatsapp" class="form-input" placeholder="60123456789">
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveBtn">Save Event</button>
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

        #statusFilter {
            width: auto;
            min-width: 120px;
        }

        .table-header .btn span {
            display: inline;
        }
    }

    /* Tab styles */
    .event-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
    }

    .tab-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        border: 1px solid var(--color-border);
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--color-text-light);
        transition: all 0.2s;
    }

    .tab-btn:hover {
        border-color: var(--color-primary);
        color: var(--color-primary);
    }

    .tab-btn.active {
        background: var(--color-primary);
        border-color: var(--color-primary);
        color: white;
    }

    @media (max-width: 480px) {
        .event-tabs {
            width: 100%;
        }

        .tab-btn {
            flex: 1;
            justify-content: center;
            padding: 8px 12px;
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

    /* Format Selector Compact for Modal */
    .format-selector-compact {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .format-option-compact {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border: 2px solid var(--color-border);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.875rem;
    }

    .format-option-compact:hover {
        border-color: var(--color-primary);
    }

    .format-option-compact input[type="radio"] {
        accent-color: var(--color-primary);
    }

    .format-option-compact:has(input:checked) {
        border-color: var(--color-primary);
        background: var(--color-primary-bg, #f5f3ff);
    }
</style>

<script>
    let currentPage = 1;
    let searchTimeout = null;
    let showPast = '0'; // '0' = active/upcoming, '1' = past

    document.addEventListener('DOMContentLoaded', () => {
        loadData();

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

    function switchTab(tab) {
        showPast = tab === 'past' ? '1' : '0';
        currentPage = 1;

        document.getElementById('tabActive').classList.toggle('active', tab === 'active');
        document.getElementById('tabPast').classList.toggle('active', tab === 'past');

        loadData();
    }

    async function loadData() {
        const query = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;

        const params = new URLSearchParams({
            type: 'events',
            q: query,
            status: status,
            past: showPast,
            page: currentPage
        });

        try {
            const response = await fetch(basePath + 'api/admin/search.php?' + params);
            const result = await response.json();

            if (result.success) {
                renderTable(result.data.data);
                renderPagination(result.data);
                const label = showPast === '1' ? 'past events' : 'active/upcoming events';
                document.getElementById('totalCount').textContent =
                    `${result.data.total.toLocaleString()} ${label}`;
            }
        } catch (error) {
            console.error('Load error:', error);
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('tableBody');

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--color-text-light);">No events found</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(event => {
            const eventDate = new Date(event.event_date);
            const dateStr = eventDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const isPast = event.is_past === 1 || event.is_past === '1';

            return `
                <tr style="${isPast ? 'opacity: 0.6;' : ''}">
                    <td>
                        <div style="font-weight: 500;">${escapeHtml(event.name)}</div>
                        <div style="font-size: 0.8rem; color: var(--color-text-light);">
                            ${escapeHtml(event.organizer || '')}
                        </div>
                    </td>
                    <td>
                        <div>${dateStr}</div>
                        ${event.event_time ? `<div style="font-size: 0.8rem; color: var(--color-text-light);">${escapeHtml(event.event_time)}</div>` : ''}
                    </td>
                    <td class="hide-mobile">
                        <div>${escapeHtml(event.venue || '-')}</div>
                        <div style="font-size: 0.8rem; color: var(--color-text-light);">
                            ${escapeHtml(event.state_name || '')}
                        </div>
                    </td>
                    <td>
                        <span class="badge ${event.status === 'upcoming' ? 'badge-success' :
                    (event.status === 'ongoing' ? 'badge-info' :
                        (event.status === 'completed' ? 'badge-warning' : 'badge-danger'))}">
                            ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="action-btn edit" onclick="editEvent(${event.id})" title="Edit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <a href="${basePath}event.php?slug=${event.slug}" class="action-btn" title="View" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            ${event.status !== 'cancelled' ? `
                                <button class="action-btn delete" onclick="updateStatus(${event.id}, 'cancelled')" title="Cancel">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                </button>
                            ` : `
                                <button class="action-btn" onclick="updateStatus(${event.id}, 'upcoming')" title="Restore" style="color: #10b981;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"></path>
                                    </svg>
                                </button>
                            `}
                            <button class="action-btn delete" onclick="deleteEvent(${event.id}, '${event.name.replace(/'/g, "\\'")}')" title="Delete Permanently" style="color: #dc2626;">
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

    function openModal() {
        document.getElementById('modalOverlay').classList.add('show');
        document.getElementById('eventModal').classList.add('show');
        document.getElementById('modalTitle').textContent = 'Add Event';
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = '';
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('show');
        document.getElementById('eventModal').classList.remove('show');
    }

    function toggleEventFormatModal() {
        const format = document.querySelector('input[name="event_format"]:checked').value;
        const venueSection = document.getElementById('venueFieldsSection');
        const onlineSection = document.getElementById('onlineFieldsSection');

        if (format === 'in_person') {
            venueSection.style.display = 'block';
            onlineSection.style.display = 'none';
        } else if (format === 'online') {
            venueSection.style.display = 'none';
            onlineSection.style.display = 'flex';
        } else { // hybrid
            venueSection.style.display = 'block';
            onlineSection.style.display = 'flex';
        }
    }

    async function saveEvent(e) {
        e.preventDefault();
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const formData = new FormData(e.target);
            const response = await fetch(basePath + 'api/user/events.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message || 'Event saved!', 'success');
                closeModal();
                loadData();
            } else {
                showToast(data.error || 'Failed to save', 'error');
            }
        } catch (error) {
            showToast('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save Event';
        }
    }

    async function updateStatus(id, status) {
        const action = status === 'cancelled' ? 'cancel' : 'restore';
        if (!confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} this event?`)) return;

        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            formData.append('type', 'event');
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

    async function editEvent(id) {
        try {
            const response = await fetch(basePath + 'api/admin/search.php?type=events&id=' + id);
            const result = await response.json();

            if (result.success && result.data.data.length > 0) {
                const event = result.data.data[0];

                document.getElementById('modalTitle').textContent = 'Edit Event';
                document.getElementById('eventId').value = event.id;
                document.getElementById('eventName').value = event.name || '';
                document.getElementById('eventDate').value = event.event_date || '';
                document.getElementById('eventEndDate').value = event.event_end_date || '';
                document.getElementById('eventTime').value = event.event_time || '';
                document.getElementById('eventState').value = event.state_id || '';
                document.getElementById('eventVenue').value = event.venue || '';
                document.getElementById('eventVenueAddress').value = event.venue_address || '';
                document.getElementById('eventOrganizer').value = event.organizer || '';
                document.getElementById('eventDescription').value = event.description || '';
                document.getElementById('eventWebsite').value = event.website_url || '';
                document.getElementById('eventRegistration').value = event.registration_url || '';
                document.getElementById('eventEmail').value = event.email || '';
                document.getElementById('eventWhatsapp').value = event.whatsapp || '';

                // New fields
                document.getElementById('eventType').value = event.event_type || '';
                document.getElementById('eventMeetingUrl').value = event.meeting_url || '';
                document.getElementById('eventLivestreamUrl').value = event.livestream_url || '';

                // Set event format
                const format = event.event_format || 'in_person';
                document.querySelector(`input[name="event_format"][value="${format}"]`).checked = true;
                toggleEventFormatModal();

                document.getElementById('modalOverlay').classList.add('show');
                document.getElementById('eventModal').classList.add('show');
            } else {
                showToast('Failed to load event data', 'error');
            }
        } catch (error) {
            console.error('Edit error:', error);
            showToast('Failed to load event data', 'error');
        }
    }

    async function deleteEvent(id, name) {
        console.log('=== DELETE EVENT DEBUG ===');
        console.log('Event ID:', id);
        console.log('Event Name:', name);

        if (!confirm(`Are you sure you want to permanently delete "${name}"?\n\nThis action cannot be undone.`)) {
            console.log('User cancelled delete');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');
            formData.append('id', id);
            formData.append('action', 'delete');

            console.log('Sending to:', basePath + 'api/admin/events.php');

            const response = await fetch(basePath + 'api/admin/events.php', {
                method: 'POST',
                body: formData
            });

            const responseText = await response.text();
            console.log('Raw response:', responseText);
            console.log('Response status:', response.status);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response was:', responseText);
                showToast('Server error: ' + responseText.substring(0, 100), 'error');
                return;
            }

            if (data.success) {
                showToast('Event deleted successfully', 'success');
                loadData();
            } else {
                console.log('API returned error:', data.error);
                showToast(data.error || 'Failed to delete event', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            showToast('Failed to delete event', 'error');
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>