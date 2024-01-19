<?php



define('TACCESS_ASSETS_URL',TACCESS_PLUGIN_URL.'/legacy/assets');
define('TACCESS_ASSETS_PATH',TACCESS_PLUGIN_PATH.'/legacy/assets');
define('TACCESS_INCLUDES_PATH',TACCESS_PLUGIN_PATH.'/legacy/includes');
define('TACCESS_TEMPLATES_PATH',TACCESS_PLUGIN_PATH.'/legacy/templates');
define('TACCESS_LOGS_PATH',TACCESS_PLUGIN_PATH.'/logs');
define( 'TACCESS_LOCALE_PATH', TACCESS_PLUGIN_PATH . '/legacy/locale' );
// backwards compatibility
define('WPCF_ACCESS_VERSION', TACCESS_VERSION);
// rename these, because conflicts

define('WPCF_ACCESS_INC_', TACCESS_INCLUDES_PATH);
// for WPML
define('TACCESS_WPML_STRING_CONTEXT','Types_Access');




// our global object
global $wpcf_access;

// logging function
if (!function_exists('taccess_log'))
{
if (defined('TACCESS_DEBUG')&&TACCESS_DEBUG)
{
    function taccess_log($message, $file=null, $level=1)
    {
        // check if we need to log..
        if (!defined('TACCESS_DEBUG')||!TACCESS_DEBUG) return false;

        // full path to log file
        if ($file==null)
        {
            $file='debug.log';
        }

        $file=TACCESS_LOGS_PATH.DIRECTORY_SEPARATOR.$file;

        /* backtrace */
		// phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.Changed
        $bTrace = debug_backtrace(); // assoc array

        /* Build the string containing the complete log line. */
        $line = PHP_EOL.sprintf('[%s, <%s>, (%d)]==> %s',
                                date("Y/m/d h:i:s", mktime()),
                                basename($bTrace[0]['file']),
                                $bTrace[0]['line'],
                                print_r($message,true) );

        if ($level>1)
        {
            $i=0;
            $line.=PHP_EOL.sprintf('Call Stack : ');
            while (++$i<$level && isset($bTrace[$i]))
            {
                $line.=PHP_EOL.sprintf("\tfile: %s, function: %s, line: %d".PHP_EOL."\targs : %s",
                                    isset($bTrace[$i]['file'])?basename($bTrace[$i]['file']):'(same as previous)',
                                    isset($bTrace[$i]['function'])?$bTrace[$i]['function']:'(anonymous)',
                                    isset($bTrace[$i]['line'])?$bTrace[$i]['line']:'UNKNOWN',
                                    print_r($bTrace[$i]['args'],true));
            }
            $line.=PHP_EOL.sprintf('End Call Stack').PHP_EOL;
        }
        // log to file
        file_put_contents($file,$line,FILE_APPEND);

        return true;
    }
}
else
{
    function taccess_log()  { }
}
}



// <<<<<<<<<<<< includes --------------------------------------------------
// Constants
require TACCESS_INCLUDES_PATH . '/Constants.php';
// Loader
include(TACCESS_PLUGIN_PATH.'/legacy/loader.php');
// Cacher
TAccess_Loader::load('CLASS/Cacher');
Access_Cacher::init();

add_action( 'after_setup_theme', 'otg_access_shortcode_generator_initialize', 999 );
function otg_access_shortcode_generator_initialize() {
	$toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
	$toolset_common_sections = array( 'toolset_shortcode_generator' );
	$toolset_common_bootstrap->load_sections( $toolset_common_sections );
	TAccess_Loader::load('CLASS/Shortcode');
	new OTG_Access_Shortcode_Generator();
}



// auxilliary global functions

// register the function for backwards compatibility
function wpcf_access_register_caps() {}


/**
 * WPML translate call.
 *
 * @param string $name
 * @param string $string
 * @param string $string
 * @return string
 */
