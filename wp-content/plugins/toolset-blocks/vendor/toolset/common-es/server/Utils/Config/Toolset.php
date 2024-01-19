<?php

namespace ToolsetCommonEs\Utils\Config;


use ToolsetCommonEs\Utils\Data\IData;
use ToolsetCommonEs\Utils\Data\StaticData;
use ToolsetCommonEs\Utils\Data\Factory as FactoryData;
use ToolsetCommonEs\Utils\Config\Factory as FactoryConfig;

class Toolset {

	/** @var IData */
	private $config;

	/** @var FactoryConfig */
	private $factory_config;

	/**
	 * Config constructor.
	 *
	 * @param IData $config
	 * @param FactoryConfig $factory_config
	 */
	public function __construct( IData $config, FactoryConfig $factory_config ) {
		$this->config = $config;
		$this->factory_config = $factory_config;
	}

	/**
	 * @param string $block_slug
	 * @param string $plugin_slug
	 *
	 * @return Block
	 */
	public function get_block_config( $block_slug, $plugin_slug ) {
		$block_config = $this->config->find( [ $plugin_slug, 'blocks', $block_slug ], [] );
		$block_config = $this->apply_tabs_presets_in_block_config( $block_config );
		return $this->factory_config->get_block( $block_config );
	}

	private function apply_tabs_presets_in_block_config( $block_config ) {
		if( ! array_key_exists( 'panels', $block_config ) ) {
			// No panels.
			return $block_config;
		}

		$tabs_presets = $this->get_tabs_presets();

		foreach( $block_config['panels'] as $panel_key => $panel ) {
			if( ! array_key_exists( 'tabs', $panel ) ) {
				// No tabs in this panel.
				continue;
			}

			if( is_string( $panel['tabs'] ) ) {
				// Preset is used.
				if( ! array_key_exists( $panel['tabs'], $tabs_presets ) ) {
					// Used preset does not exist. Delete tabs key and abort.
					unset( $block_config['panels'][ $panel_key ]['tabs'] );
					continue;
				}

				// Preset used and available. Apply.
				$block_config['panels'][ $panel_key ]['tabs'] = $tabs_presets[ $panel['tabs'] ];
			}

			// Put tabs also on block attributes.
			if( array_key_exists( 'fields', $panel ) ) {
				foreach( $panel['fields'] as $storage_key => $fields ) {
					if( $this->config->find_in( [ 'attributes', $storage_key ], $block_config ) ) {
						$block_config['attributes'][ $storage_key ]['tabs'] =
							$block_config['panels'][ $panel_key ]['tabs'];
					}
				}
			}
		}

		return $block_config;
	}

	private function get_tabs_presets() {
		return $this->config->find( ['common','blocks', 'tabs', 'presets'], [] );
	}
}
