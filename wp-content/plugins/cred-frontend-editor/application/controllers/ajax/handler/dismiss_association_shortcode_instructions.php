<?php

/**
 * Dismiss the instructions on the associatio shortcode wizard.
 *
 * @todo Move the constts to a dedicated controller for dismissed notices/instructions/help boxes.
 *
 * @since m2m
 */
class CRED_Ajax_Handler_Dismiss_Association_Shortcode_Instructions extends Toolset_Ajax_Handler_Abstract{
	
	const ID = 'cred_instructions_manager';
	const OPTION_FIELD_DISMISSED_INSTRUCTION = 'dismissed-instructions';

	function process_call( $arguments ) {
		$this->ajax_begin( 
			array( 
				'nonce' => CRED_Ajax::CALLBACK_DISMISS_ASSOCIATION_SHORTCODE_INSTRUCTIONS, 
				'is_public' => true 
			) 
		);
		
		$current_user_id = get_current_user_id();
		
		if ( 0  === $current_user_id ) {
			$this->ajax_finish( array(), false );
		}
		
		$user_settings = get_user_meta( $current_user_id, self::ID, true );
	    $user_settings = empty( $user_settings ) ? array() : $user_settings;
	    $user_settings[ self::OPTION_FIELD_DISMISSED_INSTRUCTION ][ CRED_Ajax::CALLBACK_DISMISS_ASSOCIATION_SHORTCODE_INSTRUCTIONS ] = true;
	    update_user_meta( $current_user_id, self::ID, $user_settings );
		
		$this->ajax_finish( array(), true );
	}
	
}