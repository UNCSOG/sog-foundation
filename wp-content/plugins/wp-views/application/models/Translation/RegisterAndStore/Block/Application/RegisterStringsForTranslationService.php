<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\Repository\WordPressRepository;

/**
 * Class RegisterStringsForTranslationService
 *
 * Send strings to WPML.
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application
 * @codeCoverageIgnore No need to test this service.
 *
 * @since TB 1.3
 */
class RegisterStringsForTranslationService {
	/** @var WordPressRepository */
	private $repository;

	/**
	 * RegisterStringsForTranslationService constructor.
	 *
	 * @param WordPressRepository $repository
	 */
	public function __construct( WordPressRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 *
	 * @param array $strings
	 * @param \WP_Block_Parser_Block|null $block
	 *
	 * @return array
	 */
	public function execute( $strings = [], \WP_Block_Parser_Block $block = null ) {
		if( $view = $this->repository->get_entity_by_wp_block_parser_class( $block ) ) {
			return $view->register_strings_to_translate( $strings );
		}

		return $strings;
	}
}
