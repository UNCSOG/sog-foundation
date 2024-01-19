<?php

class CRED_Ajax_Handler_Association_Form_Duplicate extends Toolset_Ajax_Handler_Abstract{

	private $model_factory = null;
	private $model = null;

	public function __construct( CRED_Ajax $cred_ajax, CRED_Association_Form_Model_Factory $factory = null ) {
		parent::__construct( $cred_ajax );

		if( null !== $factory ){
			$this->model_factory = $factory;
		} else {
			$this->model_factory = $this->get_model_factory();
		}
	}

	function process_call( $arguments ){
		$this->ajax_begin( array( 'nonce' => CRED_Association_Form_Listing_Page::LISTING_ASSOCIATION_NONCE ) );

		$this->model = $this->model_factory->build( 'Model', $_POST );
		$data = $this->model->to_array();
		$data['id'] = 0;
		$data['action'] = 'create';
		$data['form_name'] = $data['form_name'] . ' ' .  __( 'Copy', 'wp-cred' );
		$new_model = $this->model_factory->build( 'Model' );
		$new_model->populate( $data );
		$results = $new_model->process_data();

		if( $results ) {
			$message = sprintf( 'Form %s has been successfully duplicated with ID %s', $new_model->form_name, $new_model->id );
		} else {
			$message = sprintf( 'Something went wrong when duplicating form %s', $_POST['form_name'] );
		}

		$this->ajax_finish(
			array( 'results' => array(
				       	'message' => $message,
				        'id' => $new_model->get_id(),
				        'slug' => $new_model->get_slug(),
				        'post_object' => get_post( $new_model->get_id() )
					) ), $results ? true : false );
	}

	protected function get_model_factory(){
		if( null === $this->model_factory ){
			$this->model_factory = new CRED_Association_Form_Model_Factory();
		}

		return $this->model_factory;
	}
}