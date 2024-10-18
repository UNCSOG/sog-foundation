<?php

namespace ToolsetCommonEs\Block\Style\Block;

use \ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\Migration\ITask as IMigrationTask;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;

/**
 * Class Factory
 *
 * Maps block array comming from WordPress to our Style/Block class. The array can be filtered, so it's important
 * to prove every key before use.
 *
 * @package ToolsetCommonEs\Block\Style\Block
 */
class Factory {
	/** @var FactoryStyleAttribute */
	private $factory_style_attribute;

	/** @var Devices */
	private $responsive_devices;

	/** @var IFactory[]  */
	private $block_factories = array();

	/** @var IMigrationTask */
	private $migration;

	/**
	 * Factory constructor.
	 *
	 * @param FactoryStyleAttribute $factory_attribute
	 * @param Devices $responsive_devices
	 * @param IMigrationTask $migration
	 */
	public function __construct(
		FactoryStyleAttribute $factory_attribute,
		Devices $responsive_devices,
		IMigrationTask $migration
	) {
		$this->factory_style_attribute = $factory_attribute;
		$this->responsive_devices = $responsive_devices;
		$this->migration = $migration;
	}

	/**
	 * A block factory is useful to build blocks which are not directly provided by CommonES (all blocks for now).
	 * The function get_block_by_array() will loop over every sub factory to find a matching block.
	 *
	 * @param IFactory $sub_factory
	 */
	public function add_block_factory( IFactory $sub_factory ) {
		$this->block_factories[] = $sub_factory;
	}

	/**
	 * Returns an array of ToolsetCommonEs\Block\Style\Block by given object.
	 *
	 * @param array $array
	 *
	 * @return void|ABlock
	 */
	public function get_block_by_array( $array ) {
		if(
			! is_array( $array ) ||
			! array_key_exists( 'blockName', $array ) ||
			! array_key_exists( 'attrs', $array )
		) {
			return;
		}

		$block = false;

		foreach( $this->block_factories as $block_factory ) {
			if( $block = $block_factory->get_block_by_array( $array ) ) {
				// Block found, break out of loop.
				break;
			}
		}

		if( ! $block ) {
			// No Block provided by the sub factories.
			return;
		}

		$block->set_name( $array['blockName'] );
		$block->make_use_of_inner_html( $array['innerHTML'] );
		return $block;
	}

	/**
	 * The common style is stored indentically on all blocks.
	 *
	 * @param IBlock $block
	 */
	public function load_styles_attributes( IBlock $block ) {
		$partly_using_block_config = false;

		/* Block is using a config file. */
		if( $block->get_block_setup() ) {
			$partly_using_block_config = true;
			$block->load_style_attributes_by_setup( $this->factory_style_attribute );
			$block->load_block_specific_style_attributes( $this->factory_style_attribute );
			$this->migration->migrate( $block, $this->factory_style_attribute );

			if ( ! property_exists( $block, 'block_config_migration_unfinished' ) ) {
				// The block uses the block config for all settings. Abort loading non block config styles mapping.
				return;
			}
		}

		$devices = $this->responsive_devices->get();

		$common_styles_map = [
			'style' => [
				ABlock::KEY_STYLES_FOR_COMMON_STYLES => null, // null = root of blockconfig['style']
				ABlock::KEY_STYLES_FOR_HOVER => ABlock::KEY_STYLES_FOR_HOVER, // style key and storage key are the same.
				ABlock::KEY_STYLES_FOR_ACTIVE => ABlock::KEY_STYLES_FOR_ACTIVE, // style key and storage key are the same.
				ABlock::KEY_STYLES_FOR_VISITED => ABlock::KEY_STYLES_FOR_VISITED, // style key and storage key are the same.
				ABlock::KEY_STYLES_FOR_FOCUS => ABlock::KEY_STYLES_FOR_FOCUS, // style key and storage key are the same.
			],
		];

		$styles_mapping = array_merge( $common_styles_map, $block->get_advanced_styles_map() );

		// Styles provided by the "Style Settings" section.
		foreach( $devices as $device_key => $device_info ) {
			foreach ( $styles_mapping as $root_key_in_block_config => $style_map ) {
				foreach( $style_map as $storage_key => $key_in_block_config ) {
					$styles = $this->factory_style_attribute->load_common_attributes_by_array(
						$block->get_block_config(),
						$root_key_in_block_config,
						$key_in_block_config,
						$device_key
					);

					if ( ! empty( $styles ) ) {
						foreach ( $styles as $style ) {
							$block->add_style_attribute( $style, $storage_key, $device_key );
						}
					}
				}
			}
		}

		// Load block specific styles and migration.
		// Blocks, which have partly implemented block config load some stuff earlier.
		if ( ! $partly_using_block_config ) {
			$block->load_block_specific_style_attributes( $this->factory_style_attribute );
			$this->migration->migrate( $block, $this->factory_style_attribute );
		}

	}
}
