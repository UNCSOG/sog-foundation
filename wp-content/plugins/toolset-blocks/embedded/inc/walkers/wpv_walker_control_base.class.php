<?php

/**
 * Post taxonomy filter select options walker class
 *
 * @package Views
 *
 * @extends Walker
 *
 * @since 2.4.0
 */
class WPV_Walker_Control_Base extends Walker {

	public $tree_type = 'base';

	public $db_fields = array(
		'parent' => 'parent',
		'id' => 'id',
	);

	public function __construct( $walker_args = array() ) {

	}
	
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		
	}

	public function start_el( &$output, $taxonomy_term, $depth = 0, $args = array(), $current_object_id = 0 ) {
		
	}

	public function end_el( &$output, $taxonomy_term, $depth = 0, $args = array() ) {
		
	}
	
	/**
	 * Render the element label.
	 *
	 * @param string $label      The label text
	 * @param array  $attributes The label HTML tag attrbutes
	 *
	 * @return string The label HTML tag
	 *
	 * @since 2.4.0
	 */
	public function el_label( $label, $attributes = array() ) {
		$output = WPV_Frontend_Filter::get_label( $label, $attributes );
		return $output;
	}
	
    /**
     * Render the element input.
     * 
     * @param array $attributes The input HTML tag attrbutes
	 *
	 * @return string The input HTML tag
	 *
	 * @since 2.4.0
     */
	public function el_input( $attributes = array() ) {
		$output = WPV_Frontend_Filter::get_input( $attributes );
		return $output;
	}
	
	 /**
     * Render the element option.
     * 
     * @param array $attributes The option HTML tag attrbutes
	 *
	 * @return string The option HTML tag
	 *
	 * @since 2.4.0
     */
	public function el_option( $label, $attributes = array() ) {
		$output = WPV_Frontend_Filter::get_option( $label, $attributes );
		return $output;
	}


	/**
	 * Add an value to a particular attribute.
	 *
	 * @param array $attributes Associative array of attributes.
	 * @param string $attribute_name Name of an attribute where those values should be added. The attribute itself must be an array.
	 * @param string $value_to_add A single value to add to the chosen attribute.
	 *
	 * @return array Modified array of attributes. If the selected attribute wasn't an array in $attributes, its original value would have been discarded.
	 * @since BS4
	 */
	protected function add_attribute_element( $attributes, $attribute_name, $value_to_add ) {
		$attributes[ $attribute_name ] = array_unique(
			array_merge(
				toolset_ensarr( toolset_getarr( $attributes, $attribute_name ) ),
				[ $value_to_add ]
			)
		);

		return $attributes;
	}
	
}
