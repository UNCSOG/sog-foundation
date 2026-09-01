<?php
/**
 * The GTM admin core class.
 *
 * @link    http://wpmudev.com
 * @since   3.3.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Tag_Manager
 */

namespace Beehive\Core\Modules\Google_Tag_Manager;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Data\Locale;
use Beehive\Core\Controllers\Menu;
use Beehive\Core\Utils\Abstracts\Base;
use Beehive\Core\Controllers\Capability;

/**
 * Class Admin
 *
 * @package Beehive\Core\Modules\Google_Analytics
 */
class Admin extends Base {

	/**
	 * Register all the hooks related to module.
	 *
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function init() {
		// Settings menu in dashboard.
		add_filter( 'beehive_main_menu_items', array( $this, 'admin_menu' ), 2 );

		// Add i18n strings for the locale.
		add_filter( 'beehive_i18n_get_locale_scripts', array( $this, 'setup_i18n' ), 10, 2 );

		// Admin admin class to our page.
		add_filter( 'beehive_admin_body_classes_is_plugin_admin', array( $this, 'admin_body_class' ) );
	}

	/**
	 * Register admin submenu for the settings page.
	 *
	 * @param array $menu_items Menu items.
	 *
	 * @since 3.3.7
	 *
	 * @return array
	 */
	public function admin_menu( $menu_items ) {
		// Set the admin menu for GA.
		$menu_items['beehive-google-tag-manager'] = array(
			'page_title' => __( 'Google Tag Manager Settings', 'ga_trans' ),
			'menu_title' => __( 'Google Tag Manager', 'ga_trans' ),
			'cap'        => Capability::SETTINGS_CAP,
			'callback'   => array( Views\Admin::instance(), 'settings' ),
		);

		return $menu_items;
	}

	/**
	 * Add localized strings that can be used in JavaScript.
	 *
	 * @param array  $strings Existing strings.
	 * @param string $script  Script name.
	 *
	 * @since 3.3.0
	 *
	 * @return array
	 */
	public function setup_i18n( $strings, $script ) {
		switch ( $script ) {
			case 'beehive-admin':
				// Add strings.
				$strings = array_merge_recursive(
					$strings,
					Locale::welcome(),
					Data\Locale::common(),
					Data\Locale::account(),
					Data\Locale::settings()
				);
				break;
		}

		return $strings;
	}

	/**
	 * Add Beehive admin body class to plugin statistics page.
	 *
	 * @param bool $include Should add class.
	 *
	 * @since 3.3.0
	 *
	 * @return bool
	 */
	public function admin_body_class( $include ) {
		// Enqueue stats widget assets.
		if ( Helper::is_gtm_settings() ) {
			$include = true;
		}

		return $include;
	}
}
