<?php

class WPDDL_Admin_Pages extends WPDDL_Admin
{
    protected static $instance = null;

	public $helper;

    function __construct()
    {
        parent::getInstance();

        if( is_admin() ){

            $this->page = toolset_getget( 'page' );
            $this->admin_init();

            add_action('admin_footer', array(&$this, 'include_nonce' ));
            add_action('ddl_create_layout_button', array(&$this, 'create_layout_button'));
            add_action('ddl_create_layout_for_this_page', array(&$this, 'create_layout_for_this_page'));
            add_action('ddl_create_layout_for_this_cpt', array(&$this, 'create_layout_for_this_cpt'));
            add_action('admin_menu', array($this, 'add_layouts_admin_create_layout_auto'), 12);
            if ( $this->is_layouts_admin_page() ) {
                add_action('admin_enqueue_scripts', array($this, 'preload_scripts'));

            }
        }

        // loads admin helper (duplicates layouts)
        if( class_exists('WPDDL_Plugin_Layouts_Helper') ){
            $this->helper = new WPDDL_Plugin_Layouts_Helper();
        }
    }

    public function add_layouts_admin_create_layout_auto() {
        $parent_slug = 'options.php'; // Invisible
        $page_title = __( 'Create a new Layout', 'toolset' );
        $menu_title = __( 'Create a new Layout', 'toolset' );
        $capability = DDL_CREATE;
        $menu_slug = 'dd_layouts_create_auto';
        $function = array( $this, 'create_layout_auto' );
        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
    }

    public function preload_scripts(){
        global $wpddlayout;

        $wpddlayout->enqueue_scripts(
            array(
                'ddl_create_new_layout'
            )
        );

    }

    public function include_nonce(){
        ?>
        <script type="application/javascript">
                var ddl_create_layout_error = '<?php echo esc_js( __('Failed to create the layout.', 'ddl-layouts') ); ?>';
        </script>
        <?php wp_nonce_field('wp_nonce_create_layout', 'wp_nonce_create_layout'); ?>
        <input class="js-layout-new-redirect" name="layout_creation_redirect" type="hidden" value="<?php echo admin_url( 'admin.php?page=dd_layouts_edit&amp;layout_id='); ?>" />
        <?php if ( isset( $_GET['new_layout'] ) && $_GET['new_layout'] == 'true'): ?>
            <script type="application/javascript">
                    var ddl_layouts_create_new_layout_trigger = true;
            </script>
        <?php endif; ?>
        <?php
    }

    public function create_layout_for_this_page()
    {
        global $post;
        if( user_can_create_layouts() && user_can_assign_layouts() ):
            ?>
            <a href="#" class="add-new-h2 js-create-layout-for-page create-layout-for-page"><?php printf(__('Create a new layout for this %s', 'ddl-layouts'), rtrim($post->post_type, 's') );?></a>
            <?php

        else: ?>
            <button disabled class="add-new-disabled"><?php printf(__('Create a new layout for this %s', 'ddl-layouts'), rtrim($post->post_type, 's') );?></button><br>
            <?php
        endif;
    }

    public function create_layout_for_this_cpt()
    {
        global $post;

        $post_type_object = get_post_type_object( $post->post_type );

        if( user_can_create_layouts() ):
            ?>
            <a href="#" class="add-new-h2 js-create-layout-for-post-custom create-layout-for-page"><?php printf(__('Create a new template layout for  %s', 'ddl-layouts'), $post_type_object->label );?></a>
            <?php

        else: ?>
            <button disabled class="add-new-disabled"><?php printf(__('Create a new template layout for  %s', 'ddl-layouts'), $post_type_object->label );?></button><br>
            <?php
        endif;
    }

