<?php

/**
 * Wrapper Class used by Toolset Forms in order to get fields data info related to Types
 * 
 * Currently used mostrly, but not only, by CRED_Fields_Model::getFields, which is used
 * later to print the form and validate it on submission, among other things.
 *
 * All the new mechanism to get form fields for the new editor should be enforced instead,
 * step by step.
 *
 * @since 1.9.5
 */
class CRED_Fields_Types_Utils {

	/**
	 * Function to get parents data fields created in .
	 * It will create _wpcf_belongs_ and post_relationship structure array used in Toolset Forms
	 *
	 * @param $post_type
	 *
	 * @return array
	 */
	public function get_parent_fields( $post_type ) {
		if ( ! defined( 'WPCF_VERSION' ) ) {
			// Types is not actived
			return array();
		};

		$wpcf_custom_types = get_option( 'wpcf-custom-types' );
		if ( empty( $wpcf_custom_types ) ) {
			// no cpts registered by types
			return array();
		}

		$is_types_cpt = array_key_exists( $post_type, $wpcf_custom_types );

		$parents = array();
		if (
			$is_types_cpt
			&& array_key_exists( 'post_relationship', $wpcf_custom_types[ $post_type ] )
			&& array_key_exists( 'belongs', $wpcf_custom_types[ $post_type ][ 'post_relationship' ] )
		) {
			// get parents defined via 'belongs' relationship
			foreach ( $wpcf_custom_types[ $post_type ][ 'post_relationship' ][ 'belongs' ] as $ptype => $belong ) {
				if ( $belong ) {
					$_slug = "_wpcf_belongs_{$ptype}_id";
					$parents[ $_slug ] = $this->get_cred_post_relationship_array( $_slug, $ptype );
				}
			}
		}

		// get parents defined via 'has' relationship (reverse)
		foreach ( $wpcf_custom_types as $ptype => $pdata ) {
			if (
				isset( $pdata[ 'post_relationship' ] )
				&& isset( $pdata[ 'post_relationship' ][ 'has' ] )
				&& isset( $pdata[ 'post_relationship' ][ 'has' ][ $post_type ] )
				&& $pdata[ 'post_relationship' ][ 'has' ][ $post_type ]
			) {
				$_slug = "_wpcf_belongs_{$ptype}_id";
				$parents[ $_slug ] = $this->get_cred_post_relationship_array( $_slug, $ptype );
			}
		}

		return $parents;
	}
	
	public function get_hierarchical_parent_fields( $post_type_object ) {
		$parents = array();
		
		if (
			post_type_supports( $post_type_object->name, 'page-attributes' )
			&& is_post_type_hierarchical( $post_type_object->name )
		) {
			$_slug = 'post_parent';
			$parents[ $_slug ] = array(
				'is_parent' => true,
				'data' => array(
					'post_type' => $post_type_object->name,
					'repetitive' => false,
					'options' => array(),
				),
				'id' => $_slug,
				'slug' => $_slug,
				'name' => esc_js( sprintf( __( '%s Parent', 'wp-cred' ), $post_type_object->labels->singular_name ) ),
				'type' => 'select',
				'description' => esc_js( sprintf( __( 'Set the %s Parent', 'wp-cred' ), $post_type_object->labels->singular_name ) ),
			);
		}

		return $parents;
	}

	/**
	 * Get Cred post relationship as array
	 *
	 * @param $wpcf_meta_key
	 * @param $post_type_slug
	 *
	 * @return array
	 */
	private function get_cred_post_relationship_array( $wpcf_meta_key, $post_type_slug ) {
		return array(
			'is_parent' => true,
			'plugin_type' => 'types',
			'data' => array(
				'post_type' => $post_type_slug,
				'repetitive' => false,
				'options' => array(),
			),
			'id' => $wpcf_meta_key,
			'slug' => $wpcf_meta_key,
			'name' => esc_js( sprintf( __( '%s Parent', 'wp-cred' ), $post_type_slug ) ),
			'type' => 'select',
			'description' => esc_js( sprintf( __( 'Set the %s Parent', 'wp-cred' ), $post_type_slug ) ),
		);
	}

