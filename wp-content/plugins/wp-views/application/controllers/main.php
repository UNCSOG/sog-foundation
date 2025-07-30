<?php

/**
 * Main Views controller.
 *
 * @since 2.5.0
 * @since m2m WPV_Ajax included
 * @codeCoverageIgnore
 */
class WPV_Main {

	public function initialize() {
		$this->add_hooks();
	}

	public function add_hooks() {
		add_action( 'toolset_common_loaded', array( $this, 'register_autoloaded_classes' ) );

		add_action( 'toolset_common_loaded', array( $this, 'initialize_classes' ) );

		// Avada needs user editors initialized on 'after_setup_theme' priority 10 (TCOMP-135).
		add_action( 'after_setup_theme', array( $this, 'initialize_user_editor' ), 10 );

		add_action( 'after_setup_theme', array( $this, 'initialize_common_sections' ), 999 );

		add_action( 'after_setup_theme', array( $this, 'init_api' ), 9999 );

		add_action( 'init', array( $this, 'on_init' ), 1 );

		// In the plugin's deactivation hook the cron that updates third-party configuration for Dynamic Sources needs to be cleared.
		// The action handler that does the cron callback clearance is hooked on `init` with priority 10, so the current handler
		// needs to go on priority 11 or later.
		add_action( 'init', array( $this, 'register_deactivation_hook' ), 11 );
	}

	/**
	 * Register Views classes with Toolset_Common_Autoloader.
	 *
	 * @since 2.5.0
	 */
	public function register_autoloaded_classes() {
		$classmap = include WPV_PATH . '/application/autoload_classmap.php';
		do_action( 'toolset_register_classmap', $classmap );
	}

	public function initialize_classes() {
		/**
		 * @var \OTGS\Toolset\Common\Auryn\Injector
		 */
		$dic = apply_filters( 'toolset_dic', false );

		// Initilize the compatibility between Views and other third-party or OTGS plugins.
		$dic_class = new \OTGS\Toolset\Views\Controller\Dic();
		$dic_class->initialize();

		/**
		 * @var \OTGS\Toolset\Views\Controller\Shortcode\Resolution
		 */
		$shortcode_resolution = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolution' );
		$shortcode_resolution->initialize();

		/**
		 * @var \OTGS\Toolset\Views\Controller\Cache $plugin_cache
		 */
		$plugin_cache = $dic->make( '\OTGS\Toolset\Views\Controller\Cache' );
		$plugin_cache->initialize();

		/**
		 * @var \OTGS\Toolset\Views\Controller\Upgrade $wpv_upgrade
		 */
		$wpv_upgrade = $dic->make( '\OTGS\Toolset\Views\Controller\Upgrade' );
		$wpv_upgrade->initialize();

		// Initilize the compatibility between Views and other third-party or OTGS plugins.
		$compatibility = new \OTGS\Toolset\Views\Controller\Compatibility();
		$compatibility->initialize();

		$ct = $dic->make(
			'\OTGS\Toolset\Views\Controller\ContentTemplate',
			array(
				':toolset_assets_manager' => \Toolset_Assets_Manager::get_instance(),
			)
		);
		$ct->initialize();

		$wpa_helper = $dic->make( '\OTGS\Toolset\Views\Controller\WordPressArchiveHelper' );
		$wpa_helper->initialize();

		// Only register block when the current WP installation supports blocks.
		if( function_exists( 'register_block_type' ) ) {
			$view_editor_block = $dic->make(
				'\OTGS\Toolset\Views\Services\Bootstrap',
				array(
					':view_get_instance' => array(
						'\WPV_View',
						'get_instance',
					),
				)
			);
			$view_editor_block->initialize();
		}

		// @since 2.6.4
		if ( is_admin() ) {
			if ( defined( 'DOING_AJAX' ) ) {
				WPV_Ajax::initialize();
			} else {
				WPV_Admin::initialize();
			}
		}

		// @since m2m
		$filter_manager = WPV_Filter_Manager::get_instance();
		$filter_manager->initialize();

		// Initializing the Views related section in the Toolset Troubleshooting page.
		$views_troubleshooting_sections = $dic->make( OTGS\Toolset\Views\Controller\Admin\Section\Troubleshooting::class );
		$views_troubleshooting_sections->initialize();
	}

	/**
	 * Initialize the optional Toolset User Editor.
	 *
	 * @since 3.0
	 */
	public function initialize_user_editor() {
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
		$toolset_common_bootstrap->load_sections( array( 'toolset_user_editor' ) );
	}

	/**
	 * Initialize the optional Toolset Common components.
	 *
	 * @since 3.0
	 */
	public function initialize_common_sections() {
		// TODO Remove the call to load 'toolset_visual_editor'
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
		$toolset_common_sections = array( 'toolset_visual_editor', 'toolset_blocks' );
		$toolset_common_bootstrap->load_sections( $toolset_common_sections );
		do_action( 'wpv_action_did_initialize_common_sections' );
	}

	/**
	 * Init the public Views filters API.
	 *
	 * @note This gets available at after_setup_theme:9999 because we need to wait for Toolset Common to fully load.
	 *
	 * @since m2m
	 */
	public function init_api() {
		WPV_Api::initialize();
	}

	public function on_init() {
		$wpv_shortcodes = new WPV_Shortcodes();
		$wpv_shortcodes->initialize();
		$wpv_shortcodes_gui = new WPV_Shortcodes_GUI();
		$wpv_shortcodes_gui->initialize();
		$wpv_lite_handler = new WPV_Lite_Handler();
		$wpv_lite_handler->initialize();
	}

	/**
	 * Triggers the plugin deactivation hook for Dynamic Sources to unhook the cron job that updates the configuration for third-party blocks.
	 */
	public function register_deactivation_hook() {
		do_action( 'toolset/dynamic_sources/actions/register_deactivation_hook', WPV_PATH . '/' . WPV_PLUGIN_FILE );
	}
}
