                <?php
                // If Single or Archive (Category, Tag, Author or a Date based page).
                if (is_single() || is_archive()) : ?>
                        </div><!-- /.col -->

                        <?php get_sidebar(); ?>
                    </div><!-- /.row -->
                <?php endif; ?>
            </main><!-- /#main -->

            <footer id="footer">
                <div class="container">
                    <div class="row">
                        <div class="col-sm-12 col-md-4">
                            <div class="row school-logo">
                                <div class="col-sm-12">
                                    <a href="https://sog.unc.edu" title="School of Government" target="_blank">
                                        <img src="<?php echo get_template_directory_uri(); ?>/images/source_SOG_sig_Blue_White_h.png" alt="UNC-CH School of Government Logo" width="300" height="93" class="alignnone size-medium" />
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

                            <div class="row building-address address-container">
                                <div class="col-sm-12 address">
                                    <p>
                                    UNC School of Government<br/>
                                    400 South Road<br/>
                                    Knapp-Sanders Building, CB3330<br/>
                                    Chapel Hill, NC 27599-3330<br/>
                                    T: 919.966.5381
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-4 employee-navigation-container">
                            <ul class="list-unstyled">
                                <li><a href="http://digitalaccess.unc.edu/report" target="_blank" title="">Accessibility: Report a Digital Access Issue</a></li>
                                <li><a href="/wp-login" title="">Employee Login</a></li>

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

                        <div class="col-sm-12 col-md-4 sog-websites-container">
                            <h4>Please visit other School of Government websites:</h4>
                            <ul class="list-unstyled">
                                <li><a href="https://sog.unc.edu" title="" target="_blank">School of Government</a></li>
                                <li><a href="https://books.sog.unc.edu" title="" target="_blank">School of Government - Publications</a></li>
                                <li><a href="https://canons.sog.unc.edu" title="" target="_blank">Coates' Canons: NC Local Government Law </a></li>
                                <li><a href="https://ced.sog.unc.edu" title="" target="_blank">Community and Economic Development </a></li>
                                <li><a href="https://deathandtaxes.sog.unc.edu" title="" target="_blank">Death and Taxes</a></li>
                                <li><a href="https://efc.web.unc.edu" title="" target="_blank">Environmental Finance</a></li>
                                <li><a href="https://elinc.sog.unc.edu" title="" target="_blank">Environmental Law in Context</a></li>
                                <li><a href="https://ncimpact.sog.unc.edu/facts-that-matter-blog" title="" target="_blank">Facts That Matter</a></li>
                                <li><a href="https://mpamatters.web.unc.edu" title="" target="_blank">MPA Matters</a></li>
                                <li><a href="https://nccriminallaw.sog.unc.edu" title="" target="_blank">North Carolina Criminal Law</a></li>
                                <li><a href="https://civil.sog.unc.edu" title="" target="_blank">On the Civil Side</a></li>
                            </ul>
                        </div>
                    </div>

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
                                    'container_class' => 'col-md-6',
                                    //'fallback_cb'     => 'WP_Bootstrap4_Navwalker_Footer::fallback',
                                    'walker' => new WP_Bootstrap4_Navwalker_Footer(),
                                    'theme_location' => 'footer-menu',
                                    'items_wrap' => '<ul class="menu nav justify-content-end">%3$s</ul>',
                                ]
                            );
                        endif;

                        if (is_active_sidebar('third_widget_area')) : ?>
                            <div class="col-md-12">
                                <?php dynamic_sidebar('third_widget_area');

                                if (current_user_can('manage_options')) : ?>
                                    <span class="edit-link"><a href="<?php echo esc_url(admin_url('widgets.php')); ?>" class="badge bg-secondary" title=""><?php esc_html_e('Edit', 'sog-foundation-parent'); ?></a></span><!-- Show Edit Widget link -->
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div><!-- /.row -->


                    <div class="row text-center">
                        <div class="col-sm-12">
                            <p><?php printf(esc_html__('&copy; %1$s %2$s. School of Government at the University of North Carolina at Chapel Hill. All rights reserved.', 'sog-foundation-parent'), wp_date('Y'), get_bloginfo('name', 'display')); ?></p>
                        </div>
                    </div><!-- /.row -->
                </div><!-- /.container -->
            </footer><!-- /#footer -->
        </div><!-- /#wrapper -->

        <?php wp_footer(); ?>
    </body>
</html>
