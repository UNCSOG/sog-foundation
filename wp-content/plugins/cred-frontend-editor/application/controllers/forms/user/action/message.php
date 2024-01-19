<?php

namespace OTGS\Toolset\CRED\Controller\Forms\User\Action;

use OTGS\Toolset\CRED\Controller\FormAction\Message\Base as MessageBase;

/**
 * User forms action as message base controller.
 * 
 * @since 2.1.2
 */
class Message extends MessageBase {

    /**
     * Apply the right context to messages displayed after submitting user forms.
     * 
     * That context basically means:
     * - set the current user to be the one created or edited, and restore afterwards.
     * - apply basic formatting filters.
     *
     * @param string $message
     * @return string
     * @since 2.1.2
     */
    protected function apply_content_to_action_message( $message ) {
        $old_user = wp_get_current_user();
        $target_user_id = (int) toolset_getget( '_target', 0 );

        if ( $target_user_id > 0 ) {
            wp_set_current_user( $target_user_id );
        }

        $message = apply_filters( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, $message );

        wp_set_current_user( $old_user->ID );

        return $message;
    }

}