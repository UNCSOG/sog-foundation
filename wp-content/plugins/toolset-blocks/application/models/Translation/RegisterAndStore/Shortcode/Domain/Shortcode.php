<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes\Attributes;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes\IAttribute;

/**
 * Class WpvControl
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class Shortcode {
	/** @var ShortcodeSlug */
	private $slug;

	/** @var IAttribute[] */
	private $attributes;

	/**
	 * WpvControl constructor.
	 *
	 * @param ShortcodeSlug $slug
	 * @param Attributes $attributes
	 */
	public function __construct( ShortcodeSlug $slug, Attributes $attributes ) {
		$this->slug = $slug;
		$this->attributes = $attributes;
	}

	public function get_slug() {
		return $this->slug->get();
	}

	/**
	 * @param \WP_Post $post
	 *
	 * @return array
	 */
	public function get_translatable_strings( \WP_Post $post ) {
		$strings_to_translate = [];

		if( ! $shortcode_uses = $this->get_shortcode_uses( $post ) ) {
			// The shortcode is not used in the post.
			return $strings_to_translate;
		}

		$shortcodes_translated = [];
		// For every found shortcode.
		foreach( $shortcode_uses as $shortcode_string ) {
			// Don't translate the same shortcode twice. This is needed because the same shortcodes are
			// multiple times on the block storage (yep, smells like s***)
			if( in_array( $shortcode_string, $shortcodes_translated ) ) {
				continue;
			}

			// Apply values of registered attributes to package.
			$strings_to_translate = array_merge(
				$strings_to_translate,
				$this->attributes->get_translatable_strings( $shortcode_string )
			);

			// Add shortcode to translated list.
			$shortcodes_translated[] = $shortcode_string;
		}

		return $strings_to_translate;
	}

	public function apply_translation_to_post( $post, $packages ) {
		if( ! $shortcode_uses = $this->get_shortcode_uses( $post ) ) {
			// The shortcode is not used in the post.
			return;
		}

		foreach( $shortcode_uses as $original_shortcode_string ) {
			$this->attributes->apply_translation_to_post( $post, $original_shortcode_string, $packages );
		}
	}

	private function get_shortcode_uses( \WP_Post $post ) {
		$shortcode_uses = [];

		$content = $post->post_content;
		$shortcode_tag_start = '['.$this->get_slug();

		while( $shortcode_start_position = strpos( $content, $shortcode_tag_start ) ) {
			$content_until_shortcode = substr( $content, $shortcode_start_position ) ;
			$shortcode_end_position = strpos( $content_until_shortcode, ']' ) + 1;

			// Store shortcode.
			$shortcode_uses[] = substr( $content_until_shortcode, 0, $shortcode_end_position );

			// Continue with rest of content.
			$content = substr( $content_until_shortcode, $shortcode_end_position );
		}

		return $shortcode_uses;
	}
}
