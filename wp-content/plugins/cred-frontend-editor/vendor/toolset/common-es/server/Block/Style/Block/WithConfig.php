<?php


namespace ToolsetCommonEs\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;
use ToolsetCommonEs\Utils\Config\Block;

/**
 * Class Common
 *
 * A couple of blocks don't have anything specific and just using Style Settings and Container.
 *
 * @package ToolsetCommonEs\Block\Style\Block
 */
class WithConfig extends ABlock {
	/** @var array */
	private $block_config;

	/** @var string */
	private $active_template_source;


	/**
	 * WithConfig constructor.
	 *
	 * @param array $block_config
	 * @param Block $config
	 * @param string $block_name_for_id_generation
	 * @param \ToolsetCommonEs\Assets\Loader|null $assets_loader
	 */
	public function __construct( $block_config, Block $config, $block_name_for_id_generation = null, \ToolsetCommonEs\Assets\Loader $assets_loader = null ) {
		$block_name_for_id_generation = $block_name_for_id_generation ?: $config->get_slug();
		parent::__construct( $block_config, $block_name_for_id_generation, $assets_loader );
		$this->add_block_setup( $config );
	}

	public function get_block_config() {
		if( $this->block_config === null ) {
			$this->block_config = $this->get_block_setup()->merge_template_source_defaults_to_attributes( parent::get_block_config() );
		}

		return $this->block_config;
	}

	public function get_active_template_source() {
		if( $this->active_template_source === null ) {
			$this->active_template_source = $this->get_block_setup()
												 ->get_active_template_source( $this->get_block_config() );
		}

		return $this->active_template_source;
	}

	/**
	 * Refactor: this is currently nearly a copy of the ABlock.
	 * The only difference is that it uses tabs from the config.
	 */
	public function get_css( $config = array(), $force_apply = false, $responsive_device = null ) {
		$config = $this->get_block_setup()
					   ->get_style_map( $this->get_active_template_source() );

		if( $this->is_applied() && ! $force_apply ) {
			return '';
		}

		$this->mark_as_applied();
		$styles = $this->get_style_attributes();

		if( empty( $styles ) ) {
			return '';
		}

		if( empty( $config ) ) {
			return $this->get_css_container() . $this->get_all_css_for_root_element( $styles, $responsive_device );
		}

		$css = '';

		foreach ( $config as $css_selector => $style_groups ) {
			foreach ( $style_groups as $style_group_key => $styles_keys ) {
				$tabs = $this->get_tabs_of_storage_key( $style_group_key );

				foreach ( $tabs as $tab ) {
					$has_pseudo_class_placeholder = preg_match( '/%pseudoClass%/', $css_selector );
					if( empty( $tab['storageKey' ] ) && ! $has_pseudo_class_placeholder ) {
						continue;
					}
					$tab_storage_key = $tab['storageKey'];
					$pseudo_class = $tab['pseudoClass'];
					if ( array_key_exists( "$style_group_key$tab_storage_key", $styles ) ) {
						$used_styles = $styles[ "$style_group_key$tab_storage_key" ];

						// Get device data if not Desktop (which stores on root).
						if ( $responsive_device && $responsive_device !== Devices::DEVICE_DESKTOP ) {
							if ( ! array_key_exists( $responsive_device, $used_styles ) ) {
								// No explicit device data. Skip.
								continue;
							}
							$used_styles = $used_styles[ $responsive_device ];
						}

						$pseudo_selector = $has_pseudo_class_placeholder ?
							str_replace( '%pseudoClass%', $pseudo_class, $css_selector ) :
							"$css_selector$pseudo_class";
						if( array_key_exists( $pseudo_selector, $config ) ) {
							$config[ $pseudo_selector ] = [];
						}
						$config[ $pseudo_selector ][ "$style_group_key$tab_storage_key" ] =
							array_filter(
								$used_styles,
								function( $tab_style_key ) use ( $styles_keys ){
									return array_key_exists( $tab_style_key, $styles_keys );
								},
								ARRAY_FILTER_USE_KEY
							);

						if( $has_pseudo_class_placeholder ) {
							unset( $config[ $css_selector ] );
						}
					}
				}
			}
		}

		foreach ( $config as $css_selector => $style_groups ) {
			$css_selector_styles = '';
			$css_selector_transform_styles = array();

			foreach ( $style_groups as $style_group_key => $styles_keys ) {
				// Instead of an array with keys 'all' can be used to load all registered styles.
				if( $styles_keys === 'all' ) {
					if( array_key_exists( $style_group_key, $styles ) ) {
						$group_styles = $styles[ $style_group_key ];
						if( $responsive_device && $responsive_device !== Devices::DEVICE_DESKTOP ) {
							if( ! isset( $group_styles[ $responsive_device ] ) ) {
								// No responsive styles.
								continue;
							}

							$group_styles = $group_styles[ $responsive_device ];
						}

						foreach( $group_styles as $style) {
							if( is_array( $style ) ) {
								continue;
							}

							if( $style->is_transform() ) {
								$css_selector_transform_styles[] = $style->get_css();
							} else {
								$css_selector_styles .= $style->get_css();
							}
						}
					}
					continue;
				}

				if( ! is_array( $styles_keys ) ) {
					// Not 'all' and no array given. Skip to avoid breaking.
					continue;
				}

				foreach ( $styles_keys as $style_key_new_format => $style_key_old_format ) {
					$style_key = is_array( $style_key_old_format ) && array_key_exists( 'type', $style_key_old_format) ?
						$style_key_new_format :
						$style_key_old_format;

					if( ! $style = $this->get_style_of_styles_by_key( $styles, $style_group_key, $style_key, $responsive_device ) ) {
						continue;
					}

					if ( $style->is_transform() ) {
						$css_selector_transform_styles[] = $style->get_css();
					} else {
						$css_selector_styles .= $style->get_css();
					}
				}
			}

			if( ! empty( $css_selector_transform_styles ) ) {
				$css_selector_styles .= 'transform: ' . implode( ' ', $css_selector_transform_styles ) .';';
			}

			$css .= ! empty( $css_selector_styles ) ?
				$this->get_css_selector( $css_selector ) . ' { ' . $css_selector_styles . ' } ' :
				'';
		}

		return $this->apply_individual_css( $this->get_css_container() . $css );
	}

