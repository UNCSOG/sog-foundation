<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Class that handles all the WPML functionality related to shortcode attribute translations.
 *
 * @since 2.5.0
 */

class WPV_WPML_Shortcodes_Translation {

	const WPV_WPML_TRANSLATABLE_ATTRIBUTE_FORMAT = 'format';
	const WPV_WPML_TRANSLATABLE_ATTRIBUTE_PLACEHOLDER = 'placeholder';
	const WPV_WPML_TRANSLATABLE_ATTRIBUTE_DEFAULT_LABEL = 'default_label';

	protected $_context;

	public function __construct() {
		$this->_context = '';
		$this->init_hooks();
	}

	public function set_context( $context ) {
		$this->_context = $context;
	}

	/**
	 * Determine whether WPML is activated and configured.
	 *
	 * @return mixed|void
	 *
	 * @since 2.5.0
	 *
	 * @codeCoverageIgnore
	 */
	public function is_wpml_active_and_configured() {
		return apply_filters( 'toolset_is_wpml_active_and_configured', false );
	}

	/**
	 * Initialize hooks for the WPV_WPML_Shortcodes_Translation class.
	 *
	 * @since 2.5.0
	 */
	public function init_hooks() {
		// Views shortcodes integration - register on activation
		add_action( 'init', array( $this, 'register_wpml_strings_on_activation' ), 99 );

		add_filter( 'wpv_custom_inner_shortcodes', array( $this, 'wpml_string_in_custom_inner_shortcodes' ) );

		// Views shortcodes integration - generic
		add_action( 'wpv_action_wpv_register_wpml_strings', array( $this, 'register_wpml_strings' ) );
		add_action( 'wpv_action_wpv_register_wpml_strings', array( $this, 'register_shortcode_attributes_to_translate' ), 20, 2 );

		// Views shortcodes integration - automatic
		add_action( 'wpv_action_wpv_after_set_filter_meta_html', array( $this, 'register_wpml_strings' ) );
		add_action( 'wpv_action_wpv_after_set_filter_meta_html', array( $this, 'register_shortcode_attributes_to_translate' ), 20, 2 );
		add_action( 'wpv_action_wpv_after_set_loop_meta_html', array( $this, 'register_wpml_strings' ) );
		add_action( 'wpv_action_wpv_after_set_loop_meta_html', array( $this, 'register_shortcode_attributes_to_translate' ), 20, 2 );
		add_action( 'wpv_action_wpv_after_set_content_raw', array( $this, 'register_wpml_strings' ) );
		add_action( 'wpv_action_wpv_after_set_content_raw', array( $this, 'register_shortcode_attributes_to_translate' ), 20, 2 );

		add_action( 'save_post', array( $this, 'register_wpml_strings_for_save_post' ), 10, 2 );
		add_action( 'save_post', array( $this, 'register_shortcode_attributes_to_translate_for_save_post' ), 20, 2 );
	}


