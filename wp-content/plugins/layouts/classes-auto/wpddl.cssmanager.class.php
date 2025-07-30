<?php
class WPDD_Layouts_CSSManager{

	private static $instance;
	public $options_manager;
	private $css_global_option_manager;
	const API_QUERY_STRING = 'ddl_layouts_css_api';
	const INITIAL_CSS = '/*Layouts css goes here*/';
	const CSS_TEMP_DIR = '/ddl-layouts-tmp';
	const GLOBAL_YES = 'yes';

	//Since we have single css file for all layouts our class is singleton, the instance is called statically with: WPDD_Layouts_CSSManager::getInstance()
	private function __construct( WPDDL_Options_Manager $css_global_option_manager = null, WPDDL_Options_Manager $options_manager = null )
	{
		$this->css_global_option_manager = $this->set_global_options_manager( $css_global_option_manager );
		$this->options_manager   = $this->set_options_manager( $options_manager );
		$this->add_hooks();
	}

	private function set_options_manager( WPDDL_Options_Manager $options_manager = null ){
		if( ! $options_manager ){
			$this->options_manager   = new WPDDL_Options_Manager( WPDDL_CSS_OPTIONS );
		} else{
			$this->options_manager = $options_manager;
		}

		return $this->options_manager;
	}

	public function add_hooks(){
		//add the rewrite rule to load css fallback
		add_action( 'permalink_structure_changed', array(&$this, 'ddl_layouts_css_init_internal'), 2 );

		if( is_admin () )
		{
			add_action('wp_ajax_check_system_credentials', array(&$this, 'check_credentials'), 10 );

		}
		else
		{
			if( $this->get_css_global() === self::GLOBAL_YES ){
				add_action('wp_enqueue_scripts', array($this,'handle_layout_css_fe'), 999);
			} else {
				add_filter('get_layout_id_for_render', array($this,'wpddl_frontend_header_init'), 999, 2);
				// let's properly use 'wp_enqueue_scripts' instead of 'get_header' (which runs before) with a very high priority to make sure custom CSS is enqueued after theme, third party plugins and Bootstrap CSS
				add_action('wp_enqueue_scripts', array($this,'wpddl_frontend_header_init_for_content_layouts'), 999);
			}

			add_action('template_redirect', array($this, 'layout_style_router'));
			add_action( 'ddl-loaded-css-file-content', array( $this, 'clean_up_old_css_files' ), 10, 1 );
		}
	}

	private function set_global_options_manager( WPDDL_Options_Manager $options_manager = null ){
		if( ! $options_manager ){
			$this->css_global_option_manager   = new WPDDL_Options_Manager( WPDDL_Options::CSS_GLOBAL );
		} else{
			$this->css_global_option_manager = $options_manager;
		}

		return $this->css_global_option_manager;
	}

	public function get_css_global(){
		$option = $this->get_css_global_option_manager();
		return $option->get_options( WPDDL_Options::CSS_GLOBAL );
	}

	public function get_css_global_option_manager( ){
		return $this->css_global_option_manager;
	}

    public function wpddl_frontend_header_init($id, $layout)
    {
        if( $id !== 0 ) $this->handle_layout_css_fe();
        return $id;
    }

    public function wpddl_frontend_header_init_for_content_layouts(){
	    global $post;
	    if( $post && WPDD_Utils::is_private( $post->ID ) ){
		    $this->handle_layout_css_fe();
	    }
    }

	function handle_layout_css_save( $css )
	{

		$css = $this->remove_scripts_from_css( $css );

		$options = $this->options_manager->get_options();

		if( isset( $options['mode']['db_ok'] ) && $options['mode']['db_ok'] === true )
		{
			return $this->save_layout_css_to_db( $css, $options );
		}
		return false;
	}

	function remove_scripts_from_css( $css ){
		$css = preg_replace('/script.*?\/script/ius', ' ', $css )
			? preg_replace('/\<script.*?\/script\>/ius', ' ', $css )
			: $css;
		return $css;
	}

	function is_css_dir()
	{
		if( !is_writable( $this->uploads_obj()->basedir ) ){
			return false;
		}

		return @wp_mkdir_p( $this->css_dir() );
	}

	function css_dir()
	{
		return  $this->uploads_obj()->basedir . self::CSS_TEMP_DIR;
	}

    function css_url()
    {
        $protocol = is_ssl() ? 'https' : 'http';
        return set_url_scheme( $this->uploads_obj()->baseurl . self::CSS_TEMP_DIR, $protocol );
    }

