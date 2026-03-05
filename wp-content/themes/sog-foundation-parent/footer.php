                <?php
                // If Single or Archive (Category, Tag, Author or a Date based page).
                if (is_single() || is_archive()) : ?>
                        </div><!-- /.col -->

                        <?php get_sidebar(); ?>
                    </div><!-- /.row -->
                <?php endif; ?>
            </main><!-- /#main -->

            <footer id="footer" class="main-site-footer">
                <div class="container">
                    <div class="row border-bottom-dashed">
                        <div class="col-xs-12 col-sm-12 sog-logo">
                            <a href="/" title="School of Government, University of North Carolina at Chapel Hill">
                                <img src="<?php echo get_template_directory_uri(); ?>/images/source_SOG_sig_Blue_White_h.png" alt="UNC SOG Logo" class="img-responsive">
                            </a>
                            <span class="site-name">
                                <?php
                                // Add a setting for the site name in the Customizer and display it here. If no custom site name is set, fall back to the default blog name.
                                $custom_site_name = get_theme_mod('custom_site_name'); // Get custom site name from Customizer

                                if (!empty($custom_site_name)) {
                                    echo esc_html($custom_site_name); // Display custom site name
                                } else {
                                    // Fall back default display nothing if no custom site name is set, or you can choose to display the default blog name instead.
                                } ?>
                            </span>
                        </div>
                    </div>

                    <div class="row border-bottom-solid">
                        <div class="col-xs-12 col-sm-12 col-md-7 address-container">
                            <div class="address text-left">
                                UNC School of Government<br/>
                                400 South Road<br/>
                                Knapp-Sanders Building, CB 3330<br/>
                                Chapel Hill, NC 27599-3330<br/>
                                T: 919.966.5381
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-12 col-md-5 footer-navigation-container">
                            <div class="row">
                                <?php
                                if (has_nav_menu('footer-menu')) : // See function register_nav_menus() in functions.php
                                    /*
                                    Loading WordPress Custom Menu (theme_location) ... remove <div> <ul> containers and show only <li> items!!!
                                    Menu name taken from functions.php!!! ... register_nav_menu( 'footer-menu', 'Footer Menu' );
                                    !!! IMPORTANT: After adding all pages to the menu, don't forget to assign this menu to the Footer menu of "Theme locations" /wp-admin/nav-menus.php (on left side) ... Otherwise the themes will not know, which menu to use!!!
                                    */
                                    wp_nav_menu(
                                        [
                                            'container' => 'nav',
                                            'container_class' => 'col-xs-12 col-sm-12 col-md-6 text-left footer-nav',
                                            //'fallback_cb'     => 'WP_Bootstrap4_Navwalker_Footer::fallback',
                                            'walker' => new WP_Bootstrap4_Navwalker_Footer(),
                                            'theme_location' => 'footer-menu',
                                            'items_wrap' => '<ul class="menu nav justify-content-end">%3$s</ul>',
                                        ]
                                    );
                                endif;
                                ?>

                                <div class="col-xs-12 col-sm-12 col-md-6 text-left footer-nav footer-nav-employees text-right">
                                    <h2 class="footer-header text-uppercase">For Employees</h2>

                                    <ul class="list-unstyled" role="list">
                                        <li role="listitem sso-login-link">
                                            <a href="/wp-login.php" title="">Employee Login</a>
                                        </li>
                                        <li role="listitem gladys-link">
                                            <a href="https://gladys.sog.unc.edu" title="" class="text-uppercase" target="_blank">Gladys</a>
                                        </li>

                                        <?php
                                        // Check if user is logged in and has one of the specified roles.
                                        if (is_user_logged_in() && (current_user_can('administrator') || current_user_can('editor') || current_user_can('author'))) { ?>
                                            <li class="lookerstudio-analytics hide hidden">
                                                <a href="https://lookerstudio.google.com/u/0/reporting/64bc0bf3-8f77-4e63-bc3e-8ae519459865/page/LjUJ" title="" target="_blank">Analytics - LookerStudio Dashboard</a>
                                            </li>
                                            <li class="gladys-analytics">
                                                <a href="https://adminliveunc.sharepoint.com/sites/GLADYS/SitePages/Website-Analytics.aspx" title="" target="_blank">Analytics - Gladys</a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="row social-media-container">
                                <div class="col-xs-12 col-sm-12 col-md-12 text-left social">
                                    <?php // print $this->footerSocial(); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row copyright-container">
                        <div class="col-xs-12 col-sm-12 col-md-8 text-left copyright">
                            &copy; Copyright <?php print date('Y'); ?>, <div class="company-name"><a href="https://www.unc.edu" title="" target="_blank">The University of North Carolina at Chapel Hill</a></div>
                        </div>

                        <div class="col-xs-12 col-sm-12 col-md-4 text-right accessibility text-capitalize">
                            <ul class="list-unstyled" role="list">
                                <li role="listitem">
                                    <a href="https://www.unc.edu/about/privacy-statement/" title="" target="_blank">Privacy Policy</a>
                                </li>
                                <li role="listitem">
                                    <a href="https://digitalaccess.unc.edu/report/" title="" target="_blank">Accessibility</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div><!-- /.container -->
            </footer><!-- /#footer -->
        </div><!-- /#wrapper -->

        <?php wp_footer(); ?>
    </body>
</html>
