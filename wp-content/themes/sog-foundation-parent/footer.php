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
                            <h4><?php esc_html_e('Please visit other School of Government websites:', 'sog-foundation-parent'); ?></h4>

                            <ul class="list-unstyled">
                                <?php
                                $sog_sites = get_theme_mod('sog_site_selector', []);

                                if (!empty($sog_sites) && is_array($sog_sites)) :
                                    $sog_site_target = get_theme_mod('sog_site_selector_target', '_blank');
                                    $sog_site_title = get_theme_mod('sog_site_selector_title', '');
                                    $choices = [
                                        'https://sog.unc.edu' => 'School of Government',
                                        'https://books.sog.unc.edu' => 'School of Government - Publications',
                                        'https://canons.sog.unc.edu' => "Coates' Canons: NC Local Government Law",
                                        'https://ced.sog.unc.edu' => 'Community and Economic Development',
                                        'https://deathandtaxes.sog.unc.edu' => 'Death and Taxes',
                                        'https://efc.web.unc.edu' => 'Environmental Finance Center',
                                        'https://elinc.sog.unc.edu' => 'Environmental Law in Context',
                                        'https://ncimpact.sog.unc.edu/facts-that-matter-blog' => 'Facts That Matter',
                                        'https://nccriminallaw.sog.unc.edu' => 'North Carolina Criminal Law',
                                        'https://civil.sog.unc.edu' => 'On the Civil Side',
                                        'https://defendermanuals.sog.unc.edu' => 'Defender Manuals',
                                        'https://benchbook.sog.unc.edu' => 'Bench Book',
                                        'https://far.sog.unc.edu' => 'Faculty Activity Record',
                                        'https://lrs.sog.unc.edu' => 'Legislative Reporting Service',
                                        'https://crimes.sog.unc.edu' => 'Crimes',
                                        'https://ncpro.sog.unc.edu/' => 'NC PRO',
                                        'https://protectadults.sog.unc.edu' => 'Protect Adults',
                                        'https://clerks.sog.unc.edu' => 'Clerks',
                                        'https://mpa.unc.edu' => 'Masters of Public Administration',
                                        'http://courseplanner.mpa.unc.edu/' => 'MPA Course Planner',
                                        'https://courseplanner-demo.mpa.unc.edu/' => 'MPA Course Planner Demo',
                                        'https://mpa4esc.web.unc.edu' => 'MPA4ESC',
                                        'https://carolinampa.web.unc.edu' => 'Carolina MPA',
                                        'https://ncfinanceconnect.com' => 'NC Finance Connect',
                                        'https://continuing-professional-education.sog.unc.edu' => 'Continuing Professional Education',
                                        'https://lfnc.sog.unc.edu' => 'LFNC',
                                        'https://sun.sog.unc.edu' => 'SUN',
                                        'https://lgwi.web.unc.edu' => 'LGWI',
                                        'https://sogappreciate.web.unc.edu' => 'SOG Appreciate',
                                        'https://sogteaching.web.unc.edu/' => 'SOG Teaching',
                                        'https://engage.web.unc.edu' => 'Engage',
                                        'https://ncimpact.sog.unc.edu' => 'ncImpact',
                                        'https://itd.sog.unc.edu' => 'ITD',
                                        'https://efc.sog.unc.edu' => 'Environmental Finance Center',
                                        'https://dashboards.efc.sog.unc.edu' => 'EFC Dashboards',
                                        'https://budgetgame.sog.unc.edu' => 'Budget Game',
                                        'https://publicdefense.sog.unc.edu' => 'Public Defense',
                                        'https://report.web.unc.edu' => 'Report',
                                        'https://hsdocs.web.unc.edu' => 'HS Docs',
                                        'https://ccat.sog.unc.edu' => 'CCAT',
                                        'https://arpa.sog.unc.edu' => 'ARPA',
                                        'https://cplg.sog.unc.edu' => 'CPLG',
                                        'https://dfi.sog.unc.edu' => 'DFI',
                                        'https://humanservices.sog.unc.edu' => 'Human Services',
                                        'https://civilianboards.sog.unc.edu' => 'Civilian Boards',
                                        'https://lgnc.sog.unc.edu' => 'LGNC',
                                        'https://toolsfordecisionmaking.sog.unc.edu' => 'Tools for Decision Making',
                                        'https://sogimpact.sog.unc.edu' => 'SOG Impact',
                                        'https://benchmarking.sog.unc.edu' => 'Benchmarking',
                                        'https://servicemural.unc.edu' => 'Service Mural',
                                        'https://lar.sog.unc.edu/' => 'LAR',
                                        'https://cjil.sog.unc.edu' => 'CJIL',
                                        'https://cjil.shinyapps.io/MeasuringJustice/' => 'Measuring Justice',
                                        'https://courtappearance.cjil.sog.unc.edu' => 'Court Appearance',
                                        'https://orp.sites.unc.edu/' => 'ORP',
                                        'http://hrp.sog.unc.edu/' => 'HRP',
                                        'https://ncrecoveryportal.com/' => 'NC Recovery Portal',
                                        'https://leadership.sog.unc.edu' => 'Public Leadership Blog',
                                        'https://podcast.sog.unc.edu' => 'Podcast',
                                        'https://civic.sog.unc.edu/' => 'Civic',
                                        'https://cupso.org' => 'CUPSO',
                                        'https://ncadcj.org' => 'NCADCJ',
                                        'https://www.ncmanagers.org' => 'NC Managers',
                                    ];

                                    foreach ($sog_sites as $url) {
                                        $label = isset($choices[$url]) ? $choices[$url] : $url;

                                        echo '<li><a href="' . esc_url($url) . '" target="' . esc_attr($sog_site_target) . '"';

                                        if (!empty($sog_site_title)) {
                                            echo ' title="' . esc_attr($sog_site_title) . '"';
                                        }

                                        echo ' rel="noopener noreferrer">' . esc_html($label) . '</a></li>';
                                    }
                                endif;
                                ?>
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
