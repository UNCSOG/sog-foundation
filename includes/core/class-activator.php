<?php
/**
 * Plugin activation routines.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand\Core;

use SOG\Rebrand\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {
	/**
	 * Seed the default plugin settings on activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$settings = new Settings();

		if ( false === get_option( Settings::OPTION_NAME, false ) ) {
			add_option( Settings::OPTION_NAME, $settings->get_defaults() );
		}
	}
}
