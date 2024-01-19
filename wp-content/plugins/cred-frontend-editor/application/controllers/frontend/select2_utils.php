<?php

/**
 * Select2 Utils class that contains common methods used by Select2 Manager in both Field and Generic Field contexts
 *
 * @since 1.9.3
 */
class CRED_Select2_Utils {

	private static $instance;
	private $cred_field_utils;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * CRED_Select2_Utils constructor.
	 *
	 * @param CRED_Field_Utils|null $cred_field_utils
	 */
	public function __construct( CRED_Field_Utils $cred_field_utils = null ) {
		$this->cred_field_utils = ( null == $cred_field_utils ) ? CRED_Field_Utils::get_instance() : $cred_field_utils;
	}

	// Note: This is somehow related to cred-1345, there are several commented-out usages with a mention about a future release.
	// Perhaps this is not needed anymore.
	//
	//	/**
	//	 * Tries to register a post select field as select2
	//	 * checking if is on frontend and the number of elements is valid
	//	 *
	//	 * @param $html_form_id
	//	 * @param string $field_name
	//	 * @param array $field
	//	 * @param string|null $use_select2
	//	 */
	//	public function try_register_field_as_select2( $html_form_id, $field_name, $field, $use_select2 ) {
	//		$cred_select2_manager = CRED_Frontend_Select2_Manager::get_instance();
	//		if ( ! $cred_select2_manager->is_valid_field_type_for_select2( $field['type'] ) ) {
	//			return;
	//		}
	//
	//		//shortcode never will not register the field
	//		if ( isset( $use_select2 )
	//			&& $use_select2 == $cred_select2_manager::SELECT2_SHORTCODE_NEVER ) {
	//			return;
	//		}
	//
	//		$options_count = ( isset($field['data']) && isset( $field['data']['options'] ) ) ? count( $field['data']['options'] ) : 0;
	//
	//		/**
	//		 * cred_field_minimal_options_count_for_select2_transformation
	//		 *
	//		 * Number of minimal select/multiselect field options beyond which we will have select2 enabled on the current select field
	//		 *
	//		 * @since 1.9.3
	//		 *
	//		 * @param int $options_count
	//		 */
	//		$field_minimal_options_count = (int) apply_filters( 'cred_field_minimal_options_count_for_select2_transformation', 15 );
	//
	//		$has_many_options = $options_count > $field_minimal_options_count;
	//		$can_use_select2 = $cred_select2_manager->use_select2( $use_select2, $has_many_options );
	//		$is_generic_field = isset( $field['cred_generic'] ) && $field['cred_generic'] == 1;
	//
	//		$options = array();
	//		if ( $options_count > 0 ) {
	//			//I need just the options for select2 without default elements
	//			foreach ( $field['data']['options'] as $option_key => $option_value ) {
	//				if ( $option_key == 'default' ) {
	//					continue;
	//				}
	//				if ( $is_generic_field
	//					&& ( $option_key == 'display_value'
	//						|| empty( $option_value ) ) ) {
	//					unset( $field['data']['options']['display_value'] );
	//				}
	//				if ( ! $is_generic_field
	//					&& $option_value == 'no-default' ) {
	//					continue;
	//				}
	//				$options[] = $option_value;
	//			}
	//		}
	//
	//		$select2_args = array( 'parameter' => $options, 'field_settings' => $field );
	//
	//		if ( $has_many_options || $can_use_select2 ) {
	//			$cred_select2_manager->register_field_to_select2_list( $html_form_id, $field_name, $select2_args );
	//		}
	//
	//	}

