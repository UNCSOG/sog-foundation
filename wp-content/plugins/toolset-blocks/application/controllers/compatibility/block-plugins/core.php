<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\BlockPlugin;

use OTGS\Toolset\Common\Utils\Attachments;
use OTGS\Toolset\Views\Controller\Compatibility\Base;

/**
 * Handles the compatibility between Views and Core blocks.
 */
class CoreCompatibility extends Base {
	/** @var \Toolset_Constants */
	private $constants;

	/** @var Attachments */
	private $toolset_attachments;

	/**
	 * CoreCompatibility constructor.
	 *
	 * @param Attachments $toolset_attachments
	 */
	public function __construct( \Toolset_Constants $constants, Attachments $toolset_attachments ) {
		$this->constants = $constants;
		$this->toolset_attachments = $toolset_attachments;
	}

	/**
	 * Initializes the Core blocks integration.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Initializes the hooks for the Core blocks integration.
	 */
	private function init_hooks() {
		add_filter( 'wpv_filter_view_loop_item_output', array( $this, 'adjust_wp_image_class_for_proper_content_tag_filtering' ), 10, 3 );
	}

	/**
	 * It adjust the "wp-image-XXX" usually found on the img tag of the Core Image block, in order to allow proper tag filtering (adding "srcset"s, "loading"s etc.)

	 * @param string $loop_item_output
	 *
	 * @return string
	 */
	public function adjust_wp_image_class_for_proper_content_tag_filtering( $loop_item_output ) {
		if (
			$this->constants->defined( 'REST_REQUEST' ) &&
			$this->constants->constant( 'REST_REQUEST' )
		) {
			return $loop_item_output;
		}

		if ( ! preg_match_all( '/<(img)\s[^>]+>/', $loop_item_output, $matches, PREG_SET_ORDER ) ) {
			return $loop_item_output;
		}

		// List of the unique `img` tags found in $content.
		$images = array();

		foreach ( $matches as $match ) {
			list( $tag ) = $match;

			if ( preg_match( '/wp-image-([0-9]+)/i', $tag, $class_id ) ) {
				$attachment_id = absint( $class_id[1] );

				if ( $attachment_id ) {
					// If exactly the same image tag is used more than once, overwrite it.
					// All identical tags will be replaced later with 'str_replace()'.
					$images[ $tag ] = $attachment_id;
				} else {
					$images[ $tag ] = 0;
				}
			} else {
				$images[ $tag ] = 0;
			}
		}

		// Reduce the array to unique attachment IDs.
		$attachment_ids = array_unique( array_filter( array_values( $images ) ) );

		foreach ( $attachment_ids as $attachment_id ) {
			$pattern = '/<img\s+(?=[^>]*?(?<=\s)class\s*=\s*["\'].*wp-image-' . $attachment_id . '.*["\'])[^>]*?(?<=\ssrc=")([^"]*)/im';
			preg_match_all( $pattern, $loop_item_output, $out, PREG_SET_ORDER );

			foreach ( $out as $image_src ) {
				$maybe_attachment_id = $this->toolset_attachments->get_attachment_id_by_url( $image_src[1] );
				if ( null !== $maybe_attachment_id ) {
					$loop_item_output = str_replace( 'wp-image-' . $attachment_id, 'wp-image-' . $maybe_attachment_id, $loop_item_output );
				}
			}
		}

		return $loop_item_output;
	}
}
