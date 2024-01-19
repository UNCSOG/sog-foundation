<?php

/**
 * Handler for the toolset_forms_current_user_can_use_post_form filter API.
 *
 * @since 2.1.1
 */
class CRED_Api_Handler_Current_User_Can_Use_Post_Form 
    extends CRED_Api_Handler_Abstract 
    implements CRED_Api_Handler_Interface {

    /**
     * @param array $arguments {
     *     Arguments passed to this filter
     * 
     *     @type bool $user_can The value to return
     *     @type int $form_id The ID of the form being questioned
     *     @type object|bool $managed_post Optional. The WP_Post-like object for the post being edited, or false.
     * }
     * 
     * @return bool
     * 
     * @since 2.1.1
     */
    function process_call( $arguments ) {
        $user_can = toolset_getarr( $arguments, 0 );
        $form_id = toolset_getarr( $arguments, 1 );
        $managed_post = toolset_getarr( $arguments, 2, false );

        // Most probably $managed_post is a WP_Post instance, or an object with the same properties.
        $managed_post = ( isset( $managed_post->post_author ) )
            ? $managed_post
            : false;

        $form_settings = (array) get_post_meta( $form_id, '_cred_form_settings', true );
        $form_type = toolset_getnest( $form_settings, array( 'form', 'type' ) );
        
        switch ( $form_type ) {
            case 'new':
                return (bool) current_user_can( 'create_posts_with_cred_' . $form_id );
                break;
            case 'edit':
                if ( empty( $managed_post ) ) {
                    // No post to edit
                    return false;
                }
                $current_user = wp_get_current_user();
                if (
                    ! current_user_can( 'edit_own_posts_with_cred_' . $form_id )
                    && $current_user->ID == $managed_post->post_author
                ) {
                    // Current user can not edit own posts
                    return false;
                }
                if (
                    ! current_user_can( 'edit_other_posts_with_cred_' . $form_id )
                    && $current_user->ID != $managed_post->post_author
                ) {
                    // Current user can not edit others posts
                    return false;
                }
                return true;
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