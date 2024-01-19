<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Delete;

/**
 * Class post delete shortcode class.
 *
 * @since m2m
 */
class Post
	extends Base
	implements \CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-delete-post';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'type' => 'link',
		'onsuccess' => '',
		'action' => 'trash',
		'class' => '',
		'style' => '',
		'item' => '',
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
	 * @var \WP_Post
	 */
	private $post_to_delete;

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	* @return string
	* @since 2.6
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		do_action( 'toolset_enqueue_scripts', array( \CRED_Asset_Manager::SCRIPT_FRONTEND ) );

		if ( empty( $this->user_content ) ) {
			// There is no text to use as to craft a link or button
			return;
		}

		if ( false === $this->set_post_to_delete() ) {
			return;
		}

		if ( false === $this->current_user_can() ) {
			return;
		}

		$this->adjust_action_per_post_kind();

		$this->craft_label();
		$this->craft_attributes();

		switch ( $this->user_atts['type'] ) {
			case 'button':
				return $this->get_button();
			case 'link':
			default:
				return $this->get_link();
		}

		return;
	}

	private function set_post_to_delete() {
		if ( ! $item_id = $this->item->get( $this->user_atts ) ) {
			// no valid item
			return false;
		}

		// WPML support
		$this->user_atts['item'] = apply_filters( 'wpml_object_id', $item_id, get_post_type( $item_id ), true );

		$post_to_delete = get_post( $this->user_atts['item'] );
		if ( null === $post_to_delete ) {
			return false;
		}

		$this->post_to_delete = $post_to_delete;
		return $this->post_to_delete;
	}

	private function current_user_can() {
		global $current_user;

		if ( $current_user->ID == $this->post_to_delete->post_author ) {
			return current_user_can( 'delete_own_posts_with_cred' );
		}

		return current_user_can( 'delete_other_posts_with_cred' );
	}

	/**
	 * Adjusts the action to perform depending on the affected post type.
	 *
	 * If the post to delete is a RFG, then it must be hard deleted, not trashed.
	 */
	private function adjust_action_per_post_kind() {
		if ( 'delete' === $this->user_atts['action'] ) {
			return;
		}

		$post_type_object = get_post_type_object( $this->post_to_delete->post_type );

		if (
			property_exists( $post_type_object, \Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP )
			&& $post_type_object->{\Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP}
		) {
			$this->user_atts['action'] = 'delete';
		}

	}

	private function craft_label() {
		$this->user_content = $this->replace_placeholders( $this->user_content );
	}

	private function replace_placeholders( $text ) {
		// Only operate on non empty strings
		if ( empty( $text ) ) {
			return $text;
		}

		// Replace placeholders
		$text = str_replace(
			array(
				'%TITLE%',
				'%ID%'
			),
			array(
				get_the_title( $this->user_atts['item'] ),
				$this->user_atts['item'],
			),
			$text
		);

		return $text;
	}

	private function craft_attributes() {
		$classnames = empty( $this->user_atts['class'] )
			? array()
			: explode( ' ', $this->user_atts['class'] );

		$classnames[] = 'js-cred-delete-post';

		$this->attributes = array(
			'class' => $classnames,
			'style' => $this->user_atts['style'],
			'data-postid' => $this->user_atts['item'],
			'data-action' => $this->user_atts['action'],
			'data-onsuccess' => $this->user_atts['onsuccess'],
		);
	}

	private function get_attributes() {
		$out = '';
		foreach ( $this->attributes as $att_key => $att_value ) {
			if (
				in_array( $att_key, array( 'style', 'class' ) )
				&& empty( $att_value )
			) {
				continue;
			}
			$out .= ' ' . esc_attr( $att_key ) . '="';
			if ( is_array( $att_value ) ) {
				$att_value = array_unique( $att_value );
				$att_real_value = implode( ' ', $att_value );
				$out .= esc_attr( $att_real_value );
			} else {
				$out .= esc_attr( $att_value );
			}
			$out .= '"';
		}
		return $out;
	}

	private function get_button() {
		$out = '<button';
		$out .= $this->get_attributes();
		$out .= '>';
		$out .= $this->user_content;
		$out .= '</button>';

		return $out;
	}

	private function get_link() {
		$out = '<a href="#"';
		$out .= $this->get_attributes();
		$out .= '>';
		$out .= $this->user_content;
		$out .= '</a>';

		return $out;
	}

}
