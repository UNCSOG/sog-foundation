<?php
wp_functionality_constants();


define( 'WPDDL_VENDOR_ABSPATH', WPDDL_ABSPATH . '/vendor' );
define( 'WPDDL_VENDOR_RELPATH', WPDDL_RELPATH . '/vendor' );
define('WPDDL_PUBLIC_ABSPATH', WPDDL_ABSPATH . '/public');
define('WPDDL_PUBLIC_RELPATH', WPDDL_RELPATH . '/public');
define('WPDDL_TOOLSET_ABSPATH', WPDDL_VENDOR_ABSPATH . '/toolset');
define('WPDDL_TOOLSET_RELPATH', WPDDL_VENDOR_RELPATH . '/toolset');
define('WPDDL_ONTHEGO_RESOURCES', WPDDL_TOOLSET_ABSPATH . '/onthego-resources/');
define('WPDDL_INC_ABSPATH', WPDDL_ABSPATH . '/inc');
define('WPDDL_INC_RELPATH', WPDDL_RELPATH . '/inc');
define('WPDDL_CLASSES_ABSPATH', WPDDL_ABSPATH . '/classes');
define('WPDDL_CLASSES_RELPATH', WPDDL_RELPATH . '/classes');
define('WPDDL_RES_ABSPATH', WPDDL_ABSPATH . '/resources');
define('WPDDL_EMPTY_PRESET', WPDDL_RES_ABSPATH . '/preset-layouts/a-empty.ddl');
define('WPDDL_PRIVATE_EMPTY_PRESET', WPDDL_RES_ABSPATH . '/preset-layouts/a1-empty.ddl');
define('WPDDL_RES_RELPATH', WPDDL_RELPATH . '/resources');
define('WPDDL_GUI_ABSPATH', WPDDL_ABSPATH . '/inc/gui/');
define('WPDDL_GUI_RELPATH', WPDDL_RELPATH . '/inc/gui/');
define('WPDDL_SUPPORT_THEME_PATH', WPDDL_INC_ABSPATH . '/theme/');
define( 'WPDDL_CELL_TYPES_ABSPATH', WPDDL_INC_ABSPATH . '/cell_types/' );

define('WPDDL_TOOLSET_COMMON_ABSPATH', WPDDL_TOOLSET_ABSPATH . '/toolset-common');
define('WPDDL_TOOLSET_COMMON_RELPATH', WPDDL_TOOLSET_RELPATH . '/toolset-common');

define('WPDDL_TOOLSET_THEME_SETTINGS_ABSPATH', WPDDL_TOOLSET_ABSPATH . '/toolset-theme-settings');
define('WPDDL_TOOLSET_THEME_SETTINGS_RELPATH', WPDDL_TOOLSET_RELPATH . '/toolset-theme-settings');

define('WPDDL_TOOLSET_OPTIONS', 'toolset_options');
define('WPDDL_MAX_POSTS_OPTION_NAME', 'ddl_max_posts_num');
define('WPDDL_SHOW_CELL_DETAILS_ON_INSERT', 'ddl_show_cell_details_on_insert');
define('WPDDL_MAX_POSTS_OPTION_DEFAULT', 200);


if (!defined('WPDDL_DEBUG')) define('WPDDL_DEBUG', false);

//TODO: this is used for archives / loops it is better to use it only for this data. Should we rename it not to get confused..
define('WPDDL_GENERAL_OPTIONS', 'ddlayouts_options');
define('WPDDL_CSS_OPTIONS', 'layout_css_settings');
define('WPDDL_LAYOUTS_CSS', 'layout_css_styles');
define('WPDDL_JS_OPTIONS', 'layout_js_settings');
define('WPDDL_LAYOUTS_JS', 'layout_js_code');
define( 'WPDDL_LAYOUTS_EDITOR_PAGE', 'dd_layouts_edit' );
define('WPDDL_LAYOUTS_META_KEY', '_layouts_template');
define('WPDDL_LAYOUTS_POST_TYPE', 'dd_layouts');
define('WPDDL_LAYOUTS_SETTINGS', '_dd_layouts_settings');
define('WPDDL_LAYOUTS_EXTRA_MODULES', WPDDL_ABSPATH . '/extra');
define('WPDDL_LAYOUTS_EXTRA_MODULES_REL', WPDDL_RELPATH . '/extra');
define("DDL_WC_SHOP", "ddl_wc_shop");
define("DDL_WC_SHOP_CHECK", "ddl_wc_shop_check");

if (!defined('TOOLSET_EDIT_LAST')) {
	define('TOOLSET_EDIT_LAST', '_toolset_edit_last');
}


define('DDL_ITEMS_PER_PAGE', 10);
define('DDL_MAX_NUM_POSTS', 20);
define("WPDDL_FRAMEWORK_OPTION_KEY", "ddl_framework");
define("WPDDL_FRAMEWORK_OPTION_DEFAULT_KEY", "ddl_framework_default");
define("WPDDL_FRAMEWORK", "bootstrap");
define("WPDDL_FRAMEWORK_VERSION", 3);
define("WPDDL_THE_CONTENT_PRIORITY_RENDER", 999);

// Private layout
define('WPDDL_PRIVATE_LAYOUTS_IN_USE', '_private_layouts_template_in_use');
define('WPDDL_PRIVATE_LAYOUTS_ORIGINAL_CONTENT_META_KEY', '_private_layout_original_content');

// Grid Elements
define('Rows', 'Rows');
define('Cells', 'Cells');
define('Cell', 'Cell');

/** CAPABILITIES **/
// Template Layout
define("DDL_CREATE", "ddl_create_layout" );
define("DDL_ASSIGN", "ddl_assign_layout_to_content" );
define("DDL_EDIT", "ddl_edit_layout" );
define("DDL_DELETE", "ddl_delete_layout" );
// Private Layout
define("DDL_CREATE_PRIVATE", "ddl_create_content_layout" );
define("DDL_ASSIGN_PRIVATE", "ddl_assign_content_layout_to_post" );
define("DDL_EDIT_PRIVATE", "ddl_edit_content_layout" );
define("DDL_DELETE_PRIVATE", "ddl_delete_content_layout" );