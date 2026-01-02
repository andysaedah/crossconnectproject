<?php
/**
 * CrossConnect MY - Login & Register Page
 * Clean, accessible login following UI/UX best practices
 */

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/auth.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ' . url('admin/'));
    } else {
        header('Location: ' . url('dashboard/'));
    }
    exit;
}

$pageTitle = __('login_title') !== 'login_title' ? __('login_title') : 'Login';
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage() === 'bm' ? 'ms' : 'en'; ?>" translate="no" class="notranslate">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - CrossConnect MY</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/svg+xml" href="<?php echo asset('images/favicon.svg'); ?>">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #0891b2;
            --primary-dark: #0e7490;
            --primary-light: #22d3ee;
            --primary-bg: #ecfeff;
            --text: #1e293b;
            --text-light: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --bg: #f8fafc;
            --white: #ffffff;
            --danger: #ef4444;
            --success: #10b981;
            --radius: 0.5rem;
            --radius-lg: 0.75rem;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        html {
            font-size: 16px;
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: var(--font);
            background: linear-gradient(135deg, #ecfeff 0%, #f0f9ff 50%, #e0f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            line-height: 1.5;
            color: var(--text);
        }

        .auth-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .auth-card {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            overflow: hidden;
        }

        /* Header */
        .auth-header {
            text-align: center;
            padding: 2rem 2rem 1.5rem;
            background: linear-gradient(135deg, rgba(8, 145, 178, 0.05) 0%, rgba(124, 58, 237, 0.05) 100%);
            border-bottom: 1px solid var(--border);
        }

        .auth-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius-lg);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 14px rgba(8, 145, 178, 0.4);
        }

        .auth-logo i {
            font-size: 1.5rem;
            color: var(--white);
        }

        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .auth-subtitle {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        /* Tabs */
        .auth-tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
        }

        .auth-tab {
            flex: 1;
            padding: 1rem;
            font-family: var(--font);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-light);
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .auth-tab:hover {
            color: var(--text);
            background: var(--bg);
        }

        .auth-tab.active {
            color: var(--primary);
        }

        .auth-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary);
        }

        /* Form Container */
        .auth-body {
            padding: 1.5rem 2rem 2rem;
        }

        .auth-form {
            display: none;
        }

        .auth-form.active {
            display: block;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.875rem;
            pointer-events: none;
            transition: color 0.2s;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 0.875rem 0.75rem 2.5rem;
            font-family: var(--font);
            font-size: 0.9375rem;
            color: var(--text);
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
        }

        .form-input:focus+.input-icon {
            color: var(--primary);
        }

        .form-input.has-toggle {
            padding-right: 2.75rem;
        }

        .input-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            font-size: 0.875rem;
        }

        .input-toggle:hover {
            color: var(--text-light);
        }

        .form-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.375rem;
        }

        /* Remember & Forgot Row */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--text-light);
            cursor: pointer;
        }

        .checkbox-label input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--primary);
        }

        .forgot-link {
            font-size: 0.8125rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 0.875rem 1.5rem;
            font-family: var(--font);
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--white);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.35);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-submit .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--white);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .btn-submit.loading .btn-text,
        .btn-submit.loading i:not(.spinner) {
            display: none;
        }

        .btn-submit.loading .spinner {
            display: block;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Footer */
        .auth-footer {
            text-align: center;
            padding: 1.25rem 2rem;
            background: var(--bg);
            border-top: 1px solid var(--border);
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .auth-footer .copyright {
            margin-top: 0.75rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .auth-footer .copyright strong {
            color: var(--primary);
        }

        /* Language Switcher */
        .lang-switcher {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.25rem;
        }

        .lang-switcher a {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }

        .lang-switcher a:hover,
        .lang-switcher a.active {
            background: var(--white);
            color: var(--primary);
            box-shadow: var(--shadow);
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            padding: 0.875rem 1.25rem;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--white);
            box-shadow: var(--shadow-lg);
            transform: translateX(calc(100% + 2rem));
            transition: transform 0.3s ease;
            z-index: 1000;
            max-width: 320px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--danger);
        }

        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }

            .auth-header {
                padding: 1.5rem 1.5rem 1.25rem;
            }

            .auth-body {
                padding: 1.25rem 1.5rem 1.5rem;
            }

            .auth-footer {
                padding: 1rem 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <!-- Header -->
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-fire"></i>
                </div>
                <h1 class="auth-title">CrossConnect MY</h1>
                <p class="auth-subtitle"><?php _e('auth_subtitle'); ?></p>
            </div>

            <div class="auth-tabs">
                <button type="button" class="auth-tab <?php echo $activeTab === 'login' ? 'active' : ''; ?>"
                    data-tab="login">
                    <?php _e('auth_sign_in'); ?>
                </button>
                <button type="button" class="auth-tab <?php echo $activeTab === 'register' ? 'active' : ''; ?>"
                    data-tab="register">
                    <?php _e('auth_create_account'); ?>
                </button>
            </div>

            <!-- Form Body -->
            <div class="auth-body">
                <?php if (isset($_GET['timeout'])): ?>
                    <div
                        style="background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.875rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-clock"></i>
                        <span><?php _e('session_timeout_message'); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form id="loginForm" class="auth-form <?php echo $activeTab === 'login' ? 'active' : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <div class="form-group">
                        <label class="form-label" for="login-email"><?php _e('auth_email_or_username'); ?></label>
                        <div class="input-wrapper">
                            <input type="text" id="login-email" name="email" class="form-input"
                                placeholder="<?php _e('auth_email_placeholder'); ?>" required autocomplete="username">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="login-password"><?php _e('auth_password'); ?></label>
                        <div class="input-wrapper">
                            <input type="password" id="login-password" name="password" class="form-input has-toggle"
                                placeholder="<?php _e('auth_password_placeholder'); ?>" required
                                autocomplete="current-password">
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="input-toggle" onclick="togglePassword('login-password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <?php _e('auth_remember_me'); ?>
                        </label>
                        <a href="<?php echo url('auth/forgot-password.php'); ?>"
                            class="forgot-link"><?php _e('auth_forgot_password'); ?></a>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span class="btn-text"><?php _e('auth_sign_in'); ?></span>
                        <span class="spinner"></span>
                    </button>
                </form>

                <!-- Register Form -->
                <form id="registerForm" class="auth-form <?php echo $activeTab === 'register' ? 'active' : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <!-- Honeypot field for bot detection - hidden from users -->
                    <div style="position: absolute; left: -9999px;" aria-hidden="true">
                        <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-name"><?php _e('auth_full_name'); ?></label>
                        <div class="input-wrapper">
                            <input type="text" id="reg-name" name="name" class="form-input"
                                placeholder="<?php _e('auth_full_name_placeholder'); ?>" required>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-username"><?php _e('auth_username'); ?></label>
                        <div class="input-wrapper">
                            <input type="text" id="reg-username" name="username" class="form-input"
                                placeholder="<?php _e('auth_username_placeholder'); ?>" required pattern="[a-zA-Z0-9_]+"
                                minlength="3">
                            <i class="fas fa-at input-icon"></i>
                        </div>
                        <div class="form-hint"><?php _e('auth_username_hint'); ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-email"><?php _e('auth_email'); ?></label>
                        <div class="input-wrapper">
                            <input type="email" id="reg-email" name="email" class="form-input"
                                placeholder="<?php _e('auth_email_placeholder_reg'); ?>" required>
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-password"><?php _e('auth_password'); ?></label>
                        <div class="input-wrapper">
                            <input type="password" id="reg-password" name="password" class="form-input has-toggle"
                                placeholder="<?php _e('auth_password_create'); ?>" required minlength="8">
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="input-toggle" onclick="togglePassword('reg-password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-hint"><?php _e('auth_password_min_hint'); ?></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-confirm"><?php _e('auth_confirm_password'); ?></label>
                        <div class="input-wrapper">
                            <input type="password" id="reg-confirm" name="password_confirm" class="form-input"
                                placeholder="<?php _e('auth_confirm_placeholder'); ?>" required>
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-church"><?php _e('auth_church_name'); ?> <span
                                style="font-weight: 400; color: var(--text-muted);"><?php _e('auth_church_optional'); ?></span></label>
                        <div class="input-wrapper">
                            <input type="text" id="reg-church" name="church_name" class="form-input"
                                placeholder="<?php _e('auth_church_placeholder'); ?>">
                            <i class="fas fa-church input-icon"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span class="btn-text"><?php _e('auth_create_account'); ?></span>
                        <span class="spinner"></span>
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="auth-footer">
                <a href="<?php echo url('/'); ?>"><i class="fas fa-arrow-left"></i> <?php _e('back_to_home'); ?></a>
                <div class="copyright">
                    © <?php echo date('Y'); ?> CrossConnect MY · <strong><?php _e('auth_powered_by'); ?></strong>
                </div>
            </div>
        </div>

        <!-- Language Switcher -->
        <div class="lang-switcher">
            <a href="<?php echo getLanguageSwitchUrl('en'); ?>"
                class="<?php echo isCurrentLanguage('en') ? 'active' : ''; ?>">English</a>
            <a href="<?php echo getLanguageSwitchUrl('bm'); ?>"
                class="<?php echo isCurrentLanguage('bm') ? 'active' : ''; ?>">Bahasa Malaysia</a>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast"></div>

    <script>
        const basePath = '<?php echo getBasePath(); ?>';
        const authTranslations = {
            loginSuccess: '<?php echo addslashes(__('auth_login_success')); ?>',
            loginFailed: '<?php echo addslashes(__('auth_login_failed')); ?>',
            errorOccurred: '<?php echo addslashes(__('auth_error_occurred')); ?>',
            accountCreated: '<?php echo addslashes(__('auth_account_created')); ?>',
            registrationFailed: '<?php echo addslashes(__('auth_registration_failed')); ?>',
            passwordsNoMatch: '<?php echo addslashes(__('auth_passwords_no_match')); ?>'
        };

        // Tab switching
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab + 'Form').classList.add('active');
            });
        });

        // Password toggle
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => toast.classList.remove('show'), 4000);
        }

        // Login form
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('.btn-submit');
            btn.classList.add('loading');
            btn.disabled = true;

            try {
                const formData = new FormData(form);
                const response = await fetch(basePath + 'api/auth/login.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(authTranslations.loginSuccess, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || basePath + 'dashboard/';
                    }, 800);
                } else {
                    showToast(data.error || authTranslations.loginFailed, 'error');
                    btn.classList.remove('loading');
                    btn.disabled = false;
                }
            } catch (error) {
                showToast(authTranslations.errorOccurred, 'error');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });

        // Register form
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('.btn-submit');

            const password = form.querySelector('[name="password"]').value;
            const confirm = form.querySelector('[name="password_confirm"]').value;
            if (password !== confirm) {
                showToast(authTranslations.passwordsNoMatch, 'error');
                return;
            }

            btn.classList.add('loading');
            btn.disabled = true;

            try {
                const formData = new FormData(form);
                const response = await fetch(basePath + 'api/auth/register.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast(authTranslations.accountCreated, 'success');
                    setTimeout(() => {
                        window.location.href = basePath + 'auth/verify-pending.php';
                    }, 1500);
                } else {
                    showToast(data.error || authTranslations.registrationFailed, 'error');
                    btn.classList.remove('loading');
                    btn.disabled = false;
                }
            } catch (error) {
                showToast(authTranslations.errorOccurred, 'error');
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });
    </script>
</body>

</html>