<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Post\Editor\Content;

use OTGS\Toolset\CRED\Controller\Forms\Post\Main as PostFormMain;
use OTGS\Toolset\CRED\Controller\FormEditorToolbar\Base;
use OTGS\Toolset\CRED\Model\Forms\Post\Helper;
use OTGS\Toolset\CRED\Model\Settings;

/**
 * Post form content editor toolbar controller.
 *
 * @since 2.1
 */
class Toolbar extends Base {

	protected $editor_domain = 'post';
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
					'action' => $cred_ajax->get_action_js_name( \CRED_Ajax::CALLBACK_GET_POST_TYPE_FIELDS ),
					'nonce' => wp_create_nonce( \CRED_Ajax::CALLBACK_GET_POST_TYPE_FIELDS )
				),
				'shortcodes' => array(
					'form_container' => PostFormMain::SHORTCODE_NAME_FORM_CONTAINER
				),
				'fields' => array(
					'labels' => array(
						'basic' => __( 'Standard WordPress fields', 'wp-cred' ),
						'taxonomy' => __( 'Taxonomies', 'wp-cred' ),
						'meta' => __( 'Custom fields', 'wp-cred' ),
						'roles' => __( 'Related posts', 'wp-cred' ),
						'legacyParent' => __( 'Parent post', 'wp-cred' ),
						'hierarchicalParent' => __( 'Hierarchical parent post', 'wp-cred' ),
						'relationship' => __( 'Relationships', 'wp-cred' )
					),
					'fields' => array(
						'formElements' => array(
							'form_container' => array(
								'label' => __( 'Form container', 'wp-cred' ),
								'shortcode' => PostFormMain::SHORTCODE_NAME_FORM_CONTAINER,
								'requiredItem' => true,
								'attributes' => array(),
								'options' => array()
							),
							'form_messages' => array(
								'label' => __( 'Form messages', 'wp-cred' ),
								'shortcode' => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
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
					'%%POST_ID%%' => array(
						'label' => __( 'Post ID', 'wp-cred' ),
						'placeholder' => '%%POST_ID%%'
					),
					'%%POST_TITLE%%' => array(
						'label' => __( 'Post title', 'wp-cred' ),
						'placeholder' => '%%POST_TITLE%%'
					),
					'%%POST_LINK%%' => array(
						'label' => __( 'Post link', 'wp-cred' ),
						'placeholder' => '%%POST_LINK%%',
						'target__not_in' => array( 'subject' )
					),
					// Legacy placeholders for Types 2 relationships.
					// Those only work for migrated relationships, and not even safely.
					// See https://onthegosystems.myjetbrains.com/youtrack/issue/cred-2170.
					//'%%POST_PARENT_TITLE%%' => array(
					//	'label' => __( 'Post parent title', 'wp-cred' ),
					//	'placeholder' => '%%POST_PARENT_TITLE%%',
					//	'type__in' => array( 'form_submit' )
					//),
					//'%%POST_PARENT_LINK%%' => array(
					//	'label' => __( 'Post parent link', 'wp-cred' ),
					//	'placeholder' => '%%POST_PARENT_LINK%%',
					//	'target__not_in' => array( 'subject' ),
					//	'type__in' => array( 'form_submit' )
					//),
					'%%POST_ADMIN_LINK%%' => array(
						'label' => __( 'Post admin link', 'wp-cred' ),
						'placeholder' => '%%POST_ADMIN_LINK%%',
						'target__not_in' => array( 'subject' )
					),
					'%%USER_LOGIN_NAME%%' => array(
						'label' => __( '(Logged in user) User login name', 'wp-cred' ),
						'placeholder' => '%%USER_LOGIN_NAME%%'
					),
					'%%USER_DISPLAY_NAME%%' => array(
						'label' => __( '(Logged in user) User display name', 'wp-cred' ),
						'placeholder' => '%%USER_DISPLAY_NAME%%'
					),
					'%%USER_FIRST_NAME%%' => array(
						'label' => __( '(Logged in user) User first name', 'wp-cred' ),
						'placeholder' => '%%USER_FIRST_NAME%%'
					),
					'%%USER_LAST_NAME%%' => array(
						'label' => __( '(Logged in user) User last name', 'wp-cred' ),
						'placeholder' => '%%USER_LAST_NAME%%'
					),
					'%%USER_FULL_NAME%%' => array(
						'label' => __( '(Logged in user) User full name as First name Last name', 'wp-cred' ),
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
					'%%EXPIRATION_DATE%%' => array(
						'label' => __( 'Expiration date', 'wp-cred' ),
						'placeholder' => '%%EXPIRATION_DATE%%'
					)
				)
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
				'shortcode' => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
				'requiredItem' => false,
				'attributes' => array(
					'field' => 'recaptcha',
					'class' => 'form-control',
					'output' => 'bootstrap'
				),
				'options' => array()
			);
		}

		/**
		 * Filter the notification placeholders for forms.
		 *
		 * @param array[] List of existing placeholders, with label and placeholdr entries.
		 * @param string Post type of the current form object.
		 */
		$i18n['data']['placeholders'] = apply_filters( 'cred_admin_notification_placeholders', $i18n['data']['placeholders'], PostFormMain::POST_TYPE );

		if ( $initial_cache = $this->maybe_get_initial_cache() ) {
			$i18n['initialCache'] = $initial_cache;
		}

		return array_merge( $i18n_shared, $i18n );
	}

	/**
	 * Maybe populate the initial cache for fields
	 * for the post type that the current form might manipulate
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
			&& PostFormMain::POST_TYPE === get_post_type( $form_id )
		) {
			$form = new \CRED_Form_Data( $form_id, PostFormMain::POST_TYPE, false );
			$form_fields = $form->getFields();
			$post_type = toolset_getarr( $form_fields[ 'form_settings' ]->post, 'post_type', false );
			if (
				! $post_type
				|| empty( $post_type )
			) {
				return false;
			}
			$post_type_object = get_post_type_object( $post_type );
			if ( ! $post_type_object ) {
				return false;
			}

			$toolbar_helper = new Helper( $post_type_object, new \Toolset_Condition_Plugin_Types_Active() );

			return array( $post_type => $toolbar_helper->populate_items() );
		}

		return false;
	}

}
