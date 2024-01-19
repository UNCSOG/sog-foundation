<?php

/**
 * Handler for the toolset_forms_current_user_can_use_any_attachment filter API.
 *
 * @since 2.4
 */
class CRED_Api_Handler_Current_User_Can_Use_Any_Attachment
	extends CRED_Api_Handler_Abstract
	implements CRED_Api_Handler_Interface {

	// phpcs:disable Squiz.Commenting.FunctionComment.ParamCommentFullStop
	// phpcs:disable Squiz.Scope.MethodScope.Missing
	/**
	 * @param array $arguments {
	 *     @type bool $user_can The value to return
	 * }
	 * @return bool
	 *
	 * @since 2.4
	 */
	function process_call( $arguments ) {
		$user_can = toolset_getarr( $arguments, 0, false );
		$form_id = toolset_getarr( $arguments, 1, 0 );

		$form_type = get_post_type( $form_id );

		if ( false === $form_type ) {
			return $user_can;
		}

		switch ( $form_type ) {
			case \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE:
				return current_user_can( 'use_any_attachment_with_cred_post_forms' );
			case \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE:
				return current_user_can( 'use_any_attachment_with_cred_user_forms' );
			case \CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE:
				return current_user_can( 'use_any_attachment_with_cred_rel_forms' );
		}

		return $user_can;
	}
	// phpcs:enable

}
