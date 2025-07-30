<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Block\APartlyWithConfig;

/**
 * Class SingleField
 *
 * @package ToolsetBlocks\Block\Style\Block
 */
class SingleField extends APartlyWithConfig {
	const KEY_MIGRATE_PRE_1_4_LINK_TEXT_DECORATION_COLOR = 'migrate_link_text_decoration_color';

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( [ 'tb-field' ] );
	}

	/**
	 * SingleField does no have a rootClass which is currently required by the Block Config.
	 * This works with both block config and non-block config, but only because the SingleField uses just very
	 * basic selector style mappings - so don't take this as a general working solution.
	 *
	 * @param string $css_selector
	 * @param string $root_css_selector
	 *
	 * @return string
	 */
	protected function get_css_selectors_with_root( $css_selector, $root_css_selector = '' ) {
		$root_css_selector = $this->get_css_selector_root();

		// For legacy css.
		if ( trim( $css_selector ) === 'root' ) {
			return $root_css_selector;
		}

		return $root_css_selector . ' ' . $css_selector;
	}

	/**
	 * @return array
	 */
	protected function get_css_config() {
		return array(
			parent::CSS_SELECTOR_ROOT => array(
				parent::KEY_STYLES_FOR_COMMON_STYLES => array(
					'font-size',
					'font-family',
					'font-style',
					'font-weight',
					'line-height',
					'letter-spacing',
					'text-decoration',
					'text-shadow',
					'text-transform',
					'text-align',
					'color',
					'background-color',
					'border-radius',
					'padding',
					'margin',
					'box-shadow',
					'border',
					'display',
				),
			),
			'a' => array(
				self::KEY_MIGRATE_PRE_1_4_LINK_TEXT_DECORATION_COLOR => array(
					'color',
					'text-decoration',
				),
			),
		);
	}

	/**
	 * Static css of the block.
	 *
	 * @return void
	 */
	protected function print_base_css() {
		$this->css_print( TB_PATH_CSS . '/single-field.css' );
	}
}
