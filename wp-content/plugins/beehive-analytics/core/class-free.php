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
use WPMUDEV\Modules\Plugin_Cross_Sell\Utilities;

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

		// Initialize Cross Sell module.
		add_action( 'init', array( $this, 'init_cross_sell' ) );
		add_filter( 'beehive_assets_scripts_common_localize_vars', array( $this, 'localize_vars' ), 10, 2 );
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
	public function disable_giveaway( $notices, $plugin ): array {
		if ( 'beehive' === $plugin ) {
			$notices[] = 'giveaway';
		}

		return $notices;
	}

	/**
	 * Setup and load the Cross Sell module.
	 *
	 * @since 3.4.18
	 *
	 * @return void
	 */
	public function init_cross_sell() {
		// Cross Sell file.
		$cross_sell_file = BEEHIVE_DIR . '/core/external/plugins-cross-sell-page/plugin-cross-sell.php';

		// Load Cross Sell module.
		if ( ! file_exists( $cross_sell_file ) ) {
			return;
		}

		static $cross_sell = null;

		if ( ! is_null( $cross_sell ) ) {
			return;
		}

		if ( ! class_exists( '\WPMUDEV\Modules\Plugin_Cross_Sell' ) ) {
			// Load Cross Sell module.
			include_once $cross_sell_file;
		}

		$submenu_params = array(
			'slug'        => 'beehive-analytics',
			'parent_slug' => 'beehive',
			'capability'  => 'manage_options',
			'menu_slug'   => 'beehive_cross_sell',
			'position'    => 5,
		);

		$cross_sell = new \WPMUDEV\Modules\Plugin_Cross_Sell( $submenu_params );
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

	/**
	 * Get localize_vars for free version.
	 *
	 * Pass cross-sell localized data through the common localize filter
	 * so it's available in all admin scripts.
	 *
	 * @param array $vars Existing localized vars.
	 *
	 * @return array
	 * @since 3.3.13
	 *
	 */
	public function localize_vars( array $vars ): array {
		// Get plugins for cross-sell.
		$utilities    = new Utilities();
		$plugins      = $utilities->get_plugins_list();
		$current_slug = 'beehive-analytics';
		$utm_source   = $this->get_utm_source( $plugins, $current_slug );

		// Add cross-sell data to vars.
		$vars['cross_sell'] = array(
			'free_plugins' => ! empty( $plugins ) ? $this->filter_plugins_list( $plugins, $current_slug, $utm_source ) : array(),
			'pro_plugins'  => array(),
		);

		return $vars;
	}

	/**
	 * Get UTM source for the current plugin.
	 *
	 * @param array $plugins Free plugins list.
	 * @param string $current_slug Current plugin slug.
	 *
	 * @return string
	 * @since 3.3.13
	 *
	 */
	private function get_utm_source( array $plugins, string $current_slug ): ?string {
		static $utm_source = null;

		if ( empty( $utm_source ) ) {
			$utm_source = '';

			if ( ! empty( $plugins[ $current_slug ] ) ) {
				$current_plugin = $plugins[ $current_slug ];
				$utm_source     = ! empty( $current_plugin['utm_source'] ) ? $current_plugin['utm_source'] : '';
			}

			// Fallback: search for plugins matching the slug.
			if ( empty( $utm_source ) ) {
				$matched_plugins = array_filter(
					$plugins,
					function ( $plugin ) use ( $current_slug ) {
						return strpos( $plugin['slug'], $current_slug ) !== false;
					}
				);

				if ( ! empty( $matched_plugins ) ) {
					$current_plugin = reset( $matched_plugins );
					$utm_source     = ! empty( $current_plugin['utm_source'] ) ? $current_plugin['utm_source'] : '';
				}
			}
		}

		return $utm_source;
	}

	/**
	 * Filter and prepare plugins list for frontend.
	 *
	 * @param array $plugins List of plugins.
	 * @param string $current_slug Current plugin slug.
	 * @param string $utm_source UTM source for tracking.
	 *
	 * @return array
	 * @since 3.3.13
	 *
	 */
	private function filter_plugins_list( array $plugins, string $current_slug, string $utm_source ): array {
		$utilities = new Utilities();

		if ( empty( $plugins ) || empty( $current_slug ) ) {
			return $plugins;
		}

		foreach ( $plugins as $key => $plugin ) {
			// Remove the current plugin from the list and if the slug is empty.
			if ( empty( $plugin['slug'] ) || $plugin['slug'] === $current_slug ) {
				unset( $plugins[ $key ] );
				continue;
			}

			// Check if the plugin is installed and active.
			if ( ! empty( $plugin['path'] ) ) {
				$plugins[ $key ]['installed'] = $utilities->is_plugin_installed( $plugin['path'] );
				$plugins[ $key ]['active']    = is_plugin_active( $plugin['path'] );
			}

			// Set admin URL if available.
			if ( ! empty( $plugin['admin_url_page'] ) ) {
				$plugins[ $key ]['admin_url'] = admin_url( 'admin.php?page=' . $plugin['admin_url_page'] );
			} else {
				$plugins[ $key ]['admin_url'] = '';
			}

			// Set the plugin's full url for logo.
			$plugins[ $key ]['logo'] = ! empty( $plugin['logo'] ) ? WPMUDEV_MODULE_PLUGIN_CROSS_SELL_URL . 'assets/images/' . $plugin['logo'] : '';

			// Add UTM to plugin's url.
			if ( ! empty( $plugin['url'] ) && ! empty( $utm_source ) ) {
				$plugin_utm_campaign = ! empty( $plugin['utm_campaign'] ) ? $plugin['utm_campaign'] : '';

				$plugins[ $key ]['url'] = add_query_arg(
					array(
						'utm_source'   => $utm_source,
						'utm_medium'   => 'plugin',
						'utm_campaign' => $plugin_utm_campaign,
						'utm_content'  => 'plugins-cross-sell',
					),
					$plugin['url']
				);
			}
		}

		return apply_filters( 'beehive_free_cross_sell_plugins_list', $plugins );
	}
}
