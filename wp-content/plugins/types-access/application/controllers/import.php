<?php

namespace OTGS\Toolset\Access\Controllers;

/**
 * Show summary notice when import is finished
 * Process zip/xml file import on from submit
 *
 * Class Import
 *
 * @package OTGS\Toolset\Access\Controllers
 * @since 2.7
 */
class Import {

	private static $instance;

	private static $import_messages;


	/**
	 * @return Import
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function initialize() {
		self::get_instance();
	}


	/**
	 * Check if a user can import and start import process
	 */
	public function access_import_on_form_submit() {
		if (
			current_user_can( 'manage_options' )
			&& isset( $_FILES['access-import-file'] )
			&& isset( $_POST['access-import'] )
			&& isset( $_POST['access-import-form'] )
			&& wp_verify_nonce( $_POST['access-import-form'], 'access-import-form' )
		) {
			// @todo move this to wp_loaded and check current_user_can FGS!
			\TAccess_Loader::load( 'CLASS/XML_Processor' );
			$options = array();
			if ( isset( $_POST['access-overwrite-existing-settings'] ) ) {
				$options['access-overwrite-existing-settings'] = 1;
			}
			if ( isset( $_POST['access-remove-not-included-settings'] ) ) {
				$options['access-remove-not-included-settings'] = 1;
			}
			self::$import_messages = \Access_XML_Processor::importFromXML( $_FILES['access-import-file'], $options );
		}
	}


	/**
	 * Show import summary
	 */
	public function access_import_notices_messages() {
		$import_messages = self::$import_messages;
		$display_messages = array();
		if ( ! is_null( $import_messages ) ) {
			if ( is_wp_error( $import_messages ) ) {
				$display_messages = array(
					'type' => 'error',
					'message' => '<p>'
						. $import_messages->get_error_message( $import_messages->get_error_code() )
						. '</p>',
				);
			} elseif ( is_array( $import_messages ) ) {
				$display_messages = array(
					'type' => 'updated',
					'message' => '<h3>' . __( 'Access import summary :', 'wpcf-access' ) . '</h3>'
						. '<ul>'
						. '<li>' . __( 'Settings Imported :', 'wpcf-access' ) . $import_messages['new'] . '</li>'
						. '<li>' . __( 'Settings Overwritten :', 'wpcf-access' ) . $import_messages['updated'] . '</li>'
						. '<li>' . __( 'Settings Deleted :', 'wpcf-access' ) . $import_messages['deleted'] . '</li>'
						. '</ul>',
				);
			}
			if ( ! empty( $display_messages ) ) {
				?>
				<div class="message <?php echo $display_messages['type']; ?>">
					<p><?php echo $display_messages['message']; ?></p></div>
				<?php
			}
		}
	}

}
