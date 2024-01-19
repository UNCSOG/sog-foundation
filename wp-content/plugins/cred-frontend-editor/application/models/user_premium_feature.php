<?php

/**
 * User Premium Feature Class Handler
 *
 * The User Premium Feature means that user, having CRED Commerce and Woocommerce actived,
 * is able to create 'CRED User Forms' with CRED Commerce box sets and associated too a WooCommerce Product (for example membership)
 * In this way cred user form on submission will create hidden users actived only once the memebership Payment is correctly received.
 *
 * @since 1.8.x
 */
class CRED_User_Premium_Feature implements CRED_User_Premium_Feature_Interface {

	const CRED_USER_ORDER_META = '_cred_user_orders';

	const CRED_POST_ID_META = '_cred_post_id';

	/** @var CRED_User_Forms_Model */
	protected $user_form_model;
	/** @var wpdb */
	protected $wpdb;

	private static $instance;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * CRED_User_Premium_Feature constructor.
	 *
	 * @param CRED_User_Forms_Model $user_form_model
	 */
	public function __construct( CRED_User_Forms_Model $user_form_model = null ) {
		if ( null === $user_form_model ) {
			$this->user_form_model = CRED_Loader::get( 'MODEL/UserForms' );
		}
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * @param int|string $post_id
	 * @param int $order_id
	 *
	 * @return int
	 */
	public function create_draft_temporary_user( $post_id, $order_id = null ) {
		global $wpdb;
		$_cred_user_orders = $this->get_draft_users();
		if ( ! isset( $_cred_user_orders[ $post_id ] ) ) {
			return - 1;
		}

		$data = $_cred_user_orders[ $post_id ];
		$user_data = $data[ 'userdata' ];
		$user_role = is_array( $user_data[ 'user_role' ] ) ? $user_data[ 'user_role' ] : json_decode( $user_data[ 'user_role' ], true );
		$user_role = $user_role[ 0 ];

		unset( $user_data[ 'user_role' ] );
		unset( $user_data[ 'ID' ] );

		$real_post_id = $this->user_form_model->addUser( $data[ 'userdata' ], $data[ 'usermeta' ], $data[ 'fieldsInfo' ], $data[ 'removed_fields' ] );
		if ( null != $order_id ) {
			update_post_meta( $post_id, self::CRED_POST_ID_META, $real_post_id );
		}

		return $real_post_id;
	}

	/**
	 * Get the list of Draft Users
	 *
	 * @return array
	 */
	public function get_draft_users() {
		$_cred_user_orders = get_option( self::CRED_USER_ORDER_META, "" );
		if ( ! isset( $_cred_user_orders ) || empty( $_cred_user_orders ) ) {
			$_cred_user_orders = array();
		}

		if ( ! empty( $_cred_user_orders ) ) {
			$_cred_user_orders = unserialize( CRED_StaticClass::decrypt( $_cred_user_orders ) );
		}

		return $_cred_user_orders;
	}

	/**
	 * @param int $user_id
	 */
	public function delete_db_temporary_user( $user_id ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE user_id = %d", $user_id )
		);
		$wpdb->query(
			$wpdb->prepare( "DELETE FROM $wpdb->users WHERE ID = %d", $user_id )
		);
	}

	/**
	 * @return bool
	 */
	public function delete_all_draft_users() {
		update_option( self::CRED_USER_ORDER_META, "" );
		$cred_user_orders = $this->get_draft_users();

		return empty( $cred_user_orders );
	}

	/**
	 * @param array $userdata
	 * @param array $usermeta
	 * @param array $fieldsInfo
	 * @param array|null $removed_fields
	 *
	 * @return array
	 */
	public function add_temporary_user( $userdata, $usermeta, $fieldsInfo, $removed_fields = null ) {
		if ( CRED_StaticClass::$_password_generated != null ) {
			$usermeta[ md5( '_password_generated' ) ] = CRED_StaticClass::$_password_generated;
		}

		$temp = array();

		$_cred_user_orders = get_option( self::CRED_USER_ORDER_META, "" );
		if ( ! isset( $_cred_user_orders ) || empty( $_cred_user_orders ) ) {
			$_cred_user_orders = array();
		}

		if ( ! empty( $_cred_user_orders ) ) {
			$_cred_user_orders = unserialize( CRED_StaticClass::decrypt( $_cred_user_orders ) );
		}

		if ( ! isset( $removed_fields ) ) {
			$removed_fields = array();
		}

		$count = "draft_" . count( $_cred_user_orders );
		$_cred_user_orders[ $count ] = array(
			'userdata' => $userdata,
			'usermeta' => $usermeta,
			'fieldsInfo' => $fieldsInfo,
			'removed_fields' => $removed_fields,
		);

		if ( ! empty( $_cred_user_orders ) ) {
			$_cred_user_orders = CRED_StaticClass::encrypt( serialize( $_cred_user_orders ) );
		}

		update_option( self::CRED_USER_ORDER_META, $_cred_user_orders );
		unset( $temp );

		return array( 'is_commerce' => true, 'user_id' => $count );
	}

