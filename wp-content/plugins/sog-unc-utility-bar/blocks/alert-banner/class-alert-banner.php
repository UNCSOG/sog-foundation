<?php

namespace Utility_Bar\Blocks;

class Alert_Banner {

    /**
     * singleton instance
     *
     * @var [\Utility_Bar\Alert_Banner_Display]
     */
    private static $instance;

    private $block_json_data = [];

    function __construct() {
        //error_log('hello from alert banner class');
        //add_action("init", array($this, 'register_alert_banner_block_styles'));

        add_action('enqueue_block_assets', array($this, 'scripts'));
        // add_action( 'enqueue_block_editor_assets', array( $this, 'editor_scripts') );

        $this->block_json_data = \Utility_Bar\Block_Controller::getInstance()->get_block_json_data(dirname(__FILE__) . '/block.json');

        if ('published' === \Utility_Bar\Alert_Banner_Storage::get_published_status() && !\Utility_Bar\Core::using_blocks()) {
            add_action('wp_body_open', array($this, 'add_banner_to_wp_body_open'));
            //add_action('wp_footer', array($this, 'add_dynamic_styles_to_footer'), 100);
        }
        add_filter('body_class', array($this, 'add_body_class'));
        //add_action( 'enqueue_block_editor_assets', 'myguten_enqueue' );
        //set a body class depending on if the alert is published

    }


    function scripts() {

        if (!is_admin()) {
            \Utility_Bar\Block_Controller::register_block_script('blocks/alert-banner/build/frontend/', 'alert-banner-frontend', 'style',);
            \Utility_Bar\Block_Controller::register_block_script('blocks/alert-banner/build/frontend/', 'alert-banner-frontend', 'script');
            wp_localize_script('alert-banner-frontend-script', 'alertBannerFrontendScriptVars', array(
                'restUrl' => get_rest_url() . 'utility-bar-alert-service/v1/'
            ));
        } else {
            \Utility_Bar\Block_Controller::register_block_script('blocks/alert-banner/build/admin/', 'alert-banner-block', 'style');
            \Utility_Bar\Block_Controller::register_block_script('blocks/alert-banner/build/admin/', 'alert-banner-block', 'script');
        }
    }

    /**
     * render function for the alert banner
     *
     * @param [array] $atts these are the values sent from the gutenberg block
     * @return void
     */
    function render_callback($atts, $content = '') {
        //error_log( var_export( $atts, true ) );
        if (
            \Utility_Bar\Block_Controller::is_editing_block_on_backend()
            || 'published' === \Utility_Bar\Alert_Banner_Storage::get_published_status()
            || $atts['useRest']
        ) {

            //preview what attributes are sent in the error log
            //uncomment when needed, comment out when not
            //error_log( 'atts:' . var_export( $atts, true ) );

            //get the cap data
            $cap_data =  \Utility_Bar\Alert_Banner_Storage::get_stored_alert();
            //error_log( var_export( $cap_data, true ) );

            //make a cap object
            $cap = new \Utility_Bar\Cap($cap_data);

            if ($cap) {
                //error_log( 'cap:' . var_export( $cap, true ) );

                //the markup data
                $data = $this->generate_banner_markup_data_from_cap($cap);

                if (\Utility_Bar\Block_Controller::is_editing_block_on_backend()) {
                    //do not linkify or show skip link in the block editor
                    $data['linkify_banner'] = false;
                    $data['show_skip_link'] = false;
                    //error_log( var_export( $data, true ) );

                    if (isset($atts['preview']) && 'adverse' == $atts['preview']) {
                        $data['classes'] = ' adverse-alert';
                    }
                }

                //generate the markup
                $output = $this->generate_banner($data, true, true);
                //error_log( 'cap:' . var_export( $cap, true ) );

                return $output;
            }

            return '';
        }

        return '';
    }

