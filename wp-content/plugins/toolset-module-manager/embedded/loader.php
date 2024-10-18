<?php
if (!class_exists('ModMan_Loader', false))
{
/**
 *  ModMan_Loader
 *
 *  This class is responsible for loading/including all files and getting instances of all objects
 *  in an efficient and abstract manner, abstracts all hardcoded paths and dependencies and manages singleton instances
 */

// define interfaces used to implement design patterns
if (!interface_exists('ModMan_Singleton', false))
{
    /**
    * Singleton interface used as "tag" to mark singleton classes.
    */
    interface ModMan_Singleton
    {
    }
}

// it is unique
final class ModMan_Loader
{
    // pool of singleton instances, implement singleton factory, tag with singleton interface
    private static $__singleton_instances__ = array();

    // some dependencies here
    private static $__dependencies__=array();
    private static $__loaded_dependencies__=array();
    private static $__assets__=array();

    public static function init()
    {
        self::_init_();
    }

    public static function _init_()
    {
        // init assets, if any
        self::$__assets__=array(
            'SCRIPT'=>array(
                'module-manager-dev'=>array(
                    'version'=>MODMAN_VERSION,
                    'dependencies'=>array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'wp-pointer'),
                    'path'=>MODMAN_ASSETS_URL.'/js/modman.js'
                ),
                'module-manager'=>array(
                    'version'=>MODMAN_VERSION,
                    'dependencies'=>array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'wp-pointer'),
                    'path'=>MODMAN_ASSETS_URL.'/js/modman.js'
                )
            ),
            'STYLE'=>array(
                'font-awesome'=>array(
                    'version'=>MODMAN_VERSION,
                    'dependencies'=>null,
                    'path'=>MODMAN_ASSETS_URL.'/css/font-awesome.min.css'
                ),
                'module-manager-dev'=>array(
                    'version'=>MODMAN_VERSION,
                    'dependencies'=>array('font-awesome', 'wp-pointer'),
                    'path'=>MODMAN_ASSETS_URL.'/css/modman.css'
                ),
                'module-manager'=>array(
                    'version'=>MODMAN_VERSION,
                    'dependencies'=>array('font-awesome', 'wp-pointer'),
                    'path'=>MODMAN_ASSETS_URL.'/css/modman.css'
                )
            )
        );

        // init dependencies paths, if any
        self::$__dependencies__=array(
            'CONTROLLER'=>array(
                'Modules' => array(
                    array(
                        'class' => 'ModMan_Modules_Controller',
                        'path' => MODMAN_CONTROLLERS_PATH.'/Modules.php'
                    )
                )
            ),
            'MODEL'=>array(
                'Modules' => array(
                    array(
                        'class' => 'ModMan_Modules_Model',
                        'path' => MODMAN_MODELS_PATH.'/Modules.php'
                    )
                )
            ),
            'CLASS'=>array(
                'ModuleManager' => array(
                    array(
                        'class' => 'ModMan_Settings',
                        'path' => MODMAN_COMMON_PATH.'/Settings.php'
                    ),
                    array(
                        'class' => 'ModMan_Ajax_Router',
                        'path' => MODMAN_CLASSES_PATH.'/Ajax_Router.php'
                    ),
                    array(
                        'class' => 'ModuleManager',
                        'path' => MODMAN_CLASSES_PATH.'/ModuleManager.php'
                    )
                ),
                'Settings' => array(
                    array(
                        'class' => 'ModMan_Settings',
                        'path' => MODMAN_COMMON_PATH.'/Settings.php'
                    )
                ),
                'Ajax_Router' => array(
                    array(
                        'class' => 'ModMan_Ajax_Router',
                        'path' => MODMAN_CLASSES_PATH.'/Ajax_Router.php'
                    )
                )
            ),
            'VIEW'=>array(
                'modules' => array(
                    array(
                        'path' => MODMAN_VIEWS_PATH.'/modules.php'
                    )
                )
            )
        );
    }

    public static function loadLocale($id, $locale_file=false)
    {
        // load translations from locale
        load_plugin_textdomain($id, false, MODMAN_LOCALE_PATH);
        /*load_textdomain($id, MODMAN_LOCALE_PATH . '/' . $locale_file);*/
    }

    // load an asset with dependencies
    public static function loadAsset($qclass, $registerAs=false, $enqueueIt=true)
    {
        list($type, $class)=explode('/', $qclass, 2);

        if (
            isset(self::$__assets__[$type]) &&
            isset(self::$__assets__[$type][$class])
        )
        {
            $_type=&self::$__assets__[$type];
            $_class=&$_type[$class];
            if (is_array($_class['dependencies']) && !empty($_class['dependencies']))
            {
                foreach ($_class['dependencies'] as $_dep)
                {
                    if (isset(self::$__assets__[$type][$_dep]))
                    {
                        // recursively register dependencies
                        self::loadAsset("$type/$_dep", false, false);
                    }
                }
            }
            $registerAs=($registerAs)?$registerAs:$class;
            if ('SCRIPT'==$type && isset($_class['path']))
            {
                $isFooter=isset($_class['footer'])?$_class['footer']:false;
                wp_register_script($registerAs, $_class['path'], $_class['dependencies'], $_class['version'], $isFooter);
                if ($enqueueIt)
                    wp_enqueue_script($registerAs);
            }
            elseif ('STYLE'==$type && isset($_class['path']))
            {
                wp_register_style($registerAs, $_class['path'], $_class['dependencies'], $_class['version']);
                if ($enqueueIt)
                    wp_enqueue_style($registerAs);
            }
        }
    }

    // include a php file
    private static function getFile($path, $_in='require_once')
    {
        if( !is_file($path) )
        {
            printf(__('File "%s" doesn\'t exist!', 'module-manager'), $path);
            return false;
        }

        switch ($_in)
        {
            case 'include':
                include $path;
                break;

            case 'include_once':
                include_once $path;
                break;

            case 'require':
                require $path;
                break;

            case 'require_once':
                require_once $path;
                break;
        }

        return true;
    }

    // import a php class
    private static function getClass($class, $path, $_in='require_once')
    {
        if ( !class_exists( $class, false ) )
            self::getFile( $path, $_in );
    }

    // load a class with dependencies if needed
    public static function load($qclass)
    {
        list($type, $class)=explode('/', $qclass, 2);

        // try to optimize a little bit
        if ( in_array($qclass, self::$__loaded_dependencies__) )
        {
            $is_loaded=true;
        }
        else
        {
            $is_loaded=false;
            self::$__loaded_dependencies__[]=$qclass;
        }

        if (
            isset(self::$__dependencies__[$type]) &&
            isset(self::$__dependencies__[$type][$class])
        )
        {
            $_type=&self::$__dependencies__[$type];
            $_class=&$_type[$class];
            if ( !$is_loaded )
            {
                if ( isset($_type['%%PARENT%%']) && is_array($_type['%%PARENT%%']) )
                {
                    $_parent=&$_type['%%PARENT%%'];
                    foreach ($_parent as $_dep)
                    {
                        if (isset($_dep['class']))
                            self::getClass($_dep['class'], $_dep['path']);
                        else
                            self::getFile($_dep['path']);
                    }
                }
                foreach ($_class as $_dep)
                {
                    if (isset($_dep['class']))
                        self::getClass($_dep['class'], $_dep['path']);
                    else
                        self::getFile($_dep['path']);
                }
            }
            $class=end($_class);
            if (isset($class['class']))
                $class=$class['class'];
        }
        elseif ( !$is_loaded )
        {
            self::getFile($qclass);
            $class=$qclass;
        }
        return array(false, $class);
   }

    // singleton factory pattern, to enable singleton in php 5.2, etc..
    // http://stackoverflow.com/questions/7902586/extend-a-singleton-with-php-5-2-x
    // http://stackoverflow.com/questions/7987313/how-to-subclass-a-singleton-in-php
    // use tags (interfaces) to denote singletons, and then use "singleton factory"
    // http://phpgoodness.wordpress.com/2010/07/21/singleton-and-multiton-with-a-different-approach/
    // ability to pass parameters, and to avoid the singleton flag
    public static function get($qclass)
    {
        // if instance is in pool, return it immediately, only singletons are in the pool
        if ( isset(self::$__singleton_instances__[$qclass]) )
            return self::$__singleton_instances__[$qclass];

        $instance = null;

        // load it if needed
        list($type, $class) = self::load($qclass);
        // make sure it is loaded (exists) and is not interface
        // make sure it is not an interface (PHP 5)
        //if ( !$reflection->isInterface() )
        if ( class_exists( $class, false ) && !interface_exists( $class, false ))
        {
            // Parameters to call constructor (in case) and multiton's getFace (in case) with.
			// phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
            $args = func_get_args();
            array_shift( $args );

            if ( empty($args) )  // (PHP 5)
            {
                // If the object doesn't have arguments to be passed, we just instantiate it.
                // It might be quicker to use - new $class_name -.
                $instance = new $class(); //$reflection->newInstance(); // PHP 5
            }
            else
            {
                // delay using reflection unless absolutely needed (eg there are args to be passed)
                // Here's the point where we have to instantiate the class anyway. (supports PHP 5)
                $reflection = new ReflectionClass( $class );
                if ( null !== $reflection->getConstructor() )   // (PHP 5)
                    // If the object does have constructor, we pass the additional parameters we got in this method.
                    $instance = $reflection->newInstanceArgs( $args ); // PHP > 5.1.3
                else
                    // If the object doesn't have constructor, we just instantiate it.
                    // It might be quicker to use - new $class_name -.
                    $instance = new $class(); //$reflection->newInstance(); // PHP 5
            }

            if ($instance)
            {
                // If it's a singleton, we have to keep track of it. (PHP 5)
                // If might be quicker to use - $new_instance instanceof Singleton -.
                if ( $instance instanceof ModMan_Singleton /*$reflection->isSubclassOf( 'ModMan_Singleton' )*/ )
                {
                    self::$__singleton_instances__[$qclass] = $instance;
                }
                return $instance;
            }
        }
        // sth failed, no instance, return null here finally
        return null;
    }

    // USE WP Object_Cache API to cache templates (so 3rd-party cache plugins can be used also)
    public static function tpl($template, array $args=array(), $cache=false)
    {
        $template_path = MODMAN_TEMPLATES_PATH . DIRECTORY_SEPARATOR . $template . '.tpl.php';

        // NEW use caching of templates
        $output=false;
        if ($cache)
        {
            $group='_ModMan_';
            $key=md5(serialize(array($template_path, $args)));
            $output=wp_cache_get( $key, $group );
        }

        if (false === $output)
        {
            if (!is_file($template_path))
            {
                printf(__('File "%s" doesn\'t exist!', 'module-manager'), $template_path);
                return '';
            }
            $output = self::getTpl($template_path, $args);
            if ($cache) wp_cache_set( $key, $output, $group/*, $expire*/ );
        }
        return $output;
    }

    private static function getTpl($______templatepath________, array $______args______=array())
    {
        ob_start();
            if (!empty($______args______)) extract($______args______);
            include($______templatepath________);
        return ob_get_clean();
    }
}

// init on load
ModMan_Loader::init();

}
