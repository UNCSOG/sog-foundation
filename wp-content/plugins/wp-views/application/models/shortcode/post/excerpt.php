<?php

/**
 * Class WPV_Shortcode_Post_Excerpt
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Excerpt extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-excerpt';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'length'       => 0,
		'count'	       => 'char',
		'more'         => null,
		'format'       => 'autop',
		'output'       => 'formatted',
		'wpml_context' => self::SHORTCODE_NAME,
		'render_more'  => 'yes',
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
	 * WPV_Shortcode_Post_Excerpt constructor.
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
	*
	* @return string
	*
	* @since 2.5.0
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( ! $item_id = $this->item->get( $this->user_atts ) ) {
			// no valid item
			throw new WPV_Exception_Invalid_Shortcode_Attr_Item();
		}

		$out = '';
		$debug = '';

		$item = $this->get_post( $item_id );

		if ( null === $item ) {
			return $out;
		}

		if ( post_password_required( $item->ID ) ) {

			/**
			* Filter wpv_filter_post_protected_excerpt
			*
			* @param (string) The default WordPress string returned when displaying the excerpt of a password protected post
			* @param (object) $post The post object to which this shortcode is related to
			* @param (array) $atts The array of attributes passed to this shortcode
			*
			* @return (string)
			*
			* @since 1.7.0
			*/

			return apply_filters( 'wpv_filter_post_protected_excerpt', __( 'There is no excerpt because this is a protected post.', 'wpv-views' ), $item, $this->user_atts );
		}

		global $WPV_templates;

		if (
			! empty( $item )
			&& $item->post_type != 'view'
			&& $item->post_type != 'view-template'
		) {

			if ( isset( $this->infinite_loop_keys[ $item->ID ] ) ) {
				return '';
			}

			$this->infinite_loop_keys[ $item->ID ] = true;

			$out_array = array( 'out' => '', 'debug' => '' );

			if ( $this->user_atts['output'] == 'formatted' ) {
				$out_array = $this->get_formatted_excerpt(
					$item,
					$this->user_atts['length'],
					$this->user_atts['count'],
					$this->user_atts['more'],
					$this->user_atts['format'],
					$this->user_atts['wpml_context'],
					filter_var( $this->user_atts['render_more'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE )
				);
			} else {
				$out_array = $this->get_raw_excerpt( $item );
			}

			$out = $out_array['out'];
			$debug = $out_array['debug'];

			unset( $this->infinite_loop_keys[ $item->ID ] );

		}

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-excerpt', json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;
	}

	/**
	 * Prepare the formatted post excerpt.
	 *
	 * @param object    $item           The current post
	 * @param int       $length         The shortcode argument value for the 'length' argument of the wpv_post_excerpt shortcode
	 * @param string    $count          The shortcode argument value for the 'count' {char|word} argument of the wpv_post_excerpt shortcode
	 * @param string    $more           The shortcode argument value for the 'more' argument of the wpv_post_excerpt shortcode
	 * @param string    $format         The shortcode argument value for the 'format' argument of the wpv_post_excerpt shortcode
	 * @param string    $wpml_context   The WPML context
	 * @param boolean   $render_more    Whether to render the 'more' part when the excerpt is too long or not.
	 *
	 * @return array [ 'out' => The formatted post excerpt, 'debug' => Debug ]
	 *
	 * @since 2.3.0
	 * @since 2.5.0 Move to the shortcode class as a private method.
	 * @since 3.0 Added option to not render the 'more' part.
	 */
	private function get_formatted_excerpt( $item, $length, $count, $more, $format, $wpml_context, $render_more=true ) {
		global $WPV_templates;

		$debug = $out = '';

		// verify if displaying the real excerpt field or part of the content one
		$display_real_excerpt = false;
		$excerpt = $item->post_content;

		if ( ! empty( $item->post_excerpt ) ) {
			// real excerpt content available
			$display_real_excerpt = true;
			$excerpt              = $item->post_excerpt;
		}
		$excerpt = str_replace( ']]>', ']]&gt;', $excerpt );

		if ( $length > 0 ) {
			$excerpt_length = $length;
		} else if ( $display_real_excerpt ) {
			$excerpt_length = strlen( $excerpt ); // don't cut manually inserted excerpts if there is no length attribute
		} else {
			$excerpt_length = apply_filters( 'excerpt_length', 252 ); // on automatically created excerpts, apply the core excerpt_length filter
		}

		if ( $render_more ) {
			if ( \OTGS\Toolset\Views\Controller\Compatibility\Wpml::get_instance()->is_wpml_st_loaded() ) {
				$more = wpv_translate(
					'post_control_for_excerpt_more_text_' . md5( $more ),
					$more,
					false,
					$wpml_context
				);
			}

			$excerpt_more = is_null( $more )
				? apply_filters( 'excerpt_more', ' ' . '...' ) // when no more attribute is used, apply the core excerpt_more filter; it will only be used if the excerpt needs to be trimmed
				: $more;
		} else {
			// It's important that it's an empty string if we don't want to render it, because while wp_html_excerpt(),
			// if given null for $more parameter would default to empty string, wp_trim_words(), on the other hand,
			// would default to ellipsis given same value for same parameter.
			$excerpt_more = '';
		}

		/**
		 * Filter wpv_filter_post_excerpt
		 *
		 * This filter lets you modify the string that will generate the excerpt before it's passed through wpv_do_shortcode() and before the length attribute is applied
		 * This way you can parse and delete specific shortcodes from the excerpt, like the [caption] one
		 *
		 * @param $excerpt the string we will generate the excerpt from (the real $item->excerpt or the $item->content) before stretching and parsing the inner shortcodes
		 *
		 * @return $excerpt
		 *
		 * @since 1.5.1
		 */
		$excerpt = apply_filters( 'wpv_filter_post_excerpt', $excerpt );

		if ( strpos( $excerpt, '[wpv-post-excerpt' ) !== false ) {
			$debug .= ' Infinite loop prevented.';
		}

		// evaluate shortcodes before truncating tags
		$excerpt = wpv_do_shortcode( $excerpt, true );
		if ( $count == 'word' ) {
			$excerpt = wp_trim_words( $excerpt, $excerpt_length, $excerpt_more );
		} else {
			$excerpt = wp_html_excerpt( $excerpt, $excerpt_length, $excerpt_more );
		}

		$wpautop_was_removed = $WPV_templates->is_wpautop_removed();
		if (
			$wpautop_was_removed
			&& $format == 'autop'
		) {
			$WPV_templates->restore_wpautop( '' );
		} else if ( $format == 'noautop' ) {
			$WPV_templates->remove_wpautop();
		}

		// Remove the Content template excerpt filter. We don't want it applied to this shortcode
		remove_filter( 'the_excerpt', array( $WPV_templates, 'the_excerpt_for_archives' ), 1, 1 );

		/**
		 * Action to be triggered before the "the_excerpt" filter is applied to the output of the "wpv-post-excerpt" output.
		 *
		 * @param string $excerpt
		 */
		do_action( 'wpv_post_excerpt_shortcode_before_applying_the_excerpt_filter', $excerpt );

		$out .= apply_filters( 'the_excerpt', $excerpt );

		/**
		 * Action to be triggered after the "the_excerpt" filter is applied to the output of the "wpv-post-excerpt" output.
		 *
		 * @param string $excerpt
		 */
		do_action( 'wpv_post_excerpt_shortcode_after_applying_the_excerpt_filter', $excerpt );

		// restore filter
		add_filter( 'the_excerpt', array( $WPV_templates, 'the_excerpt_for_archives' ), 1, 1 );

		if (
			$wpautop_was_removed
			&& $format == 'autop'
		) {
			$WPV_templates->remove_wpautop();
			$debug .= ' Show RAW data.';
		} else if (
			! $wpautop_was_removed &&
			$format === 'noautop'
		) {
			// In views-1682, we dealt with a problem where an empty paragraph appeared somewhere below where the excerpt
			// was printed. The reason for that was that an excerpt with "noautop" format, was restoring the "autop" even
			// if it wasn't needed.
			// Here we adjusted the if clause to rule out such case.
			$WPV_templates->restore_wpautop( '' );
		}

		return array( 'out' => $out, 'debug' => $debug );
	}

	/**
	 * Prepare the raw post excerpt.
	 *
	 * @param object    $item Current post instance
	 *
	 * @return string   The raw post excerpt
	 *
	 * @since 2.3.0
	 */
	private function get_raw_excerpt( $item ) {
		global $WPV_templates;

		$debug = $out = '';

		$excerpt = $item->post_excerpt;

		$excerpt = str_replace( ']]>', ']]&gt;', $excerpt );

		/**
		 * Filter wpv_filter_post_excerpt
		 *
		 * This filter lets you modify the string that will generate the excerpt before it's passed through wpv_do_shortcode() and before the length attribute is applied
		 * This way you can parse and delete specific shortcodes from the excerpt, like the [caption] one
		 *
		 * @param $excerpt the string we will generate the excerpt from (the real $item->excerpt or the $item->content) before stretching and parsing the inner shortcodes
		 *
		 * @return $excerpt
		 *
		 * @since 1.5.1
		 */
		$excerpt = apply_filters( 'wpv_filter_post_excerpt', $excerpt );

		if ( strpos( $excerpt, '[wpv-post-excerpt' ) !== false ) {
			$debug .= ' Infinite loop prevented.';
		}

		// evaluate shortcodes before truncating tags
		$excerpt = wpv_do_shortcode( $excerpt );

		// Remove the Content template excerpt filter. We don't want it applied to this shortcode
		remove_filter( 'the_excerpt', array( $WPV_templates, 'the_excerpt_for_archives' ), 1, 1 );

		$out = apply_filters( 'the_excerpt', $excerpt );

		// restore filter
		add_filter( 'the_excerpt', array( $WPV_templates, 'the_excerpt_for_archives' ), 1, 1 );

		return array( 'out' => $out, 'debug' => $debug );
	}
}
