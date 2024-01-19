<?php
/**
 * Plugin initialization.
 *
 * @package Toolset Views
 *
 * @codeCoverageIgnore
 */

// Getting the list of files that have been required...
$required_files_list = get_required_files();
// ...and retrieving the file that require the current one.
$main_plugin_file_path = $required_files_list[ count( $required_files_list ) - 2 ];

/**
 * Set basic constant.
 */
define( 'WPV_PATH', dirname( __FILE__ ) );
define( 'WPV_PLUGIN_BASENAME', plugin_basename( $main_plugin_file_path ) );
define( 'WPV_PLUGIN_FILE', basename( WPV_PLUGIN_BASENAME ) );

/**
 * Set constants.
 */
require_once WPV_PATH . '/loader/constants.php';
// @todo move this file to the constants file and adjust the autoload classmap, please.
require_once WPV_PATH . '/backend/UserCapabilities.php';

/**
 * Require OTGS UI, OTGS Resources, Toolset Common, Theme Settings
 * and other manual dependencies
 */
require_once WPV_PATH . '/loader/manual-dependencies.php';

/**
 * Initial API.
 */
require_once WPV_PATH . '/loader/api.php';

/**
 * Initialize the Views Settings.
 *
 * @global $WPV_settings WPV_Settings Views settings manager.
 * @deprecated Use $s = WPV_Settings::get_instance() instead.
 * @todo Move to the right application/ location, and defer its loading to when it is required,
 * after checking that we do nto need to initialize anything on load (which me might!).
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-settings.class.php';
require WPV_PATH . '/inc/wpv-settings-screen.class.php';
global $WPV_settings;
$WPV_settings = WPV_Settings::get_instance();

/**
 * Public Views API functions.
 *
 * @todo move to the root directory
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-api.php';

/**
 * Helper classes.
 *
 * @todo move to the root directory
 */
require_once WPV_PATH . '/inc/classes/wpv-exception-with-message.class.php';

/**
 * WPV_View and other Toolset object wrappers.
 */
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-post-object-wrapper.class.php';
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-view-base.class.php';
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-view-embedded.class.php';
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-wordpress-archive-embedded.class.php';
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-content-template-embedded.class.php';

require_once WPV_PATH . '/inc/classes/wpv-view.class.php';
require_once WPV_PATH . '/inc/classes/wpv-wordpress-archive.class.php';
require_once WPV_PATH . '/inc/classes/wpv-content-template.class.php';

/**
 * Cache.
 */
require_once WPV_PATH_EMBEDDED . '/inc/classes/wpv-cache.class.php';

/**
 * Module Manager integration.
 *
 * @todo turn into a proper controller with proper plugin conditions.
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-module-manager.php';

/**
 * Working files.
 *
 * @todo Move to proper controllers and load on main
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-admin-messages.php';// Proper controller
require WPV_PATH_EMBEDDED . '/inc/functions-core-embedded.php';// Empty and clasify
require WPV_PATH . '/inc/functions-core.php';// Empty and clasify

/**
 * AJAX management.
 *
 * @todo most of this should be decoupled to the right files
 */
require WPV_PATH . '/loader/deprecated.php';
require WPV_PATH . '/inc/wpv-admin-ajax.php';
require WPV_PATH . '/inc/wpv-admin-ajax-layout-wizard.php';

/**
 * Debug tool.
 *
 * @todo Move to a proper controller and initialize on main.
 * @note that this dummy function wpv_debuger does nothing but acting as a flag. Boh.
 */
if ( ! function_exists( 'wpv_debuger' ) ) {
	require_once WPV_PATH_EMBEDDED . '/inc/wpv-query-debug.class.php';
}

