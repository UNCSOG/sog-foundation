<?php

namespace OTGS\Toolset\CRED\Controller\Forms\User\Editor\Content;

use OTGS\Toolset\CRED\Controller\Forms\User\Main as UserFormMain;
use OTGS\Toolset\CRED\Controller\FormEditorToolbar\Base;
use OTGS\Toolset\CRED\Model\Forms\User\Helper;
use OTGS\Toolset\CRED\Model\Settings;

/**
 * User form content editor toolbar controller.
 *
 * @since 2.1
 */
class Toolbar extends Base {

	protected $editor_domain = 'user';
	protected $editor_target = 'content';

	/**
	 * Print the toolbar buttons.
	 *
	 * @since 2.1
	 */
	public function print_toolbar_buttons() {
		global $post_ID;

		$this->print_default_buttons();
		$this->print_generic_and_conditional_buttons();
		$this->print_third_party_buttons();
		$this->print_media_button( $post_ID );
	}

	/**
	 * Print the toolbar buttons for the notification subject input.
	 *
	 * @param string $editor_id
	 *
	 * @since 2.1
	 */
	public function print_notification_subject_toolbar_buttons( $editor_id ) {
		do_action(
			'wpv_action_wpv_generate_fields_and_views_button',
			$editor_id,
			array( 'output' => 'button' )
		);

		$placeholders_args = array(
            'editor_domain' => $this->editor_domain,
            'editor_target' => $editor_id,
			'slug' => 'notification-placeholders-subject',
			'label' => __( 'Placeholders', 'wp-cred' ),
            'icon' => '<i class="fa fa-database"></i>',
            'class' => 'js-cred-form-notification-placeholders',
            'data' => array( 'kind' => 'subject' )
		);
		$this->print_button( $placeholders_args );
	}

	/**
	 * Print the toolbar buttons for the notification body editor.
	 *
	 * @param string $editor_id
	 *
	 * @since 2.1
	 */
	public function print_notification_body_toolbar_buttons( $editor_id ) {
		do_action(
			'wpv_action_wpv_generate_fields_and_views_button',
			$editor_id,
			array( 'output' => 'button' )
		);

		$placeholders_args = array(
            'editor_domain' => $this->editor_domain,
            'editor_target' => $editor_id,
			'slug' => 'notification-placeholders-body',
			'label' => __( 'Placeholders', 'wp-cred' ),
            'icon' => '<i class="fa fa-database"></i>',
            'class' => 'js-cred-form-notification-placeholders',
            'data' => array( 'kind' => 'body' )
		);
		$this->print_button( $placeholders_args );
	}

	/**
	 * Print the toolbar buttons for the message after submitting the form.
	 *
	 * @param string $editor_id
	 *
	 * @since 2.1
	 */
	public function print_action_message_toolbar_buttons( $editor_id ) {
		do_action(
			'wpv_action_wpv_generate_fields_and_views_button',
			$editor_id,
			array( 'output' => 'button' )
		);

		global $post_ID;
		$this->print_media_button( $post_ID, $editor_id );
	}

