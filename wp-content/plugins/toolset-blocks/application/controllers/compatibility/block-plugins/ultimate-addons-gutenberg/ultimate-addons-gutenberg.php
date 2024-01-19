<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\BlockPlugin;

use OTGS\Toolset\Views\Controller\Compatibility\BlockPlugin\UltimateAddonsGutenberg\UagPostAssetsFactory;

/**
 * Handles the compatibility between Views and Ultimate Addons Gutenberg.
 *
 * @since 3.2
 */
class UltimateAddonsGutenbergCompatibility extends BlockPluginCompatibility {
	const BLOCK_NAMESPACE = 'uagb';

	/** @var \Toolset_Constants */
	private $constants;

	/** @var UagPostAssetsFactory */
	private $uagb_post_assets_factory;

	/** @var \UAGB_Post_Assets */
	private $uagb_post_assets;

	/** @var string */
	private $common_block_css;

	/**
	 * UltimateAddonsGutenbergCompatibility constructor.
	 *
	 * @param UagPostAssetsFactory $uagb_post_assets_factory
	 * @param \Toolset_Constants $constants
	 */
	public function __construct(
		UagPostAssetsFactory $uagb_post_assets_factory,
		\Toolset_Constants $constants
	) {

		$this->uagb_post_assets_factory = $uagb_post_assets_factory;
		$this->constants = $constants;
	}

	/**
	 * Initializes the UAG integration.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Initializes the hooks for the UAG integration.
	 */
	private function init_hooks() {
		// Covers the case where UAG blocks are used in some post content fetched with the "Post Content" Dynamic Source.
		// This needs to specifically happen before priority '9' because otherwise it won't work properly.
		add_filter( 'the_content', array( $this, 'maybe_get_styles_for_post_content_source' ), 8 );

		// Used for the case where a Content Template using UAG blocks, needs to enqueue its assets for the blocks CSS styles
		// to be applied.
		add_action( 'wp', array( $this, 'maybe_enqueue_ct_assets' ) );

		// Used for the case where a WordPress Archive using UAG blocks, needs to enqueue its assets for the blocks CSS styles
		// to be applied.
		add_action( 'wp', array( $this, 'maybe_enqueue_wpa_assets' ) );

		// Adjusts the blocks' output and the relevant styles for those that have Dynamic Sources integrated, when inside a View.
		add_filter( 'uagb_block_attributes_for_css_and_js', array( $this, 'maybe_capture_block_id_for_css_and_js_generation' ), 10, 2 );
		add_filter( 'wpv_filter_view_loop_item_output', array( $this, 'adjust_classes_in_view_loop_item_and_generate_proper_styles' ), 10, 3 );
		add_filter( 'wpv_view_pre_do_blocks_view_layout_meta_html', array( $this, 'capture_view_template' ), 10, 2 );
	}

	/**
	 * It calculates the UAG Blocks style for the content of the "Post Content" dynamic source.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function maybe_get_styles_for_post_content_source( $content ) {
		global $post;

		if (
			$post->dynamic_sources_content_processed ||
			$post->view_template_override
		) {
			$this->uagb_post_assets = $this->uagb_post_assets_factory->get_uag_post_assets( $post->ID );
			$uag_styles = $this->get_uag_blocks_styles( $content );
			$this->uagb_post_assets = null;

			if ( ! empty( $uag_styles ) ) {
				$content = '<style>' . $uag_styles . '</style>' . $content;
			}
		}

		return $content;
	}

	/**
	 * Enqueues the CSS styles for UAG blocks inside a Content Template, in case the currently rendered post uses one.
	 */
	public function maybe_enqueue_ct_assets() {
		if ( ! is_single() ) {
			return;
		}

		global $post;

		$maybe_ct_selected = apply_filters( 'wpv_content_template_for_post', 0, $post );

		if ( 0 === (int) $maybe_ct_selected ) {
			return;
		}

		$uagb_post_assets = $this->uagb_post_assets_factory->get_uag_post_assets( $maybe_ct_selected );
		$uagb_post_assets->enqueue_scripts();
	}

