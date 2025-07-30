<?php
/**
 * Object responsible for organizing and constructing the plugin settings page.
 *
 * @package Gravity_Forms\Gravity_Forms_RECAPTCHA\Settings
 */

namespace Gravity_Forms\Gravity_Forms_RECAPTCHA\Settings;

use Gravity_Forms\Gravity_Forms_RECAPTCHA\GF_RECAPTCHA;
use Gravity_Forms\Gravity_Forms\Settings\Fields\Text;
use Gravity_Forms\Gravity_Forms_RECAPTCHA\RECAPTCHA_API;
use Gravity_Forms\Gravity_Forms_RECAPTCHA\Token_Verifier;
use GF_Field_CAPTCHA;
use GFCommon;
use GFForms;

/**
 * Class Plugin_Settings
 *
 * @since   1.0
 * @package Gravity_Forms\Gravity_Forms_RECAPTCHA\Settings
 */
class Plugin_Settings {
	/**
	 * Add-on instance.
	 *
	 * @var GF_RECAPTCHA
	 */
	private $addon;

	/**
	 * Token_Verifier instance.
	 *
	 * @var Token_Verifier
	 */
	private $token_verifier;

	/**
	 * Plugin_Settings constructor.
	 *
	 * @since 1.0
	 *
	 * @param GF_RECAPTCHA   $addon          GF_RECAPTCHA instance.
	 * @param Token_Verifier $token_verifier Instance of the Token_Verifier class.
	 */
	public function __construct( $addon, $token_verifier ) {
		$this->addon          = $addon;
		$this->token_verifier = $token_verifier;
	}

	/**
	 * Get the plugin settings fields.
	 *
	 * @since 1.0
	 * @since 1.7.0 Added support for reCAPTCHA v3 enterprise.
	 * @see   GF_RECAPTCHA::plugin_settings_fields()
	 *
	 * @return array
	 */
	public function get_fields() {

		// If we're in a legacy environment with both v2 and v3 keys, show the traditional settings.
		if ( $this->use_legacy_settings() ) {
			return array(
				$this->get_description_fields(),
				$this->get_v3_fields(),
				$this->get_v2_fields(),
				$this->get_disconnect_button(),
			);
		}

		// If there are no keys and no connection type, show the key type selection.
		if ( ! $this->has_populated_keys() && ! rgget( 'connection_type' ) ) {
			return $this->get_recaptcha_key_type();
		}

		$plugin_settings = $this->addon->get_plugin_settings();

		// Show the relevant fields based on the connection type.
		switch ( rgar( $plugin_settings, 'connection_type' ) ) {
			case 'enterprise':
				// Force manual reconnection when a WP_Error during refresh bug (versions 1.7-1.9) erased these values.
				if ( empty( $plugin_settings['access_token'] ) || empty( $plugin_settings['refresh_token'] ) ) {
					return $this->get_recaptcha_key_type();
				}

				return array(
					$this->get_description_fields( 'enterprise' ),
					$this->get_v3_enterprise_fields(),
					$this->get_disconnect_button(),
				);
			case 'v2':
				return array(
					$this->get_description_fields( 'v2' ),
					$this->get_v2_fields(),
					$this->get_change_connection_button(),
				);
			case 'classic':
				return array(
					$this->get_description_fields( 'classic' ),
					$this->get_v3_fields(),
					$this->get_change_connection_button(),
				);
			default:
				return $this->get_recaptcha_key_type();
		}
	}

