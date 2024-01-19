<?php

/**
 * Class that handles all the WPML functionality related to dialogs.
 *
 * @since 2.5.0
 */
class WPV_WPML_Shortcodes_Dialog {

	public function __construct() {
		$this->init_hooks();
		$this->add_shortcodes();
	}

	public function init_hooks() {
		add_action( 'wpv_action_collect_shortcode_groups', array( $this, 'register_shortcodes_dialog_group' ) );
		add_action( 'wpv_action_wpv_add_wpml_shortcodes_to_editor', array( $this, 'wpv_add_wpml_shortcodes_to_editor' ), 10, 2 ); //Deprecated
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data', array( $this, 'register_wpml_shortcodes_data' ) );
	}

	public function add_shortcodes() {
		// WPML shortcodes in the Fields and Views dialog
		add_shortcode( 'wpml-lang-switcher', array( $this, 'wpml_lang_switcher' ) );
		add_shortcode( 'wpml-lang-footer', array( $this, 'wpml_lang_footer' ) );
		//add_shortcode( 'wpml-breadcrumbs', array( $this, 'wpv_wpml_breadcrumbs' ) );
		add_shortcode( 'wpml-sidebar', array( $this, 'wpml_sidebar' ) );
	}

	/**
	 * Determine whether WPML is activated and configured.
	 *
	 * @return mixed|void
	 *
	 * @since 2.5.0
	 *
	 * @codeCoverageIgnore
	 */
	public function is_wpml_active_and_configured() {
		return apply_filters( 'toolset_is_wpml_active_and_configured', false );
	}

	/**
	 * Get the shortcode dialog group information.
	 *
	 * @since 2.5.0
	 */
	public function get_shortcodes_dialog_group_id_and_data() {
		$group_id = 'wpml';
		$group_data = array(
			'name' => __( 'WPML', 'wpv-views' ),
			'fields' => array(),
		);

		if ( $this->is_wpml_active_and_configured() ) {
			$group_data['fields']['wpml-string'] = array(
				'name'      => __( 'Translatable string', 'wpv-views' ),
				'handle'    => 'wpml-string',
				'shortcode' => '[wpml-string]',
				'callback'  => "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-string', title: '" . esc_js( __( 'Translatable string', 'wpv-views' ) ) . "' })",
			);
		}

		$group_data['fields']['wpml-lang-switcher'] = array(
			'name'      => __( 'Language selector', 'wpv-views' ),
			'handle'    => 'wpml-lang-switcher',
			'shortcode' => '[wpml-lang-switcher]',
			'callback'  => "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-lang-switcher', title: '" . esc_js( __( 'Language selector', 'wpv-views' ) ) . "' })",
		);
		$group_data['fields']['wpml-lang-footer'] = array(
			'name'      => __( 'Footer language selector', 'wpv-views' ),
			'handle'    => 'wpml-lang-footer',
			'shortcode' => '[wpml-lang-footer]',
			'callback'  => "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-lang-footer', title: '" . esc_js( __( 'Footer language selector', 'wpv-views' ) ) . "' })",
		);

		global $iclCMSNavigation;
		if ( isset( $iclCMSNavigation ) ) {
			$group_data['fields']['wpml-sidebar'] = array(
				'name'      => __( 'Sidebar navigation', 'wpv-views' ),
				'handle'    => 'wpml-sidebar',
				'shortcode' => '[wpml-sidebar]',
				'callback'  => "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-sidebar', title: '" . esc_js( __( 'Sidebar navigation', 'wpv-views' ) ) . "' })",
			);
		}
		
		$group_data['fields'][ WPV_Shortcode_WPML_Conditional::SHORTCODE_NAME ] = array(
			'name'      => __( 'Conditional output per language', 'wpv-views' ),
			'handle'    => WPV_Shortcode_WPML_Conditional::SHORTCODE_NAME,
			'shortcode' => '[' . WPV_Shortcode_WPML_Conditional::SHORTCODE_NAME . ']',
			'callback'  => "WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: '" . WPV_Shortcode_WPML_Conditional::SHORTCODE_NAME . "', title: '" . esc_js( __( 'Conditional output per language', 'wpv-views' ) ) . "' })",
		);

		return array(
			'group_id' => $group_id,
			'group_data' => $group_data,
		);
	}

	/**
	 * Register the WPML shortcodes in the Fields and Views dialog, inside its own group.
	 *
	 * @since unknown
	 * @since 2.5.0   Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 * @since 2.5.0   Moved the fetching of the dialog group id and data information to an external method.
	 */
	public function register_shortcodes_dialog_group() {

		if ( ! $this->is_wpml_active_and_configured() ) {
			return;
		}

		// @todo review the nonce management in the shortcodes GUI script:
		// we are passing it as a localization string, we do not need them here anymore.
		$nonce = '';

		$group_info = $this->get_shortcodes_dialog_group_id_and_data();

		$group_id = $group_info['group_id'];

		$group_data = $group_info['group_data'];

		do_action( 'wpv_action_register_shortcode_group', $group_id, $group_data );
	}

