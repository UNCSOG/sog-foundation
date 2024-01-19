<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Update one or more properties of a Content Template.
 *
 * Following POST parameters are expected:
 * - id: Content Template ID
 * - wpnonce: A valid wpv_ct_{$id}_update_properties_by_{$user_id} nonce.
 * - properties: An array of objects (that will be decoded from JSON to associative arrays),
 *     each of them representing a property with "name" and "value" keys.
 *
 * A WPV_Content_Template object will be instantiated and this function will try to update values of
 * it's properties as defined in the "properties" POST parameter. The "update transaction" mechansim
 * is used for this purpose (see WPV_Post_Object_Wrapper::update_transaction() for details
 * about update logic).
 *
 * It always returns JSON object with a 'success' key. If an "generic" error (like invalid
 * nonce or some invalid arguments) happens, success will be false. Otherwise, if success is true,
 * there will be a 'data' key containing:
 * - 'all_succeeded' - boolean
 * - 'results', an object with property names as keys and booleans indicating that particular
 *   property has been saved successfully (which depends on the logic in WPV_Content_Template),
 *   optionally also containing a "message" property that should be displayed to the user.
 *
 * @since 1.9
 * @since 2.7.0 Moved to this independent AJAX handler.
 */
class WPV_Ajax_Handler_Update_Content_Template_Properties extends Toolset_Ajax_Handler_Abstract {
	/**
	 * Main AJAX handler method that handles this AJAX call.
	 *
	 * @param array $arguments
	 */
	public function process_call( $arguments = null ) {
		$ajax_manager = $this->get_ajax_manager();

		$ajax_manager->ajax_begin(
			array(
				'nonce' => WPV_Ajax::CALLBACK_UPDATE_CONTENT_TEMPLATE_PROPERTIES,
				'public' => false,
				'capability_needed' => EDIT_VIEWS,
			)
		);

		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' ),
				),
				false
			);

			return;
		}

		$ct_id = (int) toolset_getpost( 'id', 0 );

		$ct = WPV_Content_Template::get_instance( $ct_id );

		if ( ! $ct instanceof WPV_Content_Template ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'error',
					'message' => toolset_ensarr( $ct ) && isset( $ct['error'] ) ?
						$ct['error'] :
						__( 'Invalid Content Template', 'wpv-views' ),
				),
				false
			);

			return;
		}

		$properties = toolset_getpost( 'properties', array() );
		if ( empty( $properties ) ) {
			$ajax_manager->ajax_finish(
				array(
					'type' => 'input',
					/* translators: Message for invalid arguments when updating the Content Template properties. */
					'message' => sprintf( __( 'Invalid arguments (%s)', 'wpv-views' ), $properties ),
				),
				false
			);

			return;
		}

		// Try to save data as a transaction (all at once or nothing).
		// Refer to WPV_Post_Object_Wrapper::update_transaction() for details.
		$transaction_data = array();
		foreach ( $properties as $property ) {
			// Missing property value defaults to empty array because of jQuery.ajax issues with empty arrays.
			// If it's invalid value for the property, it should be rejected during validation - no harm done here.
			$property_value = toolset_getarr( $property, 'value', array() );

			$transaction_data[ $property['name'] ] = $property_value;
		}

		// Run the update transaction.
		// Second parameter is false mostly because vm.processTitleSectionUpdateResults in JS.
		$transaction_result = $ct->update_transaction( $transaction_data, false );

		// Parse the translation result into per-property results that will be returned.
		$results = array();
		foreach ( $properties as $property ) {

			$propery_name = $property['name'];
			$result = array( 'name' => $propery_name );

			if (
				true === $transaction_result['success'] ||
				(
					true === $transaction_result['partial']
					&& in_array( $propery_name, $transaction_result['updated_properties'], true )
				)
			) {
				// Transaction success == all was updated without errors.
				$result['success'] = true;

				if ( 'assigned_single_post_types' === $propery_name ) {
					$property_value = toolset_getarr( $property, 'value', array() );
					$dissident_posts = $ct->dissident_posts;
					$result['dissidentPosts'] = $this->get_dissident_posts_for_assigned_post_types( $property_value, $dissident_posts );
				}
			} else {
				// Failure, for one or the other reason. Look for an optional error message.
				$result['success'] = false;
				if ( array_key_exists( $propery_name, $transaction_result['error_messages'] ) ) {
					$error = $transaction_result['error_messages'][ $propery_name ];
					$result['message'] = $error['message'];
					$result['code'] = $error['code'];
				}
			}

			$results[] = $result;
		}

		// Report success (because the AJAX call succeeded in general) and attach information
		// about each property update.
		$ajax_manager->ajax_finish(
			array(
				'results' => $results,
			),
			true
		);
	}

	/**
	 * Gets the dissident posts for the assigned_post_types.
	 *
	 * @param array $assigned_post_types The post types the Content Template is assigned to.
	 * @param array $ct_dissident_posts  The dissident posts for the post types this Content Template is assigned to,
	 *                                   grouped by post type.
	 *
	 * @return array
	 */
	private function get_dissident_posts_for_assigned_post_types( $assigned_post_types, $ct_dissident_posts ) {
		$result = array();
		if (
			! is_array( $ct_dissident_posts ) ||
			count( $ct_dissident_posts ) <= 0
		) {
			return $result;
		}

		foreach ( $assigned_post_types as $post_type ) {
			if ( array_key_exists( $post_type, $ct_dissident_posts ) ) {
				$result[ $post_type ] = $ct_dissident_posts[ $post_type ];
			}
		}

		return $result;
	}
}
