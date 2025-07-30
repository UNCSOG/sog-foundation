<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block;

use ToolsetCommonEs\Assets\Loader;
use OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType\Dots;
use OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType\Dropdown;
use OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType\Links;
use OTGS\Toolset\Views\Models\Block\Style\Block\PaginationType\PreviousNext;
use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\IBlock;
use ToolsetCommonEs\Block\Style\Block\IFactory;

/**
 * Class Factory
 *
 * Maps block array comming from WordPress to our Style/Block class. The array can be filtered, so it's important
 * to prove every key before use.
 *
 * @since 0.9.3
 */
class Factory implements IFactory {
	/** @var FactoryStyleAttribute */
	private $factory_style_attribute;

	/** @var Loader */
	private $assets_loader;

	/**
	 * Factory constructor.
	 *
	 * @param FactoryStyleAttribute $factory_attribute
	 */
	public function __construct( FactoryStyleAttribute $factory_attribute, Loader $assets_loader ) {
		$this->factory_style_attribute = $factory_attribute;
		$this->assets_loader = $assets_loader;
	}

	/**
	 * @param array $config
	 *
	 * @return IBlock
	 */
	public function get_block_by_array( $config ) {
		if(
			! is_array( $config ) ||
			! array_key_exists( 'blockName', $config ) ||
			! array_key_exists( 'attrs', $config )
		) {
			return;
		}

		$block_name = $config['blockName'];
		$block_attributes = $config['attrs'];

		switch( $block_name ) {
			case 'toolset-views/view-pagination-block':
				$pagination = new Pagination( $block_attributes );
				$pagination->add_type( new Dots() );
				$pagination->add_type( new Dropdown() );
				$pagination->add_type( new Links() );
				$pagination->add_type( new PreviousNext() );
				return $pagination;
			case 'toolset-views/sorting':
				return new Sorting( $block_attributes );
			case 'toolset-views/custom-search-filter':
				return new CustomSearch( $block_attributes );
			case 'toolset-views/custom-search-reset':
				return new CustomSearchReset( $block_attributes );
			case 'toolset-views/custom-search-submit':
				return new CustomSearchSubmit( $block_attributes );
			case 'toolset-views/view-template-block':
				return new LoopItem( $block_attributes );
			case 'toolset-views/view-editor':
				return new View( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-views/wpa-editor':
				return new WPA( $block_attributes, $block_name, $this->assets_loader );
		}
	}
}
