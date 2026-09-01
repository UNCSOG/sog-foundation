<?php
/**
 * Plugin Name: SOG-UNC-Rebrand
 * Plugin URI: https://sc.unc.edu/sog-it/sog-unc-rebrand
 * Description: Theme-agnostic School of Government branding scaffolding for standardized header and footer injection.
 * Version: 0.3
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

define( 'SOG_UNC_REBRAND_VERSION', '0.2.1' );
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

// Ensure the menu exists and is autopopulated if missing.
add_action('init', function() {
	$menu_name = 'For Employees Menu';
	$gladys    = 'https://gladys.sog.unc.edu';

	// Look up menu by name so we don't create duplicates.
	$menu_obj = get_term_by( 'name', $menu_name, 'nav_menu' );
	$menu_id  = $menu_obj ? (int) $menu_obj->term_id : 0;

	// If menu does not exist, create it (unassigned — admin chooses location).
	if ( ! $menu_obj ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	}

	// Seed with a safe default if the menu has no items yet.
	// Login/logout is swapped dynamically at render time (see filter below).
	$items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $items ) ) {
		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => 'Employee Login',
			'menu-item-url'    => wp_login_url(),
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		) );

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => 'GLADYS',
			'menu-item-url'    => $gladys,
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		) );
	}
} );

// Dynamically swap the employee login/logout label and URL based on authentication state.
add_filter( 'wp_nav_menu_objects', function( array $items, $args ): array {
	$menu_name = '';
	if ( ! empty( $args->menu ) && is_object( $args->menu ) ) {
		$menu_name = $args->menu->name ?? '';
	} elseif ( ! empty( $args->theme_location ) ) {
		$locations = get_nav_menu_locations();
		if ( ! empty( $locations[ $args->theme_location ] ) ) {
			$menu_obj  = wp_get_nav_menu_object( $locations[ $args->theme_location ] );
			$menu_name = $menu_obj ? $menu_obj->name : '';
		}
	}

	if ( 'For Employees Menu' !== $menu_name ) {
		return $items;
	}

	foreach ( $items as $item ) {
		if ( in_array( $item->title, array( 'Employee Login', 'Employee Logout' ), true ) ) {
			if ( is_user_logged_in() ) {
				$item->title = 'Employee Logout';
				$item->url   = wp_logout_url( get_permalink() );
			} else {
				$item->title = 'Employee Login';
				$item->url   = wp_login_url( get_permalink() );
			}
		}
	}

	return $items;
}, 10, 2 );

// Ensure the menu exists and is autopopulated if missing.
add_action('init', function() {
	$menu_name = 'SOG Client / Student Menu';

	// Look up menu by name so we don't create duplicates.
	$menu_obj = get_term_by( 'name', $menu_name, 'nav_menu' );
	$menu_id  = $menu_obj ? (int) $menu_obj->term_id : 0;

	// If menu does not exist, create it (unassigned — admin chooses location).
	if ( ! $menu_obj ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	}

	// Seed with a login item if the menu has no items yet.
	$items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $items ) ) {
		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => 'Login',
			'menu-item-url'    => wp_login_url(),
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		) );
	}
} );

// Dynamically swap the login/logout label and URL based on authentication state.
add_filter( 'wp_nav_menu_objects', function( array $items, $args ): array {
	// When a menu is rendered via theme_location, WordPress does NOT write the
	// resolved WP_Term back onto $args->menu, so we must resolve it ourselves.
	$menu_name = '';
	if ( ! empty( $args->menu ) && is_object( $args->menu ) ) {
		$menu_name = $args->menu->name ?? '';
	} elseif ( ! empty( $args->theme_location ) ) {
		$locations = get_nav_menu_locations();
		if ( ! empty( $locations[ $args->theme_location ] ) ) {
			$menu_obj  = wp_get_nav_menu_object( $locations[ $args->theme_location ] );
			$menu_name = $menu_obj ? $menu_obj->name : '';
		}
	}

	if ( 'SOG Client / Student Menu' !== $menu_name ) {
		return $items;
	}

	foreach ( $items as $item ) {
		if ( in_array( $item->title, array( 'Login', 'Logout' ), true ) ) {
			if ( is_user_logged_in() ) {
				$item->title = 'Logout';
				$item->url   = wp_logout_url( get_permalink() );
			} else {
				$item->title = 'Login';
				$item->url   = wp_login_url( get_permalink() );
			}
		}
	}

	return $items;
}, 10, 2 );

// Ensure the menu exists and is autopopulated if missing.
add_action('init', function() {
	$menu_name                 = 'SOG General Menu';
	$contact_us                = 'https://www.sog.unc.edu/webforms/contact-us';
	$visitor_info              = 'https://www.sog.unc.edu/about/visitor-information';
	$employment                = 'https://www.sog.unc.edu/about/employment-opportunities';
	$registration_portal_login = 'https://reg.learningstream.com/ram/ram_login.aspx?aid=UNCSOG&s1=46';

	// Look up menu by name so we don't create duplicates.
	$menu_obj = get_term_by( 'name', $menu_name, 'nav_menu' );
	$menu_id  = $menu_obj ? (int) $menu_obj->term_id : 0;

	// If menu does not exist, create it (unassigned — admin chooses location).
	if ( ! $menu_obj ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	}

	// Check if menu has items
	$items = wp_get_nav_menu_items($menu_id);

	if (empty($items)) {
		// Add Contact Us link
		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' => 'Contact Us',
			'menu-item-url' => $contact_us,
			'menu-item-status' => 'publish',
			'menu-item-type' => 'custom',
		));

		// Add Visitor Information link
		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' => 'Visitor Information',
			'menu-item-url' => $visitor_info,
			'menu-item-status' => 'publish',
			'menu-item-type' => 'custom',
		));

		// Add Employment Opportunities link
		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' => 'Employment',
			'menu-item-url' => $employment,
			'menu-item-status' => 'publish',
			'menu-item-type' => 'custom',
		));

		// Add Registration Portal Login link
		wp_update_nav_menu_item($menu_id, 0, array(
			'menu-item-title' => 'Learning Stream',
			'menu-item-url' => $registration_portal_login,
			'menu-item-status' => 'publish',
			'menu-item-type' => 'custom',
		));
	}
});