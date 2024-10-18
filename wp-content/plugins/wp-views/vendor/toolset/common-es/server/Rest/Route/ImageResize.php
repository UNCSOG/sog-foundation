<?php

namespace ToolsetCommonEs\Rest\Route;

use ToolsetCommonEs\Library\WordPress\Image;
use ToolsetCommonEs\Library\WordPress\User;
use ToolsetCommonEs\Utils\ImageResize as ImageResizeUtil;

class ImageResize extends ARoute {
	protected $name = 'ImageResize';
	protected $version = 1;

	/** @var Image */
	protected $wp_image;

	/** @var ImageResizeUtil */
	protected $image_util;

	/**
	 * Settings constructor.
	 *
	 * @param User $wp_user
	 * @param Image $wp_image
	 */
	public function __construct( User $wp_user, Image $wp_image, ImageResizeUtil $image_util ) {
		parent::__construct( $wp_user );

		$this->wp_image = $wp_image;
		$this->image_util = $image_util;
	}

	public function callback( \WP_REST_Request $rest_request ) {
		$params = $rest_request->get_json_params();

		if( ! is_array( $params ) ||
			! isset( $params['original'] ) ||
			( ! isset( $params['width'] ) && ! isset( $params['height'] ) ) ||
			! isset( $params['crop'] ) ||
			( $params['crop'] && ( ! isset( $params['width'] ) || ! isset( $params['height'] ) ) ) // Crop requires width and height from the user.
		) {
			return array( 'error' => __( 'Missing information for resizing the image.', 'wpv-views' ) );
		}

		// Default width, for the case proportinal resizing is selected and the user only set height.
		$width = isset( $params['width'] ) ? $params['width'] : 'undefined';
		// Default height, for the case proportinal resizing is selected and the user only set wdith.
		$height = isset( $params['height'] ) ? $params['height'] : 'undefined';

		// Get resized url. getResizedImageUrlByOriginalUrl() will create the resized image if it does not exist.
		$image_resized = $this->image_util->get_resized_image_by_original_url(
			$params['original'],
			$width,
			$height,
			$params['crop']
		);

		if( $image_resized instanceof \WP_Error ) {
			// For some reason the image could not be resized.
			switch( $image_resized->get_error_code() ) {
				case 'error_loading_image':
					$error = __( 'Orignal image could not be found.', 'wpv-views');
					break;
				default:
					$error = $image_resized->get_error_message();
			}

			return array( 'error' => $error );
		}

		if( ! $image_resized ) {
			// For some reason the image could not be resized.
			return array( 'error' => 'Something went wrong on resizing the image.' );
		}

		/**
		 * Modify width parameter to match user values. Without user width the width key needs to be erased.
		 * The resized image width will be stored inside the resized_width parameter.
		 */
		if( isset( $image_resized['width'] ) ) {
			$image_resized['resized_width'] = $image_resized['width'];

			if( $width === 'undefined' ) {
				unset( $image_resized['width'] );
			}
		}

		/**
		 * Modify height parameter to match user values. Without user height the height key needs to be erased.
		 * The resized image height will be stored inside the resized_height parameter.
		 */
		if( isset( $image_resized['height'] ) ) {
			$image_resized['resized_width'] = $image_resized['height'];

			if( $height === 'undefined' ) {
				unset( $image_resized['height'] );
			}
		}

		return $image_resized;
	}

	public function permission_callback() {
		// @todo check for Toolset Access permissions
		return $this->wp_user->current_user_can( 'edit_posts' );
	}
}
