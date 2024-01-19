<?php

use OTGS\Toolset\CRED\Controller\Factory as ControllerFactory;
use OTGS\Toolset\CRED\Model\Factory as ModelFactory;

use OTGS\Toolset\CRED\Controller\Forms\Post\Main as PostFormMain;
use OTGS\Toolset\CRED\Controller\Forms\User\Main as UserFormMain;

/**
 * Main Toolset Forms controller.
 *
 * Determines if we're in admin or front-end mode or if an AJAX call is being performed. Handles tasks
 * that are common to all three modes, if there are any.
 *
 * @since 1.9
 */
class CRED_Main {

	private $form_prefix = '__CRED__FIX__';

	public function initialize() {
		add_action( 'toolset_common_loaded', array( $this, 'register_autoloaded_classes' ) );

		if ( ! $this->should_initialize_plugin() ) {
			$this->add_minimal_hooks();
			return;
		}

		$this->add_hooks();
	}

	private function __clone() { }

	private function add_minimal_hooks() {
		add_action( 'toolset_common_loaded', function() {
			$this->register_toolset_forms_menu( \OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\BasePage::MODE_EMPTY );
		},
		11 );
		add_action( 'init', array( $this, 'init_cron_managers' ), 1 );
	}

	private function add_hooks() {
		add_action( 'toolset_common_loaded', function() {
			$this->register_toolset_forms_menu();
		},
		11 );
		add_action( 'toolset_common_loaded', array( $this, 'init_cred_association_forms' ), 999 );
		add_action( 'toolset_common_loaded', array( $this, 'init_cred_post_forms' ), 999 );
		add_action( 'toolset_common_loaded', array( $this, 'init_cred_user_forms' ), 999 );
		add_action( 'after_setup_theme', array( $this, 'init_api' ), 999 );
		add_action( 'after_setup_theme', array( $this, 'init_assets_manager' ), 999 );
		add_action( 'after_setup_theme', array( $this, 'register_shortcode_generator' ), 999 );
		add_action( 'after_setup_theme', array( $this, 'load_toolset_blocks_section' ), 999 );
		add_action( 'init', array( $this, 'on_init' ), 1 );
		add_action( 'init', array( $this, 'init_cron_managers' ), 1 );
		add_filter( 'wpml_show_admin_language_switcher', array( 'CRED_Admin_Helper', 'disable_wpml_admin_lang_switcher' ) );
		add_filter( 'query_vars', [ $this, 'hack_submit_form_post_before_parse_query' ] );
		add_action( 'parse_request', [ $this, 'restore_submit_form_post_before_parse_query' ] );
	}


	/**
	 * Register Toolset Forms classes with Toolset_Common_Autoloader.
	 *
	 * @since 1.9
	 */
	public function register_autoloaded_classes() {
		// It is possible to regenerate the classmap with Zend framework, for example:
		//
		// cd application
		// /srv/www/ZendFramework-2.4.9/bin/classmap_generator.php --overwrite
		$classmap = include( CRED_ABSPATH . '/application/autoload_classmap.php' );
		$legacy_classmap = $this->get_legacy_classmap();
		$classmap = array_merge( $classmap, $legacy_classmap );

		do_action( 'toolset_register_classmap', $classmap );
	}

	public function register_toolset_forms_menu( $forced_mode = false ) {
		/**
		 * @var \OTGS\Toolset\Common\Auryn\Injector
		 */
		$dic = apply_filters( 'toolset_dic', false );

		$toolset_forms_menu = $dic->make( '\OTGS\Toolset\CRED\Controller\ToolsetFormsMenu' );
		$toolset_forms_menu->initialize( $forced_mode );
	}

	public function init_api() {
		CRED_Api::initialize();
	}

	public function init_assets_manager() {
		CRED_Asset_Manager::get_instance();
	}

	public function register_shortcode_generator() {
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::get_instance();
		$toolset_common_sections = array( 'toolset_shortcode_generator' );
		$toolset_common_bootstrap->load_sections( $toolset_common_sections );
		$cred_shortcode_generator = new CRED_Shortcode_Generator();
		$cred_shortcode_generator->initialize();
	}


	/**
	 * Return the array of autoloaded classes in legacy Toolset Forms and their absolute paths.
	 *
	 * If you need to use a class from the legacy code in the new part, use this method for
	 * registering it with the autoloader instead of including files directly.
	 *
	 * @return string[string]
	 * @since 1.9
	 */
	private function get_legacy_classmap() {
		$classmap = array();

		return $classmap;
	}


    /**
     * Shortcut to Toolset_Common_Bootstrap::get_request_mode().
     *
     * @return string Toolset_Common_Bootstrap::MODE_*
     * @since 1.9
     */
    private function get_request_mode() {
        $tb = Toolset_Common_Bootstrap::getInstance();
        return $tb->get_request_mode();
    }


