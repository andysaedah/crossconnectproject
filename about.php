<?php
/**
 * CrossConnect MY - About Page
 * About the church directory platform
 */

require_once 'config/language.php';

$pageTitle = __('about_us');
$pageDescription = __('about_hero_text');

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="about-hero-content">
        <span class="about-label"><?php _e('about_label'); ?></span>
        <h1>CrossConnect <span class="accent">MY</span></h1>
        <p class="about-hero-text"><?php _e('about_hero_text'); ?></p>
    </div>
</section>

<!-- About Content -->
<section class="about-content">
    <div class="about-content-inner">

        <div class="about-block">
            <h2><?php _e('connecting_communities'); ?></h2>
            <p><?php _e('connecting_communities_p1'); ?></p>
            <p><?php _e('connecting_communities_p2'); ?></p>
        </div>

        <div class="about-divider"></div>

        <div class="about-features">
            <div class="about-feature">
                <div class="about-feature-number">01</div>
                <h3><?php _e('for_seekers'); ?></h3>
                <p><?php _e('for_seekers_desc'); ?></p>
            </div>
            <div class="about-feature">
                <div class="about-feature-number">02</div>
                <h3><?php _e('for_churches'); ?></h3>
                <p><?php _e('for_churches_desc'); ?></p>
            </div>
            <div class="about-feature">
                <div class="about-feature-number">03</div>
                <h3><?php _e('for_events'); ?></h3>
                <p><?php _e('for_events_desc'); ?></p>
            </div>
        </div>

        <div class="about-divider"></div>

        <div class="about-block center">
            <h2><?php _e('free_and_open'); ?></h2>
            <p><?php _e('free_and_open_desc'); ?></p>
        </div>

        <!-- Scripture Quote -->
        <div style="margin-top: 32px; padding: 0 16px;">
            <div class="scripture-card"
                style="max-width: 700px; margin: 0 auto; background: white; border-radius: 16px; padding: 32px 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid rgba(8, 145, 178, 0.1); position: relative; overflow: hidden;">
                <!-- Decorative accent bar -->
                <div
                    style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #0891b2, #06b6d4);">
                </div>

                <!-- Quote marks decoration -->
                <div
                    style="position: absolute; top: 16px; left: 20px; font-size: 4rem; color: rgba(8, 145, 178, 0.08); font-family: Georgia, serif; line-height: 1;">
                    "</div>

                <div style="text-align: center; position: relative; z-index: 1;">
                    <blockquote
                        style="font-size: 1.125rem; font-weight: 500; color: #374151; line-height: 1.9; margin: 0 0 20px; font-style: italic; padding: 0 16px;">
                        "<?php _e('scripture_acts_20_35'); ?>"
                    </blockquote>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <div
                            style="width: 28px; height: 2px; background: linear-gradient(90deg, transparent, #0891b2);">
                        </div>
                        <cite
                            style="font-size: 0.875rem; font-weight: 600; color: #0891b2; font-style: normal; letter-spacing: 0.5px;"><?php _e('scripture_acts_20_35_ref'); ?></cite>
                        <div
                            style="width: 28px; height: 2px; background: linear-gradient(90deg, #0891b2, transparent);">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Call to Action -->
<section class="about-cta">
    <div class="about-cta-content">
        <h3><?php _e('get_involved'); ?></h3>
        <p><?php _e('get_involved_desc'); ?></p>
        <a href="<?php echo url('contact.php'); ?>" class="cta-button">
            <?php _e('contact_us'); ?>
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M5 12H19M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>