	/**
	 * Get List of post fields
	 *
	 * @param $add_default
	 * @param $localized_message_callback
	 * @param $post_type_object
	 *
	 * @return array
	 */
	public function get_post_fields( $add_default, $localized_message_callback, $post_type_object ) {
		$post_fields = array();
		
		if ( ! $add_default ) {
			return $post_fields;
		}


		if ( $localized_message_callback ) {
			$message = call_user_func( $localized_message_callback, 'field_required' );
		} else {
			$message = __( 'This field is required', 'wp-cred' );
		}
		$post_fields[ 'post_title' ] = array(
			'post_type' => $post_type_object->name,
			'post_labels' => $post_type_object->labels,
			'id' => 'post_title',
			'wp_default' => true,
			'slug' => 'post_title',
			'type' => 'textfield',
			'name' => esc_js( sprintf( __( '%s Title', 'wp-cred' ), $post_type_object->labels->singular_name ) ),
			'description' => esc_js( sprintf( __( 'Title of %s (default)', 'wp-cred' ), $post_type_object->labels->singular_name ) ),
			'data' => array(
				'repetitive' => 0,
				'validate' => array( 'required' => array( 'active' => 1, 'value' => true, 'message' => $message ) ),
				'conditional_display' => array(),
				'disabled_by_type' => 0,
			),
		);
		$post_fields[ 'post_content' ] = array(
			'post_type' => $post_type_object->name,
			'post_labels' => $post_type_object->labels,
			'id' => 'post_content',
			'wp_default' => true,
			'slug' => 'post_content',
			'type' => 'wysiwyg',
			'name' => esc_js( sprintf( __( '%s Content', 'wp-cred' ), $post_type_object->labels->singular_name ) ),
			'description' => esc_js( sprintf( __( 'Content of %s (default)', 'wp-cred' ), $post_type_object->labels->singular_name ) ),
			'data' => array(/* 'repetitive' => 0, 'validate' => array ( 'required' => array ( 'active' => 1, 'value' => true, 'message' => __('This field is required','wp-cred') ) ), 'conditional_display' => array ( ), 'disabled_by_type' => 0 */ ),
			'supports' => post_type_supports( $post_type_object->name, 'editor' ) ? true : false
		);
		$post_fields[ 'post_excerpt' ] = array(
			'post_type' => $post_type_object->name,
			'post_labels' => $post_type_object->labels,
			'id' => 'post_excerpt',
			'wp_default' => true,
			'slug' => 'post_excerpt',
			'type' => 'textarea',
			'name' => esc_js( sprintf( __( '%s Excerpt', 'wp-cred' ), $post_type_object->labels->singular_name ) ),
			'description' => esc_js( sprintf( __( 'Excerpt of %s (default)', 'wp-cred' ), $post_type_object->labels->singular_name ) ),
			'data' => array(/* 'repetitive' => 0, 'validate' => array ( 'required' => array ( 'active' => 1, 'value' => true, 'message' => __('This field is required','wp-cred') ) ), 'conditional_display' => array ( ), 'disabled_by_type' => 0 */ ),
			'supports' => post_type_supports( $post_type_object->name, 'excerpt' ) ? true : false
		);

		return $post_fields;
	}

	/**
	 * Get Extra fields like _featured_image and recaptcha
	 *
	 * @param $post_type_object
	 *
	 * @return array
	 */
	public function get_extra_fields( $post_type_object ) {
		$extra_fields = array();
		$extra_fields[ 'recaptcha' ] = array(
			'id' => 're_captcha',
			'slug' => 'recaptcha',
			'name' => esc_js( __( 'reCaptcha', 'wp-cred' ) ),
			'type' => 'recaptcha',
			'cred_builtin' => true,
			'description' => esc_js( __( 'Adds Image Captcha to your forms to prevent automatic submision by bots', 'wp-cred' ) ),
		);
		$setts = CRED_Loader::get( 'MODEL/Settings' )->getSettings();
		if (
			! isset( $setts[ 'recaptcha' ][ 'public_key' ] )
			|| ! isset( $setts[ 'recaptcha' ][ 'private_key' ] )
			|| empty( $setts[ 'recaptcha' ][ 'public_key' ] )
			|| empty( $setts[ 'recaptcha' ][ 'private_key' ] ) ) {
			// no keys set for API
			$extra_fields[ 'recaptcha' ][ 'disabled' ] = true;
			$extra_fields[ 'recaptcha' ][ 'disabled_reason' ] = sprintf( '<a href="%s" target="_blank">%s</a> %s', CRED_CRED::$settingsPage, __( 'Get and Enter your API keys', 'wp-cred' ), esc_js( __( 'to use the Captcha field.', 'wp-cred' ) ) );
		}

		// featured image field
		$extra_fields[ '_featured_image' ] = array(
			'id' => '_featured_image',
			'slug' => '_featured_image',
			'name' => esc_js( __( 'Featured Image', 'wp-cred' ) ),
			'type' => 'image',
			'cred_builtin' => true,
			'description' => esc_js( sprintf( __( 'Set %s Featured Image', 'wp-cred' ), $post_type_object->labels->singular_name ) ),
			'supports' => post_type_supports( $post_type_object->name, 'thumbnail' ) ? true : false
		);

		return $extra_fields;
	}

