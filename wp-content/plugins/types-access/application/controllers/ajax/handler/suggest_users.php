<?php
/**
 * Class Access_Ajax_Handler_Suggest_Users
 * Select 2 suggest users
 *
 * @since 2.7
 */
class Access_Ajax_Handler_Suggest_Users extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Suggest_Users constructor.
	 *
	 * @param \OTGS\Toolset\Access\Ajax $access_ajax
	 */
	public function __construct( \OTGS\Toolset\Access\Ajax $access_ajax ) {//phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		parent::__construct( $access_ajax );
	}


	/**
	 * @param array $arguments
	 *
	 * @return array
	 */
	public function process_call( $arguments ) {

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );
		$out = array();
		global $wpdb;

		$total = 0;
		$q = ( isset( $_POST['q'] ) && ! empty( $_POST['q'] ) ? '%' . $wpdb->esc_like( $_POST['q'] ) .'%' : '' );//phpcs:ignore
		$sql = $wpdb->prepare( "SELECT ID, display_name, user_login FROM $wpdb->users WHERE user_nicename LIKE %s OR user_login LIKE %s OR display_name LIKE %s  LIMIT 10", $q, $q, $q );

		$found = $wpdb->get_results( $sql );//phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $found ) ) {
			foreach ( $found as $user ) {
				$total ++;
				$out['items'][] = array(
					'id' => esc_js( $user->ID ),
					'name' => esc_js( $user->user_login ),
				);
			}
		}

		$out['total_count'] = $total;
		$out['incomplete_results'] = 'false';

		return wp_send_json_success( $out );
	}
}
