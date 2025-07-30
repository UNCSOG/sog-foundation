<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\BlockPlugin;

/**
 * Handles the compatibility between Views and Kadence blocks.
 */
class KadenceCompatibility extends BlockPluginCompatibility {
	const BLOCK_NAMESPACE = 'kadence';

	// Block slugs
	// Free
	const ADVANCED_BUTTON_BLOCK_SLUG = 'advancedbtn';
	const ACCORDION_BLOCK_SLUG = 'accordion';
	const ADVANCED_HEADING_BLOCK_SLUG = 'advancedheading';
	const ICONLIST_BLOCK_SLUG = 'iconlist';
	const INFOBOX_BLOCK_SLUG = 'infobox';
	const TABS_BLOCK_SLUG = 'tabs';
	const FORM_BLOG_SLUG = 'form';
	const ADVANCED_GALLERY_BLOCK_SLUG = 'advancedgallery';
	const COLUMN_BLOCK_SLUG = 'column';
	const ROWLAYOUT_BLOCK_SLUG = 'rowlayout';
	//Pro
	const VIDEO_POPUP_BLOCK_SLUG = 'videopopup';
	const USER_INFO_BLOCK_SLUG = 'userinfo';
	const MODAL_BLOCK_SLUG = 'modal';

	// Block style slugs
	// Free
	const ADVANCED_BUTTON_STYLE_SLUG = 'btn';
	const ICONLIST_STYLE_SLUG = 'iconlist';
	const INFOBOX_STYLE_SLUG = 'infobox';
	const TABS_STYLE_SLUG = 'tabs';
	const FORM_STYLE_SLUG = 'form';
	const ADVANCED_GALLERY_STYLE_SLUG = 'gallery';
	// Pro
	const VIDEO_POPUP_STYLE_SLUG = 'videopopup';
	const ADVANCED_GALLERY_PRO_STYLE_SLUG = 'gallerypro';


	/**
	 * Frontend styles of blocks that are also needed in the editor.
	 *
	 * @var array
	 */
	const STYLES_FOR_BLOCKS = [
		// Free
		self::ADVANCED_BUTTON_BLOCK_SLUG => self::ADVANCED_BUTTON_STYLE_SLUG,
		self::ICONLIST_BLOCK_SLUG => self::ICONLIST_STYLE_SLUG,
		self::INFOBOX_BLOCK_SLUG => self::INFOBOX_STYLE_SLUG,
		self::TABS_BLOCK_SLUG => self::TABS_STYLE_SLUG,
		self::FORM_BLOG_SLUG => self::FORM_STYLE_SLUG,
		self::ADVANCED_GALLERY_BLOCK_SLUG => [ self::ADVANCED_GALLERY_STYLE_SLUG, self::ADVANCED_GALLERY_PRO_STYLE_SLUG ],
		// Pro
		self::VIDEO_POPUP_BLOCK_SLUG => self::VIDEO_POPUP_STYLE_SLUG,
	];

	/**
	 * The array of blocks not integrated with Automatic Dynamic Sources but need to pass through the compatibility mechanism
	 * to display properly in Views and WordPress Archives.
	 *
	 * @var array
	 */
	const BLOCKS_NOT_INTEGRATED_NEED_COMPATIBILITY = [
		// Free
		self::BLOCK_NAMESPACE . '/' . self::ACCORDION_BLOCK_SLUG,
	];

	/**
	 * The array of blocks, the printing of the styles of which needs to be prevented from happening in the head of the page.
	 *
	 * @var array
	 */
	const BLOCKS_NEED_PREVENTING_STYLE_IN_HEAD = [
		// Free
		self::ACCORDION_BLOCK_SLUG,
		self::ADVANCED_HEADING_BLOCK_SLUG,
		self::INFOBOX_BLOCK_SLUG,
		self::ROWLAYOUT_BLOCK_SLUG,
		self::COLUMN_BLOCK_SLUG,

		// Pro
		self::VIDEO_POPUP_BLOCK_SLUG,
		self::USER_INFO_BLOCK_SLUG,
		self::MODAL_BLOCK_SLUG,
	];

	/** @var \Toolset_Constants */
	private $constants;

	/**
	 * KadenceCompatibility constructor.
	 *
	 * @param \Toolset_Constants $constants
	 */
	public function __construct( \Toolset_Constants $constants ) {
		$this->constants = $constants;
	}

	/**
	 * Initializes the Kadence blocks integration.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Initializes the hooks for the Kadence integration.
	 */
	private function init_hooks() {
		// Adjusts the blocks' output and the relevant styles for those that have Dynamic Sources integrated, when inside a View.
		add_filter( 'kadence_blocks_frontend_build_css', array( $this, 'maybe_capture_block_id_for_css_generation' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'maybe_capture_block_id_for_css_generation_on_block_rendering' ), 10, 2 );

		add_filter( 'wpv_filter_view_loop_item_output', array( $this, 'adjust_classes_in_view_loop_item_and_generate_proper_styles' ), 10, 3 );
		add_filter( 'wpv_view_pre_do_blocks_view_layout_meta_html', array( $this, 'capture_view_template' ), 10, 2 );

		add_filter( 'kadence_blocks_render_inline_css', array( $this, 'prevent_rendering_inline_styles_in_head_for_blocks' ), 10, 3 );

		add_filter( 'wpv_filter_wpv_loop_before_post_content_replace', array( $this, 'filter_view_loop_content_for_including_frontend_styles_in_editor' ), 10, 3 );

		add_filter( 'kadence_blocks_force_render_inline_css_in_content', array( $this, 'force_inline_css_rendering_in_content' ) );
	}

