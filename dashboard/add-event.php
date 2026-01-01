<?php
/**
 * CrossConnect MY - Add New Event (User Dashboard)
 */

require_once __DIR__ . '/../config/language.php';

$currentPage = 'my-events';
$pageTitle = __('add_new_event');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

$user = getCurrentUser();
$pdo = getDbConnection();

// Get states for form
$states = [];
try {
    $states = $pdo->query("SELECT id, name FROM states ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Add event error: " . $e->getMessage());
}
?>

<!-- Page Header with Back Button -->
<div class="page-header-simple">
    <a href="<?php echo url('dashboard/my-events.php'); ?>" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"></path>
        </svg>
        <?php _e('back_to_events'); ?>
    </a>
    <h1><?php _e('add_new_event'); ?></h1>
    <p><?php _e('add_event_desc'); ?></p>
</div>

<form id="eventForm" class="content-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <div class="form-section">
        <h3><?php _e('event_details'); ?></h3>

        <div class="form-group">
            <label class="form-label"><?php _e('event_name'); ?> <span class="required">*</span></label>
            <input type="text" name="name" class="form-input" required
                placeholder="<?php _e('event_name_placeholder'); ?>">
        </div>

        <div class="form-group">
            <label class="form-label"><?php _e('description'); ?></label>
            <textarea name="description" class="form-textarea" rows="4"
                placeholder="<?php _e('event_description_placeholder'); ?>"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('organizer'); ?></label>
                <input type="text" name="organizer" class="form-input"
                    placeholder="<?php _e('organizer_placeholder'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('event_type'); ?></label>
                <select name="event_type" class="form-select">
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
    </div>

    <div class="form-section">
        <h3><?php _e('date_and_time'); ?></h3>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('start_date'); ?> <span class="required">*</span></label>
                <input type="date" name="event_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('end_date'); ?></label>
                <input type="date" name="event_end_date" class="form-input">
                <p class="form-hint"><?php _e('end_date_hint'); ?></p>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label"><?php _e('time'); ?></label>
            <input type="text" name="event_time" class="form-input" placeholder="<?php _e('time_placeholder'); ?>">
        </div>
    </div>

    <div class="form-section">
        <h3><?php _e('event_poster'); ?></h3>

        <div class="form-group">
            <label class="form-label"><?php _e('featured_image'); ?></label>
            <div class="file-upload" id="photoUpload">
                <input type="file" name="photo" id="photoInput" accept="image/*" hidden>
                <div class="file-upload-area" onclick="document.getElementById('photoInput').click()">
                    <div class="file-upload-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </div>
                    <div class="file-upload-text">
                        <strong><?php _e('click_to_upload'); ?></strong> <?php _e('or_drag_drop'); ?>
                        <span><?php _e('image_format_hint'); ?></span>
                    </div>
                </div>
                <div class="file-preview" id="photoPreview" style="display: none;">
                    <img id="photoPreviewImg" src="" alt="Preview">
                    <button type="button" class="file-remove" onclick="removePhoto()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
            <p class="form-hint"><?php _e('event_poster_hint'); ?></p>
        </div>
    </div>

    <!-- Event Format Section -->
    <div class="form-section">
        <h3><?php _e('event_format'); ?></h3>

        <div class="form-group">
            <div class="format-selector">
                <label class="format-option">
                    <input type="radio" name="event_format" value="in_person" checked onchange="toggleEventFormat()">
                    <div class="format-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span><?php _e('format_in_person'); ?></span>
                    </div>
                </label>
                <label class="format-option">
                    <input type="radio" name="event_format" value="online" onchange="toggleEventFormat()">
                    <div class="format-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                        <span><?php _e('format_online'); ?></span>
                    </div>
                </label>
                <label class="format-option">
                    <input type="radio" name="event_format" value="hybrid" onchange="toggleEventFormat()">
                    <div class="format-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M2 12h20"></path>
                            <path
                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z">
                            </path>
                        </svg>
                        <span><?php _e('format_hybrid'); ?></span>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <!-- Online Event Details (shown for online & hybrid) -->
    <div class="form-section" id="onlineEventSection" style="display: none;">
        <h3><?php _e('online_event_details'); ?></h3>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('meeting_link'); ?></label>
                <input type="url" name="meeting_url" class="form-input"
                    placeholder="<?php _e('meeting_link_placeholder'); ?>">
                <p class="form-hint"><?php _e('meeting_link_hint'); ?></p>
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('livestream_url'); ?></label>
                <input type="url" name="livestream_url" class="form-input"
                    placeholder="<?php _e('livestream_url_placeholder'); ?>">
                <p class="form-hint"><?php _e('livestream_url_hint'); ?></p>
            </div>
        </div>
    </div>

    <!-- Venue Information (hidden for online-only) -->
    <div class="form-section" id="venueSection">
        <h3><?php _e('venue_information'); ?></h3>

        <div class="form-group">
            <label class="form-label"><?php _e('venue'); ?></label>
            <input type="text" name="venue" class="form-input" placeholder="<?php _e('venue_placeholder'); ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('state'); ?></label>
                <select name="state_id" class="form-select">
                    <option value=""><?php _e('select_state'); ?></option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('city'); ?></label>
                <input type="text" name="city" class="form-input" placeholder="<?php _e('city_placeholder'); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label"><?php _e('venue_address'); ?></label>
            <textarea name="venue_address" class="form-textarea" rows="2"
                placeholder="<?php _e('venue_address_placeholder'); ?>"></textarea>
        </div>
    </div>

    <div class="form-section">
        <h3><?php _e('registration_contact'); ?></h3>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('website_url'); ?></label>
                <input type="url" name="website_url" class="form-input" placeholder="https://www.event-website.com">
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('registration_url'); ?></label>
                <input type="url" name="registration_url" class="form-input" placeholder="https://register.event.com">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('email'); ?></label>
                <input type="email" name="email" class="form-input" placeholder="info@event.com">
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('whatsapp'); ?></label>
                <input type="text" name="whatsapp" class="form-input" placeholder="60123456789">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('price_fee'); ?></label>
                <input type="text" name="price" class="form-input" placeholder="<?php _e('price_placeholder'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('max_capacity'); ?></label>
                <input type="number" name="capacity" class="form-input"
                    placeholder="<?php _e('capacity_placeholder'); ?>">
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?php echo url('dashboard/my-events.php'); ?>" class="btn btn-secondary"><?php _e('cancel'); ?></a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            <?php _e('save_event'); ?>
        </button>
    </div>
