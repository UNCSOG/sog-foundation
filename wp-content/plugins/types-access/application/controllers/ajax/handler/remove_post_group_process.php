<?php
/**
 * Class Access_Ajax_Handler_Remove_Post_Group_Process
 * Remove Post Group process
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Remove_Post_Group_Process extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Remove_Post_Group_Process constructor.
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
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$settings_access = $access_settings->get_types_settings( true, true );

		$group_id = sanitize_text_field( $_POST['group_id'] );
		if ( isset( $settings_access[ $group_id ] ) ) {
			unset( $settings_access[ $group_id ] );
		}
		$args = array(
			'posts_per_page' => - 1,
			'offset' => 0,
			'meta_key' => '_wpcf_access_group',
			'meta_value' => $group_id,
			'post_type' => 'any',
			'fields' => 'ids',
		);

		$query = new WP_Query( $args );

		if ( count( $query->posts ) > 0 ) {
			foreach ( $query->posts as $post ) {
				delete_post_meta( $post, '_wpcf_access_group' );
			}
		}

		$access_settings->updateAccessTypes( $settings_access );
		wp_send_json_success( true );
	}
}
