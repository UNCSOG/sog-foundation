<?php

/**
 * Handle saving the default editor for WordPress Archive.
 *
 * @since 3.0
 */
class WPV_Ajax_Handler_Update_Default_Wpa_Editor extends Toolset_Ajax_Handler_Abstract {
	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_UPDATE_DEFAULT_WPA_EDITOR,
			)
		);

		$settings = WPV_Settings::get_instance();

		$settings->default_wpa_editor = toolset_getpost(
			'wpv_default_wpa_editor',
			Toolset_User_Editors_Editor_Basic::BASIC_SCREEN_ID,
			array(
				Toolset_User_Editors_Editor_Basic::BASIC_SCREEN_ID,
				Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID,
			)
		);

		$settings->save();

		wp_send_json_success();
	}

}
