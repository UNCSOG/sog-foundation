<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Application\LoadWpaService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WpvFilterForceWordpressArchive
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class WpvFilterForceWordpressArchive {
	/** @var LoadWpaService */
	private $translation_service;

	/** @var Actions */
	private $wp_actions;

	/**
	 * WPMLRegisterStringPackages constructor.
	 *
	 * @param LoadWpaService $service
	 * @param Actions $wp_actions
	 */
	public function __construct( LoadWpaService $service, Actions $wp_actions ) {
		$this->translation_service = $service;
		$this->wp_actions = $wp_actions;
	}

	public function start_listen() {
		$current_language = $this->wp_actions->apply_filters( 'wpml_current_language', false );
		if( $current_language === false ) {
			return;
		}

		$default_language = $this->wp_actions->apply_filters( 'wpml_default_language', false );

		if( $current_language !== $default_language ) {
			$this->wp_actions->add_filter( 'wpv_filter_force_wordpress_archive', array( $this, 'on_event' ), 10, 1 );
		}
	}

	public function on_event( $wpa_id = null ) {
		try {
			$this->translation_service->execute( $wpa_id );
			return $wpa_id;
		} catch ( \InvalidArgumentException $exception ) {
			// Not a WPA block.
			return $wpa_id;
		} catch ( \Exception $exception ) {
			// Unexpected.
			if( defined( 'WPV_TRANSLATION_DEBUG' ) && WPV_TRANSLATION_DEBUG ) {
				// @codeCoverageIgnoreStart
				trigger_error(  'Problem with Views translation: ' . $exception->getMessage(), E_USER_WARNING );
				// @codeCoverageIgnoreEnd
			}
			return $wpa_id;
		}
	}
}
