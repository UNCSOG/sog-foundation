<?php

final class CRED_Router {

    private static $routes = array();

    public static function init() {
        
    }

    public static function addCalls($calls) {
        if (is_admin()) {
            foreach ($calls as $action => $data) {
                if (empty($data['callback']))
                    continue;

                add_action("wp_ajax_{$action}", $data['callback']);

                if (isset($data['nopriv']) && $data['nopriv'])
                    add_action("wp_ajax_nopriv_{$action}", $data['callback']);
            }
        }
    }

    public static function addRoutes($context, $routes) {
        if (!$context)
            return;

        if (!isset(self::$routes[$context]))
            self::$routes[$context] = array();

        foreach ($routes as $controller => $nopriv) {
            if (!isset(self::$routes[$context][$controller]))
                self::$routes[$context][$controller] = 0;

            if (is_admin() && !self::$routes[$context][$controller]) {
                self::$routes[$context][$controller] = 1;

                // controllers ajax hooks
                add_action("wp_ajax_{$context}_ajax_{$controller}", array(__CLASS__, 'controllerAction'));

                if ($nopriv)
                    add_action("wp_ajax_nopriv_{$context}_ajax_{$controller}", array(__CLASS__, 'controllerAction'));
            }
        }
    }

    public static function getRoute($context, $path = '', $params = null, $raw = true) {
        static $base_url = null;

        if (!empty($path) && $raw) {
            $parts = explode('/', str_replace('?', '&', ltrim($path, '/')));
            $path = '';
            foreach ($parts as $ii => $part) {
                if (0 == $ii)
                    $path.='?action=' . $context . '_ajax_' . $part;
                elseif (1 == $ii)
                    $path.='&_do_=' . $part;
                else
                    break;
            }
        }
        if ($path && !empty($path) && $params && !empty($params)) {
            foreach ($params as $p => $v) {
                if ($params[$p])
                    $path = esc_url(add_query_arg($p, $v, $path));
            }
        }
        if (null == $base_url)
            $base_url = admin_url('admin-ajax.php');

        return ($raw) ? $base_url . $path : $path;
    }

    public static function controllerAction() {
        if (!isset($_GET['action']))
            wp_die();

        $action = $_GET['action'];

        foreach (array_keys(self::$routes) as $context) {
            if (false !== strpos($action, "{$context}_ajax_")) {
                $controller = str_replace("{$context}_ajax_", '', $action);

                if (!isset(self::$routes[$context][$controller]) || !self::$routes[$context][$controller])
                    break;

                $controllerAction = isset($_GET['_do_']) ? $_GET['_do_'] : false;

                if (!$controllerAction)
                    break;

                $controllerObject = CRED_Loader::get("CONTROLLER/$controller");
                if (
                        $controllerObject &&
                        method_exists($controllerObject, $controllerAction) &&
                        is_callable(array($controllerObject, $controllerAction))
                )
                    call_user_func_array(array($controllerObject, $controllerAction), array($_GET, $_POST));

                break;
            }
        }
        wp_die();
    }

}
