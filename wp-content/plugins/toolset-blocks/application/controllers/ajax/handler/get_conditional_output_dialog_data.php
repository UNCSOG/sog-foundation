<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

use OTGS\Toolset\Views\Controller\Shortcode\InnerShortcodeRegex;

/**
 * Gather the required information for rendering the conditional output shortcode dialog.
 * This includes all the items required to compose a condition:
 * - Custom fields to check against.
 * - Taxonomies to check against.
 * - Relationships (legacy or m2m) to get data from.
 * - Views and custom registered shortcodes.
 * - Custom functions.
 * It also includes the description for the attributes that this shortcode can hold.
 *
 * @since 2.7.3
 */
class WPV_Ajax_Handler_Get_Conditional_Output_Dialog_Data extends Toolset_Ajax_Handler_Abstract {

	/**
	 * @var int The current post ID, if any.
	 */
	private $post_id = 0;

	/**
	 * @var array Storage for the fields to return.
	 */
	private $fields = array();

	/**
	 * @var array Storage for the relationships to return.
	 */
	private $relationships = array();

	use InnerShortcodeRegex;

	/**
	 * Processes the AJAX call.
	 *
	 * @param array $arguments Original action arguments.
	 * @since 2.7.3
	 */
	public function process_call( $arguments ) {

		$this->ajax_begin(
			array(
				'parameter_source' => 'get',
				'nonce' => WPV_Ajax::CALLBACK_GET_CONDITIONAL_OUTPUT_DIALOG_DATA,
				'is_public' => true,
			)
		);

		$this->post_id = (int) toolset_getget( 'postId' );

		$this->populate_custom_fields();

		$this->fields['taxonomies'] = array(
			/* translators: Label for a group on the conditional output shortcode GUI, listing taxonomies to use as conditions sources */
			'label' => __( 'Taxonomies', 'wpv-views' ),
			'type' => 'taxonomies',
			'fields' => $this->get_taxonomies(),
		);

		$this->fields['views-shortcodes'] = array(
			/* translators: Label for a group on the conditional output shortcode GUI, listing Views shortcodes to use as conditions sources */
			'label' => __( 'Views Shortcodes', 'wpv-views' ),
			'type' => 'views-shortcodes',
			'fields' => $this->get_views_shortcodes(),
		);

		$this->fields['custom-shortcodes'] = array(
			/* translators: Label for a group on the conditional output shortcode GUI, listing custom registered shortcodes to use as conditions sources */
			'label' => __( 'Custom Shortcodes', 'wpv-views' ),
			'type' => 'custom-shortcodes',
			'fields' => $this->get_custom_shortcodes(),
		);

		$this->fields['custom-functions'] = array(
			/* translators: Label for a group on the conditional output shortcode GUI, listing custom functions to use as conditions sources */
			'label' => __( 'Custom Functions', 'wpv-views' ),
			'type' => 'custom-functions',
			'fields' => $this->get_custom_functions(),
		);

		$this->ajax_finish(
			array(
				'fields' => $this->fields,
				'attributes' => $this->get_shortcode_attributes(),
				'relationships' => $this->get_relationships(),
			),
			true
		);
	}

