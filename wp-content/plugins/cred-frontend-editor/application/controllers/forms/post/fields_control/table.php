<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Post\FieldsControl;

use OTGS\Toolset\CRED\Controller\FieldsControl\Table as BaseTable;

class Table extends BaseTable {

    protected function set_private_properties() {
        $show_private = false;

	    if ( isset( $_GET['posttype'] )
		    && ( ! isset( $_GET['show_private'] )
			    || '1' != $_GET['show_private'] )
	    ) {
		    $show_private = false;
	    } elseif ( isset( $_GET['show_private'] ) && '1' == $_GET['show_private'] ) {
		    $show_private = true;
	    }


        if ( isset( $_GET['posttype'] ) ) {
            $post_type = $_GET['posttype'];
        } else {
            $post_type = '';
        }

        $this->_post_type = $post_type;
        $this->_show_private = $show_private;
    }

    protected function get_fields_model() {
        return \CRED_Loader::get( 'MODEL/Fields' );
    }

    /**
     * method overwrites WP_List_Table::get_columns() method and sets the names of the table fields
     *
     */
    function get_columns() {
        return $columns = array(
            'cred_field_name' => __( 'Post Field', 'wp-cred' ),
            'cred_cred_type' => __( 'Field Type', 'wp-cred' ),
            'cred_actions' => __( 'Actions', 'wp-cred' )
        );
    }

    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    function extra_tablenav( $which ) {
        if ( $which == "top" ) {
            // get custom post types not managed by Types
            $custom_posts = $this->get_fields_model()->getPostTypesWithoutTypes();
            ?>
            <?php _e( 'Show fields for', 'wp-cred' ); ?>
			<select id='cred_custom_posts' name='posttype'>
				<option value="" disabled="disabled" <?php selected( '', $this->_post_type ); ?>>
					<?php _e( 'Select a post type', 'wp-cred' ); ?>
				</option>
				<?php
				foreach ( $custom_posts as $cp ) {
					?>
					<option value="<?php echo esc_attr( $cp['type'] ); ?>" <?php selected( $cp['type'], $this->_post_type ); ?>>
					<?php echo $cp['name']; ?>
					</option>
					<?php
				}
				?>
			</select>
            <?php
            $this->print_private_fields_control();
        }
    }

}
