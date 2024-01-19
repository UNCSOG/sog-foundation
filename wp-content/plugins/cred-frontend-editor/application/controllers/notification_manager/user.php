<?php

/**
 * Class CRED_Notification_Manager_User.
 *
 * @since 1.9.6
 */
class CRED_Notification_Manager_User extends CRED_Notification_Manager_Base {

	private static $instance;
	protected $model;

	const FORM_PREFIX = 'cred-user-form-';

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * CRED_Notification_Manager_User constructor.
	 *
	 * @param CRED_User_Forms_Model|null $model
	 */
	public function __construct( CRED_User_Forms_Model $model = null ) {
		parent::__construct();
		$this->model = ( null === $model ) ? CRED_Loader::get( 'MODEL/UserForms' ) : $model;
	}

	/**
	 * @param int $user_id
	 * @param WP_User $user
	 */
	public function check_for_notifications( $user_id, $user ) {
		$model = $this->get_current_model();

		$attachedData = $model->getAttachedData( $user_id );
		if ( ! $attachedData ) {
			return;
		}

		$notification = false;
		foreach ( $attachedData as $form_id => $data ) {
			$notification = $model->getFormCustomField( $form_id, 'notification' );
			$snapshot_unfolded = $this->unfold( $attachedData[ $form_id ]['current']['snapshot'] );
			foreach ( $snapshot_unfolded as $field_slug => $field_hash ) {
				$snapshot_unfolded[ $field_slug ] = get_user_meta( $user_id, $field_slug, true );
			}
			$attachedData[ $form_id ]['current']['snapshot'] = $this->fold( $this->do_hash( $snapshot_unfolded ) );

			break;
		}

		if ( $notification ) {
			$this->trigger_notifications( $user_id, array(
				'notification' => $notification,
				'form_id' => $form_id,
				'post' => $user,
			), $attachedData );
		}
		// keep up-to-date with notification settings for form and post field values
		$this->update( $user_id, $form_id );
	}


	/**
	 * @return CRED_User_Forms_Model
	 */
	protected function get_current_model() {
		return $this->model;
	}

	/**
	 * Returns a post or user object by generic $object_id and $is_user_form inputs
	 *
	 * @param $user_id
	 *
	 * @return WP_Post|false
	 */
	protected function get_form_object( $user_id ) {
		$object = false;
		$user_data = get_userdata( (int) $user_id );
		if ( isset( $user_data )
			&& isset( $user_data->data ) ) {
			$object = $user_data->data;
		}

		return $object;
	}

	/**
	 * Prepare attached hashed snapshot data field referred to current time form fields
	 * in order to check if something has changed in them
	 *
	 * @param $form_id
	 * @param $user_id
	 * @param array $notifications
	 *
	 * @return array|null
	 */
	protected function get_attached_data( $form_id, $user_id, $notifications = array() ) {
		$model = $this->get_current_model();

		$object = $this->get_form_object( $user_id );
		if ( ! $object ) {
			return null;
		}

		if ( empty( $notifications ) ) {
			$notifications = $this->get_notification_data_by_model( $form_id, $model );
		}

		$attachedData = array();
		$snapshotFields = array();
		if ( ! empty( $notifications ) ) {
			foreach ( $notifications as $index => $notification ) {
				if ( isset( $notification[ 'event' ][ 'condition' ] ) ) {
					foreach ( $notification[ 'event' ][ 'condition' ] as $jj => $condition ) {
						if ( isset( $condition[ 'only_if_changed' ] ) &&
							$condition[ 'only_if_changed' ] &&
							! in_array( $condition[ 'field' ], $snapshotFields )
						) {
							// load all fields that have a changing condition from all notifications at once
							$snapshotFields[] = $condition[ 'field' ];
						}
					}
				}
			}

			$fields = $model->get_object_fields( $user_id, $snapshotFields );
			$snapshotFieldsValuesHash = $this->fold( $this->do_hash( $fields ) );
			$attachedData[ $form_id ] = array(
				'cred_form' => $form_id,
				'current' => array(
					'time' => time(),
					'post_status' => '',
					'snapshot' => $snapshotFieldsValuesHash,
				),
			);
		}

		return $attachedData;
	}

