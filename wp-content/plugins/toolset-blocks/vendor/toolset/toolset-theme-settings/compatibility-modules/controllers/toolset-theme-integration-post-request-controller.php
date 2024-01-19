<?php

/**
 * Class Toolset_Theme_Integration_Settings_Post_Request_Controller
 * It is meant to run when $_REQUEST holds Toolset_Theme_Integration_Settings_Helper::load_current_settings_object $object_id argument value, e.g. $_POST['layout_id'] value is set (for example on assignment change request)
 */
class Toolset_Theme_Integration_Settings_Post_Request_Controller extends Toolset_Theme_Integration_Settings_Admin_Controller {

	public function __construct( Toolset_Theme_Integration_Settings_Helper $helper = null, $object_id = null ){
		if( !$helper->has_theme_settings() ){
			$helper->load_current_settings_object( null, $object_id );
		}
		parent::__construct( $helper, $object_id );
	}

	public function init(){
		parent::init();
	}

	public function admin_init(){
		parent::admin_init();
	}
}