<?php

/**
 * Class CRED_Notification_Manager.
 *
 * @since 1.9.1
 * @deprecated Since 1.9.6.
 * @todo Move the in-use test notification sender to a proper AJAX call.
 */
class CRED_Notification_Manager {

	private static $instance;

	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new CRED_Notification_Manager();
		}
		return self::$instance;
	}

	/**
	 * @return object
	 */
	private function getCurrentUserData() {
		$current_user = wp_get_current_user();

		$user_data = new stdClass;
		$user_data->ID = isset($current_user->ID) ? $current_user->ID : 0;
		// Does not seem to be used anywhere!!!
		$user_data->roles = isset($current_user->roles) ? $current_user->roles : array();
		$user_data->role = isset($current_user->roles[0]) ? $current_user->roles[0] : '';
		// END Does not seem to be used anywhere!!!
		$user_data->login = isset($current_user->data->user_login) ? $current_user->data->user_login : '';
		$user_data->display_name = isset($current_user->data->display_name) ? $current_user->data->display_name : '';

		$user_data->first_name = get_user_meta( $user_data->ID, 'first_name', true );
		$user_data->last_name = get_user_meta( $user_data->ID, 'last_name', true );

		return $user_data;
	}

	/**
	 * Translate codes in notification fields of cred form (like %%POST_ID%% to post id etc..)
	 *
	 * @param array $field
	 * @param array $data
	 *
	 * @return mixed
	 */
	private function replacePlaceholders($field, $data) {
		return str_replace(array_keys($data), array_values($data), $field);
	}

	/**
	 * Retrieve string translation name of the notification based on string ID (icl string id)
	 *
	 * @param int $id
	 *
	 * @return bool|null|string
	 */
	private function getNotification_translation_name($id) {
		if (function_exists('icl_t')) {
			global $wpdb;
			$dBtable = $wpdb->prefix . "icl_strings";
			$string_translation_name_notifications = $wpdb->get_var($wpdb->prepare("SELECT name FROM $dBtable WHERE id=%d", $id));

			if ($string_translation_name_notifications) {
				return $string_translation_name_notifications;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @param int $form_id
	 * @param array $notification
	 *
	 * @return array
	 */
	public function sendTestNotification($form_id, $notification) {
		// bypass if nothing
		if ( ! $notification || empty( $notification ) ) {
			return array( 'error' => __( 'No Notification given', 'wp-cred' ) );
		}

		// dummy
		$post_id = null;

		// get Mailer
		$mailer = CRED_Loader::get('CLASS/Mail_Handler');

		// get current user
		$user = $this->getCurrentUserData();

		// get some data for placeholders
		$form_post = get_post($form_id);
		$form_title = ($form_post) ? $form_post->post_title : '';
		$date_format = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i:s' );
		$date = date( $date_format, current_time( 'timestamp' ) );


		/**
		 * Extend the notification subject placeholders.
		 *
		 * @param array Key-value pairs of placeholder-value
		 * @param int $form_id
		 * @param int|null $post_id Will be null on notification tests
		 *
		 * @since unknown
		 */
		$data_subject = apply_filters('cred_subject_notification_codes', array(
			'%%USER_LOGIN_NAME%%' => $user->login,
			'%%USER_DISPLAY_NAME%%' => $user->display_name,
			'%%USER_FIRST_NAME%%' => $user->first_name,
			'%%USER_LAST_NAME%%' => $user->last_name,
			'%%USER_FULL_NAME%%' => $user->first_name . ' ' . $user->last_name,
			'%%POST_ID%%' => 'DUMMY_POST_ID',
			'%%POST_TITLE%%' => 'DUMMY_POST_TITLE',
			'%%FORM_NAME%%' => $form_title,
			'%%DATE_TIME%%' => $date
		), $form_id, $post_id);

		/**
		 * Extend the notification body placeholders.
		 *
		 * @param array Key-value pairs of placeholder-value
		 * @param int $form_id
		 * @param int|null $post_id Will be null on notification tests
		 *
		 * @since unknown
		 */
		$data_body = apply_filters('cred_body_notification_codes', array(
			'%%USER_LOGIN_NAME%%' => $user->login,
			'%%USER_DISPLAY_NAME%%' => $user->display_name,
			'%%USER_FIRST_NAME%%' => $user->first_name,
			'%%USER_LAST_NAME%%' => $user->last_name,
			'%%USER_FULL_NAME%%' => $user->first_name . ' ' . $user->last_name,
			'%%POST_ID%%' => 'DUMMY_POST_ID',
			'%%POST_TITLE%%' => 'DUMMY_POST_TITLE',
			'%%POST_LINK%%' => 'DUMMY_POST_LINK',
			'%%POST_ADMIN_LINK%%' => 'DUMMY_ADMIN_POST_LINK',
			'%%FORM_NAME%%' => $form_title,
			'%%DATE_TIME%%' => $date,
		), $form_id, $post_id);

		// reset mail handler
		$mailer->reset();
		$mailer->setHTML(true, false);
		$recipients = array();

		// parse Notification Fields
		if ( ! isset( $notification['to']['type'] ) ) {
			$notification['to']['type'] = array();
		}
		if ( ! is_array( $notification['to']['type'] ) ) {
			$notification['to']['type'] = (array) $notification['to']['type'];
		}

		// notification to specific recipients
		if ( in_array( 'specific_mail', $notification['to']['type'] )
			&& isset( $notification['to']['specific_mail']['address'] )
		) {
			$tmp = explode(',', $notification['to']['specific_mail']['address']);
			foreach ($tmp as $aa)
				$recipients[] = array(
					'address' => $aa,
					'to' => false,
					'name' => false,
					'lastname' => false
				);
			unset($tmp);
		}

		// add custom recipients by 3rd-party
		if ( ! $recipients
			|| empty( $recipients )
		) {
			return array( 'error' => __( 'No recipients specified', 'wp-cred' ) );
		}

		// build recipients
		foreach ($recipients as $ii => $recipient) {
			// nowhere to send, bypass
			if (!isset($recipient['address']) || !$recipient['address']) {
				unset($recipients[$ii]);
				continue;
			}

			if (false === $recipient['to']) {
				// this is already formatted
				$recipients[$ii] = $recipient['address'];
				continue;
			}

			$tmp = '';
			$tmp.=$recipient['to'] . ': ';
			$tmp2 = array();
			if ( $recipient['name'] ) {
				$tmp2[] = $recipient['name'];
			}
			if ( $recipient['lastname'] ) {
				$tmp2[] = $recipient['lastname'];
			}
			if ( ! empty( $tmp2 ) ) {
				$tmp .= implode( ' ', $tmp2 ) . ' <' . $recipient['address'] . '>';
			} else {
				$tmp .= $recipient['address'];
			}

			$recipients[$ii] = $tmp;
		}
		$mailer->addRecipients($recipients);

		// build SUBJECT
		$_subj = '';
		if ( isset( $notification['mail']['subject'] ) ) {
			$_subj = $notification['mail']['subject'];
		}

		// provide WPML localisation
		if (isset($notification['_cred_icl_string_id']['subject'])) {
			$notification_subject_string_translation_name = $this->getNotification_translation_name($notification['_cred_icl_string_id']['subject']);
			if ($notification_subject_string_translation_name) {
				$_subj = cred_translate($notification_subject_string_translation_name, $_subj, 'cred-form-' . $form_title . '-' . $form_id);
			}
		}

		// replace placeholders
		$_subj = $this->replacePlaceholders($_subj, $data_subject);

		// parse shortcodes if necessary relative to $post_id
		$_subj = do_shortcode( stripslashes( $_subj ) );

		$mailer->setSubject($_subj);

		// build BODY
		$_bod = '';
		if ( isset( $notification['mail']['body'] ) ) {
			$_bod = $notification['mail']['body'];
		}

		// provide WPML localisation
		if (isset($notification['_cred_icl_string_id']['body'])) {
			$notification_body_string_translation_name = $this->getNotification_translation_name($notification['_cred_icl_string_id']['body']);
			if ($notification_body_string_translation_name) {
				$_bod = cred_translate($notification_body_string_translation_name, $_bod, 'cred-form-' . $form_title . '-' . $form_id);
			}
		}

		// replace placeholders
		$_bod = $this->replacePlaceholders($_bod, $data_body);

		// pseudo the_content filter
		$_bod = apply_filters( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, $_bod );
		$_bod = stripslashes($_bod);

		$mailer->setBody($_bod);

		// build FROM address / name, independantly
		$_from = array();
		if ( isset( $notification['from']['address'] ) && ! empty( $notification['from']['address'] ) ) {
			$_from['address'] = $notification['from']['address'];
		}
		if ( isset( $notification['from']['name'] ) && ! empty( $notification['from']['name'] ) ) {
			$_from['name'] = $notification['from']['name'];
		}
		if ( ! empty( $_from ) ) {
			$mailer->setFrom( $_from );
		}

		// send it
		$_send_result = $mailer->send();

		// custom action hooks here, for 3rd-party integration
		//do_action('cred_after_send_notifications_'.$form_id, $post_id);
		//do_action('cred_after_send_notifications', $post_id);

		if ( ! $_send_result ) {
			if (empty($_bod)) {
				return array('error' => __('Body content is required', 'wp-cred'));
			} else {
				return array('error' => __('Mail failed to be sent', 'wp-cred'));
			}
		}
		return array('success' => __('Mail sent succesfully', 'wp-cred'));
	}

}
