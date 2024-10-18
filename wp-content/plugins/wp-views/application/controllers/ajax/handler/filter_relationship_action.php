<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Handle actions with relationship filters in the view edit page.
 *
 * @since m2m
 */
class WPV_Ajax_Handler_Filter_Relationship_Action extends Toolset_Ajax_Handler_Abstract {

	/**
	 * WP Nonce.
	 *
	 * @var string
	 * @since m2m
	 */
	const NONCE = 'wpv_view_query_type_nonce';


	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin( array(
			'nonce' => WPV_Ajax::LEGACY_VIEW_QUERY_TYPE_NONCE,
			'capability_needed' => EDIT_VIEWS,
		) );

		do_action( 'toolset_do_m2m_full_init' );

		// Default message.
		$result = new Toolset_Result( false, __( 'Something went wrong', 'wpv-views' ) );
		$action = sanitize_text_field( toolset_getpost( 'wpv_action' ) );

		switch ( $action ) {
			case 'update_post_type_list':
				$result = $this->update_post_type_list();
				break;
			default:
				$result = new Toolset_Result( false, __( 'Wrong filter action', 'wpv-views' ) );
		}

		if ( is_wp_error( $result ) ) {
			$this->ajax_finish(
				$result->get_error_message(),
				false
			);
		} else {
			$this->ajax_finish(
				$result,
				true
			);
		}
	}


	/**
	 * Returns the combo with the relationships related to the post types
	 *
	 * @return array
	 * @since m2m
	 */
	private function update_post_type_list() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			return new WP_Error( 'permission', __( 'You do not have permissions for that.', 'wpv-views' ) );
		}
		if (
			! isset( $_POST["id"] )
			|| ! is_numeric( $_POST["id"] )
			|| intval( $_POST['id'] ) < 1
		) {
			return new WP_Error( 'arguments', __( 'Wrong or missing ID.', 'wpv-views' ) );
		}
		if ( ! isset( $_POST["post_type_slugs"] ) ) {
			$_POST["post_type_slugs"] = array( 'any' );
		}
		$view_array = get_post_meta( $_POST["id"],'_wpv_settings', true );
		if (
			! isset( $view_array['query_type'] )
			|| ! isset( $view_array['query_type'][0] )
			|| 'posts' !== $view_array['query_type'][0]
		) {
			return new WP_Error( 'relationship', __( 'Relationship filters are only valid for posts views.', 'wpv-views' ) );
		}

		$filter_manager = WPV_Filter_Manager::get_instance();
		$filter = $filter_manager->get_filter( Toolset_Element_Domain::POSTS, 'relationship' );

		ob_start();
		$filter->get_gui()->render_relationships_combo_by_post_type( $_POST["post_type_slugs"] );
		$select = ob_get_clean();

		$data = array(
			'id' => $_POST["id"],
			'relationship_combo' => $select,
			'message' => __( 'Data uploaded', 'wpv-views' )
		);
		return $data;
	}

}
