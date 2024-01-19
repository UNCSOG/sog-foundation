<?php

/**
 * Class CRED_Shortcode_Association_Base
 *
 * @since m2m
 */
class CRED_Shortcode_Association_Base {
	
	/**
	 * @var CRED_Shortcode_Association_Helper
	*/
	protected $helper;
	
	/**
	 * @var CRED_Frontend_Form_Flow
	 */
	private $frontend_form_flow;
	
	/**
	 * @var Toolset_Relationship_Service
	 */
	private $relationship_service;
	
	/**
	 * @param CRED_Shortcode_Association_Helper $helper
	 */
	public function __construct( CRED_Shortcode_Association_Helper $helper ) {
		$this->helper = $helper;
		$this->frontend_form_flow = $helper->get_frontend_form_flow();
		$this->relationship_service = $helper->get_relationship_service();
	}
	
	/**
	 * @return CRED_Frontend_Form_Flow
	 *
	 * @since m2m
	 */
	protected function get_frontend_form_flow() {
		return $this->frontend_form_flow;
	}
	
	/**
	 * @return Toolset_Relationship_Service
	 *
	 * @since m2m
	 */
	protected function get_relationship_service() {
		return $this->relationship_service;
	}
	
}