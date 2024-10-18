<?php


namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Application\ApplyTranslationsForSearchElementsService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WpvViewSettings
 *
 * Apply translations to the WPA settings.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class WpvViewSettings {

	/**
	 * To prevent running the same logic on every apply_filters( 'wpv_view_settings', ...) it's cached by WPA id.
	 * @var array
	 */
	static $cache = [];

	/** @var ApplyTranslationsForSearchElementsService */
	private $translation_service;

	/** @var Actions */
	private $wp_actions;

	/**
	 * WPMLRegisterStringPackages constructor.
	 *
	 * @param ApplyTranslationsForSearchElementsService $translation_service
	 * @param Actions $wp_actions
	 */
	public function __construct(
		ApplyTranslationsForSearchElementsService $translation_service,
		Actions $wp_actions
	) {
		$this->translation_service = $translation_service;
		$this->wp_actions = $wp_actions;
	}

	public function start_listen() {
		$this->wp_actions->add_filter( 'wpv_view_settings', array( $this, 'on_event' ), 1, 2 );
	}

	public function on_event( $wpa_settings = null, $wpa_id = null ) {
		try {
			if( array_key_exists( $wpa_id, self::$cache ) ) {
				// Return the cached value.
				return self::$cache[$wpa_id];
			}

			// First call for this WPA. Run translation.
			return self::$cache[$wpa_id] = $this->translation_service->execute( $wpa_id, $wpa_settings );

		} catch ( \InvalidArgumentException $exception ) {
			// Not a WPA block.
			return $wpa_settings;

		} catch ( \RuntimeException $exception ) {
			if( defined( 'WPV_TRANSLATION_DEBUG' ) && WPV_TRANSLATION_DEBUG ) {
				// @codeCoverageIgnoreStart
				trigger_error(  'Problem with Views translation: ' . $exception->getMessage(), E_USER_WARNING );
				// @codeCoverageIgnoreEnd
			}
			return $wpa_settings;

		} catch ( \Exception $exception ) {
			return $wpa_settings;
		}
	}
}
