<?php
/**
 * WPML integration for translating forms, using TM packages.
 *
 * @package Toolset Forms
 * @since 2.6
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\FormsTranslation;

use OTGS\Toolset\CRED\Controller\Forms\User\Main as UserFormController;
use OTGS\Toolset\CRED\Controller\Forms\Post\Main as PostFormController;

/**
 * Forms translation controller using TM packages.
 *
 * @since 2.6
 */
class Packages extends Base {

	const SHORTCODE_NAME = 'cred_i18n';
	const PREFIX = 'toolset-forms-';

	/**
	 * Caches packages.
	 * I don't like how WPML works, it needs a unique ID for the package and for that I have to use the form post, so I need it each time I translate some string
	 * Also, Forms is a mess and code is spread all over different classes or directories
	 *
	 * @var array
	 */
	private $packages = [];

	/**
	 * Stores Types translations so they can be translate it initially so users don't need to translate them in Forms if they are already translated in Types
	 *
	 * @var array
	 */
	private $types_translations = [];

	public function initialize() {
		add_action( 'cred_admin_save_form', array( $this, 'register_form' ), 10, 2 );
		add_shortcode( self::SHORTCODE_NAME, array( $this, 'cred_i18n_shortcode' ) );
		add_filter( 'cred_form_field_config', [ $this, 'translate_fields' ] );
		add_filter( 'cred_translate_content', [ $this, 'translate_field_content' ], 10, 2 );
		add_filter( 'cred_translate_action_message', [ $this, 'translate_message' ], 10, 3 );
		add_filter( 'cred_mail_notification', [ $this, 'translate_notifications' ], 10, 3 );
		add_action( 'delete_post', [ $this, 'delete_wpml_package' ], 10, 2 );
	}

	/**
	 * Register WPML string using packages
	 *
	 * @param string $title String title
	 * @param string $value String value
	 * @param array $array Package context
	 * @param string $id_in_group String slug
	 * @param string $string $type String type: LINE, AREA or VISUAL
	 */
	protected function register_string( $title, $value, $context, $id_in_group = '', $type = 'LINE', $existing_translations = null ) {
		$prefix = self::PREFIX . $context['name'] . '-';
		$name = $prefix . $id_in_group;
		if ( ! empty( $existing_translations ) ) {
			$this->types_translations[ $name ] = $existing_translations;
		}
		$form_post = get_post( $context['name'] );
		if ( in_array( $form_post->post_status, [ 'publish', 'private' ] ) ) {
			do_action(
				'wpml_register_string',
				$value,
				$name,
				$context,
				$title,
				$type
			);
		}
	}

	/**
	 * Returns package context using a post
	 *
	 * @param WP_Post $form Post form
	 * @return array
	 */
	protected function get_form_context( \WP_Post $form ) {
		if ( isset( $this->packages[ $form->ID ] ) ) {
			return $this->packages[ $form->ID ];
		}
		$this->packages[ $form->ID ] = array(
			'kind' => 'Toolset Forms',
			'name' => $form->ID,
			'title' => $form->post_title,
			'edit_link' => get_edit_post_link( $form->ID ),
		);
		return $this->packages[ $form->ID ];
	}

	/**
	 * Gets the package context using cached data
	 *
	 * Why do I need this? because when translating a post, the field is not attached to any post, but it is needed to translate the field, so it avoids getting post from DB
	 *
	 * @param int $form_id Form post ID
	 * @return array
	 */
	private function get_package_context( $form_id ) {
		if ( isset( $this->packages[ $form_id ] ) ) {
			return $this->packages[ $form_id ];
		}
		$form_post = get_post( $form_id );
		return $this->get_form_context( $form_post );
	}

	protected function register_strings( \WP_Post $form, $form_data ) {
		$context = $this->get_form_context( $form );
		// Title
		$this->register_string( 'Title', $form_data['post']->post_title, $context, 'title' );

		// Submission message
		$this->register_string( 'Message', $form_data['message'], $context, 'display-message' );

		$this->process_content( $form, $context );

		// register messages
		$this->register_messages( $form_data['messages'], $context );

		// Notifications
		$this->register_notifications( $form_data['notification'], $context );

		$this->provide_types_translations( $form->ID );
	}

