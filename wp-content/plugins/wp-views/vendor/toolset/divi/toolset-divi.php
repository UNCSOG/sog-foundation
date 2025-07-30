<?php
/*
Plugin Name: Toolset Divi
Plugin URI:  
Description: Allows to add Toolset Views to Divi pages.
Version:     1.1.0
Author:      
Author URI:  
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: toolset-divi

Toolset Divi is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Toolset Divi is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Toolset Divi. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

namespace Toolset\Compatibility\Divi;

if ( ! function_exists( '\Toolset\Compatibility\Divi\initializeExtension' ) ) {

	function initializeExtension() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/extension.php';

		$toolsetDivi = new \Toolset\Compatibility\Divi\Extension();
		$toolsetDivi->addHooks();
	}

	add_action( 'divi_extensions_init', '\Toolset\Compatibility\Divi\initializeExtension' );

}
