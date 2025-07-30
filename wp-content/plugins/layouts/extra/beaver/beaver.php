<?php


if( class_exists('FLBuilder') === false ){
    return;
}

class DDL_FixBeaverContentCall{

	private $has_been_removed = false;
	private static $instance = null;

	public function __construct(){
		add_action( 'ddl_before_frontend_render_cell', array(&$this, 'beaver_clean'), 10, 2 );
		add_action( 'ddl_after_frontend_render_cell', array(&$this, 'beaver_restore'), 10, 2 );
	}

    public function beaver_clean( $cell, $renderer ){

        if( class_exists('FLBuilder') && (
            ( $cell->get_cell_type() === 'cell-content-template' &&
            $cell->check_if_cell_renders_post_content() === false )  ||
            ( $cell->get_cell_type() === 'cell-text' &&
            WPDD_Utils::visual_editor_cell_has_wpvbody_tag( array($cell) ) === '' )
            )
        ){
                remove_filter( 'the_content', 'FLBuilder::render_content' );
                $this->has_been_removed = true;
        }
    }

    public function beaver_restore( $cell, $renderer ){
        if( class_exists('FLBuilder') && $this->has_been_removed ){
            add_filter( 'the_content', 'FLBuilder::render_content' );
            $this->has_been_removed = false;
        }
    }

	public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new DDL_FixBeaverContentCall();
        }

        return self::$instance;
    }

}

add_action( 'init', array('DDL_FixBeaverContentCall', 'getInstance') );