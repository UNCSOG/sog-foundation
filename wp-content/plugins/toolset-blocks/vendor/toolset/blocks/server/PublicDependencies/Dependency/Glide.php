<?php

namespace ToolsetBlocks\PublicDependencies\Dependency;

/**
 * Glide.js dependency
 *
 * @since 1.0.0
 */
class Glide implements IContent {
	const GLIDE_JS_HANDLE = 'glide';
	const GLIDE_CSS_HANDLE = 'glide';

	/**
	 * Returns true/false if the current dependency is required for the content
	 *
	 * @param string $content Content of the current post.
	 *
	 * @return bool
	 */
	public function is_required_for_content( $content ) {
		if (
			wp_script_is( self::GLIDE_JS_HANDLE, 'registered' ) ||
			wp_style_is( self::GLIDE_CSS_HANDLE, 'registered' )
		) {
			return false;
		}
		if (
			strpos( $content, 'tb-repeating-field--carousel' ) !== false ||
			strpos( $content, 'tb-image-slider--carousel' ) !== false
		) {
			return true;
		}
		return false;
	}

	/**
	 * Function to load the dependencies
	 */
	public function load_dependencies() {
		wp_register_script(
			self::GLIDE_JS_HANDLE,
			TB_URL . 'public/vendor/glide/glide.min.js',
			[],
			'3.3.0',
			false
		);

		wp_register_style(
			self::GLIDE_CSS_HANDLE,
			TB_URL . 'public/vendor/glide/glide.min.css',
			[],
			'3.3.0'
		);

		if ( ! is_admin() ) {
			// only enqueue on frontend as standalone
			// for backend it's loaded as dependency of our bundled file
			wp_enqueue_script( self::GLIDE_JS_HANDLE );
			wp_enqueue_style( self::GLIDE_CSS_HANDLE );
		}
	}
}
