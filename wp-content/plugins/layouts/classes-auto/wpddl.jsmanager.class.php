<?php
class WPDD_Layouts_JsManager{

	private static $instance;
	public $options_manager;
	private $js_global_option_manager;
	const API_QUERY_STRING = 'ddl_layouts_js_api';
	const INITIAL_JS = '/*Layouts js goes here*/';
	const JS_TEMP_DIR = '/ddl-layouts-tmp';
	const GLOBAL_YES = 'yes';

	//Since we have single js file for all layouts our class is singleton, the instance is called statically with: WPDD_Layouts_JsManager::getInstance()
	private function __construct( WPDDL_Options_Manager $js_global_option_manager = null, WPDDL_Options_Manager $options_manager = null )
	{
		$this->js_global_option_manager = $this->set_global_options_manager( $js_global_option_manager );
		$this->options_manager = $this->set_options_manager( $options_manager );
		$this->add_hooks();
	}

	private function set_options_manager( WPDDL_Options_Manager $options_manager = null ){
		if( ! $options_manager ){
			$this->options_manager   = new WPDDL_Options_Manager( WPDDL_JS_OPTIONS );
		} else{
			$this->options_manager = $options_manager;
		}

		return $this->options_manager;
	}

	private function set_global_options_manager( WPDDL_Options_Manager $options_manager = null ){
		if( ! $options_manager ){
			$this->js_global_option_manager   = new WPDDL_Options_Manager( WPDDL_Options::JS_GLOBAL );
		} else{
			$this->js_global_option_manager = $options_manager;
		}

		return $this->js_global_option_manager;
	}

	public function add_hooks(){
		//add the rewrite rule to load css fallback
		add_action( 'permalink_structure_changed', array(&$this, 'ddl_layouts_js_init_internal'), 2 );

		if( is_admin () )
		{
			add_action('wp_ajax_check_system_credentials', array(&$this, 'check_credentials'), 10 );

		}
		else
		{
			if( $this->get_js_global() === self::GLOBAL_YES ){
				add_action('wp_enqueue_scripts', array($this,'handle_layout_js_fe'));
			} else {
				add_filter('get_layout_id_for_render', array($this,'wpddl_frontend_header_init'), 999, 2);
				add_action('wp_enqueue_scripts', array($this,'wpddl_frontend_header_init_for_content_layouts'));
			}

			add_action('template_redirect', array($this, 'layout_script_router'));
			add_action( 'ddl-loaded-js-file-content', array( $this, 'clean_up_old_js_files' ), 10, 1 );
		}
	}

	public function get_js_global(){
		$option = $this->get_js_global_option_manager();
		return $option->get_options( WPDDL_Options::JS_GLOBAL );
	}

	public function get_js_global_option_manager( ){
		return $this->js_global_option_manager;
	}

    public function wpddl_frontend_header_init($id, $layout)
    {
        if( $id !== 0 ) $this->handle_layout_js_fe();
        return $id;
    }

	public function wpddl_frontend_header_init_for_content_layouts(){
		global $post;
		if( $post && WPDD_Utils::is_private($post->ID) ){
			$this->handle_layout_js_fe();
		}
	}

	function handle_layout_js_save( $js )
	{

		$options = $this->options_manager->get_options();

		if( isset( $options['mode']['db_ok'] ) && $options['mode']['db_ok'] === true )
		{
			return $this->save_layout_js_to_db( $js, $options );
		}
		return false;
	}

	function is_js_dir()
	{
		if( !is_writable( $this->uploads_obj()->basedir ) ){
			return false;
		}

		return @wp_mkdir_p( $this->js_dir() );
	}

	function js_dir()
	{
		return $this->uploads_obj()->basedir . self::JS_TEMP_DIR;
	}

	function is_js_possible()
	{
		return $this->is_js_dir() || $this->is_using_permalinks();
	}

	function uploads_obj()
	{
		$upload = wp_upload_dir();
		return (object) $upload;
	}

