<?php

require_once "CRED_StaticClass.php";
require_once "CRED_Generic_Response.php";
require_once "common/cred_functions.php";

/**
 * Main Class
 *
 * Main class of the plugin
 * Class encapsulates all hook handlers
 */
final class CRED_CRED {

    public static $_form_builder_instance;
    public static $help = array();
    public static $help_link_target = '_blank';
    public static $settingsPage = null;
    private static $prefix = '_cred_';

    /*
     * Initialize plugin enviroment
     */
    public function init() {
        
        add_filter( 'wpcf_exclude_meta_boxes_on_post_type', array('CRED_StaticClass', 'my_cred_exclude'), 10, 1 );

        // Early init to happen after Types, but before 3rd party plugins (eg Toolset Forms Commerce)
        add_action( 'init', array(__CLASS__, '_init_'), 1 );

        // try to catch user shortcodes (defined by [...]) and solve shortcodes inside shortcodes
        // adding filter with priority before do_shortcode and other WP standard filters
        add_filter( 'the_content', 'cred_do_shortcode', 9 );
    }

	/**
	 * @return CRED_Form_Builder
	 */
	public static function get_form_builder() {
		if ( self::$_form_builder_instance == null ) {
			self::$_form_builder_instance = CRED_Form_Builder::initialize();
		}

		return self::$_form_builder_instance;
	}

    /**
     * is_embedded if CRED_Admin class does not exist is embedded plugin
     *
     * @return bool
     */
    public static function is_embedded() {
        return (false === class_exists( 'CRED_Admin' ));
    }

	/**
	 * Main init hook
	 */
    public static function _init_() {
        // load textdomain
        new Toolset_Localization( 'wp-cred', CRED_ABSPATH . '/library/toolset/cred/embedded/locale', 'wp-cred-%s' );

        // load help settings (once)
        self::$help = CRED_Loader::getVar( CRED_ABSPATH . "/library/toolset/cred/embedded/classes/ini/help.ini.php" );
        // set up models and db settings
        CRED_Helper::prepareDB();
        // needed by others
        self::$settingsPage = admin_url( 'admin.php' ) . '?page=toolset-settings';
        //self::$settingsPage = admin_url('admin.php') . '?page=CRED_Settings';
        // localize forms, support for WPML
        CRED_Helper::localizeForms();
        // setup extra admin hooks for other plugins
        CRED_Helper::setupExtraHooks();

        if ( is_admin() ) {
            if ( self::is_embedded() ) {
                self::initAdmin();
            } else {
                CRED_Admin::initAdmin();
            }
        } else {
            self::$_form_builder_instance = self::get_form_builder();

            //enqueue frontend script
            CRED_Asset_Manager::get_instance()->enqueue_frontend_assets();
        }

        // add form short code hooks and filters, to display forms on front end
        CRED_Helper::addShortcodesAndFilters();

        // handle Ajax calls
        CRED_Router::addCalls( array(
            'cred_skype_ajax' => array(
                'nopriv' => true,
                'callback' => array(__CLASS__, 'cred_skype_ajax')
            ),
            'cred-ajax-delete-post' => array(
                'nopriv' => true,
                'callback' => array(__CLASS__, 'cred_ajax_delete_post')
            )
        ) );

        CRED_Router::addRoutes( 'cred', array(
            'Forms' => 0, // Forms controller
            'Posts' => 0, // Posts controller
            'Settings' => 0 // Settings controller
        ) );
        /* CRED_Router::addPages('cred', array(
          )); */
    }

    public static function initAdmin() {
        // add plugin menus
        // setup js, css assets
        CRED_Helper::setupAdmin();

        add_action( 'admin_menu', array(__CLASS__, 'admin_menu'), 20 );
    }

    public static function admin_menu() {
        if ( isset( $_GET['page'] ) && 'cred-embedded' == $_GET['page'] ) {
            $cap = 'manage_options';
            // DEVCYCLE this should not be in the tools.php menu at all
            add_submenu_page(
                    'admin.php', __( 'Embedded CRED', 'wp-cred' ), __( 'Embedded Toolset Forms', 'wp-cred' ), CRED_CAPABILITY, 'cred-embedded', 'cred_embedded_html' );
        }
        if ( isset( $_GET['page'] ) && 'cred-user-embedded' == $_GET['page'] ) {
            $cap = 'manage_options';
            // DEVCYCLE this should not be in the tools.php menu at all
            add_submenu_page(
                    'admin.php', __( 'User Embedded CRED', 'wp-cred' ), __( 'User Embedded Toolset Forms', 'wp-cred' ), CRED_CAPABILITY, 'cred-user-embedded', 'cred_user_embedded_html' );
        }
    }

    public static function media() {
        /**
         * Fix compatibility Chirps theme
         */
        if (
                (
                (isset( $_GET['post_type'] ) && ($_GET['post_type'] == CRED_FORMS_CUSTOM_POST_NAME || $_GET['post_type'] == CRED_USER_FORMS_CUSTOM_POST_NAME)) ||
                (isset( $_GET['post'] ) && isset( $_GET['action'] ))
                ) &&
                class_exists( "G1_Theme_Admin" ) )
            remove_action( 'media_buttons', array(G1_Theme_Admin(), 'extend_gallery_settings') );
    }

    public static function route($path = '', $params = null, $raw = true) {
        return CRED_Router::getRoute( 'cred', $path, $params, $raw );
    }

    //Fix issue about https on frontend
    public static function routeAjax($action) {
        $url = admin_url( 'admin-ajax.php', 'http' ) . '?action=' . $action;
        //if is_ssl and url does not contains https
        if ( is_ssl() && strpos( $url, 'https://' ) === false ) {
            $url = str_replace( "http", "https", $url );
        }
        return $url;
    }

    public static function cred_ajax_delete_post() {
        CRED_Loader::get( "CONTROLLER/Posts" )->deletePost( $_GET, $_POST );
        wp_die();
    }

    // link CRED ajax call to wp-types ajax call (use wp-types for this)
    public static function cred_skype_ajax() {
        do_action( 'wp_ajax_wpcf_ajax' );
        wp_die();
    }

    public static function getPostAdminEditLink($post_id) {
        return admin_url( 'post.php' ) . '?action=edit&post=' . $post_id;
    }

    public static function getFormEditLink($form_id) {
        //return admin_url('post.php').'?action=edit&post='.$form_id;
        if ( self::is_embedded() )
            return admin_url( 'admin.php' ) . '?page=cred-embedded&cred_id=' . $form_id;
        else
            return get_edit_post_link( $form_id );
    }

    public static function getUserFormEditLink($form_id) {
        //return admin_url('post.php').'?action=edit&post='.$form_id;
        if ( self::is_embedded() )
            return admin_url( 'admin.php' ) . '?page=cred-user-embedded&cred_id=' . $form_id;
        else
            return get_edit_post_link( $form_id );
    }

    public static function getNewFormLink($abs = true) {
        return ($abs) ? admin_url( 'post-new.php' ) . '?post_type=' . CRED_FORMS_CUSTOM_POST_NAME : 'post-new.php?post_type=' . CRED_FORMS_CUSTOM_POST_NAME;
    }

    public static function getNewUserFormLink($abs = true) {
        return ($abs) ? admin_url( 'post-new.php' ) . '?post_type=' . CRED_USER_FORMS_CUSTOM_POST_NAME : 'post-new.php?post_type=' . CRED_USER_FORMS_CUSTOM_POST_NAME;
    }

}
