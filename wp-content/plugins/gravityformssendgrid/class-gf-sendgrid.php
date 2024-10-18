<?php

defined( 'ABSPATH' ) or die();

GFForms::include_addon_framework();

/**
 * Gravity Forms SendGrid Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2018, Rocketgenius
 */
class GF_SendGrid extends GFAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the SendGrid Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from sendgrid.php
	 */
	protected $_version = GF_SENDGRID_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.2.3.8';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformssendgrid';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformssendgrid/sendgrid.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'https://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms SendGrid Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'SendGrid';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_sendgrid';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_sendgrid';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_sendgrid_uninstall';

	/**
	 * Defines the capabilities needed for the SendGrid Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_sendgrid', 'gravityforms_sendgrid_uninstall' );

	/**
	 * Contains an instance of the SendGrid API libray, if available.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    GF_SendGrid_API $api If available, contains an instance of the SendGrid API library.
	 */
	protected $api = null;

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return GF_SendGrid
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Register needed hooks.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function pre_init() {

		parent::pre_init();

		// Add SendGrid as a notification service.
		add_filter( 'gform_notification_services', array( $this, 'add_notification_service' ) );

		// Handle SendGrid notifications.
		add_filter( 'gform_pre_send_email', array( $this, 'maybe_send_email' ), 19, 4 );

	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 1.4
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return $this->is_gravityforms_supported( '2.5-beta-3.1' ) ? 'gform-icon--sendgrid' : 'dashicons-admin-generic';
	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Define plugin settings fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'description' => sprintf(
					'<p>%s</p>',
					sprintf(
						esc_html__( 'SendGrid makes it easy to reliably send email notifications. If you don\'t have a SendGrid account, you can %1$ssign up for one here%2$s. Once you have signed up, you can %3$sfind your API keys here%4$s.', 'gravityformssendgrid' ),
						'<a href="https://sendgrid.com" target="_blank">', '</a>',
						'<a href="https://app.sendgrid.com/settings/api_keys" target="_blank">', '</a>'
					)
				),
				'fields' => array(
					array(
						'name'              => 'apiKey',
						'label'             => esc_html__( 'SendGrid API Key', 'gravityformssendgrid' ),
						'type'              => 'text',
						'class'             => 'large',
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
					array(
						'type'     => 'save',
						'messages' => array(
							'success' => esc_html__( 'SendGrid settings have been updated.', 'gravityformssendgrid' ),
						),
					),
				),
			),
		);

	}





	// # NOTIFICATIONS -------------------------------------------------------------------------------------------------

	/**
	 * Add SendGrid as a notification service.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $services Existing notification services.
	 *
	 * @uses   GFAddOn::get_base_url()
	 * @uses   GF_SendGrid::initialize_api()
	 *
	 * @return array
	 */
	public function add_notification_service( $services ) {

		// If running GF prior to 2.4, check that API is initialized.
		if ( version_compare( GFFormsModel::get_database_version(), '2.4-beta-2', '<' ) && ! $this->initialize_api() ) {
			return $services;
		}

		// Add the service.
		$services['sendgrid'] = array(
			'label'            => esc_html__( 'SendGrid', 'gravityformssendgrid' ),
			'image'            => $this->is_gravityforms_supported( '2.5-beta-3.1' ) ? 'gform-icon--sendgrid' : $this->get_base_url() . '/images/icon.svg',
			'disabled'         => ! $this->initialize_api(),
			'disabled_message' => sprintf(
				esc_html__( 'You must %sauthenticate with SendGrid%s before sending emails using their service.', 'gravityformssendgrid' ),
				"<a href='" . esc_url( admin_url( 'admin.php?page=gf_settings&subview=' . $this->_slug ) ) . "'>",
				'</a>'
			),
		);

		return $services;

	}

	/**
	 * Send email through SendGrid.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array  $email          The email details.
	 * @param  string $message_format The message format, html or text.
	 * @param  array  $notification   The Notification object.
	 * @param  array  $entry          The current Entry object.
	 *
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAddOn::log_error()
	 * @uses   GFAPI::get_form()
	 * @uses   GFCommon::replace_variables()
	 * @uses   GF_SendGrid::initialize_api()
	 * @uses   GF_SendGrid_API::send_email()
	 *
	 * @return array
	 */
	public function maybe_send_email( $email, $message_format, $notification, $entry ) {

		// If email has been aborted, return the email.
		if ( $email['abort_email'] ) {
			$this->log_debug( __METHOD__ . "(): Not sending notification (#{$notification['id']} - {$notification['name']}) for {$entry['id']} via SendGrid because the notification has already been aborted by another Add-On." );
			return $email;
		}

		// If this is not a SendGrid notification or SendGrid API is not initialized, return the email.
		if ( 'sendgrid' !== rgar( $notification, 'service' ) || ! $this->initialize_api() ) {
			return $email;
		}

		// Get form object.
		$form = GFAPI::get_form( $entry['form_id'] );

		// Get from email address from email header.
		preg_match( '/<(.*)>/', $email['headers']['From'], $from_email );

		// Prepare email for SendGrid.
		$sendgrid_email = array(
			'from'             => array(
				'email' => $from_email[1],
				'name'  => GFCommon::replace_variables( rgar( $notification, 'fromName' ), $form, $entry, false, false, false, 'text' )
			),
			'personalizations' => array(
				array(
					'subject' => rgar( $email, 'subject' ),
					'to'      => array(),
				),
			),
			'content'          => array(
				array(
					'type'  => 'html' === $message_format ? 'text/html' : 'text/plain',
					'value' => $email['message'],
				),
			),
		);

		$to_emails = array_map( 'trim', explode( ',', $email['to'] ) );
		foreach ( $to_emails as $to_email ) {
			$sendgrid_email['personalizations'][0]['to'][] = array( 'email' => $to_email );
		}

		// Add BCC.
		if ( rgar( $notification, 'bcc' ) ) {
			$bcc_emails = GFCommon::replace_variables( rgar( $notification, 'bcc' ), $form, $entry, false, false, false, 'text' );
			$bcc_emails = array_map( 'trim', explode( ',', $bcc_emails ) );
			foreach ( $bcc_emails as $bcc_email ) {
				$sendgrid_email['personalizations'][0]['bcc'][] = array( 'email' => $bcc_email );
			}
		}

		// Add Reply To.
		if ( rgar( $notification, 'replyTo' ) ) {
			$sendgrid_email['reply_to']['email'] = GFCommon::replace_variables( rgar( $notification, 'replyTo' ), $form, $entry, false, false, false, 'text' );
		}

		// Add attachments.
		if ( ! empty( $email['attachments'] ) ) {

			// Loop through notification attachments, add to SendGrid email.
			foreach ( $email['attachments'] as $attachment ) {

				$sendgrid_email['attachments'][] = array(
					'content'  => base64_encode( file_get_contents( $attachment ) ),
					'filename' => basename( $attachment ),
				);

			}
		}

		/**
		 * Modify the email being sent by SendGrid.
		 *
		 * @since 1.0
		 * @since 1.1 Added entry parameter.
		 *
		 * @param array $sendgrid_email The SendGrid email arguments.
		 * @param array $email          The original email details.
		 * @param array $message_format The message format, html or text.
		 * @param array $notification   The Notification object.
		 * @param array $entry          The current Entry object.
		 */
		$sendgrid_email = apply_filters( 'gform_sendgrid_email', $sendgrid_email, $email, $message_format, $notification, $entry );

		try {

			// Log the email to be sent.
			$this->log_debug( __METHOD__ . "(): Sending notification (#{$notification['id']} - {$notification['name']}) for entry {$entry['id']} via SendGrid; " . print_r( $sendgrid_email, true ) );

			// Send email.
			$sent_email = $this->api->send_email( $sendgrid_email );

			// Log that email was sent.
			$this->log_debug( __METHOD__ . "(): Notification (#{$notification['id']} - {$notification['name']}) for entry {$entry['id']} successfully passed to SendGrid; " . print_r( $sent_email, true ) );

			// Add sending successful result note.
			// translators: Notification name followed by its ID. e.g. Admin Notification (ID: 5d4c0a2a37204).
			// translators: Add-on name followed by the successful result message. e.g. Gravity Forms SendGrid Add-On successfully passed the notification to SendGrid.
			GFFormsModel::add_note( $entry['id'], 0, sprintf( esc_html__( '%1$s (ID: %2$s)', 'gravityforms' ), $notification['name'], $notification['id'] ), sprintf( esc_html__( '%1$s successfully passed the notification to SendGrid.', 'gravityformssendgrid' ), $this->_title ), 'gravityformssendgrid', 'success' );

			// Prevent Gravity Forms from sending email.
			$email['abort_email'] = true;

		} catch ( Exception $e ) {

			$error_message = $e->getMessage();

			// Log that email failed to send.
			$this->log_error( __METHOD__ . "(): Unable to send notification (#{$notification['id']} - {$notification['name']}) for entry {$entry['id']} with SendGrid; " . $error_message );

			// Add sending error result note.
			// translators: Notification name followed by its ID. e.g. Admin Notification (ID: 5d4c0a2a37204).
			// translators: Add-on name followed by the error message. e.g. Gravity Forms SendGrid Add-On was unable to send the notification. Error: The from email does not contain a valid address.
			GFFormsModel::add_note( $entry['id'], 0, sprintf( esc_html__( '%1$s (ID: %2$s)', 'gravityforms' ), $notification['name'], $notification['id'] ), sprintf( esc_html__( '%1$s was unable to send the notification. Error: %2$s', 'gravityformssendgrid' ), $this->_title, $error_message ), 'gravityformssendgrid', 'error' );

			/**
			 * Allow developers to take additional actions when email sending fail.
			 *
			 * @since 1.4
			 *
			 * @param string $error_message                   The error message.
			 * @param array  $sendgrid_email                  The SendGrid email arguments.
			 * @param array  $email                           The original email details.
			 * @param array  $message_format                  The message format, html or text.
			 * @param array  $notification                    The Notification object.
			 * @param array  $entry                           The current Entry object.
			 */

			do_action( 'gform_sendgrid_send_email_failed', $error_message, $sendgrid_email, $email, $message_format, $notification, $entry );

		}

		return $email;

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes SendGrid API if credentials are valid.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses   GFAddOn::get_plugin_setting()
	 * @uses   GFAddOn::log_debug()
	 * @uses   GFAddOn::log_error()
	 * @uses   GF_SendGrid_API::has_scope()
	 * @uses   GF_SendGrid_API::load_scopes()
	 *
	 * @return bool|null
	 */
	public function initialize_api() {

		// If API object is already setup, return true.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Get the API key.
		$api_key = $this->get_plugin_setting( 'apiKey' );

		// If API key is empty, do not initialize API.
		if ( rgblank( $api_key ) ) {
			return null;
		}

		// Load the SendGrid API library.
		if ( ! class_exists( 'GF_SendGrid_API' ) ) {
			require_once( 'includes/class-gf-sendgrid-api.php' );
		}

		// Log that were testing the API credentials.
		$this->log_debug( __METHOD__ . '(): Validating API credentials.' );

		try {

			// Setup a new SendGrid API object.
			$sendgrid = new GF_SendGrid_API( $api_key );

			// Attempt to get profile information.
			$sendgrid->load_scopes();

			// Check for "mail.send" scope.
			if ( $sendgrid->has_scope( 'mail.send' ) ) {

				// Assign the SendGrid API object to this instance.
				$this->api = $sendgrid;

				// Log that test passed.
				$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

				return true;

			} else {

				// Log that test failed.
				$this->log_error( __METHOD__ . '(): API credentials are valid but do not have access to needed scopes.' );

				return false;

			}

		} catch ( Exception $e ) {

			// Log that test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; ' . $e->getMessage() );

			return false;

		}

	}

}
