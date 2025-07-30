<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Common\Domain\TranslationService;

/**
 * Class CustomSearchReset
 *
 * Registers the label of the Reset button to the page translation package and takes care of replacing the the
 * translated string in the translated post.
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain
 *
 * @since TB 1.3
 */
class CustomSearchReset implements ITranslatableBlock {
	/** @var Block */
	private $block;

	/** @var TranslationService  */
	private $translation_service;

	public function __construct( Block $block, TranslationService $translation_service) {
		$this->block = $block;
		$this->translation_service = $translation_service;
	}

	/**
	 * Register "Reset Search Inputs" button text for translation.
	 *
	 * @inheritDoc
	 */
	public function register_strings_to_translate( $strings_to_translate = [] ) {
		while( $line = $this->block->content_lines()->next() ) {
			if( ! $reset_label = $this->get_reset_label( $line ) ) {
				continue;
			}

			$strings_to_translate[] = $this->translation_service->get_line_object(
				$reset_label,
				__( 'Custom search reset label', 'wpv-views' ),
				$this->block->name()
			);
		}

		return $strings_to_translate;
	}


	/**
	 * Store translated "Reset Search Inputs" button label to translated post.
	 *
	 * @inheritDoc
	 */
	public function store_translated_strings( \WP_Block_Parser_Block $block, $translations, $lang ) {
		while( $line = $this->block->content_lines()->next() ) {
			if( ! $reset_label = $this->get_reset_label( $line ) ) {
				continue;
			}

			$reset_label_translated = $this->translation_service->get_translated_text_by_translations(
				$reset_label,
				$translations,
				$this->block->name(),
				$lang
			);

			if( ! empty( $reset_label_translated ) ) {
				$line_translated = preg_replace(
					'#(\[wpv-filter-reset[^\]]*?reset_label=["\'])(' . preg_quote( $reset_label, '#' ) . ')(["\'])(.*?\])#ism',
					"$1". $reset_label_translated . "$3 is_translated=\"1\"$4",
					$line
				);

				$block->innerHTML = str_replace( $line, $line_translated, $block->innerHTML );

				// Also change the label inside the label attribute.
				// This is required to display the block in the translated edit page.
				if( property_exists( $block, 'attrs' ) && is_array( $block->attrs ) ) {
					$block->attrs['label'] = $reset_label_translated;
				}
			}
		}

		return $block;
	}


	/**
	 * @param string $line Short text, using preg_match() on them is totally fine.
	 *
	 * @return false|string
	 */
	private function get_reset_label( $line ) {
		if( ! preg_match( '#\[wpv-filter-reset[^\]]*?reset_label=["\'](.*?)["\'].*?\]#ism', $line, $matches ) ) {
			return false;
		}

		return isset( $matches[1] ) && ! empty( $matches[1] ) ? $matches[1] : false;
	}
}
