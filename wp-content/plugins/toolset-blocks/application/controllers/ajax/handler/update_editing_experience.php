<?php
/**
 * Handle updating the editing experience in this site.
 *
 * @since 3.0
 */
class WPV_Ajax_Handler_Update_Editing_Experience extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_UPDATE_EDITING_EXPERIENCE,
		) );

		$settings = WPV_Settings::get_instance();
		$experience = toolset_getpost( 'experience', 'classic', array( 'classic', 'blocks', 'mixed' ) );
		$settings->editing_experience = $experience;
		$settings->save();

		wp_send_json_success();
	}

}
