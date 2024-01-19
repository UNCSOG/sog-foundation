<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\Frontend\View\Application\ApplyTranslationsForSearchElementsService;
use OTGS\Toolset\Views\Models\Translation\Frontend\View\Application\RestoreTranslatedViewComponents;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WpvViewSettings
 *
 * Hook to views "wpv_view_settings" filter to apply translations. A good part of displayed content from the View is
 * taken from the settings post meta (just to remind why the settings require translations apply).
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class WpvViewSettings {
	/** @var ApplyTranslationsForSearchElementsService */
	private $apply_translations_service;

	/** @var \WP_Post */
	private $post_translated;

	/** @var Actions */
	private $wp_actions;

	/**
	 * WPMLRegisterStringPackages constructor.
	 *
	 * @param ApplyTranslationsForSearchElementsService $restore_translations
	 * @param Actions $wp_actions
	 */
	public function __construct(
		ApplyTranslationsForSearchElementsService $restore_translations,
		Actions $wp_actions
	) {
		$this->apply_translations_service = $restore_translations;
		$this->wp_actions = $wp_actions;
	}

	/**
	 * @param \WP_Post $post
	 */
	public function set_post_translated( \WP_Post $post ) {
		$this->post_translated = $post;
	}

	/**
	 * Init filtering the meta html (inside the views settings).
	 *
	 * @throws \RuntimeException
	 */
	public function start_listen() {
		if( ! $this->post_translated ) {
			throw new \RuntimeException( 'A translated post must be set before using start_listen().' );
		}

		$this->wp_actions->add_filter( 'wpv_view_settings', array( $this, 'on_event' ), 1, 2 );
	}

	public function on_event( $wpv_settings, $view_id ) {
		try {
			return $this->apply_translations_service->execute( $view_id, $wpv_settings, $this->post_translated );
		} catch ( \InvalidArgumentException $exception ) {
			// Not a Views block.
			return $wpv_settings;
		} catch ( \Exception $exception ) {
			// Unexpected.
			if( defined( 'WPV_TRANSLATION_DEBUG' ) && WPV_TRANSLATION_DEBUG ) {
				// @codeCoverageIgnoreStart
				trigger_error(  'Problem with Views translation: ' . $exception->getMessage(), E_USER_WARNING );
				// @codeCoverageIgnoreEnd
			}
			return $wpv_settings;
		}
	}
}
