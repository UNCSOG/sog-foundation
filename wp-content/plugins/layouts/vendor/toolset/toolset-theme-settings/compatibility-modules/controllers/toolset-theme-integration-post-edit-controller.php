<?php

class Toolset_Theme_Integration_Settings_Post_Edit_Controller extends Toolset_Theme_Integration_Settings_Admin_Controller {

	const MESSAGE_SPACE = '&nbsp;';
	const USE_THEME_SETTING = 'toolset_use_theme_setting';

	public function __construct( Toolset_Theme_Integration_Settings_Helper $helper = null, $post_id = null ){
		if( !$helper->has_theme_settings() ){
			$helper->load_current_settings_object( $post_id );
		}
		parent::__construct( $helper, $post_id );
	}

	public function init(){
		parent::init();
	}

	public function admin_init(){
		parent::admin_init();

		add_action('current_screen', array($this, 'display_local_options_warning'));
	}

	public function display_local_options_warning() {
		if ( empty( $this->object_id ) ) {
			return;
		}

		// Only show the admin notice after updating the post
		if ( ! isset( $_GET['message'] ) ) {
			return;
		}

		$allowed_targets = $this->allowed_targets;
		$local_collections = $this->collections->get_collection_by_type( $allowed_targets['local'] );

		if ( empty( $local_collections ) ) {
			return;
		}

		$forced_settings = array();

		foreach ( $local_collections->getIterator() as $key => $local_model ) {
			if ( 'text' === $local_model->get_gui_type() ) {
				if (
					NULL === $local_model->get_current_switch_value()
					|| self::USE_THEME_SETTING === $local_model->get_current_switch_value()
				) {
					continue;
				}
			} elseif (
				NULL === $local_model->get_current_value()
				|| self::USE_THEME_SETTING === $local_model->get_current_value()
			) {
				continue;
			}
			$forced_settings[] = $local_model->get_referenced_label();
		}

		if ( empty( $forced_settings ) ) {
			return;
		}

		$resource_title = get_the_title($this->object_id);

		if ( $this->current_object_type == 'dd_layouts' ) {
			$resource_edit_link = admin_url( "admin.php?page=dd_layouts_edit&layout_id={$this->object_id}&action=edit" );
			$resource_type = __( 'layout', 'wpv-views' );
		} else {
			$resource_edit_link = admin_url( "admin.php?page=ct-editor&ct_id={$this->object_id}" );
			$resource_type = __( 'template', 'wpv-views' );
		}

		$notice = new Toolset_Admin_Notice_Warning( 'toolset-theme-post-meta-warning' );

		$notice_content = sprintf(
			__( 'You are using the %1$s %2$s to design this page, so the following theme settings will be inherited from the %3$s: %4$s.', 'wpv-views' ),
			'<a href="' . $resource_edit_link . '" target="_blank">' . $resource_title . '</a>',
			$resource_type,
			$resource_type,
			'<em>' . implode( '</em>, <em>', $forced_settings ) . '</em>'
		);

		Toolset_Admin_Notices_Manager::add_notice( $notice, $notice_content);
	}
}
