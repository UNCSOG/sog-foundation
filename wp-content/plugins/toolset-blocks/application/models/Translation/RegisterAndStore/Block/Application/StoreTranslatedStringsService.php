<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\Repository\WordPressRepository;

/**
 * Class StoreTranslatedStringsService
 *
 * Store translated strings from WPML.
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application
 * @codeCoverageIgnore No need to test this service.
 *
 * @since TB 1.3
 */
class StoreTranslatedStringsService {
	/** @var WordPressRepository */
	private $repository;

	/**
	 * StoreTranslatedStringsService constructor.
	 *
	 * @param WordPressRepository $repository
	 */
	public function __construct( WordPressRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * @param \WP_Block_Parser_Block $a_block
	 * @param array $translations
	 * @param string $lang
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public function execute( \WP_Block_Parser_Block $a_block = null, $translations = null, $lang = null ) {
		if( $views_block = $this->repository->get_entity_by_wp_block_parser_class( $a_block ) ) {
			return $views_block->store_translated_strings( $a_block, $translations, $lang );
		}

		return $a_block;
	}
}
