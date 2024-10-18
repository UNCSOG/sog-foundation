<?php

use OTGS\Toolset\Layouts\Util\BootstrapColumnSizes;

class WPDDL_Settings {

	private static $instance;
	const MAX_POSTS_OPTION_NAME = WPDDL_MAX_POSTS_OPTION_NAME;
	const MAX_POSTS_OPTION_DEFAULT = WPDDL_MAX_POSTS_OPTION_DEFAULT;
	private $twig = null;
	private $parent_default = 0;
	private $column_prefix;
	private $js_global = 'no';
	private $css_global = 'no';
	private $scripts_global_options_values = array( 'no' => 'no', 'yes' => 'yes' );
	public static $max_posts_num_option = self::MAX_POSTS_OPTION_DEFAULT;
	public static $show_cell_details_on_insert = 'yes';


	public function __construct() {
		$this->twig = $this->get_twig_helper( true );
		add_action( 'init', array( $this, 'add_hooks' ), 10 );
		add_action( 'init', array( $this, 'reset_framework_values_to_default' ), 21 );
		add_filter( 'ddl-get_' . WPDDL_Options::COLUMN_PREFIX . '_default_value', array( $this, 'get_column_prefix_default_option' ) );
		add_filter( 'ddl-get_' . WPDDL_Options::JS_GLOBAL . '_default_value', array( $this, 'get_js_global_default_option' ) );
		add_filter( 'ddl-get_' . WPDDL_Options::CSS_GLOBAL . '_default_value', array( $this, 'get_css_global_default_option' ) );
	}

    public function add_hooks(){
        $this->parent_default = apply_filters('ddl-get-default-'.WPDDL_Options::PARENTS_OPTIONS, $this->parent_default, WPDDL_Options::PARENTS_OPTIONS );
	    $this->column_prefix  = apply_filters('ddl-get-default-'.WPDDL_Options::COLUMN_PREFIX, $this->get_column_prefix(), WPDDL_Options::COLUMN_PREFIX );
	    $this->js_global = apply_filters( 'ddl-get-default-' . WPDDL_Options::JS_GLOBAL, $this->js_global, WPDDL_Options::JS_GLOBAL );
	    $this->css_global = apply_filters( 'ddl-get-default-' . WPDDL_Options::CSS_GLOBAL, $this->css_global, WPDDL_Options::CSS_GLOBAL );
        self::set_max_num_posts( self::get_option_max_num_posts() );
        self::set_cell_details_settings( self::get_option_cell_details_settings() );
        add_action( 'wp_ajax_ddl_update_toolset_admin_bar_menu_status', array( $this, 'ddl_update_toolset_admin_bar_menu_status' ) );
        add_action( 'wp_ajax_ddl_set_max_posts_amount', array( __CLASS__, 'ddl_set_max_posts_amount' ) );
        add_action( 'wp_ajax_ddl_set_cell_details_settings', array( $this, 'ddl_set_cell_details_settings' ) );
        add_action('wp_ajax_'.WPDDL_Options::PARENTS_OPTIONS, array(&$this, 'parent_default_ajax_callback'));
	    add_action('wp_ajax_'.WPDDL_Options::COLUMN_PREFIX, array(&$this, 'column_prefix_ajax_callback'));
	    add_action('wp_ajax_'.WPDDL_Options::JS_GLOBAL, array(&$this, WPDDL_Options::JS_GLOBAL . '_ajax_callback'));
	    add_action('wp_ajax_'.WPDDL_Options::CSS_GLOBAL, array(&$this, WPDDL_Options::CSS_GLOBAL . '_ajax_callback'));
        add_filter( 'toolset_filter_toolset_admin_bar_menu_insert', array( $this, 'extend_toolset_admin_bar_menu' ), 11, 3 );
    }

