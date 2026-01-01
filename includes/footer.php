</main>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-brand">
                <a href="<?php echo url('/'); ?>" class="footer-logo">
                    <svg class="logo-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z" />
                    </svg>
                    <span>CrossConnect <span class="logo-accent">MY</span></span>
                </a>
                <p class="footer-tagline"><?php _e('site_tagline'); ?></p>
            </div>

            <div class="footer-links">
                <div class="footer-section">
                    <h4><?php _e('quick_links'); ?></h4>
                    <ul>
                        <li><a href="<?php echo url('/'); ?>"><?php _e('nav_home'); ?></a></li>
                        <li><a href="<?php echo url('state.php?s=kuala-lumpur'); ?>">Kuala Lumpur</a></li>
                        <li><a href="<?php echo url('state.php?s=selangor'); ?>">Selangor</a></li>
                        <li><a href="<?php echo url('state.php?s=penang'); ?>">Penang</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4><?php _e('denominations'); ?></h4>
                    <ul>
                        <li><a href="<?php echo url('denomination.php?d=assemblies-of-god'); ?>">Assemblies of God</a>
                        </li>
                        <li><a href="<?php echo url('denomination.php?d=methodist'); ?>">Methodist</a></li>
                        <li><a href="<?php echo url('denomination.php?d=pentecostal'); ?>">Pentecostal</a></li>
                        <li><a href="<?php echo url('denomination.php?d=baptist'); ?>">Baptist</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4><?php _e('nav_about'); ?></h4>
                    <ul>
                        <li><a href="<?php echo url('about.php'); ?>"><?php _e('nav_about'); ?></a></li>
                        <li><a href="<?php echo url('contact.php'); ?>"><?php _e('nav_contact'); ?></a></li>
                        <li><a href="<?php echo url('privacy.php'); ?>"><?php _e('privacy_policy'); ?></a></li>
                        <li><a href="<?php echo url('terms.php'); ?>"><?php _e('terms_conditions'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p><?php _e('copyright', ['year' => date('Y')]); ?></p>
            <p class="footer-credit"><?php _e('credit'); ?></p>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 19V5M5 12L12 5L19 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
    </svg>
</button>

<!-- Scripts -->
<script src="<?php echo asset('js/main.min.js'); ?>" defer></script>
</body>

</html>