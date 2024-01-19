<?php

namespace OTGS\Toolset\CRED\Controller\Upgrade;

/**
 * Plugin upgrade factory for upgrade routines.
 *
 * @since 2.1.2
 */
class Factory {

	/**
	 * Get the righ routine given its signature key.
	 *
	 * @param string $routine
	 * @return \OTGS\Toolset\CRED\Controller\Upgrade\IRoutine
	 * @since 2.1.2
	 */
	public function get_routine( $routine ) {
		$dic = apply_filters( 'toolset_dic', false );
		switch ( $routine ) {
			case 'upgrade_db_to_2010200':
				$upgrade_db_to_2010200 = $dic->make( '\OTGS\Toolset\CRED\Controller\Upgrade\Routine2010200DbUpgrade' );
				return $upgrade_db_to_2010200;
				break;
			case 'upgrade_db_to_2030500':
				$upgrade_db_to_2030500 = $dic->make( '\OTGS\Toolset\CRED\Controller\Upgrade\Routine2030500DbUpgrade' );
				return $upgrade_db_to_2030500;
				break;
			case 'upgrade_db_to_2040000':
				$upgrade_db_to_2040000 = $dic->make( '\OTGS\Toolset\CRED\Controller\Upgrade\Routine2040000DbUpgrade' );
				return $upgrade_db_to_2040000;
				break;
			default:
				throw new \Exception( 'Unknown routine' );
		}
	}

}
