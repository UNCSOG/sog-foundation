<?php

namespace OTGS\Toolset\CRED\Controller\Upgrade;

/**
 * Upgrade database to 2040000 (Forms 2.4)
 *
 * Batch set default values for post and user forms settings about:
 * - setting for native media manager on media fields, keeping backwards compatibility:
 *    - post and user forms follow the legacy cred_file_upload_disable_progress_bar filter
 *    - relationship forms force not to use the native media manager
 *
 * @since 2.4
 */
class Routine2040000DbUpgrade implements IRoutine {

	/**
	 * @var \OTGS\Toolset\CRED\Cache\Model\Forms\Factory
	 */
	private $cache_factory;

	/**
	 * @var bool|int
	 */
	private $has_media_manager = 0;

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\CRED\Model\Cache\Forms\Factory $cache_factory
	 */
	public function __construct( \OTGS\Toolset\CRED\Model\Cache\Forms\Factory $cache_factory ) {
		$this->cache_factory = $cache_factory;
	}

	/**
	 * Execute database upgrade up to 2.4
	 *
	 * @param array $args
	 * @since 2.4
	 */
	public function execute_routine( $args = array() ) {
		$this->calculate_settings_values();
		$this->execute_post_forms_routine();
		$this->execute_user_forms_routine();
		$this->execute_relationship_forms_routine();
	}

	/**
	 * Execute database upgrade up to 2.4 for post forms.
	 *
	 * @since 2.4
	 */
	private function execute_post_forms_routine() {
		if ( ! $post_forms_caching = $this->cache_factory->create_by_domain( \CRED_Form_Domain::POSTS ) ) {
			return;
		}

		$post_forms = $post_forms_caching->get_transient();

		if ( false === $post_forms ) {
			$post_forms = $post_forms_caching->generate_transient();
		}

		$this->set_post_forms_settings_values( toolset_getarr( $post_forms, 'new', array() ) );
		$this->set_post_forms_settings_values( toolset_getarr( $post_forms, 'edit', array() ) );
		$post_forms_caching->delete_transient();
	}

	/**
	 * Execute database upgrade up to 2.4 for user forms.
	 *
	 * @since 2.4
	 */
	private function execute_user_forms_routine() {
		if ( ! $user_forms_caching = $this->cache_factory->create_by_domain( \CRED_Form_Domain::USERS ) ) {
			return;
		}

		$user_forms = $user_forms_caching->get_transient();

		if ( false === $user_forms ) {
			$user_forms = $user_forms_caching->generate_transient();
		}

		$this->set_user_forms_settings_values( toolset_getarr( $user_forms, 'new', array() ) );
		$this->set_user_forms_settings_values( toolset_getarr( $user_forms, 'edit', array() ) );
		$user_forms_caching->delete_transient();
	}

	/**
	 * Execute database upgrade up to 2.4 for relationship forms.
	 *
	 * @since 2.4
	 */
	private function execute_relationship_forms_routine() {
		if ( ! $relationship_forms_caching = $this->cache_factory->create_by_domain( \CRED_Form_Domain::ASSOCIATIONS ) ) {
			return;
		}

		$relationship_forms = $relationship_forms_caching->get_transient();

		if ( false === $relationship_forms ) {
			$relationship_forms = $relationship_forms_caching->generate_transient();
		}

		foreach ( $relationship_forms as $form_candidate ) {
			$form_settings = get_post_meta( $form_candidate->ID, 'form_settings', true );

			if ( empty( $form_settings ) ) {
				$form_settings = array();
			}

			$form_settings['has_media_button'] = 1;
			$form_settings['has_toolset_buttons'] = 1;
			$form_settings['has_media_manager'] = $this->has_media_manager;

			update_post_meta( $form_candidate->ID, 'form_settings', $form_settings, false /* $unique */ );
		}

		$relationship_forms_caching->delete_transient();
	}

	/**
	 * Get a safe version of the settings of a form.
	 *
	 * @param object $form
	 * @return object
	 * @since 2.4
	 */
	private function get_form_settings( $form ) {
		$form_settings = get_post_meta( $form->ID, '_cred_form_settings', true );

		if ( ! is_object( $form_settings ) ) {
			$form_settings = new \stdClass();
		}
		if ( ! property_exists( $form_settings, 'form' ) ) {
			$form_settings->form = array();
		}

		return $form_settings;
	}

	/**
	 * Decide default values on the upgrade routine.
	 *
	 * @since 2.4
	 */
	private function calculate_settings_values() {
		$this->has_media_manager = ( apply_filters( 'cred_file_upload_disable_progress_bar', false ) )
			? 0
			: 1;
	}

	/**
	 * Set the right default values for post forms settings.
	 *
	 * @param array $forms
	 * @since 2.4
	 */
	private function set_post_forms_settings_values( $forms = array() ) {
		foreach ( $forms as $form_candidate ) {
			$form_settings = $this->get_form_settings( $form_candidate );

			$form_settings_to_save = $form_settings->form;
			$form_settings_to_save['has_media_manager'] = $this->has_media_manager;
			$form_settings->form = $form_settings_to_save;

			update_post_meta( $form_candidate->ID, '_cred_form_settings', $form_settings, false /* $unique */ );
		}
	}

	/**
	 * Set the right default values for user forms settings.
	 *
	 * @param array $forms
	 * @since 2.4
	 */
	private function set_user_forms_settings_values( $forms = array() ) {
		foreach ( $forms as $form_candidate ) {
			$form_settings = $this->get_form_settings( $form_candidate );

			$form_settings_to_save = $form_settings->form;
			$form_settings_to_save['has_media_manager'] = $this->has_media_manager;
			$form_settings->form = $form_settings_to_save;

			update_post_meta( $form_candidate->ID, '_cred_form_settings', $form_settings, false /* $unique */ );
		}
	}

}