    public function create_layout_auto() {

        // verify permissions
        if( ! current_user_can( 'manage_options' ) && WPDD_Layouts_Users_Profiles::user_can_create() && WPDD_Layouts_Users_Profiles::user_can_assign() ) {
            die( __( 'Untrusted user', 'ddl-layouts' ) );
        }

        // verify nonce
        check_admin_referer( 'create_auto' );

        // validate parameters
        $b_type = isset( $_GET['type'] ) && preg_match( '/^([-a-z0-9_]+)$/', $_GET['type'] );
        $b_class = isset( $_GET['class'] ) && preg_match( '/^(archive|page)$/', $_GET['class'] );
        $b_post_id = isset( $_GET['post'] ) && (int) $_GET['post'] >= 0;

        // validate request
        if( ! ( $b_type && $b_class && $b_post_id ) ) {
            die( __( 'Invalid parameters', 'ddl-layouts' ) );
        }

        // get parameters
        $type = $_GET['type'];
        $class = $_GET['class'];
        $post_id = (int) $_GET['post'];

        // enforce rules
        $b_page_archive = 'page' === $type && 'archive' === $class;
        if( $b_page_archive ) {
            die( __( 'Not allowed', 'ddl-layouts' ) );
        }

        // prepare processing
        if( $post_id === 0 ) {
            $post_id = null;
        }

        $layout = null;
        $layout_id = 0;

        global $toolset_admin_bar_menu;
        $post_title = $toolset_admin_bar_menu->get_name_auto( 'layouts', $type, $class, $post_id );
        $title = sanitize_text_field( stripslashes_deep( $post_title ) );

        $taxonomy = get_taxonomy( $type );
        $is_tax = $taxonomy !== false;

        $post_type_object = get_post_type_object( $type );
        $is_cpt = $post_type_object != null;


        /* Create a new Layout */
        global $wpddlayout;

        // Is there another Layout with the same name?
        $already_exists = $wpddlayout->does_layout_with_this_name_exist( $title );
        if( $already_exists ) {
            die( __( 'A layout with this name already exists. Please use a different name.', 'ddl-layouts' ) );
        }

        // Create a empty layout. No preset.
        // TODO: Pick the preset best suited (and check if Views is installed)
        $layout = WPDD_Layouts::create_layout( 12 /* colums */, 'fluid' /* layout_type */ );

        $parent_post_name = '';
        $parent_ID = apply_filters('ddl-get-default-' . WPDDL_Options::PARENTS_OPTIONS, null, WPDDL_Options::PARENTS_OPTIONS);
        if ($parent_ID) {
            $parent_post_name = WPDD_Layouts_Cache_Singleton::get_name_by_id($parent_ID);
        }

        // Define layout parameters
        $layout['type'] = 'fluid'; // layout_type
        $layout['cssframework'] = $wpddlayout->get_css_framework();
        $layout['template'] = '';
        $layout['parent'] = $parent_post_name;
        $layout['name'] = $title;

        $args = array(
            'post_title'	=> $title,
            'post_content'	=> '',
            'post_status'	=> 'publish',
            'post_type'     => WPDDL_LAYOUTS_POST_TYPE
        );
        $layout_id = wp_insert_post( $args );

        // force layout object to take right ID
        // @see WPDD_Layouts::create_layout_callback() @ wpddl.class.php
        $layout_post = get_post( $layout_id );
        $layout['id'] = $layout_id;
        $layout['slug'] = $layout_post->post_name;

        // assign layout
        if( 'archive' === $class ) {

            if( preg_match( '/^(home-blog|search|author|year|month|day)$/', $type ) ) {

                // Create a new Layout for X archives

                /* assign Layout to X archives */
                $layouts_wordpress_loop = sprintf( 'layouts_%s-page', $type );
                $wordpress_archive_loops = array( $layouts_wordpress_loop );
                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $wordpress_archive_loops, $layout_id );

            } else if( $is_tax ) {

                // Create a new Layout for Y archives

                /* assign Layout to Y archives */
                $layouts_taxonomy_loop = sprintf( 'layouts_taxonomy_loop_%s', $type );
                $wordpress_archive_loops = array( $layouts_taxonomy_loop );
                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $wordpress_archive_loops, $layout_id );


            } else if( $is_cpt ) {

                // Create a new Layout for Z archives

                /* assign Layout to Z archives */
                $layouts_cpt = sprintf( 'layouts_cpt_%s', $type );
                $wordpress_archive_loops = array( $layouts_cpt );
                $wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save( $wordpress_archive_loops, $layout_id );

            } else {
                die( __( 'An unexpected error happened.', 'ddl-layouts' ) );
            }

        } else if( 'page' === $class ) {

            if( '404' === $type ) {

                // Create a new Layout for Error 404 page

                /* assign Layout to 404 page */
                $wordpress_others_section = array( 'layouts_404_page' );
                $wpddlayout->layout_post_loop_cell_manager->handle_others_data_save( $wordpress_others_section, $layout_id );

            } else if( $is_cpt ) {

	            // Create a new Layout for Ys

	            /* assign Layout to Y */
	            $post_types = array( $type );
	            $wpddlayout->post_types_manager->handle_post_type_data_save( $layout_id, $post_types, $post_types );

            } else if( 'page' === $type ) {

                // Create a new Layout for 'Page Title'

                /* assign Layout to Page */
                $posts = array( $post_id );
                $wpddlayout->post_types_manager->update_post_meta_for_post_type( $posts, $layout_id );

            } else {
                die( __( 'An unexpected error happened.', 'ddl-layouts' ) );
            }

        }

        // update changes
        WPDD_Layouts::save_layout_settings( $layout_id, $layout );

        // redirect to editor (headers already sent)
        $edit_link = $toolset_admin_bar_menu->get_edit_link( 'layouts', false, $type, $class, $layout_id );

        // forward url parameter toolset_help_video
        if( isset( $_GET['toolset_help_video'] ) )
            $edit_link = add_query_arg( 'toolset_help_video', $_GET['toolset_help_video'], $edit_link );

        // forward url parameter ref
        if( isset( $_GET['ref'] ) )
            $edit_link = add_query_arg( 'ref', $_GET['ref'], $edit_link );

        $exit_string = '<script type="text/javascript">'.'window.location = "' . $edit_link . '";'.'</script>';
        exit( $exit_string );

    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Admin_Pages();
        }
        return self::$instance;
    }
}
