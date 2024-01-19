<?php

if ( !class_exists( 'CRED_Loader', false ) ) {
// it is unique

    /**
     *  CRED_Loader
     *
     *  This class is responsible for loading/including all files and getting instances of all objects
     *  in an efficient and abstract manner, abstracts all hardcoded paths and dependencies and manages singleton instances automatically
     *
     *  Also it renders and caches templates even from 3rd-party plugins (that want to use the templating engine, eg Toolset Forms Commerce)
     *
     */
// define interfaces used to implement design patterns
    if ( !interface_exists( 'CRED_Singleton', false ) ) {

        /**
         * Singleton interface used as "tag" to mark singleton classes.
         */
        interface CRED_Singleton {

        }

    }

    /**
     * Class that is doing many loosely related things at once, one of those that we want to get rid of.
     *
     * @deprecated Deprecated since 1.9. The loader is - for classes - replaced by the Toolset autoloader (see CRED_Main).
     * For other assets, new manager class(es) might be needed. Don't use CRED_Loader in new (after-1.9) code.
     */
    class CRED_Loader {

        // pool of singleton instances, implement singleton factory, tag with singleton interface
        private static $__singleton_instances__ = array();
        private static $__loaded_dependencies__ = array();
        // some dependencies here
        private static $__dependencies__ = array();
        private static $__assets__ = array();
        private static $metaboxes = array();
        private static $doneInit = false;

        public static function init($__FILE__ = null) {
            if (
                    ( isset( $_GET['css'] ) || isset( $_GET['js'] ) ) &&
                    self::isRequestedFile( $__FILE__ ) &&
                    !defined( 'LOADING_ASSETS_' . __CLASS__ . '_' . $__FILE__ )
            ) {
                // called ~standalone to load concatenated assets, css or js
                define( 'LOADING_ASSETS_' . __CLASS__ . '_' . $__FILE__, 1 );
                self::loadAssets();
                return;
            }
            // called within WP
            self::_init_();
        }

        private static function _init_() {
            if ( !self::$doneInit ) {
                self::$doneInit = true;
                if ( function_exists( 'add_action' ) )
                // do the loader auto load after plugins have loaded
                    add_action( 'plugins_loaded', array(__CLASS__, 'doAutoLoad'), 10 );
            }
        }

        public static function isRequestedFile($__FILE__ = null) {
            if ( !$__FILE__ )
                $__FILE__ = (string) __FILE__;

            $file_path_info = pathinfo( $__FILE__ );
            if ( $file_path_info['extension'] != 'php' &&
                    $file_path_info['extension'] != 'js' &&
                    $file_path_info['extension'] != 'css' ) {
                return false;
            }

            $__FILE__ = str_replace( '\\', '/', (string) $__FILE__ );
            $docroot = self::getDocRoot();
            if ( false !== strpos( $__FILE__, $docroot ) ) {
                $this_script = str_replace( $docroot, '', $__FILE__ );
            } else {
                $map = self::getUSymlink( /* $_SERVER['SCRIPT_NAME'], $_SERVER['SCRIPT_FILENAME'] */ );
                if ( !empty( $map ) && false !== strpos( $__FILE__, $map[1] ) ) {
                    $this_script = str_replace( $map[1], $map[0], $__FILE__ );
                } else {
                    // finally here
                    return $__FILE__;
                }
            }
            $requested_script = str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] );

            if ( self::getOS()->isWIN ) {
                // windows is case-insensitive, so iron-out any case-sensitivity-only differences
                $requested_script = strtolower( $requested_script );
                $this_script = strtolower( $this_script );
            }

            return (bool) ($requested_script == $this_script);
        }

        /**
         * Fix $_SERVER variables for various setups.
         *
         * @access private
         * @since 3.0.0
         */
        public static function fixServerVars() {
            global $PHP_SELF;
            static $fixedvars = false;

            if ( $fixedvars )
                return;

            $default_server_values = array(
                'SERVER_SOFTWARE' => '',
                'REQUEST_URI' => '',
            );

            $_SERVER = array_merge( $default_server_values, $_SERVER );

            // Fix for IIS when running with PHP ISAPI
            if ( empty( $_SERVER['REQUEST_URI'] ) || ( php_sapi_name() != 'cgi-fcgi' && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) ) ) {

                // IIS Mod-Rewrite
                if ( isset( $_SERVER['HTTP_X_ORIGINAL_URL'] ) ) {
                    $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
                }
                // IIS Isapi_Rewrite
                else if ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) {
                    $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
                } else {
                    // Use ORIG_PATH_INFO if there is no PATH_INFO
                    if ( !isset( $_SERVER['PATH_INFO'] ) && isset( $_SERVER['ORIG_PATH_INFO'] ) )
                        $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];

                    // Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
                    if ( isset( $_SERVER['PATH_INFO'] ) ) {
                        if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] )
                            $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
                        else
                            $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
                    }

                    // Append the query string if it exists and isn't null
                    if ( !empty( $_SERVER['QUERY_STRING'] ) ) {

                        $query_string = array();
                        wp_parse_str( $_SERVER['QUERY_STRING'], $query_string );
                        $query_string = array_map('sanitize_text_field', $query_string);
                        $_SERVER['QUERY_STRING'] = http_build_query($query_string);

                        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
                    }
                }
            }

            //make REQUEST_URI safe
            $_SERVER['REQUEST_URI'] = esc_url_raw( $_SERVER['REQUEST_URI'] );

            // Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
            if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && ( strpos( $_SERVER['SCRIPT_FILENAME'], 'php.cgi' ) == strlen( $_SERVER['SCRIPT_FILENAME'] ) - 7 ) )
                $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];

            // Fix for Dreamhost and other PHP as CGI hosts
            if ( strpos( $_SERVER['SCRIPT_NAME'], 'php.cgi' ) !== false )
                unset( $_SERVER['PATH_INFO'] );

            // Fix empty PHP_SELF
            $PHP_SELF = $_SERVER['PHP_SELF'];
            if ( empty( $PHP_SELF ) )
                $_SERVER['PHP_SELF'] = $PHP_SELF = preg_replace( '/(\?.*)?$/', '', $_SERVER["REQUEST_URI"] );

            $fixedvars = true;
        }

        // http://stackoverflow.com/questions/4049856/replace-phps-realpath
        public static function truepath($path) {
            if ( function_exists( 'realpath' ) )
                return realpath( $path );

            // whether $path is unix or not
            $unipath = strlen( $path ) == 0 || $path[0] != '/';

            // attempts to detect if path is relative in which case, add cwd
            if ( strpos( $path, ':' ) === false && $unipath )
                $path = getcwd() . DIRECTORY_SEPARATOR . $path;

            // resolve path parts (single dot, double dot and double delimiters)
            $path = str_replace( array('/', '\\'), DIRECTORY_SEPARATOR, $path );
            $parts = array_filter( explode( DIRECTORY_SEPARATOR, $path ), 'strlen' );

            $absolutes = array();
            foreach ( $parts as $part ) {
                if ( '.' == $part )
                    continue;
                if ( '..' == $part )
                    array_pop( $absolutes );
                else
                    $absolutes[] = $part;
            }
            $path = implode( DIRECTORY_SEPARATOR, $absolutes );

            // resolve any symlinks
            if ( file_exists( $path ) && linkinfo( $path ) > 0 )
                $path = readlink( $path );

            // put initial separator that could have been lost
            $path = !$unipath ? '/' . $path : $path;

            return $path;
        }

        // http://gr2.php.net/php_uname
        // http://stackoverflow.com/questions/5879043/php-script-detect-whether-running-under-linux-or-windows
        // http://stackoverflow.com/questions/1482260/how-to-get-the-os-on-which-php-is-running
        public static function getOS() {
            return (object) array(
                        'isNIX' => (bool) ('/' == DIRECTORY_SEPARATOR && ':' == PATH_SEPARATOR),
                        'isMAC' => (bool) ('/' == DIRECTORY_SEPARATOR && ':' == PATH_SEPARATOR),
                        'isWIN' => (bool) ('\\' == DIRECTORY_SEPARATOR && ';' == PATH_SEPARATOR)
            );
        }

        // http://www.helicron.net/php-document-root/
        // http://php.net/manual/en/reserved.variables.server.php
        // http://php.net/manual/en/function.realpath.php
        // http://stackoverflow.com/questions/9151949/root-directory-with-php-on-apache-and-iis
        // variation used here
        public static function getDocRoot() {
            static $docroot = null;

            if ( !$docroot ) {
                self::fixServerVars();

                // not available in IIS
                if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
                    $docroot = $_SERVER['DOCUMENT_ROOT'];
                } else {
                    // for IIS
                    // these should always be available, Apache, IIS, .., PHP 4.1.0+, ..
                    if ( !empty( $_SERVER['SCRIPT_FILENAME'] ) ) {
                        //$docroot = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
                        $docroot = str_replace( '\\', '/', self::str_before( $_SERVER['SCRIPT_FILENAME'], $_SERVER['SCRIPT_NAME'] ) );
                    } elseif ( !empty( $_SERVER['PATH_TRANSLATED'] ) ) {
                        //$docroot = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
                        $docroot = str_replace( '\\', '/', self::str_before( str_replace( '\\\\', '\\', $_SERVER['PATH_TRANSLATED'] ), $_SERVER['SCRIPT_NAME'] ) );
                    } else
                        $docroot = '';
                    //$docroot=str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $docroot);
                }
            }

            $docroot = self::unslashit( $docroot );
            return $docroot;
        }

        public static function getHostUrl($trailing_slash = false) {
            static $url = null;

            if ( !$url ) {
                // try to determine the url manually
                // as robustly as possile
                self::fixServerVars();

                $url = 'http';

                if ( isset( $_SERVER["HTTPS"] ) && "on" == $_SERVER["HTTPS"] )
                    $url .= "s";

                $url .= "://" . $_SERVER['HTTP_HOST']/* $_SERVER["SERVER_NAME"] */;

                if ( !empty( $_SERVER["SERVER_PORT"] ) && "80" != $_SERVER["SERVER_PORT"] ) {
                    $url = parse_url( $url );
                    $url = $url['scheme'] . '://' . $url['host'] . ':' . (empty( $url['port'] ) ? $_SERVER["SERVER_PORT"] : $url['port']);
                }

                $url = self::unslashit( $url );
            }

            if ( $trailing_slash )
                return self::slashit( $url );
            return $url;
        }

        private static function getCommonPath($p1, $p2) {
            return implode( '/', array_intersect( explode( '/', $p1 ), explode( '/', $p2 ) ) );
        }

        private static function slashit($s) {
            return self::unslashit( $s ) . '/';
        }

        private static function unslashit($s) {
            return rtrim( $s, '/' );
        }

        public static function getUSymlink() {
            self::fixServerVars();

            // these should always be available, Apache, IIS, .., PHP 4.1.0+, ..
            $script_name = $_SERVER['SCRIPT_NAME'];
            $local = str_replace( '\\', '/', $script_name );
            if ( false !== strpos( $local, '~' ) && isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
                $script_filename = $_SERVER['SCRIPT_FILENAME'];
                $file = str_replace( '\\', '/', /* self::truepath( */ $script_filename/* ) */ );
                $common = self::getCommonPath( $local, $file );
                $usymlink = self::str_before( $local, $common );
                $uabslink = self::str_before( $file, $common );
                $map = array($usymlink, $uabslink);
            } else {
                $map = array();
            }
            // get request, remove query string
            //$request=self::str_before($request_uri, '?');

            return $map;
        }

        // http://stackoverflow.com/questions/176712/how-can-i-find-an-applications-base-url
        // http://stackoverflow.com/questions/5493075/apache-rewrite-get-original-url-in-php
        // variation used here
        public static function getBaseUrl($trailing_slash = false) {
            self::fixServerVars();

            // these should always be available, Apache, IIS, .., PHP 4.1.0+, ..
            $local = str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] );
            // get request, remove query string
            $request = self::str_before( $_SERVER['REQUEST_URI'], '?' );

            // no need to check for complete rewrited urls (with eg multisite subfolder and rewritten request url)
            // since when called the file is determined and base url can be found (with any virtual path also)

            $url = self::getHostUrl() . self::str_before( $request, $local );

            if ( $trailing_slash )
                $url = self::slashit( $url );
            else
                $url = self::unslashit( $url );

            return $url;
        }

        public static function getFileUrl($__FILE__ = null) {
            self::fixServerVars();

            if ( !$__FILE__ )
                $__FILE__ = (string) __FILE__;

            $__FILE__ = str_replace( '\\', '/', dirname( $__FILE__ ) );
            $docroot = self::getDocRoot();

            $url = '';
            if ( false !== strpos( $__FILE__, $docroot ) ) {
                $url = self::str_after( $__FILE__, $docroot );
            } else {
                $map = self::getUSymlink( /* $_SERVER['SCRIPT_NAME'], $_SERVER['SCRIPT_FILENAME'] */ );
                if ( !empty( $map ) && false !== strpos( $__FILE__, $map[1] ) ) {
                    $url = str_replace( $map[1], $map[0], $__FILE__ );
                } else {
                    // finally here
                    $url = $__FILE__;
                }
            }

            return self::getBaseUrl( substr( $url, 0, 1 ) != '/' ) . $url;
        }

        public static function getCurrentUrl($q = true) {
            self::fixServerVars();

            $server_request_uri = esc_url_raw( $_SERVER['REQUEST_URI'] );
            if ( !$q )
                return $server_request_uri;

            return self::getHostUrl() . $server_request_uri;
        }

        private static function str_before($h, $n, $s = 0) {
            $pos = strpos( $h, $n );
            return (false !== $pos) ? substr( $h, $s, $pos ) : $h;
        }

        private static function str_after($h, $n) {
            $pos = strpos( $h, $n );
            return (false !== $pos) ? substr( $h, $pos + strlen( $n ) ) : $h;
        }

        // utility function
        public static function merge() {
            if ( func_num_args() < 1 )
                return;

            $arrays = func_get_args();
            $merged = array_shift( $arrays );

            $isTargetObject = false;
            if ( is_object( $merged ) ) {
                $isTargetObject = true;
                $merged = (array) $merged;
            }

            foreach ( $arrays as $arr ) {
                $isObject = false;
                if ( is_object( $arr ) ) {
                    $isObject = true;
                    $arr = (array) $arr;
                }

                foreach ( $arr as $key => $val ) {
                    if ( array_key_exists( $key, $merged ) && (is_array( $val ) || is_object( $val )) ) {
                        $merged[$key] = self::merge( $merged[$key], $arr[$key] );
                        if ( is_object( $val ) )
                            $merged[$key] = (object) $merged[$key];
                    } else
                        $merged[$key] = $val;
                }
            }
            if ( $isTargetObject ) {
                $isTargetObject = false;
                $merged = (object) $merged;
            }
            return $merged;
        }

        // check if a dependency is registered
        public static function has( $type = 'dependency', $dep = '' ) {
            if ( 'dependency' == $type ) {
                $r = self::$__dependencies__;
                foreach ( explode( '/', $dep ) as $d ) {
                    if ( !is_array( $r ) || !isset( $r[$d] ) )
                        return false;
                    $r = $r[$d];  // walk
                }
                return true;
            }
            elseif ( 'asset' == $type ) {
                $r = self::$__assets__;
                foreach ( explode( '/', $dep ) as $d ) {
                    if ( !is_array( $r ) || !isset( $r[$d] ) )
                        return false;
                    $r = $r[$d];  // walk
                }
                return true;
            }
            return false;
        }

        // add dependencies manually
        public static function add($type = 'dependencies', $deps = array()) {
            if ( 'dependencies' == $type ) {
                self::$__dependencies__ = self::merge( self::$__dependencies__, $deps );
            } elseif ( 'assets' == $type ) {
                self::$__assets__ = self::merge( self::$__assets__, $deps );
            }
        }

        // allow plugins to autoload their deps using the loader (eg Toolset Forms Commerce)
        public static function doAutoLoad() {
            // allow deps for plugins loaded before cred
            $extra_deps = apply_filters( 'cred_loader_dependencies', array() );
            if ( !empty( $extra_deps ) )
                self::$__dependencies__ = self::merge( self::$__dependencies__, $extra_deps );

            // allow deps for plugins loaded before cred
            $extra_assets = apply_filters( 'cred_loader_assets', array() );
            if ( !empty( $extra_assets ) )
                self::$__assets__ = self::merge( self::$__assets__, $extra_assets );

            // auto load dependencies that have registered and need to auto load
            do_action( 'cred_loader_auto_load' );
        }

        private static function get_file($path) {

            if ( function_exists( 'realpath' ) )
                $path = realpath( $path );

            if ( !$path || !@is_file( $path ) )
                return '';

            return @file_get_contents( $path );
        }

        private static function buildAbsPath($rel, $base) {
            // is relative path
            if ( 0 === strpos( $rel, '.' ) ) {
                $parts = explode( '/', str_replace( '\\', '/', $rel ) );
                foreach ( array_keys( $parts ) as $ii ) {
                    if ( '.' == $parts[$ii] ) {
                        unset( $parts[$ii] );
                    } elseif ( '..' == $parts[$ii] ) {
                        $base = dirname( $base );
                        unset( $parts[$ii] );
                    }
                }
                $parts = array_values( $parts );
                array_unshift( $parts, $base );
                $rel = implode( '/', $parts );
            }
            return $rel;
        }

        // perform same functionality like WP => load-scripts.php, load-styles.php scripts
        private static function loadAssets() {
            /**
             * Disable error reporting
             *
             * Set this to error_reporting( E_ALL ) or error_reporting( E_ALL | E_STRICT ) for debugging
             */
            $load = false;
            $type = false;
            $out = '';

            if ( isset( $_GET['js'] ) ) {
                $type = 'SCRIPT';
                $load = $_GET['js'];
            } elseif ( isset( $_GET['css'] ) ) {
                $type = 'STYLE';
                $load = $_GET['css'];
            }

            if ( $type && $load ) {
                //$load = urldecode($load);
                $load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
                $load = explode( ',', $load );

                if ( $load && !empty( $load ) ) {
                    foreach ( $load as $handle ) {
                        if ( !isset( self::$__assets__[$type][$handle] ) || !isset( self::$__assets__[$type][$handle]['src'] ) )
                            continue;

                        $content = self::get_file( self::$__assets__[$type][$handle]['src'] );
                        $path = dirname( self::$__assets__[$type][$handle]['path'] );

                        // handle (relative) urls in CSS
                        if ( 'STYLE' == $type && preg_match_all( '#url\s*\(([^\)]+?)\)#', $content, $m ) ) {
                            $matches = $m[1];
                            unset( $m );
                            foreach ( $matches as $match ) {
                                $rel = trim( trim( trim( $match ), '"' ), "'" );
                                $abs = self::buildAbsPath( $rel, $path );
                                $content = str_replace( $rel, $abs, $content );
                            }
                        }

                        $out .= '/* ' . $handle . ' */' . "\n" . $content . "\n\n\n";
                    }

                    $compress = ( isset( $_GET['c'] ) && $_GET['c'] );
                    $force_gzip = ( $compress && 'gzip' == $_GET['c'] );
                    $expires_offset = 31536000; // 1 year

                    if ( 'SCRIPT' == $type )
                        header( 'Content-Type: application/x-javascript; charset=UTF-8' );
                    elseif ( 'STYLE' == $type )
                        header( 'Content-Type: text/css' );

                    header( 'Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT' );
                    header( "Cache-Control: public, max-age=$expires_offset" );

                    if (
                            $compress &&
                            !ini_get( 'zlib.output_compression' ) &&
                            'ob_gzhandler' != ini_get( 'output_handler' ) &&
                            isset( $_SERVER['HTTP_ACCEPT_ENCODING'] )
                    ) {
                        header( 'Vary: Accept-Encoding' ); // Handle proxies
                        if ( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate' ) && function_exists( 'gzdeflate' ) && !$force_gzip ) {
                            header( 'Content-Encoding: deflate' );
                            $out = gzdeflate( $out, 3 );
                        } elseif ( false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) && function_exists( 'gzencode' ) ) {
                            header( 'Content-Encoding: gzip' );
                            $out = gzencode( $out, 3 );
                        }
                    }

                    echo $out;
                }
            }
            exit;
        }

        private static function getAssetDeps($type, $class) {
            static $depth = 0;

            $assets = array();
            $deps = array();

            if ( isset( self::$__assets__[$type][$class] ) && isset( self::$__assets__[$type][$class]['dependencies'] ) ) {
                foreach ( self::$__assets__[$type][$class]['dependencies'] as $_dep ) {
                    if ( isset( self::$__assets__[$type][$_dep] ) ) {
                        if ( !isset( $assets[$_dep] ) ) {
                            $depth++;
                            list($assets2, $deps2) = self::getAssetDeps( $type, $_dep );
                            $depth--;

                            $assets = array_merge( $assets, array_diff_key( $assets2, $assets ) );
                            $deps = array_merge( $deps, array_diff_key( $deps2, $deps ) );

                            $assets[$_dep] = 1;
                        }
                    } elseif ( !isset( $deps[$_dep] ) ) {
                        $deps[$_dep] = 1;
                    }
                }
            }

            if ( 0 == $depth ) {
                // add the required asset last, after all dependencies
                $assets[$class] = 1;
            }
            return array($assets, $deps);
        }

        // load an asset with dependencies
        public static function loadAsset($qclass, $registerAs = false, $enqueueIt = false, $concat = false, $nocache = false) {
            list($type, $class) = explode( '/', $qclass, 2 );

            if (
                    isset( self::$__assets__[$type] ) &&
                    isset( self::$__assets__[$type][$class] )
            ) {
                $_type = &self::$__assets__[$type];
                $_class = &$_type[$class];

                if ( $concat ) {
                    $this_url = $_class['loader_url'];
                    list($assets, $deps) = self::getAssetDeps( $type, $class );
                    $assets = array_keys( $assets );
                    $deps = array_keys( $deps );
                } else {
                    if ( is_array( $_class['dependencies'] ) && !empty( $_class['dependencies'] ) ) {
                        foreach ( $_class['dependencies'] as $_dep ) {
                            if ( isset( self::$__assets__[$type][$_dep] ) ) {
                                // recursively register dependencies
                                self::loadAsset( "$type/$_dep", false, false, false );
                            }
                        }
                    }
                }

                $registerAs = ($registerAs) ? $registerAs : $class;
                if ( 'SCRIPT' == $type && isset( $_class['path'] ) ) {
                    $isFooter = isset( $_class['footer'] ) ? $_class['footer'] : false;

                    if ( $concat ) {
                        $uri = $this_url . '?js=' . /* urlencode( */implode( ',', $assets )/* ) */;
                        if ( $nocache )
                            $uri .= '&_nocache=' . time();
                        $depends = $deps;
                    }
                    else {
                        $uri = $_class['path'];
                        $depends = $_class['dependencies'];
                    }

                    wp_register_script( $registerAs, $uri, $depends, $_class['version'], $isFooter );
                    if ( $enqueueIt )
                        wp_enqueue_script( $registerAs );
                }
                elseif ( 'STYLE' == $type && isset( $_class['path'] ) ) {
                    if ( $concat ) {
                        $uri = $this_url . '?css=' . /* urlencode( */implode( ',', $assets )/* ) */;
                        if ( $nocache )
                            $uri .= '&_nocache=' . time();
                        $depends = $deps;
                    }
                    else {
                        $uri = $_class['path'];
                        $depends = $_class['dependencies'];
                    }

                    wp_register_style( $registerAs, $uri, $depends, $_class['version'] );
                    if ( $enqueueIt )
                        wp_enqueue_style( $registerAs );
                }
            }
        }

        // include a php file
        private static function getFile($path, $_in = 'require_once') {
            if ( !is_file( $path ) ) {
                printf( 'File "%s" doesn\'t exist!', $path );
                return false;
            }

            switch ($_in) {
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
        private static function getClass($class, $path, $_in = 'require_once') {
            if ( !class_exists( $class, false ) )
                self::getFile( $path, $_in );
        }

        // load a class with dependencies if needed
        public static function load($qclass) {
            list($type, $class) = explode( '/', $qclass, 2 );


            // try to optimize a little bit
            if ( isset( self::$__loaded_dependencies__[$qclass] ) ) {
                $is_loaded = true;
            } else {
                $is_loaded = false;
                self::$__loaded_dependencies__[$qclass] = 1;
            }

            if (
                    isset( self::$__dependencies__[$type] ) &&
                    isset( self::$__dependencies__[$type][$class] )
            ) {
                $_type = &self::$__dependencies__[$type];
                $_class = &$_type[$class];
                if ( !$is_loaded ) {
                    if ( isset( $_type['%%PARENT%%'] ) && is_array( $_type['%%PARENT%%'] ) ) {
                        $_parent = &$_type['%%PARENT%%'];
                        foreach ( $_parent as $_dep ) {
                            if ( isset( $_dep['class'] ) )
                                self::getClass( $_dep['class'], $_dep['path'] );
                            else
                                self::getFile( $_dep['path'] );
                        }
                    }
                    foreach ( $_class as $_dep ) {
                        if ( isset( $_dep['class'] ) )
                            self::getClass( $_dep['class'], $_dep['path'] );
                        else
                            self::getFile( $_dep['path'] );
                    }
                }
                $class = end( $_class );
                if ( isset( $class['class'] ) )
                    $class = $class['class'];
            }
            elseif ( !$is_loaded ) {
                self::getFile( $qclass );
                $class = $qclass;
            }
            return array(false, $class);
        }

        // singleton factory pattern, to enable singleton in php 5.2, etc..
        // http://stackoverflow.com/questions/7902586/extend-a-singleton-with-php-5-2-x
        // http://stackoverflow.com/questions/7987313/how-to-subclass-a-singleton-in-php
        // use tags (interfaces) to denote singletons, and then use "singleton factory"
        // http://phpgoodness.wordpress.com/2010/07/21/singleton-and-multiton-with-a-different-approach/
        // ability to pass parameters, and to avoid the singleton flag
        public static function get($qclass) {
            // if instance is in pool, return it immediately, only singletons are in the pool
            if ( isset( self::$__singleton_instances__[$qclass] ) )
                return self::$__singleton_instances__[$qclass];

            $instance = null;

            // load it if needed
            list($type, $class) = self::load( $qclass );
            // make sure it is loaded (exists) and is not interface
            // make sure it is not an interface (PHP 5)
            //if ( !$reflection->isInterface() )
            if ( class_exists( $class, false ) && !interface_exists( $class, false ) ) {
				// Parameters to call constructor (in case) and multiton's getFace (in case) with.
				// phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
                $args = func_get_args();
                array_shift( $args );

                if ( empty( $args ) ) {  // (PHP 5)
                    // If the object doesn't have arguments to be passed, we just instantiate it.
                    // It might be quicker to use - new $class_name -.
                    $instance = new $class(); //$reflection->newInstance(); // PHP 5
                } else {
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

                if ( $instance ) {
                    // If it's a singleton, we have to keep track of it. (PHP 5)
                    // If might be quicker to use - $new_instance instanceof Singleton -.
                    if ( $instance instanceof CRED_Singleton /* $reflection->isSubclassOf( 'CRED_Singleton' ) */ ) {
                        self::$__singleton_instances__[$qclass] = $instance;
                    }
                    return $instance;
                }
            }
            // sth failed, no instance, return null here finally
            return null;
        }

        // USE WP Object_Cache API to cache templates (so 3rd-party cache plugins can be used also)
        public static function tpl($template, array $args = array(), $cache = false) {
            if (
                    isset( self::$__dependencies__['TEMPLATE'] ) &&
                    isset( self::$__dependencies__['TEMPLATE'][$template] ) &&
                    isset( self::$__dependencies__['TEMPLATE'][$template]['path'] )
            )
                $template_path = self::$__dependencies__['TEMPLATE'][$template]['path'];
            else
                return '';

            // NEW use caching of templates
            $output = false;
            if ( $cache ) {
                $group = '__' . __CLASS__ . '_CACHE__';
                $key = md5( serialize( array($template_path, $args) ) );
                $output = wp_cache_get( $key, $group );
            }

            if ( false === $output ) {
                if ( !$template_path || !is_file( $template_path ) ) {
                    printf( 'File "%s" doesn\'t exist!', $template_path );
                    return '';
                }
                $output = self::getTpl( $template_path, $args );
                if ( $cache )
                    wp_cache_set( $key, $output, $group/* , $expire */ );
            }
            return $output;
        }

        private static function getTpl($______templatepath________, array $______args______ = array()) {
            ob_start();
            if ( !empty( $______args______ ) )
                extract( $______args______ );
            include($______templatepath________);
            return ob_get_clean();
        }

        public static function getVar($______path_______) {
            include $______path_______;
            unset( $______path_______ );

            $_______vars________ = get_defined_vars();

            // return first defined var (only one)
            if ( !empty( $_______vars________ ) )
                return reset( $_______vars________ );

            return null;
        }

        /**
         * Do form editor metaboxes.
         *
         * @param $screen
         * @param $context
         * @param $object
         * @return int
         *
         * @deprecated 2.1.2
         */
        public static function do_meta_boxes($screen, $context, $object) {
            //global $wp_meta_boxes;
            static $already_sorted = false;

            if ( empty( $screen ) )
                $screen = get_current_screen();
            elseif ( is_string( $screen ) )
                $screen = convert_to_screen( $screen );

            $page = $screen->id;
            $user_page = 'cred-form';

            $hidden = get_hidden_meta_boxes( $screen );

            printf( '<div id="%s-sortables" class="meta-box-sortables">', esc_html( $context ) );

            $i = 0;
            do {
                // Grab the ones the user has manually sorted. Pull them out of their previous context/priority and into the one the user chose
                if ( !$already_sorted && $sorted = get_user_option( "meta-box-order_$page" ) ) {
                    foreach ( $sorted as $box_context => $ids ) {
                        foreach ( explode( ',', $ids ) as $id ) {
                            if ( $id && 'dashboard_browser_nag' !== $id )
                                self::add_meta_box( $id, null, null, $screen, $box_context, 'sorted' );
                        }
                    }
                }
                $already_sorted = true;

                if ( !isset( self::$metaboxes ) || !isset( self::$metaboxes[$page] ) || !isset( self::$metaboxes[$page][$context] ) )
                    break;

                foreach ( array('high', 'sorted', 'core', 'default', 'low') as $priority ) {
                    if ( isset( self::$metaboxes[$page][$context][$priority] ) ) {
                        foreach ( (array) self::$metaboxes[$page][$context][$priority] as $box ) {
                            if ( false == $box || !$box['title'] )
                                continue;
                            $i++;
                            $style = '';
                            $hidden_class = in_array( $box['id'], $hidden ) ? ' hide-if-js' : '';
                            echo '<div id="' . $box['id'] . '" class="postbox ' . 'cred_related' /* postbox_classes($box['id'], $page) */ . $hidden_class . '" ' . '>' . "\n";
                            if ( 'dashboard_browser_nag' != $box['id'] )
                                echo '<div class="handlediv" title="' . esc_attr__( 'Click to toggle' ) . '"><br /></div>';
                            echo "<h3 class='hndle'><span>{$box['title']}</span></h3>\n";
                            echo '<div class="inside">' . "\n";
                            call_user_func( $box['callback'], $object, $box );
                            echo "</div>\n";
                            echo "</div>\n";
                        }
                    }
                }
            } while (0);

            echo "</div>";

            return $i;
        }

        // DEPRECATED
        public static function add_meta_box($id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null) {
            //global $wp_meta_boxes;

            if ( empty( $screen ) )
                $screen = get_current_screen();
            elseif ( is_string( $screen ) )
                $screen = convert_to_screen( $screen );

            $page = $screen->id;

            if ( !isset( self::$metaboxes ) )
                self::$metaboxes = array();
            if ( !isset( self::$metaboxes[$page] ) )
                self::$metaboxes[$page] = array();
            if ( !isset( self::$metaboxes[$page][$context] ) )
                self::$metaboxes[$page][$context] = array();

            foreach ( array_keys( self::$metaboxes[$page] ) as $a_context ) {
                foreach ( array('high', 'core', 'default', 'low') as $a_priority ) {
                    if ( !isset( self::$metaboxes[$page][$a_context][$a_priority][$id] ) )
                        continue;

                    // If a core box was previously added or removed by a plugin, don't add.
                    if ( 'core' == $priority ) {
                        // If core box previously deleted, don't add
                        if ( false === self::$metaboxes[$page][$a_context][$a_priority][$id] )
                            return;
                        // If box was added with default priority, give it core priority to maintain sort order
                        if ( 'default' == $a_priority ) {
                            self::$metaboxes[$page][$a_context]['core'][$id] = self::$metaboxes[$page][$a_context]['default'][$id];
                            unset( self::$metaboxes[$page][$a_context]['default'][$id] );
                        }
                        return;
                    }
                    // If no priority given and id already present, use existing priority
                    if ( empty( $priority ) ) {
                        $priority = $a_priority;
                        // else if we're adding to the sorted priority, we don't know the title or callback. Grab them from the previously added context/priority.
                    } elseif ( 'sorted' == $priority ) {
                        $title = self::$metaboxes[$page][$a_context][$a_priority][$id]['title'];
                        $callback = self::$metaboxes[$page][$a_context][$a_priority][$id]['callback'];
                        $callback_args = self::$metaboxes[$page][$a_context][$a_priority][$id]['args'];
                    }
                    // An id can be in only one priority and one context
                    if ( $priority != $a_priority || $context != $a_context )
                        unset( self::$metaboxes[$page][$a_context][$a_priority][$id] );
                }
            }

            if ( empty( $priority ) )
                $priority = 'low';

            if ( !isset( self::$metaboxes[$page][$context][$priority] ) )
                self::$metaboxes[$page][$context][$priority] = array();

            self::$metaboxes[$page][$context][$priority][$id] = array('id' => $id, 'title' => $title, 'callback' => $callback, 'args' => $callback_args);
        }

        public static function remove_meta_box($id, $screen, $context) {
            //global $wp_meta_boxes;

            if ( empty( $screen ) )
                $screen = get_current_screen();
            elseif ( is_string( $screen ) )
                $screen = convert_to_screen( $screen );

            $page = $screen->id;

            if ( !isset( self::$metaboxes ) )
                self::$metaboxes = array();
            if ( !isset( self::$metaboxes[$page] ) )
                self::$metaboxes[$page] = array();
            if ( !isset( self::$metaboxes[$page][$context] ) )
                self::$metaboxes[$page][$context] = array();

            foreach ( array('high', 'core', 'default', 'low') as $priority )
                self::$metaboxes[$page][$context][$priority][$id] = false;
        }

    }

// init on load
//CRED_Loader::init();
}
