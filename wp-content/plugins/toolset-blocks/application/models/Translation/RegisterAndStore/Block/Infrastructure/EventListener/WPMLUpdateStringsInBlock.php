<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\StoreTranslatedStringsService;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsToPackageService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WPMLUpdateStringsInBlock
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class WPMLUpdateStringsInBlock {
	/** @var StoreTranslatedStringsService */
	private $translation_to_blocks_service;

	/** @var Actions */
	private $wp_actions;

	/**
	 * WPMLRegisterStringPackages constructor.
	 *
	 * @param StoreTranslatedStringsService $strings_to_package
	 */
	public function __construct( StoreTranslatedStringsService $strings_to_package, Actions $wp_actions ) {
		$this->translation_to_blocks_service = $strings_to_package;
		$this->wp_actions = $wp_actions;
	}

	public function start_listen() {
		$this->wp_actions->add_filter( 'wpml_update_strings_in_block', array( $this, 'on_event' ), 10, 3 );
	}

	public function on_event( $block, $translations = null, $lang = null ) {
		try {
			return $this->translation_to_blocks_service->execute( $block, $translations, $lang );
		} catch( \InvalidArgumentException $exception ) {
			// Not a Views block.
			return $block;
		} catch( \Exception $exception ) {
			// Unexpected.
			return $block;
		}
	}
}
