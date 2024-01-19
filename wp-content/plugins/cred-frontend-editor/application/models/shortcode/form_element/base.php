<?php

/**
 * Class CRED_Shortcode_Element_Base
 *
 * @since m2m
 */
class CRED_Shortcode_Element_Base {
	
	/**
	 * @var CRED_Frontend_Form_Flow
	 */
	private $frontend_form_flow;
	
	/**
	 * @param CRED_Frontend_Form_Flow $relationship_service
	 */
	public function __construct( CRED_Frontend_Form_Flow $frontend_form_flow ) {
		$this->frontend_form_flow = $frontend_form_flow;
	}
	
	/**
	 * @return CRED_Frontend_Form_Flow
	 *
	 * @since m2m
	 */
	protected function get_frontend_form_flow() {
		return $this->frontend_form_flow;
	}
	
}