/**
 * Shortcodes.
 *
 * @todo Bring santy to this, please. Proper controllers, move init to main.
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-shortcodes.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-shortcodes-gui.php';
if ( ! function_exists( 'wpv_shortcode_generator_initialize' ) ) {
	add_action( 'after_setup_theme', 'wpv_shortcode_generator_initialize', 999 );

	/**
	 * Initialize Toolset Views shortcodes
	 *
	 * @since unknown
	 * @todo Move to the proper main file.
	 */
	function wpv_shortcode_generator_initialize() {
		$toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
		$toolset_common_sections = array( 'toolset_shortcode_generator' );
		$toolset_common_bootstrap->load_sections( $toolset_common_sections );
		require WPV_PATH_EMBEDDED . '/inc/classes/wpv-shortcode-generator.php';
		$wpv_shortcode_generator = new WPV_Shortcode_Generator();
		$wpv_shortcode_generator->initialize();
	}
}

/**
 * Conditional.
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-condition.php';

/**
 * Working files.
 *
 * @todo review
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-formatting-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-meta-html-embedded.php';
require WPV_PATH . '/inc/wpv-admin-changes.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-layout-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-pagination-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-archive-loop.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-user-functions.php';

/**
 * Query modifiers.
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-order-by-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-types-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-post-types-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-limit-embedded.php';

/**
 * Backend edit sections and query filters.
 *
 * Only load the sections and filter files when editing a View or WordPress Archive, or when doing AJAX.
 *
 * @since unknown
 * @since 2.4.0 WIP Added the post type filter
 * @todo Move to a proper controller registering this on proper admin pages.
 * @todo Only load query filters on legacy edit pages or when using block editor
 */
if (
	(
		isset( $_GET['page'] )
		&& in_array( $_GET['page'], array( 'views-editor', 'view-archives-editor' ), true )
	) || (
		defined( 'DOING_AJAX' )
		&& DOING_AJAX
	)
) {
	// Edit sections.
	require_once WPV_PATH . '/inc/sections/wpv-screen-options.php';

	require_once WPV_PATH . '/inc/sections/wpv-section-limit-offset.php';
	require_once WPV_PATH . '/inc/sections/wpv-section-layout-extra.php';
	require_once WPV_PATH . '/inc/sections/wpv-section-layout-extra-js.php';
	if ( ! wpv_is_views_lite() ) {
		require_once WPV_PATH . '/inc/sections/wpv-section-content.php';
	}
	// Query filters.
}
require_once WPV_PATH . '/inc/filters/wpv-filter-author.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-category.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-date.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-id.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-meta-field.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-parent.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-post-type.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-search.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-status.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-sticky.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-taxonomy-term.php';
require_once WPV_PATH . '/inc/filters/wpv-filter-users.php';

/**
 * Frontend query filters.
 *
 * @since unknown
 * @since 2.4.0 WIP Added the post type filter embedded side
 */
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-author-embedded.php';
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-category-embedded.php';
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-date-embedded.php';
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-id-embedded.php';
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-meta-field-embedded.php';
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-parent-embedded.php';
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-post-type-embedded.php';
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-search-embedded.php';
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-status-embedded.php';
require_once WPV_PATH_EMBEDDED . '/inc/filters/wpv-filter-sticky-embedded.php';

/**
 * WPML integration.
 *
 * @todo Move to the proper existing controller
 */
require WPV_PATH_EMBEDDED . '/inc/WPML/wpv_wpml_core.php';

/**
 * WooCommerce integration.
 *
 * @todo Move to a proper controller
 */
require WPV_PATH_EMBEDDED . '/inc/third-party/wpv-compatibility-woocommerce.class.php';


// Other third-party compatibility fixes.
// @todo Move to a proper controller.
require_once WPV_PATH_EMBEDDED . '/inc/third-party/wpv-compatibility-generic.class.php';
WPV_Compatibility_Generic::initialize();

/**
 * Main plugin classes.
 */
require WPV_PATH_EMBEDDED . '/inc/wpv.class.php';
require WPV_PATH . '/inc/wpv-plugin.class.php';
global $WP_Views;
$WP_Views = new WP_Views_plugin;

