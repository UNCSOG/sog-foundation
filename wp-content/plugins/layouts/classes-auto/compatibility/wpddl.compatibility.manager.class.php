<?php

/**
 * @since Layouts 2.0.2
 * Class DDL_Compatibility_Manager
 *
 * Define list of classes that needs to be loaded to keep compatibility between
 * Layouts and other plugins
 */
class DDL_Compatibility_Manager {

    private $classes_to_load = array();


    /**
     * Populate array with classes that needs to be load
     *
     * @return array
     */
    public function classes_to_load() {


        // Check whether the Elementor page builder is loaded.
        if ( class_exists( 'Elementor\Plugin' ) ) {
            $this->classes_to_load[] = array(
                'name'       => 'Elementor',
                'class_name' => 'Layouts_Compatibility_Elementor'
            );
        }

        //Check whether the Easy Digital Downloads page builder is loaded.
        if ( class_exists( 'Easy_Digital_Downloads' ) ) {
            $this->classes_to_load[] = array(
                'name'       => 'Easy Digital Downloads',
                'class_name' => 'Layouts_Compatibility_Easy_Digital_Downloads'
            );
        }

        return $this->classes_to_load;
    }

}