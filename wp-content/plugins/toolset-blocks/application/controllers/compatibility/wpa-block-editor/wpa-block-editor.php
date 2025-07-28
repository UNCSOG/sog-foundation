<?php
namespace OTGS\Toolset\Views\Controller\Compatibility;

use \OTGS\Toolset\Views\Controller\Compatibility as Compatibility;
use OTGS\Toolset\Views\Controller\Constants\ViewBlockData;
use \OTGS\Toolset\Views\Services\WPMLService;
use \OTGS\Toolset\Views\Controllers\V1\Views as ViewsController;
use \OTGS\Toolset\Views\Controllers\V1\Wpa as WpaController;

/**
 * Class BlockEditorWPA
 *
 * This class enables editing WordPress Archives using the View block from within the new editor. As the View block needs
 * to be hosted inside a post, a new helper post type ("WPA Helper") is needed for this.
 * Along with the creation of the normal WPA post, the relevant WPA Helper post is created as well. Those two are linked
 * with a parent-child relationship.
 * As soon as both posts have been created, instead of been transferred to the old editor, the user is transferred to the
 * post edit screen of the WPA Helper post which by default contains an empty WPA block, already linked to the WPA created
 * moments before. This block is then enhanced with all the relevant default properties that a View block has, adjusted for
 * use in WordPress Archives.
 * While the WPA is formed in the block, the block also provides a preview of the output. The preview is constantly been
 * hijacked to always show the proper loop in the preview, according a preview loop selector on the Settings Sidebar.
 * The frontend rendering process is not affected at all. Since the WPA post has all the proper attributes and properties
 * coming from the backend, it keeps working as it previously did.
 *
 * @package OTGS\Toolset\Views\Controller\Compatibility
 *
 * @since 3.0
 */
class BlockEditorWPA extends Compatibility\Base {
	/** @var Toolset_Constants */
	private $constants;

	/** @var Toolset_Renderer */
	private $toolset_renderer;

	/** @var Toolset_Output_Template_Repository */
	private $template_repository;

	/** @var \WPV_Settings */
	private $wpv_settings;

	/** @var \WPV_WordPress_Archive_Frontend */
	private $wpv_view_archive_loop;

	/** @var \WPV_Editor_Loop_Selection */
	private $wpv_editor_loop_selection;

	/** @var callable */
	private $wpv_wordpress_archive_get_instance;

	/** @var callable */
	private $view_base_get_instance;

	/** @var \OTGS\Toolset\Common\Wordpress\WpSafeRedirect */
	private $safe_redirect;

	/** @var WPMLService */
	private $wpml_service;

	/** @var ViewsController */
	private $views_controller;

	/** @var \WPV_Ajax */
	private $wpv_ajax;

	/** @var int Holds the WPA id when adjusting the query of a WPA in the editor preview. */
	private $current_wpa_id;

	const WPA_HELPER_POST_TYPE = 'wpa-helper';
	const WPA_EDITOR_META_KEY = '_wpa_editor';

	public function __construct(
		\Toolset_Constants $constants,
		\Toolset_Renderer $toolset_renderer,
		\WPV_Output_Template_Repository $template_repository,
		\WPV_WordPress_Archive_Frontend $wpv_view_archive_loop,
		\WPV_Settings $wpv_settings,
		\WPV_Editor_Loop_Selection $wpv_editor_loop_selection,
		callable $wpv_wordpress_archive_get_instance,
		callable $view_base_get_instance,
		\OTGS\Toolset\Common\Wordpress\WpSafeRedirect $safe_redirect,
		WPMLService $wpml_service,
		ViewsController $views_controller,
		\WPV_Ajax $wpv_ajax
	) {
		$this->constants = $constants;
		$this->toolset_renderer = $toolset_renderer;
		$this->template_repository = $template_repository;
		$this->wpv_view_archive_loop = $wpv_view_archive_loop;
		$this->wpv_settings = $wpv_settings;
		$this->wpv_wordpress_archive_get_instance = $wpv_wordpress_archive_get_instance;
		$this->view_base_get_instance = $view_base_get_instance;
		$this->wpv_editor_loop_selection = $wpv_editor_loop_selection;
		$this->safe_redirect = $safe_redirect;
		$this->wpml_service = $wpml_service;
		$this->views_controller = $views_controller;
		$this->wpv_ajax = $wpv_ajax;
	}

	public function initialize() {
		// The WPA helper post type needs to be registered no matter what as otherwise PHP Notices occur when the DS cache,
		// tries to fetch the cache for such posts using REST request.
		add_filter( 'init', array( $this, 'register_wpa_helper_post_type' ) );

		// The WPA helper post type needs to be excluded from the Toolset own post types no matter what as otherwise they
		// will show up in several places for example the Custom Field Group post type assignment panel.
		add_filter( 'toolset_filter_exclude_own_post_types', array( $this, 'add_wpa_helper_post_type_in_toolset_exclude_list' ) );

		add_action( 'the_post', array( $this, 'maybe_add_wpa_helper_related_hooks' ) );

		add_action( 'admin_init', array( $this, 'redirect_wpa_helper_listing_page' ) );

		add_filter( 'register_post_type_args', array( $this, 'make_wpa_block_editor_editable' ), 10, 2 );

		add_filter( 'wpv_filter_wpv_disable_post_content_template_metabox', array( $this, 'remove_the_content_template_metabox' ), 10, 2 );

		add_filter( 'wpv_filter_wpv_get_wpa_helper_post', array( $this, 'maybe_get_helper_post_id' ) );

		add_filter( 'wpv_filter_wpa_edit_link', array( $this, 'convert_wpa_edit_link' ), 10, 2 );

		add_action( 'wpv_action_before_render_view_editor_shortcode', array( $this, 'maybe_set_wpa_query' ) );

		add_action( 'wpv_action_after_render_view_editor_shortcode', array( $this, 'maybe_revert_in_loop' ) );

		add_filter( 'wpv_filter_view_editor_first_item_id', array( $this, 'maybe_set_wpa_first_item_id' ), 10, 2 );

		add_action( 'before_delete_post', array( $this, 'before_delete_wpa_preview_post' ), 20 );

		add_action( 'before_delete_post', array( $this, 'scan_and_delete_orphaned_preview_posts' ), 20 );

		add_filter( 'wpv_filter_localize_view_editor_assets', array( $this, 'add_maybe_is_wpa_helper_info' ) );

		add_action( 'wpv_action_after_save_views_from_previews', array( $this, 'maybe_update_loop_selection' ), 10, 2 );

		add_filter( 'wpv_filter_view_block_data_from_db', array( $this, 'adjust_view_block_data_from_db' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'switch_editor' ) );

		add_filter( 'wpv_filter_converted_view_settings_for_backend', array( $this, 'convert_view_settings_for_wpa' ), 10, 2 );

		add_filter( 'toolset_theme_settings_force_backend_editor', array( $this, 'enable_theme_settings_integration' ) );

		add_filter( 'wpv_filter_override_view_layout_settings', array( $this, 'adapt_settings_for_translation' ), 10, 2 );

		// Compatibility: Adjust the frontend post type link for WordPress Archive helper posts. It changes the link to the one
		// of the first assigned loop.
		add_filter( 'post_type_link', array( $this, 'adjust_view_post_type_link' ), 10, 2 );

		// Compatibility: Remove the WPA Helper link if "adjust_view_post_type_link" produced an empty or invalid link.
		add_filter( 'wpml_document_view_item_link', array( $this, 'maybe_delete_wpa_helper_view_link' ), 10, 5 );

		// Compatibility: Adjusts the post type for Dynamic Sources API when inside a WordPress Archive helper post.
		add_filter( 'toolset/dynamic_sources/filters/post_type_for_source_context', array( $this, 'adjust_post_types_for_source_context_in_wpa_helper' ), 10, 2 );

		// Handle the post status sync between the WPA post and the WPA Helper post when the WPA Helper post is switched between "draft/publish".
		add_action( 'transition_post_status', array( $this, 'sync_post_status_for_wpas_on_helpers_transition' ), 10, 3 );

		if ( \Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID !== $this->wpv_settings->get_raw_value( 'default_wpa_editor', false ) ) {
			return;
		}

		// Tasks only available when default editor is Gutenberg
		add_filter( 'wpv_filter_wpa_created', array( $this, 'create_wpa_helper_post' ), 10, 2 );
	}

