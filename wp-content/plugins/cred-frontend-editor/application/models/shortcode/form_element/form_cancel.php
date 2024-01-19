<?php

/**
 * Class CRED_Shortcode_Form_Submit
 *
 * @since m2m
 */
class CRED_Shortcode_Form_Cancel extends CRED_Shortcode_Element_Base implements CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-form-cancel';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'type'        => 'button',
		'class'       => '',
		'style'       => '',
		'action'      => '',
		'select_page' => '',
		'select_ct'   => '',
		'message'     => '',
	);

	/**
	 * @var string|null
	 */
	private $user_content;

	/**
	 * @var array
	 */
	private $user_atts;

	/**
	 * @var array
	 */
	private $classnames;

	/**
	 * @var array
	 */
	private $attributes;

	/**
	 * Fill default attributes that can not be set on a private property.
	 *
	 * @since m2m
	 */
	private function fill_variable_defaults() {
		$this->shortcode_atts['label'] = __( 'Cancel', 'wp-cred' );
	}

	/**
	 * Get the shortcode output value.
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	public function get_value( $atts, $content = null ) {
		$this->fill_variable_defaults();
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		$this->classnames = empty( $this->user_atts['class'] )
			? array()
			: explode( ' ', $this->user_atts['class'] );

		// set redirect confirmation message
		$redirect_msg = $this->prepare_confirmation_message();

		$this->attributes = array(
			'class' => $this->classnames,
			'style' => $this->user_atts['style'],
			'href'  => $this->generate_button_link()
		);

		$out = '';


		$out .= '<a';
		$out .= " onClick='return confirm(\"" . $redirect_msg . "\")'";
		foreach ( $this->attributes as $att_key => $att_value ) {
			if (
				in_array( $att_key, array( 'style', 'class' ) )
				&& empty( $att_value )
			) {
				continue;
			}
			$out .= ' ' . $att_key . '="';
			if ( is_array( $att_value ) ) {
				$att_value      = array_unique( $att_value );
				$att_real_value = implode( ' ', $att_value );
				$out            .= esc_attr( $att_real_value );
			} else {
				$out .= esc_attr( $att_value );
			}
			$out .= '"';
		}
		$out .= '>';
		$out .= $this->user_atts['label'];
		$out .= '</a>';

		return $out;
	}

	/**
	 * Generate confirmation message for redirect
	 *
	 * @return string
	 */
	private function prepare_confirmation_message( ){
		$current_form_id = $this->get_frontend_form_flow()->get_current_form_id();

		if( isset( $this->user_atts['message'] ) && $this->user_atts['message'] !== '' ){
			$redirect_msg = $this->user_atts['message'];
			$redirect_msg = apply_filters( 'cred_translate_action_message', $redirect_msg, 'message-cancel', $current_form_id );

		} else {
			// default msg
			$redirect_msg = __( 'You will be redirected, do you want to proceed?', 'wp-cred' );
		}

		return $redirect_msg;
	}

	/**
	 * Generate redirection page URL
	 *
	 * @return string
	 */
	private function generate_button_link() {

		// set default
		global $post;
		// When displaying a Form as a preview layer in the Gutenberg editor, `$post` is not defined, so link doesn't really matter.
		if ( ! $post && is_admin() ) {
			return '';
		}
		$button_link = get_permalink( $post->ID );

		if( isset( $this->user_atts['select_page'] ) && $this->user_atts['select_page'] !== '' ){
			$url_from_id = get_permalink( $this->user_atts['select_page'] );
			if( $url_from_id ){
				$button_link = $url_from_id;
			}
		}

		if( isset( $this->user_atts['select_ct'] ) && $this->user_atts['select_ct'] !== '' ){
			$button_link = add_query_arg( array(
				'view-template' => $this->user_atts['select_ct']
			), $button_link );
		}

		return $button_link;
	}
}
