<?php

namespace ToolsetCommonEs\Rest\Route;

use OTGS\Toolset\Common\Utils\Attachments;
use ToolsetCommonEs\Library\WordPress\Image;
use ToolsetCommonEs\Library\WordPress\User;

/**
 * Handles all the media object related interactions for the relevant REST API endpoint.
 */
class MediaObject extends ARoute {
	/** @var string */
	protected $name = 'MediaObject';

	/** @var int */
	protected $version = 1;

	/** @var Attachments */
	private $toolset_attachments;

	/** @var Image */
	private $wp_image;

	/** @var int */
	private $max_width = 0;

	/**
	 * MediaObject constructor.
	 *
	 * @param User $wp_user
	 * @param Image $wp_image
	 * @param Attachments $toolset_attachments
	 */
	public function __construct( User $wp_user, Image $wp_image, Attachments $toolset_attachments ) {
		parent::__construct( $wp_user );

		$this->wp_image = $wp_image;
		$this->toolset_attachments = $toolset_attachments;
	}

	/**
	 * By default a query for multiple images is expected (array of urls). Reason for this default is just backward
	 * compatibility. It also provides another route 'getByIdOrUrl', which can be used to request media data
	 * for one image by passing an array with the keys 'id' and 'url'. The url will only be used if the id does
	 * not deliver data.
	 *
	 * @param \WP_REST_Request $rest_request
	 *
	 * @return array
	 */
	public function callback( \WP_REST_Request $rest_request ) {
		$params = $rest_request->get_json_params();

		$route = isset( $params['route'] ) ? $params['route'] : false;

		switch ( $route ) {
			case 'getByIdOrUrl':
				return $this->get_media_object_by_id_or_url( $params );
			default:
				return $this->get_multiple_media_objects_by_urls( $params );
		}
	}


	/**
	 * The permission callback for the REST API endpoint. Returns always true, as everyone should be allowed to get media data.
	 *
	 * @return bool
	 */
	public function permission_callback() {
		// Everyone is allowed to get the media data.
		return true;
	}

	/**
	 * Get media object by id and as fallback by url.
	 *
	 * @param mixed[] $params
	 *
	 * @return array
	 */
	private function get_media_object_by_id_or_url( $params ) {
		$result = [];

		if ( ! isset( $params['id'] ) || ! isset( $params['url'] ) ) {
			return $result;
		}

		$url_without_size = preg_replace( '/(\-[0-9]{1,10}x[0-9]{1,10})([^\s]*)/', '$2', $params['url'] );

		// Try to get by id.
		$attachment = $this->wp_image->wp_prepare_attachment_for_js( $params['id'] );

		if ( $attachment && $url_without_size === $params['url'] ) {
			return $this->apply_media_details( $attachment );
		}

		// No attachment or attachment is does not match with url This can happen after import and the old attachments
		// id is now used by a new attachment - than we have attachment data, but the wrong one.
		if ( $id = $this->toolset_attachments->get_attachment_id_by_url( $url_without_size ) ) {
			// Found ID by the url. Fetch attachment again with that id.
			if ( $attachment = $this->wp_image->wp_prepare_attachment_for_js( $id ) ) {
				return $this->apply_media_details( $attachment );
			}
		}

		// No attachment data found by ID or URL.
		return [];
	}

	/**
	 * Get media objects for multiple images by url.
	 *
	 * @param mixed[] $params
	 *
	 * @return array
	 */
	private function get_multiple_media_objects_by_urls( $params ) {
		$result = [];

		if ( ! is_array( $params ) ) {
			return $result;
		}

		foreach ( $params as $url => $param ) {
			$result[ $url ] = $this->get_media_object_by_url( $param['url'] );
		}

		return $result;
	}

	/**
	 * Get media by url.
	 *
	 * @param string $url
	 *
	 * @return array
	 */
	private function get_media_object_by_url( $url ) {
		$attachment_id = $this->wp_image->attachment_url_to_postid( $url );

		if ( ! $attachment_id ) {
			global $wpdb;
			$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid=%s", $url ) );
		}

		if ( $attachment_id ) {
			return $this->apply_media_details( $this->wp_image->wp_prepare_attachment_for_js( $attachment_id ) );
		}

		// In case media object information are requested for a gravatar image, the only available information a gravatar url
		// offers is the size of the image. Gravatar images are square so the width and the height of the image are the same.
		if ( false !== strpos( $url, 'gravatar.com' ) ) {
			// Extracting the size parameter from the URL.
			preg_match( '/(&|\?)s=([0-9]+)/i', $url, $matches );
			if (
				isset( $matches[2] ) &&
				is_numeric( $matches[2] ) ) {
				return [
					'id' => 'gravatar',
					'url' => $url,
					'source_url' => $url,
					'width' => intval( $matches[2] ),
					'height' => intval( $matches[2] ),
				];
			}
		}

		return array();
	}

	/**
	 * The function wp_prepare_attachment_for_js() does not deliver all necessary data. The missing data about
	 * media sizes are applied here. This basically mimics what the Gutenberg getMedia() returns.
	 *
	 * @param mixed[] $attachment
	 *
	 * @return array
	 */
	private function apply_media_details( $attachment ) {
		if ( ! is_array( $attachment ) || ! $attachment['id'] ) {
			// No attachment or missing id.
			return $attachment;
		}

		// Get media details.
		$attachment['media_details'] = $this->wp_image->wp_get_attachment_metadata( $attachment['id'] );

		if ( ! is_array( $attachment['media_details'] ) || ! isset( $attachment['media_details']['sizes'] ) ) {
			// Failed to retrieve media details.
			return $attachment;
		}

		// Loop over each media size to apply the source_url.
		foreach ( $attachment['media_details']['sizes'] as $size => &$size_data ) {
			if ( isset( $size_data['mime-type'] ) ) {
				$size_data['mime_type'] = $size_data['mime-type'];
				unset( $size_data['mime-type'] );
			}

			// Use the same method image_downsize() does.
			$image_src = $this->wp_image->wp_get_attachment_image_src( $attachment['id'], $size );
			if ( ! $image_src ) {
				continue;
			}

			$size_data['source_url'] = $image_src[0];

			if ( isset( $image_src[1] ) && $this->max_width < $image_src[1] ) {
				$this->max_width = $image_src[1];
				$attachment['source_url'] = $image_src[0];
			}
		}

		// Add "Full" image size.
		$full_src = $this->wp_image->wp_get_attachment_image_src( $attachment['id'], 'full' );

		if ( ! empty( $full_src ) ) {
			$attachment['media_details']['sizes']['full'] = array(
				'file' => $this->wp_image->wp_basename( $full_src[0] ),
				'width' => $full_src[1],
				'height' => $full_src[2],
				'source_url' => $full_src[0],
			);

			$attachment['source_url'] = $full_src[0];
		}

		return $attachment;
	}
}
