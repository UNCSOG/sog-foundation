<?php

namespace OTGS\Toolset\CRED\Controller\Upgrade;

use \OTGS\Toolset\CRED\Controller\EditorOrigin;

/**
 * Upgrade database to 2010200 (Forms 2.3.5)
 *
 * Batch set default values for post and user forms settings about:
 * - hide comments
 * - include Add Media buttons on frontend editors
 * - include Toolset buttons on frontend editors
 *
 * @since 2.1.2
 */
class Routine2030500DbUpgrade implements IRoutine {

	/**
	 * @var \OTGS\Toolset\CRED\Cache\Model\Forms\Factory
	 */
	private $cache_factory;

	public function __construct( \OTGS\Toolset\CRED\Model\Cache\Forms\Factory $cache_factory ) {
		$this->cache_factory = $cache_factory;
	}

	/**
	 * Execute database upgrade up to 2.3.5
	 *
	 * @since 2.1.2
	 */
	public function execute_routine( $args = array() ) {
		$this->execute_post_forms_routine();
		$this->execute_user_forms_routine();
		$this->execute_relationship_forms_routine();
	}

	/**
	 * Execute database upgrade up to 2.3.5 for post forms.
	 *
	 * @since 2.3.5
	 */
	private function execute_post_forms_routine() {
		if ( ! $post_forms_caching = $this->cache_factory->create_by_domain( \CRED_Form_Domain::POSTS ) ) {
			return;
		}

		$post_forms = $post_forms_caching->get_transient();

		if ( false === $post_forms ) {
			$post_forms = $post_forms_caching->generate_transient();
		}

		$this->set_default_editor_origin_for_forms( toolset_getarr( $post_forms, 'new', array() ) );
		$this->set_default_editor_origin_for_forms( toolset_getarr( $post_forms, 'edit', array() ) );

		$post_forms_caching->delete_transient();
	}

	/**
	 * Execute database upgrade up to 2.3.5 for user forms.
	 *
	 * @since 2.3.5
	 */
	private function execute_user_forms_routine() {
		if ( ! $user_forms_caching = $this->cache_factory->create_by_domain( \CRED_Form_Domain::USERS ) ) {
			return;
		}

		$user_forms = $user_forms_caching->get_transient();

		if ( false === $user_forms ) {
			$user_forms = $user_forms_caching->generate_transient();
		}

		$this->set_default_editor_origin_for_forms( toolset_getarr( $user_forms, 'new', array() ) );
		$this->set_default_editor_origin_for_forms( toolset_getarr( $user_forms, 'edit', array() ) );

		$user_forms_caching->delete_transient();
	}

	/**
	 * Set the default value for the editor_origin extra setting on post and user forms, if needed.
	 *
	 * @param object[] $forms
	 * @since 2.3.5
	 */
	private function set_default_editor_origin_for_forms( $forms ) {
		foreach ( $forms as $form ) {
			$form_extra_settings = get_post_meta( $form->ID, '_cred_extra', true );
			$form_extra_settings = is_object( $form_extra_settings ) ? $form_extra_settings : new \stdClass();

			if (
				! isset( $form_extra_settings->editor_origin )
				|| empty( $form_extra_settings->editor_origin )
			) {
				$form_extra_settings->editor_origin = EditorOrigin::HTML;
				update_post_meta( $form->ID, '_cred_extra', $form_extra_settings );
			}
		}
	}

	/**
	 * Execute database upgrade up to 2.3.5 for relationship forms.
	 *
	 * @since 2.3.5
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
			$editor_origin = get_post_meta( $form_candidate->ID, 'editor_origin', true );

			if ( empty( $editor_origin ) ) {
				update_post_meta( $form_candidate->ID, 'editor_origin', EditorOrigin::HTML, false /* $unique */ );
			}
		}

		$relationship_forms_caching->delete_transient();
	}


}
