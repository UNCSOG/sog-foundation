<?php

namespace OTGS\Toolset\CRED\Controller\Forms\User\PageExtension;

/**
 * Form Access metabox extension.
 * 
 * @since 2.1
 * @todo Review this HTML layout, FGS
 */
class Access {

    /**
     * Generate the section for the Access integration information.
     *
     * @param object $form
     * @param array $callback_args
     * @param \Toolset_Condition_Plugin_Access_Active $di_toolset_access_condition
     * 
     * @since 2.1
     */
    public function print_metabox_content( $form, $callback_args = array(), $di_toolset_access_condition = null ) {
        $this->di_toolset_access_condition = ( null === $di_toolset_access_condition )
            ? new \Toolset_Condition_Plugin_Access_Active()
            : $di_toolset_access_condition;
        $is_access_active = $this->di_toolset_access_condition->is_met();

        $template_repository = \CRED_Output_Template_Repository::get_instance();
        $renderer = \Toolset_Renderer::get_instance();

        $template_data = array(
            'is_access_active' => $is_access_active
        );
        $renderer->render(
            $template_repository->get( \CRED_Output_Template_Repository::METABOX_USER_ACCESS ),
            $template_data
        );
   }
}