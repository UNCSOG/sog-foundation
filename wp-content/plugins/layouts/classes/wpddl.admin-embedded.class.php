<?php
class WPDDL_Admin_Pages_Embedded extends WPDDL_Admin
{
    protected static $instance = null;
    protected $promo = null;

    function __construct()
    {
        global $pagenow;

        parent::getInstance();

        if( is_admin() ){
            $this->page = toolset_getget( 'page' );
            $this->admin_init();

            add_action('ddl_create_layout_button', array(&$this, 'create_layout_button'));
            add_action('ddl_create_layout_for_this_page', array(&$this, 'create_layout_for_this_page'));
            add_action('ddl_create_layout_for_this_cpt', array(&$this, 'create_layout_for_this_cpt'));


            $page = $this->page;
            $action = isset($_GET['action']) ? $_GET['action'] : '';

            if (
                ( $pagenow == 'admin.php' && ( $page == WPDDL_LAYOUTS_POST_TYPE || $page == 'dd_layouts_edit' ) ) ||
                ( $pagenow == 'post.php' && $action === 'edit')
            ) {
                add_action('admin_enqueue_scripts', array(&$this, 'load_embedded_scripts'));
            }
        }
    }

    public function create_layout_for_this_page()
    {
        global $post;
        ?>
        <button disabled class="add-new-disabled"><?php printf(__('Create a new layout for this %s', 'ddl-layouts'), rtrim($post->post_type, 's') );?></button><br>
        <a href="#" class="ddl-open-promotional-message js-open-promotional-message padding-left-5">Enable creating layouts</a>
        <?php
    }

    public function create_layout_for_this_cpt()
    {
        global $post;
        ?>
        <button disabled class="add-new-disabled"><?php printf(__('Create a new layout for this %s', 'ddl-layouts'), rtrim($post->post_type, 's') );?></button><br>
        <a href="#" class="ddl-open-promotional-message js-open-promotional-message padding-left-5">Enable creating layouts</a>
        <?php
    }

    public function load_embedded_scripts(){
        global $wpddlayout;

        $wpddlayout->enqueue_scripts( array('ddl-embedded-mode') );
        $wpddlayout->localize_script('ddl-embedded-mode', 'DDLayout_settings_editor', array(
            'version' => WPDDL_VERSION,
            'is_embedded' => $wpddlayout->is_embedded(),
            'user_can_create' => user_can_create_layouts(),
            'strings' => array(
                'associate_layout_to_page' => __('To create an association between this Layout and a single page open....', 'ddl-layouts')
            )
        ));
    }



    protected function toolset_marketing_class_loader(){
        $this->promo = new Toolset_Promotion();
        add_filter( 'toolset_promotion_screen_ids',array(&$this, 'add_toolset_promotion_screen_id'), 10, 1 );
    }

    public function add_toolset_promotion_screen_id($ids){
        $types = get_post_types( array(
            'exclude_from_search' => false,
            'public' => true
        ) );

        foreach( $types as $type ){
            $ids[] = $type;
        }

        $ids[] = "toolset_page_dd_layouts";
        $ids[] = 'toolset_page_dd_layouts_edit';

        return $ids;
    }

    public function create_layout_button()
    {
        ?>
        <button disabled class="add-new-disabled"><?php _e('Add New', 'ddl-layouts');?></button>
        <a href="#" class="ddl-open-promotional-message js-open-promotional-message">Enable creating layouts</a>
        <?php
    }


    protected function add_tutorial_video()
    {
        return array('dd_tutorial_videos' => array(
            'title' => __('Help', 'ddl-layouts'),
            'function' => array($this, 'dd_layouts_help'),
            'subpages' => array(
                'dd_layouts_debug' => array(
                    'title' => __('Debug information', 'ddl-layouts'),
                    'function' => array(__CLASS__, 'dd_layouts_debug')
                ),
            ),
        ),);
    }

    function admin_init()
    {
        if (isset($_GET['page']) and $_GET['page'] == 'dd_layouts_edit') {
            if (isset($_GET['layout_id']) and $_GET['layout_id'] > 0) {
                $this->layouts_editor_page = true;
            }
        }
        $this->toolset_marketing_class_loader();
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Admin_Pages_Embedded();
        }

        return self::$instance;
    }

    protected function add_troubleshoot_menu()
    {
        if( isset( $_GET['page'] ) && 'dd_layouts_troubleshoot' == $_GET['page'] ){
            return array('dd_layouts_troubleshoot' => array(
                'title' => __('Troubleshoot', 'ddl-layouts'),
                'function' => array(__CLASS__, 'dd_layouts_troubleshoot'),
            ));
        }
        return array();
    }

    /**
     * troubleshoot page render hook
     */
    public static function dd_layouts_troubleshoot()
    {
        include WPDDL_GUI_ABSPATH . 'templates/layout_troubleshoot.tpl.php';
    }

    function dd_layouts_help(){
        include WPDDL_GUI_ABSPATH . 'templates/layout_help.tpl.php';
        include WPDDL_GUI_ABSPATH . 'dialogs/dialog_video_player.tpl.php';
    }
    /**
     * debug page render hook.
     */
    public static function dd_layouts_debug()
    {
        include_once WPDDL_TOOLSET_COMMON_ABSPATH . DIRECTORY_SEPARATOR.'debug/debug-information.php';
    }



}