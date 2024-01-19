<?php

use OTGS\Toolset\Common\Relationships\API\Factory;

/**
 * Class that Handles Post Relationships and Associations
 *
 * @since m2m
 */
class CRED_Form_Relationship {

	/**
	 * Relationship definition
	 *
	 * @var Toolset_Relationship_Definition
	 */
	private $definition;

	/**
	 * Association content data
	 *
	 * @var CRED_Form_Association
	 */
	private $association;

	/**
	 * Association definition
	 *
	 * @var IToolset_Association
	 */
	private $current_association;

	/**
	 * @var null|Toolset_Relationship_Definition_Repository
	 */
	private $_definition_repository;

	/**
	 * @var Factory|null This class may be instantiated even when relationships are not enabled.
	 * Use $this->get_relationships_factory().
	 */
	private $_relationships_factory;

	private static $instance;

	/**
	 * @return CRED_Form_Relationship
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return bool
	 */
	public function is_m2m_enabled() {
		return apply_filters( 'toolset_is_m2m_enabled', false );
	}

	/**
	 * CRED_Form_Relationship constructor.
	 *
	 * @param Toolset_Relationship_Definition_Repository|null $definition_repository
	 * @param CRED_Form_Association|null $association
	 */
	public function __construct( Toolset_Relationship_Definition_Repository $definition_repository = null, CRED_Form_Association $association = null ) {
		do_action( 'toolset_do_m2m_full_init' );

		$this->_definition_repository = $definition_repository;
		$this->association = ( null === $association ) ? CRED_Form_Association::get_instance() : $association;
	}

	/**
	 * @param int $user_id
	 * @param array $field
	 *
	 * @return bool
	 */
	public function connect_to_user( $user_id, $field ) {
		//TODO: for the future
		return true;
	}

	/**
	 * Create an association post_id <=> field_id
	 *
	 * @param int $post_id
	 * @param array $field
	 *
	 * @return string|bool
	 */
	public function connect_to_post( $post_id, $field ) {
		$field_name = sanitize_text_field( str_replace( '.', '_', $field[ 'key' ] ) );

		//Checking just isset because empty value is a possible value
		if ( ! isset( $_POST[ $field_name ] ) ) {
			return sprintf( __( 'Field %s is not present', 'wp-cred' ), $field_name );
		}

		//$field_id by the relationship field in $_POST
		$value = (int) $_POST[ $field_name ];

		//$post_id to connect to the $field_id related post
		$post = get_post( $post_id );
		if ( ! $post ) {
			return __( 'Post not found.', 'wp-cred' );
		}

		if ( ! isset( $post->post_type ) ) {
			return __( 'Post doesn\'t have a valid post_type.', 'wp-cred' );
		}

		if ( ! isset( $field[ 'slug' ] )
			|| empty( $field[ 'slug' ] )
		) {
			return __( 'Relationship doesn\'t have a valid slug.', 'wp-cred' );
		}

		$definition = $this->get_definition_by_relationship_slug( $field[ 'slug' ] );
		if ( ! $definition ) {
			return __( 'Relationship not found.', 'wp-cred' );
		}

		// The role belonging to the current post.
		$role = $this->get_role_from_post_type( $definition, $post->post_type );

		if ( ! isset( $field[ 'cardinality' ][ $role ][ 'max' ] ) ) {
			return __( 'Relationship doesn\'t have a valid cardinality', 'wp-cred' );
		}

		// The role belonging to the related post.
		$other_role = Toolset_Relationship_Role::other( $role );
		$other_element_type = $definition->get_element_type( $other_role );
		$other_types = $other_element_type->get_types();
		$cardinality = (int) $field[ 'cardinality' ][ $role ][ 'max' ];

		/*
		 * I am connection a Post if i have to connect a Parent to a Child or viceversa checking if i have already one
		 * in order to disconnect it before inserting a new one
		 */
		$is_child_parent_connection = (
			( Toolset_Relationship_Role::CHILD === $role
				&& Toolset_Relationship_Role::PARENT === $field[ 'role' ] )
			||
			( Toolset_Relationship_Role::CHILD === $field[ 'role' ]
				&& Toolset_Relationship_Role::PARENT === $role )
		);

		if ( ! $is_child_parent_connection ) {
			return true;
		}

		// get association
		$associations = $this->association->get_associations( $post_id, $definition, $role, 1 );
		$association = array_shift( $associations );
		if ( ! empty( $association ) ) {
			// There is a stored association
			$is_current_association_different_to_stored = $this->association->get_associated_object_id_by_role( $association, $field[ 'role' ] ) != $value;

			if ( $is_current_association_different_to_stored ) {
				// associated post has changed, delete previous
				$this->association->delete( $association );
			} else {

				//we are editing with the same value
				return true;
			}
		}

		if ( ! empty( $value ) ) {
			try {
				// set the right object in the right place, as create_association minds the order
				$parent_to_connect = ( Toolset_Relationship_Role::CHILD === $field[ 'role' ] )
					? $post
					: get_post( $value );
				$child_to_connect = ( Toolset_Relationship_Role::CHILD === $field[ 'role' ] )
					? get_post( $value )
					: $post;
				// user has set a new relationship, store it
				$result = $definition->create_association(
					$parent_to_connect,
					$child_to_connect
				);

				//If result is instance of Toolset Result means that is a error
				if ( $result instanceof Toolset_Result ) {
					return $result->get_message();
				}
			} catch ( InvalidArgumentException $invalid_argument_exception ) {
				return $invalid_argument_exception->getMessage();
			} catch ( RuntimeException $runtime_exception ) {
				return $runtime_exception->getMessage();
			} catch ( Exception $exception ) {
				return $exception->getMessage();
			}
		}

		return true;
	}

