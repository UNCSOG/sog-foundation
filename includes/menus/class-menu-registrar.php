<?php
/**
 * Register plugin-owned menu locations.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand\Menus;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Menu_Registrar {
	/**
	 * Register menu location hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_menu_locations' ) );
	}

	/**
	 * Register theme-agnostic nav menu locations owned by the plugin.
	 *
	 * @return void
	 */
	public function register_menu_locations(): void {
		register_nav_menus(
			array(
				'sog-rebrand-utility-bar'   => __( 'SOG Rebrand Utility Bar', 'sog-unc-rebrand' ),
				'sog-rebrand-header-main'   => __( 'SOG Rebrand Header Main', 'sog-unc-rebrand' ),
				'sog-rebrand-header-bottom' => __( 'SOG Rebrand Header Bottom', 'sog-unc-rebrand' ),
				'sog-rebrand-footer-1'        => __( 'SOG Rebrand Footer Column 1', 'sog-unc-rebrand' ),
				'sog-rebrand-footer-1-second' => __( 'SOG Rebrand Footer Column 1 (Second Menu)', 'sog-unc-rebrand' ),
				'sog-rebrand-footer-2'        => __( 'SOG Rebrand Footer Column 2', 'sog-unc-rebrand' ),
				'sog-rebrand-footer-2-second' => __( 'SOG Rebrand Footer Column 2 (Second Menu)', 'sog-unc-rebrand' ),
				'sog-rebrand-footer-3'        => __( 'SOG Rebrand Footer Column 3', 'sog-unc-rebrand' ),
				'sog-rebrand-footer-3-second' => __( 'SOG Rebrand Footer Column 3 (Second Menu)', 'sog-unc-rebrand' ),
				'sog-rebrand-footer-bottom'   => __( 'SOG Rebrand Footer Bottom', 'sog-unc-rebrand' ),
			)
		);
	}
}
