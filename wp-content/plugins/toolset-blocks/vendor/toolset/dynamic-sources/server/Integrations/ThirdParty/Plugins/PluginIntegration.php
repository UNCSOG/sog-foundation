<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty\Plugins;

use Toolset\DynamicSources\Integrations\ThirdParty\Configuration;

/**
 * Handles the automatic Dynamic Sources integration for the "Ultimate Addons for Gutenberg" plugin's blocks that require
 * server-side operations.
 */
abstract class PluginIntegration {
	/** @var array */
	private $integrated_blocks;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->integrated_blocks = get_option( Configuration::TOOLSET_CONFIG_OPTION_NAME, array() );
	}

	/**
	 * Initializes the class by hooking the relevant hooks.
	 */
	public function initialize() {
		add_filter( 'toolset/dynamic_sources/filters/third_party_block_integration_info', array( $this, 'get_third_party_block_integration_info' ), 10, 2 );
	}

	/**
	 * Gets the integration information for the specified block, if it's integrated.
	 *
	 * @param array $integration_info
	 * @param string $block_name
	 *
	 * @return array
	 */
	public function get_third_party_block_integration_info( $integration_info, $block_name ) {
		$integrated_block_info = $this->maybe_get_integrated_block( $block_name );
		return ! $integrated_block_info ? $integration_info : $integrated_block_info;
	}

	/**
	 * Gets the Dynamic Sources integration information for the specified block, if the block is integrated.
	 *
	 * @param string $block_name
	 *
	 * @return false|array
	 */
	protected function maybe_get_integrated_block( $block_name ) {
		return array_key_exists( $block_name, $this->integrated_blocks ) ? $this->integrated_blocks[ $block_name ] : false;
	}

	/**
	 * Sets the value of an array element, either it is nested or not.
	 *
	 * @param array        $array
	 * @param array|string $parents
	 * @param mixed        $value
	 * @param string       $glue
	 */
	protected function array_set_value( array &$array, $parents, $value, $glue = '.' ) {
		if ( ! is_array( $parents ) ) {
			$parents = explode( $glue, (string) $parents );
		}

		$ref = &$array;

		foreach ( $parents as $parent ) {
			if ( isset( $ref ) && ! is_array( $ref ) ) {
				$ref = array();
			}

			$ref = &$ref[ $parent ];
		}

		$ref = $value;
	}

	/**
	 * Injects the Dynamic Sources shortcode into the block attributes array, that are required for server-side operations.
	 *
	 * @param array  $block_attributes
	 * @param string $block_name
	 * @param bool   $do_shortcode
	 *
	 * @return array
	 */
	protected function replace_attributes_with_shortcodes_for_server_side_use( $block_attributes, $block_name, $do_shortcode = true ) {
		$block_integration_info = $this->maybe_get_integrated_block( $block_name );

		if ( ! $block_integration_info ) {
			return $block_attributes;
		}

		foreach ( $block_integration_info['dynamicAttributes'] as $dynamic_attribute_key => $dynamic_attribute_info ) {
			if (
				! isset( $dynamic_attribute_info['serverSideUse'] ) ||
				! isset( $block_attributes['serverSideUse'] ) ||
				! isset( $block_attributes['serverSideUse'][ $dynamic_attribute_key ] )
			) {
				continue;
			}

			$value = $do_shortcode ? do_shortcode( $block_attributes['serverSideUse'][ $dynamic_attribute_key ] ) : $block_attributes['serverSideUse'][ $dynamic_attribute_key ];

			$this->array_set_value( $block_attributes, $dynamic_attribute_key, $value );
		}

		return $block_attributes;
	}
}
