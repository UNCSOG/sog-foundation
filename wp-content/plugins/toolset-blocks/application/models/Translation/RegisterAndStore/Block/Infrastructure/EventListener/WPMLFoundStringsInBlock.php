<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsForTranslationService;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsToPackageService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WPMLFoundStringsInBlock
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class WPMLFoundStringsInBlock {
	/** @var RegisterStringsForTranslationService */
	private $strings_to_package_service;

	/** @var Actions */
	private $wp_actions;

	/**
	 * WPMLRegisterStringPackages constructor.
	 *
	 * @param RegisterStringsForTranslationService $strings_to_package
	 * @param Actions $wp_actions
	 */
	public function __construct( RegisterStringsForTranslationService $strings_to_package, Actions $wp_actions ) {
		$this->strings_to_package_service = $strings_to_package;
		$this->wp_actions = $wp_actions;
	}

	public function start_listen() {
		$this->wp_actions->add_filter( 'wpml_found_strings_in_block', array( $this, 'on_event' ), 10, 2 );
	}

	public function on_event( $strings, $block) {
		try {
			return $this->strings_to_package_service->execute( $strings, $block );
		} catch( \InvalidArgumentException $exception ) {
			// Not a Views block.
			return $strings;
		} catch( \Exception $exception ) {
			// Unexpected.
			return $strings;
		}
	}
}
