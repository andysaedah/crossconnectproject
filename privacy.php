<?php
/**
 * CrossConnect MY - Privacy Policy Page
 */

require_once 'config/language.php';

$pageTitle = __('privacy_title');
$pageDescription = __('privacy_description');

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="about-label">
            <?php _e('legal'); ?>
        </span>
        <h1>
            <?php _e('privacy_title'); ?>
        </h1>
        <p class="about-hero-text">
            <?php _e('terms_last_updated'); ?>: 1 January 2026
        </p>
    </div>
</section>

<!-- Privacy Content -->
<section class="about-content">
    <div class="about-content-inner">

        <div class="about-block">
            <h2>1.
                <?php _e('privacy_intro'); ?>
            </h2>
            <p>
                <?php _e('privacy_intro_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>2.
                <?php _e('privacy_collect'); ?>
            </h2>
            <p>
                <?php _e('privacy_collect_text'); ?>
            </p>
            <ul class="terms-list">
                <li>
                    <?php _e('privacy_collect_item1'); ?>
                </li>
                <li>
                    <?php _e('privacy_collect_item2'); ?>
                </li>
                <li>
                    <?php _e('privacy_collect_item3'); ?>
                </li>
                <li>
                    <?php _e('privacy_collect_item4'); ?>
                </li>
            </ul>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>3.
                <?php _e('privacy_use'); ?>
            </h2>
            <p>
                <?php _e('privacy_use_text'); ?>
            </p>
            <ul class="terms-list">
                <li>
                    <?php _e('privacy_use_item1'); ?>
                </li>
                <li>
                    <?php _e('privacy_use_item2'); ?>
                </li>
                <li>
                    <?php _e('privacy_use_item3'); ?>
                </li>
                <li>
                    <?php _e('privacy_use_item4'); ?>
                </li>
            </ul>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>4.
                <?php _e('privacy_sharing'); ?>
            </h2>
            <p>
                <?php _e('privacy_sharing_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>5.
                <?php _e('privacy_cookies'); ?>
            </h2>
            <p>
                <?php _e('privacy_cookies_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>6.
                <?php _e('privacy_security'); ?>
            </h2>
            <p>
                <?php _e('privacy_security_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>7.
                <?php _e('privacy_rights'); ?>
            </h2>
            <p>
                <?php _e('privacy_rights_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>8.
                <?php _e('privacy_contact'); ?>
            </h2>
            <p>
                <?php _e('privacy_contact_text'); ?> <a href="<?php echo url('contact.php'); ?>">
                    <?php _e('contact_page'); ?>
                </a>.
            </p>
        </div>

    </div>
</section>

<style>
    .terms-list {
        margin: 1rem 0;
        padding-left: 1.5rem;
    }

    .terms-list li {
        margin-bottom: 0.5rem;
        color: var(--text-secondary, #666);
        line-height: 1.6;
    }
</style>

<?php require_once 'includes/footer.php'; ?>