	protected function get_css_selector( $css_selector = '' ) {
		return $this->get_css_selectors_with_root( $css_selector );
	}

	protected function get_css_selectors_with_root( $css_selector, $root_css_selector = '' ) {
		$template_selector = $this->get_block_setup()->get_root_css_class( $this->get_active_template_source() );
		$css_selectors = explode( '!', $css_selector );

		$selectors = array();

		foreach( $css_selectors as $selector ) {
			$root_css_selector = preg_match( '#(\.' . $template_selector . ')($|\:|\s)#i', $selector ) ?
				$this->get_css_selector_root() :
				$this->get_css_selector_root_with_template_selector();

			// Determine css selector. If it's root there is no extra css selector required.
			$selector = $selector === self::CSS_SELECTOR_ROOT ? '' : $selector;
			$selector = empty( $selector ) || substr( $selector, 0, 1 ) == ':' ? $selector : ' ' . $selector;

			// When the template selector already exists and it's using an ampersand to be attached to the root selector
			// it will be formated like .root-selector .&template-selector due to the previous formatting. We fix this
			// case here as a hotfix.
			// Todo: cleanup the complete selector creation from root.
			$full_selector = $root_css_selector . $selector;
			$selectors[] = str_replace( ' .&', '.', $full_selector );
		}

		return implode( ', ', $selectors );
	}

	protected function apply_individual_css( $css ) {
		return $css;
	}

	protected function get_block_config_for_css() {
		return $this->get_block_config();
	}

	public function load_style_attributes_by_setup( FactoryStyleAttribute $factory ) {
		if( ! $block_config = $this->get_block_setup() ) {
			return;
		}

		$fields = $block_config->get_fields();

		$devices = array(
			Devices::DEVICE_DESKTOP,
			Devices::DEVICE_PHONE,
			Devices::DEVICE_TABLET,
		);

		// Styles provided by the "Style Settings" section.
		foreach( $devices as $device_key ) {
			foreach ( $fields as $storage_key => $field ) {
				$tabs = $this->get_tabs_of_storage_key( $storage_key );

				foreach( $tabs as $tab ) {
					$tab_storage_key = $tab['storageKey'];
					$styles = $factory->load_common_attributes_by_array(
						$this->get_block_config_for_css(),
						$storage_key,
						$tab_storage_key,
						$device_key,
						$fields[ $storage_key ]
					);

					if ( ! empty( $styles ) ) {
						foreach ( $styles as $key => $style ) {
							$this->add_style_attribute( $style, "$storage_key$tab_storage_key", $device_key, $key );
						}
					}
				}
			}
		}
	}

