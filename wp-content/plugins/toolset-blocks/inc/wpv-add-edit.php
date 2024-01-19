<?php
/**
* Added extra files to have the old and new editors working together
* TODO Once we are done, those extra files will be merged with the old ones after cleaning no longer needed functions
*/

/** General TODOs
* TODO Create extra files to make this screen modular. STATUS: 90%
*/

// Filter section files
require_once WPV_PATH . '/inc/sections/wpv-section-query-options.php';
require_once WPV_PATH . '/inc/sections/wpv-section-ordering.php';

/*
 * require files only for full version
 */
if( ! wpv_is_views_lite() ){
	require_once WPV_PATH . '/inc/sections/wpv-section-pagination.php';
	require_once WPV_PATH . '/inc/sections/wpv-section-parametric-search.php';
}
require_once WPV_PATH . '/inc/sections/wpv-section-filter-extra.php';
require_once WPV_PATH . '/inc/sections/wpv-section-filters.php';


// Layout section files
require_once WPV_PATH . '/inc/sections/wpv-section-layout-template.php';

/**
 * View edit screen
 */
function views_redesign_html() {
	$section_top_bar = new \OTGS\Toolset\Views\Controller\Admin\Section\TopBar();
	$section_top_bar->initialize();
	$section_top = new \OTGS\Toolset\Views\Controller\Admin\Section\Top();
	$section_top->initialize();
	new WPV_Section_Content_Selection();
	global $post;

	if (
		isset( $_GET['view_id'] )
		&& is_numeric( $_GET['view_id'] )
	) {
		do_action('views_edit_screen');
		$view_id = (int) $_GET['view_id'];
		$view = get_post( $view_id, OBJECT, 'edit' );

		if ( null == $view ) {
			wpv_die_toolset_alert_error( __( 'You attempted to edit a View that doesn&#8217;t exist. Perhaps it was deleted?', 'wpv-views' ) );
		}

		if ( 'view' !== $view->post_type ) {
			wpv_die_toolset_alert_error( __( 'You attempted to edit a View that doesn&#8217;t exist. Perhaps it was deleted?', 'wpv-views' ) );
		}

		$view_object = \WPV_View::get_instance( $view_id );
		$is_gutenberg_view = $view_object->get_is_gutenberg_view();
		if ( $is_gutenberg_view ) {
			$message = '<p>'
				. __( 'This View can only be edited in the Block Editor from within the post that includes it.', 'wpv_views' )
				. '</p>';

			$parent_post_id = $view_object->get_parent_post_id();
			$parent_post = ( $parent_post_id && 'trash' !== get_post_status( $parent_post_id ) )
				? get_post( $parent_post_id )
				: null;
			if ( null !== $parent_post ) {
				$message .= '<p>'
					. '<a class="button button-primary" href="' . esc_url( get_edit_post_link( $parent_post_id ) ) . '">'
					. sprintf(
						/* translators: Edit action link text for the cases where the View is build with the new View editor. */
						__( 'Edit in %s', 'wpv-views' ),
						'<em>' . $parent_post->post_title . '</em>'
					)
					. '</a>'
					. '</p>';
			}
			$die_alert = new \OTGS\Toolset\Views\Model\Wordpress\WpDie();
			$die_alert->set_message( $message );
			$die_alert->wp_die();
		}

		$view_settings_stored = get_post_meta( $view_id, '_wpv_settings', true );

		$wpv_filter_wpv_get_view_settings_args = array(
			'override_view_settings' => false,
			'extend_view_settings' => false,
			'public_view_settings' => false,
		);

		$view_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_id, $wpv_filter_wpv_get_view_settings_args );

		$view_layout_settings_stored = get_post_meta( $view_id, '_wpv_layout_settings', true );

		/**
		* Internal filter to set some View layout settings that will overwrite the ones existing in the _wpv_layout_settings postmeta
		* Only used to set default values that need to be there on the returned array,, but may not be there for legacy reasons
		* Use wpv_filter_override_view_layout_settings to override View layout settings
		*
		* @param $view_layout_settings_stored (array) Unserialized array of the _wpv_layout_settings postmeta
		* @param $view_id (integer) The View ID
		*
		* @return $view_layout_settings (array) The View layout settings
		*
		* @since 1.8.0
		*/

		$view_layout_settings = apply_filters( 'wpv_view_layout_settings', $view_layout_settings_stored, $view_id );

		if (
			isset( $view_settings['view-query-mode'] )
			&& ( 'normal' === $view_settings['view-query-mode'] )
		) {
			$post = $view;
			if ( 'trash' === get_post_status( $view_id ) ) {
				wpv_die_toolset_alert_error( __( 'You can’t edit this View because it is in the Trash. Please restore it and try again.', 'wpv-views' ) );
			}
		} else {
			wpv_die_toolset_alert_error( __('You attempted to edit a View that doesn&#8217;t exist. Perhaps it was deleted?', 'wpv-views' ) );
		}
	} else {
		wpv_die_toolset_alert_error( __( 'You attempted to edit a View that doesn&#8217;t exist. Perhaps it was deleted?', 'wpv-views' ) );
	}
	$user_id = get_current_user_id();

	

	?>
	<?php
	/**
	* Screen Options tab
	*/
	do_action( 'wpv_action_view_editor_screen_options', $view_settings, $view_layout_settings, $view_id, $user_id, $view );
	?>
	<?php
	/**
	* Actual View edit page
	*/
	?>
	<div class="wrap toolset-views toolset-views-editor js-toolset-views-editor">
		<hr class="wp-header-end"><!-- This item keeps admin notices in place -->
		<input id="post_ID" class="js-post_ID" type="hidden" value="<?php echo esc_attr( $view_id ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_edit_general_nonce' ); ?>" />
		<input id="toolset-edit-data" type="hidden" value="<?php echo esc_attr( $view_id ); ?>" data-plugin="views" />

		<?php
		/**
		 * Hook for rendering the top bar in Views editors
		 *
		 * @since 2.7
		 */
		do_action( 'wpv_action_view_editor_top_bar', $view_settings, $view_id, $user_id, $view );
		?>

		<input type="hidden" name="_wpv_settings[view-query-mode]" value="normal" />

		<div class="wpv-title-section">

			<?php
			/**
			 * Hook for sections in the Title metasection.
			 *
			 * @since 2.1
			 * @deprecated 2.7 In The title was moved to the top bar. Use wpv_action_view_editor_section_top instead.
			 */
			do_action( 'wpv_action_view_editor_section_title', $view_settings, $view_id, $user_id, $view );

			/**
			 * Hook for sections in the Title metasection, rendered on the top of the editor
			 *
			 * @since 2.7
			 */
			do_action( 'wpv_action_view_editor_section_top', $view_settings, $view_id, $user_id, $view );
			?>

		</div><!-- .wpv-title-section -->

		<div class="wpv-query-section">

			<?php
			wpv_get_view_introduction_data();
			?>

			<span class="wpv-section-title"><?php _e('The Query section determines what content the View loads from the database','wpv-views') ?></span>

			<div class="js-wpv-metasection-message-container js-wpv-metasection-message-container-query"></div>

			<?php

			/**
			* wpv_action_view_editor_section_query
			*
			* Hook for sections in the Query metasection.
			*
			* @since 2.1
			*/
			do_action( 'wpv_action_view_editor_section_query', $view_settings, $view_id, $user_id );
			/**
			* The action 'view-editor-section-query' is now deprecated, leave it for backwards compatibility
			*
			* @deprecated 2.1
			*/
			do_action( 'view-editor-section-query', $view_settings, $view_id, $user_id );
			?>
		</div><!-- .wpv-query-section -->

		<div class="wpv-filter-section">

			<?php if( ! wpv_is_views_lite() ):?>
			<span class="wpv-section-title"><?php _e('The Filter section lets you set up pagination and custom search, which let visitors control the View query','wpv-views') ?></span>
			<?php endif;?>
			<div class="js-wpv-metasection-message-container js-wpv-metasection-message-container-filter"></div>

			<?php
			wpv_get_view_filter_introduction_data();
			?>
			<?php

			/**
			* wpv_action_view_editor_section_filter
			*
			* Hook for sections in the Filter metasection.
			*
			* @since 2.1
			*/
			do_action( 'wpv_action_view_editor_section_filter', $view_settings, $view_id, $user_id );
			/**
			* The action 'view-editor-section-filter' is now deprecated, leave it for backwards compatibility
			*
			* @deprecated 2.1
			*/
			do_action( 'view-editor-section-filter', $view_settings, $view_id, $user_id );
			?>
		</div>

		<?php
		/*
		* Pagination TODO review this. https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/161787682/comments - Priority 50
		* Filters Meta HTML/CSS/JS TODO review this. https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/161787682/comments - Priority 80
		*/
		?>

		<div class="wpv-layout-section">
			<span class="wpv-section-title"><?php _e('The Loop section styles the View output on the page.','wpv-views') ?></span>

			<div class="js-wpv-metasection-message-container js-wpv-metasection-message-container-layout"></div>

			<?php
			$data = wpv_get_view_layout_introduction_data();
			wpv_toolset_help_box($data);
			?>
			<?php

			/**
			* wpv_action_view_editor_section_layout
			*
			* Hook for sections in the first half of the Loop metasection.
			*
			* @since 2.1
			*/
			do_action( 'wpv_action_view_editor_section_layout', $view_settings, $view_layout_settings, $view_id, $user_id );

			/**
			 * wpv_action_view_editor_section_view_wrapper
			 *
			 * Hook for sections in the View Wrapper metasection.
			 *
			 * @since 2.6.4
			 */
			do_action( 'wpv_action_view_editor_section_view_wrapper', $view_settings, $view_id, $user_id, $view );

			/**
			* The action 'view-editor-section-layout' is now deprecated, leave it for backwards compatibility
			*
			* @deprecated 2.1
			*/
			do_action( 'view-editor-section-layout', $view_settings, $view_layout_settings, $view_id, $user_id );

			/**
			* wpv_action_view_editor_section_extra
			*
			* Hook for sections in the second half of the Loop metasection.
			*
			* @since 2.1
			*/
			do_action( 'wpv_action_view_editor_section_extra', $view_settings, $view_id, $user_id );
			/**
			* The action 'view-editor-section-extra' is now deprecated, leave it for backwards compatibility
			*
			* @deprecated 2.1
			*/
			do_action( 'view-editor-section-extra', $view_settings, $view_id, $user_id );
			?>
		</div>

		<?php
		$display_help = ( isset( $_GET['in-iframe-for-layout'] ) && $_GET['in-iframe-for-layout'] == 1 ) ? false : true;

		if ( $display_help === true ) { ?>
			<div class="wpv-help-section">
			<?php
				wpv_display_view_howto_help_box();
			?>
			</div>
		<?php } ?>
	</div><!-- .toolset-views -->
	<?php

		/**
		* wpv_action_view_editor_section_hidden
		*
		* Show hidden container for dialogs, pointers and messages that need to be taken from an existing HTML element
		*
		* @param $args['settings']					$view_settings
		* @param $args['settings_stored']			$view_settings_stored
		* @param $args['layout_settings']			$view_layout_settings
		* @param $args['layout_settings_stored']	$view_layout_settings_stored
		* @param $args['id']						$view_id
		* @param $args['user_id']					$user_id
		*
		* @note			You can use the .popup-window-container classname to hide the containers added here
		*
		* @since 2.1
		*/

		do_action( 'wpv_action_view_editor_section_hidden', array(
				'settings'					=> $view_settings,
				'settings_stored'			=> $view_settings_stored,
				'layout_settings'			=> $view_layout_settings,
				'layout_settings_stored'	=> $view_layout_settings_stored,
				'id'						=> $view_id,
				'user_id'					=> $user_id
			)
		);

		/**
		* view-editor-section-hidden
		*
		* Show hidden container for dialogs, pointers and messages that need to be taken from an existing HTML element
		*
		* @param $view_settings
		* @param $view_laqyout_settings
		* @param $view_id
		* @param $user_id
		*
		* @note that you can use the .popup-window-container classname to hide the containers added here
		*
		* @since 1.7
		*
		* @deprecated 2.1	Use wpv_action_view_editor_section_hidden instead
		*/

		do_action( 'view-editor-section-hidden', $view_settings, $view_layout_settings, $view_id, $user_id );

		if ( ! class_exists( '_WP_Editors' ) ) {
			require( ABSPATH . WPINC . '/class-wp-editor.php' );
		}
		_WP_Editors::wp_link_dialog();

		/**
		* wpv_action_view_editor_after_sections
		*
		* Final action to include additional data.
		* Used to generate the Types post relationship tree reference, as doing it too early fails because post types are managed as "inactive".
		*
		* @todo Move here the generation of filter validation rules.
		*
		* @param $view_settings
		* @param $view_laqyout_settings
		* @param $view_id
		* @param $user_id
		*
		* @since 2.1
		*/

		do_action( 'wpv_action_view_editor_after_sections', $view_settings, $view_layout_settings, $view_id, $user_id );
	?>
<?php }


add_filter( 'icl_post_link', 'wpv_provide_edit_view_link', 10, 4 );

/**
 * Provide link for editing Views via icl_post_link.
 *
 * @param array|null|mixed $link
 * @param string $post_type
 * @param int $post_id
 * @param string $link_purpose
 * @return array|null|mixed Link data or $link.
 * @since 1.12
 */
function wpv_provide_edit_view_link( $link, $post_type, $post_id, $link_purpose ) {
	if (
		WPV_View_Base::POST_TYPE == $post_type
		&& 'edit' == $link_purpose
	) {
		$view = WPV_View_Base::get_instance( $post_id );
		if (
			null != $view
			&& $view->is_a_view()
		) {
			$link = array(
				'is_disabled' => false,
				'url' => esc_url_raw(
					add_query_arg(
						array( 'page' => 'views-editor', 'view_id' => $post_id ),
						admin_url( 'admin.php' )
					)
				)
			);
		}
	}
	return $link;
}
