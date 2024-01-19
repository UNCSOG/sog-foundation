<?php

namespace OTGS\Toolset\CRED\Controller\FieldsControl;

use OTGS\Toolset\CRED\Controller\FieldsControl\Db as FieldsControlDb;
use OTGS\Toolset\CRED\Model\Field\Generic\Gui as GenericGui;

/**
 * Prepare and render the non Toolset fields table.
 * 
 * @since 2.1
 */
class Table extends \WP_List_Table {

    protected $_post_type = '';
    protected $_show_private = false;

    protected $template_repository = null;
    protected $renderer = null;

    function __construct() {
        parent::__construct( array(
            'plural' => 'list_customfields', //plural label, also this well be one of the table css class
            'singular' => 'list_customfield', //Singular label
            'ajax' => false //We won't support Ajax for this table
        ) );
        $this->set_shared_private_properties();
        $this->set_private_properties();
    }

    protected function set_shared_private_properties() {
        $this->template_repository = \CRED_Output_Template_Repository::get_instance();
        $this->renderer = \Toolset_Renderer::get_instance();
    }

    protected function set_private_properties() {}

    protected function get_fields_model() {
        return null;
    }

    protected function print_private_fields_control() {
        $this->renderer->render(
			$this->template_repository->get( \CRED_Output_Template_Repository::FIELDS_CONTROL_SHARED_PRIVATE_FIELDS_CONTROL ),
			array( 'show_private' => $this->_show_private )
		);
        ?>
        <?php
    }

    function prepare_items() {
        $this->items = array();
        $totalitems = 0;

        $orderby = 'meta_key';
        $order = 'asc';

        $perpage = \CRED_Helper::get_current_screen_per_page();
        $paged = intval( toolset_getget( 'paged', 1 ) );
        if ( $paged < 1 ) {
            $paged = 1;
        }

        /* -- Fetch the items -- */
        if ( ! empty( $this->_post_type ) ) {
            $fm = $this->get_fields_model();
            if ( ! is_null( $fm ) ) {
                $totalitems = $fm->getPostTypeCustomFields( $this->_post_type, array(), $this->_show_private, -1, $perpage );
                if ( ( $paged - 1 ) * $perpage > $totalitems ) {
                    $paged = 1;
                }
                $this->items = $fm->getPostTypeCustomFields( $this->_post_type, array(), $this->_show_private, $paged, $perpage, $orderby, $order );
            }
        }

        /* -- Register the pagination -- */
        $totalpages = ceil( $totalitems / $perpage );
        $this->set_pagination_args( array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
            'paged' => $paged,
            'posttype' => $this->_post_type,
            'show_private' => $this->_show_private ? '1' : '0'
        ) );

        /* Register the columns */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array( $columns, $hidden, $sortable );
    }

    public function display_rows() {
        $meta_fields = $this->items;

        if ( empty( $meta_fields ) ) {
            return false;
        }

        $fields_control_db = new FieldsControlDb();
		$cred_fields = $fields_control_db->get_fields_per_post_type( $this->_post_type );
        
        $generic_fields_gui = new GenericGui();
        $field_types_labels = $generic_fields_gui->get_generic_fields_labels();

        list( $columns, $hidden ) = $this->get_column_info();

        $context = array(
            'cred_fields' => $cred_fields,
            'field_types_labels' => $field_types_labels,
            'columns' => $columns
        );

        foreach ( $meta_fields as $meta_key ) {
            $context['meta_key'] = $meta_key;
            $this->renderer->render(
                $this->template_repository->get( \CRED_Output_Template_Repository::FIELDS_CONTROL_SHARED_TABLE_ROW ),
                $context
            );
        }
    }

}