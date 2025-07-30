<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\Common;
use ToolsetBlocks\Block\Style\Block\Grid;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

/**
 * Loop Item Styles
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block
 */
class WPA extends View {
	/**
	 * @var MobileDetect
	 */
	private $mobile_detect;

	public function get_css_block_class() {
		return '.wp-block-toolset-views-wpa-editor';
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		$css = parent::get_css( $this->css_config(), $force_apply, $responsive_device );
		$css = preg_replace(
			'/\[(data-toolset-views-wpa-editor)=\"([^\"]*)\"\]/',
			'',
			$css
		);
		return $css;
	}

	/**
	 * Abuse filter_block_content, which is called before filter_content to get the instance
	 * of MobileDetect. A bit hacky, but this can be removed once the WPA rendering is using the default approach for#
	 * rendering blocks -> views-3260.
	 *
	 * @param $content
	 * @param MobileDetect $device_detect
	 *
	 * @return string
	 */
	public function filter_block_content( $content, MobileDetect $device_detect ) {
		$this->mobile_detect = $device_detect;

		return $content;
	}

	/**
	 * Required to use the filter_content instead of filter_block_content as the block content
	 * only contains the wpa shortcode, which is rendered on filter_content.
	 *
	 * This shouldn't become a problem as there will always be just one WPA per page.
	 * Can be changed to use filter_block_content as all other blocks once views-3260 is applied.
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function filter_content( $content ) {
		$config = $this->get_block_config();
		$style = isset( $config['style'] ) ? $config['style'] : [];

		$content = $this->filter_content_apply_css_classes( $content, $style );
		$content = $this->filter_content_apply_id( $content, $style );
		$content = $this->filter_content_apply_data_hash( $content );

		return $content;
	}


	/**
	 * Apply block alignment.
	 *
	 * @param string $content This the full post content.
	 * @param array $style The style attributes of the block.
	 *
	 * @return string
	 */
	private function filter_content_apply_css_classes( $content, $style ) {
		if ( $this->mobile_detect ) {
			$block_alignment = $this->get_block_alignment( $style, $this->mobile_detect );
		} else {
			$block_alignment = isset( $style['blockAlign'] ) ?
				$style['blockAlign'] :
				'';
		}

		$css_classes = ! empty ( $block_alignment ) ? [ 'align'.$block_alignment ] : [];

		if (
			isset( $style['cssClasses'] ) && is_array( $style['cssClasses'] ) && ! empty( $style['cssClasses'] ) ) {
			$css_classes = array_merge( $css_classes, $style['cssClasses'] );
		}

		if ( empty( $css_classes) ) {
			return $content;
		}

		// Add css class.
		return preg_replace(
			'/(class=[\"\'](?:.*?)wp-block-toolset-views-wpa-editor)(?:.*?)([\"\'])/',
			'$1 '. implode( ' ', $css_classes ).'$2',
			$content,
			1 // Only the first class needs to be adjusted.
		);
	}


	/**
	 * Make sure the id added in the backend GUI is applied.
	 * This is not fixable by js as any migration code kills the WPA load.
	 *
	 * @param string $content This the full post content.
	 * @param array $style The style attributes of the block.
	 *
	 * @return string
	 */
	private function filter_content_apply_id( $content, $style ) {
		if ( ! isset( $style['id'] ) || empty( $style['id'] ) ) {
			return $content;
		}

		if(
			preg_match( '/\sid=[^>]*?class=[\"\'](?:.*?)wp-block-toolset-views-wpa-editor/', $content ) ||
			preg_match( '/\sclass=[\"\'](?:.*?)wp-block-toolset-views-wpa-editor[^>]*? id=/', $content )
		) {
			// An id is already applied.
			return $content;
		}

		// Add id.
		return preg_replace(
			'/(class=[\"\'](?:.*?)wp-block-toolset-views-wpa-editor)/',
			' id="'. esc_attr( $style['id'] ) .'" $1',
			$content,
			1 // Only the first div with wp-block-toolset-views-wpa-editor needs to be adjusted.
		);
	}

	/**
	 * Make sure the id added in the backend GUI is applied.
	 * This is not fixable by js as any migration code kills the WPA load.
	 *
	 * @param string $content This the full post content.
	 *
	 * @return string
	 */
	private function filter_content_apply_data_hash( $content ) {
		if(
			preg_match( '/\sdata-toolset-views-wpa-editor=[^>]*?class=[\"\'](?:.*?)wp-block-toolset-views-wpa-editor/', $content ) ||
			preg_match( '/\sclass=[\"\'](?:.*?)wp-block-toolset-views-wpa-editor[^>]*? data-toolset-views-wpa-editor=/', $content )
		) {
			// An id is already applied.
			return $content;
		}

		// Add id.
		return preg_replace(
			'/(class=[\"\'](?:.*?)wp-block-toolset-views-wpa-editor)/',
			' data-toolset-views-wpa-editor="'. esc_attr( $this->get_id() ) .'" $1',
			$content,
			1 // Only the first div with wp-block-toolset-views-wpa-editor needs to be adjusted.
		);
	}
}