	/**
	 * Returns the array with the fake shortodes.
	 *
	 * @param integer $view_id
	 *
	 * @return array The array with the fake shortcodes.
	 */
	public function get_fake_shortcodes_array( $view_id = null ) {
		$is_or_includes_views_block = false;

		if ( $view_id ) {
			$is_views_block = get_post_meta( $view_id, '_wpv_is_gutenberg_view', true );

			if ( $is_views_block ) {
				$is_or_includes_views_block = true;
			} else {
				$included_views_blocks = get_post_meta( $view_id, '_wpv_contains_gutenberg_views', true );
				$is_or_includes_views_block = ! empty( $included_views_blocks );
			}
		}

		// For Legacy Views we need to keep all fake controls, otherwise they won't be send to translation at all.
		// For block views it's important to not send them here, otherwise we would produce duplicated entries in
		// String Translations.
		return $is_or_includes_views_block ?
			[
				'wpv-sort-orderby' => array( $this, 'fake_wpv_sorting_shortcode_to_wpml_register_string' ),
				'wpv-sort-order' => array( $this, 'fake_wpv_sorting_shortcode_to_wpml_register_string' ),
			] : [
				'wpv-control' => array( $this, 'fake_wpv_control_shortcode_to_wpml_register_string' ),
				'wpv-control-post-taxonomy' => array( $this, 'fake_wpv_control_post_taxonomy_shortcode_to_wpml_register_string' ),
				'wpv-control-postmeta' => array( $this, 'fake_wpv_control_postmeta_shortcode_to_wpml_register_string' ),
				'wpv-control-item' => array( $this, 'fake_wpv_control_post_ancestor_shortcode_to_wpml_register_string' ),
				'wpv-control-post-ancestor' => array( $this, 'fake_wpv_control_post_ancestor_shortcode_to_wpml_register_string' ), // Deprecated shortcode
				'wpv-filter-submit' => array( $this, 'fake_wpv_filter_submit_shortcode_to_wpml_register_string' ),
				'wpv-filter-reset' => array( $this, 'fake_wpv_filter_reset_shortcode_to_wpml_register_string' ),
				'wpv-sort-orderby' => array( $this, 'fake_wpv_sorting_shortcode_to_wpml_register_string' ),
				'wpv-sort-order' => array( $this, 'fake_wpv_sorting_shortcode_to_wpml_register_string' ),
				'wpv-pager-nav-links' => array( $this, 'fake_wpv_pagination_shortcode_to_wpml_register_string' ),
				'wpv-pager-archive-nav-links' => array( $this, 'fake_wpv_pagination_shortcode_to_wpml_register_string' ),
				'wpv-post-previous-link' => array( $this, 'fake_wpv_post_shortcode_to_wpml_register_string' ),
				'wpv-post-next-link' => array( $this, 'fake_wpv_post_shortcode_to_wpml_register_string' ),
				'toolset-edit-post-link' => array( $this, 'fake_toolset_edit_link_to_wpml_register_string' ),
				'toolset-edit-user-link' => array( $this, 'fake_toolset_edit_link_to_wpml_register_string' ),
				'wpv-filter-search-box' => array( $this, 'fake_wpv_filter_search_box_shortcode_to_wpml_register_string' ),
			];
	}

	/**
	 * Register shortcode attributes for translation.
	 *
	 * @param string  $content  The content coming from the saved editor
	 * @param int     $id       The View id
	 *
	 * @since unknown
	 * @since 2.4.0 Added call to register the pagination shortcode attributes for translation.
	 * @since 2.4.0 Added call to register the frontend filters shortcode attributes for translation.
	 * @since 2.4.0 Added call to register the content of the toolset-edit-post-link and toolset-edit-user-link shortcodes.
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the callbacks name.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function register_shortcode_attributes_to_translate( $content, $id ) {
		if ( $this->is_wpml_active_and_configured() ) {
			$this->set_context( 'View ' . get_post_field( 'post_name', $id ) );

			if ( get_post_status( $id ) !== 'publish' ) {
				return;
			}

			/**
			 * Filter wpv_filter_get_fake_shortcodes_for_attributes_translation
			 *
			 * This filter extends the fake shortcodes array that will be used to translate real shortcode attributes.
			 *
			 * @param  array $fake_shortcodes  The array with the fake shortcodes registered for translation.
			 *
			 * @return array The array with the fake shortcodes registered for attribute translation.
			 *
			 * @since 2.5.2
			 */
			$fake_shortcodes = apply_filters(
				'wpv_filter_get_fake_shortcodes_for_attributes_translation',
				$this->get_fake_shortcodes_array( $id )
			);

			$this->register_shortcodes_to_translate( $content, $fake_shortcodes );

