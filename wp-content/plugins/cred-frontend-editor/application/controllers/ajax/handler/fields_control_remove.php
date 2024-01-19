<?php

use OTGS\Toolset\CRED\Controller\FieldsControl\Db;

/**
 * Remove a non Toolset field from Forms control.
 * 
 * @since 2.1
 */
class CRED_Ajax_Handler_Fields_Control_Remove extends Toolset_Ajax_Handler_Abstract {

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
				'nonce' => \CRED_Ajax::CALLBACK_FIELDS_CONTROL_REMOVE,
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
		
		$db_manager = $this->get_fields_control_db_manager();

		$db_manager->remove_field( $meta_key, $post_type );
		
		$this->ajax_finish( array(), true );
	}
	
}