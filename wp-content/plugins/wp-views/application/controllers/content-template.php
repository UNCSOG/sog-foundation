<?php

namespace OTGS\Toolset\Views\Controller;

class ContentTemplate {
	/** @var \Toolset_Assets_Manager */
	private $toolset_assets_manager;

	/** @var \Toolset_Constants */
	private $toolset_constants;

	const SCRIPT_HANDLE = 'ct-block-editor';

	const OBJECT_HANDLE = 'ctBlockEditor';

	/**
	 * ContentTemplate constructor.
	 *
	 * @param \Toolset_Assets_Manager $toolset_assets_manager
	 * @param \Toolset_Constants      $toolset_constants
	 */
	public function __construct( \Toolset_Assets_Manager $toolset_assets_manager, \Toolset_Constants $toolset_constants ) {
		$this->toolset_assets_manager = $toolset_assets_manager;
		$this->toolset_constants = $toolset_constants;
	}

	public function initialize() {
		// It needs to happen after "admin_init", priority 11, because this is where Theme Settings are populated.
		add_action( 'admin_init', [ $this, 'register_assets' ], 12 );

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_assets' ] );
	}

	public function register_assets() {
		$this->toolset_assets_manager->register_script(
			self::SCRIPT_HANDLE,
			$this->toolset_constants->constant( 'WPV_URL' ) . '/public/js/contentTemplate.js',
			[ 'toolset-common-es' ],
			$this->toolset_constants->constant( 'WPV_VERSION' ),
			false
		);

		$localization_data = $this->add_main_data( [] );
		$localization_data = $this->add_theme_settings( $localization_data );

		$this->toolset_assets_manager->localize_script(
			self::SCRIPT_HANDLE,
			self::OBJECT_HANDLE,
			$localization_data
		);
	}

	public function enqueue_assets() {
		do_action( 'toolset_enqueue_scripts', [ self::SCRIPT_HANDLE ] );
	}

	/**
	 * Adds main localization data
	 *
	 * @param  array $localization_data
	 *
	 * @return array
	 */
	private function add_main_data( $localization_data ) {
		$localization_data['ajaxurl'] = admin_url( 'admin-ajax.php', null );
		$localization_data['noticeNonce'] = wp_create_nonce( 'wpv_ajax_dismiss_admin_notice' );
		$localization_data['noticeDismissed'] = apply_filters( 'wpv_filter_wpv_is_dismissed_notice', false, array( 'id' => 'ct-editing-notice', 'type' => 'user' ) ) ? 1 : 0;

		return $localization_data;
	}

	/**
	 * Adds the theme settings related data.
	 *
	 * @param  array $localization_data
	 *
	 * @return array
	 */
	private function add_theme_settings( $localization_data ) {
		$theme_settings = apply_filters( 'wpv_filter_get_theme_settings', [] );

		if ( empty( $theme_settings ) ) {
			return $localization_data;
		}

		$localization_data['themeName'] = $theme_settings['theme_name'];
		$localization_data['themeSlug'] = $theme_settings['theme_slug'];
		$localization_data['collections'] = $theme_settings['collections'];

		return $localization_data;
	}
}