	function handle_layout_js_fe()
	{

		$options = $this->options_manager->get_options();

		if( isset( $options['mode']['db_ok'] ) )
		{
			// Create a file in the uploads directory.
			$file_ok = false;

			if ( $this->is_js_dir() ) {
				$js = stripslashes( $this->options_manager->get_options(WPDDL_LAYOUTS_JS) );
				$md5 = md5($js);
				$file_name = $this->js_dir() .'/'. $md5 . '.js';
				if (!is_file($file_name)) {
					// create the file.
                    
					$file_ok = file_put_contents($file_name, $js);
				}
				else if( is_file($file_name) )
				{
					$file_ok = true;
				}
				
				if ($file_ok) {
                    wp_register_script( 'wp_ddl_layout_fe_js', $this->js_url() . '/' . $md5 . '.js', array(), WPDDL_VERSION, true );
					wp_enqueue_script('wp_ddl_layout_fe_js' );
				}

				do_action( 'ddl-loaded-js-file-content', $file_name );

 			}

			if ( !$file_ok && $this->is_using_permalinks() ) {

				// we couldn't create a file in the uploads directory.
				// Use the method that uses the template_redirect hook.
				wp_register_script( 'wp_ddl_layout_fe_js', site_url() . '/ddl-layouts-load-js.js?c=1', array(), WPDDL_VERSION, true  );
                wp_enqueue_script('wp_ddl_layout_fe_js' );
			}
		}

	}

