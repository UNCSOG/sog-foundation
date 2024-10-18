<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain;

/**
 * Interface IRepository
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain
 *
 * @since TB 1.3
 */
interface IRepository {
	/**
	 * @param int $id WPA id. Also the wpa has as post type "view". The actual WPA data is in wpa-helper post type.
	 *
	 * @return WPA
	 */
	public function get_wpa_by_id( $id );
}
