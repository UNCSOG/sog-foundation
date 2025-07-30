<?php

class WPDDL_ModuleManagerSupport
{

    private static $instance;
    const LAYOUTS_MODULE_MANAGER_KEY = WPDDL_LAYOUTS_POST_TYPE;
    const LAYOUTS_MODULE_MANAGER_CSS_ID = 'CSS';
    const LAYOUTS_MODULE_MANAGER_JS_ID = 'JS';
    private $track_has_posts = array();

    private function __construct()
    {
        add_filter('wpmodules_register_sections', array(&$this, 'ddl_register_modules_sections'), 20, 1);

        add_filter('wpmodules_register_items_' . self::LAYOUTS_MODULE_MANAGER_KEY, array(&$this, 'register_modules_layouts_items'), 10, 1);
        add_filter('wpmodules_export_items_' . self::LAYOUTS_MODULE_MANAGER_KEY, array(&$this, 'export_modules_layouts_items'), 10, 2);
        add_filter('wpmodules_import_items_' . self::LAYOUTS_MODULE_MANAGER_KEY, array(&$this, 'import_modules_layouts_items'), 10, 4);
        add_filter('wpmodules_items_check_' . self::LAYOUTS_MODULE_MANAGER_KEY, array(&$this, 'modules_layouts_exist'), 10, 1);
        add_filter('wpmodules_export_pluginversions_' . self::LAYOUTS_MODULE_MANAGER_KEY, array(&$this, 'plugin_version'));
        add_filter('wpmodules_import_pluginversions_' . self::LAYOUTS_MODULE_MANAGER_KEY, array(&$this, 'plugin_version'));
    }

    function ddl_register_modules_sections($sections)
    {
        $sections[self::LAYOUTS_MODULE_MANAGER_KEY] = array(
            'title' => __('Layouts', 'ddl-layouts'),
            'icon' => WPDDL_RES_RELPATH . '/images/layouts-icon-color_12X12.png',
            'icon_css' => 'icon-layouts-logo ont-icon-16 ont-color-orange'
        );

        return $sections;
    }

    function plugin_version()
    {
        return WPDDL_VERSION;
    }

    public function register_modules_layouts_items($arg)
    {
        $ret = array();
        $layouts = WPDD_Layouts_Cache_Singleton::get_published_layouts();
        foreach ($layouts as $layout) {
            $ret[] = array(
                'id' => self::LAYOUTS_MODULE_MANAGER_KEY.$layout->ID,
                'title' => $layout->post_title,
                'details' => sprintf(__("Fetch layout %s", 'ddl-layouts'), $layout->post_title)
            );
        }

        usort( $ret, array ( new Toolset_ArrayUtils('title'), 'sort_string_ascendant' ) );

        $ret[] = array(
            'id' => self::LAYOUTS_MODULE_MANAGER_KEY.self::LAYOUTS_MODULE_MANAGER_CSS_ID,
            'title' => __("Layouts CSS", 'ddl-layouts'),
            'details' => __("The Layouts CSS you can export as layouts.css file", 'ddl-layouts')
        );

        $ret[] = array(
            'id' => self::LAYOUTS_MODULE_MANAGER_KEY.self::LAYOUTS_MODULE_MANAGER_JS_ID,
            'title' => __("Layouts JS", 'ddl-layouts'),
            'details' => __("The Layouts JS you can export as layouts.js file", 'ddl-layouts')
        );

        return $ret;
    }

    function export_modules_layouts_items($res, $items)
    {
        $export = $this->get_items_to_export($items);
        $hashes = $export->hash;

        foreach ($items as $jj => $item) {
            /**
             * just to be 100% safe
             */
            $item['id'] = self::LAYOUTS_MODULE_MANAGER_KEY.$this->real_id( $item );
            $item['hash'] = $hashes[$this->real_id( $item )];
            $items[$jj] = $item;
        }

        return array('array' => $export->json, 'items' => $items);
    }

    /**
     * waiting for export messages implementation on MM side
     */
    function handle_message_for_posts_belong( ){

        $true = array_filter( $this->track_has_posts, array( new Toolset_ArrayUtils( null, true ), 'value_in_array' ) );

        if( count($true) === 0 ) return;

        $html = '';
        foreach( $this->track_has_posts as $layout => $bool ){
            if( $bool === true ){
                $html .= __('The %s layout you are exporting is used to display single pages. Please note that when you import this module on another site, the layout will not be assigned to specific pages, so you will need to edit the layout and assign it manually.', 'ddl-layouts');
            }
        }

        return WPDDL_Messages::dismissible_notice( 'ddl-layouts-has-posts-notice',  $html);

    }

