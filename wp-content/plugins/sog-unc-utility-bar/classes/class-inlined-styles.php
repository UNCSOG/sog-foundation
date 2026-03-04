<?php
/**
 * collecting dynamic styles to be inlined
 */
namespace Utility_Bar;

class Inlined_Styles{

    // singleton instance
    private static $instance;
    
    public static $styles;

    function __construct( ){
        self::$styles = '';
    }

    /**
     * adds well formated css to the styles var
     *
     * @param [type] $new_styles
     * @return void
     */
    function add_styles( $new_styles ){
        self::$styles .= $new_styles;
    }

    /**
     * gets the styles var to be outputted
     *
     * @return void
     */
    function get_styles(){
        if( '' != self::$styles ){
            return self::$styles;
        } else {
            return false;
        }
    }

    /**
     * format a style rule
     *
     * @param [string] $selector
     * @param [array] $declarations
     * @return void
     */
    function make_style_rule( $selector, $declarations ){
        $style = '';
        if( is_array( $declarations ) && !empty( $declarations ) ){
            $style .= $selector . '{';
                foreach( $declarations as $declaration_key => $declaration_val ){
                    $style .= ' ' . $declaration_key . ':' . $declaration_val .';';
                }
            $style .= ' }';
        }

        return $style;
    }

    /**
     * you need to use the singleton so it's all there at the end
     *
     * @return Inlined_Styles
     */
    public static function getInstance(): Inlined_Styles{
        static::$instance = static::$instance ?? new static();
        return static::$instance;
    }
}