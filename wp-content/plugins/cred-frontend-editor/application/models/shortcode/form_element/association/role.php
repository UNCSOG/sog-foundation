<?php

/**
 * Class CRED_Shortcode_Association_Role
 *
 * @since m2m
 */
class CRED_Shortcode_Association_Role extends CRED_Shortcode_Association_Base implements CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-relationship-role';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'role'  => '',
		'orderby' => 'ID',
		'order' => 'DESC',
		'author' => '',
		'class' => '', // classnames
		'style' => '' // extra inline styles
	);

	/**
	 * @var string|null
	 */
	private $user_content;

	/**
	 * @var array
	 */
	private $user_atts;

	/**
	 * @var string|null
	 */
	private $relationship;

	/**
	 * @var string|null
	 */
	private $role;

	/**
	 * @var array
	 */
	private $classnames;

	/**
	 * @var array
	 */
	private $enlimbo_args;

	public function __construct( CRED_Shortcode_Association_Helper $helper ) {
		parent::__construct( $helper );
	}

	/**
	 * Get current role object ID, if any, based on the role attribute.
	 *
	 * @param $role
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_current_role_id( $role ) {
		switch ( $role ) {
			case Toolset_Relationship_Role::PARENT:
				return $this->helper->get_current_parent_id();
			case Toolset_Relationship_Role::CHILD:
				return $this->helper->get_current_child_id();
			default:
				break;
		}
		return null;
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
		$this->enlimbo_args = array();

		if ( empty( $this->user_atts['role'] ) ) {
			return;
		}

		$this->role = $this->user_atts['role'];
		$this->relationship = $this->helper->get_current_relationship();
		$this->classnames = empty( $this->user_atts['class'] )
			? array()
			: explode( ' ', $this->user_atts['class'] );

		if ( ! $this->relationship ) {
			return;
		}

		$request_relationship = $this->get_relationship_service()->find_by_string( $this->relationship );

		if ( ! $request_relationship instanceof Toolset_Relationship_Definition ) {
			return;
		};

		$this->maybe_set_sorting();

		$this->maybe_set_filter_by_author();

		switch( $this->role ) {
			case Toolset_Relationship_Role::PARENT:
				return $this->get_frontend_control( $this->get_parent_post_type( $request_relationship ), Toolset_Relationship_Role::PARENT );
			case Toolset_Relationship_Role::CHILD:
				return $this->get_frontend_control( $this->get_child_post_type( $request_relationship ), Toolset_Relationship_Role::CHILD );
			default:
				return;
		}

		return ;
	}

	/**
	 * Maybe set sorting options based on shortcode attribute values.
	 *
	 * @since m2m
	 */
	private function maybe_set_sorting() {
		if ( ! in_array( $this->user_atts['orderby'], array( 'date', 'title', 'ID' ) ) ) {
			$this->user_atts['orderby'] = 'ID';
		}

		if ( ! in_array( $this->user_atts['order'], array( 'ASC', 'DESC' ) ) ) {
			$this->user_atts['order'] = 'DESC';
		}

		return;
	}

	/**
	 * Maybe filter the offered role options based on shortcode attribute values or a set of API filters.
	 *
	 * @since m2m
	 */
	private function maybe_set_filter_by_author() {
		if ( '' != $this->user_atts['author'] ) {
			if ( '$current' === $this->user_atts['author'] ) {
				$this->user_atts['author'] = get_current_user_id();
			}
			$this->user_atts['author'] = (int) $this->user_atts['author'];
			return;
		}

		// Try to force an author from API filters
		$form_id = $this->helper->get_frontend_form_flow()->get_current_form_id();

		$query_arguments = new Toolset_Potential_Association_Query_Arguments();

		$query_arguments->addFilter(
			new CRED_Potential_Association_Query_Filter_Posts_Author_For_Association_Role( $form_id, $this->role )
		);

		$additional_query_arguments = $query_arguments->get();
		$query_args = toolset_ensarr( toolset_getarr( $additional_query_arguments, 'wp_query_override' ) );

		if ( array_key_exists( 'author', $query_args ) ) {
			$this->user_atts['author'] = (int) $query_args['author'];
			return;
		}

		if ( array( '0' ) === toolset_getarr( $query_args, 'post__in' ) ) {
			$this->user_atts['author'] = 0;
			return;
		}

		return;
	}

	/**
	 * Set the frontend selector current selected value, if any.
	 *
	 * @return bool
	 *
	 * @since m2m
	 */
	private function maybe_set_frontend_control_current_value() {
		$this->enlimbo_args['field']['#default_value'] = '';

		$current_value = $this->get_current_role_id( $this->role );
		if ( $current_value ) {
			$this->enlimbo_args['field']['#options'][ $current_value ] = array(
				'#title' => get_the_title( $current_value ),
				'#value' => $current_value
			);
			$this->enlimbo_args['field']['#default_value'] = $current_value;
			$this->enlimbo_args['field']['#attributes']['disabled'] = 'disabled';
			return true;
		}
		return false;
	}

	/**
	 * Populate the frontend selector options with posts of a given post type.
	 *
	 * Note that it will not populate it when there is a default value, which disables the selector.
	 *
	 * @param $post_type
	 *
	 * @since m2m
	 */
	private function populate_frontend_selector( $connect_to = null, $args = null ) {

		if( $this->helper->is_edit_form_request() ){
			$this->maybe_handle_populate_with_potential_associations( $connect_to, $args );
		} else {
			// display an empty select2 box which populates on ajax request on user input
			return array();
		}
	}

	private function maybe_handle_populate_with_potential_associations( $connect = null, $args = null ){
		$candidates = $this->helper->get_potential_associations( $connect, $args );

		foreach ( $candidates as $candidate_post ) {
			$this->enlimbo_args['field']['#options'][ $candidate_post->get_id() ] = array(
				'#title' => $candidate_post->get_title(),
				'#value' => $candidate_post->get_id()
			);
		}

	}

	/**
	 * Get the frontend selector output.
	 *
	 * @param $post_type
	 * @param $role
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_frontend_control( $post_type, $role, $connect_to = null ) {
		if ( ! $post_type ) {
			return;
		}

		$this->classnames[] = 'cred_association_select_role js-cred_association_select_role';
		$this->classnames[] = 'cred_association_' . $this->relationship . '_' . $this->role;

		$this->enlimbo_args = array(
			'field' => array(
				'#type' => 'select',
				'#id' => 'cred_association_' . $this->relationship . '_' . $this->role,
				'#name' => 'cred_association_' . $this->relationship . '_' . $this->role,
				'#attributes' => array(
					'style' => $this->user_atts['style'],
					'data-orderby' => $this->user_atts['orderby'],
					'data-order' => $this->user_atts['order'],
					'data-author' => $this->user_atts['author'],
					'data-post_type' => $post_type,
					'data-parsley-required' => "true",
					'required' => 'required'
				),
				'#inline' => true,
				'#options' => array(),
			)
		);

		$needs_hidden_field = false;

		if ( $this->maybe_set_frontend_control_current_value() ) {
			$needs_hidden_field = true;
		}

		$this->enlimbo_args['field']['#attributes']['class'] = implode( ' ', $this->classnames );

		$return = toolset_form_control( $this->enlimbo_args );

		if ( $needs_hidden_field ) {
			$return .= '<input type="hidden" name="' . $this->enlimbo_args['field']['#name'] . '" value="' . $this->enlimbo_args['field']['#default_value'] . '" />';
		}

		return $return;
	}

	/**
	 * Get the relationship parent post type.
	 *
	 * @param $request_relationship
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_parent_post_type( $request_relationship ) {
		$parent_types = $request_relationship->get_parent_type()->get_types();

		if ( count( $parent_types ) != 1 ) {
			return;
		}

		$parent_post_type = current( $parent_types );
		return $parent_post_type;
	}

	/**
	 * Get the relationship child post type.
	 *
	 * @param $request_relationship
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	private function get_child_post_type( $request_relationship ) {
		$child_types = $request_relationship->get_child_type()->get_types();

		if ( count( $child_types ) != 1 ) {
			return;
		}

		$child_post_type = current( $child_types );
		return $child_post_type;
	}

}
