<?php

interface WPDD_Layouts_Editor_interface {
	public function save_layout_data_callback();

}

abstract class WPDD_Layouts_Editor implements WPDD_Layouts_Editor_interface {

	protected $post = null;
	protected $lang = null;
	protected $display_refresh_cache_message = false;
	protected $main = null;
	protected $layouts = array();
	protected $is_private = false;
	protected $layout_type = 'normal';
	protected $settings = null;

	const PREVIEW_WIDTH = 150;
	const PREVIEW_HEIGHT = 150;
	const AMOUNT_OF_POSTS_TO_SHOW = 5;
	const POPUP_MESSAGE_OPTION = 'ddl_popup_blocked_dismiss';
	protected static $MAX_NUM_POSTS = 1000;

	public function save_layout_data_callback() {
		// needs to be overriden, do not declare abstract for php 5.2 compatibility
	}

	abstract protected function get_layouts( $layout = null );

	abstract protected function get_layout( $layout = null );

	public function __construct( &$main ) {
		$this->main = &$main;
		$this->lang = apply_filters( 'wpml_current_language', null );
		$this->settings = $this->get_layouts_settings_instance();
		add_filter( 'ddl_layout_settings_save', array( &$this, 'settings_save_callback' ), 10, 3 );
	}

	public function init_editor() {

	}

	protected function save_layout_data( $post_data = null ) {

		if ( $post_data['layout_model'] && $post_data['layout_id'] ) {
			do_action('ddl-before_save_layout_data');
			$raw                = stripslashes( $post_data['layout_model'] );
			$json               = json_decode( $raw, true );

			// Make sure that layout title doesn't contain any HTML
            $json['name'] = sanitize_text_field( htmlspecialchars_decode( $json['name'] ) );

			$children_to_delete = $json['children_to_delete'];
			$child_delete_mode  = $json['child_delete_mode'];

			$is_private = WPDD_Utils::is_private( $post_data['layout_id'] );

			unset( $json['children_to_delete'] );
			unset( $json['child_delete_mode'] );

			$post = get_post( $post_data['layout_id'] );

			$msg = array();

			if ( ( $post->post_title != $json['name'] || $post->post_name != $json['slug'] ) && $is_private !== true ) {

				if ( $this->slug_exists( $json['slug'], $post_data['layout_id'] ) ) {
					return array( "Data" => array( 'error' => __( sprintf( 'The layout %s cannot be saved, the post name  %s is already taken. Please try with a different name.', $json['name'], $json['slug'] ), 'ddl-layouts' ) ) );

				} else {
					if ( $post->post_name != $json['slug'] ) {
						$slug = get_sample_permalink( $post_data['layout_id'], $json['name'], $json['slug'] );
						$slug = $slug[1];
					} else {
						$slug = $json['slug'];
					}

					$postarr = apply_filters( 'ddl_layout_post_save', array(
						'ID'         => $post_data['layout_id'],
						'post_title' => $json['name'],
						'post_name'  => $slug
					), $json, $raw );

					$updated_id = wp_update_post( $postarr );

					$updated_post = get_post( $updated_id );

					$json['slug'] = $updated_post->post_name;

					if ( $this->normalize_layout_slug_if_changed( $post_data['layout_id'], $json, $post->post_name ) ) {
						$msg['slug'] = urldecode( $updated_post->post_name );
					}

				}

			}
			$meta_value = WPDD_Layouts::get_layout_settings( $post_data['layout_id'], false, false );
			if ( $raw === $meta_value ) {
				// no need to save as it hasn't changed.
				$up = false;
			} else {

				$json = apply_filters( 'ddl_layout_settings_save', $json, $post, $raw );

				$up = WPDD_Layouts::save_layout_settings( $post_data['layout_id'], $json );
			}

			do_action( 'ddl_action_layout_has_been_saved', $up, $post_data['layout_id'], $json );

			if ( $children_to_delete && ! empty( $children_to_delete ) ) {
				$delete_children = $this->purge_layout_children( $children_to_delete, $child_delete_mode );
				if ( $delete_children ) {
					$msg['layout_children_deleted'] = $delete_children;
				}
			}

			WPDD_Layouts::register_strings_for_translation( $post_data['layout_id'], $is_private );

			if( $is_private ){
				do_action( 'ddl_update_translated_posts', $post->ID );
			}
			$msg['message']['layout_changed'] = (int) $up !== 0 ? true : false;

			if ( isset( $post_data['silent'] ) && $post_data['silent'] == true ) {
				$msg['message']['silent'] = true;
			} else {
				$msg['message']['silent'] = false;
			}

			$msg['message']['display_cache_message'] = $this->display_refresh_cache_message;

			WPDD_Layouts::set_toolset_edit_last( $post_data['layout_id'], $up );

			// Update Visual Editor (Text Cell) preferred editor for new cells
			if ( isset( $post_data['preferred_editor'] ) && preg_match( '/^(codemirror|tinymce)$/', $post_data['preferred_editor'] ) ) {
				update_user_option( get_current_user_id(), 'ddl_preferred_editor', $post_data['preferred_editor'] );
			}
			if ( isset( $updated_id ) === false && $is_private === false ) {
				do_action( 'edit_post', $post->ID, $post ); // Fix for WP_Super_Cache
				do_action( 'save_post', $post->ID, $post, true );
			}
			do_action('ddl-after_save_layout_data');
		}

		return $msg;

	}