	function is_css_possible()
	{
		return $this->is_css_dir() || $this->is_using_permalinks();
	}

	function uploads_obj()
	{
		$upload = wp_upload_dir();
		return (object) $upload;
	}

	function handle_layout_css_fe()
	{

	    if( defined('DOING_AJAX') && DOING_AJAX ) return;

		$options = $this->options_manager->get_options();

		if( isset( $options['mode']['db_ok'] ) )
		{
			// Create a file in the uploads directory.
			$file_ok = false;

			if ( $this->is_css_dir() ) {
				$css = stripslashes( $this->options_manager->get_options(WPDDL_LAYOUTS_CSS) );
				$md5 = md5($css);
				$file_name = $this->css_dir() .'/'. $md5 . '.css';

				if (!is_file($file_name)) {
					// create the file.
					$file_ok = file_put_contents($file_name, $css);

				} else if( is_file($file_name) ) {
					$file_ok = true;
				}

				$is_file_empty = $this->is_css_file_empty_or_default($file_name);

				if ($file_ok && $is_file_empty === false ) {
					wp_enqueue_style('wp_ddl_layout_fe_css', $this->css_url() . '/' . $md5 . '.css', array(), WPDDL_VERSION, 'screen' );
				}

				do_action( 'ddl-loaded-css-file-content', $file_name );
 			}

			if ( !$file_ok && $this->is_using_permalinks() ) {

				// we couldn't create a file in the uploads directory.
				// Use the method that uses the template_redirect hook.
				wp_enqueue_style('wp_ddl_layout_fe_css', site_url() . '/ddl-layouts-load-styles.css?c=1', array(), WPDDL_VERSION, 'screen' );
			}
		}

	}

	function clean_up_old_css_files( $exclude_file ){

		$dir_str = $this->css_dir();
		$dir     = opendir( $dir_str );

		while ( ( $file_name = readdir( $dir ) ) !== false ) {

			$currentFile = $dir_str . DIRECTORY_SEPARATOR . $file_name;

			if (
				is_file( $currentFile )
				&& wp_normalize_path( $exclude_file ) !== wp_normalize_path( $currentFile )
			) {

				$info = pathinfo( $currentFile );
				/**
				 * http://php.net/manual/en/function.pathinfo.php#refsect1-function.pathinfo-returnvalues
				 * It will only return 'extension' if the file has an extension
				 */
				if ( isset( $info['extension'] ) ) {

					/**
					 * This file has extension, validate
					 * Only allows CSS files.
					 */

					$the_extension= $info['extension'];
					if( 'css' === $the_extension  ){
						unlink( $currentFile );
					}
				}
			}
		}
		closedir( $dir );

	}

	/**
	 * Check does file contains only default content
	 * @param $file_name
	 *
	 * @return bool
	 */
	public function is_css_file_empty_or_default($file_name){
    	if(is_file($file_name)){
    		$file_content = file_get_contents($file_name);
    		return ( trim($file_content) === '/*Layouts css goes here*/' || trim($file_content) === ''  ) ? true : false;
	    }

	    return true;
	}

	public function save_layout_css_to_db( $css, $options, $force = false ){

		if( $this->options_manager->update_options( $options['mode']['css_option_record'], $css, $force ) )
		{
			return array(
				'db_ok' => true,
				'css_option_record' => $options['mode']['css_option_record'],
				'db_success' => sprintf( __( 'CSS was successfully saved in %s option in database.', 'ddl-layouts' ), $options['mode']['css_option_record'] )
			);
		}

		return null;
	}

	function save_css_settings() {

		if( isset( $_POST['action'] ) &&  $_POST['action'] == 'ddl_layout_save_css_settings' )
		{
            if( user_can_edit_layouts() === false ){
                die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
            }
			if ( !wp_verify_nonce($_POST['ddl_layout_css_settings_nonce'], 'ddl_layout_css_settings_nonce') ){
                die( WPDD_Utils::ajax_nonce_fail( __METHOD__ ) );
            }

			$mode = $_POST['layout_css_option'];

			$save_in = $this->css_settings_handle_mode( $mode );

			if( isset( $save_in['db_ok'] ) && $save_in['db_ok'] === false )
			{
				die( wp_json_encode( array( "error" =>  __("There are problems saving this option in the database.", 'ddl-layouts') ) ) );
			}
			else if( isset( $save_in['db_ok'] ) && $save_in['db_ok'] )
			{
				$message = array( "message" =>  __("CSS option saved.", 'ddl-layouts') );
			}

			$copy_css = $save_in != $this->options_manager->get_options('mode');

			if ($copy_css) {
				// we need to copy the css.
				$css = $this->get_layouts_css();
			}

			$this->options_manager->update_options( 'mode', $save_in, true );

			if ($copy_css) {
				$this->handle_layout_css_save($css);
			}

			die(  wp_json_encode( $message )  );
		}

		die( wp_json_encode( array( "error" => __("Something went wrong communicating with the server", 'ddl-layouts' ) ) )  );
	}