	/**
	 * Register the WPML shortcodes in the Fields and Views dialog, when the WPML pieces are available.
	 *
	 * @todo avoid globals and use API filters instead.
	 *
	 * @note We do not add the [wpml-breadcrumbs] shortcode as it needs to be executed outside the post loop, hence it is useless for Views.
	 *
	 * @since 2.3.0
	 * @deprecated 2.3.0 Keep it for backwards compatibility.
	 *
	 * @codeCoverageIgnore
	 */
	public function wpv_add_wpml_shortcodes_to_editor( $editor, $nonce ) {

		_doing_it_wrong(
			'wpv_add_wpml_shortcodes_to_editor',
			__( 'This function was deprecated in Views 2.3.0. Use the "wpv_action_wpv_register_dialog_group" action instead.', 'wpv-views' ),
			'2.3.0'
		);

		if ( $this->is_wpml_active_and_configured() ) {
			$editor->add_insert_shortcode_menu(
				__( 'Language selector', 'wpv-views' ),
				'wpml-lang-switcher',
				'WPML',
				"WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-lang-switcher', title: '" . esc_js( __( 'Language selector', 'wpv-views' ) ) . "' })"
			);
			$editor->add_insert_shortcode_menu(
				__( 'Footer language selector', 'wpv-views' ),
				'wpml-lang-footer',
				'WPML',
				"WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-lang-footer', title: '" . esc_js( __( 'Footer language selector', 'wpv-views' ) ) . "' })"
			);
			global $iclCMSNavigation;
			if ( isset( $iclCMSNavigation ) ) {
				/*

				$editor->add_insert_shortcode_menu(
					__( 'Breadcrumbs navigation', 'wpv-views' ),
					'wpml-breadcrumbs',
					'WPML'
				);
				*/
				$editor->add_insert_shortcode_menu(
					__( 'Sidebar navigation', 'wpv-views' ),
					'wpml-sidebar',
					'WPML',
					"WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: 'wpml-sidebar', title: '" . esc_js( __( 'Sidebar navigation', 'wpv-views' ) ) . "' })"
				);
			}
		}
	}

	/**
	 * Register the WPML shortcodes in the general shortcodes GUI so we can display attribute oprions and information.
	 *
	 * @param array $views_shortcodes The registered Views shortcodes.
	 *
	 * @return array
	 *
	 * @since 2.3.0
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 */
	public function register_wpml_shortcodes_data( $views_shortcodes ) {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return $views_shortcodes;
		}

		$views_shortcodes['wpml-string'] = array(
			'callback' => array( $this, 'shortcodes_get_wpml_string_data' ),
		);