    public function js_global_ajax_callback(){
	    if( user_can_assign_layouts() === false ){
		    die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
	    }

	    if( $_POST && wp_verify_nonce( $_POST[ WPDDL_Options::JS_GLOBAL . '_nonce'], WPDDL_Options::JS_GLOBAL . '_nonce' ) && isset( $_POST['action'] ) && $_POST['action'] === WPDDL_Options::JS_GLOBAL )
	    {

		    if( isset( $_POST[WPDDL_Options::JS_GLOBAL] ) ){

			    $update = apply_filters( 'ddl-set-default-'.WPDDL_Options::JS_GLOBAL, WPDDL_Options::JS_GLOBAL, $_POST[WPDDL_Options::JS_GLOBAL] );
		    }

		    if( $update )
		    {
			    $send =  array( 'Data'=> array( 'message' => __('Updated option', 'ddl-layouts'), 'value' => $_POST[WPDDL_Options::JS_GLOBAL]  ) );

		    } else {
			    $send =  array( 'Data'=> array( 'error' => __('Option not updated', 'ddl-layouts') ) );

		    }
	    }
	    else
	    {
		    $send = array( 'error' =>  sprintf( __( 'Nonce problem: apparently we do not know where the request comes from. %s',  'ddl-layouts'), __METHOD__  ) );
	    }

	    wp_send_json($send);
    }

