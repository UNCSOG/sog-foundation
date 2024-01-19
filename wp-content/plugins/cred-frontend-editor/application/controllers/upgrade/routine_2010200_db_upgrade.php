<?php

namespace OTGS\Toolset\CRED\Controller\Upgrade;

/**
 * Upgrade database to 2010200 (Forms 2.1.2)
 *
 * Batch set default values for post and user forms settings about:
 * - hide comments
 * - include Add Media buttons on frontend editors
 * - include Toolset buttons on frontend editors
 *
 * @since 2.1.2
 */
class Routine2010200DbUpgrade implements IRoutine {

	/**
	 * @var \OTGS\Toolset\CRED\Cache\Model\Forms\Factory
	 */
	private $cache_factory;

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\CRED\Model\Cache\Forms\Factory $cache_factory
	 */
	public function __construct( \OTGS\Toolset\CRED\Model\Cache\Forms\Factory $cache_factory ) {
		$this->cache_factory = $cache_factory;
	}

	/**
	 * Execute database upgrade up to 2.1.2
	 *
	 * @param array $args
	 * @since 2.1.2
	 */
	public function execute_routine( $args = array() ) {
		$this->execute_post_forms_routine();
		$this->execute_user_forms_routine();
	}

	/**
	 * Execute database upgrade up to 2.1.2 for post forms.
	 *
	 * @since 2.1.2
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
	 * Execute database upgrade up to 2.1.2 for user forms.
	 *
	 * @since 2.1.2
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
	 * Get a safe version of the settings of a form.
	 *
	 * @param object $form
	 * @return object
	 * @since 2.1.2
	 */
	private function get_form_settings( $form ) {
		$form_settings = get_post_meta( $form->ID, '_cred_form_settings', true );

		if ( ! is_object( $form_settings ) )  {
			$form_settings = new \stdClass();
		}
		if ( ! property_exists( $form_settings, 'form' ) ) {
			$form_settings->form = array();
		}

		return $form_settings;
	}

	/**
	 * Set the right default values for post forms settings.
	 *
	 * @param array $forms
	 * @since 2.1.2
	 */
	private function set_post_forms_settings_values( $forms = array() ) {
		foreach ( $forms as $form_candidate ) {
			$form_settings = $this->get_form_settings( $form_candidate );

			$form_settings_to_save = $form_settings->form;
			$form_settings_to_save['hide_comments'] = toolset_getarr( $form_settings_to_save, 'hide_comments', 0 );
			$form_settings_to_save['has_media_button'] = toolset_getarr( $form_settings_to_save, 'has_media_button', 0 );
			$form_settings_to_save['has_toolset_buttons'] = $form_settings_to_save['has_media_button'];

			$form_settings->form = $form_settings_to_save;

			update_post_meta( $form_candidate->ID, '_cred_form_settings', $form_settings, false /* $unique */ );
		}
	}

	/**
	 * Set the right default values for user forms settings.
	 *
	 * @param array $forms
	 * @since 2.1.2
	 */
	private function set_user_forms_settings_values( $forms = array() ) {
		foreach ( $forms as $form_candidate ) {
			$form_settings = $this->get_form_settings( $form_candidate );

			$form_settings_to_save = $form_settings->form;
			$form_settings_to_save['hide_comments'] = 1;
			$form_settings_to_save['has_media_button'] = 1;
			$form_settings_to_save['has_toolset_buttons'] = 1;

			$form_settings->form = $form_settings_to_save;

			update_post_meta( $form_candidate->ID, '_cred_form_settings', $form_settings, false /* $unique */ );
		}
	}

}
