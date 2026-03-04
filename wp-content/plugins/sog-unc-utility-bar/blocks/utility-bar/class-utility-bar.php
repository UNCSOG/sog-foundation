<?php

namespace Utility_Bar\Blocks;

use Utility_Bar\Core as Core;

class Utility_Bar {

    /**
     * singleton instance
     *
     * @var [\Utility_Bar\Blocks\Utility_Bar]
     */
    private static $instance;



    function __construct() {

        // error_log('hello from utility bar class');
        // add_action('customize_preview_init', array($this, 'customize_preview_js'));
        // these are hooks to keep customizer and the settings api in sync

        /**
         * make the settings api change with customizer
         */
        add_action('customize_save_after', function () {
            $new_value = get_theme_mod('utility_bar_display');
            // error_log( 'customize_save_after: ' . var_export( $new_value, true ) );

            if ($new_value) {
                update_option('_unc_utility_bar_display', $new_value);
            }
        });


        /**
         * make the null values 0 so there is a value
         */
        add_filter('pre_update_option__unc_utility_bar_display', function ($value, $option, $old_value) {
            // error_log('pre_update_option_{_unc_utility_bar_display}');

            if (NULL == $value) {
                //error_log('setting null value to no');
                $value = 'no';
            }

            return $value;
        }, 10, 3);

        /**
         * when the checkbox is no or not there it goes thru add_option
         * this seems wrong
         */
        add_action('add_option__unc_utility_bar_display', function ($option, $value) {
            // error_log( 'checkbox _unc_utility_bar_display option updated to ' . $value );

            // we need to also se the theme mod set in customizer
            set_theme_mod('utility_bar_display', $value);
        }, 11, 2);


        if ('no' != get_theme_mod('utility_bar_display')) {
            // error_log( 'utility_bar_display' . var_export( get_theme_mod('utility_bar_display'), true ));

            add_filter('body_class', function ($classes) {
                $classes[] = 'utility-bar-displayed';
                return $classes;
            }, 10, 1);
        }

        /**
         * when the checkbox yes its updated
         * this seems wrong
         */
        add_action('update_option__unc_utility_bar_display', function ($old_value, $value, $option) {
            // error_log( 'checkbox _unc_utility_bar_display option updated to ' . $value );

            // we need to also se the theme mod set in customizer
            set_theme_mod('utility_bar_display', $value);
        }, 12, 3);



        if (false === Core::using_blocks() && false == \Utility_Bar\Core::legacy_has_utility_bar()) {
            // error_log('go for classic');

            add_action('customize_register', array($this, 'utility_bar_customize_register'));
            add_action('wp_body_open', array($this, 'add_utility_bar_to_wp_body_open'));
            // do this if not using a block editor

            // Enqueue Customizer preview JS for live preview
            add_action('customize_preview_init', function() {
                wp_enqueue_script(
                    'unc-utility-bar-customizer-preview',
                    plugins_url('../../assets/js/customizer-preview.js', __FILE__),
                    array('jquery', 'customize-preview'),
                    null,
                    true
                );
            });

            // add_action('wp_footer', array($this, 'add_dynamic_styles_to_footer'), 100);
        }
    }

