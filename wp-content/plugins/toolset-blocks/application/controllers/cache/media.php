<?php

namespace OTGS\Toolset\Views\Controller\Cache;

/**
 * Featured image cache controller.
 *
 * @since 2.8.1
 */
class Media {

	protected $media_model = null;

	/**
	 * Construct
	 *
	 * @param \OTGS\Toolset\Views\Model\Wordpress\Media $media_model
	 * @since 2.8.1
	 */
	public function __construct( \OTGS\Toolset\Views\Model\Wordpress\Media $media_model ) {
		$this->media_model = $media_model;
	}

	/**
	 * Initialize the featured image cache management.
	 *
	 * @since 2.8.1
	 */
	public function initialize() {
		add_action( 'delete_attachment', array( $this, 'delete_featured_image_custom_size' ) );
	}

	/**
	 * Delete all custom sized versions of a featured image once it gets removed.
	 *
	 * @param int $attachment_post_id
	 * @since 2.8.1
	 */
	public function delete_featured_image_custom_size( $attachment_post_id ) {
		$this->media_model->delete_resized_images_by_attachment_id( $attachment_post_id );
	}
}
