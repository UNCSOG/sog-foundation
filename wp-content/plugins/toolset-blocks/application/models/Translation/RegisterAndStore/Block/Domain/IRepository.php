<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain;

/**
 * Interface IRepository
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain
 *
 * @since TB 1.3
 */
interface IRepository {
	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return ITranslatableBlock
	 */
	public function get_entity_by_wp_block_parser_class( \WP_Block_Parser_Block $block );
}