	/**
	 * Gets the relationship definition
	 *
	 * @param string|bool $relationship_slug
	 *
	 * @return bool|Toolset_Relationship_Definition
	 */
	public function get_definition_by_relationship_slug( $relationship_slug = false ) {
		if ( $relationship_slug !== false
			&& isset( $this->definition[ $relationship_slug ] )
		) {
			return $this->definition[ $relationship_slug ];
		}

		if ( $relationship_slug ) {
			$definition_repository = $this->get_definition_repository();
			$this->definition[ $relationship_slug ] = $definition_repository->get_definition( $relationship_slug );
		} else {
			$association = $this->get_association_by_association_uid();
			if ( ! $association ) {
				return false;
			}
			$this->definition[ $relationship_slug ] = $association->get_definition();
		}

		return $this->definition[ $relationship_slug ];
	}

	/**
	 * Gets the current defined association
	 *
	 * @param int|bool $association_uid
	 *
	 * @return bool|IToolset_Association
	 */
	private function get_association_by_association_uid( $association_uid = false ) {
		if ( $this->current_association ) {
			return $this->current_association;
		}

		if ( ! $association_uid ) {
			return false;
		}

		$query = $this->get_relationships_factory()->association_query();
		$association = $query->add( $query->association_id( $association_uid ) )
		 	->return_association_instances()
			->limit( 1 )
			->get_results();

		if ( empty( $association ) ) {
			return false;
		}

		$this->current_association = $association[ 0 ];

		return $this->current_association;
	}

	/**
	 * Returns the definition repository
	 *
	 * @return Toolset_Relationship_Definition_Repository
	 */
	private function get_definition_repository() {
		if ( null === $this->_definition_repository ) {
			$this->_definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		}

		return $this->_definition_repository;
	}

	/**
	 * Gets the role of the relationship from the post type
	 *
	 * @param IToolset_Relationship_Definition $definition Relationship definition.
	 * @param string $post_type The post type.
	 *
	 * @return string
	 * @throws InvalidArgumentException In case of error.
	 */
	private function get_role_from_post_type( $definition, $post_type ) {
		$parent_type = $definition->get_element_type( Toolset_Relationship_Role::PARENT )->get_types();
		$child_type = $definition->get_element_type( Toolset_Relationship_Role::CHILD )->get_types();

		if ( $post_type === $parent_type[ 0 ] ) {
			return Toolset_Relationship_Role::PARENT;
		} elseif ( $post_type === $child_type[ 0 ] ) {
			return Toolset_Relationship_Role::CHILD;
		} else {
			throw new InvalidArgumentException( __( 'The post type doesn\'t belong to the relationship', 'wp-cred' ) );
		}
	}

