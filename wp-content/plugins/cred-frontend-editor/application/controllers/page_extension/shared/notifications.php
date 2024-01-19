<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Shared\PageExtension;

use OTGS\Toolset\CRED\Controller\Forms\Post\Main as PostMain;
use OTGS\Toolset\CRED\Controller\LinksManager;
use OTGS\Toolset\CRED\Model\Wordpress\Status;

/**
 * Form Notifications metabox extension.
 *
 * @since 2.1
 *
 */
class Notifications {

	/**
	 * @var Status
	 */
	private $status_model = null;

	public function __construct( Status $status_model_di = null ) {
		$this->status_model = ( null === $status_model_di )
			? Status::get_instance()
			: $status_model_di;
	}

    /**
     * Generate the section for the Access integration information.
     *
     * @param object $form
     * @param array $callback_args
     *
     * @since 2.1
     */
    public function print_metabox_content( $form, $callback_args = array() ) {
        $form = $form->filter( 'raw' );
        $form_type = $form->post_type;
        $notifications_object = toolset_getnest( $callback_args, array( 'args', 'notification' ), array() );
        $form_settings = toolset_getnest( $callback_args, array( 'args', 'form_settings' ), array() );
        $notifications = isset( $notifications_object->notifications )
            ? (array) $notifications_object->notifications
            : array();
        $enableTestMail = ! \CRED_Helper::$currentPage->isCustomPostNew;

        $template_repository = \CRED_Output_Template_Repository::get_instance();
        $renderer = \Toolset_Renderer::get_instance();
        $templates_data = array(
            'enableTestMail' => $enableTestMail,
            'form_type' => $form_type,
			'form' => $form,
			'form_settings' => $form_settings,
		);

		if ( PostMain::POST_TYPE === $form_type ) {
			$templates_data['stati'] = array(
				'basic' => $this->status_model->get_basic_stati(),
				'native' => $this->status_model->get_native_stati(),
				'custom' => $this->status_model->get_custom_stati(),
			);
			$templates_data['stati_label'] = array(
				'native' => $this->status_model->get_native_stati_group_label(),
				'custom' => $this->status_model->get_custom_stati_group_label(),
			);
		}

        $links_manager = new LinksManager();

        ?>
        <!-- templates here-->
        <script type="text/html-template" id="cred_notification_field_condition_template">
        <?php
        $conditions_data = $templates_data;
        $conditions_data['ii'] = '__i__';
        $conditions_data['jj'] = '__j__';
        $conditions_data['notification'] = array();
        $conditions_data['condition'] = array();
        $renderer->render(
            $template_repository->get( \CRED_Output_Template_Repository::NOTIFICATION_EDITOR_SECTION_SHARED_TRIGGER_META_CONDITION ),
            $conditions_data
        );
        ?>
        </script>
        <script type="text/html-template" id="cred_notification_template">
        <?php
        $templates_data['ii'] = '__i__';
        $templates_data['notification'] = array();
        $renderer->render(
            $template_repository->get( \CRED_Output_Template_Repository::NOTIFICATION_EDITOR_ITEM ),
            $templates_data
        );
        ?>
        </script>
        <!-- /end templates -->

        <div id='cred_notification_settings_panel_container'>

            <div class="clearfix cred-notification-settings-panel-header">
                <p class='cred-explain-text alignleft'>
                    <?php
                    _e( 'Add notifications to send emails after submitting this form.', 'wp-cred' );
                    echo CRED_STRING_SPACE;
                    $documentation_link = $links_manager->get_escaped_link(
                        CRED_DOC_LINK_NOTIFICATIONS,
                        array(
                            'utm_source' => 'plugin',
                            'utm_campaign' => 'forms',
                            'utm_medium' => 'gui',
                            'utm_term' => 'email-notifications'
                        )
                    );
                    echo sprintf(
                        '<a href="%1$s" title="%2$s" target="_blank">%3$s %4$s</a>.',
                        $documentation_link,
                        esc_attr( __( 'Check our documentation', 'wp-cred' ) ),
                        __( 'Check our documentation', 'wp-cred' ),
                        '<i class="fa fa-external-link"></i>'
                    );
                    ?>
                </p>

                <a id='cred-notification-add-button'
                    class='button button-secondary alignright cred-notification-add-button'
                    data-cred-bind="{
                        event: 'click',
                        action: 'addItem',
                        tmplRef: '#cred_notification_template',
                        modelRef: '_cred[notification][notifications][__i__]',
                        domRef: '#cred_notification_settings_panel_container',
                        replace: [
                        '__i__', {next: '_cred[notification][notifications]'}
                        ]
                    }">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: -0.25em;"></span>
                    <?php _e( 'Add new notification', 'wp-cred' ); ?>
                </a>

            </div>

            <?php
            foreach ( $notifications as $ii => $notification ) {
                $templates_data[ 'ii' ] = $ii;
                $templates_data[ 'notification' ] = $notification;

                $renderer->render(
                    $template_repository->get( \CRED_Output_Template_Repository::NOTIFICATION_EDITOR_ITEM ),
                    $templates_data
                );
            }
            ?>
        </div>
        <?php
   }
}