	/**
	 * Register messages
	 *
	 * @param array $messages Form messages
	 */
	private function register_messages( $messages, $context ) {
		if ( is_array( $messages ) ) {
			foreach ( $messages as $message_id => $message ) {
				$this->register_string( 'Message for '. $message_id, $message, $context, 'message-' . $message_id );
			}
		}
	}

	/**
	 * Register notifications
	 *
	 * @param array $notifications Form notifications
	 */
	private function register_notifications( $notifications, $context ) {
		if ( $notifications && isset( $notifications->notifications ) && is_array( $notifications->notifications ) ) {
			foreach ( $notifications->notifications as $notification_id => $notification ) {
				$mail_subject = $notification['mail']['subject'];
				$mail_body = $notification['mail']['body'];

				$this->register_string( 'Notification ' . $notification_id . ' Subject', $mail_subject, $context, $notification_id . '-notification-subject' );
				$this->register_string( 'Notification ' . $notification_id . ' body', $mail_body, $context, $notification_id . '-notification-body' );
				if ( ! empty( $notification['from']['name'] ) ) {
					$from = sanitize_text_field( $notification['from']['name'] );
					$this->register_string( 'Notification ' . $notification_id . ' From', $from, $context, $notification_id . '-notification-from' );
				}
			}
		}
	}

	/**
	 * Process Form content to extract shortcodes
	 *
	 * @param string $form Form post
	 * @param string $context WPML package context
	 */
	private function process_content( \WP_Post $form, $context ) {
		$content = $form->post_content;
		$shortcodes = array(
			'cred-field',
			'cred_field',
			'cred_generic_field',
			'cred_i18n',
			'cred-relationship-field',
			'cred-form-cancel',
			'cred-form-submit',
		);
		$shortcode_regex = get_shortcode_regex( $shortcodes );
		preg_match_all( "/$shortcode_regex/", $content, $matches );
		foreach ( $matches[3] as $index => $match ) {
			$attributes = shortcode_parse_atts( $match );
			switch ( $matches[2][ $index ] ) {
				case 'cred-field':
				case 'cred_field':
				case 'cred-relationship-field':
					$this->register_strings_from_cred_shortcodes( $form, $context, $attributes );
					break;
				case 'cred_generic_field':
					$attributes['data'] = (array) json_decode( $matches[5][ $index ], true );
					$this->register_strings_from_cred_shortcodes( $form, $context, $attributes );
					break;
				case 'cred-form-cancel':
					if ( ! empty( $attributes['message'] ) ) {
						$this->register_string( 'Relationship ' . $form->post_title . ' Cancel', $attributes['message'], $context, 'message-cancel' );
					}
					break;
				case 'cred-form-submit':
					if ( ! empty( $attributes['label'] ) ) {
						$this->register_string( 'Relationship ' . $form->post_title . ' Submit', $attributes['label'], $context, 'submit' );
					}
					break;
				case 'cred_i18n':
					preg_match( '/(.*)-label$/', $attributes['name'], $m );
					if ( isset( $m[1] ) ) {
						// In this case, it is a label for the Field that is related to a field, so it might be translated by Types
						$existing_translation = '';
						$field_definition = $this->get_field_definition_by_slug( \Toolset_Field_Utils::DOMAIN_POSTS, $m[1] );
						if ( $field_definition ) {
							$this->types_field_translator->set_field_definition( $field_definition );
							$existing_translation = $this->types_field_translator->get_translations( $matches[5][ $index ], 'name' );
						}
					}
					$this->register_string( 'Generic text (index: ' . $index . ')', $matches[5][ $index ], $context, $attributes['name'], self::WPML_STRING_TYPE_LINE, $existing_translation );
					break;
			}
		}
	}

	/**
	 * Returns a Types field definition
	 *
	 * @param string $field_domain Field domain: \Toolset_Field_Utils::DOMAIN_USERS or \Toolset_Field_Utils::DOMAIN_POSTS
	 * @param string $field_slug Field slug
	 * @return \Toolset_Field_Definition
	 */
	private function get_field_definition_by_slug( $field_domain, $field_slug ) {
		$field_definition_factory = \Toolset_Field_Definition_Factory::get_factory_by_domain( $field_domain );
		return $field_definition_factory->load_field_definition( $field_slug );
	}