	/**
	 * Set current relationship definition object
	 *
	 * @param string $relationship_slug
	 * @param Toolset_Relationship_Definition $definition
	 */
	public function set_toolset_relationship_definition( $relationship_slug, $definition ) {
		$this->definition[ $relationship_slug ] = $definition;
	}


	/**
	 * Given a role the function will return the opposite
	 *
	 * @param Toolset_Relationship_Role $role
	 *
	 * @return string
	 */
	public function get_the_other_role( $role ) {
		return Toolset_Relationship_Role::other( $role );
	}

	/**
	 * Get the association form using the other role
	 *
	 * @param int $id
	 * @param IToolset_Relationship_Definition $definition
	 * @param string $role
	 *
	 * @return array
	 */
	public function get_ruled_association_by_id( $id, $definition, $role ) {
		$the_other_role = $this->get_the_other_role( $role );

		return $this->association->get_association_by_role( $id, $definition, $the_other_role );
	}

	/**
	 * Retrieve relationships array in order to translate Types relationships into cred forms scaffold shortcodes
	 *
	 * @param $post_type_object
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_relationships( $post_type_object ) {
		$relationships = array();

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return $relationships;
		}

		$relationship_query = new Toolset_Relationship_Query_V2();
		do_action( 'toolset_do_m2m_full_init' );

		if (
			property_exists( $post_type_object, Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP )
			&& $post_type_object->{Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP}
		) {
			$relationship_query_result = $relationship_query
				->do_not_add_default_conditions()
				->add( $relationship_query->has_domain_and_type( $post_type_object->name, Toolset_Element_Domain::POSTS ) )
				->add( $relationship_query->is_active( true ) )
				->get_results();
		} else {
			$relationship_query_result = $relationship_query
				->add( $relationship_query->has_domain_and_type( $post_type_object->name, Toolset_Element_Domain::POSTS ) )
				->add( $relationship_query->is_active( true ) )
				->get_results();
		}


		foreach ( $relationship_query_result as $relationship ) {
			$relationship_array = $this->relationship_definition_to_array( $relationship, $post_type_object );
			//TODO: remove this once we want to enable the parent side childs handler
			if ( isset( $relationship_array[ 'cardinality' ] )
				&& isset( $relationship_array[ 'cardinality' ][ $relationship_array[ 'role' ] ] )
				&& $relationship_array[ 'cardinality' ][ $relationship_array[ 'role' ] ][ 'max' ] !== - 1
			) {
				$relationships[ $relationship_array[ 'key' ] ] = $relationship_array;
			}
		}

		return $relationships;
	}

	/**
	 * Map Legacy Parent Fields adding m2m Relationship definition in order to mantain back compatibility with m2m API
	 *
	 * @param array $parents
	 * @param string $child_post_type_object
	 *
	 * @throws Exception
	 */
	public function map_parents_legacy_relationships( &$parents, $child_post_type_object ) {
		$relationship_definition = $this->get_definition_repository();
		foreach ( $parents as &$parent ) {
			$parent_post_type = $parent[ 'data' ][ 'post_type' ];
			$relationship = $relationship_definition->get_legacy_definition( $parent_post_type, $child_post_type_object->name );
			$relationship_array = $this->relationship_definition_to_array( $relationship, $child_post_type_object );
			//Force slug for back compatibility
			$relationship_array[ 'key' ] = $parent[ 'slug' ];
			$parent[ 'relationship' ] = $relationship_array;
		}
	}

	/**
	 * Mapping fields array with post_reference fields
	 *
	 * @param $field
	 * @param $post_type_object
	 *
	 * @return bool
	 */
	public function map_post_reference_fields( &$field, $post_type_object ) {
		// In case some users decide to use the same name for a relationship and another type of field
		if ( $field['type'] !== 'post' ) {
			return false;
		}
		// The relationship of the Post Reference Field
		$relationship_definitions_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$post_reference_field_relationship = $relationship_definitions_repository->get_definition( $field[ 'slug' ] );

		if ( isset( $post_reference_field_relationship ) ) {
			$relationship_array = $this->relationship_definition_to_array( $post_reference_field_relationship, $post_type_object );
			$relationship_array[ 'key' ] = $field[ 'slug' ];
			$field[ 'label' ] = $field[ 'key' ] = $field[ 'slug' ];
			$field[ 'is_relationship' ] = true;
			$field[ 'is_post_reference' ] = true;
			$field[ 'data' ][ 'post_type' ] = $field[ 'data' ][ 'post_reference_type' ];
			$field[ 'role' ] = $relationship_array[ 'role' ];
			$field[ 'relationship' ] = $relationship_array;

			return true;
		}

		return false;
	}

