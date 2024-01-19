<?php

/**
 * Class CRED_Shortcode_Form_Submit
 *
 * @since m2m
 */
class CRED_Shortcode_Form_Submit extends CRED_Shortcode_Element_Base implements CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-form-submit';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'type'  => 'input',
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
	 * Fill default attributes that can not be set on a private property.
	 *
	 * @since m2m
	 */
	private function fill_variable_defaults() {
		$this->shortcode_atts['label'] = __( 'Submit', 'wp-cred' );
	}

	/**
	 * Craft the label for the item, based on an optional shortcode attribute or the shortcode content.
	 *
	 * @since 2.5.7
	 */
	private function craft_label() {
		if ( null !== $this->user_content ) {
			return;
		}

		$this->user_content = $this->user_atts['label'];
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
		$this->user_content = apply_filters( 'cred_translate_content', $this->user_atts['label'], 'submit' );

		$this->craft_label();

		$this->classnames = empty( $this->user_atts['class'] )
			? array()
			: explode( ' ', $this->user_atts['class'] );

		$this->classnames[] = 'btn';

		$this->attributes = array(
			'type'		=> 'submit',
			'class'		=> $this->classnames,
			'style'		=> $this->user_atts['style']
		);

		$out = '';

		switch ( $this->user_atts['type'] ) {
			case 'button':
				$out .= '<button';
				foreach ( $this->attributes as $att_key => $att_value ) {
					if (
						in_array( $att_key, array( 'style', 'class' ) )
						&& empty( $att_value )
					) {
						continue;
					}
					$out .= ' ' . $att_key . '="';
					if ( is_array( $att_value ) ) {
						$att_value = array_unique( $att_value );
						$att_real_value = implode( ' ', $att_value );
						$out .= esc_attr( $att_real_value );
					} else {
						$out .= esc_attr( $att_value );
					}
					$out .= '"';
				}
				$out .= '>';
				$out .= $this->user_content;
				$out .= '</button>';

				break;
			case 'input':
			default:
				$this->attributes['name'] = 'cred-form-submit';
				$this->attributes['value'] = $this->user_content;
				$out .= '<input';
				foreach ( $this->attributes as $att_key => $att_value ) {
					if (
						in_array( $att_key, array( 'style', 'class' ) )
						&& empty( $att_value )
					) {
						continue;
					}
					$out .= ' ' . $att_key . '="';
					if ( is_array( $att_value ) ) {
						$att_value = array_unique( $att_value );
						$att_real_value = implode( ' ', $att_value );
						$out .= esc_attr( $att_real_value );
					} else {
						$out .= esc_attr( $att_value );
					}
					$out .= '"';
				}
				$out .= ' />';
				break;
		}

		return $out;
	}

}
