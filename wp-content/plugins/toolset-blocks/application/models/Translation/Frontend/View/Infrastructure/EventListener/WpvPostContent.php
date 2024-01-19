<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\Frontend\View\Application\ApplyTranslationsForStringsBeforeAfterLoopService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class WpvPostContent
 *
 * Apply translations to the content.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener
 *
 * @since TB 1.3
 */
class WpvPostContent {
	/** @var ApplyTranslationsForStringsBeforeAfterLoopService */
	private $translation_service;

	/** @var \WP_Post */
	private $post_translated;

	/** @var Actions */
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

	/**
	 * @param \WP_Post $post
	 */
	public function set_post_translated( \WP_Post $post ) {
		$this->post_translated = $post;
	}

	public function start_listen() {
		$this->wp_actions->add_filter( 'wpv_post_content', array( $this, 'on_event' ), 10, 2 );
	}

	public function on_event( $post_content, $view_id ) {
		try {
			return $this->translation_service->execute( $view_id, $post_content, $this->post_translated );
		} catch ( \InvalidArgumentException $exception ) {
			// Not a Views block.
			return $post_content;
		} catch ( \Exception $exception ) {
			// Unexpected
			if( defined( 'WPV_TRANSLATION_DEBUG' ) && WPV_TRANSLATION_DEBUG ) {
				// @codeCoverageIgnoreStart
				trigger_error(  'Problem with Views translation: ' . $exception->getMessage(), E_USER_WARNING );
				// @codeCoverageIgnoreEnd
			}
			return $post_content;
		}
	}
}
