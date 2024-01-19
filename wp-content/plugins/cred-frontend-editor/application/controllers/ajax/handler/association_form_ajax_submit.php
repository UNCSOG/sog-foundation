<?php

class CRED_Ajax_Handler_Association_Form_Ajax_Submit extends Toolset_Ajax_Handler_Abstract{
	const MODEL_PREFIX = 'Association_Model';

	private $model_factory = null;
	private $model = null;
	private $api = null;

	public function __construct( CRED_Ajax $cred_ajax, CRED_Association_Form_Model_Factory $factory = null, CRED_Association_Form_Relationship_API_Helper $api = null ) {
		parent::__construct( $cred_ajax );

		if( null !== $factory ){
			$this->model_factory = $factory;
		} else {
			$this->model_factory = $this->get_model_factory();
		}

		if( null !== $api ){
			$this->api = $api;
		} else {
			$this->api = $this->get_api();
		}
	}

	function process_call( $arguments ){
		$this->ajax_begin( array( 'nonce' => CRED_Association_Form_Main::CRED_ASSOCIATION_FORM_AJAX_NONCE, 'nonce_parameter' => CRED_Association_Form_Main::CRED_ASSOCIATION_FORM_AJAX_NONCE, 'is_public' => true ) );

		$this->set_model();
		$this->upload_posted_media_fields();
		$this->populate_model( $_POST );

		$results = $this->process_data();

		$association = isset( $results['association'] ) ? $results['association'] : null;

		$fields_has_changed = $this->check_if_any_field_has_been_saved( $results );

		$messages = get_post_meta( $_POST['cred_form_id'], 'messages', true);

		if ( null === $association ) {
			$message = $messages['cred_message_post_not_saved_singular'] . ' ' . __( 'The Association you try to save does not exist.', 'wp-cred' );
		} else if( is_numeric($association ) || $fields_has_changed ) {
			$message = $messages['cred_message_post_saved'];
		} else {
			$message = $messages['cred_message_post_not_saved_singular'] . ' ' . $results['association'];
		}

		$results_send = array( 'message' => $message,
		                       'id' => $this->model->get_association(),
		                       'slug' => $this->model->get_relationship_slug(),
		                       'ok' => is_numeric( $association ) ? true : false,
		                       'availability' => $this->get_role_elements_availability()
		);

		$is_success = $association ? true : false;

		$this->ajax_finish( array( 'results' => $results_send ),  $is_success );
	}

	protected function get_model_factory(){
		if( null === $this->model_factory ){
			$this->model_factory = new CRED_Association_Form_Model_Factory();
		}

		return $this->model_factory;
	}

	private function check_if_any_field_has_been_saved( $results ){
		if( ! isset( $results['fields'] ) ){
			return false;
		}

		$fields_data = $results['fields'];

		$changed = false;

		foreach( $fields_data as $field => $value ){
			if( false !== $value ){
				$changed = true;
				break;
			}
		}

		return $changed;
	}

	protected function get_api(){
		if( null === $this->api ){
			$this->api = new CRED_Association_Form_Relationship_API_Helper();
		}

		return $this->api;
	}

	protected function set_model(){
		if( ! $this->model ){
			$this->model = $this->model_factory->build( self::MODEL_PREFIX, $this->api );
		}
		return $this->model;
	}

	/**
	 * @since 2.4
	 */
	private function upload_posted_media_fields() {
		$this->api->upload_posted_media_fields();
	}

	/**
	 * @param array $data
	 */
	private function populate_model( $data ) {
		$this->model->populate( $data );
	}

	/**
	 * @return mixed
	 */
	private function process_data() {
		return $this->model->process_data();
	}

	/**
	 * @return mixed
	 */
	private function is_parent_available_for_new_associations(){
		return $this->model->is_parent_available_for_new_associations( );
	}

	/**
	 * @return mixed
	 */
	private function is_child_available_for_new_associations(){
		return $this->model->is_child_available_for_new_associations( );
	}

	/**
	 * @return array
	 */
	private function get_role_elements_availability(){
		return array(
			CRED_Association_Form_Relationship_API_Helper::ASSOCIATION_ROLE_PARENT => $this->is_parent_available_for_new_associations(),
			CRED_Association_Form_Relationship_API_Helper::ASSOCIATION_ROLE_CHILD => $this->is_child_available_for_new_associations()
		);
	}
}