	/**
	 * @param $user_id
	 * @param $attached_data
	 *
	 * @return bool
	 */
	protected function save_attached_data( $user_id, $attached_data ) {
		if ( empty( $attached_data ) ) {
			return false;
		}

		//Removing hooks before setAttachedData in order to avoid infinite loops
		//because of update_meta called in setAttachedData
		$model = $this->get_current_model();
		CRED_Notification_Manager_Utils::get_instance()->remove_hooks();
		$is_attached_data_saved = $model->setAttachedData( (int) $user_id, $attached_data );
		CRED_Notification_Manager_Utils::get_instance()->add_hooks();

		return $is_attached_data_saved;
	}

	/**
	 * @param $user_id
	 * @param array $attached_data
	 *
	 * @return bool
	 */
	protected function delete_attached_data( $user_id, $attached_data = array() ) {
		if ( ! empty( $attached_data ) ) {
			return false;
		}

		$model = $this->get_current_model();

		return $model->removeAttachedData( (int) $user_id );
	}

	/**
	 * @param int $user_id
	 * @param array $data
	 * @param null $attached_data
	 */
	public function trigger_notifications( $user_id, $data, $attached_data = null ) {
		$form_id = $data[ 'form_id' ];
		$model = $this->get_current_model();

		if ( empty( $user_id ) ) {
			return;
		}

		$user_data = get_userdata( $user_id );
		if ( isset( $user_data ) && ! empty( $user_data ) ) {
			$object = $user_data->data;
		}

		if ( ! isset( $object )
			&& isset( $data[ 'customer' ] )
		) {
			$object = $data[ 'customer' ];
			$object->ID = $user_id;
		}

		if ( ! isset( $object ) ) {
			return;
		}

		if ( empty( $attached_data ) ) {
			$attached_data = $model->getAttachedData( $user_id );
		}

		// trigger for this event, if set
		if ( isset( $data[ 'event' ] ) ) {
			$this->event = $data[ 'event' ];
		} else {
			$this->event = false;
		}

		$notification = isset( $data[ 'notification' ] ) ? $data[ 'notification' ] : false;
		if (
			! $notification
			|| ! isset( $notification->enable )
			|| ! $notification->enable
			|| empty( $notification->notifications )
		) {
			return;
		}

		$notifications_to_send = array();
		foreach ( $notification->notifications as $key => $single_notification ) {
			if ( isset( $single_notification[ 'disabled' ] )
				&& $single_notification[ 'disabled' ] == 1
			) {
				continue;
			}

			$single_notification['form_id'] = $form_id;

			$snapshot = isset( $attached_data[ $form_id ] ) ? $attached_data[ $form_id ][ 'current' ] : array();

			/**
			 * Modify the notification event type.
			 *
			 * Use this to create custom notification event types,
			 * to send notifications out of their natural trigger action,
			 * combined with the cred_custom_notification_event_type_condition hook.
			 *
			 * @param string $single_notification[ 'event' ][ 'type' ]
			 * @param array $notification
			 * @param int $form_id
			 * @param int $user_id
			 * @return string
			 *
			 * @since unknown
			 * @since 1.9.6 Removed by mistake
			 * @since 2.2 Restored
			 */
			$single_notification[ 'event' ][ 'type' ] = apply_filters(
				'cred_notification_event_type',
				$single_notification[ 'event' ][ 'type' ],
				$single_notification,
				$form_id,
				$user_id
			);

			$is_correct_notification_event_type = ( $single_notification[ 'event' ][ 'type' ] == $this->event );

			$is_payment_and_order_complete = ( $single_notification[ 'event' ][ 'type' ] == 'payment_complete'
				&& $this->event == 'order_completed' );

			$is_order_modified = ( $is_correct_notification_event_type
				&& $this->event == 'order_modified'
				&& isset( $data[ 'data_order' ] )
				&& isset( $data[ 'data_order' ][ 'new_status' ] )
				&& $data[ 'data_order' ][ 'new_status' ] == $single_notification[ 'event' ][ 'order_status' ]
				&& $data[ 'data_order' ][ 'previous_status' ] != $data[ 'data_order' ][ 'new_status' ] );

			$is_post_modified = ( $is_correct_notification_event_type
				&& $this->event == 'post_modified'
				&& isset( $object->post_status )
				&& $object->post_status == $single_notification[ 'event' ][ 'post_status' ]
				&& isset( $snapshot[ 'post_status' ] )
				&& $snapshot[ 'post_status' ] != $object->post_status );

			$is_form_submit = ( $is_correct_notification_event_type
				&& $this->event == 'form_submit' );

			$is_order_created = ( $is_correct_notification_event_type
				&& $this->event == 'order_created' );

			/**
			 * Bypass the notification conditions failure.
			 *
			 * Use this to submit notifications out of their natural trigger action,
			 * combined with the cred_custom_notification_event_type_condition hook.
			 *
			 * @param bool false Whether to force send the notification.
			 * @param array $notification
			 * @param int $form_id
			 * @param int $user_id
			 * @return bool
			 *
			 * @since unknown
			 * @since 1.9.6 Removed by mistake
			 * @since 2.2 Restored
			 */
			$is_custom_notification_to_send = apply_filters(
				'cred_custom_notification_event_type_condition',
				false,
				$single_notification,
				$form_id,
				$user_id
			);

			if ( $is_payment_and_order_complete
				|| $is_order_modified
				|| $is_post_modified
				|| $is_form_submit
				|| $is_order_created
				|| $is_custom_notification_to_send
			) {
				$notifications_to_send[] = $single_notification;
			} else {
				if ( isset( $single_notification[ 'event' ] ) ) {
					$condition_fields = array();
					$notification_condition_fields = array();
					if ( isset( $single_notification[ 'event' ][ 'condition' ] )
						&& ! empty( $single_notification[ 'event' ][ 'condition' ] )
					) {
						foreach ( $single_notification[ 'event' ][ 'condition' ] as $key => $condition ) {
							$condition_fields[] = $condition[ 'field' ];
						}
						$notification_condition_fields = $model->get_object_fields( $user_id, $condition_fields );
					}

					$send_notification = $this->evaluate_conditions( $single_notification, $notification_condition_fields, $snapshot );

					if ( $send_notification ) {
						$notifications_to_send[] = $single_notification;
					}
				}
			}
		}

		if ( ! empty( $notifications_to_send ) ) {
			$this->enqueue_notifications( $user_id, $form_id, $notifications_to_send );
		}
	}


