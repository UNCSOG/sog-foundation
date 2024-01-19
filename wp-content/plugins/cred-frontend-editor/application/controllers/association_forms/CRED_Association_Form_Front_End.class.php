<?php

/**
 * Class CRED_Association_Form_Front_End
 */
class CRED_Association_Form_Front_End extends CRED_Association_Form_Abstract {

	private $assets_to_load_js = array();
	private $assets_to_load_css = array();

	const AJAX_ROLE_ACTION = 'cred_association_form_ajax_role_find';
	const AJAX_ROLE_NONCE_NAME = 'cred_association_form_ajax_role_find_nonce';
	const JS_FRONT_END_MAIN = 'toolset_cred_association_forms_front_end_script_main';
	const JS_FRONT_END_MAIN_REL_PATH = '/public/association_forms/js/front_end/main.js';
	const JS_FRONT_END_MAIN_I18N = 'CredAssociationsFormSettings';

	const CSS_FRONT_END_HANDLE = 'toolset_cred_association_forms_front_end_css';
	const CSS_FRONT_END_REL_PATH = '/public/association_forms/css/front_end.css';

	const JS_FRONT_END_DELETE = 'toolset_cred_association_forms_front_end_script_delete';
	const JS_FRONT_END_DELETE_REL_PATH = '/public/association_forms/js/front_end/delete.js';
	const JS_FRONT_END_DELETE_I18N = 'toolset_cred_association_forms_front_end_script_delete_i18n';

	public function __construct( CRED_Association_Form_Model_Factory $model_factory, CRED_Association_Form_Relationship_API_Helper $helper ) {
		parent::__construct( $model_factory, $helper );
	}

	public function add_hooks() {
		// Manage assets required by shortcodes
		add_action( 'cred_do_shortcode_' . CRED_Shortcode_Association_Form::SHORTCODE_NAME, array( $this, 'run_in_shortcode_form' ) );
		add_action( 'cred_do_shortcode_' . OTGS\Toolset\CRED\Model\Shortcode\Delete\Association::SHORTCODE_NAME, array( $this, 'run_in_shortcode_delete' ) );
		// Manage form feedback in case of success
		add_filter( 'cred_form_feedback', array( $this, 'set_post_success_feedback' ), 10, 3 );
		add_filter( 'cred_form_feedback_classnames', array( $this, 'add_style_to_feedback' ), 10, 3 );
	}

	public function initialize() {
		$this->add_hooks();
		$this->assets_manager = Toolset_Assets_Manager::get_instance();
		$this->init_scripts_and_styles();
	}

	private function init_scripts_and_styles() {
		$this->assets_to_load_js = array(
			CRED_Shortcode_Association_Form::SHORTCODE_NAME => array(),
			OTGS\Toolset\CRED\Model\Shortcode\Delete\Association::SHORTCODE_NAME => array(),
		);
		$this->assets_to_load_css = array(
			CRED_Shortcode_Association_Form::SHORTCODE_NAME => array(),
			OTGS\Toolset\CRED\Model\Shortcode\Delete\Association::SHORTCODE_NAME => array(),
		);

		$this->register_assets();
	}

	private function register_assets() {
		// Main form shortcode assets
		$this->assets_manager->register_script( self::JS_FRONT_END_MAIN, CRED_ABSURL . self::JS_FRONT_END_MAIN_REL_PATH, array(
			'jquery',
			'jquery-form',
			'underscore',
			Toolset_Assets_Manager::SCRIPT_PARSLEY,
			Toolset_Assets_Manager::SCRIPT_UTILS,
		), CRED_FE_VERSION, true );

		$toolset_ajax = Toolset_Ajax::get_instance();
		$action_suggest_type = $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_POST_TYPE );
		$action_suggest_title = $toolset_ajax->get_action_js_name( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE );

		$media_model = new OTGS\Toolset\CRED\Model\Wordpress\Media();

		$this->assets_manager->localize_script(
			self::JS_FRONT_END_MAIN,
			self::JS_FRONT_END_MAIN_I18N,
			array(
				'ajax_action' => CRED_Association_Form_Main::CRED_ASSOCIATION_FORM_AJAX_ACTION,
				'ajax_suggest_action_type' => $action_suggest_type,
				'ajax_suggest_action_title' => $action_suggest_title,
				'ajax_role_action' => self::AJAX_ROLE_ACTION,
				'ajax_role_nonce_name' => self::AJAX_ROLE_NONCE_NAME,
				'ajax_role_nonce' => wp_create_nonce( self::AJAX_ROLE_NONCE_NAME, self::AJAX_ROLE_NONCE_NAME ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'wpnonce' => CRED_Association_Form_Main::CRED_ASSOCIATION_FORM_AJAX_NONCE,
				'select2nonce_type'        => wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_POST_TYPE ),
				'select2nonce_title'        => wp_create_nonce( Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ),
				'strings' => array(
					'fail_text' => __( 'An error occurred while processing the ajax request, check the log, refresh and try again.', 'wp-cred' ),
					'role_placeholder' => __( 'Search for a post', 'wp-cred' ),
					'role_no_matches' => __( 'No %POST_TYPE_LABEL% can be associated to %OTHER_POST_TITLE%', 'wp-cred' ),
					'role_no_search' => __( 'No results can be found searching: %SEARCH%', 'wp-cred' ),
					/* translators: Error message when trying to upload a file too big on a media field used in a frontend form */
					'file_too_big' => __( 'You cannot upload a file of this size', 'wp-cred' ),
					'file_not_supported' => $media_model->get_upload_validation_error_message(),
				),
				'current_language' => apply_filters( 'wpml_current_language', false ),
			)
		);
		$this->assets_to_load_js[ CRED_Shortcode_Association_Form::SHORTCODE_NAME ][ self::JS_FRONT_END_MAIN ] = self::JS_FRONT_END_MAIN;

