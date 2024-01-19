<?php

namespace OTGS\Toolset\CRED\Controller\FormEditorToolbar;

use OTGS\Toolset\CRED\Controller\FormEditorToolbar\Button;
use OTGS\Toolset\CRED\Model\Field\Generic\Gui as GenericGui;

/**
 * Abstract class to extend for each of the editor toolbars.
 *
 * This one is responsible of printing toolbar button and including the assets to make them work.
 *
 * @since 2.1
 */
abstract class Base {

	/**
	 * Main toolbar script handle and relative path templates.
	 */
	const JS_TOOLBAR_PROTOTYPE = 'cred_editor_toolbar_prototype';
	const JS_TOOLBAR_HANDLE = 'toolset_cred_%s_form_%s_editor_toolbar_js';
	const JS_TOOLBAR_REL_PATH = '/public/form_editor_toolbar/js/%s_form_%s.js';
	const JS_TOOLBAR_I18N_NAME = 'cred_%s_form_%s_editor_toolbar_i18n';

	const CSS_TOOLBAR = 'cred_editor_toolbar';

	/**
	 * Editor domain: post|user|association
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
	protected $editor_domain;

	/**
	 * Toolbar target: ID of the editor it is attached to.
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
	protected $editor_target;

	/**
	 * Toolbar required scripts.
	 *
	 * @var array
	 *
	 * @since 2.1
	 */
	protected $scripts = array();

	/**
	 * Toolbar required styles.
	 *
	 * @var array
	 *
	 * @since 2.1
	 */
	protected $styles = array();

	/**
	 * Toolset_Assets_Manager instance.
	 *
	 * @var \Toolset_Assets_Manager
	 *
	 * @since 2.1
	 */
	public $assets_manager;

