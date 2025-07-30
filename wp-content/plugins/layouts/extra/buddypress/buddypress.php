<?php

// This cell is incomplete so we wont load it now.

if ( !defined( 'BP_VERSION' ) ) {
    return;
}

/**
 * BuddyPress cell class.
 *
 * @version 1.0
 * @todo Buddypress overrides Layouts template on BP pages
 */
class BP_Cell
{

    protected $_relpath, $_page_template, $_layout_id = null;

    function __construct(){
        $this->_relpath = WPDDL_RELPATH . '/extra/buddypress';
        add_action( 'init', array( $this, 'init' ), 20 );
        //add_action( 'get_layout_id_for_render', array( &$this, 'has_cell' ), 888, 2 );
		add_action( 'template_redirect', array( &$this, 'template_redirect' ), 30, 1 );
    }

    public function init() {
	    $this->register_bp_cell();
    }

	private function register_bp_cell(){
		register_dd_layout_cell_type( 'buddypress',
			array(
				// The name of the cell type.
				'name' => __( 'BuddyPress', 'theme-context' ),
				// css class name for the icon. This is displayed in the popup when creating new cell types.
				'cell-image-url' => DDL_ICONS_SVG_REL_PATH . 'buddypress-cell.svg',
				// A description of the cell type. This is displayed in the popup when creating new cell types.
				'description' => __( 'BuddyPress loop.', 'theme-context' ),
				// Category used to group cell types together.
				'category' => __( 'Buddypress', 'theme-context' ),
				// The text for the button that is displayed in the popup when creating new cell types.
				'button-text' => __( 'Add', 'theme-context' ),
				// The dialog title to be displayed when creating a new cell of this type
				'dialog-title-create' => __( 'Insert BuddyPress output', 'theme-context' ),
				// The dialog title to be displayed when editing a cell of this type
				'dialog-title-edit' => __( 'Edit', 'theme-context' ),
				// The function name of a callback function that supplies the user
				// interface for creating or editing the cell type.
				// Can be left blank if the cell type has no UI.
				'dialog-template-callback' => array( $this, 'dialog_template_callback' ),
				// The function name of a callback function that returns the HTML to be rendered in the front end.
				// This function will receive the $cell_settings that the user has entered via the cell edit dialog.
				'cell-content-callback' => array( $this, 'content_callback' ),
				// The function name of a callback function that returns the HTML for displaying the cell in the editor.
				'cell-template-callback' => array( $this, 'template_callback' ),
				// The class name or names to add when the cell is output. Separate class names with a space.
				'cell-class' => 'bp-cell',
				// Preview image URL
				'preview-image-url' => $this->_relpath . '/icon.png',
			)
		);
	}

    function template_redirect( ){
        global $post;

        if( null === $post ) return;

		$page_array = get_option( 'bp-pages', array() );
		$page_array = is_array( $page_array ) ? $page_array : array();
        $active_modules_pages = array_keys( $page_array );

        if (
			in_array( $post->post_name, $active_modules_pages )
			&& $_layout_id = get_post_meta( $page_array[ $post->post_name ], WPDDL_LAYOUTS_META_KEY, true )
		) {

            if( is_numeric( $_layout_id ) ){
                $this->_layout_id = $_layout_id;
            } else {
                $_layout_id = WPDD_Utils::get_layout_id_from_post_name($_layout_id);
                $this->_layout_id = $_layout_id;
            }
            if( $_layout_id ){
                add_filter('get_layout_id_for_render', array(&$this, 'get_layout_id'), 99);
                add_filter('ddl-is_ddlayout_assigned', array(&$this, 'get_layout_id'), 99);
            }

        }
    }

    function get_layout_id( $layout_id ){
        $layout_id = $this->_layout_id;
        return $layout_id;
    }

    /**
     * Checks if template has BP cell and add filters.
     *
     * @param type $layout_id
     * @param type $args
     * @return type
     */
    function has_cell( $layout_id, $args ){
        $layout_settings = WPDD_Layouts::get_layout_settings( $layout_id, true );
        $layout_instance = new WPDD_json2layout();
        $layout = $layout_instance->json_decode( wp_json_encode( $layout_settings ) );
        if ( $layout->has_cell_of_type( 'buddypress' ) && is_buddypress() ) {
            // Do something

        }
        return $layout_id;
    }