	/**
	 * List of types group and group_conditions
	 *
	 * @param $post_type_object
	 * @param $fields
	 * @param $groups
	 * @param $groups_conditions
	 */
	public function set_fields_groups_and_group_conditions( $post_type_object, &$fields, &$groups, &$groups_conditions ) {
		$toolset_types_condition = new Toolset_Condition_Plugin_Types_Active();
		$is_toolset_types_available = $toolset_types_condition->is_met();
		
		if ( ! $is_toolset_types_available ) {
			return;
		}
		
		$groups_by_post_type = array();
		$post_fields_group_factory = Toolset_Field_Group_Post_Factory::get_instance();
		
		if (
			apply_filters( 'toolset_is_m2m_enabled', false ) 
			&& property_exists( $post_type_object, Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP )
			&& $post_type_object->{Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP}
		) {
			do_action( 'toolset_do_m2m_full_init' );
			$post_fields_group_factory = Toolset_Field_Group_Post_Factory::get_instance();
			$groups_for_rfg = $post_fields_group_factory->query_groups( array(
				'purpose' => Toolset_Field_Group_Post::PURPOSE_FOR_REPEATING_FIELD_GROUP,
				'post_status' => 'hidden'
			) );
			foreach ( $groups_for_rfg as $group_for_rfg ) {
				if ( 
					$group_for_rfg instanceof Toolset_Field_Group_Post 
					&& $post_type_object->name == get_post_meta( $group_for_rfg->get_id(), Types_Field_Group_Repeatable::OPTION_NAME_LINKED_POST_TYPE, true )
				) {
					$groups_by_post_type[] = $group_for_rfg;
				}
			}
		} else {
			$groups_by_post_type = $post_fields_group_factory->get_groups_by_post_type( $post_type_object->name );
		}
		
		if ( empty( $groups_by_post_type ) ) {
			return;
		}
		
		$group_ids = array();
		
		foreach ( $groups_by_post_type as $field_group ) {
			$fields_in_group = $field_group->get_field_definitions();
			$fields_in_group_slugs = array();
			foreach ( $fields_in_group as $field_in_group ) {
				$field_definition = $field_in_group->get_definition_array();
				$fields_in_group_slugs[] = $field_definition['slug'];
				$fields[ $field_definition['slug'] ] = $field_definition;
				$fields[ $field_definition['slug'] ][ 'post_labels' ] = $post_type_object->labels;
				$fields[ $field_definition['slug'] ][ 'post_type' ] = $post_type_object->name;
				$fields[ $field_definition['slug'] ][ 'plugin_type' ] = 'types';
				
				$fields[ $field_definition['slug'] ][ 'plugin_type_prefix' ] = 
					'wpcf-' === substr( $field_definition['meta_key'], 0, 5 ) 
					? 'wpcf-' 
					: '';
			}
			
			if ( ! empty( $fields_in_group_slugs ) ) {
				$groups[ $field_group->get_name() ] = implode( ',', $fields_in_group_slugs );
				$group_ids[] = $field_group->get_id();
			}
		}
		
		if ( empty( $group_ids ) ) {
			return;
		}
		
		// For conditional groups data we do need to query the database, there is no TC API for this yet
		global $wpdb;
		$group_ids_list = implode( ',', $group_ids );
		$group_ids_count = count( $group_ids );
		$sql_conditional = "SELECT P.post_title, M.meta_value FROM {$wpdb->posts} As P, {$wpdb->postmeta} As M
			WHERE P.ID IN ({$group_ids_list})
			AND M.post_id = P.ID
			AND M.meta_key = '_wpcf_conditional_display'
			AND NOT (M.meta_value IS NULL)
			AND post_status = 'publish'
			ORDER BY ID ASC 
			LIMIT {$group_ids_count}";
		$group_fields_conditional = $wpdb->get_results( $sql_conditional );
		
		foreach ( $group_fields_conditional as $group_conditional ) {
			$conditional_data = maybe_unserialize( $group_conditional->meta_value );
			
			if ( 
				isset( $condition_data[ 'custom' ] ) 
				&& isset( $condition_data[ 'custom_use' ] ) 
				&& $condition_data[ 'custom_use' ] == 1 
			) {
				$groups_conditions[ $group_conditional->post_title ] = $condition_data[ 'custom' ];
			} elseif ( 
				isset( $condition_data[ 'conditions' ] ) 
				&& is_array( $condition_data[ 'conditions' ] ) 
				&& isset( $condition_data[ 'relation' ] ) 
			) {
				$conditional_string_parts = array();
				foreach ( $condition_data[ 'conditions' ] as $cond ) {
					$conditional_string_parts[] = '($(' . $cond[ 'field' ] . ') ' . $cond[ 'operation' ] . ' \'' . $cond[ 'value' ] . '\')';
				}
				$conditional_string = implode( ' ' . $condition_data[ 'relation' ] . ' ', $conditional_string_parts );
				$groups_conditions[ $group_conditional->post_title ] = $conditional_string;
			}
		}
	}