	/**
	 * Determine in which request mode we are and initialize the right dedicated controller.
	 *
	 * @since 1.9
	 */
	public function on_init() {
		/**
		 * @var \OTGS\Toolset\Common\Auryn\Injector
		 */
		$dic = apply_filters( 'toolset_dic', false );

		/**
		 *  @var \OTGS\Toolset\CRED\Controller\Cache $cred_cache
		 */
		$cred_cache = $dic->make( '\OTGS\Toolset\CRED\Controller\Cache' );
		$cred_cache->initialize();

		/**
		 *  @var \OTGS\Toolset\CRED\Controller\Upgrade $cred_upgrade
		 */
		$cred_upgrade = $dic->make( '\OTGS\Toolset\CRED\Controller\Upgrade' );
		$cred_upgrade->initialize();

		if ( ! is_admin()
			|| cred_is_ajax_call()
		) {
			CRED_Frontend_Select2_Manager::get_instance();
		}

		//Init Notification Hooks
		CRED_Notification_Manager_Utils::get_instance();

		$cred_ajax = new CRED_Ajax();
		$cred_ajax->initialize();

		$this->try_to_start_output_buffering();

		switch( $this->get_request_mode() ) {
			case Toolset_Common_Bootstrap::MODE_ADMIN:
				// todo CRED_Admin controller
				break;
			case Toolset_Common_Bootstrap::MODE_FRONTEND:
				// todo CRED_Frontend controller
				/**
				 * @var \OTGS\Toolset\CRED\Controller\CommentsManager $cred_comments_manager
				 */
				$cred_comments_manager = $dic->make( '\OTGS\Toolset\CRED\Controller\CommentsManager' );
				$cred_comments_manager->initialize();
				break;
			case Toolset_Common_Bootstrap::MODE_AJAX:
				CRED_Ajax::initialize();
				break;
		}

		$cred_shortcodes = new CRED_Shortcodes();
		$cred_shortcodes->initialize();

		$cred_permissions = new \OTGS\Toolset\CRED\Controller\Permissions();
		$cred_permissions->initialize();

		$cred_compatibility = new \OTGS\Toolset\CRED\Controller\Compatibility();
		$cred_compatibility->initialize();
	}

	/**
	 * Init managers related to cron jobs / events
	 *
	 * This bypasses the check on should_initialize_plugin,
	 * since cron items are requested everywhere.
	 */
	public function init_cron_managers() {
		/**
		 * @var \OTGS\Toolset\Common\Auryn\Injector
		 */
		$dic = apply_filters( 'toolset_dic', false );

		/**
		 * @var \OTGS\Toolset\CRED\Controller\DraftsManager $cred_drafts_manager
		 */
		$cred_drafts_manager = $dic->make( '\OTGS\Toolset\CRED\Controller\DraftsManager' );
		$cred_drafts_manager->initialize();

		/**
		 * @var \OTGS\Toolset\CRED\Controller\ExpirationManager $cred_expiration_manager
		 */
		$cred_expiration_manager = $dic->make( '\OTGS\Toolset\CRED\Controller\ExpirationManager' );
		$cred_expiration_manager->initialize();
	}