	/**
	 * Main toolbar script handle.
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
	protected $js_toolbar_handle;

	/**
	 * Main toolbar script relative path.
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
	protected $js_toolbar_relpath;

	/**
	 * Main toolbar script localization object name.
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
	protected $js_toolbar_i18n_name;

	public function initialize() {

		$this->assets_manager = \Toolset_Assets_Manager::get_instance();

		$this->js_toolbar_handle = sprintf( self::JS_TOOLBAR_HANDLE, $this->editor_domain, $this->editor_target );
		$this->js_toolbar_relpath = sprintf( self::JS_TOOLBAR_REL_PATH, $this->editor_domain, $this->editor_target );
		$this->js_toolbar_i18n_name = sprintf( self::JS_TOOLBAR_I18N_NAME, $this->editor_domain, $this->editor_target );

		$this->add_hooks();
		$this->init_assets();
		$this->load_assets();
	}

	/**
	 * Register the required hooks.
	 * - Print toolbar buttons.
	 * - Load toolbar assets.
	 * - Print toolbar templates.
	 *
	 * @since 2.1
	 */
	public function add_hooks() {
		add_action( 'cred_content_editor_print_toolbar_buttons', array( $this, 'print_toolbar_buttons' ) );
		add_action( 'cred_notification_subject_editor_print_toolbar_buttons', array( $this, 'print_notification_subject_toolbar_buttons' ) );
		add_action( 'cred_notification_body_editor_print_toolbar_buttons', array( $this, 'print_notification_body_toolbar_buttons' ) );
		add_action( 'cred_settings_action_message_editor_print_toolbar_buttons', array( $this, 'print_action_message_toolbar_buttons' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets') );
		add_action( 'admin_footer', array( $this, 'print_templates' ) );
	}

	/**
	 * Print the toolbar buttons for the main editor.
	 * Each subclass will print their own based on their domain.
	 *
	 * @since 2.1
	 */
	abstract public function print_toolbar_buttons();

	/**
	 * Print the toolbar buttons for the notification subject input.
	 * Each subclass will print their own based on their domain.
	 *
	 * @param string $editor_id
	 *
	 * @since 2.1
	 */
	abstract public function print_notification_subject_toolbar_buttons( $editor_id );

	/**
	 * Print the toolbar buttons for the notification body editor.
	 * Each subclass will print their own based on their domain.
	 *
	 * @param string $editor_id
	 *
	 * @since 2.1
	 */
	abstract public function print_notification_body_toolbar_buttons( $editor_id );

	/**
	 * Print the toolbar buttons for the message after submitting the form.
	 * Each subclass will print their own based on their domain.
	 *
	 * @param string $editor_id
	 *
	 * @since 2.1
	 */
	abstract public function print_action_message_toolbar_buttons( $editor_id );

	/**
	 * Get the localization data for the toolbar main script.
	 *
	 * @since 2.1
	 */
	abstract protected function get_script_localization();

	/**
	 * Print a single toolbar button.
	 *
	 * @param array $args
	 *
	 * @uses OTGS\Toolset\CRED\Controller\FormEditorToolbar\Button
	 * @since 2.1
	 */
	protected function print_button( $args = array() ) {
		$defaults = array(
			'editor_domain' => $this->editor_domain,
			'editor_target' => $this->editor_target,
			'slug' => '',
			'label' => '',
			'icon' => ''
		);
		$args = wp_parse_args( $args, $defaults );

		$button = new Button( $args );
		$button->render();
	}

	/**
	 * Print the shared buttons for all form editors: scaffold and fields.
	 *
	 * @since 2.1
	 */
	protected function print_default_buttons() {
		$this->print_button(
			array(
				'slug' => 'fields',
				'label' => __( 'Add fields', 'wp-cred' ),
				'icon' => '<span class="dashicons dashicons-forms" style="vertical-align:text-top"></span>'
			)
		);
	}

	/**
	 * Print the buttons to insert generic fields and conditional groups.
	 *
	 * @since 2.1
	 */
	protected function print_generic_and_conditional_buttons() {
		$this->print_button(
			array(
				'slug' => 'generic-fields',
				'label' => __( 'Add generic fields', 'wp-cred' ),
				'icon' => '<i class="fa fa-lg fa-paperclip" aria-hidden="true"></i>'
			)
		);
		$this->print_button(
			array(
				'slug' => 'conditional-groups',
				'label' => __( 'Add conditional groups', 'wp-cred' ),
				'icon' => '<span class="dashicons dashicons-visibility" style="vertical-align:text-top"></span>'
			)
		);
	}

	/**
	 * Print third party buttons, including Toolset buttons.
	 *
	 * @since 2.2.1
	 */
	protected function print_third_party_buttons() {
		do_action(
			'wpv_action_wpv_generate_fields_and_views_button',
			$this->editor_target,
			array( 'output' => 'button' )
		);
	}

	/**
	 * Print the media button.
	 *
	 * @param int $post_id Post to attach media to.
	 * @param string Optional ID for the target editor, defaults to the main editor.
	 *
	 * @since 2.1
	 */
	protected function print_media_button( $post_id, $editor_target = null ) {
		$editor_target = ! $editor_target ? $this->editor_target : $editor_target;
		$this->print_button(
			array(
				'editor_target' => $editor_target,
				'slug' => 'media',
				'label' => __( 'Add Media', 'wp-cred' ),
				'icon' => '<span class="dashicons dashicons-admin-media" style="vertical-align:text-top"></span>',
				'class' => 'js-toolset-editor-media-manager',
				'data' => array(
					'postid' => $post_id
				)
			)
		);
	}

	/**
	 * Register the toolbar assets:
	 * - generic prototype for toolbar management.
	 * - specific script for the current form type.
	 * - generic stylesheet for toolbars.
	 * - dependencies.
	 *
	 * @since 2.1
	 */
	protected function init_assets() {

		$this->assets_manager->register_style(
			self::CSS_TOOLBAR,
			CRED_ABSURL . '/public/form_editor_toolbar/css/editor_toolbar.css',
			array( 'editor-buttons' ),
			CRED_FE_VERSION
		);

		$this->assets_manager->register_script(
			self::JS_TOOLBAR_PROTOTYPE,
			CRED_ABSURL . '/public/form_editor_toolbar/js/prototype.js',
			array(
				'jquery',
				'jquery-ui-dialog',
				'jquery-ui-tabs',
				'jquery-ui-sortable',
				'shortcode',
				'underscore',
				'wp-util',
				\Toolset_Assets_Manager::SCRIPT_TOOLSET_SHORTCODE,
				\Toolset_Assets_Manager::SCRIPT_TOOLSET_MEDIA_MANAGER
			),
			CRED_FE_VERSION
		);

		$this->assets_manager->register_script(
			$this->js_toolbar_handle,
			CRED_ABSURL . $this->js_toolbar_relpath,
			array( self::JS_TOOLBAR_PROTOTYPE ),
			CRED_FE_VERSION
		);

		// Maybe no need to enqueue the media because it is a native post edit page.
		// But as we are removing the native editor, it might be needd, so keep it.
		wp_enqueue_media();

		$this->assets_manager->localize_script(
			$this->js_toolbar_handle,
			$this->js_toolbar_i18n_name,
			$this->get_script_localization()
		);

		$this->scripts[ $this->js_toolbar_handle ] = $this->js_toolbar_handle;

		$this->styles[ \Toolset_Assets_Manager::STYLE_TOOLSET_COMMON ] = \Toolset_Assets_Manager::STYLE_TOOLSET_COMMON;
		$this->styles[ \Toolset_Assets_Manager::STYLE_TOOLSET_DIALOGS_OVERRIDES ] = \Toolset_Assets_Manager::STYLE_TOOLSET_DIALOGS_OVERRIDES;
		$this->styles[ \Toolset_Assets_Manager::STYLE_SELECT2_CSS ] = \Toolset_Assets_Manager::STYLE_SELECT2_CSS;
		$this->styles[ \Toolset_Assets_Manager::STYLE_FONT_AWESOME ] = \Toolset_Assets_Manager::STYLE_FONT_AWESOME;
		$this->styles[ self::CSS_TOOLBAR ] = self::CSS_TOOLBAR;

		do_action( 'otg_action_otg_enforce_styles' );
	}

	/**
	 * Craft a set of shared data to be used in the toolbar script.
	 * This set gets completed by each subclass for its own script.
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	protected function get_shared_script_localization() {

		$generic_fields_gui = new GenericGui();

		return array(
			'action' => array(
				'loading' => __( 'Loading...', 'wp-cred' ),
				'insert' => __( 'Insert', 'wp-cred' ),
				'cancel' => __( 'Cancel', 'wp-cred' ),
				'back' => __( 'Back', 'wp-cred' )
			),
			'dialog' => array(
				'fields' => array(
					'header' => __( 'Add fields to the form', 'wp-cred' )
				),
				'genericFields' => array(
					'header' => __( 'Add generic fields to the form', 'wp-cred' ),
					'fields' => $generic_fields_gui->get_generic_fields()
				),
				'shortcode' => array(
					'header' => __( 'Insert a field', 'wp-cred' ),
					'group' => array(
						'header' => __( 'Options for this field', 'wp-cred' )
					)
				),
				'conditionalGroups' => array(
					'shortcode' => 'cred_show_group',
					'header' => __( 'Insert a conditional group', 'wp-cred' ),
					'warning' => __( 'Your custom conditions will be lost if you switch back to GUI editing.', 'wp-cred' ),
					'edit_manually' => __( 'Edit conditions manually', 'wp-cred' ),
					'edit_gui' => __( 'Edit conditions using the GUI', 'wp-cred' )
				),
				'placeholders' => array(
					'header' => __( 'Insert a placeholder', 'wp-cred' )
				)
			)
		);
	}

	/**
	 * Enqueue the toolbar registered scripts and styles.
	 *
	 * @since 2.1
	 */
	public function load_assets() {
		do_action( 'toolset_enqueue_scripts', $this->scripts );
		do_action( 'toolset_enqueue_styles', $this->styles );
	}

	/**
	 * Print the toolbar templates:
	 * - Fields dialog.
	 * - Generic fields dialog.
	 * - Generic fields options table and row.
	 * - Field item dialog.
	 * - Conditional output dialog.
	 * - Conditional output row.
	 * - Notifications placeholder dialog.
	 * - Notifications placeholder item.
	 *
	 * @since 2.1
	 */
	public function print_templates() {

		do_action( 'toolset_action_require_shortcodes_templates' );

		$template_repository = \CRED_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_FIELDS_DIALOG ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_GENERIC_FIELDS_DIALOG ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_GENERIC_FIELDS_OPTIONS_MANUAL_TABLE ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_GENERIC_FIELDS_OPTIONS_MANUAL_ROW ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_FIELDS_ITEM ),
			null
		);

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_CONDITIONAL_GROUPS_DIALOG ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_CONDITIONAL_GROUPS_ROW ),
			null
		);

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::NOTIFICATION_EDITOR_TOOLBAR_PLACEHOLDERS_DIALOG ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::NOTIFICATION_EDITOR_TOOLBAR_PLACEHOLDERS_ITEM ),
			null
		);

	}

}