	/**
	 * Tries to register a m2m relationship parent select field as select2
	 * checking if is on frontend and the number of elements is valid
	 *
	 * @param string $html_form_id
	 * @param string $field_name
	 * @param array $field
	 * @param string $max_results
	 * @param string|null $use_select2
	 *
	 * @return array
	 */
	public function try_register_relationship_parent_as_select2( $html_form_id, $field_name, $field, $max_results, $use_select2 ) {
		$cred_select2_manager = CRED_Frontend_Select2_Manager::get_instance();

		$is_real_admin = ( is_admin() && ! cred_is_ajax_call() );

		$potential_parents = array();
		if ( ! $cred_select2_manager->is_valid_field_type_for_select2( $field['type'] ) ) {
			return $potential_parents;
		}

		//If is_admin i need to get potential parents for WPML translations
		if ( $is_real_admin
			|| (
				isset( $use_select2 )
				&& $use_select2 == $cred_select2_manager::SELECT2_SHORTCODE_NEVER
			)
		) {
			//I need all potentials parents when i am in admin for translation elaborations
			$potential_parents = $this->cred_field_utils->get_potential_parents( $field['data']['post_type'], $field['slug'], $field['wpml_context'] );
		} else {
			$select2_args = array( 'action' => $cred_select2_manager::SELECT2_RELATIONSHIP_PARENTS, 'parameter' => $field['data']['post_type'], 'field_settings' => $field );
			$cred_select2_manager->register_field_to_select2_list( $html_form_id, $field_name, $select2_args );
		}

		return $potential_parents;
	}

	/**
	 * Tries to register a parent select field as select2
	 * checking if is on frontend and the number of elements is valid
	 *
	 * @param string $html_form_id
	 * @param string $field_name
	 * @param array $field
	 * @param string $max_results
	 * @param string|null $use_select2
	 * @param array $forced_args
	 *
	 * @return array
	 */
	public function try_register_parent_as_select2( $html_form_id, $field_name, $field, $max_results, $use_select2, $forced_args = array() ) {
		$cred_select2_manager = CRED_Frontend_Select2_Manager::get_instance();

		$is_real_admin = ( is_admin() && ! cred_is_ajax_call() );

		$potential_parents = array();
		if ( ! $cred_select2_manager->is_valid_field_type_for_select2( $field['type'] ) ) {
			return $potential_parents;
		}

		//If is_admin i need to get potential parents for WPML translations
		if ( $is_real_admin
			|| (
				isset( $use_select2 )
				&& $use_select2 == $cred_select2_manager::SELECT2_SHORTCODE_NEVER
			)
		) {
			//I need all potentials parents when i am in admin for translation elaborations
			$potential_parents = $this->cred_field_utils->get_potential_parents( $field['data']['post_type'], $field['slug'], $field['wpml_context'], -1, '', $forced_args );
		} else {
			/**
			 * cred_parent_minimal_options_count_for_select2_transformation
			 *
			 * Number of minimal select parent field options beyond which we will have select2 enabled on the current select field
			 *
			 * @since 1.9.3
			 *
			 * @param int $options_count
			 */
			$parent_minimal_options_count = (int) apply_filters( 'cred_parent_minimal_options_count_for_select2_transformation', 15 );

			$parents_count = $this->cred_field_utils->get_count_posts( $field['data']['post_type'], $parent_minimal_options_count + 1 );

			$has_many_options = $parents_count > $parent_minimal_options_count;

			$can_use_select2 = $cred_select2_manager->use_select2( $use_select2, $has_many_options );

			//if there are not many options is not needed the select2
			if ( ! $has_many_options ) {
				//I need all potentials parents when i dont have select2 field
				$potential_parents = $this->cred_field_utils->get_potential_parents( $field['data']['post_type'], $field['slug'], $field['wpml_context'], -1, '', $forced_args );

				if ( $can_use_select2 ) {
					$select2_args = array( 'parameter' => $potential_parents, 'field_settings' => $field );
					$cred_select2_manager->register_field_to_select2_list( $html_form_id, $field_name, $select2_args );
				}
			} else {
				$select2_args = array( 'action' => $cred_select2_manager::SELECT2_PARENTS, 'parameter' => $field['data']['post_type'], 'field_settings' => $field );
				$cred_select2_manager->register_field_to_select2_list( $html_form_id, $field_name, $select2_args );
			}
		}

		return $potential_parents;
	}

	/**
	 * Sets the current value for the select2 registered field
	 * Operation that needs to be called after the registration of select2 ajax field component
	 *
	 * @param string $html_form_id
	 * @param string $field_name
	 * @param string $value
	 * @param string $post_type
	 */
	public function set_current_value_to_registered_select2_field( $html_form_id, $field_name, $value, $post_type ) {
		$cred_select2_manager = CRED_Frontend_Select2_Manager::get_instance();
		$cred_select2_manager->set_current_value_to_registered_select2_field( $html_form_id, $field_name, $value, $post_type );
	}

}
