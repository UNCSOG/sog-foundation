<?php

// current version
if (!(defined('MODMAN_VERSION'))) {
define('MODMAN_VERSION','1.8.10');
}

if (!(defined('MODMAN_NAME'))) {
define('MODMAN_NAME','MODMAN');
}

if (!(defined('MODMAN_CAPABILITY'))) {
define('MODMAN_CAPABILITY','manage_options');
}

if (!(defined('MODMAN_PLUGIN_PATH'))) {
if ( function_exists('realpath') )
    define('MODMAN_PLUGIN_PATH', realpath(dirname(__FILE__)));
else
    define('MODMAN_PLUGIN_PATH', dirname(__FILE__));
}

if (!(defined('MODMAN_PLUGIN'))) {
define('MODMAN_PLUGIN', plugin_basename(__FILE__));
}

if (!(defined('MODMAN_PLUGIN_FOLDER'))) {
define('MODMAN_PLUGIN_FOLDER', basename(MODMAN_PLUGIN_PATH));
}

if (!(defined('MODMAN_PLUGIN_NAME'))) {
define('MODMAN_PLUGIN_NAME',MODMAN_PLUGIN_FOLDER.'/'.basename(__FILE__));
}

if (!(defined('MODMAN_PLUGIN_BASENAME'))) {
define('MODMAN_PLUGIN_BASENAME',MODMAN_PLUGIN);
}

if (!defined('WPVDEMO_TOOLSET_DOMAIN')) {
	define('WPVDEMO_TOOLSET_DOMAIN', 'toolset.com');
}

//Define correct URL with embedded MM implementation
if (!(defined('MODMAN_PLUGIN_URL'))) {
	if (defined('MODMAN_RUN_MODE')) {
		define('MODMAN_PLUGIN_URL',plugins_url().'/'.MODMAN_PLUGIN_FOLDER);
	} else {
		//Not full version detected!
		//First, let's check what kind is activated
		if (defined('MODULE_MANAGER_EMBEDDED_ALONE')) {
			//There you go, standalone embedded activation, let's load resources from inside plugins
			define('MODMAN_PLUGIN_URL',plugins_url().'/'.MODMAN_PLUGIN_FOLDER);
		} else {
			//This might be theme embedded
			define('MODMAN_PLUGIN_URL',get_template_directory_uri().'/'.MODMAN_PLUGIN_FOLDER);
		}
	}
}

if (!(defined('MODMAN_ASSETS_URL'))) {
define('MODMAN_ASSETS_URL',MODMAN_PLUGIN_URL.'/assets');
}

if (!(defined('MODMAN_ASSETS_PATH'))) {
define('MODMAN_ASSETS_PATH',MODMAN_PLUGIN_PATH.'/assets');
}

if (!(defined('MODMAN_VIEWS_PATH'))) {
define('MODMAN_VIEWS_PATH',MODMAN_PLUGIN_PATH.'/views');
}

if (!(defined('MODMAN_TEMPLATES_PATH'))) {
define('MODMAN_TEMPLATES_PATH',MODMAN_PLUGIN_PATH.'/views/templates');
}

if (!(defined('MODMAN_CLASSES_PATH'))) {
define('MODMAN_CLASSES_PATH',MODMAN_PLUGIN_PATH.'/classes');
}

if (!(defined('MODMAN_COMMON_PATH'))) {
define('MODMAN_COMMON_PATH',MODMAN_PLUGIN_PATH.'/common');
}

if (!(defined('MODMAN_TABLES_PATH'))) {
define('MODMAN_TABLES_PATH',MODMAN_PLUGIN_PATH.'/views/tables');
}

if (!(defined('MODMAN_CONTROLLERS_PATH'))) {
define('MODMAN_CONTROLLERS_PATH',MODMAN_PLUGIN_PATH.'/controllers');
}

if (!(defined('MODMAN_MODELS_PATH'))) {
define('MODMAN_MODELS_PATH',MODMAN_PLUGIN_PATH.'/models');
}

if (!(defined('MODMAN_LOGS_PATH'))) {
define('MODMAN_LOGS_PATH',MODMAN_PLUGIN_PATH.'/logs');
}

if (!(defined('MODMAN_LOCALE_PATH'))) {
define('MODMAN_LOCALE_PATH',MODMAN_PLUGIN_FOLDER.'/locale');
}

if (!(defined('MODMAN_LIBRARYCLASS_PATH'))) {
//Define library class path
define('MODMAN_LIBRARYCLASS_PATH',MODMAN_PLUGIN_PATH.'/library');
}

if (!(defined('MODMAN_LIBRARYXML_PATH'))) {
//Define modules library XML download path
define( 'MODMAN_LIBRARYXML_PATH', 'https://ref.toolset.com/_reference_sites/demos-index-modules.json' );
}

if (!(defined('MODMAN_ORIGINATING_HOST'))) {
	//Define modules library XML download path
	define('MODMAN_ORIGINATING_HOST','ref.toolset.com');
}

// save temp module zips
if (!(defined('MODMAN_TMP_PATH'))) {
define('MODMAN_TMP_PATH',WP_CONTENT_DIR.'/_modulemanager_tmp_');
}

if (!(defined('MODMAN_TMP_LOCK'))) {
define('MODMAN_TMP_LOCK','______lock_____');
}

// clear all tmps after this time
if (!(defined('MODMAN_PURGE_TIME'))) {
define('MODMAN_PURGE_TIME', 86400); // 24 hours
}

if (!(defined('MODMAN_MODULE_INFO'))) {
define('MODMAN_MODULE_INFO','__module_info__');
}

if (!(defined('MODMAN_MODULE_TMP_FILE'))) {
define('MODMAN_MODULE_TMP_FILE','__module_tmp_file__');
}

