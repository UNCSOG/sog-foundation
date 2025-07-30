<?php

/**
 * This class will load compatibility classes only in case when specific 3rd party plugin is active.
 *
 * @since Layouts 2.0.2
 * @todo Check why this needs to be a singleton at all.
 */
class Toolset_Compatibility_Loader {

    private static $instance;

    public static function get_instance() {
        if ( ! self::$instance ) {
            self::$instance = new Toolset_Compatibility_Loader( new Toolset_Compatibility_Handler_Factory() );
        }

        return self::$instance;
    }

	/** @var Toolset_Compatibility_Handler_Factory */
    private $handler_factory;


	/**
	 * Toolset_Compatibility_Loader constructor.
	 *
	 * @param Toolset_Compatibility_Handler_Factory $handler_factory
	 */
    public function __construct( Toolset_Compatibility_Handler_Factory $handler_factory ) {
    	$this->handler_factory = $handler_factory;
    }

    public function initialize(){
        add_action( 'init', array( $this, 'initialize_interop_handlers' ), 10 );
		$this->run_theme_handler_factory();
    }



    /**
     * Load and initialize compatibility handlers if the relevant plugin/theme is active.
     *
     * @since Layouts 2.0.2
     */
    public function initialize_interop_handlers() {

        /**
         * Use filter to register compatibility classes that needs to be loaded here
         *
         * Example:
         *
         * add_filter( 'toolset_register_compatibility_classes',  'register_compatibility_classes' , 99, 1 );
         * function register_compatibility_classes($classes_to_load){
         *     $classes_to_load[] = array(
         *         'name'       => 'Easy Digital Downloads',
         *         'class_name' => 'Layouts_Compatibility_Easy_Digital_Downloads'
         *     );
         *     return $classes_to_load;
         * }
         *
         */
        $classes_to_load = apply_filters( 'toolset_register_compatibility_classes', array() );

        if ( is_array( $classes_to_load ) ) {
            foreach ( $classes_to_load as $handler_definition ) {
                $handler_class_name = $handler_definition['class_name'];
                if ( class_exists( $handler_class_name ) ) {
                    $handler = $this->handler_factory->create( $handler_class_name );
                    $handler->initialize();
                }
            }
        }
    }

    public function run_theme_handler_factory(){
        // create instance of Toolset Compatibility Themes Factory if Layouts or Views are active
        $layouts_active = new Toolset_Theme_Settings_Condition_Plugin_Layouts_Active();
        $views_active   = new Toolset_Theme_Settings_Condition_Plugin_Views_Active();
        $blocks_active   = new Toolset_Theme_Settings_Condition_Plugin_Blocks_Active();

        if ( $layouts_active->is_met() || $views_active->is_met() || $blocks_active->is_met() ) {
            new Toolset_Compatibility_Theme_Handler_Factory();
        }
    }

}
