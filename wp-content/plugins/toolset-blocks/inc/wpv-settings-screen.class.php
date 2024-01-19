<?php

// @todo move the sections render to templates when posible

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

class WPV_Settings_Screen {

	const SETTINGS_FLAVOUR_VARIATIONS = 'wpv-flavour-variations';

	private static $instance;

	public static function get_instance() {
		if( null == WPV_Settings_Screen::$instance ) {
			WPV_Settings_Screen::$instance = new WPV_Settings_Screen();
		}
		return WPV_Settings_Screen::$instance;
	}


    private function __construct() {
        add_action( 'init',						array( $this, 'init' ) );
		add_action( 'toolset_menu_admin_enqueue_scripts',	array( $this, 'toolset_menu_admin_enqueue_scripts' ) );
    }

    function init() {

		/**
		* General section
		*/
		// Codemirror options
		add_filter( 'toolset_filter_toolset_register_settings_general_section',				array( $this, 'wpv_codemirror_options' ), 20 );
		add_action( 'wp_ajax_wpv_update_codemirror_status',									array( $this, 'wpv_update_codemirror_status' ) );

		/**
		* Front End Content section
		*/
		add_filter( 'toolset_filter_toolset_register_settings_section',						array( $this, 'register_settings_front_end_content_section' ), 30 );
	    // Hidden custom fields options
		add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section',	array( $this, 'wpv_show_hidden_custom_fields_options' ) );
		add_action( 'wp_ajax_wpv_get_hidden_custom_fields',									array( $this, 'wpv_get_hidden_custom_fields' ) );
		add_action( 'wp_ajax_wpv_set_hidden_custom_fields',									array( $this, 'wpv_set_hidden_custom_fields' ) );
		// spaces in meta query filters
		add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section',	array( $this, 'render_query_filters_options' ) );
		add_action( 'wp_ajax_wpv_update_query_filters_options',								array( $this, 'update_query_filters_options' ) );
		// History management options
		add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section',	array( $this, 'wpv_frontend_history_management_options' ), 20 );
		add_action( 'wp_ajax_wpv_update_pagination_options',								array( $this, 'wpv_update_pagination_options' ) );
		// Custom inner shortcodes options
		add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section',	array( $this, 'wpv_custom_inner_shortcodes_options' ), 40 );
		add_action( 'wp_ajax_wpv_update_custom_inner_shortcodes',							array( $this, 'wpv_update_custom_inner_shortcodes' ) );
		// Custom conditional functions options
		add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section',	array( $this, 'wpv_custom_conditional_functions' ), 50 );
		add_action( 'wp_ajax_wpv_update_custom_conditional_functions',						array( $this, 'wpv_update_custom_conditional_functions' ) );
		// Theme support options
		add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section',	array( $this, 'wpv_content_templates_theme_support_options' ), 70 );
		add_action( 'wp_ajax_wpv_update_content_templates_theme_support_settings',			array( $this, 'wpv_update_content_templates_theme_support_settings' ) );
		// Debug options
		add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section',	array( $this, 'wpv_views_debug_options' ), 80 );
        add_action( 'wp_ajax_wpv_update_views_debug_status',								array( $this, 'wpv_update_views_debug_status' ) );
		// Whitelist domains options
		add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section',	array( $this, 'wpv_whitelist_domains' ), 100 );
        add_action( 'wp_ajax_wpv_update_whitelist_domains',									array( $this, 'wpv_update_whitelist_domains' ) );
        add_action( 'wp_ajax_wpv_update_whitelist_subdomains',									array( $this, 'wpv_update_whitelist_subdomains' ) );
		// Page builders options
	    add_filter( 'toolset_filter_toolset_register_settings_front-end-content_section', array( $this, 'views_page_builders_options' ), 110 );

		/**
		* Map section
		*/
		add_filter( 'toolset_filter_toolset_register_settings_section',						array( $this, 'register_settings_maps_section' ), 60 );
		// Legacy maps options
		add_filter( 'toolset_filter_toolset_register_settings_maps_section',				array( $this, 'wpv_map_plugin_options' ), 40 );
		add_action( 'wp_ajax_wpv_update_map_plugin_status',									array( $this, 'wpv_update_map_plugin_status' ) );

		$this->register_editing_experience_options();

        // Register Settings CSS
        wp_register_style( 'views-admin-css', WPV_URL_EMBEDDED . '/res/css/views-admin.css', array(
			'wp-pointer', 'font-awesome',
			'toolset-colorbox', 'toolset-select2-css', 'toolset-select2-overrides-css',
			Toolset_Assets_Manager::STYLE_NOTIFICATIONS,
			'views-admin-dialogs-css'
		), WPV_VERSION );

	    $wpv_ajax = WPV_Ajax::get_instance();

        // Register Settings JS
        wp_register_script( 'views-settings-js', WPV_URL . '/res/js/views_settings.js',		array( 'jquery', 'underscore', 'jquery-ui-dialog', 'jquery-ui-tabs', 'toolset-settings' ), WPV_VERSION, true );
		$settings_script_texts = array(
			'close'								=> __( 'Close', 'wpv-views' ),
			'apply'								=> __( 'Apply', 'wpv-views' ),
			'setting_saved'						=> __( 'Settings saved', 'wpv-views' ),
			'hidde_fields_dialog_title'			=> __( 'Select hidden custom fields to show', 'wpv-views' ),
			'hidden_fields_selected'			=> __( 'The following private custom fields are showing in the Views GUI:', 'wpv-views' ),
			'hidden_fields_unselected'			=> __( 'No private custom fields are showing in the Views GUI.', 'wpv-views' ),
			'hidden_fields_count_zero'			=> __( 'There are no hidden custom fields on your site.', 'wpv-views' ),
			'wpnonce'							=> wp_create_nonce( 'wpv_settings_nonce' )
		);

		$settings_ajax_info = array(
			'ajax' => array(
				'action' => array(
					'save_default_user_editor' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_DEFAULT_USER_EDITOR ),
					'save_default_wpa_editor' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_DEFAULT_WPA_EDITOR ),
					'save_views_page_builders_frontend_content_options' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_SAVE_VIEWS_PAGE_BUILDERS_FRONTEND_CONTENT_SETTINGS ),
					'save_manage_meta_transient_method' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_MANAGE_META_TRANSIENT_METHOD ),
					'save_manage_meta_transient_manual_fire' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_MANAGE_META_TRANSIENT_MANUAL_FIRE ),
					'save_editing_experience' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_EDITING_EXPERIENCE ),
					'save_toolset_theme_settings' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_TOOLSET_THEME_SETTINGS ),
				),
				'nonce' => array(
					'save_default_user_editor' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_DEFAULT_USER_EDITOR ),
					'save_default_wpa_editor' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_DEFAULT_WPA_EDITOR ),
					'save_views_page_builders_frontend_content_options' => wp_create_nonce( WPV_Ajax::CALLBACK_SAVE_VIEWS_PAGE_BUILDERS_FRONTEND_CONTENT_SETTINGS ),
					'save_manage_meta_transient_method' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_MANAGE_META_TRANSIENT_METHOD ),
					'save_manage_meta_transient_manual_fire' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_MANAGE_META_TRANSIENT_MANUAL_FIRE ),
					'save_editing_experience' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_EDITING_EXPERIENCE ),
					'save_toolset_theme_settings' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_TOOLSET_THEME_SETTINGS ),
				),
				'feedback' => array(
					'save_manage_meta_transient_manual_fire' => array(
						'error' => __( 'Something went wrong, please reload the page and try again', 'wpv-views' ),
					),
				),
			),
		);

		wp_localize_script( 'views-settings-js', 'wpv_settings_texts', array_merge( $settings_script_texts, $settings_ajax_info ) );

		/**
		* API filters to get Views settings
		*
		* @todo move this out to a proper API class...
		*/

		add_filter( 'wpv_filter_wpv_codemirror_autoresize',									array( $this, 'wpv_filter_wpv_codemirror_autoresize' ) );
    }

	function toolset_menu_admin_enqueue_scripts( $current_page ) {
		switch ( $current_page ) {
			case 'toolset-settings':
				wp_enqueue_script( 'views-settings-js' );
				break;
		}
		// @todo move the dialogs styles to common and use those classnames instead
		wp_enqueue_style( 'views-admin-css' );
	}

	function register_settings_front_end_content_section( $sections ) {
		$sections['front-end-content'] = array(
			'slug'	=> 'front-end-content',
			'title'	=> __( 'Front-end Content', 'wpv-views' )
		);
		return $sections;
	}

	function register_settings_maps_section( $sections ) {
		// Do not register the legady maps version tab if on Views Lite.
		if ( wpv_is_views_lite() ) {
			return $sections;
		}
		$sections['maps'] = array(
			'slug'	=> 'maps',
			'title'	=> __( 'Maps', 'wpv-views' )
		);
		return $sections;
	}

	/**
	* Codemirror - settings and saving
	*/

	function wpv_codemirror_options( $sections ) {
		$settings = WPV_Settings::get_instance();
		$section_content = '';
		ob_start();
        ?>
		<ul class="">
			<li>
			<h3><?php _e( 'Autoresize', 'wpv-views' ); ?></h3>
				<label>
					<input id="js-wpv-codemirror-autoresize" type="checkbox" name="wpv-codemirror-autoresize" class="js-wpv-codemirror-autoresize" value="1" <?php checked( $settings->wpv_codemirror_autoresize == 1 ); ?> autocomplete="off" />
					<?php _e( "Autoresize the Views editors as their content grows", 'wpv-views' ); ?>
				</label>
			</li>

		</ul>
		<?php
		wp_nonce_field( 'wpv_codemirror_options_nonce', 'wpv_codemirror_options_nonce' );
		?>
        <?php
		$section_content = ob_get_clean();

		$sections['codemirror-settings'] = array(
			'slug'		=> 'codemirror-settings',
			'title'		=> __( 'Text editors options', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
    }

	function wpv_update_codemirror_status() {
		$settings = WPV_Settings::get_instance();
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_codemirror_options_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$autoresize = ( isset( $_POST['autoresize'] ) ) ? sanitize_text_field( $_POST['autoresize'] ) : '';
		$settings->wpv_codemirror_autoresize = ( $autoresize == 'true' ) ? 1 : 0;
		$settings->save();
		wp_send_json_success();
	}

	/**
	 * Setting for selecting the default user editor for Content Templates.
	 *
	 * @param array $sections
	 * @return array
	 * @since 2.8
	 */
	public function default_user_editor_options( $sections ) {
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			// Only offer to select the default user editor on WP 5.0+
			return $sections;
		}

		$settings = WPV_Settings::get_instance();
		$section_content = '';

		// Get all registered user editors, but avoid the native one - it matches the legacy native post editor.
		$registered_user_editors = apply_filters( 'toolset_filter_toolset_registered_user_editors', array() );
		$registered_user_editors['basic'] = __( 'Classic Editor', 'wpv-views' );
		if ( array_key_exists( 'native', $registered_user_editors ) ) {
			unset( $registered_user_editors['native'] );
		}
		ob_start();
        ?>
		<div class="js-wpv-default-user-editor-summary">
			<ul>
			<?php
			foreach ( $registered_user_editors as $registered_user_editor_id => $registered_user_editor_name ) {
				?>
				<li>
					<label>
						<input id="wpv-default-user-editor-<?php echo esc_attr( $registered_user_editor_id ); ?>"
							type="radio"
							autocomplete="off"
							name="wpv-default-user-editor"
							<?php checked( $settings->default_user_editor, $registered_user_editor_id ); ?>
							value="<?php echo esc_attr( $registered_user_editor_id ); ?>" />
						<?php
						echo esc_html( $registered_user_editor_name );
						?>
					</label>
				</li>
				<?php
			}
			?>
			</ul>
		</div>
        <?php
		$section_content = ob_get_clean();

		$sections['default-user-editor-settings'] = array(
			'slug'		=> 'default-user-editor-settings',
			/* translators: Title for the settings section to decide the editor to use with Content Templates. */
			'title'		=> __( 'Editor to use for Content Templates', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
	}

	/**
	 * Setting for selecting the default editor for WordPress Archives.
	 *
	 * @param array $sections
	 * @return array
	 *
	 * @since 3.0
	 */
	public function default_wpa_editor_options( $sections ) {
		if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			// Only offer to select the default WordPress Archive editor on WP 5.0+
			return $sections;
		}

		$settings = WPV_Settings::get_instance();
		$documentation_link_args = array(
			'query'		=> array(
				'utm_source'	=> 'plugin',
				'utm_campaign'	=> 'blocks',
				'utm_medium'	=> 'gui',
				'utm_term'		=> 'Toolset Account'
			)
		);
		$downloads_link = WPV_Admin_Messages::get_documentation_promotional_link( $documentation_link_args, 'https://toolset.com/account/downloads/' );

		$context = array(
			'default_wpa_editor' => $settings->default_wpa_editor,
			'downloads_link' => $downloads_link,
		);
		$template_repository = \WPV_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();
		$section_content = $renderer->render(
			$template_repository->get( \WPV_Output_Template_Repository::VIEWS_SETTINGS_WPA_EDITOR_OPTIONS ),
			$context,
			false
		);

		$sections['default-wpa-editor-settings'] = array(
			'slug'		=> 'default-wpa-editor-settings',
			/* translators: Title for the settings section to decide the editor to use with Content Templates. */
			'title'		=> __( 'Editor to use for WordPress Archives', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
	}

	/**
	 * Setting for disabling the Toolset theme Settings integration
	 *
	 * @param array $sections
	 * @return array
	 *
	 * @since 3.0
	 */
	public function disable_theme_settings_options( $sections ) {
		$settings = WPV_Settings::get_instance();

		$context = array(
			'disable_theme_settings' => $settings->disable_theme_settings,
		);

		$template_repository = \WPV_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();
		$section_content = $renderer->render(
			$template_repository->get( \WPV_Output_Template_Repository::VIEWS_SETTINGS_THEME_SETTINGS_OPTIONS ),
			$context,
			false
		);

		$sections['theme-settings-settings'] = array(
			'slug'		=> 'theme-settings-settings',
			/* translators: Title for the settings section to decide the editor to use with Content Templates. */
			'title'		=> __( 'Toolset Theme Settings', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
	}

	/**
	* Hidden custom fields - settings, getter and setter
	*/

    function wpv_show_hidden_custom_fields_options( $sections ) {
        $settings = WPV_Settings::get_instance();
        if (
			isset( $settings->wpv_show_hidden_fields )
			&& $settings->wpv_show_hidden_fields != ''
		) {
            $selected_fields = explode( ',', $settings->wpv_show_hidden_fields );
        } else {
            $selected_fields = array();
        }
		$section_content = '';
		ob_start();
        ?>
		<div class="js-wpv-hidden-custom-fields-summary">
		<?php
		if ( sizeof( $selected_fields ) > 0 ) {
			?>
			<p class="js-wpv-hidden-custom-fields-summary-text">
			<?php
			_e( 'The following private custom fields are showing in the Views GUI:', 'wpv-views' );
			?>
			</p>
			<ul class="toolset-taglike-list js-wpv-hidden-custom-fields-selected-list">
				<?php foreach ( $selected_fields as $cf ): ?>
					<li class="js-wpv-hidden-custom-fields-selected-list-item" data-field="<?php echo esc_attr( $cf )?>"><?php echo esc_html( $cf )?></li>
				<?php endforeach; ?>
			</ul>
			<?php
		} else {
			?>
			<p class="js-wpv-hidden-custom-fields-summary-text">
			<?php
			_e( 'No private custom fields are showing in the Views GUI.', 'wpv-views' );
			?>
			</p>
			<?php
		}
		?>
		</div>
		<p>
			<button class="button-secondary js-wpv-select-hidden-custom-fields"><?php _e( 'Select custom fields', 'wpv-views' ); ?></button>
		</p>
		<?php wp_nonce_field( 'wpv_show_hidden_custom_fields_nonce', 'wpv_show_hidden_custom_fields_nonce' ); ?>
        <?php
		$section_content = ob_get_clean();

		$sections['hidden-custom-fields-settings'] = array(
			'slug'		=> 'hidden-custom-fields-settings',
			'title'		=> __( 'Hidden custom fields', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
    }

	function wpv_get_hidden_custom_fields() {
		if (
			! isset( $_GET["wpnonce"] )
			|| ! wp_verify_nonce( $_GET["wpnonce"], 'wpv_show_hidden_custom_fields_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		global $WP_Views;
		$meta_keys = $WP_Views->get_hidden_meta_keys();
		$settings = WPV_Settings::get_instance();
        if (
			isset( $settings->wpv_show_hidden_fields )
			&& $settings->wpv_show_hidden_fields != ''
		) {
            $defaults = explode( ',', $settings->wpv_show_hidden_fields );
        } else {
            $defaults = array();
        }
		$defaults = array_map( 'trim', $defaults );
		$meta_keys_count = count( $meta_keys );
		ob_start();
		?>
		<div class="wpv-dialog">
			<?php
			if ( $meta_keys_count > 0 ) {
			?>
			<ul class="cf-list toolset-mightlong-list js-wpv-hidden-custom-fields-all-list">
				<?php foreach ( $meta_keys as $key => $field ) { ?>
					<?php if ( strpos( $field, '_' ) === 0 ) { ?>
						<li>
							<input type="checkbox" class="js-wpv-hidden-field-item" value="<?php echo esc_attr( $field ); ?>" id="wpv-hidden-field-<?php echo esc_attr( $field ); ?>" <?php checked( in_array( $field, $defaults ) ); ?> />
							<label for="wpv-hidden-field-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $field ); ?></label>
						</li>
					<?php } ?>
				<?php } ?>
			</ul>
			<?php
			} else {
			?>
			<p class="toolset-alert toolset-alert-info">
				<?php _e( 'There are no hidden custom fields on your site.', 'wpv-views' ); ?>
			</p>
			<?php
			}
			?>
		</div>
		<?php
		$content = ob_get_clean();
		$data = array(
			'content'	=> $content,
			'count'		=> $meta_keys_count
		);
		wp_send_json_success( $data );
	}

	function wpv_set_hidden_custom_fields() {
		$settings = WPV_Settings::get_instance();
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_show_hidden_custom_fields_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$selected_fields = isset( $_POST['fields'] ) ? $_POST['fields'] : array();
		$selected_fields = array_map( 'esc_attr', $selected_fields );
		$selected_fields = array_map( 'trim', $selected_fields );
		$selected_fields_string = implode( ',', $selected_fields );
		$settings->wpv_show_hidden_fields = $selected_fields_string;
		ob_start();
		if ( sizeof( $selected_fields ) > 0 ) {
			?>
			<p class="js-wpv-hidden-custom-fields-summary-text">
			<?php
			_e( 'The following private custom fields are showing in the Views GUI:', 'wpv-views' );
			?>
			</p>
			<ul class="toolset-taglike-list js-wpv-hidden-custom-fields-selected-list">
				<?php foreach ( $selected_fields as $cf ): ?>
					<li class="js-wpv-hidden-custom-fields-selected-list-item" data-field="<?php echo esc_attr( $cf )?>"><?php echo esc_html( $cf )?></li>
				<?php endforeach; ?>
			</ul>
			<?php
		} else {
			?>
			<p class="js-wpv-hidden-custom-fields-summary-text">
			<?php
			_e( 'No private custom fields are showing in the Views GUI.', 'wpv-views' );
			?>
			</p>
			<?php
		}
		$content = ob_get_clean();
		$data = array(
			'content'	=> $content
		);
		do_action( 'wpv_action_wpv_delete_transient_meta_keys' );
		$settings->save();
		wp_send_json_success( $data );
	}

	function render_query_filters_options( $sections ) {
		$settings = WPV_Settings::get_instance();
		ob_start();
        ?>
		<h3><?php _e( 'Query filters by meta fields', 'wpv-views' ); ?></h3>
		<div class="toolset-advanced-setting">
			<p>
				<label>
					<input id="js-wpv-support-spaces-in-meta-filters" type="checkbox" name="wpv-support-spaces-in-meta-filters" class="js-wpv-query-filters-options js-wpv-support-spaces-in-meta-filters" value="on" <?php checked( $settings->support_spaces_in_meta_filters ); ?> autocomplete="off" />
					<?php _e( "Support query filters by custom fields that include a space or a dot in their meta key", 'wpv-views' ); ?>
				</label>
			</p>
			<p>
				<?php _e( 'Types fields do not include spaces or dots in their meta key, so it can be disabled if this site only has Views filters by Types fields.', 'wpv-views' ); ?>
			</p>
			<p>
				<?php _e( 'Enabling this option might have a performance penalty.', 'wpv-views' ); ?>
			</p>
		</div>
		<?php
		$section_content = ob_get_clean();

		$sections['wpv-support-spaces-in-meta-filters'] = array(
			'slug'		=> 'wpv-support-spaces-in-meta-filters',
			'title'		=> __( 'Query filters', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
	}

	function update_query_filters_options() {
		$settings = WPV_Settings::get_instance();
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_settings_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$support_spaces_in_meta_filters = ( isset( $_POST['support_spaces_in_meta_filters'] ) ) ? sanitize_text_field( $_POST['support_spaces_in_meta_filters'] ) : '';
		$settings->support_spaces_in_meta_filters = ( $support_spaces_in_meta_filters == 'true' );
		$settings->save();
		wp_send_json_success();
	}

	/**
	* History management - settings and saving
	*/

	function wpv_frontend_history_management_options( $sections ) {
		$settings = WPV_Settings::get_instance();
		ob_start();
        ?>
		<h3><?php _e( 'Browser history management for AJAX pagination', 'wpv-views' ); ?></h3>
		<div class="toolset-advanced-setting">
			<p>
				<label>
					<input id="js-wpv-enable-pagination-manage-history" type="checkbox" name="wpv-enable-pagination-manage-history" class="js-wpv-enable-manage-history" value="on" <?php checked( $settings->wpv_enable_pagination_manage_history == 1 ); ?> autocomplete="off" />
					<?php _e( "Enable history management setttings for manual AJAX pagination", 'wpv-views' ); ?>
				</label>
			</p>
			<p>
				<?php _e( 'When doing manual AJAX pagination on a View or WordPress Archive, you can add each page to the browser history, so it can be reached using the back and forth browser buttons. You can either enable this feature (and control it on each View or WordPress Archive) or disable it globally.', 'wpv-views' ); ?>
			</p>
		</div>
		<h3><?php _e( 'Browser history management for AJAX custom searches', 'wpv-views' ); ?></h3>
		<div class="toolset-advanced-setting">
			<p>
				<label>
					<input id="js-wpv-enable-parametric-search-manage-history" type="checkbox" name="wpv-enable-parametric-search-manage-history" class="js-wpv-enable-manage-history" value="on" <?php checked( $settings->wpv_enable_parametric_search_manage_history == 1 ); ?> autocomplete="off" />
					<?php _e( "Enable history management setttings for AJAX custom search", 'wpv-views' ); ?>
				</label>
			</p>
			<p>
				<?php _e( 'When loading custom search results using AJAX, you can adjust the URL to match the options selected, so it can be used to link to those specific results. You can either enable this feature (and control it on each View or WordPress Archive) or disable it globally.', 'wpv-views' ); ?>
			</p>
		</div>
        <?php
		$section_content = ob_get_clean();

		$sections['wpv-browser-history-settings'] = array(
			'slug'		=> 'wpv-browser-history-settings',
			'title'		=> __( 'Browser history management', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
	}

	function wpv_update_pagination_options() {
		$settings = WPV_Settings::get_instance();
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_settings_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$enable_pagination_history_management = ( isset( $_POST['enable_pagination_history_management'] ) ) ? sanitize_text_field( $_POST['enable_pagination_history_management'] ) : '';
		$settings->wpv_enable_pagination_manage_history = ( $enable_pagination_history_management == 'true' ) ? 1 : 0;
		$enable_parametric_search_history_management = ( isset( $_POST['enable_parametric_search_history_management'] ) ) ? sanitize_text_field( $_POST['enable_parametric_search_history_management'] ) : '';
		$settings->wpv_enable_parametric_search_manage_history = ( $enable_parametric_search_history_management == 'true' ) ? 1 : 0;
		$settings->save();
		wp_send_json_success();
	}


	/**
	* Custom inner shortcodes - settings, saving and deleting
	*/

    function wpv_custom_inner_shortcodes_options( $sections ) {
    	$settings = WPV_Settings::get_instance();
        if ( isset( $settings->wpv_custom_inner_shortcodes ) && $settings->wpv_custom_inner_shortcodes != '' ) {
            $custom_shrt = $settings->wpv_custom_inner_shortcodes;
        } else {
            $custom_shrt = array();
        }
        if ( !is_array( $custom_shrt ) ) {
            $custom_shrt = array();
        }

		ob_start();
        ?>
		<div class="js-wpv-custom-inner-shortcodes-summary">
			<div class="js-wpv-add-item-settings-wrapper">
				<?php
				$custom_inner_api_shortcodes = array();
				$custom_inner_api_shortcodes = apply_filters( 'wpv_custom_inner_shortcodes', $custom_inner_api_shortcodes );
				if ( count( $custom_inner_api_shortcodes ) > 0 ) {
					?>
				<h3><?php _e('Shortcodes registered automatically', 'wpv-views'); ?></h3>
				<ul class="wpv-taglike-list">
					<?php
					sort( $custom_inner_api_shortcodes );

					foreach ( $custom_inner_api_shortcodes as $custom_shortcode ) {
						?>
						<li class="js-<?php echo $custom_shortcode; ?>-api-item">
							<span class="">[<?php echo esc_html( $custom_shortcode ); ?>]</span>
						</li>
						<?php
					}
					?>
				</ul>
					<?php
					}
				?>
				<h3><?php _e('Shortcodes registered manually', 'wpv-views'); ?></h3>
				<p>
					<?php _e( 'List of custom and third-party shortcodes you want to be able to use as Views shortcode arguments.', 'wpv-views' ); ?>
				</p>
				<ul class="wpv-taglike-list js-wpv-add-item-settings-list js-wpv-custom-shortcode-list">
					<?php
					if ( count( $custom_shrt ) > 0 ) {
						sort( $custom_shrt );
						foreach ( $custom_shrt as $custom_shrtcode ) {
							?>
							<li class="js-<?php echo $custom_shrtcode; ?>-item">
								<span class="">[<?php echo esc_html( $custom_shrtcode ); ?>]</span>
								<i class="icon-remove-sign fa fa-times-circle js-wpv-custom-shortcode-delete" data-target="<?php echo esc_attr( $custom_shrtcode ); ?>"></i>
							</li>
							<?php
						}
					}
					?>
				</ul>
				<form class="js-wpv-add-item-settings-form js-wpv-custom-inner-shortcodes-form-add">
					<input type="text" placeholder="<?php _e( 'Shortcode name', 'wpv-views' ); ?>" class="js-wpv-add-item-settings-form-newname js-wpv-custom-inner-shortcode-newname" autocomplete="off" />
					<button class="button button-secondary js-wpv-add-item-settings-form-button js-wpv-custom-inner-shortcodes-add" type="button" disabled><i class="icon-plus fa fa-plus"></i> <?php _e( 'Add', 'wpv-views' ); ?></button>
					<span class="toolset-alert toolset-alert-error hidden js-wpv-cs-error"><?php _e( 'Only letters, numbers, underscores and dashes', 'wpv-views' ); ?></span>
					<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-dup"><?php _e( 'That shortcode already exists', 'wpv-views' ); ?></span>
					<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-ajaxfail"><?php _e( 'An error ocurred', 'wpv-views' ); ?></span>
				</form>
			</div>
			<p>
				<?php _e( 'For example, to support <code>[wpv-post-title id="[my-custom-shortcode]"]</code> add <strong>my-custom-shortcode</strong> as a third-party shortcode argument above.', 'wpv-views' ); ?>
			</p>
			<p>
				<?php
				$documentation_link_args = array(
					'query'		=> array(
						'utm_source'	=> 'viewsplugin',
						'utm_campaign'	=> 'views',
						'utm_medium'	=> 'toolset-settings',
						'utm_term'		=> 'documentation page'
					)
				);
				echo sprintf(
					__( 'Get more details in the <a href="%1$s" title="%2$s">documentation page</a>.', 'wpv-views' ),
					WPV_Admin_Messages::get_documentation_promotional_link( $documentation_link_args, 'https://toolset.com/documentation/user-guides/shortcodes-within-shortcodes/' ),
					esc_attr( __( 'Documentation on the third-party shortcode arguments', 'wpv-views' ) )
				);
				?>
			</p>
			<?php wp_nonce_field( 'wpv_custom_inner_shortcodes_nonce', 'wpv_custom_inner_shortcodes_nonce' ); ?>
		</div>
        <?php
		$section_content = ob_get_clean();

		$sections['custom-inner-shortcodes-settings'] = array(
			'slug'		=> 'custom-inner-shortcodes-settings',
			'title'		=> __( 'Third-party shortcode arguments', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
    }

	function wpv_update_custom_inner_shortcodes() {
    	$settings = WPV_Settings::get_instance();
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! (
				wp_verify_nonce( $_POST["wpnonce"], 'wpv_custom_inner_shortcodes_nonce' )
				|| wp_verify_nonce( $_POST['wpnonce'], 'wpv_custom_conditional_extra_settings' )
			)
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			isset( $settings->wpv_custom_inner_shortcodes )
			&& is_array( $settings->wpv_custom_inner_shortcodes )
		) {
			$shortcodes = $settings->wpv_custom_inner_shortcodes;
		} else {
			$shortcodes = array();
		}
		if ( isset( $_POST['csaction'] ) && isset( $_POST['cstarget'] ) ) {
			switch ( $_POST['csaction'] ) {
				case 'add':
					// Shortcode names: http://codex.wordpress.org/Shortcode_API#Names
					if ( ! in_array( $_POST['cstarget'], $shortcodes ) && preg_match( '#^[^ \t\r\n\x00\x20<>&\'"\[\]/]+$#', $_POST['cstarget'] ) ) {
						$shortcodes[] = $_POST['cstarget'];
					}
					break;
				case 'delete':
					$key = array_search( $_POST['cstarget'], $shortcodes );
					if ( $key !== false ) {
						unset( $shortcodes[$key] );
					}
					break;
			}
			$settings->wpv_custom_inner_shortcodes = array_values( $shortcodes );
			$settings->save();
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
    }

	/**
	* Custom conditional functions - settings, saving and deleting
	*/

    function wpv_custom_conditional_functions( $sections ) {
    	$settings = WPV_Settings::get_instance();
        if ( isset( $settings->wpv_custom_conditional_functions ) && $settings->wpv_custom_conditional_functions != '' ) {
            $custom_func = $settings->wpv_custom_conditional_functions;
        } else {
            $custom_func = array();
        }
        if ( !is_array( $custom_func ) ) {
            $custom_func = array();
        }
		ob_start();
        ?>
		<div class="js-wpv-custom-conditional-functions-summary">
			<div class="js-wpv-add-item-settings-wrapper">
			<?php
				$custom_inner_api_functions = array();
				$custom_inner_api_functions = apply_filters( 'wpv_filter_wpv_custom_conditional_functions', $custom_inner_api_functions );
				if ( count( $custom_inner_api_functions ) > 0 ) {
					?>
				<h3><?php _e( 'Functions registered automatically', 'wpv-views' ); ?></h3>
				<ul class="wpv-taglike-list">
					<?php
					sort( $custom_inner_api_functions );

					foreach ( $custom_inner_api_functions as $custom_function ) {
						?>
						<li class="js-<?php echo esc_attr( str_replace( '::', '-_paamayim_-', $custom_function ) ); ?>-api-item">
							<span class=""><?php echo esc_html( $custom_function ); ?></span>
						</li>
						<?php
					}
					?>
				</ul>
					<?php
					}
				?>
				<h3><?php _e( 'Functions registered manually', 'wpv-views' ); ?></h3>
				<p>
					<?php _e( 'List of functions and class methods that you want to be able to use inside the Views <code>[wpv-conditional]</code> shortcode <code>if</code> attribute.', 'wpv-views' ); ?>
				</p>
				<ul class="wpv-taglike-list js-wpv-add-item-settings-list js-wpv-custom-functions-list">
					<?php
					if ( count( $custom_func ) > 0 ) {
						sort( $custom_func );
						foreach ( $custom_func as $custom_function ) {
							?>
							<li class="js-<?php echo esc_attr( str_replace( '::', '-_paamayim_-', $custom_function ) ); ?>-item">
								<span class=""><?php echo esc_html( $custom_function ); ?></span>
								<i class="icon-remove-sign fa fa-times-circle js-wpv-custom-function-delete" data-target="<?php echo str_replace( '::', '-_paamayim_-', $custom_function ); ?>"></i>
							</li>
							<?php
						}
					}
					?>
				</ul>
				<form class="js-wpv-add-item-settings-form js-wpv-custom-conditional-functions-form-add">
					<input type="text" placeholder="<?php _e( 'Function name', 'wpv-views' ); ?>" class="js-wpv-add-item-settings-form-newname js-wpv-custom-conditional-function-newname" autocomplete="off" />
					<button class="button button-secondary js-wpv-add-item-settings-form-button js-wpv-custom-conditional-function-add" type="button" disabled><i class="icon-plus fa fa-plus"></i> <?php _e( 'Add', 'wpv-views' ); ?></button>
					<span class="toolset-alert toolset-alert-error hidden js-wpv-cs-error"><?php _e( 'Only letters, numbers, underscores and dashes', 'wpv-views' ); ?></span>
					<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-dup"><?php _e( 'That function already exists', 'wpv-views' ); ?></span>
					<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-ajaxfail"><?php _e( 'An error ocurred', 'wpv-views' ); ?></span>
				</form>
			</div>
			<p>
				<?php _e( 'For example, to support <em>my_function()</em> add <strong>my_function</strong> as a function name above. For class methods, use the syntax <strong>Class::method</strong>.', 'wpv-views' ); ?>
			</p>
			<p>
				<?php
				$documentation_link_args = array(
					'query'		=> array(
						'utm_source'	=> 'plugin',
						'utm_campaign'	=> 'views',
						'utm_medium'	=> 'gui',
						'utm_term'		=> 'documentation page'
					),
					'anchor'	=> 'using-custom-functions'
				);
				echo sprintf(
					__( 'Get more details in the <a href="%1$s" title="%2$s">documentation page</a>.', 'wpv-views' ),
					WPV_Admin_Messages::get_documentation_promotional_link( $documentation_link_args, 'https://toolset.com/documentation/user-guides/views/conditional-html-output-in-views/' ),
					esc_attr( __( 'Documentation on functions inside conditional evaluations', 'wpv-views' ) )
				);
				?>
			</p>
			<?php wp_nonce_field( 'wpv_custom_conditional_functions_nonce', 'wpv_custom_conditional_functions_nonce' ); ?>
		</div>
        <?php
		$section_content = ob_get_clean();

		$sections['custom-conditional-functions-settings'] = array(
			'slug'		=> 'custom-conditional-functions-settings',
			'title'		=> __( 'Functions inside conditional evaluations', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
    }

	 function wpv_update_custom_conditional_functions() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! (
				wp_verify_nonce( $_POST["wpnonce"], 'wpv_custom_conditional_functions_nonce' )
				|| wp_verify_nonce( $_POST['wpnonce'], 'wpv_custom_conditional_extra_settings' )
			)
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$settings = WPV_Settings::get_instance();
		if ( isset( $settings->wpv_custom_conditional_functions ) && is_array( $settings->wpv_custom_conditional_functions ) ) {
			$functions = $settings->wpv_custom_conditional_functions;
		} else {
			$functions = array();
		}
		if ( isset( $_POST['csaction'] ) && isset( $_POST['cstarget'] ) ) {
			switch ( $_POST['csaction'] ) {
				case 'add':
					if ( !in_array( $_POST['cstarget'], $functions ) ) {
						$functions[] = $_POST['cstarget'];
					}
					break;
				case 'delete':
					$target = str_replace( '-_paamayim_-', '::', $_POST['cstarget'] );
					$key = array_search( $target, $functions );
					if ( $key !== false ) {
						unset( $functions[$key] );
					}
					break;
			}

			$settings->wpv_custom_conditional_functions = array_values( $functions );
			$settings->save();
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
    }

	/**
	* Content Templates theme support - settings and saving
	*/

	function wpv_content_templates_theme_support_options( $sections ) {
        $settings = WPV_Settings::get_instance();
		/*
		DEPRECATED
		global $WPV_templates;
        $options = $WPV_templates->legacy_view_settings( $options );
		*/
        if ( ! isset( $settings->wpv_theme_function ) ) {
            $settings->wpv_theme_function = '';
        }
        if ( ! isset( $settings->wpv_theme_function_debug ) ) {
            $settings->wpv_theme_function_debug = false;
        }
		ob_start();
        ?>
		<div class="js-wpv-content-templates-theme-support-form">
			<?php
			echo '<p>'
				. sprintf(
					__( 'Content Templates modify the content when called from <a href="%s" target="_blank">the_content</a> function.', 'wpv-views' ),
					'http://codex.wordpress.org/Function_Reference/the_content'
				)
				. WPV_MESSAGE_SPACE_CHAR
				. __( 'Some themes don\'t use this function, but define their own.', 'wpv-views' )
				. WPV_MESSAGE_SPACE_CHAR
				. __( "If Content Templates don't work with your theme then you can enter the name of the function your theme uses here:", 'wpv-views' )
				. '</p>';
			?>
			<input type="text" id="wpv-content-templates-theme-support-function" class="js-wpv-content-templates-theme-support-function" name="wpv-content-templates-theme-support-function" value="<?php echo $settings->wpv_theme_function; ?>" autocomplete="off" />
			<button class="button-secondary js-wpv-content-templates-theme-support-function-save" disabled="disabled"><?php echo esc_html( __( 'Apply', 'wpv-views' ) ); ?></button>
			<p>
				<?php
				echo __( "Don't know the name of your theme function?", 'wpv-views' )
					. WPV_MESSAGE_SPACE_CHAR
					. __( "Enable debugging and go to a page that should display a Content Template and Views will display the call function name.", 'wpv-views' );
				?>
			</p>
			<p>
				<input type="checkbox" id="wpv-content-templates-theme-support-debug" class="js-wpv-content-templates-theme-support-debug" name="wpv-content-templates-theme-support-enable-debug" value="1" <?php checked( $settings->wpv_theme_function_debug ); ?> autocomplete="off" />
				<label for="wpv-content-templates-theme-support-debug"><?php _e( "Enable theme support debugging", 'wpv-views' ); ?></label>
			</p>
			<p>
				<?php
				echo __( 'Note that this method will only work if your theme has a proper dedicated function to display the content.', 'wpv-views' )
					. WPV_MESSAGE_SPACE_CHAR
					. __( 'Views will not accept generic PHP functions or auxiliar WordPress functions like <code>require</code>, <code>require_once</code>, <code>include</code>, <code>include_once</code>, <code>locate_template</code>, <code>load_template</code>, <code>apply_filters</code>, <code>call_user_func_array</code>.', 'wpv-views' );
				?>
			</p>
		</div>
		<?php
		wp_nonce_field( 'wpv_view_templates_theme_support', 'wpv_view_templates_theme_support' );
		?>
        <?php
		$section_content = ob_get_clean();

		$sections['content-templates-theme-support-settings'] = array(
			'slug'		=> 'content-templates-theme-support-settings',
			'title'		=> __( 'Theme support for Content Templates', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
    }

	function wpv_update_content_templates_theme_support_settings() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_templates_theme_support' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$settings = WPV_Settings::get_instance();
		$has_changed = false;
		if ( isset( $_POST['theme_function'] ) ) {
			$theme_function = ( isset( $_POST['theme_function'] ) ) ? sanitize_text_field( $_POST['theme_function'] ) : '';
			$settings->wpv_theme_function = $theme_function;
			$has_changed = true;
		}
		if ( isset( $_POST['theme_debug'] ) ) {
			$theme_function_debug = ( isset( $_POST['theme_debug'] ) ) ? sanitize_text_field( $_POST['theme_debug'] ) : 'false';
			$settings->wpv_theme_function_debug = ( $theme_function_debug == 'true' ) ? true : false;
			$has_changed = true;
		}
		if ( $has_changed ) {
			$settings->save();
		}
		wp_send_json_success();
	}

	/**
	* Views debug - settings and saving
	*/

	function wpv_views_debug_options( $sections ) {
    	$settings = WPV_Settings::get_instance();
		ob_start();
        ?>
		<p>
			<?php _e( "Enabling Views debug will open a popup on every page showing a Views element.", 'wpv-views' ); ?>

		</p>
		<p>
			<?php _e( 'This popup will show usefull information about the elements being displayed: time needed to render, memory used, shortcodes details...', 'wpv-views' ); ?>
		</p>
		<p>
			<?php _e( 'There are two modes: compact and full. Compact mode will give you an overview of the elements rendered. The full mode will display a complete report with all the object involved on the page.', 'wpv-views' ); ?>
		</p>
		<p>
			<?php
			$documentation_link_args = array(
				'query'		=> array(
					'utm_source'	=> 'plugin',
					'utm_campaign'	=> 'views',
					'utm_medium'	=> 'gui',
				)
			);
			echo sprintf(
				__( 'Get more details in the <a href="%1$s" title="%2$s">documentation page</a>.', 'wpv-views' ),
				WPV_Admin_Messages::get_documentation_promotional_link( $documentation_link_args, 'https://toolset.com/documentation/programmer-reference/debugging-sites-built-with-toolset/' ),
				esc_attr( __( 'Documentation on the Views debug modes', 'wpv-views' ) )
			);
			?>
		</p>
		<p>
			<label>
				<input type="checkbox" name="wpv-debug-mode" class="js-wpv-debug-mode" value="1" <?php checked( $settings->wpv_debug_mode ); ?> autocomplete="off" />
				<?php _e( "Enable Views debug mode", 'wpv-views' ); ?>
			</label>
		</p>
			<div class="toolset-advanced-setting js-wpv-views-debug-additional-options<?php echo empty( $settings->wpv_debug_mode ) ? ' hidden' : ''; ?>">
				<ul>
					<li><label>
							<input type="radio" name="wpv_debug_mode_type" class="js-wpv-debug-mode-type" value="compact" <?php checked( $settings->wpv_debug_mode_type == 'compact' ); ?> autocomplete="off" />
							<?php _e( "Compact debug mode", 'wpv-views' ); ?>
						</label>
					</li>
					<li>
						<label>
							<input type="radio" name="wpv_debug_mode_type" class="js-wpv-debug-mode-type" value="full" <?php checked( $settings->wpv_debug_mode_type == 'full' ); ?> autocomplete="off" />
							<?php _e( "Full debug mode", 'wpv-views' ); ?>
						</label>
					</li>
				</ul>
				<p>
				<?php
				echo __( 'Views debugger will need to open a popup window, but your browser may block it.', 'wpv-views' )
					. WPV_MESSAGE_SPACE_CHAR
					. __( 'Please refer to the following links for documentation related to the most used browsers:' )
					. WPV_MESSAGE_SPACE_CHAR
				?>
					<a href="http://mzl.la/MyNqBe" target="_blank">Mozilla Firefox</a> &bull;
					<a href="http://windows.microsoft.com/en-us/internet-explorer/ie-security-privacy-settings" target="_blank">Internet Explorer</a> &bull;
					<a href="https://support.google.com/chrome/answer/95472" target="_blank">Google Chrome</a> &bull;
					<a href="http://www.opera.com/help/tutorials/personalize/content/#siteprefs" target="_blank">Opera</a>
				</p>
			</div><!-- close .js-wpv-views-debug-additional-options -->

		<?php
		wp_nonce_field( 'wpv_views_debug_nonce', 'wpv_views_debug_nonce' );
		?>
        <?php
		$section_content = ob_get_clean();

		$sections['views-debug-settings'] = array(
			'slug'		=> 'views-debug-settings',
			'title'		=> __( 'Debug mode', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
    }

	function wpv_update_views_debug_status() {
		$settings = WPV_Settings::get_instance();
		$settings_defaults = $settings->get_defaults();
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_views_debug_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}

        $status = ( isset( $_POST['debug_status'] ) && in_array( $_POST['debug_status'], array( '0', '1' ) ) ) ? (int) $_POST['debug_status'] : null;
		$mode_type = ( isset( $_POST['debug_mode_type'] ) && in_array( $_POST['debug_mode_type'], array( 'compact', 'full' ) ) ) ? $_POST['debug_mode_type'] : $settings_defaults[ WPV_Settings::DEBUG_MODE_TYPE ];

		if ( ! is_null( $status ) ) {
			$settings->wpv_debug_mode = $status;
			if ( ! is_null ( $mode_type ) ) {
				$settings->wpv_debug_mode_type = $mode_type;
			}
			$settings->save();
			wp_send_json_success();
		} else {
			$data = array(
				'type' => 'data',
				'message' => __( 'Wrong data.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
    }

	/**
	 * Whitelist Domains - settings, saving and deleting
	 *
	 * @since 2.3
	 */

	function wpv_whitelist_domains( $sections ) {
		$settings = WPV_Settings::get_instance();
		if ( isset( $settings->wpv_whitelist_domains ) && $settings->wpv_whitelist_domains != '' ) {
			$whitelisted = $settings->wpv_whitelist_domains;
		} else {
			$whitelisted = array();
		}
		if ( !is_array( $whitelisted ) ) {
			$whitelisted = array();
		}
		ob_start();
		?>
			<p>
				<?php _e( 'List of subdomains or external domains you want to allow safe redirection to. These are used with some Views shortcodes, offering a redirection URL, via an attribute.', 'wpv-views' ); ?>
			</p>
			<div class="js-wpv-add-item-settings-wrapper">
				<ul class="wpv-taglike-list js-wpv-add-item-settings-list js-wpv-whitelist-domains-list">
					<?php
					if ( count( $whitelisted ) > 0 ) {
						sort( $whitelisted );
						foreach ( $whitelisted as $domain ) {
							?>
							<li class="js-<?php echo str_replace( '.', '-', str_replace( '*', '-', str_replace( ':', '-', $domain ) ) ); ?>-item">
								<span class=""><?php echo $domain; ?></span>
								<i class="icon-remove-sign fa fa-times-circle js-wpv-whitelist-domains-delete" data-target="<?php echo str_replace( '.', '-', $domain ); ?>"></i>
							</li>
							<?php
						}
					}
					?>
				</ul>
				<form class="js-wpv-add-item-settings-form js-wpv-whitelist-domains-form-add">
					<input type="text" placeholder="<?php _e( 'Domain', 'wpv-views' ); ?>" class="js-wpv-add-item-settings-form-newname js-wpv-whitelist-domains-newname" autocomplete="off" />
					<button class="button button-secondary js-wpv-add-item-settings-form-button js-wpv-whitelist-domains-add" type="button" disabled><i class="icon-plus fa fa-plus"></i> <?php _e( 'Add', 'wpv-views' ); ?></button>
					<span class="toolset-alert toolset-alert-error hidden js-wpv-cs-error"><?php _e( 'Only letters, numbers, dots, underscores and dashes', 'wpv-views' ); ?></span>
					<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-dup"><?php _e( 'That domain already exists', 'wpv-views' ); ?></span>
					<span class="toolset-alert toolset-alert-info hidden js-wpv-cs-ajaxfail"><?php _e( 'An error ocurred', 'wpv-views' ); ?></span>
				</form>
			</div>
			<p>
				<?php _e( 'Do not prepend domain name with protocol identifier, such as <code>http</code> or <code>https</code>. Only domain name should work as a good qualifier. For example, <code>example.com</code> or <code>www.example.com</code> is an acceptable entry.', 'wpv-views' ); ?>
			</p>
			<p>
				<?php

				$current_site = parse_url(get_site_url());
				$current_domain = $current_site['host'];

				echo sprintf(
				__( 'To allow a specific subdomain, use <code>subdomain.example.com</code>. To allow all possible subdomains, use <code>*.example.com</code>.', 'wpv-views' ));
				?>
			</p>
			<p>
				<?php
				$documentation_link_args = array(
					'query'		=> array(
						'utm_source'	=> 'viewsplugin',
						'utm_campaign'	=> 'views',
						'utm_medium'	=> 'toolset-settings',
						'utm_term'		=> 'documentation page'
					),
					'anchor'	=> 'whitelist-domains'
				);
				?>
			</p>

		<?php wp_nonce_field( 'wpv_whitelist_domains_nonce', 'wpv_whitelist_domains_nonce' ); ?>
		<?php wp_nonce_field( 'wpv_whitelist_subdomains_nonce', 'wpv_whitelist_subdomains_nonce' ); ?>
		<?php
		$section_content = ob_get_clean();

		$sections['whitelist-domains-settings'] = array(
			'slug'		=> 'whitelist-domains-settings',
			'title'		=> __( 'Safe redirects', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
	}

	/**
	 * @since 2.3
	 */
	function wpv_update_whitelist_domains() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! (
				wp_verify_nonce( $_POST["wpnonce"], 'wpv_whitelist_domains_nonce' )
				|| wp_verify_nonce( $_POST['wpnonce'], 'wpv_whitelist_domains_extra_settings' )
			)
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$settings = WPV_Settings::get_instance();
		if ( isset( $settings->wpv_whitelist_domains ) && is_array( $settings->wpv_whitelist_domains ) ) {
			$domains = $settings->wpv_whitelist_domains;
		} else {
			$domains = array();
		}
		if ( isset( $_POST['csaction'] ) && isset( $_POST['cstarget'] ) ) {
			switch ( $_POST['csaction'] ) {
				case 'add':
					if ( !in_array( $_POST['cstarget'], $domains ) ) {
						$domains[] = $_POST['cstarget'];
					}
					break;
				case 'delete':
					$target = str_replace( '-', '.', $_POST['cstarget'] );
					$key = array_search( $target, $domains );
					if ( $key !== false ) {
						unset( $domains[$key] );
					}
					break;
			}

			$settings->wpv_whitelist_domains = $domains;
			$settings->save();
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * @since 2.3
	 */
	function wpv_update_whitelist_subdomains() {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! (
				wp_verify_nonce( $_POST["wpnonce"], 'wpv_whitelist_subdomains_nonce' )
				|| wp_verify_nonce( $_POST['wpnonce'], 'wpv_whitelist_subdomains_extra_settings' )
			)
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$settings = WPV_Settings::get_instance();
		$whitelist_subdomains = ( isset( $_POST['whitelist_subdomains'] ) ) ? sanitize_text_field( $_POST['whitelist_subdomains'] ) : '';
		$settings->wpv_whitelist_subdomains = ( $whitelist_subdomains == 'true' ) ? 1 : 0;
		$settings->save();
		wp_send_json_success();
	}

	/**
	* Maps (legacy) - settings and saving
	*/

	function wpv_map_plugin_options( $sections ) {
    	$settings				= WPV_Settings::get_instance();
		$toolset_maps_installed	= apply_filters( 'toolset_is_maps_available', false );
		ob_start();
		if ( $toolset_maps_installed ) {
			?>
		<p>
			<?php
			echo __( "You can enable the legacy Views Maps plugin if you already use in your site.", 'wpv-views' );
			?>
		</p>
			<?php
		} else {
			?>
		<p>
			<?php
			echo __( "Enabling the legacy Views Maps plugin will add the Google Maps API and the Views Maps plugin to your site.", 'wpv-views' )
				. WPV_MESSAGE_SPACE_CHAR
				. __( 'This will let you create maps on your site and use Views to plot WordPress posts on a Google Map.', 'wpv-views' );
			?>
		</p>
		<p>
			<?php
			echo __( 'Please consider updating to the new Toolset Maps plugin for extended features and better compatibility.', 'wpv-views' );
			?>
		</p>
			<?php
		}
        ?>
		<p>
			<?php
			$documentation_link_args = array(
				'query'		=> array(
					'utm_source'	=> 'plugin',
					'utm_campaign'	=> 'blocks',
					'utm_medium'	=> 'gui',
				)
			);
			echo sprintf(
				__( 'Get more details about the new Toolset Maps plugin in the <a href="%1$s" title="%2$s" target="_blank">documentation page</a>.', 'wpv-views' ),
				WPV_Admin_Messages::get_documentation_promotional_link( $documentation_link_args, 'https://toolset.com/course-lesson/displaying-a-list-of-posts-on-a-map/' ),
				esc_attr( __( 'Documentation on the Toolset Maps plugin', 'wpv-views' ) )
			);
			?>
		</p>
		<div class="js-map-plugin-form">
			<p>
				<label>
					<input type="checkbox" name="wpv-map-plugin" class="js-wpv-map-plugin" value="1" <?php checked( $settings->wpv_map_plugin ); ?> autocomplete="off" />
					<?php echo __( "Enable the legacy Views Map plugin", 'wpv-views' ); ?>
				</label>
			</p>

		</div>
		<?php
		wp_nonce_field( 'wpv_map_plugin_nonce', 'wpv_map_plugin_nonce' );
		?>
        <?php
		$section_content = ob_get_clean();

		$sections['maps-legacy'] = array(
			'slug'		=> 'maps-legacy',
			'title'		=> __( 'Map plugin (legacy)', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
    }

	function wpv_update_map_plugin_status() {
    	$settings = WPV_Settings::get_instance();
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_map_plugin_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$status = ( isset( $_POST['status'] ) ) ? sanitize_text_field( $_POST['status'] ) : 'true';
		$settings->wpv_map_plugin = ( $status == 'true' ) ? 1 : 0;
		$settings->save();
		wp_send_json_success();
    }

	private function register_editing_experience_options() {
		// Editing experience
		add_filter( 'toolset_filter_toolset_register_settings_general_section',	array( $this, 'flavour_variations_options' ), 9990 );
		// Default user editor
		add_filter( 'toolset_filter_toolset_register_settings_general_section',	array( $this, 'default_user_editor_options' ), 9991 );
		// Default editor for WordPress Archives
		add_filter( 'toolset_filter_toolset_register_settings_general_section',	array( $this, 'default_wpa_editor_options' ), 9992 );
		// Disable Toolset Theme Settings
		add_filter( 'toolset_filter_toolset_register_settings_general_section',	array( $this, 'disable_theme_settings_options' ), 11999 );
	}

	public function flavour_variations_options( $sections ) {
		$settings = WPV_Settings::get_instance();
		ob_start();
		?>
		<h3><?php echo esc_html( __( 'Interface', 'wpv-views' ) ); ?></h3>
		<div class="toolset-advanced-setting">
			<ul>
				<li><label>
						<input type="radio" name="wpv-editing-experience" class="js-wpv-editing-experience-option" value="classic" <?php checked( $settings->editing_experience === 'classic' ); ?> autocomplete="off" />
						<?php echo esc_html( __( 'Show only the legacy interface for designing with shortcodes', 'wpv-views' ) ); ?>
					</label>
				</li>
				<li>
					<label>
						<input type="radio" name="wpv-editing-experience" class="js-wpv-editing-experience-option" value="blocks" <?php checked( $settings->editing_experience === 'blocks' ); ?> autocomplete="off" />
						<?php echo esc_html( __( 'Show only the Blocks interface', 'wpv-views' ) ); ?>
					</label>
				</li>
				<li>
					<label>
						<input type="radio" name="wpv-editing-experience" class="js-wpv-editing-experience-option" value="mixed" <?php checked( $settings->editing_experience === 'mixed' ); ?> autocomplete="off" />
						<?php echo esc_html( __( 'Show both the legacy and Blocks interface and let me choose which to use for each item I build', 'wpv-views' ) ); ?>
					</label>
				</li>
			</ul>
		</div>
		<?php
		$section_content = ob_get_clean();

		$sections[ self::SETTINGS_FLAVOUR_VARIATIONS ] = array(
			'slug'		=> self::SETTINGS_FLAVOUR_VARIATIONS,
			'title'		=> __( 'Editing experience', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
	}

    /**
	 * API filters to get some Views settings data
	 */
	public function wpv_filter_wpv_codemirror_autoresize( $status ) {
		$settings = WPV_Settings::get_instance();
		if ( $settings->wpv_codemirror_autoresize ) {
			$status = true;
		} else {
			$status = false;
		}
		return $status;
	}

	/**
	 * Page Builder related settings
	 */
	public function views_page_builders_options( $sections ) {
		$show_views_page_builders_settings = new Toolset_Condition_Plugin_Views_Show_Page_Builder_Frontend_Content_Settings();

		if ( ! $show_views_page_builders_settings->is_met() ) {
			return $sections;
		}

		$settings = WPV_Settings::get_instance();

		$context = array(
			'allow_views_wp_widgets_in_elementor' => $settings->allow_views_wp_widgets_in_elementor
		);

		$template_repository = WPV_Output_Template_Repository::get_instance();
		$renderer = Toolset_Renderer::get_instance();
		$section_content = $renderer->render(
			$template_repository->get( WPV_Output_Template_Repository::VIEWS_SETTINGS_PAGE_BUILDER_OPTIONS ),
			$context,
			false
		);

		$sections['page-builders-settings'] = array(
			'slug'		=> 'page-builders-settings',
			'title'		=> __( 'Page Builders', 'wpv-views' ),
			'content'	=> $section_content
		);
		return $sections;
	}
}

// Initialize the Settings screen
// @todo do this only when absolutely necessary
WPV_Settings_Screen::get_instance();
