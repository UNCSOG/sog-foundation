<?php

/**
 * Get the relevant association form fields given the affected relationship.
 * 
 * @uses CRED_Association_Form_Toolbar_Helper
 * 
 * @since 2.0
 * @since 2.1 Use CRED_Association_Form_Toolbar_Helper to generate the revelant fields.
 */
class CRED_Ajax_Handler_Get_Relationship_Fields extends Toolset_Ajax_Handler_Abstract{

	function process_call( $arguments ) {
		$this->ajax_begin( 
			array( 
				'nonce' => CRED_Ajax::CALLBACK_GET_RELATIONSHIP_FIELDS, 
				'parameter_source' => 'get', 
				'is_public' => true
			) 
		);

		$relationship = toolset_getget( 'objectKey' );
		
		if ( empty( $relationship ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing relationship.', 'wp-cred' ) ), false );
		}
		
		$toolbar_helper = new CRED_Association_Form_Toolbar_Helper( $relationship );

		$results = $toolbar_helper->populate_items();
		
		$this->ajax_finish( $results, true );
	}
	
}