<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post;

use OTGS\Toolset\Views\Controller\Filters\ComponentsFactory;

/**
 * Filter by post product price.
 */
class ProductPrice extends \WPV_Filter_Base {

	const SLUG = 'post_product/price';
	const SELECTOR_SLUG = 'post_product_price';

	// Before Toolset 1.5, Tolset WooCommerce Blocks used to generate companion custom filds.
	// Those fields stored cron-calculated values for filtering and sorting purposes.
	// Since Toolset 1.5 we translate filtering and sorting by those fields into using native methods.
	const LEGACY_FIELD_SLUG = 'views_woo_price';//views_woo_in_stock'

	/** @var \Toolset_Condition_Woocommerce_Active */
	private $is_woocommerce_active;

	/** @var ComponentsFactory */
	private $factory;

	/**
	 * Construct the filter.
	 *
	 * @param \Toolset_Condition_Woocommerce_Active $is_woocommerce_active
	 */
	function __construct(
		\Toolset_Condition_Woocommerce_Active $is_woocommerce_active,
		ComponentsFactory $factory
	) {
		$this->is_woocommerce_active = $is_woocommerce_active;
		$this->factory = $factory;
	}

	/**
	 * Initialize the filter.
	 */
	public function initialize() {
		if ( false === $this->are_conditions_met() ) {
			return;
		}

		$this->gui = $this->factory->get_component( 'ProductPriceGui' );
		$this->gui->initialize();

		$this->query = $this->factory->get_component( 'ProductPriceQuery' );
		$this->query->initialize();

		$this->search = $this->factory->get_component( 'ProductPriceSearch' );
		$this->search->initialize();
	}

	/**
	 * Check filter conditions as dependencies.
	 *
	 * @return bool
	 */
	public function are_conditions_met() {
		return $this->is_woocommerce_active->is_met();
	}

}
