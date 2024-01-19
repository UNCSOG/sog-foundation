<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Application;

use OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\Repository\WordPressRepository;

/**
 * Class ApplyTranslationsForStringsBeforeAfterLoopService
 *
 * Between the different View components (Search Container / Loop) there can other blocks being placed. We need
 * to apply the translations of these blocks.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Application
 * @codeCoverageIgnore No need to test this service.
 *
 * @since TB 1.3
 */
class ApplyTranslationsForStringsBeforeAfterLoopService {

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
	 * @param int $view_id
	 * @param string $untranslated_content
	 *
	 * @param \WP_Post $post_translated
	 *
	 * @return string
	 */
	public function execute( $view_id, $untranslated_content, \WP_Post $post_translated ) {
		$view = $this->repository->get_view_by_id( $view_id, $post_translated );

		return $view->get_translated_post_content( $untranslated_content );
	}
}
