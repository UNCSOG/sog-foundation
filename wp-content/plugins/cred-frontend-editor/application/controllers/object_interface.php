<?php

/**
 * Interface implemented by CRED_Post_Data
 */
interface ICRED_Object_Data {

	public function get_post_data( $post_id );

	public function get_user_data( $user_id );
}