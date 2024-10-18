<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Application;

use OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\Repository\WordPressRepository;

/**
 * Class ApplyTranslationsForSearchElementsService
 *
 * For search filters (user inputs) and buttons in the search container.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Application
 * @codeCoverageIgnore No need to test this service.
 *
 * @since TB 1.3
 */
class ApplyTranslationsForSearchElementsService {

	/** @var WordPressRepository */
	private $repository;

	/**
	 * RestoreFilterTranslation constructor.
	 *
	 * @param WordPressRepository $repository
	 */
	public function __construct( WordPressRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Apply translation to $settings of $view_id by using the $post_translated.
	 *
	 * @param array $settings
	 * @param int $view_id
	 * @param \WP_Post $post_translated
	 *
	 * @return array
	 */
	public function execute( $view_id, $settings, \WP_Post $post_translated ) {
		$view = $this->repository->get_view_by_id_and_settings_and_post( $view_id, $settings, $post_translated );

		// Return translated settings.
		return $view->get_translated_wpv_settings();
	}
}