	/**
	 * Checks fist_name and second_name updates
	 *
	 * @param int $user_id User ID.
	 * @param string $meta_key Meta key.
	 * @param string $_meta_value Meta value.
	 * @since 2.0.1
	 */
	public function check_for_notifications_for_user_meta( $user_id, $meta_key, $_meta_value ) {
		$user = get_userdata( $user_id );
		$this->check_for_notifications( $user_id, $user );
	}


	/**
	 * There is not creation of Post so in input we just have the new created User ID
	 *
	 * @param int $user_id User is going to be created
	 * @param int $form_id User Form ID
	 * @param array $notificationsToSent
	 *
	 * @return bool
	 */
	public function send_notifications( $user_id, $form_id, $notificationsToSent ) {
		//We are not creating any $post so we can use only the $post container
		global $post;
		$post_id = false;
		if ( $post ) {
			$post_id = $post->ID;
		}

		// custom action hooks here, for 3rd-party integration
		// get Mailer
		$mailer = CRED_Loader::get( 'CLASS/Mail_Handler' );

		$mailer->setFormId( $form_id );
		$mailer->setPostId( $user_id );

		//user created/updated
		$user_data = get_userdata( $user_id );
		$user = ( isset( $user_data->data ) ) ? $user_data->data : null;
		if ( isset( $user ) ) {
			$user->nickname = get_user_meta( $user_id, 'nickname', true );
		}

		$date_format = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i:s' );
		$now = date( $date_format, current_time( 'timestamp' ) );

		// get some data for placeholders
		$form_post = get_post( $form_id );
		$form_title = ( $form_post ) ? $form_post->post_title : '';
		$link = get_permalink( $post_id );
		$title = get_the_title( $post_id );
		$admin_edit_link = CRED_CRED::getPostAdminEditLink( $post_id );

		$subject_placeholders = array_merge( $this->get_placeholders_user_array( $user ), $this->get_placeholders_post_array( $post_id, $title, $form_title, $now ) );
		// placeholder codes, allow to add custom
		$data_subject = $this->get_data_subject_applying_filters( $post_id, $form_id, $subject_placeholders );

		$object_placeholders = array_merge( $this->get_placeholders_user_array( $user ), $this->get_placeholders_post_array( $post_id, $title, $form_title, $now, $link, $admin_edit_link ) );
		// placeholder codes, allow to add custom
		$data_body = $this->get_data_body_applying_filters( $post_id, $form_id, $object_placeholders );

		$send_notification_result = true;
		foreach ( $notificationsToSent as $notification_counter => $notification ) {

			$notification[ 'notification_counter' ] = $notification_counter;
			$notification[ 'form_id' ] = $form_id;
			$notification[ 'post_id' ] = $user_id;

			/*
			 * sendNotification could be called from different hooks (save_posts / updated_post_meta)
			 * checking notification_queue will avoid to send duplicated notifications
			 */
			$hashed_notification_value = hash( 'md5', serialize( $notification ) );
			if ( in_array( $hashed_notification_value, $this->notification_sent_record ) ) {
				continue;
			}
			$this->notification_sent_record[] = $hashed_notification_value;

			//Checks for old notification (back compatibility)
			$notification_name = isset( $notification[ 'name' ] ) ? $notification[ 'name' ] : '';
			$mailer->setNotificationName( $notification_name );
			$mailer->setNotificationNum( $notification_counter );

			// bypass if nothing
			if (
				! $notification ||
				empty( $notification ) ||
				! ( isset( $notification[ 'to' ][ 'type' ] ) ||
					isset( $notification[ 'to' ][ 'author' ] ) )
			) {
				continue;
			}

			// parse Notification Fields
			if ( ! isset( $notification[ 'to' ][ 'type' ] ) ) {
				$notification[ 'to' ][ 'type' ] = array();
			}
			if ( ! is_array( $notification[ 'to' ][ 'type' ] ) ) {
				$notification[ 'to' ][ 'type' ] = (array) $notification[ 'to' ][ 'type' ];
			}

			// reset mail handler
			$mailer->reset();
			$mailer->setHTML( true, false );
			$recipients = array();

			$this->try_add_author_to_recipients( $user_id, $form_id, $notification, $recipients );
			$this->try_add_mail_field_to_recipients( $user_id, $notification, $recipients, $user );
			$this->try_add_wp_user_to_recipients( $notification, $recipients );
			$this->try_add_user_id_field_to_recipients( $user_id, $notification, $recipients );
			$this->try_add_specific_mail_to_recipients( $notification, $recipients );

			// add custom recipients by 3rd-party
			$recipients = apply_filters( 'cred_notification_recipients', $recipients, $notification, $form_id, $user_id );
			if ( ! $recipients
				|| empty( $recipients )
			) {
				continue;
			}

			$this->build_recipients( $recipients );
			$mailer->addRecipients( $recipients );

			if ( isset( $_POST[ CRED_StaticClass::PREFIX . 'cred_container_id' ] ) ) {
				$notification[ 'mail' ][ 'body' ] = str_replace( "[cred-container-id]", CRED_StaticClass::$_cred_container_id, $notification[ 'mail' ][ 'body' ] );
			}

			// build SUBJECT
			$subject = '';
			if ( isset( $notification[ 'mail' ][ 'subject' ] ) ) {
				$subject = $notification[ 'mail' ][ 'subject' ];
			}

			// build BODY
			$body = '';
			if ( isset( $notification[ 'mail' ][ 'body' ] ) ) {
				$body = $notification[ 'mail' ][ 'body' ];
			}

			$mail_subject = CRED_StaticClass::unesc_meta_data( $notification[ 'mail' ][ 'subject' ] );
			$mail_body = CRED_StaticClass::unesc_meta_data( $notification[ 'mail' ][ 'body' ] );

			$hashSubject = CRED_Helper::strHash( "notification-subject-" . $form_id . "-" . $notification_counter );
			$hashBody = CRED_Helper::strHash( "notification-body-" . $form_id . "-" . $notification_counter );

			$form = get_post( $form_id );
			$prefix = self::FORM_PREFIX;
			$context = $prefix . $form->post_title . '-' . $form_id;

			$subject = cred_translate( 'CRED Notification Subject ' . $hashSubject, $mail_subject, $context );
			$body = cred_translate( 'CRED Notification Body ' . $hashBody, $mail_body, $context );

			// replace placeholders
			$subject = $this->replace_placeholders( $subject, $data_subject );

			// replace placeholders
			$body = $this->replace_placeholders( $body, $data_body );

			if ( defined( 'WPCF_EMBEDDED_ABSPATH' )
				&& WPCF_EMBEDDED_ABSPATH
			) {
				require_once WPCF_EMBEDDED_ABSPATH . '/frontend.php';
			}

			// parse shortcodes if necessary
			$subject = do_shortcode( $subject );
			$subject = stripslashes( $subject );

			// pseudo the_content filter
			$body = apply_filters( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, $body );
			$body = stripslashes( $body );

			$mailer->setSubject( $subject );
			$mailer->setBody( $body );

			$_from = $this->get_mail_form_by_notification( $notification );
			if ( ! empty( $_from ) ) {
				$mailer->setFrom( $_from );
			}

			// send it
			$_send_result = $mailer->send();

			if ( $_send_result !== true ) {
				update_option( '_' . $form_id . '_last_mail_error', $_send_result );
			}

			$send_notification_result = $send_notification_result && $_send_result;
		}

		// custom action hooks here, for 3rd-party integration
		do_action( 'cred_after_send_notifications', $user_id );

		return $send_notification_result;
	}