	public function init_cred_association_forms(){
		// Only initialize association forms if M2M is activated
		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}
		$controller_factory = new ControllerFactory();
		$model_factory = new CRED_Association_Form_Model_Factory();
		$helper = new CRED_Association_Form_Relationship_API_Helper();
		$association_forms = new CRED_Association_Form_Main( $controller_factory, $model_factory, $helper );
		$association_forms->initialize();
	}

	public function init_cred_post_forms() {
		$controller_factory = new ControllerFactory();
		$model_factory = new ModelFactory();
		$post_forms = new PostFormMain( $controller_factory, $model_factory );
		$post_forms->initialize();
	}

	public function init_cred_user_forms() {
		$controller_factory = new ControllerFactory();
		$model_factory = new ModelFactory();
		$user_forms = new UserFormMain( $controller_factory, $model_factory );
		$user_forms->initialize();
	}

	/**
	 * Fix PHP Warning header already sent on redirection adding a ob_start() on submition
	 *
	 * @since 1.9.4
	 */
	public function try_to_start_output_buffering() {
		if ( ! is_admin()
			&& ! empty( $_POST )
			&& isset( $_GET['_tt'] )
		) {
			ob_start();
		}
	}

	/**
	 * Load the Toolset Gutenberg blocks section from Toolset Common.
	 *
	 * @since 2.0.1
	 */
	public function load_toolset_blocks_section() {
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
		$toolset_common_sections = array( 'toolset_blocks' );
		$toolset_common_bootstrap->load_sections( $toolset_common_sections );
	}

	/**
	 * Avoid loading the plugin in selected backend pages, where the is no Forms interaction.
	 *
	 * @since 2.4
	 */
	private function should_initialize_plugin() {
		// Admin pages related to BBpress,
		// including some custom WPML and Toolset support pages
		$support_uris = array(
			'/.*admin\.php\?page=wpml-support-queues/',
			'/.*admin\.php\?page=translation-support-queue/',
			'/.*admin\.php\?page=translation-services-support-queue/',
			'/.*admin\.php\?page=bbps_management_dashboard/',
			'/.*admin\.php\?page=bbps-stats-dashboard.*/',
			'/.*admin\.php\?page=bbps_rating_dashboard/',
			'/.*admin\.php\?page=bbps_tags_dashboard/',
			'/.*admin\.php\?page=bbps_management_badges/',
			'/.*admin\.php\?page=bbps_summary_ratings/',
			'/.*admin\.php\?page=bbps_tickets_summary_queue/',
			'/.*admin\.php\?page=bbps_automatic_assignment_stats/',
			'/.*admin\.php\?page=bbp-chat-support-queue/',
			'/.*admin\.php\?page=bbp-chat-support-options/',
			'/.*admin\.php\?page=bbp-chat-statistics/',
		);

		// Admin pages related to compatibility
		$compatibility_uris = array(
			'/.*admin\.php\?page=comp-themes/',
			'/.*admin\.php\?page=comp-plugins/',
			'/.*admin\.php\?page=comp-extensions/',
		);

		// Admin pages related to WPML management
		$wpml_uris = array(
			'/.*admin\.php\?page=sitepress-multilingual-cms\/menu\/languages\.php/',
			'/.*admin\.php\?page=sitepress-multilingual-cms\/menu\/theme-localization\.php/',
			'/.*admin\.php\?page=wpml-translation-management\/menu\/main\.php/',
			'/.*admin\.php\?page=wpml-translation-management\/menu\/translations-queue\.php/',
			'/.*admin\.php\?page=sitepress-multilingual-cms\/menu\/menu-sync\/menus-sync\.php/',
			'/.*admin\.php\?page=wpml-string-translation\/menu\/string-translation\.php/',
			'/.*admin\.php\?page=sitepress-multilingual-cms\/menu\/taxonomy-translation\.php/',
			'/.*admin\.php\?page=wpml-sticky-links/',
			'/.*admin\.php\?page=wpml-cms-nav\/menu\/navigation\.php/',
			'/.*admin\.php\?page=wpml-translation-feedback-list/',
			'/.*admin\.php\?page=wpml-package-management/',
			'/.*admin\.php\?page=wpml-translation-management\/menu\/settings/',
			'/.*admin\.php\?page=sitepress-multilingual-cms\/menu\/support\.php/',
		);

		// Core pages where Forms is not required
		$wp_uris = array(
			'/.*wp-admin\/about\.php/',
			'/.*wp-admin\/update-core\.php/',
			'/.*wp-admin\/upload\.php/',
			'/.*wp-admin\/media-new\.php/',
			'/.*wp-admin\/themes\.php/',
			'/.*wp-admin\/customize\.php.*/',
			'/.*wp-admin\/nav-menus\.php/',
			'/.*wp-admin\/plugin-install\.php/',
			'/.*wp-admin\/users\.php/',
			'/.*wp-admin\/tools\.php/',
			'/.*wp-admin\/import\.php/',
			'/.*wp-admin\/export\.php/',
			'/.*wp-admin\/options-general\.php/',
			'/.*wp-admin\/options-writing\.php/',
			'/.*wp-admin\/options-reading\.php/',
			'/.*wp-admin\/options-discussion\.php/',
			'/.*wp-admin\/options-media\.php/',
			'/.*wp-admin\/options-permalink\.php/',
			'/.*wp-admin\/privacy\.php/',
		);

		if ( wp_doing_ajax() ) {
			return true;
		}

		if ( ! is_admin() ) {
			return true;
		}

		$uri = $this->get_request_uri();

		$disallowed_uris = array_merge( $support_uris, $compatibility_uris, $wpml_uris, $wp_uris );

		foreach ( $disallowed_uris as $pattern ) {
			if ( preg_match( $pattern, $uri ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get request uri.
	 *
	 * @return string
	 */
	private function get_request_uri() {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			return htmlspecialchars( strip_tags( wp_unslash( $_SERVER['REQUEST_URI'] ) )  );
		}

		return '';
	}

	/**
	 * When submitting a form that contains a taxonomy, post type slug or other "reserved word", it can collide with parsing the query.
	 * To avoid this error, when sending the form, it modifies `$_POST` keys adding a prefix in order to avoid collision
	 *
	 * It uses `query_vars` filter because there is not any action defined at the beginning of the process
	 *
	 * @param array $query_vars WP query vars.
	 * @return array
	 */
	public function hack_submit_form_post_before_parse_query( $query_vars ) {
		if ( isset( $_POST[ '_cred_cred_prefix_form_id' ] ) ) {
			foreach ( $_POST as $key => $value ) {
				$_POST[ $this->form_prefix . $key ] = $value;
				unset( $_POST[ $key ] );
			}
		}
		return $query_vars;
	}

	/**
	 * Restores the previous `$_POST` keys
	 *
	 * @param object $obj Not used.
	 */
	public function restore_submit_form_post_before_parse_query( $obj ) {
		if ( isset( $_POST[ $this->form_prefix . '_cred_cred_prefix_form_id' ] ) ) {
			foreach ( $_POST as $key => $value ) {
				$_POST[ str_replace( $this->form_prefix, '', $key ) ] = $value;
				unset( $_POST[ $key ] );
			}
		}
	}
}
