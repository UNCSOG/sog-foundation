<?php

use OTGS\Toolset\CRED\Controller\FieldsControl\Db;

/**
 * Add a non Toolset field under Forms control.
 * 
 * @since 2.1
 */
class CRED_Ajax_Handler_Fields_Control_Add extends Toolset_Ajax_Handler_Abstract {

    /**
     * Fields control database controller.
     *
     * @var OTGS\Toolset\CRED\Controller\FieldsControl\Db
     */
    private $di_db;

    public function __construct(
		$ajax_manager,
		Db $di_db = null
	) {
        parent::__construct( $ajax_manager );
        $this->di_db = $di_db;
    }
    
    private function get_fields_control_db_manager() {
        $this->di_db = ( null === $this->di_db )
        	? new Db()
            : $this->di_db;
        return $this->di_db;
    }

	function process_call( $arguments ) {
		$this->ajax_begin( 
			array( 
				'nonce' => CRED_Ajax::CALLBACK_FIELDS_CONTROL_ADD,
                'parameter_source' => 'post',
                'capability_needed' => CRED_CAPABILITY
			) 
        );

		$domain = toolset_getpost( 'domain' );
		if ( empty( $domain ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing domain.', 'wp-cred' ) ), false );
		}
        
        $meta_key = toolset_getpost( 'metaKey' );
		if ( empty( $meta_key ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing meta key.', 'wp-cred' ) ), false );
        }
        $meta_key = sanitize_text_field( $meta_key );

        $post_type = toolset_getpost( 'postType' );
        if ( empty( $post_type ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing post type.', 'wp-cred' ) ), false );
        }

        $field_data = array(
            'name' => $meta_key,
            'type' => toolset_getpost( 'type' ),
            'default' => toolset_getpost( 'default' ),
            'required' => ( 'true' == toolset_getpost( 'required' ) ),
            'validate_format' => ( 'true' == toolset_getpost( 'validateFormat' ) ),
            'include_scaffold' => ( 'true' == toolset_getpost( 'includeInScaffold' ) ),
            'options' => toolset_getpost( 'options', array() )
        );

        $db_manager = $this->get_fields_control_db_manager();

        $db_manager->set_field( $field_data, $post_type );

        $field_data = $db_manager->get_field( $meta_key, $post_type );

		$this->ajax_finish( array( 'fieldData' => $field_data ), true );
	}
	
}