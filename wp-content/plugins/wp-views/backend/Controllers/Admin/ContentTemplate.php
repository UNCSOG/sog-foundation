<?php

namespace OTGS\Toolset\Views\Controllers\Admin;

use OTGS\Toolset\Views\Controllers\HookControllerInterface;
use OTGS\Toolset\Views\Services\ContentTemplateService;

/**
 * Responsible to register all necessary actions and filters for the new Content Template logic.
 *
 * @package OTGS\Toolset\Views\Controllers\Admin
 */
class ContentTemplate implements HookControllerInterface {

	/**
	 * @var ContentTemplateService
	 */
	protected $content_template_service;

	/**
	 * ContentTemplate constructor.
	 *
	 * @param ContentTemplateService $content_template_service
	 */
	public function __construct( ContentTemplateService $content_template_service ) {
		$this->content_template_service = $content_template_service;
	}


	/**
	 * Registers the Content Template Usage post meta
	 */
	public function register_hooks() {
		$this->register_meta();

		add_action( 'update_postmeta', array( $this, 'save_content_template_meta' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'save_content_template_meta' ), 10, 4 );

		add_action( 'update_postmeta', array( $this, 'cleanup_post_meta_content_template' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'cleanup_post_meta_content_template' ), 10, 4 );
		add_action( 'transition_post_status', array( $this, 'save_assingments_on_status_transition' ), 10, 3 );

		// This action ensures that the Content Template Meta for Conditions is in sync with the actual settings values.
		add_action( 'wpv_action_content_template_edit', array( $this, 'migrate_content_template_meta' ) );

		add_filter( 'wpv_content_template_for_post', array( $this, 'get_content_template_for_post' ), 10, 2 );

		add_filter( 'wp_insert_post', array( $this, 'set_default_content_template_meta' ), 10, 3 );
		add_filter( 'replace_editor', array( $this, 'handle_classic_editor_for_new_posts' ), 10, 2 );
	}

	/**
	 * Automatically deletes the '_views_template' post meta when the value is an empty string.
	 *
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param string $meta_value
	 */
	public function cleanup_post_meta_content_template( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( ContentTemplateService::META_KEY_CUSTOM_TEMPLATE !== $meta_key ) {
			return;
		}
		if ( '' === $meta_value ) {
			delete_post_meta( $object_id, $meta_key, $meta_value );
		}
	}


	/**
	 * Saves proper settings for CT Usages, when transitioning the CT from 'trash' to 'publish' and vice-versa.
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param \WP_Post $post
	 */
	public function save_assingments_on_status_transition( $new_status, $old_status, $post ) {
		if ( ContentTemplateService::POST_TYPE !== $post->post_type ) {
			return;
		}
		if ( in_array( $new_status, [ 'trash', 'draft' ], true ) && 'publish' === $old_status ) {
			$this->content_template_service->remove_content_template_settings( $post->ID );
		}
		if ( 'publish' === $new_status && in_array( $old_status, [ 'trash', 'draft' ], true ) ) {
			$this->content_template_service->publish_content_template_settings( $post->ID );
		}
	}

	/**
	 * Ensure the usages present prior to the Blocks 1.5 are shown in the CT editor.
	 *
	 * @param int $content_template_id
	 */
	public function migrate_content_template_meta( $content_template_id ) {
		$this->content_template_service->migrate_content_template( intval( $content_template_id ) );
	}

	/**
	 * Gets the content template assigned to that post.
	 *
	 * @param int $default_content_template_id
	 * @param \WP_Post $post
	 *
	 * @return int
	 */
	public function get_content_template_for_post( $default_content_template_id, $post ) {
		if ( $post instanceof \WP_Post ) {
			return $this->content_template_service->get_template_for( $post );
		}
		return $default_content_template_id;
	}

	/**
	 * Parse and save the Content Template settings from the postmeta changes.
	 *
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param string $meta_value
	 */
	public function save_content_template_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'usage' === $meta_key ) {
			$this->save_content_template_usage( $object_id, $meta_value );
		}
		if ( 'usage_priority' === $meta_key ) {
			$this->content_template_service->set_priority_settings( $object_id, $meta_value );
		}
	}


	/**
	 * Sets the default meta to the Content Template post when created.
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 * @param boolean $update
	 */
	public function set_default_content_template_meta( $post_id, \WP_Post $post, $update ) {
		$this->content_template_service->set_default_meta( $post );
	}

	/**
	 * Handles the creation of new Content Template specially for classic editor.
	 *
	 * @param int $value
	 * @param \WP_Post $post
	 *
	 * @return mixed|null
	 */
	public function handle_classic_editor_for_new_posts( $value, $post ) {
		if ( ContentTemplateService::POST_TYPE !== $post->post_type ) {
			return $value;
		}

		$selected_editor = get_post_meta( $post->ID, \WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE, true );
		if (
			! in_array(
				$selected_editor,
				[
					\Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID,
					\Toolset_User_Editors_Editor_Avada::AVADA_SCREEN_ID,
					\Toolset_User_Editors_Editor_Beaver::BEAVER_SCREEN_ID,
					\Toolset_User_Editors_Editor_Divi::DIVI_SCREEN_ID,
					\Toolset_User_Editors_Editor_Layouts::LAYOUTS_SCREEN_ID,
				],
				true
			)
		) {
			$url = sprintf( '%s&ct_id=%s', admin_url( 'admin.php?page=ct-editor' ), $post->ID );
			wp_redirect( $url );
		}

		return $value;
	}

	/**
	 * Write settings for the content template usage that was saved.
	 *
	 * @param int $object_id
	 * @param string $meta_value
	 */
	private function save_content_template_usage( $object_id, $meta_value ) {
		$usage = maybe_unserialize( $meta_value );

		if ( $usage && count( $usage ) > 0 ) {
			foreach ( $usage as $usage_for_post_type ) {
				$target_post_type = toolset_getarr( $usage_for_post_type, 'post_type', '' );
				$enabled = toolset_getarr( $usage_for_post_type, 'enabled', false );
				$conditions = toolset_getarr( $usage_for_post_type, 'conditions', array() );
				$parsed_conditions = toolset_getarr( $usage_for_post_type, 'parsed_conditions', '' );
				$priority = get_post_meta( $object_id, 'usage_priority', true );

				$this->content_template_service->set_usage_settings( $target_post_type, $object_id, $enabled, $conditions, $parsed_conditions, $priority, time() );
			}
		}
	}


	/**
	 * Registers the meta attributes used by Content Templates.
	 */
	protected function register_meta() {
		register_post_meta(
			'view-template',
			'usage',
			array(
				'object_subtype' => 'view-template',
				'single' => true,
				'type' => 'object',
				'show_in_rest' => array(
					'schema' => array(
						'type' => 'array',
						'additionalProperties' => true,
					),
				),
			)
		);

		register_post_meta(
			'view-template',
			'usage_priority',
			array(
				'object_subtype' => 'view-template',
				'single' => true,
				'type' => 'integer',
				'show_in_rest' => true,
			)
		);
	}
}
