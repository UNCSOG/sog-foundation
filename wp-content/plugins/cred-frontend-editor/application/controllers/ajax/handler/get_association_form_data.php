<?php

/**
 * Get the association form data needed to generate the shortcode dialog to insret it.
 *
 * Right now, it just returns data related to the involved roles plus 
 * whether the underlying relationship has its own fields.
 *
 * @since m2m
 */
class CRED_Ajax_Handler_Get_Association_Form_Data extends Toolset_Ajax_Handler_Abstract{

	
	private function add_noun_indefinite_article( $noun ) {
		$a_article = __( 'a %s', 'wp-cred' );
		$an_article = __( 'an %s', 'wp-cred' );
		
		$first_letter = substr( $noun, 0, 1 );
		$first_letter = strtolower( $first_letter );
		$article = in_array( $first_letter, array( 'a', 'e', 'i', 'o', 'u' ) ) ? $an_article : $a_article;
		
		return sprintf(
			$article,
			$noun
		);
	}

	function process_call( $arguments ) {
		$this->ajax_begin( 
			array( 
				'nonce' => CRED_Ajax::CALLBACK_GET_ASSOCIATION_FORM_DATA, 
				'parameter_source' => 'get', 
				'is_public' => true
			) 
		);

		$form_slug = toolset_getget( 'form' );
		
		if ( empty( $form_slug ) ) {
			$this->ajax_finish( array( 'message' => __( 'The form no longer exists.', 'wp-cred' ) ), false );
		}
		
		$form_object = cred_get_object_form( $form_slug, CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE );
		
		if ( ! $form_object instanceof WP_Post ) {
			$this->ajax_finish( 
				array( 
					'message' => sprintf( 
						__( 'The form %s no longer exists.', 'wp-cred' ), 
						'<strong>' . esc_html( $form_slug ) . '</strong>'
					) 
				), 
				false 
			);
		}
		
		$relationship = get_post_meta( $form_object->ID, 'relationship', true );
		
		if ( empty( $relationship ) ) {
			$this->ajax_finish( 
				array( 
					'message' => sprintf( 
						__( 'The form %s is not associated to a relationship.', 'wp-cred' ), 
						'<strong>' . esc_html( $form_object->post_title ) . '</strong>'
					) 
				),
				false
			);
		}
		
		$results = array(
			'relationship' => array(
				'label' => '',
				'parent' => array(),
				'child' => array(),
				'hasFields' => false
			)
		);
		
		do_action( 'toolset_do_m2m_full_init' );
		
		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$relationship_definition = $relationship_repository->get_definition( $relationship );
		
		if ( ! $relationship_definition instanceof Toolset_Relationship_Definition ) {
			$this->ajax_finish( 
				array( 
					'message' => sprintf( 
						__( 'The form %1$s manages a missing %2$s relationship.', 'wp-cred' ),
						'<strong>' . esc_html( $form_object->post_title ) . '</strong>',
						'<strong>' . esc_html( $relationship ) . '</strong>'
					)
				),
				false
			);
		}
		
		if ( ! $relationship_definition->is_active() ) {
			$this->ajax_finish( 
				array( 
					'message' => sprintf( 
						__( 'The form %1$s manages a %2$s relationship that is not active.', 'wp-cred' ),
						'<strong>' . esc_html( $form_object->post_title ) . '</strong>',
						'<strong>' . esc_html( $relationship ) . '</strong>'
					)
				),
				false
			);
		}
		
		$results['relationship']['slug'] = $relationship_definition->get_slug();
		$results['relationship']['label'] = $relationship_definition->get_display_name();
		$results['relationship']['labelSingular'] = $relationship_definition->get_display_name_singular();
		$results['relationship']['labelSingularPrefixed'] = $this->add_noun_indefinite_article( $relationship_definition->get_display_name_singular() );
		$results['relationship']['addNew'] = sprintf(
			__( 'Edit this %s', 'wp-cred' ),
			$relationship_definition->get_display_name_singular()
		);

		$parent_types = $relationship_definition->get_parent_type()->get_types();
		$parent_type = $parent_types[0];
		$parent_type_object = get_post_type_object( $parent_type );
		$child_types = $relationship_definition->get_child_type()->get_types();
		$child_type = $child_types[0];
		$child_type_object = get_post_type_object( $child_type );
		
		$results['relationship']['parent']['type']  = $parent_type;
		$results['relationship']['parent']['label'] = $parent_type_object->label;
		$results['relationship']['parent']['labelSingular'] = $parent_type_object->labels->singular_name;
		$results['relationship']['parent']['labelSingularPrefixed'] = $this->add_noun_indefinite_article( $parent_type_object->labels->singular_name );
		$results['relationship']['parent']['addNew'] = sprintf(
			__( 'Connect another %s', 'wp-cred' ),
			$parent_type_object->labels->singular_name
		);

		$results['relationship']['child']['type']   = $child_type;
		$results['relationship']['child']['label']  = $child_type_object->label;
		$results['relationship']['child']['labelSingular'] = $child_type_object->labels->singular_name;
		$results['relationship']['child']['labelSingularPrefixed'] = $this->add_noun_indefinite_article( $child_type_object->labels->singular_name );
		$results['relationship']['child']['addNew'] = sprintf(
			__( 'Connect another %s', 'wp-cred' ),
			$child_type_object->labels->singular_name
		);
		
		if ( $relationship_definition->has_association_field_definitions() ) {
			$results['relationship']['hasFields'] = true;
		}
		
		$this->ajax_finish( $results, true );
		
	}
	
}