	/**
	 * Transform a relationship definition into a CRED Compatible relationship array
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param string $referrer_post_type_object
	 *
	 * @return array
	 * @throws Exception
	 */
	private function relationship_definition_to_array( $relationship, $referrer_post_type_object ) {
		//Special case in case we have m2m enabled but no migration is done on old types relationship
		if ( ! isset( $relationship ) ) {
			return array();
		}
		if ( ! $relationship instanceof IToolset_Relationship_Definition ) {
			throw new Exception( __( "Wrong Relationship type Definition", 'wp-cred' ) );
		}
		$relationship_array = array(
			'id' => $relationship->get_row_id(),
			'type' => 'relationship',
			'label' => $relationship->get_display_name(),
			'slug' => $relationship->get_slug(),
			'name' => $relationship->get_display_name_singular(),
			'parent' => $relationship->get_parent_type()->get_types(),
			'child' => $relationship->get_child_type()->get_types(),
			'parent_domain' => $relationship->get_parent_domain(),
			'child_domain' => $relationship->get_child_domain(),
			'cardinality' => $relationship->get_cardinality()->get_definition_array(),
			'is_relationship' => true,
		);

		if (
			property_exists( $referrer_post_type_object, Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP )
			&& $referrer_post_type_object->{Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP}
		) {
			$parent_post_types = $relationship->get_parent_type()->get_types();
			$parent_post_type_object = get_post_type_object( $parent_post_types[ 0 ] );
			$relationship_array[ 'label' ] = $parent_post_type_object->labels->name;
			$relationship_array[ 'name' ] = $parent_post_type_object->labels->singular_name;
		}

		$parent_types = $relationship->get_parent_type()->get_types();
		$child_types = $relationship->get_child_type()->get_types();
		//TODO: in the future we should consider multiple post types relations
		$target_role = isset( $parent_types[ 0 ] )
		&& ( $referrer_post_type_object->name == $parent_types[ 0 ] ) ? Toolset_Relationship_Role::CHILD : Toolset_Relationship_Role::PARENT;
		$key = '@' . $relationship->get_slug() . '.' . $target_role;
		$target_post_type = reset( ${"{$target_role}_types"} );
		$relationship_array[ 'role' ] = $target_role;
		$relationship_array[ 'key' ] = $key;
		$relationship_array[ 'data' ] = array( 'post_type' => $target_post_type );
		//TODO: probably we need to change with need to change to the correct description
		$relationship_array[ 'description' ] = "Select {$target_post_type}";
		$relationship_array[ 'required' ] = ( $relationship_array[ 'cardinality' ][ $target_role ][ 'min' ] === 0 ) ? false : true;

		return $relationship_array;
	}

	/**
	 * @param $field_id
	 * @param $role
	 * @param $cardinality
	 * @param $relationship_definition
	 *
	 * @return bool
	 */
	private function can_associate( $field_id, $role, $cardinality, $relationship_definition ) {
		$associations = $this->get_ruled_association_by_id( $field_id, $relationship_definition, $role );

		return $cardinality === - 1 || count( $associations ) < $cardinality;
	}

	/**
	 * @param $role
	 *
	 * @return mixed|null
	 */
	public function get_relationship_role_object( $role ) {
		$roles = array(
			'child' => new Toolset_Relationship_Role_Child(),
			'parent' => new Toolset_Relationship_Role_Parent(),
		);

		return array_key_exists( $role, $roles ) ? $roles[ $role ] : null;
	}


	/**
	 * @return Factory
	 */
	private function get_relationships_factory() {
		if( null === $this->_relationships_factory ) {
			$this->_relationships_factory = new Factory();
		}
		return $this->_relationships_factory;
	}

}