	/**
	 * Populate the entry for custom fields, separating them between Types groups and
	 * native fields in a separate group.
	 *
	 * @since 2.7.3
	 * @refactoring WE should probably get the Types plus native fields in a different way,
	 *     instead of getting al meta keys and then guessing whether they are Types and
	 *     what group they belong to. Maybe follow the shortcodes groups generation.
	 */
	private function populate_custom_fields() {
		// Types and custom fields, grouped by Types or generic group.
		// This shouts refactor!!! Types should register its fiels here,
		// we should probably have an API.
		$post_meta_keys = apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
		$native_fields = array();

		foreach ( $post_meta_keys as $key ) {
			if ( empty( $key ) ) {
				continue;
			}

			$types_field_data = wpv_types_get_field_data( $key );

			if (
				! empty( $types_field_data )
				&& '' !== toolset_getarr( $types_field_data, 'id' )
			) {
				if ( function_exists( 'wpcf_admin_fields_get_groups_by_field' ) ) {
					$field_group = array();
					foreach ( wpcf_admin_fields_get_groups_by_field( $types_field_data['id'] ) as $gs ) {
						$field_group = $gs;
					}
					$field_group_slug = toolset_getarr( $field_group, 'slug', false );
					$field_group_label = toolset_getarr( $field_group, 'name', false );

					if (
						! $field_group_slug
						|| ! $field_group_label
					) {
						// Skip Types orphan fields in no longer existing groups
						continue;
					}

					if ( ! toolset_getarr( $this->fields, $field_group_slug, false ) ) {
						$this->fields[ $field_group_slug ] = array(
							'label' => $field_group_label,
							'type' => 'types',
							'fields' => array(),
						);
					}

					$this->fields[ $field_group_slug ]['fields'][ $key ] = array(
						'label' => toolset_getarr( $types_field_data, 'name', $key ),
						'slug' => sprintf( '$(%s)', $key ),
					);
				} else {
					$native_fields[ $key ] = array(
						'label' => toolset_getarr( $types_field_data, 'name', $key ),
						'slug' => sprintf( '$(%s)', $key ),
					);
				}
			} else {
				$native_fields[ $key ] = array(
					'label' => $key,
					'slug' => sprintf( '$(%s)', $key ),
				);
			}
		}

		if ( ! empty( $native_fields ) ) {
			$this->fields['custom-fields'] = array(
				/* translators: Label for a group on the conditional output shortcode GUI, listing native custom fields to use as conditions sources */
				'label' => __( 'Custom Fields', 'wpv-views' ),
				'type' => 'custom-fields',
				'fields' => $native_fields,
			);
		}
	}

	/**
	 * Gather the Views shortcodes that can be used inside conditions.
	 * In theory, all wpv- shortcodes provided by \OTGS\Toolset\Views\Controller\Shortcode\InnerShortcodeRegex::get_inner_shortcodes_regex.
	 *
	 * @todo Note that this is highly cumbersome:
	 * shortcodes should register themselves as supported shortcodes.
	 *
	 * @return array[] {
	 *     @type $label The label for the option for each shortcode
	 *     @type $slug The string that will get included as condition
	 * }
	 * @since 2.7.3
	 */
	private function get_views_shortcodes() {
		// Views Shortcodes
		// This shoults REFACTOR!!!!!
		global $shortcode_tags;
		$fields = array();

		if ( is_array( $shortcode_tags ) ) {
			$views_shortcodes_regex = $this->get_inner_shortcodes_regex();
			$include_expression = '/^(' . $views_shortcodes_regex . ')/';

			foreach ( array_keys( $shortcode_tags ) as $key ) {
				if ( ! preg_match( $include_expression, $key ) ) {
					continue;
				}

				// Skip non-Views shortcodes
				if ( ! preg_match( '/^wpv/', $key ) ) {
					continue;
				}

				// Adjust or skip shortcodes that render by default an HTML tag
				// or can contain HTML tags
				// since their opening tag will break the condition
				if (
					WPV_Shortcode_Post_Body::SHORTCODE_NAME === $key
					|| WPV_Shortcode_Post_Edit_Link::SHORTCODE_NAME === $key
					|| WPV_Shortcode_Post_Link::SHORTCODE_NAME === $key
					|| WPV_Shortcode_Post_Read_More::SHORTCODE_NAME === $key
					|| WPV_Shortcode_Post_Next_Link::SHORTCODE_NAME === $key
					|| WPV_Shortcode_Post_Previous_Link::SHORTCODE_NAME === $key
				) {
					continue;
				}

				$shortcode_slug = $key;

				if ( WPV_Shortcode_Post_Featured_Image::SHORTCODE_NAME === $key ) {
					$shortcode_slug = WPV_Shortcode_Post_Featured_Image::SHORTCODE_NAME . ' output="url"';
				}

				$fields[ $key ] = array(
					'label' => $key,
					'slug' => sprintf( '\'[%s]\'', $shortcode_slug ),
				);
			}
			ksort( $fields );
		}

		return $fields;
	}

	/**
	 * Gather all public taxonomies to offer them as comparison sources.
	 *
	 * @return array[] {
	 *     @type $label The label for the option for each taxonomy
	 *     @type $slug The string that will get included as condition
	 * }
	 * @since 2.7.3
	 */
	private function get_taxonomies() {
		$taxonomies_args = array(
			'public'   => true,
		);
		$taxonomies = get_taxonomies( $taxonomies_args, 'objects' );

		$taxonomy_fields = array();

		foreach ( $taxonomies as $taxonomy ) {
			$taxonomy_fields[ $taxonomy->name ] = array(
				'label' => $taxonomy->label,
				'slug' => sprintf( '#(%s)', $taxonomy->name ),
			);
		}

		return $taxonomy_fields;
	}

