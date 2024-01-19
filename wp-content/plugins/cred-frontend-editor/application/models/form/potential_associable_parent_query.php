<?php

/**
 * Relationship Association Parents Query Handler
 *
 * @since 2.0
 */
class CRED_Form_Potential_Associable_Parent_Query {


	/** @var \OTGS\Toolset\Common\Relationships\API\Factory */
	private $relationships_factory;


	/**
	 * CRED_Form_Potential_Associable_Parent_Query constructor.
	 *
	 * @param \OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory
	 */
	public function __construct( \OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory ) {
		$this->relationships_factory = $relationships_factory;
	}


	/**
	 * Retrieve only associable to $connect_to item, role parents/child elements
	 *
	 * @param IToolset_Relationship_Definition $relationship_definition base relationship definition of form field
	 * @param \OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild $role_object role of items to search
	 * @param IToolset_Element $connect_to element to connect to
	 * @param array $args Additional query arguments:
	 *     - search_string: string
	 *     - count_results: bool
	 *     - items_per_page: int
	 *     - page: int
	 *     - wp_query_override: array
	 * @param bool $check_distinct_relationships See PotentialAssociationQuery::get_results() for explanation.
	 *
	 * @return IToolset_Element[]|null
	 */
	public function get_potential_associable_parent_result(
		IToolset_Relationship_Definition $relationship_definition,
		\OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild $role_object,
		IToolset_Element $connect_to,
		$args = [],
		$check_distinct_relationships = true
	) {
		// On the front-end, we shall respect the post type translatability mode. This means,
		// we won't offer default-language posts that don't have a version in the current language
		// if their post type is set to the "show only translated items" mode.
		$args['force_display_as_translated'] = false;

		try {
			$query = $this->relationships_factory->potential_association_query(
				$relationship_definition,
				$role_object, // role
				$connect_to, // the known end $id
				$args // use this to search results by title or whatnot - depends of their domain
			);

			// Using parameter false in order to overwrite the element if exists
			// because in Toolset Form behavior we do not delete before replacing relationship element

			/*
			 TODO: use 'false' only when we are in relationship form 1 => many case
			 */
			return $query->get_results(false, $check_distinct_relationships );
		} catch ( InvalidArgumentException $e ) {
			/** @noinspection ForgottenDebugOutputInspection */
			error_log( $e->getMessage() );

			return null;
		}
	}
}
