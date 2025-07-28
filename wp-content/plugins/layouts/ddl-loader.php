<?php
if (defined('WPDDL_VERSION')) return;



define( 'WPDDL_VERSION', '2.6.17' );
define( 'WPDDL_VERSION_OPTION', 'ddl_layouts_plugin_version' );
define( 'WPDDL_VERSIONS_COMPARE_OPTION', 'ddl_layouts_plugin_versions_compare' );
define( 'LAYOUTS_PLUGIN_NAME', 'Toolset Layouts' );
define( 'WPDDL_NOTES_URL', 'https://toolset.com/version/layouts-2-6-14/' );
define( 'WPDDL_ABSPATH', dirname(__FILE__) );
define( 'WPDDL_RELPATH', plugins_url() . '/' . basename(dirname(__FILE__) ) );

require_once WPDDL_ABSPATH . '/inc/constants.php';

// Initialize the class autoloader.
//
//

// It is possible to regenerate the classmap with Zend framework.
// See the "recreate_classmap.sh" script in the plugin root directory.
$classmap = include WPDDL_INC_ABSPATH . '/autoload_classmap.php';
require_once WPDDL_INC_ABSPATH . '/early_autoloader.php';
WPDDL_Early_Autoloader::initialize();
$autoloader = WPDDL_Early_Autoloader::get_instance();
$autoloader->register_classmap( $classmap );

require_once WPDDL_VENDOR_ABSPATH . '/otgs/ui/loader.php';
otgs_ui_initialize( WPDDL_VENDOR_ABSPATH . '/otgs/ui', WPDDL_VENDOR_RELPATH . '/otgs/ui' );

require WPDDL_ONTHEGO_RESOURCES . 'loader.php';
onthego_initialize( WPDDL_ONTHEGO_RESOURCES, WPDDL_RELPATH . '/vendor/toolset/onthego-resources/' );

require_once WPDDL_TOOLSET_COMMON_ABSPATH . '/loader.php';
toolset_common_initialize(WPDDL_TOOLSET_COMMON_ABSPATH, WPDDL_TOOLSET_COMMON_RELPATH);

require_once WPDDL_TOOLSET_THEME_SETTINGS_ABSPATH . '/loader.php';
toolset_theme_settings_initialize(WPDDL_TOOLSET_THEME_SETTINGS_ABSPATH, WPDDL_TOOLSET_THEME_SETTINGS_RELPATH );


add_action( 'plugins_loaded', 'ddl_register_layouts_plugin_version' );

if( !function_exists('ddl_register_layouts_plugin_version') ){

	function ddl_register_layouts_plugin_version()
	{
		$previous_version = get_option( WPDDL_VERSION_OPTION, '1.8.9' );
		$version_compare = version_compare( $previous_version, WPDDL_VERSION );

		if( $version_compare === 0 ){
			return;
		} else {
			// TODO: upgrade routine fix for 2.0.3, turn into a proper upgrade mechanism
			wpddl_upgrade_db_to_2030000();
			// register current version
			update_option( WPDDL_VERSION_OPTION, WPDDL_VERSION );
			// track if last update operation was an upgrade (-1) or downgrade (1)
			update_option( WPDDL_VERSIONS_COMPARE_OPTION, $version_compare );
		}
	}
}

/**
 * Make sure that an user with username 'admin' is an actual admin or superadmin.
 * Remove the custom Layouts capabilities from him otherwise.
 *
 * @since 2.0.3
 */
function wpddl_upgrade_db_to_2030000() {
	global $wp_roles, $current_user;

	$user_profiles = new WPDD_Layouts_Users_Profiles( $wp_roles, $current_user );
	$user_profiles->clean_the_mess_in_nonadmin_user_caps( 'admin' );

	$user_profiles_private = new WPDD_Layouts_Users_Profiles_Private( $wp_roles, $current_user );
	$user_profiles_private->clean_the_mess_in_nonadmin_user_caps( 'admin' );
}


add_action( 'after_setup_theme', 'wpddl_plugin_setup', 11 );

if ( !function_exists('wpddl_plugin_setup') ) {
	function wpddl_plugin_setup() {

		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin.class.php';

		if ( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-embedded.class.php' )
			&& defined( 'WPDDL_EMBEDDED' )
			&& (
				defined( 'WPDDL_DEVELOPMENT' ) === false
				&& defined( 'WPDDL_PRODUCTION' ) === false
			)
		) {
			require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-embedded.class.php';
		} elseif (
			file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-plugin.class.php' )
			&& (
				defined( 'WPDDL_DEVELOPMENT' )
				|| defined( 'WPDDL_PRODUCTION' ) )
		) {
			require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-plugin.class.php';
		}

		require_once WPDDL_INC_ABSPATH . '/help_links.php';
		require_once WPDDL_INC_ABSPATH . '/api/ddl-features-api.php';

		require_once WPDDL_TOOLSET_COMMON_ABSPATH . '/utility/dialogs/toolset.dialog-boxes.class.php';

		require_once WPDDL_CLASSES_ABSPATH . '/wpddl.class.php';

		require_once WPDDL_CLASSES_ABSPATH . '/wpddl.scripts.class.php';

		$private_layouts = new WPDDL_Private_Layout();
		$private_layouts->add_hooks();

		if ( file_exists( WPDDL_CLASSES_ABSPATH . '/wpddl.PluginLayouts-helper.class.php' ) && ( defined( 'WPDDL_DEVELOPMENT' ) || defined( 'WPDDL_PRODUCTION' ) ) ) {
			require_once WPDDL_CLASSES_ABSPATH . '/wpddl.PluginLayouts-helper.class.php';
		}

		require_once WPDDL_LAYOUTS_EXTRA_MODULES . '/wddl.extra-loader.class.php';

		require_once WPDDL_GUI_ABSPATH . '/dialogs/dialogs.php';

		require_once WPDDL_GUI_ABSPATH . '/dialogs/wpddl.create-cell-dialog.class.php';
		require_once WPDDL_GUI_ABSPATH . '/editor/editor.php';
		require_once WPDDL_GUI_ABSPATH . '/frontend-editor/editor.php';
		require_once WPDDL_INC_ABSPATH . '/api/ddl-cells-api.php';
		require_once WPDDL_INC_ABSPATH . '/api/ddl-fields-api.php';

		require_once WPDDL_INC_ABSPATH . '/api/ddl-theme-api.php';
		require_once WPDDL_INC_ABSPATH . '/api/ddl-shortcodes.php';

		// Add theme export menu.
		require_once WPDDL_INC_ABSPATH . '/theme/wpddl.theme-support.class.php';
	}
}

if (
	file_exists( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.layouts-helper.class.php' )
	&& defined( 'WPDDL_EMBEDDED') === false
	&& (
		defined( 'WPDDL_DEVELOPMENT' ) === true
		|| defined( 'WPDDL_PRODUCTION' ) === true )
) {
	require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'classes/wpddl.layouts-helper.class.php';
}

// init plugin
WPDD_LayoutsPlugin::getInstance( new WPDDL_WPML_Support() );
