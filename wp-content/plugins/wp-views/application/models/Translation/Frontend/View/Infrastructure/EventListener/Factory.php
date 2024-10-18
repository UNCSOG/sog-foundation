<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\Frontend\View\Application\ApplyTranslationsForSearchElementsService;
use OTGS\Toolset\Views\Models\Translation\Frontend\View\Application\ApplyTranslationsForStringsBeforeAfterLoopService;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Allows creating instances of WpvPostContent and WpvViewSettings.
 *
 * @since 1.5.0
 */
class Factory {
	/** @var ApplyTranslationsForStringsBeforeAfterLoopService */
	private $translation_service_strings_before_after_loop;

	/** @var ApplyTranslationsForSearchElementsService */
	private $translation_service_search_elements;

	/** @var Actions */
	private $wp_actions;

	/**
	 * Factory constructor.
	 *
	 * Just adding the dependencies of the generated projects here as they will always all be needed and this
	 * way it's handier having the DIC creating them.
	 *
	 * @param ApplyTranslationsForStringsBeforeAfterLoopService $translation_service_strings_before_after_loop
	 * @param ApplyTranslationsForSearchElementsService $translation_service_search_elements
	 * @param Actions $wp_actions
	 */
	public function __construct(
		ApplyTranslationsForStringsBeforeAfterLoopService $translation_service_strings_before_after_loop,
		ApplyTranslationsForSearchElementsService $translation_service_search_elements,
		Actions $wp_actions
	) {
		$this->translation_service_strings_before_after_loop = $translation_service_strings_before_after_loop;
		$this->translation_service_search_elements = $translation_service_search_elements;
		$this->wp_actions = $wp_actions;
	}

	/**
	 * @return WpvPostContent
	 */
	public function wpv_post_content() {
		return new WpvPostContent(
			$this->translation_service_strings_before_after_loop,
			$this->wp_actions
		);
	}

	/**
	 * @return WpvViewSettings
	 */
	public function wpv_view_settings() {
		return new WpvViewSettings(
			$this->translation_service_search_elements,
			$this->wp_actions
		);
	}
}
