<?php
/**
 * Handler for the cred_delete_form filter API.
 *
 * Usage: $delete_form = apply_filters( 'cred_delete_form', 'slug-or-ID', 'form-type' );
 * form-type can be: users, posts, relationships
 *
 * @since m2m
 */
class CRED_Api_Handler_Delete_Form extends CRED_Api_Handler_Abstract implements CRED_Api_Handler_Interface {

	/**
	 * Permission to execute delete
	 * @return bool
	 */
	private function permission_to_execute(){

		$can_execute = true;

		/**
		 * Add logic to prevent executing delete in some cases,
		 * like wrong user permission or calling outside admin area
		 */

		return $can_execute;

	}

	public function __construct() { }

	/**
	 * @param array $arguments
	 *
	 * @return array
	 */
	function process_call( $arguments ) {

		// can be slug or id
		$forms = toolset_getarr( $arguments, 0 );

		if( is_array( $forms ) && count( $forms ) === 0 ) return $this->fail( 'missing_id' );

		$form_type = toolset_getarr( $arguments, 1 );

		// check permissions
		if( ! $this->permission_to_execute() ){
			return $this->fail( 'wrong_permission' );
		}

		// if it's not valid form type stop executing
		if ( ! array_key_exists( $form_type, $this->domain_data ) ) {
			return $this->fail( 'wrong_form_type' );
		}

		// get post type real db name
		$post_type = $this->domain_data[ $form_type ]['post_type'];

		$results = array();

		// check do we have provided ID or Slug
		foreach( $forms as $form ){
			$form_id = ( is_numeric( $form ) ) ? $form : $this->get_by_slug( $form, $post_type );

			// if ID exist try to delete, otherwise return error message
			if( $form_id ){
				$results[] = $this->delete( $form_id );
			} else {
				$results[] = $this->fail( 'missing_id' );
			}
		}

		if( count( $results ) > 0 ){
			return $results;
		}

		// return general unable to delete message
		return $this->fail( 'unable_to_delete' );

	}


	/**
	 * @param $slug
	 * @param $post_type
	 *
	 * @return int|bool
	 */
	private function get_by_slug( $slug, $post_type ){

		global $wpdb;

		// find post id using slug and form post type
		$get_post_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s LIMIT 1",
			$slug, $post_type
		) );

		// return post id if exists
		if( $get_post_id ){
			return (int) $get_post_id;
		}

		return false;
	}


	/**
	 * Delete post
	 * @param $id
	 *
	 * @return array
	 */
	private function delete( $id ){
		$delete = wp_delete_post( $id, true );

		if( $delete && is_object( $delete ) ){
			return array(
				'status' => true,
				'id' => (int) $delete->ID
			);
		}

		return $this->fail( 'unable_to_delete' );
	}

	/**
	 * Fail messages
	 * @param $fail_type
	 *
	 * @return array
	 */
	private function fail( $fail_type ){
		$messages = array(
			'wrong_permission' => array(
				'status' => false,
				'message'=> __("You don't have permission to execute this command",'wp-cred')
			),
			'wrong_form_type' =>  array(
				'status' => false,
				'message'=> __( 'Invalid form type value', 'wp-cred' )
			),
			'missing_id' => array(
				'status' => false,
				'message'=> __( 'Unable to find item under passed ID or Slug', 'wp-cred' )
			),
			'unable_to_delete' => array(
				'status' => false,
				'message'=> __( 'Unable to delete this item', 'wp-cred' )
			),
		);

		return ( key_exists( $fail_type, $messages ) ) ? $messages[ $fail_type ] : $messages['unable_to_delete'];
	}
}