    /**
     * makes markup data for the banner from the saved cap message
     *
     * @param [type] $cap
     * @return void
     */
    function generate_banner_markup_data_from_cap($cap) {

        $actype =  $cap->get_actype();

        //an array to send data to the template
        $data = array();

        //get the cap ID
        //we dont filter this one
        if (false != ($id = $cap->get('identifier'))) {
            $data['id'] =  $id;
        }

        /******************************************** Markup *********************************************/

        /*
         * classes for the banner alert
         *
         * array of classes that will be joined
         * [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 
         */
        $classes_array = apply_filters('alert_service_banner_classes', array(strtolower($actype) . '-alert'), $cap);
        //error_log( var_export( $classes_array, true ) );
        $data['classes'] = ' ' . join(' ', $classes_array);

        // get the web link
        //no reason to filter this one
        $data['web_link'] = $cap->get_info('web');

        /*
         * turn the link part on and off
         *
         * bool, if it's on
         * [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 
         */
        $data['linkify_banner'] = apply_filters('alert_service_linkify_banner', true, $cap);


        //construct the message from the headline
        //$cap_msg = $actype . ': ' . $cap->get_info('headline');
        $cap_msg = $cap->get_info('headline');

        /**
         * Filter the message
         *
         * @param [string] $cap_msg a string, the message the users will see
         * @param [string] $actype is a string, possibilities are 'weather', 'adverse', 'emergency', 'crime'
         * @param [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object.  
         */
        $data['cap_msg'] =  apply_filters('alert_service_banner_msg', $cap_msg, $actype, $cap);

        /**
         * filter the skip linktarget
         *
         * @param [string] target is a dom identifier of some location on the page the skip link should go to.  IDs work best, defailts to #maincontent
         * @param [string] $actype is a string, possibilities are 'weather', 'adverse', 'emergency', 'crime'
         * @param [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 
         */
        $data['skip_link_target'] = apply_filters('alert_service_skip_link_target', '#maincontent', $actype, $cap);


        /**
         * filter the skip link test
         *
         * @param [string] the text used in the skip link
         * @param [string] $actype is a string, possibilities are 'weather', 'adverse', 'emergency', 'crime'
         * @param [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 
         */
        $data['skip_link_text'] = apply_filters('alert_service_skip_link_text', 'Skip to main content', $actype, $cap);

        /**
         * show or hide the skip link
         *
         * @param [bool] bool to show or hide the skiplink
         * @param [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 
         */
        $data['show_skip_link'] = apply_filters('alert_service_show_skip_link', true, $cap);

        /**
         * filter the entire data array
         *
         * @param [array] $data, an array of all the daya going to the banner template
         * @param [string] $actype is a string, possibilities are 'weather', 'adverse', 'emergency', 'crime'
         * @param [\Alert_Service\Cap] $cap is a \Alert_Service\Cap object. 
         */
        $data = apply_filters('alert_service_banner_data', $data, $actype, $cap);

        return $data;
    }

    /**
     * generate the banner markup
     *
     * @var [array] $data an array of all the data used in the template
     * @var [bool] return the markup (true) or echo the markup (false), defaults to echo the markup
     * @return void
     */

    function generate_banner($data, $return = false, $useRest = false, $isBlock = true) {

        $data['classes'] .= ' alert-service-banner';
        $atts = array(
            'class' => $data['classes'],
            'id' => isset($data['id']) ? $data['id'] : ''
        );

        if ($isBlock) {
            $data['wrapper_attributes'] = get_block_wrapper_attributes($atts);
        } else {
            $data['wrapper_attributes'] =  'class="' . $data['classes'] . '"';
        }

        //error_log(var_export($data, true));
        $return ? ob_start() : '';

        //the template loader
        $template_loader = new \Utility_Bar\Template_Loader();

        if ($useRest) {
            $template_loader
                ->set_template_data($data)
                ->get_template_part('alert', 'banner-rest');
        } else {
            $template_loader
                ->set_template_data($data)
                ->get_template_part('alert', 'banner');
        }


        return $return ? ob_get_clean() : true;
    }

    /**
     * adds the markup to the wp_open_body tag
     *
     * @return void
     */
    function add_banner_to_wp_body_open() {
        //global $template;
        wp_enqueue_style('alert-banner-frontend-style');
        $show_alert_banner = apply_filters('unc_utility_bar\alert_banner_display', true);
        //error_log( 'we are going to show the alert banner ' . var_export( $show_alert_banner, true ) );
        //we need to check if the block is being used her e
        if ($show_alert_banner) {
            //get the cap data
            $cap_data = \Utility_Bar\Alert_Banner_Storage::get_stored_alert();

            if ($cap_data) {
                //make a cap object
                $cap = new \Utility_Bar\Cap($cap_data);

                //the markup data
                $data = $this->generate_banner_markup_data_from_cap($cap);

                $this->generate_banner($data, false, false, false);

                //the styles
                //$this->generate_banner_styles_from_cap($cap);
            }
        }
    }

    /**
     * set a body class depending on if the alert is published
     *
     * @param [array] $classes
     * @return void
     */
    function add_body_class($classes) {
        $status = \Utility_Bar\Alert_Banner_Storage::get_published_status();
        $classes[] = 'alert-' . $status;
        return $classes;
    }

    /**
     * you only need this once
     *
     * @return Banner_Display
     */
    public static function getInstance(): Alert_Banner {
        static::$instance = static::$instance ?? new static();
        return static::$instance;
    }
}