	function clean_up_old_js_files( $exclude_file ){

		$dir_str = $this->js_dir();
		$dir     = opendir( $dir_str );

		while ( ( $file_name = readdir( $dir ) ) !== false ) {

			$currentFile = $dir_str . DIRECTORY_SEPARATOR . $file_name;

			if ( is_file( $currentFile ) && $exclude_file !== $currentFile ) {

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
					if( 'js' === $the_extension  ){
						unlink( $currentFile );
					}
				}
			}
		}
		closedir( $dir );

	}

	function js_url()
	{
		$protocol = is_ssl() ? 'https' : 'http';
		return set_url_scheme( $this->uploads_obj()->baseurl . self::JS_TEMP_DIR, $protocol );
	}

	public function save_layout_js_to_db( $js, $options, $force = false ){

		if( $this->options_manager->update_options( $options['mode']['js_option_record'], $js, $force ) )
		{
			return array(
				'db_ok' => true,
				'js_option_record' => $options['mode']['js_option_record'],
				'db_success' => sprintf( __( 'JS was successfully saved in %s option in database.', 'ddl-layouts' ), $options['mode']['js_option_record'] )
			);
		}

		return null;
	}

	function save_js_settings() {

		if( isset( $_POST['action'] ) &&  $_POST['action'] == 'ddl_layout_save_js_settings' )
		{
            if( user_can_edit_layouts() === false ){
                die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
            }
			if ( !wp_verify_nonce($_POST['ddl_layout_js_settings_nonce'], 'ddl_layout_js_settings_nonce') ){
                die( WPDD_Utils::ajax_nonce_fail( __METHOD__ ) );
            }

			$mode = $_POST['layout_js_option'];

			$save_in = $this->js_settings_handle_mode( $mode );

			if( isset( $save_in['db_ok'] ) && $save_in['db_ok'] === false )
			{
				die( wp_json_encode( array( "error" =>  __("There are problems saving this option in the database.", 'ddl-layouts') ) ) );
			}
			else if( isset( $save_in['db_ok'] ) && $save_in['db_ok'] )
			{
				$message = array( "message" =>  __("JS option saved.", 'ddl-layouts') );
			}

			$copy_js = $save_in != $this->options_manager->get_options('mode');

			if ($copy_js) {
				// we need to copy the js.
				$js = $this->get_layouts_js();
			}

			$this->options_manager->update_options( 'mode', $save_in, true );

			if ($copy_js) {
				$this->handle_layout_js_save($js);
			}

			die(  wp_json_encode( $message )  );
		}

		die( wp_json_encode( array( "error" => __("Something went wrong communicating with the server", 'ddl-layouts' ) ) )  );
	}

	function js_settings_handle_mode( $mode )
	{
		switch( $mode )
		{
			case 'db':
				return $this->js_db_handle();
			default:
				return $this->js_db_handle();
		}

		return false;
	}

	function js_db_handle()
	{
		if( $this->is_using_permalinks() ){
			$this->ddl_layouts_js_init_internal();
		}
		$this->options_manager->update_options( WPDDL_LAYOUTS_JS, self::INITIAL_JS );
		return array( 'db_ok' => true, 'js_option_record' => WPDDL_LAYOUTS_JS );
	}

	function ddl_layouts_js_init_internal()
	{
		global $wp_rewrite;

		if( $wp_rewrite->using_permalinks() ){
			add_rewrite_rule( 'ddl-layouts-load-js.js$', 'index.php?' .self::API_QUERY_STRING. '=1', 'top' );
			$wp_rewrite->flush_rules( true );
		}
		return $wp_rewrite->rules;
	}

	private function is_using_permalinks()
	{
		global $wp_rewrite;

		return $wp_rewrite->using_permalinks();
	}

	public static function getInstance( WPDDL_Options_Manager $js_global_option_manager = null, WPDDL_Options_Manager $options_manager = null )
	{
		if (!self::$instance)
		{
			self::$instance = new WPDD_Layouts_JsManager( $js_global_option_manager, $options_manager );
		}

		return self::$instance;
	}

	/**
	 * For unit testing, forces the object to be contructed again
	 */
	public static function tearDown(){
		self::$instance = null;
	}
	
	function layout_script_router() {
		$bits =explode("/", esc_attr($_SERVER['REQUEST_URI']) );
		for ($i = 0; $i < sizeof($bits); $i++) {
			if (strpos($bits[$i], 'ddl-layouts-load-js.js') === 0) {
				$js = stripslashes( $this->options_manager->get_options(WPDDL_LAYOUTS_JS) );
				include_once WPDDL_RES_ABSPATH . '/load-script.php'; // TODO create new loader for js
				exit();
			}
		}
	}
	
	public function get_layouts_js()
	{
        $ret = self::INITIAL_JS;
		$options = $this->options_manager->get_options();

		if( !isset( $options['mode'] ) )
		{
			$this->js_settings_init();
            $ret = $this->get_layouts_js();
		}
		elseif( isset( $options['mode'] ) )
		{
			$option = $options['mode'];

			if( isset($option['db_ok']) && $option['db_ok'] === true )
			{
                $ret = $this->options_manager->get_options( $option['js_option_record'] );
			}
		}

        return $ret;
	}
	public function js_settings_init()
	{
		$options = $this->options_manager->get_options();
		$js_opt = isset( $options['mode'] ) ? $options['mode'] : false;

		if( $js_opt === false )
		{
			$ret = $this->where_is_js_saved();
			$this->options_manager->update_options( 'mode', $ret );
			return $ret;
		}

		return null;
	}

	private function where_is_js_saved()
	{
		return $this->js_db_handle();
	}
	
	public function import_js_from_theme( $source_dir )
	{

		$file = $source_dir. '/layouts.js';

		if( !file_exists( $file  ) ) return;

		$import_js = @file_get_contents($file);

        return $this->import_js( $import_js );
	}

    public function import_js( $import_js, $overwrite = false )
    {
        if( !$import_js ) return false;

        $js = $this->get_layouts_js();

        if( $js == $import_js ) return false;

        if( $overwrite === false )
        {
            if ($js == '' || $js == self::INITIAL_JS) {

                $options = $this->options_manager->get_options();

                return $this->save_layout_js_to_db( $import_js, $options, true ) === null ? false : true;
            }
        }
        else
        {
            $options = $this->options_manager->get_options();

            return $this->save_layout_js_to_db( $import_js, $options, true ) === null ? false : true;
        }

        return false;
    }
     
}