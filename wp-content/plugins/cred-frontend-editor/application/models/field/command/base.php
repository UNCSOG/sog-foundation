<?php

/**
 * Transform shortcode attributes to field object for rendering fields.
 *
 * Each field type should have a child class extending this one:
 * - Form fields.
 * - Post fields.
 * - User fields.
 * - Custom fields.
 * - Extra fields.
 * - Relationship fields:
 *     - Hierarchical relationships.
 *     - Legacy relationships (before Types m2m).
 *     - Types relationships.
 *     - Post reference fields.
 * - Taxonomy fields.
 */
abstract class CRED_Field_Command_Base implements ICRED_Field_Command {

	protected $filtered_attributes;
	protected $translate_field_factory;
	protected $expected_parent_post_type = false;

	protected $form;
	protected $form_helper;
	protected $form_fields;
	protected $form_type;
	protected $form_rendering;
	protected $post_type;

	protected $field_name;
	protected $value;
	protected $readonly;
	protected $required;
	protected $escape;

	/**
	 * CRED_Field_Command_Base constructor.
	 *
	 * @param array $filtered_attributes
	 * @param \CRED_Form_Data $form
	 * @param \CRED_Form_Builder_Helper $form_helper
	 * @param \CRED_Translate_Field_Factory $translate_field_factory
	 * @param \CRED_Form_Rendering $form_rendering
	 */
	public function __construct(
		$filtered_attributes,
		\CRED_Form_Data $form,
		\CRED_Form_Builder_Helper $form_helper,
		\CRED_Translate_Field_Factory $translate_field_factory,
		\CRED_Form_Rendering $form_rendering
	) {
		$this->filtered_attributes = $filtered_attributes;
		$this->form = $form;
		$form_fields = $form->getFields();
		$this->form_fields = $form_fields;
		$this->form_type = $form_fields[ 'form_settings' ]->form[ 'type' ];
		$this->post_type = $form_fields[ 'form_settings' ]->post[ 'post_type' ];
		$this->field_name = $filtered_attributes[ 'field' ];
		$this->form_helper = $form_helper;
		$this->translate_field_factory = $translate_field_factory;
		$this->form_rendering = $form_rendering;

		/*
		 * result of this use fix_cred_field_shortcode_value_attribute_by_single_quote
		 */
		$this->value = str_replace( "@_cred_rsq_@", "'", $this->filtered_attributes[ 'value' ] );
		$this->readonly = (bool) ( strtoupper( $this->filtered_attributes[ 'readonly' ] ) === 'TRUE' );
		$this->required = (bool) ( strtoupper( $this->filtered_attributes[ 'required' ] ) === 'TRUE' );
		$this->escape = false;
	}

	/**
	 * Protected function used to get a uniformed array object with standard key values
	 *
	 * @param $field
	 * @param $field_object
	 *
	 * @return array
	 *
	 * @since 1.9.3
	 */
	protected function get_uniformed_field( $field, $field_object ) {
		$uniformed_field_array = array(
			'name' => $field[ 'name' ],
			'type' => $field[ 'type' ],
			'id' => isset( $field[ 'id' ] ) ? $field[ 'id' ] : $field[ 'name' ],
			'slug' => isset( $field[ 'slug' ] ) ? $field[ 'slug' ] : $field[ 'name' ],
			'title' => isset( $field_object[ 'title' ] ) ? $field_object[ 'title' ] : $field[ 'name' ],
			'label' => isset( $field_object[ 'title' ] ) ? $field_object[ 'title' ] : $field[ 'name' ],
			'value' => isset( $field_object[ 'value' ] ) ? $field_object[ 'value' ] : '',
			'attr' => $field_object[ 'attr' ],
			'data' => $field_object[ 'data' ],
			'form_html_id' => $field[ 'form_html_id' ],
		);
		if ( isset( $field_object[ 'attr' ] ) ) {
			$uniformed_field_array[ 'attr' ] = $field_object[ 'attr' ];
		}
		if ( isset( $field_object[ 'data' ] ) ) {
			$uniformed_field_array[ 'data' ] = $field_object[ 'data' ];
		}

		return $uniformed_field_array;
	}

	abstract public function execute();
}
