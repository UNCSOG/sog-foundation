<?php
class WPDDL_Templates_Settings{
    private static $instance;
    private $options;
	private $default_template;
	private $views_flavour_installed;

    const TEMPLATE_OPTION = 'ddl-template-option';
    const DEFAULT_OPTION = 'ddl-template-default';
    const DEFAULT_VALUE = 'ddl-template-default-value';
    const DEFAULT_MESSAGE = 'ddl-template-default-message';
    const DEFAULT_LAYOUT = 'ddl-template-default-layout';
    const TEMPLATE_OPTION_USER = 'ddl-template-default-user';
    static $DEFAULT_MESSAGE_TEXT = '';
    static $TEMPLATE_MESSAGE_PATH = '';

    private function __construct(){
        self::$TEMPLATE_MESSAGE_PATH = WPDDL_GUI_ABSPATH . 'templates/layout-not-assigned.tpl.php';
        self::$DEFAULT_MESSAGE_TEXT = __("The content of this page is not showing yet because no template layout is assigned to it. A template layout controls what appears for pages. It's like the PHP template for other themes", 'ddl-layouts');
        $this->init();
    }

    private function init(){
		$this->options = new WPDDL_Options_Manager(self::TEMPLATE_OPTION);
		$this->views_flavour_installed = apply_filters( 'toolset_views_flavour_installed', 'classic' );
        $this->set_default_message_default();
        $this->set_default_value_default();
        $this->set_default_user_default();
        $this->init_hooks();
    }

    private function init_hooks(){
        add_filter('toolset_add_registered_script', array(&$this, 'register_scripts'), 99, 1 );
        add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'), 99 );
        add_filter('ddl-get_template_default_message', array(&$this, 'get_default_message') );
        add_filter( 'ddl-default-template-option-checked', array(&$this, 'is_checked'), 10, 2 );
        add_action('wp_ajax_'.self::DEFAULT_OPTION, array(&$this, 'ajax_callback'));
        add_filter( 'template_include', array( $this, 'get_default_template' ), 102, 1 );
        add_filter( 'template_include', array( $this, 'template_include' ), 108, 1 );
    }


    public function get_default_template( $template ){
            $this->default_template = $template;
            return $template;
    }

    public function template_include( $template ){

		add_filter( 'ddl-template_include_force_render', array(&$this, '_true'), 10, 1 );

		if ( 'blocks' === $this->views_flavour_installed ) {
			return $template;
		}

        if( is_ddlayout_assigned() ) return $template;

        if( (int) $this->get_default_user() === 1 && user_can_assign_layouts() === false ){
            return $template;
        }

        $option = $this->get_default_value();

        if( $option === 1 ){
            add_filter( 'ddl_generate_assignment_button', array( &$this, 'generate_assignment_button' ) );
            return self::$TEMPLATE_MESSAGE_PATH;
        } elseif( $option === 2 ){
            return $this->default_template;
        } else if( $option === 3 ){
            return $this->handle_layout_case( $template );
        }

        return $template;
    }

    public function generate_assignment_button(){

        if( !class_exists( 'Toolset_Admin_Bar_Menu' ) ){
            return null;
        }

        $toolsetAdminBar = Toolset_Admin_Bar_Menu::get_instance();
        $context = $toolsetAdminBar->get_context();

		if ( ! $context ) {
            return null;
        }

        // Get type {post types, taxonomies, wordpress archives slugs, 404} and class {page, archive}
        list( $type, $my_class ) = explode( '|', $context );

        $menu_title = $toolsetAdminBar->get_title( 'layouts', true, $type, $my_class, null );
        $post_id = null;

        $assign_layout_link = wp_nonce_url( admin_url( sprintf( 'admin.php?page=dd_layouts_create_auto&type=%s&class=%s&post=%s', $type, $my_class, $post_id ) ), 'create_auto' );
        return array( "menu_link" => $assign_layout_link, "menu_title" => $menu_title );

    }

    public function _true($bool){
        return true;
    }

    private function handle_layout_case( $template ){
        $file = basename( $template );
        $path = dirname( $template );
        $has_layout = WPDD_Utils::template_have_layout( $file, $path );

        if( $has_layout ){
            $tpl_path = $template;
        } else {
            $tpl = apply_filters('ddl-determine_main_template', 'page.php', 'page.php', 'page');
            $tpl_path = apply_filters('ddl-get_current_integration_template_path', $tpl);
        }


        if( $tpl_path ){
            add_filter('ddl-is_ddlayout_assigned', array(&$this, 'return_true'), 10, 1 );
            add_filter('get_layout_id_for_render', array(&$this, 'get_default_layout'), 999 );
            return $tpl_path;
        }

        return $template;
    }

    public function return_true( $bool ){
        return true;
    }

    public static function getInstance( )
    {
        if (!self::$instance)
        {
            self::$instance = new WPDDL_Templates_Settings( );
        }

        return self::$instance;
    }

    public function gui(){
        require_once WPDDL_GUI_ABSPATH . 'templates/layouts-template-settings-gui.tpl.php';
    }

    public function register_scripts( $scripts ){
            $scripts['ddl-templates-settings'] = new WPDDL_script('ddl-templates-settings', WPDDL_RES_RELPATH . "/js/ddl-templates-settings.js", array('jquery', 'underscore', 'toolset_select2', 'toolset-utils'), WPDDL_VERSION, true);
            return $scripts;
    }

