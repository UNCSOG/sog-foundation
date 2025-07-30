<?php

if( !defined('FS_CHMOD_DIR') ) define( 'FS_CHMOD_DIR', ( 0755 & ~ umask() ) );
if( !defined('FS_CHMOD_FILE') ) define( 'FS_CHMOD_FILE', ( 0644 & ~ umask() ) );

Class WPDD_FileManager{

	private $dir_message = '';
	private $dir_writable = true;
	private $theme_layouts_dir = '';
	private $url_nonce_string = '';
    private $root_dir = '';

	function __construct( $dir = '', $nonce = '', $root = false )
	{
        $this->set_root_dir( $root );
		$this->theme_layouts_dir = $this->get_root_dir() . $dir;
		$this->url_nonce_string = $nonce;
	}

	private function set_root_dir( $root ){
        $this->root_dir = $root ? $root : get_stylesheet_directory();
    }

    public function get_root_dir(){
        return $this->root_dir;
    }

	function check_theme_dir_is_writable( $suggest_message )
	{
		if ( !is_dir( $this->theme_layouts_dir ) ) {
			if ( !is_writable( $this->get_root_dir() ) ) {
				$this->dir_message = sprintf(__('The theme directory %s is not writable.', 'ddl-layouts'),
					'<strong>' . $this->get_root_dir() . '</strong>');
				$this->dir_writable = false;
			} else {
				mkdir( $this->theme_layouts_dir, FS_CHMOD_DIR);
			}
		} else {
			if (!is_writable( $this->theme_layouts_dir) ) {
				$this->dir_message = sprintf(__('The layouts directory %s is not writable.', 'ddl-layouts'),
					'<strong>' . $this->theme_layouts_dir . '</strong>');
				$this->dir_writable = false;
			}
		}

		if ( !$this->dir_writable ) {
			$this->dir_message .= '<br />';
			$this->dir_message .= $suggest_message;
		}

		return $this->dir_message;
	}

	function get_dir_message()
	{
		return $this->dir_message;
	}

	function print_dir_message()
	{
		echo $this->get_dir_message();
	}

	function dir_is_writable(){
		return $this->dir_writable;
	}

	function get_layouts_theme_dir()
	{
		return $this->theme_layouts_dir;
	}

	function file_get_name_and_extension( $str_name )
	{
		$last = strrpos( $str_name, '.' );

		$name = substr($str_name, 0, $last);
		$extension = substr($str_name, $last);
		$ret = (object) array('name'=>$name, 'extension' => $extension);
		return $ret;
	}

	function save_file( $name, $extension, $content = '', $extra = array(), $force_rewrite = false )
	{

		$this->check_theme_dir_is_writable( '' );

		$file_name = $this->theme_layouts_dir . '/' . $name . $extension;

		// if the file is already there and dir is writable say ok but don't do nothing
		if( is_file($file_name) && is_writable($file_name) && $force_rewrite === false )
		{
			$file_ok = true;
		}
		// if the file is not there do it
		else if ( !is_file($file_name) || $force_rewrite || is_writable($file_name) ) {
			// this repetition to prevent problems when one switch theme
			if( is_file($file_name) && is_writable($file_name) ){
				$file_ok = file_put_contents($file_name, $content) !== false;
			}
			else if( !is_file($file_name) )
			{
				$file_ok = file_put_contents($file_name, $content) !== false;
			}
			else{
				$file_ok = false;
			}
		}
		//if dir is not writable notify
		else {
			$file_ok = false;
		}

		$results = array(
			'file_ok' => $file_ok,
			'file_name' => $file_name
		);

		return array_merge( $results, $extra );
	}

	public static function file_exists( $file )
	{
		return file_exists( $file );
	}

	public static function get_file_content( $file )
	{
		if( self::file_exists($file) )
		{
			$ret = file_get_contents( $file );

			if( $ret !== false )
			{
				return $ret;
			}
			else
			{
				return sprintf(__('Error reading file %s', 'ddl-layouts'), $file ) ;
			}
		}
		else
		{
			return sprintf(__('File %s does not exist', 'ddl-layouts'), $file ) ;
		}

	}

	public static function include_files_from_dir( $path, $tpls_dir, $scope = null )
	{
		$dir_str = $path . $tpls_dir . '/';
		$dir = opendir( $dir_str );
		while( ( $currentFile = readdir($dir) ) !== false )
		{
			if ( $currentFile == '.' || $currentFile == '..' || $currentFile[0] == '.' )
			{
				continue;
			}

			include $dir_str.$currentFile;
		}
		closedir($dir);
	}
}