	protected function slug_exists( $slug, $layout_id ) {
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_name=%s AND ID != %d", WPDDL_LAYOUTS_POST_TYPE, $slug, $layout_id ) );

		if ( ! empty( $id ) ) {
			return true;
		}

		return false;
	}

	protected function purge_layout_children( $children, $action ) {
		if ( ! is_array( $children ) ) {
			return false;
		}

		$ret = array();

		foreach ( $children as $child ) {
			$id             = intval( $child );
			$layout         = WPDD_Layouts::get_layout_settings( $id, true );
			$layout->parent = '';
			WPDD_Layouts::save_layout_settings( $id, $layout );

			if ( $action === 'delete' ) {
				// We also need to delete grandchildren
				$layout         = WPDD_Layouts::get_layout_from_id( $id );
				$grand_children = $layout->get_children();
				$this->purge_layout_children( $grand_children, $action );
				$this->main->post_types_manager->purge_layout_post_type_data( $id );
				$ret[] = wp_trash_post( $id );
			}
		}

		return true;
	}

	protected function normalize_layout_slug_if_changed( $layout_id, $layout_data, $previous_slug ) {

		$current = (object) $layout_data;

		if ( $current->slug === $previous_slug ) {
			return false;
		}

		$this->normalize_posts_where_used_data_on_slug_change( $current->slug, $previous_slug );

		if ( property_exists( $current, 'has_child' ) && $current->has_child === true ) {
			$this->normalize_children_on_slug_change( $current, $current->slug, $previous_slug );
		}

		return true;
	}

	protected function normalize_posts_where_used_data_on_slug_change( $slug, $previous_slug ) {
		global $wpdb;

		$sql = $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_key = %s AND meta_value = %s", sanitize_text_field( $slug ), WPDD_Layouts_PostTypesManager::META_KEY, $previous_slug );

		$wpdb->query( $sql );
	}

	protected function normalize_children_on_slug_change( $layout, $slug, $previous_slug ) {
		$defaults = array(
			'posts_per_page'   => - 1,
			'post_type'        => WPDDL_LAYOUTS_POST_TYPE,
			'suppress_filters' => true,
			'post_status'      => 'publish',
			'posts_per_page'   => - 1
		);

		$query = new WP_Query( $defaults );

		$list = $query->posts;

		$children = DDL_GroupedLayouts::get_children( $layout, $list, $previous_slug );

		if ( ! is_array( $children ) || sizeof( $children ) === 0 ) {
			return;
		}

		if ( is_array( $children ) && sizeof( $children ) > 0 ) {
			foreach ( $children as $child ) {
				$current         = WPDD_Layouts::get_layout_settings( $child, true );
				$current->parent = $slug;
				WPDD_Layouts::save_layout_settings( $child, $current );
			}
		}
	}