	/**
	 * When using `[cred_field]` and `[cred_generic_field]` shortcode, strings must be registered
	 *
	 * @param \WP_Post $form
	 * @param string $atts Shortcode attributes
	 */
	private function register_strings_from_cred_shortcodes( \WP_Post $form, $context, $atts ) {
		extract(
			shortcode_atts( array(
				'value' => null,
				'select_text' => null,
				'field' => null,
			), $atts )
		);

		// Fix for relationship fields
		if ( ! $field && isset( $atts['name'] ) ) {
			$field = $atts['name'];
		}

		$field_domain = 'cred-user-form' === $form->post_type? \Toolset_Field_Utils::DOMAIN_USERS : \Toolset_Field_Utils::DOMAIN_POSTS;
		$field_definition = $this->get_field_definition_by_slug( $field_domain, $field );

		if ( $field_definition ) {
			$this->types_field_translator->set_field_definition( $field_definition );
			if ( in_array( $field_definition->get_type()->get_slug(), array(
				\Toolset_Field_Type_Definition_Factory::CHECKBOXES,
				\Toolset_Field_Type_Definition_Factory::RADIO,
				\Toolset_Field_Type_Definition_Factory::SELECT
			) ) ) {
				$options = $field_definition->get_field_options();
				if ( ! empty( $options ) ) {
					foreach ( $options as $option_id => $option ) {
						$label = $option->get_label();
						if ( ! empty( $label ) ) {
							$this->register_string( 'Option for field: ' . $field . ' with id: ' . $option_id, $label, $context, $option_id );
						}
					}
				}
			}
			if ( $field_definition->get_type()->get_slug() === \Toolset_Field_Type_Definition_Factory::CHECKBOX ) {
				$this->register_string( 'Checkbox label for field: ' . $field, $field_definition->get_name(), $context, $field . '-label' );
			}
			$description = $field_definition->get_description();
			if ( ! empty( $description ) ) {
				$this->register_string( 'Description for field: ' . $field, $description, $context, $field . '-description' );
			}
			$data_attributes = [
				'placeholder' => [ 'Placeholder ' . $field, $field . '-placeholder', 'placeholder' ],
				'user_default_value' => [ 'Default value ' . $field, $field . '-default-value', 'default value' ],
				'display_value_not_selected' => [ 'Not selected value ' . $field, $field . '-not-selected-value', 'value not selected' ],
				'display_value_selected' => [ 'Selected value ' . $field, $field . '-selected-value', 'value selected' ],
			];
			$field_definition_array = $field_definition->get_definition_array();
			if ( isset( $field_definition_array['data'] ) ) {
				foreach ( $data_attributes as $data_key => $data_attribute ) {
					if ( isset( $field_definition_array['data'][ $data_key ] ) ) {
						$existing_translations = $this->types_field_translator->get_translations( $field_definition_array['data'][ $data_key ], $data_attribute[2] );
						$this->register_string( $data_attribute[0], $field_definition_array['data'][ $data_key ], $context, $data_attribute[1], self::WPML_STRING_TYPE_LINE, $existing_translations );
					}
				}
				if ( isset( $field_definition_array['data']['validate'] ) ) {
					foreach ( $field_definition_array['data']['validate'] as $validation => $validation_data ) {
						$this->register_string( 'Validation ' . $field . ' ' . $validation, $validation_data['message'], $context, $field . '-validation-' . $validation );
					}
				}
			}
		}

		// Generic fields
		if ( isset( $atts['data']['options'] ) ) {
			foreach ( $atts['data']['options'] as $option_id => $option_data ) {
				if ( isset( $option_data['label'] ) ) {
					$this->register_string( 'Label ' . $field . '-' . $index, $option_data['label'], $context, $field . '-option' );
				}
			}
		}

		if ( isset( $atts['select_text'] ) ) {
			$this->register_string( 'Label for select ' . $field, $atts['select_text'], $context, $field . '-select_text' );
		}

		if ( null !== $value
			&& ! empty( $value )
			&& is_string( $value )
		) {
			$name = $field . '-default-value';
			$this->register_string( 'Default value ' . $field, $value, $context, $name );
		}
	}

