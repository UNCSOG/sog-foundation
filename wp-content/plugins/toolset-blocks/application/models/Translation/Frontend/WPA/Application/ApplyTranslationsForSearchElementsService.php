<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Application;

use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\Repository\WordPressRepository;

/**
 * Class ApplyTranslationsForSearchElementsService
 *
 * For search filters (user inputs) and buttons in the search container.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Application
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
	 * @param int $wpa_id
	 * @param array $settings
	 *
	 * @return array
	 */
	public function execute( $wpa_id, $settings ) {
		$wpa = $this->repository->get_wpa_with_settings( $wpa_id, $settings );

		// Return translated settings.
		return $wpa->get_translated_wpa_settings();
	}
}
