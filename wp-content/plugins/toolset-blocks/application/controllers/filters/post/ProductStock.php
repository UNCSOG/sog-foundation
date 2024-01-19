<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post;

use OTGS\Toolset\Views\Controller\Filters\ComponentsFactory;

/**
 * Filter by post product stock.
 *
 * NOe that this only needs to pipeline legacy filters by a no longer existing field to he native hidden WooCommerce field for this.
 */
class ProductStock extends \WPV_Filter_Base {

	const SLUG = 'post_product/stock';
	const SELECTOR_SLUG = 'post_product_stock';

	// Before Toolset 1.5, Tolset WooCommerce Blocks used to generate companion custom filds.
	// Those fields stored cron-calculated values for filtering and sorting purposes.
	// Since Toolset 1.5 we translate filtering and sorting by those fields into using native methods.
	const LEGACY_FIELD_SLUG = 'views_woo_in_stock';

	const FIELD_SLUG = '_stock_status';

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

		$this->query = $this->factory->get_component( 'ProductStockQuery' );
		$this->query->initialize();
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
