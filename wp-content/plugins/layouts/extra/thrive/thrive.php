<?php


if(function_exists("tve_editor_content") === false){
    return;
}

class DDL_FixThriveCellPrefixing{

	private $has_been_removed = false;
	private static $instance = null;

	public function __construct(){
		add_action( 'ddl_before_frontend_render_cell', array(&$this, 'remove_cell_thrive_prefix'), 10, 2 );
        add_action( 'ddl_after_frontend_render_cell', array(&$this, 'restore_prefix'), 10, 2 );
        add_action( 'init', array(&$this, "delay_early_actions"), (PHP_INT_MAX + 1), 2);
	}

    public function remove_cell_thrive_prefix($cell, $renderer){
         if( function_exists("tve_editor_content") && (( 
            $cell->get_cell_type() === 'cell-content-template' 
            && $cell->check_if_cell_renders_post_content() === false )  
            || ( $cell->get_cell_type() === 'cell-text' ))){

                remove_filter( 'the_content', 'tve_editor_content' );
                $this->has_been_removed = true;
        }
    }

    public function restore_prefix( $cell, $renderer ){
        if( function_exists("tve_editor_content") && $this->has_been_removed ){
            add_filter( 'the_content', 'tve_editor_content' );

            $this->has_been_removed = false;
        }
    }


    public function delay_early_actions(){
        if(function_exists('tve_wp_action')){
          remove_action( 'wp', 'tve_wp_action' );
          add_action( 'init', 'tve_wp_action', (PHP_INT_MAX + 1));
        }
    }


	public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new DDL_FixThriveCellPrefixing();
        }
        
        return self::$instance;
    }

}

add_action( 'init', array('DDL_FixThriveCellPrefixing', 'getInstance') );