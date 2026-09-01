<?php
/**
 * The common class of the plugin.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Controllers
 */

namespace Beehive\Core\Controllers;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Utils\Abstracts\Base;

/**
 * Class Common
 *
 * @package Beehive\Core\Controllers
 */
class Common extends Base {

	/**
	 * Initialize the class by registering hooks.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function init() {
		// Process upgrade.
		add_action( 'init', array( $this, 'upgrade' ) );
	}

	/**
	 * Run upgrade process if required.
	 *
	 * We need to make sure we have upgraded all old settings
	 * to our new version without fail. Run upgrade script only
	 * within admin page.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function upgrade() {
		Installer::instance()->upgrade();
	}
}
