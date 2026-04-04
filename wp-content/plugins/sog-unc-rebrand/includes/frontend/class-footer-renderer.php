<?php
/**
 * Footer rendering coordinator.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand\Frontend;

use SOG\Rebrand\Admin\Settings;
use SOG\Rebrand\Core\Display_Rules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Footer_Renderer {
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
	 * Render the plugin footer template.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! $this->display_rules->should_display_footer() ) {
			return;
		}

		$settings = $this->settings->get_settings();

		load_template(
			SOG_UNC_REBRAND_PATH . 'templates/footer.php',
			false,
			array(
				'settings' => $settings,
			)
		);
	}
}
