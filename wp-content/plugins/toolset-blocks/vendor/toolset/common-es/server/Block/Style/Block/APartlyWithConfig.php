<?php

namespace ToolsetCommonEs\Block\Style\Block;

/**
 * Class PartlyWithConfig
 *
 * @package ToolsetCommonEs\Block\Style\Block
 */
abstract class APartlyWithConfig extends WithConfig {
	/** @var bool */
	public $block_config_migration_unfinished = true;

	/**
	 * Combine new block config css with old non block config css.
	 *
	 * @param array $config
	 * @param false $force_apply
	 * @param null $responsive_device
	 *
	 * @return string
	 */
	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		$this->print_base_css();
		$css_legacy = $this->get_css_no_block_config( $this->get_css_config(), $force_apply, $responsive_device );
		$css_block_config = parent::get_css( [], true, $responsive_device );

		return $css_legacy . ' ' . $css_block_config;
	}

	/**
	 * Non block config selector style mapping.
	 *
	 * @return array
	 */
	abstract protected function get_css_config();


	/**
	 * The function needs to be used to print the blocks base css.
	 *
	 * @return void
	 */
	abstract protected function print_base_css();
}
