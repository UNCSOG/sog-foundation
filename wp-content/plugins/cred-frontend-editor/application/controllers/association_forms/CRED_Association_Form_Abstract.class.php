<?php
abstract class CRED_Association_Form_Abstract{
	private $model_factory = null;
	private $assets_to_load;
	protected $model = null;
	protected $view = null;
	protected $helper = null;
	public $assets_manager = null;

	const LISTING_SLUG = 'cred_relationship_forms';
	const EDITOR_SLUG = 'cred_relationship_form';

	abstract protected function add_hooks();


	public function __construct( CRED_Association_Form_Model_Factory $model_factory = null, CRED_Association_Form_Relationship_API_Helper $helper = null ) {
		$this->helper = $helper;
		$this->model_factory = $model_factory;
		add_action( 'init', array( $this,'initialize' ), 12 );
	}

	public function initialize(){
		$this->add_hooks();
		$this->assets_manager = Toolset_Assets_Manager::get_instance();
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::get_instance();
		$toolset_common_bootstrap->register_gui_base();
	}

	public function define_assets( $js, $styles ){
		$this->assets_to_load['styles'] = $styles;
		$this->assets_to_load['js'] = $js;
		return $this->assets_to_load ;
	}

	public function load_assets(){
		do_action( 'toolset_enqueue_scripts', $this->assets_to_load['js'] );
		do_action( 'toolset_enqueue_styles', $this->assets_to_load['styles'] );
	}

	public function get_model( $model_type = null, $args = null ){

		if( null === $this->model ){
			if( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['id'] ) ){
				$this->model = $this->build_model( $model_type, $_GET );
			} else {
				$this->model = $this->build_model( $model_type, $args );
			}
		}

		return $this->model;
	}

	protected function build_model( $model_type = null, $args = null ){
		try{
			if( null === $model_type ){
				return $this->model_factory->build( 'Model', $args );
			} else {
				return $this->model_factory->build( $model_type, $args );
			}
		} catch( Exception $exception ){
			error_log( $exception->getMessage() );
			return null;
		}
	}

	public function get_view( $view_type, $model = null, $helper = null, $repository = null ){

		$view_factory = new CRED_Page_Manager_Factory();
		try{
			$view = $view_factory->build( $view_type, $model, $helper, $repository );
			return $view;
		} catch( Exception $exception ){
			error_log( $exception->getMessage() );
			return null;
		}
	}

}