    public function enqueue_scripts(){
        if( isset($_GET['page']) && $_GET['page'] === 'toolset-settings'){
            do_action( 'ddl-enqueue_styles', array( 'toolset-select2-css', 'layouts-select2-overrides-css', 'toolset-notifications-css', 'font-awesome') );
            do_action( 'ddl-enqueue_scripts', array('ddl-templates-settings') );
            do_action( 'ddl-localize_script', 'ddl-templates-settings', 'TemplatesSettingsData', array(
                'Data' => array(
                    'ddl_templates_settings_nonce' => wp_create_nonce('ddl_templates_settings_nonce'),
                    'default_option_name' => self::DEFAULT_OPTION,
                    'default_value_name' => self::DEFAULT_VALUE,
                    'default_message_name' => self::DEFAULT_MESSAGE,
                    'default_value' => $this->get_default_value(),
                    'default_layout_name' => self::DEFAULT_LAYOUT,
                    'default_option_user_name' => self::TEMPLATE_OPTION_USER,
                    'default_user_value' => $this->get_default_user()
                ),
                'strings' => array(

                )
            ));
        }
    }

    public function is_checked( $val, $field ){
        $value = (int) $field;
        return $value === $val ? 'checked' : '';
    }

    public function get_default_message(){
            return stripslashes( $this->get_option_value( self::DEFAULT_MESSAGE ) );
    }

    private function get_option_value($key){
        return $this->options->get_options($key);
    }

    private function set_default_message_default(){
        $default = $this->get_default_message();

        if( !$default ){
            $this->set_option_value( self::DEFAULT_MESSAGE, self::$DEFAULT_MESSAGE_TEXT );
        }
    }

    private function set_default_value_default(){
        $value = $this->get_default_value();
        if( !$value  ){
            $this->set_option_value( self::DEFAULT_VALUE, 2 );
        }
    }

    private function set_default_user_default(){
        $value = $this->get_default_user();
        if( !$value  ){
            $this->set_option_value( self::TEMPLATE_OPTION_USER, 2 );
        }
    }

    private function set_option_value( $option, $value ){
        return $this->options->update_options( $option, $value, true );
    }

    private function set_default_message( $message ){
        return $this->set_option_value( self::DEFAULT_MESSAGE, $message);
    }

    private function set_default_value( $value ){
        return $this->set_option_value( self::DEFAULT_VALUE, $value);
    }

    private function set_default_layout( $id ){
        return $this->set_option_value( self::DEFAULT_LAYOUT, $id);
    }

    public function get_default_layout(){
        return $this->get_option_value( self::DEFAULT_LAYOUT );
    }

    private function set_default_user( $value ){
        return $this->set_option_value( self::TEMPLATE_OPTION_USER, $value);
    }

    public function get_default_user(){
        return apply_filters( 'ddl-template_include_force_user_option', $this->get_option_value( self::TEMPLATE_OPTION_USER ) );
    }

    public function get_default_value(){
            return apply_filters( 'ddl-template_include_force_option', (int) $this->get_option_value( self::DEFAULT_VALUE ) );
    }

    private function layouts_options(){
        $default = $this->get_default_layout();
        $default = $default ? $default : '';
        $layouts = array();
        $layouts = WPDD_Layouts_Cache_Singleton::get_published_layouts();
        $dummy = new stdClass();
        $dummy->ID = '';
        $dummy->post_title = __('--- Select a default layout ---', 'ddl-layouts');
        array_unshift( $layouts, $dummy );
        ob_start();
        foreach( $layouts as $layout ):
            if( apply_filters('ddl-layout_is_parent', $layout->ID) === false ):
            $selected = $layout->ID === $default ? 'selected="selected"' : '';
            ?>
                <option <?php echo $selected;?> value="<?php echo $layout->ID;?>"><?php echo $layout->post_title;?></option>
            <?php
                endif;
            endforeach;
        return ob_get_clean();
    }

    public function ajax_callback(){
        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

        if( $_POST && wp_verify_nonce( $_POST['ddl_templates_settings_nonce'], 'ddl_templates_settings_nonce' ) )
        {
            if( isset( $_POST[self::DEFAULT_OPTION] ) ){
                $update = $this->set_default_value( $_POST[self::DEFAULT_OPTION] );
            }

            if( isset( $_POST[self::DEFAULT_MESSAGE] ) ){
                $this->set_default_message( $_POST[self::DEFAULT_MESSAGE] );
            } elseif( isset( $_POST[self::DEFAULT_LAYOUT] ) ){
                $this->set_default_layout( $_POST[self::DEFAULT_LAYOUT] );
            } else {
                $this->set_default_message( '' );
                $this->set_default_layout( '' );
            }

            if( isset( $_POST[self::TEMPLATE_OPTION_USER] ) ){
                $this->set_default_user( $_POST[self::TEMPLATE_OPTION_USER] );
            }

            if( $update )
            {
                $send =  array( 'Data'=> array( 'message' => __('Updated option', 'ddl-layouts')  ) );

            } else {
                $send =  array( 'Data'=> array( 'error' => __('Option not updated', 'ddl-layouts') ) );

            }
        }
        else
        {
            $send = array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') );
        }

        wp_send_json($send);
    }
}
