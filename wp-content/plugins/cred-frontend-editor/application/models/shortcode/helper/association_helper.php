<?php

use OTGS\Toolset\Common\Relationships\API\Factory;

/**
 * Class CRED_Shortcode_Association_Helper
 *
 * @since m2m
 */
class CRED_Shortcode_Association_Helper implements CRED_Shortcode_Helper_Interface {

	const ROLE_PARENT = 'parent';

	const ROLE_CHILD = 'child';

	const PARENT_SHORTCODE_ATTRIBUTE = 'parent_item';

	const CHILD_SHORTCODE_ATTRIBUTE = 'child_item';

	const PARENT_URL_PARAMETER = 'cred_parent_id';

	const CHILD_URL_PARAMETER = 'cred_child_id';

	const ACTION_URL_PARAMETER = 'cred_action';

	const ACTION_URL_NEW_PARENT = 'assign_new_parent';

	const ACTION_URL_NEW_CHILD = 'assign_new_child';

	const ACTION_URL_EDIT = 'edit_current';

	/**
	 * @var CRED_Frontend_Form_Flow
	 */
	private $frontend_form_flow;

	/**
	 * @var Toolset_Relationship_Service
	 */
	private $relationship_service;

	/**
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	private $attr_item_chain;

	/**
	 * @var int
	 */
	private $form_id;


	/** @var Factory|null We may not be able to set this via constructor because the class is needed too early. */
	private $_relationships_factory;


	/**
	 * @param CRED_Frontend_Form_Flow $frontend_form_flow
	 * @param Toolset_Relationship_Service $relationship_service
	 * @param Toolset_Shortcode_Attr_Interface $attr_item_chain
	 * @param Factory $relationships_factory
	 */
	public function __construct(
		CRED_Frontend_Form_Flow $frontend_form_flow,
		Toolset_Relationship_Service $relationship_service,
		Toolset_Shortcode_Attr_Interface $attr_item_chain,
		Factory $relationships_factory = null
	) {
		$this->frontend_form_flow = $frontend_form_flow;
		$this->relationship_service = $relationship_service;
		$this->attr_item_chain = $attr_item_chain;
		$this->_relationships_factory = $relationships_factory;
		$this->add_hooks();
	}


	protected function add_hooks() {

	}


	/**
	 * @return CRED_Frontend_Form_Flow
	 *
	 * @since m2m
	 */
	public function get_frontend_form_flow() {
		return $this->frontend_form_flow;
	}


	/**
	 * @return Toolset_Relationship_Service
	 *
	 * @since m2m
	 */
	public function get_relationship_service() {
		return $this->relationship_service;
	}


	/**
	 * Get the current parent ID.
	 *
	 * Priority:
	 * - shortcode attribute
	 * - URL parameter
	 *
	 * @return null|int
	 *
	 * @since m2m
	 */
	public function get_current_parent_id() {
		return $this->get_current_element_id( $this->get_relationships_factory()->role_parent() );
	}


	public function get_current_element_id( \OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild $role ) {
		$element_shortcode_attribute = $role->get_name() === \Toolset_Relationship_Role::PARENT
			? self::PARENT_SHORTCODE_ATTRIBUTE
			: self::CHILD_SHORTCODE_ATTRIBUTE;
		$action_new_opposite_role_element = $role->get_name() === \Toolset_Relationship_Role::PARENT
			? self::ACTION_URL_NEW_CHILD
			: self::ACTION_URL_NEW_PARENT;
		$element_url_parameter = $role->get_name() === \Toolset_Relationship_Role::PARENT
			? self::PARENT_URL_PARAMETER
			: self::CHILD_URL_PARAMETER;

		$form_attributes = $this->get_frontend_form_flow()->get_current_form_attributes();

		if (
			$form_attributes
			&& ! empty( $form_attributes[ $element_shortcode_attribute ] )
		) {
			return $this->get_current_item_id( $form_attributes[ $element_shortcode_attribute ] );
		}


		if (
			isset( $_GET[ self::ACTION_URL_PARAMETER ] )
			&& $action_new_opposite_role_element == $_GET[ self::ACTION_URL_PARAMETER ]
		) {
			global $post;

			return $post->ID;
		}

		if (
			isset( $_GET[ self::ACTION_URL_PARAMETER ] )
			&& self::ACTION_URL_EDIT == $_GET[ self::ACTION_URL_PARAMETER ]
		) {
			$association = $this->get_current_association_object();
			if ( $association instanceof Toolset_Association ) {
				try {
					return $association->get_element( $role->get_name() )->get_id();
				} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
					return null;
				}
			}

			return null;
		}