    /** for the customizer */
    function utility_bar_customize_register($wp_customize) {

        $wp_customize->add_setting(
            'utility_bar_display',
            array(
                'transport' => 'postMessage',
            )
        );

        $wp_customize->add_control('utility_bar_display', array(
            'default' => 'yes',
            'label'    => __('Show/Hide Utility Bar', 'Utility Bar'),
            'section'  => 'layout',
            'setting' => 'show',
            'settings' => 'utility_bar_display',
            'type'     => 'radio',
            'choices'  => array(
                'yes' => 'Show',
                'no' => 'Hide',
            ),
        ));

        $wp_customize->add_section('layout', array(
            'title'    => __('UNC Utility Bar', 'utility_bar'),
            'priority' => 1,
        ));

        // color choices
        $wp_customize->add_setting(
            'utility_bar_display_colors',
            array(
                'transport' => 'postMessage',
                'default'   => 'dark-gray',
            )
        );

        $wp_customize->add_control('utility_bar_display_colors', array(
            'default' => 'dark-gray',
            'label'    => __('Utility Bar Color Options', 'Utility Bar'),
            'section'  => 'layout',
            'settings' => 'utility_bar_display_colors',
            'type'     => 'radio',
            'choices'  => array(
                'dark-gray' => 'Dark Gray',
                'gray'      => 'Gray',
                'black'     => 'Black',
                'navy'      => 'Navy',
                'blue'      => 'Blue',
                'white'     => 'White',
            ),
        ));

        // menu alignment
        $wp_customize->add_setting(
            'utility_bar_menu_alignment',
            array(
                'transport' => 'postMessage',
                'default'   => 'right',
            )
        );

        $wp_customize->add_control('utility_bar_menu_alignment', array(
            'default'  => 'right',
            'label'    => __('Menu Alignment', 'Utility Bar'),
            'section'  => 'layout',
            'settings' => 'utility_bar_menu_alignment',
            'type'     => 'radio',
            'choices'  => array(
                'left'   => 'Left',
                'center' => 'Center',
                'right'  => 'Right',
            ),
        ));


        // utility bar width
        $wp_customize->add_setting(
            'utility_bar_width',
            array(
                'transport' => 'postMessage',
                'default'   => '1170px',
            )
        );

        $wp_customize->add_control('utility_bar_width', array(
            'label'       => __('Utility Bar Width', 'Utility Bar'),
            'description' => __('Set the max width of the utility bar (e.g. 1170px, 100vw, 90%). Leave blank for default.', 'Utility Bar'),
            'section'     => 'layout',
            'settings'    => 'utility_bar_width',
            'type'        => 'text',
        ));

        // title visibility
        $wp_customize->add_setting(
            'utility_bar_show_title',
            array(
                'transport' => 'postMessage',
                'default'   => 'yes',
            )
        );

        $wp_customize->add_control('utility_bar_show_title', array(
            'default'  => 'yes',
            'label'    => __('Show/Hide Title Text', 'Utility Bar'),
            'section'  => 'layout',
            'settings' => 'utility_bar_show_title',
            'type'     => 'radio',
            'choices'  => array(
                'yes' => 'Show',
                'no'  => 'Hide',
            ),
        ));

        // custom title text
        $wp_customize->add_setting(
            'utility_bar_title_text',
            array(
                'transport' => 'postMessage',
                'default'   => 'The University of North Carolina at Chapel Hill',
            )
        );

        $wp_customize->add_control('utility_bar_title_text', array(
            'label'       => __('Title Text', 'Utility Bar'),
            'description' => __('Leave blank to use the default: "The University of North Carolina at Chapel Hill".', 'Utility Bar'),
            'section'     => 'layout',
            'settings'    => 'utility_bar_title_text',
            'type'        => 'text',
        ));

        // Short title visibility
        $wp_customize->add_setting(
            'utility_bar_show_short_title',
            array(
                'transport' => 'postMessage',
                'default'   => 'yes',
            )
        );

        $wp_customize->add_control('utility_bar_show_short_title', array(
            'default'  => 'yes',
            'label'    => __('Show/Hide Short Title Text', 'Utility Bar'),
            'section'  => 'layout',
            'settings' => 'utility_bar_show_short_title',
            'type'     => 'radio',
            'choices'  => array(
                'yes' => 'Show',
                'no'  => 'Hide',
            ),
        ));

        // Short Title setting
        $wp_customize->add_setting(
            'utility_bar_short_title_text',
            array(
                'transport' => 'postMessage',
                'default'   => 'UNC-CH',
            )
        );

        $wp_customize->add_control('utility_bar_short_title_text', array(
            'label'       => __('Short Title Text', 'Utility Bar'),
            'description' => __('A short version of the title for compact displays or alternate uses. The set default is "UNC-CH". Shows only on small mobile screens.', 'Utility Bar'),
            'section'     => 'layout',
            'settings'    => 'utility_bar_short_title_text',
            'type'        => 'text',
        ));


        $wp_customize->add_setting(
            'site_banner_msg',
            array(
                'transport' => 'postMessage',
            )
        );

        // $wp_customize->add_control('site_banner_msg', array(
        //     'label'    => __('Site Banner message', 'Utility Bar'),
        //     'section'  => 'banner',
        //     'settings' => 'site_banner_msg',
        //     'type'     => 'text',
        // ));

        // $wp_customize->add_section('banner', array(
        //     'title'    => __('Site Banner', 'utility_bar'),
        //     'priority' => 2,
        // ));
    }


    /**
     * in classic we add the utility bar into wp_body_open
     * there is also a conditional in the customizer that can change utility bar view,
     * that conditional will be in this class's constructor
     *
     * @return void
     */
    function add_utility_bar_to_wp_body_open() {
        if ('no' != get_theme_mod('utility_bar_display') && !Core::using_blocks()) {
            $show_utility_bar = true;
            $show_utility_bar = apply_filters('unc_utility_bar\utility_bar_display', $show_utility_bar);
            // error_log( 'we are going to show the utility bar ' . var_export(  $show_utility_bar, true ) );

            if ($show_utility_bar) {
                $data = [];

                $colors = get_theme_mod('utility_bar_display_colors');
                $colors = $colors ? $colors : 'dark-gray';
                $alignment = get_theme_mod('utility_bar_menu_alignment', 'right');
                $show_title = get_theme_mod('utility_bar_show_title', 'yes');
                $title_text = get_theme_mod('utility_bar_title_text', 'The University of North Carolina at Chapel Hill');
                $title_text = $title_text ? $title_text : 'The University of North Carolina at Chapel Hill';
                $show_short_title = get_theme_mod('utility_bar_show_short_title', 'yes');
                $short_title_text = get_theme_mod('utility_bar_short_title_text', '');
                $short_title_text = $short_title_text ? $short_title_text : 'UNC-CH';
                // if (empty($short_title_text)) {
                //     $short_title_text = 'UNC-CH';
                // }
                $width = get_theme_mod('utility_bar_width', '1170px');

                $data['short_title_text'] = esc_html($short_title_text);
                $data['wrapper_attributes'] = ' id="unc-utility-bar" class="' . $colors . '" data-color="' . $colors . '" data-menu-align="' . esc_attr($alignment) . '" style="--unc-utility-bar-width:' . esc_attr($width) . ';"';
                $data['show_title'] = ($show_title !== 'no');
                $data['show_short_title'] = ($show_short_title !== 'no');
                $data['title_text'] = esc_html($title_text);

                $template_loader = new \Utility_Bar\Template_Loader();
                $template_loader
                    ->set_template_data($data)
                    ->get_template_part('utility', 'bar');
            }
        }
    }

    /**
     * you only need this once
     *
     * @return Utility_Bar
     */
    public static function getInstance(): Utility_Bar {
        static::$instance = static::$instance ?? new static();
        return static::$instance;
    }
}
