<?php

namespace OTGS\Toolset\Views\Controller\Shortcode;

/**
 * Generate the regular expression for matchin known inner shortcodes.
 *
 * @since 3.3.0
 */
trait InnerShortcodeRegex {

	/**
	 * Get the basic regular expression to catch almost all Types and Views shortcodes.
	 *
	 * We consider as internal shortcodes those which generate a plain outcome, as a string
	 * with little to none HTML structure, because those are to be considered as attribute values
	 * for HTML and other shortcodes.
	 *
	 * This leaves out items like password-management shortcodes, for example.
	 * We include Views because we can force them to produce JSON-valid structures.
	 *
	 * @return string
	 * @since 3.3.0
	 */
	public function get_inner_shortcodes_regex() {
		$regex = 'wpv-post-|wpv-taxonomy-|'
			. 'wpv-view|'
			. 'types|'
			. 'wpv-current-user|wpv-user|'
			. 'wpv-attribute|'
			. 'wpv-archive-title|wpv-bloginfo|'
			. 'wpv-found-count|wpv-items-count|wpv-posts-found|'
			. 'wpv-pager|wpv-search-term|wpv-theme-option|wpv-loop-index';
		return $regex;
	}

}
