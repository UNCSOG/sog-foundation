<?php
/**
 * Class Access_Ajax_Handler_Specific_Users_Process
 * Add specific user to Access settings
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Specific_Users_Process extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Specific_Users_Process constructor.
	 *
	 * @param \OTGS\Toolset\Access\Ajax $access_ajax
	 */
	public function __construct( \OTGS\Toolset\Access\Ajax $access_ajax ) {
		parent::__construct( $access_ajax );
	}


	/**
	 * @param mixed $arguments
	 *
	 * @return array
	 */
	function process_call( $arguments ) {

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );


		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['groupid'] ) || ! isset( $_POST['option_name'] ) ) {
			return;
		}

		global $wpcf_access;
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$access_capabilities = \OTGS\Toolset\Access\Models\Capabilities::get_instance();
		$wpcf_access->settings = $access_settings->get_access_settings( true, true );

		$updated = array();

		$users = ( isset( $_POST['users'] ) && is_array( $_POST['users'] ) ? $_POST['users'] : array() );
		$id = $_POST['id'];
		$groupid = $_POST['groupid'];
		$option = $_POST['option_name'];
		$settings = array();
		if ( in_array( $groupid, $this->get_third_party_exception_array() ) !== false ) {
			if ( isset( $wpcf_access->settings->third_party[ $groupid ] ) ) {
				$settings = $wpcf_access->settings->third_party[ $groupid ];
			}
		} else {
			$settings = $wpcf_access->settings->$groupid;
		}

		if ( ! isset( $settings[ $id ] ) ) {
			$settings[ $id ] = array( 'mode' => 'not_managed', 'permissions' => array() );
		}
		$settings[ $id ]['permissions'][ $option ]['users'] = array();
		for ( $i = 0; $i < count( $users ); $i ++ ) {
			$settings[ $id ]['permissions'][ $option ]['users'][] = intval( $users[ $i ] );
		}

		$users = $settings[ $id ]['permissions'][ $option ]['users'];
		$output['options_texts'][ $option ] = '';
		if ( count( $users ) > 0 ) {
			$args = array(
				'orderby' => 'user_login',
				'include' => array_slice( $users, 0, 2 ),
			);
			$user_query = new WP_User_Query( $args );
			foreach ( $user_query->results as $user ) {
				$output['options_texts'][ $option ] .= $user->data->user_login . '<br>';
			}
			$output['options_texts'][ $option ] .= ( ( count( $users ) > 2 ) ? 'and '
				. ( count( $users ) - 2 )
				. ' more' : '' );
		}
		if ( in_array( $groupid, array( 'types', 'tax' ) ) ) {
			$dep = $access_capabilities->access_dependencies();
			$dep = $dep[ $option ];

			$updated = array();
			//Add users from $dep
			if ( isset( $dep['true_allow'] ) && is_array( $dep['true_allow'] ) ) {
				//List options related to current option
				for ( $i = 0; $i < count( $dep['true_allow'] ); $i ++ ) {
					$option_name = $dep['true_allow'][ $i ];
					if ( ! isset( $settings[ $id ]['permissions'][ $option_name ]['users'] )
						|| ! is_array( $settings[ $id ]['permissions'][ $option_name ]['users'] ) ) {
						$settings[ $id ]['permissions'][ $option_name ]['users'] = array();
					}
					for ( $j = 0; $j < count( $users ); $j ++ ) {
						if ( in_array( $users[ $j ], $settings[ $id ]['permissions'][ $option_name ]['users'] )
							=== false ) {
							$settings[ $id ]['permissions'][ $option_name ]['users'][] = $users[ $j ];
							if ( in_array( $option_name, $updated ) === false ) {
								$updated[] = $option_name;
							}
						}
					}
					$output['options_texts'][ $option_name ] = '';
					if ( count( $settings[ $id ]['permissions'][ $option_name ]['users'] ) > 0 ) {
						$args = array(
							'orderby' => 'user_login',
							'include' => array_slice( $settings[ $id ]['permissions'][ $option_name ]['users'], 0, 2 ),
						);
						$user_query = new WP_User_Query( $args );
						foreach ( $user_query->results as $user ) {
							$output['options_texts'][ $option_name ] .= $user->data->user_login . '<br>';
						}
						$output['options_texts'][ $option_name ] .= ( ( count( $settings[ $id ]['permissions'][ $option_name ]['users'] )
							> 2 ) ? 'and '
							. ( count( $settings[ $id ]['permissions'][ $option_name ]['users'] ) - 2 )
							. ' more' : '' );
					}
				}
			}

			//Remove user to $dep
			if ( isset( $dep['false_disallow'] ) && is_array( $dep['false_disallow'] ) ) {
				//List options related to current option
				for ( $i = 0; $i < count( $dep['false_disallow'] ); $i ++ ) {
					$option_name = $dep['false_disallow'][ $i ];
					if ( isset( $settings[ $id ]['permissions'][ $option_name ]['users'] )
						&& is_array( $settings[ $id ]['permissions'][ $option_name ]['users'] ) ) {
						for ( $j = 0; $j < count( $settings[ $id ]['permissions'][ $option_name ]['users'] ); $j ++ ) {
							if ( in_array( $settings[ $id ]['permissions'][ $option_name ]['users'][ $j ], $users )
								=== false ) {
								unset( $settings[ $id ]['permissions'][ $option_name ]['users'][ $j ] );
								if ( in_array( $option_name, $updated ) === false ) {
									$updated[] = $option_name;
								}
							}
						}
						$output['options_texts'][ $option_name ] = '';
						if ( count( $settings[ $id ]['permissions'][ $option_name ]['users'] ) > 0 ) {
							$args = array(
								'orderby' => 'user_login',
								'include' => array_slice( $settings[ $id ]['permissions'][ $option_name ]['users'], 0, 2 ),
							);
							$user_query = new WP_User_Query( $args );
							foreach ( $user_query->results as $user ) {
								$output['options_texts'][ $option_name ] .= $user->data->user_login . '<br>';
							}
							$output['options_texts'][ $option_name ] .= ( ( count( $settings[ $id ]['permissions'][ $option_name ]['users'] )
								> 2 ) ? 'and ' . ( count( $settings[ $id ]['permissions'][ $option_name ]['users'] )
									- 2 ) . ' more' : '' );
						}
					}

				}
			}


		}
		if ( count( $updated ) > 0 ) {
			$output['updated_sections'] = "Since you updated '$option', '"
				. implode( "','", $updated )
				. "' has also been updated.";
		}

		if ( in_array( $groupid, self::get_third_party_exception_array() ) !== false ) {
			$wpcf_access->settings->third_party[ $groupid ] = $settings;
		} else {
			$wpcf_access->settings->$groupid = $settings;
		}

		$access_settings->updateAccessSettings( $wpcf_access->settings );

		wp_send_json_success( $output );

	}


	/**
	 * Retrun an array of third party settings exceptions
	 *
	 * @return array
	 * @since 2.4
	 */
	private function get_third_party_exception_array() {
		return array( '__FIELDS', '__CRED_CRED', '__CRED_CRED_USER', '__USERMETA_FIELDS', '__CRED_CRED_REL' );
	}
}
