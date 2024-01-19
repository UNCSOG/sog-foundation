<?php

/**
 * Singleton encapsulating (new) WPML-related functionality.
 *
 * @since 1.10
 * @since 2.5.0  Some of the functionality of this class was unloaded to other smaller
 *               classes (WPV_WPML_Shortcodes_Dialog and WPV_WPML_Shortcodes_Translation).
 */
class WPV_WPML_Integration_Embedded {

	/**
	 * The instance.
	 *
	 * @var WPV_WPML_Integration_Embedded
	 * @since 1.10
	 */
	protected static $instance = null;

	/**
	 * @var null|Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured
	 */
	private $wpml_is_active_and_configured;

	/**
	 * Cache whether WPML ST is loaded.
	 *
	 * @var bool
	 */
	private $is_wpml_st_loaded = null;

	/**
	 * Cache whether WPML TM is loaded.
	 *
	 * @var bool
	 */
	private $is_wpml_tm_loaded = null;

	/**
	 * Get the instance of the singleton (and create it if it doesn't exist yet).
	 *
	 * @return WPV_WPML_Integration_Embedded
	 * @since 1.10
	 *
	 * @codeCoverageIgnore
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new WPV_WPML_Integration_Embedded();
		}
		return self::$instance;
	}

	/**
	 * Initialize the singleton.
	 *
	 * @since 1.10
	 *
	 * @codeCoverageIgnore
	 */
	public static function initialize() {
		self::get_instance();
	}

	/**
	 * @var string Holds the current context when registering attribute values for Views shortcodes.
	 * @since 2.3.0
	 */
	protected $_context = '';

	/**
	 * Singleton instantiation.
	 *
	 * Should happen before plugins_loaded action. Register further action hooks.
	 *
	 * @param Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured|null $wpml_is_active_and_configured
	 *
	 * @note ATTENTION!!! Always use as a singleton in production code.
	 * @note We do not add the [wpml-breadcrumbs] shortcode as it needs to be executed outside the post loop, hence it is useless for Views.
	 *
	 * @since 1.10
	 */
	public function __construct( \Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured $wpml_is_active_and_configured = null ) {
		$this->wpml_is_active_and_configured = $wpml_is_active_and_configured
			?: new \Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured();

		$this->init_hooks();

		new WPV_WPML_Shortcodes_Dialog();
		new WPV_WPML_Shortcodes_Translation();
	}

	public static function tear_down() {
		self::$instance = null;
	}

	/**
	 * Initialize the class.
	 *
	 * @since 2.5.0
	 */

	public function init_hooks() {
		add_action( 'init', array( $this, 'add_string_translation_to_formatting_instructions' ) );

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// Action after saving translated post content
		add_action( 'icl_pro_translation_completed', array( $this, 'icl_pro_translation_completed' ) );

		add_action( 'wp_ajax_wpv_suggest_wpml_contexts', array( $this, 'suggest_wpml_contexts' ) );
		add_action( 'wp_ajax_nopriv_wpv_suggest_wpml_contexts', array( $this, 'suggest_wpml_contexts' ) );

		add_action( 'wpv_updated__wpv_view_template_extra_css_meta', array( $this, 'maybe_sync_custom_field' ), 10, 2 );
		add_action( 'wpv_updated__wpv_view_template_extra_js_meta', array( $this, 'maybe_sync_custom_field' ), 10, 2 );

		add_filter( 'wpv_filter_selected_taxonomy_filter_values', array( $this, 'adjust_selected_taxonomy_term_value' ), 10, 2 );

		add_filter( 'wpml_ls_language_url', array( $this, 'maybe_clean_wpml_lang_switcher_link' ) );

	}

	/**
	 * Determine whether WPML String Translation is active and fully loaded.
	 *
	 * @return bool

	 * @since 2.5.0
	 */
	public function is_wpml_st_loaded() {
		if ( null === $this->is_wpml_st_loaded ) {
			$this->is_wpml_st_loaded = Toolset_WPML_Compatibility::get_instance()->is_wpml_st_active();
		}
		return $this->is_wpml_st_loaded;
	}

	/**
	 * Determine whether WPML Translation Management is active and fully loaded.
	 *
	 * @return bool

	 * @since 2.5.0
	 */
	public function is_wpml_tm_loaded() {
		if ( null === $this->is_wpml_tm_loaded ) {
			$this->is_wpml_tm_loaded = Toolset_WPML_Compatibility::get_instance()->is_wpml_tm_active();
		}
		return $this->is_wpml_tm_loaded;
	}

