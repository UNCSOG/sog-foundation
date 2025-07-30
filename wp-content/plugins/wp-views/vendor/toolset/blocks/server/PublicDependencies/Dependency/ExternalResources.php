<?php

namespace ToolsetBlocks\PublicDependencies\Dependency;

/**
 * External resources dependency
 *
 * Loads external css or js depending on the content
 *
 * @since 1.0.0
 * @todo Use it in a general way, get the file extension and load a style or a script
 */
class ExternalResources implements IContent {

	/**
	 * External URL to be loaded
	 *
	 * @var array<string>
	 * @since 1.0.0
	 */
	private $urls = [];

	/**
	 * Returns true/false if the current dependency is required for the content
	 *
	 * @param string $content Content of the current post
	 *
	 * @return mixed
	 */
	public function is_required_for_content( $content ) {
		// Split content to not run into timeout for huge content.
		$blocks = explode( '<!--', $content );

		foreach ( $blocks as $block ) {
			// Button Block.
			if ( strpos( $block, 'wp:toolset-blocks/button {' ) && ! strpos( $block, 'fontCode":""' ) ) {
				preg_match_all( '/"cssUrl":"([^"]+)"/', $block, $m );
				if ( isset( $m[1] ) && ! empty( $m[1] ) ) {
					foreach ( $m[1] as $url ) {
						if ( ! in_array( $url, $this->urls, true ) ) {
							$this->urls[] = $url;
						}
					}
				}
			}

			// Star Rating Block.
			if ( strpos( $block, 'wp:toolset-blocks/star-rating {' ) ) {
				preg_match_all( '/"customFontURL":"([^"]+)"/', $block, $m );
				if ( isset( $m[1] ) && ! empty( $m[1] ) ) {
					foreach ( $m[1] as $url ) {
						if ( ! in_array( $url, $this->urls, true ) ) {
							$this->urls[] = $url;
						}
					}
				}
			}
		}

		$this->urls = array_map(
			function ( $url ) {
				// WPML might add slashes in URLs.
				return stripslashes( $url );
			},
			$this->urls
		);
		$this->urls = array_unique( $this->urls );

		return $this->urls;
	}

	/**
	 * Function to load the dependencies
	 */
	public function load_dependencies() {
		foreach ( $this->urls as $url ) {
			if ( strpos( $url, '/dashicons.css' ) !== false ) {
				// DO NOT LOAD DASHICONS AS 'toolset-blocks-dashicons.css'.
				wp_enqueue_style( 'dashicons' );
				continue;
			}
			$slug = 'toolset-blocks-' . preg_replace( '/.*\/([^\/]+)$/', '$1', $url );
			wp_enqueue_style( $slug, $url );
		}
	}
}
