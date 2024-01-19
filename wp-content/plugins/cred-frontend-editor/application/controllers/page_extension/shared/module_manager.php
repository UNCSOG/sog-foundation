<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Shared\PageExtension;

/**
 * Form Module Manager metabox extension.
 * 
 * @since 2.1
 */
class ModuleManager {

    /**
     * Generate the Module Manager section.
     *
     * @param object $form
     * @param array $callback_args
     * 
     * @since 2.1
     */
    public function print_metabox_content( $form, $callback_args = array() ) {
        $form = $form->filter( 'raw' );

        $key = ( $form->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME ) 
            ? _CRED_MODULE_MANAGER_USER_KEY_ 
            : _CRED_MODULE_MANAGER_KEY_;
        $element = array( 
                'id' => $key . $form->ID, 
                'title' => $form->post_title, 
                'section' => $key 
        );
        do_action('wpmodules_inline_element_gui', $element);
   }
}