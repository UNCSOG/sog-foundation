<?php

/**
 * Class WPV_Shortcode_Post_Featured_Image
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Featured_Image extends WPV_Shortcode_Base {

	const SHORTCODE_NAME = 'wpv-post-featured-image';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'item'         => null, // post
		'id'           => null, // synonym for 'item'
		'post_id'      => null, // synonym for 'item'
		'size'         => 'thumbnail',
		'output'       => '',
		'raw'          => 'false',// DEPRECATED
		'data'         => '',// DEPRECATED
		'attr'         => '',
		'class'        => '',
		'width'        => '',
		'height'       => '',
		'crop'         => false,
		'crop_horizontal' => 'center',
		'crop_vertical'   => 'center'
	);

	private $post_attributes_info = array(
		'id'          => 'ID',
		'author'      => 'post_author',
		'date'        => 'post_date',
		'description' => 'post_content',
		'title'       => 'post_title',
		'caption'     => 'post_excerpt',
		'original'    => 'guid'
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
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	private $item;

	/**
	 * @var \OTGS\Toolset\Views\Model\Wordpress\Media
	 */
	private $media;

	/**
	 * WPV_Shortcode_Post_Featured_Image constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $item
	 */

	public function __construct(
		Toolset_Shortcode_Attr_Interface $item,
		\OTGS\Toolset\Views\Model\Wordpress\Media $media = null
	) {
		$this->item = $item;
		$this->media = ( null === $media )
			? new \OTGS\Toolset\Views\Model\Wordpress\Media()
			: $media;
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

		// LEGACY - backwards compatibility
		$this->adjust_legacy_attributes();
		// END LEGACY - backwards compatibility

		$out = '';

		$item = $this->get_post( $item_id );

		if ( null === $item ) {
			return $out;
		}

		if ( 'img' == $this->user_atts['output'] ) {
			$out = $this->get_featured_image( $item->ID );
		} else {
			$out = $this->get_featured_image_data( $item->ID );
		}
		$out = apply_filters( 'wpv-post-featured-image', $out );

		apply_filters( 'wpv_shortcode_debug', 'wpv-post-featured-image', json_encode( $this->user_atts ), '', 'Data received from cache', $out );

		return $out;
	}

	private function adjust_legacy_attributes() {
		if ( empty( $this->user_atts['output'] ) ) {
			if (
				$this->user_atts['raw'] === 'true'
				|| ! empty( $this->user_atts['data'] )
			) {
				if ( empty( $this->user_atts['data'] ) ) {
					$this->user_atts['output'] = 'url';
				} else {
					$this->user_atts['output'] = $this->user_atts['data'];
				}
			} else {
				$this->user_atts['output'] = 'img';
			}
		}
	}

	private function get_featured_image( $item_id ) {
		$out = '';
		$crop = false;

		$attr_array = $this->get_img_attributes();

		// Custom Image Size
		if ( 'custom' == $this->user_atts['size'] ) {
			$post_thumbnail_id = get_post_thumbnail_id( $item_id );
			$out_array = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );

			if ( $out_array ) {
				if ( $this->user_atts['crop'] ) {
					$crop = array( $this->user_atts['crop_horizontal'], $this->user_atts['crop_vertical'] );
				}

				$image = $this->resize_image( $out_array[0], $this->user_atts['width'], $this->user_atts['height'], $crop );

				if ( ! is_wp_error( $image ) ) {
					$attr_array['src'] = $image;
					$attr_array['width'] = $this->user_atts['width'];
					$attr_array['height'] = $this->user_atts['height'];
					$attr_array['alt'] = trim( strip_tags( get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true ) ) );
					$out = wpv_get_html_tag( 'img', $attr_array, true, '' );
				}
			}
		} else {
			$out = get_the_post_thumbnail( $item_id, $this->user_atts['size'], $attr_array );
		}

		return $out;
	}

	private function get_featured_image_data( $item_id ) {
		$out = '';
		$crop = false;

		$post_thumbnail_id = get_post_thumbnail_id( $item_id );
		if ( ! empty( $post_thumbnail_id ) ) {
			switch ( $this->user_atts['output'] ) {
				case 'id':
				case 'author':
				case 'date':
				case 'description':
				case 'title':
				case 'caption':
				case 'original':
					$new_info = get_post( $post_thumbnail_id );
					$new_value = $this->post_attributes_info[ $this->user_atts['output'] ];
					$out = isset( $new_info->$new_value ) ? $new_info->$new_value : '';
					break;
				case 'alt':
					$out = get_post_meta( $post_thumbnail_id , '_wp_attachment_image_alt', true );
					break;
				case 'url':
				default:
					$out_array = wp_get_attachment_image_src( $post_thumbnail_id, $this->user_atts['size'] );
					$out = $out_array[0];

					if ( 'custom' == $this->user_atts['size'] ) {
						if ( $this->user_atts['crop'] ) {
							$crop = array( $this->user_atts['crop_horizontal'], $this->user_atts['crop_vertical'] );
						}

						$image = $this->resize_image( $out_array[0], $this->user_atts['width'], $this->user_atts['height'], $crop );

						if ( ! is_wp_error( $image ) ) {
							$out = $image;
						}
					}

					$out = set_url_scheme( $out );
				break;
			}
		}
		return $out;
	}

	private function get_img_attributes() {
		$attr_array = array();

		if ( ! empty( $this->user_atts['attr'] ) ) {
			$ampersand_valid = '&';
			$character_valid = array( '&' );
			$query_var_valid = array(
				'&title',
				'&alt',
				'&class'
			);
			$brackets_valid = array( '[', ']' );
			$ampersand_alias = array( '&amp;', '&#038;' );
			$query_var_html_entity = array(
				'&#038;title',
				'&#038;alt',
				'&#038;class'
			);
			$query_var_escaped = array(
				'&amp;title',
				'&amp;alt',
				'&amp;class'
			);
			$brackets_escaped = array( '&#91;', '&#93;' );
			$character_hack_replace = array( '#wpv-amperhack#' );
			$query_var_hack_replace = array(
				'#wpv-title-hack#',
				'#wpv-alt-hack#',
				'#wpv-class-hack#'
			);

			// first, escape and strip tags
			$attr = esc_attr( strip_tags( $this->user_atts['attr'] ) );
			// now, hack the ampersands on legitimate query-like attributes
			$attr = str_replace( $query_var_html_entity, $query_var_hack_replace, $attr );
			$attr = str_replace( $query_var_escaped, $query_var_hack_replace, $attr );
			// adjust the brackets
			$attr = str_replace( $brackets_valid, $brackets_escaped, $attr );
			// hack the remaining ampersands, even the ones coming from HTML characters
			$attr = str_replace( $ampersand_alias, $ampersand_valid, $attr );
			$attr = str_replace( $character_valid, $character_hack_replace, $attr );
			// add nack the legitimate ampersands
			$attr = str_replace( $query_var_hack_replace, $query_var_valid, $attr );
			// parse the attributes
			wp_parse_str( $attr, $attr_array );
			// restore the other ampersands
			$attr_array = str_replace( $character_hack_replace, $character_valid, $attr_array );
		}

		$size_class = $this->user_atts['size'];
		if ( 'custom' === $this->user_atts['size'] ) {
			$size_class = $this->user_atts['width'] . 'x' . $this->user_atts['height'];
		}

		$attr_array['class'] = 'attachment-' . esc_attr( $size_class )
			. ' size-' . esc_attr( $size_class )
			. ( empty( $this->user_atts['class'] )
				? ''
				: ' ' . esc_attr( $this->user_atts['class'] )
			);

		return $attr_array;
	}

	 /**
	 * Apply image resizing, based on width/height and proportional/cropping.
	 * Supports wp_upload_dir() based image locations only.
	 *
	 * @param string $image_url URL of the image
	 * @param int $width Intended maximum width of the image
	 * @param int $height Intended maximum height of the image
	 * @param array|bool $crop Array of crop positions or 'false'
	 * @return string|WP_Error Resized image URL or WP_Error
	 * @since 2.2.0
	 */
	private function resize_image( $image_url, $width, $height, $crop ) {
		return $this->media->resize_image( $image_url, $width, $height, $crop );
	}

}