	function css_settings_handle_mode( $mode )
	{
		switch( $mode )
		{
			case 'db':
				return $this->css_db_handle();
			default:
				return $this->css_db_handle();
		}

		return false;
	}

	function css_db_handle()
	{
		if( $this->is_using_permalinks() ){
			$this->ddl_layouts_css_init_internal();
		}
		$this->options_manager->update_options( WPDDL_LAYOUTS_CSS, self::INITIAL_CSS );
		return array( 'db_ok' => true, 'css_option_record' => WPDDL_LAYOUTS_CSS );
	}

	function ddl_layouts_css_init_internal()
	{
		global $wp_rewrite;

		if( $wp_rewrite->using_permalinks() ){
			add_rewrite_rule( 'ddl-layouts-load-styles.css$', 'index.php?' .self::API_QUERY_STRING. '=1', 'top' );
			$wp_rewrite->flush_rules( true );
		}
		return $wp_rewrite->rules;
	}

	private function is_using_permalinks()
	{
		global $wp_rewrite;

		return $wp_rewrite->using_permalinks();
	}

	public static function getInstance( WPDDL_Options_Manager $css_global_option_manager = null, WPDDL_Options_Manager $options_manager = null )
	{
		if (!self::$instance)
		{
			self::$instance = new WPDD_Layouts_CSSManager( $css_global_option_manager, $options_manager );
		}

		return self::$instance;
	}

	/**
	 * For unit testing, forces the object to be contructed again
	 */
	public static function tearDown(){
		self::$instance = null;
	}

	function layout_style_router() {
		$bits =explode("/", esc_attr($_SERVER['REQUEST_URI']) );
		for ($i = 0; $i < sizeof($bits); $i++) {

			if (strpos($bits[$i], 'ddl-layouts-load-styles.css') === 0) {
				$css = stripslashes( $this->options_manager->get_options(WPDDL_LAYOUTS_CSS) );
				include_once WPDDL_RES_ABSPATH . '/load-styles.php';
				exit();
			}
		}
	}

	public function get_layouts_css()
	{
        $ret = self::INITIAL_CSS;
		$options = $this->options_manager->get_options();

		if( !isset( $options['mode'] ) )
		{
			$this->css_settings_init();
            $ret = $this->get_layouts_css();
		}
		elseif( isset( $options['mode'] ) )
		{
			$option = $options['mode'];

			if( isset($option['db_ok']) && $option['db_ok'] === true )
			{
                $ret = $this->options_manager->get_options( $option['css_option_record'] );
			}
		}

        return $ret;
	}
	public function css_settings_init()
	{
		$options = $this->options_manager->get_options();
		$css_opt = isset( $options['mode'] ) ? $options['mode'] : false;

		if( $css_opt === false )
		{
			$ret = $this->where_is_css_saved();
			$this->options_manager->update_options( 'mode', $ret );
			return $ret;
		}

		return null;
	}

	private function where_is_css_saved()
	{
		return $this->css_db_handle();
	}

	public function import_css_from_theme( $source_dir )
	{

		$file = $source_dir. '/layouts.css';

		if( !file_exists( $file  ) ) return;

		$import_css = @file_get_contents($file);

        return $this->import_css( $import_css );
	}

    public function import_css( $import_css, $overwrite = false )
    {
        if( !$import_css ) return false;

        $css = $this->get_layouts_css();

        if( $css == $import_css ) return false;

        if( $overwrite === false )
        {
            if ($css == '' || $css == self::INITIAL_CSS) {

                $options = $this->options_manager->get_options();

                return $this->save_layout_css_to_db( $import_css, $options, true ) === null ? false : true;
            }
        }
        else
        {
            $options = $this->options_manager->get_options();

            return $this->save_layout_css_to_db( $import_css, $options, true ) === null ? false : true;
        }

        return false;
    }
}
