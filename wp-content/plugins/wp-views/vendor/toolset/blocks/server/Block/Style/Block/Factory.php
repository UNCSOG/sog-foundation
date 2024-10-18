<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Assets\Loader;
use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\IBlock;
use ToolsetCommonEs\Block\Style\Block\IFactory;
use ToolsetCommonEs\Utils\Config\Toolset;

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

	/** @var Toolset */
	private $config_toolset;

	/** @var Loader */
	private $assets_loader;


	/**
	 * Factory constructor.
	 *
	 * @param FactoryStyleAttribute $factory_attribute
	 * @param Toolset $config
	 * @param Loader $assets_loader
	 */
	public function __construct( FactoryStyleAttribute $factory_attribute, Toolset $config, Loader $assets_loader ) {
		$this->factory_style_attribute = $factory_attribute;
		$this->config_toolset = $config;
		$this->assets_loader = $assets_loader;
	}

	/**
	 * @param array $config
	 *
	 * @return void|IBlock
	 */
	public function get_block_by_array( $config ) {
		if (
			! is_array( $config ) ||
			! array_key_exists( 'blockName', $config ) ||
			! array_key_exists( 'attrs', $config )
		) {
			return;
		}

		$block_name = $config['blockName'];
		$block_attributes = $config['attrs'];

		switch ( $block_name ) {
			case 'toolset-blocks/grid-column':
				return new GridColumn( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/grid':
				return new Grid( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/audio':
				return new Audio( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/countdown':
				return new Countdown( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/field':
				return new SingleField( $block_attributes, $this->get_block_config( 'field' ), $block_name, $this->assets_loader );
			case 'toolset-blocks/button':
				return new Button( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/container':
				return new Container( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/fields-and-text':
				return new FieldsAndText( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/gallery':
				return new Gallery( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/heading':
				return new Heading( $block_attributes, $this->get_block_config( 'heading' ), $block_name, $this->assets_loader );
			case 'toolset-blocks/image':
				return new Image( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/star-rating':
				return new StarRating( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/video':
				return new Video( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/progress':
				return new Progress( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/repeating-field':
				return new RepeatingField( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/social-share':
				return new SocialShare( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/youtube':
				return new Youtube( $block_attributes, $block_name, $this->assets_loader );
			case 'toolset-blocks/image-slider':
				return new ImageSlider( $block_attributes, $block_name, $this->assets_loader );
			default:
				return;
		}
	}


	/**
	 * Get Block Config
	 *
	 * @param string $block
	 *
	 * @return \ToolsetCommonEs\Utils\Config\Block
	 */
	private function get_block_config( $block ) {
		$namespace = 'toolsetBlocks';
		return $this->config_toolset->get_block_config( $block, $namespace );
	}
}
