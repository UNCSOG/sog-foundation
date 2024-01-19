<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Application;

use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\Repository\WordPressRepository;

/**
 * Class ApplyTranslationsForStringsBeforeAfterLoopService
 *
 * Between the different View components (Search Container / Loop) there can other blocks being placed. We need
 * to apply the translations of these blocks.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Application
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
	 * @param int $wpa_id
	 * @param string $untranslated_content
	 *
	 * @return string
	 */
	public function execute( $wpa_id, $untranslated_content ) {
		$wpa = $this->repository->get_wpa_by_id( $wpa_id );

		return $wpa->get_translated_post_content( $untranslated_content );
	}
}
