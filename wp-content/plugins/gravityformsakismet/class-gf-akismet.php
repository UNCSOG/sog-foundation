<?php

namespace Gravity_Forms\Gravity_Forms_Akismet;

defined( 'ABSPATH' ) || die();

use GFForms;
use GFAddOn;
use GFCommon;
use GFAPI;
use GFFormsModel;
use Akismet;
use Gravity_Forms\Gravity_Forms_Akismet\Settings;

// Include the Gravity Forms Add-On Framework.
GFForms::include_addon_framework();

/**
 * Gravity Forms Akismet Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Gravity Forms
 * @copyright Copyright (c) 2020-2021, Gravity Forms
 */
class GF_Akismet extends GFAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @var    GF_Akismet $_instance If available, contains an instance of this class
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Gravity Forms Akismet Add-On.
	 *
	 * @since  1.0
	 * @var    string $_version Contains the version.
	 */
	protected $_version = GF_AKISMET_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = GF_AKISMET_MIN_GF_VERSION;

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformsakismet';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformsakismet/akismet.php';

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
	protected $_title = 'Gravity Forms Akismet Add-On';

	/**
	 * Defines the short title of the add-on.
	 *
	 * @since  1.0
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Akismet';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capabilities needed for the Gravity Forms Akismet Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array(
		'gravityforms_akismet',
		'gravityforms_akismet_uninstall',
	);

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_akismet';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_akismet';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_akismet_uninstall';

	/**
	 * Instance of the object responsible for mapping Gravity Forms fields to the Akismet array.
	 *
	 * @since 1.0
	 *
	 * @var Akismet_Fields_Filter
	 */
	private $akismet_fields_filter;

	/**
	 * Wrapper class for form settings.
	 *
	 * @since 1.0
	 * @var Settings\Form_Settings
	 */
	private $form_settings;

	/**
	 * Instance of the Akismet API wrapper.
	 *
	 * @since 1.1
	 *
	 * @var null|API
	 */
	private $api;

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @since  1.0
	 *
	 * @return GF_Akismet $_instance An instance of the GF_Akismet class.
	 */
	public static function get_instance() {

		if ( self::$_instance == null ) {
			self::$_instance = new GF_Akismet();
		}

		return self::$_instance;

	}

	/**
	 * Pre-initialize add-on services.
	 *
	 * @since 1.0
	 */
	public function pre_init() {
		require_once __DIR__ . '/includes/class-akismet-fields-filter.php';
		require_once __DIR__ . '/includes/settings/class-form-settings.php';

		$this->akismet_fields_filter = new Akismet_Fields_Filter( $this );
		$this->form_settings         = new Settings\Form_Settings( $this );

		$this->add_disable_core_filter();
		add_filter( 'gform_plugin_settings_fields', array( $this, 'remove_core_settings_field' ) );
		add_filter( 'gform_entry_is_spam', array( $this, 'is_entry_spam' ), 80, 3 );
		add_filter( 'gform_update_status', array( $this, 'entry_status_change' ), 1, 3 );
		add_filter( 'gform_form_tag', array( $this, 'add_akismet_inputs' ), 50, 2 );

		parent::pre_init();
	}

	// # FORM DISPLAY --------------------------------------------------------------------------------------------------

	/**
	 * Callback for gform_form_tag; adds the Akismet honeypot inputs to the form.
	 *
	 * @since 1.1
	 *
	 * @param string $form_tag The opening HTML form tag.
	 * @param array  $form     The form currently being prepared for display.
	 *
	 * @return string
	 */
	public function add_akismet_inputs( $form_tag, $form ) {
		remove_filter( 'gform_get_form_filter', array( 'Akismet', 'inject_custom_form_fields' ) );

		if ( empty( $form_tag ) || ! $this->is_enabled_form( $form ) ) {
			return $form_tag;
		}

		static $field_count = 0;
		++ $field_count;

		$prefix = 'ak_';
		$value  = mt_rand( 0, 250 );
		$script = GFCommon::get_inline_script_tag( sprintf( 'document.getElementById( "ak_js_%d" ).setAttribute( "value", ( new Date() ).getTime() );', $field_count ), false );

		$form_tag .= <<<EOD
					<div style="display: none !important;" class="akismet-fields-container gf_invisible" data-prefix="{$prefix}">
						<label>&#916;<textarea name="{$prefix}hp_textarea" cols="45" rows="8" maxlength="100"></textarea></label>
						<input type="hidden" id="{$prefix}js_{$field_count}" name="{$prefix}js" value="{$value}" />
						{$script}
					</div>
EOD;

		return $form_tag;
	}

	// # ENTRY PROCESSING ----------------------------------------------------------------------------------------------

	/**
	 * Callback for gform_entry_is_spam; performs the Akimset spam check.
	 *
	 * @since 1.1
	 *
	 * @param bool  $is_spam Indicates if the submission has been flagged as spam.
	 * @param array $form    The form currently being processed.
	 * @param array $entry   The entry currently being processed.
	 *
	 * @return bool
	 */
	public function is_entry_spam( $is_spam, $form, $entry ) {
		remove_filter( 'gform_entry_is_spam', array( 'GFCommon', 'entry_is_spam_akismet' ), 90 );

		$entry_id = (int) rgar( $entry, 'id' );
		if ( $is_spam ) {
			$this->log_debug( __METHOD__ . sprintf( '(): Entry #%d has already been marked as spam by another anti-spam solution.', $entry_id ) );

			return $is_spam;
		}

		if ( ! $this->is_enabled_form( $form ) ) {
			$this->log_debug( __METHOD__ . sprintf( '(): Not evaluating entry #%d; integration disabled for form #%d.', $entry_id, rgar( $form, 'id' ) ) );

			return $is_spam;
		}

		$fields = $this->get_akismet_fields( $form, $entry, 'submit' );
		if ( empty( $fields ) ) {
			$this->log_debug( __METHOD__ . sprintf( '(): No values to evaluate for entry #%d.', $entry_id ) );

			return $is_spam;
		}

		$this->initalize_api();
		$response = $this->api->spam_check( $fields );

		if ( is_wp_error( $response ) ) {
			$this->log_debug( __METHOD__ . sprintf( '(): Spam check failed for entry #%d; ', $entry_id ) . $response->get_error_message() );

			return false;
		} elseif ( $response['body'] === 'true' ) {
			$this->log_debug( __METHOD__ . sprintf( '(): Entry #%d IS spam; ', $entry_id ) . print_r( $response, true ) );
			GFCommon::set_spam_filter( rgar( $form, 'id' ), $this->get_short_title(), '' );

			return true;
		} elseif ( $response['body'] === 'false' ) {
			$this->log_debug( __METHOD__ . sprintf( '(): Entry #%d is NOT spam; ', $entry_id ) . print_r( $response, true ) );

			return false;
		}

		$this->log_debug( __METHOD__ . sprintf( '(): Spam check failed for entry #%d; ', $entry_id ) . print_r( $response, true ) );

		return false;
	}

	/**
	 * Callback for gform_update_status; notifies Akismet that the entry has been manually marked as spam or ham.
	 *
	 * @since 1.1
	 *
	 * @param int    $entry_id       The ID of the entry the status changed for.
	 * @param string $new_value      The value value of the status property.
	 * @param string $previous_value The previous value of the status property.
	 *
	 * @return void
	 */
	public function entry_status_change( $entry_id, $new_value, $previous_value ) {
		$mark_as_spam = ( $new_value === 'spam' && $previous_value === 'active' );
		$mark_as_ham  = ( $new_value === 'active' && $previous_value === 'spam' );

		if ( ! $mark_as_spam && ! $mark_as_ham ) {
			return;
		}

		$entry = GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) ) {
			return;
		}

		$form = GFAPI::get_form( rgar( $entry, 'form_id' ) );
		if ( ! $form ) {
			return;
		}

		if ( ! $this->is_enabled_form( $form ) ) {
			$this->log_debug( __METHOD__ . sprintf( '(): Not processing entry #%d; integration disabled for form #%d.', $entry_id, rgar( $form, 'id' ) ) );

			return;
		}

		$action = $mark_as_spam ? 'spam' : 'ham';
		$fields = $this->get_akismet_fields( $form, $entry, $action );
		if ( empty( $fields ) ) {
			$this->log_debug( __METHOD__ . sprintf( '(): No values to evaluate for entry #%d.', $entry_id ) );

			return;
		}

		$this->initalize_api();

		if ( $mark_as_spam ) {
			$note     = esc_html__( 'Akismet notified that the entry was marked as spam.', 'gravityformsakismet' );
			$response = $this->api->submit_spam( $fields );
		} else {
			$note     = esc_html__( 'Akismet notified that the entry was marked as not spam.', 'gravityformsakismet' );
			$response = $this->api->submit_ham( $fields );
		}

		$this->add_note( $entry_id, $note );
		$this->log_debug( __METHOD__ . sprintf( '(): Akismet notified that entry #%d was marked as %s; ', $entry_id, $action ) . print_r( $response, true ) );
	}

	// # FORM SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Define form settings fields.
	 *
	 * @since  1.0
	 *
	 * @param array $form The current form.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return $this->form_settings->get_fields( $form );
	}

	/**
	 * The settings page icon.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_menu_icon() {
		return 'gform-icon--akismet';
	}

	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Returns the fields to be displayed on the Forms > Settings > Akismet page.
	 *
	 * @since 1.1
	 *
	 * @return array[]
	 */
	public function plugin_settings_fields() {
		static $fields;

		if ( ! $fields ) {
			require_once __DIR__ . '/includes/settings/class-plugin-settings.php';
			$fields = ( new Settings\Plugin_Settings( $this ) )->get_fields();
		}

		return $fields;
	}

	/**
	 * Feedback callback for the API key field.
	 *
	 * @since 1.1
	 *
	 * @param string $api_key The value of the API key field.
	 *
	 * @return bool|null
	 */
	public function verify_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return null;
		}

		$this->initalize_api();
		$response = $this->api->verify_key( $api_key );
		if ( is_wp_error( $response ) ) {
			$this->log_error( __METHOD__ . '(): Unable to verify key; ' . $response->get_error_message() );

			return false;
		} elseif ( $response['body'] === 'valid' ) {
			$this->log_debug( __METHOD__ . '(): Key is valid; ' . print_r( $response, true ) );

			return true;
		} elseif ( $response['body'] === 'invalid' ) {
			$this->log_debug( __METHOD__ . '(): Key is invalid; ' . print_r( $response, true ) );

			return false;
		}

		$this->log_debug( __METHOD__ . '(): Unable to verify key; ' . print_r( $response, true ) );

		return false;
	}

	// # DISABLE OLD GF CORE -------------------------------------------------------------------------------------------
	// GF 2.9.11.2+: GFCommon::has_akismet() disables the core integration when the add-on is active and includes initalize_api().

	/**
	 * Helper to add the filter that aims to disable the core integration via the rg_gforms_enable_akismet option.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public function add_disable_core_filter() {
		if ( ! $this->is_akismet_plugin_active() ) {
			return;
		}

		add_filter( 'pre_option_rg_gforms_enable_akismet', array( $this, 'disable_core_option' ), PHP_INT_MAX );
	}

	/**
	 * Helper to remove the callback that filters the rg_gforms_enable_akismet option.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public function remove_disable_core_filter() {
		remove_filter( 'pre_option_rg_gforms_enable_akismet', array( $this, 'disable_core_option' ), PHP_INT_MAX );
	}

	/**
	 * Callback for the pre_option_rg_gforms_enable_akismet filter.
	 *
	 * @since 1.1
	 *
	 * @return string
	 */
	public function disable_core_option() {
		if ( $this->is_akismet_plugin_active() ) {
			add_filter( 'gform_akismet_enabled', array( $this, 'disable_core_akismet_enabled' ), PHP_INT_MAX );
		}

		return '0';
	}

	/**
	 * Callback for the core version of the gform_akismet_enabled filter.
	 *
	 * @since 1.1
	 *
	 * @param bool $enabled Indicates if the Akismet integration is enabled.
	 *
	 * @return string
	 */
	public function disable_core_akismet_enabled( $enabled ) {
		remove_filter( 'gform_akismet_enabled', array( $this, 'disable_core_akismet_enabled' ), PHP_INT_MAX );

		return $this->is_akismet_plugin_active() ? '0' : $enabled;
	}

	/**
	 * Callback for gform_plugin_settings_fields; removes the core toggle.
	 *
	 * @since 1.1
	 *
	 * @param array $fields The fields to be displayed on the Forms > Settings page.
	 *
	 * @return array[]
	 */
	public function remove_core_settings_field( $fields ) {
		unset( $fields['akismet'] );

		return $fields;
	}

	// # UPGRADE -------------------------------------------------------------------------------------------------------

	/**
	 * Populates the add-on settings.
	 *
	 * @since 1.1
	 *
	 * @param string $previous_version Empty or the previously installed version number.
	 *
	 * @return void
	 */
	public function upgrade( $previous_version ) {
		if ( ! empty( $this->get_plugin_settings() ) ) {
			return;
		}

		$this->update_plugin_settings( array(
			'enabled' => $this->is_enabled_core(),
			'api_key' => $this->get_akismet_plugin_api_key(),
		) );
	}

	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * Initializes the API.
	 *
	 * @since 1.1
	 *
	 * @return void
	 */
	public function initalize_api() {
		if ( ! empty( $this->api ) ) {
			return;
		}

		require_once __DIR__ . '/includes/class-api.php';
		$this->api = new API( $this );
	}

	/**
	 * Determines if the Akismet integration is enabled for the site on the Forms > Settings > Akismet page.
	 *
	 * @since  1.0
	 * @since  1.1 Updated to use the add-on setting.
	 *
	 * @return bool
	 */
	public function is_enabled_global() {
		static $enabled = null;

		if ( is_null( $enabled ) ) {
			$enabled = $this->get_plugin_setting( 'enabled' );
		}

		return $enabled;
	}

	/**
	 * Determines if the Akismet integration is enabled for the supplied form.
	 *
	 * @since  1.0
	 *
	 * @param array $form The current form.
	 *
	 * @return bool
	 */
	public function is_enabled_form( $form ) {
		static $status = array();
		$form_id = (int) rgar( $form, 'id', 0 );

		if ( ! isset( $status[ $form_id ] ) ) {
			$enabled = $this->is_enabled_global();

			if ( $enabled ) {
				$settings = $this->get_form_settings( $form );
				$enabled  = empty( $settings ) || rgar( $settings, 'enabled' ) === '1';
			}

			/**
			 * Allows the Akismet integration to be enabled or disabled.
			 *
			 * @since 1.1 Copied over from GFCommon::akismet_enabled().
			 *
			 * @param bool $enabled Indicates if the Akismet integration is enabled.
			 * @param int  $form_id The ID of the form being processed.
			 */
			$status[ $form_id ] = (bool) gf_apply_filters( array( 'gform_akismet_enabled', $form_id ), $enabled, $form_id );
		}

		return $status[ $form_id ];
	}

	/**
	 * Determines if the core integration is enabled.
	 *
	 * @since 1.1
	 *
	 * @return bool
	 */
	public function is_enabled_core() {
		$this->remove_disable_core_filter();
		$enabled = get_option( 'rg_gforms_enable_akismet' );
		$this->add_disable_core_filter();

		return $enabled === false || $enabled === '1';
	}

	/**
	 * Determines if the Akismet plugin is active.
	 *
	 * @since 1.1
	 *
	 * @return bool
	 */
	public function is_akismet_plugin_active() {
		return class_exists( 'Akismet' );
	}

	/**
	 * Helper to get the API key from the Akismet plugin.
	 *
	 * @since 1.1
	 *
	 * @return string
	 */
	public function get_akismet_plugin_api_key() {
		if ( $this->is_akismet_plugin_active() ) {
			return (string) Akismet::get_api_key();
		}

		return '';
	}

	/**
	 * Gets the data to be sent to Akismet.
	 *
	 * @since 1.1
	 *
	 * @param array  $form   The form which created the entry.
	 * @param array  $entry  The entry being processed.
	 * @param string $action The action triggering the Akismet request: submit, spam, or ham.
	 *
	 * @return array
	 */
	public function get_akismet_fields( $form, $entry, $action ) {
		$form_id = (int) rgar( $form, 'id' );
		$this->log_debug( sprintf( '%s(): action: %s; form: %d; entry: %d.', __METHOD__, $action, $form_id, rgar( $entry, 'id' ) ) );

		$settings = $this->get_form_settings( $form );

		if ( empty( $settings ) ) {
			$this->log_debug( __METHOD__ . '(): Settings not configured; using defaults.' );
			$settings = $this->form_settings->get_default_settings( $form, $entry );
		}

		$this->log_debug( __METHOD__ . '(): Settings => ' . print_r( $settings, true ) );

		$fields = $this->akismet_fields_filter->get_fields( $settings, $form, $entry, $action );

		$this->log_debug( __METHOD__ . '(): Values to be sent to Akismet => ' . print_r( $fields, true ) );

		return $fields;
	}

	// # DEPRECATED ----------------------------------------------------------------------------------------------------

	/**
	 * Callback method to the `minimum_requirements` override.
	 *
	 * This method ensures we have all of the minimum requirements needed run the add-on.
	 *
	 * @since 1.0
	 * @depecated 1.1
	 * @remove-in 1.2
	 *
	 * @return array
	 */
	public function check_minimum_requirements() {
		_deprecated_function( __METHOD__, '1.1' );

		$meets_requirements = true;
		$errors             = array();

		if ( ! GFCommon::has_akismet() ) {
			$meets_requirements = false;
			$errors[]           = esc_html__( 'The Akismet plugin is either inactive or not installed.', 'gravityformsakismet' );
		}

		if ( ! $this->is_enabled_global() ) {
			$meets_requirements = false;
			$errors[]           = esc_html__( 'To use this add-on, please visit the Forms -> Settings page to enable Akismet integration', 'gravityformsakismet' );
		}

		return $meets_requirements
			? array( 'meets_requirements' => true )
			: array(
				'meets_requirements' => false,
				'errors'             => $errors,
			);
	}

	/**
	 * Enables or disables Akismet based on the form settings.
	 *
	 * @since 1.0
	 * @depecated 1.1
	 * @remove-in 1.2
	 *
	 * @param bool $enabled Indicates if Akismet is enabled.
	 * @param int  $form_id The ID of the form being processed.
	 *
	 * @return bool
	 */
	public function filter_akismet_enabled( $enabled, $form_id ) {
		_deprecated_function( __METHOD__, '1.1' );

		if ( ! $enabled ) {
			return false;
		}

		if ( ! Akismet::get_api_key() ) {
			$this->log_debug( __METHOD__ . '(): Aborting; Akismet is not configured.' );

			return false;
		}

		$form = GFAPI::get_form( $form_id );

		if ( ! $form ) {
			return false;
		}

		return $this->is_enabled_form( $form );
	}

	/**
	 * Replaces the default Akismet field mappings with the new mappings based on the form specific configuration.
	 *
	 * @since     1.0
	 * @depecated 1.1
	 * @remove-in 1.2
	 *
	 * @param array  $akismet_fields The data passed from Akismet to Gravity Forms.
	 * @param array  $form           The form which created the entry.
	 * @param array  $entry          The form which created the entry.
	 * @param string $action         The action triggering the Akismet request: submit, spam, or ham.
	 *
	 * @return array
	 */
	public function filter_akismet_fields( $akismet_fields, $form, $entry, $action ) {
		_deprecated_function( __METHOD__, '1.1' );

		return $this->get_akismet_fields( $form, $entry, $action );
	}

	/**
	 * Handles any necessary processes after receiving a response from Akismet.
	 *
	 * @since 1.0
	 * @depecated 1.1
	 * @remove-in 1.2
	 *
	 * @param array|WP_Error $response HTTP response or WP_Error object.
	 * @param string         $context  Context under which the hook is fired.
	 * @param string         $class    HTTP transport used.
	 * @param array          $args     HTTP request arguments.
	 * @param string         $url      The request URL.
	 */
	public function handle_akismet_response( $response, $context, $class, $args, $url ) {
		_deprecated_function( __METHOD__, '1.1' );

		if ( ! $this->is_akismet_response( $response, $args, $url ) ) {
			return;
		}

		$this->log_debug( __METHOD__ . '(): request body => ' . $args['body'] );

		$response_body = wp_remote_retrieve_body( $response );

		$this->log_debug(
			__METHOD__ . '(): response => '
			. print_r(
				array(
					wp_remote_retrieve_headers( $response ),
					$response_body,
				),
				true
			)
		);

		$this->maybe_mark_as_spam( $response_body );

		remove_action( 'http_api_debug', array( $this, 'handle_akismet_response' ) );
	}

	/**
	 * Checks whether the current response being processed is for Akismet.
	 *
	 * @since 1.0
	 * @depecated 1.1
	 * @remove-in 1.2
	 *
	 * @param array|WP_Error $response The API response.
	 * @param array          $args     HTTP request arguments.
	 * @param string         $url      The request URL.
	 *
	 * @return bool
	 */
	private function is_akismet_response( $response, $args, $url ) {
		_deprecated_function( __METHOD__, '1.1' );

		return (
			rgar( $args, 'method' ) === 'POST'
			&& stripos( $url, 'rest.akismet.com' ) !== false
			&& ! is_wp_error( $response )
		);
	}

	/**
	 * Replaces the created_by merge tag.
	 *
	 * @since 1.0
	 * @depecated 1.1
	 * @remove-in 1.2
	 *
	 * @param string $text       The current text in which merge tags are being replaced.
	 * @param array  $form       The current form object.
	 * @param array  $entry      The current entry object.
	 * @param bool   $url_encode Whether or not to encode any URLs found in the replaced value.
	 * @param bool   $esc_html   Whether or not to encode HTML found in the replaced value.
	 * @param bool   $nl2br      Whether or not to convert newlines to break tags.
	 * @param string $format     The format requested for the location the merge is being used. Possible values: html, text or url.
	 *
	 * @return string
	 */
	public function filter_pre_replace_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		_deprecated_function( __METHOD__, '1.1' );

		if ( strpos( $text, '{' ) === false ) {
			return $text;
		}

		preg_match_all( '/{created_by:(.*?)}/', $text, $matches, PREG_SET_ORDER );

		if ( empty( $matches ) ) {
			return $text;
		}

		$entry_creator = ! empty( $entry['created_by'] ) ? get_userdata( $entry['created_by'] ) : false;
		foreach ( $matches as $match ) {
			$full_tag = $match[0];
			$property = $match[1];

			if ( $entry_creator && $property !== 'user_pass' ) {
				$value = $entry_creator->get( $property );
				$value = $url_encode ? urlencode( $value ) : $value;
			} else {
				$value = '';
			}

			$text = str_replace( $full_tag, $value, $text );
		}

		return $text;
	}

	/**
	 * Adds additional actions for an entry if it needs to be marked as spam.
	 *
	 * @since 1.0
	 * @depecated 1.1
	 * @remove-in 1.2
	 *
	 * @param array $response_body HTTP response body.
	 */
	private function maybe_mark_as_spam( $response_body ) {
		_deprecated_function( __METHOD__, '1.1' );

		if ( $response_body !== 'true' ) {
			return;
		}

		add_action( 'gform_entry_created', array( $this, 'add_marked_as_spam_note_to_entry' ) );
	}

	/**
	 * Adds a note to an entry at the time that it is marked as spam.
	 *
	 * @since 1.0
	 * @depecated 1.1
	 * @remove-in 1.2
	 *
	 * @param array $entry The entry data.
	 */
	public function add_marked_as_spam_note_to_entry( $entry ) {
		_deprecated_function( __METHOD__, '1.1' );

		if ( rgar( $entry, 'status' ) !== 'spam' ) {
			return;
		}

		$this->log_debug( __METHOD__ . '(): marking entry as spam.' );

		$this->add_note( rgar( $entry, 'id' ), esc_html__( 'This entry has been marked as spam.', 'gravityformsakismet' ), 'success' );
	}

}
