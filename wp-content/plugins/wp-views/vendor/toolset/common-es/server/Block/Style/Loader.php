<?php

namespace ToolsetCommonEs\Block\Style;

use ToolsetCommonEs\Block\Style\Block\Factory;
use ToolsetCommonEs\Block\Style\Block\IBlock;
use ToolsetCommonEs\Block\Style\Responsive\Devices\Devices;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;
use ToolsetCommonEs\Library\WordPress\Actions as WPActions;
use ToolsetCommonEs\Utils\SettingsStorage;

class Loader {
	const ROOT_CSS_SELECTOR_ID = 'tbcss';

	/** @var Factory  */
	private $block_factory;

	/** @var IBlock[] */
	private $blocks = array();

	/** @var WPActions */
	private $wp_actions;

	/** @var Devices */
	private $responsive_devices;

	/** @var array */
	private $blocks_applied = array();

	/** @var array */
	private $applied_styles = [];

	/**
	 * Blocks Style Backup is needed for reappling style when automatic excerpt is used.
	 * Because WP does trimming on the content and removes our structure.
	 * This is used to reapply it after trimming of WP was done.
	 * KEY = base64 encoded style (that's left after WP trim)
	 * VALUE = complete structure, div > base64 code && script to move it to head
	 *
	 * @var array
	 */
	private $blocks_style_backup = array();

	/** @var MobileDetect */
	private $device_detect;

	/** @var \ToolsetCommonEs\Assets\Loader */
	private $assets_loader;

	/** @var string */
	private $selector_body_id = '';

	/**
	 * Loader constructor.
	 *
	 * @param Factory $block_factory
	 * @param WPActions $wp_actions
	 * @param Devices $responsive_devices
	 * @param MobileDetect $device_detect
	 * @param \ToolsetCommonEs\Assets\Loader $assets_loader
	 * @param SettingsStorage $settings_storage
	 */
	public function __construct(
		Factory $block_factory,
		WPActions $wp_actions,
		Devices $responsive_devices,
		MobileDetect $device_detect,
		\ToolsetCommonEs\Assets\Loader $assets_loader,
		SettingsStorage $settings_storage
	) {
		$this->block_factory = $block_factory;
		$this->wp_actions = $wp_actions;
		$this->responsive_devices = $responsive_devices;
		$this->device_detect = $device_detect;
		$this->assets_loader = $assets_loader;

		$this->apply_settings_for_frontend_script( $settings_storage );

		$this->wp_actions->add_filter( 'render_block', array( $this, 'register_block' ), 10, 2 );
		$this->wp_actions->add_filter( 'wpv_filter_wpv_view_shortcode_output', array( $this, 'views_pagination_content_filter' ), 10, 2 );
		$this->wp_actions->add_filter( 'wpv_filter_wpv_view_shortcode_output', array( $this, 'maybe_apply_blocks_style_by_script_to_head' ), PHP_INT_MAX - 1, 1 );
		$this->wp_actions->add_filter( 'wpv_filter_wpv_widget_output', array( $this, 'apply_blocks_style_by_script_to_head' ) );
		$this->wp_actions->add_action( 'wp', array( $this, 'hook_up_frontend' ) );
		$this->wp_actions->add_filter( 'wpv-pre-do-shortcode', array( $this, 'hook_to_wpv_post_do_shortcode' ) );

		$this->wp_actions->add_filter( 'toolset/dynamic_sources/actions/register_sources', array( $this, 'hook_to_dynamic_sources' ) );

	}

	public function hook_up_frontend() {
		$doing_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;

		if( ! $doing_rest_request ) {
			$this->wp_actions->add_filter( 'wpv_filter_wpv_view_shortcode_output', array( $this, 'maybe_apply_blocks_style_by_script_to_head' ), PHP_INT_MAX - 1, 1 );
			$this->wp_actions->add_filter( 'toolset_the_content_wpa', array( $this, 'apply_blocks_style_by_script_to_head' ), PHP_INT_MAX - 1, 1 );
			$this->wp_actions->add_filter( 'wp_trim_excerpt', array( $this, 'repair_block_style_in_trimmed_text' ), PHP_INT_MAX, 1 );
			$this->wp_actions->add_filter( 'wp_trim_words', array( $this, 'repair_block_style_in_trimmed_text' ), PHP_INT_MAX, 1 );
		}
	}


