<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice;

use OTGS\Toolset\Views\Controller\Filters\AbstractSearch;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice;
use OTGS\Toolset\Views\Model\Shortcode\Control\WpvControlPostProductPrice;

/**
 * Search component for the filter.
 */
class ProductPriceSearch extends AbstractSearch {

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
		$form_filters_shortcodes[ WpvControlPostProductPrice::SHORTCODE_NAME ] = array(
			'query_type_target' => 'posts',
			'custom_search_filter_group' => __( 'Product filters', 'wpv-views' ),
			'custom_search_filter_items' => array(
				ProductPrice::SLUG => array(
					'name' => __( 'Product filter: by price', 'wpv-views' ),
					'present' => array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG ),
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
	 * @todo Check why the ToolsetShortcodeSettings component from TCES assumes that registered shortcodes will have a display-options group of fields.
	 */
	public function register_shortcodes_data( $views_shortcodes ) {
		$views_shortcodes[ WpvControlPostProductPrice::SHORTCODE_NAME ] = array(
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
		$data = array(
			'name' => __( 'Product filter: by price', 'wpv-views' ),
			'label' => __( 'Product filter: by price', 'wpv-views' ),
			'attributes' => array(
				'display-options' => array(
					'label' => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'step' => array(
							'label' => __( 'Slider drag&drop step', 'wpv-views' ),
							'type' => 'number',
							'default' => '1',
						),
						'start' => array(
							'label' => __( 'Minimum value', 'wpv-views' ),
							'type' => 'radio',
							'options' => array(
								'minimum' => __( 'Start with the minimum available price', 'wpv-views' ),
								'zero' => __( 'Start at zero', 'wpv-views' ),
							),
							'default' => 'minimum',
						),
					),
					'content' => array(
						'label' => __( 'Legend of the range slider', 'wpv-views' ),
						'default' => __( 'Price: %%MIN%% &mdash; %%MAX%%', 'wpv-views' ),
					),
				),
				'filter-options' => array(
					'label' => __( 'Filter options', 'wpv-views' ),
					'header' => __( 'Filter options', 'wpv-views' ),
					'fields' => array(),
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
		if ( strpos( $filter_html, '[' . WpvControlPostProductPrice::SHORTCODE_NAME ) === false ) {
			return $view_settings;
		}

		$filters = toolset_getarr( $view_settings, \WPV_Filter_Manager::SETTING_KEY, array() );
		$filters[ ProductPrice::SLUG ] = array(
			'mode' => 'url_parameter',
			\WPV_Filter_Manager::EDITOR_MODE => \WPV_Filter_Manager::FILTER_MODE_FROM_SEARCH_FILTER,
		);
		$view_settings[ \WPV_Filter_Manager::SETTING_KEY ] = $filters;

		return $view_settings;
	}

}
