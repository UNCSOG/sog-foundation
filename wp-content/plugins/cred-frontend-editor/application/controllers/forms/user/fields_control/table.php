<?php

namespace OTGS\Toolset\CRED\Controller\Forms\User\FieldsControl;

use OTGS\Toolset\CRED\Controller\FieldsControl\Table as BaseTable;

class Table extends BaseTable {

    protected function set_private_properties() {
        $show_private = false;
        if ( ! empty( $_GET )
            && ! isset( $_GET['show_private'] )
        ) {
            $show_private = false;
        } else {
            $show_private = true;
        }

        $this->_post_type = CRED_USER_FORMS_CUSTOM_POST_NAME;
        $this->_show_private = $show_private;
    }

    protected function get_fields_model() {
        return \CRED_Loader::get( 'MODEL/UserFields' );
    }

    /**
     * method overwrites WP_List_Table::get_columns() method and sets the names of the table fields 
     * 
     */
    function get_columns() {
        return $columns = array(
            'cred_field_name' => __( 'User Field', 'wp-cred' ),
            'cred_cred_type' => __( 'Field Type', 'wp-cred' ),
            'cred_actions' => __( 'Actions', 'wp-cred' )
        );
    }

    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav($which) {
        if ( $which == "top" ) {
            $this->print_private_fields_control();
        }
    }

}
