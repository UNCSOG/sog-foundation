<?php

namespace OTGS\Toolset\Views\Services;

use OTGS\Toolset\Views\ToolsetRelationshipQueryFactory;

/**
 * Handle the Content Selection options data offered in the relevant section of the View creation Wizard.
 */
class ContentSelectionService {
	const RELATIONSHIP_TYPE_O2O = 'o2o';
	const RELATIONSHIP_TYPE_O2M = 'o2m';
	const RELATIONSHIP_TYPE_M2O = 'm2o';
	const RELATIONSHIP_TYPE_M2M = 'm2m';

	const KEY_SLUG = 'slug';
	const KEY_DISPLAY_NAME_SINGULAR = 'displayNameSingular';
	const KEY_DISPLAY_NAME_PLURAL = 'displayNamePlural';

	/** @var \Toolset_Relationship_Query_Factory */
	private $relationship_query_factory;

	/** @var \Toolset_Post_Type_Repository */
	private $toolset_post_type_repository;

	/** @var \Toolset_Post_Type_Query_Factory  */
	private $post_type_query_factory;

	/** @var array */
	private $toolset_relationship_role_all;

	/**
	 * ContentSelectionService constructor.
	 *
	 * @param null|\Toolset_Relationship_Query_Factory $relationship_query_factory
	 * @param null|\Toolset_Post_Type_Query_Factory    $post_type_query_factory
	 * @param null|\Toolset_Post_Type_Repository       $toolset_post_type_repository
	 * @param array                                    $toolset_relationship_role_all
	 */
	public function __construct(
		\Toolset_Relationship_Query_Factory $relationship_query_factory = null,
		\Toolset_Post_Type_Query_Factory $post_type_query_factory = null,
		\Toolset_Post_Type_Repository $toolset_post_type_repository = null,
		array $toolset_relationship_role_all = array()
	) {
		$this->relationship_query_factory = $relationship_query_factory;
		$this->post_type_query_factory = $post_type_query_factory;
		$this->toolset_post_type_repository = $toolset_post_type_repository;
		$this->toolset_relationship_role_all = $toolset_relationship_role_all;
	}

