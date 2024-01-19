<?php

/**
 * Toolset_Theme_Settings_Condition_Plugin_Views_Active
 *
 * @since 1.0
 */
class Toolset_Theme_Settings_Condition_Plugin_Blocks_Active implements Toolset_Theme_Settings_Condition_Interface {

	public function is_met() {
		if ( ! defined( 'WPV_VERSION' ) ) {
			return false;
		}

		return ( 'blocks' === apply_filters( 'toolset_views_flavour_installed', 'classic' ) );
	}

}
