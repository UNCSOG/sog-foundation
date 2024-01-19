<?php

use OTGS\Toolset\Common\Relationships\API\Factory;

/**
 * Class relationship association content data handler
 *
 * @since m2m
 */
class CRED_Form_Association {


	/**
	 * @var Factory|null This class may be instantiated even when relationships are not enabled.
	 * Use $this->get_relationships_factory().
	 */
	private $_relationships_factory;

	private static $instance;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * CRED_Form_Association constructor.
	 *
	 * @param Factory $relationships_factory
	 */
	public function __construct( Factory $relationships_factory = null ) {
		$this->_relationships_factory = $relationships_factory;
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


	/**
	 * @param int $object_id
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @param string $role_name
	 * @param int $limit
	 *
	 * @return IToolset_Association[]
	 */
	public function get_associations( $object_id, $relationship_definition, $role_name = Toolset_Relationship_Role::CHILD, $limit = 1 ) {
		$role = \Toolset_Relationship_Role::role_from_name( $role_name );
		$query = $this->get_relationships_factory()->association_query();
		return $query->add( $query->relationship( $relationship_definition ) )
			->add( $query->element_id_and_domain(
				$object_id, $relationship_definition->get_element_type( $role )->get_domain(), $role
			) )
			->limit( $limit )
			->return_association_instances()
			->get_results();
	}

	/**
	 * @param IToolset_Association $association
	 * @param string $role
	 *
	 * @return int
	 */
	public function get_associated_object_id_by_role( $association, $role ) {
		return $association->get_element( \Toolset_Relationship_Role::role_from_name( $role ) )->get_id();
	}

	/**
	 * Get associations by a given item, relationship definition, and role.
	 *
	 * @param int $id
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @param string $relationship_role_name
	 * @param int $limit
	 *
	 * @return array
	 *
	 * @note Disable the associations query cache because sometimes this needs to run while rendering a form after saving it,
	 *       and right now the saving mechanism prints, saves, and prints again, hence the cache holds the previous value.
	 */
	public function get_association_by_role( $id, $relationship_definition, $relationship_role_name, $limit = 1 ) {
		$element_role = \Toolset_Relationship_Role::role_from_name( $relationship_role_name );

		$query = $this->get_relationships_factory()->association_query();
		$results = $query
			->use_cache( false )
			->add( $query->relationship( $relationship_definition ) )
			->add( $query->element_id_and_domain( $id,
				$relationship_definition->get_element_type( $element_role )->get_domain(),
				$element_role
			) )
			->limit( $limit )
			->get_results();

		return $this->get_related_content_data( $results, Toolset_Relationship_Role::other( $relationship_role_name ) );
	}

	/**
	 * Get related posts data from array of associations
	 *
	 * @param IToolset_Association[] $associations Array of related content.
	 *
	 * @return array
	 */
	public function get_related_content_data( $associations, $role ) {
		$related_posts = array();

		foreach ( $associations as $association ) {

			// The related post.
			try {
				$post = $association->get_element( \Toolset_Relationship_Role::role_from_name( $role ) );
				$fields = $association->get_fields();
				$uid = $association->get_uid();
			} catch ( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
				// An element was supposed to be in the database but it's missing. We're going to
				// report a data integrity issue and skip it.
				do_action(
					'toolset_report_m2m_integrity_issue',
					new Toolset_Relationship_Database_Issue_Missing_Element(
						$e->get_domain(),
						$e->get_element_id()
					)
				);

				continue;
			}
			$related_posts[] = array(
				'uid' => $uid,
				'role' => $role,
				'post' => $post,
				'fields' => $fields,
				'has_intermediary_fields' => ( $fields && count( $fields ) > 0 ),
			);
		}

		return $related_posts;
	}


	/**
	 * @param IToolset_Association $association
	 *
	 * @return Toolset_Result
	 */
	public function delete( $association ) {
		return $association->get_driver()->delete_association( $association );
	}
}
