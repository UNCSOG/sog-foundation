<?php

use OTGS\Toolset\CRED\Model\Forms\Post\Helper;

/**
 * Get the relevant post form fields given the affected post type.
 *
 * @since 2.1
 */
class CRED_Ajax_Handler_Get_Post_Type_Fields extends Toolset_Ajax_Handler_Abstract {

	/**
	 * @var Toolset_Condition_Plugin_Types_Active
	 *
	 * @since 2.1
	 */
	private $di_toolset_types_condition = null;


	private $toolset_settings;

	public function __construct(
		$ajax_manager,
		$di_toolset_types_condition = null,
		Toolset_Settings $toolset_settings_di = null
	) {
        parent::__construct( $ajax_manager );
        $this->di_toolset_types_condition = ( null === $di_toolset_types_condition )
			? new Toolset_Condition_Plugin_Types_Active()
			: $di_toolset_types_condition;
        $this->toolset_settings = $toolset_settings_di ?: Toolset_Settings::get_instance();
    }

	function process_call( $arguments ) {
		$this->ajax_begin(
			array(
				'nonce' => CRED_Ajax::CALLBACK_GET_POST_TYPE_FIELDS,
				'parameter_source' => 'get',
				'is_public' => true
			)
		);

		$post_type = toolset_getget( 'objectKey' );

		if ( empty( $post_type ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing post type.', 'wp-cred' ) ), false );
			return;
		}

        $post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object ) {
			$this->ajax_finish( array( 'message' => __( 'Undefined post type.', 'wp-cred' ) ), false );
			return;
		}

		$toolbar_helper = new Helper( $post_type_object, $this->di_toolset_types_condition, $this->toolset_settings );

		$results = $toolbar_helper->populate_items();

		$this->ajax_finish( $results, true );
	}

}
