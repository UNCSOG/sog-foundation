<?php

/**
 * Filter by post relationship. Used in Views and WPAs.
 *
 * @since m2m
 */
class WPV_Filter_Post_Relationship extends WPV_Filter_Base {

	const SLUG = 'post-relationship';

	/**
	 * @var array
	 */
	private $conditions = array();

	/**
	 * @var bool
	 */
	private $is_m2m_available = null;

	/**
	 * @var Toolset_Relationship_Definition[]|null
	 */
	private $legacy_relationships = null;

	/**
	 * @var Toolset_Relationship_Definition[]
	 */
	private $legacy_guessed_relationships = array();

	/**
	 * @var Toolset_Condition_Plugin_Types_Active
	 */
	protected $is_types_active;

	/**
	 * @var Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured
	 */
	protected $is_wpml_active_and_configured;

	/**
	 * @var null|string[]
	 */
	private $intermediary_post_types = null;

	/**
	 * List of post type slugs that are directly related to a set of post types, including IPTs.
	 * Aims to contain all post types that can have a relationship filter defined against another list of post types.
	 *
	 * Each array item has a key of #-separated original post types slugs, and the value is the array of candidates to filter by.
	 *
	 * @var array[]
	 */
	private $closest_related_cache = array();

	/**
	 * Construct the filter.
	 *
	 * @param \Toolset_Condition_Plugin_Types_Active $is_types_active
	 * @param \Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured $is_wpml_active_and_configured
	 */
	function __construct(
		\Toolset_Condition_Plugin_Types_Active $is_types_active,
		\Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured $is_wpml_active_and_configured
	) {
		$this->is_types_active = $is_types_active;
		$this->is_wpml_active_and_configured = $is_wpml_active_and_configured;

		if ( $this->is_types_installed() ) {
			$this->gui = new WPV_Filter_Post_Relationship_Gui( $this );
			$this->query = new WPV_Filter_Post_Relationship_Query( $this );
			$this->search = new WPV_Filter_Post_Relationship_Search( $this );
		}
	}

	/**
	 * Check if Toolset Types is installed.
	 *
	 * @return bool
	 *
	 * @since m2m
	 */
	public function is_types_installed() {
		if ( ! isset( $this->conditions['types'] ) ) {
			$this->conditions['types'] = $this->is_types_active;
		}

		return $this->conditions['types']->is_met();
	}

	/**
	 * Check if WPML is installed.
	 *
	 * @return bool
	 *
	 * @since m2m
	 */
	public function is_wpml_installed_and_ready() {
		if ( ! isset( $this->conditions['wpml'] ) ) {
			$this->conditions['wpml'] = $this->is_wpml_active_and_configured;
		}

		return $this->conditions['wpml']->is_met();
	}

	/**
	 * Check if m2m is activated. If so, maybe full initialize it.
	 *
	 * @return bool
	 *
	 * @since m2m
	 */
	public function check_and_init_m2m() {
		if ( ! is_null( $this->is_m2m_available ) ) {
			return $this->is_m2m_available;
		}

		$this->is_m2m_available = apply_filters( 'toolset_is_m2m_enabled', false );
		if ( $this->is_m2m_available ) {
			do_action( 'toolset_do_m2m_full_init' );
		}

		return $this->is_m2m_available;
	}

	/**
	 * Get post types that are directly related to the ones returned by the current loop.
	 *
	 * @return string[]
	 *
	 * @since m2m
	 * @todo review the m2m query
	 */
	public function get_closest_related_to_returned_post_types() {
		$returned_post_types = $this->get_returned_post_types();
		$returned_post_types_hash = implode( '#', $returned_post_types );

		$cached_query = toolset_getarr( $this->closest_related_cache, $returned_post_types_hash, false );
		if ( false !== $cached_query ) {
			return $cached_query;
		}

		$closest_related = array();

		if ( $this->check_and_init_m2m() ) {
			$relationship_query = new Toolset_Relationship_Query_V2();
			$conditions = array();
			foreach ( $returned_post_types as $returned_post_type_slug ) {
				$conditions[] = $relationship_query->has_domain_and_type( $returned_post_type_slug, Toolset_Element_Domain::POSTS );
				$conditions[] = $relationship_query->intermediary_type( $returned_post_type_slug );
			}
			$definitions = $relationship_query
				->add( $relationship_query->do_or( $conditions ) )
				->add( $relationship_query->origin( null ) )
				->get_results();
			foreach ( $definitions as $definition ) {
				$parent_type = $definition->get_parent_type()->get_types();
				$child_type = $definition->get_child_type()->get_types();
				$closest_related = array_merge( $closest_related, $parent_type );
				$closest_related = array_merge( $closest_related, $child_type );
				if ( null != $definition->get_intermediary_post_type() ) {
					$closest_related[] = $definition->get_intermediary_post_type();
				}
			}
		} else if ( $this->is_types_installed() ) {
			foreach ( $returned_post_types as $returned_post_type_slug ) {
				$parent_parents_array = wpcf_pr_get_belongs( $returned_post_type_slug );
				if ( $parent_parents_array != false && is_array( $parent_parents_array ) ) {
					$closest_related = array_merge( $closest_related, array_values( array_keys( $parent_parents_array ) ) );
				}
			}
		}

		$this->closest_related_cache[ $returned_post_types_hash ] = $closest_related;
		return $closest_related;
	}

