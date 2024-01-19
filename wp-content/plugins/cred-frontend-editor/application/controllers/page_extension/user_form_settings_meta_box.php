<?php

/**
 * Class Responsible to create Form Settings Meta Box
 *
 * @since 1.9.4
 */
class CRED_Page_Extension_User_Form_Settings_Meta_Box extends CRED_Page_Extension_Form_Settings_Meta_Box_Base {

	private static $instance;

	public function __construct() {
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
		global $wp_roles;
		$all_user_roles = $wp_roles->roles;

		//Form Settings
		$settings = $args['args']['form_settings']->form;

		//Get All redirection "go to specific post" settings
		$default_empty_action_post_type_label = esc_attr( __( '- - Select post type - -', 'wp-cred' ) );
		$default_empty_action_post_label = esc_attr( __( '- - Select post - -', 'wp-cred' ) );
		$this->get_form_go_to_specific_post_settings( $settings, $default_empty_action_post_type_label, $default_empty_action_post_label, $current_action_post, $form_current_custom_post, $form_post_types );

		//All Page List
		$form_action_pages = $this->get_form_action_pages( $settings );

		//Selected User Roles
		$selected_user_roles = $this->get_selected_user_roles( $settings );
		
		$form_actions = apply_filters( 'cred_admin_submit_action_options', array(
			"form" => __( 'Keep displaying this form', 'wp-cred' ),
			"message" => __( 'Display a message instead of the form...', 'wp-cred' ),
			"custom_post" => __( 'Go to a specific post...', 'wp-cred' ),
			//"user" => __('Display the user', 'wp-cred'),
			"page" => __( 'Go to a page...', 'wp-cred' ),
		), $settings['action'], $form );

		$fix_settings_action = false;
		if (
			isset( $settings['action'] )
			&& $settings['action'] == 'user'
		) {
			$settings['action'] = 'form';
			$fix_settings_action = true;
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
			'settings_form_type' => $settings['type'],
			'user_roles' => $all_user_roles,
			'selected_user_roles' => $selected_user_roles,
			'form_type' => 'user',
			'fix_settings_action' => $fix_settings_action,
		);
		$this->enqueue_scripts( $enqueue_scripts_settings );

		//Print Template
		echo CRED_Loader::tpl( 'user-form-settings-meta-box', array(
			'form' => $form,
			'form_actions' => $form_actions,
			'settings' => $settings,
			'post_types' => CRED_Loader::get( 'MODEL/Fields' )->getPostTypes(),
			'form_post_types' => $form_post_types,
			'form_current_custom_post' => $form_current_custom_post,
			'default_empty_action_post_type' => $default_empty_action_post_type_label,
			'default_empty_action_post' => $default_empty_action_post_label,
			'form_action_pages' => $form_action_pages,
			'user_roles' => $all_user_roles,
			'help' => CRED_CRED::$help,
			'help_target' => CRED_CRED::$help_link_target,
		) );
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	protected function get_selected_user_roles( $settings ) {
		$selected_user_roles = array();
		if ( isset( $settings['user_role'] ) && ! empty( $settings['user_role'] ) ) {
			$selected_user_roles = json_decode( $settings['user_role'], true );
			if ( is_array( $selected_user_roles ) ) {
				array_filter( $selected_user_roles );
			}
		}
		//fix array indexing
		return array_values($selected_user_roles);
	}
}