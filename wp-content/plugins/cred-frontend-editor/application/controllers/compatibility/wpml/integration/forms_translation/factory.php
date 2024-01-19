<?php
/**
 * Factory for the WPML integration for translating forms.
 *
 * @package Toolset Forms
 * @since 2.6
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\FormsTranslation;

/**
 * Forms translation controller factory.
 *
 * @since 2.6
 */
class Factory {

	/**
	 * Gets a controller based on WPML translation mode
	 *
	 * @param string $mode WPML mode: `packages` or `legacy`.
	 * @throws \RuntimeException When `$mode` is not correct.
	 * @return Base
	 */
	public function get_controller( $mode ) {
		$dic = apply_filters( 'toolset_dic', false );
		switch ( $mode ) {
			case 'packages':
				return $dic->make( '\OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\FormsTranslation\Packages' );
			case 'legacy':
				return $dic->make( '\OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\FormsTranslation\Legacy' );
			default:
				throw new \RuntimeException( 'Unknown WPML forms translator' );
		}
	}
}