    function import_modules_layouts_items( $result, $path, $items_key, $items )
    {
        /**EMERSON: We want Layouts that are not selected to be NOT imported*/
        /**START*/
        if ((is_array($items)) && (is_array($items_key))) {
            foreach ($items as $k=>$v) {
                if (isset($v['id'])) {
                    $layout_id_import=$v['id'];
                    if (!(in_array($layout_id_import,$items_key))) {
                        //Not selected, removed
                        unset($items[$k]);
                    }
                }
            }
            $items=array_values($items);

        }
        /**END*/

        $result = $this->import_module_data( $path, $items );

        if ( is_wp_error( $result ) ) {
            return $result->get_error_message( $result->get_error_code() );
        }

        return $result;
    }

    function import_module_data( $path, $items ){
        global $wpddlayout_theme;

        /**EMERSON: Add module items parameter */
        /**START*/
        $res = $wpddlayout_theme->import_layouts_and_css_from_dir( $path, true, false, true,$items );
        /**END*/

        if ( null === $res ) {
            return new WP_Error( 'import_failure', sprintf( __( 'No data where imported.', 'ddl-layouts' ) ) );
        }

        $import_data = $wpddlayout_theme->get_import_data();

        if( isset( $import_data['items'] ) ){
            foreach( $import_data['items'] as $key => $val ){
                $old_element = array_filter( $items, array( new Toolset_ArrayUtils('title', $key ), 'filter_array' ) );
                $old_element = array_values( $old_element );
                $import_data['items'][$old_element[0]['id']] = self::LAYOUTS_MODULE_MANAGER_KEY.$val;
                unset( $import_data['items'][$key] );
            }
            if( isset($import_data[self::LAYOUTS_MODULE_MANAGER_CSS_ID]) && $import_data[self::LAYOUTS_MODULE_MANAGER_CSS_ID] ){
                $import_data['items'][self::LAYOUTS_MODULE_MANAGER_KEY.self::LAYOUTS_MODULE_MANAGER_CSS_ID] = self::LAYOUTS_MODULE_MANAGER_KEY.self::LAYOUTS_MODULE_MANAGER_CSS_ID;
            }
            if( isset($import_data[self::LAYOUTS_MODULE_MANAGER_JS_ID]) && $import_data[self::LAYOUTS_MODULE_MANAGER_JS_ID] ){
                $import_data['items'][self::LAYOUTS_MODULE_MANAGER_KEY.self::LAYOUTS_MODULE_MANAGER_JS_ID] = self::LAYOUTS_MODULE_MANAGER_KEY.self::LAYOUTS_MODULE_MANAGER_JS_ID;
            }
        }

        unset( $import_data[WPDDL_ModuleManagerSupport::LAYOUTS_MODULE_MANAGER_JS_ID] );
        unset( $import_data[WPDDL_ModuleManagerSupport::LAYOUTS_MODULE_MANAGER_CSS_ID] );
        return $import_data;
    }


    function modules_layouts_exist($items)
    {
        foreach ($items as $key => $item) {
            $id = $this->real_id( $item );
            if ($id !== self::LAYOUTS_MODULE_MANAGER_CSS_ID && $id !== self::LAYOUTS_MODULE_MANAGER_JS_ID) {
                $layouts = get_posts(
                    array(
                        'post_type'              => WPDDL_LAYOUTS_POST_TYPE,
                        'title'                  => $item['title'],
                        'post_status'            => 'all',
                        'numberposts'            => 1,
                        'update_post_term_cache' => false,
                        'update_post_meta_cache' => false,
                        'orderby'                => 'post_date ID',
                        'order'                  => 'ASC',
                    )
                );
                if ( ! empty ( $layouts ) ) {
                    $layout = $layouts[0];
                    $items[$key]['exists'] = true;
                    if (isset($item['hash'])) {

                        $hash = $this->computeLayoutHashForLayout($layout);

                        if ($hash && $item['hash'] != $hash){
                            $items[$key]['is_different'] = true;
                        }
                        else{
                            $items[$key]['is_different'] = false;
                        }

                    }
                } else {
                    $items[$key]['exists'] = false;
                }
            } else {

                if($id === self::LAYOUTS_MODULE_MANAGER_CSS_ID ){

                    global $wpddlayout_theme;
                    $css_string = $wpddlayout_theme->get_layout_css();

                    $items[$key]['exists'] = true;

                    if (isset($item['hash'])) {

                       $hash = $this->get_layouts_css_hash($css_string);

                       if ($hash && $item['hash'] != $hash){
                          $items[$key]['is_different'] = true;
                       } else {
                          $items[$key]['is_different'] = false;
                       }

                    }
                }

                if($id === self::LAYOUTS_MODULE_MANAGER_JS_ID ){

                    global $wpddlayout_theme;
                    $js_string = $wpddlayout_theme->get_layout_js();

                    $items[$key]['exists'] = true;

                    if (isset($item['hash'])) {

                       $hash = $this->get_layouts_js_hash($js_string);

                       if ($hash && $item['hash'] != $hash){
                          $items[$key]['is_different'] = true;
                       } else {
                          $items[$key]['is_different'] = false;
                       }
                    }
                }

            }
        }
        return $items;
    }