			$this->set_context( '' );
		}
	}

	/**
	 * Register shortcode attributes for translation for the "save_post" hook.
	 *
	 * @param int     $id The ID of the post being saved.
	 * @param WP_Post $post The WP_Post object instance of the post being saved.
	 *
	 * @since 2.7.0
	 */
	public function register_shortcode_attributes_to_translate_for_save_post( $id, $post ) {
		if (
			is_int( $id ) &&
			$post instanceof WP_Post &&
			property_exists( $post, 'post_content' )
		) {
			$this->register_shortcode_attributes_to_translate( $post->post_content, $id );
		}
	}

	/**
	 * Generic method to register shortcode attributes for translation in WPML.
	 *
	 * @param string $content The content to parse for shortcodes with atrributes to translate
	 * @param array  $fake_shortcodes List of shortcodes to look for, with their fake callbacks to register translatable attributes
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	private function register_shortcodes_to_translate( $content, $fake_shortcodes ) {
		$is_match = false;
		$shortcodes_to_match = array_keys( $fake_shortcodes );
		foreach ( $shortcodes_to_match as $to_match ) {
			$is_match = ( $is_match || strpos( $content, '[' . $to_match ) !== false );
		}

		if ( ! $is_match ) {
			return;
		}

		global $shortcode_tags;
		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;

		remove_all_shortcodes();

		foreach ( $fake_shortcodes as $shortcode => $callback ) {
			add_shortcode( $shortcode, $callback );
		}

		$content = stripslashes( $content );

		do_shortcode( $content );

		$shortcode_tags = $orig_shortcode_tags;
	}

	/**
	 * Register the [wpml-string] shortcodes on WPML.
	 *
	 * @param string $content The content to parse for [wpml-string] shortcodes to register.
	 *
	 * @since 2.3.0
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function register_wpml_strings( $content ) {
		if ( $this->is_wpml_active_and_configured() ) {
			$fake_shortcodes = array(
				'wpml-string' => array( $this, 'fake_wpml_string_shortcode_to_wpml_register_string' ),
			);
			$this->register_shortcodes_to_translate( $content, $fake_shortcodes );

		}
	}

	/**
	 * Register the [wpml-string] shortcodes on WPML for the "save_post" hook.
	 *
	 * @param int     $id The ID of the post being saved.
	 * @param WP_Post $post The WP_Post object instance of the post being saved.
	 *
	 * @since 2.7.0
	 */
	public function register_wpml_strings_for_save_post( $id, $post ) {
		if (
			$post instanceof WP_Post &&
			property_exists( $post, 'post_content' )
		) {
			$this->register_wpml_strings( $post->post_content );
		}
	}

	/**
	 * Register all Views wpml-string shortcodes and all translatable strings in Views shortcodes.
	 *
	 * @since 1.5.0
	 * @since 1.6.2 Change of the hook to init as the user capabilities are not reliable before that (and they are used in get_posts()).
	 * @since 2.3.0 Moved to be a proper method in the WPML integration class.
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	function register_wpml_strings_on_activation() {
		if (
			$this->is_wpml_active_and_configured()
			&& ! get_option( 'wpv_strings_translation_initialized', false )
			&& current_user_can( EDIT_VIEWS )
		) {
			// Register strings from Views
			$views = get_posts( 'post_type=view&post_status=any&posts_per_page=-1' );
			foreach ( $views as $key => $view_post ) {
				$view_post = (array) $view_post;
				// Register strings in the content
				do_action( 'wpv_action_wpv_register_wpml_strings', $view_post['post_content'], $view_post['ID'] );
				// Register strings in the Filter HTML textarea
				$view_array = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $view_post['ID'] );
				if ( isset( $view_array['filter_meta_html'] ) ) {
					do_action( 'wpv_action_wpv_register_wpml_strings', $view_array['filter_meta_html'], $view_post['ID'] );
				}
				// Register strings in the Layout HTML textarea
				$view_layout_array = apply_filters( 'wpv_filter_wpv_get_view_layout_settings', array(), $view_post['ID'] );
				if ( isset( $view_layout_array['layout_meta_html'] ) ) {
					do_action( 'wpv_action_wpv_register_wpml_strings', $view_layout_array['layout_meta_html'], $view_post['ID'] );
				}
			}
			// Register strings from Content Templates
			$view_templates = get_posts( 'post_type=view-template&post_status=any&posts_per_page=-1' );
			foreach ( $view_templates as $key => $ct_post ) {
				$ct_post = (array) $ct_post;
				// Register strings in the content
				do_action( 'wpv_action_wpv_register_wpml_strings', $ct_post['post_content'], $ct_post['ID'] );
			}
			// Update the flag in the options so this is only run once
			update_option( 'wpv_strings_translation_initialized', 1 );
		}
	}

	/**
	 * Add the [wpml-string] shortcode to the allowed inner shortcodes,
	 * even if the [wpml-string] shortcode itself does not exist.
	 *
	 * @param array $custom_inner_shortcodes List of allowed custom inner shortcodes
	 *
	 * @return array
	 *
	 * @since 1.4.0
	 * @since 2.3.0 Moved to a proper method in the WPML integration class.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	function wpml_string_in_custom_inner_shortcodes( $custom_inner_shortcodes ) {
		if ( ! is_array( $custom_inner_shortcodes ) ) {
			$custom_inner_shortcodes = array();
		}
		$custom_inner_shortcodes[] = 'wpml-string';
		$custom_inner_shortcodes = array_unique( $custom_inner_shortcodes );
		return $custom_inner_shortcodes;
	}

	/**
	 * Register wpv-control shortcode attributes for translation.
	 *
	 * @param array $atts The shotcode attributes
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpv_control_shortcode_to_wpml_register_string( $atts ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		$url_param = isset( $atts['url_param'] ) ? $atts['url_param'] : '';

		if ( empty( $url_param ) ) {
			return;
		}

		// We need to catch each attribute one by one, just because of legacy and backwards compatibility :-)
		if ( isset( $atts['auto_fill_default'] ) ) {
			do_action( 'wpml_register_single_string', $this->_context, $url_param . '_auto_fill_default', $atts['auto_fill_default'] );
		}

		if ( isset( $atts['display_values'] ) ) {
			$display_values = explode( ',', $atts['display_values'] );
			foreach ( $display_values as $display_value_key => $display_value_to_translate ) {
				$display_value_to_translate = str_replace( array( '%%COMMA%%', '%comma%', '\,' ), ',', $display_value_to_translate );
				do_action( 'wpml_register_single_string', $this->_context, $url_param . '_display_values_' . ( $display_value_key + 1 ), $display_value_to_translate );
			}
		}

		if ( isset( $atts['title'] ) ) {
			do_action( 'wpml_register_single_string', $this->_context, $url_param . '_title', $atts['title'] );
		}

		// Taxonomy attributes
		if ( isset( $atts['default_label'] ) ) {
			do_action( 'wpml_register_single_string', $this->_context, $url_param . '_default_label', $atts['default_label'] );
		}

		return;
	}

	/**
	 * Register wpv-control-post-taxonomy shortcode attributes for translation.
	 *
	 * @param array $atts The shotcode attributes
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpv_control_post_taxonomy_shortcode_to_wpml_register_string( $atts ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		$url_param = isset( $atts['url_param'] ) ? $atts['url_param'] : '';

		if ( empty( $url_param ) ) {
			return;
		}

		$attributes_to_translate = array( self::WPV_WPML_TRANSLATABLE_ATTRIBUTE_DEFAULT_LABEL, self::WPV_WPML_TRANSLATABLE_ATTRIBUTE_FORMAT );

		$this->register_attributes_to_translate( $atts, $attributes_to_translate, $url_param . '_' . '%s' );

		return;
	}

	/**
	 * Register wpv-control-postmeta shortcode attributes for translation.
	 *
	 * @param array $atts The shotcode attributes
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpv_control_postmeta_shortcode_to_wpml_register_string( $atts ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		$url_param = isset( $atts['url_param'] ) ? $atts['url_param'] : '';

		if ( empty( $url_param ) ) {
			return;
		}

		// We need to catch each attribute one by one, just because of legacy and backwards compatibility :-)
		if ( isset( $atts['default_label'] ) ) {
			do_action( 'wpml_register_single_string', $this->_context, $url_param . '_auto_fill_default', $atts['default_label'] );
		}

		$attributes_to_translate = array( self::WPV_WPML_TRANSLATABLE_ATTRIBUTE_FORMAT, self::WPV_WPML_TRANSLATABLE_ATTRIBUTE_PLACEHOLDER );

		$this->register_attributes_to_translate( $atts, $attributes_to_translate, 'wpv_control_postmeta_' . '%s' . '_' . esc_attr( $atts['url_param'] ) );

		if ( isset( $atts['display_values'] ) ) {
			$display_values = explode( ',', $atts['display_values'] );
			foreach ( $display_values as $display_value_key => $display_value_to_translate ) {
				$display_value_to_translate = str_replace( array( '%%COMMA%%', '%comma%', '\,' ), ',', $display_value_to_translate );
				do_action( 'wpml_register_single_string', $this->_context, $url_param . '_display_values_' . ( $display_value_key + 1 ), $display_value_to_translate );
			}
		}

		return;
	}

	/**
	 * Register wpv-control-post-ancestor shortcode attributes for translation.
	 *
	 * @param array $atts The shotcode attributes
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpv_control_post_ancestor_shortcode_to_wpml_register_string( $atts ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		$ancestor_type = isset( $atts['ancestor_type'] ) ? $atts['ancestor_type'] : '';

		if ( empty( $ancestor_type ) ) {
			return;
		}

		$attributes_to_translate = array( self::WPV_WPML_TRANSLATABLE_ATTRIBUTE_DEFAULT_LABEL, self::WPV_WPML_TRANSLATABLE_ATTRIBUTE_FORMAT );

		$this->register_attributes_to_translate( $atts, $attributes_to_translate, $ancestor_type . '_' . '%s' );

		return;
	}

	/**
	 * Register wpv-filter-submit shortcode attributes for translation.
	 *
	 * @param array $atts The shotcode attributes
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpv_filter_submit_shortcode_to_wpml_register_string( $atts ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		$name = isset( $atts['name'] ) ? $atts['name'] : '';

		if ( empty( $name ) ) {
			return;
		}

		do_action( 'wpml_register_single_string', $this->_context, 'submit_name', $name );

		return;
	}

	/**
	 * Register wpv-filter-search-box shortcode attributes for translation.
	 *
	 * @param array $atts wpv-filter-search-box shortcode attributes
	 *
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpv_filter_search_box_shortcode_to_wpml_register_string( $atts ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		$placeholder = isset( $atts['placeholder'] ) ? $atts['placeholder'] : '';

		if ( empty( $placeholder ) ) {
			return;
		}

		do_action( 'wpml_register_single_string', $this->_context, 'search_input_placeholder', $placeholder );

		return;
	}

	/**
	 * Register wpv-filter-reset shortcode attributes for translation.
	 *
	 * @param array $atts The shotcode attributes
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpv_filter_reset_shortcode_to_wpml_register_string( $atts ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		$reset_label = isset( $atts['reset_label'] ) ? $atts['reset_label'] : '';

		if ( empty( $reset_label ) ) {
			return;
		}

		do_action( 'wpml_register_single_string', $this->_context, 'button_reset_label', $reset_label );

		return;
	}

	/**
	 * Fake callback for the wpml-string shortcode,
	 * so its attributes can be parsed and defaulted, and the string can be registered.
	 *
	 * @param array  $atts    The shotcode attributes
	 * @param string $content The shortcode content
	 *
	 * @since 2.2.2
	 * @since 2.3.0 Moved to a proper method of the WPML integration class.
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpml_string_shortcode_to_wpml_register_string( $atts, $content ) {
		if ( $this->is_wpml_active_and_configured() ) {
			$atts = shortcode_atts(
				array(
					'context' => 'wpml-shortcode',
					'name' => '',
				),
				$atts
			);
			$atts['name'] = empty( $atts['name'] ) ? 'wpml-shortcode-' . md5( $content ) : $atts['name'];
			do_action( 'wpml_register_single_string', $atts['context'], $atts['name'], $content );
		}
		return;
	}

	/**
	 * Fake callback for the wpv-post-xxx shortcodes, so their attributes can be registered.
	 *
	 * @param (array)   atts        Shortcode attributes
	 * @param (string)  content     Shortcode content
	 * @param (string)  tag         Shortcode tag
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpv_post_shortcode_to_wpml_register_string( $atts, $content, $tag ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		if ( ! is_array( $atts ) ) {
			return;
		}

		$wpml_context = '';
		$atts_to_names_for_labels = array();
		switch ( $tag ) {
			case 'wpv-post-previous-link':
				$wpml_context = $this->get_wpml_context_attribute( $atts, 'wpv-post-previous-link' );
				$atts_to_names_for_labels['format'] = 'post_control_for_previous_link_format';
				$atts_to_names_for_labels['link'] = 'post_control_for_previous_link_text';
				break;
			case 'wpv-post-next-link':
				$wpml_context = $this->get_wpml_context_attribute( $atts, 'wpv-post-next-link' );
				$atts_to_names_for_labels['format'] = 'post_control_for_next_link_format';
				$atts_to_names_for_labels['link'] = 'post_control_for_next_link_text';
				break;
			default:
				return;
		}

		foreach ( $atts as $att_key => $att_value ) {
			foreach ( $atts_to_names_for_labels as $att_for_label => $name_for_label ) {
				if ( strpos( $att_key, $att_for_label ) === 0 ) {
					$att_meta_key = substr( $att_key, strlen( $att_for_label ) );
					$name = $name_for_label . $att_meta_key . '_' . md5( $att_value );
					$context = '' !== $wpml_context ? $wpml_context : $this->_context;
					do_action( 'wpml_register_single_string', $context, $name, $att_value );
					break;
				}
			}
		}
	}

	/**
	 * Fake callback for the toolset-edit-xxx-link shortcodes, so their content can be registered.
	 *
	 * @param (array)   atts        Shortcode attributes
	 * @param (string)  content     Shortcode content
	 * @param (string)  tag         Shortcode tag
	 *
	 * @note Those shortcodes are registered for translation using the 'Toolset Shortcodes' context
	 * @note This translation uses a shortened content hash for pseudo-unique contexts
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_toolset_edit_link_to_wpml_register_string( $atts, $content, $tag ) {

		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		$atts = shortcode_atts(
			array(
				'content_template_slug' => '',
				'layout_slug' => '',
			),
			$atts
		);

		if (
			empty( $atts['content_template_slug'] )
			&& empty( $atts['layout_slug'] )
		) {
			return;
		}

		$name = $tag;
		foreach ( $atts as $att_key => $att_value ) {
			if ( in_array( $att_key, array( 'content_template_slug', 'layout_slug' ) ) ) {
				$name .= '_' . $att_value;
			}
		}
		$name .= '_' . substr( md5( $content ), 0, 12 );

		do_action( 'wpml_register_single_string', 'Toolset Shortcodes', $name, $content );
	}

	/**
	 * Fake callback for the wpv-sort-orderby and wpv-sort-order shortcodes,
	 * so its label attributes can be registered.
	 *
	 * @param array $atts The shotcode attributes
	 *
	 * @since 2.3.0
	 * @since 2.3.1 Register "label_asc_for_{field-slug] and label_desc_for_{field-slug} attribute values.
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 *
	 */
	public function fake_wpv_sorting_shortcode_to_wpml_register_string( $atts ) {

		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		if ( ! is_array( $atts ) ) {
			return;
		}

		$atts_to_names_for_labels = array(
			'label_for_' => 'sorting_control_for_',
			'label_asc_for_' => 'sorting_control_asc_for_',
			'label_desc_for_' => 'sorting_control_desc_for_',
		);

		foreach ( $atts as $att_key => $att_value ) {

			foreach ( $atts_to_names_for_labels as $att_for_label => $name_for_label ) {

				if ( strpos( $att_key, $att_for_label ) === 0 ) {

					$att_meta_key = substr( $att_key, strlen( $att_for_label ) );
					$name = $name_for_label . $att_meta_key;
					do_action( 'wpml_register_single_string', $this->_context, $name, $att_value );
					break;
				}
			}
		}
	}

	/**
	 * Fake callback for the wpv-pager-nav-links and wpv-pager-archive-nav-links shortcodes,
	 * so their label attributes can be registered.
	 *
	 * @param array $atts The shotcode attributes
	 *
	 * @since 2.4.0
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Translation.
	 */
	public function fake_wpv_pagination_shortcode_to_wpml_register_string( $atts ) {

		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		if ( ! is_array( $atts ) ) {
			return;
		}

		$atts_to_names_for_labels = array(
			'text_for_' => 'pagination_control_for_',
		);

		foreach ( $atts as $att_key => $att_value ) {

			foreach ( $atts_to_names_for_labels as $att_for_label => $name_for_label ) {

				if ( strpos( $att_key, $att_for_label ) === 0 ) {

					$att_meta_key = substr( $att_key, strlen( $att_for_label ) );
					$name = $name_for_label . $att_meta_key . '_' . md5( $att_value );
					do_action( 'wpml_register_single_string', $this->_context, $name, $att_value );
					break;
				}
			}
		}
	}

	/**
	 * Registers for translation the shortcode attributes that should be translated.
	 *
	 * @param array $shortcodes_atts   The shotcode attributes.
	 * @param array $atts_to_translate The array with the attribute names that need to be translated.
	 * @param string $name             The name under which translatable shortcode attributes is been registered.
	 *
	 * @note The $name parameter can be a format string that contains a type specifier for a single argument, mostly
	 *       used in order to include the translatable attribute name inside the registration name.
	 *
	 * @since 2.6.4
	 */
	function register_attributes_to_translate( $shortcodes_atts, $atts_to_translate, $name ) {
		foreach ( $atts_to_translate as $att_to_translate ) {
			if ( isset( $shortcodes_atts[ $att_to_translate ] ) ) {
				do_action( 'wpml_register_single_string', $this->_context, sprintf( $name, $att_to_translate ), $shortcodes_atts[ $att_to_translate ] );
			}
		}
	}

	/**
	 * Gets the custom WPML context, if it is defined in the shortcode's arguments.
	 *
	 * @param array  $atts
	 * @param string $default_context
	 *
	 * @return string
	 *
	 * @since 2.7.0
	 */
	private function get_wpml_context_attribute( $atts, $default_context) {
		return isset( $atts['wpml_context'] ) && '' !== $atts['wpml_context'] ? $atts['wpml_context'] : $default_context;
	}
}
