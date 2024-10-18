<?php

class WPDD_register_layout_theme_section{

	private $theme_sections;
	private $current_theme_section;

	function __construct(){
		$this->theme_sections = array();
		$this->current_theme_section = '';
	}

	function register_dd_layout_theme_section($theme_section, $data) {
		if (array_key_exists($theme_section, $this->theme_sections)) {
			return false;
		} else {
			$this->current_theme_section = $theme_section;

			if (!isset($data['name'])) { $data['name'] = ''; }
			if (!isset($data['description'])) { $data['description'] = ''; }


			$this->theme_sections[$theme_section] = $data; // Initialize here so it can be accessed during dialog template callback.


			$this->theme_sections[$theme_section] = $data;

			$this->current_theme_section = '';

			return true;
		}
	}

	function get_input_name($name) {
		return 'ddl-layout-' . $name;
	}

	function get_theme_sections ( $sections = null /* php prevent warning*/ ) {
		return array_keys($this->theme_sections);
	}

	function get_theme_section_info($theme_section) {
		return isset( $this->theme_sections[$theme_section] ) ? $this->theme_sections[$theme_section] : null;
	}

	function get_current_theme_section_info() {
		if ($this->current_theme_section) {
			return $this->theme_sections[$this->current_theme_section];
		} else {
			return array();
		}

	}

	function create_theme_section( $theme_section, $name ) {
		if ( isset($this->theme_sections[$theme_section]) ) {
			return new WPDD_registered_theme_section($theme_section, $name, $this->theme_sections[$theme_section]);
		} else {
			return null;
		}
	}
}

class WPDD_layout_theme_section extends WPDD_layout_element {

	private $theme_section_data;
	private $theme_section;
	private $row_data;

	function __construct($theme_section, $name, $row_data, $theme_section_data) {
		parent::__construct( null, $name );
		$this->set_theme_section($theme_section);
		$this->theme_section_data = $theme_section_data;
		$this->row_data = $row_data;
        $this->context = null;
	}

	function frontend_render($target) {
		$this->frontend_render_theme_section_content($target);
	}

	function frontend_render_theme_section_content($target) {

		if( isset($this->theme_section_data['custom-row-content-callback']) ){
			$content = call_user_func($this->theme_section_data['custom-row-content-callback'], $this->theme_section_data);
		}
		// This is for retro compatibility only with versions < 1.0
		else if ( isset($this->theme_section_data['theme-section-content-callback']) ) {
			 $content = call_user_func($this->theme_section_data['theme-section-content-callback'], $this->theme_section_data);
		 } else {
			$content = '';
		}
		$target->theme_section_content_callback($content);
	}

	function set_theme_section($theme_section) {
		$this->theme_section = $theme_section;
	}

	function get_theme_section()
	{
		return $this->theme_section;
	}

	function get_layout_type()
	{
		return $this->row_data['layout_type'];
	}
	/* I added this to prevent errors */
	function get_width()
	{
		return 12;
	}
    function find_cell_of_type($cell_type){
        return false;
    }

	function get_all_cells_of_type( $cell_type ){
		return array();
	}

	function get_cells_of_type( $cell_type ){
		return array();
	}

	function find_cells_of_type( $cell_type ){
		return array();
	}
	function get_cell_by_id( $cell_id ){
		return null;
	}

	function get_width_of_child_layout_cell(){
		return 0;
	}

	function is_row_with_child(){
		return false;
	}

    function set_context($context) {
        $this->context = $context;
    }
}