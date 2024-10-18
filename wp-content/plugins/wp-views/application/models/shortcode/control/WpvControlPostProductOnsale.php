<?php

namespace OTGS\Toolset\Views\Model\Shortcode\Control;

use OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale\ProductOnsaleQuery;

class WpvControlPostProductOnsale implements \WPV_Shortcode_Interface {

	const SHORTCODE_NAME = 'wpv-control-post-product-onsale';

	/** @var array */
	private $shortcode_atts = array(
		'url_parameter' => '',
	);

	/** @var array */
	private $required_atts = array(
		'url_parameter',
	);

	/** @var \WPV_Filter_Manager */
	private $filter_manager;

	/** @var string|null */
	private $user_content;

	/** @var array */
	private $user_atts;

	/** @var WPV_Filter_Base */
	private $filter;

	/**
	 * Constructor.
	 *
	 * @param \WPV_Filter_Manager $filter_manager
	 */
	public function __construct( \WPV_Filter_Manager $filter_manager ) {
		$this->filter_manager = $filter_manager;
	}

	/**
	 * Get the value of the shortcode.
	 *
	 * @param string[] $atts
	 * @param string|null $content
	 * @return string
	 */
	public function get_value( $atts, $content = null ) {
		$this->user_atts = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		foreach ( $this->required_atts as $required_att ) {
			if ( empty( $this->user_atts[ $required_att ] ) ) {
				return '';
			}
		}

		$this->filter = $this->filter_manager->get_filter( \Toolset_Element_Domain::POSTS, ProductOnsale::SLUG );

		if ( false === $this->filter->are_conditions_met() ) {
			return '';
		}

		if ( null == $this->user_content ) {
			$this->user_content = __( 'On sale', 'wpv-views' );
		}

		$current_view = apply_filters( 'wpv_filter_wpv_get_current_view', 0 );
		$identifer = 'wpv-product-onsale-' . $current_view;

		$outcome = '<input id="' . esc_attr( $identifer ) . '" '
			. 'type="checkbox" name="' . esc_attr( $this->user_atts[ 'url_parameter' ] ) . '" value="1" '
			. 'class="form-check-input wpv-checkbox-input wpv-product-onsale js-wpv-filter-trigger" '
			. checked( ProductOnsaleQuery::FILTER_VALUE === strval( toolset_getget( $this->user_atts[ 'url_parameter' ] ) ), true, false )
			. '>'
		 	. '<label for="' . esc_attr( $identifer ) . '" class="form-check-label wpv-checkbox-label wpv-product-onsale-label">' . esc_html( $this->user_content ) . '</label>';

		return $outcome;
	}

}
