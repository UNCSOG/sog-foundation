<?php

namespace OTGS\Toolset\CRED\Controller\FieldsControl;

use OTGS\Toolset\CRED\Model\Field\Generic\Gui as GenericGui;

/**
 * Abstract class to extend for each of the fields control admin page
 *
 * This one is responsible of including the assets to make actions work.
 *
 * @since 2.1
 */
abstract class Base {

	/**
	 * Main script handle and relative path templates.
	 */
    const JS_PROTOTYPE = 'cred_fields_control_prototype';
    const JS_PROTOTYPE_REL_PATH = '/public/fields_control/js/prototype.js';
	const JS_HANDLE = 'cred_%s_fields_control_js';
	const JS_REL_PATH = '/public/fields_control/js/%s_fields_control.js';
	const JS_I18N_NAME = 'cred_%s_fields_control_i18n';

	const CSS_HANDLE = 'cred_fields_control_css';
	const CSS_REL_PATH = '/public/fields_control/css/fields_control.css';

    /**
	 * Domain: post|user|association
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
    protected $domain;

    /**
	 * Required scripts.
	 *
	 * @var array
	 *
	 * @since 2.1
	 */
	protected $scripts = array();

	/**
	 * Required styles.
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
	 * Main script handle.
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
	protected $js_handle;

	/**
	 * Main script relative path.
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
	protected $js_relpath;

	/**
	 * Main script localization object name.
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
    protected $js_i18n_name;

    public function initialize() {

		$this->assets_manager = \Toolset_Assets_Manager::get_instance();

		$this->js_handle = sprintf( self::JS_HANDLE, $this->domain );
		$this->js_relpath = sprintf( self::JS_REL_PATH, $this->domain );
		$this->js_i18n_name = sprintf( self::JS_I18N_NAME, $this->domain );

		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function admin_init() {

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
		add_action( 'admin_footer', array( $this, 'print_templates' ) );
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

    	$this->assets_manager->register_script(
			self::JS_PROTOTYPE,
			CRED_ABSURL . self::JS_PROTOTYPE_REL_PATH,
			array(
				'jquery',
				'jquery-ui-dialog',
				'jquery-ui-tabs',
				'jquery-ui-sortable',
				'underscore',
				'wp-util',
				\Toolset_Assets_Manager::SCRIPT_TOOLSET_SHORTCODE
			),
			CRED_FE_VERSION
		);

		$this->assets_manager->register_script(
			$this->js_handle,
			CRED_ABSURL . $this->js_relpath,
			array( self::JS_PROTOTYPE ),
			CRED_FE_VERSION
		);

		$this->assets_manager->localize_script(
			$this->js_handle,
			$this->js_i18n_name,
			$this->get_script_localization()
		);

		$this->scripts[ $this->js_handle ] = $this->js_handle;

		$this->assets_manager->register_style(
			self::CSS_HANDLE,
			CRED_ABSURL . self::CSS_REL_PATH,
			array(),
			CRED_FE_VERSION
		);

		$this->styles[ self::CSS_HANDLE ] = self::CSS_HANDLE;
		$this->styles[ \Toolset_Assets_Manager::STYLE_TOOLSET_COMMON ] = \Toolset_Assets_Manager::STYLE_TOOLSET_COMMON;
		$this->styles[ \Toolset_Assets_Manager::STYLE_TOOLSET_DIALOGS_OVERRIDES ] = \Toolset_Assets_Manager::STYLE_TOOLSET_DIALOGS_OVERRIDES;
		$this->styles[ \Toolset_Assets_Manager::STYLE_SELECT2_CSS ] = \Toolset_Assets_Manager::STYLE_SELECT2_CSS;
		$this->styles[ \Toolset_Assets_Manager::STYLE_FONT_AWESOME ] = \Toolset_Assets_Manager::STYLE_FONT_AWESOME;

		do_action( 'otg_action_otg_enforce_styles' );
    }

    /**
	 * Enqueue the registered scripts and styles.
	 *
	 * @since 2.1
	 */
	public function load_assets() {
		do_action( 'toolset_enqueue_scripts', $this->scripts );
		do_action( 'toolset_enqueue_styles', $this->styles );
    }

    /**
	 * Get the localization data for the main script.
	 *
	 * @since 2.1
	 */
    abstract protected function get_script_localization();

    /**
	 * Craft a set of shared data to be used in the scripts per domain.
	 * This set gets completed by each subclass for its own script.
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
    protected function get_shared_script_localization() {
		$origin = admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' )  );
		$query_args['toolset_force_one_query_arg'] = 'toolset';
		$ajaxurl = esc_url( add_query_arg(
			$query_args,
			$origin
		) );

		$cred_ajax = \CRED_Ajax::get_instance();

		$generic_fields_gui = new GenericGui();
		$generic_fields_options = $generic_fields_gui->get_generic_fields();

        return array(
            'action' => array(
				'loading' => __( 'Loading...', 'wp-cred' ),
				'saving' => __( 'Saving', 'wp-cred' ),
				'save' => __( 'Save', 'wp-cred' ),
				'cancel' => __( 'Cancel', 'wp-cred' ),
				'back' => __( 'Back', 'wp-cred' )
			),
			'data' => array(
				'ajaxurl' => $ajaxurl,
				'add' => array(
					'action' => $cred_ajax->get_action_js_name( \CRED_Ajax::CALLBACK_FIELDS_CONTROL_ADD ),
					'nonce' => wp_create_nonce( \CRED_Ajax::CALLBACK_FIELDS_CONTROL_ADD )
				),
				'delete' => array(
					'action' => $cred_ajax->get_action_js_name( \CRED_Ajax::CALLBACK_FIELDS_CONTROL_REMOVE ),
					'nonce' => wp_create_nonce( \CRED_Ajax::CALLBACK_FIELDS_CONTROL_REMOVE )
				)
			),
			'labels' => array(
				'notSet' => __( 'Not Set', 'wp-cred' ),
				'unknown' => __( 'Unknown', 'wp-cred' ),
				'fieldOptions' => __( 'Field options', 'wp-cred' )
			),
			'fields' => $generic_fields_options,
			'parameters' => array(
				'cred_custom' => true
			),
			'attributes' => array(
				'include_scaffold' => array(
					'label' => __( 'Include this field in the form scaffold generator', 'wp-cred' ),
					'type'  => 'radio',
					'options' => array(
						'no' => __( 'No, do not include it', 'wp-cred' ),
						'yes' => __( 'Yes, include this field in the scaffold', 'wp-cred' )
					),
					'defaultValue' => 'yes'
				)
			)
        );
    }

    /**
	 * Print the toolbar templates:
	 * - Scaffold dialog.
	 * - Scaffold item.
	 * - Scaffold item options.
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
			$template_repository->get( \CRED_Output_Template_Repository::FIELDS_CONTROL_ADD_OR_EDIT_DIALOG ),
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

    }

}
