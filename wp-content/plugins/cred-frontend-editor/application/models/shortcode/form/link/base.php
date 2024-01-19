<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Form\Link;

/**
 * Base class for form edit links.
 *
 * @since 2.1
 */
class Base {

	/**
	 * @var \Toolset_Shortcode_Attr_Interface
	 */
	protected $item;

	/**
	 * @var string|null
	 */
	protected $user_content;

	/**
	 * @var array
	 */
	protected $user_atts;

	/**
	 * @var array
	 */
	protected $classnames;

	/**
	 * @var integer
	 */
	protected $current_content_template = 0;

	/**
	 * @var array
	 */
	protected $content_templates_to_forms = array();

	/**
	 * @var array
	 */
	protected $layouts_to_forms = array();

	/**
	 * @var array
	 */
	protected $attributes;

	/**
	 * @param \Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct( \Toolset_Shortcode_Attr_Interface $item ) {
		$this->item = $item;
	}

	/**
	 * Get the form ID for a form cell in a layout.
	 *
	 * @param int    $layout_id
	 * @param string $form_type
	 *
	 * @return int
	 *
	 * @uses $this->layouts_to_forms
	 *
	 * @since 2.1
	 */
	protected function get_form_in_layout( $layout_id, $form_type = 'post' ) {

		$form_id = 0;

		if ( ! in_array( $form_type, array( 'post', 'user' ) ) ) {
			return $form_id;
		}

		if ( isset( $this->layouts_to_forms[ $layout_id ] ) ) {
			return $this->layouts_to_forms[ $layout_id ];
		}

		$cell_type = ( 'user' == $form_type ) ? 'cred-user-cell' : 'cred-cell';
		$cell_content_property_key = ( 'user' == $form_type ) ? 'ddl_layout_cred_user_id' : 'ddl_layout_cred_id';

		$cells_in_layout = apply_filters( 'ddl-filter_get_layout_cells_by_type', array(), $layout_id, $cell_type );

		if ( 0 == count( $cells_in_layout ) ) {
			return $form_id;
		}

		$cred_cell = array_shift( $cells_in_layout );

		$form_id = apply_filters( 'ddl-filter_get_cell_content_property', $form_id, $cred_cell, $cell_content_property_key );

		return $form_id;

	}

	/**
	 * Get the form ID for a form shortcode inside a Content Template body.
	 *
	 * @param int    $ct_id
	 * @param string $form_type
	 *
	 * @return int
	 *
	 * @uses $this->content_templates_to_forms
	 *
	 * @since 2.1
	 */
	protected function get_form_in_content_template( $ct_id, $form_type = 'post' ) {

		$form_id = 0;

		if ( ! in_array( $form_type, array( 'post', 'user' ) ) ) {
			return $form_id;
		}

		if ( isset( $this->content_templates_to_forms[ $ct_id ] ) ) {
			return $this->content_templates_to_forms[ $ct_id ];
		}

		$ct_content = get_post_field( 'post_content', $ct_id );

		if (
			'post' == $form_type
			&& strpos( $ct_content, '[cred_form ' ) === false
			&& strpos( $ct_content, '[cred-form ' ) === false
			&& strpos( $ct_content, '{!{cred_form ' ) === false
			&& strpos( $ct_content, '{!{cred-form ' ) === false
		) {
			return $form_id;
		}

		if (
			'user' == $form_type
			&& strpos( $ct_content, '[cred_user_form ' ) === false
			&& strpos( $ct_content, '[cred-user-form ' ) === false
			&& strpos( $ct_content, '{!{cred_user_form ' ) === false
			&& strpos( $ct_content, '{!{cred-user-form ' ) === false
		) {
			return $form_id;
		}

		// Make sure we can parse shortcodes in alternative syntax
		$ct_content = apply_filters( 'toolset_transform_shortcode_format', $ct_content );

		$this->current_content_template = $ct_id;

		global $shortcode_tags;
		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		if ( 'post' == $form_type ) {
			add_shortcode( 'cred_form', array( $this, 'fake_cred_post_form_shortcode_to_get_form' ) );
			add_shortcode( 'cred-form', array( $this, 'fake_cred_post_form_shortcode_to_get_form' ) );
		} else {
			add_shortcode( 'cred_user_form', array( $this, 'fake_cred_user_form_shortcode_to_get_form' ) );
			add_shortcode( 'cred-user-form', array( $this, 'fake_cred_user_form_shortcode_to_get_form' ) );
		}

		do_shortcode( $ct_content );

		$shortcode_tags = $orig_shortcode_tags;

		$this->current_content_template = 0;

		if ( isset( $this->content_templates_to_forms[ $ct_id ] ) ) {
			return $this->content_templates_to_forms[ $ct_id ];
		}

		return $form_id;

	}

