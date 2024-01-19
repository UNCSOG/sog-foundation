<?php

/**
 * User Premium Feature Class Handler
 */
interface CRED_User_Premium_Feature_Interface {

	/**
	 * @param int|string $post_id
	 * @param int $order_id
	 *
	 * @return int
	 */
	public function create_draft_temporary_user( $post_id, $order_id = null );

	/**
	 * Get the list of Draft Users
	 *
	 * @return array
	 */
	public function get_draft_users();

	/**
	 * @param $user_id
	 */
	public function delete_db_temporary_user( $user_id );

	/**
	 * @return bool
	 */
	public function delete_all_draft_users();

	/**
	 * @param $userdata
	 * @param $usermeta
	 * @param $fieldsInfo
	 * @param null $removed_fields
	 *
	 * @return array
	 */
	public function add_temporary_user( $userdata, $usermeta, $fieldsInfo, $removed_fields = null );

	/**
	 * @param int $num
	 * @param int|null $order_id
	 *
	 * @return bool|int|WP_Error
	 */
	public function publish_temporary_user( $num, $order_id = null );

	/**
	 * Delete user from Draft list of users
	 *
	 * @param int $num
	 *
	 * @return bool
	 */
	public function delete_draft_temporary_user( $num );
}