    private function real_id( $item ){
        return str_replace(self::LAYOUTS_MODULE_MANAGER_KEY, '', $item['id']);
    }

    private function get_layouts_css_hash($css)
    {
        $hash_data = array();
        $hash_data['file_name'] = 'layouts.css';
        $hash_data['css_string'] = preg_replace('/\s+/', '', $css);
        return md5( serialize($hash_data) );
    }
    private function get_layouts_js_hash($css)
    {
        $hash_data = array();
        $hash_data['file_name'] = 'layouts.js';
        $hash_data['js_string'] = preg_replace('/\s+/', '', $css);
        return md5( serialize($hash_data) );
    }


    private function get_items_to_export($items)
    {
        global $wpddlayout_theme;
        $ret = array();
        $hash = array();
        $include_css = false;
        $include_js = false;

        foreach ($items as $item) {
            $id = $this->real_id( $item );
            if ($id !== self::LAYOUTS_MODULE_MANAGER_CSS_ID && $id !== self::LAYOUTS_MODULE_MANAGER_JS_ID) {
                $layout = get_post((int)$id);
                $settings = $wpddlayout_theme->build_export_data_from_post( $layout, array('post_types', 'archives', 'attachments') );
                $has_posts = isset( $settings['has_posts'] ) && $settings['has_posts'];
                unset( $settings['has_posts'] );
                $ret[] = $settings;
                $this->track_has_posts[$layout->post_title] = $has_posts;
                $hash[$layout->ID] = $this->do_hash( $layout, $settings);
            } else {
                if(($id === self::LAYOUTS_MODULE_MANAGER_CSS_ID)){
                $include_css = true;
            }
                if(($id === self::LAYOUTS_MODULE_MANAGER_JS_ID)){
                    $include_js = true;
                }
            }
        }

        $keys = array_map(array(new Toolset_ArrayUtils('file_name'), 'remap_by_property'), $ret);
        $values = array_map(array(new Toolset_ArrayUtils('file_data'), 'remap_by_property'), $ret);

        $json = array_combine(
            $keys,
            $values
        );

        if ($include_css) {
            $css = $wpddlayout_theme->get_layout_css();
            if ( is_string( $css ) ) {
                $json['layouts.css'] = $css;
                $hash_data = array();
                $hash_data['file_name'] = 'layouts.css';
                $hash_data['css_string'] = preg_replace('/\s+/', '', $css);
                $hash[self::LAYOUTS_MODULE_MANAGER_CSS_ID] = md5(serialize($hash_data));
            }

        }

        if ($include_js) {
            $js = $wpddlayout_theme->get_layout_js();
            if ( is_string( $js ) ) {
                $json['layouts.js'] = $js;
                $hash_data = array();
                $hash_data['file_name'] = 'layouts.js';
                $hash_data['js_string'] = preg_replace('/\s+/', '', $js);
                $hash[self::LAYOUTS_MODULE_MANAGER_JS_ID] = md5(serialize($hash_data));
            }

        }

        return (object)array('json' => $json, 'hash' => $hash);
    }

    private function computeLayoutHashForLayout($layout)
    {
        global $wpddlayout_theme;
        $settings = $wpddlayout_theme->build_export_data_from_post( $layout, array() );
        return $this->do_hash( $layout, $settings);
    }

    private function do_hash( $layout, $settings){
        $hash_data = array();
        $hash_data['post_name'] = $layout->post_name;
        $hash_data['post_title'] = $layout->post_title;
        $to_hash = json_decode( $settings['file_data'] );
        if( is_object($to_hash) && property_exists($to_hash, 'post_types') ){
            unset( $to_hash->post_types );
        }
        if( is_object($to_hash) && property_exists($to_hash, 'archives') ){
            unset( $to_hash->archives );
        }
        if( is_object($to_hash) && property_exists($to_hash, 'media') ){
            unset( $to_hash->media );
        }
        $to_hash = wp_json_encode( $to_hash );
        $to_hash = WPDDL_LayoutsResources::remove_url( $to_hash );
        $hash_data['settings'] = preg_replace( '/\s+/', '', $to_hash );
        return md5(serialize($hash_data));
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_ModuleManagerSupport();
        }

        return self::$instance;
    }
}
