<?php

namespace OTGS\Toolset\Views\Models\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\Common;
use ToolsetCommonEs\Library\MobileDetect\MobileDetect;

/**
 * Custom Search Styles
 *
 * @package OTGS\Toolset\Views\Models\Block\Style\Block
 */
class CustomSearch extends Common {
	const KEY_STYLES_LABEL = 'label';
	const KEY_STYLES_INPUT = 'input';

	/**
	 * @return string
	 */
	public function get_css_block_class() {
		return '.wpv-custom-search-filter';
	}

	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		return parent::get_css( $this->get_css_config(), $force_apply, $responsive_device );
	}

	public function filter_block_content( $content, MobileDetect $device_detect ) {
		// Backwards compatibilty for translations being done via String Translation.
		if( function_exists( 'icl_t' ) ) {
			// Using directly icl_t is needed, because using [wpml-string] will register the string, which needs
			// to be avoided as we already offer to translate it in the Translation Editor package.
			$content = preg_replace_callback(
				'#(<label .*?class=".*?wpv-custom-search-filter.*?>)(.*?)(</label>)#ism',
				function( $matches ) {
					return
						$matches[1] .
						icl_t( 'wpv-views', 'wpml-shortcode-' . md5( $matches[2] ), $matches[2] ) .
						$matches[3];
				},
				$content
			);
		}

		return $this->common_filter_block_content_by_block_css_class(
			'wpv-custom-search-filter',
			$content,
			$device_detect
		);
	}

	public function load_block_specific_style_attributes( FactoryStyleAttribute $factory ) {
		$config = $this->get_block_config();

		// Label styles.
		$factory->apply_common_styles_to_block( $this, $config, 'styleLabel', null, self::KEY_STYLES_LABEL );

		// Input styles.
		$factory->apply_common_styles_to_block( $this, $config, 'styleInput', null, self::KEY_STYLES_INPUT );
	}

	private function get_css_config() {
		return [
			'label' .
			'!.editor-rich-text__editable' => [
				self::KEY_STYLES_LABEL => 'all'
			],
			'input' .
			'!button' .
			'!select' .
			'!textarea' => [
				self::KEY_STYLES_INPUT => 'all'
			]
		];
	}
}
