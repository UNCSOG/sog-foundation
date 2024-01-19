<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Delete;

/**
 * Class Association delete shortcode class.
 *
 * @since m2m
 */
class Association
	extends Base
	implements \CRED_Shortcode_Interface, \CRED_Shortcode_Interface_Conditional {

	const SHORTCODE_NAME = 'cred-delete-relationship';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'type'         => 'link',
		'relationship' => '',
		'role_items'   => '',
		'related_item_one' => '',
		'related_item_two' => '',
		'redirect' => 'none',
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
	 * @return bool
	 *
	 * @since m2m
	 */
	public function condition_is_met() {
		return apply_filters( 'toolset_is_m2m_enabled', false );
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
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( empty( $this->user_atts['relationship'] ) ) {
			return;
		}

		if (
			empty( $this->user_atts['role_items'] )
			&& (
				empty( $this->user_atts['related_item_one'] )
				|| empty( $this->user_atts['related_item_two'] )
			)
		) {
			return;
		}

		if ( '$fromViews' == $this->user_atts['role_items'] ) {
			$this->user_atts['related_item_one'] = '$fromfilter';
			$this->user_atts['related_item_two'] = '$current';
		}

		$related_item_one = $this->get_role_id( $this->user_atts['related_item_one'] );
		$related_item_two = $this->get_role_id( $this->user_atts['related_item_two'] );

		$is_some_item_empty = empty( $related_item_one )
			|| empty( $related_item_two );

		$is_current_user_restricted = ! current_user_can( 'edit_post', $related_item_one )
			|| ! current_user_can( 'edit_post', $related_item_two );

		if (
			$is_some_item_empty
			|| $is_current_user_restricted
		) {
			return;
		}

		$this->classnames = empty( $this->user_atts['class'] )
			? array()
			: explode( ' ', $this->user_atts['class'] );

		$this->classnames[] = 'js-cred-delete-relationship';

		$this->attributes = array(
			'class' => $this->classnames,
			'style' => $this->user_atts['style'],
			'data-relationship' => $this->user_atts['relationship'],
			'data-relateditemone' => $related_item_one,
			'data-relateditemtwo' => $related_item_two,
			'data-redirect' => $this->user_atts['redirect']
		);

		$out = '';

		// TODO isolate the rendering method, try to use toolset_form_control and check the output.
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
			case 'link':
			default:
				$this->classnames[] = 'btn';
				$out .= '<a';
				$out .= ' href="#"';
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
				$out .= '</a>';

				break;
				break;
		}

		return $out;
	}
	private function get_role_id( $attribute_value = '' ) {
		$result_value = $attribute_value;
		switch ( $attribute_value ) {
			case '$current':
			case '$fromfilter':
				$result_value = $this->item->get( array( 'item' => $attribute_value ) );
				break;
			default:
				global $post;
				$result_value = $this->item->get( array( 'item' => $attribute_value ) );
				if (
					$result_value != $attribute_value
					&& $result_value == $post->ID
				) {
					$result_value = '';
				}
				break;
		}
		return $result_value;
	}

}
