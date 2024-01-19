<?php

/**
 * Create a Content Template to be used as edit template, and fill it with the provided form shortcode.
 *
 * @since m2m
 */
class CRED_Ajax_Handler_Create_Form_Template extends Toolset_Ajax_Handler_Abstract {

	function process_call( $arguments ) {
		$this->ajax_begin( 
			array( 
				'nonce' => CRED_Ajax::CALLBACK_CREATE_FORM_TEMPLATE, 
				'parameter_source' => 'get', 
				'is_public' => true
			) 
		);
		
		$condition = new Toolset_Condition_Plugin_Views_Active();
		
		if ( ! $condition->is_met() ) {
			$this->ajax_finish( array( 'message' => __( 'Toolset Views is required.', 'wp-cred' ) ), false );
		}

		$template_title = toolset_getget( 'ctTitle' );
		$shortcode = toolset_getget( 'shortcode' );
		$form_slug = toolset_getget( 'formSlug' );
		
		if (
			empty( $template_title ) 
			|| empty( $shortcode ) 
			|| empty( $form_slug )
		) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing data.', 'wp-cred' ) ), false );
		}
		
		$template_array = array(
			'post_content' => '[' . $shortcode . ' form="' . $form_slug . '"]',
			'post_title' => $template_title,
			'post_status' => 'publish',
			'post_type' => WPV_Content_Template_Embedded::POST_TYPE
		);
		
		$generated_template_id = wp_insert_post( $template_array );
		
		if ( is_wp_error( $generated_template_id ) ) {
			$this->ajax_finish( array( 'message' => __( 'Could not create the template.', 'wp-cred' ) ), false );
		}
		
		$generated_template = get_post( $generated_template_id );
		
		if ( ! $generated_template ) {
			$this->ajax_finish( array( 'message' => __( 'Could not create the template.', 'wp-cred' ) ), false );
		}
		
		$results = array(
			'ctSlug' => $generated_template->post_name
		);
		
		$this->ajax_finish( $results, true );
		
	}
	
}