<?php
/**
 * WPML integration factory.
 *
 * @package Toolset Forms
 * @since 2.6
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration;

/**
 * Factory for the WPML integration coponents.
 *
 * @since 2.6
 */
class Factory {

	public function get_integration( $integration ) {
		$dic = apply_filters( 'toolset_dic', false );

		switch ( $integration ) {
			case 'frontend':
				return $dic->make( '\OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\Frontend' );
			case 'forms_translation':
				return $dic->make( '\OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\FormsTranslation' );
			case 'fallback':
				return $dic->make( '\OTGS\Toolset\CRED\Controller\Compatibility\Wpml\Integration\Fallback' );
			default:
				throw new \RuntimeException( 'Unknown WPML integration' );
				break;
		}

		return false;
	}

}
