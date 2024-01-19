<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain;

/**
 * Interface IRepository
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain
 *
 * @since TB 1.3
 */
interface IRepository {
	/**
	 * @param int $view_id
	 * @param array $wpv_settings
	 * @param \WP_Post $post
	 *
	 * @return View
	 */
	public function get_view_by_id_and_settings_and_post( $view_id, $wpv_settings, \WP_Post $post );
}
