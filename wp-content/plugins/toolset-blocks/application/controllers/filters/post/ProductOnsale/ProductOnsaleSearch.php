<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;

use OTGS\Toolset\Views\Controller\Filters\AbstractSearch;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;
use OTGS\Toolset\Views\Model\Shortcode\Control\WpvControlPostProductOnsale;

/**
 * Search component for the filter.
 */
class ProductOnsaleSearch extends AbstractSearch {

	/** @var string[] */
	private $faked_query_filter = array();

	/**
	 * Load component hooks.
	 *
	 * Note that this is hooked into init:1 since search shortcodes are listed as
	 * localization data on init:10 for the parametric script in the editor.
	 *
	 * @return void
	 */
	public function load_hooks() {
		add_filter( 'wpv_filter_wpv_register_form_filters_shortcodes', array( $this, 'register_form_filters_shortcodes' ), 0 );
		add_filter( 'wpv_filter_wpv_shortcodes_gui_data', array( $this, 'register_shortcodes_data' ) );
		add_filter( 'wpv_filter_object_settings_for_fake_url_query_filters', array( $this, 'fake_url_query_filters' ) );
	}

	/**
	 * Register the shortcode as a search form shortcode.
	 *
	 * @param mixed[] $form_filters_shortcodes
	 * @return mixed[]
	 */
	public function register_form_filters_shortcodes( $form_filters_shortcodes ) {
		$form_filters_shortcodes[ WpvControlPostProductOnsale::SHORTCODE_NAME ] = array(
			'query_type_target' => 'posts',
			'custom_search_filter_group' => __( 'Product filters', 'wpv-views' ),
			'custom_search_filter_items' => array(
				ProductOnsale::SLUG => array(
					'name' => __( 'Product filter: on sale status', 'wpv-views' ),
					'present' => array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG ),
					'params' => array(),
				)
			)
		);
		return $form_filters_shortcodes;
	}

	/**
	 * Register the shortcode attributes.
	 *
	 * @param mixed[] $views_shortcodes
	 * @return mixed[]
	 */
	public function register_shortcodes_data( $views_shortcodes ) {
		$views_shortcodes[ WpvControlPostProductOnsale::SHORTCODE_NAME ] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}

	/**
	 * Gather the shortcode attributes definition.
	 *
	 * @param mixed[] $parameters Extra parameters passed to the GUI.
	 * @param mixed[] $overrides Existing parameters when editing an existing shortcode.
	 * @return mixed[]
	 * @todo Check why the ToolsetShortcodeSettings component from TCES assumes that registered shortcodes will have a display-options group of fields.
	 */
	public function get_shortcode_data( $parameters = array(), $overrides = array() ) {
		$default_url_parameter = toolset_getnest( $overrides, array( 'attributes', 'url_parameter' ), 'wpv-on-sale' );
		$data = array(
			'name' => __( 'Product filter: on sale status', 'wpv-views' ),
			'label' => __( 'Product filter: on sale status', 'wpv-views' ),
			'attributes' => array(
				'display-options' => array(
					'label' => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(),
					'content' => array(
						'label' => __( 'Label of the checkbox', 'wpv-views' ),
						'default' => __( 'On sale', 'wpv-views' ),
					),
				),
				'filter-options' => array(
					'label'		=> __( 'Filter options', 'wpv-views' ),
					'header'	=> __( 'Filter options', 'wpv-views' ),
					'fields' => array(
						'url_parameter' => array(
							'label'			=> __( 'URL parameter to use', 'wpv-views' ),
							'type'			=> 'text',
							'default_force'	=> $default_url_parameter,
							'required'		=> true
						),
					),
				),
			),
		);

		return $data;
	}

	/**
	 * Generate a compnion query filter when a frontend search shortcode exists in the form.
	 *
	 * @param mixed[] $view_settings
	 * @return mixed[]
	 */
	public function fake_url_query_filters( $view_settings ) {
		$filter_html = toolset_getarr( $view_settings, 'filter_meta_html', '' );
		if ( strpos( $filter_html, '[' . WpvControlPostProductOnsale::SHORTCODE_NAME ) === false ) {
			return $view_settings;
		}

		global $shortcode_tags;
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();
		add_shortcode( WpvControlPostProductOnsale::SHORTCODE_NAME, array( $this, 'fake_shortcode_callback' ) );
		do_shortcode( $filter_html );
		$shortcode_tags = $orig_shortcode_tags;

		$filters = toolset_getarr( $view_settings, \WPV_Filter_Manager::SETTING_KEY, array() );
		$filters[ ProductOnsale::SLUG ] = $this->faked_query_filter;
		$view_settings[ \WPV_Filter_Manager::SETTING_KEY ] = $filters;

		return $view_settings;
	}

	/**
	 * Faked shortcode callback just to collect attribute values.
	 *
	 * @param string[] $atts
	 * @param string|null $content
	 */
	public function fake_shortcode_callback( $atts, $content = null ) {
		$this->faked_query_filter = array(
			'mode' => 'url_parameter',
			'url_parameter' => toolset_getarr( $atts, 'url_parameter', 'wpv-on-sale' ),
			\WPV_Filter_Manager::EDITOR_MODE => \WPV_Filter_Manager::FILTER_MODE_FROM_SEARCH_FILTER,
		);
	}

}
