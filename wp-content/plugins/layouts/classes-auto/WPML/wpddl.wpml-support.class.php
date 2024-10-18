<?php

class WPDDL_WPML_Support {

	public function add_hooks() {
		add_action( 'wpml_before_init', array( $this, 'initialize' ) );
	}

	public function initialize() {
		$wpml_private_layouts = new WPDDL_WPML_Private_Layout( new WPDD_json2layout(), new WPDDL_Private_Layout() );
		$wpml_private_layouts->add_hooks();

		$update_translated_private_layouts = new WPDDL_Update_Translated_Private_Layout( $wpml_private_layouts );
		$update_translated_private_layouts->add_hooks();

		$wpml_tm_dashboard = new WPDDL_WPML_TM_Dashboard();
		$wpml_tm_dashboard->add_hooks();
	}

}