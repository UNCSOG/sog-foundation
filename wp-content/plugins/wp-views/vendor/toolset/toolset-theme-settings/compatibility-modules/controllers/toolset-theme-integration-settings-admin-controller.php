<?php
//TODO: let's remove this one
class Toolset_Theme_Integration_Settings_Admin_Controller extends Toolset_Theme_Integration_Settings_Abstract_Controller {

	public function __construct( Toolset_Theme_Integration_Settings_Helper $helper = null, $arg_one = null ){
		parent::__construct( $helper, $arg_one );
	}

	public function init(){
		parent::init();
	}

	public function admin_init(){
		parent::admin_init();
	}
}