		$views_shortcodes['wpml-lang-switcher'] = array(
			'callback' => array( $this, 'shortcodes_get_wpml_lang_switcher_data' ),
		);
		$views_shortcodes['wpml-lang-footer'] = array(
			'callback' => array( $this, 'shortcodes_get_wpml_lang_footer_data' ),
		);
		global $iclCMSNavigation;
		if ( isset( $iclCMSNavigation ) ) {
			$views_shortcodes['wpml-sidebar'] = array(
				'callback' => array( $this, 'shortcodes_get_wpml_sidebar_data' ),
			);
		}
		return $views_shortcodes;
	}

	/**
	 * Callback for the [wpml-lang-switcher] shortcode.
	 *
	 * @param array   $atts
	 * @param string  $value
	 *
	 * @return string
	 *
	 * @since unknown
	 * @since 2.3.0   Moved to this compatibility class
	 * @since 2.5.0   Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 */
	public function wpml_lang_switcher( $atts, $value ) {

		ob_start();
		do_action( 'wpml_add_language_selector' );
		$result = ob_get_clean();

		return $result;
	}

	/**
	 * Callback for the [wpml-lang-footer] shortcode.
	 *
	 * @param array   $atts
	 * @param string  $value
	 *
	 * @return string
	 *
	 * @since unknown
	 * @since 2.3.0   Moved to this compatibility class
	 * @since 2.5.0   Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 */
	public function wpml_lang_footer( $atts, $value ) {

		ob_start();
		do_action( 'wpml_footer_language_selector' );
		$result = ob_get_clean();

		return $result;

	}

	/**
	 * Callback for the [wpml-breadcrumbs] shortcode.
	 *
	 * @note We do not add the [wpml-breadcrumbs] shortcode as it needs to be executed outside the post loop, hence it is useless for Views.
	 *
	 * @param array   $atts
	 * @param string  $value
	 *
	 * @return string
	 *
	 * @since unknown
	 * @since 2.3.0   Moved to this compatibility class
	 * @since 2.5.0   Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 */
	/*
	public function wpv_wpml_breadcrumbs( $atts, $value ) {

		ob_start();
		do_action( 'icl_navigation_breadcrumb' );
		$result = ob_get_clean();

		return $result;
	}
	*/

	/**
	 * Callback for the [wpml-sidebar] shortcode.
	 *
	 * @param array   $atts
	 * @param string  $value
	 *
	 * @return string
	 *
	 * @since unknown
	 * @since 2.3.0   Moved to this compatibility class
	 * @since 2.5.0   Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 */
	public function wpml_sidebar( $atts, $value ) {

		ob_start();
		do_action( 'icl_navigation_sidebar' );
		$result = ob_get_clean();

		return $result;
	}

	/**
	 * Get the data for the GUI for the [wpml-string] shortcode.
	 *
	 * @since unknown
	 * @since 2.5.0 Changed the way we are checking whether WPML is activated and configured or not. Now we are using a filter
	 *              that get this kind of info from the TC. We also changed the way the strings are registered for translation,
	 *              which is now done using the "wpml_register_single_string" action.
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 */
	function shortcodes_get_wpml_string_data() {
		if ( ! $this->is_wpml_active_and_configured() ) {
			return array();
		}

		$data = array(
			'name' => __( 'Translatable string', 'wpv-views' ),
			'label' => __( 'Translatable string', 'wpv-views' ),
			'attributes' => array(
				'display-options' => array(
					'label' => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'context' => array(
							'label' => __( 'WPML context', 'wpv-views' ),
							'type' => 'suggest',
							'action' => 'wpv_suggest_wpml_contexts',
							'default' => '',
							'required' => true,
							'placeholder' => __( 'Start typing', 'wpv-views' ),
						),
						'name' => array(
							'label' => __( 'String name', 'wpv-views' ),
							'type' => 'text',
							'default' => '',
							'description' => __( 'Name this string to find it easily in the WPML String Translation page.', 'wpv-views' ),
						),
					),
					'content' => array(
						'label' => __( 'String to translate', 'wpv-views' ),
					),
				),
			),
		);
		return $data;
	}

	/**
	 * Get the data for the GUI for the [wpml-lang-switcher] shortcode.
	 *
	 * @since unknown
	 * @since 2.5.0    Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 */
	public function shortcodes_get_wpml_lang_switcher_data() {

		if ( ! $this->is_wpml_active_and_configured() ) {
			return array();
		}

		$data = array(
			'name' => __( 'Language switcher', 'wpv-views' ),
			'label' => __( 'Language switcher', 'wpv-views' ),
			'attributes' => array(
				'display-info' => array(
					'label' => __( 'Information', 'wpv-views' ),
					'header' => __( 'Information', 'wpv-views' ),
					'fields' => array(
						'wpv-wpml-lang-switcher-information' => array(
							'type' => 'info',
							'content' => __( 'This will display a language switcher styled as set in the WPML > Languages settings.', 'wpv-views' ),
						),
					),
				),
			),
		);
		return $data;
	}

	/**
	 * Get the data for the GUI for the [wpml-lang-footer] shortcode.
	 *
	 * @since unknown
	 * @since 2.5.0    Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 */
	public function shortcodes_get_wpml_lang_footer_data() {

		if ( ! $this->is_wpml_active_and_configured() ) {
			return array();
		}

		$data = array(
			'name' => __( 'Footer language switcher', 'wpv-views' ),
			'label' => __( 'Footer language switcher', 'wpv-views' ),
			'attributes' => array(
				'display-info' => array(
					'label' => __( 'Information', 'wpv-views' ),
					'header' => __( 'Information', 'wpv-views' ),
					'fields' => array(
						'wpv-wpml-lang-footer-information' => array(
							'type' => 'info',
							'content' => __( 'This will display a footer language switcher styled as set in the WPML > Languages settings.', 'wpv-views' ),
						),
					),
				),
			),
		);
		return $data;
	}

	/**
	 * Get the data for the GUI for the [wpml-sidebar] shortcode.
	 *
	 * @since unknown
	 * @since 2.5.0   Moved to a new Class, WPV_WPML_Shortcodes_Dialog.
	 */
	public function shortcodes_get_wpml_sidebar_data() {

		if ( ! $this->is_wpml_active_and_configured() ) {
			return array();
		}

		global $iclCMSNavigation;
		if ( ! isset( $iclCMSNavigation ) ) {
			return array();
		}

		$data = array(
			'name' => __( 'Sidebar navigation', 'wpv-views' ),
			'label' => __( 'Sidebar navigation', 'wpv-views' ),
			'attributes' => array(
				'display-info' => array(
					'label' => __( 'Information', 'wpv-views' ),
					'header' => __( 'Information', 'wpv-views' ),
					'fields' => array(
						'wpv-wpml-sidebar-information' => array(
							'type' => 'info',
							'content' => __( 'This will display the current page local navigation tree with ancestors, siblings and descendants.', 'wpv-views' ),
						),
					),
				),
			),
		);
		return $data;
	}
}
