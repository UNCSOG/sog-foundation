<?php

class Toolset_Compatibility_Theme_avada extends Toolset_Compatibility_Theme_Handler {
	const COMPILERS_OPTION_NAME = 'toolset_fusion_compilers';
	const COMPILERS_OPTION_VALUE_DEFAULT = 0;

	/**
	 * @return mixed|void
	 * changed access level to public for testing purposes
	 */
	public function run_hooks() {
		$this->set_inline_css_mode();

		if ( $this->is_settings_page() ) {
			add_filter( 'toolset_filter_toolset_register_settings_general_section', array(
				$this,
				'register_avada_settings_items'
			), 999, 2 );
			add_filter( 'toolset_add_registered_script', array( $this, 'add_register_script' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'settings_enqueue' ) );
		}

		add_action( 'wp_ajax_save_fusion_compiler_option', array( $this, 'save_fusion_compiler_option_callback' ) );
	}

	/**
	 * Force Avada css mode to inline instead of file, this solves the problem with caching
	 */
	private function set_inline_css_mode() {
		if ( ! defined( 'FUSION_DISABLE_COMPILERS' ) ) {
			define( 'FUSION_DISABLE_COMPILERS', apply_filters( 'toolset_disable_fusion_compilers', $this->is_disable_compilers() ) );
		}
	}

	/**
	 * @param $sections
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function register_avada_settings_items( $sections, $settings ) {
		$sections['fusion-compilers'] = array(
			'slug'     => 'fusion-compilers',
			'title'    => __( 'Avada Fusion Compilers Options', 'wp-views' ),
			'callback' => array( $this, 'fusion_compilers_gui' )
		);

		return $sections;
	}

	/**
	 * Adds a GUI to Toolset Settings General tab to turn on/off compilers
	 */
	public function fusion_compilers_gui() {
		$fusion_compilers_on = $this->are_compilers_on();
		ob_start();

		require_once TOOLSET_THEME_SETTINGS_PATH . '/compatibility-modules/templates/toolset-theme-integration-fusion-compilers-settings.tpl.php';

		echo ob_get_clean();
	}

	/**
	 * @return mixed|void
	 */
	public function get_option_enable_fusion_compilers() {
		return get_option( self::COMPILERS_OPTION_NAME, self::COMPILERS_OPTION_VALUE_DEFAULT );
	}

	/**
	 * @param $bool
	 *
	 * @return bool
	 */
	public function set_option_enable_fusion_compilers( $bool ) {
		return update_option( self::COMPILERS_OPTION_NAME, $bool );
	}

	/**
	 * @return bool
	 */
	public function are_compilers_on() {
		$option              = (int) $this->get_option_enable_fusion_compilers();
		$fusion_compilers_on = $option === 1 ? true : false;

		return $fusion_compilers_on;
	}

	/**
	 * @return bool
	 */
	public function is_disable_compilers() {
		$option            = (int) $this->get_option_enable_fusion_compilers();
		$disable_compilers = $option === 1 ? false : true;

		return $disable_compilers;
	}

	/**
	 * @param $scripts
	 *
	 * @return mixed
	 */
	public function add_register_script( $scripts ) {
		// register the script only if in Toolset Settings page
		if ( ! $this->is_settings_page() ) {
			return $scripts;
		}

		$scripts['avada-settings-script'] = new Toolset_Script( 'avada-settings-script', TOOLSET_THEME_SETTINGS_URL . '/res/js/themes/avada-settings-script.js', array(
			'jquery',
			'underscore',
			'toolset-utils'
		), TOOLSET_THEME_SETTINGS_VERSION );

		return $scripts;
	}

	/**
	 *
	 */
	public function settings_enqueue() {
		// enqueue the script only if in Toolset Settings page
		if ( ! $this->is_settings_page() ) {
			return;
		}
		do_action( 'toolset_enqueue_scripts', array( 'avada-settings-script' ) );
	}

	/**
	 * @return bool
	 */
	public function is_settings_page() {
		return is_admin() && isset( $_GET['page'] ) && $_GET['page'] === 'toolset-settings';
	}

	/**
	 * AJAX Callback to save Compilers Settings in wp_options table
	 */
	public function save_fusion_compiler_option_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			$data = array(
				'type'    => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'toolset_fusion-compilers_nonce' ) ) {
			$data = array(
				'type'    => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		if ( ! isset( $_POST['fusion_compilers_on'] ) || ! is_numeric( $_POST['fusion_compilers_on'] ) || intval( $_POST['fusion_compilers_on'] ) > 1 ) {
			$data = array(
				'type'    => 'option',
				'message' => __( 'Wrong or missing value.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

		$value = intval( $_POST['fusion_compilers_on'] );

		$update = $this->set_option_enable_fusion_compilers( $value );

		if ( ! $update ) {
			$data = array(
				'type'    => 'option',
				'message' => __( 'There was a problem updating option value in database.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		} else {
			$data = array(
				'type'    => 'option',
				'message' => sprintf( __( 'Fusion Compilers have been turned %s.', 'wpv-views' ), $value === 1 ? 'on' : 'off' )
			);
		}

		wp_send_json_success( $data );
	}
}