	/**
	 * Localize JavaScript
	 *
	 * @return array
	 */
	protected function get_editor_js_strings() {
		return apply_filters( 'ddl-get_editor_js_strings', array(
			'toolbar'                                 => array(
				'title'             => __( 'Layout front-end editing', 'ddl-layouts' ),
				'edit'              => __( 'Edit layout on back-end', 'ddl-layouts' ),
				'done'              => __( 'Done', 'ddl-layouts' ),
				'close'             => __( 'Close', 'ddl-layouts' ),
				'cancel'            => __( 'Cancel', 'ddl-layouts' ),
				'undo'              => __( 'Undo', 'ddl-layouts' ),
				'redo'              => __( 'Redo', 'ddl-layouts' ),
				'show_styling_info' => __( 'Show styling info', 'ddl-layouts' ),
				'hide_styling_info' => __( 'Hide styling info', 'ddl-layouts' )

			),
            'layout_storage' => array(
                'title'                 => __( 'Layout Storage', 'ddl-layouts' ),
                'cancel'                => __( 'Cancel', 'ddl-layouts' ),
                'save'                  => __( 'Save', 'ddl-layouts' ),
                'yes_save'              => __( 'Yes, Save', 'ddl-layouts' ),
                'info_message'          => __( 'You are about to update the layout. If you edited it manually, you\'re probably going to cause damage to the layout and will lose your design. The only reason to modify the layout here is if you pasted the complete content from another layout.', 'ddl-layouts' ),
                'confirmation_checkbox' => __( 'I understand', 'ddl-layouts' ),
                'json_format_error'     => __( 'JSON code you have entered is invalid. Please check your code and try again. Text area is updated with the last valid code that was previously entered.', 'ddl-layouts')
            ),
			'only_one_cell'                           => __( "You can't insert another cell of this type. Only one cell of this type is allowed per layout.", 'ddl-layouts' ),
			'save_required'                           => __( 'This layout has changed', 'ddl-layouts' ),
			'page_leave_warning'                      => __( 'This layout has changed. Are you sure you want to leave this page?', 'ddl-layouts' ),
			'save_before_edit_parent'                 => __( 'Do you want to save the current layout before editing the parent layout?', 'ddl-layouts' ),
			'save_required_edit_child'                => __( 'Switching to the child layout', 'ddl-layouts' ),
			'save_before_edit_child'                  => __( 'Do you want to save the current layout before editing the child layout?', 'ddl-layouts' ),
			'save_layout_yes'                         => __( 'Save layout', 'ddl-layouts' ),
			'save_layout_no'                          => __( 'Discard changes', 'ddl-layouts' ),
			'save_required_new_child'                 => __( 'Creating a new child layout', 'ddl-layouts' ),
			'save_before_creating_new_child'          => __( 'Do you want to save the current layout before creating a new child layout?', 'ddl-layouts' ),
			'no_parent'                               => __( 'No parent set', 'ddl-layouts' ),
			'content_template'                        => __( 'Content Template', 'ddl-layouts' ),
			'save_complete'                           => __( '%NAME% saved', 'ddl-layouts' ),
			'one_column'                              => __( '1 Column', 'ddl-layouts' ),
			'columns'                                 => __( 'Columns', 'ddl-layouts' ),
			'at_least_class_or_id'                    => __( 'You should define either an ID or one class for this cell to style its CSS', 'ddl-layouts' ),
			'ajax_error'                              => __( 'There was an error during the ajax request, make sure the data you send are in json format.', 'ddl-layouts' ),
			'select_range_one_column'                 => __( 'Move the mouse to resize, click again to create.', 'ddl-layouts' ),
			'select_range_one_column_short'           => __( '1 column', 'ddl-layouts' ),
			'select_range_more_columns'               => __( '%d columns - click again to create', 'ddl-layouts' ),
			'select_range_more_columns_short'         => __( '%d columns', 'ddl-layouts' ),
			'dialog_yes'                              => __( 'Yes', 'ddl-layouts' ),
			'dialog_no'                               => __( 'No', 'ddl-layouts' ),
			'dialog_cancel'                           => __( 'Cancel', 'ddl-layouts' ),
			'slug_unwanted_character'                 => __( "The slug should contain only lower case letters", 'ddl-layouts' ),
			'save_and_also_save_css'                  => __( 'The layout has been saved. Layouts CSS has been updated.', 'ddl-layouts' ),
			'save_and_save_css_problem'               => __( 'The layout has been saved. Layouts CSS has NOT been updated. Please retry or check write permissions for uploads directory.', 'ddl-layouts' ),
			'invalid_slug'                            => __( "The entered value for layout slug shouldn't be an empty string.", 'ddl-layouts' ),
			'title_not_empty_string'                  => __( "The title shouldn't be an empty string.", 'ddl-layouts' ),
			'more_than_4_rows'                        => __( 'If you need more than 4 rows you can add them later in the editor', 'ddl-layouts' ),
			'id_duplicate'                            => __( "This id is already used in the current layout, please select a unique id for this element", 'ddl-layouts' ),
			'edit_cell'                               => __( 'Edit cell', 'ddl-layouts' ),
			'remove_cell'                             => __( 'Remove cell', 'ddl-layouts' ),
			'set_cell_type'                           => __( 'Select cell type', 'ddl-layouts' ),
			'show_grid_edit'                          => __( 'Show grid edit', 'ddl-layouts' ),
			'hide_grid_edit'                          => __( 'Hide grid edit', 'ddl-layouts' ),
			'css_file_loading_problem'                => __( 'It is not possible to handle CSS loading in the front end. You should either make your uploads directory writable by the server, or use permalinks.', 'ddl-layouts' ),
			'save_required_open_view'                 => __( 'Switching to the View', 'ddl-layouts' ),
			'save_before_open_view'                   => __( 'The layout has changed. Do you want to save the current layout before switching to the View?', 'ddl-layouts' ),
			'close_view_iframe'                       => __( 'Close this view and return to the layout', 'ddl-layouts' ),
			'save_and_close_view_iframe'              => __( 'Save and Close this view and return to the layout', 'ddl-layouts' ),
			'close_view_iframe_without_save'          => __( 'Close this view and discard the changes', 'ddl-layouts' ),
			'video_message_text'                      => __( 'Please enter a valid YouTube video URL.', 'ddl-layouts' ),
			'title_one_comment_text'                  => __( 'The text for one comment title is missing', 'ddl-layouts' ),
			'title_multi_comments_text'               => __( 'The text for multiple comments title missing', 'ddl-layouts' ),
			'this_field_is_required'                  => __( 'This field cannot be empty', 'ddl-layouts' ),
			'no_changes_nothing_to_save'              => __( 'No changes were made, nothing to save to the server.', 'ddl-layouts' ),
			'no_drop_title'                           => __( 'You cannot drag to here', 'ddl-layouts' ),
			'no_drop_content'                         => __( "You cannot drag the cell into the target row because the cell is %NN% columns wide and the target row has only %MM% free columns. %OO%To move this cell, first resize it and make it at most %MM% columns wide.", 'ddl-layouts' ),
			'no_drop_content_wider'                   => __( "The target row's columns are wider, so the space appears sufficient, but there is not enough room for the cell." . ' ', 'ddl-layouts' ),
			'no_more_pages'                           => __( "This layout is already assigned to all pages.", 'ddl-layouts' ),
			'no_more_posts'                           => __( "This layout is already assigned to all posts items.", 'ddl-layouts' ),
			'no_more_pages_in_db'                     => __( "No pages found.", 'ddl-layouts' ),
			'no_more_posts_in_db'                     => __( "No post items found.", 'ddl-layouts' ),
			'new_ct_message_title'                    => __( "Content Template", 'ddl-layouts' ),
			'new_ct_message'                          => __( "Insert fields to display parts of the content and add HTML around them for styling.", 'ddl-layouts' ),
			'views_plugin_missing'                    => apply_filters( 'toolset_is_views_available', false ) === false ? __( "Sorry, preview is not available. Please make sure that the Toolset Views plugin is active.", 'ddl-layouts' ) : __( "Sorry, preview is not available. This View may have been deleted.", 'ddl-layouts' ),
			'views_resource_missing'                  => __( 'The View was not found. It may have been deleted or Views plugin is not active.', 'ddl-layouts' ),
			'views_archive_resource_missing'          => __( 'The Wordpress Archive was not found. It may have been deleted or Views plugin is not active.', 'ddl-layouts' ),
			'not_allowed_content_layout_cells'        => apply_filters( 'ddl-disabled_cells_on_content_layout', array() ),
			'this_is_a_parent_layout'                 => __( 'This layout has children. You should assign one of its children to content and not this parent layout.', 'ddl-layouts' ),
			'switch_editor_warning_message'           => __( 'You are about to switch editing modes. Please note that this may change the content of the cell. Are you sure?', 'ddl-layouts' ),
			'content_template_should_have_name'       => __( 'A Content Template should have a name please provide one.', 'ddl-layouts' ),
			'refresh_cache_message'                   => __( 'This layout is used to display many posts. If you are using a caching plugin, you should clear page cache.', 'ddl-layouts' ),
			'dont_show_again'                         => __( 'Don\'t show this message again', 'ddl-layouts' ),
			'user_no_caps'                            => __( 'You don\'t have permission to perform this action.', 'ddl-layouts' ),
			'user_no_caps_cell' => __('You cannot edit this cell with your role.', 'ddl-layouts' ),
			'image_box_choose'                        => __( 'Choose an image', 'ddl-layouts' ),
			'image_box_change'                        => __( 'Change image', 'ddl-layouts' ),
			'help_pointer_title'                      => __( 'Layouts Help', 'ddl-layouts' ),
			'cred_layout_css_text'                    => __( 'Layouts cell styling', 'ddl-layouts' ),
			'popup_blocked'                           => __( 'Warning popups blocked', 'ddl-layouts' ),
			'dialog_close'                            => __( 'OK', 'ddl-layouts' ),
			'all_changes_saved'                       => __( 'All changes saved', 'ddl-layouts' ),
			'saving'                                  => __( 'Saving...', 'ddl-layouts' ),
			'problem_saving'                          => __( 'Problem saving data', 'ddl-layouts' ),
			'no_changes'                              => __( 'No changes made', 'ddl-layouts' ),
			'no_changes_for'                          => __( 'No changes made for layout %NAME%', 'ddl-layouts' ),
			'layout_assigned'                         => __( ' is an assigned layout!', 'ddl-layouts' ),
			'layout_assigned_text'                    => __( ' is assigned to render WordPress resources in front-end and cannot be deleted. To delete it remove assignments first and try again.', 'ddl-layouts' ),
			'close'                                   => __( 'Close', 'ddl-layouts' ),
			'cancel'                                  => __( 'Cancel', 'ddl-layouts' ),
			'forbidden_paste'                         => __( 'Sorry, but you cannot paste this row here', 'ddl-layouts' ),
			'contains_child'                          => __( 'Now, create a child layout', 'ddl-layouts' ),
			'skip'                                    => __( 'Skip (Choose later)', 'ddl-layouts' ),
			'create_child'                            => __( 'Edit the cell and create a child layout ', 'ddl-layouts' ),
			'dismiss_button'                          => __( 'Cancel', 'ddl-layouts' ),
			'unassign_layout_and_create_child_button' => __( 'Proceed and remove assignments', 'ddl-layouts' ),
			'remove_assignments'                      => __( 'Parent layout assignments', 'ddl-layouts' ),
			'remove_assignments_button'               => __( 'Remove all assignments', 'ddl-layouts' ),
			'you_cant_use_archive_cell'               => sprintf( __( 'You cannot add an archive cell into a page. To display lists, use the %sView cell%s. Learn more about %sWordPress archives cells%s.', 'ddl-layouts' ), '<a href="https://toolset.com/documentation/legacy-features/toolset-layouts/view-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">', '</a>', '<a href="https://toolset.com/documentation/legacy-features/toolset-layouts/wordpress-archive-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">', '</a>' ),
			'you_cant_use_comments_cell'              => __( 'You cannot add a comments cell into the content of a page. To display comments, use the cell in a template layout.', 'ddl-layouts' ),
			'you_cant_use_this_cell'                  => __( 'You cannot use this cell in Content Layouts', 'ddl-layouts' ),
			'apply'                                   => __( 'Apply', 'ddl-layouts' ),
			'layout_hierarchy_settings_tooltip'       => __( 'Layout hierarchy settings', 'ddl-layouts' ),
			'cred_relationship_form_name_postfix' => __( 'Relationship Form', 'ddl-layouts' )
		), $this );
	}