	/**
	 * Gather all custom shortcodes to offer them as comparison sources.
	 *
	 * @return array[] {
	 *     @type $label The label for the option for each shortcode
	 *     @type $slug The string that will get included as condition
	 * }
	 * @since 2.7.3
	 */
	private function get_custom_shortcodes() {
		$global_settings = WPV_Settings::get_instance();
		$fields = array();

		if ( ! empty( $global_settings->wpv_custom_inner_shortcodes ) ) {
			foreach ( $global_settings->wpv_custom_inner_shortcodes as $key ) {
				if ( empty( $key ) ) {
					continue;
				}
				$fields[ $key ] = array(
					'label' => sprintf( '[%s]', $key ),
					'slug' => sprintf( '\'[%s]\'', $key ),
				);
			}
		}

		return $fields;
	}

	/**
	 * Gather all custom functions to offer them as comparison sources.
	 *
	 * @return array[] {
	 *     @type $label The label for the option for each shortcode
	 *     @type $slug The string that will get included as condition
	 * }
	 * @since 2.7.3
	 */
	private function get_custom_functions() {
		$global_settings = WPV_Settings::get_instance();
		$fields = array();

		// Custom functions
		if ( ! empty( $global_settings->wpv_custom_conditional_functions ) ) {
			foreach ( $global_settings->wpv_custom_conditional_functions as $key ) {
				if ( empty( $key ) ) {
					continue;
				}
				$fields[ $key ] = array(
					'label' => sprintf( '%s()', $key ),
					'slug' => sprintf( '%s()', $key ),
				);
			}
		}

		return $fields;
	}

	/**
	 * Gather all the data for the wpv-conditional shortcode attributes.
	 *
	 * @return array
	 * @since 2.7.3
	 */
	private function get_shortcode_attributes() {
		$attributes = array(
			'conditions' => array(
				/* translators: Title of the conditional output shortcode GUI dialog */
				'label' => __( 'Conditional output', 'wpv-views' ),
				/* translators: Title of the conditional output shortcode GUI dialog */
				'header' => __( 'Conditional output', 'wpv-views' ),
				'fields' => array(
					'if' => array(
						/* translators: Title of the conditional output shortcode GUI dialog section to set conditions */
						'label' => __( 'Conditions to evaluate', 'wpv-views' ),
						'type' => 'callback',
					),
					'evaluate' => array(
						/* translators: Title of the conditional output shortcode GUI dialog section to decide whether conditions should pass or not */
						'label' => __( 'Conditions evaluation', 'wpv-views' ),
						'type' => 'radio',
						'options' => array(
							/* translators: Label of the conditional output shortcode GUI dialog option about whether conditions should pass or not */
							'true' => __( 'The evaluation result should be TRUE', 'wpv-views' ),
							/* translators: Label of the conditional output shortcode GUI dialog option about whether conditions should pass or not */
							'false' => __( 'The evaluation result should be FALSE', 'wpv-views' ),
						),
						/* translators: Description of the conditional output shortcode GUI dialog option about whether conditions should pass or not */
						'description' => __( 'Whether the condition should be compared to TRUE or to FALSE.', 'wpv-views' ),
						'defaultValue' => 'true',
					),
				),
			),
		);

		if ( current_user_can( EDIT_VIEWS ) ) {
			$attributes['debug'] = array(
				/* translators: Title of the conditional output shortcode GUI dialog section to enable debug output */
				'label' => __( 'Conditional debug', 'wpv-views' ),
				/* translators: Title of the conditional output shortcode GUI dialog section to enable debug output */
				'header' => __( 'Conditional debug', 'wpv-views' ),
				'fields' => array(
					'debug' => array(
						/* translators: Title of the conditional output shortcode GUI dialog section to decide whether debug output should be generated or not */
						'label' => __( 'Show debug', 'wpv-views' ),
						'type' => 'radio',
						'options' => array(
							/* translators: Label of the conditional output shortcode GUI dialog option to decide whether debug output should be generated or not */
							'true' => __( 'Show debug information to administrators', 'wpv-views' ),
							/* translators: Label of the conditional output shortcode GUI dialog option to decide whether debug output should be generated or not */
							'false' => __( 'Don\'t show any debug information', 'wpv-views' ),
						),
						/* translators: Description of the conditional output shortcode GUI dialog option to decide whether debug output should be generated or not */
						'description' => __( 'Show additional information to administrators about the evaluation process.', 'wpv-views' ),
						'defaultValue' => 'false',
					),
				),
			);

			$attributes['settings'] = array(
				/* translators: Title of the conditional output shortcode GUI dialog section to fill additional settings */
				'label' => __( 'Additional settings', 'wpv-views' ),
				/* translators: Title of the conditional output shortcode GUI dialog section to fill additional settings */
				'header' => __( 'Additional settings', 'wpv-views' ),
				'fields' => array(
					'shortcodes' => array(
						/* translators: Label of the conditional output shortcode GUI dialog option related to custom shortcodes */
						'label' => __( 'Third party shortcodes', 'wpv-views' ),
						'type' => 'callback',
					),
					'functions' => array(
						/* translators: Label of the conditional output shortcode GUI dialog option related to custom registered functions */
						'label' => __( 'Registered functions', 'wpv-views' ),
						'type' => 'callback',
					),
				),
			);
		}

		return $attributes;
	}

