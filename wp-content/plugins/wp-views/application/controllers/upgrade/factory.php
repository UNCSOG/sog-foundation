<?php

namespace OTGS\Toolset\Views\Controller\Upgrade;

/**
 * Plugin upgrade factory for upgrade routines.
 *
 * @since 2.8.3
 */
class Factory {

	/**
	 * Get the righ routine given its signature key.
	 *
	 * Some routines have singleton dependencies,
	 * hence we can not use DIC to instantiate them :-/
	 *
	 * @param string $routine
	 * @return \OTGS\Toolset\Views\Controller\Upgrade\IRoutine
	 * @since 2.8.3
	 */
	public function get_routine( $routine ) {
		$dic = apply_filters( 'toolset_dic', false );
		switch ( $routine ) {
			case 'setup':
				$setup = $dic->make( '\OTGS\Toolset\Views\Controller\Upgrade\Setup' );
				return $setup;
			case 'upgrade_db_to_2080300':
				$upgrade_db_to_2080300 = $dic->make( '\OTGS\Toolset\Views\Controller\Upgrade\Routine2080300DbUpgrade' );
				return $upgrade_db_to_2080300;
			case 'default_editors':
				// For 3.0.
				$default_editors = $dic->make( 'OTGS\Toolset\Views\Controller\Upgrade\DefaultEditors' );
				return $default_editors;
			case 'capabilities':
				// For 3.0.
				$capabilities = new \OTGS\Toolset\Views\Controller\Upgrade\Capabilities();
				return $capabilities;
			case 'maybe_redirect':
				// For 3.0.
				$maybe_redirect = $dic->make( '\OTGS\Toolset\Views\Controller\Upgrade\MaybeRedirect' );
				return $maybe_redirect;
			case 'update_editing_experience':
				// For 3.0 onwards.
				$update_editing_experience = $dic->make( '\OTGS\Toolset\Views\Controller\Upgrade\UpdateEditingExperience' );
				return $update_editing_experience;
			case 'clean_legacy_cache_indexes':
				// For 3.1.
				$clean_legacy_cache_indexes = new \OTGS\Toolset\Views\Controller\Upgrade\CleanLegacyCacheIndexes();
				return $clean_legacy_cache_indexes;
			default:
				throw new \Exception( 'Unknown routine' );
		}
	}

}