function taccess_translate($name, $string, $context = TACCESS_WPML_STRING_CONTEXT) {
	$string = apply_filters( 'wpml_translate_single_string', $string, $context, $name );
    return $string;
}


/**
 * Registers WPML translation string.
 *
 * @param string $name
 * @param string $value
 * @param string $context
 */
function taccess_translate_register_string($name, $value, $context = TACCESS_WPML_STRING_CONTEXT,  $allow_empty_value = false)
{
    if (function_exists('icl_register_string')) {
        icl_register_string($context, $name, stripslashes($value),
                $allow_empty_value);
    }
}


// register if needed and translatev on the fly
function taccess_t($name, $str, $context = TACCESS_WPML_STRING_CONTEXT,  $allow_empty_value = false)
{
    taccess_translate_register_string($name, $str, $context,  $allow_empty_value);
    return taccess_translate($name, $str, $context);
}

/*
 * List of caps with Description
 */
function getDefaultWordpressCaps()
{

		$default_wordpress_caps = array(
			'activate_plugins' => __('Allows access to Administration Panel: Plugins','wpcf-access'),
			'edit_dashboard' => __('Edit Dashboard','wpcf-access'),
			'edit_theme_options' => __('Allows access to Administration Panel: Appearance (Widgets, Menus, Customize, Background, Header)','wpcf-access'),
			'export' => __('Allows access to Administration Panel: Export','wpcf-access'),
			'import' => __('Allows access to Administration Panel: Import','wpcf-access'),
			'list_users' => __('List users','wpcf-access'),
			'manage_links' => __('Allows access to Administration Panel: Links, Add new link','wpcf-access'),
			'manage_options' => __('Allows access to Administration Panel: Settings (General , Writing , Reading, Discussion, Permalinks, Miscellaneous)','wpcf-access'),
			'promote_users' => __('Enables the "Change role to..." dropdown in the admin user list','wpcf-access'),
			'remove_users' => __('Remove users','wpcf-access'),
			'switch_themes' => __('Allows access to Administration Panel: Appearance (Themes)','wpcf-access'),
			'upload_files' => __('Allows access to Administration Panel: Media (Add New)','wpcf-access'),
			'update_core' => __('Update Wordpress Core Files','wpcf-access'),
			'update_plugins' => __('Update Plugins','wpcf-access'),
			'update_themes' => __('Update Themes','wpcf-access'),
			'install_plugins' => __('Install New Plugins','wpcf-access'),
			'install_themes' => __('Install New Themes','wpcf-access'),
			'delete_themes' => __('Delete Themes','wpcf-access'),
			'edit_plugins' => __('Edit Plugin Files','wpcf-access'),
			'edit_themes' => __('Edit Theme Files','wpcf-access'),
			'edit_users' => __('Edit User Options','wpcf-access'),
			'create_users' => __('Add New Users','wpcf-access'),
			'delete_users' => __('Delete Users (Only for single site)','wpcf-access'),
			'unfiltered_html' => __('Allows user to post HTML markup or even JavaScript code in pages, posts, and comments','wpcf-access'),
			'delete_plugins' => __('Delete Plugins','wpcf-access')
		);
		return $default_wordpress_caps;
}

/*
 * List of default wordpress caps by level
 */
