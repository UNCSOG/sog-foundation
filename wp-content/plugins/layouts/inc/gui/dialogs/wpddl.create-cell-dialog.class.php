<?php
define( 'DDL_ICONS_PATH', WPDDL_ABSPATH . '/resources/images/cell-icons/');
define('DDL_ICONS_REL_PATH', WPDDL_RES_RELPATH . '/images/cell-icons/');
define('DDL_ICONS_SVG_REL_PATH', DDL_ICONS_REL_PATH . 'svg/');
define('DDL_ICONS_PNG_REL_PATH', DDL_ICONS_REL_PATH . 'png/');

class CreateCellDialog{

    private $cell_categories;
    private $cell_types;
    public static $instance = null;

    private $has_icon_default = true;

    const DEFAULT_ICON_NAME = 'generic-cell.svg';

    private function __construct(){
        global $wpddlayout;
        $this->cell_categories = $wpddlayout->get_cell_categories();
        $this->cell_types = $wpddlayout->get_cell_types();
        $this->include_dialog_template();
        $this->include_cell_preview_template();
    }

    public static final function get_default_icon(){
        return DDL_ICONS_SVG_REL_PATH . self::DEFAULT_ICON_NAME;
    }

    private function include_dialog_template()
    {
        global $wpddlayout;

        include_once(WPDDL_GUI_ABSPATH . 'dialogs/dialog-cell-types.tpl.php');
    }

    public function get_cell_icon_uri( $file_uri ){

        if( !$file_uri ){
            $this->has_icon_default = true;
            return self::get_default_icon();
        }

        if( self::file_exists_at_uri( $file_uri ) ){
            $this->has_icon_default = false;
            return $file_uri;
        } else {
            $this->has_icon_default = true;
            return self::get_default_icon();
        }
    }

    private function has_icon_default(){
        return $this->has_icon_default;
    }

    public function get_cell_icon( $file_uri ){
        return  $this->get_cell_icon_uri( $file_uri );
    }

    private static function file_exists_at_uri( $file_uri ){
	    return true;
    }

    public static function getInstance( )
    {
        if (!self::$instance)
        {
            self::$instance = new CreateCellDialog();
        }

        return self::$instance;
    }

    private function include_cell_preview_template(){
        include_once WPDDL_GUI_ABSPATH . 'dialogs/js/templates/ddl-create-cell-preview.tpl.php';
    }

}