<?php

class CRED_Ajax_Handler_Association_Form_Add_New extends Toolset_Ajax_Handler_Abstract{

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
		$this->ajax_begin( array( 'nonce' => CRED_Association_Form_Editor_Page::CREATE_EDIT_ASSOCIATION_NONCE ) );

		$this->model = $this->model_factory->build( 'Model' );
		$this->model->populate( $_POST );

		$results = $this->model->process_data();

		if( $results ) {
			$message = sprintf( 'Form %s has been succesfully saved with ID %s', stripslashes( $_POST['form_name'] ), $results );
		} else {
			$message = sprintf( 'Something went wrong when saving form %s', $_POST['form_name'] );
		}

		$this->ajax_finish( array( 'results' => array( 'message' => $message, 'id' => $this->model->get_id(), 'slug' => $this->model->get_slug() ) ), $results ? true : false );
	}

	protected function get_model_factory(){
		if( null === $this->model_factory ){
			$this->model_factory = new CRED_Association_Form_Model_Factory();
		}

		return $this->model_factory;
	}
}