	/**
	 * Enqueues the CSS styles for UAG blocks inside a WordPress Archive.
	 *
	 * Applies for the blocs that are outside the loop, so their styles are saved in the helper post.
	 */
	public function maybe_enqueue_wpa_assets() {
		if ( ! is_archive() && ! is_home() && !	is_search()	) {
			return;
		}

		// Get the ID of the WordPress Archive in use.
		$wpa_id = apply_filters( 'wpv_filter_wpv_get_current_archive', null );

		if ( ! $wpa_id ) {
			return;
		}

		// Try to get the ID of the WordPress Archive Helper post, in case the WordPress Archive is built with blocks.
		$maybe_wpa_helper_id = apply_filters( 'wpv_filter_wpv_get_wpa_helper_post', $wpa_id );

		$uagb_post_assets = $this->uagb_post_assets_factory->get_uag_post_assets( $maybe_wpa_helper_id );
		$uagb_post_assets->enqueue_scripts();
	}

	/**
	 * Gets the styles for UAG Blocks inside the given content.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	private function get_uag_blocks_styles( $content ) {
		if ( ! function_exists( 'has_blocks' ) ) {
			return '';
		}

		if ( ! has_blocks( $content ) ) {
			return '';
		}

		$blocks = parse_blocks( $content );

		if (
			! is_array( $blocks ) ||
			empty( $blocks )
		) {
			return '';
		}

		$uag_styles =
			null !== $this->uagb_post_assets ?
				$this->uagb_post_assets->get_blocks_assets( $blocks ) :
				'';

		return array_key_exists( 'css', $uag_styles ) ? $uag_styles['css'] : '';
	}

	/**
	 * Adjusts the block ID for CSS and JS generation by adding the post ID to the end of the unique block identifier.
	 *
	 * @param array $block_attributes
	 * @param string $block_name
	 *
	 * @return array
	 */
	public function maybe_capture_block_id_for_css_and_js_generation( $block_attributes, $block_name ) {
		if ( ! $this->is_block_from_compatible_plugin( $block_name ) ) {
			return $block_attributes;
		}

		$block_integration_info = apply_filters( 'toolset/dynamic_sources/filters/third_party_block_integration_info', array(), $block_name );

		if (
			! $block_integration_info ||
			! array_key_exists( 'block_id', $block_attributes )
		) {
			return $block_attributes;
		}

		if ( ! in_array( $block_attributes['block_id'], $this->original_block_id_array, true ) ) {
			array_push( $this->original_block_id_array, $block_attributes['block_id'] );
		}

		return $block_attributes;
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

		add_action( 'uagb_single_block_common_css_composed', array( $this, 'store_single_block_common_block_css' ) );

		$this->uagb_post_assets = $this->uagb_post_assets_factory->get_uag_post_assets( $post->ID );
		$template_styles = $this->get_uag_blocks_styles( $this->template_content );
		$this->uagb_post_assets = null;

		$loop_item_styles = $template_styles;

		foreach ( $this->original_block_id_array as $block_id ) {
			$search_for_block_id = $block_id;
			$replace_with_modified_block_id = $block_id . '_' . $post->ID;

			$loop_item_styles = str_replace( $search_for_block_id, $replace_with_modified_block_id, $loop_item_styles );

			$loop_item_output = str_replace( $search_for_block_id, $replace_with_modified_block_id, $loop_item_output );
		}

		// If the classes in the View loop item are adjusted during a REST Request, then this is probably the rendering of
		// the View/WPA block preview in the editor.
		// UAG blocks have specific styles for the editor, where the styles of the block are preceded by an id, the "wpwrap"
		// which is the ID of wrapper div of the admin area of WordPress.
		// So when the classes are adjusted in the styles for the View loop item, since these are frontend styles, they
		// are preceded by this ID in order to actually override the ones set by the editor.
		// Sorry, there was no other way.
		if (
			$this->constants->defined( 'REST_REQUEST' ) &&
			$this->constants->constant( 'REST_REQUEST' )
		) {
			$loop_item_styles = str_replace( '.uagb-block-', '#wpwrap .uagb-block-', $loop_item_styles );
		}

		$output = $loop_item_output;

		if ( $loop_item_styles !== $template_styles ) {
			if ( 0 !== $index ) {
				$loop_item_styles = str_replace( $this->common_block_css, '', $loop_item_styles );
			}

			if ( ! empty( $loop_item_styles ) ) {
				$output = '<style>' . $loop_item_styles . '</style>' . $output;
			}
		}

		remove_action( 'uagb_single_block_common_css_composed', array( $this, 'store_single_block_common_block_css' ) );

		return $output;
	}


	/**
	 * Stores the common (for all the devices) block css, while rendering a loop, either in a View or a WordPress Archive,
	 * so that this common css is included only once.
	 *
	 * @param string $common_block_css
	 */
	public function store_single_block_common_block_css( $common_block_css ) {
		$this->common_block_css = $common_block_css;
	}
}
