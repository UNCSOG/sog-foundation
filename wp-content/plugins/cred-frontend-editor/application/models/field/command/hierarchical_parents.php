<?php

use OTGS\Toolset\CRED\Model\Field\Command\AbstractRelationshipCommand;

/**
 * Transform shortcode attributes to field object for rendering hierarchical parent selector fields.
 *
 * @since 2.0
 */
class CRED_Field_Command_Hierarchical_Parents extends AbstractRelationshipCommand {

	const STATIC_FIELDS_INDEX = 'hierarchical_parents';

	/**
	 * Execute the command and craft the field object.
	 *
	 * @return mixed[]
	 * @note Legacy parents and hierarchical parents share the same logic.
	 */
	public function execute() {
		$field = $this->setup_field();

		$this->expected_parent_post_type = toolset_getnest( $field, [ 'data', 'post_type' ], null );

		$force_author = ( isset( $this->filtered_attributes[ 'author' ] ) ) ? $this->filtered_attributes[ 'author' ] : '';
		$force_author = $this->maybe_set_ancestor_filter_by_author( $force_author, $this->field_name );

		$potential_parents = \CRED_Select2_Utils::get_instance()->try_register_parent_as_select2(
			$this->filtered_attributes[ 'html_form_id' ],
			$this->field_name,
			$field,
			toolset_getarr( $this->filtered_attributes, 'max_results', null ),
			toolset_getarr( $this->filtered_attributes, 'use_select2', null ),
			$this->calculate_forced_args( $field, $force_author )
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

		\CRED_Select2_Utils::get_instance()->set_current_value_to_registered_select2_field(
			$this->filtered_attributes[ 'html_form_id' ],
			$this->field_name,
			( null !== $default_option ) ? $default_option : toolset_getarr( $field_object, 'value' ),
			$this->expected_parent_post_type
		);

		$this->normalize_stored_field_data( $field, $field_object );

		return $field_object;
	}

}
