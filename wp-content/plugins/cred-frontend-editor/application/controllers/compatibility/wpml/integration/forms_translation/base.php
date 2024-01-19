<?php
/**
 * WPML integration for translating forms, using legacy WPML ST.
 *
 * @package Toolset Forms
 * @since 2.6
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\FormsTranslation;

use OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\Origin\TypesFieldTranslator;

/**
 * Forms translation controller using legacy WPML ST.
 *
 * @since 2.6
 */
abstract class Base {
	const WPML_STRING_TYPE_LINE = 'LINE';

	protected $post_form_model;

	protected $user_form_model;

	/** @var  \CRED_Association_Form_Model */
	protected $relationship_form_model_factory;

	protected $types_field_translator;

	public function __construct(
		\CRED_Forms_Model $post_form_model,
		\CRED_User_Forms_Model $user_form_model,
		\CRED_Association_Form_Model_Factory $relationship_form_model_factory,
		TypesFieldTranslator $types_field_translator
	) {
		$this->post_form_model = $post_form_model;
		$this->user_form_model = $user_form_model;
		$this->types_field_translator = $types_field_translator;
		$this->relationship_form_model_factory = $relationship_form_model_factory;
	}

	public abstract function initialize();

	protected abstract function register_string( $title, $value, $context, $id_in_group = '', $type = 'LINE', $existing_translation = null );

	protected function get_form_model( $form ) {
		switch ( $form->post_type ) {
			case \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE:
				return $this->post_form_model;
				break;
			case \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE:
				return $this->user_form_model;
				break;
			case \CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE:
				$args = [
					'id' => $form->ID,
					'name' => $form->post_title,
				];
				return $this->relationship_form_model_factory->build( 'Model', $args );
				break;
			default:
				return false;
		}
	}

	protected function generate_form_data( \WP_Post $form ) {
		$form_data = array(
			'post' => $form,
			'message' => '',
			'messages' => array(),
			'notification' => (object) array(
				'notifications' => array()
			)
		);

		$form_model = $this->get_form_model( $form );

		if ( false === $form_model ) {
			return $form_data;
		}

		if ( $form_model instanceof \CRED_Abstract_Model ) {
			$form_data = $this->get_data_from_user_post_form( $form_model, $form_data );
		} else {
			$form_data = $this->get_data_from_relationship_form( $form_model, $form_data );
		}

		return $form_data;
	}

	/**
	 * Generates data object with model for User and Post forms
	 *
	 * @param \CRED_Abstract_Model $form_model Post or User model
	 * @param array $form_data Default form data
	 */
	private function get_data_from_user_post_form( \CRED_Abstract_Model $form_model, $form_data ) {
		$fields = $form_model->getFormCustomFields(
			$form_data['post']->ID,
			array(
				'form_settings',
				'notification',
				'extra',
			)
		);

		$settings = isset( $fields['form_settings'] ) ? $fields['form_settings'] : false;
		$notification = isset( $fields['notification'] ) ? $fields['notification'] : false;
		$extra = isset( $fields['extra'] ) ? $fields['extra'] : false;

		if ( $settings && isset( $settings->form['action_message'] ) ) {
			$form_data['message'] = $settings->form['action_message'];
		}

		if ( $notification ) {
			$form_data['notification'] = $notification;
		}

		if ( $extra && isset( $extra->messages ) ) {
			$form_data['messages'] = $extra->messages;
		}
		return $form_data;
	}

	/**
	 * Generates data object with model for User and Post forms
	 *
	 * @param \CRED_Abstract_Model $form_model Post or User model
	 * @param array $form_data Default form data
	 */
	private function get_data_from_relationship_form( \CRED_Association_Form_Model $form_model, $form_data ) {
		$form_data['post'] = get_post( $form_model->get_id() );
		$form_data['messages'] = $form_model->messages;

		return $form_data;
	}

	protected abstract function get_form_context( \WP_Post $form );

	public function register_form( $form_id, \WP_Post $form ) {
		$form_data = $this->generate_form_data( $form );
		$strings = $this->register_strings( $form, $form_data );
	}

	protected abstract function register_strings( \WP_Post $form, $form_data );

}