	/**
	 * WPML integration actions on admin_init.
	 *
	 * @since 1.10
	 */
	public function admin_init() {
		$this->hook_filters_for_links();
	}

	/**
	 * Hook into WPML filters and modify links to edit or view Content Templates in
	 * WPML Translation Management.
	 *
	 * @since 1.10
	 */
	protected function hook_filters_for_links() {
		add_filter( 'wpml_document_edit_item_link', array( $this, 'wpml_get_document_edit_link_ct' ), 10, 5 );
		add_filter( 'wpml_document_view_item_link', array( $this, 'wpml_get_document_view_link_ct' ), 10, 5 );
		add_filter( 'wpml_document_edit_item_url', array( $this, 'wpml_document_edit_item_url_ct' ), 10, 3 );
	}

	/**
	 * Modify Edit link on Translation Dashboard of WPML Translation Management
	 *
	 * For Content Templates in default language, return the link to CT read-only page. For
	 * CTs in different languages, don't show any link.
	 *
	 * @param string $post_edit_link The HTML code of the link.
	 * @param string $label Link label to be displayed.
	 * @param object $current_document
	 * @param string $element_type 'post' for posts.
	 * @param string $content_type If $element_type is 'post', this will contain a post type.
	 *
	 * @return string Link HTML.
	 *
	 * @since 1.10
	 */
	public function wpml_get_document_edit_link_ct( $post_edit_link, $label, $current_document, $element_type, $content_type ) {

		if ( 'post' == $element_type && WPV_Content_Template_Embedded::POST_TYPE == $content_type ) {
			$ct_id = $current_document->ID;

			// we know WPML is active, nothing else should call this filter
			global $sitepress;

			if ( $sitepress->get_default_language() != $current_document->language_code ) {
				// We don't allow editing CTs in nondefault languages in our editor.
				// todo add link to translation editor instead
				$post_edit_link = '';
			} else {
				$link = apply_filters( 'icl_post_link', array(), WPV_Content_Template_Embedded::POST_TYPE, $ct_id, 'edit' );
				$is_disabled = wpv_getarr( $link, 'is_disabled', false );
				$url = wpv_getarr( $link, 'url' );

				if ( $is_disabled ) {
					$post_edit_link = '';
				} elseif ( ! empty( $url ) ) {
					$post_edit_link = sprintf( '<a href="%s" target="_blank">%s</a>', $url, $label );
				}
			}
		}
		return $post_edit_link;
	}


	/**
	 * Modify View link on Translation Dashboard of WPML Translation Management
	 *
	 * Content Templates have no clear "View" option, so we're disabling the link for them.
	 *
	 * @param string $post_view_link Current view link.
	 * @param string $label Link label to be displayed.
	 * @param object $current_document
	 * @param string $element_type 'post' for posts.
	 * @param string $content_type If $element_type is 'post', this will contain a post type.
	 *
	 * @return string Link HTML
	 *
	 * @since 1.10
	 */
	public function wpml_get_document_view_link_ct( $post_view_link,
		/** @noinspection PhpUnusedParameterInspection */ $label,
		/** @noinspection PhpUnusedParameterInspection */ $current_document,
												 $element_type, $content_type ) {
		if ( 'post' == $element_type && WPV_Content_Template_Embedded::POST_TYPE == $content_type ) {
			// For a Content Template, there is nothing to view directly
			// todo link to some example content, if any exists
			$post_view_link = '';
		}
		return $post_view_link;
	}

	/**
	 * Modify edit URLs for Content Templates on Translation Queue in WPML Translation Management.
	 *
	 * For CT, return URL to CT edit page.
	 *
	 * @param string $edit_url Current edit URL
	 * @param string $content_type For posts, this will be post_{$post_type}.
	 * @param int    $element_id Post ID if the element is a post.
	 *
	 * @return string Edit URL.
	 *
	 * @since 1.10
	 */
	public function wpml_document_edit_item_url_ct( $edit_url, $content_type, $element_id ) {
		if ( 'post_' . WPV_Content_Template_Embedded::POST_TYPE == $content_type ) {
			if ( $element_id ) {
				$link = apply_filters( 'icl_post_link', array(), WPV_Content_Template_Embedded::POST_TYPE, $element_id, 'edit' );
				$url = wpv_getarr( $link, 'url' );
				$is_disabled = wpv_getarr( $link, 'is_disabled', false );
				if ( $is_disabled ) {
					$edit_url = ''; // todo check if this works well
				} elseif ( ! empty( $url ) ) {
					$edit_url = $url;
				}
			}
		}

		return $edit_url;
	}

