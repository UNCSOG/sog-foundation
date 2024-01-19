<?php

namespace OTGS\Toolset\CRED\Controller\Compatibility\EditorBlocks;

/**
 * Handles the creation of the Toolset Toolset Form Gutenberg block to allow Toolset Form embedding inside the Gutenberg editor.
 *
 * @package OTGS\Toolset\CRED\Controller\EditorBlocks
 * @since 2.3
 */
class Form extends \Toolset_Gutenberg_Block {

	const BLOCK_NAME = 'toolset/cred-form';

	/**
	 * Inits WordPress hooks
	 *
	 * @since 2.3
	 */
	public function init_hooks() {
		add_action( 'init', array( $this, 'register_block_editor_assets' ) );

		add_action( 'init', array( $this, 'register_block_type' ) );

		// Hook scripts function into block editor hook.
		add_action( 'enqueue_block_editor_assets', array( $this, 'blocks_editor_scripts' ) );
	}

	/**
	 * Register the needed assets for the Toolset Gutenberg blocks
	 *
	 * @since 2.3
	 */
	public function register_block_editor_assets() {

		if ( $this->cred_active->is_met() ) {
			$cred_ajax = \CRED_Ajax::get_instance();
			$cred_forms_posts_domain = \CRED_Form_Domain::POSTS;
			$cred_forms_users_domain = \CRED_Form_Domain::USERS;
			$cred_forms_relationships_domain = \CRED_Form_Domain::ASSOCIATIONS;

			wp_localize_script(
				\CRED_Asset_Manager::EDITOR_BLOCK_FORM_JS,
				'cred_form_block_strings',
				array(
					'isCREDActive' => $this->cred_active->is_met(),
					'blockCategory' => \Toolset_Blocks::TOOLSET_GUTENBERG_BLOCKS_CATEGORY_SLUG,
					'blockName' => self::BLOCK_NAME,
					'publishedForms' => array(
						'postForms' => apply_filters( 'cred_get_available_forms', array(), $cred_forms_posts_domain ),
						'userForms' => apply_filters( 'cred_get_available_forms', array(), $cred_forms_users_domain ),
						'relationshipForms' => array(
							'new' => apply_filters( 'cred_get_available_forms', array(), $cred_forms_relationships_domain ),
						),
					),
					'wpnonce' => wp_create_nonce( \CRED_Ajax::CALLBACK_GET_CRED_FORM_BLOCK_PREVIEW ),
					'association' => array(
						'action' => $cred_ajax->get_action_js_name( \CRED_Ajax::CALLBACK_GET_ASSOCIATION_FORM_DATA ),
						'wpnonce' => wp_create_nonce( $this->constants->constant( 'CRED_Ajax::CALLBACK_GET_ASSOCIATION_FORM_DATA' ) ),
					),
					'actionName' => $cred_ajax->get_action_js_name( \CRED_Ajax::CALLBACK_GET_CRED_FORM_BLOCK_PREVIEW ),
				)
			);
		}
	}

	/**
	 * Register block type. We can use this method to register the editor & frontend scripts as well as the render callback.
	 *
	 * @note For now the scripts registration is disabled as it creates console errors on the classic editor.
	 *
	 * @since 2.3
	 */
	public function register_block_type() {
		register_block_type(
			self::BLOCK_NAME,
			array(
				'attributes' => array(
					'form' => array(
						'type' => 'string',
						'default' => '',
					),
					'formType' => array(
						'type' => 'string',
						'default' => '',
					),
					'formAction' => array(
						'type' => 'string',
						'default' => '',
					),
					'postToEdit' => array(
						'type' => 'string',
						'default' => 'current',
					),
					'anotherPostToEdit' => array(
						'type' => 'object',
						'default' => '',
					),
					'userToEdit' => array(
						'type' => 'string',
						'default' => 'current',
					),
					'anotherUserToEdit' => array(
						'type' => 'object',
						'default' => '',
					),
					'relationshipFormType' => array(
						'type' => 'string',
						'default' => 'create',
					),
					'relationshipParentItem' => array(
						'type' => 'string',
						'default' => '$current',
					),
					'relationshipChildItem' => array(
						'type' => 'string',
						'default' => '$current',
					),
					'relationshipParentItemSpecific' => array(
						'type' => 'object',
						'default' => '',
					),
					'relationshipChildItemSpecific' => array(
						'type' => 'object',
						'default' => '',
					),
				),
				'editor_script' => \CRED_Asset_Manager::EDITOR_BLOCK_FORM_JS, // Editor script.
				'editor_style' => \CRED_Asset_Manager::EDITOR_BLOCK_FORM_CSS, // Editor style.
			)
		);
	}

	/**
	 * Enqueue assets, needed on the editor side, for the Toolset Gutenberg blocks
	 *
	 * @since 2.3
	 */
	public function blocks_editor_scripts() {
		do_action( 'toolset_enqueue_styles', array( 'toolset-blocks-react-select-css' ) );
	}
}