	/**
	 * Show the reCAPTCHA Connection Type selection.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	private function get_recaptcha_key_type() {

		$legacy_only                 = version_compare( GFForms::$version, '2.9.0-dev-1', '<' );
		$enterprise_card_description = $legacy_only ?
			esc_html__( 'reCAPTCHA v3 Enterprise keys require Gravity Forms 2.9 or later. Upgrade your Gravity Forms installation to get access to this connection method', 'gravityformsrecaptcha' ) :
			esc_html__( 'Modern reCAPTCHA keys using the enterprise API. All newly created v3 keys are enterprise keys. Unlike classic keys, enterprise keys require only a site key to function.', 'gravityformsrecaptcha' );

		$card_choices = array(
			array(
				'label'       => esc_html__( 'v3 Enterprise', 'gravityformsrecaptcha' ),
				'value'       => 'enterprise',
				'disabled'    => $legacy_only,
				'icon'        => $this->addon->get_base_url() . '/images/v3enterprise.png',
				'tag'         => esc_html__( 'Recommended', 'gravityformsrecaptcha' ),
				'color'       => 'orange',
				'title'       => esc_html__( 'reCAPTCHA v3 Enterprise', 'gravityformsrecaptcha' ),
				'description' => $enterprise_card_description,
			),
			array(
				'label'       => esc_html__( 'v2', 'gravityformsrecaptcha' ),
				'value'       => 'v2',
				'icon'        => $this->addon->get_base_url() . '/images/v3.png',
				'tag'         => esc_html__( 'Challenge based', 'gravityformsrecaptcha' ),
				'color'       => 'orange',
				'title'       => esc_html__( 'reCAPTCHA v2', 'gravityformsrecaptcha' ),
				'description' => esc_html__( 'Challenge based keys using the v2 API. This method allows a reCAPTCHA challenge field to be inserted into your forms.', 'gravityformsrecaptcha' ),
			),
			array(
				'label'       => esc_html__( 'v3 Classic', 'gravityformsrecaptcha' ),
				'value'       => 'classic',
				'icon'        => $this->addon->get_base_url() . '/images/v3.png',
				'tag'         => esc_html__( 'Legacy', 'gravityformsrecaptcha' ),
				'color'       => 'blue-ribbon',
				'title'       => esc_html__( 'reCAPTCHA v3 Classic', 'gravityformsrecaptcha' ),
				'description' => esc_html__( 'Legacy v3 keys. New classic keys can no longer be created, but you can use an existing site key and secret.', 'gravityformsrecaptcha' ),
			),
		);

		return array(
			array(
				'title'       => esc_html__( 'Select reCAPTCHA Connection Type', 'gravityformsrecaptcha' ),
				'description' => esc_html__( 'Select which type of keys to use for reCAPTCHA.', 'gravityformsrecaptcha' ),
				'fields'      => array(
					array(
						'name'     => 'nonce',
						'type'     => 'nonce_connect',
						'readonly' => true,
					),
					array(
						'name'     => 'action',
						'type'     => 'hidden',
						'readonly' => true,
						'value'    => 'recaptcha_setup',
						'id'       => 'recaptcha_setup',
					),
					array(
						'type'  => 'save',
						'value' => esc_html__( 'Continue &rarr;', 'gravityformsrecaptcha' ),
						'id'    => 'recaptcha-connect',
					),

				),
			),
			array(
				'fields' => array(
					array(
						'name'    => 'connection_type',
						'type'    => 'card',
						'choices' => $card_choices,
					),
				),
			),
		);
	}

	/**
	 * Check if the keys have been populated.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	private function has_populated_keys() {
		$plugin_settings = $this->addon->get_plugin_settings();
		if ( ! empty( rgar( $plugin_settings, 'site_key_v3' ) ) ||
			! empty( rgar( $plugin_settings, 'secret_key_v3' ) ) ||
			! empty( rgar( $plugin_settings, 'site_key_v2' ) ) ||
			! empty( rgar( $plugin_settings, 'secret_key_v2' ) ) ||
			! empty( rgar( $plugin_settings, 'site_key_v3_enterprise' ) ) ||
			! empty( rgar( $plugin_settings, 'access_token' ) )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Determine if we should show the legacy settings screen.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	private function use_legacy_settings() {
		$plugin_settings = $this->addon->get_plugin_settings();
		if ( ( ! empty( rgar( $plugin_settings, 'site_key_v3' ) ) ||
			! empty( rgar( $plugin_settings, 'secret_key_v3' ) ) ||
			! empty( rgar( $plugin_settings, 'site_key_v2' ) ) ||
			! empty( rgar( $plugin_settings, 'secret_key_v2' ) )
		) && empty( rgar( $plugin_settings, 'connection_type' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets any custom plugin settings, ensuring they contain the latest values from the constants.
	 *
	 * @since 1.0
	 *
	 * @param array $settings Add-on's parent plugin settings.
	 *
	 * @return array
	 */
	public function get_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$site_key = $this->get_recaptcha_key( 'site_key_v3', true );
		if ( $site_key ) {
			$settings['site_key_v3'] = $site_key;
		}

		$secret_key = $this->get_recaptcha_key( 'secret_key_v3', true );
		if ( $secret_key ) {
			$settings['secret_key_v3'] = $secret_key;
		}

