<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\APartlyWithConfig;

/**
 * Class Heading
 *
 * @package ToolsetBlocks\Block\Style\Block
 */
class Heading extends APartlyWithConfig {
	const KEY_STYLES_FOR_HEADING = 'heading';
	const KEY_MIGRATE_PRE_1_4_LINK_TEXT_DECORATION_COLOR = 'migrate_link_text_decoration_color';

	/** @var string h1,h2...h6 */
	private $tag;

	/**
	 * @return string
	 */
	public function get_css_block_class() {
		return '.tb-heading';
	}

	/**
	 * @param FactoryStyleAttribute $factory
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();
		if ( isset( $config['align'] ) ) {
			if ( $style = $factory->get_attribute( 'text-align', $config['align'] ) ) {
				$this->add_style_attribute( $style, self::KEY_STYLES_FOR_HEADING );
			}
		}
	}


	/**
	 * Grab content for some content dependentant style applies and headline tag.
	 *
	 * @param string $inner_html The block content.
	 *
	 * @return mixed|void
	 */
	public function make_use_of_inner_html( $inner_html ) {
		parent::make_use_of_inner_html( $inner_html );

		// Grab used headline tag.
		preg_match( '#\<(h[1-6])#', $inner_html, $matches );

		if ( isset( $matches[1] ) ) {
			$this->tag = $matches[1];
		}
	}

	protected function get_css_selector( $css_selector = self::CSS_SELECTOR_ROOT ) {
		// Determine css selector. If it's root there is no extra css selector required.
		$css_selector = $css_selector === self::CSS_SELECTOR_ROOT ? '' : $css_selector . ' ';

		$css_selector = substr( $css_selector, 0, 1 ) == ':' ? $css_selector : ' ' . $css_selector;

		return $this->get_css_selector_prepend() .
			$this->tag .
			$this->get_css_block_class() .
			'[data-' . str_replace( '/', '-', $this->get_name() ) . '="' .
			$this->get_id() . '"]' . $css_selector;
	}

	/**
	 * @return array[]
	 */
	protected function get_css_config() {
		return array(
			parent::CSS_SELECTOR_ROOT => array(
				self::KEY_STYLES_FOR_HEADING => array(
					'text-align',
				),
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
					'color',
					'text-align',
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
		$this->css_print( TB_PATH_CSS . '/heading.css' );
	}
}