	/**
	 * Get relationships defined before activating m2m.
	 *
	 * @return Toolset_Relationship_Definition[]
	 *
	 * @since m2m
	 */
	public function get_legacy_relationships() {

		if ( null !== $this->legacy_relationships ) {
			return $this->legacy_relationships;
		}

		if ( ! $this->check_and_init_m2m() ) {
			$this->legacy_relationships = array();
			return $this->legacy_relationships;
		}

		$relationship_query = new Toolset_Relationship_Query_V2();
		$this->legacy_relationships = $relationship_query
			->add( $relationship_query->is_legacy( true ) )
			->get_results();

		return $this->legacy_relationships;
	}

	/**
	 * Get a legacy relationship between two post types, if any.
	 *
	 * @param string $parent_type
	 * @param string $child_type
	 * @return \Toolset_Relationship_Definition|false
	 */
	public function guess_legacy_relationship( $parent_type, $child_type ) {
		$cache_key = $parent_type . '|#|' . $child_type;

		if ( isset( $this->legacy_guessed_relationships[ $cache_key ] ) ) {
			return $this->legacy_guessed_relationships[ $cache_key ];
		}

		if ( ! $this->check_and_init_m2m() ) {
			$this->legacy_guessed_relationships[ $cache_key ] = false;
			return $this->legacy_guessed_relationships[ $cache_key ];
		}

		$relationship_query = new \Toolset_Relationship_Query_V2();
		$relationship_query->do_not_add_default_conditions();
		$definitions = $relationship_query
			->add( $relationship_query->is_legacy( true ) )
			->add( $relationship_query->has_domain_and_type( $parent_type, \Toolset_Element_Domain::POSTS, new \Toolset_Relationship_Role_Parent() ) )
			->add( $relationship_query->has_domain_and_type( $child_type, \Toolset_Element_Domain::POSTS, new \Toolset_Relationship_Role_Child() ) )
			->get_results();

		if ( empty( $definitions ) ) {
			$this->legacy_guessed_relationships[ $cache_key ] = false;
			return $this->legacy_guessed_relationships[ $cache_key ];
		}

		$definition = reset( $definitions );
		$this->legacy_guessed_relationships[ $cache_key ] = $definition;
		return $this->legacy_guessed_relationships[ $cache_key ];
	}

	/**
	 * Create an array of ancestors in legacy Types post relationships.
	 *
	 * @param $post_types (array) array of post type slugs to get the relationships from
	 * @param $level (int) depth of recursion, we are hardcoding limiting it to 5
	 *
	 * @return string[]
	 *
	 * @note this function is recursive
	 *
	 * @since 1.6.0
	 */
	public function get_legacy_post_type_ancestors( $post_types = array(), $level = 0 ) {
		$parents_array = array();
		if ( ! is_array( $post_types ) ) {
			// Sometimes, when saving the Content Selection section with no post type selected, this is not an array
			// That can happen when switching to list taxonomy terms or users without selecting a post type first
			return $parents_array;
		}
		if (
			function_exists( 'wpcf_pr_get_belongs' )
			&& $level < 5
		) {
			foreach ( $post_types as $post_type_slug ) {
				$this_parents = wpcf_pr_get_belongs( $post_type_slug );
				if (
					$this_parents != false
					&& is_array( $this_parents )
				) {
					$new_parents = array_values( array_keys( $this_parents ) );
					$parents_array = array_merge( $parents_array, $new_parents );
					$grandparents_array = $this->get_legacy_post_type_ancestors( $new_parents, $level + 1 );
					$parents_array = array_merge( $parents_array, $grandparents_array );
				}
			}
		}
		return $parents_array;
	}