    /**
     * Callback function that returns the user interface for the cell dialog.
     *
     * Notice that we are using 'text_data' for the input element name.
     * This can then be accessed during frontend render using
     * $cell_settings['text_data']
     */
	public function dialog_template_callback() {
		ob_start();
		?>

		<div class="ddl-form">
            <fieldset>
                <legend><?php _e( 'BuddyPress Component:', 'ddl-layouts' ); ?></legend>
					<div class="fields-group">
                        <?php
                        $components = $this->get_eligible_components();

                        foreach ( $components as $component ) :
                            $name = $component->name;
                            $slug = $component->slug;
                            ?>
                            <p>
                                <label for="<?php the_ddl_name_attr( 'bp_component_load' ); ?>">
                                    <input type="radio" value="<?php echo $slug; ?>" name="<?php the_ddl_name_attr( 'bp_component_load' ); ?>"/>
                                    <span><?php echo $name; ?></span>
                                </label>
                            </p>
                        <?php endforeach; ?>
                    </div>
            </fieldset>
		</div>

		<?php
		return ob_get_clean();
	}

    /**
     * Callback function for displaying the cell in the editor.
     *
     * @return string
     */
    public function template_callback() {
	    ob_start();
	    ?>
	    <div class="cell-content">

		    <p class="cell-name">{{ name }}</p>
		    <div class="cell-preview">
			    <div class="ddl-video-preview">
				    <img src="<?php echo DDL_ICONS_SVG_REL_PATH . 'buddypress.svg'; ?>" height="130px">
			    </div>
		    </div>
	    </div>
	    <?php
	    return ob_get_clean();
    }

	private function get_eligible_components(){
		$loaded = array(
			array(
				'name' => 'Groups',
				'slug' => 'groups'
			),
			array(
				'name' => 'Activity',
				'slug' => 'activity'
			),
			array(
				'name' => 'Blogs',
				'slug' => 'blogs'
			),
			array(
				'name' => 'Members',
				'slug' => 'members'
			),
			array(
				'name' => 'Forums',
				'slug' => 'forums'
			),
		);

		$ret = array();

		foreach( $loaded as $component ){
			if( $this->is_bp_active_component( $component['slug'] ) ){
				$ret[] = (object) $component;
			}
		}
		return $ret;
	}

	private function is_bp_active_component ( $component ){
		if( $component === 'blogs' ){
			return is_multisite() && bp_is_active( $component );
		}

		return bp_is_active( $component );
	}

    /**
     * Callback function for display the cell in the front end.
     *
     * Render captured BuddyPress output.
     * @see bp_cell_init()
     * @uses apply_filters( 'the_content' );
     * @param type $cell_settings
     * @return type
     */
    public function content_callback( $cell_settings ) {

        /*
         * $wp_query->in_the_loop has to be forced as TRUE
         * because BP will not render if FALSE.
         * It's set by WP with the_post() call (in loop have_posts()).
         */
	    $component = get_ddl_field('bp_component_load');

	    if( $this->is_bp_active_component( $component ) === false ){
		    $output = $this->missing_component_message( $component );
	    } else {
		    $output = $this->get_component_loop( $component );
	    }
        return $output;
    }

	private function missing_component_message( $component ){
			return sprintf( __('The %s component is required to display the cell. Activate it first.', 'ddl-layouts'), $component );
	}

	private function missing_file_message( $component ){
		return sprintf( __('There is no template for %s component. Create one first.', 'ddl-layouts'), $component );
	}

	private function get_component_loop($component){
		$file_path = $this->get_theme_compat_dir() . 'buddypress/' . $component . '/index.php';

		ob_start();
		if( file_exists( $file_path ) ){
			include $file_path;
		} else {
			echo $this->missing_file_message( $component );
		}
		return ob_get_clean();
	}

	private function get_theme_compat_dir(){
		global $bp;
		return $bp->__get('theme_compat')->theme->dir;
	}

}

new BP_Cell();