	/**
	 * This action hook is invoked when the translation in WPML TM is completed.
	 *
	 * For Views, WPAs and Content Templates, we will manually run the appropriate "after update" action.
	 *
	 * @param int $new_post_id ID of the newly created post.
	 * @since 1.12
	 */
	public function icl_pro_translation_completed( $new_post_id ) {
		$post = get_post( $new_post_id );
		if ( $post instanceof WP_Post ) {
			switch ( $post->post_type ) {
				case WPV_Content_Template_Embedded::POST_TYPE:
					$ct = WPV_Content_Template_Embedded::get_instance( $post );
					$ct->after_update_action();
					break;
				case WPV_View_Base::POST_TYPE:
					// View or WPA, doesn't make difference this time.
					$view = WPV_View_Base::get_instance( $post );
					$view->after_update_action();
					break;
			}
		}
	}

	public function wpml_get_user_admin_language_post_id( $id, $element_type = 'any' ) {
		$current_user_id = get_current_user_id();
		$user_admin_lang = apply_filters( 'wpml_get_user_admin_language', '', $current_user_id );
		$id = apply_filters( 'translate_object_id', $id, $element_type, true, $user_admin_lang );
		return $id;
	}

	/**
	 * Registers the hooks to add the String Translation information to the formatting instructions under CodeMirror textareas
	 *
	 * @since 1.7
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Integration_Embedded.
	 */
	public function add_string_translation_to_formatting_instructions() {
		if ( $this->is_wpml_st_loaded() ) {
			// Register the section
			add_filter( 'wpv_filter_formatting_help_filter', array( $this, 'register_wpml_section' ) );
			add_filter( 'wpv_filter_formatting_help_layout', array( $this, 'register_wpml_section' ) );
			add_filter( 'wpv_filter_formatting_help_inline_content_template', array( $this, 'register_wpml_section' ) );
			add_filter( 'wpv_filter_formatting_help_layouts_content_template_cell', array( $this, 'register_wpml_section' ) );
			add_filter( 'wpv_filter_formatting_help_combined_output', array( $this, 'register_wpml_section' ) );
			add_filter( 'wpv_filter_formatting_help_content_template', array( $this, 'register_wpml_section' ) );

			// Register the section content
			add_filter( 'wpv_filter_formatting_instructions_section', array( $this, 'wpml_string_translation_shortcodes_instructions' ), 10, 2 );
		}
	}

	/**
	 * Registers the formatting instructions section for WPML in several textareas
	 *
	 * Check if the string_translation section has already been registered. If not, add it to the hooked formatting instructions boxes
	 *
	 * @param array $sections   Registered sections for the formatting instructions.
	 *
	 * @return array $sections
	 *
	 * @since 1.7
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Integration_Embedded.
	 */

	function register_wpml_section( $sections ) {
		if ( ! in_array( 'string_translation', $sections ) ) {
			array_splice( $sections, -2, 0, array( 'string_translation' ) );
		}
		return $sections;
	}


	/**
	 * Registers the content of the WPML section in several formatting instructions boxes
	 *
	 * @param $return (array|false) What to return, generally an array for the section that you want to give content to
	 *     'classname' => (string) A specific classname for this section, useful when some kind of show/hide functionality is needed
	 *     'title' => (string) The title of the section
	 *     'content' => (string) The main text of the section
	 *     'table' => (array) Table of ( Element, Description) arrays to showcase shortcodes, markup or related things
	 *         array(
	 *             'element' => (string) The element to describe. You can use some classes to add styling like in the CodeMirror instances: .wpv-code-shortcode, .wpv-code-html, .wpv-code-attr or .wpv-code-val
	 *             'description' => (string) The element description
	 *         )
	 *     'content_extra' => (string) Extra text to be displayed after the table
	 * @param $section (string) The name of the section
	 * @return array $return (array|false)
	 *
	 * @since 1.7
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Integration_Embedded.
	 */
	public function wpml_string_translation_shortcodes_instructions( $return, $section ) {
		if ( 'string_translation' == $section ) {
			$return = array(
				'classname' => 'js-wpv-editor-instructions-for-string-translation',
				'title' => __( 'String translation shortcodes', 'wpv-views' ),
				'content' => '',
				'table' => array(
					array(
						'element' => '<span class="wpv-code-shortcode">[wpml-string</span> <span class="wpv-code-attr">context</span>=<span class="wpv-code-val">"wpv-views"</span><span class="wpv-code-shortcode">]</span>'
									 . __( 'Text content', 'wpv-views' )
									 . '<span class="wpv-code-shortcode">[/wpml-string]</span>',
						'description' => __( 'Makes the text content translatable via WPML\'s String Translation.', 'wpv-views' ),
					),
				),
				'content_extra' => '',
			);
		}
		return $return;
	}

