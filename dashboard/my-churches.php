<?php
/**
 * CrossConnect MY - My Churches Page
 * User can view, add, edit, delete their own churches
 */

require_once __DIR__ . '/../config/language.php';

$currentPage = 'my-churches';
$pageTitle = __('dash_my_churches');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/dashboard-header.php';

$user = getCurrentUser();

// Get user's churches
$churches = [];
$pdo = getDbConnection();
try {
    $stmt = $pdo->prepare("
        SELECT c.*, s.name as state_name, d.name as denomination_name
        FROM churches c
        LEFT JOIN states s ON c.state_id = s.id
        LEFT JOIN denominations d ON c.denomination_id = d.id
        WHERE c.created_by = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $churches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get states and denominations for edit modal
    $states = $pdo->query("SELECT id, name FROM states ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $denominations = $pdo->query("SELECT id, name FROM denominations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("My churches error: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-info">
        <h2><?php _e('dash_my_churches'); ?></h2>
        <p><?php echo count($churches); ?> <?php _e('churches'); ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?php echo url('dashboard/add-church.php'); ?>" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span><?php _e('add_church'); ?></span>
        </a>
    </div>
</div>

<?php if (empty($churches)): ?>
    <!-- Empty State -->
    <div class="dashboard-card">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M18 21H6V12L3 12L12 3L21 12L18 12V21Z"></path>
                <path d="M9 21V15H15V21"></path>
            </svg>
            <h3><?php _e('no_churches_yet'); ?></h3>
            <p><?php _e('no_churches_yet_desc'); ?></p>
            <a href="<?php echo url('dashboard/add-church.php'); ?>" class="btn btn-primary"><?php _e('add_church'); ?></a>
        </div>
    </div>
<?php else: ?>
    <!-- Churches List -->
    <div class="dashboard-card">
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php _e('church'); ?></th>
                            <th class="hide-mobile"><?php _e('location'); ?></th>
                            <th class="hide-mobile"><?php _e('denomination'); ?></th>
                            <th><?php _e('status'); ?></th>
                            <th><?php _e('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($churches as $church): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($church['name']); ?></div>
                                    <div class="show-mobile-only"
                                        style="font-size: 0.75rem; color: var(--color-text-light); margin-top: 2px;">
                                        <?php echo htmlspecialchars($church['state_name'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="hide-mobile"><?php echo htmlspecialchars($church['state_name'] ?? '-'); ?></td>
                                <td class="hide-mobile"><?php echo htmlspecialchars($church['denomination_name'] ?? '-'); ?>
                                </td>
                                <td>
                                    <span class="badge <?php
                                    echo $church['status'] === 'active' ? 'badge-success' :
                                        ($church['status'] === 'pending' ? 'badge-warning' : 'badge-danger');
                                    ?>">
                                        <?php
                                        $statusKey = $church['status'] ?? 'pending';
                                        _e($statusKey);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="<?php echo url('church.php?slug=' . $church['slug']); ?>" class="action-btn"
                                            title="<?php _e('view'); ?>" target="_blank">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                        <button class="action-btn edit" onclick="editChurch(<?php echo $church['id']; ?>)"
                                            title="<?php _e('dash_edit'); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <button class="action-btn delete"
                                            onclick="deleteChurch(<?php echo $church['id']; ?>, '<?php echo htmlspecialchars(addslashes($church['name'])); ?>')"
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
<div class="modal" id="churchModal">
    <div class="modal-header">
        <h3 id="modalTitle"><?php _e('edit_church'); ?></h3>
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
            <div class="form-group">
                <label class="form-label"><?php _e('church_name'); ?> *</label>
                <input type="text" name="name" id="churchName" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('state'); ?> *</label>
                    <select name="state_id" id="churchState" class="form-select" required>
                        <option value=""><?php _e('select_state'); ?></option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>"><?php echo htmlspecialchars($state['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('denomination'); ?></label>
                    <select name="denomination_id" id="churchDenomination" class="form-select">
                        <option value=""><?php _e('select_denomination'); ?></option>
                        <?php foreach ($denominations as $denom): ?>
                            <option value="<?php echo $denom['id']; ?>"><?php echo htmlspecialchars($denom['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('city'); ?></label>
                    <input type="text" name="city" id="churchCity" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('phone'); ?></label>
                    <input type="text" name="phone" id="churchPhone" class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?php _e('address'); ?></label>
                <textarea name="address" id="churchAddress" class="form-textarea" rows="2"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?php _e('email'); ?></label>
                    <input type="email" name="email" id="churchEmail" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php _e('website'); ?></label>
                    <input type="url" name="website" id="churchWebsite" class="form-input" placeholder="https://">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Facebook</label>
                    <input type="text" name="facebook" id="churchFacebook" class="form-input" placeholder="mychurch">
                    <p class="form-hint"><?php _e('facebook_hint'); ?></p>
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram</label>
                    <input type="text" name="instagram" id="churchInstagram" class="form-input" placeholder="mychurch">
                    <p class="form-hint"><?php _e('instagram_hint'); ?></p>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">YouTube</label>
                <input type="text" name="youtube" id="churchYoutube" class="form-input" placeholder="mychurch">
                <p class="form-hint"><?php _e('youtube_hint'); ?></p>
            </div>

            <div class="form-group">
                <label class="form-label"><?php _e('description'); ?></label>
                <textarea name="description" id="churchDescription" class="form-textarea" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label"><?php _e('service_times'); ?></label>
                <textarea name="service_times" id="churchServiceTimes" class="form-textarea" rows="2"
                    placeholder="<?php _e('service_times_placeholder'); ?>"></textarea>
            </div>

            <!-- Service Languages Checkboxes -->
            <div class="form-group">
                <label class="form-label"><?php _e('service_languages'); ?></label>
                <p class="form-hint" style="margin-top: 0; margin-bottom: 8px;"><?php _e('service_languages_hint'); ?>
                </p>
                <div class="checkbox-grid">
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="bm" id="langBm">
                        <span class="checkbox-text"><?php _e('service_lang_bm'); ?></span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="en" id="langEn">
                        <span class="checkbox-text"><?php _e('service_lang_en'); ?></span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="chinese" id="langChinese">
                        <span class="checkbox-text"><?php _e('service_lang_chinese'); ?></span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="tamil" id="langTamil">
                        <span class="checkbox-text"><?php _e('service_lang_tamil'); ?></span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="service_languages[]" value="other" id="langOther">
                        <span class="checkbox-text"><?php _e('service_lang_other'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Image Upload -->
            <div class="form-group">
                <label class="form-label"><?php _e('church_photo'); ?></label>
                <div class="image-upload-edit" id="imageUploadContainer">
                    <div class="current-image-preview" id="currentImagePreview" style="display: none;">
                        <img id="currentImageImg" src="" alt="Current image">
                        <div class="image-overlay">
                            <span><?php _e('click_to_change'); ?></span>
                        </div>
                    </div>
                    <div class="upload-placeholder" id="uploadPlaceholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <span><?php _e('click_to_upload'); ?></span>
                    </div>
                    <input type="file" name="photo" id="churchPhoto" accept="image/*" hidden>
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
    const churchesData = <?php echo json_encode($churches); ?>;
    const translations = {
        editChurch: <?php echo json_encode(__('edit_church')); ?>,
        saveChanges: <?php echo json_encode(__('save_changes')); ?>,
        saving: <?php echo json_encode(__('saving')); ?>,
        churchSaved: <?php echo json_encode(__('church_saved')); ?>,
        churchSaveFailed: <?php echo json_encode(__('church_save_failed')); ?>,
        churchDeleted: <?php echo json_encode(__('church_deleted')); ?>,
        churchDeleteFailed: <?php echo json_encode(__('church_delete_failed')); ?>,
        confirmDelete: <?php echo json_encode(__('confirm_delete_church')); ?>,
        errorOccurred: <?php echo json_encode(__('dash_error_occurred')); ?>
    };

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('show');
        document.getElementById('churchModal').classList.remove('show');
    }

    function editChurch(id) {
        const church = churchesData.find(c => c.id == id);
        if (!church) return;

        document.getElementById('modalTitle').textContent = translations.editChurch;
        document.getElementById('churchId').value = id;
        document.getElementById('churchName').value = church.name || '';
        document.getElementById('churchState').value = church.state_id || '';
        document.getElementById('churchDenomination').value = church.denomination_id || '';
        document.getElementById('churchCity').value = church.city || '';
        document.getElementById('churchPhone').value = church.phone || '';
        document.getElementById('churchAddress').value = church.address || '';
        document.getElementById('churchEmail').value = church.email || '';
        document.getElementById('churchWebsite').value = church.website || '';
        document.getElementById('churchFacebook').value = church.facebook || '';
        document.getElementById('churchInstagram').value = church.instagram || '';
        document.getElementById('churchYoutube').value = church.youtube || '';
        document.getElementById('churchDescription').value = church.description || '';
        document.getElementById('churchServiceTimes').value = church.service_times || '';

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

    async function saveChurch(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = translations.saving;

        try {
            const formData = new FormData(form);
            const response = await fetch(basePath + 'api/user/churches.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message || translations.churchSaved, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || translations.churchSaveFailed, 'error');
                btn.disabled = false;
                btn.textContent = translations.saveChanges;
            }
        } catch (error) {
            showToast(translations.errorOccurred, 'error');
            btn.disabled = false;
            btn.textContent = translations.saveChanges;
        }
    }

    async function deleteChurch(id, name) {
        const confirmMsg = translations.confirmDelete.replace('{name}', name);
        if (!confirm(confirmMsg)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'delete');
            formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');

            const response = await fetch(basePath + 'api/user/churches.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(translations.churchDeleted, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || translations.churchDeleteFailed, 'error');
            }
        } catch (error) {
            showToast(translations.errorOccurred, 'error');
        }
    }

    // Close modal on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    // Auto-open edit modal if ?edit= parameter is present
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const editId = urlParams.get('edit');
        if (editId) {
            // Small delay to ensure page is fully loaded
            setTimeout(() => {
                editChurch(parseInt(editId));
            }, 300);
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>