<?php

use OTGS\Toolset\CRED\Controller\Factory;

class CRED_Association_Form_Main {
	const ASSOCIATION_FORMS_POST_TYPE = 'cred_rel_form';
	const TRANSIENT_KEY = 'cred_transient_published_rel_forms';

	const RELATIONSHIP_POST_TYPE = 'relationship';
	const CRED_ASSOCIATION_FORM_AJAX_ACTION = 'cred_association_form_ajax_submit';
	const CRED_ASSOCIATION_FORM_AJAX_NONCE = 'cred_association_form_ajax_submit_nonce';

	private $controller_factory = null;
	private $model_factory = null;
	private $helper = null;

	private $condition_post_request = false;
	private $condition_front_end = false;
	private $condition_ajax_request = false;
	private $condition_back_end = false;

	private $requirements_met = false;

	public function __construct( Factory $controller_factory, CRED_Association_Form_Model_Factory $factory_model, CRED_Association_Form_Relationship_API_Helper $helper ) {
		$this->controller_factory = $controller_factory;
		$this->model_factory = $factory_model;
		$this->helper = $helper;
	}

	public function initialize(){
		add_action( 'init' ,array( $this, 'run_association_forms_if' ) );
	}

	public function run_association_forms_if(){

		// initialize only if requirements are meet
		if( ! $this->check_requirements() ){
			return false;
		}

		return $this->run();
	}


	/**
	 * Initialize association forms
	 */
	protected function run(){
		$controller = null;

		$this->register_association_forms_post_type();
		$this->add_hooks();
		$this->set_routing_conditions();
		return $this->route();
	}

	private function route(){
		try{

			$controller = null;

			if(  $this->condition_ajax_request ){
				return $controller;
			}

			if( $this->condition_post_request ){
				$this->controller_factory->build( 'association', 'Post_Request', $this->model_factory, $this->helper );
			}

			if( $this->condition_back_end ){
				$controller = $this->controller_factory->build( 'association', 'Back_End', $this->model_factory, $this->helper );
			} else if( $this->condition_front_end ){
				$controller = $this->controller_factory->build( 'association', 'Front_End', $this->model_factory, $this->helper );
			}
			return $controller;
		} catch( Exception $exception ){
			error_log( $exception->getMessage() );
			return null;
		}
	}

	protected function add_hooks(){
		// Add hooks if necessary here
	}


	/**
	 * Check requirements for associations forms and update $this->requirements_met
	 * Types plugin must be active
	 * m2m must be enabled
	 */
	public function check_requirements(){
		// check is types active
		$types_active = new Toolset_Condition_Plugin_Types_Active();
		$is_types_active = $types_active->is_met();
		// is m2m enabled
		$is_m2m_enabled = apply_filters( 'toolset_is_m2m_enabled', false );

		if( $is_types_active && $is_m2m_enabled ){
			$this->requirements_met = true;
			return true;
		}

		return false;
	}

	/**
	 * Check if we are in the frontend singular page of one of this forms, by checking the following conditions:
	 * - Not in admin.
	 * - Has a 'post_type' query argument with the current form slug as value.
	 * - Has a Content Template forced upon the content coming from a 'content-template-id' query argument.
	 *
	 * @return bool
	 *
	 * @since m2m
	 */
	private function is_fronted_form_singular() {
		$post_id = toolset_getget( 'p' );
		$content_template_id = toolset_getget( 'content-template-id' );
		return (
			! is_admin()
			&& self::ASSOCIATION_FORMS_POST_TYPE === toolset_getget( 'post_type' )
			&& ! empty( $post_id )
			&& ctype_digit( $post_id )
			&& ! empty( $content_template_id )
			&& ctype_digit( $content_template_id )
		);
	}

	/**
	 * Register new post type for association forms.
	 *
	 * Note that on their frontend singular pages we declare them as publicly_queryable
	 * so we can display the form from a link in a known environment.
	 */
	private function register_association_forms_post_type() {
		$args = array(
			'public' => false,
			'publicly_queryable' => $this->is_fronted_form_singular(),
			'show_ui' => true,
			'show_in_menu' => false,
			'query_var' => false,
			'rewrite' => false,
			'can_export' => false,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => 50,
			'supports' => array( 'title', 'editor' ),
		);
		register_post_type( self::ASSOCIATION_FORMS_POST_TYPE, $args );
	}

	/**
	 * Set the condition for AJAX or post request
	 *
	 * @note This condition should not rely just on toolset_getpost( 'cred_form_id', false ),
	 * it is waaaaaay to dangerous and narrows the usage of the cred_form_id
	 * URL parameter, also for only association forms.
	 */
	protected function set_condition_is_post_request(){
		if(
			is_admin()
			&& self::CRED_ASSOCIATION_FORM_AJAX_ACTION === toolset_getpost( 'action' )
		){
			$this->condition_ajax_request = toolset_getpost( 'cred_form_id', true );
		} else if(
			toolset_getpost( 'cred_form_id', false )
			&& toolset_getpost( 'cred_relationship_slug', false )
		){
			$this->condition_post_request = toolset_getpost( 'cred_form_id' );
		}
	}

	protected function set_condition_is_back_end(){
		if( is_admin() &&
		    /* null is a valid value here */
		     $this->condition_ajax_request === false &&
		     $this->condition_post_request === false
		){
			$this->condition_back_end = true;
		}
	}

	protected function set_condition_is_front_end(){
		if( ! is_admin() ){
			$this->condition_front_end = true;
		}
	}

	protected function set_routing_conditions(){
		$this->set_condition_is_post_request();
		$this->set_condition_is_back_end();
		$this->set_condition_is_front_end();
	}
}
