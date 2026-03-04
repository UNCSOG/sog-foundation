<?php
/**
 * for logging messages
 * this a needs garbage service
 * 
 * @package Utility_Bar
 * 
 */

namespace Utility_Bar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Log{

	private $log_to = 'file';

	private $log_file;

	private $path;

	private $errors;

	function __construct(){
		$uploads_dir = wp_upload_dir();
		$this->path = $uploads_dir['basedir'] . '/alert-service/';
		$this->log_file = 'log_' . date("Y.m.d") . '.log';
	}

	/**
	 * put $log text into a file
	 *
	 * @param [string] $log
	 * @return void
	 */
	function log_to_file( $log_string ){
		//check if directory exists
        //if not attempt to create it

        if( !file_exists( $this->path ) ){
            $mkdir = mkdir( $this->path );
            if( $mkdir ){
                //error_log( 'alert service log folder created' );
            } else {
                $this->errors[] = "could not create 'alert service log folder";
                return false;
            }
        }
        
        //check if file exists
        //if not attempt to create it
        if( !file_exists( $this->path . $this->log_file ) ){
            $touch = touch( $this->path . $this->log_file );
            if( $touch ){
                //error_log( 'site lister file touched');
            } else {
                $this->errors[] = "could not touch site lister file";
                return false;
            }
        }

		
		$log = "User: " . $_SERVER['REMOTE_ADDR'] . ' - ' . date("F j, Y, g:i a") . PHP_EOL . $log_string . PHP_EOL;
		//error_log( var_export( $log, true ) );
		$put = file_put_contents( $this->path . $this->log_file, $log, FILE_APPEND );

		if( false === $put ){
			return false;
		} else {
			return true;
		}
	}

}