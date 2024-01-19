<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain;

/**
 * Interface ITranslatableBlock
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain
 *
 * @since TB 1.3
 */
interface ITranslatableBlock {
	/**
	 * The values are passed by 'wpml_found_strings_in_block' filter.
	 *
	 * @param array $strings_to_translate
	 *
	 * @return array
	 */
	public function register_strings_to_translate( $strings_to_translate );


	/**
	 * The values are passed by the 'wpml_update_strings_in_block' filter.
	 *
	 * @param \WP_Block_Parser_Block $block The block with the original texts.
	 * @param array $translations Collection of all translated texts.
	 * @param string $lang The wanted languages.
	 *
	 * @return \WP_Block_Parser_Block The block with the translated texts.
	 */
	public function store_translated_strings( \WP_Block_Parser_Block $block, $translations, $lang );
}