	/**
	 * Attach style apply to WP Filter 'the_content'.
	 */
	private function attach_style_apply_to_the_content() {
		if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			$this->wp_actions->add_filter(
				'the_content',
				array( $this, 'apply_blocks_style_by_script_to_head' ),
				PHP_INT_MAX - 1,
				1
			);
		}
	}

	/**
	 * Detach style apply of WP Filter 'the_content'.
	 */
	private function detach_style_apply_to_the_content() {
		if ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			$this->wp_actions->remove_filter(
				'the_content',
				array( $this, 'apply_blocks_style_by_script_to_head' ),
				PHP_INT_MAX - 1
			);
		}
	}

	public function hook_to_dynamic_sources() {
		$this->wp_actions->add_filter( 'render_block', array( $this, 'register_block' ), 10, 2 );
		// THIS SHOULD NO LONGER BE NEEDED DUE TO CHANGES ON views-2911
		// $this->wp_actions->add_filter( 'the_content', array( $this, 'apply_blocks_style_directly' ), PHP_INT_MAX, 1 );
	}

	/**
	 * This is called on 'wpv-pre-do-shortcode'. Simply because REST_REQUEST is not loaded on init.
	 *
	 * @param string $content
	 *
	 * @return mixed
	 */
	public function hook_to_wpv_post_do_shortcode( $content ) {
		// only need to hook once
		$this->wp_actions->remove_filter( 'wpv-pre-do-shortcode', array( $this, 'hook_to_wpv_post_do_shortcode' ) );

		// When on admin or on a rest call we need to apply the style directly to the loop item.
		if( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			// this is for the preview of Views on posts
			$this->wp_actions->add_filter( 'wpv-post-do-shortcode', array( $this, 'apply_blocks_style_directly' ), 10, 2 );
		}

		// also need to hook to render_block() for collecting block css
		$this->wp_actions->add_filter( 'render_block', array( $this, 'register_block' ), 10, 2 );

		// THIS SHOULD NO LONGER BE NEEDED DUE TO CHANGES ON views-2911
		// add 'apply_blocks_style_directly' to views after render hook
		// $this->wp_actions->add_filter( 'the_content', array( $this, 'apply_blocks_style_by_script_to_head' ), PHP_INT_MAX - 1, 1 );

		return $content;
	}

	public function maybe_apply_blocks_style_by_script_to_head( $content ) {
		if ( has_blocks( $content ) ) {
			return $this->apply_blocks_style_by_script_to_head( $content );
		}
		return $content;
	}

	public function apply_blocks_style_by_script_to_head( $content ) {
		global $wp_current_filter;

		if ( in_array( 'get_the_excerpt', $wp_current_filter ) ) {
			// Do not touch excerpts.
			return $content;
		}

		$content = $this->block_specific_content_filter( $content );

		if ( empty( $content ) || is_feed() ) {
			return $content;
		}

		$is_admin = is_admin();
		$is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
		$is_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;

		// Reverse blocks. This is important to have inner blocks styles AFTER outer blocks as later applied style
		// has an higher priority. Otherwise the inner blocks style would not be applied.
		$reversed_blocks = array_reverse( $this->blocks );
		$devices = $this->responsive_devices->get();
		$media_styles = '';

		$media_break_points = array_map( function( $device_info ) {
			return isset( $device_info['maxWidth'] ) && $device_info['maxWidth'] ?
				(int) $device_info['maxWidth'] :
				PHP_INT_MAX;
		}, $devices );

		asort( $media_break_points );

		foreach ( $devices as $device_key => $device_info ) {
			$style = '';

			foreach ( $reversed_blocks as $block_key => $block ) {
				if ( false === strpos( $content, $block_key ) ) {
					// Avoid adding styles to external blocks processed over the_content filters.
					// While one of our blocks might live in the post content, rendering a 3rd party shortcode full of blocks
					// can lead to our styles being attached to each of those inner, external blocks.
					// Let's add our block styles only when processing a piece of content containing that block.
					// Also, per a comment below, we tend to apply styles to a block up to two times (due to Views loops).
					// If a 3rd party shortcode fires the the_content filter internally more than once, then our block might miss its own styles.
					continue;
				}
				$this->blocks_applied[] = $block->get_id();
				$block->prepend_to_css_selector( $this->selector_body_id );
				$style .= $block->get_css( [], true, $device_key );

				// Static CSS
				$static_block_css = $block->get_static_css();

				if ( ! empty( $static_block_css ) && ! $is_admin && ! $is_rest && ! $is_ajax ) {
					// @codingStandardsIgnoreStart
					// No need to escape the encoded styles.
					$style_encoded = base64_encode( $static_block_css );
					echo '<div class="tces-js-style-encoded" style="display:none;">' .
						$style_encoded .
						'</div>' .
						$this->js_to_move_style_to_head();
					// @codingStandardsIgnoreEnd
				}
			}

			if ( empty( $style ) ) {
				continue;
			}

			$media_styles = $this->min_max_media_query_for_display( $device_key, $device_info, $style, $media_break_points, $media_styles );
		}

		if( ! empty( $media_styles ) ) {
			$style_encoded =  base64_encode( $media_styles );

			// Check if the style was already applied on the current current_filter or on 0 or on 1.
			$current_filter = $this->wp_actions->current_filter();
			$applied_styles = array_key_exists( $current_filter, $this->applied_styles ) ?
				(array) $this->applied_styles[ $current_filter ] :
				[];

			// There is some oddness when using DS post_content inside a View. The first apply of styles
			// going into a black hole, so we need to apply the styles at least twice. This will probably lead
			// to some duplicated encoded styles transfers when no DS post content is used. (Anyway does not hurt,
			// there will only be one encoded, but it's useless to transfer twice the same data).
			if ( array_key_exists( $style_encoded, $applied_styles ) && $applied_styles[ $style_encoded ] >= 2 ) {
				// The same style was already rendered. Abort.
				return $content;
			}

			$style_div_with_script = '<div class="tces-js-style-encoded" style="display:none;">' .
								$style_encoded .
								'</div>' .
								$this->js_to_move_style_to_head();

			// Put to backup for later re-apply. In the case WP excerpt trim runs.
			$this->blocks_style_backup[ $style_encoded ] = $style_div_with_script;

			// Apply to content.
			$content = $style_div_with_script . $content;

			// Add style to applied styles collection.
			$this->applied_styles[ $current_filter ] = array_key_exists( $current_filter, $this->applied_styles ) ?
				$this->applied_styles[ $current_filter ] :
				[];

			if ( array_key_exists( $style_encoded, $this->applied_styles[ $current_filter ] ) ) {
				$this->applied_styles[ $current_filter ][ $style_encoded ]++;
			} else {
				$this->applied_styles[ $current_filter ][ $style_encoded ] = 1;
			}
		}
		$fonts = $this->block_fonts();
		$font_sets = array();
		foreach( $fonts as $family => $variants ) {
			$font_sets[] = str_replace( ' ', '+', $family ) . ':' . implode( ',', $variants );
		}

		if( ! empty( $font_sets ) ) {
			$font_sets_encoded = base64_encode( implode( '###', $font_sets ) );
			$font_sets_div_with_script = '<div class="tces-js-font-encoded" style="display:none;">' .
										 $font_sets_encoded .
										 '</div>' .
										 $this->js_to_move_fonts_to_head();

			// Put to backup for later re-apply. In the case WP excerpt trim runs.
			$this->blocks_style_backup[ $font_sets_encoded ] = $font_sets_div_with_script;

			// Apply to content.
			$content = $font_sets_div_with_script . $content;
		}

		return $content;
	}

	public function apply_blocks_style_directly( $content, $doing_excerpt ) {
		$responsive_device = apply_filters( 'wpv_view_block_preview_for_responsive_device', null );

		if( $doing_excerpt || empty( $responsive_device ) ) {
			return $content;
		}
		$devices = $this->responsive_devices->get();
		$content = $this->block_specific_content_filter( $content );

		$fonts = $this->block_fonts();
		$font_sets = array();
		foreach( $fonts as $family => $variants ) {
			$font_sets[] =  str_replace( ' ', '+', $family ) . ':' . implode( ',', $variants );
		}

		$content = empty( $font_sets )
			? $content
			: '<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=' . implode( '|', $font_sets ) . '" />' . $content;

		$style = '';

		// Reverse blocks. This is important to have inner blocks styles AFTER outer blocks as later applied style
		// has an higher priority. Otherwise the inner blocks style would not be applied.
		$reversed_blocks = array_reverse( $this->blocks );

		foreach( $reversed_blocks as $block ) {
			if ( in_array( $block->get_id(), $this->blocks_applied ) ) {
				// already applied
				continue;
			}

			foreach( $devices as $device_key => $device_data ) {
				$this->blocks_applied[] = $block->get_id();
				$style .= $block->get_css( array(), true, $device_key );

				if( $device_key === $responsive_device ) {
					// Only load css until the current selected device is reached.
					// When tablet is selected: Desktop is loaded first, then tablet and phone is skipped.
					// Why not only loading current selected device's style? Cascading needs to be kept!
					break;
				}
			}
		}


		$content = empty( $style )
			? $content
			: '<style>' . $style . '</style>' . $content;

		return $content;
	}

	/**
	 * Returns the style of a block.
	 *
	 * @param string $block_content
	 * @param \WP_Block_Parser_Block $block The class typing is not part of the parameter list as the parse blocks
	 *                                        parser can be alter by a filter. So it's not really sure if we get
	 *                                        WP_Block_Parser_Block here. Check that params exist before use!
	 *
	 * @return string
	 */
	public function register_block( $block_content, $block ) {
		try {
			$this->detach_style_apply_to_the_content();
			$supported_block = $this->block_factory->get_block_by_array( $block );

			if( $supported_block ) {
				if( ! array_key_exists( $supported_block->get_id(), $this->blocks ) ) {
					// Register the first instance of the block. Multiple instances are possible, e.g. in a View loop.
					$this->blocks[ $supported_block->get_id() ] = $supported_block;

					// Load style attributes. This is only needed for the first instance of the block.
					$this->block_factory->load_styles_attributes( $supported_block );

					// Workaround for Views/WPA as it does not support filter_block_content().
					// The 'on_register' method is not part of the interface and currently only the
					// YouTube block needs this workaround for Views.
					if( method_exists( $supported_block, 'on_register' ) ) {
						$supported_block->on_register();
					}
				}

				// Following applies the data-id to the block, which is used to apply the block styles.
				// It's important that this runs for every instance of the block and not only for the
				// first, which is used to apply the styles.
				$updated_content = preg_replace_callback(
					'/(data-'. str_replace( '/', '-', $supported_block->get_name() ) .')=\"([^\"]*)\"/',
					function( $matches ) use ( $supported_block ) {
						return $matches[1] . '="' . $supported_block->get_id() . '"';
					},
					$block_content,
					$supported_block->get_html_root_element_count()
				);

				$updated_content = $supported_block->filter_block_content( $updated_content, $this->device_detect );

				return $updated_content;
			}
		} catch( \Exception $e ) {
			// Something went wrong, which may end in an unexpected display on the frontend.
			// Do nothing.
		} finally {
			$this->attach_style_apply_to_the_content();
		}

		return $block_content;
	}

	public function repair_block_style_in_trimmed_text( $text ) {
		if( empty( $this->blocks_style_backup ) || strpos( $text, 'tces-js-style-encoded' ) !== false ) {
			// No block styling at all or already repaired.
			return $text;
		}

		// Replace all style base64 strings by the complete required html structure / scripts.
		return str_replace(
			array_keys( $this->blocks_style_backup ),
			array_values( $this->blocks_style_backup ),
			$text
		);
	}

	private function block_specific_content_filter( $content ) {
		foreach( $this->blocks as $block ) {
			$content = $block->filter_content( $content );
		}

		return $content;
	}

	/**
	 * This is required for views ajax pagination. Otherwise DS inside styles (like background of Container) are
	 * not applied on page switches.
	 *
	 * @param string $content
	 * @param int $id
	 *
	 * @return mixed
	 */
	public function views_pagination_content_filter( $content, $id ) {
		foreach( $this->blocks as $block ) {
			$content = $block->filter_content( $content );
		}

		return $content;
	}

	private function block_fonts() {
		$fonts = array();
		foreach( $this->blocks as $block ) {
			$block_fonts = array_merge(
				$block->get_font( $this->responsive_devices->get() ),
				$block->get_fonts_by_setup( $this->responsive_devices->get() )
			);

			foreach( $block_fonts as $block_font ) {
				$family = $block_font['family'];
				$variant = $block_font['variant'];

				if( ! isset( $fonts[ $family ] ) ) {
					// New font.
					$fonts[ $family ] = array( $variant );
				} elseif( ! in_array( $variant, $fonts[ $family ] ) ) {
					// Existing font, but variant is new.
					$fonts[ $family ][] = $variant;
				}
			}
		}

		return $fonts;
	}

	private function js_to_move_style_to_head() {
		return '<script class="tces-js-style-to-head">toolsetCommonEs.styleToHead()</script>';
	}

	private function js_to_move_fonts_to_head() {
		return '<script class="tces-js-font-to-head">toolsetCommonEs.fontToHead()</script>';

	}


	/**
	 * Current there's only one setting: Add body id to selectors
	 * When it's active the information needs to be passed to the script.
	 *
	 * @param SettingsStorage $settings_storage
	 */
	private function apply_settings_for_frontend_script( SettingsStorage $settings_storage ) {
		if ( is_admin() ) {
			return;
		}

		$apply_block_to_id =
			$settings_storage->get_setting(
				$settings_storage::SETTINGS_ADD_BLOCK_ID_TO_SELECTORS,
				false
			);

		if ( $apply_block_to_id ) {
			$this->selector_body_id = '#' . self::ROOT_CSS_SELECTOR_ID;
		}

		if ( ! empty( $this->selector_body_id ) ) {
			wp_localize_script(
				'toolset-common-es-frontend',
				'tcesData',
				[ 'bodyIdToSelectors' => self::ROOT_CSS_SELECTOR_ID ]
			);
		}
	}

	/**
	 * This will put 'display: none' attributes into a media query with min and max width.
	 * Reason: The GUI shows Hide Element, but that settings should not be inherited by lower devices like
	 * other style attributes like color, font and so on. The Hide Element option is only for the current selected
	 * device and to make that work it's required to move the related 'display: none' into a min and max width range.
	 *
	 * All other styles get only the max-width breakpoint to have them inherited.
	 *
	 * @param string $device The device the media query is for.
	 * @param array $device_sizes The device min- and max-width.
	 * @param string $style Style of all elements.
	 * @param array $media_break_points Available breakpoints.
	 * @param string $media_styles All media styles.
	 *
	 * @return string The filtered media styles.
	 */
	private function min_max_media_query_for_display( $device, $device_sizes, $style, $media_break_points, $media_styles ) {
		$media_max_width = isset( $device_sizes['maxWidth'] ) && $device_sizes['maxWidth']
			? $device_sizes['maxWidth']
			: PHP_INT_MAX;

		$style_elements = explode( '}', $style );

		foreach ( $style_elements as &$style_element ) {
			if ( ! strpos( $style_element, 'display: none;' ) ) {
				// "Hide Block" is not used here.
				continue;
			}

			$pos_start_attributes = strpos( $style_element, '{' );
			if ( ! $pos_start_attributes ) {
				// Invalid css.
				continue;
			}

			// Get element.
			$selector = substr( $style_element, 0, $pos_start_attributes );

			if ( empty( $selector ) ) {
				// Invalid css.
				continue;
			}

			// Reset media breakpoints pointer.
			reset( $media_break_points );

			// Move pointer until reaching current device key.
			while ( key( $media_break_points ) !== $device ) {
				next( $media_break_points );
			}

			// Get the media min width by selecting the previous break point.
			// i.e. for Tablet, the min width is the break point (max-width) of Phone.
			$media_min_width = prev( $media_break_points );

			if ( ! $media_min_width ) {
				// No need to have an extra media query when no min-width is available.
				continue;
			}

			// Remove display none frome the style attributes.
			$style_element = str_replace( 'display: none;', '', $style_element );

			// Add the display none to a media range, to make it only active for the curr
			$media_styles .= '@media only screen and (min-width: ' . ( $media_min_width + 1 ) . 'px) ';
			$media_styles .= $media_max_width < PHP_INT_MAX
				? 'and (max-width: ' . $media_max_width . 'px) '
				: '';
			$media_styles .= '{ ' . trim( $selector ) . ' { display: none; } } ';
		}

		$style = implode( '}', $style_elements );

		$media_styles .= $media_max_width < PHP_INT_MAX
			? '@media only screen and (max-width: ' . $device_sizes['maxWidth'] . 'px) { ' . $style . ' } '
			: $style;

		return $media_styles;
	}
}
