<?php

namespace ToolsetCommonEs\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Attribute\IAttribute;
use ToolsetCommonEs\Block\Style\Loader;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;
use ToolsetCommonEs\Utils\Config\Block;

abstract class ABlock implements IBlock {
	/**
	 * 'root' is an alias for the current block root element selector, which is a combination of name + clientId.
	 * @var string
	 */
	const CSS_SELECTOR_ROOT = 'root';

	/**
	 * 'common' styles holds all css attributes which are provided Toolset Common ES > Style Control Composition
	 * Most blocks are not using more than this common styles selection.
	 * @var string
	 */
	const KEY_STYLES_FOR_COMMON_STYLES = 'common';

	/**
	 * 'container' holds all css attributes which are provided Toolset Common ES > Container
	 * @var string
	 */
	const KEY_STYLES_FOR_CONTAINER = 'container';

	/**
	 * ':hover' pseudo class css attributes
	 *KEY_STYLES_FOR_HOVER
	 * @var string
	 */
	const KEY_STYLES_FOR_HOVER = ':hover';

	/**
	 * ':hover' pseudo class css attributes
	 *
	 * @var string
	 */
	const KEY_STYLES_FOR_ACTIVE = ':active';

	/**
	 * ':visited' pseudo class css attributes
	 *
	 * @var string
	 */
	const KEY_STYLES_FOR_VISITED = ':visited';

	/**
	 * ':focus' pseudo class css attributes
	 *
	 * @var string
	 */
	const KEY_STYLES_FOR_FOCUS = ':focus';

	/** @var string the id is build by using the clientId */
	private $id;

	/** @var string */
	private $name;

	/** @var array */
	private $block_config = array();

	/** @var array[string]IAttribute[] */
	private $style_attributes = array();

	/** @var bool */
	private $is_applied = false;

	/** @var Block */
	private $block_setup;

	/** @var string The block content. */
	private $content = '';

	/** @var ?\ToolsetCommonEs\Assets\Loader */
	private $assets_loader;

	/** @var string  */
	private $static_css = '';

	/** @var string */
	private $css_selector_prepend = '';

	/**
	 * ABlock constructor.
	 *
	 * @param array $block_config
	 * @param string $block_name_for_id_generation
	 * @param \ToolsetCommonEs\Assets\Loader|null $assets_loader
	 */
	public function __construct( $block_config, $block_name_for_id_generation = 'unknown', \ToolsetCommonEs\Assets\Loader $assets_loader = null ) {
		$block_config = is_array( $block_config ) ? $block_config : [];
		$this->set_id( $block_config, $block_name_for_id_generation );
		$this->set_block_config( $block_config );
		$this->assets_loader = $assets_loader;
	}

	public function add_block_setup( Block $block ) {
		$this->block_setup = $block;
	}

	public function get_block_setup() {
		return $this->block_setup;
	}


	/**
	 * Allows to prepend a selector to every block selector.
	 * The selector must be complete like '.class' or '#id'.
	 *
	 * @param string $prepend
	 */
	public function prepend_to_css_selector( $prepend ) {
		$this->css_selector_prepend = $prepend;
	}

	/**
	 * Apply a style attribute.
	 *
	 * @param IAttribute $style_attribute
	 * @param string $group
	 * @param string $responsive_device
	 *
	 * @param string|null $storage_key If not explicit set, the style attribute name will be used.
	 *
	 * @return void
	 */
	public function add_style_attribute(
		IAttribute $style_attribute,
		$group = self::KEY_STYLES_FOR_COMMON_STYLES,
		$responsive_device = null,
		$storage_key = null
	) {
		$storage_key = $storage_key ?: $style_attribute->get_name();
		if( ! $responsive_device || $responsive_device === Devices::DEVICE_DESKTOP ) {
			$this->style_attributes[ $group ][ $storage_key ] = $style_attribute;
			return;
		}

		$this->style_attributes[ $group ][ $responsive_device ][ $storage_key ] = $style_attribute;
	}

