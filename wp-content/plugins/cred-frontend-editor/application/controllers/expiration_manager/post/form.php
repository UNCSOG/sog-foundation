<?php

namespace OTGS\Toolset\CRED\Controller\ExpirationManager\Post;

use OTGS\Toolset\CRED\Controller\ExpirationManager\Post as PostExpirationManager;
use OTGS\Toolset\CRED\Model\Forms\Post\Expiration\Settings as PostExpirationSettingsModel;

/**
 * Controller for post forms editors on post expirations.
 *
 * @since 2.3
 */
class Form {

	const GENERAL_SETTING_NAME = '_cred_form_settings';

	const SCRIPT_HANDLE = 'cred-post-expiration-form';
	const SCRIPT_I18N = 'cred_post_expiration_form_i18n';

	/**
	 * @var \OTGS\Toolset\CRED\Controller\ExpirationManager\Post
	 */
	private $manager;

	/**
	 * Manager constructor.
	 *
	 * @since 2.3
	 */
	public function __construct( PostExpirationManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Initialize the manager.
	 *
	 * @since 2.3
	 */
	public function initialize() {
		$this->register_assets();
		$this->add_hooks();
	}

	/**
	 * Register the assets for the post expiration setting in forms editors.
	 *
	 * @since 2.3
	 */
	private function register_assets() {
		wp_register_script(
			self::SCRIPT_HANDLE,
			CRED_ABSURL . '/public/expiration/post/form.js',
			array( 'jquery' ),
			CRED_FE_VERSION,
			true
		);

		$post_expiration_i18n = array();
		wp_localize_script( self::SCRIPT_HANDLE, self::SCRIPT_I18N, $post_expiration_i18n );
	}

	/**
	 * Add hooks.
	 *
	 * @since 2.3
	 */
	private function add_hooks() {
		add_action( 'cred_ext_cred_post_form_settings', array( $this, 'render_settings' ), 10, 2 );
		add_action( 'cred_admin_save_form', array( $this, 'save_settings' ), 10, 2 );
	}

	/**
	 * Render the post form settings for post expiration.
	 *
	 * @param object $form
	 * @param array $settings
	 * @since 2.3
	 */
	public function render_settings( $form, $settings ) {
		wp_enqueue_script( self::SCRIPT_HANDLE );

		$form_expiration_settings_manager = new PostExpirationSettingsModel();

		$template_repository = \CRED_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();
		$context = array(
			'settings' => $form_expiration_settings_manager->load( $form->ID ),
			'stati' => array(
				'basic' => apply_filters(
					'cred_pe_post_expiration_post_basic_status',
					$this->manager->get_status_model()->get_basic_stati()
				),
				'native' => apply_filters(
					'cred_pe_post_expiration_post_status',
					$this->manager->get_status_model()->get_native_stati_with_trash()
				),
				'custom' => apply_filters(
					'cred_pe_post_expiration_post_custom_status',
					$this->manager->get_status_model()->get_custom_stati()
				),
			),
			'stati_label' => array(
				'native' => $this->manager->get_status_model()->get_native_stati_group_label(),
				'custom' => $this->manager->get_status_model()->get_custom_stati_group_label(),
			),
		);

		$renderer->render(
			$template_repository->get( \CRED_Output_Template_Repository::SETTINGS_POST_EXPIRATION ),
			$context
		);
	}

	/**
	 * Save the post form expiration settings.
	 * When enabling the feature on a form, register support for the post type it manages.
	 *
	 * @param int $form_id
	 * @param object $form_post
	 * @since 2.3
	 */
	public function save_settings( $form_id, $form_post ) {
		$form_expiration_settings_manager = new PostExpirationSettingsModel();
		$form_expiration_settings = $form_expiration_settings_manager->save_posted_settings( $form_id );

		if ( ! $form_expiration_settings['enable'] ) {
			return;
		}

		$form_settings = get_post_meta( $form_id, self::GENERAL_SETTING_NAME, true );
		if ( isset( $form_settings->post['post_type'] ) ) {
			$this->manager->add_post_type_support( $form_settings->post['post_type'] );
		}

	}

}