	public function css_global_ajax_callback(){
		if( user_can_assign_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}

		if( $_POST && wp_verify_nonce( $_POST[ WPDDL_Options::CSS_GLOBAL . '_nonce'], WPDDL_Options::CSS_GLOBAL . '_nonce' ) && isset( $_POST['action'] ) && $_POST['action'] === WPDDL_Options::CSS_GLOBAL )
		{

			if( isset( $_POST[WPDDL_Options::CSS_GLOBAL] ) ){

				$update = apply_filters( 'ddl-set-default-'.WPDDL_Options::CSS_GLOBAL, WPDDL_Options::CSS_GLOBAL, $_POST[WPDDL_Options::CSS_GLOBAL] );
			}

			if( $update )
			{
				$send =  array( 'Data'=> array( 'message' => __('Updated option', 'ddl-layouts'), 'value' => $_POST[WPDDL_Options::CSS_GLOBAL]  ) );

			} else {
				$send =  array( 'Data'=> array( 'error' => __('Option not updated', 'ddl-layouts') ) );

			}
		}
		else
		{
			$send = array( 'error' =>  sprintf( __( 'Nonce problem: apparently we do not know where the request comes from. %s',  'ddl-layouts'), __METHOD__ ) );
		}

		wp_send_json($send);
	}

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Settings();
        }

        return self::$instance;
    }

	public function reset_framework_values_to_default() {

		$api = $this->get_framework_instance();
		$framework = get_option( WPDDL_FRAMEWORK_OPTION_KEY, null );
		$framework_default = get_option( WPDDL_FRAMEWORK_OPTION_DEFAULT_KEY );

		if ( $framework && $framework !== $framework_default ) {
			update_option( WPDDL_FRAMEWORK_OPTION_DEFAULT_KEY, $framework_default );
			$this->column_prefix = apply_filters( 'ddl-set-default-' . WPDDL_Options::COLUMN_PREFIX, WPDDL_Options::COLUMN_PREFIX, $api->get_column_prefix() );
		} else {
			update_option( WPDDL_FRAMEWORK_OPTION_KEY, WPDDL_FRAMEWORK, $framework );
		}
	}

    public function init(){
        $this->init_gui();
    }

    public function get_default_parent(){
        return $this->parent_default;
    }

    public function get_column_prefix(){
        if( null === $this->column_prefix ) {
			$this->column_prefix = apply_filters(
				'ddl-get_default_column_prefix',
				BootstrapColumnSizes::get_instance()->get_column_class_prefix( BootstrapColumnSizes::DEFAULT_VALUE )
			);
		}

        return $this->column_prefix;
    }

	public function get_js_global( ){
		return $this->js_global;
	}

	public function get_css_global( ){
        return $this->css_global;
    }

    public function get_column_prefix_default_option( ){
        return array( WPDDL_Options::COLUMN_PREFIX => $this->get_column_prefix() );
    }

	public function get_js_global_default_option( ){
		return array( WPDDL_Options::JS_GLOBAL => $this->get_js_global() );
	}

	public function get_css_global_default_option( ){
		return array( WPDDL_Options::CSS_GLOBAL => $this->get_css_global() );
	}

    /**
     * Layouts Settings page set up
     */
    function init_gui() {

        $settings_script_texts = array(
            'setting_saved' => __( 'Settings saved', 'ddl-layouts' ),
            'parent_default' => $this->parent_default,
            'parent_option_name' => WPDDL_Options::PARENTS_OPTIONS,
            'parent_settings_nonce' => wp_create_nonce( WPDDL_Options::PARENTS_OPTIONS.'_nonce', WPDDL_Options::PARENTS_OPTIONS.'nonce' ),
            'column_default' => $this->get_column_prefix(),
            'column_option_name' => WPDDL_Options::COLUMN_PREFIX,
            'column_settings_nonce' => wp_create_nonce( WPDDL_Options::COLUMN_PREFIX.'_nonce', WPDDL_Options::COLUMN_PREFIX.'nonce' ),
            'js_settings_values' => $this->scripts_global_options_values,
            'js_settings_value' => $this->js_global,
            'js_settings_option_name' => WPDDL_Options::JS_GLOBAL,
            'js_settings_nonce' => wp_create_nonce( WPDDL_Options::JS_GLOBAL . '_nonce', WPDDL_Options::JS_GLOBAL . 'nonce' ),
            'css_settings_values' => $this->scripts_global_options_values,
            'css_settings_value' => $this->css_global,
            'css_settings_option_name' => WPDDL_Options::CSS_GLOBAL,
            'css_settings_nonce' => wp_create_nonce( WPDDL_Options::CSS_GLOBAL . '_nonce', WPDDL_Options::CSS_GLOBAL . 'nonce' ),
        );

        if ( is_admin() && isset( $_GET['page'] ) && ($_GET['page'] === 'toolset-settings' || $_GET['page'] === 'dd_layouts_edit') ) {
            do_action( 'ddl-enqueue_styles', 'layouts-settings-admin-css' );
            do_action( 'ddl-enqueue_scripts', 'layouts-settings-admin-js' );
            do_action( 'ddl-localize_script', 'layouts-settings-admin-js', 'DDL_Settings_JS', $settings_script_texts );
        }
        
    }

    function default_parent_gui(){
        require_once WPDDL_GUI_ABSPATH . 'templates/layouts-parent-settings-gui.tpl.php';
    }

	function default_column_gui(){
		$this->load_default_prefix_template( );
	}

	private function load_default_prefix_template( ) {
		$prefix_data = array();
	    $prefix_data['items'] = $this->get_framework_column_prefixes();
	    $prefix_data['prefix_option_name'] = WPDDL_Options::COLUMN_PREFIX;
	    $prefix_data['help_link'] = WPDDL_BOOTSTRAP_GRID_SIZE;
		$prefix_data[WPDDL_Options::COLUMN_PREFIX] = $this->get_column_prefix();
		$context = $this->twig->build_generic_twig_context( $prefix_data, 'prefixes'  );
		echo $this->twig->render( '/layout-bootstrap-prefix-settings.tpl.twig', $context );
	}

    function ddl_update_toolset_admin_bar_menu_status() {
        
        if ( ! current_user_can( 'manage_options' ) ) {
            $data = array(
                'type' => 'capability',
                'message' => __( 'You do not have permissions for that.', 'ddl-layouts' )
            );
            wp_send_json_error( $data );
        }
        if (
                ! isset( $_POST["wpnonce"] ) || ! wp_verify_nonce( $_POST["wpnonce"], 'ddl_toolset_admin_bar_menu_nonce' )
        ) {
            $data = array(
                'type' => 'nonce',
                'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'ddl-layouts' )
            );
            wp_send_json_error( $data );
        }
        
        $status = ( isset( $_POST['status'] ) ) ? sanitize_text_field( $_POST['status'] ) : 'true';
        $toolset_options = get_option( 'toolset_options', array() );
        $toolset_options['show_admin_bar_shortcut'] = ( $status == 'true' ) ? 'on' : 'off';
        update_option( 'toolset_options', $toolset_options );
        wp_send_json_success();
        
    }

    ////////////////////////////////////////////////////////////////////////////
    //
    // Layouts Settings Page - GUI Code
    //
    ////////////////////////////////////////////////////////////////////////////


    public function ddl_show_hidden_toolset_admin_bar_menu(  ) {
        $toolset_options = get_option( 'toolset_options', array() );
        $toolset_admin_bar_menu_show = ( isset( $toolset_options['show_admin_bar_shortcut'] ) && $toolset_options['show_admin_bar_shortcut'] == 'off' ) ? false : true;
        ob_start();
        require_once WPDDL_GUI_ABSPATH . 'templates/layout-settings-admin_bar.tpl.php';
        echo ob_get_clean();
    }
    
    public function extend_toolset_admin_bar_menu( $menu_item_definitions, $context, $post_id ){
        if( !is_array( $menu_item_definitions ) ) {
            $menu_item_definitions = array();
        }

        $menu_item_definitions[] = array(
	    'title' => __( 'Layouts CSS and JS Editor', 'ddl-layouts' ),
	    'menu_id' => 'toolset_layouts_edit_css',
	    'href' => admin_url().'admin.php?page=dd_layout_CSS_JS'
	);

	return $menu_item_definitions;
    }

    function ddl_set_max_query_size(  ){
        self::$max_posts_num_option = self::get_option_max_num_posts();
        ob_start();

        require_once WPDDL_GUI_ABSPATH . 'templates/layout-settings-wp_query.tpl.php';

        echo ob_get_clean();
    }

    public function ddl_show_layout_cell_details( ){
        $option_value = self::get_option_cell_details_settings();
        ob_start();
        require_once WPDDL_GUI_ABSPATH . 'templates/layout-settings-cell-details.tpl.php';
        echo ob_get_clean();
    }

	public function ddl_show_layout_scripts_options_gui( ){
        $js_option_value = $this->js_global;
        $js_option_name = WPDDL_Options::JS_GLOBAL;
		$css_option_value = $this->css_global;
		$css_option_name = WPDDL_Options::CSS_GLOBAL;
		$layouts_scripts_global_options_values = $this->scripts_global_options_values;
		ob_start();
		require_once WPDDL_GUI_ABSPATH . 'templates/layout-settings-css-js.tpl.php';
		echo ob_get_clean();
    }

    public static function get_option_max_num_posts(){
            return get_option( self::MAX_POSTS_OPTION_NAME, self::MAX_POSTS_OPTION_DEFAULT );
    }

    public static function set_option_max_num_posts( $num ){
        return update_option( self::MAX_POSTS_OPTION_NAME, $num );
    }

    public static function get_max_posts_num( ){
        return self::$max_posts_num_option;
    }

    public static function set_max_num_posts( $num ){
        return self::$max_posts_num_option = $num;
    }

	/**
	 * Set uption to show/hide cell description
	 * @param $value string
	 * @return string
	 */
    public static function set_cell_details_settings($value){
        return self::$show_cell_details_on_insert = $value;
    }

	/**
	 * Get show/hide cell description option value from database
	 * @return string
	 */
    public static function get_option_cell_details_settings(){
        return get_option( WPDDL_SHOW_CELL_DETAILS_ON_INSERT, 'yes' );
    }

	/**
	 * Get show/hide cell description option value from object
	 * @return string
	 */
    public static function get_cell_details_settings(){
        return self::$show_cell_details_on_insert;
    }

    public static function ddl_set_max_posts_amount( ){
        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

        if( $_POST && wp_verify_nonce( $_POST['ddl_max-posts-num_nonce'], 'ddl_max-posts-num_nonce' ) )
        {
            $update = false;
            $amount = isset( $_POST['amount_posts'] ) ? $_POST['amount_posts'] : self::$max_posts_num_option;

            if( $amount !==  self::$max_posts_num_option ){
                self::$max_posts_num_option = $amount;
                $update = self::set_option_max_num_posts( $amount );
            }


            if( $update )
            {
                $send = wp_json_encode( array( 'Data'=> array( 'message' => __('Updated option', 'ddl-layouts'), 'amount' => $amount  ) )  );

            } else {
                $send = wp_json_encode( array( 'Data'=> array( 'error' => __('Option not updated', 'ddl-layouts'), 'amount' => $amount  ) ) );

            }
        }
        else
        {
            $send = wp_json_encode( array( 'error' =>  sprintf( __( 'Nonce problem: apparently we do not know where the request comes from. %s', 'ddl-layouts'), __METHOD__ ) ) );
        }

        die($send);
    }

	/**
	 * Update the Views Bootrstap version
	 *
	 * @since 2.0
	 * $_POST:
	 *  wpnonce: ddl_cell-details_nonce
	 *  show_cell_details: yes|no
	 * @return json with update status
	 */
    public function ddl_set_cell_details_settings(){

        if ( user_can_edit_layouts() === false ) {
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

        if ( ! wp_verify_nonce( $_POST['ddl_cell-details_nonce'], 'ddl_cell-details_nonce' ) ) {
            $send = array( 'error' => sprintf( __(  'Nonce problem: apparently we do not know where the request comes from. %s', 'ddl-layouts' ), __METHOD__ ) );
	        wp_send_json_error( $send );
        }

        $show_cell_details = isset( $_POST['show_cell_details'] ) ? $_POST['show_cell_details'] : 'no';
        self::set_cell_details_settings( $show_cell_details );
        $options_updated = update_option( WPDDL_SHOW_CELL_DETAILS_ON_INSERT, $show_cell_details );

        if ( $options_updated ) {
            $send = array(
                'Data' => array(
                    'message' => __( 'Updated option', 'ddl-layouts' ),
                    'show_cell_details'  => $show_cell_details
                )
            );
        } else {
            $send = array(
                'Data' => array(
                    'error' => __( 'Option not updated', 'ddl-layouts' ),
                    'show_cell_details' => $show_cell_details
                )
            );
        }
	    wp_send_json_success( $send );

    }

    /**
     * @deprecated
     */
    private function parents_options(){
        $default_parent = $this->parent_default;
        $parents = WPDD_Layouts::get_available_parents();?>
        <option value=""><?php _e("None", 'ddl-layouts'); ?></option>
        <?php
        for ( $i=0,$total_parents=count($parents); $i<$total_parents; $i++){
            $selected = '';
            if ( $parents[$i]->ID == $default_parent ){
                $selected = ' selected';
            }
            echo '<option value="'.$parents[$i]->ID.'"'.$selected.'>'.$parents[$i]->post_title.'</option>';
        }
    }

    public function parent_default_ajax_callback(){

        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

        if( $_POST && wp_verify_nonce( $_POST['parents_options_nonce'], 'parents_options_nonce' ) && isset( $_POST['action'] ) && $_POST['action'] === 'parents_options' )
        {

            if( isset( $_POST[WPDDL_Options::PARENTS_OPTIONS] ) ){

                $update = apply_filters('ddl-set-default-'.WPDDL_Options::PARENTS_OPTIONS, WPDDL_Options::PARENTS_OPTIONS, $_POST[WPDDL_Options::PARENTS_OPTIONS] );
            }

            if( $update )
            {
                $send =  array( 'Data'=> array( 'message' => __('Updated option', 'ddl-layouts'), 'value' => $_POST[WPDDL_Options::PARENTS_OPTIONS]  ) );

            } else {
                $send =  array( 'Data'=> array( 'error' => __('Option not updated', 'ddl-layouts') ) );

            }
        }
        else
        {
            $send = array( 'error' =>  sprintf( __( 'Nonce problem: apparently we do not know where the request comes from. %s',  'ddl-layouts'), __METHOD__ ) );
        }

        wp_send_json($send);
    }

	public function column_prefix_ajax_callback() {

		if ( user_can_assign_layouts() === false ) {
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}

		if ( $_POST && wp_verify_nonce( $_POST[ WPDDL_Options::COLUMN_PREFIX . '_nonce' ], WPDDL_Options::COLUMN_PREFIX . '_nonce' ) && isset( $_POST['action'] ) && WPDDL_Options::COLUMN_PREFIX === $_POST['action'] ) {

			if ( isset( $_POST[ WPDDL_Options::COLUMN_PREFIX ] ) ) {

				$column_prefix = sanitize_text_field( wp_unslash( $_POST[ WPDDL_Options::COLUMN_PREFIX ] ) );

				$update = apply_filters( 'ddl-set-default-' . WPDDL_Options::COLUMN_PREFIX, WPDDL_Options::COLUMN_PREFIX, $column_prefix );
			}

			if ( $update ) {
				$send = array(
					'Data' => array(
						'message' => __( 'Updated option', 'ddl-layouts' ),
						'value' => $column_prefix,
					),
				);

			} else {
				$send = array( 'Data' => array( 'error' => __( 'Option not updated', 'ddl-layouts' ) ) );

			}
		} else {
			$send = array( 'error' => sprintf( __( 'Nonce problem: apparently we do not know where the request comes from. %s', 'ddl-layouts' ), __METHOD__ ) ); /* Translators: recurring error notification structure. */
		}

		wp_send_json( $send );
	}

	private function get_framework_instance(){
		return WPDDL_Framework::getInstance();
	}

	private function get_twig_helper( $debug = false ){
	    return new WPDD_Helper_Twig( $debug );
    }

    private function get_framework_column_prefixes(){
	    $framework = $this->get_framework_instance();
	    return $framework->get_framework_prefixes_data();
    }

}
