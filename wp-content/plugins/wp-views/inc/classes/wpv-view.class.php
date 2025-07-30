<?php

/**
 * Represents a single View.
 *
 * Full version with setters & co.
 *
 * @since 1.9
 */
class WPV_View extends WPV_View_Embedded {


    /**
     * See parent class constructor description.
     *
     * @param int|WP_Post $view View post object or ID.
     */
    public function __construct( $view ) {
        parent::__construct( $view );
    }


    /* ************************************************************************* *\
            Static methods
    \* ************************************************************************* */


    /**
     * Create an instance of WPV_View from View ID or a WP_Post object.
     *
     * See WPV_View_Embedded constructor for details.
     *
     * @param int|WP_Post $view View ID or a WP_Post object.
     *
     * @return null|WPV_View
     */
    public static function get_instance( $view ) {
        try{
            $view = new WPV_View( $view );
            return $view;
        } catch( Exception $e ) {
            return null;
        }
    }


    /**
     * Create a new View.
     *
     * If the View purpose is set to "slider", also automatically create new Loop template.
     *
     * @param string $title New View title. Must be unique and valid (see validate_title()).
     * @param array $args (
     *          @type array $view_settings View settings that should override the default ones. Optional.
     *          @type array $loop_settings Loop settings that should override the default ones. Optional.
     *          @type bool $forbid_loop_template Never create a Loop template for this View. Optional, default is false.
     *     )
     *
     * @return WPV_View New View object.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws WPV_RuntimeExceptionWithMessage
     *
     * @note overriding default Views settings and layout settings must provide complete data when the element is an
     * array, because it overrides them all. For example, $args['settings']['pagination'] can not override just the
     * "postsper page" options: it must provide a complete pagination implementation. This might change and be corrected
     * in the future, keeping backwards compatibility.
     *
     * @since 1.10
     */
    public static function create( $title, $args ) {

        $view_id = WPV_View_Base::create_post( $title, $args );

        $view = new WPV_View( $view_id );

        $view->defer_after_update_actions();

        // Construct default View settings and Loop settings based on View purpose
        $view_settings = wpv_getarr( $args, 'view_settings', array() );

        $view_settings[ WPV_View_Base::VIEW_SETTINGS_QUERY_MODE ] = 'normal';

        $view_purpose = wpv_getarr( $view_settings, WPV_View_Embedded::VIEW_SETTINGS_PURPOSE, 'full', array( 'full', 'pagination', 'parametric', 'slider', 'all' ) );
        $view_settings[ WPV_View_Embedded::VIEW_SETTINGS_PURPOSE ] = $view_purpose;

        $view_settings_default = wpv_view_default_settings( $view_purpose );
        $view_settings = wp_parse_args( $view_settings, $view_settings_default );

        $view->update_postmeta( WPV_View_Base::POSTMETA_VIEW_SETTINGS, $view_settings );

        $loop_settings_default = wpv_view_default_layout_settings( $view_purpose );

        $loop_settings = wpv_getarr( $args, 'loop_settings', array() );
        $loop_settings = wp_parse_args( $loop_settings, $loop_settings_default );

        $view->update_postmeta( WPV_View_Base::POSTMETA_LOOP_SETTINGS, $loop_settings );

        // For the Slider purpose, automatically create a Loop template
        $forbid_loop_template = wpv_getarr( $args, 'forbid_loop_template', false );
        if ( ! $forbid_loop_template && ( 'slider' == $view_purpose ) ) {

            $ct_title = sprintf( '%s - %s', $title, __( 'slide', 'wpv-views' ) );

            $view->create_loop_template( $ct_title, '[wpv-post-link]' );

            // I really hate this solution
            $view->update_postmeta( '_wpv_first_time_load', 'on' );
        }

        $view->resume_after_update_actions();

        return $view;
    }

	/**
	 * Gets the parent post ID for the View.
	 *
	 * Views created in the block editor can only be edited inside the post/page they were created into or to another one
	 * that has become their parent post by a user action. This method returns the ID of that post/page.
	 *
	 * @return int|null
	 */
    public function get_parent_post_id() {
	    $view_parent_post_id = null;
		$view_data = $this->get_postmeta( '_wpv_view_data' );

	    if ( ! $view_data ) {
		    return $view_parent_post_id;
	    }

	    if ( isset( $view_data['parentPostId'] ) ) {
		    $view_parent_post_id = $view_data['parentPostId'];
	    } elseif ( isset( $view_data['general']['parent_post_id'] ) ) {
		    // support newly created views where parent_post_id is a part of 'general' structure
			$view_parent_post_id = $view_data['general']['parent_post_id'];
			if ( ! empty( $view_data['general']['parent_post_slug'] ) ) {
				// if post slug doesn't match the post, let's try to find post by slug
				$parent_post = \WP_Post::get_instance( $view_parent_post_id );
				if (
					$parent_post &&
					$view_data['general']['parent_post_slug'] !== $parent_post->post_name
				) {
					$posts = get_posts( array(
						'name' => $view_data['general']['parent_post_slug'],
						'post_type' => 'view',
						'post_status' => 'publish',
						'numberposts' => 1,
					) );
					if ( count( $posts ) > 0 ) {
						$view_parent_post_id = $posts[0]->ID;
					}
				}
			}
	    }

	    return $view_parent_post_id;
    }