	/**
	 * Try to add to recipients author notification
	 *
	 * @param int $user_id
	 * @param int $form_id
	 * @param array $notification
	 * @param array $recipients
	 *
	 * @return bool
	 */
	protected function try_add_author_to_recipients( $user_id, $form_id, $notification, &$recipients ) {
		if ( isset( $notification[ 'to' ][ 'author' ] )
			&& 'author' == $notification[ 'to' ][ 'author' ]
			&& $user_id
		) {
			$_to_type = 'to';
			$user_info = get_userdata( $user_id );

			$_addr_name = ( isset( $user_info ) && isset( $user_info->user_firstname ) && ! empty( $user_info->user_firstname ) ) ? $user_info->user_firstname : false;
			$_addr_lastname = ( isset( $user_info ) && isset( $user_info->user_lasttname ) && ! empty( $user_info->user_lasttname ) ) ? $user_info->user_lastname : false;
			$_addr = $user_info->user_email;

			if ( isset( $_addr ) ) {
				$recipients[] = array(
					'to' => $_to_type,
					'address' => $_addr,
					'name' => $_addr_name,
					'lastname' => $_addr_lastname,
				);

				return true;
			}
		}

		return false;
	}

	/**
	 * Try to add to recipients mail field notification
	 *
	 * @param int $user_id
	 * @param array $notification
	 * @param array $recipients
	 * @param WP_User|null $the_user
	 *
	 * @return bool
	 */
	protected function try_add_mail_field_to_recipients( $user_id, $notification, &$recipients, $the_user = null ) {
		if (
			in_array( 'mail_field', $notification[ 'to' ][ 'type' ] ) &&
			isset( $notification[ 'to' ][ 'mail_field' ][ 'address_field' ] ) &&
			! empty( $notification[ 'to' ][ 'mail_field' ][ 'address_field' ] )
		) {
			$_to_type = 'to';
			$_addr = false;
			$_addr_name = false;
			$_addr_lastname = false;

			$is_user_email = ($notification[ 'to' ][ 'mail_field' ][ 'address_field' ] == 'user_email'
				&& isset( $the_user )
				&& isset( $the_user->user_email ));

			$_addr = ( $is_user_email ) ? array( $the_user->user_email ) : $this->model->getUserMeta( $user_id, $notification[ 'to' ][ 'mail_field' ][ 'address_field' ], false );

			//get_user_meta as single returns empty string if user meta does not exists
			if ( empty( $_addr ) ) {
				return false;
			}

			if (
				isset( $notification[ 'to' ][ 'mail_field' ][ 'to_type' ] )
				&& in_array( $notification[ 'to' ][ 'mail_field' ][ 'to_type' ], array( 'to', 'cc', 'bcc' ) )
			) {
				$_to_type = $notification[ 'to' ][ 'mail_field' ][ 'to_type' ];
			}

			if (
				isset( $notification[ 'to' ][ 'mail_field' ][ 'name_field' ] )
				&& ! empty( $notification[ 'to' ][ 'mail_field' ][ 'name_field' ] )
				&& '###none###' != $notification[ 'to' ][ 'mail_field' ][ 'name_field' ]
			) {
				$_addr_name = $this->model->getUserMeta( $user_id, $notification[ 'to' ][ 'mail_field' ][ 'name_field' ] );
			}

			if (
				isset( $notification[ 'to' ][ 'mail_field' ][ 'lastname_field' ] )
				&& ! empty( $notification[ 'to' ][ 'mail_field' ][ 'lastname_field' ] )
				&& '###none###' != $notification[ 'to' ][ 'mail_field' ][ 'lastname_field' ]
			) {
				$_addr_lastname = $this->model->getUserMeta( $user_id, $notification[ 'to' ][ 'mail_field' ][ 'lastname_field' ] );
			}

			// add to recipients
			foreach( $_addr as $address_to_notify ) {
				$recipients[] = array(
					'to' => $_to_type,
					'address' => $address_to_notify,
					'name' => $_addr_name,
					'lastname' => $_addr_lastname,
				);
			}

			return true;
		}

		return false;
	}

