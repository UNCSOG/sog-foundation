
<?php

defined('ABSPATH') || exit;

/**
 * Implement Theme Customizer additions and adjustments.
 * https://codex.wordpress.org/Theme_Customization_API
 *
 * How do I "output" custom theme modification settings? https://developer.wordpress.org/reference/functions/get_theme_mod
 * echo get_theme_mod( 'copyright_info' );
 * or: echo get_theme_mod( 'copyright_info', 'Default (c) Copyright Info if nothing provided' );
 *
 * "sanitize_callback": https://codex.wordpress.org/Data_Validation
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 *
 * @return void
 */
function sog_foundation_parent_customize($wp_customize) {
    /**
     * Initialize sections
     */
    $wp_customize->add_section(
        'theme_header_section',
        [
            'title' => __('Header', 'sog-foundation-parent'),
            'priority' => 1000,
        ]
    );

    /**
     * Section: Page Layout
     */
    // Header Logo.
    $wp_customize->add_setting(
        'header_logo',
        [
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ]
    );

    $wp_customize->add_control(
        new WP_Customize_Image_Control(
            $wp_customize,
            'header_logo',
            [
                'label' => __('Upload Header Logo', 'sog-foundation-parent'),
                'description' => __('Height: &gt;80px', 'sog-foundation-parent'),
                'section' => 'theme_header_section',
                'settings' => 'header_logo',
                'priority' => 1,
            ]
        )
    );

    // Favicon Image
    $wp_customize->add_setting(
        'favicon_image',
        [
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ]
    );

    $wp_customize->add_control(
        new WP_Customize_Image_Control(
            $wp_customize,
            'favicon_image',
            [
                'label' => __('Favicon Image', 'sog-foundation-parent'),
                'description' => __('Upload a favicon (recommended size: 32x32px or 64x64px PNG).', 'sog-foundation-parent'),
                'section' => 'title_tagline',
                'settings' => 'favicon_image',
                'priority' => 2,
            ]
        )
    );

    // Predefined Navbar scheme.
    $wp_customize->add_setting(
        'navbar_scheme',
        [
            'default' => 'default',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );

    $wp_customize->add_control(
        'navbar_scheme',
        [
            'type' => 'radio',
            'label' => __('Navbar Scheme', 'sog-foundation-parent'),
            'section' => 'theme_header_section',
            'choices' => [
                'navbar-light bg-light' => __('Default', 'sog-foundation-parent'),
                'navbar-dark bg-dark' => __('Dark', 'sog-foundation-parent'),
                'navbar-dark bg-primary' => __('Primary', 'sog-foundation-parent'),
            ],
            'settings' => 'navbar_scheme',
            'priority' => 1,
        ]
    );

    // Fixed Header?
    $wp_customize->add_setting(
        'navbar_position',
        [
            'default' => 'static',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );

    $wp_customize->add_control(
        'navbar_position',
        [
            'type' => 'radio',
            'label' => __('Navbar', 'sog-foundation-parent'),
            'section' => 'theme_header_section',
            'choices' => [
                'static' => __('Static', 'sog-foundation-parent'),
                'fixed_top' => __('Fixed to top', 'sog-foundation-parent'),
                'fixed_bottom' => __('Fixed to bottom', 'sog-foundation-parent'),
            ],
            'settings' => 'navbar_position',
            'priority' => 2,
        ]
    );

    // Search?
    $wp_customize->add_setting(
        'search_enabled',
        [
            'default' => '1',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );

    $wp_customize->add_control(
        'search_enabled',
        [
            'type' => 'checkbox',
            'label' => __('Show Searchfield?', 'sog-foundation-parent'),
            'section' => 'theme_header_section',
            'settings' => 'search_enabled',
            'priority' => 3,
        ]
    );

    // Custom Site Name
    $wp_customize->add_setting(
        'custom_site_name',
        [
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );

    $wp_customize->add_control(
        'custom_site_name',
        [
            'type' => 'text',
            'label' => __('Custom Site Name', 'sog-foundation-parent'),
            'description' => __('Enter a custom site name to display in the header instead of the default site title.', 'sog-foundation-parent'),
            'section' => 'title_tagline',
            'settings' => 'custom_site_name',
            'priority' => 1,
        ]
    );

    // Container Width Setting
    $wp_customize->add_setting(
        'container_width',
        [
            'default' => 'container',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );

    $wp_customize->add_control(
        'container_width',
        [
            'type' => 'select',
            'label' => __('Container Width', 'sog-foundation-parent'),
            'description' => __('Choose the maximum width for the site container or select Custom to enter your own.'),
            'section' => 'theme_header_section',
            'settings' => 'container_width',
            'choices' => [
                'container' => __('Default (Fixed Width)', 'sog-foundation-parent'),
                'container-fluid' => __('Full Width (Fluid)', 'sog-foundation-parent'),
                'custom' => __('Custom Width', 'sog-foundation-parent'),
            ],
            'priority' => 20,
        ]
    );

    // Custom Container Width Value
    $wp_customize->add_setting(
        'container_custom_width',
        [
            'default' => '',
            'sanitize_callback' => function($value) {
                // Allow only valid CSS max-width values (e.g., 1200px, 90vw, 80%, 50em, 40rem)
                return preg_match('/^([0-9]+)(px|vw|%|em|rem)$/', trim($value)) ? trim($value) : '';
            },
        ]
    );

    $wp_customize->add_control(
        'container_custom_width',
        [
            'type' => 'text',
            'label' => __('Custom Container Max-Width', 'sog-foundation-parent'),
            'description' => __('Enter a CSS max-width value (e.g., 1200px, 90vw, 80%, 50em, 40rem). Only applies if "Custom Width" is selected.', 'sog-foundation-parent'),
            'section' => 'theme_header_section',
            'settings' => 'container_custom_width',
            'priority' => 21,
        ]
    );

    // SOG Site Selector - Multiple Checkboxes
    $wp_customize->add_setting(
        'sog_site_selector',
        [
            'default' => [],
            'sanitize_callback' => function($input) {
                if (is_array($input)) {
                    return array_map('esc_url_raw', $input);
                }
                return [];
            },
            'type' => 'option',
        ]
    );

    $wp_customize->add_control(
        new WP_Customize_Control(
            $wp_customize,
            'sog_site_selector',
            [
                'type' => 'checkbox',
                'label' => __('Choose SOG Sites', 'sog-foundation-parent'),
                'description' => __('Select one or more School of Government related sites.'),
                'section' => 'theme_header_section',
                'settings' => 'sog_site_selector',
                'choices' => [
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
                    'https://ced.sog.unc.edu' => 'Community and Economic Development',
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
                    // 'https://courses.sog.unc.edu' => 'School of Government - Courses',
                    // 'https://forms.sog.unc.edu' => 'School of Government - Forms',
                    // 'https://library.sog.unc.edu' => 'School of Government - Library',
                    // 'https://mpamatters.web.unc.edu' => 'MPA Matters',
                    // 'https://app.sog.unc.edu' => 'App',
                    // 'https://apps.sog.unc.edu/' => 'Apps',
                    // 'https://sso.sog.unc.edu' => 'SSO',
                    // 'https://status.sog.unc.edu' => 'Status',
                    // 'https://unc-sog-importer-app.herokuapp.com/' => 'UNC SOG Importer App',
                    // 'https://live-sog-dwi.pantheonsite.io' => 'Live SOG DWI',
                ],
                'priority' => 30,
            ]
        )
    );

    // SOG Site Selector Target Attribute
    $wp_customize->add_setting(
        'sog_site_selector_target',
        [
            'default' => '_blank',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );
    $wp_customize->add_control(
        'sog_site_selector_target',
        [
            'type' => 'select',
            'label' => __('SOG Site Link Target', 'sog-foundation-parent'),
            'description' => __('Choose how the SOG site link opens.'),
            'section' => 'theme_header_section',
            'settings' => 'sog_site_selector_target',
            'choices' => [
                '_blank' => __('New Tab (_blank)', 'sog-foundation-parent'),
                '_self' => __('Same Tab (_self)', 'sog-foundation-parent'),
            ],
            'priority' => 31,
        ]
    );

    // SOG Site Selector Title Attribute
    $wp_customize->add_setting(
        'sog_site_selector_title',
        [
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );

    $wp_customize->add_control(
        'sog_site_selector_title',
        [
            'type' => 'text',
            'label' => __('SOG Site Link Title', 'sog-foundation-parent'),
            'description' => __('Add a descriptive title for accessibility.'),
            'section' => 'theme_header_section',
            'settings' => 'sog_site_selector_title',
            'priority' => 32,
        ]
    );
}
add_action('customize_register', 'sog_foundation_parent_customize');

/**
 * Bind JS handlers to make Theme Customizer preview reload changes asynchronously.
 *
 * @return void
 */
function sog_foundation_parent_customize_preview_js()
{
    wp_enqueue_script('customizer', get_template_directory_uri() . '/inc/customizer.js', [ 'jquery' ], null, true);
}
add_action('customize_preview_init', 'sog_foundation_parent_customize_preview_js');