	/**
	 * Get a list of intermediary post types.
	 *
	 * @return string[]
	 *
	 * @since m2m
	 */
	public function get_intermediary_post_types() {
		if ( null != $this->intermediary_post_types ) {
			return $this->intermediary_post_types;
		}

		$post_type_query_factory = new Toolset_Post_Type_Query_Factory();
		$intermediary_post_type_query = $post_type_query_factory->create(
			array(
				Toolset_Post_Type_Query::IS_INTERMEDIARY => true,
				Toolset_Post_Type_Query::RETURN_TYPE => 'slug'
			)
		);

		$this->intermediary_post_types = $intermediary_post_type_query->get_results();

		return $this->intermediary_post_types;
	}

	/**
	 * Generate a relationships tree structure given a relationships string
	 * as passed to the shortcode ancestors attribute.
	 *
	 * Each node of the returned array refers to a relationship node in the filter, and contains:
	 * - the key as the involved post type slug, as it can only appear once.
	 * - type: the involved post type slug.
	 * - relationship: the involved relationship slug.
	 * - role: the role of the involved post type in the involved relationship.
	 * - role_target: the role that plays, in the involved relationship,
	 *   the post type in the next element of the relationships chain.
	 *
	 * @para string $relationships_chain_string
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	public function get_relationship_tree( $relationships_chain_string ) {
		$relationship_tree = array();

		// Make sure that HTML-entitied relationship chain strings are managed properly.
		$relationships_chain_string = str_replace( '&gt;', '>', $relationships_chain_string );

		$relationships_chain_pieces = explode( '>', $relationships_chain_string );
		foreach ( $relationships_chain_pieces as $piece_index => $relationships_chain_step ) {
			// $piece should be a string with the format 'postType@relationship.role'
			// but on legacy relationships, in which case it will be just 'postType'.
			$relationships_chain_step_data = explode( '@', $relationships_chain_step );

			$ancestor_type = $relationships_chain_step_data[0];

			$relationships_chain_step_role_data = isset( $relationships_chain_step_data[1] )
				? explode( '.', $relationships_chain_step_data[1] )
				: array( '', 'parent' );
			$relationship_tree[ $ancestor_type ] = array(
				'type' => $ancestor_type,
				'relationship' => $relationships_chain_step_role_data[0],
				'role' => isset( $relationships_chain_step_role_data[1] )
					? $relationships_chain_step_role_data[1]
					: 'parent'
			);

			if ( empty( $relationship_tree[ $ancestor_type ]['relationship'] ) ) {
				$relationship_tree[ $ancestor_type ]['role_target'] = 'child';
				continue;
			}

			if ( ! $this->check_and_init_m2m() ) {
				continue;
			}

			$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
			$relationship_definition = $relationship_repository->get_definition( $relationship_tree[ $ancestor_type ]['relationship'] );
			if ( null === $relationship_definition ) {
				$relationship_tree[ $ancestor_type ]['role_target'] = Toolset_Relationship_Role::CHILD;
				continue;
			}

			if ( isset( $relationships_chain_pieces[ $piece_index + 1 ] ) )  {
				$next_pieces = explode( '@', $relationships_chain_pieces[ $piece_index + 1 ] );
				$next_post_types = array( $next_pieces[0] );
			} else {
				$next_post_types = $this->get_returned_post_types();
			}

			$relationship_tree[ $ancestor_type ]['role_target'] = $this->get_ancestor_step_role_target(
				$relationship_definition, $relationship_tree[ $ancestor_type ]['role'], $next_post_types
			);

		}

		return $relationship_tree;
	}


	/**
	 * Get the role of the next step in the relationships tree chain in the relationship of the current step.
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 * @param string $role
	 * @param string[] $next_post_types
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_ancestor_step_role_target( Toolset_Relationship_Definition $relationship_definition, $role, $next_post_types ) {
		$parent_types = $relationship_definition->get_parent_type()->get_types();
		$intermediary_type = $relationship_definition->get_intermediary_post_type();

		switch ( $role ) {
			case Toolset_Relationship_Role::PARENT:
				if (
					null != $intermediary_type
					&& in_array( $intermediary_type, $next_post_types )
				) {
					return Toolset_Relationship_Role::INTERMEDIARY;
					break;
				}
				return Toolset_Relationship_Role::CHILD;
				break;
			case Toolset_Relationship_Role::CHILD:
				if (
					null != $intermediary_type
					&& in_array( $intermediary_type, $next_post_types )
				) {
					return Toolset_Relationship_Role::INTERMEDIARY;
					break;
				}
				return Toolset_Relationship_Role::PARENT;
				break;
			case Toolset_Relationship_Role::INTERMEDIARY:
				$parent_types_intersect = array_intersect( $parent_types, $next_post_types );
				if ( count( $parent_types_intersect ) > 0 ) {
					return Toolset_Relationship_Role::PARENT;
				} else {
					return Toolset_Relationship_Role::CHILD;
				}
				break;
			default:
				return Toolset_Relationship_Role::CHILD;
				break;
		}

		return Toolset_Relationship_Role::CHILD;
	}

	/**
	 * Adjust the association query limit based on the relationship, roles and related items invovled.
	 *
	 * @param int $preset_limit Limit to use when no restriction is possible.
	 * @param \Toolset_Relationship_Cardinality $relationship_cardinality
	 * @param string $role_to_query
	 * @param int $known_role_items_count
	 * @return int
	 */
	public function get_association_adjusted_query_limit(
		$preset_limit,
		\Toolset_Relationship_Cardinality $relationship_cardinality,
		$role_to_query,
		$known_role_items_count
	) {
		// One to one relationship: the queried side will have at much as many as known related.
		if ( $relationship_cardinality->is_one_to_one() ) {
			return $known_role_items_count;
		}
		// One to many relationship: when querying parents, we have at most as many as passed children.
		if (
			$relationship_cardinality->is_one_to_many()
			&& \Toolset_Relationship_Role::PARENT === $role_to_query
		) {
			return $known_role_items_count;
		}
		// Many to one relationship: when querying children, we have at most as many as passed parents.
		if (
			$relationship_cardinality->is_many_to_one()
			&& \Toolset_Relationship_Role::CHILD === $role_to_query
		) {
			return $known_role_items_count;
		}

		// Many to many relationships gathering the other extreme of the relationship can not have a reduced limit here.
		return $preset_limit;
	}

