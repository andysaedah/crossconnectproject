<?php
/**
 * CrossConnect MY - Add New Church (User Dashboard)
 */

require_once __DIR__ . '/../config/language.php';

$currentPage = 'my-churches';
$pageTitle = __('add_new_church');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

$user = getCurrentUser();
$pdo = getDbConnection();

// Get states and denominations for form
$states = [];
$denominations = [];
try {
    $states = $pdo->query("SELECT id, name FROM states ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $denominations = $pdo->query("SELECT id, name FROM denominations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Add church error: " . $e->getMessage());
}
?>

<!-- Page Header with Back Button -->
<div class="page-header-simple">
    <a href="<?php echo url('dashboard/my-churches.php'); ?>" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"></path>
        </svg>
        <?php _e('back_to_churches'); ?>
    </a>
    <h1><?php _e('add_new_church'); ?></h1>
    <p><?php _e('add_church_desc'); ?></p>
</div>

<form id="churchForm" class="content-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <div class="form-section">
        <h3><?php _e('basic_information'); ?></h3>

        <div class="form-group">
            <label class="form-label"><?php _e('church_name'); ?> <span class="required">*</span></label>
            <input type="text" name="name" class="form-input" required
                placeholder="<?php _e('church_name_placeholder'); ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('denomination'); ?></label>
                <select name="denomination_id" class="form-select">
                    <option value=""><?php _e('select_denomination'); ?></option>
                    <?php foreach ($denominations as $denom): ?>
                        <option value="<?php echo $denom['id']; ?>"><?php echo htmlspecialchars($denom['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('state'); ?> <span class="required">*</span></label>
                <select name="state_id" class="form-select" required>
                    <option value=""><?php _e('select_state'); ?></option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('city'); ?></label>
                <input type="text" name="city" class="form-input" placeholder="<?php _e('city_placeholder'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('postcode'); ?></label>
                <input type="text" name="postcode" class="form-input"
                    placeholder="<?php _e('postcode_placeholder'); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label"><?php _e('address'); ?></label>
            <textarea name="address" class="form-textarea" rows="2"
                placeholder="<?php _e('address_placeholder'); ?>"></textarea>
        </div>

        <div class="form-group">
            <label class="form-label"><?php _e('description'); ?></label>
            <textarea name="description" class="form-textarea" rows="4"
                placeholder="<?php _e('church_description_placeholder'); ?>"></textarea>
        </div>
    </div>

    <div class="form-section">
        <h3><?php _e('church_photo'); ?></h3>

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
            <p class="form-hint"><?php _e('church_photo_hint'); ?></p>
        </div>
    </div>

    <div class="form-section">
        <h3><?php _e('contact_information'); ?></h3>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('phone'); ?></label>
                <input type="text" name="phone" class="form-input" placeholder="<?php _e('phone_placeholder'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?php _e('email'); ?></label>
                <input type="email" name="email" class="form-input" placeholder="<?php _e('email_placeholder'); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?php _e('website'); ?></label>
                <input type="url" name="website" class="form-input" placeholder="https://www.church.org">
            </div>
            <div class="form-group">
                <label class="form-label">Facebook</label>
                <input type="text" name="facebook" class="form-input" placeholder="mychurch">
                <p class="form-hint"><?php _e('facebook_hint'); ?></p>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Instagram</label>
                <input type="text" name="instagram" class="form-input" placeholder="mychurch">
                <p class="form-hint"><?php _e('instagram_hint'); ?></p>
            </div>
            <div class="form-group">
                <label class="form-label">YouTube</label>
                <input type="text" name="youtube" class="form-input" placeholder="mychurch">
                <p class="form-hint"><?php _e('youtube_hint'); ?></p>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3><?php _e('service_times'); ?></h3>

        <div class="form-group">
            <label class="form-label"><?php _e('weekly_services'); ?></label>
            <textarea name="service_times" class="form-textarea" rows="3"
                placeholder="<?php _e('service_times_long_placeholder'); ?>"></textarea>
            <p class="form-hint"><?php _e('service_times_hint'); ?></p>
        </div>

        <div class="form-group">
            <label class="form-label"><?php _e('service_languages'); ?></label>
            <p class="form-hint" style="margin-top: 0; margin-bottom: 8px;"><?php _e('service_languages_hint'); ?></p>
            <div class="checkbox-grid">
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="bm">
                    <span class="checkbox-text"><?php _e('service_lang_bm'); ?></span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="en">
                    <span class="checkbox-text"><?php _e('service_lang_en'); ?></span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="chinese">
                    <span class="checkbox-text"><?php _e('service_lang_chinese'); ?></span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="tamil">
                    <span class="checkbox-text"><?php _e('service_lang_tamil'); ?></span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="other">
                    <span class="checkbox-text"><?php _e('service_lang_other'); ?></span>
                </label>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?php echo url('dashboard/my-churches.php'); ?>" class="btn btn-secondary"><?php _e('cancel'); ?></a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            <?php _e('save_church'); ?>
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
        background: var(--color-primary-bg, #f5f3ff);
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

    const translations = {
        churchAdded: <?php echo json_encode(__('church_added_success')); ?>,
        addFailed: <?php echo json_encode(__('church_save_failed')); ?>,
        errorOccurred: <?php echo json_encode(__('dash_error_occurred')); ?>,
        saving: <?php echo json_encode(__('saving')); ?>,
        saveChurch: <?php echo json_encode(__('save_church')); ?>
    };

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
    document.getElementById('churchForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span>' + translations.saving + '</span>';

        try {
            const formData = new FormData(this);

            // DEBUG: Log form data
            debugLog('=== ADD CHURCH DEBUG ===');
            debugLog('Sending form data:');
            for (let [key, value] of formData.entries()) {
                debugLog(key + ':', value);
            }

            const response = await fetch(basePath + 'api/user/churches.php', {
                method: 'POST',
                body: formData
            });

            // DEBUG: Log raw response
            const responseText = await response.text();
            debugLog('Raw API response:', responseText);
            debugLog('Response status:', response.status);

            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                debugLog('JSON parse error:', parseError);
                debugLog('Response was:', responseText);
                showToast('Server error: ' + responseText.substring(0, 100), 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> ' + translations.saveChurch;
                return;
            }

            if (data.success) {
                showToast(translations.churchAdded, 'success');
                setTimeout(() => {
                    window.location.href = basePath + 'dashboard/my-churches.php';
                }, 1000);
            } else {
                debugLog('API returned error:', data.error);
                showToast(data.error || translations.addFailed, 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> ' + translations.saveChurch;
            }
        } catch (error) {
            debugLog('Fetch error:', error);
            showToast(translations.errorOccurred, 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> ' + translations.saveChurch;
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