<?php

namespace OTGS\Toolset\CRED\Controller\Forms;

use OTGS\Toolset\CRED\Controller\PageExtension\Factory;
use OTGS\Toolset\CRED\Model\Factory as ModelFactory;
use OTGS\Toolset\CRED\Model\Field\Generic\Gui as GenericGui;
use OTGS\Toolset\CRED\Controller\Forms\Main as FormMain;
use OTGS\Toolset\CRED\Model\Settings;

/**
 * Forms main backend controller.
 *
 * @since 2.1
 */
class Backend extends Base {

	const DOMAIN = 'shared';
	const JS_EDITOR_I18N_NAME = 'cred_post_form_content_editor_i18n';

	protected $assets_to_load_js = array();
	protected $assets_to_load_css = array();

	/**
	 * @var OTGS\Toolset\CRED\Controller\PageExtension\Factory
	 *
	 * @since 2.1
	 */
	protected $page_extension_factory = null;

	protected $metaboxes = array();

	/**
	 * Used for testing purposes
	 *
	 * @var \Toolset_Common_Bootstrap
	 */
	private $toolset_common_bootstrap;

	/**
	 * Form container shortcode
	 *
	 * @var string
	 * @since 2.2
	 */
	protected $form_container = '';


	/** @var \Toolset_Settings */
	protected $toolset_settings;


	/**
	 * Constructor
	 *
	 * @param ModelFactory $model_factory Model factory.
	 * @param \Toolset_Common_Bootstrap $toolset_common_bootstrap_di For testing purposes.
	 * @param \Toolset_Settings|null $toolset_settings_di
	 */
	public function __construct(
		ModelFactory $model_factory, \Toolset_Common_Bootstrap $toolset_common_bootstrap_di = null, \Toolset_Settings $toolset_settings_di = null
	) {
		parent::__construct( $model_factory );
		$this->page_extension_factory   = new Factory();
		$this->toolset_common_bootstrap = $toolset_common_bootstrap_di;
		$this->toolset_settings = $toolset_settings_di ?: \Toolset_Settings::get_instance();
	}

	/**
	 * Initialize backend.
	 *
	 * @since 2.1
	 */
	public function initialize() {
		parent::initialize();
		if ( $this->is_edit_page() ) {
			// Disable the Toolset Views conditional output quicktag from editors.
			add_filter( 'wpv_filter_wpv_disable_conditional_output_quicktag', '__return_true' );
			// Force include the Quicktag link template.
			add_action( 'admin_footer', array( $this, 'force_quicktag_link_template' ) );
			// Disable Gravity Forms assets, see cred-2384.
			add_action( 'admin_enqueue_scripts', array( $this, 'dequeue_gravity_forms_assets' ) );
		}
	}

	/**
	 * Force include the Quicktag link template so it works.
	 *
	 * @since 2.1
	 */
	public function force_quicktag_link_template() {
		if ( ! class_exists( '_WP_Editors' ) ) {
			require ABSPATH . WPINC . '/class-wp-editor.php';
		}
		\_WP_Editors::wp_link_dialog();
	}

	/**
	 * Add frontend hooks.
	 *
	 * @since 2.1
	 */
	public function add_hooks() {
		add_action( 'admin_footer', array( $this, 'print_templates' ) );
	}

	/**
	 * Initialize assets.
	 *
	 * @since 2.1
	 */
	protected function init_scripts_and_styles() {
		$this->load_backend_assets();
	}

	/**
	 * Load assets.
	 *
	 * @since 2.1
	 */
	protected function load_backend_assets() {
		$this->register_assets();
		$this->define_assets( $this->assets_to_load_js, $this->assets_to_load_css );
		$this->load_assets();
	}

