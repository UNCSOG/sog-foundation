<?php

define( "PAD", "\t" );
define( "NL", "\r\n" );

if ( ! class_exists( "CredFormCreator", false ) ) {

	/**
	 * Description of CredFormCreator
	 *
	 * usage: CredFormCreator::cred_create_form('mycredform_name_post', 'new', 'post');
	 *        CredFormCreator::cred_create_form('mycredform_name_page', 'edit', 'page');
	 * to include:
	 * if (defined( 'CRED_CLASSES_PATH' )) {
	 * require_once CRED_CLASSES_PATH."/CredFormCreator.php";
	 * CredFormCreator::cred_create_form('test', 'new', 'page');
	 * }
	 *
	 * @author Franko
	 */
	class CredFormCreator {

		/**
		 *
		 * stdClass Object
		 * (
		 * [form] => Array
		 * (
		 * [hide_comments] => 0
		 * [has_media_button] => 0
		 * [has_toolset_buttons] => 0
		 * [has_media_manager] => 0
		 * [action_message] =>
		 * [type] => new
		 * [action] => form
		 * [redirect_delay] => 0
		 * )
		 *
		 * [post] => Array
		 * (
		 * [post_type] => post
		 * [post_status] => publish
		 * )
		 *
		 * )
		 *
		 *
		 * stdClass Object
		 * (
		 * [notifications] => Array
		 * (
		 * )
		 *
		 * [enable] => 1
		 * )
		 *
		 * @param type $name
		 * @param type $mode [new|edit]
		 * @param type $post_type
		 */
		public static $_created = array();

		/**
		 * @param $name
		 * @param $mode
		 * @param $post_type
		 *
		 * @return mixed
		 */
		public static function cred_create_form( $name, $mode, $post_type ) {
			$name = sanitize_text_field( $name );
			if ( empty( self::$_created ) && ! in_array( $name, self::$_created ) ) {
				self::$_created[] = $name;

				$forms = get_posts(
					array(
						'post_type'              => CRED_FORMS_CUSTOM_POST_NAME,
						'title'                  => wp_specialchars_decode( $name ),
						'post_status'            => 'all',
						'numberposts'            => 1,
						'update_post_term_cache' => false,
						'update_post_meta_cache' => false,
						'orderby'                => 'post_date ID',
						'order'                  => 'ASC',
					)
				);

				if ( ! empty( $forms ) ) {
					//TODO: give message? Toolset Form already exists
					return;
				}

				$model = CRED_Loader::get( 'MODEL/Forms' );

				$form = new stdClass;
				$form->ID = '';
				$form->post_title = $name;
				$form->post_content = '';
				$form->post_status = 'private';
				$form->comment_status = 'closed';
				$form->ping_status = 'closed';
				$form->post_type = CRED_FORMS_CUSTOM_POST_NAME;
				$form->post_name = CRED_FORMS_CUSTOM_POST_NAME;

				$fields = array();
				$fields['form_settings'] = new stdClass;
				$fields['form_settings']->form_type = $mode;
				$fields['form_settings']->form_action = 'form';
				$fields['form_settings']->form_action_page = '';
				$fields['form_settings']->redirect_delay = 0;
				$fields['form_settings']->message = '';
				$fields['form_settings']->hide_comments = 1;
				$fields['form_settings']->include_captcha_scaffold = 0;
				$fields['form_settings']->include_wpml_scaffold = 0;
				$fields['form_settings']->has_media_button = 0;
				$fields['form_settings']->has_toolset_buttons = 0;
				$fields['form_settings']->has_media_manager = 0;
				$fields['form_settings']->cred_theme_css = 'minimal';

				$fields['form_settings']->post_type = $post_type;
				$fields['form_settings']->post_status = 'publish';

				$fields['wizard'] = - 1;

				$fields['extra'] = new stdClass;
				$fields['extra']->css = '';
				$fields['extra']->js = '';
				$fields['extra']->scaffold = '';

				$fields['extra']->messages = $model->getDefaultMessages();

				return $model->saveForm( $form, $fields );
			}
		}

	}

}
