<?php
/**
 * Plugin Name: SOG-UNC-Rebrand
 * Plugin URI: https://sc.unc.edu/sog-it/sog-unc-rebrand
 * Description: Theme-agnostic School of Government branding scaffolding for standardized header and footer injection.
 * Version: 0.2.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Matias Silva, UNC School of Government
 * Co-Author: Lindsay Hoyt, UNC School of Government
 * Text Domain: sog-unc-rebrand
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOG_UNC_REBRAND_VERSION', '0.1.4' );
define( 'SOG_UNC_REBRAND_FILE', __FILE__ );
define( 'SOG_UNC_REBRAND_PATH', plugin_dir_path( __FILE__ ) );
define( 'SOG_UNC_REBRAND_URL', plugin_dir_url( __FILE__ ) );

require_once SOG_UNC_REBRAND_PATH . 'includes/class-autoloader.php';

\SOG\Rebrand\Autoloader::register();

register_activation_hook(
	__FILE__,
	static function() {
		\SOG\Rebrand\Core\Activator::activate();
	}
);

add_action(
	'plugins_loaded',
	static function() {
		\SOG\Rebrand\Plugin::instance()->boot();
	}
);

// Add link to the settings page for the plugin on the plugins page in the WordPress admin.
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function( array $links ): array {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=sog-unc-rebrand' ) ) . '">' . __( 'Settings', 'sog-unc-rebrand' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
);

// Ensure the sog-rebrand-footer-bottom menu exists and is autopopulated if missing.
add_action('init', function() {
	$location = 'sog-rebrand-footer-bottom';
	$menu_name = 'Footer Bottom Menu';
	$privacy_url = 'https://www.unc.edu/about/privacy-statement/';
	$accessibility_url = 'https://digitalaccessibility.unc.edu/';

	// Get all menu locations and menus
	$locations = get_nav_menu_locations();
	$menu_id = isset($locations[$location]) ? (int)$locations[$location] : 0;
	$menu_obj = $menu_id ? wp_get_nav_menu_object($menu_id) : false;

	// If menu does not exist, create it and assign to location
	if (!$menu_obj) {
		$menu_id = wp_create_nav_menu($menu_name);
		if ($menu_id && is_nav_menu($menu_id)) {
			$locations[$location] = $menu_id;
			set_theme_mod('nav_menu_locations', $locations);
		}
	}

	// Check if menu has items
	$items = wp_get_nav_menu_items($menu_id);

	if (empty($items)) {
		// Add Privacy Policy link
		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' => 'Privacy Policy',
			'menu-item-url' => $privacy_url,
			'menu-item-status' => 'publish',
			'menu-item-type' => 'custom',
		));

		// Add Accessibility link
		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' => 'Accessibility',
			'menu-item-url' => $accessibility_url,
			'menu-item-status' => 'publish',
			'menu-item-type' => 'custom',
		));
	}
});
