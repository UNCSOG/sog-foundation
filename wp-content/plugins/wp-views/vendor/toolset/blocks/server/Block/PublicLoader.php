<?php

namespace ToolsetBlocks\Block;

use Toolset\DynamicSources\Utils\Toolset as ToolsetUtils;

/**
 * "Toolset Blocks" plugin's main class.
 *
 * @package toolset-blocks
 */
class PublicLoader {
	const TOOLSET_BLOCKS_CATEGORY_SLUG = 'toolset';
	const TOOLSET_BLOCKS_BLOCK_NAMESPACE = 'toolset-blocks';
	const TOOLSET_BLOCKS_BLOCK_EDITOR_JS_HANDLE = 'toolset_blocks-block-js';
	const TOOLSET_BLOCKS_BLOCK_EDITOR_CSS_HANDLE = 'toolset_blocks-block-editor-css';
	const TOOLSET_BLOCKS_BLOCK_CSS_HANDLE = 'toolset_blocks-style-css';
	const GLIDE_JS_HANDLE = 'toolset_blocks-glide-js';
	const GLIDE_CSS_HANDLE = 'toolset_blocks-glide-css';

	/**
	 * Add the necessary hooks for the plugin initialization.
	 */
	public function initialize() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.7.2', '<=' ) ) {
			add_filter( 'block_categories', array( $this, 'register_toolset_blocks_category' ), 20 );
		} else {
			add_filter( 'block_categories_all', array( $this, 'register_toolset_blocks_category' ), 20 );
		}

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		add_filter( 'wpml_update_strings_in_block', [ $this, 'wpml_update_strings_in_block_filter' ], 10, 3 );
	}

	/**
	 * Register the Toolset blocks category.
	 *
	 * @param array $categories The array with the categories of the Gutenberg widgets.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function register_toolset_blocks_category( $categories ) {
		if ( ! array_search( 'toolset', array_column( $categories, 'slug' ) ) ) {
			$categories = array_merge(
				$categories,
				array(
					array(
						'slug' => self::TOOLSET_BLOCKS_CATEGORY_SLUG,
						'title' => 'Toolset',
					),
				)
			);
		}

		return $categories;
	}

	/**
	 * Enqueue Gutenberg block assets for backend editor.
	 *
	 * @uses {wp-blocks} for block type registration & related functions.
	 * @uses {wp-element} for WP Element abstraction — structure of blocks.
	 * @uses {wp-i18n} to internationalize the block's text.
	 * @uses {wp-editor} for WP editor styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_block_editor_assets() {
		do_action( 'toolset/dynamic_sources/actions/register_dynamic_sources_localization_data' );

		// Editor Scripts.
		$script_dependencies = array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			'wp-editor',
			'wp-nux',
			'lodash',
			\Toolset\DynamicSources\DynamicSources::TOOLSET_DYNAMIC_SOURCES_SCRIPT_HANDLE,
			'codemirror-htmlmixed',
			'glide',
			'toolset-common-es',
		);

		// This is no longer needed when this bug is fixed:
		// https://github.com/webpack-contrib/mini-css-extract-plugin/issues/147
		$script_dependencies = $this->workaround_webpack4_bug( $script_dependencies );

		wp_enqueue_script(
			self::TOOLSET_BLOCKS_BLOCK_EDITOR_JS_HANDLE,
			TB_BUNDLED_SCRIPT_PATH . '/blocks.js',
			$script_dependencies,
			TB_VER,
			true // Enqueue the script in the footer.
		);

		$toolset_utils = new ToolsetUtils();

		// The Views allowed functions for conditionals need to be passed through "array_values" for the following reason.
		// Whenever a new custom function is allowed to be used in conditionals, it's added to an array. Once a function is deleted,
		// it's removed from this array but the array keys are not rearranged, causing a structure similar to:
		//
		// array(
		// 0 => 'lorem',
		// 3 => 'dolor',
		// 7 => 'sit',
		// )
		//
		// When this array is sent to the client side through script localization, since JS won't see sequential keys
		// this will be automatically converted into an object instead of an array.
		// In the Conditional block, Array prototype functions are used for this variable, which causes console errors.
		//
		// The same thing applies to the allowed shortcodes for shortcode attributes
		$views_allowed_functions = array_values( $this->get_views_allowed_functions() );
		$views_allowed_shortcodes = array_values( $this->get_views_allowed_shortcodes() );

		$localization_array = array(
			'namespace' => self::TOOLSET_BLOCKS_BLOCK_NAMESPACE,
			'category' => self::TOOLSET_BLOCKS_CATEGORY_SLUG,
			'themeColors' => get_theme_support( 'editor-color-palette' ),
			'extra' => [
				'dashiconsURL' => home_url( 'wp-includes/css/dashicons.css' ),
				'isViewsEnabled' => $toolset_utils->is_views_enabled(),
				'isTypesEnabled' => $toolset_utils->is_types_enabled(),
				'listRoles' => $this->get_list_roles(),
				'viewsAllowedFunctions' => $views_allowed_functions,
				'viewsAllowedShortcodes' => $views_allowed_shortcodes,
			],
		);

		$localization_array = apply_filters( 'toolset_blocks/filters/localize', $localization_array );

		wp_localize_script(
			self::TOOLSET_BLOCKS_BLOCK_EDITOR_JS_HANDLE,
			'toolsetBlocksStrings',
			$localization_array
		);

		wp_set_script_translations( self::TOOLSET_BLOCKS_BLOCK_EDITOR_JS_HANDLE, 'toolset-blocks', TB_PATH . '/languages/' );

		// Style
		if ( ! TB_HMR_RUNNING ) {
			// only load css when hmr is NOT active, otherwise it's included in the js
			wp_enqueue_style(
				self::TOOLSET_BLOCKS_BLOCK_EDITOR_CSS_HANDLE,
				TB_URL . 'public/css/edit.css',
				array(
					'wp-editor',
					'wp-edit-blocks',
					'codemirror',
					'glide',
					'toolset-common-es',
				),
				TB_VER
			);
		} else {
			// if HMR is loaded we still need to load Toolset Common Es style
			wp_enqueue_style( 'toolset-common-es' );
			wp_enqueue_style( 'codemirror' );
		}

		// Glide.js Gallery library
		wp_enqueue_script(
			self::GLIDE_JS_HANDLE,
			TB_URL . 'public/vendor/glide/glide.min.js',
			[],
			TB_VER
		);
		wp_enqueue_style(
			self::GLIDE_CSS_HANDLE,
			TB_URL . 'public/vendor/glide/glide.min.css',
			[],
			TB_VER
		);
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param array                  $string_translations
	 * @param string                 $lang
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public function wpml_update_strings_in_block_filter( $block, $string_translations, $lang ) {
		switch ( $block->blockName ) {
			case 'toolset-blocks/fields-and-text':
				if ( strpos( $block->innerHTML, '<p' ) === false ) {
					// The translation is pure text. (Not happening with ATE - Mai 2020)
					$block->innerHTML = wpautop( $block->innerHTML );
				}
				break;
			default:
				break;
		}

		return $block;
	}

	/**
	 * Workaround for Webpack4 issue
	 * https://github.com/webpack-contrib/mini-css-extract-plugin/issues/147
	 *
	 * Once issue is fixed we can also remove /public/js/edit.js and
	 * /public/js/style.js from our repo.
	 */
	private function workaround_webpack4_bug( $script_dependencies ) {
		if ( TB_HMR_RUNNING ) {
				// not needed when hmr is active
				return $script_dependencies;
		}

		if ( file_exists( TB_PATH . '/public/js/edit.js' ) ) {
			wp_register_script(
				'toolset_blocks-block-edit-js',
				TB_BUNDLED_SCRIPT_PATH . '/edit.js',
				array(),
				TB_VER
			);

			$script_dependencies[] = 'toolset_blocks-block-edit-js';
		}

		if ( file_exists( TB_PATH . '/public/js/edit.js' ) ) {
			wp_register_script(
				'toolset_blocks-block-style-js',
				TB_BUNDLED_SCRIPT_PATH . '/style.js',
				array(),
				TB_VER
			);

			$script_dependencies[] = 'toolset_blocks-block-style-js';
		}

		return $script_dependencies;
	}

	/**
	 * Gets the list of available roles
	 *
	 * @return array
	 */
	private function get_list_roles() {
		if ( ! function_exists( 'get_editable_roles' ) ) {
			return [];
		}
		$result = [
			[
				'value' => '',
				'label' => __( 'Select a role', 'wpv-views' ),
			],
		];
		foreach ( get_editable_roles() as $role_name => $role_info ) {
			$result[] = [
				'value' => $role_name,
				'label' => $role_info['name'],
			];
		}

		return $result;
	}

	/**
	 * Returns the list of allowed functions for `wpv-conditional` shortcode
	 *
	 * @return array
	 */
	private function get_views_allowed_functions() {
		global $WPV_settings;
		if ( ! $WPV_settings ) {
			return [];
		}
		return apply_filters( 'wpv_filter_wpv_custom_conditional_functions', $WPV_settings->wpv_custom_conditional_functions );
	}

	/**
	 * Returns the list of allowed shortcodes for `wpv-conditional` shortcode
	 *
	 * @return array
	 */
	private function get_views_allowed_shortcodes() {
		global $WPV_settings;
		if ( ! $WPV_settings ) {
			return [];
		}
		return apply_filters( 'wpv_custom_inner_shortcodes', $WPV_settings->wpv_custom_inner_shortcodes );
	}
}