	/**
	 * Translates a string using WPML packages
	 *
	 * @param string $string String to translate
	 * @param string $name String name
	 * @return string
	 */
	private function translate_string( $string, $name ) {
		$form_post_id = apply_filters( 'cred_current_form_post_id', null );
		$package = $this->get_package_context( $form_post_id );

		return apply_filters( 'wpml_translate_string', $string, self::PREFIX . $form_post_id . '-' . $name, $package );
	}

	/**
	 * [cred_i18n] renderer
	 *
	 * @param array $atts Shortcode attributes
	 * @param string $content Shortcode content
	 * @return string
	 */
	public function cred_i18n_shortcode( $atts, $content ) {
		$atts = shortcode_atts( array(
			'name' => '',
		), $atts );

		return $this->translate_string( $content, $atts['name'] );
	}

	/**
	 * Translates Field data
	 *
	 * Note: I would create a different class for translating it, but the way Forms is "designed", makes it very difficult to achieve
	 * A form contains settings for messages, but those messages are included later in the field, so a field can't be translated indepently as an entity,
	 * but mixing strings from the field and the rest of the options.
	 *
	 * @param array $field_data Field data
	 * @return array
	 * @see CRED_Form_Rendering::renderField
	 */
	public function translate_fields( $field_data ) {
		// Default value that is actually "value"
		$normalized_name = preg_replace( '/wpcf\[([^\]]*)\]/', '$1', str_replace( 'wpcf-', '', $field_data['name'] ) );
		if ( isset( $field_data['value'] ) && ! empty( $field_data['value'] ) ) {
			$name = $normalized_name . '-default-value';
			if ( 'submit' === $field_data['type'] ) {
				$name = 'form_submit-default-value';
			}
			// Why the string name contains `-default-value` but the field data is `value`?
			// Because the field option is Default Value, but the config option is Value 🤷
			$field_data['value'] = $this->translate_string( $field_data['value'], $name );
		}

		// Fields attributes
		if ( isset( $field_data['options'] ) ) {
			foreach ( $field_data['options'] as $key => $option ) {
				if ( isset( $field_data['options'][ $key ]['title'] ) ) {
					$field_data['options'][ $key ]['title'] = $this->translate_string( $field_data['options'][ $key ]['title'], $key );
				}
			}
		}

		if ( \Toolset_Field_Type_Definition_Factory::CHECKBOX === $field_data['type'] ) {
			$field_data['title'] = $this->translate_string( $field_data['title'], $normalized_name . '-label' );
		}

		// Fields validation
		if ( isset( $field_data['validation'] ) ) {
			foreach ( $field_data['validation'] as $key => $validate ) {
				if ( isset( $field_data['validation'][ $key ]['message'] ) ) {
					// This is needed because JS errors, although they are translated after submitting
					$mapping = [
						'user_login-validation-username' => 'message-cred_message_invalid_username',
						'user_login-validation-required' => 'message-cred_message_field_required',
						'post_title-validation-required' => 'message-cred_message_field_required',
						'user_pass-validation-required' => 'message-cred_message_field_required',
						'user_pass2-validation-equalto' => 'message-cred_message_passwords_do_not_match',
						'user_email-validation-email' => 'message-cred_message_enter_valid_email',
						'user_email-validation-required' => 'message-cred_message_field_required',
					];
					$string_suffix = $normalized_name . '-validation-' . $key;
					if ( array_key_exists( $string_suffix, $mapping ) ) {
						$string_suffix = $mapping[ $string_suffix ];
					}
					$field_data['validation'][ $key ]['message'] = $this->translate_string( $validate['message'], $string_suffix );
				}
			}
		}

		// Form messages that are included in the form 🤦‍♂️
		if ( isset( $field_data['attribute'] ) ) {
			// Why not, the message slug don't match with attributes names.
			$messages = [
				'add_text' => 'message-cred_message_add_taxonomy',
				'add_new_text' => 'message-cred_message_add_new_taxonomy',
				'placeholder' => $normalized_name . '-placeholder',
				'select_text' => $normalized_name . '-select_text',
			];
			foreach ( $messages as $attribute => $message_name ) {
				if ( isset( $field_data['attribute'][ $attribute ] ) ) {
					$field_data['attribute'][ $attribute ] = $this->translate_string( $field_data['attribute'][ $attribute ], $message_name );
				}
			}
		}

		// Relationship forms
		if ( isset( $field_data['user_default_value'] ) && ! empty( $field_data['user_default_value'] ) ) {
			$name = $normalized_name . '-default-value';
			$field_data['user_default_value'] = $this->translate_string( $field_data['user_default_value'], $name );
			$field_data['value'] = $field_data['user_default_value'];
		}

		// Special case for relationship forms
		if ( isset( $field_data['placeholder'] ) && ! empty( $field_data['placeholder'] ) ) {
			$name = $normalized_name . '-placeholder';
			$field_data['placeholder'] = $this->translate_string( $field_data['placeholder'], $name );
		}

		// Descriptions
		if ( isset( $field_data['description'] ) && ! empty( $field_data['description'] ) ) {
			$name = $normalized_name . '-description';
			$field_data['description'] = $this->translate_string( $field_data['description'], $name );
		}

		return $field_data;
	}