	/**
	 * @param int $num
	 * @param int|null $order_id
	 *
	 * @return bool|int|WP_Error
	 */
	public function publish_temporary_user( $num, $order_id = null ) {
		$_cred_user_orders = get_option( self::CRED_USER_ORDER_META, "" );

		if ( ! isset( $_cred_user_orders ) || empty( $_cred_user_orders ) ) {
			return false;
		}

		if ( ! empty( $_cred_user_orders ) ) {
			$_cred_user_orders = unserialize( CRED_StaticClass::decrypt( $_cred_user_orders ) );
		}

		if ( ! isset( $_cred_user_orders[ $num ] ) ) {
			return false;
		}

		$data = $_cred_user_orders[ $num ];

		//avoid to delete temporary user because of possible refund
		if ( ! empty( $_cred_user_orders ) ) {
			$_cred_user_orders = CRED_StaticClass::encrypt( serialize( $_cred_user_orders ) );
		}

		update_option( "_cred_user_orders", $_cred_user_orders );

		if ( isset( $data[ 'usermeta' ][ md5( '_password_generated' ) ] ) ) {
			CRED_StaticClass::$_password_generated = $data[ 'usermeta' ][ md5( '_password_generated' ) ];
			unset( $data[ 'usermeta' ][ md5( '_password_generated' ) ] );
		}

		if ( (int) toolset_getarr( $data[ 'userdata' ], 'ID', 0 ) > 0 ) {
			$new_user_id = $this->user_form_model->updateUser( $data[ 'userdata' ] );
			$this->user_form_model->updateUserInfo( $new_user_id, $data[ 'usermeta' ], $data[ 'fieldsInfo' ], $data[ 'removed_fields' ] );
		} else {
			$new_user_id = $this->user_form_model->addUser( $data[ 'userdata' ], $data[ 'usermeta' ], $data[ 'fieldsInfo' ], $data[ 'removed_fields' ] );
		}

		if ( isset( $order_id ) ) {
			$order_id = (int) $order_id;
			$sql = sprintf( 'SELECT * FROM %s WHERE post_id = %d', $this->wpdb->postmeta, $order_id );
			$metas = $this->wpdb->get_results( $sql );
			foreach ( $metas as $meta ) {
				$mkey = substr( $meta->meta_key, 1, strlen( $meta->meta_key ) );
				update_user_meta( $new_user_id, $mkey, $meta->meta_value );
			}

			//update draft_N with the real user
			update_post_meta( $order_id, self::CRED_POST_ID_META, $new_user_id );
		}

		return $new_user_id;
	}

	/**
	 * Delete user from Draft list of users
	 *
	 * @param int $num
	 *
	 * @return bool
	 */
	public function delete_draft_temporary_user( $num ) {
		$_cred_user_orders = get_option( self::CRED_USER_ORDER_META, "" );
		if ( ! isset( $_cred_user_orders )
			|| empty( $_cred_user_orders ) ) {
			return false;
		}

		if ( ! empty( $_cred_user_orders ) ) {
			$_cred_user_orders = unserialize( CRED_StaticClass::decrypt( $_cred_user_orders ) );
		}

		if ( ! isset( $_cred_user_orders[ $num ] ) ) {
			return false;
		}

		unset( $_cred_user_orders[ $num ] );

		if ( ! empty( $_cred_user_orders ) ) {
			$_cred_user_orders = CRED_StaticClass::encrypt( serialize( $_cred_user_orders ) );
		}

		update_option( self::CRED_USER_ORDER_META, $_cred_user_orders );

		return true;
	}
}
