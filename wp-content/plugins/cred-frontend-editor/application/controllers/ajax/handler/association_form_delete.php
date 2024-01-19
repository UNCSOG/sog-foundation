<?php

/**
 * Class CRED_Ajax_Handler_Association_Form_Delete
 * Ajax handler for single and bulk delete
 */
class CRED_Ajax_Handler_Association_Form_Delete extends Toolset_Ajax_Handler_Abstract{

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

	/**
	 * Check $_POST and decide is this bulk or single delete
	 * @return bool
	 */
	function is_bulk_delete(){
		if( toolset_getarr( $_POST, 'delete_type', 'single' ) === 'bulk' ){
			return true;
		}
		return false;
	}

	/**
	 * Get post ID from $_POST global
	 * @return int
	 */
	function get_post_id(){
		$post_id = toolset_getarr( $_POST, 'id', false );
		return $post_id;
	}

	function process_call( $arguments ){
		$this->ajax_begin( array( 'nonce' => CRED_Association_Form_Listing_Page::LISTING_ASSOCIATION_NONCE ) );

		if( $this->is_bulk_delete() ){
			$this->bulk_delete();
		} else {
			$this->single_delete();
		}

	}

	/**
	 * Delete single association form
	 */
	protected function single_delete(){
		$this->model = $this->model_factory->build( 'Model' );
		$this->model->populate( $_POST );
		$deleted_post = $this->model->delete_form();

		if( $deleted_post ) {
			$message = sprintf( __( 'Form %s has been successfully deleted', 'wp-cred'), $deleted_post->post_title );
		} else {
			$message = sprintf( __( 'Something went wrong when deleting form %s', 'wp-cred'), $this->model->id );
		}

		$this->ajax_finish( array( 'results' => array( 'message' => $message, 'id' => $this->model->get_id(), 'slug' => $this->model->get_slug() ) ), $deleted_post ? true : false );
	}

	/**
	 * Delete selected association forms
	 */
	protected function bulk_delete(){
		$deleted_posts = $this->form_bulk_delete();
		if( $deleted_posts ) {
			$message = sprintf( __('Selected forms are successfully deleted. Number of deleted forms %d', 'wp-cred'), count( $deleted_posts ) );
		} else {
			$message = __( 'Unable to delete selected forms', 'wp-cred' );
		}

		$this->ajax_finish( array( 'results' => array( 'message' => $message, 'ids' => $deleted_posts ) ), $deleted_posts ? true : false  );
	}

	protected function get_model_factory(){
		if( null === $this->model_factory ){
			$this->model_factory = new CRED_Association_Form_Model_Factory();
		}

		return $this->model_factory;
	}

	/**
	 * Check post global for ids and delete forms
	 * @return array
	 */
	protected function form_bulk_delete( ){
		$get_bulk_ids =  toolset_getarr( $_POST, 'ids', null );
		$deleted_ids = array();

		if( is_array( $get_bulk_ids ) && count( $get_bulk_ids ) > 0 ){
			foreach($get_bulk_ids as $single_id){
				$delete = wp_delete_post( $single_id, true );
				$deleted_ids[] = $delete->ID;
			}

		}

		return $deleted_ids;
	}
}