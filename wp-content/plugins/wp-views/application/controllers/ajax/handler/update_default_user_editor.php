<?php

/**
 * Handle saving the default user editor for Content Templates.
 *
 * @since 2.8
 */
class WPV_Ajax_Handler_Update_Default_User_Editor extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_UPDATE_DEFAULT_USER_EDITOR,
		) );

		$settings = WPV_Settings::get_instance();

		/**
		 * Filters the allowed user editors in order to offer Content Template editing through the supported theme
		 * editors when such theme is enabled.
		 *
		 * @params array $default_editors
		 */
		$allowed_user_editors = apply_filters(
			'wpv_filter_wpv_default_user_editor',
			array(
				Toolset_User_Editors_Editor_Basic::BASIC_SCREEN_ID,
				Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID,
			)
		);

		$settings->default_user_editor = toolset_getpost( 'wpv_default_user_editor', Toolset_User_Editors_Editor_Basic::BASIC_SCREEN_ID, $allowed_user_editors );
		$settings->save();

		wp_send_json_success();
	}

}
