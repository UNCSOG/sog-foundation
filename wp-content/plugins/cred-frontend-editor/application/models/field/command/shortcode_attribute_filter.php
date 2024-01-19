<?php

/**
 * Whitelist and provide defaults to all known shortcode attributes.
 *
 * @since 1.9.6
 */
class CRED_Field_Shortcode_Attribute_Filter {

	protected $form_helper;

	private static $instance;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Create filtered field attributes
	 *
	 * @param $attributes
	 * @param $form_helper
	 *
	 * @return array
	 */
	public function get_filtered_attributes( $attributes, $form_helper ) {
		$this->form_helper = $form_helper;
		$base_shortcode_attributes = $this->get_basic_shortcode_attributes();
		$filtered_attributes = shortcode_atts( $base_shortcode_attributes, $attributes );

		return $filtered_attributes;
	}

	/**
	 * Return basic default field attributes
	 *
	 * @return array
	 */
	public function get_basic_shortcode_attributes() {
		return array(
			'class' => '',
			'post' => '',
			'field' => '',
			'value' => false,
			'urlparam' => '',
			'placeholder' => null,
			'escape' => false,
			'readonly' => false,
			'taxonomy' => null,
			//added new attribute for cases when for instance: taxonomy has the same slug of a field @since 1.9.5
			'force_type' => 'field',
			'single_select' => null,
			'type' => '',
			'display' => null,
			'max_width' => null,
			'max_height' => null,
			'max_results' => null,
			'order' => null,
			'ordering' => null,
			'required' => false,
			//for parent select fields @deprecated since 1.9.1 use select_text
			'no_parent_text' => null,
			//Select default label for select/parent fields
			'select_text' => null,
			'use_select2' => null,
			//TODO: trying to remove form builder helper dependency because of just this
			'validate_text' => $this->form_helper->getLocalisedMessage( 'field_required' ),
			'show_popular' => false,
			'output' => false,
			'author' => '',
			'preview' => '',
			'previewsize' => '',
			'select_label' => '',
			'edit_label' => ''
		);
	}
}
