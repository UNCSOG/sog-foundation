<?php

namespace Toolset\Compatibility\Divi;

class Extension extends \DiviExtension {

	/**
	 * The gettext domain for the extension's translations.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $gettext_domain = 'toolset-divi';

	/**
	 * The extension's WP Plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $name = 'toolset-divi';

	/**
	 * The extension's version
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $version = '1.1.0';

	/**
	 * @param string $name
	 * @param array  $args
	 */
	public function __construct() {
		$this->plugin_dir     = plugin_dir_path( __FILE__ );
		$this->plugin_dir_url = plugin_dir_url( $this->plugin_dir );

		parent::__construct( $this->name );
	}

	/**
	 * Adds required hooks.
	 */
	public function addHooks() {
		add_action( 'wp_ajax_toolset_divi_render_preview', [ $this, 'ajax_render_preview' ] );
		add_action( 'admin_init', [ $this, 'disable_app_boot_on_view_ajax_event' ] );
	}

	/**
	 * AJAX endpoint for previewing Views in the editor.
	 */
	public function ajax_render_preview() {
		$return_true = function() { return true; };

		add_filter( 'wpv_filter_disable_caching', $return_true );
		echo render_view( [ 'name' => sanitize_title( $_POST['slug'] ) ] );
		remove_filter( 'wpv_filter_disable_caching', $return_true );

		wp_die();
	}


	/**
	 * Make sure that AJAX events for Views do not return a container for the Divi frontend editor app.
	 *
	 * Note that the mechanism on Divi is added at wp_loaded,
	 * so we need to disable it afterwards: admin_init is good enough.
	 */
	public function disable_app_boot_on_view_ajax_event() {
		if (
			defined('DOING_AJAX')
			&& DOING_AJAX
			&& isset( $_REQUEST['action'] )
			&& (
				$_REQUEST['action'] == 'wpv_get_view_query_results'
				|| $_REQUEST['action'] == 'wpv_get_archive_query_results'
			)
		) {
			remove_filter( 'the_content', 'et_fb_app_boot', 1 );
		}
	}
}
