<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post;

use OTGS\Toolset\Views\Controller\Filters\ComponentsFactory;

/**
 * Filter by post product onsale status.
 */
class ProductOnsale extends \WPV_Filter_Base {

	const SLUG = 'post_product/onsale';
	const SELECTOR_SLUG = 'post_product_onsale';

	// Before Toolset 1.5, Tolset WooCommerce Blocks used to generate companion custom filds.
	// Those fields stored cron-calculated values for filtering and sorting purposes.
	// Since Toolset 1.5 we translate filtering and sorting by those fields into using native methods.
	const LEGACY_FIELD_SLUG = 'views_woo_on_sale';

	/** @var \Toolset_Condition_Woocommerce_Active */
	private $is_woocommerce_active;

	/** @var ProductOnsale\Factory */
	private $factory;

	/**
	 * Construct the filter.
	 *
	 * @param \Toolset_Condition_Woocommerce_Active $is_woocommerce_active
	 * @param ComponentsFactory $factory
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

		$this->gui = $this->factory->get_component( 'ProductOnsaleGui' );
		$this->gui->initialize();

		$this->query = $this->factory->get_component( 'ProductOnsaleQuery' );
		$this->query->initialize();

		$this->search = $this->factory->get_component( 'ProductOnsaleSearch' );
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
