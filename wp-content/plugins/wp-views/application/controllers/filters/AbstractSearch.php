<?php

namespace OTGS\Toolset\Views\Controller\Filters;

/**
 * Shared methods and routines for the frontend search component of filters.
 */
abstract class AbstractSearch {

	/**
	 * Search component initialization.
	 */
	public function initialize() {
		add_action( 'init', array( $this, 'load_hooks' ), 5 );
	}

	/**
	 * Set callbacks for registering the query for Views and WPAs.
	 *
	 * This requires callbacks in the following hooks:
	 * - wpv_filter_wpv_register_form_filters_shortcodes for registering the search shortcode.
	 * - wpv_filter_wpv_shortcodes_gui_data for gathering the shortcode GUI data.
	 * - wpv_filter_object_settings_for_fake_url_query_filters for generating query filters on the fly when needed.
	 *
	 * NOte that this requires to be run on init:5 since shortcoes data is used by init:10.
	 */
	abstract public function load_hooks();

}
