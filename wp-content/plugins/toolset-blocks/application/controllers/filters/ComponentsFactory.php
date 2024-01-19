<?php

namespace OTGS\Toolset\Views\Controller\Filters;

class ComponentsFactory {

	/**
	 * Get the filter component.
	 *
	 * @param string $component
	 * @return mixed
	 */
	public function get_component( $component ) {
		$dic = apply_filters( 'toolset_dic', false );

		switch ( $component ) {
			// Product On Sale Filter
			case 'ProductPriceGui':
				return $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice\ProductPriceGui' );
			case 'ProductPriceQuery':
				return $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice\ProductPriceQuery' );
			case 'ProductPriceSearch':
				return $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice\ProductPriceSearch' );
			// Product Price Filter
			case 'ProductOnsaleGui':
				return $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale\ProductOnsaleGui' );
			case 'ProductOnsaleQuery':
				return $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale\ProductOnsaleQuery' );
			case 'ProductOnsaleSearch':
				return $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale\ProductOnsaleSearch' );
			// Product Stock filter.
			case 'ProductStockQuery':
				return $dic->make( '\OTGS\Toolset\Views\Controller\Filters\Post\ProductStock\ProductStockQuery' );
		}

		return null;
	}

}