	/**
	 * Fake a cred_form shortcode to get the form ID.
	 *
	 * @since 2.1
	 */
	public function fake_cred_post_form_shortcode_to_get_form( $atts ) {
		$this->fake_cred_form_shortcode_to_get_form( $atts, CRED_FORMS_CUSTOM_POST_NAME );
		return;
	}

	/**
	 * Fake a cred_user_form shortcode to get the form ID.
	 *
	 * @since 2.1
	 */
	public function fake_cred_user_form_shortcode_to_get_form( $atts ) {
		$this->fake_cred_form_shortcode_to_get_form( $atts, CRED_USER_FORMS_CUSTOM_POST_NAME );
		return;
	}

	/**
	 * Cache the form ID from a cred_form or cred_user_form shortcode attributes.
	 *
	 * @uses $this->current_content_template
	 * @uses $this->content_templates_to_forms
	 *
	 * @since 2.1
	 */
	protected function fake_cred_form_shortcode_to_get_form( $atts, $form_post_type ) {
		$atts = shortcode_atts(
			array(
				'form' => ''
			),
			$atts
		);

		$form = $atts['form'];
		$current_content_template = $this->current_content_template;

		if ( empty( $form ) ) {
			return;
		}

		if (
			is_string( $form )
			&& ! is_numeric( $form )
		) {
			$result = get_page_by_path( wp_specialchars_decode( $form ), OBJECT, $form_post_type );
			if (
				$result
				&& is_object( $result )
				&& isset( $result->ID )
			) {
				$this->content_templates_to_forms[ $current_content_template ] = $result->ID;
				return;
			} else {
				$forms = get_posts(
					array(
						'post_type'              => $form_post_type,
						'title'                  => wp_specialchars_decode( $form ),
						'post_status'            => 'all',
						'numberposts'            => 1,
						'update_post_term_cache' => false,
						'update_post_meta_cache' => false,
						'orderby'                => 'post_date ID',
						'order'                  => 'ASC',
					)
				);
				if ( ! empty( $forms ) ) {
					$result = $forms[0];
					$this->content_templates_to_forms[ $current_content_template ] = $result->ID;
					return;
				}
			}
		} else {
			if ( is_numeric( $form ) ) {
				$result = get_post( $form );
				if (
					$result
					&& is_object( $result )
					&& isset( $result->ID )
				) {
					$this->content_templates_to_forms[ $current_content_template ] = $result->ID;
					return;
				}
			}
		}

		return;
	}

	/**
	 * Compose the final link HTML tag based on the given attributes.
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	protected function craft_link_output() {
		$output = '<a';
		foreach ( $this->attributes as $att_key => $att_value ) {
			if (
				in_array( $att_key, array( 'style', 'class', 'target' ) )
				&& empty( $att_value )
			) {
				continue;
			}
			$output .= ' ' . $att_key . '="';
			if ( is_array( $att_value ) ) {
				$att_value = array_unique( $att_value );
				$att_real_value = implode( ' ', $att_value );
				$output .= esc_attr( $att_real_value );
			} else {
				$output .= esc_attr( $att_value );
			}
			$output .= '"';
		}
		$output .= '>';

		$output .= $this->user_content;

		$output .= '</a>';

		return $output;
	}

	/**
	 * Translate a link anchor string with WPML ST.
	 *
	 * @param string $name
	 * @param string $string
	 * @param boolean $register
	 * @param string $context
	 *
	 * @return string
	 *
	 * @since 2.1
	 */
	protected function translate_link( $name, $string, $register = false, $context = 'Toolset Shortcodes' ) {
		if ( $register ) {
			do_action( 'wpml_register_single_string', $context, $name, $string );
		}

		return apply_filters( 'wpml_translate_single_string', stripslashes( $string ), $context, $name );
	}

}
