<?php
/**
 * Handle updating the editing experience in this site.
 *
 * @since 3.6.6
 */
class WPV_Ajax_Handler_Update_Toolset_Theme_Settings extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_UPDATE_TOOLSET_THEME_SETTINGS,
		) );

		$views_settings = WPV_Settings::get_instance();

		/**
		 * Allow Views WordPress widgets in Elementor.
		 */
		$disable_theme_settings = sanitize_text_field( toolset_getpost( 'disableToolsetThemeSettings', 'false' ) );
		$views_settings->disable_theme_settings = ( 'true' === $disable_theme_settings ) ? 1 : 0;

		// More settings might come here in the future.

		$views_settings->save();

		wp_send_json_success();
	}

}
