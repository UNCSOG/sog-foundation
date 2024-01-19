<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Form\Link;

/**
 * Association edit link class.
 *
 * @since m2m
 */
class Association
	extends Base
	implements \CRED_Shortcode_Interface, \CRED_Shortcode_Interface_Conditional {

	const SHORTCODE_NAME = 'cred-relationship-form-link';

	/**
     * @var \Toolset_Condition_Plugin_Views_Active
     */
    private $views_condition;

    /**
     * @var \Toolset_Condition_Plugin_Layouts_Active
     */
    private $layouts_condition;

    /**
	 * @param \Toolset_Shortcode_Attr_Interface $item
     * @param \Toolset_Condition_Plugin_Views_Active $di_views_condition
     * @param \Toolset_Condition_Plugin_Layouts_Active $di_layouts_condition
	 */
	public function __construct(
        \Toolset_Shortcode_Attr_Interface $item,
        \Toolset_Condition_Plugin_Views_Active $di_views_condition = null,
        \Toolset_Condition_Plugin_Layouts_Active $di_layouts_condition = null
    ) {
        parent::__construct( $item );
        $this->views_condition = ( $di_views_condition instanceof \Toolset_Condition_Plugin_Views_Active )
            ? $di_views_condition
            : new \Toolset_Condition_Plugin_Views_Active();
        $this->layouts_condition = ( $di_layouts_condition instanceof \Toolset_Condition_Plugin_Layouts_Active )
            ? $di_layouts_condition
            : new \Toolset_Condition_Plugin_Layouts_Active();
	}

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'content_template_slug' => '',
		'layout_slug' => '',
		'target'    => 'self',
		'style'     => '',
		'class'     => '',
		'form' => '',
		'role_items'   => '',
		'parent_item' => '',
		'child_item'  => ''
	);

	/**
	 * @return bool
	 *
	 * @since m2m
	 */
	public function condition_is_met() {
		return (
			apply_filters( 'toolset_is_m2m_enabled', false )
			&& (
				$this->views_condition->is_met()
				|| $this->layouts_condition->is_met()
			)
		);
	}

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	*
	* @return string
	*
	* @since m2m
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if (
			(
				empty( $this->user_atts['content_template_slug'] )
				&& empty( $this->user_atts['layout_slug'] )
			)
			|| empty( $this->user_atts['form'] )
			|| (
				empty( $this->user_atts['parent_item'] )
				&& empty( $this->user_atts['child_item'] )
				&& empty( $this->user_atts['role_items'] )
			)
		) {
			// Linking to nothing: no add new X to Y, no edit current association, no CT
			return '';
		}

		$link_attributes = array();
		$link = $this->get_link_href();

		if ( is_wp_error( $link ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				return $link->get_error_message();
			}
			return '';
		}

		if ( null === $link ) {
			return '';
		}

		if ( ! empty( $this->user_atts['layout_slug'] ) ) {
			$layout_id = apply_filters( 'ddl-get_layout_id_by_slug', 0, $this->user_atts['layout_slug'] );
			if ( $layout_id ) {
				$link_attributes['layout_id'] = $layout_id;
			} else {
				return;
			}
		} else if ( ! empty( $this->user_atts['content_template_slug'] ) ) {
			$ct_id = apply_filters( 'wpv_get_template_id_by_name', 0, $this->user_atts['content_template_slug'] );
			if ( $ct_id ) {
				$link_attributes['content-template-id'] = $ct_id;
			} else {
				return '';
			}
		}

		$link = add_query_arg( $link_attributes, $link );

		$this->classnames = empty( $this->user_atts['class'] )
			? array()
			: explode( ' ', $this->user_atts['class'] );

		$this->classnames[] = 'cred-edit-relationship';

		$this->attributes = array(
			'class' => $this->classnames,
			'style' => $this->user_atts['style'],
			'href'  => $link,
			'target' => in_array( $this->user_atts['target'], array( 'top', 'blank' ) ) ? ( '_' . $this->user_atts['target'] ) : ''
		);

		if ( empty( $this->attributes['href'] ) ) {
			return '';
		}

		$this->user_content = do_shortcode( $this->user_content );

		return $this->craft_link_output();
	}

	/**
	 * Check whether the shortcome points to the current association.
	 *
	 * @return bool
	 *
	 * @since m2m
	 */
	private function can_link_to_current_association() {
		return ( ! empty( $this->user_atts['role_items'] ) );
	}

	/**
	 * Check whether the shortcome points to an association by setting the involved items.
	 *
	 * @return bool
	 *
	 * @since m2m
	 */
	private function can_link_to_association_by_roles() {
		return ( ! empty( $this->user_atts['parent_item'] ) && ! empty( $this->user_atts['child_item'] ) );
	}

	/**
	 * Get the link target basic URL.
	 *
	 * @return string|WP_Error|null
	 *
	 * @since m2m
	 */
	private function get_link_href() {
		if ( $this->can_link_to_current_association() ) {
			return $this->get_current_association_url();
		}

		if ( $this->can_link_to_association_by_roles() ) {
			return $this->get_association_url_by_roles();
		}

		return $this->get_available_role_url();
	}

	/**
	 * Get the current form object.
	 *
	 * @return WP_Post|null
	 *
	 * @since m2m
	 */
	protected function get_object_form() {
		return cred_get_object_form( $this->user_atts['form'], \CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE );
	}

	/**
	 * Get the link target basic URL based on the current association.
	 *
	 * @return string|WP_Error
	 *
	 * @since m2m
	 */
	private function get_current_association_url() {
		$form_object = $this->get_object_form();

		if ( ! $form_object instanceof \WP_Post ) {
			return new \WP_Error(
				'missing-relationship-form',
				__( 'This relationship form no longer exists', 'wp-cred' )
			);
		}

		if ( 'publish' != $form_object->post_status ) {
			return new \WP_Error(
				'unpublished-relationship-form',
				__( 'This relationship form is not published', 'wp-cred' )
			);
		}

		$relationship_slug = get_post_meta( $form_object->ID, 'relationship', true );

		$related_item_one = $this->get_role_id( '$fromfilter' );
		$related_item_two = $this->get_role_id( '$current' );


		if (
			empty( $related_item_one )
			|| empty( $related_item_two )
		) {
			return new \WP_Error(
				'missing-association-items',
				__( 'The items in the conection are not well defined', 'wp-cred' )
			);
		}

		$relationship_repository = \Toolset_Relationship_Definition_Repository::get_instance();
		$relationship_definition = $relationship_repository->get_definition( $relationship_slug );

		if ( ! $relationship_definition instanceof \Toolset_Relationship_Definition ) {
			return new \WP_Error(
				'missing-relationship',
				__( 'This relationship form refers to a relationship that no longer exists', 'wp-cred' )
			);
		}

		if ( ! $relationship_definition->is_active() ) {
			return new \WP_Error(
				'missing-relationship',
				__( 'This relationship form refers to a relationship that is not active', 'wp-cred' )
			);
		}

		$association_query = new \Toolset_Association_Query_V2();
		$association_query->add( $association_query->relationship_slug( $relationship_slug ) );

		$condition_one = $association_query->do_and(
			$association_query->element_id_and_domain( $related_item_one, \Toolset_Element_Domain::POSTS, new \Toolset_Relationship_Role_Parent() ),
			$association_query->element_id_and_domain( $related_item_two, \Toolset_Element_Domain::POSTS, new \Toolset_Relationship_Role_Child() )
		);
		$condition_two = $association_query->do_and(
			$association_query->element_id_and_domain( $related_item_two, \Toolset_Element_Domain::POSTS, new \Toolset_Relationship_Role_Parent() ),
			$association_query->element_id_and_domain( $related_item_one, \Toolset_Element_Domain::POSTS, new \Toolset_Relationship_Role_Child() )
		);

		$association_query->add( $association_query->do_or( $condition_one, $condition_two ) );
		$association_query->limit( 1 );

		$associations = $association_query->get_results();


		if ( ! count( $associations ) ) {
			return new \WP_Error(
				'missing-associatio',
				__( 'The items in the conection are not actually connected', 'wp-cred' )
			);
		}

		// Link to the association single frontend page with the right CT upon it, and make sure
		// we pass the parent and child IDs in URL parameters. It is *needed*

		return $this->craft_association_url( $associations[0] );
	}

	/**
	 * Get the link target basic URL based on an association set by the involved items.
	 *
	 * @return string|WP_Error
	 *
	 * @since m2m
	 */
	private function get_association_url_by_roles() {
		// Link to the vailable role with the right URL parameter on it
		$form_object = $this->get_object_form();

		if ( ! $form_object instanceof \WP_Post ) {
			return new \WP_Error(
				'missing-relationship-form',
				__( 'This relationship form no longer exists', 'wp-cred' )
			);
		}

		if ( 'publish' != $form_object->post_status ) {
			return new \WP_Error(
				'unpublished-relationship-form',
				__( 'This relationship form is not published', 'wp-cred' )
			);
		}

		$relationship_slug = get_post_meta( $form_object->ID, 'relationship', true );

		$related_parent = $this->get_role_id( $this->user_atts['parent_item'] );
		$related_child = $this->get_role_id( $this->user_atts['child_item'] );

		if (
			empty( $related_parent )
			|| empty( $related_child )
		) {
			return new \WP_Error(
				'missing-association-items',
				__( 'The items in the conection are not well defined', 'wp-cred' )
			);
		}

		$relationship_repository = \Toolset_Relationship_Definition_Repository::get_instance();
		$relationship_definition = $relationship_repository->get_definition( $relationship_slug );

		if ( ! $relationship_definition instanceof \Toolset_Relationship_Definition ) {
			return new \WP_Error(
				'missing-relationship',
				__( 'This relationship form refers to a relationship that no longer exists', 'wp-cred' )
			);
		}

		if ( ! $relationship_definition->is_active() ) {
			return new \WP_Error(
				'missing-relationship',
				__( 'This relationship form refers to a relationship that is not active', 'wp-cred' )
			);
		}

		$association_query = new \Toolset_Association_Query_V2();
		$association_query->add( $association_query->relationship_slug( $relationship_slug ) );

		$condition = $association_query->do_and(
			$association_query->element_id_and_domain( $related_parent, \Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Parent() ),
			$association_query->element_id_and_domain( $related_child, \Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child() )
		);

		$association_query->add( $condition );
		$association_query->limit( 1 );

		$associations = $association_query->get_results();

		if ( ! count( $associations ) ) {
			return new \WP_Error(
				'missing-associatio',
				__( 'The items in the conection are not actually connected', 'wp-cred' )
			);
		}

		return $this->craft_association_url( $associations[0] );
	}

	/**
	 * Get the link target basic URL based on an association pbject.
	 *
	 * @param IToolset_Association $association Current association.
	 * @return string
	 *
	 * @since m2m
	 */
	private function craft_association_url( $association ) {
		$intermediary_post = $association->get_element( new \Toolset_Relationship_Role_Intermediary() );

		if ( null === $intermediary_post ) {
			return new \WP_Error( 'editing_relationship_form_ipt_not_existing', __( 'The Relationship\'s Intermediary Post does not exist', 'wp-cred' ) );
		}

		if ( false === $intermediary_post instanceof \IToolset_Post ) {
			return new \WP_Error( 'editing_relationship_form_ipt_not_existing', __( 'The Relationship\'s Intermediary Post does not exist', 'wp-cred' ) );
		}

		$intermediary_post_type = get_post_type_object( $intermediary_post->get_type() );

		if ( null === $intermediary_post_type ) {
			return new \WP_Error( 'editing_relationship_form_ipt_not_existing', __( 'The Relationship\'s Intermediary Post Type does not exist', 'wp-cred' ) );
		}

		// Editing Relationship Forms need IPT to be publicly queryable. If don't return an error.
		if ( ! $intermediary_post_type->publicly_queryable ) {
			// translators: A relationship has a post type (named intermediary) that has to be public for the users.
			return new \WP_Error( 'editing_relationship_form_ipt_not_public', __( 'The Relationship\'s Intermediary Post Type must be public in order to edit the association', 'wp-cred' ) );
		}

		$association_url = get_permalink( $intermediary_post->get_id() );

		return add_query_arg(
			array(
				\CRED_Shortcode_Association_Helper::ACTION_URL_PARAMETER => \CRED_Shortcode_Association_Helper::ACTION_URL_EDIT
			),
			$association_url
		);
	}

	/**
	 * Get the link target basic URL based on an item part of the relationship.
	 *
	 * @return string|WP_Error|null
	 *
	 * @since m2m
	 */
	private function get_available_role_url() {
		// Link to the vailable role with the right URL parameter on it
		$form_object = $this->get_object_form();

		if ( ! $form_object instanceof \WP_Post ) {
			return new \WP_Error(
				'missing-relationship-form',
				__( 'This relationship form no longer exists', 'wp-cred' )
			);
		}

		if ( 'publish' != $form_object->post_status ) {
			return new \WP_Error(
				'unpublished-relationship-form',
				__( 'This relationship form is not published', 'wp-cred' )
			);
		}

		$relationship_slug = get_post_meta( $form_object->ID, 'relationship', true );
		$relationship_repository = \Toolset_Relationship_Definition_Repository::get_instance();
		$relationship_definition = $relationship_repository->get_definition( $relationship_slug );

		if ( ! $relationship_definition instanceof \Toolset_Relationship_Definition ) {
			return new \WP_Error(
				'missing-relationship',
				__( 'This relationship form refers to a relationship that no longer exists', 'wp-cred' )
			);
		}

		if ( ! $relationship_definition->is_active() ) {
			return new \WP_Error(
				'missing-relationship',
				__( 'This relationship form refers to a relationship that is not active', 'wp-cred' )
			);
		}

		if ( ! empty( $this->user_atts['parent_item'] ) ) {
			$role_id = $this->get_role_id( $this->user_atts['parent_item'] );
			if ( ! $role_id ) {
				return new \WP_Error(
					'missing-association-item',
					__( 'The item to connect items to is not well defined', 'wp-cred' )
				);
			}
			$role_type = get_post_type( $role_id );
			$relationship_definition_parent = $relationship_definition->get_parent_type()->get_types();
			if (
				! $role_type
				|| ! in_array( $role_type, $relationship_definition_parent )
			) {
				return new \WP_Error(
					'wrong-association-item-type',
					__( 'The item to connect items to can not be connected with this relationship', 'wp-cred' )
				);
			}
			$role_url = get_permalink( $role_id );
			return add_query_arg(
				array(
					\CRED_Shortcode_Association_Helper::ACTION_URL_PARAMETER => \CRED_Shortcode_Association_Helper::ACTION_URL_NEW_CHILD
				),
				$role_url
			);
		}

		if ( ! empty( $this->user_atts['child_item'] ) ) {
			$role_id = $this->get_role_id( $this->user_atts['child_item'] );
			if ( ! $role_id ) {
				return new \WP_Error(
					'missing-association-item',
					__( 'The item to connect items to is not well defined', 'wp-cred' )
				);
			}
			$role_type = get_post_type( $role_id );
			$relationship_definition_child = $relationship_definition->get_child_type()->get_types();
			if (
				! $role_type
				|| ! in_array( $role_type, $relationship_definition_child )
			) {
				return new \WP_Error(
					'wrong-association-item-type',
					__( 'The item to connect items to can not be connected with this relationship', 'wp-cred' )
				);
			}
			$role_url = get_permalink( $role_id );
			return add_query_arg(
				array(
					\CRED_Shortcode_Association_Helper::ACTION_URL_PARAMETER => \CRED_Shortcode_Association_Helper::ACTION_URL_NEW_PARENT
				),
				$role_url
			);
		}

		return null;
	}

	/**
	 * Resolve a given role ID by the attribute value.
	 *
	 * @param $source string
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_role_id( $source = '' ) {
		$result_value = $this->item->get( array( 'item' => $source ) );
		return $result_value;
	}

}
