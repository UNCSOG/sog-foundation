<?php

/**
 * Class that transform "Taxonomies" shortcode attributes to field object for rendering fields
 *
 * @since 1.9.6
 */
class CRED_Field_Command_Taxonomies extends CRED_Field_Command_Base {

	public function execute() {
		if ( ! taxonomy_exists( $this->field_name ) ) {
			// Avoid errors when trying to display a field about a non existing taxonomy
			return false;
		}
		$field = CRED_StaticClass::$out[ 'fields' ][ 'taxonomies' ][ $this->field_name ];
		// check which fields are actually used in form
		$field[ 'form_html_id' ] = $this->translate_field_factory->get_html_form_field_id( $field );

		$name = $field[ 'name' ];

		$additional_attributes = array(
			'preset_value' => $this->filtered_attributes[ 'display' ],
			'is_tax' => true,
			'single_select' => ( $this->filtered_attributes[ 'single_select' ] === 'true' ),
			'show_popular' => $this->filtered_attributes[ 'show_popular' ],
			'placeholder' => $this->filtered_attributes[ 'placeholder' ],
			'class' => $this->filtered_attributes[ 'class' ],
			'output' => $this->filtered_attributes[ 'output' ],
		);

		$field_object = $this->translate_field_factory->cred_translate_field( $name, $field, $additional_attributes );

		CRED_StaticClass::$out[ 'form_fields' ][ $name ] = $this->get_uniformed_field( $field, $field_object );
		CRED_StaticClass::$out[ 'current_form_fields' ][ $name ] = $this->get_uniformed_field( $field, $field_object );
		CRED_StaticClass::$out[ 'form_fields_info' ][ $name ] = array(
			'type' => $field[ 'type' ],
			'repetitive' => ( isset( $field[ 'data' ][ 'repetitive' ] ) && $field[ 'data' ][ 'repetitive' ] ),
			'plugin_type' => ( isset( $field[ 'plugin_type' ] ) ) ? $field[ 'plugin_type' ] : '',
			'name' => $name,
			'display' => $this->value,
		);

		return $field_object;
	}
}