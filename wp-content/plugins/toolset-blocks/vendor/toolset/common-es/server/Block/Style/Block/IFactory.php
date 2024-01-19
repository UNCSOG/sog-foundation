<?php

namespace ToolsetCommonEs\Block\Style\Block;

/**
 * Interface IFactory
 * @package ToolsetCommonEs\Block\Style\Block
 *
 * @since 1.0.1
 */
interface IFactory {
	/**
	 * Returns a IBlock object by the given config array.
	 *
	 * @param array $config Provided by the WP core 'render_block' filter.
	 *
	 * @return ABlock
	 */
	public function get_block_by_array( $config );
}
