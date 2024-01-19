<?php

namespace ToolsetCommonEs\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\ContentModifier\IContentModifier;
use ToolsetCommonEs\Block\Style\Attribute\IAttribute;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;
use ToolsetCommonEs\Utils\Config\Block;

/**
 * Interface IBlock
 * @package ToolsetCommonEs\Block\Style\Block
 *
 * @since 1.0.1
 */
interface IBlock {
	/**
	 * Id of the block.
	 *
	 * @return string
	 */
	public function get_id();


	/**
	 * Production ready css string. This string includes selectors and is not intended to be used for inline css.
	 *
	 * @param array $config
	 * @param bool $force_apply
	 * @param null|string $responsive_device
	 * @return string
	 */
	public function get_css( $config = array(), $force_apply = false, $responsive_device = null );

	/**
	 * The block config array provided by the WP core 'render_block' filter.
	 *
	 * @return array
	 */
	public function get_block_config();

	/**
	 * Everything except the common style storage key.
	 * For example, the block has button styles with :hover and :active tabs:
	 *	 return [
	 *		'buttonStyle' => [
	 *			self::KEY_STYLES_FOR_BUTTON => null,
	 *			self::KEY_STYLES_FOR_BUTTON_HOVER => ':hover',
	 *			self::KEY_STYLES_FOR_BUTTON_ACTIVE => ':active',
	 *		]
	 *	];
	 *
	 * The button styles are stored in the block config 'buttonStyle'. Everything which should go
	 * to self::KEY_STYLES_FOR_BUTTON is stored on the root (null) of 'buttonStyle'. Everything which should
	 * go to self::KEY_STYLES_FOR_BUTTON_HOVER (hover styles) is stored in the subkey ':hover'. And so on.
	 * The only special here is that null is targeting the root.
	 *
	 * @return array
	 */
	public function get_advanced_styles_map();

	/**
	 * Appends a style to the block.
	 *
	 * @param IAttribute $style_attribute
	 * @param string $group
	 * @param string $responsive_device
	 * @param string|null $storage_key
	 *
	 * @return mixed
	 */
	public function add_style_attribute( IAttribute $style_attribute, $group = '', $responsive_device = '', $storage_key = null );

	/**
	 * Allows the block to inject own style attributes. This is useful for styles not covered by common or some
	 * custom way of storing styles.
	 *
	 * @param \ToolsetCommonEs\Block\Style\Attribute\Factory $factory
	 *
	 * @return mixed
	 */
	public function load_block_specific_style_attributes( \ToolsetCommonEs\Block\Style\Attribute\Factory $factory );

	/**
	 * Allows to change the page content output.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function filter_content( $content );

	/**
	 * Allows to change block content output.
	 *
	 * @param string $content
	 * @param MobileDetect $device_detect
	 *
	 * @return mixed
	 */
	public function filter_block_content( $content, MobileDetect $device_detect );

	/**
	 * Returns used font. Currently this only supports to have ONE font per block.
	 *
	 * @param array $devices
	 * @param string $attribute Block config attribute name.
	 *
	 * @return array
	 */
	public function get_font( $devices = [], $attribute = 'style' );

	/**
	 * This is called before collecting CSS, for the case the block needs content information for building the
	 * css. This happens on the TB Heading block, which collects the tagname (h1/h2/..) from the content to add it
	 * to the css selector.
	 *
	 * @param string $inner_html
	 *
	 * @return mixed
	 */
	public function make_use_of_inner_html( $inner_html );

	/**
	 * Root class of the block. For example the heading block of TB returns '.tb-heading'. This is not required
	 * but helps to have a more specific selector. More specific selector means higher priority to get the style
	 * applied.
	 *
	 * @return string
	 */
	public function get_css_block_class();


	/**
	 * Currently all blocks have only one html root element, EXCEPT the countdown block, which has
	 * 4 root html elements (days / hours / minutes / seconds). This is important for the style id generation.
	 *
	 * @return int
	 */
	public function get_html_root_element_count();

	/**
	 * The name is misleading. It should be get_block_config, but that was already used in the past for
	 * the user settings on the block.
	 *
	 * Todo: Refactor naming.
	 *
	 * @return mixed
	 */
	public function get_block_setup();


	/**
	 * The name is misleading. It should be add_block_config. See function get_block_setup().
	 *
	 * Todo: Refactor naming.
	 *
	 * @param Block $block
	 *
	 * @return mixed
	 */
	public function add_block_setup( Block $block );


	/**
	 * Should be load_style_attributes_by_config. See function get_block_setup().
	 *
	 * Todo: Refactor naming.
	 *
	 * @param \ToolsetCommonEs\Block\Style\Attribute\Factory $factory
	 *
	 * @return mixed
	 */
	public function load_style_attributes_by_setup( \ToolsetCommonEs\Block\Style\Attribute\Factory $factory );


	/**
	 * Should be get_fonts_by_setup. See function get_block_setup().
	 *
	 * Todo: Refactor naming.
	 *
	 * @param array $devices
	 *
	 * @return array
	 */
	public function get_fonts_by_setup( $devices = [] );


	/**
	 * Returns the static css of a block.
	 * Notice: if the same type of block is used more than once, the second block won't return the static css
	 * as it's only needed once per request (and currently not needed for anything else).
	 *
	 * @return string
	 */
	public function get_static_css();


	/**
	 * Allows to prepend a selector to every block selector.
	 * The selector must be complete like '.class' or '#id'.
	 *
	 * @param string $prepend
	 */
	public function prepend_to_css_selector( $prepend );
}
