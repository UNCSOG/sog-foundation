<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Application;

use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\Repository\WordPressRepository;

/**
 * Class LoadWpaService
 *
 * Just loads an WPA. This is needed as we need to hook to different filters in the legacy code to collect all required
 * data for the WPA translation.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Application
 * @codeCoverageIgnore No need to test this service.
 *
 * @since TB 1.3
 */
class LoadWpaService {

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
	 *
	 */
	public function execute( $wpa_id ) {
		$this->repository->get_wpa_by_id( $wpa_id );
	}
}
