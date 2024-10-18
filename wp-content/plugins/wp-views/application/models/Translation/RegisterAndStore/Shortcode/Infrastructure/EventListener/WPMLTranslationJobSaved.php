<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Application\StringsToPostService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WPMLTranslationJobSaved
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class WPMLTranslationJobSaved {
	/** @var StringsToPostService */
	private $strings_to_post_service;

	/** @var Actions */
	private $wp_actions;

	/** @var array */
	private $packages;

	/**
	 * WPMLRegisterStringPackages constructor.
	 *
	 * @param StringsToPostService $strings_to_package_service
	 * @param Actions $wp_actions
	 */
	public function __construct( StringsToPostService $strings_to_package_service, Actions $wp_actions ) {
		$this->strings_to_post_service = $strings_to_package_service;
		$this->wp_actions              = $wp_actions;
	}

	public function start_listen() {
		$this->wp_actions->add_action( 'wpml_translation_job_saved', array( $this, 'collect_translation_data' ), 100, 3 );
		$this->wp_actions->add_action( 'wpml_page_builder_string_translated', array( $this, 'on_event' ), 20, 2 );
	}

	// This shouldn't be on the event listener.
	public function collect_translation_data( $translated_post_id, $fields, $job ) {
		$packages = [];

		if( ! is_object( $job ) || ! property_exists( $job, 'elements' ) ) {
			// $job format has probably changed.
			$this->packages = $packages;
			return;
		}

		foreach( $job->elements as $element ) {
			if( ! is_object( $element ) || ! property_exists( $element, 'field_type' ) ) {
				continue;
			}

			if( preg_match( '#package-string-[0-9]*#', $element->field_type, $package_name ) ) {
				if( ! isset( $packages[ $package_name[0] ] ) ) {
					$packages[ $package_name[0] ] = [];
				}

				$packages[ $package_name[0] ][ base64_decode( $element->field_data ) ] =
					base64_decode( $element->field_data_translated );
			}
		}

		$this->packages = $packages;
	}

	public function get_packages(){
		return $this->packages;
	}

	public function on_event( $kind, $translated_post_id ) {
		try {
			return $this->strings_to_post_service->execute( get_post( $translated_post_id ), $this->get_packages() );
		} catch( \InvalidArgumentException $exception ) {
			return;
		} catch( \Exception $exception ) {
			return;
		}
	}
}
