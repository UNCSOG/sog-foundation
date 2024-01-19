<?php

/**
 * Class CRED_Shortcode_Association_Form
 *
 * @since m2m
 */
class CRED_Shortcode_Association_Form extends CRED_Shortcode_Form_Abstract implements CRED_Shortcode_Interface, CRED_Shortcode_Interface_Conditional {
	
	const SHORTCODE_NAME = 'cred-relationship-form';
	
	protected function set_shortcode_atts() {
		$this->shortcode_atts = array(
			'form' => '',
			'parent_item' => '',
			'child_item'  => '',
			'role_items' => ''
		);
	}

	/**
	 * @return bool
	 *
	 * @since m2m
	 */
	public function condition_is_met() {
		return apply_filters( 'toolset_is_m2m_enabled', false );
	}
	
	/**
	 * @return null|WP_Post|WP_Error
	 *
	 * @since m2m
	 */
	protected function get_object_form() {
		$form_object = cred_get_object_form( $this->user_atts['form'], CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE );
		
		if ( ! $form_object instanceof WP_Post ) {
			return new WP_Error(
				'missing-relationship-form',
				__( 'This relationship form no longer exists', 'wp-cred' )
			);
		}
		
		if ( 'publish' != $form_object->post_status ) {
			return new WP_Error(
				'unpublished-relationship-form',
				__( 'This relationship form is not published', 'wp-cred' )
			);
		}
		
		$relationship_slug = get_post_meta( $form_object->ID, 'relationship', true );
		
		if ( empty( $relationship_slug ) ) {
			return new WP_Error(
				'missing-relationship',
				__( 'This relationship form is not related to any relationship', 'wp-cred' )
			);
		}
		
		do_action( 'toolset_do_m2m_full_init' );
		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$definition = $relationship_repository->get_definition( $relationship_slug );
		
		if ( ! $definition instanceof Toolset_Relationship_Definition ) {
			return new WP_Error(
				'missing-relationship',
				__( 'This relationship form refers to a relationship that no longer exists', 'wp-cred' )
			);
		}
		
		if ( ! $definition->is_active() ) {
			return new WP_Error(
				'missing-relationship',
				__( 'This relationship form refers to a relationship that is not active', 'wp-cred' )
			);
		}
		
		return $form_object;
	}
	
}