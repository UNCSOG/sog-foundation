<?php
class WDDL_ExtraModulesLoader {

    private static $instance;

    private $root_dir;


    private function __construct() {
        $this->root_dir = dirname( __FILE__ );
        $this->load_modules();
    }


    private function load_modules() {
        // WP is not loaded don't do nothing
        if( !defined( 'ABSPATH' ) ) {
            exit;
        }

        $scanned_directory = array_diff( scandir( $this->root_dir ), array( '..', '.' ) );

        foreach( $scanned_directory as $sub_dir ) {
            $module_dir = $this->root_dir . DIRECTORY_SEPARATOR . $sub_dir;

            if( is_dir( $module_dir ) ) {
                $module_loader_file = $module_dir . DIRECTORY_SEPARATOR . $sub_dir . '.php';

                if( is_file( $module_loader_file ) ) {

                    require_once( $module_loader_file );
                }
            }
        }
    }


    public static function getInstance() {
        if( !self::$instance ) {
            self::$instance = new WDDL_ExtraModulesLoader();
        }

        return self::$instance;
    }


	/**
	 * @return string Root directory of extra modules.
	 */
    public function get_root_dir() {
        return $this->root_dir;
    }
}