	/**
	 * Returns true if the View was created in the block editor.
	 *
	 * @return bool
	 */
    public function get_is_gutenberg_view() {
	    $maybe_gutenberg_view = $this->get_postmeta( '_wpv_is_gutenberg_view' );
	    return ! $maybe_gutenberg_view ? false : true;
    }


    /* ************************************************************************* *\
        Setters (& validators)
    \* ************************************************************************* */


    /**
     * Validate Filter meta HTML before saving it to database.
     *
     * Perform syntax check to ensure mandatory elements are all present exactly once and in the right order.
     * If that's not the case, throw an exception containing a message - this time very user-friendly one,
     * with thorough description of what's wrong and with minimal demo content.
     *
     * @param string $value The value to be sanitized. It *must* have added slashes (especially before quotes), otherwise
     *     the validation has undefined result.
     * @return string The same value if validation has passed.
     * @throws WPV_RuntimeExceptionWithMessage if validation fails.
     * @since 1.10
     */
    protected function _validate_filter_meta_html( $value ) {

        // List of separate elements to match, each with a match pattern and label and indent level for display purposes.
        $elements = array(
            array( 'label' => '[wpv-filter-start]', 'pattern' => "\\[wpv-filter-start(\\ +[a-z]+\\=\\\\\\\"[a-z0-9]*\\\\\\\")*\\ *\\]", 'indent' => 0 ),
            array( 'label' => '[wpv-filter-end]', 'pattern' => "\\[wpv-filter-end\\]", 'indent' => 0 )
        );

        $this->validate_meta_html_content( $value, __( 'Filter', 'wpv-views' ), $elements );

        return $value;
    }


    /**
     * Set Filter meta HTML.
     *
     * This is a View setting.
     *
     * Also registers strings and labels in wpv-control* shortcodes for WPML translation if the value has changed.
     *
     * @param string $value The value to be sanitized. It *must* have added slashes (especially before quotes), otherwise
     *     the validation has undefined result.
     * @throws WPV_RuntimeExceptionWithMessage if validation fails.
     * @since 1.10
     */
    protected function _set_filter_meta_html( $value ) {
        $value = $this->_validate_filter_meta_html($value);
        if( $this->filter_meta_html != $value ) {
            $this->set_view_setting( WPV_View_Embedded::VIEW_SETTINS_FILTER_META_HTML, $value );
			/**
			 * Fires once the value for the Filter editor for a View has been updated,
			 * but before it has been saved.
			 *
			 * @since 2.3.0
			 *
			 * @param string 	$value		Value of the Filter editor.
			 * @param int		$this->id	View ID.
			 *
			 * @note This replaces two calls used here before: 'wpv_register_wpml_strings' and 'wpv_add_controls_labels_to_translation'
			 */
			do_action( 'wpv_action_wpv_after_set_filter_meta_html', $value, $this->id );
        }
    }

	/**
	 * Resets the Filter Meta HTML content to its default state.
	 */
	public function reset_filter_meta_html() {
		$view_defaults = $this->get_postmeta_defaults();
		$this->_set_filter_meta_html( $view_defaults[ WPV_View_Base::POSTMETA_VIEW_SETTINGS ][ WPV_View_Embedded::VIEW_SETTINS_FILTER_META_HTML ] );
	}


    /**
     * Set Filter extra CSS code.
     *
     * This is a View setting.
     *
     * @param string $value
     * @since 1.10
     */
    protected function _set_filter_css( $value ) {
        $this->set_view_setting( WPV_View_Embedded::VIEW_SETTINS_FILTER_CSS, $value );
    }


    /**
     * Set Filter extra JS code.
     *
     * This is a View setting.
     *
     * @param string $value
     * @since 1.10
     */
    protected function _set_filter_js( $value ) {
        $this->set_view_setting( WPV_View_Embedded::VIEW_SETTINS_FILTER_JS, $value );
    }
}
