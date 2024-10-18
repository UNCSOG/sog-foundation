<?php

// @todo comment
abstract class WPDDL_Cell_Abstract implements WPDDL_Cell_Interface {

	protected $id;
	protected $factory;

	public function setup() {
		add_filter( 'dd_layouts_register_cell_factory', array( $this, 'cell' ) );
		apply_filters('ddl_default_support_features',array(
			$this->get_id()
		));
	}

	public function cell( $factories ) {
        if( ddl_has_feature($this->id) ){
            $factories[$this->id] = new $this->factory();
        }
		return $factories;
	}

	public function get_id(){
	    return $this->id;
    }
}