// load on the go resources
if (!( defined('MODULE_MANAGER_EMBEDDED_ALONE') )) {
	//We wish to initialize this only when running the full plugin mode
	require_once MODMAN_PLUGIN_PATH . '/onthego-resources/loader.php';
	onthego_initialize(MODMAN_PLUGIN_PATH . '/onthego-resources', MODMAN_PLUGIN_URL. '/onthego-resources/' );
}

/*
if (!(defined('MODMAN_DEBUG'))) {
define('MODMAN_DEBUG',true);
}

if (!(defined('MODMAN_DEV'))) {
define('MODMAN_DEV',true);
}
*/

// logging function
if (!function_exists('modman_log'))
{
if (defined('MODMAN_DEBUG')&&MODMAN_DEBUG)
{
    function modman_log($message, $file=null, $type=null, $level=1)
    {
        // debug levels
        $dlevels=array(
            'default' => defined('MODMAN_DEBUG') && MODMAN_DEBUG
        );

        // check if we need to log..
        if (!$dlevels['default']) return false;
        if ($type==null) $type='default';
        if (!isset($dlevels[$type]) || !$dlevels[$type]) return false;

        // full path to log file
        if ($file==null)
        {
            $file='debug.log';
        }
        $file=MODMAN_LOGS_PATH.DIRECTORY_SEPARATOR.$file;

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
    function modman_log()  { }
}
}

if (!function_exists('mm_check_toolset_plugins'))
{
	/*
	* mm_check_toolset_plugins
	*
	* Checks info for the Toolset plugins on the current site
	*
	* @param $info (string) 'all_installed'|'all_full'|'any_embedded'
	*
	* @return boolean
	*/
	/* Returns FALSE if Types or Views are installed on embedded mode, TRUE otherwise */
	function mm_check_toolset_plugins( $info = 'all_installed' ) {
		$installed = array();
		$full = array();
		$embedded = array();
		$result = false;

		/*Types*/
		global $wpcf;
		if ( isset( $wpcf ) ) {
			//Types is active
			$installed[] = 'types';
			if ( defined( 'WPCF_RUNNING_EMBEDDED' ) ) {
				$embedded[] = 'types';
			} else {
				$full[] = 'types';
			}
		}

		/*Views*/
		global $WP_Views;
		if ( isset( $WP_Views ) ) {
			// Views is installed
			$installed[] = 'views';
			if ( $WP_Views->is_embedded() ) {
				$embedded[] = 'views';
			} else {
				$full[] = 'views';
			}
		}

		/*CRED*/
		if ( defined( 'CRED_FE_VERSION' ) ) {
			// CRED is installed therefore full
			$installed[] = 'cred';
			$full[] = 'cred';
		}

		switch ( $info ) {
			case 'all_installed':
				$instaled_count = count( $installed );
				if ( $instaled_count === 3 ) {
					$result = true;
				}
				break;
			case 'all_full':
				$full_count = count( $full );
				if ( $full_count === 3 ) {
					$result = true;
				}
				break;
			case 'any_embedded':
				$embedded_count = count( $embedded );
				if ( $embedded_count > 0 ) {
					$result = true;
				}
				break;
			case 'all_disabled':
				$embedded_count = count( $embedded );
				$installed_count = count( $installed );
				$full_count = count( $full );
				if (($embedded_count===0) && ($installed_count===0) && ($full_count===0)) {
					$result = true;
				}
				break;
			case 'full_types_views':
				if ((in_array('types',$full)) && (in_array('views',$full)) && (!(in_array('cred',$full)))) {
					$result = true;
				}
				break;
		}

		return $result;
	}
}

if (!function_exists('mm_check_has_cred_in_module')) {
	    //Returns TRUE if has CRED in module.
	    function mm_check_has_cred_in_module() {
			$current_modules=get_option('modman_modules');
			$has_cred=array();
			$result_cred=false;
			if (is_array($current_modules))	{
              foreach ($current_modules as $k=>$the_module) {
              	   if (is_array($the_module))	{
              	   		if (array_key_exists('cred', $the_module)) {
              	   			$has_cred[]=$k;
              	   		}
              	   }
              }

              $counted=count($has_cred);
              if ($counted > 0) {
              	$result_cred=true;
              }
			}
			return $result_cred;
	    }
}
/** Add the Views translations in the common folder */
if ( !defined( 'WPT_LOCALIZATION' ) ) {
	require_once( MODMAN_COMMON_PATH . '/localization/wpt-localization.php' );
}
if (!function_exists('modman_embedded_locale')) {
/** This is added on embedded plugin.php only, used to load locale in embedded mode used in Toolset Bootstrap theme */
	add_filter( 'load_textdomain_mofile', 'modman_embedded_locale' );
	function modman_embedded_locale($path) {
		if ((!(defined('MODMAN_RUN_MODE'))) && (!(defined('MODULE_MANAGER_EMBEDDED_ALONE')))) {
			//We are running embedded and standalone mode
			if (defined('WP_PLUGIN_DIR')) {
				$embedded_test_path=WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'embedded-modules-manager';
				$embedded_theme_path=get_template_directory();
				if ((strpos($path , $embedded_test_path) !== false)) {
					$locale_path = str_replace(WP_PLUGIN_DIR, $embedded_theme_path, $path);
					if (file_exists($locale_path)) {
						$path= $locale_path;
					}
				}
			}
		}
		return $path;
	}
}
// <<<<<<<<<<<< includes --------------------------------------------------
include(MODMAN_PLUGIN_PATH.'/loader.php');
// include basic classes
ModMan_Loader::load('CLASS/ModuleManager');
// init
ModuleManager::init();
