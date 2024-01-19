<?php

use OTGS\Toolset\CRED\Model\Field\Command\AbstractRelationshipCommand;

/**
 * Transform shortcode attributes to field object for rendering Types related selector fields.
 *
 * @since m2m
 */
class CRED_Field_Command_Relationships extends AbstractRelationshipCommand {

	const STATIC_FIELDS_INDEX = 'relationships';

	/** @var \OTGS\Toolset\Common\Relationships\API\Factory */
	private $relationships_factory;

	/** @var \CRED_Form_Relationship */
	private $form_relationship;

	/**
	 * CRED_Field_Command_Relationships constructor.
	 *
	 * @param $filtered_attributes
	 * @param \CRED_Form_Data $form
	 * @param \CRED_Form_Builder_Helper $form_helper
	 * @param \CRED_Translate_Field_Factory $translate_field_factory
	 * @param \CRED_Form_Rendering $form_rendering
	 * @param \CRED_Form_Relationship|null $form_relationship_di
	 * @param \OTGS\Toolset\Common\Relationships\API\Factory|null $relationships_factory_di
	 */
	public function __construct(
		$filtered_attributes,
		\CRED_Form_Data $form,
		\CRED_Form_Builder_Helper $form_helper,
		\CRED_Translate_Field_Factory $translate_field_factory,
		\CRED_Form_Rendering $form_rendering,
		\CRED_Form_Relationship $form_relationship_di = null, // Needed for legacy code and unit tests
		\OTGS\Toolset\Common\Relationships\API\Factory $relationships_factory_di = null // Needed for legacy code and unit tests
	) {
		parent::__construct( $filtered_attributes, $form, $form_helper, $translate_field_factory, $form_rendering );

		$this->form_relationship = $form_relationship_di ? $form_relationship_di : \CRED_Form_Relationship::get_instance();

		$this->relationships_factory = $relationships_factory_di ? $relationships_factory_di : toolset_dic_make( '\OTGS\Toolset\Common\Relationships\API\Factory' );
	}

	/**
	 * Execute the command and craft the field object.
	 *
	 * @return mixed[]
	 */
	public function execute() {
		$field = $this->setup_field();
		$field['type'] = 'select';
		$field['post_id'] = $this->form_rendering->_post_id;
		$field['form_type'] = $this->form->get_form_type();

		$this->expected_parent_post_type = toolset_getnest( $field, [ 'data', 'post_type' ], null );

		$force_author = ( isset( $this->filtered_attributes['author'] ) ) ? $this->filtered_attributes['author'] : '';
		$force_author = $this->maybe_set_ancestor_filter_by_author( $force_author, $this->field_name );

		/*
		 * If it is editing a RFG item, the field should be readonly.
		 * TODO hightly unefficient!!!
		 */
		if ( $this->form->get_form_type() === 'edit' ) {
			$form_fields = $this->form->getFields();
			if (
				isset( $form_fields[ 'form_settings' ] )
				&& isset( $form_fields['form_settings']->post )
			) {
				$post_type = $form_fields['form_settings']->post['post_type'];
				$repeatable_field_groups_names = get_post_types( array( \Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP => true ) );
				if ( in_array( $post_type, $repeatable_field_groups_names, true ) ) {
					$this->readonly = true;
				}
			}
		}

		$potential_parents = \CRED_Select2_Utils::get_instance()->try_register_relationship_parent_as_select2(
			$this->form_rendering->html_form_id,
			$this->field_name,
			$field,
			toolset_getarr( $this->filtered_attributes, 'max_results', null ),
			toolset_getarr( $this->filtered_attributes, 'use_select2', null )
		);

		$default_option = $this->maybe_set_default_from_current( $force_author, $this->expected_parent_post_type );
		$default_option_from_url = $this->maybe_set_default_from_url( $force_author, $this->expected_parent_post_type );
		if ( null !== $default_option_from_url ) {
			$default_option = $default_option_from_url;
		}

		$field = $this->populate_options( $field, $potential_parents, $default_option );

		$field_object = $this->translate_field_factory->cred_translate_field(
			$this->field_name,
			$field,
			$this->populate_additional_attributes( $field, $force_author )
		);

		// Get Relationship definition
		$relationship_definition = $this->form_relationship
			->get_definition_by_relationship_slug( $field['slug'] );

		// Get possible association parent
		$results = $this->form_relationship
			->get_ruled_association_by_id( $this->form_rendering->_post_id, $relationship_definition, $field['role'] );

		if ( count( $results ) > 0
			&& isset( $results[0]['post'] )
		) {
			//TODO: maybe we need to improve checking here
			$current_post_id = $results[0]['post']->get_id();
			$field_object['value'] = $current_post_id;
			if ( ! isset( $field_object['attr'] ) ) {
				$field_object['attr'] = array();
			}
			$field_object['attr']['actual_value'] = $current_post_id;
		}

		\CRED_Select2_Utils::get_instance()->set_current_value_to_registered_select2_field(
			$this->form_rendering->html_form_id,
			$this->field_name,
			( null !== $default_option ) ? $default_option : toolset_getarr( $field_object, 'value' ),
			$this->expected_parent_post_type
		);

		$this->normalize_stored_field_data( $field, $field_object );

		if (
			$this->maybe_disable_for_non_default_wpml_language()
			&& current_user_can( 'manage_options' )
		) {
			$field_object['description'] = __( 'Relationships can only be managed in the default language.', 'wp-cred' );
		}

		return $field_object;
	}


	/**
	 * Disable relationship fields displayed on a form to create posts in a language different than default.
	 *
	 * You can only create an association for a post that has a translation into the default language.
	 * When creating new posts, this does not happen automatically.
	 *
	 * @since 2.1
	 * @since 2.6 Take into account whether we still require default language version of a post for creating associations.
	 */
	private function maybe_disable_for_non_default_wpml_language() {
		if ( 'new' != $this->form_type ) {
			return false;
		}

		if( ! $this->relationships_factory->database_operations()->requires_default_language_post() ) {
			return false;
		}

		if ( apply_filters( 'wpml_default_language', '' ) != apply_filters( 'wpml_current_language', '' ) ) {
			$this->readonly = true;
			return true;
		}

		return false;
	}

	/**
	 * Decide whether the parent selector will be limited to items by a given author.
	 *
	 * @param string|int $force_author
	 * @param string $field_name
	 * @return string|int
	 */
	protected function maybe_set_ancestor_filter_by_author( $force_author, $field_name ) {
		if ( '$current' === $force_author ) {
			$force_author = get_current_user_id();
			$force_author = (int) $force_author;

			return $force_author;
		}

		if ( ! empty( $force_author ) ) {
			return $force_author;
		}

		$form_id = $this->form_rendering->form_id;

		$query_arguments = new \Toolset_Potential_Association_Query_Arguments();

		$query_arguments->addFilter(
			new \CRED_Potential_Association_Query_Filter_Posts_Author_For_Post_Ancestor( $form_id, $field_name )
		);

		$additional_query_arguments = $query_arguments->get();
		$query_args = toolset_ensarr( toolset_getarr( $additional_query_arguments, 'wp_query_override' ) );

		if ( array_key_exists( 'author', $query_args ) ) {
			$force_author = (int) $query_args['author'];

			return $force_author;
		}

		if ( array( '0' ) === toolset_getarr( $query_args, 'post__in' ) ) {
			$force_author = 0;

			return $force_author;
		}

		return $force_author;
	}
}
