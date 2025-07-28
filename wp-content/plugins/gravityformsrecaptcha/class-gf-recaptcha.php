<?php

namespace Gravity_Forms\Gravity_Forms_RECAPTCHA;

defined( 'ABSPATH' ) || die();

use GFForms;
use GFAddOn;
use GF_Fields;
use GFAPI;
use GFCommon;
use GFFormDisplay;
use GFFormsModel;
use Gravity_Forms\Gravity_Forms_RECAPTCHA\Settings;

// Include the Gravity Forms Add-On Framework.
GFForms::include_addon_framework();

/**
 * Gravity Forms Gravity Forms Recaptcha Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Gravity Forms
 * @copyright Copyright (c) 2021, Gravity Forms
 */
class GF_RECAPTCHA extends GFAddOn {

	/**
	 * Option name for triggering quota hit notification.
	 *
	 * @since 1.7
	 */
	const RECAPTCHA_QUOTA_LIMIT_HIT = 'gf_recaptcha_quota_limit_hit';

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @var    GF_RECAPTCHA $_instance If available, contains an instance of this class
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Gravity Forms Recaptcha Add-On.
	 *
	 * @since  1.0
	 * @var    string $_version Contains the version.
	 */
	protected $_version = GF_RECAPTCHA_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = GF_RECAPTCHA_MIN_GF_VERSION;

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsrecaptcha';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsrecaptcha/recaptcha.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this add-on can be found.
	 *
	 * @since  1.0
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'https://gravityforms.com';

	/**
	 * Defines the title of this add-on.
	 *
	 * @since  1.0
	 * @var    string $_title The title of the add-on.
	 */
	protected $_title = 'Gravity Forms reCAPTCHA Add-On';

	/**
	 * Defines the short title of the add-on.
	 *
	 * @since  1.0
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'reCAPTCHA';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capabilities needed for the Gravity Forms Recaptcha Add-On
	 *
	 * @since  1.0
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_recaptcha', 'gravityforms_recaptcha_uninstall' );

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_recaptcha';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_recaptcha';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_recaptcha_uninstall';

	/**
	 * Class instance.
	 *
	 * @var RECAPTCHA_API
	 */
	private $api;

	/**
	 * Object responsible for verifying tokens.
	 *
	 * @var Token_Verifier
	 */
	private $token_verifier;

	/**
	 * Prefix for add-on assets.
	 *
	 * @since 1.0
	 * @var string
	 */
	private $asset_prefix = 'gforms_recaptcha_';

	/**
	 * Wrapper class for plugin settings.
	 *
	 * @since 1.0
	 * @var Settings\Plugin_Settings
	 */
	private $plugin_settings;

	/**
	 * GF_Field_RECAPTCHA instance.
	 *
	 * @since 1.0
	 * @var GF_Field_RECAPTCHA
	 */
	private $field;

	/**
	 * Possible disabled states for v3.
	 *
	 * disabled: reCAPTCHA is disabled in feed settings.
	 * disconnected: No valid v3 site and secret keys are saved.
	 * quota_bypass: reCAPTCHA API quota limit hit.
	 *
	 * @var array
	 */
	private $v3_disabled_states = array(
		'disabled',
		'disconnected',
		'disabled (quota limit)'
	);

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @since  1.0
	 *
	 * @return GF_RECAPTCHA $_instance An instance of the GF_RECAPTCHA class
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof GF_RECAPTCHA ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Run add-on pre-initialization processes.
	 *
	 * @since 1.0
	 */
	public function pre_init() {
		require_once plugin_dir_path( __FILE__ ) . '/includes/settings/class-plugin-settings.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-gf-field-recaptcha.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-recaptcha-api.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-token-verifier.php';

		$this->api             = new RECAPTCHA_API();
		$this->token_verifier  = new Token_Verifier( $this, $this->api );
		$this->plugin_settings = new Settings\Plugin_Settings( $this, $this->token_verifier );
		$this->field           = new GF_Field_RECAPTCHA();

		GF_Fields::register( $this->field );

		add_filter( 'gform_settings_menu', array( $this, 'replace_core_recaptcha_menu_item' ) );

		parent::pre_init();
	}