	/**
	 * Suggest for WPML string shortcode context, from a suggest callback
	 *
	 * @since 1.4
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Integration_Embedded.
	 */
	public function suggest_wpml_contexts() {
		global $wpdb;
		$context_q = '%' . wpv_esc_like( $_REQUEST['q'] ) . '%';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT context
			FROM {$wpdb->prefix}icl_strings
			WHERE context LIKE %s
			ORDER BY context ASC",
				$context_q
			)
		);

		$suggestions_string = '';
		foreach ( $results as $row ) {
			$suggestions_string .= $row->context . PHP_EOL;
		}

		wp_send_json( $suggestions_string );
	}

	/**
	 * Determines if the Content Template post meta for the extra CSS or JS should also be updated for the translations.
	 * of the Content Template.
	 *
	 * WPML synchronises custom field values between translations upon "save_post". When saving a Content Template section,
	 * other than the Content Template content, title or slug, we are doing it with "update_post_meta", so the "save_post"
	 * action is not triggered, thus the custom field values are not synced. We need to sync them manually.
	 *
	 * @param int    $post_id
	 * @param string $meta_key
	 */
	public function maybe_sync_custom_field( $post_id, $meta_key ) {
		if (
			in_array(
				$meta_key,
				array(
					WPV_Content_Template_Embedded::POSTMETA_TEMPLATE_EXTRA_CSS,
					WPV_Content_Template_Embedded::POSTMETA_TEMPLATE_EXTRA_JS,
				),
				true
			) &&
			$this->wpml_is_active_and_configured->is_met()
		) {
			do_action( 'wpml_sync_custom_field', $post_id, $meta_key );
		}
	}

	/**
	 * Adjust the selected value for a taxonomy frontend filter when WPML is enabled.
	 *
	 * @param array $walker_args The walker arguments being built.
	 * @param array $atts        The shortcode attributes.
	 *
	 * @return array The (filtered) The walker arguments.
	 *
	 * @since 2.7.0
	 * @note This is not going to work on taxonomy filters
	 *  set to render option values as term names, so I am
	 *  defaulting them all to use slugs if they provide no other data.
	 */
	public function adjust_selected_taxonomy_term_value( $walker_args, $atts ) {
		if (
			! isset( $walker_args['selected'] ) ||
			! is_array( $walker_args['selected'] ) ||
			! isset( $atts['taxonomy'] )
		) {
			return $walker_args;
		}

		global $sitepress;

		// To get the translated taxonomy slugs, we need to remove this WPML filter.
		$filter_exists = remove_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10 );

		foreach ( $walker_args['selected'] as $key => $walker_arg ) {
			$term = get_term_by( 'slug', $walker_arg, $atts['taxonomy'] );

			if ( false === $term ) {
				continue;
			}

			$walker_args['selected'][ $key ] = urldecode_deep( $term->slug );
		}

		if ( $filter_exists ) {
			add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ), 10, 4 );
		}

		return $walker_args;
	}

	/**
	 * Cleans the URL of the WPML language switcher by removing the numeric indexes from array-ed posted data.
	 *
	 * @see $this->clean_permalink_url
	 *
	 * @param string $language_url
	 *
	 * @return string
	 */
	public function maybe_clean_wpml_lang_switcher_link( $language_url ) {
		global $WPV_Pagination_Embedded;
		return '' !== toolset_getget( 'wpv_view_count', '' ) ?
			$WPV_Pagination_Embedded->clean_permalink_url( $language_url ) :
			$language_url;
	}

	/**
	 * Get the language status HTML for the WPML TM translations of a given post.
	 *
	 * This HTML status contains a link to the translation queue and the status icon.
	 *
	 * @param int    $post_id The ID of the post to check.
	 * @param string $language The language to check.
	 * @return string|null
	 * @since 3.1
	 */
	public function get_wpml_tm_status_html( $post_id, $language ) {
		if ( ! $this->is_wpml_tm_loaded() ) {
			return null;
		}

		if ( ! class_exists( 'WPML_Post_Status_Display_Factory' ) ) {
			return null;
		}
		global $sitepress;
		$factory = new WPML_Post_Status_Display_Factory( $sitepress );

		if ( ! is_callable( array( $factory, 'create' ) ) ) {
			return null;
		}
		$display = $factory->create();

		if ( ! is_callable( array( $display, 'get_status_html' ) ) ) {
			return null;
		}

		return $display->get_status_html( $post_id, $language );
	}
}
