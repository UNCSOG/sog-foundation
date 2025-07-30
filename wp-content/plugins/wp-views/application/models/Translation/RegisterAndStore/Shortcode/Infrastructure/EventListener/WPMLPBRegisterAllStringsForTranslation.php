<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Application\StringsToPackageService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WPMLTMTranslationJobData
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class WPMLPBRegisterAllStringsForTranslation {
	/** @var StringsToPackageService */
	private $strings_to_package_service;

	/** @var Actions */
	private $wp_actions;

	/**
	 * WPMLRegisterStringPackages constructor.
	 *
	 * @param StringsToPackageService $strings_to_package_service
	 * @param Actions $wp_actions
	 */
	public function __construct( StringsToPackageService $strings_to_package_service, Actions $wp_actions ) {
		$this->strings_to_package_service = $strings_to_package_service;
		$this->wp_actions = $wp_actions;
	}

	public function start_listen() {
		// $this->wp_actions->add_action( 'wpml_page_builder_register_strings', array( $this, 'on_event' ), 20, 2 );
		$this->wp_actions->add_action( 'wpml_pb_register_all_strings_for_translation', array( $this, 'on_event' ) );
	}

	public function on_event( $post ) {
		try {
			return $this->strings_to_package_service->execute( $post );
		} catch( \InvalidArgumentException $exception ) {
			return;
		} catch( \Exception $exception ) {
			return;
		}
	}
}
