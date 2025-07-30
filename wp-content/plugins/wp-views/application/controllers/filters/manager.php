<?php

use \OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;
use \OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice;
use \OTGS\Toolset\Views\Controller\Filters\Post\ProductStock;

/**
 * Filter manager for Toolset Views.
 *
 * Initializes, stored and allows access to every query filter registered within Toolset Views.
 *
 * @since m2m
 */
class WPV_Filter_Manager {

	/** Key to store all filters in the View or WPA settings. */
	const SETTING_KEY = 'filters';

	/** Key to set the filter editor mode: full (normal), ghost (from a serch filter), forced (from a content selection option). */
	const EDITOR_MODE = 'editor_mode';

	/** Key to set a filter summary to bypass the one crafted on-the-fly. */
	const EDITOR_SUMMARY = 'editor_summary';

	/** Key to set a filter title to bypass the native one. */
	const EDITOR_TITLE = 'editor_title';

	// Mode for query filters created by themselves.
	const FILTER_MODE_FULL = 'full';

	// Mode for query filters created on-the-fly from frontend search filters.
	const FILTER_MODE_FROM_SEARCH_FILTER = 'ghost';

	// Mode for query filters created on-the-fly from content selection special cases.
	const FILTER_MODE_FROM_CONTENT_SELECTION = 'forced';

	// When a variable parameter (a shortcode attribute, a, URL parameter) on legacy format VIEW_PARAM(xxx) URL_PARAM(yyy) finds no value.
	const NO_DYNAMIC_VALUE_FOUND = 'WPV_NO_PARAM_FOUND';

	/**
	 * @var WPV_Filter_Manager|null
	 */
	protected static $instance = null;

	/**
	 * @var array
	 */
	protected $filters = array(
		\Toolset_Element_Domain::POSTS => array(),
		\Toolset_Element_Domain::TERMS => array(),
		\Toolset_Element_Domain::USERS => array(),
	);

	/**
	 * Get the instance of this object.
	 *
	 * @since m2m
	 */
    public static function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new \WPV_Filter_Manager();
        }
        return self::$instance;
    }

	/**
	 * Initialize each domain filters.
	 *
	 * @since m2m
	 */
	public function initialize() {
		$this->initialize_post_filters();
		$this->initialize_term_filters();
		$this->initialize_user_filters();
	}

	/**
	 * Initialize post-related filters.
	 *
	 * @since m2m
	 */
	private function initialize_post_filters() {
		$dic = apply_filters( 'toolset_dic', false );

		// Filter by post relationships.
		$this->filters[ \Toolset_Element_Domain::POSTS ]['relationship'] = $dic->make( '\WPV_Filter_Post_Relationship' );

		// Filter by post product on sale status.
		$this->filters[ \Toolset_Element_Domain::POSTS ][ ProductOnsale::SLUG ] = $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale' );
		$this->filters[ \Toolset_Element_Domain::POSTS ][ ProductOnsale::SLUG ]->initialize();
		// Filter by post product price.
		$this->filters[ \Toolset_Element_Domain::POSTS ][ ProductPrice::SLUG ] = $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice' );
		$this->filters[ \Toolset_Element_Domain::POSTS ][ ProductPrice::SLUG ]->initialize();
		// Filter by post product price.
		$this->filters[ \Toolset_Element_Domain::POSTS ][ ProductStock::SLUG ] = $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductStock' );
		$this->filters[ \Toolset_Element_Domain::POSTS ][ ProductStock::SLUG ]->initialize();
	}

	/**
	 * Initialize term-related filters.
	 *
	 * @since m2m
	 */
	private function initialize_term_filters() {}

	/**
	 * Initialize user-related filters.
	 *
	 * @since m2m
	 */
	private function initialize_user_filters() {}

	/**
	 * Get a filter per domain and slug.
	 *
	 * @since m2m
	 */
	public function get_filter( $domain, $slug ) {
		return ( isset( $this->filters[ $domain ][ $slug ] ) )
			? $this->filters[ $domain ][ $slug ]
			: null;
	}

}
