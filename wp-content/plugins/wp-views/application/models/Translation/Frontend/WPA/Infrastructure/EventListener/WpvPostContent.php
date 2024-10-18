<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Application\ApplyTranslationsForStringsBeforeAfterLoopService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WpvPostContent
 *
 * Apply translations to the content.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class WpvPostContent {
	/** @var ApplyTranslationsForStringsBeforeAfterLoopService */
	private $translation_service;
	/**
	 * @var Actions
	 */
	private $wp_actions;

	/**
	 * WPMLRegisterStringPackages constructor.
	 *
	 * @param ApplyTranslationsForStringsBeforeAfterLoopService $translation_service
	 * @param Actions $wp_actions
	 */
	public function __construct(
		ApplyTranslationsForStringsBeforeAfterLoopService $translation_service,
		Actions $wp_actions
	) {
		$this->translation_service = $translation_service;
		$this->wp_actions = $wp_actions;
	}

	public function start_listen() {
		$current_language = $this->wp_actions->apply_filters( 'wpml_current_language', false );
		if( $current_language === false ) {
			return;
		}

		$default_language = $this->wp_actions->apply_filters( 'wpml_default_language', false );

		if( $current_language !== $default_language ) {
			$this->wp_actions->add_filter( 'wpv_post_content', array( $this, 'on_event' ), 10, 2 );
		}
	}

	public function on_event( $post_content, $wpa_id ) {
		try {
			return $this->translation_service->execute( $wpa_id, $post_content );
		} catch ( \InvalidArgumentException $exception ) {
			// Not a WPA block.
			return $post_content;
		} catch ( \Exception $exception ) {
			// Unexpected.
			if( defined( 'WPV_TRANSLATION_DEBUG' ) && WPV_TRANSLATION_DEBUG ) {
				// @codeCoverageIgnoreStart
				trigger_error(  'Problem with Views translation: ' . $exception->getMessage(), E_USER_WARNING );
				// @codeCoverageIgnoreEnd
			}
			return $post_content;
		}
	}
}
