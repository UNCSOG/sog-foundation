<?php
/**
 * The admin view class for the Tag Manager module.
 *
 * @link    http://wpmudev.com
 * @since   3.3.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Tag_Manager\Views
 */

namespace Beehive\Core\Modules\Google_Tag_Manager\Views;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class Admin
 *
 * @package Beehive\Core\Modules\Google_Tag_Manager\Views
 */
class Admin extends \Beehive\Core\Utils\Abstracts\View {

	/**
	 * Render GTM admin settings page.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function settings() {
		$this->render_admin_page();
	}
}
