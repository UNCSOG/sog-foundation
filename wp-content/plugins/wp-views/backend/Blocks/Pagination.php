<?php

namespace OTGS\Toolset\Views\Blocks;

class Pagination {
	public function initialize() {
		/*
		 * Register render callback which will be used for view rendering
		 * using template from the Gutenberg "modern" mode
		 */
		register_block_type(
			'toolset-views/view-pagination-block',
			array(
				'render_callback' => array( $this, 'render_callback' ),
			)
		);
	}

	/*
	 * Callback to "render" the pagination block.
	 *
	 * Practically what it does is that in order to make the links type pagination block translatable by WPML,
	 * it replaces the placeholders set by the "save" component of the block only for the case where the correct
	 * block attributes are set. The rest of the pagination types are left untouched and continue to render as they
	 * did before.
	 *
	 * @param  array  $attributes
	 * @param  string $content
	 *
	 * @return string
	 */
	public function render_callback( $attributes, $content ) {
		// The server-side block render callback for the pagination block only runs for the Links pagination block,
		// either for the View or the WPA version. Otherwise it proceeds with the client side rendering.
		if (
			false === strpos( $content, 'wpv-pager-nav-links' ) &&
			false === strpos( $content, 'wpv-pager-archive-nav-links' )
		) {
			return $content;
		}

		$attributes = shortcode_atts(
			array(
				'text_for_previous_link' => __( 'Previous', 'wpv-views' ),
				'text_for_next_link' => __( 'Next', 'wpv-views' ),
				'text_for_last_link' => __( 'Last', 'wpv-views' ),
				'text_for_first_link' => __( 'First', 'wpv-views' ),
			),
			$attributes
		);

		$attributes_for_replacement = array(
			'text_for_previous_link',
			'text_for_next_link',
			'text_for_last_link',
			'text_for_first_link',
		);

		foreach ( $attributes_for_replacement as $attribute ) {
			if ( false !== strpos( $content, $attribute ) ) {
				// Needs to translate the special characters but decode UTF-8 entities like emojis or acutes.
				$content = str_replace( $attribute . '_to_be_replaced', htmlspecialchars( html_entity_decode( $attributes[ $attribute ] ) ), $content );
			}
		}

		return $content;
	}
}
