<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Class WPV_Shortcode_Post_Body
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Body extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-body';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'view_template'    => 'None',
		'output'       => 'normal',
		'suppress_filters' => 'false'
	);

	/**
	 * @var array
	 */
	private $infinite_loop_keys = array();

	/**
	 * @var string|null
	 */
	private $user_content;

	/**
	 * @var array
	 */
	private $user_atts;


	/**
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	private $item;

	/**
	 * WPV_Shortcode_Post_Body constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct(
		Toolset_Shortcode_Attr_Interface $item
	) {
		$this->item  = $item;
	}

	/**
	 * Get the shortcode output value.
	 *
	 * @param $atts
	 * @param $content
	 * @return string
	 * @since 2.5.0
	 * @since 2.8.3 Do not render the content or apply a CT to an excluded post type.
	 * @since 3.2.0 Complete refactor as this is not a normal post shortcode:
	 *     - Render anyway when referencing a valid CT over a valid object.
	 *         - Consider loop templates for ters and user Views.
	 *     - Fail gracefully when requesting a missing CT.
	 *     - Fail gracefully when requesting a missing post with item attributes.
	 *     - Fail gracefully when requesting to render a post and there is no post set.
	 */
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( ! $item_id = $this->item->get( $this->user_atts ) ) {
			// no valid item
			return $this->get_value_without_context( $atts );
		}

		// We do have a context, which can be a global post or a numeric item ID.
		$item = $this->get_post( $item_id );

		global $post;
		$post_switched = false;
		$post_cloned = null;
		if ( $this->should_switch_global_post_to_item_attribute( $item ) ) {
			$post_switched = true;
			$post_cloned = $post;
			$post = $item;
		}

		// Avoid the case for a shortcode over a post when no post is set, belongs to an excluded post type, or is password protected.
		$current_item_type = apply_filters( 'wpv_filter_wpv_get_query_type', 'posts' );
		if ( 'posts' === $current_item_type ) {
			if ( false === ( $post instanceof \WP_Post ) ) {
				if ( $post_switched ) {
					$post = $post_cloned;
				}
				return;
			} elseif ( $this->is_excluded_post_type( $post ) ) {
				if ( $post_switched ) {
					$post = $post_cloned;
				}
				return;
			} else if ( $this->should_require_post_password( $post ) ) {
				$post_protected_password_form = get_the_password_form( $post );

				/**
				 * Filter the form for posts protected by password that were supposed to be rendered by this shortcode.
				 *
				 * @param string $post_protected_password_form The default WordPress password form
				 * @param object $post The post object to which this shortcode is related to
				 * @param array $atts The array of attributes passed to this shortcode
				 * @return string
				 * @since 1.7.0
				 */
				$out = apply_filters( 'wpv_filter_post_protected_body', $post_protected_password_form, $post, $atts );
				if ( $post_switched ) {
					$post = $post_cloned;
				}
				return $out;
			}
		}

		if ( ! isset( $atts['view_template'] ) ) {
			$out = $this->get_undocumented_legacy_outcome();

			if ( $post_switched ) {
				$post = $post_cloned;
			}

			return $out;
		}

		if ( 'none' === strtolower( $this->user_atts['view_template'] ) ) {
			$out = $this->get_post_content_value();

			if ( $post_switched ) {
				$post = $post_cloned;
			}

			return $out;
		}

		$out = $this->get_content_template_value();

		if ( $post_switched ) {
			$post = $post_cloned;
		}

		return $out;
	}

	/**
	 * Try to generate some output when the item API can not return a valid match.
	 *
	 * This means that:
	 * - the item attributes could not be resolved.
	 * - OR there are no item attributes but the current global post is not set.
	 * In this case, we can only generate a valid outcome if the View is inside a terms or users loop
	 * AND the shortcode references a CT, and not the current post content.
	 *
	 * @param array $atts The shortcode attributes
	 * @return void|string
	 * @since 3.2
	 */
	private function get_value_without_context( $atts ) {
		// Here the current post is not set and the attributes might point to a missing post.
		if ( $this->has_specific_item_attribute() ) {
			// Context failed because it references a non existing item.
			return;
		}

		$current_item_type = apply_filters( 'wpv_filter_wpv_get_query_type', 'posts' );
		if ( 'posts' === $current_item_type ) {
			// Trying to display content for a post that does not exist.
			return;
		}

		if (
			! isset( $atts['view_template'] )
			|| 'none' === strtolower( $atts['view_template'] )
		) {
			// There is no post, hence there is no undocumented glitch to render.
			return;
		}

		return $this->get_content_template_value();
	}

	/**
	 * Get the content generated by the CT referenced in the shortcode attributes.
	 *
	 * @return void|string
	 * @since 3.2
	 */
	private function get_content_template_value() {
		$infinite_loop_index = $this->get_infinite_loop_index();

		if ( isset( $this->infinite_loop_keys[ $infinite_loop_index ] ) ) {
			return $this->get_infinite_loop_error();
		}

		$template_id = $this->get_template_id_by_name( $this->user_atts['view_template'] );

		if ( 0 === $template_id ) {
			return;
		}

		do_action( 'wpv_register_printed_content_template', $template_id );

		global $WPVDebug;

		$this->infinite_loop_keys[ $infinite_loop_index ] = 1;

		do_action( 'wpv_before_shortcode_post_body' );

		$output_mode = get_post_meta( $template_id, '_wpv_view_template_mode', true );
		$hook = ( 'true' === $this->user_atts['suppress_filters'] ) ? 'wpv_filter_wpv_the_content_suppressed' : 'the_content';

		$WPVDebug->wpv_debug_start( $template_id, $this->user_atts, 'content-template' );
		$WPVDebug->set_index();

		$unprocessed_value_to_render = $this->get_unprocessed_value_to_render();
		$hooks_to_restore = array();
		$hooks_to_restore_add = true;

		$this->disable_wpml_alt_lang();

		$nested_templates_memory = $this->manage_nested_templates();

		if (
			$this->has_blocks( $unprocessed_value_to_render )
			|| 'raw_mode' === $output_mode
		) {
			$hooks_to_restore = $this->maybe_disable_formatting_hooks( $hook );
			$unprocessed_value_to_render = $this->force_filter_template_content( $unprocessed_value_to_render, $template_id );
		} else {
			$hooks_to_restore = $this->maybe_enforce_formatting_hooks( $hook );
			$hooks_to_restore_add = false;
		}

		$out = apply_filters( $hook, $unprocessed_value_to_render );

		/**
		 * Filter the outcome of this shortcode.
		 *
		 * @param string $out The outcome of the shortcode.
		 * @param int $template_id The ID of the applied Content Template.
		 * @return string
		 * @since 3.2
		 */
		$out = apply_filters( 'wpv_filter_wpv-post-body_output', $out, $template_id );

		$this->maybe_restore_formatting_hooks( $hook, $hooks_to_restore, $hooks_to_restore_add );

		$this->restore_nested_templates( $nested_templates_memory );

		$this->restore_wpml_alt_lang();

		$WPVDebug->add_log_item( 'output', $out );
		$WPVDebug->wpv_debug_end();

		do_action( 'wpv_after_shortcode_post_body' );

		unset( $this->infinite_loop_keys[ $infinite_loop_index ] );

		return $out;
	}

	/**
	 * Get the content generated by the current global post content.
	 *
	 * In the unexpected event of this being called when n global post is set,
	 * self::get_unprocessed_value_to_render will take care of producing an empty string.
	 *
	 * @return string
	 * @since 3.2
	 */
	private function get_post_content_value() {
		$infinite_loop_index = $this->get_infinite_loop_index();

		if ( isset( $this->infinite_loop_keys[ $infinite_loop_index ] ) ) {
			return $this->get_infinite_loop_error();
		}

		global $WPVDebug;

		$this->infinite_loop_keys[ $infinite_loop_index ] = 1;

		do_action( 'wpv_before_shortcode_post_body' );

		// normal (default) - use wpautop
		// raw - remove wpautop
		// inherit - when used inside a Content Template, inherit its wpautop setting; when used outside a Template, inherit from the post itself (so add format, just like "normal")
		$output_mode = $this->user_atts['output'];
		$hook = ( 'true' === $this->user_atts['suppress_filters'] ) ? 'wpv_filter_wpv_the_content_suppressed' : 'the_content';

		$WPVDebug->wpv_debug_start( 'none', $this->user_atts, 'content-template' );
		$WPVDebug->set_index();

		$unprocessed_value_to_render = $this->get_unprocessed_value_to_render();
		$hooks_to_restore = array();
		$hooks_to_restore_add = true;

		$this->disable_wpml_alt_lang();

		$nested_templates_memory = $this->manage_nested_templates();

		if (
			$this->has_blocks( $unprocessed_value_to_render )
			|| 'raw' === $output_mode
		) {
			$hooks_to_restore = $this->maybe_disable_formatting_hooks( $hook );
		} elseif ( 'normal' === $output_mode ) {
			$hooks_to_restore = $this->maybe_enforce_formatting_hooks( $hook );
			$hooks_to_restore_add = false;
		}

		$out = apply_filters( $hook, $unprocessed_value_to_render );

		/**
		 * Filter the outcome of this shortcode.
		 *
		 * @param string $out The outcome of the shortcode.
		 * @param int $template_id The ID of the applied Content Template, which right here is zero.
		 * @return string
		 * @since 3.2
		 */
		$out = apply_filters( 'wpv_filter_wpv-post-body_output', $out, 0 );

		$this->maybe_restore_formatting_hooks( $hook, $hooks_to_restore, $hooks_to_restore_add );

		$this->restore_nested_templates( $nested_templates_memory );

		$this->restore_wpml_alt_lang();

		$WPVDebug->add_log_item( 'output', $out );
		$WPVDebug->wpv_debug_end();

		do_action( 'wpv_after_shortcode_post_body' );

		unset( $this->infinite_loop_keys[ $infinite_loop_index ] );

		return $out;
	}

	/**
	 * Make official the unofficial undocumented legacy behavior when the view_template attribute is missing.
	 *
	 * This case, which is not officially suported, has been used by clients
	 * to have different outcomes in different scenarios:
	 * - the CT assigned to the single post in single pages.
	 * - the CT assigned to archive loops on archive pages.
	 * Although we do not support this case, we can not just remove it.
	 * This code offloads the outcome decision to WPV_template::the_content.
	 *
	 * @return string
	 * @since 3.2
	 */
	private function get_undocumented_legacy_outcome() {
		$infinite_loop_index = $this->get_infinite_loop_index();
		$infinite_loop_index .= '##no#view_template#attribute##';

		if ( isset( $this->infinite_loop_keys[ $infinite_loop_index ] ) ) {
			return $this->get_infinite_loop_error();
		}

		global $WPVDebug, $WPV_templates;

		$this->infinite_loop_keys[ $infinite_loop_index ] = 1;

		do_action( 'wpv_before_shortcode_post_body' );

		$WPVDebug->wpv_debug_start( 'none', $this->user_atts, 'content-template' );
		$WPVDebug->set_index();

		$unprocessed_value_to_render = $this->get_unprocessed_value_to_render();

		$this->disable_wpml_alt_lang();

		// Keep the wpautop management from $WPV_templates.
		// Note that $outpt_mode is always 'normal' here.
		// NOTE BUG: we need to first remove_wpautop because for some reason not doing so switches the global $post to the top_current_page one
		$wpautop_was_removed = $WPV_templates->is_wpautop_removed();

		$WPV_templates->remove_wpautop();
		$WPV_templates->restore_wpautop();

		if ( 'true' === $this->user_atts['suppress_filters'] ) {

			/**
			 * Mimics the the_content filter on wpv-post-body shortcodes with attribute suppress_filters="true"
			 * Check WPV_template::init()
			 *
			 * @param string $unprocessed_value_to_render The current post content.
			 * @return string
			 * @since 1.8.0
			 */
			$out .= apply_filters( 'wpv_filter_wpv_the_content_suppressed', $unprocessed_value_to_render );
		} else {
			// Avoid attachment previews.
			$prepend_attachment_priority = has_filter( 'the_content', 'prepend_attachment' );
			if ( false !== $prepend_attachment_priority ) {
				remove_filter( 'the_content', 'prepend_attachment' );
			}

			$filter_state = new WPV_WP_filter_state( 'the_content' );
			$out .= apply_filters( 'the_content', $unprocessed_value_to_render );
			$filter_state->restore();

			if ( false !== $prepend_attachment_priority ) {
				add_filter( 'the_content', 'prepend_attachment', $prepend_attachment_priority );
			}
		}

		$out = '';

		$this->restore_wpml_alt_lang();

		// Restore the wpautop configuration only if is has been changed
		if ( $wpautop_was_removed ) {
			$WPV_templates->remove_wpautop();
		} else {
			$WPV_templates->restore_wpautop();
		}

		$WPVDebug->add_log_item( 'output', $out );
		$WPVDebug->wpv_debug_end();

		do_action( 'wpv_after_shortcode_post_body' );

		unset( $this->infinite_loop_keys[ $infinite_loop_index ] );

		return $out;
	}

	/**
	 * Calculate the infinite loop index for the current scenario.
	 *
	 * @return string
	 * @since 3.2
	 */
	private function get_infinite_loop_index() {
		global $WPVDebug, $WP_Views, $post;

		$current_item_type = apply_filters( 'wpv_filter_wpv_get_query_type', 'posts' );
		$current_stop_infinite_loop_key = $current_item_type . '-';

		switch ( $current_item_type ) {
			case 'posts':
				$current_stop_infinite_loop_key .= (string) $post->ID . '-';
				if ( $WPVDebug->user_can_debug() ) {
					$WPVDebug->add_log( 'content-template', $post );
				}
				break;
			case 'taxonomy':
				$current_stop_infinite_loop_key .= (string) $WP_Views->taxonomy_data['term']->term_id . '-';
				if ( $WPVDebug->user_can_debug() ) {
					$WPVDebug->add_log( 'content-template', $WP_Views->taxonomy_data['term'] );
				}
				break;
			case 'users':
				$current_stop_infinite_loop_key .= (string) $WP_Views->users_data['term']->ID . '-';
				if ( $WPVDebug->user_can_debug() ) {
					$WPVDebug->add_log( 'content-template', $WP_Views->users_data['term'] );
				}
				break;
		}

		$current_stop_infinite_loop_key .= $this->user_atts['view_template'];

		return $current_stop_infinite_loop_key;
	}

	/**
	 * Get the error message for admins when falling into an infinite loop.
	 *
	 * @return string
	 * @since 3.2
	 */
	private function get_infinite_loop_error() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			return;
		}

		$infinite_loop_debug = '<p style="font-weight:bold !important;color: red !important;">'
			. __( 'Content not displayed because it produces an infinite loop.', 'wpv-views' )
			. '<br />'
			. __( 'The wpv-post-body shortcode was called more than once with the same attributes over the same context, triggering an infinite loop.', 'wpv-views' )
			. '</p>';

		return $infinite_loop_debug;
	}

	/**
	 * Check whether a given variable is a post instance of a post type to exclude.
	 *
	 * @param \WP_post $item
	 * @return bool
	 */
	private function is_excluded_post_type( \WP_Post $item ) {
		$toolset_exclude_list = new \Toolset_Post_Type_Exclude_List();
		if ( $toolset_exclude_list->is_excluded( $item->post_type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether the shortcode includes an item (or synonim) attribute with a value.
	 *
	 * @return bool
	 * @since 3.2
	 */
	private function has_specific_item_attribute() {
		if (
			! empty ( $this->user_atts['item'] )
			|| ! empty ( $this->user_atts['id'] )
			|| ! empty ( $this->user_atts['post_id'] )
		) {
			// The shortcode was told to render a specific post.
			return true;
		}

		return false;
	}

	/**
	 * Disable the WPML mechanism to display links to ranslated content.
	 *
	 * @since 3.2
	 */
	private function disable_wpml_alt_lang() {
		// Remove the icl language switcher to stop WPML from add the
		// "This post is avaiable in XXXX" twice.
		// Before WPML 3.6.0
		add_filter( 'icl_post_alternative_languages', '__return_empty_string', 999 );
		// After WPML 3.6.0
		add_filter( 'wpml_ls_post_alternative_languages', '__return_empty_string', 999 );
	}

	/**
	 * Restore the WPML mechanism to display links to ranslated content.
	 *
	 * @since 3.2
	 */
	private function restore_wpml_alt_lang() {
		// Before WPML 3.6.0
		remove_filter( 'icl_post_alternative_languages', '__return_empty_string', 999 );
		// After WPML 3.6.0
		remove_filter( 'wpml_ls_post_alternative_languages', '__return_empty_string', 999 );
	}

	/**
	 * Keep track of nested applications of this shortcode over the same post object.
	 *
	 * @return null|string
	 * @since 3.2
	 */
	private function manage_nested_templates() {
		global $post;
		if ( false === ( $post instanceof \WP_Post ) ) {
			return null;
		}

		$nested_templates_memory = null;
		if (
			isset( $post->view_template_override )
			&& $post->view_template_override != ''
		) {
			$nested_templates_memory = $post->view_template_override;
		}

		$post->view_template_override = $this->user_atts['view_template'];

		return $nested_templates_memory;
	}

	/**
	 * Restore the reference to the previous instance of this shortcode, if any.
	 *
	 * @param null|string $nested_templates_memory
	 * @since 3.2
	 */
	private function restore_nested_templates( $nested_templates_memory ) {
		global $post;
		if ( false === ( $post instanceof \WP_Post ) ) {
			return;
		}

		if ( isset( $post->view_template_override ) ) {
			if ( $nested_templates_memory ) {
				$post->view_template_override = $nested_templates_memory;
			} else {
				unset( $post->view_template_override );
			}
		}
	}

	/**
	 * After resolving the item shortcode attribute, check whether the global post should be replaced by it.
	 *
	 * @param null|\WP_Post $item
	 * @return bool
	 * @since 3.2
	 */
	private function should_switch_global_post_to_item_attribute( $item ) {
		if ( null === $item ) {
			return false;
		}

		global $post;

		if ( false === ( $post instanceof \WP_Post ) ) {
			return true;
		}

		if ( $post->ID !== $item->ID ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the unprocessed content to render by the shortcode.
	 *
	 * It can be the CT content if the shortcode is set to render one,
	 * or the current post body if set to render None CT.
	 *
	 * @return string
	 * @since 3.2
	 */
	private function get_unprocessed_value_to_render() {
		if ( strtolower( $this->user_atts['view_template'] ) === 'none' ) {
			global $post;
			return ( $post instanceof \WP_Post ) ? $post->post_content : '';
		} else {
			$template_id = $this->get_template_id_by_name( $this->user_atts['view_template'] );
			return ( 0 === $template_id ) ? '' : get_post_field( 'post_content', $template_id );
		}
	}

	/**
	 * Make sure that the filter on a Content Template output is run.
	 *
	 * This is needed when disabling the WPV_template::the_content filter over the main applied hook.
	 * For historical reasons Content Template outcome has always been filtered over that the_content callback;
	 * however, as we manage the post body shortcode here, that got lost.
	 * This method restates the natural expected behavior.
	 *
	 * @param string $template_content
	 * @param int $template_id
	 * @return string
	 */
	private function force_filter_template_content( $template_content, $template_id ) {
		global $post;
		if ( false === ( $post instanceof \WP_Post ) ) {
			return $template_content;
		}

		return apply_filters( 'wpv_filter_content_template_output', $template_content, $template_id, $post->ID, 'listing-' . $post->post_type );
	}

	/**
	 * Check whether we are rendering data for a post that is password protected.
	 *
	 * @param null|\WP_Post $post
	 * @return bool
	 * @since 3.2
	 */
	private function should_require_post_password( $post ) {
		if ( false === ( $post instanceof \WP_Post ) ) {
			return false;
		}

		return post_password_required( $post );
	}

	/**
	 * Check whether a piece of content has been created using the blocks editor.
	 *
	 * @param string $content
	 * @return bool
	 * @since 3.2
	 */
	private function has_blocks( $content ) {
		if ( function_exists('has_blocks') ) {
			return has_blocks( $content );
		}

		return false;
	}

	/**
	 * Disable the known formatting hooks that might be assigned to a given hook.
	 *
	 * @param string $hook
	 * @return array List of hooks that were removed, by their priorities.
	 * @since 3.2
	 */
	private function maybe_disable_formatting_hooks( $hook ) {
		global $WPV_templates;

		$callbacks_candidates = array(
			'wpautop',
			'shortcode_unautop',
			'gutenberg_wpautop',
			'prepend_attachment',
			array( $WPV_templates, 'the_content' ),
			array( $WPV_templates, 'restore_wpautop' ),
		);
		$callbacks = array();

		foreach ( $callbacks_candidates as $callback ) {
			$priority = has_filter( $hook, $callback );
			if ( false !== $priority ) {
				remove_filter( $hook, $callback, $priority );
				$callbacks[ $priority ] = toolset_getarr( $callbacks, $priority, array() );
				$callbacks[ $priority ][] = $callback;
			}
		}

		return $callbacks;
	}

	/**
	 * Enforce the known formatting hooks that might be missing from a given hook.
	 *
	 * @param string $hook
	 * @return array List of hooks that were nforced, by their priorities.
	 * @since 3.2
	 */
	private function maybe_enforce_formatting_hooks( $hook ) {
		global $WPV_templates;

		$callbacks_candidates = array(
			'1' => array(
				array( $WPV_templates, 'the_content' ),
			),
			'6' => array(
				'gutenberg_wpautop',
			),
			'10' => array(
				'wpautop',
				'shortcode_unautop',
				'prepend_attachment',
			),
			'999' => array(
				array( $WPV_templates, 'restore_wpautop' ),
			),
		);
		$callbacks = array();

		foreach ( $callbacks_candidates as $priority_candidate => $callbacks_list ) {
			foreach ( $callbacks_list as $callback ) {
				$priority = has_filter( $hook, $callback );
				if (
					false === $priority
					&& is_callable( $callback )
				) {
					add_filter( $hook, $callback, $priority_candidate );
					$callbacks[ $priority_candidate ] = toolset_getarr( $callbacks, $priority_candidate, array() );
					$callbacks[ $priority_candidate ][] = $callback;
				}
			}
		}

		return $callbacks;
	}

	/**
	 * Rstore or remove hooks that were removed or enforced, to return to an original state.
	 *
	 * @param string $hook
	 * @param array $callbacks List of hooks to restore or remove, by their priorities.
	 * @param bool $add Whether to add or remove those hooks.
	 * @since 3.2
	 */
	private function maybe_restore_formatting_hooks( $hook, $callbacks, $add = true ) {
		foreach ( $callbacks as $priority => $callbacks_list ) {
			foreach ( $callbacks_list as $callback ) {
				if ( ! is_callable( $callback ) ) {
					continue;
				}
				if ( $add ) {
					add_filter( $hook, $callback, $priority );
				} else {
					remove_filter( $hook, $callback, $priority );
				}
			}
		}
	}

	/**
		 * @param  string $template_name
		 *
		 * @return int
		 */
		private function get_template_id_by_name( $template_name ) {
			$template_id = \WPV_Content_Template_Embedded::get_template_id_by_name( $template_name );
			if ( 0 === $template_id ) {
				return $template_id;
			}

			// The following filter might return null if WPML is active, hence we cast to int before returning.
			$template_id = apply_filters( 'translate_object_id', $template_id, \WPV_Content_Template_Embedded::POST_TYPE, true, null );
			return intval( $template_id );
		}
}