	/**
	 * Adding the hooks to append WPA related data to the View block localization array.
	 */
	public function maybe_add_wpa_helper_related_hooks() {
		if ( $this->is_wpa_helper() ) {
			add_filter( 'wpv_filter_localize_view_block_strings', array( $this, 'maybe_add_loop_selection_options' ) );

			add_filter( 'wpv_filter_localize_view_block_strings', array( $this, 'maybe_add_loop_links' ) );

			add_filter( 'wpv_filter_localize_view_block_strings', array( $this, 'add_switch_to_classic_url' ) );

			add_filter( 'wpv_filter_localize_view_block_strings', array( $this, 'add_theme_settings' ) );

			add_action( 'admin_head', array( $this, 'remove_all_metaboxes' ), PHP_INT_MAX );
		}
	}

	public function make_wpa_block_editor_editable( $args, $name ) {
		if ( 'view' === $name ) {
			$args['show_ui'] = true;
			$args['show_in_rest'] = true;
		}
		return $args;
	}

	private function is_wpa( $view_id ) {
		if (
			$view_id <= 0 ||
			! \WPV_View_Base::is_archive_view( $view_id )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Determines if the global or the given post is of type WPA Helper.
	 *
	 * @param null|\WP_Post $wpa_helper_post
	 *
	 * @return bool
	 */
	private function is_wpa_helper( $wpa_helper_post = null ) {
		if ( ! $wpa_helper_post ) {
			global $post;
			$wpa_helper_post = $post;
		}

		if ( ! $wpa_helper_post ) {
			return false;
		}

		return self::WPA_HELPER_POST_TYPE === $wpa_helper_post->post_type;
	}

	/**
	 * Creates the default post content for the WordPress Archive Helper post.
	 *
	 * @param array  $attributes The array of attributes to tbe injected in case of the WordPress Archive helper post recreation.
	 * @param string $content The content that might need to be injected in case of the WordPress Archive helper post recreation.
	 *
	 * @return string
	 */
	private function get_wpa_helper_post_content( $attributes = array(), $content = '' ) {
		$renderer = $this->toolset_renderer;

		if ( ! is_array( $attributes ) ) {
			$attributes = array( $attributes );
		}

		if ( empty( $attributes ) ) {
			$attributes = '';
		} else {
			// The array of attributes to be injected to the WPA markup. The {} characters will need to be trimmed as
			// the WordPress Archive helper post default template already contains them.
			$attributes = trim( wp_json_encode( $attributes ), '{}' );
		}

		$wpa_content = sprintf(
			$renderer->render(
				$this->template_repository->get( $this->constants->constant( 'WPV_Output_Template_Repository::WPA_EDITOR_BLOCK_DEFAULT_MARKUP' ) ),
				array( 'has-content' => ! empty( $content ) ),
				false
			),
			$attributes,
			$content
		);

		return $wpa_content;
	}

	/**
	 * Provides the WPA Helper post ID for the given WordPress Archive post ID.
	 *
	 * @param $wpa_id
	 *
	 * @return int
	 */
	public function maybe_get_helper_post_id( $wpa_id ) {
		if ( ! $wpa_id ) {
			return 0;
		}

		$args = array(
			'numberposts' => 1,
			'post_parent' => $wpa_id,
			'post_type' => self::WPA_HELPER_POST_TYPE,
		);

		$children = get_children( $args );

		if ( empty( $children ) ) {
			return $this->maybe_recreate_missing_helper_post( (int) $wpa_id );
		}

		return (int) array_keys( $children )[0];
	}

	/**
	 * It recreates both the WordPress Archive helper post and the WordPress Archive preview post when there is enough
	 * data saved on the actual WordPress Archive (View) post.
	 *
	 * @param int $wpa_id
	 *
	 * @return int
	 */
	private function maybe_recreate_missing_helper_post( $wpa_id ) {
		/** @var \WPV_WordPress_Archive $wpa */
		$wpa = call_user_func( $this->wpv_wordpress_archive_get_instance, $wpa_id );

		if ( ! $wpa || ! $wpa->get_is_gutenberg_wpa() ) {
			return $wpa_id;
		}

		if (
			0 === $wpa->view_data_parent_post_id ||
			0 === $wpa->view_data_initial_parent_post_id ||
			0 === $wpa->view_data_id ||
			0 === $wpa->view_data_preview_id ||
			'' === $wpa->view_data_view_template
		) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'Toolset Views: A WordPress Archive Helper post was attempted to be recreated but the process failed because the WordPress Archive data is corrupted for WordPress Archive: "%s"', $wpa->title ) );
			return $wpa_id;
		}

		// Create a dummy (no relevant content) WordPress Archive helper post...
		$wpa_helper_post_id = $this->create_wpa_helper_post( $wpa_id, $wpa );