		return array_merge(
			$settings,
			array(
				'site_key_v2'   => get_option( 'rg_gforms_captcha_public_key' ),
				'secret_key_v2' => get_option( 'rg_gforms_captcha_private_key' ),
				'type_v2'       => get_option( 'rg_gforms_captcha_type' ),
			)
		);
	}

	/**
	 * Handles updating of custom plugin settings.
	 *
	 * @since 1.0
	 *
	 * @param array $settings Update the v2 settings.
	 */
	public function update_settings( $settings ) {
		update_option( 'rg_gforms_captcha_public_key', rgar( $settings, 'site_key_v2' ) );
		update_option( 'rg_gforms_captcha_private_key', rgar( $settings, 'secret_key_v2' ) );
		update_option( 'rg_gforms_captcha_type', rgar( $settings, 'type_v2' ) );
	}

	/**
	 * Get the description section for the plugin settings.
	 *
	 * @param string $connection_type The connection type.
	 *
	 * @since 1.0
	 * @since 1.7.0 Updated to show the correct description based on the connection type.
	 *
	 * @return array
	 */
	private function get_description_fields( $connection_type = null ) {
		return array(
			'id'          => 'gravityformsrecaptcha_description',
			'title'       => esc_html__( 'reCAPTCHA Settings', 'gravityformsrecaptcha' ),
			'description' => $this->get_settings_intro_description( $connection_type ),
			'fields'      => array(
				array(
					'type' => 'html',
				),
			),
		);
	}

	/**
	 * Get the plugin settings fields for reCAPTCHA v3.
	 *
	 * @since 1.0
	 * @return array
	 */
	private function get_v3_fields() {
		$site_key        = $this->get_recaptcha_key( 'site_key_v3', true );
		$secret_key      = $this->get_recaptcha_key( 'secret_key_v3', true );
		$connection_type = $this->addon->get_connection_type();

		return array(
			'id'     => 'gravityformsrecaptcha_v3',
			'title'  => esc_html__( 'reCAPTCHA v3 Classic', 'gravityformsrecaptcha' ),
			'fields' => array(
				array(
					'name'              => 'site_key_v3',
					'label'             => esc_html__( 'Site Key', 'gravityformsrecaptcha' ),
					'type'              => 'text',
					'feedback_callback' => array( $this, 'v3_keys_status_feedback_callback' ),
					'readonly'          => empty( $site_key ) ? '' : 'readonly',
					'after_input'       => $this->get_constant_message( $site_key, 'GF_RECAPTCHA_V3_SITE_KEY' ),
				),
				array(
					'name'              => 'secret_key_v3',
					'label'             => esc_html__( 'Secret Key', 'gravityformsrecaptcha' ),
					'type'              => 'text',
					'feedback_callback' => array( $this, 'v3_keys_status_feedback_callback' ),
					'readonly'          => empty( $secret_key ) ? '' : 'readonly',
					'after_input'       => $this->get_constant_message( $secret_key, 'GF_RECAPTCHA_V3_SECRET_KEY' ),
				),
				array(
					'name'                => 'score_threshold_v3',
					'label'               => esc_html__( 'Score Threshold', 'gravityformsrecaptcha' ),
					'description'         => $this->get_score_threshold_description(),
					'default_value'       => 0.5,
					'type'                => 'text',
					'input_type'          => 'number',
					'step'                => '0.01',
					'min'                 => '0.0',
					'max'                 => '1.0',
					'validation_callback' => array( $this, 'validate_score_threshold_v3' ),
				),
				array(
					'name'     => 'connection_type',
					'type'     => 'hidden',
					'readonly' => true,
					'value'    => $connection_type,
					'id'       => 'connection_type',
				),
				array(
					'name'        => 'disable_badge_v3',
					'label'       => esc_html__( 'Disable Google reCAPTCHA Badge', 'gravityformsrecaptcha' ),
					'description' => esc_html__( 'By default reCAPTCHA v3 displays a badge on every page of your site with links to the Google terms of service and privacy policy. You are allowed to hide the badge as long as you include the reCAPTCHA branding and links visibly in the user flow.', 'gravityformsrecaptcha' ),
					'type'        => 'checkbox',
					'choices'     => array(
						array(
							'name'  => 'disable_badge_v3',
							'label' => esc_html__( 'I have added the reCAPTCHA branding, terms of service and privacy policy to my site. ', 'gravityformsrecaptcha' ),
						),
					),
				),
				array(
					'name'          => 'recaptcha_keys_status_v3',
					'type'          => 'checkbox',
					'default_value' => $this->get_recaptcha_key( 'recaptcha_keys_status_v3' ),
					'hidden'        => true,
					'choices'       => array(
						array(
							'type' => 'checkbox',
							'name' => 'recaptcha_keys_status_v3',
						),
					),
				),
				array(
					'name' => 'action',
					'type' => 'recaptcha_action',
				),
			),
		);
	}

	/**
	 * Get the plugin settings fields for reCAPTCHA v3 enterprise.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	private function get_v3_enterprise_fields() {
		$site_key        = $this->get_recaptcha_key( 'site_key_v3_enterprise', true );
		$connection_type = $this->addon->get_connection_type();

		return array(
			'id'     => 'gravityformsrecaptcha_v3',
			'title'  => esc_html__( 'reCAPTCHA v3 Enterprise', 'gravityformsrecaptcha' ),
			'fields' => array(
				array(
					'name'  => 'recaptcha_enterprise_fields',
					'label' => esc_html__( 'reCAPTCHA Project ID', 'gravityformsrecaptcha' ),
					'type'  => 'recaptcha_enterprise_fields',
				),
				array(
					'name'                => 'score_threshold_v3',
					'label'               => esc_html__( 'Score Threshold', 'gravityformsrecaptcha' ),
					'description'         => $this->get_score_threshold_description(),
					'default_value'       => 0.5,
					'type'                => 'text',
					'input_type'          => 'number',
					'step'                => '0.01',
					'min'                 => '0.0',
					'max'                 => '1.0',
					'validation_callback' => array( $this, 'validate_score_threshold_v3' ),
				),
				array(
					'name'     => 'connection_type',
					'type'     => 'hidden',
					'readonly' => true,
					'value'    => $connection_type,
					'id'       => 'connection_type',
				),
				array(
					'name'        => 'disable_badge_v3',
					'label'       => esc_html__( 'Disable Google reCAPTCHA Badge', 'gravityformsrecaptcha' ),
					'description' => esc_html__( 'By default reCAPTCHA v3 displays a badge on every page of your site with links to the Google terms of service and privacy policy. You are allowed to hide the badge as long as you include the reCAPTCHA branding and links visibly in the user flow.', 'gravityformsrecaptcha' ),
					'type'        => 'checkbox',
					'choices'     => array(
						array(
							'name'  => 'disable_badge_v3',
							'label' => esc_html__( 'I have added the reCAPTCHA branding, terms of service and privacy policy to my site. ', 'gravityformsrecaptcha' ),
						),
					),
				),
				array(
					'name'          => 'recaptcha_keys_status_v3',
					'type'          => 'checkbox',
					'default_value' => $this->get_recaptcha_key( 'recaptcha_keys_status_v3' ),
					'hidden'        => true,
					'choices'       => array(
						array(
							'type' => 'checkbox',
							'name' => 'recaptcha_keys_status_v3',
						),
					),
				),
				array(
					'name' => 'nonce',
					'type' => 'nonce_connect',
				),
				array(
					'name' => 'action',
					'type' => 'recaptcha_action',
				),
			),
		);
	}

	/**
	 * Returns the setting info message to be displayed when the value is defined using a constant.
	 *
	 * @since 1.3
	 *
	 * @param string $value    The value.
	 * @param string $constant The constant name.
	 *
	 * @return string
	 */
	public function get_constant_message( $value, $constant ) {
		if ( empty( $value ) ) {
			return '';
		}

		// translators: %s is the constant name.
		return '<div class="alert gforms_note_info">' . sprintf( esc_html__( 'Value defined using the %s constant.', 'gravityformsrecaptcha' ), $constant ) . '</div>';
	}

	/**
	 * Get the plugin settings fields for reCAPTCHA v2.
	 *
	 * @since 1.0
	 * @return array
	 */
	private function get_v2_fields() {
		$connection_type = $this->addon->get_connection_type();

		return array(
			'id'     => 'gravityformsrecaptcha_v2',
			'title'  => esc_html__( 'reCAPTCHA v2', 'gravityformsrecaptcha' ),
			'fields' => array(
				array(
					'name'              => 'site_key_v2',
					'label'             => esc_html__( 'Site Key', 'gravityformsrecaptcha' ),
					'tooltip'           => gform_tooltip( 'settings_recaptcha_public', null, true ),
					'type'              => 'text',
					'feedback_callback' => array( $this, 'validate_key_v2' ),
				),
				array(
					'name'              => 'secret_key_v2',
					'label'             => esc_html__( 'Secret Key', 'gravityformsrecaptcha' ),
					'tooltip'           => gform_tooltip( 'settings_recaptcha_private', null, true ),
					'type'              => 'text',
					'feedback_callback' => array( $this, 'validate_key_v2' ),
				),
				array(
					'name'          => 'type_v2',
					'label'         => esc_html__( 'Type', 'gravityformsrecaptcha' ),
					'tooltip'       => gform_tooltip( 'settings_recaptcha_type', null, true ),
					'type'          => 'radio',
					'horizontal'    => true,
					'default_value' => 'checkbox',
					'choices'       => array(
						array(
							'label' => esc_html__( 'Checkbox', 'gravityformsrecaptcha' ),
							'value' => 'checkbox',
						),
						array(
							'label' => esc_html__( 'Invisible', 'gravityformsrecaptcha' ),
							'value' => 'invisible',
						),
					),
				),
				array(
					'name'     => 'connection_type',
					'type'     => 'hidden',
					'readonly' => true,
					'value'    => $connection_type,
					'id'       => 'connection_type',
				),
				array(
					'name'                => 'reset_v2',
					'label'               => esc_html__( 'Validate Keys', 'gravityformsrecaptcha' ),
					'type'                => 'recaptcha_reset',
					'callback'            => array( $this, 'handle_recaptcha_v2_reset' ),
					'hidden'              => true,
					'validation_callback' => function( $field, $value ) {

						// If reCAPTCHA key is empty, exit.
						if ( rgblank( $value ) ) {
							return;
						}

						$values = $this->addon->get_settings_renderer()->get_posted_values();

						// Get public, private keys, API response.
						$public_key  = rgar( $values, 'site_key_v2' );
						$private_key = rgar( $values, 'secret_key_v2' );
						$response    = rgpost( 'g-recaptcha-response' );

						// If keys and response are provided, verify and save.
						if ( $public_key && $private_key && $response ) {
							// Log public, private keys, API response.
							// @codingStandardsIgnoreStart - print_r okay for logging.
							GFCommon::log_debug( __METHOD__ . '(): reCAPTCHA Site Key:' . print_r( $public_key, true ) );
							GFCommon::log_debug( __METHOD__ . '(): reCAPTCHA Secret Key:' . print_r( $private_key, true ) );
							GFCommon::log_debug( __METHOD__ . '(): reCAPTCHA Response:' . print_r( $response, true ) );

							// Verify response.
							$recaptcha          = new GF_Field_CAPTCHA();
							$recaptcha_response = $recaptcha->verify_recaptcha_response( $response, $private_key );

							// Log verification response.
							GFCommon::log_debug( __METHOD__ . '(): reCAPTCHA verification response:' . print_r( $recaptcha_response, true ) );
							// @codingStandardsIgnoreEnd

							// If response is false, return validation error.
							if ( $recaptcha_response === false ) {
								$field->set_error( __( 'reCAPTCHA keys are invalid.', 'gravityformsrecaptcha' ) );
							}

							// Save status.
							update_option( 'gform_recaptcha_keys_status', $recaptcha_response );
						} else {
							// Delete existing status.
							delete_option( 'gform_recaptcha_keys_status' );
						}
					},
				),
				array(
					'name' => 'action',
					'type' => 'recaptcha_action',
				),
			),
		);
	}

	/**
	 * Convert an array containing arrays of translated strings into HTML paragraphs.
	 *
	 * @param array $paragraphs An array of arrays containing translated text.
	 *
	 * @since 1.0
	 * @return string
	 */
	private function get_description( array $paragraphs ) {
		$description_text = array();

		foreach ( $paragraphs as $paragraph ) {
			$description_text[] = '<p>' . implode( ' ', $paragraph ) . '</p>';
		}

		return implode( '', $description_text );
	}

	/**
	 * The disconnect button field.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	private function get_disconnect_button() {
		return array(
			'id'     => 'gravityformsrecaptcha_disconnect',
			'title'  => esc_html__( 'Disconnect reCAPTCHA', 'gravityformsrecaptcha' ),
			'fields' => array(
				array(
					'type'  => 'disconnect_recaptcha',
					'value' => esc_html__( 'Disconnect', 'gravityformsrecaptcha' ),
					'id'    => 'recaptcha-disconnect',
				),
			),
		);
	}

	/**
	 * The change connection type button field.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	private function get_change_connection_button() {
		return array(
			'id'     => 'gravityformsrecaptcha_change_connection',
			'title'  => esc_html__( 'Change reCAPTCHA Connection Type', 'gravityformsrecaptcha' ),
			'fields' => array(
				array(
					'type'  => 'change_connection_type',
					'value' => esc_html__( 'Change Connection Type', 'gravityformsrecaptcha' ),
					'id'    => 'recaptcha-change-connection',
				),
			),
		);
	}

	/**
	 * Get the contents of the description field.
	 *
	 * @param string $connection_type The connection type.
	 *
	 * @since 1.0
	 * @since 1.7.0 Updated to show the correct description based on the connection type.
	 *
	 * @return array
	 */
	private function get_settings_intro_description( $connection_type = null ) {
		$description = array();

		$description[] = array(
			esc_html__( 'Google reCAPTCHA is a free anti-spam service that protects your website from fraud and abuse.', 'gravityformsrecaptcha' ),
			esc_html__( 'By adding reCAPTCHA to your forms, you can deter automated software from submitting form entries, while still ensuring a user-friendly experience for real people.', 'gravityformsrecaptcha' ),
		);

		if ( ! $connection_type ) {
			$description[] = array(
				esc_html__( 'Gravity Forms integrates with three types of Google reCAPTCHA.', 'gravityformsrecaptcha' ),
				'<ul><li>',
				esc_html__( 'reCAPTCHA v3 - Adds a script to every page of your site and uploads form content for processing by Google.', 'gravityformsrecaptcha' ),
				esc_html__( 'All submissions are accepted and suspicious submissions are marked as spam.', 'gravityformsrecaptcha' ),
				esc_html__( 'When reCAPTCHA v3 is configured, it is enabled automatically on all forms by default. It can be disabled for specific forms in the form settings.', 'gravityformsrecaptcha' ),
				'</li><li>',
				esc_html__( 'reCAPTCHA v2 (Invisible) - Displays a badge on your form and will present a challenge to the user if the activity is suspicious e.g. select the traffic lights.', 'gravityformsrecaptcha' ),
				esc_html__( 'Please note, only v2 keys are supported and checkbox keys are not compatible with invisible reCAPTCHA.', 'gravityformsrecaptcha' ),
				esc_html__( 'To activate reCAPTCHA v2 on your form, simply add the CAPTCHA field in the form editor.', 'gravityformsrecaptcha' ),
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( 'https://docs.gravityforms.com/captcha/' ),
					__( 'Read more about reCAPTCHA.', 'gravityformsrecaptcha' )
				),
				'</li><li>',
				esc_html__( 'reCAPTCHA v2 (Checkbox) - Requires a user to click a checkbox to indicate that they are not a robot and displays a challenge if the activity is suspicious', 'gravityformsrecaptcha' ),
				'</li></ul>',
			);
		} elseif ( $connection_type === 'enterprise' ) {
			$description[] = array(
				esc_html__( 'v3 enterprise reCAPTCHA adds a script to every page of your site and uploads form content for processing by Google.', 'gravityformsrecaptcha' ),
				esc_html__( 'A site secret is not required since you are authenticated by OAuth.', 'gravityformsrecaptcha' ),
				esc_html__( 'Enterprise reCAPTCHA gives you much greater control over how submissions are handled via the Cloud Console in your Google Cloud Project.', 'gravityformsrecaptcha' ),
				esc_html__( 'All submissions are accepted and suspicious submissions are marked as spam.', 'gravityformsrecaptcha' ),
				esc_html__( 'When reCAPTCHA v3 is configured, it is enabled automatically on all forms by default. It can be disabled for specific forms in the form settings.', 'gravityformsrecaptcha' ),
			);
		} elseif ( $connection_type === 'v2' ) {
			$description[] = array(
				esc_html__( 'reCAPTCHA v2 (challenge-based) can run in invisible mode where a user will receive a challenge only if Google sees suspicious behavior, or with a checkbox, where the user will click a checkbox to indicate they are not a robot.', 'gravityformsrecaptcha' ),
				esc_html__( 'Please note, only v2 keys are supported and checkbox keys are not compatible with invisible reCAPTCHA.', 'gravityformsrecaptcha' ),
				esc_html__( 'To activate reCAPTCHA v2 on your form, simply add the CAPTCHA field in the form editor.', 'gravityformsrecaptcha' ),
			);
		} elseif ( $connection_type === 'classic' ) {
			$description[] = array(
				esc_html__( 'v3 Classic reCAPTCHA Adds a script to every page of your site and uploads form content for processing by Google.', 'gravityformsrecaptcha' ),
				esc_html__( 'All submissions are accepted and suspicious submissions are marked as spam.', 'gravityformsrecaptcha' ),
				esc_html__( 'When reCAPTCHA v3 is configured, it is enabled automatically on all forms by default. It can be disabled for specific forms in the form settings.', 'gravityformsrecaptcha' ),
			);
		}

		$description[] = array(
			esc_html__( 'For more information on reCAPTCHA, which version is right for you, and how to add it to your forms,', 'gravityformsrecaptcha' ),
			sprintf(
				'<a target="_blank" href="%s">%s</a>',
				esc_url( 'https://docs.gravityforms.com/captcha/' ),
				esc_html__( 'check out our documentation.', 'gravityformsrecaptcha' )
			),
		);

		return $this->get_description( $description );
	}

	/**
	 * Get the description for the score threshold.
	 *
	 * @since 1.0
	 * @return string
	 */
	private function get_score_threshold_description() {
		$description = array(
			array(
				esc_html__( 'reCAPTCHA v3 returns a score (1.0 is very likely a good interaction, 0.0 is very likely a bot).', 'gravityformsrecaptcha' ),
				esc_html__( 'If the score is less than or equal to this threshold, the form submission will be sent to spam.', 'gravityformsrecaptcha' ),
				esc_html__( 'The default threshold is 0.5.', 'gravityformsrecaptcha' ),
				sprintf(
					'<a target="_blank" href="%s">Learn about reCAPTCHA.</a>',
					esc_url( 'https://docs.gravityforms.com/captcha/' )
				),
			),
		);

		return $this->get_description( $description );
	}

	/**
	 * Renders a reCAPTCHA verification field.
	 *
	 * @since 1.0
	 *
	 * @param array $props Field properties.
	 * @param bool  $echo  Output the field markup directly.
	 *
	 * @return string
	 */
	public function handle_recaptcha_v2_reset( $props = array(), $echo = true ) {
		// Add setup message.
		$html = sprintf(
			'<p id="gforms_checkbox_recaptcha_message" style="margin-bottom:10px;">%s</p>',
			esc_html__( 'Please complete the reCAPTCHA widget to validate your reCAPTCHA keys:', 'gravityforms' )
		);

		// Add reCAPTCHA container, reset input.
		$html .= '<div id="recaptcha"></div>';
		$html .= sprintf( '<input type="hidden" name="%s_%s" />', esc_attr( $this->addon->get_settings_renderer()->get_input_name_prefix() ), esc_attr( $props['name'] ) );

		return $html;
	}

	/**
	 * Validate that the score is a number between 0.0 and 1.0
	 *
	 * @since 1.0
	 *
	 * @param Base   $field Settings field object.
	 * @param string $score The submitted score threshold.
	 *
	 * @return bool
	 */
	public function validate_score_threshold_v3( $field, $score ) {
		if ( ! $field instanceof Text ) {
			$field->set_error( esc_html__( 'Unexpected field type.', 'gravityformsrecaptcha' ) );
			return false;
		}

		$field_value = (float) $score;

		if ( ! is_numeric( $score ) || $field_value < $field->min || $field_value > $field->max ) {
			$field->set_error( esc_html__( 'Score threshold must be between 0.0 and 1.0', 'gravityformsrecaptcha' ) );
			return false;
		}

		return true;
	}

	/**
	 * Returns true, false, or null, depending on the state of validation.
	 *
	 * The add-on framework will use this value to determine which field icon to display.
	 *
	 * @since 1.0
	 *
	 * @param null|string $key_status The status of the key (a string of 1 or 0).
	 * @param string      $value      The posted value of the field to validate.
	 *
	 * @return bool|null
	 */
	public function check_validated_status( $key_status, $value ) {
		if ( ! is_null( $key_status ) ) {
			return (bool) $key_status;
		}

		return rgblank( $value ) ? null : false;
	}

	/**
	 * Return strue, false, or null, depending on the state of validation.
	 *
	 * The add-on framework will use this value to determine which field icon to display.
	 *
	 * @since 1.0
	 *
	 * @param string $value The posted value of the field.
	 *
	 * @return bool|null
	 */
	public function validate_key_v2( $value ) {
		return $this->check_validated_status( get_option( 'gform_recaptcha_keys_status', null ), $value );
	}

	/**
	 * Feedback callback for v3 key validation.
	 *
	 * @param string $value The posted value.
	 *
	 * @return bool|null
	 */
	public function v3_keys_status_feedback_callback( $value ) {
		return $this->check_validated_status( $this->addon->get_setting( 'recaptcha_keys_status_v3' ), $value );
	}

	/**
	 * Ajax callback to verify the secret key on the plugin settings screen.
	 *
	 * @since 1.0
	 */
	public function verify_v3_keys() {
		$result = $this->token_verifier->verify(
			sanitize_text_field( rgpost( 'token' ) ),
			sanitize_text_field( rgpost( 'secret_key_v3' ) )
		);

		$this->apply_status_changes( $result );

		if ( is_wp_error( $result ) ) {
			$this->addon->log_debug( __METHOD__ . '(): Failed to verify reCAPTCHA token. ' . $result->get_error_message() );

			wp_send_json_error();
		}

		$this->addon->log_debug( __METHOD__ . '(): reCAPTCHA token successfully verified.' );

		$result->keys_status = $this->addon->get_plugin_setting( 'recaptcha_keys_status_v3' );

		wp_send_json_success( $result );
	}

	/**
	 * Applies updates to the verified key status when the site and secret v3 keys are saved.
	 *
	 * @since 1.0
	 *
	 * @param object $response The response of the secret key verification process.
	 */
	private function apply_status_changes( $response ) {
		$posted_keys = $this->get_posted_keys();

		// Set the updated status of the keys.
		$posted_keys['recaptcha_keys_status_v3'] = ( ! is_wp_error( $response ) && $response->success === true ) ? '1' : '0';

		$this->addon->update_plugin_settings(
			array_merge(
				$this->addon->get_plugin_settings(),
				$posted_keys
			)
		);
	}

	/**
	 * Get the posted of the v3 keys from the settings page.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	private function get_posted_keys() {
		$settings          = $this->addon->get_plugin_settings();
		$posted_site_key   = $this->get_posted_key( 'site_key_v3' );
		$posted_secret_key = $this->get_posted_key( 'secret_key_v3' );

		if (
			$posted_site_key === rgar( $settings, 'site_key_v3' )
			&& $posted_secret_key === rgar( $settings, 'secret_key_v3' )
		) {
			return array();
		}

		return array(
			'site_key_v3'   => $posted_site_key,
			'secret_key_v3' => $posted_secret_key,
		);
	}

	/**
	 * Gets the value of the specified input from the $_POST.
	 *
	 * @since 1.3
	 *
	 * @param string $key_name The name of the key to retrieve.
	 *
	 * @return string
	 */
	private function get_posted_key( $key_name ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			$key_name = "_gform_setting_{$key_name}";
		}

		return sanitize_text_field( rgpost( $key_name ) );
	}

	/**
	 * Get the value of one of the reCAPTCHA keys from the plugin settings.
	 *
	 * Checks first for a value defined as a constant, and secondarily, the add-on options.
	 *
	 * @since 1.0
	 * @since 1.3 Added the $only_from_constant param.
	 *
	 * @param string $key_name           The name of the key to retrieve.
	 * @param bool   $only_from_constant Indicates if value should only be retrieved from the constant.
	 *
	 * @return string
	 */
	public function get_recaptcha_key( $key_name, $only_from_constant = false ) {
		if ( ! $only_from_constant && is_admin() ) {
			$posted_key = $this->get_posted_key( $key_name );

			if ( $posted_key ) {
				return $posted_key;
			}
		}

		$keys = array(
			'site_key_v3'            => defined( 'GF_RECAPTCHA_V3_SITE_KEY' ) ? GF_RECAPTCHA_V3_SITE_KEY : '',
			'secret_key_v3'          => defined( 'GF_RECAPTCHA_V3_SECRET_KEY' ) ? GF_RECAPTCHA_V3_SECRET_KEY : '',
			'site_key_v2'            => '',
			'secret_key_v2'          => '',
			'site_key_v3_enterprise' => defined( 'GF_RECAPTCHA_V3_SITE_KEY_ENTERPRISE' ) ? GF_RECAPTCHA_V3_SITE_KEY_ENTERPRISE : '',
			'project_number'         => defined( 'GF_RECAPTCHA_PROJECT_NUMBER' ) ? GF_RECAPTCHA_PROJECT_NUMBER : '',
		);

		if ( ! in_array( $key_name, array_keys( $keys ), true ) ) {
			return '';
		}

		$key = rgar( $keys, $key_name, '' );

		if ( ! empty( $key ) || $only_from_constant ) {
			return $key;
		}

		$key = $this->addon->get_plugin_setting( $key_name );

		return ! empty( $key ) ? $key : '';
	}

	/**
	 * Perform the enterprise OAuth connection.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function ajax_perform_enterprise_oauth() {

		$this->addon->verify_ajax_nonce();

		$state = wp_create_nonce( 'gravityforms_recaptcha_connect' );

		if ( get_transient( 'gravityapi_request_' . $this->addon->get_slug() ) ) {
			delete_transient( 'gravityapi_request_' . $this->addon->get_slug() );
		}

		set_transient( 'gravityapi_request_' . $this->addon->get_slug(), $state, 10 * MINUTE_IN_SECONDS );

		$settings_url = rawurlencode(
			add_query_arg(
				array(
					'page'    => 'gf_settings',
					'subview' => $this->addon->get_slug(),
				),
				admin_url( 'admin.php' )
			)
		);

		$redirect_url = add_query_arg(
			array(
				'redirect_to' => $settings_url,
				'state'       => $state,
				'license'     => GFCommon::get_key(),
			),
			RECAPTCHA_API::get_gravity_api_url()
		);

		$this->addon->log_debug( "Redirecting to Gravity API: {$redirect_url}" );

		wp_send_json_success(
			array(
				'errors'   => false,
				'redirect' => esc_url_raw( $redirect_url ),
			)
		);
	}

	/**
	 * Maybe update the auth tokens in the plugin settings.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function maybe_update_auth_tokens() {

		$payload = $this->find_oauth_payload();

		if ( ! $payload || $this->addon->is_save_postback() ) {
			return;
		}

		if ( rgar( $payload, 'auth_error' ) ) {
			GFCommon::add_message( esc_html__( 'An error occured while connecting to the API.', 'gravityformsrecaptcha' ) );
			$this->addon->log_error( $payload['auth_error'] );
			return;
		}

		$auth_payload = $this->get_decoded_auth_payload( $payload );

		if ( rgar( $auth_payload, 'access_token' ) ) {
			$settings                    = $this->addon->get_plugin_settings();
			$settings['access_token']    = rgar( $auth_payload, 'access_token' );
			$settings['refresh_token']   = rgar( $auth_payload, 'refresh_token' );
			$settings['date_created']    = rgar( $auth_payload, 'created' );
			$settings['connection_type'] = 'enterprise';
			$this->addon->update_plugin_settings( $settings );
		}
	}

	/**
	 * Get the authorization payload data.
	 *
	 * Returns the auth POST request if it's present, otherwise attempts to return a recent transient cache.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	private function find_oauth_payload() {
		$payload = array_filter(
			array(
				'auth_payload' => rgpost( 'auth_payload' ),
				'auth_error'   => rgpost( 'auth_error' ),
				'state'        => rgpost( 'state' ),
			)
		);

		if ( isset( $payload['auth_error'] ) ) {
			return $payload;
		}

		$payload = get_transient( 'gravityapi_response_' . $this->addon->get_slug() );
		if ( rgar( $payload, 'state' ) !== get_transient( 'gravityapi_request_' . $this->addon->get_slug() ) ) {
			return array();
		}

		delete_transient( 'gravityapi_response_' . $this->addon->get_slug() );
		return is_array( $payload ) ? $payload : array();
	}

	/**
	 * Get the decoded auth payload.
	 *
	 * @param array $payload The auth payload.
	 *
	 * @since 1.7.0
	 *
	 * @return array|mixed
	 */
	private function get_decoded_auth_payload( $payload ) {
		$auth_payload_string = rgar( $payload, 'auth_payload' );
		return empty( $auth_payload_string ) ? array() : json_decode( $auth_payload_string, true );
	}
}
