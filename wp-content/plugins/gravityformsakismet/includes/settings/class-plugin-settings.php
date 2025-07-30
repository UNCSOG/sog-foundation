<?php

namespace Gravity_Forms\Gravity_Forms_Akismet\Settings;

use Gravity_Forms\Gravity_Forms_Akismet\GF_Akismet;

/**
 * Defines the fields for the Forms > Settings > Akismet page.
 *
 * @since 1.1
 */
class Plugin_Settings {

	/**
	 * The current instance of the add-on.
	 *
	 * @since 1.1
	 *
	 * @var null|GF_Akismet
	 */
	private $add_on;

	/**
	 * Plugin_Settings constructor.
	 *
	 * @since 1.1
	 *
	 * @param GF_Akismet $add_on The current instance of the add-on.
	 */
	public function __construct( $add_on = null ) {
		$this->add_on = $add_on instanceof GF_Akismet ? $add_on : gf_akismet();
	}

	/**
	 * Returns the fields to be displayed on the Forms > Settings > Akismet page.
	 *
	 * @since 1.1
	 *
	 * @return array[]
	 */
	public function get_fields() {
		return array(
			array(
				'title'       => esc_html__( 'Akismet Settings', 'gravityformsakismet' ),
				'description' => sprintf(
					'<p>%1$s <a href="https://akismet.com/pricing/" target="_blank" >%2$s<span class="screen-reader-text">%3$s</span>&nbsp;<span class="gform-icon gform-icon--external-link"></span></a>.</p>',
					esc_html__( "Protect your form entries from spam using Akismet. Don't have an Akismet account?", 'gravityformsakismet' ),
					esc_html__( 'Sign up here', 'gravityformsakismet' ),
					esc_html__( '(opens in a new tab)', 'gravityformsakismet' )
				),
				'fields'      => array(
					array(
						'name'          => 'enabled',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Akismet Integration', 'gravityformsakismet' ),
						'default_value' => true,
						'save_callback' => function ( $field, $value ) {
							// Keeping the legacy core setting in sync in case the add-on is deactivated.
							$this->add_on->remove_disable_core_filter();
							update_option( 'rg_gforms_enable_akismet', (bool) $value );
							$this->add_on->add_disable_core_filter();

							return $value;
						},
					),
					array(
						'name'              => 'api_key',
						'label'             => esc_html__( 'API Key', 'gravityformsakismet' ),
						'type'              => 'text',
						'default_value'     => $this->add_on->get_akismet_plugin_api_key(),
						'required'          => true,
						'description'       => sprintf(
							'<p><a href="https://akismet.com/account/" target="_blank" >%1$s<span class="screen-reader-text">%2$s</span>&nbsp;<span class="gform-icon gform-icon--external-link"></span></a></p>',
							esc_html__( 'Click here to find your Akismet API Key', 'gravityformsakismet' ),
							esc_html__( '(opens in a new tab)', 'gravityformsakismet' )
						),
						'dependency'        => array(
							'live'   => true,
							'fields' => array(
								array(
									'field' => 'enabled',
								),
							),
						),
						'feedback_callback' => array( $this->add_on, 'verify_api_key' ),
					),
				),
			),
		);
	}

}
