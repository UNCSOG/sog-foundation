<?php
/**
 * Frontend hook wiring for header and footer injection.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand\Core;

use SOG\Rebrand\Admin\Settings;
use SOG\Rebrand\Frontend\Footer_Renderer;
use SOG\Rebrand\Frontend\Header_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hook_Manager {
	/**
	 * Settings module.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Header renderer.
	 *
	 * @var Header_Renderer
	 */
	private $header_renderer;

	/**
	 * Footer renderer.
	 *
	 * @var Footer_Renderer
	 */
	private $footer_renderer;

	/**
	 * Constructor.
	 *
	 * @param Settings        $settings Settings module.
	 * @param Header_Renderer $header_renderer Header renderer.
	 * @param Footer_Renderer $footer_renderer Footer renderer.
	 */
	public function __construct( Settings $settings, Header_Renderer $header_renderer, Footer_Renderer $footer_renderer ) {
		$this->settings        = $settings;
		$this->header_renderer = $header_renderer;
		$this->footer_renderer = $footer_renderer;
	}

	/**
	 * Register hook loader callbacks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp', array( $this, 'attach_render_hooks' ) );
	}

	/**
	 * Attach header/footer output to configured hooks.
	 *
	 * @return void
	 */
	public function attach_render_hooks(): void {
		$settings = $this->settings->get_settings();

		if ( ! empty( $settings['header_enabled'] ) && ! empty( $settings['header_hook'] ) ) {
			add_action(
				$settings['header_hook'],
				array( $this->header_renderer, 'render' ),
				(int) $settings['header_hook_priority']
			);
		}

		if ( ! empty( $settings['footer_enabled'] ) && ! empty( $settings['footer_hook'] ) ) {
			add_action(
				$settings['footer_hook'],
				array( $this->footer_renderer, 'render' ),
				(int) $settings['footer_hook_priority']
			);
		}
	}
}
