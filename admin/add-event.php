<?php
/**
 * CrossConnect MY - Add New Event
 */

$currentPage = 'events';
$pageTitle = 'Add New Event';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

requireAdmin();

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
    <a href="<?php echo url('admin/events.php'); ?>" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"></path>
        </svg>
        Back to Events
    </a>
    <h1>Add New Event</h1>
    <p>Fill in the details below to add a new event to the directory.</p>
</div>

<form id="eventForm" class="content-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <div class="form-section">
        <h3>Event Details</h3>

        <div class="form-group">
            <label class="form-label">Event Name <span class="required">*</span></label>
            <input type="text" name="name" class="form-input" required
                placeholder="e.g., Malaysia Christian Conference 2025">
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" rows="4"
                placeholder="Describe the event, its purpose, and what attendees can expect..."></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Organizer</label>
                <input type="text" name="organizer" class="form-input" placeholder="e.g., Grace Assembly">
            </div>
            <div class="form-group">
                <label class="form-label">Event Type</label>
                <select name="event_type" class="form-select">
                    <option value="">Select Type</option>
                    <option value="conference">Conference</option>
                    <option value="worship">Worship Night</option>
                    <option value="seminar">Seminar</option>
                    <option value="retreat">Retreat</option>
                    <option value="concert">Concert</option>
                    <option value="outreach">Outreach</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3>Date & Time</h3>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Start Date <span class="required">*</span></label>
                <input type="date" name="event_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="event_end_date" class="form-input">
                <p class="form-hint">Leave blank for single-day events</p>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Time</label>
            <input type="text" name="event_time" class="form-input" placeholder="e.g., 9:00 AM - 5:00 PM">
        </div>
    </div>

    <div class="form-section">
        <h3>Event Poster / Banner</h3>

        <div class="form-group">
            <label class="form-label">Featured Image</label>
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
                        <strong>Click to upload</strong> or drag and drop
                        <span>PNG, JPG, WEBP up to 5MB</span>
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
            <p class="form-hint">Upload the event poster, banner, or a promotional image</p>
        </div>
    </div>

    <div class="form-section">
        <h3>Venue Information</h3>

        <div class="form-group">
            <label class="form-label">Venue Name</label>
            <input type="text" name="venue" class="form-input" placeholder="e.g., KLCC Convention Centre">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">State</label>
                <select name="state_id" class="form-select">
                    <option value="">Select State</option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-input" placeholder="e.g., Kuala Lumpur">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Venue Address</label>
            <textarea name="venue_address" class="form-textarea" rows="2"
                placeholder="Full address of the venue"></textarea>
        </div>
    </div>

    <div class="form-section">
        <h3>Registration & Contact</h3>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Website</label>
                <input type="url" name="website_url" class="form-input" placeholder="https://www.event-website.com">
            </div>
            <div class="form-group">
                <label class="form-label">Registration URL</label>
                <input type="url" name="registration_url" class="form-input" placeholder="https://register.event.com">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Contact Email</label>
                <input type="email" name="email" class="form-input" placeholder="info@event.com">
            </div>
            <div class="form-group">
                <label class="form-label">WhatsApp</label>
                <input type="text" name="whatsapp" class="form-input" placeholder="60123456789">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Price / Fee</label>
                <input type="text" name="price" class="form-input" placeholder="e.g., RM50 per person or Free">
            </div>
            <div class="form-group">
                <label class="form-label">Max Capacity</label>
                <input type="number" name="capacity" class="form-input" placeholder="e.g., 500">
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?php echo url('admin/events.php'); ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Save Event
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
</style>

<script>
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
        btn.innerHTML = '<span>Saving...</span>';

        try {
            const formData = new FormData(this);

            const response = await fetch(basePath + 'api/user/events.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('Event added successfully!', 'success');
                setTimeout(() => {
                    window.location.href = basePath + 'admin/events.php';
                }, 1000);
            } else {
                showToast(data.error || 'Failed to add event', 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Save Event';
            }
        } catch (error) {
            showToast('An error occurred', 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Save Event';
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