	/**
	 * Some Form strings come from Types, so they need to be registered in the TM package but we need to avoid user to translate strings twice: in Types and Forms
	 *
	 * This method set translations coming from Types
	 *
	 * @param int $form_id Form ID needed for getting the package context
	 */
	private function provide_types_translations( $form_id ) {
		$key = 'cred_translated_using_types';
		if ( ! get_post_meta( $form_id, $key, true) && ! empty( $this->types_translations ) ) {
			do_action( 'wpml_set_translated_strings', $this->types_translations, $this->get_package_context( $form_id ) );
			update_post_meta( $form_id, $key, true );
		}
	}

	/**
	 * Translates the messages
	 *
	 * @param string $message Text to be translated
	 * @param string $message_type Used in string name
	 * @param int $post_id Form post ID
	 *
	 * @return string
	 */
	public function translate_message( $message, $message_type, $post_id ) {
		add_filter( 'cred_current_form_post_id', function( $original_post_id ) use ( $post_id ) {
			if ( $original_post_id ) {
				return $original_post_id;
			}
			return $post_id;
		} );

		return $this->translate_string( $message, $message_type );
	}

	/**
	 * Action that registers data from relationship forms
	 *
	 * @param \CRED_Association_Form_Model $form Form model
	 */
	public function translate_relationship_form( \CRED_Association_Form_Model $form ) {
		$form_post = get_post( $form->id );
		if ( $form_post ) {
			$context = $this->get_form_context( $form_post );
			$this->register_messages( $form->get_messages(), $context );
		}
	}

	/**
	 * Translates notifications
	 *
	 * @param array $notifications Notifications
	 * @param int $notification_id Notification id
	 * @param int $post_id Form post ID
	 * @return array
	 */
	public function translate_notifications( $notifications, $notification_id, $post_id ) {
		add_filter( 'cred_current_form_post_id', function( $original_post_id ) use ( $post_id ) {
			if ( $original_post_id ) {
				return $original_post_id;
			}
			return $post_id;
		} );

		if ( isset( $notifications['from']['name'] ) ) {
			$notifications['from']['name'] = $this->translate_string( $notifications['from']['name'], $notification_id . '-notification-from' );
		}
		$notifications['mail']['subject'] = $this->translate_string( $notifications['mail']['subject'], $notification_id . '-notification-subject' );
		$notifications['mail']['body'] = $this->translate_string( $notifications['mail']['body'], $notification_id . '-notification-body' );

		return $notifications;
	}

	/**
	 * Translates options labels
	 *
	 * @param string $value String to translate
	 * @param string $key Field option key
	 * @param string
	 */
	public function translate_field_content( $value, $key ) {
		return $this->translate_string( $value, $key );
	}

	/**
	 * Deletes a WPML package attached to a post
	 *
	 * @param int $post_id Post ID.
	 * @param WP_Post $post Post.
	 */
	public function delete_wpml_package( $post_id, $post ) {
		if ( in_array( $post->post_type, [ UserFormController::POST_TYPE, PostFormController::POST_TYPE ] ) ) {
			$package = $this->get_package_context( $post_id );
			do_action( 'wpml_delete_package', $package['name'], $package['kind'] );
		}
	}
}
