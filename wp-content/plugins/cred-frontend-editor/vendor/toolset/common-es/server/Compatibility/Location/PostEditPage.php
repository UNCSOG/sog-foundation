<?php

namespace ToolsetCommonEs\Compatibility\Location;


use ToolsetCommonEs\Compatibility\ILocation;
use ToolsetCommonEs\Library\WordPress\Page\PostEdit as WordpressPostEditPage;

class PostEditPage implements ILocation {
	/** @var WordpressPostEditPage */
	private $wp_post_edit_page;

	/**
	 * PostEditPage constructor.
	 *
	 * @param WordpressPostEditPage $wordpress_post_edit_page
	 */
	public function __construct( WordpressPostEditPage $wordpress_post_edit_page ) {
		$this->wp_post_edit_page = $wordpress_post_edit_page;
	}

	public function is_open() {
		return $this->wp_post_edit_page->is_open();
	}

	public function apply_css_rules( $css_rules, $id = null ) {
		$this->wp_post_edit_page->apply_css_rules( $css_rules, $id );
	}

	public function get_css_selector() {
		return '.post-php';
	}
}

