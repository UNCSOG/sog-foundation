<?php
if( ! class_exists( 'CRED_scripts_manager', false ) ) {
    class CRED_style extends Toolset_Style
    {

        public function __construct($handle, $path = 'wordpress_default', $deps = array(), $ver = false, $media = 'screen')
        {
            parent::__construct($handle, $path, $deps, $ver, $media);
        }
    }

    class CRED_script extends Toolset_Script
    {
        public function __construct($handle, $path = 'wordpress_default', $deps = array(), $ver = false, $in_footer = false)
        {
            parent::__construct( $handle, $path, $deps, $ver, $in_footer);
        }
    }

    class CRED_scripts_manager extends Toolset_Assets_Manager
    {

        protected function initialize_styles()
        {

            return;
        }


        protected function initialize_scripts()
        {

            return;
        }
    }
    add_action( 'init', array('CRED_scripts_manager', 'getInstance') );
}