	/**
	 * Complete shared data to be used in the toolbar script.
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	protected function get_script_localization() {
		$origin = admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' )  );
		$query_args['toolset_force_one_query_arg'] = 'toolset';
		$ajaxurl = esc_url( add_query_arg(
			$query_args,
			$origin
		) );

		$cred_ajax = \CRED_Ajax::get_instance();

		$i18n_shared = $this->get_shared_script_localization();

		$i18n = array(
			'messages' => array(
				'selection_missing' => __( 'You need to select a post type first', 'wp-cred' )
			),
			'data' => array(
				'ajaxurl' => $ajaxurl,
				'requestObjectFields' => array(
					'action' => $cred_ajax->get_action_js_name( \CRED_Ajax::CALLBACK_GET_ROLES_FIELDS ),
					'nonce' => wp_create_nonce( \CRED_Ajax::CALLBACK_GET_ROLES_FIELDS )
				),
				'shortcodes' => array(
					'form_container' => UserFormMain::SHORTCODE_NAME_FORM_CONTAINER
				),
				'fields' => array(
					'labels' => array(
						'basic' => __( 'Standard WordPress fields', 'wp-cred' ),
						'meta' => __( 'User fields', 'wp-cred' )
					),
					'fields' => array(
						'formElements' => array(
							'form_container' => array(
								'label' => __( 'Form container', 'wp-cred' ),
								'shortcode' => UserFormMain::SHORTCODE_NAME_FORM_CONTAINER,
								'requiredItem' => true,
								'attributes' => array(),
								'options' => array()
							),
							'form_messages' => array(
								'label' => __( 'Form messages', 'wp-cred' ),
								'shortcode' => UserFormMain::SHORTCODE_NAME_FORM_FIELD,
								'requiredItem' => false,
								'attributes' => array(
									'field' => 'form_messages',
									'class' => 'alert alert-warning',
								),
								'options' => array()
							),
						)
					)
				),
				'placeholders' => array(
					'%%USER_USERNAME%%' => array(
						'label' => __( 'Username', 'wp-cred' ),
						'placeholder' => '%%USER_USERNAME%%'
					),
					'%%USER_NICKNAME%%' => array(
						'label' => __( 'Nickname', 'wp-cred' ),
						'placeholder' => '%%USER_NICKNAME%%'
					),
					'%%USER_PASSWORD%%' => array(
						'label' => __( 'Password', 'wp-cred' ),
						'placeholder' => '%%USER_PASSWORD%%'
					),
					'%%RESET_PASSWORD_LINK%%' => array(
						'label' => __( 'Reset password link', 'wp-cred' ),
						'placeholder' => '%%RESET_PASSWORD_LINK%%',
						'target__not_in' => array( 'subject' )
					),
					'%%USER_EMAIL%%' => array(
						'label' => __( 'User email', 'wp-cred' ),
						'placeholder' => '%%USER_EMAIL%%'
					),
					'%%USER_USERID%%' => array(
						'label' => __( 'User ID', 'wp-cred' ),
						'placeholder' => '%%USER_USERID%%'
					),
					'%%USER_LOGIN_NAME%%' => array(
						'label' => __( 'User login name', 'wp-cred' ),
						'placeholder' => '%%USER_LOGIN_NAME%%'
					),
					'%%USER_DISPLAY_NAME%%' => array(
						'label' => __( 'User display name', 'wp-cred' ),
						'placeholder' => '%%USER_DISPLAY_NAME%%'
					),
					'%%USER_FIRST_NAME%%' => array(
						'label' => __( 'User first name', 'wp-cred' ),
						'placeholder' => '%%USER_FIRST_NAME%%'
					),
					'%%USER_LAST_NAME%%' => array(
						'label' => __( 'User last name', 'wp-cred' ),
						'placeholder' => '%%USER_LAST_NAME%%'
					),
					'%%USER_FULL_NAME%%' => array(
						'label' => __( 'User full name as First name Last name', 'wp-cred' ),
						'placeholder' => '%%USER_FULL_NAME%%'
					),
					'%%FORM_NAME%%' => array(
						'label' => __( 'Form name', 'wp-cred' ),
						'placeholder' => '%%FORM_NAME%%'
					),
					'%%FORM_DATA%%' => array(
						'label' => __( 'Form data', 'wp-cred' ),
						'placeholder' => '%%FORM_DATA%%',
						'target__not_in' => array( 'subject' ),
						'type__in' => array( 'form_submit', 'order_created' )
					),
					'%%DATE_TIME%%' => array(
						'label' => __( 'Date/Time', 'wp-cred' ),
						'placeholder' => '%%DATE_TIME%%'
					),
				),
				'i18n' => [
					'user_pass2' => __( 'Repeat Password', 'wp-cred' ),
				],
			)
		);

		$cred_settings = Settings::get_instance()->get_settings();
		if (
			isset( $cred_settings['recaptcha']['public_key'] )
			&& isset( $cred_settings['recaptcha']['private_key'] )
			&& ! empty( $cred_settings['recaptcha']['public_key'] )
			&& ! empty( $cred_settings['recaptcha']['private_key'] )
		) {
			$i18n['data']['fields']['fields']['formElements']['recaptcha'] = array(
				'label' => __( 'reCaptcha', 'wp-cred' ),
				'shortcode' => UserFormMain::SHORTCODE_NAME_FORM_FIELD,
				'requiredItem' => false,
				'attributes' => array(
					'field' => 'recaptcha',
					'class' => 'form-control',
					'output' => 'bootstrap'
				),
				'options' => array()
			);
		}

		/** This filter is documented in application\controllers\forms\post\editor\content\toolbar.php */
		$i18n['data']['placeholders'] = apply_filters( 'cred_admin_notification_placeholders', $i18n['data']['placeholders'], UserFormMain::POST_TYPE );

		if ( $initial_cache = $this->maybe_get_initial_cache() ) {
			$i18n['initialCache'] = $initial_cache;
		}

		return array_merge( $i18n_shared, $i18n );
	}

	/**
	 * Maybe populate the initial cache for fields
	 * for the user roles that the current form might manipulate.
	 *
	 * @since 2.3.1
	 * @return array|bool
	 */
	private function maybe_get_initial_cache() {
		global $pagenow;
		$form_id = (int) toolset_getget( 'post', 0 );

		if (
			'post.php' === $pagenow
			&& $form_id > 0
			&& UserFormMain::POST_TYPE === get_post_type( $form_id )
		) {
			$form = new \CRED_Form_Data( $form_id, UserFormMain::POST_TYPE, false );
			$form_fields = $form->getFields();
			$user_roles = toolset_getarr( $form_fields[ 'form_settings' ]->form, 'user_role', false );
			if (
				! $user_roles
				|| empty( $user_roles )
			) {
				return false;
			}
			$selected_user_roles = json_decode( $user_roles, true );
			if ( ! is_array( $selected_user_roles ) ) {
				return false;
			}

			$selected_user_roles = array_filter( $selected_user_roles );

			$toolbar_helper = new Helper( $selected_user_roles, new \Toolset_Condition_Plugin_Types_Active() );

			$cache_key = implode( '|:|', $selected_user_roles );

			return array( $cache_key => $toolbar_helper->populate_items() );
		}

		return false;
	}

}
