<?php
namespace ToolsetBlocks\Plugin\WPML;

use ToolsetBlocks\Plugin\IPlugin;
use ToolsetBlocks\Block\Image\Content\Placeholders;

/**
 * Handles WPML integration
 */
class WPML implements IPlugin {
	public function load() {
		add_filter( 'wpml_tm_job_field_is_translatable', [ $this, 'fields_not_translatable' ], 10, 2 );
	}

	/**
	 * Image block uses image placeholders for DS and it doesn't have to be translateble.
	 */
	public function fields_not_translatable( $is_translatable, $job_translate ) {
		$data = $job_translate['field_data'];
		if ( 'base64' === $job_translate['field_format'] ) {
			$data = base64_decode( $data );
		}
		$field_values_to_not_translate = [ Placeholders::PLACEHOLDER_ALT_TEXT, Placeholders::PLACEHOLDER_ID, Placeholders::PLACEHOLDER_URL, Placeholders::PLACEHOLDER_FILENAME, Placeholders::PLACEHOLDER_ATTACHMENT_URL, Placeholders::PLACEHOLDER_WP_IMAGE_CLASS ];
		if ( in_array( $data, $field_values_to_not_translate, true ) ) {
			$is_translatable = 0;
		}
		return $is_translatable;
	}
}
