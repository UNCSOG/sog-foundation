<?php

/**
 * Class CRED_Api_Handler_Create_New_Form
 *
 * handler for cred_create_new_form filter: apply_filters( 'cred_create_new_form', $form = null/object, $name = '', $domain = 'posts/users/relationships', $args = array / null );
 *
 * $args keys maybe:
 * 'mode' => new/edit, 'post_type' => 'post/page/cpt', 'user_role' => 'admin/guest/editor/etc', 'autogenerate_user' => $bool, 'autogenerate_password' => $bool, 'autogenerate_nickname' => $bool
 *
 */
class CRED_Api_Handler_Create_New_Form extends CRED_Api_Handler_Abstract implements CRED_Api_Handler_Interface{

	private $factory;

	public function __construct( CRED_Association_Form_Model_Factory $model_factory = null ) {
		if( null !== $model_factory ){
			$this->factory = $model_factory;
		} else {
			$this->factory = $this->get_association_form_model_factory();
		}
	}

	public function process_call( $arguments ) {

		$domain = toolset_getarr( $arguments, 2 );


		if ( ! array_key_exists( $domain, $this->domain_data ) ) {
			return array();
		}

		$name = toolset_getarr( $arguments, 1 );

		if( ! $name ) {
			return array();
		}

		$args = toolset_getarr( $arguments, 3 );
		$mode = toolset_getarr( $args, 'mode' );

		$result = null;

		switch ( $domain ) {
			case CRED_Form_Domain::POSTS:
				$post_type = toolset_getarr( $args, 'post_type' );
				$result = $this->create_post_form( $name, $mode, $post_type );
				break;
			case CRED_Form_Domain::USERS:
				$user_role = toolset_getarr( $args, 'user_role' );
				$autogenerate_user = toolset_getarr( $args, 'autogenerate_user' );
				$autogenerate_password = toolset_getarr( $args, 'autogenerate_password' );
				$autogenerate_nickname = toolset_getarr( $args, 'autogenerate_nickname' );
				$result = $this->create_users_form( $name, $mode, $user_role, $autogenerate_user, $autogenerate_password, $autogenerate_nickname );
				break;
			case CRED_Form_Domain::ASSOCIATIONS:
				$result = $this->create_relationship_form( $name );
				break;
		}


		if( null !== $result ){
			return array(
				'status' => true,
				'result' => $result
			);
		}

		return array(
			'status' => false,
			'message' => __( 'It was impossible to create the relationship form this time!', 'wp-cred' )
		);
	}

	public function get_association_form_model_factory(){
		return new CRED_Association_Form_Model_Factory();
	}

	/**
	 * @param $name
	 * @param $mode
	 * @param $user_role
	 * @param $autogenerate_user
	 * @param $autogenerate_password
	 * @param $autogenerate_nickname
	 *
	 * @return null|object
	 */
	private function create_users_form( $name, $mode = 'new', $user_role = 'guest', $autogenerate_user = false, $autogenerate_password = true, $autogenerate_nickname = true ){
		if ( ! class_exists( 'CredUserFormCreator' ) ) {
			require_once CRED_ABSPATH . '/library/toolset/cred/embedded/classes/CredUserFormCreator.php';
		}
		$id = CredUserFormCreator::cred_create_form( $name, $mode, array( $user_role ), $autogenerate_user, $autogenerate_password, $autogenerate_nickname);

		if( $id ){
			return (object) array(
				'ID' => $id,
				'name' => $name,
				'user_role' => $user_role
			);
		}

		return null;
	}

	private function create_post_form( $name, $mode = 'new', $post_type = 'post' ){
		if ( ! class_exists( 'CredFormCreator' ) ) {
			require_once CRED_ABSPATH . '/library/toolset/cred/embedded/classes/CredFormCreator.php';
		}
		$id = CredFormCreator::cred_create_form( $name, $mode, $post_type );

		if( $id ){
			return (object) array(
				'ID' => $id,
				'name' => $name,
				'mode' => $mode,
				'post_type' => $post_type
			);
		}

		return null;
	}

	/**
	 * @param $name
	 *
	 * @return null|object
	 */
	private function create_relationship_form( $name ){

		try{
			$model = $this->factory->build( 'Model', array( 'name' => $name, 'action' => 'create' ) );
		} catch( Exception $exception ){
			error_log( $exception->getMessage() );
			return null;
		}


		$form_id = $model->process_data();

		if( !$form_id ) return null;

		$form = get_post( $form_id );

		return (object) array(
			'ID' => $form->ID,
			'name' => $form->post_title,
			'slug' => $form->post_name
		);
	}

}