		return isset( $_GET[ $element_url_parameter ] )
			? (int) $_GET[ $element_url_parameter ]
			: null;
	}


	public function set_form_id( $id ) {
		$this->form_id = $id;
	}


	public function get_form_id() {
		return $this->form_id;
	}


	/**
	 * Get the current child ID.
	 *
	 * Priority:
	 * - shortcode attribute
	 * - URL parameter
	 *
	 * @return null|int
	 *
	 * @since m2m
	 */
	public function get_current_child_id() {
		return $this->get_current_element_id( $this->get_relationships_factory()->role_child() );
	}


	public function get_current_item_id( $attribute_value = '' ) {
		$result_value = $attribute_value;
		switch ( $attribute_value ) {
			case '$current':
				global $post;
				if ( $post instanceof WP_Post ) {
					$result_value = $post->ID;
				}
				break;
			/*
			case '$fromfilter':
				$result_value = $this->get_role_id_from_filter();
				break;
			*/
			default:
				global $post;
				$result_value = $this->attr_item_chain->get( array( 'item' => $attribute_value ) );
				if (
					$result_value != $attribute_value
					&& $result_value == $post->ID
				) {
					$result_value = '';
				}
				break;
		}

		return $result_value;
	}


	/**
	 * @return bool
	 */
	public function is_parent_fixed() {
		return null !== $this->get_current_parent_id();
	}


	/**
	 * @return bool
	 */
	public function is_child_fixed() {
		return null !== $this->get_current_child_id();
	}


	/**
	 * Get the current relationship, as set in the current association form settings..
	 *
	 * @return null|string
	 *
	 * @since m2m
	 */
	public function get_current_relationship() {

		if ( $this->get_form_id() ) {
			$current_form_id = $this->get_form_id();
		} else {
			$current_form_id = $this->get_frontend_form_flow()->get_current_form_id();
		}

		if ( ! $current_form_id ) {
			return null;
		}

		$relationship = get_post_meta( $current_form_id, 'relationship', true );

		return empty( $relationship )
			? null
			: $relationship;
	}


	/**
	 * Get the current association post, as set in the URL parameter or as the current post.
	 *
	 * @return null|Toolset_Association
	 *
	 * @since m2m
	 */
	public function get_current_association_object() {
		if ( ! $relationship_slug = $this->get_current_relationship() ) {
			return null;
		}

		$association = array();

		if (
			isset( $_GET[ self::ACTION_URL_PARAMETER ] )
			&& self::ACTION_URL_EDIT === $_GET[ self::ACTION_URL_PARAMETER ]
		) {

			global $post;

			$query = $this->get_relationships_factory()->association_query();
			$association = $query->add( $query->relationship_slug( $relationship_slug ) )
				->add( $query->element_id_and_domain(
					$post->ID, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Intermediary()
				) )
				->limit( 1 )
				->get_results();

		} elseif (
			( $parent = $this->get_current_parent_id() )
			&& ( $child = $this->get_current_child_id() )
		) {
			$query = $this->get_relationships_factory()->association_query();
			$association = $query->add( $query->relationship_slug( $relationship_slug ) )
				->add( $query->element_id_and_domain(
					$parent, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Parent()
				) )
				->add( $query->element_id_and_domain(
					$child, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child()
				) )
				->limit( 1 )
				->get_results();
		}

		if ( ! empty( $association ) ) {
			return $association[0];
		}

		return null;

	}


	/**
	 * Get the current association post, as set in the URL parameter or as the current post.
	 *
	 * @return null|IToolset_Post|IToolset_Element
	 *
	 * @since m2m
	 */
	public function get_current_association() {
		$association = $this->get_current_association_object();

		if ( $association instanceof Toolset_Association ) {
			try {
				return $association->get_element( $this->get_relationships_factory()->role_intermediary() );
			} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
				return null;
			}
		}

		return null;

	}


	/**
	 * @param $role
	 *
	 * @return mixed|null
	 */
	public function get_relationship_role_object( $role ) {
		$roles = array(
			self::ROLE_CHILD => $this->get_toolset_relationhip_role_child(),
			self::ROLE_PARENT => $this->get_toolset_relationhip_role_parent(),
		);

		return array_key_exists( $role, $roles ) ? $roles[ $role ] : null;
	}


	/**
	 * @return Toolset_Relationship_Role_Child
	 */
	public function get_toolset_relationhip_role_child() {
		return new Toolset_Relationship_Role_Child();
	}


	/**
	 * @return Toolset_Relationship_Role_Parent
	 */
	public function get_toolset_relationhip_role_parent() {
		return new Toolset_Relationship_Role_Parent();
	}


	/**
	 * @return null|string
	 */
	public function get_relationship_other_end_role_index() {

		if ( $this->is_child_fixed() ) {
			return self::ROLE_PARENT;
		} elseif ( $this->is_parent_fixed() ) {
			return self::ROLE_CHILD;
		}

		return null;
	}


	/**
	 * @param int $connect_to
	 * @param array $args
	 *
	 * @return \OTGS\Toolset\Common\Relationships\API\PotentialAssociationQuery|null
	 */
	public function get_query_potential_association_query( $connect_to = 0, $args = array() ) {
		if ( $connect_to === 0 ) {
			return null;
		}

		$connect_to = $this->get_connect_to_as_object( $connect_to );

		if ( null === $connect_to ) {
			return null;
		}

		$role_index = isset( $args['current_role'] )
			? $args['current_role']
			: $this->get_relationship_other_end_role_index();

		if ( null === $role_index ) {
			return null;
		}

		$role_object = $this->get_relationship_role_object( $role_index );

		if ( null === $role_object ) {
			return null;
		}

		$relationship = $this->get_current_relationship();

		if ( null === $relationship ) {
			return null;
		}

		$relationship_definition = $this->get_relationship_definition( $relationship );

		if ( ! $relationship_definition ) {
			return null;
		}

		if ( isset( $args['other_current_role'] ) ) {
			unset( $args['other_current_role'] );
		}

		if ( isset( $args['current_role'] ) ) {
			unset( $args['current_role'] );
		}

		// On the front-end, we shall respect the post type translatability mode. This means,
		// we won't offer default-language posts that don't have a version in the current language
		// if their post type is set to the "show only translated items" mode.
		$args['force_display_as_translated'] = false;

		// prevents fatal error when $connect_to has a post_type different from the one expected from the query
		try {
			$query = $this->get_relationships_factory()->potential_association_query(
				$relationship_definition,
				$role_object, // role
				$connect_to, // the known end $id
				$args // use this to search results by title or whatnot - depends of their domain
			);
		} catch ( InvalidArgumentException $e ) {
			/** @noinspection ForgottenDebugOutputInspection */
			error_log( $e->getMessage() );

			return null;
		}


		return $query;

	}


	/**
	 * @return int|null
	 */
	public function get_root_connection() {
		if ( $this->is_parent_fixed() ) {
			return $this->get_current_parent_id();
		} elseif ( $this->is_child_fixed() ) {
			return $this->get_current_child_id();
		}

		return null;
	}


	/**
	 * @param null $connect
	 * @param array $args
	 *
	 * @return IToolset_Element[]
	 */
	public function get_potential_associations( $connect = null, $args = array() ) {
		$connect_to = $connect ? : $this->get_root_connection();

		if ( null === $connect_to ) {
			return null;
		}

		$query = $this->get_query_potential_association_query( $connect_to, $args );

		if ( null === $query ) {
			return array();
		}

		return $query->get_results();
	}


	/**
	 * @return bool
	 */
	public function is_edit_form_request() {
		return $this->is_parent_fixed() || $this->is_child_fixed();
	}


	public function get_relationship_definition( $relationship_slug ) {
		return \Toolset_Relationship_Definition_Repository::get_instance()->get_definition( $relationship_slug );
	}


	public function get_connect_to_as_object( $post_id_or_post_object = null ) {
		$element = Toolset_Element::get_instance( 'posts', $post_id_or_post_object );

		if ( ! $element ) {
			return null;
		}

		return $element;
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