	/**
	 * List of custom fields by groups
	 *
	 * @param $isCredCustomPost
	 * @param $fields
	 * @param $credCustomFields
	 * @param $groups
	 */
	public function add_cred_custom_fields_in_groups( $isCredCustomPost, $credCustomFields, &$fields, &$groups ) {
		if ( $isCredCustomPost ) {
			$fields = array_merge( $fields, $credCustomFields );
			foreach ( $credCustomFields as $key => $field_data ) {
				if ( ! isset( $field_data[ '_cred_ignore' ] )
					|| ! $field_data[ '_cred_ignore' ] ) {
					$groups[ '_CRED_Custom_Fields_' ] = implode( ',', array_keys( $credCustomFields ) );
					// Has at least one field not ignored from scaffold
					break;
				}
			}
		}
	}

	/**
	 * List of taxonomies
	 *
	 * @param $post_type_object
	 *
	 * @return array
	 */
	public function get_taxonomies( $post_type_object ) {
		$all_taxonomies = get_taxonomies( array(
			'public' => true,
			'_builtin' => false,
		), 'objects', 'or' );
		$taxonomies = array();
		foreach ( $all_taxonomies as $tax ) {
			$taxonomy = &$tax;
			//$taxonomy = get_taxonomy($tax);
			if ( ! in_array( $post_type_object->name, $taxonomy->object_type ) ) {
				continue;
			}
			if ( in_array( $taxonomy->name, array( 'post_format' ) ) ) {
				continue;
			}

			$key = $taxonomy->name;
			$taxonomies[ $key ] = array(
				'type' => ( $taxonomy->hierarchical ) ? 'taxonomy_hierarchical' : 'taxonomy_plain',
				'label' => $taxonomy->label,
				'name' => $taxonomy->name,
				'hierarchical' => $taxonomy->hierarchical,
			);
			if ( $taxonomy->hierarchical ) {
				$taxonomies[ $key ][ 'all' ] = $this->buildTerms( get_terms( $taxonomy->name, array(
					'hide_empty' => 0,
					'fields' => 'all',
				) ) );
			} else {
				$taxonomies[ $key ][ 'most_popular' ] = $this->buildTerms( get_terms( $taxonomy->name, array(
					'number' => 8,
					'order_by' => 'count',
					'fields' => 'all',
				) ) );
			}
		}
		unset( $all_taxonomies );

		return $taxonomies;
	}

	/**
	 * @param $obj_terms
	 *
	 * @return array
	 */
	private function buildTerms( $obj_terms ) {
		$tax_terms = array();
		foreach ( $obj_terms as $term ) {
			$tax_terms[] = array(
				'name' => $term->name,
				'count' => $term->count,
				'parent' => $term->parent,
				'term_taxonomy_id' => $term->term_taxonomy_id,
				'term_id' => $term->term_id,
			);
		}

		return $tax_terms;
	}

	/**
	 * Basic Form Fields
	 *
	 * @return array
	 */
	public function get_form_fields() {
		$form_fields = array();
		$form_fields[ 'form' ] = array(
			'id' => 'credform',
			'name' => esc_js( __( 'Form Container', 'wp-cred' ) ),
			'slug' => 'credform',
			'type' => 'credform',
			'cred_builtin' => true,
			'description' => esc_js( __( 'Form (required)', 'wp-cred', 'wp-cred' ) ),
		);
		$form_fields[ 'form_submit' ] = array(
			'value' => __( 'Submit', 'wp-cred' ),
			'id' => 'form_submit',
			'name' => esc_js( __( 'Form Submit', 'wp-cred' ) ),
			'slug' => 'form_submit',
			'type' => 'form_submit',
			'cred_builtin' => true,
			'description' => esc_js( __( 'Form Submit Button', 'wp-cred' ) ),
		);
		$form_fields[ 'form_messages' ] = array(
			'value' => '',
			'id' => 'form_messages',
			'name' => esc_js( __( 'Form Messages', 'wp-cred' ) ),
			'slug' => 'form_messages',
			'type' => 'form_messages',
			'cred_builtin' => true,
			'description' => esc_js( __( 'Form Messages Container', 'wp-cred' ) ),
		);

		return $form_fields;
	}

	/**
	 * @param $fields
	 * @param $post_type_object
	 *
	 * @return array
	 */
	public function get_post_reference_fields( &$fields, $post_type_object ) {
		$post_reference_fields = array();
		foreach ( $fields as $slug => &$field ) {
			if ( CRED_Form_Relationship::get_instance()->map_post_reference_fields( $field, $post_type_object ) ) {
				$post_reference_fields[ $field[ 'slug' ] ] = $field;
				unset( $fields[ $slug ] );
			}
		}

		return $post_reference_fields;
	}
}