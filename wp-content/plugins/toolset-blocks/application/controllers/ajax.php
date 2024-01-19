<?php

/**
 * Main AJAX call controller for Views.
 *
 * This class can be used in any way only after the Common Library is loaded.
 *
 * Please read the important usage instructions for the superclass:
 *
 * @inheritdoc
 *
 * @since m2m
 */
class WPV_Ajax extends Toolset_Ajax {
	const HANDLER_CLASS_PREFIX = 'WPV_Ajax_Handler_';

	/**
	 * Action names.
	 *
	 * @since m2m
	 */

	// Generic stuff
	const CALLBACK_CREATE_VIEW = 'create_view';
	const CALLBACK_DUPLICATE_VIEW = 'duplicate_view';

	const CALLBACK_CREATE_PAGE_FOR_VIEW = 'create_page_for_view';


	// Filters update and delete
	const CALLBACK_FILTER_POST_RELATIONSHIP_UPDATE = 'filter_post_relationship_update';
	const CALLBACK_FILTER_POST_RELATIONSHIP_DELETE = 'filter_post_relationship_delete';
	const CALLBACK_FILTER_RELATIONSHIP = 'filter_relationship_action';

	const CALLBACK_FILTER_POST_PRODUCT_ONSALE_UPDATE = 'filter_post_product_onsale_update';
	const CALLBACK_FILTER_POST_PRODUCT_ONSALE_DELETE = 'filter_post_product_onsale_delete';
	const CALLBACK_FILTER_POST_PRODUCT_PRICE_UPDATE = 'filter_post_product_price_update';
	const CALLBACK_FILTER_POST_PRODUCT_PRICE_DELETE = 'filter_post_product_price_delete';

	const CALLBACK_UPDATE_DESCRIPTION = 'update_description';
	const CALLBACK_UPDATE_CONTENT_SELECTION = 'update_content_selection';

	const CALLBACK_GET_RELATIONSHIPS_DATA = 'get_relationships_data';

	const CALLBACK_SCAN_VIEW_USAGE = 'scan_view_usage';
	const CALLBACK_CLEAR_VIEW_CACHE = 'clear_view_cache';

	const CALLBACK_SAVE_VIEWS_PAGE_BUILDERS_FRONTEND_CONTENT_SETTINGS = 'save_views_page_builders_frontend_content_settings';

	// View Editor settings update callbacks.
	const CALLBACK_UPDATE_VIEW_TITLE = 'update_view_title';
	const CALLBACK_UPDATE_VIEW_WRAPPER_SECTION = 'view_wrapper_section_update';

	//Layout wizard callbacks.
	const CALLBACK_GENERATE_VIEW_LOOP_OUTPUT = 'generate_view_loop_output';

	const CALLBACK_CREATE_CONTENT_TEMPLATE = 'create_content_template';
	const CALLBACK_DUPLICATE_CONTENT_TEMPLATE = 'duplicate_content_template';
	const CALLBACK_UPDATE_CONTENT_TEMPLATE_PROPERTIES = 'update_content_template_properties';
	const CALLBACK_CREATE_WORDPRESS_ARCHIVE = 'create_wordpress_archive';

	const CALLBACK_CREATE_LAYOUT_CONTENT_TEMPLATE = 'create_layout_content_template';
	const CALLBACK_ADD_INLINE_CONTENT_TEMPLATE = 'add_inline_content_template';

	// Editor blocks.
	const CALLBACK_GET_VIEW_BLOCK_PREVIEW = 'get_view_block_preview';
	const CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW = 'get_content_template_block_preview';
	const CALLBACK_GET_TAXONOMY_TERMS = 'get_taxonomy_terms';

	const CALLBACK_CREATE_FORM_TARGET_PAGE = 'create_form_target_page';

	// Deprecated calls, should fire deprecation notices
	const CALLBACK_GET_DPS_RELATED = 'get_dps_related';

	// Shortcodes GUI
	const CALLBACK_GET_CONDITIONAL_OUTPUT_DIALOG_DATA = 'get_conditional_output_dialog_data';

	// Toolset Settings page
	const CALLBACK_UPDATE_DEFAULT_USER_EDITOR = 'update_default_user_editor';
	const CALLBACK_UPDATE_DEFAULT_WPA_EDITOR = 'update_default_wpa_editor';

	const CALLBACK_UPDATE_MANAGE_META_TRANSIENT_METHOD = 'update_manage_meta_transient_method';
	const CALLBACK_UPDATE_MANAGE_META_TRANSIENT_MANUAL_FIRE = 'update_manage_meta_transient_manual_fire';

	const CALLBACK_UPDATE_EDITING_EXPERIENCE = 'update_editing_experience';

