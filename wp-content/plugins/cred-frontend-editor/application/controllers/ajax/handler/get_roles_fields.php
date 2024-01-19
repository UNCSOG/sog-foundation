<?php

use OTGS\Toolset\CRED\Model\Forms\User\Helper;

/**
 * Get the relevant user form fields given the affected user role(s).
 * 
 * @uses CRED_User_Form_Toolbar_Helper
 * 
 * @since 2.1
 */
class CRED_Ajax_Handler_Get_Roles_Fields extends Toolset_Ajax_Handler_Abstract {

	/**
	 * @var Toolset_Condition_Plugin_Types_Active
	 * 
	 * @since 2.1
	 */
	private $di_toolset_types_condition = null;

	public function __construct(
		$ajax_manager,
		$di_toolset_types_condition = null
	) {
        parent::__construct( $ajax_manager );
        $this->di_toolset_types_condition = ( null === $di_toolset_types_condition )
			? new Toolset_Condition_Plugin_Types_Active()
			: $di_toolset_types_condition;
    }

	function process_call( $arguments ) {
		$this->ajax_begin( 
			array( 
				'nonce' => CRED_Ajax::CALLBACK_GET_ROLES_FIELDS, 
				'parameter_source' => 'get', 
				'is_public' => true
			) 
		);

		$roles = toolset_getget( 'objectKey' );
		
		if ( empty( $roles ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing roles.', 'wp-cred' ) ), false );
		}

		$roles = toolset_ensarr( $roles, array( $roles ) );

		$toolbar_helper = new Helper( $roles, $this->di_toolset_types_condition );

		$results = $toolbar_helper->populate_items();
		
		$this->ajax_finish( $results, true );
	}
	
}