function getDefaultCaps(){
	$default_caps = array();
	//Level 0
	$default_caps[0] = array();

	//Level 1
	$default_caps[1] = array('delete_posts','edit_posts');

	//Level 2,3,4,5,6
	$default_caps[2] = $default_caps[3] = $default_caps[4] = $default_caps[5] = $default_caps[6] = array(
	'upload_files','delete_posts','delete_published_posts','edit_posts','edit_published_posts','publish_posts'
	);

	//Level 7,8,9
	$default_caps[7] = $default_caps[8] = $default_caps[9] = array(
	    'delete_others_pages','delete_others_posts','delete_pages','delete_posts','delete_private_pages','delete_private_posts',
	    'delete_published_pages','delete_published_posts','edit_others_pages','edit_others_posts','edit_pages','edit_posts','edit_private_pages',
	    'edit_private_posts','edit_published_pages','edit_published_posts','manage_categories','manage_links','moderate_comments','publish_pages',
	    'publish_posts','read_private_pages','read_private_posts','unfiltered_html','upload_files'
	);

	//Level 10
	$default_caps[10] = array(
		'activate_plugins','delete_others_pages','delete_others_posts','delete_pages','delete_plugins','delete_posts','delete_private_pages',
		'delete_private_posts','delete_published_pages','delete_published_posts','edit_dashboard','edit_files','edit_others_pages',
		'edit_others_posts','edit_pages','edit_posts','edit_private_pages','edit_private_posts','edit_published_pages','edit_published_posts',
		'edit_theme_options','export','import','list_users','manage_categories','manage_links','manage_options','moderate_comments','promote_users',
		'publish_pages','publish_posts','read_private_pages','read_private_posts','remove_users','switch_themes','upload_files','create_product',
		'update_core','update_plugins','update_themes','install_plugins','install_themes','delete_themes','edit_plugins','edit_themes','edit_users',
		'create_users','delete_users','unfiltered_html'
	);

	return $default_caps;
}

//Get Woocommerce caps
function get_woocommerce_caps(){
	$woocommerce_caps = array(
		'manage_woocommerce'=>__('Manage WooCommerce Settings','wpcf-access'),
		'view_woocommerce_reports'=>__('Manage WooCommerce Reports','wpcf-access')
	);
	return $woocommerce_caps;
}
//Get WPML caps
function get_wpml_caps(){
	$wpml_caps_list = array(
		'wpml_manage_translation_management'=>__('Manage Translation Management','wpcf-access'),
		'wpml_manage_languages'=>__('Manage Languages','wpcf-access'),
		'wpml_manage_theme_and_plugin_localization'=>__('Manage Theme and Plugin localization','wpcf-access'),
		'wpml_manage_support'=>__('Manage Support','wpcf-access'),
		'wpml_manage_woocommerce_multilingual'=>__('Manage WooCommerce Multilingual','wpcf-access'),
		'wpml_operate_woocommerce_multilingual'=>__('Operate WooCommerce Multilingual. Everything on WCML except the settings tab.','wpcf-access'),
		'wpml_manage_media_translation'=>__('Manage Media translation','wpcf-access'),
		'wpml_manage_navigation'=>__('Manage Navigation','wpcf-access'),
		'wpml_manage_sticky_links'=>__('Manage Sticky Links','wpcf-access'),
		'wpml_manage_string_translation'=>__('Manage String Translation','wpcf-access'),
		'wpml_manage_translation_analytics'=>__('Manage Translation Analytics','wpcf-access'),
		'wpml_manage_wp_menus_sync'=>__('Manage WPML Menus Sync','wpcf-access'),
		'wpml_manage_taxonomy_translation'=>__('Manage Taxonomy Translation','wpcf-access'),
		'wpml_manage_troubleshooting'=>__('Manage Troubleshooting','wpcf-access'),
		'wpml_manage_translation_options'=>__('Translation options','wpcf-access')
	);
	return $wpml_caps_list;
}

/**
 * @return array
 * @deprecated
 */
function get_toolset_caps(){
	$wpml_caps_list = array(
		'toolset_manage_views'=>__('Manage Views','wpcf-access'),
		'toolset_manage_types'=>__('Manage Types','wpcf-access'),
		'toolset_manage_cred'=>__('Manage CRED','wpcf-access'),
		'toolset_manage_access'=>__('Manage Access','wpcf-access'),
	);
	return $wpml_caps_list;
}

function wpcf_check_if_woocommerce(){

	if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }

}

