<?php

namespace ToolsetBlocks\Block;

/**
 * i18n class.
 *
 * Some texts can't be translated using Javascript `wp.i18n` because when the block is saved,
 * the `post_content` will text in the same language than WP dashboard, but if the page is loaded in a different language (WP dashboard language)
 * the block will we generated using that language and blocks will be marked as not valid by Gutenber
 *
 * Translatable texts are defined here so PoEdit or `wp i18n make-pot` could find the texts
 *
 * @link https://github.com/WordPress/gutenberg/issues/12708#issuecomment-463545346
 * @link https://github.com/WordPress/gutenberg/issues/7604
 *
 * @package toolset-blocks
 */
class I18n {

	const NUMBER_OF_STARS = 'star-rating-number-of-stars';

	/**
	 * Init functions
	 * - Adds the shortcode
	 */
	public function initialize() {
		add_shortcode( 'tb-i18n', array( $this, 'i18n_shortcode_render' ) );

		load_plugin_textdomain( 'toolset-blocks', FALSE, basename( TB_PATH ) . '/languages/' );
	}

	/**
	 * I18n shortcode:
	 * `[tb-i18n code="translation-code" placeholders="escaped text" ]`
	 * - code: one of the class constansts
	 * - placeholder: list of strings, escaped and json formatted
	 */
	public function i18n_shortcode_render( $attributes ) {
		if ( ! isset( $attributes[ 'code' ] ) ) {
			return '';
		}
		switch ( $attributes[ 'code' ] ) {
			case self::NUMBER_OF_STARS:
				if ( ! isset( $attributes[ 'placeholders' ] ) ) {
					return '';
				}
				$placeholders = json_decode( urldecode( $attributes[ 'placeholders' ] ) );
				$placeholders = array_map( function( $value ) {
					return do_shortcode( $value );
				}, $placeholders );
				// translators: placeholders are numbers used in a star rating system: 1 of 3 stars.
				return vsprintf( __( '%1$s of %2$s stars', 'wpv-views' ), $placeholders );
				break;
			default:
				return '';
		}
	}
}