	/**
	 * Gets the Post Type Content Selection options.
	 *
	 * @return array
	 */
	public function get_post_type_options() {
		$post_types = array();
		$post_types_arr = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types_arr as $post_type_object ) {
			$post_type = $this->toolset_post_type_repository->get( $post_type_object->name );

			// Remove post types that are intermediary post types from this set. They will appear in another group.
			if (
				$post_type &&
				$post_type->is_intermediary()
			) {
				continue;
			}

			$post_types[ $post_type_object->name ] = array(
				self::KEY_SLUG => $post_type_object->name,
				self::KEY_DISPLAY_NAME_SINGULAR => $post_type_object->labels->singular_name,
				self::KEY_DISPLAY_NAME_PLURAL => $post_type_object->labels->name,
			);
		}
		return $post_types;
	}

	/**
	 * Gets the Repeatable Field Groups (RFGs) Content Selection options.
	 *
	 * @param null|string $current_post_type
	 *
	 * @return array
	 */
	public function get_rfg_options( $current_post_type = null ) {
		$is_enabled_m2m = apply_filters( 'toolset_is_m2m_enabled', false );
		if ( ! $is_enabled_m2m || ( ! $current_post_type && ! get_post_type() ) ) {
			return array();
		}

		if ( ! $current_post_type ) {
			$current_post_type = get_post_type();
		}

		$relationship_query = $this->relationship_query_factory->relationships_v2();
		$rfg_definitions = $relationship_query
			->add( $relationship_query->has_domain_and_type( $current_post_type, \Toolset_Element_Domain::POSTS ) )
			->add( $relationship_query->origin( \Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD ) )
			->get_results();

		return array_map(
			function( $rfg ) {
				return array(
					self::KEY_SLUG => $rfg->get_slug(),
					self::KEY_DISPLAY_NAME_SINGULAR => $rfg->get_display_name_singular(),
					self::KEY_DISPLAY_NAME_PLURAL => $rfg->get_display_name_plural(),
				);
			},
			$rfg_definitions
		);
	}

	/**
	 * Gets the Related Post Type Content Selection options.
	 *
	 * @param null|string $current_post_type
	 *
	 * @return array
	 *
	 * @todo: Cover this method with proper unit tests. The unit test can be really complex and time is really pressing for now.
	 */
	public function get_related_post_type_options( $current_post_type = null ) {
		$is_enabled_m2m = apply_filters( 'toolset_is_m2m_enabled', false );
		if ( ! $is_enabled_m2m || ( ! $current_post_type && ! get_post_type() ) ) {
			return array(
				'post_types' => array(),
				'intermediary_post_types' => array(),
			);
		}

		if ( ! $current_post_type ) {
			$current_post_type = get_post_type();
		}
		$relationship_query = $this->relationship_query_factory->relationships_v2();
		$relationship_definitions = $relationship_query
			->add(
				$relationship_query->has_domain_and_type(
					$current_post_type,
					\Toolset_Element_Domain::POSTS
				)
			)
			->get_results();

		$related_post_types = array();
		$related_intermediary_post_types = array();

		foreach ( $relationship_definitions as $relationship_definition ) {
			foreach ( $this->toolset_relationship_role_all as $relationship_role ) {
				$related_post_type_slugs = $relationship_definition->get_element_type( $relationship_role )->get_types();
				$relationship_type = $this->get_relationship_type( $relationship_definition->get_cardinality() );
				$relationship_slug = $relationship_definition->get_slug();
				foreach ( $related_post_type_slugs as $related_post_type_slug ) {
					if (
						! $related_post_type_slug ||
						$related_post_type_slug === $current_post_type ||
						self::RELATIONSHIP_TYPE_O2O === $relationship_type ||
						( self::RELATIONSHIP_TYPE_O2M === $relationship_type && \Toolset_Relationship_Role::PARENT === $relationship_role->get_name() ) ||
						( self::RELATIONSHIP_TYPE_M2O === $relationship_type && \Toolset_Relationship_Role::CHILD === $relationship_role->get_name() )
					) {
						continue;
					}

					$related_post_type = get_post_type_object( $related_post_type_slug );
					$post_type = array(
						self::KEY_SLUG => $related_post_type->name,
						self::KEY_DISPLAY_NAME_SINGULAR => $related_post_type->labels->singular_name,
						self::KEY_DISPLAY_NAME_PLURAL => $related_post_type->labels->name,
						'relationshipType' => $relationship_type,
						'relationshipSlug' => $relationship_slug,
					);

					switch ( $relationship_role ) {
						case \Toolset_Relationship_Role::INTERMEDIARY:
							$related_intermediary_post_types[] = $post_type;
							break;
						default:
							$related_post_types[ $related_post_type->name ] = $post_type;
							break;
					}
				}
			}
		}

		return array(
			'post_types' => $related_post_types,
			'intermediary_post_types' => $related_intermediary_post_types,
		);
	}

	/**
	 * Gets the Intermediary Post Type Content Selection options.
	 *
	 * @return \IToolset_Post_Type[]|string[]
	 */
	public function get_intermediary_post_types() {
		$intermediary_post_type_query = $this->post_type_query_factory->create(
			array(
				\Toolset_Post_Type_Query::IS_INTERMEDIARY => true,
			)
		);

		$intermediary_post_types = $intermediary_post_type_query->get_results();

		return array_map(
			function( $post_type ) {
				return array(
					self::KEY_SLUG => $post_type->get_slug(),
					self::KEY_DISPLAY_NAME_SINGULAR => $post_type->get_label( \Toolset_Post_Type_Labels::SINGULAR_NAME ),
					self::KEY_DISPLAY_NAME_PLURAL => $post_type->get_label(),
				);
			},
			$intermediary_post_types
		);
	}

	/**
	 * Gets the post relationship type based on the "Toolset_Relationship_Cardinality" object.
	 *
	 * @param \Toolset_Relationship_Cardinality $relationship_cardinality
	 *
	 * @return null|string
	 */
	private function get_relationship_type( $relationship_cardinality ) {
		if ( $relationship_cardinality->is_one_to_one() ) {
			return self::RELATIONSHIP_TYPE_O2O;
		}

		if ( $relationship_cardinality->is_one_to_many() ) {
			return self::RELATIONSHIP_TYPE_O2M;
		}

		if ( $relationship_cardinality->is_many_to_one() ) {
			return self::RELATIONSHIP_TYPE_M2O;
		}

		if ( $relationship_cardinality->is_many_to_many() ) {
			return self::RELATIONSHIP_TYPE_M2M;
		}

		return null;
	}
}
