<?php

namespace OTGS\Toolset\Views\Controller\Compatibility;

use OTGS\Toolset\Common\Condition\Theme\Divi\IsDiviThemeActive;

/**
 * Handles the compatibility between Views and Divi.
 *
 * @since 3.2.0
 */
class DiviCompatibility extends Base {
	/** @var IsDiviThemeActive */
	private $divi_is_active;

	/**
	 * DiviCompatibility constructor.
	 *
	 * @param IsDiviThemeActive|null $divi_is_active
	 */
	public function __construct( IsDiviThemeActive $divi_is_active = null ) {
		$this->divi_is_active = $divi_is_active ?: new IsDiviThemeActive();
	}

	/**
	 * Initializes the Divi Theme compatibility layer.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Initializes the hooks for the Divi Theme compatibility.
	 */
	private function init_hooks() {
		add_action( 'pre_get_posts', array( $this, 'enable_divi_layout_block_for_cts' ) );

		add_action( 'wpv_post_excerpt_shortcode_before_applying_the_excerpt_filter' , [ $this, 'prevent_the_excerpt_filter_from_doing_shortcodes' ] );
		add_action( 'wpv_post_excerpt_shortcode_after_applying_the_excerpt_filter' , [ $this, 'restore_doing_shortcodes_on_the_excerpt_filter' ] );
	}

	/**
	 * Allows the Divi Layout block to work properly in Content Templates when a page is set as the home page of the site. (I know...)
	 *
	 * There is a case where the Divi Layout block fails to work properly in Content Templates when a page is set as a home
	 * page of the site (instead of the blog page). When the user tries to create a new Divi Layout through the Divi Layout
	 * block, Divi loads a blank page where it will load its editor. Divi tries to connect its editor with the post/page
	 * it loads if for (in our case Content Templates) but since Content Templates is an internal post type, not publicly queryable,
	 * the connection process falls back to a possible page post assigned as a home page and this processes messes the proper
	 * use of Divi Layout blocks in Content Templates.
	 *
	 * This is the best way I (konstantinos.g) could describe this.
	 *
	 * @param \WP_Query $query
	 */
	public function enable_divi_layout_block_for_cts( $query ) {
		// When:
		// 1. Divi is active
		// 2. This is the main query
		// 3. The method that checks if the current request is about the preview of a Divi Layout block is callable
		// 4. The current request is about the preview of a Divi Layout block
		// 5. The current request is made through the Content Template edit page.
		// 6. A page is set as the site's home page
		// 7. The page set as the site's home page is set
		// 8. The request has fallen back to be handled as a home page request
		// Then this fallback needs to be removed and let the request be handled as it was supposed to if it happened through
		// regular post type.
		if (
			$this->divi_is_active->is_met() &&
			$query->is_main_query() &&
			is_callable( '\ET_GB_Block_Layout::is_layout_block_preview' ) &&
			\ET_GB_Block_Layout::is_layout_block_preview() &&
			\WPV_Content_Template_Embedded::POST_TYPE === toolset_getget( 'et_post_type', null ) &&
			'page' === get_option( 'show_on_front' ) &&
			get_option( 'page_on_front' ) &&
			$query->get( 'page_id' ) === get_option( 'page_on_front' )
		) {
			$query->set( 'page_id', sanitize_text_field( '' ) );
		}
	}


	/**
	 * Removes the "do_shortcode" callback hooked to be applied when the "the_excerpt" filter is applied on the output of the
	 * "wpv-post-excerpt" shortcode.
	 * Divi is hooking this callback causing infinite loop issues when the shortcode is used inside a View.
	 */
	public function prevent_the_excerpt_filter_from_doing_shortcodes() {
		remove_filter( 'the_excerpt', 'do_shortcode' );
	}

	/**
	 * Re-hooks the "do_shortcode" callback to be applied when the "the_excerpt" filter is applied, after it has been removed
	 * for the expansion of the "wpv-post-excerpt" shortcode.
	 * Divi is hooking this callback causing infinite loop issues when the shortcode is used inside a View.
	 */
	public function restore_doing_shortcodes_on_the_excerpt_filter() {
		add_filter( 'the_excerpt', 'do_shortcode' );
	}
}
