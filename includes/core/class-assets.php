<?php
/**
 * Asset registration and enqueue logic.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand\Core;

use SOG\Rebrand\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {
	/**
	 * Settings module.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Display rules module.
	 *
	 * @var Display_Rules
	 */
	private $display_rules;

	/**
	 * Constructor.
	 *
	 * @param Settings      $settings Settings module.
	 * @param Display_Rules $display_rules Display rules module.
	 */
	public function __construct( Settings $settings, Display_Rules $display_rules ) {
		$this->settings      = $settings;
		$this->display_rules = $display_rules;
	}

	/**
	 * Register asset hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue frontend CSS and JS when the plugin may render output.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		$settings = $this->settings->get_settings();

		if ( ! $this->display_rules->should_display_header() && ! $this->display_rules->should_display_footer() ) {
			return;
		}

		if ( empty( $settings['header_enabled'] ) && empty( $settings['footer_enabled'] ) ) {
			return;
		}

		if ( ! empty( $settings['load_frontend_fonts'] ) ) {
			wp_enqueue_style(
				'sog-unc-rebrand-fonts',
				$this->get_font_stylesheet_url(),
				array(),
				null
			);
		}

		wp_enqueue_style(
			'sog-unc-rebrand-frontend',
			SOG_UNC_REBRAND_URL . 'assets/css/frontend.css',
			array(),
			SOG_UNC_REBRAND_VERSION
		);

		wp_enqueue_style(
			'sog-unc-rebrand-frontend-hoyt',
			SOG_UNC_REBRAND_URL . 'assets/css/frontend-hoyt.css',
			array( 'sog-unc-rebrand-frontend' ),
			SOG_UNC_REBRAND_VERSION
		);

		$variant_stylesheets = array(
			'simple-text',
			'simple-text-grid',
			'simple-text-vertical',
			'simple-text-vertical-line',
			'simple-text-vertical-line-alternate',
			'simple-text-vertical-line-special-btn-navigation-name-inline',
			'simple-text-vertical-nav-search',
			'simple-text-vertical-no-navigation',
			'simple-text-vertical-search',
			'simple-text-vertical-social-give',
			'simple-text-vertical-social-navigation',
			'simple-text-vertical-social-no-navigation',
		);

		foreach ( $variant_stylesheets as $variant ) {
			wp_enqueue_style(
				'sog-unc-rebrand-frontend-' . $variant,
				SOG_UNC_REBRAND_URL . 'assets/css/frontend-' . $variant . '.css',
				array( 'sog-unc-rebrand-frontend-hoyt' ),
				SOG_UNC_REBRAND_VERSION
			);
		}

		wp_enqueue_script(
			'sog-unc-rebrand-frontend',
			SOG_UNC_REBRAND_URL . 'assets/js/frontend.js',
			array(),
			SOG_UNC_REBRAND_VERSION,
			true
		);
	}

	/**
	 * Build the external stylesheet URL for the plugin font families.
	 *
	 * @return string
	 */
	private function get_font_stylesheet_url(): string {
		return 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@500;700&display=swap';
	}

	/**
	 * Enqueue admin scripts and core WordPress UI assets on the plugin settings screen.
	 *
	 * @param string $hook_suffix Current admin screen suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, 'sog-unc-rebrand' ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'sog-unc-rebrand-admin',
			SOG_UNC_REBRAND_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			SOG_UNC_REBRAND_VERSION,
			true
		);
	}
}
