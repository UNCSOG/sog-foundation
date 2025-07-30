<?php

namespace OTGS\Toolset\Views\Controller\Compatibility\BlockPlugin;

use OTGS\Toolset\Views\Services\Bootstrap;

/**
 * Handles the compatibility between Views and Ultimate Addons Gutenberg.
 *
 * @since 3.2
 */
class UltimateAddonsGutenbergCompatibilityPre123 extends BlockPluginCompatibility {
	const BLOCK_NAMESPACE = 'uagb';

	/** @var \Toolset_Constants */
	private $constants;

	/** @var callable */
	private $uagb_helper_get_instance;

	/** @var callable */
	private $wpv_view_get_instance;

	/** @var string */
	public $uag_styles = null;


	/**
	 * UltimateAddonsGutenbergCompatibility constructor.
	 *
	 * @param callable $uagb_helper_get_instance
	 * @param \Toolset_Constants $constants
	 * @param callable $wpv_view_get_instance
	 */
	public function __construct(
		callable $uagb_helper_get_instance,
		\Toolset_Constants $constants,
		callable $wpv_view_get_instance
	) {

		$this->uagb_helper_get_instance = $uagb_helper_get_instance;
		$this->constants = $constants;
		$this->wpv_view_get_instance = $wpv_view_get_instance;
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
		// Covers the case where UAG blocks are used in a WordPress Archive built with blocks.
		add_action( 'wp', array( $this, 'maybe_generate_stylesheet_for_wpa' ), PHP_INT_MAX - 1 );

		// Together the filters below cover the case where UAG blocks are used inside a View, in order to properly render the
		// View preview.
		add_filter( 'wpv_view_pre_do_blocks_view_layout_meta_html', array( $this, 'maybe_extract_uag_blocks_styles' ) );
		add_filter( 'wpv-post-do-shortcode', array( $this, 'maybe_append_uag_blocks_styles' ), 10, 2 );

		// Covers the case where a post that uses a Content Template with UAG blocks but no UAG blocks in the post's content.
		add_filter( 'uagb_post_for_stylesheet', array( $this, 'maybe_get_ct_post' ) );

		// Covers the case where UAG blocks are used in a WordPress Archive built with blocks and the first item in the loop
		// doesn't use a Content Template with UAG blocks in its content.
		add_filter( 'uagb_post_for_stylesheet', array( $this, 'maybe_get_blocks_wpa_helper_post' ) );

		// Covers the case where a legacy View that uses a Content Template designed with the block editor and with UAG block is inserted in a post
		// using the View block.
		add_filter( 'uagb_post_for_stylesheet', array( $this, 'maybe_scan_for_legacy_view_block_with_content_template_with_uag_blocks' ) );

		// Covers the case where UAG blocks are used in some post content fetched with the "Post Content" Dynamic Source.
		// This needs to specifically happen before priority '9' because otherwise it won't work properly.
		add_filter( 'the_content', array( $this, 'maybe_get_styles_for_post_content_source' ), 8 );

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
			$uag_styles = $this->get_uag_blocks_styles( $content );

			if ( ! empty( $uag_styles ) ) {
				$content = '<style>' . $uag_styles . '</style>' . $content;
			}
		}

