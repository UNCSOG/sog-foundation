<?php
abstract class WPDDL_Framework_Integration_Abstract{
        protected $framework_slug;
        protected $framework_label;

        final public static function get_instance() {
            static $instances = array();
            $called_class = get_called_class();
            if( !isset( $instances[ $called_class ] ) && class_exists($called_class) ) {
                $instances[ $called_class ] = new $called_class();
            }
            return $instances[ $called_class ];
        }

        protected function __construct(){
            add_action('ddl-integration_override_before_init', array(&$this,'setUpFrameWork'), 10, 2);
            add_action( 'init', array(&$this, 'run'), 20 );
        }

        protected function __clone() { }

        public function setUpFrameWork( $slug, $label ){
                $this->framework_slug = $slug;
                $this->framework_label = $label;
        }

        private function override_framework_default( $option ){
        	$framework = get_option( WPDDL_FRAMEWORK_OPTION_KEY, WPDDL_FRAMEWORK );

        	if( $framework !== $option ){
		        return update_option( WPDDL_FRAMEWORK_OPTION_KEY, $option, $framework );
	        }

			return false;
        }

        public function run(){
            $message = '';
            if( $this->check_implementation() ){
	            $this->override_framework_default( $this->framework_slug );
                add_filter('ddl-set_framework', array(&$this, 'get_slug'), 10, 1 );
                add_filter('ddl-set_framework_option', array(&$this, 'get_slug'), 10, 1 );
                add_filter('ddl-set_up_frameworks', array(&$this, 'set_label'), 10, 1);
                add_filter('ddl-get_current_framework_name', array(&$this, 'get_label'), 10, 1);
                add_filter('ddl-get_current_framework_option', array(&$this, 'get_slug') );
                add_filter( 'ddl-get-column-prefix', array(&$this, 'getColumnPrefix'), 12 );
                add_filter('ddl-get_additional_column_class', array(&$this, 'get_additional_column_class') );
                do_action('ddl-init_integration_override');
            } else {
                $message .= sprintf( '<p>%s</p>', __( 'Framework integration failed since we are missing framework slug and name. Please call this action with those values are parameters in your constructor function: do_action(\'ddl-integration_override_before_init\', \'slug\', \'Framework name\');', 'ddl-layouts' ) );
                WPDDL_Messages::add_admin_notice( 'warning', $message, true );
            }
        }

        public function get_slug(){
            return $this->framework_slug;
        }

        public function set_label( $array ){
            $array[$this->get_slug()] = (object) array('label' => $this->framework_label);
            return $array;
        }

        public function get_label(){
            return $this->framework_label;
        }

        private function check_implementation(){
            global $wp_filter;
            if( array_key_exists('ddl-integration_override_before_init', $wp_filter) &&
                is_string( $this->framework_slug ) &&
                is_string( $this->framework_label )
            ) {
                return true;
            } else {
                return false;
            }
            return false;
        }

        private function fail(){

        }

        abstract public function getColumnPrefix();

        abstract public function get_additional_column_class();
}