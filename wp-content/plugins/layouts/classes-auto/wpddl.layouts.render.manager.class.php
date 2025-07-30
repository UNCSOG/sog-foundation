<?php
class WPDD_Layouts_RenderManager{
    private static $instance;
    private $render_errors;
    private $loop_found;
    private $attachment_markup = '';
    private $content_removed_for_CRED= false;
    private $loop_has_no_posts = false;
	private $password_protection_form_rendered = false;

	private $views_installed = false;
	private $views_flavour_installed = 'classic';

    private function __construct(  ){
        $this->render_errors = array();
		$this->loop_found = false;

		$this->views_installed = apply_filters( 'toolset_is_views_available', false );
		$this->views_flavour_installed = apply_filters( 'toolset_views_flavour_installed', 'classic' );

        if( !is_admin() && ( !defined('DOING_AJAX') || DOING_AJAX === false )  ){
            add_action('wp_head', array($this,'wpddl_frontend_header_init'));
            add_action('wpddl_before_header', array($this, 'before_header_hook'));
            add_filter('ddl_render_cell_content', array(&$this,'fix_attachment_body'), 10, 3 );
            add_filter( 'ddl_render_cell_content', array(&$this, 'fix_cred_link_content_template_when_form_displays'), 10, 3 );
            add_filter('prepend_attachment', array(&$this, 'attachment_handler'), 999);
            add_action( 'ddl_before_frontend_render_cell', array(&$this, 'prevent_CRED_duplication_generic'), 1001, 2 );
            add_action('ddl_before_frontend_render_cell', array(&$this,'prevent_CRED_duplication_content_template'), 8, 2 );
            //add_action( 'ddl_apply_the_content_filter_in_post_content_cell', array(&$this,'prevent_the_content_filter_to_apply'), 99, 2 );
            add_action( 'ddl_after_frontend_render_cell', array(&$this, 'restore_the_content_for_cred'), 10, 2 );
            add_action('pre_get_posts', array(&$this, 'maybe_disable_views_wordpress_archives'), 0);
            add_action('wp', array(&$this, 'maybe_disable_views_content_template_for_single'), 0);
			add_action('wp', array(&$this, 'maybe_disable_views_content_template_for_archives'), 0);
            add_action('wp', array(&$this, 'maybe_enable_archive_loop_replacement'));
            add_filter('ddl-content-template-cell-do_shortcode', array(&$this, 'prevent_cred_recursion'), 10, 2);
            add_action('get_header', array(&$this, 'fix_for_woocommerce_genesis'), 1 );
            add_filter('ddl_render_cell_content', array(&$this, 'message_if_menu_is_not_assigned'),11,2);
            add_filter('ddl-is_ddlayout_assigned', array(&$this, 'fix_cred_preview_render'), 99, 1 );
	        add_filter('ddl-is_ddlayout_assigned', array(&$this, 'is_assigned_through_argument'), 100, 1 );
            add_filter( 'ddl-template_include_force_option', array(&$this, 'template_include_force_option'), 99, 1 );
            add_filter('the_content', array(&$this, 'render_template_layout_for_post'), WPDDL_THE_CONTENT_PRIORITY_RENDER, 1);
            add_filter( 'ddl-get-parent-layout-for-render', array( &$this, 'get_parent_layout_preview'), 99, 3);


            // Fix for 'Toolset Starter' theme.
            // When WooCommerce is enabled, the '/shop' page does not recognize the assigned layout.
            if( 'toolset starter' == strtolower( wp_get_theme() )) {
                add_action('template_redirect', array(&$this, 'fix_for_toolset_starter_wc_redirect'), 999);
            }
        }
    }

    public function get_parent_layout_preview( $parent_layout, $parent_slug, $current_layout ){
			if( !isset( $_POST['parent_layout_preview'] ) ){
				return $parent_layout;
			}

			$jsonObject = new WPDD_json2layout();
	        $layout = $jsonObject->json_decode( stripslashes( $_POST['parent_layout_preview'] ) );

	        if( null === $layout ){
		        return $parent_layout;
	        }

			if( $layout->get_post_slug() === $parent_slug ){
				return $layout;
			}

			return $parent_layout;
    }

    public function restore_the_content_for_cred( $cell, $renderer ){
        if( $this->content_removed_for_CRED ){
            add_filter('the_content', array('CRED_Helper', 'replaceContentWithForm'), 1000);
            $this->content_removed_for_CRED = false;
        }
    }

    public function template_include_force_option( $option ){
        if( $_GET && ( isset( $_GET['cred_form_preview'] ) || isset( $_GET['cred_user_form_preview'] ) ) ){
            $option = 2;
        }

        return $option;
    }

    public function fix_cred_preview_render( $bool ){
        if( $_GET && ( isset( $_GET['cred_form_preview'] ) || isset( $_GET['cred_user_form_preview'] ) ) ){
            $bool = false;
        }

        return $bool;
    }

