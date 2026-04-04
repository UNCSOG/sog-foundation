<?php
/**
 * Main plugin bootstrapper.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand;

use SOG\Rebrand\Admin\Settings;
use SOG\Rebrand\Core\Assets;
use SOG\Rebrand\Core\Display_Rules;
use SOG\Rebrand\Core\Hook_Manager;
use SOG\Rebrand\Frontend\Footer_Renderer;
use SOG\Rebrand\Frontend\Header_Renderer;
use SOG\Rebrand\Menus\Menu_Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Track whether the plugin has booted.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot plugin modules and hook registration.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$settings      = new Settings();
		$display_rules = new Display_Rules( $settings );
		$assets        = new Assets( $settings, $display_rules );
		$header        = new Header_Renderer( $settings, $display_rules );
		$footer        = new Footer_Renderer( $settings, $display_rules );
		$menus         = new Menu_Registrar();
		$hooks         = new Hook_Manager( $settings, $header, $footer );

		$settings->register();
		$assets->register();
		$menus->register();
		$hooks->register();

		$this->booted = true;
	}
}
