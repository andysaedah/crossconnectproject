<?php
/**
 * CrossConnect MY - My Events Page
 * User can view, add, edit, delete their own events
 */

require_once __DIR__ . '/../config/language.php';

$currentPage = 'my-events';
$pageTitle = __('dash_my_events');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

$user = getCurrentUser();

// Get user's events
$events = [];
$pdo = getDbConnection();
try {
    $stmt = $pdo->prepare("
        SELECT e.*, s.name as state_name
        FROM events e
        LEFT JOIN states s ON e.state_id = s.id
        WHERE e.created_by = ?
        ORDER BY e.event_date DESC
    ");
    $stmt->execute([$user['id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get states for edit modal
    $states = $pdo->query("SELECT id, name FROM states ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("My events error: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2><?php _e('dash_my_events'); ?></h2>
        <p><?php echo count($events); ?> <?php _e('events'); ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?php echo url('dashboard/add-event.php'); ?>" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span><?php _e('add_event'); ?></span>
        </a>
    </div>
</div>

<?php if (empty($events)): ?>
    <!-- Empty State -->
    <div class="dashboard-card">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                <path d="M16 2V6M8 2V6M3 10H21"></path>
            </svg>
            <h3><?php _e('no_events_yet'); ?></h3>
            <p><?php _e('no_events_yet_desc'); ?></p>
            <a href="<?php echo url('dashboard/add-event.php'); ?>" class="btn btn-primary"><?php _e('add_event'); ?></a>
        </div>
    </div>
<?php else: ?>
    <!-- Events List -->
    <div class="dashboard-card">
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php _e('event'); ?></th>
                            <th><?php _e('date'); ?></th>
                            <th class="hide-mobile"><?php _e('location'); ?></th>
                            <th><?php _e('status'); ?></th>
                            <th><?php _e('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($event['name']); ?></div>
                                    <div class="show-mobile-only"
                                        style="font-size: 0.75rem; color: var(--color-text-light); margin-top: 2px;">
                                        <?php echo htmlspecialchars($event['venue'] ?? ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo date('M j, Y', strtotime($event['event_date'])); ?></div>
                                    <?php if ($event['event_time']): ?>
                                        <div style="font-size: 0.75rem; color: var(--color-text-light);">
                                            <?php echo htmlspecialchars($event['event_time']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile">
                                    <div><?php echo htmlspecialchars($event['venue'] ?? '-'); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-light);">
                                        <?php echo htmlspecialchars($event['state_name'] ?? ''); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php
                                    echo $event['status'] === 'upcoming' ? 'badge-success' :
                                        ($event['status'] === 'ongoing' ? 'badge-info' :
                                            ($event['status'] === 'completed' ? 'badge-warning' : 'badge-danger'));
                                    ?>">
                                        <?php
                                        $statusKey = $event['status'] ?? 'upcoming';
                                        _e($statusKey);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="<?php echo url('event.php?slug=' . $event['slug']); ?>" class="action-btn"
                                            title="<?php _e('view'); ?>" target="_blank">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                        <button class="action-btn edit" onclick="editEvent(<?php echo $event['id']; ?>)"
                                            title="<?php _e('dash_edit'); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <button class="action-btn delete"
                                            onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars(addslashes($event['name'])); ?>')"
                                            title="<?php _e('delete'); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Edit Modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()"></div>
<div class="modal" id="eventModal">
    <div class="modal-header">
        <h3 id="modalTitle"><?php _e('edit_event'); ?></h3>
        <button class="modal-close" onclick="closeModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <form id="eventForm" onsubmit="saveEvent(event)" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="id" id="eventId">

        <div class="modal-body">
            <div class="form-group">
                <label class="form-label"><?php _e('event_name'); ?> *</label>
                <input type="text" name="name" id="eventName" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('start_date'); ?> *</label>
                    <input type="date" name="event_date" id="eventDate" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('end_date'); ?></label>
                    <input type="date" name="event_end_date" id="eventEndDate" class="form-input">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('time'); ?></label>
                    <input type="text" name="event_time" id="eventTime" class="form-input"
                        placeholder="<?php _e('time_placeholder'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('organizer'); ?></label>
                    <input type="text" name="organizer" id="eventOrganizer" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('event_type'); ?></label>
                    <select name="event_type" id="eventType" class="form-select">
                        <option value=""><?php _e('select_type'); ?></option>
                        <option value="conference"><?php _e('type_conference'); ?></option>
                        <option value="worship"><?php _e('type_worship'); ?></option>
                        <option value="prayer"><?php _e('type_prayer'); ?></option>
                        <option value="seminar"><?php _e('type_seminar'); ?></option>
                        <option value="retreat"><?php _e('type_retreat'); ?></option>
                        <option value="concert"><?php _e('type_concert'); ?></option>
                        <option value="outreach"><?php _e('type_outreach'); ?></option>
                        <option value="other"><?php _e('type_other'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Event Format -->
            <div class="form-group">
                <label class="form-label"><?php _e('event_format'); ?></label>
                <div class="format-selector-compact">
                    <label class="format-option-compact">
                        <input type="radio" name="event_format" id="formatInPerson" value="in_person" checked
                            onchange="toggleEventFormatModal()">
                        <span><?php _e('format_in_person'); ?></span>
                    </label>
                    <label class="format-option-compact">
                        <input type="radio" name="event_format" id="formatOnline" value="online"
                            onchange="toggleEventFormatModal()">
                        <span><?php _e('format_online'); ?></span>
                    </label>
                    <label class="format-option-compact">
                        <input type="radio" name="event_format" id="formatHybrid" value="hybrid"
                            onchange="toggleEventFormatModal()">
                        <span><?php _e('format_hybrid'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Online Event Fields -->
            <div class="form-row" id="onlineFieldsSection" style="display: none;">
                <div class="form-group">
                    <label class="form-label"><?php _e('meeting_link'); ?></label>
                    <input type="url" name="meeting_url" id="eventMeetingUrl" class="form-input"
                        placeholder="<?php _e('meeting_link_placeholder'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('livestream_url'); ?></label>
                    <input type="url" name="livestream_url" id="eventLivestreamUrl" class="form-input"
                        placeholder="<?php _e('livestream_url_placeholder'); ?>">
                </div>
            </div>

            <!-- Venue Fields -->
            <div id="venueFieldsSection">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php _e('venue'); ?></label>
                        <input type="text" name="venue" id="eventVenue" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php _e('state'); ?></label>
                        <select name="state_id" id="eventState" class="form-select">
                            <option value=""><?php _e('select_state'); ?></option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php _e('venue_address'); ?></label>
                    <textarea name="venue_address" id="eventVenueAddress" class="form-textarea" rows="2"></textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?php _e('description'); ?></label>
                <textarea name="description" id="eventDescription" class="form-textarea" rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('website_url'); ?></label>
                    <input type="url" name="website_url" id="eventWebsite" class="form-input" placeholder="https://">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('registration_url'); ?></label>
                    <input type="url" name="registration_url" id="eventRegistration" class="form-input"
                        placeholder="https://">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('email'); ?></label>
                    <input type="email" name="email" id="eventEmail" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('whatsapp'); ?></label>
                    <input type="text" name="whatsapp" id="eventWhatsapp" class="form-input" placeholder="60123456789">
                </div>
            </div>

            <!-- Image Upload -->
            <div class="form-group">
                <label class="form-label"><?php _e('event_poster'); ?></label>
                <div class="image-upload-edit" id="eventImageUploadContainer">
                    <div class="current-image-preview" id="eventCurrentImagePreview" style="display: none;">
                        <img id="eventCurrentImageImg" src="" alt="Current image">
                        <div class="image-overlay">
                            <span><?php _e('click_to_change'); ?></span>
                        </div>
                    </div>
                    <div class="upload-placeholder" id="eventUploadPlaceholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <span><?php _e('click_to_upload'); ?></span>
                    </div>
                    <input type="file" name="photo" id="eventPhoto" accept="image/*" hidden>
                </div>
                <p class="form-hint"><?php _e('keep_current_image'); ?></p>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()"><?php _e('cancel'); ?></button>
            <button type="submit" class="btn btn-primary" id="saveBtn"><?php _e('save_changes'); ?></button>
        </div>
    </form>
</div>

<style>
    /* Modal Styles */
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
    const eventsData = <?php echo json_encode($events); ?>;
    const translations = {
        editEvent: <?php echo json_encode(__('edit_event')); ?>,
        saveChanges: <?php echo json_encode(__('save_changes')); ?>,
        saving: <?php echo json_encode(__('saving')); ?>,
        eventSaved: <?php echo json_encode(__('event_saved')); ?>,
        eventSaveFailed: <?php echo json_encode(__('event_save_failed')); ?>,
        eventDeleted: <?php echo json_encode(__('event_deleted')); ?>,
        eventDeleteFailed: <?php echo json_encode(__('event_delete_failed')); ?>,
        confirmDelete: <?php echo json_encode(__('confirm_delete_event')); ?>,
        errorOccurred: <?php echo json_encode(__('dash_error_occurred')); ?>
    };

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

    function editEvent(id) {
        const event = eventsData.find(e => e.id == id);
        if (!event) return;

        document.getElementById('modalTitle').textContent = translations.editEvent;
        document.getElementById('eventId').value = id;
        document.getElementById('eventName').value = event.name || '';
        document.getElementById('eventDate').value = event.event_date || '';
        document.getElementById('eventEndDate').value = event.event_end_date || '';
        document.getElementById('eventTime').value = event.event_time || '';
        document.getElementById('eventState').value = event.state_id || '';
        document.getElementById('eventVenue').value = event.venue || '';
        document.getElementById('eventOrganizer').value = event.organizer || '';
        document.getElementById('eventVenueAddress').value = event.venue_address || '';
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

        // Handle image preview
        const imagePreview = document.getElementById('eventCurrentImagePreview');
        const uploadPlaceholder = document.getElementById('eventUploadPlaceholder');
        const currentImageImg = document.getElementById('eventCurrentImageImg');

        // Check for poster_url or image_url
        const imageUrl = event.poster_url || event.image_url;
        if (imageUrl) {
            currentImageImg.src = imageUrl;
            imagePreview.style.display = 'block';
            uploadPlaceholder.style.display = 'none';
        } else {
            imagePreview.style.display = 'none';
            uploadPlaceholder.style.display = 'flex';
        }

        // Reset file input
        document.getElementById('eventPhoto').value = '';

        document.getElementById('modalOverlay').classList.add('show');
        document.getElementById('eventModal').classList.add('show');
    }

    // Image upload click handler
    document.getElementById('eventImageUploadContainer').addEventListener('click', function () {
        document.getElementById('eventPhoto').click();
    });

    // Image preview on file select
    document.getElementById('eventPhoto').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('eventCurrentImageImg').src = e.target.result;
                document.getElementById('eventCurrentImagePreview').style.display = 'block';
                document.getElementById('eventUploadPlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    async function saveEvent(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = translations.saving;

        try {
            const formData = new FormData(form);
            const response = await fetch(basePath + 'api/user/events.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message || translations.eventSaved, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || translations.eventSaveFailed, 'error');
                btn.disabled = false;
                btn.textContent = translations.saveChanges;
            }
        } catch (error) {
            showToast(translations.errorOccurred, 'error');
            btn.disabled = false;
            btn.textContent = translations.saveChanges;
        }
    }

    async function deleteEvent(id, name) {
        const confirmMsg = translations.confirmDelete.replace('{name}', name);
        if (!confirm(confirmMsg)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'delete');
            formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');

            const response = await fetch(basePath + 'api/user/events.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(translations.eventDeleted, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || translations.eventDeleteFailed, 'error');
            }
        } catch (error) {
            showToast(translations.errorOccurred, 'error');
        }
    }

    // Close modal on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>