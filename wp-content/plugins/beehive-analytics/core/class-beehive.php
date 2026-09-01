<?php
/**
 * The core plugin class.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core
 */

namespace Beehive\Core;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Controllers\Settings;
use Beehive\Core\Utils\Abstracts\Base;

/**
 * Class Beehive
 *
 * @package Beehive\Core
 */
final class Beehive extends Base {

	/**
	 * Settings helper class instance.
	 *
	 * @since  3.2.0
	 * @var settings
	 */
	public $settings;

	/**
	 * Initialize functionality of the plugin.
	 *
	 * This is where we kick-start the plugin by defining
	 * everything required and register all hooks.
	 *
	 * @since  3.2.0
	 * @access protected
	 *
	 * @return void
	 */
	protected function __construct() {
		$this->define();
		$this->init();
		$this->run();
	}

	/**
	 * Create new instances of required classes.
	 *
	 * @since  3.2.0
	 * @access private
	 *
	 * @return void
	 */
	private function init() {
		// Define settings class.
		$this->settings = Settings::instance();
	}

	/**
	 * Register all of the actions and filters.
	 *
	 * @since  3.2.0
	 * @access private
	 *
	 * @return void
	 */
	private function run() {
		// Run free version.
		Core::instance()->setup();
			// Run free version.
			Free::instance()->setup();
	}

	/**
	 * Define all the constants required for the plugin.
	 *
	 * We define only the required items at the main plugin file so that
	 * we can handle the Pro/Free conflicts easily.
	 *
	 * @since 3.2.0
	 */
	private function define() {
		// Plugin directory.
		if ( ! defined( 'BEEHIVE_DIR' ) ) {
			define( 'BEEHIVE_DIR', plugin_dir_path( BEEHIVE_PLUGIN_FILE ) );
		}

		// Plugin url.
		if ( ! defined( 'BEEHIVE_URL' ) ) {
			define( 'BEEHIVE_URL', plugin_dir_url( BEEHIVE_PLUGIN_FILE ) );
		}
	}
}