	/**
	 * Replaces the core recaptcha settings menu item with the addon settings menu item.
	 *
	 * @param array $settings_tabs Registered settings tabs.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function replace_core_recaptcha_menu_item( $settings_tabs ) {
		// Get tab names with the same index as is in the settings tabs.
		$tabs = array_combine( array_keys( $settings_tabs ), array_column( $settings_tabs, 'name' ) );

		// Bail if for some reason this add-on is not registered as a settings tab.
		if ( ! in_array( $this->_slug, $tabs ) ) {
			return $settings_tabs;
		}

		$prepared_tabs = array_flip( $tabs );

		$settings_tabs[ rgar( $prepared_tabs, 'recaptcha' ) ]['name'] = $this->_slug;
		unset( $settings_tabs[ rgar( $prepared_tabs, $this->_slug ) ] );

		return $settings_tabs;
	}

	/**
	 * Register initialization hooks.
	 *
	 * @since  1.0
	 */
	public function init() {
		parent::init();

		if ( ! $this->is_gravityforms_supported( $this->_min_gravityforms_version ) ) {
			return;
		}

		// Enqueue shared scripts that need to run everywhere, instead of just on forms pages.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_recaptcha_script' ) );
		add_action( 'gform_preview_init', array( $this, 'maybe_enqueue_recaptcha_script' ) );

		// Add Recaptcha field to the form output.
		add_filter( 'gform_form_tag', array( $this, 'add_recaptcha_input' ), 50, 2  );

		// Register a custom metabox for the entry details page.
		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'register_meta_box' ), 10, 3 );

		add_filter( 'gform_entry_is_spam', array( $this, 'check_for_spam_entry' ), 10, 3 );
		add_filter( 'gform_validation', array( $this, 'validate_submission' ) );

		add_filter( 'gform_field_content', array( $this, 'update_captcha_field_settings_link' ), 10, 2 );
		add_filter( 'gform_incomplete_submission_pre_save', array( $this, 'add_recaptcha_v3_input_to_draft' ), 10, 3 );

		// Catch the ajax call to remove the reCAPTCHA quota notice.
		add_action( 'wp_ajax_gf_recaptcha_quota_notice', array( $this, 'gf_recaptcha_quota_notice_dismiss' ), 10, 0 );

	}

	/**
	 * Register admin initialization hooks.
	 *
	 * @since 1.0
	 */
	public function init_admin() {
		$this->plugin_settings->maybe_update_auth_tokens();
		parent::init_admin();

		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_recaptcha_script' ) );
		add_action( 'admin_notices', array( $this, 'recaptcha_quota_notice' ), 10, 0 );
	}

	/**
	 * Override plugin_settings_init to maybe display the saved settings message.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function plugin_settings_init() {
		parent::plugin_settings_init();
		$this->maybe_display_settings_saved_message();
	}

	/**
	 * Validate the secret key on the plugin settings screen.
	 *
	 * @since 1.0
	 */
	public function init_ajax() {
		parent::init_ajax();

		add_action( 'wp_ajax_verify_secret_key', array( $this->plugin_settings, 'verify_v3_keys' ) );
		add_action( 'wp_ajax_update_reload_settings', array( $this, 'update_reload_settings' ) );
		add_action( 'wp_ajax_perform_enterprise_oauth', array( $this->plugin_settings, 'ajax_perform_enterprise_oauth' ) );
		add_action( 'wp_ajax_disconnect_recaptcha', array( $this, 'ajax_disconnect_recaptcha' ) );
		add_action( 'wp_ajax_get_enterprise_site_keys', array( $this, 'ajax_get_enterprise_site_keys' ) );
		add_action( 'wp_ajax_save_recaptcha_enterprise_data', array( $this, 'ajax_save_recaptcha_enterprise_data' ) );
	}

	/**
	 * Register scripts.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array();

		// Prevent plugin settings from loading on the frontend. Remove this condition to see it in action.
		if ( is_admin() ) {
			if ( $this->requires_recaptcha_script() ) {
				$admin_deps = array( 'jquery', "{$this->asset_prefix}recaptcha" );
			} else {
				$admin_deps = array( 'jquery' );
			}

			$scripts[] = array(
				'handle'  => "{$this->asset_prefix}plugin_settings",
				'src'     => $this->get_script_url( 'plugin_settings' ),
				'version' => $this->_version,
				'deps'    => $admin_deps,
				'enqueue' => array(
					array(
						'admin_page' => array( 'plugin_settings' ),
						'tab'        => $this->_slug,
					),
				),
			);
		}

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Get the URL for a JavaScript file.
	 *
	 * @since 1.0
	 *
	 * @param string $filename The name of the script to return.
	 *
	 * @return string
	 */
	private function get_script_url( $filename ) {
		$base_path = $this->get_base_path() . '/js';
		$base_url  = $this->get_base_url() . '/js';

		// Production scripts.
		if ( is_readable( "{$base_path}/{$filename}.min.js" ) && ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			return "{$base_url}/{$filename}.min.js";
		}

		// Uncompiled scripts.
		if ( is_readable( "{$base_path}/src/{$filename}.js" ) ) {
			return "{$base_url}/src/{$filename}.js";
		}

		// Compiled dev scripts.
		return "{$base_url}/{$filename}.js";
	}

	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Define plugin settings fields.
	 *
	 * @since  1.0
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return $this->plugin_settings->get_fields();
	}

	/**
	 * Initialize the plugin settings.
	 *
	 * This method overrides the add-on framework because we need to retrieve the values for reCAPTCHA v2 from core
	 * and populate them if they exist. Since the Plugin_Settings class houses all of the logic related to the plugin
	 * settings screen, we need to pass the return value of this method's parent to delegate that responsibility.
	 *
	 * In a future release, once reCAPTCHA logic is migrated into this add-on, we
	 * should be able to safely remove this override.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_plugin_settings() {
		$current_settings = parent::get_plugin_settings();
		// If the mode is enterprise, we don't need the v2 core settings.
		if ( rgar( $current_settings, 'connection_type' ) === 'enterprise' ) {
			return $current_settings;
		}

		// Merge in the v2 core settings in other modes.
		return $this->plugin_settings->get_settings( parent::get_plugin_settings() );
	}

	/**
	 * Sets a nonce for the reCAPTCHA settings page
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function settings_nonce_connect() {
		echo sprintf( '<input type="hidden" name="recaptcha_nonce" value="%s" />', esc_attr( wp_create_nonce( 'connect_recaptcha' ) ) );
	}

	/**
	 * Setting for the disconnect button.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function settings_disconnect_recaptcha() {
		$disconnect_uri  = esc_url_raw(
			add_query_arg(
				array(
					'page'    => 'gf_settings',
					'subview' => 'gravityformsrecaptcha',
					'action'  => 'gfrecaptcha-disconnect',
					'nonce'   => wp_create_nonce( 'gforms_google_recaptcha_disconnect' ),
				),
				admin_url( 'admin.php' )
			)
		);
		$disconnect_link = sprintf( '<a href="%s" class="button gfrecaptcha-disconnect">%s</a> ', esc_url_raw( $disconnect_uri ), esc_html__( 'Disconnect from reCAPTCHA', 'gravityformsrecaptcha' ) );
		echo wp_kses_post( $disconnect_link );
	}

	/**
	 * Setting for the change connection type button.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function settings_change_connection_type() {
		$disconnect_uri  = esc_url_raw(
			add_query_arg(
				array(
					'page'    => 'gf_settings',
					'subview' => 'gravityformsrecaptcha',
					'action'  => 'gfrecaptcha-disconnect',
					'nonce'   => wp_create_nonce( 'gforms_google_recaptcha_disconnect' ),
				),
				admin_url( 'admin.php' )
			)
		);
		$disconnect_link = sprintf( '<a href="%s" class="button gfrecaptcha-disconnect gfrecaptcha-changetype">%s</a> ', esc_url_raw( $disconnect_uri ), esc_html__( 'Change Connection Type', 'gravityformsrecaptcha' ) );
		echo wp_kses_post( $disconnect_link );
	}

	/**
	 * Returns the message to display when there is an issue communicating with Google.
	 *
	 * @since 1.10
	 *
	 * @return string
	 */
	private function comms_error_message() {
		if ( method_exists( 'GFCommon', 'get_support_url' ) ) {
			$support_url = GFCommon::get_support_url();
		} else {
			$support_url = 'https://www.gravityforms.com/open-support-ticket/';
		}

		/* translators: 1: Open link tag 2: Screen reader text opening span tag 3: Screen reader text closing span tag, external link span tags, and closing link tag */

		return sprintf( esc_html__( 'There is a problem communicating with Google right now. Please check back later. If this issue persists for more than a day, please %1$sopen a support ticket%2$s(opens in a new tab)%3$s.', 'gravityformsrecaptcha' ), "<a href='" . esc_url( $support_url ) . "' target='_blank'>", '<span class="screen-reader-text">', '</span>&nbsp;<span class="gform-icon gform-icon--external-link"></span></a>' );
	}

	/**
	 * Echos an error message.
	 *
	 * @since 1.10
	 *
	 * @return void
	 */
	private function echo_error_message( $message ) {
		echo '<div class="error-alert-container alert-container">
					<div class="gform-alert gform-alert--error" data-js="gform-alert">
						<span class="gform-alert__icon gform-icon gform-icon--circle-close" aria-hidden="true"></span>
						<div class="gform-alert__message-wrap">
							<p class="gform-alert__message">' . $message . '</p>
						</div>
					</div>
				</div>';
	}

	/**
	 * Setting to display the reCAPTCHA Enterprise fields.
	 *
	 * @since 1.7.0
	 *
	 * @return false|void
	 */
	public function settings_recaptcha_enterprise_fields() {
		$plugin_settings        = $this->get_plugin_settings();
		$current_project_number = $this->get_plugin_settings_instance()->get_recaptcha_key( 'project_number' );
		$current_site_key       = rgar( $plugin_settings, 'site_key_v3_enterprise' ) ? rgar( $plugin_settings, 'site_key_v3_enterprise' ) : '';
		if ( ! $this->initialize_api() ) {
			$this->log_debug( __METHOD__ . '(): Unable to initialize reCAPTCHA API.' );
			$this->echo_error_message( $this->comms_error_message() );

			return false;
		}

		if ( ! $this->current_user_can_any( $this->_capabilities_form_settings ) ) {
			$this->log_debug( __METHOD__ . '(): User does not have Form Settings capability.' );

			return false;
		}

		$response = $this->api->get_recaptcha_projects();

		if ( is_wp_error( $response ) ) {
			$this->log_debug( __METHOD__ . '(): Could not retrieve Google projects.' );
			$this->echo_error_message( esc_html__( 'You have no available projects for reCAPTCHA or have insufficient permissions', 'gravityformsrecaptcha' ) );

			return false;
		}

		if ( defined( 'GF_RECAPTCHA_PROJECT_NUMBER' ) ) {
			echo '<div class="gform-settings-field">';
			echo '<span class="gform-settings-input__container"><input type="text" readonly value="' . esc_attr( GF_RECAPTCHA_PROJECT_NUMBER ) . '"> </span>';
			echo wp_kses_post( $this->plugin_settings->get_constant_message( GF_RECAPTCHA_PROJECT_NUMBER, 'GF_RECAPTCHA_PROJECT_NUMBER' ) );
			echo '</div>';
		} else {
			echo '<div class="gform-settings-field">';
			echo '<select name="recaptcha_project" id="recaptcha_project">';
			echo '<option value="">' . esc_html__( 'Select a Project', 'gravityformsrecaptcha' ) . '</option>';
			foreach ( $response['projects'] as $project ) {
				if ( $project['lifecycleState'] !== 'ACTIVE' ) {
					continue;
				}

				$is_selected = $current_project_number === $project['projectNumber'] ? 'selected' : '';

				printf(
					'<option value="%1$s" data-project-id="%2$s" data-project-name="%3$s" %4$s>%3$s</option>',
					esc_attr( $project['projectNumber'] ),
					esc_attr( $project['projectId'] ),
					esc_html( $project['name'] ),
					esc_html( $is_selected )
				);
			}
			echo '</select></div>';
		}

		if ( defined( 'GF_RECAPTCHA_V3_SITE_KEY_ENTERPRISE' ) ) {
			echo '<div class="gform-settings-field__header"><label for="recaptcha-site-keys" class="gform-settings-label">' . esc_html__( 'Enterprise Site Key', 'gravityformsrecaptcha' ) . '</label></div>';
			echo '<span class="gform-settings-input__container"><input type="text" readonly value="' . esc_attr( GF_RECAPTCHA_V3_SITE_KEY_ENTERPRISE ) . '"> </span>';
			echo wp_kses_post( $this->plugin_settings->get_constant_message( GF_RECAPTCHA_V3_SITE_KEY_ENTERPRISE, 'GF_RECAPTCHA_V3_SITE_KEY_ENTERPRISE' ) );
		} else {
			if ( ! empty( $current_project_number ) ) {
				$site_keys = $this->api->get_enterprise_site_keys( $current_project_number );

				if ( is_wp_error( $site_keys ) ) {
					$this->log_debug( __METHOD__ . '(): Error retrieving site keys associated with the selected project.' );
					$this->echo_error_message( esc_html__( 'There was an error retrieving the reCAPTCHA site keys.', 'gravityformsrecaptcha' ) );

					return false;
				}

				// Create select field markup.
				$html  = '<div class="gform-settings-field__header"><label for="recaptcha-site-keys" class="gform-settings-label">' . esc_html__( 'Enterprise Site Key', 'gravityformsrecaptcha' ) . '</label></div>';
				$html .= '<select name="recaptcha-site-keys">';
				$html .= '<option value="">' . esc_html__( 'Select a site key', 'gravityformsrecaptcha' ) . '</option>';
				if ( rgar( $site_keys, 'keys' ) ) {
					foreach ( rgar( $site_keys, 'keys' ) as $site_key ) {
						$current_site_key_name = basename( $site_key['name'] );
						if ( $current_site_key === $current_site_key_name ) {
							$is_site_key_selected = 'selected';
						} else {
							$is_site_key_selected = '';
						}
						$html .= sprintf(
							'<option value="%1$s" data-site-key-display-name="%2$s" %3$s>%2$s</option>',
							esc_attr( basename( $site_key['name'] ) ),
							esc_attr( $site_key['displayName'] ),
							$is_site_key_selected
						);
					}
				}
				$html .= '</select>';
				echo '<div id="recaptcha-site-keys">' . $html . '</div>';
			}
			?>
			<div id="recaptcha-site-keys"></div>
			<?php
		}
	}

	/**
	 * Setting to display the hidden reCAPTCHA action.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function settings_recaptcha_action() {
		$connection_type = $this->get_connection_type();

		$actions = array(
			'enterprise' => 'gf_recaptcha_enterprise',
			'classic'    => 'gf_recaptcha_v3_classic',
			'v2'         => 'gf_recaptcha_v2',
		);

		if ( isset( $actions[ $connection_type ] ) ) {
			echo '<input type="hidden" name="gf_recaptcha_action" value="' . esc_attr( $actions[ $connection_type ] ) . '" />';
		} else {
			echo '<input type="hidden" name="gf_recaptcha_action" value="gf_recaptcha_v3_classic" />';
			echo '<input type="hidden" name="gf_recaptcha_action" value="gf_recaptcha_v2" />';
		}
	}

	/**
	 * Callback to update plugin settings on save.
	 *
	 * We override this method in order to save values for reCAPTCHA v2 with their original keys in the options table.
	 * In a future release, we'll eventually migrate all previous reCAPTCHA logic into this add-on, at which time we
	 * should be able to remove this method altogether.
	 *
	 * @since 1.0
	 *
	 * @param array $settings The settings to update.
	 */
	public function update_plugin_settings( $settings ) {

		if ( $this->get_connection_type() !== 'enterprise' ) {
			$this->plugin_settings->update_settings( $settings );
			parent::update_plugin_settings( $settings );
		} else {
			// In Enterprise we need to merge the settings so we don't lost the access token and refresh token.
			$current_settings = $this->get_plugin_settings();
			if ( is_array( $current_settings ) ) {
				$settings = array_merge( $current_settings, $settings );
			}

			parent::update_plugin_settings( $settings );
		}
	}

	/**
	 * Maybe display the settings saved message on the enterprise settings screen.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	private function maybe_display_settings_saved_message() {
		if ( $this->get_connection_type() === 'enterprise' && rgget( 'subview' ) === 'gravityformsrecaptcha' ) {
			$renderer = $this->get_settings_renderer();
			if ( ! $renderer ) {
				return;
			}
			$renderer->set_postback_message_callback(
				function() {
					if ( rgget( 'saved' ) === '1' ) {
						return 'Settings Saved';
					}
				}
			);
			$this->set_settings_renderer( $renderer );
		}
	}

	/**
	 * The settings page icon.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_menu_icon() {
		return 'gform-icon--recaptcha';
	}

	/**
	 * Add the recaptcha field to the end of the form.
	 *
	 * @since 1.0
	 *
	 * @depecated 1.1
	 *
	 * @param array $form The form array.
	 *
	 * @return array
	 */
	public function add_recaptcha_field( $form ) {
		return $form;
	}

	/**
	 * Add the recaptcha input to the form.
	 *
	 * @since 1.1
	 *
	 * @param string $form_tag The form tag.
	 * @param array  $form     The form array.
	 *
	 * @return string
	 */
	public function add_recaptcha_input( $form_tag, $form ) {
		if ( empty( $form_tag ) || $this->is_disabled_by_form_setting( $form ) || ! $this->initialize_api( false ) ) {
			return $form_tag;
		}

		return $form_tag . $this->field->get_field_input( $form );
	}

	// # FORM SETTINGS

	/**
	 * Register a form settings tab for reCAPTCHA v3.
	 *
	 * @since 1.0
	 *
	 * @param array $form The form data.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => 'reCAPTCHA Settings',
				'fields' => array(
					array(
						'type'    => 'checkbox',
						'name'    => 'disable-recaptchav3',
						'choices' => array(
							array(
								'name'          => 'disable-recaptchav3',
								'label'         => __( 'Disable reCAPTCHA v3 for this form.', 'gravityformsrecaptcha' ),
								'default_value' => 0,
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Updates the query string for the settings link displayed in the form editor preview of the Captcha field.
	 *
	 * @since 1.2
	 *
	 * @param string    $field_content The field markup.
	 * @param \GF_Field $field         The field being processed.
	 *
	 * @return string
	 */
	public function update_captcha_field_settings_link( $field_content, $field ) {
		if ( $field->type !== 'captcha' || ! $field->is_form_editor() ) {
			return $field_content;
		}

		return str_replace(
			array( '&subview=recaptcha', '?page=gf_settings' ),
			array( '', '?page=gf_settings&subview=gravityformsrecaptcha' ),
			$field_content
		);
	}

	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Get the instance of the Token_Verifier class.
	 *
	 * @since 1.0
	 *
	 * @return Token_Verifier
	 */
	public function get_token_verifier() {
		return $this->token_verifier;
	}

	/**
	 * Get the instance of the Plugin_Settings class.
	 *
	 * @return Settings\Plugin_Settings
	 */
	public function get_plugin_settings_instance() {
		return $this->plugin_settings;
	}

	/**
	 * Initialize the connection to the reCAPTCHA API.
	 *
	 * @since 1.0
	 * @since 1.7.0 Separate methods for initialize enterprise and classic APIs.
	 * @since 1.8.0 Added the optional $refresh_token param.
	 *
	 * @param bool $refresh_token Indicates if the auth token should be refreshed.
	 *
	 * @return bool
	 */
	private function initialize_api( $refresh_token = true ) {
		static $result = null;

		if ( is_bool( $result ) ) {
			return $result;
		}

		$plugin_settings = $this->get_plugin_settings();
		$connection_type = rgar( $plugin_settings, 'connection_type' );

		switch ( $connection_type ) {
			case 'enterprise':
				$result = $this->initialize_enterprise_api( $plugin_settings, $refresh_token );
				break;
			case 'v2':
				$this->log_debug( __METHOD__ . '(): Aborting; v2 connection type selected.' );
				$result = false;
				break;
			default:
				$result = $this->initialize_classic_api();
		}

		return $result;
	}

	/**
	 * Initialize the Enterprise API.
	 *
	 * @since 1.7.0
	 * @since 1.8.0 Added the optional $refresh_token param and refresh locking.
	 *
	 * @param array $plugin_settings The plugin settings.
	 * @param bool  $refresh_token   Indicates if the auth token should be refreshed.
	 *
	 * @return bool
	 */
	private function initialize_enterprise_api( $plugin_settings, $refresh_token ) {
		if ( ! rgar( $plugin_settings, 'access_token' ) ) {
			$this->log_debug( __METHOD__ . '(): Access token does not exist, unable to initialize API.' );

			return false;
		}

		$date_created = (int) rgar( $plugin_settings, 'date_token', 0 );
		if ( empty( $date_created ) ) {
			$date_created = (int) rgar( $plugin_settings, 'date_created', 0 );
		}

		if ( ! $refresh_token || ! ( time() > ( $date_created + 3600 ) ) ) {
			$this->log_debug( __METHOD__ . '(): Enterprise API Initialized.' );
			$this->get_api_instance();

			return true;
		}

		if ( ! rgar( $plugin_settings, 'refresh_token' ) ) {
			$this->log_error( __METHOD__ . '(): API tokens expired; refresh token does not exist, unable to refresh access token.' );

			return false;
		}

		$this->log_debug( __METHOD__ . '(): API tokens expired, start refreshing.' );

		if ( ! class_exists( 'Gravity_Forms\Gravity_Forms_RECAPTCHA\Refresh_Lock_Handler' ) ) {
			require_once 'includes/class-refresh-lock-handler.php';
		}

		$refresh_lock_handler = new Refresh_Lock_Handler( $this );

		if ( $refresh_lock_handler->can_refresh_token() === false ) {
			$this->log_debug( __METHOD__ . '():  Aborting; ' . $refresh_lock_handler->refresh_lock_reason );

			return false;
		}

		$refresh_lock_handler->lock();

		// Refresh token.
		$auth_response = $this->api->refresh_token( $plugin_settings['refresh_token'] );

		if ( is_wp_error( $auth_response ) ) {
			$this->log_error( __METHOD__ . '(): API access token failed to be refreshed; ' . $auth_response->get_error_message() );
			$refresh_lock_handler->release_lock();
			$refresh_lock_handler->increment_rate_limit();

			return false;
		}

		$decoded_response = json_decode( rgar( $auth_response, 'auth_payload' ), true );

		if ( ! empty( $decoded_response['auth_error'] ) ) {
			$this->log_error( __METHOD__ . '(): API access token failed to be refreshed (auth_error); ' . print_r( $decoded_response['auth_error'], true ) );
			$refresh_lock_handler->release_lock();
			$refresh_lock_handler->increment_rate_limit();

			return false;
		}

		$plugin_settings['access_token']  = rgar( $decoded_response, 'access_token' );
		$plugin_settings['refresh_token'] = rgar( $decoded_response, 'refresh_token' );
		$plugin_settings['date_token']    = rgar( $decoded_response, 'created' );

		// Save plugin settings.
		$this->update_plugin_settings( $plugin_settings );
		$this->log_debug( __METHOD__ . '(): API access token has been refreshed; Enterprise API Initialized.' );
		$this->get_api_instance();
		$refresh_lock_handler->release_lock();
		$refresh_lock_handler->reset_rate_limit();

		return true;
	}

	/**
	 * Initialize the v2 and v3 Classic settings.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	private function initialize_classic_api() {
		static $result;

		if ( is_bool( $result ) ) {
			return $result;
		}

		$result     = false;
		$site_key   = $this->plugin_settings->get_recaptcha_key( 'site_key_v3' );
		$secret_key = $this->plugin_settings->get_recaptcha_key( 'secret_key_v3' );

		if ( ! ( $site_key && $secret_key ) ) {
			$this->log_debug( __METHOD__ . '(): Missing v3 key configuration. Please check the add-on settings.' );

			return false;
		}

		if ( '1' !== $this->get_plugin_setting( 'recaptcha_keys_status_v3' ) ) {
			$this->log_debug( __METHOD__ . '(): Could not initialize reCAPTCHA v3 because site and/or secret key is invalid.' );

			return false;
		}

		$result = true;
		$this->log_debug( __METHOD__ . '(): API Initialized.' );

		return true;
	}

	/**
	 * Get the Enterprise API instance.
	 *
	 * @since 1.7.0
	 *
	 * @return RECAPTCHA_API
	 */
	public function get_api_instance() {
		$plugin_settings = $this->get_plugin_settings();
		$auth_data       = array(
			'access_token'  => rgar( $plugin_settings, 'access_token' ),
			'refresh_token' => rgar( $plugin_settings, 'refresh_token' ),
			'project_id'    => rgar( $plugin_settings, 'project_id' ),
		);

		$this->api = new RECAPTCHA_API( $auth_data, $this );

		return $this->api;
	}

	/**
	 * Check to determine whether the reCAPTCHA script is needed on a page.
	 *
	 * The script is needed on every page of the front-end if we're able to initialize the API because we've already
	 * verified that the v3 site and secret keys are valid.
	 *
	 * On the back-end, we only want to load this on the settings page, and it should be available regardless of the
	 * status of the keys.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	private function requires_recaptcha_script() {
		return is_admin() ? $this->is_plugin_settings( $this->_slug ) : $this->initialize_api( false );
	}

	/**
	 * Custom enqueuing of the external reCAPTCHA script.
	 *
	 * This script is enqueued via the normal WordPress process because, on the front-end, it's needed on every
	 * single page of the site in order for reCAPTCHA to properly score the interactions leading up to the form
	 * submission.
	 *
	 * @since 1.0
	 * @see GF_RECAPTCHA::init()
	 */
	public function maybe_enqueue_recaptcha_script() {
		if ( ! $this->requires_recaptcha_script() ) {
			return;
		}

		if ( $this->get_connection_type() === 'enterprise' ) {
			$this->enqueue_enterprise_recaptcha_script();

			return;
		}

		$script_url = add_query_arg(
			'render',
			$this->plugin_settings->get_recaptcha_key( 'site_key_v3' ),
			'https://www.google.com/recaptcha/api.js'
		);

		wp_enqueue_script(
			"{$this->asset_prefix}recaptcha",
			$script_url,
			array(),
			$this->_version,
			$this->get_enqueue_script_args()
		);

		$strings                    = $this->localize_script_common_strings();
		$strings['site_key']        = $this->plugin_settings->get_recaptcha_key( 'site_key_v3' );
		$strings['connection_type'] = 'classic';

		wp_localize_script(
			"{$this->asset_prefix}recaptcha",
			"{$this->asset_prefix}recaptcha_strings",
			$strings
		);

		$this->enqueue_frontend_script();
	}

	/**
	 * Enqueues our frontend script that handles executing the external script and hiding the badge.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	private function enqueue_frontend_script() {
		$frontend_script_name = version_compare( GFForms::$version, '2.9.0-dev-1', '<' ) ? 'frontend-legacy' : 'frontend';
		$deps                 = array( "{$this->asset_prefix}recaptcha" );

		if ( $frontend_script_name === 'frontend-legacy' ) {
			$deps[] = 'jquery';
		}

		wp_enqueue_script(
			$this->asset_prefix . $frontend_script_name,
			$this->get_script_url( $frontend_script_name ),
			$deps,
			$this->_version,
			$this->get_enqueue_script_args()
		);
	}

	/**
	 * Returns the array used for the args param of wp_enqueue_script().
	 *
	 * @since 1.8.0
	 *
	 * @return array
	 */
	private function get_enqueue_script_args() {
		return array(
			'strategy'  => 'defer',
			'in_footer' => true,
		);
	}

	/**
	 * Custom enqueuing of the external reCAPTCHA Enterprise script.
	 *
	 * This script is enqueued via the normal WordPress process because, on the front-end, it's needed on every
	 * single page of the site in order for reCAPTCHA to properly score the interactions leading up to the form
	 * submission.
	 *
	 * @since 1.8.0
	 */
	private function enqueue_enterprise_recaptcha_script() {
		$script_url = add_query_arg(
			'render',
			$this->plugin_settings->get_recaptcha_key( 'site_key_v3_enterprise' ),
			'https://www.google.com/recaptcha/enterprise.js'
		);

		wp_enqueue_script(
			"{$this->asset_prefix}recaptcha",
			$script_url,
			array(),
			$this->_version,
			$this->get_enqueue_script_args()
		);

		$strings                    = $this->localize_script_common_strings();
		$strings['site_key']        = $this->plugin_settings->get_recaptcha_key( 'site_key_v3_enterprise' );
		$strings['connection_type'] = 'enterprise';
		$strings['ajaxurl']         = admin_url( 'admin-ajax.php' );

		wp_localize_script(
			"{$this->asset_prefix}recaptcha",
			"{$this->asset_prefix}recaptcha_strings",
			$strings
		);

		$this->enqueue_frontend_script();
	}

	/**
	 * Get the strings used to localize classic and enterprise reCAPTCHA scripts.
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	private function localize_script_common_strings() {
		$disable_badge = array_key_exists( '_gform_setting_disable_badge_v3', $_POST )
			? rgpost( '_gform_setting_disable_badge_v3' ) === '1'
			: $this->get_plugin_setting( 'disable_badge_v3' ) === '1';

		return array(
			'nonce'                          => wp_create_nonce( "{$this->_slug}_verify_token_nonce" ),
			'disconnect'                     => wp_strip_all_tags( __( 'Disconnecting', 'gravityformsrecaptcha' ) ),
			'change_connection_type'         => wp_strip_all_tags( __( 'Resetting', 'gravityformsrecaptcha' ) ),
			'spinner'                        => GFCommon::get_base_url() . '/images/spinner.svg',
			'connection_type'                => $this->get_connection_type(),
			'disable_badge'                  => $disable_badge,
			'change_connection_type_title'   => __( 'Change Connection Type', 'gravityformsrecaptcha' ),
			'change_connection_type_message' => __( 'Changing the connection type will delete your current settings.  Do you want to proceed?', 'gravityformsrecaptcha' ),
			'disconnect_title'               => __( 'Disconnect', 'gravityformsrecaptcha' ),
			'disconnect_message'             => __( 'Disconnecting from reCAPTCHA will delete your current settings.  Do you want to proceed?', 'gravityformsrecaptcha' ),
		);
	}

	/**
	 * Sets up additional data points for sorting on the entry.
	 *
	 * @since 1.0
	 *
	 * @param array $entry_meta The entry metadata.
	 * @param int   $form_id The ID of the form.
	 *
	 * @return array
	 */
	public function get_entry_meta( $entry_meta, $form_id ) {
		$entry_meta[ "{$this->_slug}_score" ] = array(
			'label'                      => __( 'reCAPTCHA Score', 'gravityformsrecaptcha' ),
			'is_numeric'                 => true,
			'update_entry_meta_callback' => array( $this, 'update_entry_meta' ),
			'is_default_column'          => true,
			'filter'                     => array(
				'operators' => array( 'is', '>', '<' ),
			),
		);

		return $entry_meta;
	}

	/**
	 * Save the Recaptcha metadata values to the entry.
	 *
	 * @since 1.0
	 *
	 * @see   GF_RECAPTCHA::get_entry_meta()
	 *
	 * @param string $key   The entry meta key.
	 * @param array  $entry The entry data.
	 * @param array  $form  The form data.
	 *
	 * @return float|void
	 */
	public function update_entry_meta( $key, $entry, $form ) {
		if ( $key !== "{$this->_slug}_score" ) {
			return;
		}

		if ( $this->is_disabled_by_form_setting( $form ) ) {
			$this->log_debug( __METHOD__ . '(): reCAPTCHA v3 disabled on form ' . rgar( $form, 'id' ) );
			return 'disabled';
		}

		if ( $this->is_disabled_by_quota_limit() ) {
			$this->log_debug( __METHOD__ . '(): reCAPTCHA v3 disabled due to API quota limit.' );
			return 'disabled (quota limit)';
		}

		if ( ! $this->initialize_api() ) {
			return 'disconnected';
		}

		if ( $this->get_connection_type() === 'enterprise' && ! $this->enterprise_keys_configured() ) {
			return 'disconnected';
		}

		return $this->token_verifier->get_score();
	}

	/**
	 * Registers a metabox on the entry details screen.
	 *
	 * @since 1.0
	 *
	 * @param array $metaboxes Gravity Forms registered metaboxes.
	 * @param array $entry     The entry array.
	 * @param array $form      The form array.
	 *
	 * @return array
	 */
	public function register_meta_box( $metaboxes, $entry, $form ) {
		$score = $this->get_score_from_entry( $entry );

		if ( ! $score ) {
			return $metaboxes;
		}

		$metaboxes[ $this->_slug ] = array(
			'title'    => esc_html__( 'reCAPTCHA', 'gravityformsrecaptcha' ),
			'callback' => array( $this, 'add_recaptcha_meta_box' ),
			'context'  => 'side',
		);

		return $metaboxes;
	}

	/**
	 * Callback to output the entry details metabox.
	 *
	 * @since 1.0
	 * @see   GF_RECAPTCHA::register_meta_box()
	 *
	 * @param array $data An array containing the form and entry data.
	 */
	public function add_recaptcha_meta_box( $data ) {
		$score = $this->get_score_from_entry( rgar( $data, 'entry' ) );

		printf(
			'<div><p>%s: %s</p><p><a href="%s">%s</a></p></div>',
			esc_html__( 'Score', 'gravityformsrecaptcha' ),
			esc_html( $score ),
			esc_html( 'https://docs.gravityforms.com/captcha/' ),
			esc_html__( 'Click here to learn more about reCAPTCHA.', 'gravityformsrecaptcha' )
		);
	}

	/**
	 * Callback to gform_entry_is_spam that determines whether to categorize this entry as such.
	 *
	 * @since 1.0
	 *
	 * @see   GF_RECAPTCHA::init();
	 *
	 * @param bool  $is_spam Whether the entry is spam.
	 * @param array $form    The form data.
	 * @param array $entry   The entry data.
	 *
	 * @return bool
	 */
	public function check_for_spam_entry( $is_spam, $form, $entry ) {

		if ( $is_spam ) {
			$this->log_debug( __METHOD__ . '(): Skipping, entry has already been identified as spam by another anti-spam solution.' );
			return $is_spam;
		}

		$is_spam = $this->is_spam_submission( $form, $entry );
		$this->log_debug( __METHOD__ . '(): Is submission considered spam? ' . ( $is_spam ? 'Yes.' : 'No.' ) );

		return $is_spam;
	}

	/**
	 * Determines if the submission is spam by comparing its score with the threshold.
	 *
	 * @since 1.4
	 * @since 1.5 Added the optional $entry param.
	 *
	 * @param array $form  The form being processed.
	 * @param array $entry The entry being processed.
	 *
	 * @return bool
	 */
	public function is_spam_submission( $form, $entry = array() ) {
		if ( $this->should_skip_validation( $form ) || $this->is_disabled_by_quota_limit() ) {
			$this->log_debug( __METHOD__ . '(): Score check skipped.' );

			return false;
		}

		$score     = empty( $entry ) ? $this->token_verifier->get_score() : $this->get_score_from_entry( $entry );
		$threshold = $this->get_spam_score_threshold();

		return (float) $score <= (float) $threshold;
	}
	/**
	 * Get the Recaptcha score from the entry details.
	 *
	 * @since 1.0
	 *
	 * @param array $entry The entry array.
	 *
	 * @return float|string
	 */
	private function get_score_from_entry( $entry ) {
		$score = rgar( $entry, "{$this->_slug}_score" );

		if ( in_array( $score, $this->v3_disabled_states, true ) ) {
			return $score;
		}

		return $score ? (float) $score : $this->token_verifier->get_score();
	}

	/**
	 * The score that determines whether the entry is spam.
	 *
	 * Hard-coded for now, but this will eventually be an option within the add-on.
	 *
	 * @since 1.0
	 *
	 * @return float
	 */
	private function get_spam_score_threshold() {
		static $value;

		if ( ! empty( $value ) ) {
			return $value;
		}

		$value = (float) $this->get_plugin_setting( 'score_threshold_v3' );
		if ( empty( $value ) ) {
			$value = 0.5;
		}
		$this->log_debug( __METHOD__ . '(): ' . $value );

		return $value;
	}

	/**
	 * Determine whether a given form has disabled reCAPTCHA within its settings.
	 *
	 * @since 1.0
	 *
	 * @param array $form The form data.
	 *
	 * @return bool
	 */
	private function is_disabled_by_form_setting( $form ) {
		return empty( $form['id'] ) || '1' === rgar( $this->get_form_settings( $form ), 'disable-recaptchav3' );
	}

	/**
	 * Determine whether a given form has disabled reCAPTCHA within its settings.
	 *
	 * @since 1.7
	 *
	 * @return bool
	 */
	private function is_disabled_by_quota_limit() {
		$recaptcha_result = $this->token_verifier->get_recaptcha_result();

		if ( is_a( $recaptcha_result, "stdClass" ) && property_exists( $recaptcha_result, 'score' ) && $recaptcha_result->score === 'disabled (quota limit)' ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate the form submission.
	 *
	 * @since 1.0
	 *
	 * @param array $submission_data The submitted form data.
	 *
	 * @return array
	 */
	public function validate_submission( $submission_data ) {
		$this->log_debug( __METHOD__ . '(): Validating form (#' . rgars( $submission_data, 'form/id' ) . ') submission.' );

		if ( $this->should_skip_validation( rgar( $submission_data, 'form' ) ) ) {
			$this->log_debug( __METHOD__ . '(): Validation skipped.' );

			return $submission_data;
		}

		$this->log_debug( __METHOD__ . '(): Validating reCAPTCHA v3.' );

		return $this->field->validation_check( $submission_data );
	}

	/**
	 * Check If reCaptcha validation should be skipped.
	 *
	 * In some situations where the form validation could be triggered twice, for example while making a stripe payment element transaction
	 * we want to skip the reCaptcha validation so it isn't triggered twice, as this will make it always fail.
	 *
	 * @since 1.4
	 * @since 1.5 Changed param to $form array.
	 *
	 * @param array $form The form being processed.
	 *
	 * @return bool
	 */
	public function should_skip_validation( $form ) {
		static $result = array();

		$form_id = rgar( $form, 'id' );
		if ( isset( $result[ $form_id ] ) ) {
			return $result[ $form_id ];
		}

		$result[ $form_id ] = true;

		if ( $this->is_preview() ) {
			$this->log_debug( __METHOD__ . '(): Yes! Form preview page.' );

			return true;
		}

		if ( ! $this->initialize_api() ) {
			$this->log_debug( __METHOD__ . '(): Yes! API not initialized.' );

			return true;
		}

		if ( $this->get_connection_type() === 'enterprise' && ! $this->enterprise_keys_configured() ) {
			$this->log_debug( __METHOD__ . '(): Yes! Enterprise has not been fully configured.' );

			return true;
		}

		if ( $this->is_disabled_by_form_setting( $form ) ) {
			$this->log_debug( __METHOD__ . '(): Yes! Disabled by form setting.' );

			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && ! isset( $_POST[ $this->field->get_input_name( $form_id ) ] ) ) {
			$this->log_debug( __METHOD__ . '(): Yes! REST request without input.' );

			return true;
		}

		// For older versions of Stripe, skip the first validation attempt and only validate on the second attempt. Newer versions of Stripe will validate twice without a problem.
		if ( $this->is_stripe_validation() && version_compare( gf_stripe()->get_version(), '5.4.3', '<' ) ) {
			$this->log_debug( __METHOD__ . '(): Yes! Older Stripe validation.' );

			return true;
		}

		$result[ $form_id ] = false;

		return false;
	}

	/**
	 * Check if the Enterprise keys are configured.
	 *
	 * @since 1.7.0
	 */
	public function enterprise_keys_configured() {
		$site_key = $this->plugin_settings->get_recaptcha_key( 'site_key_v3_enterprise' );
		$project  = $this->plugin_settings->get_recaptcha_key( 'project_number' );

		if ( ! ( $site_key && $project ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if this is a stripe validation request.
	 *
	 * @since 1.4
	 *
	 * @return bool Returns true if this is a stripe validation request. Returns false otherwise.
	 */
	public function is_stripe_validation() {
		return function_exists( 'gf_stripe' ) && rgpost( 'action' ) === 'gfstripe_validate_form';
	}

	/**
	 * Check if this is a preview request, taking into account Stripe's validation request.
	 *
	 * @since 1.4
	 *
	 * @return bool Returns true if this is a preview request. Returns false otherwise.
	 */
	public function is_preview() {

		return parent::is_preview() || ( $this->is_stripe_validation() && rgget( 'preview' ) === '1' );
	}

	/**
	 * Add the recaptcha v3 input and value to the draft.
	 *
	 * @since 1.2
	 *
	 * @param array  $submission_json The json containing the submitted values and the partial entry created from the values.
	 * @param string $resume_token    The resume token.
	 * @param array  $form            The form data.
	 *
	 * @return string The json string for the submission with the recaptcha v3 input and value added.
	 */
	public function add_recaptcha_v3_input_to_draft( $submission_json, $resume_token, $form ) {
		$submission                                   = json_decode( $submission_json, true );
		$input_name                                   = $this->field->get_input_name( rgar( $form , 'id' ) );
		$submission[ 'partial_entry' ][ $input_name ] = rgpost( $input_name );

		return wp_json_encode( $submission );
	}

	/**
	 * Shows admin notice if the quota limit has been reached. Once the notice
	 * is dismissed, the admin notice will go away until the next time the
	 * quota limit is reached.
	 *
	 * @since 1.7
	 *
	 * @return void
	 */
	public function recaptcha_quota_notice ( ) {
		if ( ! current_user_can( 'gform_full_access' ) ) {
			return;
		}

		if ( false === get_option( self::RECAPTCHA_QUOTA_LIMIT_HIT ) ) {
			return;
		}

		?>
		<div class="notice notice-warning is-dismissible gf-notice"
				data-gf_recaptcha_quota_nonce="<?php echo wp_create_nonce( 'gf_recaptcha_quota_notice' ) ?>" >
			<h2><?php echo $this->_title; ?></h2>
			<p>
				<?php
					// translators: %s is the link markup.
					echo sprintf(
						esc_html__( 'You have reached the quota limit for reCAPTCHA set by Google. Please check the quota on your %sreCAPTCHA Account%s.', 'gravityformsrecaptcha' ),
						'<a href="https://cloud.google.com/security/products/recaptcha" target="_blank">',
						'</a>'
					);
				?>
			</p>
		</div>
		<script>
			jQuery( document ).ready( function( $ ) {
				$( document ).on( 'click', '.notice-dismiss', function() {
					var $div = $( this ).closest( 'div.notice' );
					if ( $div.length > 0 ) {
						var nonce = $div.data( 'gf_recaptcha_quota_nonce' );
						jQuery.ajax( {
							url: ajaxurl,
							data: {
								action: 'gf_recaptcha_quota_notice',
								nonce: nonce,
							},
						} );
					}
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Removes setting checked by the reCAPTCHA quota limit notice.
	 *
	 * @since 1.7
	 *
	 * @return void
	 */
	public function gf_recaptcha_quota_notice_dismiss() {
		check_admin_referer( 'gf_recaptcha_quota_notice', 'nonce' );
		delete_option( self::RECAPTCHA_QUOTA_LIMIT_HIT );
		wp_send_json_success();
	}

	/**
	 * Update and reload the settings.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function update_reload_settings() {
		if ( ! wp_verify_nonce( rgpost( 'nonce' ), 'connect_recaptcha' ) || ! $this->current_user_can_any( $this->_capabilities_form_settings ) ) {
			wp_send_json_error(
				array(
					'errors'   => true,
					'redirect' => '',
				)
			);
		}

		$redirect_url                = admin_url( 'admin.php?page=gf_settings&subview=' . $this->_slug );
		$settings                    = $this->get_plugin_settings();
		$settings['connection_type'] = sanitize_text_field( rgpost( 'connection_type' ) );

		// Updating options.
		$this->update_plugin_settings( $settings );

		wp_send_json_success(
			array(
				'errors'   => false,
				'redirect' => esc_url_raw( $redirect_url ),
			)
		);
	}

	/**
	 * Get the connection type.
	 *
	 * @since 1.7.0
	 *
	 * @return string The connection type
	 */
	public function get_connection_type() {
		$settings = $this->get_plugin_settings();
		return rgar( $settings, 'connection_type' );
	}

	/**
	 * Disconnects user from reCAPTCHA and deletes relevant settings.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function ajax_disconnect_recaptcha() {

		// Verify nonce and capability.
		$this->verify_ajax_nonce( 'gforms_google_recaptcha_disconnect' );

		if ( ! $this->current_user_can_any( $this->_capabilities_form_settings ) ) {
			$this->log_debug( __METHOD__ . '(): Permissions for form settings not met.' );
			wp_send_json_error( new WP_Error( 'google_recaptcha_error', wp_strip_all_tags( __( 'User does not have required permissions to setup reCAPTCHA.', 'gravityformsrecaptcha' ) ) ) );
		}

		delete_option( 'gravityformsaddon_gravityformsrecaptcha_settings' );
		delete_option( 'rg_gforms_captcha_public_key' );
		delete_option( 'rg_gforms_captcha_private_key' );
		delete_option( 'rg_gforms_captcha_type' );
		delete_option( 'gform_recaptcha_keys_status' );
		wp_send_json_success( array() );
	}

	/**
	 * Verify the ajax nonce.
	 *
	 * @param string $nonce_action The name of the nonce action. Defaults to 'connect_recaptcha'.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function verify_ajax_nonce( $nonce_action = 'connect_recaptcha' ) {
		if ( ! wp_verify_nonce( rgpost( 'nonce' ), $nonce_action ) ) {
			$this->log_debug( __METHOD__ . '(): Nonce validation failed.' );
			wp_send_json_error( new WP_Error( 'google_recaptcha_error', wp_strip_all_tags( __( 'Nonce validation has failed.', 'gravityformsrecatpcha' ) ) ) );
		}
	}

	/**
	 * Get the Enterprise site keys with Ajax.
	 *
	 * @param string|null $project The Google Project ID.
	 *
	 * @since 1.7.0
	 *
	 * @return false|void
	 */
	public function ajax_get_enterprise_site_keys( $project = null ) {

		$this->verify_ajax_nonce();

		if ( ! $this->initialize_api() ) {
			$this->log_debug( __METHOD__ . '(): Unable to initialize reCAPTCHA API.' );

			return false;
		}

		if ( ! $this->current_user_can_any( $this->_capabilities_form_settings ) ) {
			$this->log_debug( __METHOD__ . '(): User does not have Form Settings capability.' );

			return false;
		}

		// Retrieving data streams.
		$project   = $project ? $project : sanitize_text_field( rgpost( 'project' ) );
		$site_keys = $this->api->get_enterprise_site_keys( $project );
		if ( is_wp_error( $site_keys ) ) {
			$this->log_debug( __METHOD__ . '(): Error retrieving sitekeys associated with the selected project.' );
			wp_send_json_error( new WP_Error( 'google_recaptcha_error', wp_strip_all_tags( __( 'There was an error retrieving reCAPTHCA site keys.', 'gravityformsrecaptcha' ) ) ) );
		}

		$data = array();

		foreach ( $site_keys['keys'] as $site_key ) {
			$data[] = array(
				'value'       => basename( $site_key['name'] ),
				'displayName' => $site_key['displayName'],
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Update the plugin settings with the selected enterprise data.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function ajax_save_recaptcha_enterprise_data() {

		$this->verify_ajax_nonce();

		$this->log_debug( __METHOD__ . '(): Saving reCAPTCHA Enterprise settings.' );
		$updated_settings = array();

		$updated_settings['project_number']         = sanitize_text_field( rgpost( 'project_number' ) );
		$updated_settings['project_id']             = sanitize_text_field( rgpost( 'project_id' ) );
		$updated_settings['site_key_v3_enterprise'] = sanitize_text_field( rgpost( 'site_key_v3_enterprise' ) );
		$updated_settings['site_key_display_name']  = sanitize_text_field( rgpost( 'site_key_display_name' ) );
		$updated_settings['score_threshold_v3']     = sanitize_text_field( rgpost( 'score_threshold_v3' ) );
		$updated_settings['disable_badge_v3']       = rgpost( 'disable_badge_v3' ) === '1' ? '1' : '0';

		$this->update_plugin_settings( $updated_settings );

		// Build redirect url and return it.
		$redirect_url = add_query_arg(
			array(
				'page'    => 'gf_settings',
				'subview' => 'gravityformsrecaptcha',
				'saved'   => '1',
			),
			admin_url( 'admin.php' )
		);
		wp_send_json_success( esc_url_raw( $redirect_url ) );
	}

}
