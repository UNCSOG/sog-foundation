<?php

/**
 * Class CRED_Shortcode_Form_Feedback
 *
 * @since m2m
 */
class CRED_Shortcode_Form_Feedback extends CRED_Shortcode_Element_Base implements CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-form-feedback';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'type'  => 'div',
		'class' => '', // classnames
		'style' => '' // extra inline styles
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
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		$current_form_id = $this->get_frontend_form_flow()->get_current_form_id();
        $form_count = $this->get_frontend_form_flow()->get_current_form_count();

		$this->user_atts['type'] = in_array( $this->user_atts['type'], array( 'div', 'span' ) )
			? $this->user_atts['type']
			: 'div';

		$this->classnames = empty( $this->user_atts['class'] )
			? array()
			: explode( ' ', $this->user_atts['class'] );

		$this->classnames[] = 'cred-form-feedback-wrap';
		$this->classnames[] = 'messages';
		$this->classnames[] = 'cred-message';
		$this->classnames[] = 'alert';

		$this->classnames = apply_filters( 'cred_form_feedback_classnames', $this->classnames, $current_form_id, $form_count );

		$this->attributes = array(
			'id'    => 'cred-form-feedback-' . $current_form_id,
			'class' => $this->classnames,
			'style' => $this->user_atts['style']
		);

		$output = '<' . $this->user_atts['type'];
		foreach ( $this->attributes as $att_key => $att_value ) {
			if (
				in_array( $att_key, array( 'style', 'class' ) )
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

		$output .= apply_filters( 'cred_form_feedback', '', $current_form_id, $form_count );

		$output .= '</' . $this->user_atts['type'] . '>';

		return $output;
	}

}
