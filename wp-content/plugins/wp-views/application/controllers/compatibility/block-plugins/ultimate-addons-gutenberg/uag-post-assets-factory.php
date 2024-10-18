<?php
namespace OTGS\Toolset\Views\Controller\Compatibility\BlockPlugin\UltimateAddonsGutenberg;

/**
 * @codeCoverageIgnore
 */
class UagPostAssetsFactory {
	public function get_uag_post_assets( $post_id ) {
		return new \UAGB_Post_Assets( $post_id );
	}
}
