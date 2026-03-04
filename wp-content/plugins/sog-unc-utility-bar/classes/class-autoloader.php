<?php
/**
 * Autoloads classes from the classes folder so you dont have to include() anything
 * 
 * @package Utility_Bar
 * 
 */

namespace Utility_Bar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Autoloader{
    private $namespace, $path;
    function __construct( $namespace, $path ){
        $this->namespace = $namespace;
        $this->path = $path;
    }
    
    /**
     * The autoloader
     *
     * @param string $class_name $class_name the classname as it comes in from spl_autoload_register in plugin root file
     *
     * @return  null
     */
    function autoloader( $class_name ) {
		if ( false !== strpos( $class_name, $this->namespace ) ) {
            $classDir = $this->path . 'classes';
            $blocksDir = $this->path .'blocks';
            $class_name_array = explode( '\\', $class_name );
            $fullpath = null;
			if( $this->namespace == $class_name_array[0] && !class_exists( $class_name )  ){
                //remove namespace
                unset( $class_name_array[0] ); 
                //fix keys
                $class_name_array = array_values( $class_name_array ); 
                //count array entities
                $array_count = count( $class_name_array ); 

                
                if( 'blocks' === strtolower($class_name_array[0]) ){
                    $path = $blocksDir;
                    $class_name_raw = $class_name_array[ $array_count - 1 ];
                    $class_name = strtolower( str_replace( "_", "-", $class_name_raw ) );
                    $path .= '/'.$class_name;
                    $class_file = '/class-' . $class_name . '.php';
        
                } else {
                    //if it's not a block, this should start in the class directory
                    $path = $classDir;
                    for( $i = 0; $i < $array_count - 1; $i++ ){//loop thru classname
                        $path .= "/" . strtolower( str_replace( "_", "-", $class_name_array[$i] ) );
                    }
                    $class_name_raw = $class_name_array[ $array_count - 1 ];
                    $class_name = strtolower( str_replace( "_", "-", $class_name_raw ) );
                    $class_file = '/class-' . $class_name . '.php';
                   
                }

                if( $path && $class_file ){
                    $fullpath  = $path . $class_file;
                }
                
                //error_log( $fullpath );
            }

            if( $fullpath ){
                include $fullpath;
            }
		}
    }
}