require WPV_PATH_EMBEDDED . '/inc/views-templates/functions-templates.php';
require WPV_PATH . '/inc/views-templates/wpv-template-plugin.class.php';
global $WPV_templates;
$WPV_templates = new WPV_template_plugin();

/**
 * Query controllers.
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-query.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-taxonomy-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-users-embedded.php';

/**
 * Frameworks integration.
 *
 * @todo Move to a proper controller
 */
require_once WPV_PATH_EMBEDDED . '/inc/third-party/wpv-framework-api.php';

/**
 * Widgets.
 *
 * @todo Move to a proper controller
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-widgets.php';

/**
 * Listing pages.
 *
 * @todo review whether we can load this on demand on main.php based on the current request mode
 */
// Including files for listing pages.
require_once WPV_PATH . '/inc/wpv-listing-common.php';
// Including files for Views listings and editing.
require_once WPV_PATH . '/inc/redesign/wpv-views-listing-page.php';
require_once WPV_PATH . '/inc/wpv-add-edit.php';
// Including file for Content Templates listing and editing.
require_once WPV_PATH . '/inc/redesign/wpv-content-templates-listing-page.php';
require_once WPV_PATH . '/inc/ct-editor/ct-editor.php';
// Including file for WordPress Archives listing and editing.
require_once WPV_PATH . '/inc/redesign/wpv-archive-listing-page.php';
require_once WPV_PATH . '/inc/wpv-archive-add-edit.php';

/**
 * Export / import.
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-import-export-embedded.php';
require WPV_PATH . '/inc/wpv-import-export.php';

/**
 * Working files.
 *
 * @todo review
 */
require WPV_PATH_EMBEDDED . '/inc/wpv-summary-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-readonly-embedded.php';
require WPV_PATH . '/inc/wpv-admin-update-help.php';
require WPV_PATH . '/inc/wpv-admin-notices.php';

/**
 * THIS IS A TEMPORARY FIX. REMOVE ONCE WooCommerce Blocks IS MERGED TO Views.
 *
 * Manual load \ToolsetCommonEs\Rest\API as WooCommerce Blocks uses composer autoloader,
 * which runs before our autoloader and that result in WooCommerce Blocks loading an old version
 * of CommonES.
 */
if( ! class_exists( '\ToolsetCommonEs\Library\WordPress\Rest', false ) )
	require WPV_PATH . '/vendor/toolset/common-es/server/Library/WordPress/Rest.php';

if( ! class_exists( '\ToolsetCommonEs\Utils\ScriptData', false ) )
	require WPV_PATH . '/vendor/toolset/common-es/server/Utils/ScriptData.php';

if( ! class_exists( '\ToolsetCommonEs\Rest\API', false ) )
	require WPV_PATH . '/vendor/toolset/common-es/server/Rest/API.php';



/**
 * Load all dependencies that needs toolset common loader
 * to be completely loaded before being required.
 */
if ( ! function_exists( 'wpv_toolset_common_dependent_setup' ) ) {
	add_action('after_setup_theme', 'wpv_toolset_common_dependent_setup', 11 );
	function wpv_toolset_common_dependent_setup(){
		// The initializatio of this class can be safely moved to application/controlers/admin.php
		require_once WPV_PATH_EMBEDDED . '/inc/wpv-views-help-videos.class.php';
		// This class seems not used anywhere.
		// I prefer that each component takes care of its assets insead of a centralized one,
		// and we have the TC assets manager to register and enqueue if needed.
		// This should be deprecated and removed.
		require_once WPV_PATH_EMBEDDED . '/inc/wpv-views-scripts.class.php';
	}
}

/**
 * Inline documentation plugin support - internal usage.
 */
require_once WPV_PATH . '/loader/inline-doc-support.php';

/**
 * Bootstrap Views.
 *
 * @todo Most of the things above should happen there, actually,
 * or the intermediary step shoud be removed, or something.
 */
require_once WPV_PATH . '/application/bootstrap.php';
