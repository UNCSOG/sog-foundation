<?php

namespace ToolsetBlocks\PublicDependencies\Dependency;

/**
 * Lightbox dependency
 *
 * @since 1.0.0
 */
class Lightbox implements IContent {

	/**
	 * Returns true/false if the current dependency is required for the content
	 *
	 * @param string $content Content of the current post
	 *
	 * @return bool
	 */
	public function is_required_for_content( $content ) {
		if ( strpos( $content, 'data-lightbox' ) !== false ) {
			return true;
		}

		if ( preg_match(
			'#<!-- wp:toolset-blocks\/image-slider((?!"lightboxEnabled":false).)' .
			'*?<!-- \/wp:toolset-blocks\/image-slider#ism',
			$content
		) ) {
			// Image Slider using lightbox with dynamic loaded images. There is no "lightboxEnabled":true (as it is
			// the default value) so we need to check that it's not false.
			return true;
		}

		return false;
	}

	/**
	 * Function to load the dependencies
	 */
	public function load_dependencies() {
		// respect theme lightbox
		if ( ! wp_script_is( 'lightbox' ) ) {
			wp_enqueue_script(
				'lightbox',
				TB_URL . 'public/vendor/lightbox/js/lightbox.min.js',
				array( 'jquery' ),
				'2.10.0'
			);
		}

		// respect theme lightbox
		if ( ! wp_style_is( 'lightbox' ) ) {
			wp_enqueue_style(
				'lightbox',
				TB_URL . 'public/vendor/lightbox/css/lightbox.min.css',
				array(),
				'2.10.0'
			);
		}
	}
}
