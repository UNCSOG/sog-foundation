<?php

namespace OTGS\Toolset\CRED\Controller\Forms\User\PageExtension;

/**
 * Post form content metabox extension.
 *
 * @since 2.1
 */
class Content {

    /**
     * Generate the user form content editor, and populate its toolbar.
     *
     * @param object $form
     * @param array $callback_args
     *
     * @since 2.1
     */
    public function print_metabox_content( $form, $callback_args = array() ) {
        // The $form WP_Post gets its content with an 'edit' filter on its content: it is HTML encoded
        $form = $form->filter( 'raw' );
        $args = toolset_getarr( $callback_args, 'args', array() );
        $extra = toolset_getarr( $args, 'extra', array() );
        $extra_js = $extra->js;
        $extra_css = $extra->css;
        $context = array(
            'extra' => $extra,
            'extra_js' => $extra_js,
            'extra_css' => $extra_css,
            'form' => $form,
            'notice' => $this->get_inline_notice_content(),
			'required_notice' => $this->get_inline_required_notice_content(),
        );
        $template_repository = \CRED_Output_Template_Repository::get_instance();
        $renderer = \Toolset_Renderer::get_instance();
        $output = $renderer->render(
            $template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_CONTAINER ),
            $context
        );
   }

    /**
     * Gets the content of a Toolset notice, adds a inline class a returns /**
     *
     * @since 2.2
     */
    private function get_inline_notice_content() {
        ob_start();
        // translators: There are 2 kind of editors (visual and HTML), if the user switchs from html to editor, changes could be lost.
        $notice = new \Toolset_Admin_Notice_Dismissible( 'scaffold_html_editor', __( 'Changes that you make in the HTML editor will be lost if you switch back to the Visual editor.', 'wp-cred' ) );
        if ( \Toolset_Admin_Notices_Manager::is_notice_dismissed( $notice ) ) {
            return '';
        }
        $notice->set_inline_mode( true );
        $notice->render();
        $notice_content = ob_get_clean();

        return $notice_content;
	}

	/**
	 * Gets the content of a Toolset required notice, adds a inline class a returns /**
	 *
	 * @since 2.6.7
	 */
	private function get_inline_required_notice_content() {
		ob_start();
		// translators: "Default Value" is the name of an option.
		$notice = new \Toolset_Admin_Notice_Dismissible( 'scaffold_html_editor', __( 'Please set Default Value for the fields you marked as required.', 'wp-cred' ) );
		if ( \Toolset_Admin_Notices_Manager::is_notice_dismissed( $notice ) ) {
			return '';
		}
		$notice->set_type( 'error' );
		$notice->set_inline_mode( true );
		$notice->render();
		$notice_content = ob_get_clean();
		return $notice_content;
	}
}