	/**
	 * On block rendering, it adjusts the block ID for CSS generation by adding the post ID to the end of the unique block identifier.
	 *
	 * @param string $block_content
	 * @param array  $parsed_block
	 *
	 * @return mixed
	 */
	public function maybe_capture_block_id_for_css_generation_on_block_rendering( $block_content, $parsed_block ) {
		$this->maybe_capture_block_id_for_css_generation( $parsed_block );
		return $block_content;
	}


	/**
	 * Adjusts the block ID for CSS generation by adding the post ID to the end of the unique block identifier.
	 *
	 * @param array $block
	 *
	 * @return array
	 */
	public function maybe_capture_block_id_for_css_generation( $block ) {
		if (
			! isset( $block['blockName'] ) ||
			! isset( $block['attrs'] ) ||
			! is_array( $block['attrs'] )
		) {
			return $block;
		}

		$block_name = $block['blockName'];
		$block_attributes = $block['attrs'];

		if ( ! $this->is_block_from_compatible_plugin( $block_name ) ) {
			return $block;
		}

		$block_integration_info = apply_filters( 'toolset/dynamic_sources/filters/third_party_block_integration_info', array(), $block_name );

		if (
			(
				! $block_integration_info ||
				! array_key_exists( 'uniqueID', $block_attributes )
			) &&
			! in_array( $block_name, self::BLOCKS_NOT_INTEGRATED_NEED_COMPATIBILITY, true )
		) {
			return $block;
		}

		if ( ! in_array( $block_attributes['uniqueID'], $this->original_block_id_array, true ) ) {
			array_push( $this->original_block_id_array, $block_attributes['uniqueID'] );
		}

		return $block;
	}

	/**
	 * Adjusts the loop item output to have proper classes (modified to include the post ID) for the blocks' output as well
	 * as prepends the loop item output with the proper styles for the modified classes.
	 *
	 * @param string   $loop_item_output
	 * @param int      $index
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	public function adjust_classes_in_view_loop_item_and_generate_proper_styles( $loop_item_output, $index, $post ) {
		if ( ! $this->has_block_from_compatible_plugin( $this->template_content ) ) {
			return $loop_item_output;
		}

		foreach ( $this->original_block_id_array as $block_id ) {
			$search_for_block_id = $block_id;
			$replace_with_modified_block_id = $block_id . '_' . $post->ID;

			$loop_item_output = str_replace( $search_for_block_id, $replace_with_modified_block_id, $loop_item_output );
		}

		return $loop_item_output;
	}

	/**
	 * Prevents the rendering of inline styles in head for specific blocks.
	 *
	 * @param boolean $render_inline_css
	 * @param string  $block_name
	 * @param string  $unique_id
	 *
	 * @return boolean
	 */
	public function prevent_rendering_inline_styles_in_head_for_blocks( $render_inline_css, $block_name, $unique_id ) {
		if ( ! in_array( $block_name, self::BLOCKS_NEED_PREVENTING_STYLE_IN_HEAD, true ) ) {
			return $render_inline_css;
		}

		if ( doing_action( 'wp_enqueue_scripts' ) ) {
			return false;
		}

		return $render_inline_css;
	}


	/**
	 * Filters the View loop content and prepends the frontend block styles to it, whenever there is a need for this (if the
	 * block is used in the View).
	 *
	 * @param string $loop
	 *
	 * @return string
	 */
	public function filter_view_loop_content_for_including_frontend_styles_in_editor( $loop ) {
		if (
			! $this->constants->defined( 'REST_REQUEST' ) ||
			! $this->constants->constant( 'REST_REQUEST' )
		) {
			return $loop;
		}

		$kadence_blocks_frontend_css = '';
		foreach ( self::STYLES_FOR_BLOCKS as $block_slug => $style_slug ) {
			$search_string_suffix = $block_slug;

			if ( 'videopopup' === $block_slug ) {
				$search_string_suffix = 'video-popup';
			}

			$kadence_blocks_search_pattern = "wp-block-kadence-$search_string_suffix|kt-$search_string_suffix|kadence-$search_string_suffix";
			if ( 1 === preg_match( "/$kadence_blocks_search_pattern/", $loop ) ) {
				$style_slug_array = $style_slug;

				if ( ! is_array( $style_slug_array ) ) {
					$style_slug_array = array( $style_slug_array );
				}

				foreach( $style_slug_array as $style_slug_array_element ) {
					$css_file_path = $this->constants->defined( 'KADENCE_BLOCKS_PATH' ) ? $this->constants->constant( 'KADENCE_BLOCKS_PATH' ) . 'dist/blocks/' . $style_slug_array_element . '.style.build.css' : '';
					$css_file_path_pro = $this->constants->defined( 'KBP_PATH' ) ? $this->constants->constant( 'KBP_PATH' ) . 'dist/blocks/' . $style_slug_array_element . '.style.build.css' : '';
					if ( file_exists( $css_file_path ) ) {
						$kadence_blocks_frontend_css .= file_get_contents( $css_file_path );
					} elseif ( file_exists( $css_file_path_pro ) ) {
						$kadence_blocks_frontend_css .= file_get_contents( $css_file_path_pro );
					}
				}
			}
		}

		if ( ! empty( $kadence_blocks_frontend_css ) ) {
			$loop = '<style>' . $kadence_blocks_frontend_css . '</style>' . $loop;
		}

		return $loop;
	}


	/**
	 * @param $should_render_css_inline
	 *
	 * @return bool
	 */
	public function force_inline_css_rendering_in_content( $should_render_css_inline ) {
		if (
			1 === did_action( 'wpv_action_before_doing_blocks_in_view_block_template' ) ||
			1 === did_action( 'wpv_action_before_doing_blocks_in_views_layout_meta_html' )
		) {
			return true;
		}

		return $should_render_css_inline;
	}
}