	/**
	 * Register necessary scripts and styles.
	 *
	 * @since 2.1
	 */
	protected function register_assets() {
		if ( $this->is_edit_page() ) {
			$this->assets_manager->register_script(
				static::JS_EDITOR_HANDLE,
				CRED_ABSURL . static::JS_EDITOR_REL_PATH,
				array( \CRED_Asset_Manager::SCRIPT_EDITOR_PROTOTYPE, 'jquery-ui-droppable' ),
				CRED_FE_VERSION
			);

			$this->assets_manager->localize_script(
				static::JS_EDITOR_HANDLE,
				static::JS_EDITOR_I18N_NAME,
				$this->get_scaffold_localization()
			);
			$this->assets_to_load_js['editor_main'] = static::JS_EDITOR_HANDLE;

			$this->assets_to_load_css['editor_shared'] = \CRED_Asset_Manager::STYLE_EDITOR;

			$toolset_common_bootstrap = $this->toolset_common_bootstrap ? $this->toolset_common_bootstrap : \Toolset_Common_Bootstrap::get_instance();
			$toolset_common_bootstrap->register_gui_base();
			$this->gui_base = \Toolset_Gui_Base::get_instance();
			$this->gui_base->init();
			$this->assets_to_load_css['toolset_gui_base'] = \Toolset_Gui_Base::STYLE_GUI_BASE;
		}
	}

	/**
	 * Compatibility: remove some assets that Gravity Forms adds to all native edit pages.
	 */
	public function dequeue_gravity_forms_assets() {
		wp_dequeue_script( 'gform_tooltip_init' );
		wp_dequeue_style( 'gform_tooltip' );
		wp_dequeue_style( 'gform_font_awesome' );
	}

	/**
	 * Register settings metabox
	 *
	 * @param array $form_fields Form Fields.
	 */
	protected function register_settings_metabox( $form_fields ) {
		$callback = '__return_false';
		switch ( static::DOMAIN ) {
			case 'post':
				$form_settings_meta_box = \CRED_Page_Extension_Post_Form_Settings_Meta_Box::get_instance();
				$callback = array( $form_settings_meta_box, 'execute' );
				break;
			case 'user':
				$form_settings_meta_box = \CRED_Page_Extension_User_Form_Settings_Meta_Box::get_instance();
				$callback = array( $form_settings_meta_box, 'execute' );
				break;
		}

		$this->metaboxes['credformtypediv'] = array(
			'title' => __( 'Settings', 'wp-cred' ),
			'callback' => $callback,
			'post_type' => null,
			'context' => 'normal',
			'priority' => 'high',
			'callback_args' => $form_fields,
		);
	}

	protected function register_access_metabox( $form_fields ) {
		$this->metaboxes['accessmessagesdiv'] = array(
			'title' => __( 'Access Control', 'wp-cred' ),
			'callback' => $this->page_extension_factory->get_callback( static::DOMAIN, 'access' ),
			'post_type' => null,
			'context' => 'normal',
			'priority' => 'high',
			'callback_args' => $form_fields,
		);
	}

	protected function register_content_metabox( $form_fields ) {
		$this->metaboxes['credformcontentdiv'] = array(
			'title' => __( 'Form editor', 'wp-cred' ),
			'callback' => $this->page_extension_factory->get_callback( static::DOMAIN, 'content' ),
			'post_type' => null,
			'context' => 'normal',
			'priority' => 'high',
			'callback_args' => $form_fields,
		);
	}

	protected function register_notifications_metabox( $form_fields ) {
		$this->metaboxes['crednotificationdiv'] = array(
			'title' => __( 'E-mail Notifications', 'wp-cred' ),
			'callback' => $this->page_extension_factory->get_callback( self::DOMAIN, 'notifications' ),
			'post_type' => null,
			'context' => 'normal',
			'priority' => 'high',
			'callback_args' => $form_fields,
		);
	}

	protected function register_messages_metabox( $form_fields ) {
		$callback = '__return_false';
		switch ( static::DOMAIN ) {
			case 'post':
				$callback = array( 'CRED_Admin_Helper', 'add_post_messages_metabox' );
				break;
			case 'user':
				$callback = array( 'CRED_Admin_Helper', 'add_user_messages_metabox' );
				break;
		}

		$this->metaboxes['credmessagesdiv'] = array(
			'title' => __( 'Messages', 'wp-cred' ),
			'callback' => $callback,
			'post_type' => null,
			'context' => 'normal',
			'priority' => 'high',
			'callback_args' => $form_fields,
		);
	}

