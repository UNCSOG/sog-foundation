<?php
/**
 * Defines everything for the free version of the plugin.
 *
 * @note    Only hooks fired after the `plugins_loaded` hook will work here.
 *          You need to register earlier hooks separately.
 *
 * @link    http://wpmudev.com
 * @since   3.3.13
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core
 */

namespace Beehive\Core;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Utils\Abstracts\Base;

/**
 * Class Free
 *
 * @package Beehive\Core
 */
class Free extends Base {

	/**
	 * Setup the plugin and register all hooks.
	 *
	 * @since 3.3.13
	 *
	 * @return void
	 */
	public function setup() {
		/**
		 * Important: Do not change the priority.
		 *
		 * We need to initialize the modules as early as possible
		 * but using `init` hook. Then only other hooks will work.
		 */
		add_action( 'init', array( $this, 'init_modules' ), - 1 );

		// Initialize sub modules.
		add_action( 'admin_init', array( $this, 'init_notices' ), 1 );
		// Disable giveaway notice.
		add_action( 'wpmudev_notices_disabled_notices', array( $this, 'disable_giveaway' ), 10, 2 );

		/**
		 * Action hook to trigger after initializing all free features.
		 *
		 * @since 3.3.13
		 */
		do_action( 'beehive_after_free_init' );
	}

	/**
	 * Setup WPMUDEV Dashboard notifications.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function init_notices() {
		// Notice module file.
		include_once BEEHIVE_DIR . '/core/external/free-notices/module.php';

		// Register plugin for notice.
		do_action(
			'wpmudev_register_notices',
			'beehive',
			array(
				'basename'     => plugin_basename( BEEHIVE_PLUGIN_FILE ),
				'title'        => 'Beehive',
				'wp_slug'      => 'beehive-analytics',
				'installed_on' => time(),
				'screens'      => array(
					'toplevel_page_beehive',
					'toplevel_page_beehive-network',
					'dashboard_page_beehive-accounts',
					'dashboard_page_beehive-accounts-network',
					'dashboard_page_beehive-settings',
					'dashboard_page_beehive-settings-network',
					'dashboard_page_beehive-tutorials',
					'dashboard_page_beehive-tutorials-network',
					'dashboard_page_beehive-google-analytics',
					'dashboard_page_beehive-google-analytics-network',
					'dashboard_page_beehive-google-tag-manager',
					'dashboard_page_beehive-google-tag-manager-network',
					'toplevel_page_beehive-statistics',
					'toplevel_page_beehive-statistics-network',
				),
			)
		);
	}

	/**
	 * Disable giveaway notice.
	 *
	 * @since 3.4.7
	 *
	 * @param array  $notices Disabled notices.
	 * @param string $plugin  Plugin ID.
	 *
	 * @return array
	 */
	public function disable_giveaway( $notices, $plugin ) {
		if ( 'beehive' === $plugin ) {
			$notices[] = 'giveaway';
		}

		return $notices;
	}

	/**
	 * Initialize modules for the free version of the plugin.
	 *
	 * Note: Hooks that execute after init hook with priority 1 or higher
	 * will only work from this method. You need to handle the earlier hooks separately.
	 * Hook into `beehive_after_core_modules_init` to add new
	 * module.
	 *
	 * @since 3.3.13
	 */
	public function init_modules() {
		/**
		 * Action hook to execute after free modules initialization.
		 *
		 * @since 3.3.13
		 */
		do_action( 'beehive_after_free_modules_init' );
	}
}