		// ...but if the helper post is not created for some reason, return the WordPress Archive ID.
		if ( $wpa_id === (int) $wpa_helper_post_id ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'Toolset Views: A WordPress Archive Helper post was attempted to be recreated but the process failed when creating the helper post for the WordPress Archive: "%s"', $wpa->title ) );
			return $wpa_id;
		}

		$new_wpa_data = $wpa->view_data;

		// Update the WordPress Archive/View data with the IDs of the new WordPress Archive helper post.
		$new_wpa_data[ \WPV_View_Base ::VIEW_DATA_GENERAL ][ \WPV_View_Base::VIEW_DATA_GENERAL_PARENT_POST_ID ] = $wpa_helper_post_id;
		$new_wpa_data[ \WPV_View_Base::VIEW_DATA_GENERAL ][ \WPV_View_Base::VIEW_DATA_GENERAL_INITIAL_PARENT_POST_ID ] = $wpa_helper_post_id;

		// Proceed with the creation of a new WordPress Archive preview post.
		$wpa_preview_post_data = $this->views_controller->create_preview_item( $new_wpa_data, 'archive' );

		/** @var \WPV_WordPress_Archive $wpa_preview_post */
		$wpa_preview_post = call_user_func( $this->wpv_wordpress_archive_get_instance, $wpa_preview_post_data['id'] );

		// Make the new WordPress Archive preview post to match the actual WordPress Archive post.
		wp_update_post(
			array(
				'ID' => $wpa_preview_post_data['id'],
				'post_title' => $wpa->title,
				'post_content' => $wpa->content,
			)
		);

		$new_wpa_data[ \WPV_View_Base::VIEW_DATA_ID ] = $wpa_preview_post_data['id'];
		$new_wpa_data[ \WPV_View_Base::VIEW_DATA_GENERAL ][ \WPV_View_Base::VIEW_DATA_GENERAL_PREVIEW_ID ] = $wpa_preview_post_data['id'];

		// Set the updated WordPress Archive/View data for both the actual and the preview post.
		$wpa->view_data = $new_wpa_data;
		$wpa_preview_post->view_data = $new_wpa_data;

		// Add a post meta to the new WordPress Archive preview post to help for the identification as orphaned later.
		$wpa_preview_post->update_postmeta( WpaController::WPA_PREVIEW_OF_META_KEY, $wpa->slug );

		// And finally, set the post content of the helper post with the proper WordPress Archive block layout
		$helper_post_inner_content = $wpa->view_data_view_template;
		wp_update_post(
			array(
				'ID' => $wpa_helper_post_id,
				'post_content' => $this->get_wpa_helper_post_content(
					array(
						'reduxStoreId' => 'views-editor-1234567890',
						'viewId' => $wpa_id,
						'viewSlug' => $wpa->slug,
						'previewId' => $wpa_preview_post_data['id'],
						'wizardDone' => true,
						'wizardStep' => 3,
					),
					$helper_post_inner_content
				),
			)
		);

		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf( 'Toolset Views: The WordPress Archive Helper post had to be recreated for the WordPress Archive: "%s"', $wpa->title ) );

		return $wpa_helper_post_id;
	}

	/**
	 * Converts the WordPress Archive edit links in the WordPress Archive listing page.
	 *
	 * @param string      $wpa_edit_link
	 * @param null|string $wpa_id
	 *
	 * @return string|void|null
	 */
	public function convert_wpa_edit_link( $wpa_edit_link, $wpa_id = null ) {
		// Legacy name variable : WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase .
		global $WPV_settings; // phpcs:ignore

		$edit_link = $wpa_edit_link;
		if (
			( ! $wpa_id && \Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID === $WPV_settings->default_wpa_editor ) || // phpcs:ignore
			$this->is_gutenberg_editor( $wpa_id )
		) {
			$wpa_helper_id = $this->maybe_get_helper_post_id( $wpa_id );
			// Pass a context to get_edit_post_link different than 'display',
			// needed for the frontend admin bar WPA creation.
			$edit_link = $wpa_helper_id > 0 ? get_edit_post_link( $wpa_helper_id, 'link' ) : admin_url( 'post.php?action=edit&post=' );
		}

		return $edit_link;
	}

	/**
	 * Creates the WPA Helper post for a given WordPress Archive post.
	 *
	 * @param int                    $wpa_id The ID for the new WordPress Archive for which a new Helper post will be created.
	 * @param \WPV_WordPress_Archive $wpa    The WordPress Archive object.
	 *
	 * @return mixed
	 */
	public function create_wpa_helper_post( $wpa_id, $wpa ) {
		$helper_post = array(
			'post_title' => $wpa->title,
			'post_name' => $wpa->slug . '-' . self::WPA_HELPER_POST_TYPE,
			'post_content' => $this->get_wpa_helper_post_content(),
			'post_status' => 'publish',
			'post_type' => self::WPA_HELPER_POST_TYPE,
			'post_parent' => $wpa_id,
		);

		$wpa_helper_post_id = wp_insert_post( $helper_post );
		update_post_meta( $wpa_id, self::WPA_EDITOR_META_KEY, \Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID );

		return $wpa_helper_post_id ?: $wpa_id;
	}

	/**
	 * Registers the WPA Helper post type.
	 */
	public function register_wpa_helper_post_type() {
		$labels = array(
			'name' => _x( 'WordPress Archives Blocks', 'Post Type General Name', 'wpv-views' ),
			'singular_name' => _x( 'WordPress Archive', 'Post Type Singular Name', 'wpv-views' ),
			'menu_name' => _x( 'WordPress Archives Blocks', 'Admin Menu text', 'wpv-views' ),
			'name_admin_bar' => _x( 'WordPress Archives Blocks', 'Add New on Toolbar', 'wpv-views' ),
			'archives' => __( 'WordPress Archives Blocks Archives', 'wpv-views' ),
			'attributes' => __( 'WordPress Archives Blocks Attributes', 'wpv-views' ),
			'parent_item_colon' => __( 'Parent WordPress Archives Block:', 'wpv-views' ),
			'all_items' => __( 'All WordPress Archives Blocks', 'wpv-views' ),
			'add_new_item' => __( 'Add New WordPress Archives Block', 'wpv-views' ),
			'add_new' => __( 'Add New', 'wpv-views' ),
			'new_item' => __( 'New WordPress Archives Block', 'wpv-views' ),
			'edit_item' => __( 'Edit WordPress Archives Block', 'wpv-views' ),
			'update_item' => __( 'Update WordPress Archives Block', 'wpv-views' ),
			'view_item' => __( 'View WordPress Archives Block', 'wpv-views' ),
			'view_items' => __( 'View WordPress Archives Blocks', 'wpv-views' ),
			'search_items' => __( 'Search WordPress Archives Blocks', 'wpv-views' ),
			'not_found' => __( 'Not found', 'wpv-views' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'wpv-views' ),
			'featured_image' => __( 'Featured Image', 'wpv-views' ),
			'set_featured_image' => __( 'Set featured image', 'wpv-views' ),
			'remove_featured_image' => __( 'Remove featured image', 'wpv-views' ),
			'use_featured_image' => __( 'Use as featured image', 'wpv-views' ),
			'insert_into_item' => __( 'Insert into WordPress Archives Block', 'wpv-views' ),
			'uploaded_to_this_item' => __( 'Uploaded to this WordPress Archives Block', 'wpv-views' ),
			'items_list' => __( 'WordPress Archives Blocks list', 'wpv-views' ),
			'items_list_navigation' => __( 'WordPress Archives Blocks list navigation', 'wpv-views' ),
			'filter_items_list' => __( 'Filter WordPress Archives Blocks list', 'wpv-views' ),
		);
		$args = array(
			'label' => __( 'WordPress Archives Blocks', 'wpv-views' ),
			'description' => '',
			'labels' => $labels,
			'menu_icon' => '',
			'supports' => array( 'title', 'editor', 'author' ),
			'taxonomies' => array(),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'menu_position' => 5,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false,
			'can_export' => false,
			'has_archive' => false,
			'hierarchical' => true,
			'exclude_from_search' => true,
			'show_in_rest' => true,
			'publicly_queryable' => false,
			'capability_type' => 'post',
		);
		register_post_type( self::WPA_HELPER_POST_TYPE, $args );
	}

	/**
	 * Adds the WPA helper post to the Toolset Exclude list of post types.
	 *
	 * @param $exclude_list
	 *
	 * @return array
	 */
	public function add_wpa_helper_post_type_in_toolset_exclude_list( $exclude_list ) {
		$exclude_list[] = self::WPA_HELPER_POST_TYPE;

		return $exclude_list;
	}

	/**
	 * Converts the View settings array into WordPress Archive settings.
	 *
	 * @param $view_settings
	 * @param $view_data
	 *
	 * @return mixed
	 */
	public function convert_view_settings_for_wpa( $view_settings, $view_data ) {
		$view_id = (int) toolset_getnest( $view_data, array( 'general', 'id' ), 0 );
		if (
			toolset_getarr( $view_data, 'is_wpa', false ) ||
			$this->is_wpa( $view_id )
		) {
			$view_settings['view-query-mode'] = 'archive';
			$wpa = call_user_func( $this->wpv_wordpress_archive_get_instance, $view_id );
			$view_settings[ \WPV_View_Embedded::VIEW_SETTINGS_PURPOSE ] = $wpa->view_settings[ \WPV_View_Embedded::VIEW_SETTINGS_PURPOSE ];
		}

		return $view_settings;
	}

	/**
	 * Removes the Content Template metabox from the WPA Helper edit page.
	 *
	 * @param $status
	 * @param $post
	 *
	 * @return bool
	 */
	public function remove_the_content_template_metabox( $status, $post ) {
		if ( self::WPA_HELPER_POST_TYPE === $post->post_type ) {
			return true;
		}

		return $status;
	}

	/**
	 * Sets the needed parameters for the WordPress Archive to be rendered properly on the editor preview.
	 *
	 * @param $wpa_id
	 */
	public function maybe_set_wpa_query( $wpa_id ) {
		if ( ! $this->is_wpa( $wpa_id ) ) {
			return;
		}

		$wpa = call_user_func( $this->wpv_wordpress_archive_get_instance, $wpa_id );
		$wpa_data = $wpa->get_postmeta( '_wpv_view_data' );

		$this->wpv_view_archive_loop->in_the_loop = true;
		$args = array(
			'posts_per_page' => toolset_getnest( $wpa_data, array( 'pagination', 'page_size' ) ),
		);

		$selected_loop = toolset_getnest( $wpa_data, array( ViewBlockData::VIEW_BLOCK_CONTENT_SELECTION_DATA, ViewBlockData::VIEW_BLOCK_CONTENT_SELECTION_WPA_LOOPS_PREVIEW ) );
		$assigned_loops = toolset_getnest( $wpa_data, array( ViewBlockData::VIEW_BLOCK_CONTENT_SELECTION_DATA, ViewBlockData::VIEW_BLOCK_CONTENT_SELECTION_WPA_LOOPS ) );

		$wpa_loop_options = toolset_getnest( $wpa_data, array( ViewBlockData::VIEW_BLOCK_CONTENT_SELECTION_DATA, ViewBlockData::VIEW_BLOCK_CONTENT_SELECTION_WPA_LOOPS_OPTIONS ), [] );
		$assigned_loop_for_specific_taxonomy_terms = [];
		foreach ( $wpa_loop_options as $wpa_loop_option ) {
			foreach ( $wpa_loop_option['values'] as $term ) {
				array_push(
					$assigned_loop_for_specific_taxonomy_terms,
					$wpa_loop_option['slug'] . '#' . $term['slug']
				);
			}
		}


		// For the cases where the preview loop hasn't been set or the set loop is no longer in the list of assigned loops...
		if (
			! $selected_loop ||
			! (
				in_array( $selected_loop, $assigned_loops, true ) ||
				in_array( $selected_loop, $assigned_loop_for_specific_taxonomy_terms, true )
			)
		) {
			// ...select the first loop in the assigned list or null.
			$selected_loop = isset( $assigned_loops[0] ) ? $assigned_loops[0] : null;
			if ( ! $selected_loop ) {
				$selected_loop = isset( $assigned_loop_for_specific_taxonomy_terms[0] ) ? $assigned_loop_for_specific_taxonomy_terms[0] : null;
			}
		}

		// The selected loop is a post type, so the query will fetch only posts of this post type.
		if ( get_post_type_object( $selected_loop ) ) {
			$args['post_type'] = $selected_loop;
		}

		// The selected loop is a taxonomy, so the query will fetch only posts related to this taxonomy.
		if ( get_taxonomy( $selected_loop ) ) {
			// First the ID of a term with posts is retrieved.
			$terms_with_posts = get_terms(
				$selected_loop,
				array(
					'hide_empty' => 1,
					'number' => 1,
				)
			);

			// If the retrieved term has posts...
			if (
				false === $terms_with_posts instanceof WP_Error &&
				is_array( $terms_with_posts ) &&
				! empty( $terms_with_posts )
			) {
				// ...the retrieved term slug is fed to the query in order to get posts that has that term assigned to them.
				$terms_with_posts = array_values( $terms_with_posts );
				$term = array_shift( $terms_with_posts );
				$args['tax_query'] = array(
					array(
						'taxonomy' => $selected_loop,
						'field' => 'slug',
						'terms' => $term,
					),
				);
			}
		}

		if ( 'author-page' === $selected_loop ) {
			// todo: Make the author for the author archive page preview dynamic.
			// Currently, the currently logged-in user ID is used to build the preview for the author page, but a select
			// control offering the site's users needs to be placed in the inspector of the block.
			$args['author'] = get_current_user_id();
		}

		if (
			in_array( $selected_loop, $assigned_loop_for_specific_taxonomy_terms, true ) &&
			0 < count( $wpa_loop_options )
		) {
			$selected_loop_array = explode( '#', $selected_loop );
			$taxonomy_slug = $selected_loop_array[0];
			$term_slug = $selected_loop_array[1];

			$args['tax_query'] = [
				[
					'taxonomy' => $taxonomy_slug,
					'field' => 'slug',
					'terms' => $term_slug,
				],
			];
		}

		/**
		 * Adding a "pre_get_posts" action handler before adjusting the query for the WPA editor preview, to take into
		 * account everything that happens on the same action for the frontend of a WPA, for example query filters.
		 */
		add_action( 'pre_get_posts', array( $this, 'view_editor_wpa_query_pre_get_posts' ) );

		$this->current_wpa_id = $wpa_id;

		$this->wpv_view_archive_loop->query = new \WP_Query( $args );

		$this->current_wpa_id = null;

		/**
		 * Remove the "pre_get_posts" action handler after adjusting the query for the WPA editor preview, since it's no longer necessary.
		 */
		remove_action( 'pre_get_posts', array( $this, 'view_editor_wpa_query_pre_get_posts' ) );
	}

	/**
	 * Runs all the hooks that need to be run before getting the post when rendering the WPA editor preview.
	 *
	 * @param \WP_Query $query
	 */
	public function view_editor_wpa_query_pre_get_posts( $query ) {
		$wpa_id = $this->current_wpa_id;
		$wpa_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $wpa_id );

		do_action( 'wpv_action_apply_archive_query_settings', $query, $wpa_settings, $wpa_id );
	}

	/**
	 * Reverts "in_the_loop" property for the WordPress Archive loop.
	 *
	 * This is needed because on the editor when the preview of a WordPress Archive query is built to be previewed, the
	 * "in_the_loop" property is manually set to "true".
	 *
	 * @param $wpa_id
	 */
	public function maybe_revert_in_loop( $wpa_id  ) {
		if ( ! $this->is_wpa( $wpa_id ) ) {
			return;
		}

		$this->wpv_view_archive_loop->in_the_loop = false;
	}

	/**
	 * Sets the first item ID for the WordPress Archive block in order to render the WordPress Archive in proper order.
	 *
	 * @param $first_item_id
	 * @param $wpa_id
	 *
	 * @return mixed
	 */
	public function maybe_set_wpa_first_item_id( $first_item_id, $wpa_id ) {
		if ( ! $this->is_wpa( $wpa_id ) ) {
			return $first_item_id;
		}

		$wpa_posts = $this->wpv_view_archive_loop->query->posts;
		if ( count( $wpa_posts ) ) {
			$first_item_id = $wpa_posts[0]->ID;
		}

		return $first_item_id;
	}

	/**
	 * Handles the deletion of the WPA Helper post whenever a WordPress Archive post is been deleted.
	 *
	 * @param $wpa_id
	 */
	public function before_delete_wpa_preview_post( $wpa_id ) {
		if ( ! $this->is_wpa( $wpa_id ) || ! $this->is_gutenberg_editor( $wpa_id ) ) {
			return;
		}

		$args = array(
			'post_parent' => $wpa_id,
			'post_type' => self::WPA_HELPER_POST_TYPE,
		);

		$wpa_helper = get_posts( $args );

		if (
			is_array( $wpa_helper ) &&
			count( $wpa_helper ) > 0
		) {
			// Delete all the Children of the Parent Page
			foreach ( $wpa_helper as $wpa_helper_post ) {
				wp_delete_post( $wpa_helper_post->ID, true );
			}
		}
	}

	/**
	 * Scans for orphaned preview post for the given WPA and deletes them if any.
	 *
	 * The WordPress Archive block links to the relevant preview post upon WPA Helper post save. If this doesn't happen
	 * and the page is refreshed before that, the created preview post remains orphan and when the page loads a new one
	 * is created.
	 *
	 * @param int $wpa_id ID of the WPA whose preview posts should be scanned.
	 */
	public function scan_and_delete_orphaned_preview_posts( $wpa_id ) {
		if ( ! $this->is_wpa( $wpa_id ) ) {
			return;
		}

		$wpa = call_user_func( $this->wpv_wordpress_archive_get_instance, $wpa_id );

		if (
			'trash' === $wpa->post_status &&
			'__trashed' === substr( $wpa->slug, -9 )
		) {
			$wpa_slug = substr( $wpa->slug, 0, -9 );
		} else {
			$wpa_slug = $wpa->slug;
		}

		$args = array(
			'post_type' => \WPV_View_Base::POST_TYPE,
			'post_status' => 'draft',
			'meta_query' => array(
				array(
					'key' => \OTGS\Toolset\Views\Controllers\V1\Wpa::WPA_PREVIEW_OF_META_KEY,
					'value' => $wpa_slug,
					'compare' => '=',
				),
			),
		);

		$other_preview_posts = new \WP_Query( $args );
		foreach ( $other_preview_posts->get_posts() as $other_preview_post ) {
			wp_delete_post( $other_preview_post->ID );
		}
	}

	/**
	 * Specifies in the View/WordPress Archive block localization data whether the current block is a View or a WordPress
	 * Archive block.
	 *
	 * @param  array $localization_data
	 *
	 * @return array
	 */
	public function add_maybe_is_wpa_helper_info( $localization_data ) {
		global $post;

		$localization_data['isWpaHelper'] = $post && $this->is_wpa_helper( $post );

		return $localization_data;
	}

	/**
	 * Pre-populates the available Loops and adds them to the localization data of the View/WordPress Archive block.
	 *
	 * @param  array $localization_data
	 *
	 * @return array
	 */
	public function maybe_add_loop_selection_options( $localization_data ) {
		$localization_data['loopSelectionOptions'] = $this->get_wpa_loops();

		return $localization_data;
	}

	/*
	 * Populates the available Loops.
	 */
	private function get_wpa_loops() {
		$wp_loops = \WPV_Editor_Loop_Selection::get_wp_loops();
		$pt_loops = \WPV_Editor_Loop_Selection::get_pt_loops();
		$taxonomy_loops = array_map(
			function( $taxonomy ) {
				return (array) $taxonomy;
			},
			\WPV_Editor_Loop_Selection::get_taxonomy_loops()
		);


		$result = array(
			'wordpressLoops' => array(),
			'postTypeLoops' => array(),
			'taxonomyLoops' => array(),
		);

		foreach ( $wp_loops as $key => $wp_loop ) {
			$result['wordpressLoops'][] = array(
				'value' => $key,
				'label' => $wp_loop['display_name'],
				'name' => 'wpv-view-loop-' . $key,
				'url' => $wp_loop[ 'archive_url' ],
			);
		}

		foreach ( $pt_loops as $pt_loops ) {
			$result['postTypeLoops'][] = array(
				'value' => $pt_loops['name'],
				'label' => $pt_loops['display_name'],
				'name' => 'wpv-view-loop-' . $pt_loops['loop'],
				'url' => $pt_loops[ 'archive_url' ],
			);
		}

		foreach ( $taxonomy_loops as $taxonomy_loop ) {
			// Get ID of a term that has some posts, if such term exists.
			$terms_with_posts = get_terms(
				$taxonomy_loop['name'],
				array(
					'hide_empty' => 1,
					'number' => 1,
				)
			);

			if (
				$terms_with_posts instanceof WP_Error ||
				! is_array( $terms_with_posts ) ||
				empty( $terms_with_posts )
			) {
				$tax_link = null;
			} else {
				$terms_with_posts = array_values( $terms_with_posts );
				$term = array_shift( $terms_with_posts );
				$tax_link = get_term_link( $term, $taxonomy_loop['name'] );
			}

			$result['taxonomyLoops'][] = array(
				'value' => $taxonomy_loop['name'],
				'label' => $taxonomy_loop['label'],
				'singular' => $taxonomy_loop['labels']->singular_name,
				'options_available' => null !== $tax_link,
				'options_url' => wpv_get_views_ajaxurl(),
				'options_params' => array(
					'action' => $this->wpv_ajax->get_action_js_name( \WPV_Ajax::CALLBACK_GET_TAXONOMY_TERMS ),
					'wpnonce' => wp_create_nonce( \WPV_Ajax::CALLBACK_GET_TAXONOMY_TERMS ),
					'taxonomy' => $taxonomy_loop['name'],
				),
				'name' => 'wpv-view-taxonomy-loop-' . $taxonomy_loop['name'],
				'url' => $tax_link,
			);
		}

		return $result;
	}

	/**
	 * Flattens the loops array.
	 *
	 * @return array
	 */
	private function get_flattened_wpa_loops() {
		$loops = $this->get_wpa_loops();
		return array_merge(
			$loops['wordpressLoops'],
			$loops['postTypeLoops'],
			$loops['taxonomyLoops']
		);
	}

	/**
	 * Handles the saving of the Loop selection whenever the WordPress Archive block is saved.
	 * @param $wpa_id
	 * @param $wpa_data
	 */
	public function maybe_update_loop_selection( $wpa_id, $wpa_data ) {
		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			! $this->is_wpa( $wpa_id ) ||
			! $this->is_gutenberg_editor( $wpa_id ) ||
			'publish' !== get_post_status( $wpa_id ) || // The loops won't be updated for the WordPress Archive preview post which is in "draft" status.
			toolset_getnest( $wpa_data, array( 'general', 'id' ), false ) !== $wpa_id
		) {
			return;
		}

		$loops = $this->get_flattened_wpa_loops();

		$assigned_loops = toolset_getnest( $wpa_data, array( 'content_selection', 'wpaLoops' ) );
		$assigned_loops_options = toolset_getnest( $wpa_data, array( 'content_selection', 'wpaLoopsOptions' ), [] );

		$assigned_loops_for_saving = array_reduce(
			$assigned_loops,
			function( $result, $assigned_loop ) use ( $loops ) {
				$maybe_loop_index = array_search( $assigned_loop, array_column( $loops, 'value' ), true );
				if ( false !== $maybe_loop_index ) {
					$result[ $loops[ $maybe_loop_index ]['name'] ] = 'on';
				}
				return $result;
			},
			array()
		);

		// Reduce the assigned loop options to settings for wpaLoops with selected terms.
		$assigned_loops_options_for_saving = array_reduce(
			$assigned_loops_options,
			function( $result, $assigned_loop ) use ( $loops ) {

				// Check if the current specific loop is actually available
				$maybe_loop_index = array_search( $assigned_loop['slug'], array_column( $loops, 'value' ), true );
				if ( false !== $maybe_loop_index ) {

					// Iterate all the options (terms) selected in this specific loop,
					foreach ( $assigned_loop['values'] as $loop_options ) {

						// The setting key name is generated and added for each option selected.
						$setting_name = sprintf(
							\WPV_WordPress_Archive_Frontend::SETTINGS_TAXONOMY_LOOP_OPTION,
							$assigned_loop['slug'],
							$loop_options['slug']
						);
						$result[ $setting_name ] = 'on';
					}
				}
				return $result;
			},
			array()
		);

		$this->wpv_view_archive_loop->update_view_archive_settings( $wpa_id, $assigned_loops_for_saving + $assigned_loops_options_for_saving );
	}

	public function adjust_view_block_data_from_db( $view_data, $wpa_id ) {
		$wpa = call_user_func( $this->view_base_get_instance, $wpa_id );
		// we need to set the view ID in all cases, because it could be changed on import/export and recreated from slug
		$view_data['general']['id'] = $wpa_id;
		if ( $wpa->is_a_wordpress_archive() ) {
			$assigned_loops = array_map(
				function( $item ) {
					return isset( $item['post_type_name'] ) ? $item['post_type_name'] : $item['slug'];
				},
				$wpa->get_assigned_loops()
			);
			$assigned_loop_options = array_map(
				function( $item ) {
					$values = array_reduce(
						$item['wpa_options'],
						function( $result, $term_slug ) use ( $item ) {
							$term = get_term_by( 'slug', $term_slug, $item['slug'] );

							if ( false !== $term ) {
								$term_link = get_term_link( $term_slug, $term->taxonomy );

								$result[] = array(
									'value' => $term->term_id,
									'slug' => $term->slug,
									'label' => $term->name,
									'link' => ! is_wp_error( $term_link ) ? $term_link : null,
								);
							}

							return $result;
						},
						array()
					);

					return array(
						'slug' => $item['slug'],
						'values' => $values,
					);
				},
				$wpa->get_assigned_loop_options()
			);

			$view_data['general']['name'] = $wpa->title;
			$view_data['general']['slug'] = $wpa->slug;
			$view_data['general']['isWpa'] = true;
			if ( ! $view_data['pagination']['enable_pagination'] ) {
				$view_data['pagination']['type'] = 'disabled';
			}
			$view_data['content_selection']['wpaLoops'] = $assigned_loops;
			$view_data['content_selection']['wpaLoopsOptions'] = $assigned_loop_options;
			$default_preview_loop = isset( $assigned_loops[0] ) ? $assigned_loops[0] : null;
			$view_data['content_selection']['wpaLoopsPreview'] = toolset_getnest( $view_data, array( 'content_selection', 'wpaLoopsPreview' ), $default_preview_loop );
			$view_data['loop']['wizard_done'] = true;
			$view_data['loop']['wizard_step'] = 3;
		}

		return $view_data;
	}

	/**
	 * Adds the WordPress Archive loop preview links to the localization for use in the WPA Helper post edit page.
	 *
	 * @param array $localization_data The localization data.
	 *
	 * @return array
	 */
	public function maybe_add_loop_links( $localization_data ) {
		$loops = $this->get_flattened_wpa_loops();
		$links = array();

		foreach ( $loops as $loop ) {
			$links[ $loop['value'] ] = $loop['url'];
		}

		$localization_data['archiveLinks'] = $links;

		return $localization_data;
	}

	/**
	 * It is triggered when a post is trashed. Checks if the trashed post is a WPA Helper post and if so, it reverts back
	 * to its proper state ("published") and handles the trashing of the WPA post it's linked to.
	 * The trashing, apart from the status change for the WPA post, also clears the assignment of it to any post loops.
	 */
	private function maybe_wpa_helper_post_trashed() {
		$is_trashed = sanitize_text_field( toolset_getget( 'trashed', false ) );

		if ( ! $is_trashed ) {
			return;
		}

		$post_ids = explode( ',', sanitize_text_field( toolset_getget( 'ids', array() ) ) );
		if ( count( $post_ids ) > 1 ) {
			return;
		}

		$post_id = $post_ids[0];

		if ( self::WPA_HELPER_POST_TYPE === get_post_type( $post_id ) ) {
			// Restore the WPA helper post to its proper state (always "published").
			wp_update_post(
				array(
					'ID' => $post_id,
					'post_status' => 'publish',
				)
			);

			// Trash the actual WPA post.
			$wpa_helper_post = get_post( $post_id );
			wp_update_post(
				array(
					'ID' => $wpa_helper_post->post_parent,
					'post_status' => 'trash',
				)
			);

			// Remove any assignments to loops for the WPA post.
			$settings_array = $this->wpv_settings->get();
			if ( ! empty( $settings_array ) ) {
				foreach ( $settings_array as $option_name => $option_value ) {
					if (
						0 === strpos( $option_name, 'view_' ) &&
						(int) $option_value === (int) $wpa_helper_post->post_parent
					) {
						$this->wpv_settings[ $option_name ] = 0;
					}
				}
				$this->wpv_settings->save();
			}
		}
	}

	/**
	 * Returns if the WPA has been created using Gutenberg editor
	 *
	 * @param int $wpa_id WPA ID.
	 * @return boolean
	 */
	private function is_gutenberg_editor( $wpa_id ) {
		$wpa_editor = get_post_meta( $wpa_id, self::WPA_EDITOR_META_KEY, true );
		return \Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID === $wpa_editor;
	}

	/**
	 * Handles WPA switch editors
	 */
	public function switch_editor() {
		$change_editor = toolset_getget( 'wpa_change_editor' );
		if ( \Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID === $change_editor ) {
			$this->switch_to_classic_editor();
		} elseif ( 'basic' === $change_editor ) {
			$this->switch_to_block_editor();
		}

	}

	/**
	 * Switches from GB WPA to a classic one
	 */
	private function switch_to_classic_editor() {
		$wpa_id = toolset_getget( 'view_id' );
		if ( $this->is_gutenberg_editor( $wpa_id ) ) {
			// Handles wpa-helper.
			$this->before_delete_wpa_preview_post( $wpa_id );
			$this->scan_and_delete_orphaned_preview_posts( $wpa_id );
			delete_post_meta( $wpa_id, self::WPA_EDITOR_META_KEY );

			// Handles wpa settings.
			$meta = get_post_meta( $wpa_id, '_wpv_layout_settings', true );
			$empty_loop_output = \WPV_View_Base::generate_loop_output();
			$meta['layout_meta_html'] = $empty_loop_output['loop_output_settings']['layout_meta_html'];
			update_post_meta( $wpa_id, '_wpv_layout_settings', $meta );

			$snapshot = get_post_meta( $wpa_id, '_wpa_snapshot', true );
			delete_post_meta( $wpa_id, '_wpv_view_data' );
			delete_post_meta( $wpa_id, '_wpa_snapshot' );

			if ( $snapshot ) {
				$meta['real_fields'] = $snapshot;
				update_post_meta( $wpa_id, '_wpv_layout_settings', $meta );
			}

			$settings = get_post_meta( $wpa_id, '_wpv_settings', true );
			$settings['view-query-mode'] = 'archive';
			update_post_meta( $wpa_id, '_wpv_settings', $settings );

			// Handles wpa content.
			$wpa = get_post( $wpa_id );
			$wpa->post_content = "[wpv-layout-meta-html]\n[wpv-filter-meta-html]";
			wp_update_post( $wpa );
		}
	}

	/**
	 * Redirects the user if he tries to access the WPA Helper post listing page.
	 */
	public function redirect_wpa_helper_listing_page() {
		global $pagenow;
		/* Check current admin page. */
		if (
			'edit.php' === $pagenow &&
			self::WPA_HELPER_POST_TYPE === sanitize_text_field( toolset_getget( 'post_type', '' ) )
		) {
			$this->maybe_wpa_helper_post_trashed();

			$redirect_url = add_query_arg(
				array(
					'page' => 'view-archives',
				),
				admin_url( 'admin.php' )
			);

			if ( $this->safe_redirect->wp_safe_redirect( $redirect_url ) ) {
				exit;
			}
		}
	}

	/**
	 * Switches from Classic editor to GB
	 */
	private function switch_to_block_editor() {
		$wpa_id = toolset_getget( 'view_id' );
		if ( ! $this->is_gutenberg_editor( $wpa_id ) ) {
			update_post_meta( $wpa_id, self::WPA_EDITOR_META_KEY, \Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID );

			/** @var \WPV_WordPress_Archive $wpa */
			$wpa = call_user_func( $this->view_base_get_instance, $wpa_id );

			$wp_helper_id = $this->create_wpa_helper_post( $wpa_id, $wpa );
			$meta = get_post_meta( $wpa_id, '_wpv_layout_settings', true );
			if ( isset( $meta['real_fields'] ) ) {
				update_post_meta( $wpa_id, '_wpa_snapshot', $meta['real_fields'] );
			}

			$settings = get_post_meta( $wpa_id, '_wpv_settings', true );
			$settings['view-query-mode'] = 'archive';
			update_post_meta( $wpa_id, '_wpv_settings', $settings );

			$edit_link = get_edit_post_link( $wp_helper_id, 'none' );

			if ( $this->safe_redirect->wp_safe_redirect( $edit_link ) ) {
				exit;
			}
		}
	}

	/**
	 * Adds the URL for switching the WordPress Archive editor for the current WordPress Archive back to the legacy one.
	 *
	 * @param  array $localization_data
	 *
	 * @return array
	 */
	public function add_switch_to_classic_url( $localization_data ) {
		global $post;

		if (
			! $post ||
			! isset( $post->post_parent )
		) {
			return $localization_data;
		}

		$localization_data['switchToClassicUrl'] = admin_url( 'admin.php?page=view-archives-editor&view_id=' . $post->post_parent ) . '&wpa_change_editor=gutenberg';
		$localization_data['includeSwitchToClassicUrl'] = ( 'mixed' === wpv_get_views_editing_experience() ) ? '1' : '0';
		return $localization_data;
	}

	/**
	 * Remove all the meta boxes because first no meta boxes are needed when editing a WPA but also interferes with the proper
	 * execution of the WPA Helper trash post workflow.
	 *
	 * The WPA helper post is marked as trashed, then the actual WPA post is marked as trashed and the helper post is reverted
	 * to public, but the when GB saves the meta box data, the helper post is turned again back to trashed, because GB!
	 */
	public function remove_all_metaboxes() {
		global $wp_meta_boxes;

		// If WPML is active then let translation metabox.
		if ( function_exists( 'icl_object_id' ) && isset( $wp_meta_boxes[ self::WPA_HELPER_POST_TYPE ]['side']['high']['icl_div'] ) ) {
			$wp_meta_boxes[ self::WPA_HELPER_POST_TYPE ] = [ // phpcs:ignore
				'side' => [
					'high' => [ 'icl_div' => $wp_meta_boxes[ self::WPA_HELPER_POST_TYPE ]['side']['high']['icl_div'] ],
				],
			];
		} else {
			// Removing all the meta boxes for the WordPress Archive helper post type.
			unset( $wp_meta_boxes[ self::WPA_HELPER_POST_TYPE ] );
		}
	}

	/**
	 * Modifies WPA settings if WPML is installed
	 *
	 * @param array $settings View settings.
	 * @param int   $wpa_id WPA ID.
	 * @return array
	 */
	public function adapt_settings_for_translation( $settings, $wpa_id ) {
		// If WPML is active.
		$wpml_active_and_configured = apply_filters( 'wpml_setting', false, 'setup_complete' );

		// is_admin() is always true for ajax calls. Also Check if it's a pagination ajax call.
		$is_ajax_pagination = wp_doing_ajax() &&
							  array_key_exists( 'action', $_REQUEST ) &&
							in_array(
								$_REQUEST['action'],
								[ 'wpv_get_view_query_results', 'wpv_get_archive_query_results' ],
								true
							);
		$is_frontend_call_or_ajax_call = ! is_admin() || $is_ajax_pagination;

		if ( $is_frontend_call_or_ajax_call && $wpml_active_and_configured && $this->is_wpa( $wpa_id ) && isset( $settings['layout_meta_html'] ) ) {
			$helper_id = $this->maybe_get_helper_post_id( $wpa_id );
			$translated_helper_id = apply_filters( 'wpml_object_id', $helper_id, self::WPA_HELPER_POST_TYPE, true );
			if ( $helper_id !== $translated_helper_id ) {
				$translated_helper = get_post( $translated_helper_id );
				$settings = $this->wpml_service->update_settings_from_html( $translated_helper->post_content, $settings );
			}
		}
		return $settings;
	}

	/**
	 * Fakes the enabling of the theme settings integration for the case where a WPA helper post is edited, in order to
	 * offer the theme settings on the WPA block.
	 *
	 * @param  bool $condition
	 *
	 * @return bool
	 */
	public function enable_theme_settings_integration( $condition ) {
		if ( ! is_admin() ) {
			return $condition;
		}

		$action = sanitize_text_field( toolset_getget( 'action', false ) );

		if ( ! $action || 'edit' !== $action ) {
			return $condition;
		}

		$post_id = sanitize_text_field( toolset_getget( 'post', false ) );

		if ( ! $post_id || self::WPA_HELPER_POST_TYPE !== get_post_type( $post_id ) ) {
			return $condition;
		}

		return true;
	}

	/**
	 * Adds the theme settings related data.
	 *
	 * @param  array $localization_data
	 *
	 * @return array
	 */
	public function add_theme_settings( $localization_data ) {
		$theme_settings = apply_filters( 'wpv_filter_get_theme_settings', array() );

		if ( empty( $theme_settings ) ) {
			return $localization_data;
		}

		$localization_data['themeName'] = $theme_settings['theme_name'];
		$localization_data['themeSlug'] = $theme_settings['theme_slug'];
		$localization_data['collections'] = $theme_settings['collections'];

		return $localization_data;
	}

	/**
	 * Adjusts the frontend post link for WordPress Archive helper posts.
	 * It changes the link to the one of the first assigned loop.
	 * Mostly to be used in the Translation Manager main page and in the Translations page of WPML.
	 *
	 * @param string   $post_link The actual post link.
	 * @param \WP_Post $post      The WP_Post object.
	 *
	 * @return string|null The filtered post link.
	 *
	 * @throws \Exception
	 */
	public function adjust_view_post_type_link( $post_link, $post ) {
		if ( ! $this->is_wpa_helper( $post ) ) {
			return $post_link;
		}

		$wpa = call_user_func( $this->view_base_get_instance, $post->post_parent );

		// $wpa can be null here when the translation WPA Helper post is passed through the "adjust_view_post_type_link".
		// In this case we need to switch to the original WPA Helper post for the rest of the process.
		if ( ! $wpa ) {
			$original_wpa_helper_post_id = apply_filters( 'wpv_filter_get_original_language_ct_post_id_from_translation', $post->ID );

			if ( $original_wpa_helper_post_id <= 0 ) {
				return null;
			}

			$original_wpa_helper_post = get_post( $original_wpa_helper_post_id );

			if (
				! $original_wpa_helper_post ||
				! $original_wpa_helper_post->post_parent
			) {
				return null;
			}

			$wpa = call_user_func( $this->view_base_get_instance, $original_wpa_helper_post->post_parent );

			if ( ! $wpa ) {
				return null;
			}
		}

		if ( $wpa->is_a_wordpress_archive() ) {
			$post_link = null;
			$assigned_loops = $wpa->get_assigned_loops();
			if ( ! empty( $assigned_loops ) ) {
				$loop = array_shift( $assigned_loops );
				while (
					null === $post_link &&
					null !== $loop
				) {
					$link_from_loop = $this->get_post_link_from_loop( $loop );
					$post_link = is_string( $link_from_loop ) ? $link_from_loop : null;
					$loop = array_shift( $assigned_loops );
				}
			}

			$post_is_original_content = apply_filters( 'wpml_is_original_content', true, $post->ID, 'post_' . self::WPA_HELPER_POST_TYPE );
			if (
				$post_link &&
				! $post_is_original_content
			) {
				$from_language = apply_filters( 'wpml_post_language_details', false, $post->ID );
				if (
					false !== $from_language &&
					isset( $from_language['language_code'] )
				) {
					$post_link = apply_filters( 'wpml_permalink', $post_link, $from_language['language_code'] );
				}
			}
		}

		return $post_link;
	}

	/**
	 * Gets the frontend post link for the given loop.
	 *
	 * @param  array $loop The given loop.
	 *
	 * @return mixed|string|null
	 *
	 * @throws \Exception
	 */
	private function get_post_link_from_loop( $loop ) {
		$post_link = null;

		switch ( $loop['loop_type'] ) {
			case 'native':
				$wp_loops = $this->wpv_editor_loop_selection->get_wp_loops_non_static();
				$post_link = $wp_loops[ $loop['slug'] ]['archive_url'];
				break;
			case 'post_type':
				$pt_loops = $this->wpv_editor_loop_selection->get_pt_loops_non_static();
				$post_link = $pt_loops[ $loop['post_type_name'] ]['archive_url'];
				break;
			case 'taxonomy':
				$tax_loops = $this->wpv_editor_loop_selection->get_taxonomy_loops_non_static();
				$taxonomy_slug = $loop['slug'];

				// Get ID of a term that has some posts, if such term exists.
				$terms_with_posts = get_terms(
					$tax_loops[ $taxonomy_slug ],
					array(
						'hide_empty' => 1,
						'number' => 1,
					)
				);

				if (
					! $terms_with_posts instanceof WP_Error &&
					is_array( $terms_with_posts ) &&
					! empty( $terms_with_posts )
				) {
					$terms_with_posts = array_values( $terms_with_posts );
					$term = array_shift( $terms_with_posts );
					$term_link = get_term_link( $term, $taxonomy_slug );
					$post_link = is_string( $term_link ) ? $term_link : null;
				}
				break;
		}

		return $post_link;
	}

	public function maybe_delete_wpa_helper_view_link( $post_view_link, $link_text, $current_document, $element_type, $type ) {
		if ( self::WPA_HELPER_POST_TYPE === $type ) {
			$matches = null;

			preg_match(
				'/<a href="([^"]*)"[^>]*>/',
				$post_view_link,
				$matches
			);

			// There is no actual link tag.
			if ( empty( $matches ) ) {
				$post_view_link = null;
			}

			// It doesn't have an href attribute, or lacks content in the href attribute.
			if ( count( $matches ) < 1 || '' === $matches[1] ) {
				$post_view_link = null;
			}
		}
		return $post_view_link;
	}

	/**
	 * Handles the syncing of the post status of a WordPress Archive post when the WordPress Archive helper post connected
	 * to it switches between "draft" or "published".
	 *
	 * @param string   $new_status  The new status.
	 * @param string   $old_status  The old status.
	 * @param \WP_Post $post_object The \WP_Post object that transitions.
	 */
	public function sync_post_status_for_wpas_on_helpers_transition( $new_status, $old_status, $post_object ) {
		if (
			$new_status === $old_status ||
			! $this->is_wpa_helper( $post_object ) ||
			! $post_object->post_parent
		) {
			return;
		}

		$wpa_data_to_update = array(
			'ID' => $post_object->post_parent,
			'post_status' => $new_status,
		);

		wp_update_post( $wpa_data_to_update );
	}

	/**
	 * Adjusts the post type for the source context in WordPress Archive Helper posts.
	 *
	 * Basically, for non CPT loops, it adjusts the post type(s) in order for the proper dynamic sources to be populated.
	 *
	 * @param string|array $post_type
	 * @param bool|int     $post_id
	 *
	 * @return string[] Post type slugs.
	 *
	 * @throws \Exception Exceptions can come up from the call of "$this->wpv_editor_loop_selection->get_wp_loops_non_static",
	 *                    which tries to populate a DateTime that at certain circumstance, not relevant for our case, can
	 *                    throw a generic Exception.
	 */
	public function adjust_post_types_for_source_context_in_wpa_helper( $post_type, $post_id ) {
		// A specific request is targeted her, the REST request to fetch dynamic sources for the selected WPA loop in WPAs.
		// This request is in the for of "http://example.com/wp-json/toolset-dynamic-sources/v1/dynamic-sources?post-type=X&preview-post-id=Y
		if (
			! (
				$this->constants->defined( 'REST_REQUEST' ) &&
				$this->constants->constant( 'REST_REQUEST' ) &&
				toolset_getget( 'post-type', null )
			)
		) {
			return $post_type;
		}

		// If $post_type is a string, it needs to be converted to an array.
		if ( ! is_array( $post_type ) ) {
			$post_type = array( $post_type );
		}

		// The native WordPress loops are fetched...
		$wp_loops = $this->wpv_editor_loop_selection->get_wp_loops_non_static();

		// and for each one of them we are checking to see if DS API is set to populate the sources for this loop.
		foreach ( $wp_loops as $wp_loop_key => $wp_loop ) {
			$wp_loop_index = array_search( $wp_loop_key, $post_type, true );
			if ( false !== $wp_loop_index ) {
				// In case the loop is there, it needs to be removed as it makes no sense for DS API and it needs to be
				// replaced with the actual post type(s) this loop represents.
				unset( $post_type[ $wp_loop_index ] );
				$post_type = array_merge( $post_type, $wp_loop['post_type'] );
			}
		}

		return $post_type;
	}
}
