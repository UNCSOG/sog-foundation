<?php
namespace OTGS\Toolset\Views\Controller\Compatibility\EditorBlocks\ContentTemplate;

use \OTGS\Toolset\Views\Controller\Compatibility\EditorBlocks as ViewsEditorBlocks;

/**
 * Handles the creation of the Toolset Content Template editor (Gutenberg) block to allow Content Template embedding inside the editor.
 *
 * @since 2.6.0
 * @since 2.7.0 Moved here from Toolset Common.
 */
class Block extends ViewsEditorBlocks\Base {
	const BLOCK_NAME = 'toolset/ct';

	/**
	 * Initializes the hooks for the Content Template block.
	 */
	public function init_hooks() {
		add_action( 'init', array( $this, 'register_block_editor_assets' ) );

		add_action( 'init', array( $this, 'register_block_type' ) );
	}

	/**
	 * Register the needed assets for the Toolset Content Template editor (Gutenberg) block.
	 */
	public function register_block_editor_assets() {
		$this->toolset_assets_manager->register_script(
			'toolset-ct-block-js',
			$this->constants->constant( 'WPV_URL' ) . '/public/js/ct.block.js',
			array( 'wp-editor' ),
			$this->constants->constant( 'WPV_VERSION' )
		);

		$locale = null;
		if ( function_exists( 'wp_get_jed_locale_data' ) ) {
			$locale = wp_get_jed_locale_data( 'wpv-views' );
		} elseif ( function_exists( 'gutenberg_get_jed_locale_data' ) ) {
			$locale = gutenberg_get_jed_locale_data( 'wpv-views' );
		} else {
			$locale = array(
				array(
					'domain' => 'wpv-views',
					'lang' => 'en_US',
				),
			);
		}

		wp_localize_script(
			'toolset-ct-block-js',
			'toolset_ct_block_strings',
			array(
				'blockName' => self::BLOCK_NAME,
				'blockCategory' => \Toolset_Blocks::TOOLSET_GUTENBERG_BLOCKS_CATEGORY_SLUG,
				'publishedCTs' => $this->get_available_cts(),
				'wpnonce' => wp_create_nonce( \WPV_Ajax::CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW ),
				'actionName' => $this->toolset_ajax_manager->get_action_js_name( \WPV_Ajax::CALLBACK_GET_CONTENT_TEMPLATE_BLOCK_PREVIEW ),
				'locale' => $locale,
			)
		);

		$this->toolset_assets_manager->register_style(
			'toolset-ct-block-editor-css',
			$this->constants->constant( 'WPV_URL' ) . '/public/css/ct.block.css',
			array(),
			$this->constants->constant( 'WPV_VERSION' )
		);
	}

	/**
	 * Register block type. We can use this method to register the editor & frontend scripts as well as the render callback.
	 *
	 * @note For now the scripts registration is disabled as it creates console errors on the classic editor.
	 */
	public function register_block_type() {
		register_block_type(
			self::BLOCK_NAME,
			array(
				'attributes' => array(
					'ct' => array(
						'type' => 'string',
						'default' => '',
					),
					'align' => array(
						'type' => 'string',
						'default' => '',
					),
				),
				'editor_script' => 'toolset-ct-block-js', // Editor script.
				'editor_style' => 'toolset-ct-block-editor-css', // Editor style.
			)
		);
	}

	/**
	 * Retrieve the published Content Templates
	 *
	 * @return array|mixed
	 */
	public function get_available_cts() {
		global $pagenow;
		$ct_objects = apply_filters( 'wpv_get_available_content_templates', array() );

		if ( ! $ct_objects ) {
			$ct_objects = array();
		}

		$values_to_exclude = array();

		// Exclude current Content Template.
		$action = toolset_getget( 'action', null );
		$action = null === $action ? toolset_getpost( 'action', null ) : $action;

		$post_id = (int) toolset_getget( 'post', 0 );
		$post_id = ( 0 === $post_id ? (int) toolset_getpost( 'post_ID', 0 ) : $post_id );

		$post = get_post( $post_id );
		if (
			'post.php' === $pagenow
			&& ( 'edit' === $action || 'editpost' === $action )
			&& null !== $post
			&& \WPV_Content_Template_Embedded::POST_TYPE === $post->post_type
		) {
			$values_to_exclude[] = $post_id;
		}

		// Exclude all Loop Templates.
		$exclude_loop_templates_ids = wpv_get_loop_content_template_ids();
		if ( count( $exclude_loop_templates_ids ) > 0 ) {
			$exclude_loop_templates_ids_sanitized = array_map( 'esc_attr', $exclude_loop_templates_ids );
			$exclude_loop_templates_ids_sanitized = array_map( 'trim', $exclude_loop_templates_ids_sanitized );
			// is_numeric + intval does sanitization.
			$exclude_loop_templates_ids_sanitized = array_filter( $exclude_loop_templates_ids_sanitized, 'is_numeric' );
			$exclude_loop_templates_ids_sanitized = array_map( 'intval', $exclude_loop_templates_ids_sanitized );
			if ( count( $exclude_loop_templates_ids_sanitized ) > 0 ) {
				$values_to_exclude = array_merge( $values_to_exclude, $exclude_loop_templates_ids_sanitized );
			}
		}

		return array_filter(
			$ct_objects,
			function( $ct ) use ( $values_to_exclude ) {
				return ! in_array( intval( $ct->ID ), $values_to_exclude, true );
			}
		);
	}
}
