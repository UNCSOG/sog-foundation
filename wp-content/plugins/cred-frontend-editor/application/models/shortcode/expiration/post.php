<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Expiration;

use OTGS\Toolset\CRED\Controller\ExpirationManager\Post\Singular;

/**
 * Post expiration date shortcode.
 *
 * @since 2.3
 */
class Post implements \CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-post-expiration';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item' => null, // post
		'id' => null, // synonym for 'item'
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
	 * @var \Toolset_Shortcode_Attr_Interface
	 */
	protected $item;

	/**
	 * @param \Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct( \Toolset_Shortcode_Attr_Interface $item ) {
		$this->item = $item;
		$this->shortcode_atts['format'] = get_option( 'date_format' );
	}

	/**
	 * Gets the post, either the one with ID equal to the ID given as a parameter or its translation.
	 *
	 * @param int $item_id
	 * @return array|null|WP_Post
	 */
	private function get_post( $item_id ) {
		$item = get_post( $item_id );

		if ( null === $item ) {
			return null;
		}

		// Adjust for WPML support
		// If WPML is enabled, $item_id should contain the right ID for the current post in the current language
		// However, if using the id attribute, we might need to adjust it to the translated post for the given ID
		$item_id = (int) apply_filters( 'translate_object_id', $item_id, $item->post_type, true, null );

		if ( $item_id !== $item->ID ) {
			$item = get_post( $item_id );
		}

		return $item;
	}

	/**
	 * Adjust the stored expiration timestamp to match the local timezone, before printing it.
	 *
	 * @param int $timestamp
	 * @return int
	 * @since 2.4
	 */
	private function get_local_expiration_timestamp( $timestamp ) {
		if ( ! \Toolset_Date_Utils::get_instance()->is_timestamp_in_range( $timestamp ) ) {
			$timestamp = \Toolset_Date_Utils::TIMESTAMP_UPPER_BOUNDARY;
		}
		$gmt_timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ), 'Y-m-d H:i:s' );
		$gmt_timestamp_datetime = new \DateTime( $gmt_timestamp );
		$timestamp = $gmt_timestamp_datetime->format( 'U' );

		return $timestamp;
	}

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	* @return string
	* @since 2.3
	*/
	public function get_value( $atts, $content = null ) {
		if ( ! apply_filters( 'toolset_forms_is_post_expiration_enabled', false ) ) {
			return '';
		}

		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( ! $item_id = $this->item->get( $this->user_atts ) ) {
			// no valid item
			throw new CRED_Exception_Invalid_Shortcode_Attr_Item();
		}

		$out = '';

		$item = $this->get_post( $item_id );

		if ( null === $item ) {
			return $out;
		}

		$post_expiration_time = get_post_meta( $item->ID, Singular::POST_META_TIME, true );

		if ( ! empty( $post_expiration_time ) ) {
			$post_expiration_time = $this->get_local_expiration_timestamp( $post_expiration_time );
		}

		if ( \Toolset_Date_Utils::get_instance()->is_timestamp_in_range( $post_expiration_time ) ) {
			$out = apply_filters( 'the_time', adodb_date( $this->user_atts['format'], $post_expiration_time ) );
		}

		return $out;
	}

}
