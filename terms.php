<?php
/**
 * CrossConnect MY - Terms & Conditions Page
 */

require_once 'config/language.php';

$pageTitle = __('terms_title');
$pageDescription = __('terms_description');

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="about-label">
            <?php _e('legal'); ?>
        </span>
        <h1>
            <?php _e('terms_title'); ?>
        </h1>
        <p class="about-hero-text">
            <?php _e('terms_last_updated'); ?>: 1 January 2026
        </p>
    </div>
</section>

<!-- Terms Content -->
<section class="about-content">
    <div class="about-content-inner">

        <div class="about-block">
            <h2>1.
                <?php _e('terms_acceptance'); ?>
            </h2>
            <p>
                <?php _e('terms_acceptance_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>2.
                <?php _e('terms_use_of_service'); ?>
            </h2>
            <p>
                <?php _e('terms_use_of_service_text'); ?>
            </p>
            <ul class="terms-list">
                <li>
                    <?php _e('terms_use_item1'); ?>
                </li>
                <li>
                    <?php _e('terms_use_item2'); ?>
                </li>
                <li>
                    <?php _e('terms_use_item3'); ?>
                </li>
                <li>
                    <?php _e('terms_use_item4'); ?>
                </li>
            </ul>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>3.
                <?php _e('terms_user_content'); ?>
            </h2>
            <p>
                <?php _e('terms_user_content_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>4.
                <?php _e('terms_accuracy'); ?>
            </h2>
            <p>
                <?php _e('terms_accuracy_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>5.
                <?php _e('terms_privacy'); ?>
            </h2>
            <p>
                <?php _e('terms_privacy_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>6.
                <?php _e('terms_modifications'); ?>
            </h2>
            <p>
                <?php _e('terms_modifications_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>7.
                <?php _e('terms_disclaimer'); ?>
            </h2>
            <p>
                <?php _e('terms_disclaimer_text'); ?>
            </p>
        </div>

        <div class="about-divider"></div>

        <div class="about-block">
            <h2>8.
                <?php _e('terms_contact'); ?>
            </h2>
            <p>
                <?php _e('terms_contact_text'); ?> <a href="<?php echo url('contact.php'); ?>">
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