	protected function register_save_metabox( $form_fields ) {
		$this->metaboxes['topbardiv'] = array(
			'title' => __( 'Top bar', 'wp-cred' ),
			'callback' => $this->page_extension_factory->get_callback( self::DOMAIN, 'save' ),
			'post_type' => null,
			'context' => 'normal',
			'priority' => 'high',
			'callback_args' => $form_fields,
		);
	}

	// Just refactored the page extension factory to take module_manager and get ModuleManager
	protected function maybe_register_module_manager_metabox() {
		if ( ! defined( 'MODMAN_PLUGIN_NAME' ) ) {
			return;
		}

		$this->metaboxes['modulemanagerdiv'] = array(
			'title' => __( 'Module Manager', 'wp-cred' ),
			'callback' => $this->page_extension_factory->get_callback( self::DOMAIN, 'module_manager' ),
			'post_type' => null,
			'context' => 'normal',
			'priority' => 'default',
			'callback_args' => array(),
		);
	}

	/**
	 * Print the toolbar templates:
	 * - Scaffold dialog.
	 * - Scaffold item.
	 * - Scaffol item options.
	 * - Scaffol item options media.
	 *
	 * @since 2.2
	 */
	public function print_templates() {
		$template_repository = \CRED_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_CONTENT ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS_MEDIA ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS_CONDITIONALS ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS_HTML_CONTENT ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_NO_DATA ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_DIALOG_SWITCH_TO_DD ),
			null
		);
		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::CONTENT_EDITOR_WIZARD_TITLE_WRAPPER ),
			null
		);
	}


	/**
	 * Craft a set of shared data to be used in the toolbar script.
	 * This set gets completed by each subclass for its own script.
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	protected function get_scaffold_localization() {
		$is_WPML = apply_filters( 'toolset_is_wpml_active_and_configured', false );
		$scaffold_options = array();

		$form_id = toolset_getget( 'post', 0 );

		if ( 'user' === static::DOMAIN ) {
			$form_settings = get_post_meta( $form_id, '_cred_form_settings', true );

			if (
				false === $form_settings
				|| empty( $form_settings )
			) {
				$form_settings = new \stdClass();
			} else {
				$form_settings = maybe_unserialize( $form_settings );
			}

			$form_settings->form = isset( $form_settings->form ) ? $form_settings->form : array();

			$scaffold_options = array(
				'autogeneratedUsername' => array(
					'label'   => __( 'Auto-generate Username', 'wp-cred' ),
					'inputName' => 'autogenerate_username_scaffold',
					'checked' => (bool) toolset_getarr( $form_settings->form, 'autogenerate_username_scaffold', false ),
				),
				'autogeneratedPassword' => array(
					'label'   => __( 'Auto-generate Password', 'wp-cred' ),
					'inputName' => 'autogenerate_password_scaffold',
					'checked' => (bool) toolset_getarr( $form_settings->form, 'autogenerate_password_scaffold', false ),
				),
				'autogeneratedNickname' => array(
					'label'   => __( 'Auto-generate Nickname', 'wp-cred' ),
					'inputName' => 'autogenerate_nickname_scaffold',
					'checked' => (bool) toolset_getarr( $form_settings->form, 'autogenerate_nickname_scaffold', false ),
				),
			);
		}

		// If there is not settings, default value is true
		$scaffold_options['grid_enabled'] = ( $this->toolset_settings->get_bootstrap_version_numeric() > 0 );

		$generic_fields_gui = new GenericGui();

		$i18n = array(
			'panels' => array(
				'scaffold' => array(
					'header' => __( 'Auto-generate form content', 'wp-cred' ),
					'options' => $scaffold_options,
					'yes' => __( 'Yes', 'wp-cred' ),
					'no' => __( 'No', 'wp-cred' ),
					'no_post_type' => __( 'Please, select a post type before creating a Form', 'wp-cred' ),
					'no_user_role' => __( 'Please, select an user role before creating a Form', 'wp-cred' ),
					'bootstrap_version' => $this->toolset_settings->get_bootstrap_version_numeric(),
				),
				'fields' => array(
					'header' => __( 'Add fields to the form', 'wp-cred' ),
				),
				'genericFields' => array(
					'header' => __( 'Add generic fields to the form', 'wp-cred' ),
					'fields' => $this->normalize_field_data( $generic_fields_gui->get_generic_fields() ),
				),
				'shortcode' => array(
					'header' => __( 'Insert a field', 'wp-cred' ),
					'group' => array(
						'header' => __( 'Options for this field', 'wp-cred' ),
					),
				),
				'conditionalGroups' => array(
					'shortcode' => 'cred_show_group',
					'header' => __( 'Insert a conditional group', 'wp-cred' ),
					'warning' => __( 'Your custom conditions will be lost if you switch back to GUI editing.', 'wp-cred' ),
					'edit_manually' => __( 'Edit conditions manually', 'wp-cred' ),
					'edit_gui' => __( 'Edit conditions using the GUI', 'wp-cred' ),
				),
				'placeholders' => array(
					'header' => __( 'Insert a placeholder', 'wp-cred' ),
				),
			),
			'data' => array(
				'shortcodes' => array(
					'form_container' => $this->form_container,
				),
				'scaffold' => array(
					'scaffold_field_id' => \CRED_Form_Builder::SCAFFOLD_FIELD_ID,
					'fields' => array(
						'extra' => array(
							'media'        => array(
								// translators: box used to include media elements: images, videos, ...
								'label'      => __( 'Add media', 'wp-cred' ),
								// translators: Images, videos, ... can be added to a form.
								'tooltip'    => __( 'Block that allows you to add media (e.g. image, video) that will be displayed in the form', 'wp-cred' ),
								'shortcode'  => '',
								'attributes' => array(
									\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'media',
								),
								'permanent'  => true,
								'options'    => array(
									'template' => 'cred-editor-scaffold-itemOptions-media',
								),
							),
							'html'         => array(
								// translators: A box for adding HTML Content.
								'label'      => __( 'HTML content', 'wp-cred' ),
								// translators: The user can add custom HTML to a form.
								'tooltip'    => __( 'Block that allows you to add any HTML content to the form', 'wp-cred' ),
								'shortcode'  => '',
								'attributes' => array(
									\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'html',
								),
								'permanent'  => true,
								'options'    => array(
									'template' => 'cred-editor-scaffold-itemOptions-html-content',
								),
							),
							'conditionals' => array(
								// translators: box to include groups of elements depending on some conditions.
								'label'      => __( 'Conditional Group', 'wp-cred' ),
								// translators: Forms can have several elements that will be displayed if a condition is fullfilled.
								'tooltip'    => __( 'Block of fields that will be displayed depending on set conditions', 'wp-cred' ),
								'shortcode'  => 'cred_show_group',
								'attributes' => array(
									\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'conditionals',
								),
								'permanent'  => true,
								'options'    => array(
									'template' => 'cred-editor-scaffold-itemOptions-conditionals',
								),
							),
						),
						'formElements' => array(
							'feedback' => array(
								'label'      => __( 'Form messages', 'wp-cred' ),
								// translators: After a form is submitted, notifications (success, error, ...) are displayed, this element represents these notifications.
								'tooltip'    => __( 'Block that displays form notifications (e.g. errors, success messages)', 'wp-cred' ),
								'shortcode'  => FormMain::SHORTCODE_NAME_FORM_FIELD,
								'attributes' => array(
									\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'form_messages',
									'field' => 'form_messages',
								),
								'options'    => array(
									'class' => array(
										'label' => __( 'Additional classnames', 'wp-cred' ),
										'type'  => 'text',
										'defaultForceValue' => 'alert alert-warning',
									),
								),
								'location'   => 'top',
							),
						),
					),
				),
			),
		);

		$blocked_recaptcha = true;
		$cred_settings     = Settings::get_instance()->get_settings();
		if (
			isset( $cred_settings['recaptcha']['public_key'] )
			&& isset( $cred_settings['recaptcha']['private_key'] )
			&& ! empty( $cred_settings['recaptcha']['public_key'] )
			&& ! empty( $cred_settings['recaptcha']['private_key'] )
		) {
			$blocked_recaptcha = false;
		}

		$recaptcha_settings_url = admin_url( 'admin.php' );
		$recaptcha_settings_url = add_query_arg(
			array(
				'page' => 'toolset-settings',
				'tab'  => 'forms',
			),
			$recaptcha_settings_url
		);

		$i18n['data']['scaffold']['fields']['formElements']['recaptcha'] = array(
			'label'         => __( 'reCaptcha', 'wp-cred' ),
			// translators: Images, videos, ... can be added to a form.
			'tooltip'       => ! $blocked_recaptcha
				? false
				: esc_attr( '<a href="' . $recaptcha_settings_url . '" target="_blank">' . __( 'You need an API key to use the reCaptcha field', 'wp-cred' ) . '<i class=\'fa fa-external-link\'></i></a>' ),
			'shortcode'     => FormMain::SHORTCODE_NAME_FORM_FIELD,
			'blockedItem'   => $blocked_recaptcha,
			'blockedReason' => __( 'You need an API key to use the reCaptcha field', 'wp-cred' ),
			'blockedLink'   => $recaptcha_settings_url,
			'attributes'    => array(
				\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'recaptcha',
				'field'                               => 'recaptcha',
				'class'                               => 'form-control',
				'output'                              => 'bootstrap',
			),
			'location'      => 'bottom',
		);

		$i18n['data']['scaffold']['fields']['formElements']['submit'] = array(
			'label'        => __( 'Submit button', 'wp-cred' ),
			'shortcode'    => FormMain::SHORTCODE_NAME_FORM_FIELD,
			'requiredItem' => true,
			'attributes'   => array(
				\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'form_submit',
				'field'                               => 'form_submit',
				'output'                              => 'bootstrap',
			),
			'options'      => array(
				'value' => array(
					'label'             => __( 'Button label', 'wp-cred' ),
					'type'              => 'text',
					'defaultForceValue' => __( 'Submit', 'wp-cred' ),
				),
				'class' => array(
					'label'             => __( 'Additional classnames', 'wp-cred' ),
					'type'              => 'text',
					'defaultForceValue' => 'btn btn-primary btn-lg',
				),
			),
			'location'     => 'bottom',
		);

		return $i18n;
	}


	/**
	 * Some fields are permanent, it means they can be removed from the sidebar. It adds a permanent attribute to the field.
	 * Also it adds an option for the legend
	 *
	 * @param array $fields List of fields
	 * @return array
	 * @since 2.2
	 */
	private function normalize_field_data( $fields ) {
		foreach ( $fields as $key => $field ) {
			$fields[ $key ]['permanent'] = true;
			if ( isset( $fields[ $key ]['attributes'] ) ) {
				$fields[ $key ]['attributes'][ \CRED_Form_Builder::SCAFFOLD_FIELD_ID ] = $fields[ $key ]['attributes']['type'];
			}
			if ( ! isset( $fields[ $key ]['options'] ) ) {
				$fields[ $key ]['options'] = array();
			}
			$fields[ $key ]['options'] = $this->add_label_option( $fields[ $key ]['options'] );
		}
		return $fields;
	}

	/**
	 * Adds label option to fields
	 *
	 * @param array $options Fields options.
	 * @return array Fields options updated
	 * @since 2.3
	 */
	private function add_label_option( $options ) {
		$options['label'] = array(
			'label'             => __( 'Label', 'wp-cred' ),
			'type'              => 'text',
			'defaultForceValue' => '',
			// translators: it is a text input that will be translated into a <label>text</label> HTML element.
			'description'       => __( 'A &lt;label&gt; included in the Form\'s HTML', 'wp-cred' ),
		);
		return $options;
	}
}
