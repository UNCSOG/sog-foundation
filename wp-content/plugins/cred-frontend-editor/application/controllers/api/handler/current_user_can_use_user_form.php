<?php

/**
 * Handler for the toolset_forms_current_user_can_use_user_form filter API.
 *
 * @since 2.1.1
 */
class CRED_Api_Handler_Current_User_Can_Use_User_Form 
    extends CRED_Api_Handler_Abstract 
    implements CRED_Api_Handler_Interface {

    private $di_toolset_access_condition = null;

    public function __construct( $di_toolset_access_condition = null) {
        $this->di_toolset_access_condition = ( null === $di_toolset_access_condition )
            ? new Toolset_Condition_Plugin_Access_Active()
            : $di_toolset_access_condition;
    }

    /**
     * @param array $arguments {
     *     Arguments passed to this filter
     * 
     *     @type bool $user_can The value to return
     *     @type int $form_id The ID of the form being questioned
     *     @type object|bool $managed_user Optional. The WP_User-like object for the user being edited, or false.
     * }
     * 
     * @return bool
     * 
     * @since 2.1.1
     */
    function process_call( $arguments ) {
        $user_can = toolset_getarr( $arguments, 0 );
        $form_id = toolset_getarr( $arguments, 1 );
        $managed_user = toolset_getarr( $arguments, 2, false );

        // Most probably $managed_user is a WP_User instance, or an object with the same properties.
        $managed_user = ( isset( $managed_user->ID ) )
            ? $managed_user
            : false;
        
        $form_settings = (array) get_post_meta( $form_id, '_cred_form_settings', true );
        $form_type = toolset_getnest( $form_settings, array( 'form', 'type' ) );

        switch ( $form_type ) {
            case 'new':
                return (bool) current_user_can( 'create_users_with_cred_' . $form_id );
                break;
            case 'edit':
                if ( empty( $managed_user ) ) {
                    // No user to edit
                    return false;
                }
                $current_user = wp_get_current_user();
                if ( 0 == $current_user->ID ) {
                    // Guests can not edit users by design
                    return false;
                }
                if ( $current_user->ID == $managed_user->ID ) {
                    // Editing the current user
                    $current_user_can_edit_self = current_user_can( 'edit_own_user_with_cred_' . $form_id );
                    if ( is_multisite() ) {
                        // In multisite, edit own only if user of the current blog
                        return ( $current_user_can_edit_self && is_user_member_of_blog( $managed_user->ID ) );
                    }
                    return $current_user_can_edit_self;
                } else {
                    // Editing another user
                    // By design, other user editing is blocked unless Access is installed
                    // and the current user has rights to do so
                    $current_user_can_edit_others = ( $this->di_toolset_access_condition->is_met() && current_user_can( 'edit_other_users_with_cred_' . $form_id ) );
                    if ( is_multisite() ) {
                        $super_admins = get_super_admins();
                        if ( 
                            is_array( $super_admins )
                            && isset( $managed_user->user_login )
                            && in_array( $managed_user->user_login, $super_admins )
                        ) {
                            return false;
                        }
                        return ( $current_user_can_edit_others && is_user_member_of_blog( $managed_user->ID ) );
                    }
                    return $current_user_can_edit_others;
                }
                break;
            case 'translation':
                return apply_filters( 'cred_wpml_glue_check_user_privileges', false );
                break;
            default:
                return false;
                break;
        }
        return $user_can;
    }
    
}