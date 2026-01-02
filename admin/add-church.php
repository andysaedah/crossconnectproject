<?php
/**
 * CrossConnect MY - Add New Church
 */

$currentPage = 'churches';
$pageTitle = 'Add New Church';

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
    error_log("Add church error: " . $e->getMessage());
}
?>

<!-- Page Header with Back Button -->
<div class="page-header-simple">
    <a href="<?php echo url('admin/churches.php'); ?>" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"></path>
        </svg>
        Back to Churches
    </a>
    <h1>Add New Church</h1>
    <p>Fill in the details below to add a new church to the directory.</p>
</div>

<form id="churchForm" class="content-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

    <div class="form-section">
        <h3>Basic Information</h3>

        <div class="form-group">
            <label class="form-label">Church Name <span class="required">*</span></label>
            <input type="text" name="name" class="form-input" required placeholder="e.g., Grace Assembly Church">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Denomination</label>
                <select name="denomination_id" class="form-select">
                    <option value="">Select Denomination</option>
                    <?php foreach ($denominations as $denom): ?>
                        <option value="<?php echo $denom['id']; ?>"><?php echo htmlspecialchars($denom['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">State <span class="required">*</span></label>
                <select name="state_id" class="form-select" required>
                    <option value="">Select State</option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-input" placeholder="e.g., Kuala Lumpur">
            </div>
            <div class="form-group">
                <label class="form-label">Postcode</label>
                <input type="text" name="postcode" class="form-input" placeholder="e.g., 50450">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Full Address</label>
            <textarea name="address" class="form-textarea" rows="2"
                placeholder="Street address, building name, etc."></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" rows="4"
                placeholder="Brief description of the church, its mission, and community..."></textarea>
        </div>
    </div>

    <div class="form-section">
        <h3>Church Photo</h3>

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
            <p class="form-hint">Upload a photo of the church building or logo</p>
        </div>
    </div>

    <div class="form-section">
        <h3>Contact Information</h3>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-input" placeholder="e.g., 03-1234 5678">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" placeholder="e.g., info@church.org">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Website</label>
                <input type="url" name="website" class="form-input" placeholder="https://www.church.org">
            </div>
            <div class="form-group">
                <label class="form-label">Facebook</label>
                <input type="text" name="facebook" class="form-input" placeholder="mychurch">
                <p class="form-hint">Just the username, not the full URL</p>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Instagram</label>
                <input type="text" name="instagram" class="form-input" placeholder="mychurch">
                <p class="form-hint">Just the username without @</p>
            </div>
            <div class="form-group">
                <label class="form-label">YouTube</label>
                <input type="text" name="youtube" class="form-input" placeholder="mychurch">
                <p class="form-hint">Channel handle without @</p>
            </div>
        </div>

        <!-- Twitter hidden for now
        <div class="form-group">
            <label class="form-label">Twitter / X</label>
            <input type="text" name="twitter" class="form-input" placeholder="@mychurch">
        </div>
        -->
    </div>

    <div class="form-section">
        <h3>Service Times</h3>

        <div class="form-group">
            <label class="form-label">Weekly Services</label>
            <textarea name="service_times" class="form-textarea" rows="3"
                placeholder="Sunday: 9:00 AM, 11:00 AM&#10;Wednesday: 7:30 PM (Prayer Meeting)"></textarea>
            <p class="form-hint">List your regular service times, one per line</p>
        </div>

        <div class="form-group">
            <label class="form-label">Service Languages</label>
            <p class="form-hint" style="margin-top: 0; margin-bottom: 8px;">Select the languages used in services</p>
            <div class="checkbox-grid">
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="bm">
                    <span class="checkbox-text">BM Service</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="en">
                    <span class="checkbox-text">English Service</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="chinese">
                    <span class="checkbox-text">Chinese Service</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="tamil">
                    <span class="checkbox-text">Tamil Service</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="service_languages[]" value="other">
                    <span class="checkbox-text">Other Languages</span>
                </label>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?php echo url('admin/churches.php'); ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            Save Church
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
        btn.innerHTML = '<span>Saving...</span>';

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
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Save Church';
                return;
            }

            if (data.success) {
                showToast('Church added successfully!', 'success');
                setTimeout(() => {
                    window.location.href = basePath + 'admin/churches.php';
                }, 1000);
            } else {
                debugLog('API returned error:', data.error);
                showToast(data.error || 'Failed to add church', 'error');
                btn.disabled = false;
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Save Church';
            }
        } catch (error) {
            debugLog('Fetch error:', error);
            showToast('An error occurred', 'error');
            btn.disabled = false;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> Save Church';
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