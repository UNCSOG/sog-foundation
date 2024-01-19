<?php

namespace OTGS\Toolset\Views\Services;

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;
use OTGS\Toolset\Common\PostType\EditorMode;

/**
 * Handles all the related actions needed for the View block to be loaded properly.
 */
class ViewEditorService {
	/** @var \WPV_Ajax */
	private $toolset_ajax_manager;

	/** @var \Toolset_Settings */
	private $toolset_settings;

	/** @var ContentSelectionService */
	private $content_selection_service;

	/** @var \Toolset_Constants */
	private $constants;

	/**
	 * ViewEditorService constructor.
	 *
	 * @param \Toolset_Constants $constants
	 * @param ContentSelectionService $content_selection_service
	 * @param callable $toolset_settings_get_instance
	 * @param callable $toolset_ajax_manager_get_instance
	 */
	public function __construct(
		\Toolset_Constants $constants,
		ContentSelectionService $content_selection_service,
		callable $toolset_settings_get_instance,
		callable $toolset_ajax_manager_get_instance
	) {
		$this->constants = $constants;
		$this->content_selection_service = $content_selection_service;
		$this->toolset_ajax_manager = $toolset_ajax_manager_get_instance;
		$this->toolset_settings = $toolset_settings_get_instance;
	}

	/**
	 * Initialize class.
	 */
	public function initialize() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_view_editor_assets' ) );
	}

	/**
	 * Register View editor JS file and frontend CSS
	 */
	public function register_view_editor_assets() {
		$current_user = wp_get_current_user();

		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_enqueue_script(
			'register_view_editor_assets', // Unique handle.
			$this->constants->constant( 'WPV_URL' ) . '/public/js/view-editor.js',
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-editor',
				'toolset-common-es',
				'toolset-conditionals',
				'views-filters-js',
				'wpv-parametric-admin-script',
			),
			$this->constants->constant( 'WPV_VERSION' )
		);

		wp_enqueue_style(
			'register_view_editor_css',
			$this->constants->constant( 'WPV_URL' ) . '/public/css/view-editor.css',
			array( 'toolset-common-es', 'toolset-conditionals' ),
			$this->constants->constant( 'WPV_VERSION' )
		);

		$default_loop_template = get_user_meta( $current_user->ID, '_wpv_default_template', true );
		if ( empty( $default_loop_template ) ) {
			$default_loop_template = null;
		}

		$loop_item_template_on_top = get_user_meta( $current_user->ID, '_wpv_default_loop_item_on_top', true );
		if ( empty( $loop_item_template_on_top ) ) {
			$loop_item_template_on_top = 0;
		}

		$shortcode_settings = array_map(
			function( $item ) {
				return is_callable( $item['callback'] ) ? call_user_func( $item['callback'] ) : [];
			},
			apply_filters( 'wpv_filter_wpv_shortcodes_gui_data', [] )
		);

		/** @var \Toolset_Settings $toolset_settings */
		$toolset_settings = call_user_func( $this->toolset_settings );

		/**
		 * Filters the assets of Views related to the block editor.
		 *
		 * @param array $assets
		 *
		 * @return array
		 */
		$view_editor_assets = apply_filters(
			'wpv_filter_localize_view_editor_assets',
			array(
				'currentUser' => $current_user,
				'defaultLoopTemplate' => $default_loop_template,
				'loopItemTemplateOnTop' => $loop_item_template_on_top,
				'bootstrapVersion' => $toolset_settings->bootstrap_version_numeric,
				'shortcodes_settings' => $shortcode_settings,
				'canEditViews' => $this->user_can_edit_views_as_blocks() ? '1' : '0',
			)
		);

		wp_localize_script(
			'register_view_editor_assets',
			'viewsInfo',
			$view_editor_assets
		);

		// Ported from old "View" block
		$locale = null;
		if ( function_exists( 'wp_get_jed_locale_data' ) ) {
			$locale = wp_get_jed_locale_data( 'wpv-views' );
		} elseif ( function_exists( 'gutenberg_get_jed_locale_data' ) ) {
			$locale = gutenberg_get_jed_locale_data( 'wpv-views' );
		} else {
			$locale = array(
				array(
					'domain' => 'wpv-views',
					'lang' => 'en_US',
				),
			);
		}

		$related_post_type_options = $this->content_selection_service->get_related_post_type_options();

		/** @var \WPV_Ajax $toolset_ajax_manager */
		$toolset_ajax_manager = call_user_func( $this->toolset_ajax_manager );

		/**
		 * Filters the localization strings of the View block related to the block editor.
		 *
		 * @param array $assets
		 *
		 * @return array
		 */
		$view_block_strings = apply_filters(
			'wpv_filter_localize_view_block_strings',
			array(
				'blockName' => Bootstrap::BLOCK_NAME,
				'blockCategory' => \Toolset_Blocks::TOOLSET_GUTENBERG_BLOCKS_CATEGORY_SLUG,
				'publishedViews' => apply_filters( 'wpv_get_available_views', array() ),
				'wpnonce' => wp_create_nonce( \WPV_Ajax::CALLBACK_GET_VIEW_BLOCK_PREVIEW ),
				'actionName' => $toolset_ajax_manager->get_action_js_name( \WPV_Ajax::CALLBACK_GET_VIEW_BLOCK_PREVIEW ),
				'locale' => $locale,
				'contentSelectionOptions' => array(
					'post_types' => $this->content_selection_service->get_post_type_options(),
					'rfgs' => $this->content_selection_service->get_rfg_options(),
					'relatedPostTypes' => $related_post_type_options['post_types'],
					'relatedIntermediaryPostTypes' => $related_post_type_options['intermediary_post_types'],
					'intermediaryPostTypes' => $this->content_selection_service->get_intermediary_post_types(),
				),
				'siteUrl' => get_site_url(),
			)
		);

		wp_localize_script(
			'register_view_editor_assets',
			'toolset_view_block_strings',
			$view_block_strings
		);
	}

	/**
	 * Check whether the current user can create or edit Views as blocks,
	 * either because the selected editing experience does not support that
	 * or because the user does not have the proper capabilities.
	 *
	 * @return bool
	 * @since 3.0
	 */
	private function user_can_edit_views_as_blocks() {
		if ( EditorMode::CLASSIC === wpv_get_views_editing_experience() ) {
			return false;
		}

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			return false;
		}

		return true;
	}
}
