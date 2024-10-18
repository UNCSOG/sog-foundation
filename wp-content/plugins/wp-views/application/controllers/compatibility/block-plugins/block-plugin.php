<?php
namespace OTGS\Toolset\Views\Controller\Compatibility\BlockPlugin;

use OTGS\Toolset\Views\Controller\Compatibility\Base;

/**
 * Class BlockPluginCompatibility
 */
abstract class BlockPluginCompatibility extends Base {
	/** @var array */
	protected $original_block_id_array = array();

	/** @var string */
	protected $template_content = '';

	/**
	 * Captures the View template in order to uses it later to determine if blocks' style related action should be taken.
	 *
	 * @param string $template_content
	 *
	 * @return string
	 */
	public function capture_view_template( $template_content ) {
		$this->template_content = $template_content;

		return $template_content;
	}


	/**
	 * Determines if the specified block name belong to a block in the compatible plugin blocks list.
	 *
	 * @param string $block_name
	 *
	 * @return bool
	 */
	protected function is_block_from_compatible_plugin( $block_name ) {
		return $this->content_has_block_from_compatible_plugin( $block_name );
	}


	/**
	 * Determines if the specified content contains blocks that are in the compatible plugin blocks list.
	 *
	 * @param string $content
	 *
	 * @return bool
	 */
	protected function has_block_from_compatible_plugin( $content ) {
		return $this->content_has_block_from_compatible_plugin( $content );
	}


	/**
	 * Determines if the specified content contains blocks that are in the compatible plugin blocks list.
	 *
	 * @param string $content
	 *
	 * @return bool
	 */
	private function content_has_block_from_compatible_plugin( $content ) {
		return strpos( $content, static::BLOCK_NAMESPACE . '/' ) !== false;
	}

	/**
	 * Adjusts the loop item output to have proper classes (modified to include the post ID) for the blocks' output as well
	 * as prepends the loop item output with the proper styles for the modified classes.
	 *
	 * @param string   $loop_item_output
	 * @param int      $index
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	abstract public function adjust_classes_in_view_loop_item_and_generate_proper_styles( $loop_item_output, $index, $post );
}