</form>

<style>
    .page-header-simple {
        margin-bottom: 32px;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--color-text-light);
        text-decoration: none;
        font-size: 0.875rem;
        margin-bottom: 12px;
        transition: color 0.15s;
    }

    .back-link:hover {
        color: var(--color-primary);
    }

    .back-link svg {
        width: 18px;
        height: 18px;
    }

    .page-header-simple h1 {
        margin: 0 0 4px;
        font-size: 1.75rem;
    }

    .page-header-simple p {
        margin: 0;
        color: var(--color-text-light);
    }

    .content-form {
        max-width: 800px;
    }

    .form-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .form-section h3 {
        margin: 0 0 20px;
        font-size: 1.125rem;
        color: var(--color-text);
        padding-bottom: 12px;
        border-bottom: 1px solid var(--color-border);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    @media (max-width: 640px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }

    .required {
        color: #ef4444;
    }

    .file-upload-area {
        border: 2px dashed var(--color-border);
        border-radius: 12px;
        padding: 32px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: var(--color-bg);
    }

    .file-upload-area:hover {
        border-color: var(--color-primary);
        background: var(--color-primary-bg);
    }

    .file-upload-icon {
        margin-bottom: 12px;
    }

    .file-upload-icon svg {
        width: 48px;
        height: 48px;
        color: var(--color-text-light);
    }

    .file-upload-text {
        color: var(--color-text);
    }

    .file-upload-text span {
        display: block;
        font-size: 0.8rem;
        color: var(--color-text-light);
        margin-top: 4px;
    }

    .file-preview {
        position: relative;
        display: inline-block;
        margin-top: 12px;
    }

    .file-preview img {
        max-width: 200px;
        max-height: 150px;
        border-radius: 8px;
        object-fit: cover;
    }

    .file-remove {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #ef4444;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .file-remove svg {
        width: 14px;
        height: 14px;
        color: white;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        padding-top: 12px;
    }

    .form-actions .btn {
        min-width: 120px;
    }

    /* Event Format Selector */
    .format-selector {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .format-option {
        flex: 1;
        min-width: 100px;
        cursor: pointer;
    }

    .format-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .format-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 16px 12px;
        border: 2px solid var(--color-border);
        border-radius: 12px;
        background: var(--color-bg);
        transition: all 0.2s;
        text-align: center;
    }

    .format-card svg {
        width: 28px;
        height: 28px;
        color: var(--color-text-light);
        transition: color 0.2s;
    }

    .format-card span {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--color-text);
    }

    .format-option:hover .format-card {
        border-color: var(--color-primary);
        background: var(--color-primary-bg, #f5f3ff);
    }

    .format-option input[type="radio"]:checked+.format-card {
        border-color: var(--color-primary);
        background: var(--color-primary-bg, #f5f3ff);
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
    }

    .format-option input[type="radio"]:checked+.format-card svg {
        color: var(--color-primary);
    }

    .format-option input[type="radio"]:checked+.format-card span {
        color: var(--color-primary);
    }

    @media (max-width: 480px) {
        .format-selector {
            flex-direction: column;
        }

        .format-option {
            min-width: 100%;
        }

        .format-card {
            flex-direction: row;
            justify-content: center;
            padding: 12px 16px;
        }
    }
</style>

<script>
    const translations = {
        eventAdded: <?php echo json_encode(__('event_added_success')); ?>,
        addFailed: <?php echo json_encode(__('event_save_failed')); ?>,
        errorOccurred: <?php echo json_encode(__('dash_error_occurred')); ?>,
        saving: <?php echo json_encode(__('saving')); ?>,
        saveEvent: <?php echo json_encode(__('save_event')); ?>
    };

    // Toggle venue/online sections based on event format
    function toggleEventFormat() {
        const format = document.querySelector('input[name="event_format"]:checked').value;
        const venueSection = document.getElementById('venueSection');
        const onlineSection = document.getElementById('onlineEventSection');

        if (format === 'in_person') {
            venueSection.style.display = 'block';
            onlineSection.style.display = 'none';
        } else if (format === 'online') {
            venueSection.style.display = 'none';
            onlineSection.style.display = 'block';
        } else { // hybrid
            venueSection.style.display = 'block';
            onlineSection.style.display = 'block';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', toggleEventFormat);

    // Photo upload preview
    document.getElementById('photoInput').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('photoPreviewImg').src = e.target.result;
                document.getElementById('photoPreview').style.display = 'inline-block';
                document.querySelector('.file-upload-area').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    function removePhoto() {
        document.getElementById('photoInput').value = '';
        document.getElementById('photoPreview').style.display = 'none';
        document.querySelector('.file-upload-area').style.display = 'block';
    }

    // Form submission
    document.getElementById('eventForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span>' + translations.saving + '</span>';

        try {
            const formData = new FormData(this);

            const response = await fetch(basePath + 'api/user/events.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(translations.eventAdded, 'success');
                setTimeout(() => {
                    window.location.href = basePath + 'dashboard/my-events.php';
                }, 1000);
            } else {
                showToast(data.error || translations.addFailed, 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> ' + translations.saveEvent;
            }
        } catch (error) {
            showToast(translations.errorOccurred, 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> ' + translations.saveEvent;
        }
    });

    // Drag and drop
    const uploadArea = document.querySelector('.file-upload-area');

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = 'var(--color-primary)';
        uploadArea.style.background = 'var(--color-primary-bg)';
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '';
        uploadArea.style.background = '';
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '';
        uploadArea.style.background = '';

        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            document.getElementById('photoInput').files = e.dataTransfer.files;
            document.getElementById('photoInput').dispatchEvent(new Event('change'));
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>