function wpcf_access_woocommerce_capabilities ($data){
    $is_woocommerce = wpcf_check_if_woocommerce();
	if ( $is_woocommerce ){
        $wp_roles['label'] = __('Woocommerce capabilities', 'wpcf-access');
        $wp_roles['capabilities'] = array(
            'manage_woocommerce' => __('Manage WooCommerce Settings','wpcf-access'),
            'view_woocommerce_reports' => __('Manage WooCommerce Reports','wpcf-access')
        );
        $data[] = $wp_roles;
	}
    return $data;
}

function wpcf_access_wpml_capabilities ($data){


    if( defined('ICL_SITEPRESS_VERSION') && ICL_SITEPRESS_VERSION >= 3.1){


        if ( has_filter('wpml_roles_read_only') ){
            $wp_roles = apply_filters('wpml_roles_read_only',array());
        }else{
            $wp_roles['label'] = __('WPML capabilities', 'wpcf-access');
            $wp_roles['capabilities'] = array(
                'wpml_manage_translation_management'=>__('Manage Translation Management','wpcf-access'),
                'wpml_manage_languages'=>__('Manage Languages','wpcf-access'),
                'wpml_manage_theme_and_plugin_localization'=>__('Manage Theme and Plugin localization','wpcf-access'),
                'wpml_manage_support'=>__('Manage Support','wpcf-access'),
                'wpml_manage_woocommerce_multilingual'=>__('Manage WooCommerce Multilingual','wpcf-access'),
                'wpml_operate_woocommerce_multilingual'=>__('Operate WooCommerce Multilingual. Everything on WCML except the settings tab.','wpcf-access'),
                'wpml_manage_media_translation'=>__('Manage Media translation','wpcf-access'),
                'wpml_manage_navigation'=>__('Manage Navigation','wpcf-access'),
                'wpml_manage_sticky_links'=>__('Manage Sticky Links','wpcf-access'),
                'wpml_manage_string_translation'=>__('Manage String Translation','wpcf-access'),
                'wpml_manage_translation_analytics'=>__('Manage Translation Analytics','wpcf-access'),
                'wpml_manage_wp_menus_sync'=>__('Manage WPML Menus Sync','wpcf-access'),
                'wpml_manage_taxonomy_translation'=>__('Manage Taxonomy Translation','wpcf-access'),
                'wpml_manage_troubleshooting'=>__('Manage Troubleshooting','wpcf-access'),
                'wpml_manage_translation_options'=>__('Translation options','wpcf-access')
            );
        }
        $data[] = $wp_roles;
	}
    return $data;
}


function wpcf_access_general_capabilities ($data){
    $wp_roles['label'] = __('General capabilities', 'wpcf-access');
    $wp_roles['capabilities'] = getDefaultWordpressCaps();
    $data[] = $wp_roles;
    return $data;
}

function wpcf_access_access_capabilities ($data){
    $wp_roles['label'] = __('Access capabilities', 'wpcf-access');
    $wp_roles['capabilities'] = array(
		'access_change_post_group'=>__('Select access group for content','wpcf-access'),
        'access_create_new_group'=>__('Create new access groups','wpcf-access')
    );
    $data[] = $wp_roles;
    return $data;
}
function wpcf_access_layouts_capabilities ($data){
    if ( class_exists('WPDD_Layouts_Users_Profiles') ){
        $wp_roles['label'] = __('Layouts capabilities', 'wpcf-access');
        $wp_roles['capabilities'] = WPDD_Layouts_Users_Profiles::ddl_get_capabilities();
        $data[] = $wp_roles;
	}
    return $data;
}

/**
* toolset_access_return_installed_version
*
* Return the curently installed Access version number.
*
* @since 2.1
*/

add_filter( 'toolset_access_version_installed', 'toolset_access_return_installed_version' );

function toolset_access_return_installed_version( $version ) {
	return TACCESS_VERSION;
};