	/**
	 * The ID of the Block.
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param string $name
	 */
	public function set_name( $name ) {
		if( is_string( $name ) ) {
			$this->name = $name;
		}
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * @return string
	 */
	public function get_content() {
		return $this->content;
	}

	/**
	 * @return array
	 */
	public function get_block_config() {
		return $this->block_config;
	}

	public function get_advanced_styles_map() {
		return [];
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	public function filter_content( $content ) {
		return $content;
	}

	public function filter_block_content( $content, MobileDetect $device_detect ) {
		$name = $this->get_name();
		if( strpos( $name, 'toolset-blocks/' ) === 0 || strpos( $name, 'woocommerce-views/' ) === 0 ) {
			$name = str_replace( 'toolset-blocks/', 'tb-', $name );
			$name = str_replace( 'woocommerce-views/', 'wooviews-', $name );
			return $this->common_filter_block_content_by_block_css_class(
				$name,
				$content,
				$device_detect
			);
		}

		return $content;
	}

	protected function common_filter_block_content_by_block_css_class( $css_class, $content, MobileDetect $device_detect ) {
		$config = $this->get_block_config();
		$style = isset( $config['style'] ) ? $config['style'] : [];
		$block_alignment = $this->get_block_alignment( $style, $device_detect );

		if( ! $block_alignment ) {
			return $content;
		}

		return preg_replace(
			'/(class=[\"\'](?:.*?)' . $css_class . '(?:.*?))([\"\'])/',
			'$1 align'.$block_alignment.'$2',
			$content,
			1 // Only the first class needs to be adjusted.
		);
	}

	protected function get_block_alignment( $config, MobileDetect $device_detect ) {
		$desktop = isset( $config['blockAlign'] ) ? $config['blockAlign'] : false;

		$tablet = isset( $config[ DEVICES::DEVICE_TABLET ] ) &&
				  isset( $config[ DEVICES::DEVICE_TABLET ]['blockAlign'] ) ?
			$config[ DEVICES::DEVICE_TABLET ]['blockAlign'] :
			false;

		$phone = isset( $config[ DEVICES::DEVICE_PHONE ] ) &&
				 isset( $config[ DEVICES::DEVICE_PHONE ]['blockAlign'] ) ?
			$config[ DEVICES::DEVICE_PHONE ]['blockAlign'] :
			false;

		if( ! $desktop && ! $tablet && ! $phone ) {
			// no alignment
			return false;
		}

		// Current requesting device is a phone.
		if( $device_detect->isMobile() && ! $device_detect->isTablet() ) {
			if( $phone ) return $phone;
			if( $tablet ) return $tablet;
			return $desktop;
		}

		// Current requesting device is a tablet.
		if( $device_detect->isTablet() && $tablet ) return $tablet;


		// Current requesting device is a desktop.
		return $desktop;
	}

	/**
	 * @param array $config Optional to determine on which element css should go. If no config is given all styles will
	 *                        be applied to the root element.
	 *
	 *                        [self::CSS_SELECTOR_ROOT] =>
	 *                            [KEY_STYLES_FOR_COMMON_STYLES] => [ 'box-shadow', 'border' ]
	 *                        ['.a-css-selector' ]
	 *                            [KEY_STYLES_FOR_COMMON_STYLES] => [ 'background-color' ]
	 *                          ['.another-css-selector' ]
	 *                            [KEY_STYLES_CUSTOM] => 'all' (Loads all styles in KEY_STYLES_CUSTOM)
	 *
	 *                        This will apply 'box-shadow' and 'border' from 'common' to the root element
	 *                        and 'background-color' from 'common' to "[root] .a-css-selector".
	 *
	 *                        See ./Image.php for a real example.
	 *
	 * @param bool $force_apply
	 *
	 * @param null|string $responsive_device
	 *
	 * @return string
	 */
	public function get_css( $config = array(), $force_apply = false, $responsive_device = null ) {
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

		return $this->get_css_container() . $css;
	}

	/**
	 * Returns used font. Currently this only supports to have ONE font per block.
	 *
	 * @param array $devices List of devices.
	 * @param string $attribute Block config attribute name.
	 *
	 * @return array
	 */
	public function get_font( $devices = [ Devices::DEVICE_DESKTOP => true ], $attribute = 'style' ) {
		if( $this->get_block_setup() ) {
			// When the blocks uses a config, the get_fonts_by_setup() function will take care.
			return [];
		}

		$fonts = [];
		$config = $this->get_block_config();

		if( ! array_key_exists( $attribute, $config ) || ! is_array( $config[ $attribute ] ) ) {
			return $fonts;
		}

		$config_common = $config[ $attribute ];

		foreach( $devices as $device_key => $device_data ) {
			if( $device_key ===  Devices::DEVICE_DESKTOP ) {
				// Desktop data is stored on the root of $config.
				$config_device = $config_common;
			} else if( array_key_exists( $device_key, $config_common ) ) {
				// Device data is available.
				$config_device = $config_common[ $device_key ];
			} else {
				// No data. No font.
				continue;
			}

			if( ! empty( $config_device ) ) {
				if ( array_key_exists( 'font', $config_device ) ) {
					// Add font.
					$fonts[] = $this->get_font_by_config( $config_device );
				}
				foreach ( $this->get_pseudo_element_types() as $pseudo_element ) {
					if ( isset( $config_device[ $pseudo_element ] ) && isset( $config_device[ $pseudo_element ]['font'] ) ) {
						// Add font.
						$fonts[] = $this->get_font_by_config( $config_device[ $pseudo_element ] );
					}
				}
			}

			// Add font.
			if ( isset( $config_device['font'] ) ) {
				$fonts[] = [
					'family' => $config_device['font'],
					'variant' => isset( $config_device['fontVariant'] ) ?
						$config_device['fontVariant'] :
						'regular'
				];
			}
		}

		return $fonts;
	}

	/**
	 * Returns the font CSS data
	 *
	 * @param array $config Block config.
	 */
	private function get_font_by_config( $config ) {
		return [
			'family' => $config['font'],
			'variant' => isset( $config['fontVariant'] ) ?
				$config['fontVariant'] :
				'regular'
		];
	}

	public function make_use_of_inner_html( $inner_html ) {
		$this->content = $inner_html;
	}

	/**
	 * @return string
	 */
	public function get_css_block_class() {
		return '';
	}


	/**
	 * @return int
	 */
	public function get_html_root_element_count() {
		return 1;
	}

	public function load_style_attributes_by_setup( FactoryStyleAttribute $factory ) {
		return;
	}

	public function get_fonts_by_setup( $devices = [ Devices::DEVICE_DESKTOP => true ] ) {
		return [];
	}

	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {}

	/**
	 * @param array $styles
	 * @param string $style_group_key
	 * @param string|string[] $key
	 *
	 * @param string $responsive_device
	 *
	 * @return IAttribute|null
	 */
	protected function get_style_of_styles_by_key( $styles, $style_group_key, $key, $responsive_device = null ) {
		if( ! is_array( $styles ) || ! array_key_exists( $style_group_key, $styles ) ) {
			return null;
		}

		if( $key instanceof IAttribute ) {
			return $key;
		}

		$group_styles = $styles[ $style_group_key ];

		if( $responsive_device && $responsive_device !== Devices::DEVICE_DESKTOP ) {
			if( ! array_key_exists( $responsive_device, $group_styles ) ) {
				return null;
			}

			$group_styles = $group_styles[ $responsive_device ];
		}

		if( is_array( $key ) ) {
			if( ! array_key_exists( 'type', $key ) ) {
				return null;
			}
			$key = $key['type'];
		}

		if( ! array_key_exists( $key, $group_styles ) ) {
			return null;
		}

		return $group_styles[ $key ];
	}

	protected function get_css_container() {
		$styles = $this->get_style_attributes();

		if( empty( $styles ) ||
			! array_key_exists( self::KEY_STYLES_FOR_CONTAINER, $styles ) ||
			empty( $styles[ self::KEY_STYLES_FOR_CONTAINER ] )
		) {
			return '';
		}
		$css_selector_styles = '';
		$css_selector_transform_styles = array();
		foreach( $styles[ self::KEY_STYLES_FOR_CONTAINER ] as $style ) {
			if( $style->is_transform() ) {
				$css_selector_transform_styles[] = $style->get_css();
			} else {
				$css_selector_styles .= $style->get_css();
			}
		}

		if( ! empty( $css_selector_transform_styles ) ) {
			$css_selector_styles .= 'transform: ' . implode( ' ', $css_selector_transform_styles ) .';';
		}

		return ! empty( $css_selector_styles ) ?
			$this->get_css_selector_container() . ' { ' . $css_selector_styles . ' } ' :
			'';
	}

	/**
	 * @param array $styles
	 * @param string $responsive_device
	 *
	 * @return string
	 */
	protected function get_all_css_for_root_element( $styles, $responsive_device ) {
		$css = '';
		$transform_css = array();
		foreach( $styles as $group => $style_groups ) {
			/** @var IAttribute $style */
			if( $responsive_device && $responsive_device !== Devices::DEVICE_DESKTOP ) {
				if( ! isset( $style_groups[ $responsive_device ] ) ) {
					// No responsive styles.
					continue;
				}

				$style_groups = $style_groups[ $responsive_device ];
			}

			foreach( $style_groups as $style) {
				if( is_array( $style ) ) {
					continue;
				}
				if( $style->is_transform() ) {
					$transform_css[] = $style->get_css();
				} else {
					$css .= $style->get_css();
				}
			}
		}

		if( ! empty( $transform_css ) ) {
			$css .= 'transform: ' . implode( ', ', $transform_css ) . ';';
		}

		return ! empty( $css ) ? $this->get_css_selector() . ' { ' . $css . ' } ' : '';
	}

	/**
	 * @param string $css_selector
	 *
	 * @return string
	 */
	protected function get_css_selector( $css_selector = self::CSS_SELECTOR_ROOT ) {
		// Root selector
		$root_css_selector = $this->get_css_selector_root();

		return $this->get_css_selectors_with_root( $css_selector, $root_css_selector );
	}

	protected function get_css_selectors_with_root( $css_selector, $root_css_selector ) {
		$css_selectors = explode( '!', $css_selector );

		$selectors = array();

		foreach( $css_selectors as $selector ) {
			// Determine css selector. If it's root there is no extra css selector required.
			$selector = $selector === self::CSS_SELECTOR_ROOT ? '' : $selector;
			$selector = empty( $selector ) || substr( $selector, 0, 1 ) == ':' ? $selector : ' ' . $selector;

			$selectors[] = $root_css_selector . $selector;
		}

		return implode( ', ', $selectors );
	}

	/** @return string */
	protected function get_css_selector_prepend() {
		return ! empty( $this->css_selector_prepend )
			? $this->css_selector_prepend . ' '
			: '';
	}

	protected function get_css_selector_root() {
		return $this->get_css_selector_prepend() .
			$this->get_css_block_class() .
			'[data-' . str_replace( '/', '-', $this->get_name() ) . '="' . $this->get_id() . '"]';
	}

	protected function get_css_selector_container() {
		return '[data-tb-container="' . $this->get_id() . '"]';
	}

	/**
	 * @return array[string]IAttribute[]
	 */
	public function get_style_attributes() {
		return $this->style_attributes;
	}

	/**
	 * Set ID of the Block.
	 *
	 * @param array $block_config
	 * @param string $block_name_for_id_generation
	 */
	protected function set_id( $block_config, $block_name_for_id_generation = 'unknown' ){
		$this->filter_for_id_generation( $block_config );
		$json_config = json_encode( $block_config );
		$this->id = md5( $block_name_for_id_generation . $json_config );
	}

	private function filter_for_id_generation( &$config ) {
		if ( ! is_array( $config ) ) {
			return $config;
		}
		// List of ignored keys.
		$ignored_keys = [ 'content', 'translatedWithWPMLTM', 'wpmlTranslatedContent' ];

		foreach ( $config as $key => $value ) {
			if ( is_array ( $value ) ) {
				$config[ $key ] = $this->filter_for_id_generation( $value );
			}

			if ( $key === 'fontCode' ) {
				// Erase special characters of fontCode as these differ between original and Views cache.
				$config['fontCode'] = preg_replace( '/[^a-z0-9]/i', '', $config['fontCode'] );
			}

			if ( in_array( $key, $ignored_keys, true ) ) {
				unset( $config[ $key ] );
			}
		}

		return $config;
	}

	/**
	 * Returns true when the block is already applied.
	 *
	 * @return bool
	 */
	protected function is_applied() {
		return $this->is_applied;
	}

	/**
	 * Mark the block as applied.
	 */
	protected function mark_as_applied() {
		$this->is_applied = true;
	}

	/**
	 * Set the Block Config. Usually the raw config of what is stored on the block.
	 * @param array $block_config
	 */
	protected function set_block_config( $block_config ) {
		$this->block_config = is_array( $block_config ) ? $block_config : [];
	}

	/**
	 * Returns the list of possible pseudo elements types: `:hover`, `:visited`, ...
	 *
	 * @return array
	 */
	protected function get_pseudo_element_types() {
		return [
			self::KEY_STYLES_FOR_HOVER,
			self::KEY_STYLES_FOR_ACTIVE,
			self::KEY_STYLES_FOR_VISITED,
			self::KEY_STYLES_FOR_FOCUS,
		];
	}


	/**
	 * @param string $file The path of the file.
	 *
	 * @return string
	 */
	protected function get_css_file_content( $file ) {
		if ( ! $this->assets_loader ) {
			return '';
		}

		return $this->assets_loader->get_css_file_content( $file );
	}

	/**
	 * @param string $file Path of the file.
	 *
	 * @return void
	 */
	protected function css_print( $file ) {
		if ( ! $this->assets_loader ) {
			return;
		}

		$css = $this->assets_loader->css_print( $file, false );
		$this->static_css = $css ? $css : '';
	}


	/**
	 * @param array $expected_classes Classes to expect.
	 *
	 * @return string All existing classes as selector: .first-class.second-class.
	 */
	protected function get_existing_block_classes_as_selector( $expected_classes ) {
		$content = $this->get_content();
		$selector = '';
		foreach ( $expected_classes as $class ) {
			if ( strpos( $content, $class ) ) {
				$selector .= '.' . $class;
			}
		}

		return $selector;
	}


	/**
	 * @return string
	 */
	public function get_static_css() {
		return preg_replace( '#background-image:url\((["\'])?([a-z./-]*)(["\'])?\)(;)?#', '', $this->static_css );
	}
}
