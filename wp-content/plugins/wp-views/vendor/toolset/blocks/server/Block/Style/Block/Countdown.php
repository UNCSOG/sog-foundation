<?php

namespace ToolsetBlocks\Block\Style\Block;

use ToolsetCommonEs\Block\Style\Block\Common;

class Countdown extends Common {
	/**
	 * Css of the block.
	 *
	 * @param array $config
	 * @param false $force_apply
	 * @param null $responsive_device
	 *
	 * @return string
	 */
	public function get_css( $config = [], $force_apply = false, $responsive_device = null ) {
		$css = $this->get_css_file_content( TB_PATH_CSS . '/countdown.css' );
		$parent_css = parent::get_css( $config, $force_apply, $responsive_device );

		return ! empty( $parent_css )
			? $css . ' ' . $parent_css
			: $css;
	}

	public function get_html_root_element_count() {
		return 4;
	}
}
