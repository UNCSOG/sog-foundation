<?php

include "CRED_Admin_Helper.php";

final class CRED_Admin {

	public static function initAdmin() {
		// add plugin menus
		// setup js, css assets
		CRED_Admin_Helper::setupAdmin();
		//hereaho
		CRED_CRED::media();

		// save custom fields of cred forms
		add_action('save_post', array(__CLASS__, 'saveFormCustomFields'), 10, 2);

		// IMPORTANT: drafts should now be left with post_status=draft, maybe show up because of previous versions
		add_filter('wp_insert_post_data', array(__CLASS__, 'forcePrivateforForms'));

		//Hooks when fusion builder enqueue their scripts
		add_action('fusion_builder_admin_scripts_hook', array(__CLASS__, 'fusionBuildrCompatibilityHook'));

	}

	/**
	 * Checks if the current request corresponds to CRED pages
	 * and loads the info into CRED_Admin static variables.
	 */
	public static function load_current_page() {
		// determine current admin page
		CRED_Helper::getAdminPage( array(
			'post_type' => CRED_FORMS_CUSTOM_POST_NAME,
			'base' => 'admin.php',
			'pages' => array(
				'view-archives-editor',
				'views-editor',
				'CRED_Forms',
				'CRED_Fields',
				'CRED_Settings',
				'toolset-settings',
				'CRED_Help',
			),
		) );

		CRED_Helper::getAdminPage( array(
			'post_type' => CRED_USER_FORMS_CUSTOM_POST_NAME,
			'base' => 'admin.php',
			'pages' => array(
				'view-archives-editor',
				'views-editor',
				'CRED_User_Forms',
				'CRED_User_Fields',
				'CRED_Settings',
				'toolset-settings',
				'CRED_Help',
			),
		) );

		CRED_Helper::getAdminPage( array(
			'post_type' => CRED_RELATIONSHIP_FORMS_CUSTOM_POST_NAME,
			'base' => 'admin.php',
			'pages' => array(
				'cred_relationship_form',
				'cred_relationship_forms',
			),
		) );
	}


	public static function forcePrivateforForms($post) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return $post;

		if (CRED_FORMS_CUSTOM_POST_NAME != $post['post_type'] &&
				CRED_USER_FORMS_CUSTOM_POST_NAME != $post['post_type'])
			return $post;

		if (isset($post['ID']) && !current_user_can('edit_post', $post['ID']))
			return $post;

		if (isset($post['ID']) && wp_is_post_revision($post['ID']))
			return $post;

		if ('auto-draft' == $post['post_status'])
			return $post;

		//Force unique slug
//        if (isset($post['post_title'])) {
//            $post['post_name'] = sanitize_title($post['post_title']);
//        }