	const CALLBACK_UPDATE_TOOLSET_THEME_SETTINGS = 'update_toolset_theme_settings';

	const CT_KILL_DISSIDENT_POSTS = 'ct_kill_dissident_posts';

	/**
	 * Legacy nonce for query type view page
	 *
	 * @since m2m
	 */
	const LEGACY_VIEW_QUERY_TYPE_NONCE = 'view_query_type_nonce';


	/**
	 * List of callbacks.
	 *
	 * @var array
	 * @since m2m
	 */
	private static $callbacks = array(
		self::CALLBACK_CREATE_VIEW,
		self::CALLBACK_DUPLICATE_VIEW,

		self::CALLBACK_CREATE_PAGE_FOR_VIEW,

		self::CALLBACK_FILTER_POST_RELATIONSHIP_UPDATE,
		self::CALLBACK_FILTER_POST_RELATIONSHIP_DELETE,
		self::CALLBACK_FILTER_RELATIONSHIP,

		self::CALLBACK_FILTER_POST_PRODUCT_ONSALE_UPDATE,
		self::CALLBACK_FILTER_POST_PRODUCT_ONSALE_DELETE,

		self::CALLBACK_FILTER_POST_PRODUCT_PRICE_UPDATE,
		self::CALLBACK_FILTER_POST_PRODUCT_PRICE_DELETE,

		self::CALLBACK_UPDATE_DESCRIPTION,
		self::CALLBACK_UPDATE_CONTENT_SELECTION,

		self::CALLBACK_GET_RELATIONSHIPS_DATA,

		self::CALLBACK_SCAN_VIEW_USAGE,
		self::CALLBACK_CLEAR_VIEW_CACHE,

		self::CALLBACK_SAVE_VIEWS_PAGE_BUILDERS_FRONTEND_CONTENT_SETTINGS,

		self::CALLBACK_UPDATE_VIEW_TITLE,
		self::CALLBACK_UPDATE_VIEW_WRAPPER_SECTION,

		self::CALLBACK_GENERATE_VIEW_LOOP_OUTPUT,

		self::CALLBACK_CREATE_CONTENT_TEMPLATE,
		self::CALLBACK_DUPLICATE_CONTENT_TEMPLATE,
		self::CALLBACK_UPDATE_CONTENT_TEMPLATE_PROPERTIES,
		self::CALLBACK_CREATE_WORDPRESS_ARCHIVE,

		self::CALLBACK_CREATE_LAYOUT_CONTENT_TEMPLATE,
		self::CALLBACK_ADD_INLINE_CONTENT_TEMPLATE,

		self::CALLBACK_GET_VIEW_BLOCK_PREVIEW,
		self::CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW,
		self::CALLBACK_GET_TAXONOMY_TERMS,

		self::CALLBACK_CREATE_FORM_TARGET_PAGE,

		self::CALLBACK_GET_DPS_RELATED,

		self::CALLBACK_GET_CONDITIONAL_OUTPUT_DIALOG_DATA,

		self::CALLBACK_UPDATE_DEFAULT_USER_EDITOR,
		self::CALLBACK_UPDATE_DEFAULT_WPA_EDITOR,
		self::CALLBACK_UPDATE_MANAGE_META_TRANSIENT_METHOD,
		self::CALLBACK_UPDATE_MANAGE_META_TRANSIENT_MANUAL_FIRE,
		self::CALLBACK_UPDATE_EDITING_EXPERIENCE,
		self::CALLBACK_UPDATE_TOOLSET_THEME_SETTINGS,

		self::CT_KILL_DISSIDENT_POSTS
	);

	/**
	 * @var WPV_Ajax
	 * @since m2m
	 */
	private static $views_instance;


	public static function get_instance() {
		if( null === self::$views_instance ) {
			self::$views_instance = new self();
		}
		return self::$views_instance;
	}


	/**
	 * @inheritdoc
	 *
	 * @param bool $capitalized Capitalized text?.
	 * @return string
	 * @since m2m
	 */
	protected function get_plugin_slug( $capitalized = false ) {
		return ( $capitalized ? 'WPV' : 'wpv' );
	}


	/**
	 * @inheritdoc
	 * @return array
	 * @since m2m
	 */
	protected function get_callback_names() {
		return self::$callbacks;
	}


	/**
	 * Handles all initialization of everything except AJAX callbacks itself that is needed when
	 * we're DOING_AJAX.
	 *
	 * Since this is executed on every AJAX call, make sure it's as lightweight as possible.
	 *
	 * @since 2.1
	 */
	protected function additional_ajax_init() {
		// TODO Nothing yet.
	}
}
