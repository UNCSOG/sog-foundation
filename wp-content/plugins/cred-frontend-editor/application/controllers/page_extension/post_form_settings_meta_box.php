<?php

use OTGS\Toolset\CRED\Model\Wordpress\Status;

/**
 * Class Responsible to create Form Settings Meta Box
 *
 * @since 1.9.3
 */
class CRED_Page_Extension_Post_Form_Settings_Meta_Box extends CRED_Page_Extension_Form_Settings_Meta_Box_Base {

	/**
	 * @var CRED_Page_Extension_Post_Form_Settings_Meta_Box
	 */
	private static $instance;

	/**
	 * @var Status
	 */
	private $status_model = null;

	public function __construct( Status $status_model_di = null ) {
		$this->status_model = ( null === $status_model_di )
			? Status::get_instance()
			: $status_model_di;
	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param array $form
	 * @param array $args
	 */
	public function execute( $form, $args ) {
		//Form Settings
		$settings = $args['args']['form_settings']->form;

		//Get All redirection "go to specific post" settings
		$default_empty_action_post_type_label = esc_attr( __( '- - Select post type - -', 'wp-cred' ) );
		$default_empty_action_post_label = esc_attr( __( '- - Select post - -', 'wp-cred' ) );
		$this->get_form_go_to_specific_post_settings( $settings, $default_empty_action_post_type_label, $default_empty_action_post_label, $current_action_post, $form_current_custom_post, $form_post_types );

		//All Page List
		$form_action_pages = $this->get_form_action_pages( $settings );

		$repeating_fields_groups_post_types = array();
		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			do_action( 'toolset_do_m2m_full_init' );
			$repeating_fields_groups_post_types = get_post_types( array( Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP => true ), 'objects' );
		}

		//Enqueue scripts
		$enqueue_scripts_settings = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'form_current_action_post' => $current_action_post,
			'has_current_action_post' => isset( $current_action_post ),
			'form_current_action_post_id' => isset( $current_action_post ) ? esc_attr( $current_action_post->ID ) : null,
			'form_current_action_post_title' => isset( $current_action_post ) ? $current_action_post->post_title : null,
			'default_redirect_custom_post_min_posts_count_for_select2' => 15,
			'default_empty_action_post_type' => $default_empty_action_post_type_label,
			'default_empty_action_post' => $default_empty_action_post_label,
			'default_select2_placeholder' => esc_attr( __( 'Type some characters..', 'wp-cred' ) ),
			'form_type' => 'post',
		);
		$this->enqueue_scripts( $enqueue_scripts_settings );

		//Print Template
		echo CRED_Loader::tpl( 'form-settings-meta-box', array(
			'form' => $form,
			'settings' => $settings,
			'post_types' => CRED_Loader::get( 'MODEL/Fields' )->getPostTypes(),
			'stati' => array(
				'basic' => $this->status_model->get_basic_stati(),
				'native' => $this->status_model->get_native_stati(),
				'custom' => $this->status_model->get_custom_stati(),
			),
			'stati_label' => array(
				'native' => $this->status_model->get_native_stati_group_label(),
				'custom' => $this->status_model->get_custom_stati_group_label(),
			),
			'repeating_fields_groups_post_types' => $repeating_fields_groups_post_types,
			'form_post_types' => $form_post_types,
			'form_current_custom_post' => $form_current_custom_post,
			'default_empty_action_post_type' => $default_empty_action_post_type_label,
			'default_empty_action_post' => $default_empty_action_post_label,
			'form_action_pages' => $form_action_pages,
			'help' => CRED_CRED::$help,
			'help_target' => CRED_CRED::$help_link_target,
		) );
	}
}