    function fix_for_toolset_starter_wc_include($template) {
        // Only when WC is enabled/active.
        if( $this->is_woocommerce_enabled() ) {
            // 'Toolset Starter' theme's page.php already has the cases to check the assigned layout.
            // We just need to enforce or override WC's default handling for /shop and Product pages,
            // to use page.php.
            $new_template = locate_template( array( 'page.php' ) );

            if ( '' != $new_template ) {
                return $new_template ;
            }
        }

        return $template;
    }

    function fix_for_toolset_starter_wc_redirect() {
        add_filter('template_include', array(&$this, 'fix_for_toolset_starter_wc_include'), 103, 1);
    }

    /*
     * Add message if menu cell is palaced but menu is not assigned
     */
    function message_if_menu_is_not_assigned($content, $cell){

	$available_types_of_cells = array('menu-cell', 'avada-menu','avada-secondary-menu','2016-header-menu','divi-primary-navigation','genesis-menu','primary','secondary','navbar');
	if (
	    (in_array($cell->get_cell_type(), $available_types_of_cells) && trim(strip_tags($content)) === '')
	    /* This case below covers only situation when we have menu-cell placed on layout but menu is not assigned or created (or have 0 items inside)
	     * Problem with this cell is that content output is not empty even if you don't have any items inside menu,
	     * this is very rare situation but it is covered
	     */
	    || (count(get_terms('nav_menu')) === 0 && $cell->get_cell_type() === 'menu-cell')
	) {
	    $alert_message = '<p>' . sprintf(__('You currently have no menus assigned to this theme location. Go to %sAppearance -> Menus -> Manage Locations%s and assign a menu to appropriate location.', 'ddl-layouts'), '<a href="' . admin_url() . 'nav-menus.php?action=locations">', '</a>') . '</p>';
	    $content = '<div class="alert alert-warning">' . $alert_message . '</div>';
	}
	return $content;
    }

    /**
     * WooCommerce / Generic Fixes
     */
    public function is_woocommerce_enabled() {
        if( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
            return true;
        } else {
            return false;
        }
    }

    public function is_woocommerce_shop() {
        if( $this->is_woocommerce_enabled() ) {
            // Check if 'Shop' page has a separate layout assigned.
            $shop_page_id = get_option( 'woocommerce_shop_page_id' );
            $layout_selected = get_post_meta( $shop_page_id, WPDDL_LAYOUTS_META_KEY, true );
        }

        // If it's 'Shop' page and has a separate layout assigned.
        if( isset( $layout_selected ) && $layout_selected && function_exists( 'is_shop' ) && is_shop() ) {
            return $layout_selected;
        } else {
            return false;
        }
    }

    public function is_woocommerce_product() {
        if( $this->is_woocommerce_enabled() && function_exists('is_product') && is_product() ) {
            return true;
        } else {
            return false;
        }
    }