	/**
	 * Gather all relationships to offer them as items sources.
	 *
	 * @return array[] {
	 *     @type $label The label for the option for each relationship
	 *     @type $slug The string that will get included as source
	 * }
	 * @since 2.7.3
	 */
	private function get_relationships() {
		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			$this->populate_legacy_relationships();
		} else {
			$this->populate_m2m_relationships();
		}
		return $this->relationships;
	}

	/**
	 * Populate legacy relationships to offer them as items sources.
	 *
	 * @since 2.7.3
	 */
	private function populate_legacy_relationships() {
		$post_type = ( 0 === $this->post_id ) ? false : get_post_type( $this->post_id );

		$custom_post_types_relations = get_option( 'wpcf-custom-types', array() );

		if ( false === $post_type ) {
			foreach ( $custom_post_types_relations as $cptr_key => $cptr_data ) {
				if ( isset( $cptr_data['post_relationship']['has'] ) ) {
					$this->relationships[ $cptr_key ] = array(
						'label' => toolset_getnest( $custom_post_types_relations, array( $cptr_key, 'labels', 'singular_name' ), $cptr_key ),
						'slug' => '$' . $cptr_key,
					);
				}
				if (
					isset( $cptr_data['post_relationship']['belongs'] )
					&& is_array( $cptr_data['post_relationship']['belongs'] )
				) {
					$this_belongs = array_keys( $cptr_data['post_relationship']['belongs'] );
					foreach ( $this_belongs as $this_belongs_candidate ) {
						if ( isset( $custom_post_types_relations[ $this_belongs_candidate ] ) ) {
							$this->relationships[ $this_belongs_candidate ] = array(
								'label' => toolset_getnest( $custom_post_types_relations, array( $this_belongs_candidate, 'labels', 'singular_name' ), $this_belongs_candidate ),
								'slug' => '$' . $this_belongs_candidate,
							);
						}
					}
				}
			}
		} else {
			foreach ( $custom_post_types_relations as $cptr_key => $cptr_data ) {
				if (
					isset( $cptr_data['post_relationship']['has'] )
					&& in_array( $post_type, array_keys( $cptr_data['post_relationship']['has'] ), true )
				) {
					$this->relationships[ $cptr_key ] = array(
						'label' => toolset_getnest( $custom_post_types_relations, array( $cptr_key, 'labels', 'singular_name' ), $cptr_key ),
						'slug' => '$' . $cptr_key,
					);
				}
			}
			if ( isset( $custom_post_types_relations[ $post_type ] ) ) {
				$current_post_type_data = $custom_post_types_relations[ $post_type ];
				if (
					isset( $current_post_type_data['post_relationship'] )
					&& ! empty( $current_post_type_data['post_relationship'] )
					&& isset( $current_post_type_data['post_relationship']['belongs'] )
				) {
					foreach ( array_keys( $current_post_type_data['post_relationship']['belongs'] ) as $cpt_in_relation ) {
						// Watch out! WE are not currently clearing the has and belongs entries of the relationships when deleting a post type
						// So make sure the post type does exist
						if ( isset( $custom_post_types_relations[ $cpt_in_relation ] ) ) {
							$this->relationships[ $cpt_in_relation ] = array(
								'label' => toolset_getnest( $custom_post_types_relations, array( $cpt_in_relation, 'labels', 'singular_name' ), $cpt_in_relation ),
								'slug' => '$' . $cpt_in_relation,
							);
						}
					}
				}
			}
		}
	}

	/**
	 * Populate m2m relationships to offer them as items sources.
	 *
	 * @since 2.7.3
	 */
	private function populate_m2m_relationships() {
		$post_type = ( 0 === $this->post_id ) ? false : get_post_type( $this->post_id );

		do_action( 'toolset_do_m2m_full_init' );
		$query = new Toolset_Relationship_Query_V2();

		$query->add(
			$query->do_or(
				$query->has_cardinality( $query->cardinality()->one_to_one() ),
				$query->has_cardinality( $query->cardinality()->one_to_many() ),
				$query->has_cardinality( $query->cardinality()->many_to_one() )
			)
		);

		if ( $post_type ) {
			$relationship_definitions = $query->add(
				$query->do_and(
					$query->do_or(
						$query->has_domain_and_type( $post_type, Toolset_Element_Domain::POSTS ),
						$query->intermediary_type( $post_type )
					),
					$query->do_or(
						$query->origin( Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ),
						$query->origin( Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD )
					)
				)
			);
		} else {
			$relationship_definitions = $query->add(
				$query->do_or(
					$query->origin( Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ),
					$query->origin( Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD )
				)
			);
		}

		$relationship_definitions = $query->get_results();

		foreach ( $relationship_definitions as $definition ) {
			$relationship_cardinality = $definition->get_cardinality()->get_type();
			$relationship_parents = $definition->get_element_type( Toolset_Relationship_Role::PARENT )->get_types();
			$relationship_children = $definition->get_element_type( Toolset_Relationship_Role::CHILD )->get_types();

			switch ( $relationship_cardinality ) {
				case 'one-to-one':
					if ( ! in_array( $post_type, $relationship_parents, true ) ) {
						$this->include_m2m_relationship_field( $definition, $relationship_parents, Toolset_Relationship_Role::PARENT );
					}
					if ( ! in_array( $post_type, $relationship_children, true ) ) {
						$this->include_m2m_relationship_field( $definition, $relationship_children, Toolset_Relationship_Role::CHILD );
					}
					break;
				case 'one-to-many':
					if ( ! in_array( $post_type, $relationship_parents, true ) ) {
						$this->include_m2m_relationship_field( $definition, $relationship_parents, Toolset_Relationship_Role::PARENT );
					}
					break;
				case 'many-to-one':
					if ( ! in_array( $post_type, $relationship_children, true ) ) {
						$this->include_m2m_relationship_field( $definition, $relationship_children, Toolset_Relationship_Role::CHILD );
					}
					break;
			}
		}
	}

	/**
	 * Auxiliar method to cache m2m relationships to offer them as item sources.
	 *
	 * @param \Toolset_Relationship_Definition $relationship_definition
	 * @param array $relationship_related
	 * @param string $role
	 * @since 2.7.3
	 */
	private function include_m2m_relationship_field( $relationship_definition, $relationship_related, $role ) {
		$post_types_repository = Toolset_Post_Type_Repository::get_instance();

		$relationship_label = $relationship_definition->get_display_name();
		$relationship_slug = $relationship_definition->get_slug();

		$first_type_for_role = reset( $relationship_related );
		$post_type_for_role = $post_types_repository->get( $first_type_for_role );

		$this->relationships[ $relationship_slug . '.' . $role ] = array(
			'label' => sprintf(
				/* translators: Label of the conditional output shortcode GUI dialog option to get data from related posts: the first variable is the related post type, the second one is the relationship */
				__( 'from a related %1$s (%2$s)', 'wpv-views' ),
				$post_type_for_role->get_label( Toolset_Post_Type_Labels::SINGULAR_NAME ),
				$relationship_label
			),
			'slug' => '@' . $relationship_slug . '.' . $role,
		);
	}

}
