<?php
/**
 * Class Access_Ajax_Handler_Specific_Usetrs_Form
 * Load specific users form
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Specific_Users_Form extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Specific_Usetrs_Form constructor.
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

		global $wpcf_access;
		if ( ! isset( $_POST['id'] ) || ! isset( $_POST['groupid'] ) || ! isset( $_POST['option_name'] ) ) {
			return;
		}
		$id = $_POST['id'];
		$groupid = $_POST['groupid'];
		$option = $_POST['option_name'];
		$out = '<form method="" id="wpcf-access-set_error_page">';
		$out .= '
			<p>
				<label for="toolset-access-user-suggest-field">' . __( 'Search user', 'wpcf-access' ) . ':</label>
				<select id="toolset-access-user-suggest-field"></select>
			</p>';

		$out .= '<div class="js-otgs-access-posts-listing otgs-access-posts-listing otgs-access-users-listing">';

		$settings = '';
		if ( in_array( $groupid, $this->get_third_party_exception_array() ) !== false ) {
			if ( isset( $wpcf_access->settings->third_party[ $groupid ] ) ) {
				$settings = $wpcf_access->settings->third_party[ $groupid ];
			}
		} else {
			$settings = $wpcf_access->settings->$groupid;
		}

		if ( ! empty( $settings )
			&& isset( $settings[ $id ]['permissions'][ $option ]['users'] )
			&& count( $settings[ $id ]['permissions'][ $option ]['users'] ) > 0 ) {
			$users = $settings[ $id ]['permissions'][ $option ]['users'];
			$args = array(
				'orderby' => 'user_login',
				'include' => $users,
			);
			$user_query = new WP_User_Query( $args );
			foreach ( $user_query->results as $user ) {
				$out .= '<div class="js-assigned-access-item js-assigned-access-item-'
					. esc_attr( $user->ID )
					. '" data-newitem="1" data-itemid="'
					. esc_attr( $user->ID )
					. '">'
					.
					$user->data->user_login
					. ' <a href="" class="js-wpcf-unassign-access-item" data-id="'
					. esc_attr( $user->ID )
					. '"><i class="fa fa-times"></i></a></div>';
			};
		}

		$out .= '</div>';


		$out .= '</div>';
		$out .= '</form>';

		wp_send_json_success( $out );

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