	public function clear_page_caches( $layout_id ) {
		$post_ids = $this->main->get_where_used( $layout_id, false, true, self::$MAX_NUM_POSTS, array(
			'publish',
			'draft',
			'private'
		), 'ids', 'any', true );

		if ( $this->main->get_where_used_count() === 0 ) {
			return;
		}

		if ( $this->main->get_where_used_count() > self::$MAX_NUM_POSTS ) {
			$this->display_refresh_cache_message = true;
			return;
		}

		$this->display_refresh_cache_message = false;

		$temp_post = $_POST;
		$_POST     = array();

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			// Call save_post action so caching plugins clear the cache for this page.
			do_action( 'edit_post', $post->ID, $post ); // fix for WP_Super_Cache
			do_action( 'save_post', $post_id, $post, true );
		}

		$_POST = $temp_post;
	}

	function settings_save_callback( $json, $post, $raw ) {

		if ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) {
			return $json;
		}

		$this->clear_page_caches( $post->ID );

		return $json;
	}

	function update_wpml_state() {

		if ( user_can_edit_layouts() === false ) {
			die( __( "You don't have permission to perform this action!", 'ddl-layouts' ) );
		}

		if ( ! isset( $_POST['wpnonce'] ) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_layout_view_nonce' ) ) {
			die();
		}

		$is_private = WPDD_Utils::is_private( $_POST['layout_id'] );

		$layout = WPDD_Layouts::get_layout_from_id( $_POST['layout_id'], $is_private );

		if ( $_POST['register_strings'] == 'true' && is_object( $layout ) ) {
			$layout->register_strings_for_translation( null );
		}

		do_action( 'WPML_show_package_language_ui', $layout->get_string_context() );

		die();
	}

	protected function get_cells_data() {
		$cells = array();
		foreach ( $this->main->get_cell_types() as $cell_type ) {
			$cell_info           = $this->main->get_cell_info( $cell_type );
			$cells[ $cell_type ] = (object) $cell_info;
		}

		return $cells;
	}

	protected function get_framework_prefixes_data(){

		$framework = $this->get_framework_instance();

		return $framework->get_framework_prefixes_data();
	}

	private function get_framework_instance(){
		return WPDDL_Framework::getInstance();
	}

	protected function get_layouts_settings_instance(){
		return WPDDL_Settings::getInstance();
	}
}