	/**
	 * Try to add notification user_id_field to recipients
	 *
	 * @param int $user_id
	 * @param array $notification
	 * @param array $recipients
	 *
	 * @return bool
	 */
	protected function try_add_user_id_field_to_recipients( $user_id, $notification, &$recipients ) {
		// notification to an exisiting wp user
		if ( in_array( 'user_id_field', $notification[ 'to' ][ 'type' ] ) ) {
			$_to_type = 'to';
			$_addr = false;
			$_addr_name = false;
			$_addr_lastname = false;

			if (
				isset( $notification[ 'to' ][ 'user_id_field' ][ 'to_type' ] ) &&
				in_array( $notification[ 'to' ][ 'user_id_field' ][ 'to_type' ], array( 'to', 'cc', 'bcc' ) )
			) {
				$_to_type = $notification[ 'to' ][ 'user_id_field' ][ 'to_type' ];
			}

			$recipient_user_id = @trim( $this->model->getUserMeta( $user_id, $notification[ 'to' ][ 'user_id_field' ][ 'field_name' ] ) );
			if ( $recipient_user_id ) {
			$user_info = get_userdata( $recipient_user_id );
				if ( $user_info ) {
					$_addr = ( isset( $user_info->user_email ) && ! empty( $user_info->user_email ) ) ? $user_info->user_email : false;
					$_addr_name = ( isset( $user_info->user_firstname ) && ! empty( $user_info->user_firstname ) ) ? $user_info->user_firstname : false;
					$_addr_lastname = ( isset( $user_info->user_lasttname ) && ! empty( $user_info->user_lasttname ) ) ? $user_info->user_lastname : false;

					// add to recipients
					$recipients[] = array(
						'to' => $_to_type,
						'address' => $_addr,
						'name' => $_addr_name,
						'lastname' => $_addr_lastname,
					);

					return true;
				}
			}
		}

		return false;
	}
}