	public function get_fonts_by_setup( $devices = [ Devices::DEVICE_DESKTOP => true ] ) {
		if( ! $block_config = $this->get_block_setup() ) {
			return [];
		}

		$fonts = [];
		$block_fields = $block_config->get_fields();
		$block_values = $this->get_block_config();

		foreach( $block_fields as $storage_key => $fields ) {
			if( ! array_key_exists( $storage_key, $block_values ) ) {
				// This storage key has no values.
				continue;
			}

			$tabs = $this->get_tabs_of_storage_key( $storage_key );

			foreach( $tabs as $tab ) {
				if( ! array_key_exists( $storage_key, $block_values ) ) {
					// No values for the storage key.
					continue;
				}

				$storage_values = $block_values[ $storage_key ];

				if( ! empty( $tab[ 'storageKey' ] ) ) {
					if( ! array_key_exists( $tab['storageKey'], $storage_values ) ) {
						// Tab exists, but no values for it. No need to look for fonts.
						continue;
					}
					$storage_values = $storage_values[ $tab['storageKey'] ];
				}

				foreach( $fields as $field_storage_key => $field ) {
					if( ! is_array( $field ) ||
						! array_key_exists( 'type', $field ) ||
						$field['type'] !== 'font' ) {
						// No font field.
						continue;
					}

					// Check fonts for all devices.
					foreach( $devices as $device_key => $device_data ) {
						if( $device_key ===  Devices::DEVICE_DESKTOP ) {
							// Desktop data is stored on the root of $config.
							$device_values = $storage_values;
						} else if( array_key_exists( $device_key, $storage_values ) ) {
							// Device data is available.
							$device_values = $storage_values[ $device_key ];
						} else {
							// No data for the device. Continue.
							continue;
						}

						if( ! is_array( $device_values ) || ! array_key_exists( $field_storage_key, $device_values ) ) {
							// No font for this device.
							continue;
						}

						// Add font.
						$fonts[] = [
							'family' => $device_values[ $field_storage_key ],
							// Todo fontVariant could have a custom key.
							'variant' => isset( $device_values['fontVariant'] ) ?
								$device_values['fontVariant'] :
								'regular'
						];
					}
				}
			}
		}
		return $fonts;
	}

	private function get_tabs_of_storage_key( $storage_key ) {
		$tabs = $this->get_block_setup()->get_tabs_of_storage_key( $storage_key );

		if( empty( $tabs ) ) {
			// Apply dummy tab, with null for storageKey. Has the tab null the root will be used.
			// This allows to always use logic as there would be tabs.
			$tabs[] = [
				'storageKey' => null,
			];
		}

		foreach( $tabs as $tab_key => $tab ) {
			// Make sure to have storageKey for all tabs. If none is set use null.
			// Saves validation on each function => create a class for tab.
			if( ! is_array( $tab ) || ! array_key_exists( 'storageKey', $tab ) ) {
				$tabs[ $tab_key ]['storageKey' ] = null;
			}
		}

		return $tabs;
	}

	protected function find_in_block_values( $needle, $return_on_false = false ) {
		$block_values = $this->get_block_config();
		return $this->get_block_setup()->find_in( $needle, $block_values, $return_on_false );
	}

	protected function get_css_selector_root_with_template_selector() {
		$template_selector = $this->get_block_setup()->get_root_css_class( $this->get_active_template_source() );
		$space_between = ' ';

		if( substr( $template_selector, 0, 1 ) === '&' ) {
			$space_between = '';
			// Remove the & from the template selector.
			$template_selector = substr( $template_selector, 1, strlen( $template_selector ) - 1 );
		}

		return $this->get_css_selector_root().$space_between.'.'.$template_selector;
	}

	/**
	 * This forwards to the non Block Config get_css() function.
	 * It's needed to allow blocks to migrate to Blocks Config step by step.
	 *
	 * @param array $config
	 * @param false $force_apply
	 * @param null $responsive_device
	 *
	 * @return string
	 */
	public function get_css_no_block_config( $config = array(), $force_apply = false, $responsive_device = null ) {
		return parent::get_css( $config, $force_apply, $responsive_device );
	}
}
