<?php

// @todo comment
abstract class WPDDL_Cell_Abstract_Cell  extends WPDD_layout_cell {

	protected $id;
	protected $view_file;

	function __construct( $id, $name, $width, $css_class_name = '', $editor_visual_template_id = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '' ) {
	    $cell_type = $this->id;
	    parent::__construct( $id, $name, $width, $css_class_name, $cell_type , $content, $css_id, $tag, $unique_id );
        $this->set_cell_type( $cell_type );
	}

	protected function setViewFile() {
		return null;
	}

	function frontend_render_cell_content( $target ) {

		$this->view_file = $this->setViewFile();

		if( $this->view_file === null )
			return;

		if( file_exists( $this->view_file ) && is_readable( $this->view_file ) ) {

			ob_start();

			include( $this->view_file );

			return $target->cell_content_callback( ob_get_clean(), $this );

		}
	}
}