		$this->assets_manager->register_style( self::CSS_FRONT_END_HANDLE, CRED_ABSURL . self::CSS_FRONT_END_REL_PATH, array(
			Toolset_Assets_Manager::STYLE_PARSLEY,
		), CRED_FE_VERSION );
		$this->assets_to_load_css[ CRED_Shortcode_Association_Form::SHORTCODE_NAME ][ self::CSS_FRONT_END_HANDLE ] = self::CSS_FRONT_END_HANDLE;

		// Delete shortcode assets
		$this->assets_manager->register_script( self::JS_FRONT_END_DELETE, CRED_ABSURL . self::JS_FRONT_END_DELETE_REL_PATH, array(
				'jquery',
				'underscore',
				Toolset_Assets_Manager::SCRIPT_UTILS,
			), CRED_FE_VERSION, true );

		$origin = admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' )  );
		$query_args['toolset_force_one_query_arg'] = 'toolset';
		$ajaxurl = esc_url( add_query_arg(
			$query_args,
			$origin
		) );
		$this->assets_manager->localize_script(
			self::JS_FRONT_END_DELETE,
			self::JS_FRONT_END_DELETE_I18N,
			array(
				'data' => array(
					'ajaxurl' => $ajaxurl,
					'nonce' => wp_create_nonce( CRED_Ajax::CALLBACK_DELETE_ASSOCIATION ),
				),
			)
		);
		$this->assets_to_load_js[ OTGS\Toolset\CRED\Model\Shortcode\Delete\Association::SHORTCODE_NAME ][ self::JS_FRONT_END_DELETE ] = self::JS_FRONT_END_DELETE;

	}

	public function run_in_shortcode_form() {
		// load in footer conditionally
		$this->load_assets_js( CRED_Shortcode_Association_Form::SHORTCODE_NAME );
		// load also CSS in the footer, so it is loaded conditionally and we avoid conflicts if form is not there
		$this->load_assets_css( CRED_Shortcode_Association_Form::SHORTCODE_NAME );
	}

	public function run_in_shortcode_delete() {
		// load in footer conditionally
		$this->load_assets_js( OTGS\Toolset\CRED\Model\Shortcode\Delete\Association::SHORTCODE_NAME );
		// load also CSS in the footer, so it is loaded conditionally and we avoid conflicts if form is not there
		$this->load_assets_css( OTGS\Toolset\CRED\Model\Shortcode\Delete\Association::SHORTCODE_NAME );
	}

	public function load_assets_js( $shortcode ) {
		do_action( 'toolset_enqueue_scripts', $this->assets_to_load_js[ $shortcode ] );
	}

	public function load_assets_css( $shortcode ) {
		do_action( 'toolset_enqueue_styles', $this->assets_to_load_css[ $shortcode ] );
	}

	/**
	 * Set the right success feedback on a form that was successfully posted and saved.
	 *
	 * Maybe we also need to compare against the form count, not available here.
	 *
	 * @param string $message
	 * @param int $form_id
	 * @param int $form_count
	 * @return string
	 * @since 2.3.2
	 */
	public function set_post_success_feedback( $message, $form_id, $form_count ) {
		$submitted_form_id = toolset_getget( CRED_Shortcode_Form_Container_Base::REDIRECT_REFERRER_FORM_ID_KEY, false );
		if (
			! $submitted_form_id
			|| (int) $submitted_form_id !== $form_id
		) {
			return $message;
		}

		$messages = get_post_meta( $submitted_form_id, 'messages', true);
		$message = $messages['cred_message_post_saved'];
		$message = apply_filters( 'cred_translate_action_message', $message, 'message-cred_message_post_saved', $form_id );

		return $message;
	}

	/**
	 * Set the right success feedback classname on a form that was successfully posted and saved.
	 *
	 * Maybe we also need to compare against the form count, not available here.
	 *
	 * @param array $classes
	 * @param int $form_id
	 * @param int $form_count
	 * @return array
	 * @since 2.3.2
	 */
	public function add_style_to_feedback( $classes, $form_id, $form_count ) {
		$submitted_form_id = toolset_getget( CRED_Shortcode_Form_Container_Base::REDIRECT_REFERRER_FORM_ID_KEY, false );
		if (
			! $submitted_form_id
			|| (int) $submitted_form_id !== $form_id
		) {
			return $classes;
		}

		$classes[] = 'alert-success';

		return $classes;
	}
}
