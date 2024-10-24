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
function sog_foundation_customize($wp_customize)
{
    /**
     * Initialize sections
     */
    $wp_customize->add_section(
        'theme_header_section',
        [
            'title' => __('Header', 'sog-foundation-child'),
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
                'label' => __('Upload Header Logo', 'sog-foundation-child'),
                'description' => __('Height: &gt;80px', 'sog-foundation-child'),
                'section' => 'theme_header_section',
                'settings' => 'header_logo',
                'priority' => 1,
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
            'label' => __('Navbar Scheme', 'sog-foundation-child'),
            'section' => 'theme_header_section',
            'choices' => [
                'navbar-light bg-light' => __('Default', 'sog-foundation-child'),
                'navbar-dark bg-dark' => __('Dark', 'sog-foundation-child'),
                'navbar-dark bg-primary' => __('Primary', 'sog-foundation-child'),
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
            'label' => __('Navbar', 'sog-foundation-child'),
            'section' => 'theme_header_section',
            'choices' => [
                'static' => __('Static', 'sog-foundation-child'),
                'fixed_top' => __('Fixed to top', 'sog-foundation-child'),
                'fixed_bottom' => __('Fixed to bottom', 'sog-foundation-child'),
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
            'label' => __('Show Searchfield?', 'sog-foundation-child'),
            'section' => 'theme_header_section',
            'settings' => 'search_enabled',
            'priority' => 3,
        ]
    );
}
add_action('customize_register', 'sog_foundation_customize');

/**
 * Bind JS handlers to make Theme Customizer preview reload changes asynchronously.
 *
 * @return void
 */
function sog_foundation_parent_customize_preview_js()
{
    wp_enqueue_script('customizer', get_template_directory_uri() . '/inc/customizer.js', [ 'jquery' ], null, true);
}
add_action('customize_preview_init', 'sog_foundation_customize_preview_js');