    public function is_woocommerce_archive() {
        if(
            $this->is_woocommerce_enabled() && (
                ( function_exists('is_product_category') && is_product_category() ) ||
                ( function_exists('is_product_taxonomy') && is_product_taxonomy() ) ||
                ( function_exists('is_product_tag') && is_product_tag() )
            )
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Woocommerce/Genesis Fix
     */
    public function fix_for_woocommerce_genesis(){
        $obj = $this->get_queried_object();

        if( function_exists( 'is_shop' ) && is_shop() && WPDD_Utils::is_wp_post_object( $obj )  ){
            $layout = get_post_meta( $obj->ID, WPDDL_LAYOUTS_META_KEY, true );

            if( $layout ){
                add_filter( 'ddl-is_ddlayout_assigned', array(&$this, 'return_true'), 10, 1 );
                add_filter('get_layout_id_for_render', array(&$this, 'return_layout_id'), 10, 2 );
            }
        }

    }

    public function return_true( $bool ){
        return true;
    }

    public function return_layout_id( $id, $args ){
        $obj = $this->get_queried_object();
        if( WPDD_Utils::is_wp_post_object( $obj ) ){
            return get_post_meta( $obj->ID, WPDDL_LAYOUTS_META_KEY, true );
        }
        return $id;
    }

    //TODO: let's see if we can remove this sooner or later and delagate CRED to fix its own problems
    public function prevent_cred_recursion( $content, $cell ){

        if( class_exists('CRED_Helper') && strpos($content, '[cred_') !== false){
	        $content = preg_replace('~\[(cred_.+[^\[\]]+)\]~', '[[\1]]', $content);
        }

        return $content;
    }




    function fix_attachment_body( $content, $cell, $renderer ){
        global $post;

        // Do not render attachment post type posts' bodies automatically
        if( WPDD_Utils::is_wp_post_object( $post ) && $post->post_type === 'attachment' && $this->attachment_markup ){
            if( $cell->get_cell_type() === "cell-post-content" ){
                return $content;
            } else {
                $content = WPDD_Utils::str_replace_once( $this->attachment_markup , '', $content);
            }
        }
        return $content;
    }

    /**
     * @param $cell
     * @param $renderer
     * Prevents Visual Editor cells to render CRED
     */
    public function prevent_CRED_duplication_generic($cell, $renderer){
        if( ( isset( $_GET['cred-edit-form']) || isset( $_GET['cred-edit-user-form'] ) ) &&
            class_exists('CRED_Helper') && $cell->get_cell_type( ) === 'cell-text'
        ){
            $cred_links = $cell->content_content_contains( array( 'cred_link_form', 'cred_link_user_form' ) );
            $post_body = $cell->content_content_contains( array( 'wpv-post-body') );

            if( $cred_links && $post_body ){
                $this->hide_cred_cred_links($_GET); // wpv post body renders the form keep the filter
            } elseif( $cred_links && !$post_body ) {
                $this->hide_cred_cred_links($_GET); // the_content filter renders the form keep the filter
            } elseif( !$cred_links && $post_body ) {
                // do nothing // wpv post body renders the form keep the filter
                $this->content_removed_for_CRED = false;
            } else {
                remove_filter('the_content', array('CRED_Helper', 'replaceContentWithForm'), 1000);
                $this->content_removed_for_CRED = true; // the form is rendered directly by do_shortcode, remove the filter to prevent duplication
            }
        }
    }

    public function prevent_the_content_filter_to_apply( $bool, $cell ){
	    if( isset( $_GET['cred-edit-form']) || isset( $_GET['cred-edit-user-form'] ) ){
		    $bool = false;
	    }
	    return $bool;
    }

    public function fix_cred_link_content_template_when_form_displays( $content, $cell, $renderer ){
        if( ( isset( $_GET['cred-edit-form']) || isset( $_GET['cred-edit-user-form'] ) ) &&
            class_exists('CRED_Helper')
        ){

            if( $cell->get_cell_type( ) === 'cell-content-template' && WPDD_Utils::string_contanins_strings( $content, array( '?cred-edit-user-form=', '?cred-edit-form=' ) ) ){
                $content = $this->hide_cred_cred_links($_GET, false).$content;
            }
        }

        return $content;
    }

    function hide_cred_cred_links( $get, $echo = true ){
        $selector = '';
        if( isset($get['cred-edit-form']) ){
            $selector = 'cred-edit-form';
        } elseif ( isset($get['cred-edit-user-form']) ){
            $selector = 'cred-edit-user-form';
        }

        if( $selector !== '' ):
            ob_start();?>
            <style type="text/css">
                <!--
                a[href*="<?php echo $selector;?>"]{display:none;}
                -->
            </style>
            <?php
            if( $echo ){
                echo ob_get_clean();
            } else {
                return ob_get_clean();
            }
        endif;
        return '';
    }

    /**
     * @param $cell
     * @param $renderer
     * This is equivalent for CT cell preventing the_content filter to be applied if necessary
     */
    public function prevent_CRED_duplication_content_template( $cell, $renderer ){
        $content = $cell->get_content();
        $what_page = isset( $content['page'] ) && $content['page'] ? $content['page'] : '';
        if( isset( $_GET['cred-edit-form']) &&
            class_exists('CRED_Helper') &&
            $cell->get_cell_type() === 'cell-content-template' &&
            ( $cell->check_if_cell_renders_post_content( ) === false ||
                $what_page == 'this_page' )
        ){
            add_filter( 'wpv_filter_wpv_render_view_template_force_suppress_filters', array(&$this, 'wpv_render_view_template_force_suppress_filters_callback' ), 999, 5 );

        }
    }

    public function wpv_render_view_template_force_suppress_filters_callback( $bool, $ct_post, $post_in, $current_user_in, $args ){
        return true;
    }

    function attachment_handler($html){
        $this->attachment_markup = $html;
        return $html;
    }

    function get_layout_renderer( $layout, $args )
    {
        $manager = new WPDD_layout_render_manager($layout );
        $renderer = $manager->get_renderer( );
        // set properties  and callbacks dynamically to current renderer
        if( is_array($args) && count($args) > 0 )
        {
            $renderer->set_layout_arguments( $args );
        }
        return $renderer;
    }

    function get_query_post_if_any( $queried_object)
    {
        return 'object' === gettype( $queried_object ) && get_class( $queried_object ) === 'WP_Post' ? $queried_object : null;
    }

    function get_queried_object()
    {
        global $wp_query;
        $queried_object = $wp_query->get_queried_object();
        return $queried_object;
    }

    function is_assigned_through_argument( $bool ){
        if( isset( $_GET['layout_id'] ) ){
            return true;
        }

        return $bool;
    }

	function get_layout_id_for_render( $layout, $args = null ) {

        // if there is a URL argument then let it win over everything else
		if ( isset( $_GET['layout_id'] ) ) {
			$id = $_GET['layout_id'];

			return apply_filters( 'get_layout_id_for_render', (int) $id, $layout );
		}

        global $wpddlayout;

        $options = is_null( $args ) === false && is_array( $args ) === true ? (object) $args : false;

        $allow_overrides = $options && property_exists( $options, 'allow_overrides' ) ? $options->allow_overrides : true;

        $id = 0;

        if ($layout) {
            $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout);
        }

        if( $allow_overrides === true ){
            // If it's 'Shop' page and has a separate layout assigned.
	        $layout_selected = $this->is_woocommerce_shop();

            if( false !== $layout_selected ) {
                global $post;

                // WC Hack: if there's no product added, but a layout is assigned to 'shop' page.
                // This hack prevents falling into a PHP Notice.
                $tmpPostType = 'page';

                if( isset( $post ) ) {
                    $tmpPostType = $post->post_type;
                }

                $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);
                $option = $wpddlayout->post_types_manager->get_layout_to_type_object($tmpPostType);

                if (is_object($option) && property_exists($option, 'layout_id') && (int)$option->layout_id === (int)$id) {
                    $id = $option->layout_id;
                }
            }
            elseif( $this->is_woocommerce_product() ) { // If product page
                global $post;

                if( $post !== null )
                {

                    $post_id = $post->ID;
                    $layout_selected = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

                    if ( $layout_selected ) {
                        $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);
                        $option = $wpddlayout->post_types_manager->get_layout_to_type_object($post->post_type);

                        if( is_object( $option ) && property_exists( $option, 'layout_id') && (int) $option->layout_id === (int) $id )
                        {
                            $id = $option->layout_id;
                        }
                    }
                }
            }
            elseif( $this->is_woocommerce_archive() ) { // If Product archive (i.e. post type archive, category, tag or tax)
                $term =  $this->get_queried_object();
                if ( $term && property_exists( $term, 'taxonomy' ) && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy) ) {
                    $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy);
                }