		return $content;
	}

	/**
	 * Appends the styles for the UAG blocks that exist inside the template of a View/WordPress Archive for the editor preview.
	 *
	 * The styles are injected this way because we do this in the exact same way for our own blocks.
	 *
	 * @param string $content
	 * @param bool   $doing_excerpt
	 *
	 * @return string
	 */
	public function maybe_append_uag_blocks_styles( $content, $doing_excerpt ) {
		if (
			! $this->constants->defined( 'REST_REQUEST' ) ||
			! $this->constants->constant( 'REST_REQUEST' )
		) {
			return $content;
		}

		if (
			$doing_excerpt ||
			empty( $this->uag_styles )
		) {
			return $content;
		}

		$content = '<style>' . $this->uag_styles . '</style>' . $content;

		$this->uag_styles = null;

		return $content;
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

		$uagb_helper_class_instance = call_user_func( $this->uagb_helper_get_instance );
		$uag_styles = $uagb_helper_class_instance->get_assets( $blocks );

		return $uag_styles['css'];
	}

	/**
	 * Extracts the UAG blocks styles from the View Layout Meta HTML in order to later inject them properly so that they
	 * show up properly inside a View or a WordPress Archive.
	 *
	 * @param  string $content
	 *
	 * @return string
	 */
	public function maybe_extract_uag_blocks_styles( $content ) {
		if (
			! $this->constants->defined( 'REST_REQUEST' ) ||
			! $this->constants->constant( 'REST_REQUEST' )
		) {
			return $content;
		}

		$uag_styles = $this->get_uag_blocks_styles( $content );

		if ( ! empty( $uag_styles ) ) {
			$this->uag_styles = $uag_styles;
		}

		return $content;
	}

	/**
	 * Generates the stylesheet for UAG blocks when they lie inside a WordPress Archive.
	 */
	public function maybe_generate_stylesheet_for_wpa() {
		// Check if the WordPress Archive is built with blocks.
		$wpa_helper_post = $this->maybe_get_blocks_wpa_helper_post( null );

		if ( ! $wpa_helper_post ) {
			return;
		}

		/** @var \UAGB_Helper $uagb_helper_class_instance */
		$uagb_helper_class_instance = call_user_func( $this->uagb_helper_get_instance );
		$uagb_helper_class_instance->get_generated_stylesheet( $wpa_helper_post );
	}

	/**
	 * Checks if the $post uses a Content Template, in which case it returns the Content Template post in order to generate
	 * the stylesheet for it.
	 *
	 * @param \WP_Post $post
	 *
	 * @return \WP_Post
	 */
	public function maybe_get_ct_post( $post ) {
		if ( ! $post || ! isset( $post->ID ) ) {
			return $post;
		}

		$maybe_ct_selected = apply_filters( 'wpv_content_template_for_post', 0, $post );

		if ( 0 !== (int) $maybe_ct_selected ) {
			$post = get_post( $maybe_ct_selected );
		}

		return $post;
	}

	/**
	 * Checks if the the user currently views a WordPress Archive built with blocks, in which case it returns the WPA Helper
	 * post in order to generate the stylesheet for it.
	 *
	 * @param null||\WP_Post $post
	 *
	 * @return array|\WP_Post|null
	 */
	public function maybe_get_blocks_wpa_helper_post( $post ) {
		if (
		! (
			is_archive() ||
			is_home() ||
			is_search()
		)
		) {
			// Do nothing if it's not a WordPress Archive.
			return $post;
		}

		// Get the ID of the WordPress Archive in use.
		$wpa_id = apply_filters( 'wpv_filter_wpv_get_current_archive', null );
		if ( ! $wpa_id ) {
			return $post;
		}

		// Check if the WordPress Archive is built with blocks.
		return get_post( apply_filters( 'wpv_filter_wpv_get_wpa_helper_post', $wpa_id ) ) ?: $post;
	}

	/**
	 * Scans the post content for a View block showing a legacy View using a Content Template designed with the block editor
	 * using UAG blocks.
	 *
	 * @param $post
	 *
	 * @return array|\WP_Post|null
	 */
	public function maybe_scan_for_legacy_view_block_with_content_template_with_uag_blocks( $post ) {
		if (
			! $post ||
			! isset( $post->post_content )
		) {
			return $post;
		}

		$post_blocks = parse_blocks( $post->post_content );

		foreach ( $post_blocks as $block ) {
			if ( Bootstrap::MODERN_BLOCK_NAME !== $block['blockName'] ) {
				continue;
			}

			$view = call_user_func( $this->wpv_view_get_instance, toolset_getnest( $block, array( 'attrs', 'viewId' ), 0 ) );

			if (
				$view &&
				$view->has_loop_template &&
				$view->loop_template_id
			) {
				$loop_template = get_post( $view->loop_template_id );
				if ( $this->has_block_from_compatible_plugin( $loop_template->post_content ) ) {
					return $loop_template;
				}
			}
		}

		return $post;
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

		$template_styles = $this->get_uag_blocks_styles( $this->template_content );

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
			$output = '<style>' . $loop_item_styles . '</style>' . $output;
		}

		return $output;
	}
}