		$post['post_status'] = 'private';
		return $post;
	}

	// when form is submitted from admin, save the custom fields which describe the form configuration to DB
	public static function saveFormCustomFields($post_id, $post) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		if (wp_is_post_revision($post_id))
			return;

		if (CRED_FORMS_CUSTOM_POST_NAME != $post->post_type &&
				CRED_USER_FORMS_CUSTOM_POST_NAME != $post->post_type)
			return;

		if (!current_user_can('edit_post', $post_id))
			return;

		// hook not called from admin edit page, return
		if (empty($_POST) || !isset($_POST['cred-admin-post-page-field']) || !wp_verify_nonce($_POST['cred-admin-post-page-field'], 'cred-admin-post-page-action'))
			return;

		if (isset($_POST['_cred']) && is_array($_POST['_cred']) && !empty($_POST['_cred'])) {
			// new format
			if (CRED_FORMS_CUSTOM_POST_NAME == $post->post_type) {
				$model = CRED_Loader::get('MODEL/Forms');
				$add_merge = array(
					'hide_comments' => 0,
					'has_media_button' => 0,
					'has_toolset_buttons' => 0,
					'has_media_manager' => 0,
					'action_message' => '',
				);
			}

			if (CRED_USER_FORMS_CUSTOM_POST_NAME == $post->post_type) {
				$model = CRED_Loader::get('MODEL/UserForms');

				if (isset($_POST['_cred']['form']['user_role'])) {
					if ( isset( $_POST['_cred']['form']['user_role'] ) ) {
					$_POST['_cred']['form']['user_role'] = array_filter( $_POST['_cred']['form']['user_role'], 'strlen' );
					}
					$tmp_array = array();
					if ( isset( $_POST['_cred']['form']['user_role'] ) &&
					count( $_POST['_cred']['form']['user_role'] ) > 0 ) {
					foreach ( $_POST['_cred']['form']['user_role'] as $ele ) {
						$tmp_array[] = $ele;
					}
					$tmp_array = array_unique( $tmp_array );
					}
					$_POST['_cred']['form']['user_role'] = json_encode($tmp_array);
				}

				$add_merge = array(
					'hide_comments' => 0,
					'has_media_button' => 0,
					'has_toolset_buttons' => 0,
					'has_media_manager' => 0,
					'action_message' => '',
					'autogenerate_username_scaffold' => isset($_POST['_cred']['form']['autogenerate_username_scaffold']) ? 1 : 0,
					'autogenerate_nickname_scaffold' => isset($_POST['_cred']['form']['autogenerate_nickname_scaffold']) ? 1 : 0,
					'autogenerate_password_scaffold' => isset($_POST['_cred']['form']['autogenerate_password_scaffold']) ? 1 : 0,
				);
			}

			// settings (form, post, actions, messages, css etc..)
			$settings = new stdClass;
			$settings->form = isset($_POST['_cred']['form']) ? $_POST['_cred']['form'] : array();
			$settings->post = isset($_POST['_cred']['post']) ? $_POST['_cred']['post'] : array();
			$settings->form = CRED_Helper::mergeArrays($add_merge, $settings->form);

			// notifications
			$notification = new stdClass;
			$notification->notifications = array();
			// normalize order of notifications using array_values
			$notification->notifications = isset($_POST['_cred']['notification']['notifications']) ? array_values($_POST['_cred']['notification']['notifications']) : array();
			//we have notifications allways enabled
			//$notification->enable=isset($_POST['_cred']['notification']['enable'])?1:0;
			$notification->enable = 1;
			foreach ($notification->notifications as $ii => $nott) {
				if (isset($nott['event']['condition']) && is_array($nott['event']['condition'])) {
					// normalize order
					$notification->notifications[$ii]['event']['condition'] = array_values($notification->notifications[$ii]['event']['condition']);
					$notification->notifications[$ii]['event']['condition'] = CRED_Helper::applyDefaults($notification->notifications[$ii]['event']['condition'], array(
								'field' => '',
								'op' => '',
								'value' => '',
								'only_if_changed' => 0
					));
				} else {
					$notification->notifications[$ii]['event']['condition'] = array();
				}
			}

			//add_filter('wp_kses_allowed_html', array(__CLASS__, '_cred_set_allowed_html_tag'), 10, 2);
			$settings_model = CRED_Loader::get('MODEL/Settings');
			$curr_settings = $settings_model->getSettings();
			$__allowed_tags = (isset($curr_settings) && isset($curr_settings['allowed_tags'])) ? $curr_settings['allowed_tags'] : array();

			// extra
			$allowed_tags = wp_kses_allowed_html('post');
			foreach ($allowed_tags as $key => $value) {
				if (isset($__allowed_tags) && !empty($__allowed_tags) && !array_key_exists($key, $__allowed_tags)) {
					unset($allowed_tags[$key]);
				}
			}
			unset($__allowed_tags);

			$allowed_protocols = array('http', 'https', 'mailto');

			$extra_js = toolset_getnest( $_POST, array( '_cred', 'extra', 'js' ) );
			$extra_css = toolset_getnest( $_POST, array( '_cred', 'extra', 'css' ) );
			$extra_scaffold = toolset_getnest( $_POST, array( '_cred', 'extra', 'scaffold' ) );
			$extra_editor_origin = toolset_getnest( $_POST, array( '_cred', 'extra', 'editor_origin' ) );

			$default_messages = $model->getDefaultMessages();
			$extra = new stdClass;
			$extra->css = $extra_css;
			$extra->js = $extra_js;
			$extra->messages = (isset($_POST['_cred']['extra']['messages'])) ? $_POST['_cred']['extra']['messages'] : $default_messages;
			$extra->scaffold = $extra_scaffold;
			$extra->editor_origin = $extra_editor_origin;

			// update
			$model->updateFormCustomFields($post_id, array(
				'form_settings' => $settings,
				'notification' => $notification,
				'extra' => $extra
			));

			// wizard
			if (isset($_POST['_cred']['wizard']))
				$model->updateFormCustomField($post_id, 'wizard', intval($_POST['_cred']['wizard']));

			// validation
			if (isset($_POST['_cred']['validation']))
				$model->updateFormCustomField($post_id, 'validation', $_POST['_cred']['validation']);
			else
				$model->updateFormCustomField($post_id, 'validation', array('success' => 1));

			// allow 3rd-party to do its own stuff on CRED form save
			do_action('cred_admin_save_form', $post_id, $post);

		}
	}

	public static function _cred_set_allowed_html_tag($allowed, $context) {
		$settings_model = CRED_Loader::get('MODEL/Settings');
		$settings = $settings_model->getSettings();
		$__allowed_tags = $settings['allowed_tags'];

		if (is_array($context)) {
			return $allowed;
		}

		if ($context === 'post') {
			foreach ($allowed as $key => $value) {
				if (!array_key_exists($key, $__allowed_tags)) {
					unset($allowed[$key]);
				}
			}
		}

		return apply_filters($allowed);
	}

	public static function fusionBuildrCompatibilityHook() {
		//Fusion Builder Code Mirror Compatibility Fix
		wp_dequeue_script('fusion-builder-codemirror-js');
		wp_dequeue_style("fusion-builder-codemirror-css");
	}

}