            // when blog is front
            }
            elseif( is_front_page() && is_home() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG) ){
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG);

            // when blog is not front
            }
            elseif ((is_home()) && (!(is_front_page())) && (!(is_page())) && ($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG)) && !get_option( 'page_for_posts' )) {
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG);
            }
            elseif($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_STATIC_BLOG) && is_home() && (!(is_front_page())) && get_option( 'page_for_posts' )){
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_STATIC_BLOG);
            }
            elseif($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_HOME) && is_front_page() && (!(is_home())) && get_option('page_on_front')){
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_HOME);
            }
            elseif ( is_post_type_archive()  ) {

                $post_type_object = $this->get_queried_object();

                if ( $post_type_object && property_exists( $post_type_object, 'public' ) && $post_type_object->public && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type_object->name) ) {
                    $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type_object->name);
                }elseif ($post_type_object && property_exists($post_type_object, 'taxonomy') && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX . $post_type_object->taxonomy)) {
                    $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX . $post_type_object->taxonomy );
                } elseif( is_search() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_SEARCH) ){
                    $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_SEARCH);
                }
            }
            elseif ( is_archive() && ( is_tax() || is_category() || is_tag() ) ) {

                $term =  $this->get_queried_object();
                if ( $term && property_exists( $term, 'taxonomy' ) && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy) ) {
                    $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX.$term->taxonomy);
                }

            }
            // Check other archives
            elseif ( is_search()  && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_SEARCH) ) {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_SEARCH);
            }
            elseif ( is_author() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_AUTHOR ) ) {
                $author = WPDD_layout_post_loop_cell_manager::OPTION_AUTHOR;
                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( $author );
            }
            elseif ( is_year() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_YEAR) ) {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_YEAR);
            }
            elseif ( is_month() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_MONTH) ) {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_MONTH);
            }
            elseif ( is_day() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_DAY) ) {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_DAY);
            }
            elseif( is_404() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_404 ) )
            {

                $id = $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_404 );
            }
            elseif( is_front_page() && get_option( 'show_on_front' ) == 'page' && get_option( 'page_on_front' ) != 0 ) {
                // When a static page is assigned to the Reading settings Posts page and that page has a layout assigned, use it
                $static_page_for_posts = get_option( 'page_on_front' );
                $layout_selected = get_post_meta( $static_page_for_posts, WPDDL_LAYOUTS_META_KEY, true );
                if ( $layout_selected ) {
                    $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);
                }
            }
            elseif( is_home() && get_option( 'show_on_front' ) == 'page' && get_option( 'page_for_posts' ) != 0 ) {
                // When a static page is assigned to the Reading settings Posts page and that page has a layout assigned, use it
                $static_page_for_posts = get_option( 'page_for_posts' );
                $layout_selected = get_post_meta( $static_page_for_posts, WPDDL_LAYOUTS_META_KEY, true );
                if ( $layout_selected ) {
                    $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);
                }
            }
            else{

                global $post;

                if( $post !== null && is_singular() )
                {
                    $post_id = $post->ID;

                    $layout_selected = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

                    if ( $layout_selected ) {

                        $id = WPDD_Layouts_Cache_Singleton::get_id_by_name($layout_selected);

                        $option = $wpddlayout->post_types_manager->get_layout_to_type_object($post->post_type);

                        if( is_object( $option ) && property_exists( $option, 'layout_id') && (int) $option->layout_id === (int) $id )
                        {
                            $id = $option->layout_id;
                        }
                    }
                }
            }
        }

        return apply_filters('get_layout_id_for_render', (int) $id, $layout );
    }

    function get_layout_content_for_render( $layout, $args )
    {
        $id = $this->get_layout_id_for_render( $layout, $args );

        $content = '';

        if ($id) {

            // Check for preview mode
            $layout = $this->get_rendered_layout($id);

            if ($layout) {
                $content = $this->get_rendered_layout_content( $layout, $args );
            } else {
                if( user_can_create_layouts() ){
	                $content = '<p>' . __('Please check the layout you are trying to render actually exists.', 'ddl-layouts') . '</p>';
                }
            }
        } else {
            if ( !$layout && user_can_assign_layouts() ) {
                $content = '<p>' . __('You need to select a layout for this page. The layout selection is available in the page editor.', 'ddl-layouts') . '</p>';
            }
        }

        return apply_filters('get_layout_content_for_render', $content, $this, $layout, $args );
    }

    private function get_rendered_layout_content( $layout, $args ){
        $renderer = $this->get_layout_renderer( $layout, $args );
        //$renderer = new WPDD_layout_render($layout);
        $content = $renderer->render( );

        $render_errors = $this->get_render_errors();
        if (sizeof($render_errors)) {
            $content .= '<p class="alert alert-error"><strong>' . __('There were errors while rendering this layout.', 'ddl-layouts') . '</strong></p>';
            foreach($render_errors as $error) {
                $content .= '<p class="alert alert-error">' . $error . '</p>';
            }
        }
        return $content;
    }

    public function get_rendered_layout( $id ){

        $layout = null;
        $old_id = $id;

        if ( isset($_GET['layout_id']) ) {
            $id = $_GET['layout_id'];
        }

        if( isset( $_POST['layout_preview'] ) && $_POST['layout_preview'] ){

            $json_parser = new WPDD_json2layout();
            $layout = $json_parser->json_decode( stripslashes( $_POST['layout_preview'] ) );

        } else {
            $layout = WPDD_Layouts::get_layout_from_id( $id );
            if (!$layout && isset($_GET['layout_id'])) {
                if ($id != $old_id) {
                    $layout = WPDD_Layouts::get_layout_from_id( $old_id );
                }
            }
        }
        return $layout;
    }

    function wpddl_frontend_header_init(){
        global $wpddlayout;

        $wpddlayout->header_added = TRUE;

        $queried_object = $this->get_queried_object();
        $post = $this->get_query_post_if_any( $queried_object);


        if( null === $post ) return;
        // if there is a css enqueue it here
        $post_id = $post->ID;

        $layout_selected = get_post_meta($post_id, WPDDL_LAYOUTS_META_KEY, true);

        if( $layout_selected ){
            $id = $wpddlayout->get_post_ID_by_slug( $layout_selected, WPDDL_LAYOUTS_POST_TYPE );
            $header_content = get_post_meta($id, 'dd_layouts_header');
            echo isset($header_content[0]) ? $header_content[0] : '';
        }
    }

    function before_header_hook(){
        if (isset($_GET['layout_id'])) {
            $layout_selected = $_GET['layout_id'];
        } else {
            $post_id = get_the_ID();
            $layout_selected = WPDD_Layouts::get_layout_settings( $post_id, false );
        }
        if($layout_selected>0){
            //$layout_content = get_post_meta($layout_selected, WPDDL_LAYOUTS_SETTINGS);

            $layout_content =  WPDD_Layouts::get_layout_settings_raw_not_cached( $layout_selected, false );

            if (sizeof($layout_content) > 0) {
                $test = new WPDD_json2layout();
                $layout = $test->json_decode($layout_content[0]);
                $manager = new WPDD_layout_render_manager($layout);
                $renderer = $manager->get_renderer( );
                $html = $renderer->render_to_html();

                echo $html;
            }
        }
    }

    function record_render_error($data) {
        if ( !in_array($data, $this->render_errors) ) {
            $this->render_errors[] = $data;
        }
    }

    function get_render_errors() {
        return $this->render_errors;
    }

    public function item_has_ddlayout_assigned()
    {
	    // if there is a URL argument then let it win over everything else
	    if ( isset( $_GET['layout_id'] ) ) {
		    return true;
	    }

        global $wpddlayout;

        // If it's 'Shop' page and has a separate layout assigned.
        if( $this->is_woocommerce_shop() ) {
            return true;
        }
        elseif ( $this->is_woocommerce_product() ) { // If product page
	        return $this->single_has_layout();
        }
        elseif( is_front_page() && is_home() && $wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG) ){
            return true;
        // when blog is not front
        } elseif ((is_home()) && (!(is_front_page())) && (!(is_page())) && ($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_BLOG)) && !get_option( 'page_for_posts' )) {
            return true;
        }
        elseif($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_STATIC_BLOG) && is_home() && (!(is_front_page())) && get_option( 'page_for_posts' )){
            return true;
        }
        elseif($wpddlayout->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_HOME) && is_front_page() && (!(is_home())) && get_option('page_on_front')){
            return true;
        }
        elseif ( is_post_type_archive() || $this->is_woocommerce_archive() ) {
            $post_type_object = $this->get_queried_object();

            if ($post_type_object && property_exists($post_type_object, 'public') && $post_type_object->public && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX . $post_type_object->name)) {
                return true;
            } elseif ($post_type_object && property_exists($post_type_object, 'taxonomy') && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX . $post_type_object->taxonomy)) {
                return true;
            }

        }
        elseif (is_archive() && (is_tax() || is_category() || is_tag())) {
            $term = $this->get_queried_object();
            if ($term && property_exists($term, 'taxonomy') && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_TAXONOMY_PREFIX . $term->taxonomy)) {
                return true;
            }
        } // Check other archives
        elseif (is_search() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_SEARCH)) {

            return true;
        }
        elseif (is_author() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_AUTHOR)) {
            return true;
        }
        elseif (is_year() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_YEAR)) {

            return true;
        }
        elseif (is_month() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_MONTH)) {

            return true;
        }
        elseif (is_day() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_DAY)) {

            return true;
        }
        elseif (is_404() && $wpddlayout->layout_post_loop_cell_manager->get_option(WPDD_layout_post_loop_cell_manager::OPTION_404)) {
            return true;
        }
        elseif( is_front_page() && get_option( 'show_on_front' ) == 'page' && get_option( 'page_on_front' ) != 0 ) {
            // When a static page is assigned to the Reading settings Posts page and that page has a layout assigned, use it
            $static_page_for_posts = get_option( 'page_on_front' );
            $layout_selected = get_post_meta( $static_page_for_posts, WPDDL_LAYOUTS_META_KEY, true );
            if ( $layout_selected ) {
                return true;
            }
        }
        elseif( is_home() && get_option( 'show_on_front' ) == 'page' && get_option( 'page_for_posts' ) != 0 ) {
            // When a static page is assigned to the Reading settings Posts page and that page has a layout assigned, use it
            $static_page_for_posts = get_option( 'page_for_posts' );
            $layout_selected = get_post_meta( $static_page_for_posts, WPDDL_LAYOUTS_META_KEY, true );
            if ( $layout_selected ) {
                return true;
            }
        }
        else {
            if( is_singular() ){
                return $this->single_has_layout();
            }
        }
        return false;
    }

    private function single_has_layout(){
	    global $post;

	    if( WPDD_Utils::is_wp_post_object( $post ) ){

		    $assigned_template = get_post_meta($post->ID, WPDDL_LAYOUTS_META_KEY, true);

		    if ( !$assigned_template ) {
			    return false;
		    }

		    return $assigned_template !== 'none';
	    }
    }

    public function render_template_layout_for_post($content){
		if (
			'blocks' === $this->views_flavour_installed
			&& is_wpv_content_template_assigned()
		) {
			// Using Toolset Blocks in a singular post with a CT assignment, skip Layouts.
			return $content;
		}

	    global $post;

        if( apply_filters( 'ddl-is_integrated_theme', false ) ){
	        if ( post_password_required( $post ) ) {
	            if( ! $this->password_protection_form_rendered ){
		            $this->password_protection_form_rendered = true;
		            return get_the_password_form( $post );
                } else {
	                return '';
                }

	        } else {
		        return $content;
            }
        }

        if( is_feed() ){
            return $content;
        }

	    $post = get_post( $post );
	    if ( is_null( $post ) ) {
		    return $content;
	    }

		if ( ! is_singular() ) {
			return $content;
		}

	    if ( post_password_required( $post ) ) {
		    return $content;
	    }

		//check if there's a layout assigned to current scenario
		if (  ! is_ddlayout_assigned() && ! isset( $_GET['layout_id'] ) ) {
			return $content;
		}

        // Core functions that we accept calls from.
        $the_content_core = array(
				'the_content'
		);
		// Known theme functions that we accept calls from.
		$the_content_themes = array(
			// WPTouch theme(s)
			'wptouch_the_content'
		);
		// Known plugin functions that we accept calls from.
		$the_content_plugins = array(
			// Elementor Pro content widget
			'ElementorPro\Modules\ThemeBuilder\Widgets\Post_Content::render'
		);
		// known funcions that we should not allow
		$the_content_blacklist = array(
				'require', 'require_once', 'include', 'include_once',
				'locate_template', 'load_template',
				'apply_filters', 'call_user_func_array',
				'wpcf_fields_wysiwyg_view'
		);

	    if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
			// phpcs:ignore PHPCompatibility.PHP.NewFunctionParameters.debug_backtrace_limitFound
			$db = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 ); // @codingStandardsIgnoreLine
		} else {
	    	// phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
			$db = debug_backtrace();
		}

	    $function_candidate = array();

	    // From php7 debug_backtrace() has changed, and the target function might be at index 2 instead of 3 as in php < 7
	    // Also, from WP 4.7 the new way to manage hooks adds some intermediary items so let's cover our backs reaching to 5

	    if ( isset( $db[5]['function'] ) ) {
			if ( isset( $db[5]['class'] ) ) {
				$function_candidate[] = $db[5]['class'] . '::' . $db[5]['function'] ;
			} else {
				$function_candidate[] = $db[5]['function'];
			}
		}

		if ( isset( $db[4]['function'] ) ) {
			if ( isset( $db[4]['class'] ) ) {
				$function_candidate[] = $db[4]['class'] . '::' . $db[4]['function'] ;
			} else {
				$function_candidate[] = $db[4]['function'];
			}
		}

		if ( isset( $db[3]['function'] ) ) {
			if ( isset( $db[3]['class'] ) ) {
				$function_candidate[] = $db[3]['class'] . '::' . $db[3]['function'] ;
			} else {
				$function_candidate[] = $db[3]['function'];
			}
		}

		if ( isset( $db[2]['function'] ) ) {
			if ( isset( $db[2]['class'] ) ) {
				$function_candidate[] = $db[2]['class'] . '::' . $db[2]['function'] ;
			} else {
				$function_candidate[] = $db[2]['function'];
			}
		}

		if ( isset( $db[1]['function'] ) ) {
			if ( isset( $db[1]['class'] ) ) {
				$function_candidate[] = $db[1]['class'] . '::' . $db[1]['function'] ;
			} else {
				$function_candidate[] = $db[1]['function'];
			}
		}

		$function_candidate = array_diff( $function_candidate, $the_content_blacklist );

	    if ( empty( $function_candidate ) ) {
			// We don't have a non-forbidden calling function.
		    if ( current_user_can( 'administrator' ) ) {
			    if (WPDDL_DEBUG) {
				    $content = __( '<strong>Template Layout debug: </strong>There are no valid calling functions', 'ddl-layouts' )
						. '<br />' . $content;
			    }
		    }
		    return $content;
	    }

	    $function_ok = false;

	    foreach ( $function_candidate as $function_candidate_for_content ) {
		    if (
			    in_array( $function_candidate_for_content, $the_content_core )
			    || in_array( $function_candidate_for_content, $the_content_themes )
			    || in_array( $function_candidate_for_content, $the_content_plugins )
		    ) {
			    $function_ok = true;
		    }
	    }

	    if ( ! $function_ok ) {
		    // We don't accept calls from the calling function.
		    if ( current_user_can( 'administrator' ) ) {
			    if (WPDDL_DEBUG) {
				    $function_candidate_string = implode( ', ', $function_candidate );
				    $content = sprintf(
					               __( '<strong>Template Layout debug: </strong>Calling functions are <strong>%s</strong>', 'ddl-layouts' ),
					               $function_candidate_string
				               ) . '<br />' . $content;
			    }
		    }
		    return $content;
	    }

		//display layout inside the content
		$content = apply_filters('get_the_ddlayout', '', array('initialize_loop' => false) );

        return $content;
    }

    /**
     * Check first if it's necessary to disable WordPress Archives execution from Views
     * @param object $query The main WordPress query object
     */
    public function maybe_disable_views_wordpress_archives( $query ) {
		if (
			'blocks' === $this->views_flavour_installed
			&& is_wpv_wp_archive_assigned()
		) {
			// Using Toolset Blocks in an archive with a WPA assignment, skip Layouts.
			return;
		}

        if((is_archive() || is_home() || is_search()) && is_ddlayout_assigned() && $query->is_main_query()){
			// First, disable the WordPress Archives execution from Views
            add_filter('wpv_filter_wpv_override_wordpress_archive', '__return_false');
			// Second, prevent 404s by forcing the settings from the loop Views cell, if any.
			$layout_id = $this->get_layout_id_for_render( 0 );
			$layout = WPDD_Layouts::get_layout_from_id( $layout_id );
            if( ! $layout ){
                return;
            }
			$this->disable_views_wordpress_archives($layout, $query);
        }
    }
    /**
     * Disable the WordPress Archives execution from Views when the current archive has a layout assigned.
     *
     * @param object $query The main WordPress query object
     * @param object $layout Layout object
     *
     * @since unknown
     * @since 2.0.0 Force the settings from the loop Views cell used in this archive, if any, to avoid 404s
     */
    public function disable_views_wordpress_archives($layout, $query ){
		if (
			'blocks' === $this->views_flavour_installed
			&& is_wpv_wp_archive_assigned()
		) {
			// Using Toolset Blocks in an archive with a WPA assignment, skip Layouts.
			return;
		}

        $post_loop_cells = $layout->get_cells_of_type( 'post-loop-views-cell' );
        if ( count( $post_loop_cells ) > 0 ) {
            // There should only be one loop View cell
            foreach( $post_loop_cells as $wpa_cell ) {
                $wpa_id = $wpa_cell->get('ddl_layout_view_id');
                $archive_settings = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $wpa_id );
                do_action( 'wpv_action_apply_archive_query_settings', $query, $archive_settings, $wpa_id );
            }
        }
    }

	public function maybe_disable_views_content_template_for_single() {
		if (
			'blocks' === $this->views_flavour_installed
			&& is_wpv_content_template_assigned()
		) {
			// Using Toolset Blocks in an archive with a CT assignment, skip Layouts.
			return;
		}

		if(is_singular() && is_ddlayout_assigned() ){
            add_filter('wpv_filter_wpv_override_content_template_for_single', '__return_false');
        }
	}

	public function maybe_disable_views_content_template_for_archives() {
		if (
			'blocks' === $this->views_flavour_installed
			&& is_wpv_wp_archive_template_assigned()
		) {
			// Using Toolset Blocks in a singular post with a CT assignment, skip Layouts.
			return;
		}

		if((is_archive() || is_home() || is_search()) && is_ddlayout_assigned()){
            add_filter('wpv_filter_wpv_override_content_template_for_archive', '__return_false');
        }
	}

	private function maybe_create_dummy_post_for_archive( ){
		global $wp_query;

		if ( ! have_posts() ) {
			// We need to handle empty loops and force the loop processing
			// Create a dummy WP_Post and set the post count to 1
			// That will fire the loop_start and loop_end hooks
			$wp_query->post_count = 1;
			$dummy_post_obj = (object) array(
				'ID'				=> 9999999999999,
				'post_author'		=> '1',
				'post_name'			=> '',
				'post_type'			=> '',
				'post_title'		=> '',
				'post_date'			=> '0000-00-00 00:00:00',
				'post_date_gmt'		=> '0000-00-00 00:00:00',
				'post_content'		=> '',
				'post_excerpt'		=> '',
				'post_status'		=> 'publish',
				'comment_status'	=> 'closed',
				'ping_status'		=> 'closed',
				'post_password'		=> '',
				'post_parent'		=> 0,
				'post_modified'		=> '0000-00-00 00:00:00',
				'post_modified_gmt'	=> '0000-00-00 00:00:00',
				'comment_count'		=> '0',
				'menu_order'		=> '0'
			);
			$dummy_post = new WP_Post( $dummy_post_obj );
			$wp_query->posts = array( $dummy_post );
			$this->loop_has_no_posts = true;
		}
    }

	public function maybe_enable_archive_loop_replacement() {
		if (
			'blocks' === $this->views_flavour_installed
			&& is_wpv_wp_archive_assigned()
		) {
			// Using Toolset Blocks in an archive with a WPA assignment, skip Layouts.
			return;
		}

		if (
			(is_archive() || is_home() || is_search())
			&& is_ddlayout_assigned()
			&& ! apply_filters( 'ddl-is_integrated_theme', false )
		){
            add_action( 'loop_start', array( $this, 'loop_start' ), 1, 1 );
			add_action( 'loop_end', array( $this, 'loop_end' ), 999, 1 );
			$this->maybe_create_dummy_post_for_archive( );
        }
	}

	function loop_start( $query ) {
		if ( $query->is_main_query() && ! is_feed() ) {

			ob_start();
			$this->loop_found = true;

		}
	}

	function loop_end( $query ) {
		if (
			$this->loop_found
			&& $query->is_main_query()
		) {

			ob_end_clean();
			if ( $this->loop_has_no_posts ) {
				// Reset everything if the loop has no posts.
				// Then the View will render with no posts.
				wp_reset_query();
				$this->loop_has_no_posts = false;
			}
			$this->loop_found = false;

			//display layout inside the content
			$layout_as_archive_loop = apply_filters('get_the_ddlayout', '', array('initialize_loop' => false) );

			echo $layout_as_archive_loop;

		}

	}

    public function return_false(){
        return false;
    }

    public static function getInstance(  )
    {
        if (!self::$instance)
        {
            self::$instance = new WPDD_Layouts_RenderManager(  );
        }

        return self::$instance;
    }

    public static function tearDown(){
        self::$instance = null;
    }
}