	/**
	 * Filter the results of association queries to return only items in the right status.
	 *
	 * @param false|int[] $results
	 * @return false|int[]
	 */
	public function filter_association_query_results_by_status( $results ) {
		if ( ! $results || empty( $results ) ) {
			return $results;
		}

		$results_count = count( $results );

		if ( $results_count < 5 ) {
			return $this->filter_short_association_query_results_by_status( $results );
		}

		return $this->filter_large_association_query_results_by_status( $results );
	}

	/**
	 * Only for queries with a low limit.
	 *
	 * @param int[] $results
	 * @return int[]
	 */
	private function filter_short_association_query_results_by_status( $results ) {
		$results = array_filter( $results, function( $item ) {
			$status = get_post_status( $item );
			if ( false == $status ) {
				return false;
			}
			return in_array( $status, array( 'publish', 'future', 'draft', 'pending', 'private' ), true );
		} );

		return $results;
	}

	/**
	 * Only for queries with a high limit.
	 *
	 * @param int[] $results
	 * @return int[]
	 */
	private function filter_large_association_query_results_by_status( $results ) {
		// Sanitize values to ensure INT
		// Build query to get IDs and status, or just IDs taking out the undesired status.
		// Return results.
		$results = array_map( 'esc_attr', $results );
		$results = array_map( 'trim', $results );
		// is_numeric does sanitization
		$results = array_filter( $results, 'is_numeric' );
		$results = array_map( 'intval', $results );

		if ( 0 === count( $results ) ) {
			return array();
		}

		global $wpdb;

		$sql_statement = "SELECT ID, post_status FROM {$wpdb->posts} WHERE ID IN ('" . implode("','", $results) . "') LIMIT %d";

		$cached_filtered_results = wp_cache_get( md5( $sql_statement ), 'toolset_rel_status_filter' );

		if ( false !== $cached_filtered_results ) {
			return $cached_filtered_results;
		}

		$filtered_results = array();
		$results_count = count( $results );
		$results_with_status = $wpdb->get_results(
			$wpdb->prepare(
				$sql_statement,
				$results_count
			)
		);
		foreach ( $results_with_status as $result_and_status ) {
			if ( in_array( $result_and_status->post_status, array( 'publish', 'future', 'draft', 'pending', 'private' ), true ) ) {
				$filtered_results[] = $result_and_status->ID;
			}
		}

		wp_cache_set( md5( $sql_statement ), $filtered_results, 'toolset_rel_status_filter' );

		return $filtered_results;
	}

}
