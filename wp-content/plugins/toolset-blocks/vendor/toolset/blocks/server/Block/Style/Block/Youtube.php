<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

/**
 * Class Youtube
 *
 * @package ToolsetBlocks\Block\Style\Block
 *
 * @since 1.3
 */
class Youtube extends ABlock {

	private $defaults = [
		'settings' => [
			'start' => null,
			'end' => null,
			'autoplay' => false,
			'playsinline' => false,
			'show_controls' => true,
			'allow_fullscreen' => true,
			'allow_keyboard_controls' => true,
			'only_suggest_same_channel_videos' => true,
			'use_white_progress_bar' => false,
		],
		'style' => [
			'aspectRatio' => [
				'setup' => '16:9',
			],
			'width' => 100,
			'widthUnit' => '%',
		],
	];

	public function __construct( $block_config, $block_name_for_id_generation = 'unknown', \ToolsetCommonEs\Assets\Loader $assets_loader = null ) {
		$block_config = toolset_array_merge_recursive_distinct( $this->defaults, $block_config );
		parent::__construct( $block_config, $block_name_for_id_generation, $assets_loader );
	}

	/**
	 * Returns hardcoded css classes of the block to have a more specific selector.
	 *
	 * @return string
	 */
	public function get_css_block_class() {
		return $this->get_existing_block_classes_as_selector( [ 'tb-youtube' ] );
	}

	/**
	 * @param array $config
	 *
	 * @param bool $force_apply
	 *
	 * @return string
	 */
	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/youtube.css' );
		$parent_css = parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}

	// This is no interface function and should always be called by checking method exists before.
	// It's required for Views / WPA rendering as these does not follow the standard routine rendering blocks.
	// Can be removed once views-3260 is resolved.
	public function on_register() {
		add_filter( 'wpv-post-do-shortcode', function( $content ) {
			$content = preg_replace_callback(
				'/<div(.*?)class="tb-youtube"(.*?)<\/iframe><\/div><\/div>/mi',
				function( $matches ) {
					return $this->rewrite_youtube_urls_to_embed( $matches[0] );
				},
				$content
			);

			return $content;
		}, 10, 1 );
	}

	/**
	 * @param string $content
	 * @param MobileDetect $device_detect
	 * @return string
	 */
	public function filter_block_content( $content, MobileDetect $device_detect ) {
		$regex_search = '#(\[tb-youtube-iframe )(src=".*?")(\])#ism';
		$regex_replace = '<iframe $2 frameBorder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';

		$content = preg_replace( $regex_search, $regex_replace, $content );

		// Skip when the shortcode of the dynamic field is not rendered at this point.
		// Part of the views-3260 workaround, see $this->on_register().
		if ( strpos( $content, '[tb-dynamic ' ) !== false ) {
			return $content;
		}

		if ( ! defined( 'TB_SCRIPT_STYLE_LAZY_LOAD' ) || TB_SCRIPT_STYLE_LAZY_LOAD ) {
			// Replace the src by data-src. On user interaction or when the block is in the viewport 'data-src' is
			// replaced via js to 'src' again. This way it does not delay the page load.
			$content = str_replace( ' src=', ' data-src=', $content );
		}

		// Also apply parent filter.
		$content = parent::filter_block_content( $content, $device_detect );

		return $this->rewrite_youtube_urls_to_embed( $content );
	}

	private function rewrite_youtube_urls_to_embed( $content ) {
		$config = $this->get_block_config();
		$youtube_embed_base_url = 'https://www.youtube.com/embed/';
		$yt_settings = isset( $config['settings'] ) ? $config['settings'] : [];
		$url_params = [];
		if ( $yt_settings['start'] !== null ) {
			array_push( $url_params, 'start=' . (int) $yt_settings['start'] ); }
		if ( $yt_settings['end'] !== null ) {
			array_push( $url_params, 'end=' . (int) $yt_settings['end'] ); }
		if ( $yt_settings['autoplay'] === true ) {
			array_push( $url_params, 'autoplay=1' ); }
		if ( $yt_settings['playsinline'] === true ) {
			array_push( $url_params, 'playsinline=1' ); }
		if ( $yt_settings['show_controls'] === false ) {
			array_push( $url_params, 'controls=0' ); }
		if ( $yt_settings['allow_fullscreen'] === false ) {
			array_push( $url_params, 'fs=0' ); }
		if ( $yt_settings['allow_keyboard_controls'] === false ) {
			array_push( $url_params, 'disablekb=1' ); }
		if ( $yt_settings['only_suggest_same_channel_videos'] === true ) {
			array_push( $url_params, 'rel=0' ); }
		if ( $yt_settings['use_white_progress_bar'] === true ) {
			array_push( $url_params, 'color=white' ); }

		$url_params_string = count( $url_params ) > 0 ?
			implode( '&', $url_params ) :
			'';

		$video_ids = [];

		$content = preg_replace_callback(
			'/<iframe.*?src="\K(.*?)"/ism',
			function ( $matches ) use ( $url_params_string, $video_ids, $youtube_embed_base_url ) {
				if ( strpos( $matches[1], 'toolset=1' ) ) {
					// This was already converted.
					return $matches[1] . '"';
				}

				$videos = explode( ',', $matches[1] );

				foreach ( $videos as $video ) {
					if ( preg_match_all(
						'/\b(?:https?:\/\/)?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)\/(?:(?:\??v=?i?=?\/?)|watch\?vi?=|watch\?.*?&v=|(embed|shorts)\/|)([A-Z0-9_-]{11})\S*(?=\s|$)/mi',
						$video,
						$youtube_id
					) ) {
						$video_ids[] = $youtube_id[2][0];
					}
				}

				if ( count( $video_ids ) === 0 ) {
					// Shouldn't happen... but return original input if it happens.
					return $matches[1];
				}

				$src = '';

				// If there are more than one video the first parameter will be ?playlist=.
				$appender_for_video_settings = '?';

				for ( $i = 0; $i < count( $video_ids ); $i++ ) {
					if ( $i === 0 ) {
						// First video id is placed directly behind embed.
						$src = $youtube_embed_base_url . $video_ids[ $i ];
					} elseif ( $i === 1 ) {
						// Second video will introduce the ?playlist parameter.
						$src .= '?playlist=' . $video_ids[ $i ];
						$appender_for_video_settings = '&';
					} else {
						// All other videos will be appendend to the playlist.
						$src .= ',' . $video_ids[ $i ];
					}
				}

				if ( ! empty( $url_params_string ) ) {
					$src .= $appender_for_video_settings . $url_params_string;
					$appender_for_video_settings = '&';
				}

				return $src . $appender_for_video_settings . 'toolset=1"';
			},
			$content
		);

		return strpos( $content, $youtube_embed_base_url ) !== false ?
			$content :
			'';
	}

	/**
	 * @param FactoryStyleAttribute $factory
	 */
	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {

	}

	private function get_css_config() {
		return array(
			self::CSS_SELECTOR_ROOT => [
				self::KEY_STYLES_FOR_COMMON_STYLES => [
					'width',
					'display',
				],
			],
			'> div' => [
				self::KEY_STYLES_FOR_COMMON_STYLES => [
					'aspectRatio',
				],
			],

			'> div > iframe' => [
				self::KEY_STYLES_FOR_COMMON_STYLES => [
					'margin',
					'padding',
					'border',
					'border-radius',
					'box-shadow',
				],
			],
		);
	}
}
