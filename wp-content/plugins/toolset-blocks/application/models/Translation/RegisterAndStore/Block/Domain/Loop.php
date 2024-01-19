<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Common\Domain\TranslationService;

/**
 * Class Loop
 *
 * The loop has an user input for the "No posts found" case. This will register all wpml-string shortcodes.
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain
 *
 * @since TB 1.3
 */
class Loop implements ITranslatableBlock{
	/** @var Block */
	private $block;

	/** @var View */
	private $view;

	/** @var TranslationService  */
	private $translation_service;

	/**
	 * Loop constructor.
	 *
	 * @param Block $block
	 * @param TranslationService $translation_service
	 * @param View $view
	 */
	public function __construct( Block $block, TranslationService $translation_service, View $view ) {
		$this->block = $block;
		$this->translation_service = $translation_service;
		$this->view = $view;
	}

	/**
	 * Register "No items found" text for translation.
	 * Note: there is a bug in WPML, which will currently prevent the display of the translated string on the frontend:
	 * https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-3797
	 *
	 * Still keep the this intact, so it's fixed once WPML do the update.
	 *
	 * @inheritDoc
	 *
	 */
	public function register_strings_to_translate( $strings_to_translate = [] ) {
		$no_items_found_text = $this->view->get_no_items_found_text();

		preg_match_all(
			'#\[wpml-string.*?context=["\'](.*?)["\'].*?](.*?)\[\/wpml-string]#ism',
			$no_items_found_text,
			$wpml_strings,
			PREG_SET_ORDER
		);

		foreach( $wpml_strings as $wpml_string ) {
			if( ! is_array( $wpml_string ) || ! isset( $wpml_string[2] ) ) {
				// @codeCoverageIgnoreStart
				// This cannot happen with the above regex. And if it's happens the unit test for this method will fail.
				continue;
				// @codeCoverageIgnoreEnd
			}

			$strings_to_translate[] = $this->translation_service->get_line_object(
				// Put the complete shortcode [wpml-string] to WPML. WPML will keep the context, also the package
				// have a different context.
				$wpml_string[0],
				__( 'Loop label for "no items found"', 'wpv-views' ),
				$this->block->name()
			);
		}

		return $strings_to_translate;
	}


	/**
	 * @inheritDoc
	 */
	public function store_translated_strings( \WP_Block_Parser_Block $block, $translations, $lang ) {
		// Nothing to do here.
		// When the user adds [wpml-string context="any context"] shortcodes to the "No posts found" area, we will
		// transfer them to the package, but keep the shortcode untouched.

		// Return unmodified block.
		return $block;
	}
}
