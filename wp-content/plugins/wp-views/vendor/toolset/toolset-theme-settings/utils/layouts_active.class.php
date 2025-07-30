<?php

/**
 * Toolset_Theme_Settings_Condition_Plugin_Layouts_Active
 *
 * @since 1.0
 */
class Toolset_Theme_Settings_Condition_Plugin_Layouts_Active implements Toolset_Theme_Settings_Condition_Interface {

	public function is_met() {
		if( defined( 'WPDDL_DEVELOPMENT' ) || defined( 'WPDDL_PRODUCTION' ) ) {
			return true;
		}

		return false;
	}

}