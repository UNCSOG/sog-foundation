<?php
/**
 * MainClass
 *
 * Main class of the plugin
 * Class encapsulates all hook handlers
 *
 */
class ModuleManager
{

    public static $messages=array();
    public static $page;
/**
 * Initialize plugin enviroment
 */
   public static function init()
   {
        // init them
        add_action('init', array(__CLASS__, '_init_'), 100); // late init in order to have all custom post types and taxonomies registered
   }

   public static function _init_()
   {
        // load translations from locale
        ModMan_Loader::loadLocale('module-manager');

        // set up models and db settings
        self::prepareDB();

        if(is_admin())
        {
            // do this on admin only
            self::prepareFolders();

            // setup js, css assets
            add_action('admin_enqueue_scripts', array(__CLASS__,'onAdminEnqueueScripts'));
            add_action('wpmodules_inline_element_gui', array(__CLASS__, 'inlineElementGUI'), 5, 1);

            // add plugin menus
            add_action('admin_menu', array(__CLASS__, 'addMenuItems'));

            //New Toolset unified menu support
            add_filter( 'toolset_filter_register_menu_pages', array(__CLASS__, 'modulemanager_unified_menu'), 70 );

            //Shared import screen
            add_filter( 'toolset_filter_register_export_import_section', array(__CLASS__, 'modulemanager_register_export_import_section' ) ,70 );

            // make page a parameter
            self::$page=admin_url('admin.php').'?page=ModuleManager_Modules';

            // Add settings link on plugin page
            add_filter("plugin_action_links_".MODMAN_PLUGIN, array(__CLASS__, 'addSettingsLink'));

            //Module Manager 1.6.5+ Support for release notes link
            add_filter( 'plugin_row_meta', array(__CLASS__, 'modulemanager_plugin_plugin_row_meta'), 10, 4 );

            /**
             * Module Manager 1.6.7+ Fixed usability issues when identifying WordPress Archives in Modules
             */
            if ( defined('_VIEWS_MODULE_MANAGER_KEY_') ) {
            	add_filter( 'wpmodules_register_sections', array(__CLASS__, 'modulemanager_identify_wordpress_archives'), PHP_INT_MAX, 1 );
            	add_filter( 'wpmodules_register_items_'._VIEWS_MODULE_MANAGER_KEY_, array(__CLASS__, 'modulemanager_identify_wordpress_archives_items'), PHP_INT_MAX, 1 );
            }

			if ( ! class_exists( 'Module_Manager_Wp_Import' ) ) {
				require( MODMAN_PLUGIN_PATH . '/controllers/wp_import.php' );
			}

        }
        // user settings
        ModMan_Settings::userSettings(array('module-manager'));

        // include router for REST API
        ModMan_Ajax_Router::addRoutes('modman', array(
            'Modules'=>0 // Modules controller
        ));
   }

    public static function onAdminEnqueueScripts()
    {
        global $pagenow, $post_type, $wp_version;

        // setup css js
        if
            (
               $pagenow=='admin.php' && isset($_GET['page']) &&
               'ModuleManager_Modules'==$_GET['page']
            )
        {
            if (defined('MODMAN_DEV')&&MODMAN_DEV)
            {
                ModMan_Loader::loadAsset('STYLE/module-manager-dev', 'module-manager');
                ModMan_Loader::loadAsset('SCRIPT/module-manager-dev', 'module-manager');
            }
            else
            {
                ModMan_Loader::loadAsset('STYLE/module-manager', 'module-manager');
                ModMan_Loader::loadAsset('SCRIPT/module-manager', 'module-manager');
            }

	        if ( ! wp_script_is( 'wp-pointer' ) ) {
		        wp_enqueue_script('wp-pointer');
	        }
	        if ( ! wp_style_is( 'wp-pointer' ) ) {
		        wp_enqueue_style('wp-pointer');
	        }

            // Translations
            // insert into javascript
            wp_localize_script( 'module-manager', 'ModuleManagerConfig', array(
                'Settings'=>array(
                    'ajaxurl' => self::route('/Modules/saveModules'),
                    'exportModuleRoute' => self::route('/Modules/exportModule'),
                    'moduleInfoKey' => MODMAN_MODULE_INFO
                ),
                'Locale'=>array(
                    'onPageExit' => __('Settings have changed, if you leave the page they will not be saved!','module-manager'),
                    'onModuleRemove' => __('Do you want to completely remove this module?','module-manager'),
                    'addNewModuleTip' => '<h3>'.__('Add new module','module-manager').'</h3>'.'<p>'.__('Click here to add a new module','module-manager').'</p>',
                    'newModuleName' => __('Module Name','modman'),
                	'duplicatenewModuleName' =>__('Duplicate Module Name- please choose another name','modman'),
                	'illegalwModuleName' =>__('Module name valid characters: a-z or A-Z or 0-9 or hyphen and space characters.','modman'),
                    'addElementsTip' =>	'<h3>' . __( 'How to add elements', 'module-manager' ) . '</h3>'.'<p>' . __( 'Drag elements here to add them to this module.', 'module-manager' ) . '</p>',
                    'exportErrorMsg' => __('An error occurred please try again','module-manager'),
                    'moduleEmptyMsg' => __('Module is empty','module-manager'),
                    'addAllItemsText'=> __('Add All Items','module-manager'),
                    'itemNotAvailableTip' => '<h3>'.__('Item Not Available','module-manager').'</h3>'.'<p>'.__('This item has been removed from your site or is not available, so it will not be included in the exported module.','module-manager').'</p>'
                )
            ));
        }
    }

    // setup settings menu link on plugins page
    public static function addSettingsLink($links)
    {
        if (current_user_can(MODMAN_CAPABILITY))
        {
            $settings_link = '<a href="'.self::$page.'">'.__('Settings','module-manager').'</a>';
            array_unshift($links, $settings_link);
        }
        return $links;
    }

    // setup Module Manager menus in admin
    public static function addMenuItems()
    {
    	//New Toolset unified menu
    	$can_support_unified_menu = self::modulemanager_can_implement_unified_menu();
    	if ( ! ( $can_support_unified_menu ) ) {

    		$menu_label = __( 'Module Manager','module-manager' );
	        $mm_index = 'ModuleManager_Modules';
			$toolset_any_embedded = mm_check_toolset_plugins( 'any_embedded' );
			$toolset_all_disabled = mm_check_toolset_plugins( 'all_disabled' );
			$toolset_all_full = mm_check_toolset_plugins( 'all_full' );
			$toolset_all_full_types_views = mm_check_toolset_plugins( 'full_types_views' );

			$hook1=add_menu_page($menu_label, $menu_label, MODMAN_CAPABILITY, $mm_index, array(__CLASS__, 'ModulesMenuPage'), 'none', 120);
			if (( $toolset_any_embedded ) || ($toolset_all_disabled)) {
				//Add modules library to menu as the first element on the submenu (by using the same slug $mmm_index) because there is any Toolset plugin embeded
				$hook3=add_submenu_page($mm_index, __('Modules Library', 'module-manager'), __('Modules Library', 'module-manager'), MODMAN_CAPABILITY, $mm_index);
			} else {
				if (($toolset_all_full) || ($toolset_all_full_types_views)) {
				//Add modules library to menu
				$hook3=add_submenu_page($mm_index, __('Modules Library', 'module-manager'), __('Modules Library', 'module-manager'), MODMAN_CAPABILITY, 'admin.php?page=ModuleManager_Modules&tab=library');
				} else {
					$hook3=add_submenu_page($mm_index, __('Modules Library', 'module-manager'), __('Modules Library', 'module-manager'), MODMAN_CAPABILITY, $mm_index);
				}
			}
			//Add import page to menu
			$hook2=add_submenu_page($mm_index, __('Import Modules', 'module-manager'), __('Import Modules', 'module-manager'), MODMAN_CAPABILITY, 'admin.php?page=ModuleManager_Modules&tab=import');

	        //self::addScreenHelp($hook1, '');
	        //self::addScreenHelp($hook2, '');
    	}
   }

    public static function ModulesMenuPage()
    {
        ModMan_Loader::load('VIEW/modules');
    }

    /**
     * Adds help on admin pages.
     *
     * @param type $contextual_help
     * @param type $screen_id
     * @param type $screen
     * @return type
     */
    public static function addScreenHelp( $hook, $contextual_help = '' )
    {
        global $wp_version;
        $call = false;

        // WP 3.3 changes
        if ( version_compare( $wp_version, '3.2.1', '>' ) )
        {
            set_current_screen( $hook );
            $screen = get_current_screen();
            if ( !is_null( $screen ) )
            {
                $args = array(
                    'title' => __( 'Module Manager', 'module-manager' ),
                    'id' => $hook,
                    'content' => $contextual_help,
                    'callback' => false,
                );
                $screen->add_help_tab( $args );
            }
        }
        else
        {
            add_contextual_help( $hook, $contextual_help );
        }
    }

    public static function route($path='', $params=null, $raw=true)
    {
        return ModMan_Ajax_Router::getRoute('modman', $path, $params, $raw);
    }

    public static function sanitizeTags($html)
    {
        // allow p,br,bold,italic and links
        $allowed_html=array(
            'a' => array(
                'href' => array(),
                'title' => array(),
                'target'=>array()
            ),
            'p' => array(),
            'br' => array(),
            'em' => array(),
            'i' => array(),
            'b' => array(),
            'strong' => array()
        );

        return wp_kses($html, $allowed_html);
    }

    public static function inlineElementGUI($element)
    {
        $mm_element=(object)array(
            'id'=>$element['id'],
            'title'=>$element['title'],
            'section'=>$element['section'],
            'description'=>isset($element['description'])?$element['description']:''
        );
        echo ModMan_Loader::tpl('inline', array(
            'element'=>$mm_element,
            'modules'=>ModMan_loader::get('MODEL/Modules')->getModules()
        ));
    }

    public static function exportModule($modulename, $ajax=true)
    {
        $modules=ModMan_Loader::get('MODEL/Modules')->getModules();

        if (!isset($modules[$modulename]))
        {
            return new WP_Error('module_not_exist', __('Module does not exist', 'module-manager'));
        }

        $module=$modules[$modulename];
        $xmls=array();
        if (!isset($module[MODMAN_MODULE_INFO]))
            $module[MODMAN_MODULE_INFO]=array();
        if (!isset($module[MODMAN_MODULE_INFO]['description']))
            $module[MODMAN_MODULE_INFO]['description']='';
        if (!isset($module[MODMAN_MODULE_INFO]['name']))
            $module[MODMAN_MODULE_INFO]['name']=$modulename;

        foreach ($module as $section_id=>$items)
        {
            if ( $section_id!=MODMAN_MODULE_INFO && is_array($items) && !empty($items) )
            {
                // pass full items data, and let override
                /*foreach ($items as $ii=>$item)
                {
                    $items[$ii]=$item['id'];
                }*/
                // make sure script does not timeout, 2 mins
                set_time_limit(120);

                //Make sure all group ID are of correct format before exporting!
                if ($section_id=='groups') {

                		foreach ($items as $outer_key=>$group_item_values_array) {

                			foreach ($group_item_values_array as $inner_key=>$inner_value) {

                				if ($inner_key=='id') {

                					//Test for correct format
                					$arbitrary_value=intval(str_replace('12'.$section_id.'21','',$inner_value));

                					if (!($arbitrary_value)) {
                						//Wrong format, correct it
                					    $posts_arbitrary = get_posts(
											array(
												'post_type'              => 'wp-types-group',
												'title'                  => $group_item_values_array['title'],
												'post_status'            => 'all',
												'numberposts'            => 1,
												'update_post_term_cache' => false,
												'update_post_meta_cache' => false,
												'orderby'                => 'post_date ID',
												'order'                  => 'ASC',
											)
										);
                					    //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/187254315/comments#287007203
                						if ( ! empty( $posts_arbitrary ) ) {
											$post_arbitrary = $posts_arbitrary[0];
                							$group_id_db= $post_arbitrary->ID;
                							$items[$outer_key][$inner_key]='12'.$section_id.'21'.$group_id_db;
                						}
                					}

                				}
                			}

                		}
                }
                $res=apply_filters('wpmodules_export_items_'.$section_id, array('xml'=>'', 'items'=>$items), $items);

                if (is_array($res) && isset($res['xml']))
                {
                    if (!empty($res['xml']))
                        $xmls[$section_id] = $res['xml'];
                    if (!empty($res['items']))
                        $module[$section_id] = $res['items'];
                }
                elseif ( is_array($res) && isset($res['array']) ){

                    if (!empty($res['array'])){
                        $xmls[$section_id] = $res['array'];
                    }
                    if (!empty($res['items'])){
                        $module[$section_id] = $res['items'];
                    }
                }
                elseif (is_string($res) && !empty($res)) // compatibility if only string returned
                {
                    $xmls[$section_id] = $res;
                }


                $plugin_version_exported=apply_filters('wpmodules_export_pluginversions_'.$section_id,$section_id);

                if (!(empty($plugin_version_exported))) {

                	if ($plugin_version_exported!=$section_id) {

                		//Include in modules info array
                		$module[MODMAN_MODULE_INFO][$section_id.'_plugin_version']=$plugin_version_exported;

                	}

                }

            }
        }
        // add module info also
        $module=self::prepareModuleInfo($module);

        $xmls[MODMAN_MODULE_INFO] = serialize($module);

        self::exportModuleZIP($modulename, $xmls, $ajax);
    }

    public static function prepareModuleInfo($module) {

    	//Content Template
    	if (defined('_VIEW_TEMPLATES_MODULE_MANAGER_KEY_')) {
    		$view_templates_module_manager_key=_VIEW_TEMPLATES_MODULE_MANAGER_KEY_;
    	} else {
    		$view_templates_module_manager_key='view-templates';
    	}
    	//Views
    	if (defined('_VIEWS_MODULE_MANAGER_KEY_')) {
    		$views_module_manager_key=_VIEWS_MODULE_MANAGER_KEY_;
    	} else {
    		$views_module_manager_key='views';
    	}
    	//CRED 1.3
    	if (defined('_CRED_MODULE_MANAGER_KEY_')) {
    		$cred_module_manager_key=_CRED_MODULE_MANAGER_KEY_;
    	} else {
    		$cred_module_manager_key='cred';
    	}

        if (defined('_CRED_MODULE_MANAGER_USER_KEY_')) {
            $cred_module_manager_key = _CRED_MODULE_MANAGER_USER_KEY_;
        } else {
            $cred_module_manager_key = 'cred-user';
        }

        //Handle Forms
        if (isset($module['cred-user'])) {
            $cred = $module['cred-user'];
            if ((is_array($cred)) && (!(empty($cred)))) {

                //Loop through the CRED module
                foreach ($cred as $cred_looped => $cred_info) {
                    if (isset($cred_info['title'])) {
                        $cred_title_info_id_data = $cred_info['title'];
                        if (empty($cred_title_info_id_data)) {
                            //No Title Set
                            $cred_id = str_replace(_CRED_MODULE_MANAGER_USER_KEY_, '', $cred_info['id']);
                            $cred_object = get_post($cred_id);
                            if ((isset($cred_object->post_type)) && (isset($cred_object->post_title))) {
                                $cred_pt = $cred_object->post_type;
                                $cred_title = $cred_object->post_title;
                                //Set Title
                                if ($cred_pt == 'cred-user-form') {
                                    $module['cred-user'][$cred_looped]['title'] = $cred_title;
                                }
                            }
                        }
                    }
                }
            }
        }


        //Handle Content Templates
    	if (isset($module['view-templates'])) {
    		$viewtemplates=$module['view-templates'];
    		if ((is_array($viewtemplates)) && (!(empty($viewtemplates)))) {

    			//Loop through the Content Templates module
    			foreach ($viewtemplates as $ct_looped=>$ct_info) {
    				if (isset($ct_info['title'])) {
    					$title_info_id_data=$ct_info['title'];
    					if (empty($title_info_id_data)) {
    						//No Title Set
    						$ct_id=str_replace( $view_templates_module_manager_key, '', $ct_info['id'] );
    						$ct_object=get_post($ct_id);
    						if ((isset($ct_object->post_type)) && (isset($ct_object->post_title)))	 {
    							$ct_pt=$ct_object->post_type;
    							$ct_title=$ct_object->post_title;
    							//Set Title
    							if ($ct_pt == 'view-template') {
    								$module['view-templates'][$ct_looped]['title']=$ct_title;
    							}
    						}
    					}
    				}
    			}
    		}
    	}

    	//Handle Views
    	if (isset($module['views'])) {
    		$views=$module['views'];
    		if ((is_array($views)) && (!(empty($views)))) {

    			//Loop through the Views module
    			foreach ($views as $views_looped=>$view_info) {
    				if (isset($view_info['title'])) {
    					$view_title_info_id_data=$view_info['title'];
    					if (empty($view_title_info_id_data)) {
    						//No Title Set
    						$view_id=str_replace( $views_module_manager_key, '', $view_info['id'] );
    						$view_object=get_post($view_id);
    						if ((isset($view_object->post_type)) && (isset($view_object->post_title)))	 {
    							$view_pt=$view_object->post_type;
    							$view_title=$view_object->post_title;
    							//Set Title
    							if ($view_pt == 'view') {
    								$module['views'][$views_looped]['title']=$view_title;
    							}
    						}
    					}
    				}
    			}
    		}
    	}

    	//Handle Forms
    	if (isset($module['cred'])) {
    		$cred=$module['cred'];
    		if ((is_array($cred)) && (!(empty($cred)))) {

    			//Loop through the CRED module
    			foreach ($cred as $cred_looped=>$cred_info) {
    				if (isset($cred_info['title'])) {
    					$cred_title_info_id_data=$cred_info['title'];
    					if (empty($cred_title_info_id_data)) {
    						//No Title Set
    						$cred_id=str_replace(_CRED_MODULE_MANAGER_KEY_,'',$cred_info['id']);
    						$cred_object=get_post($cred_id);
    						if ((isset($cred_object->post_type)) && (isset($cred_object->post_title)))	 {
    							$cred_pt=$cred_object->post_type;
    							$cred_title=$cred_object->post_title;
    							//Set Title
    							if ($cred_pt == 'cred-form') {
    								$module['cred'][$cred_looped]['title']=$cred_title;
    							}
    						}
    					}
    				}
    			}
    		}
    	}

        //Handle Layouts
        //Check this constant just in case Layouts is not activated.
    	if ( !defined('WPDDL_LAYOUTS_POST_TYPE') ) {
    		define('WPDDL_LAYOUTS_POST_TYPE', 'dd_layouts');
    	}
        if (isset($module[WPDDL_LAYOUTS_POST_TYPE])) {
            $layouts=$module[WPDDL_LAYOUTS_POST_TYPE];
            if ((is_array($layouts)) && (!(empty($layouts)))) {
                //Loop through the CRED module
                foreach ($layouts as $layouts_looped=>$layouts_info) {
                    if (isset($layouts_info['title'])) {
                        $layouts_title_info_id_data=$layouts_info['title'];
                        if ( $layouts_title_info_id_data == '' ) {
                            //No Title Set
                            $layouts_id=str_replace(WPDDL_LAYOUTS_POST_TYPE,'',$layouts_info['id']);
                            $layouts_object = get_post($layouts_id);
                            if ((isset($layouts_object->post_type)) && (isset($layouts_object->post_title)))	 {
                                $layouts_pt=$layouts_object->post_type;
                                $layouts_title=$layouts_object->post_title;
                                //Set Title
                                if ($layouts_pt == WPDDL_LAYOUTS_POST_TYPE) {
                                    $module[WPDDL_LAYOUTS_POST_TYPE][$layouts_looped]['title']=$layouts_title;
                                }
                            } elseif( $layouts_info['id'] === 'CSS' || WPDDL_LAYOUTS_POST_TYPE.'CSS' === $layouts_info['id'] ){
                                $module[WPDDL_LAYOUTS_POST_TYPE][$layouts_looped]['title'] = __("Layouts CSS", 'ddl-layouts');
                            } elseif( $layouts_info['id'] === 'JS' || WPDDL_LAYOUTS_POST_TYPE.'JS' === $layouts_info['id'] ){
                                $module[WPDDL_LAYOUTS_POST_TYPE][$layouts_looped]['title'] = __("Layouts JS", 'ddl-layouts');
                            }
                        }
                    }
                }
            }
        }

        return $module;
    }

    private static function exportModuleZIP($modulename, $import_data, $ajax=true)
    {
        if (empty($import_data))
        {
            return new WP_Error('nothing_to_export', __('Nothing to export', 'module-manager'));
        }

        if (!class_exists('ZipArchive'))
        {
            return new WP_Error('zip_not_supported', __('PHP does not support ZipArchive class', 'module-manager'));
        }

        $modulename = sanitize_key($modulename);
        if (!empty($modulename))
        {
            $modulename .= '-';
        }

        $zipname = $modulename . date('Y-m-d') . '.zip';
        $zip = new ZipArchive();
        /*
        $tmp='tmp';
        // http://php.net/manual/en/function.tempnam.php#93256
        if (function_exists('sys_get_temp_dir'))
            $tmp=sys_get_temp_dir();
        */
        /*EMERSON, generate tmp files in WP uploads directory*/
        $uploads_directory=wp_upload_dir();
        $uploads_directory_basedir=$uploads_directory['basedir'];

        $file = tempnam($uploads_directory_basedir, "zip");
        $zip->open($file, ZipArchive::OVERWRITE);


        foreach ($import_data as $data_key => $file_data)
        {
            if ( $data_key!== MODMAN_MODULE_INFO && is_string($file_data) ){
                $res = $zip->addFromString($data_key.'.xml', $file_data);
            } elseif( $data_key!== MODMAN_MODULE_INFO && is_array($file_data) ){
                $dir = $zip->addEmptyDir( $data_key );
                foreach( $file_data as $filename => $json_string ){
                    $res = $zip->addFromString($data_key.'/'.$filename, $json_string);
                }
            } else{
                $res = $zip->addFromString($data_key, $file_data);
            }

            unset($import_data[$data_key]);
        }

        unset($import_data);
        $zip->close();

        $data = file_get_contents($file);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=" . $zipname);
        header("Content-Type: application/zip");
        header("Content-length: " . strlen($data) . "\n\n");
        header("Content-Transfer-Encoding: binary");
        if ($ajax) header("Set-Cookie: __ModManExportDownload=true; path=/");
        echo $data;
        unlink($file);
        die();
    }

    private static function generateTmpName()
    {
        return MODMAN_TMP_PATH.DIRECTORY_SEPARATOR.time().'.'.uniqid();
    }

    public static function importModuleStepByStep($step=0, $params = array())
    {
        $DS=DIRECTORY_SEPARATOR;
        $info=array();
        $items=array();
        $module_name='';
        switch ($step)
        {
            case 1:
                if (isset($params['file']))
                {
                    $xmls = self::importModuleZIP($params['file']);
                    if (is_wp_error($xmls) || !is_array($xmls))
                    {
                        return new WP_Error('import_error', $xmls->get_error_message($xmls->get_error_code()));
                    }

                    $tmp_dir=ModuleManager::generateTmpName();

                    if (@mkdir($tmp_dir))
                    {
                        if (isset($xmls[MODMAN_MODULE_INFO]))
                        {
                            $info=$xmls[MODMAN_MODULE_INFO];
                            if (is_serialized($xmls[MODMAN_MODULE_INFO]))
                                $info=unserialize($xmls[MODMAN_MODULE_INFO]);
                            //$module_name=$info['name'];
                            // save tmp dir for future steps
                            $info[MODMAN_MODULE_TMP_FILE]=$tmp_dir;
                            // save module info file
                            file_put_contents($tmp_dir.$DS.MODMAN_MODULE_INFO, $xmls[MODMAN_MODULE_INFO]);
                        }
                        $sections=ModMan_Loader::get('MODEL/Modules')->getRegisteredSections();

                        foreach (array_keys( $sections ) as $section_id )
                        {
							if ( 'demo_content' === $section_id ) {
								$module_info = unserialize ( $xmls['__module_info__'] );
								if ( isset( $module_info['demo_content'] ) && ! empty( $module_info['demo_content'] ) ) {
									foreach( $module_info['demo_content'] as $dc_index => $dc_info ) {
										if ( ! isset( $xmls[ 'demo_content_' . $dc_info['id'] ] ) )  continue;
										file_put_contents($tmp_dir . $DS . $section_id . '_'. $dc_info['id'] . '.xml', $xmls[ 'demo_content_' . $dc_info['id'] ] );
									}
								}
							}
                            if ( isset( $xmls[$section_id] ) )
                            {
                                if( is_string( $xmls[$section_id] ) ){
                                    // save xml files
                                    file_put_contents($tmp_dir.$DS.$section_id.'.xml', $xmls[$section_id]);

                                } elseif( is_array( $xmls[$section_id] ) ){

                                        if( @mkdir($tmp_dir.$DS.$section_id) ){
                                            foreach( $xmls[$section_id] as $name => $data ){
                                            	/**
                                            	 * mm-84: Some module might not have JS or CSS resulting in importing issues.
                                            	 * Change && to ||
                                            	 */
                                                if ( ( isset( $data['css'] ) )  || ( isset( $data['js'] ) ) ) {
                                                	//Layouts CSS and JS resource
                                                	foreach ( $data as $resources_extension => $resource_details ) {
                                                		file_put_contents($tmp_dir.$DS.$section_id.$DS.$name.'.'.$resource_details['extension'], $resource_details['data']);
                                                	}
                                                } else {
                                                	file_put_contents($tmp_dir.$DS.$section_id.$DS.$name.'.'.$data['extension'], $data['data']);
                                                }

                                            }
                                        }
                                }

                                // get existing items
                                $items=$info[$section_id];

                                $items=apply_filters('wpmodules_items_check_'.$section_id, $items);
                                $info[$section_id]=$items;
                            }
                        }
                        return $info;
                    }
                    else
                    {
                        return new WP_Error('import_error', __('Could not create tmp module folder','module-manager'));
                    }
                    return new WP_Error('import_error', __('Unknown error','module-manager'));
                }
                return new WP_Error('import_error', __('No module file given', 'module-manager'));
                break;
            case 2:

                $results=array();
                $info=array();
                $has_errors=false;
                if (isset($params['info']))
                {
                    if (is_dir($params['info']))
                    {
                        $tmp_dir=$params['info'];
                        $info=unserialize(file_get_contents($tmp_dir.$DS.MODMAN_MODULE_INFO));
                    }
                    else
                    {
                        return new WP_Error('import_error', __('Tmp module folder does not exist', 'module-manager'));
                    }
               }
                else
                {
                    return new WP_Error('import_error', __('No module info given', 'module-manager'));
                }

                if (isset($params['items']))
                {
                    $items=$params['items'];
                }
                else
                {
                    return new WP_Error('import_error', __('No module items given', 'module-manager'));
                }

                // get sections
                $sections=ModMan_Loader::get('MODEL/Modules')->getRegisteredSections();

                // get xml files from prev step
                $xmls = array_diff( scandir($tmp_dir), array('.','..') );

                foreach (array_keys($sections) as $section_id)
                {
                    // if file exists
                    if (in_array($section_id.'.xml', $xmls) && isset($items[$section_id]))
                    {
                         // make sure script does not timeout, 2 mins
                        set_time_limit(120);
	                    if ( 'm2m_relationships' == $section_id && class_exists('Toolset_Post_Type_Repository') ) {
		                    // Refresh post types list to include freshly added post types.
		                    $post_type_repository = Toolset_Post_Type_Repository::get_instance();
		                    $post_type_repository->refresh_all_post_types();
	                    }
                        $results[$section_id] = apply_filters('wpmodules_import_items_'.$section_id, null, file_get_contents($tmp_dir.$DS.$section_id.'.xml'), array_keys($items[$section_id]), array() /* not ready yet */);

                   } elseif( in_array($section_id, $xmls) && isset($items[$section_id]) && is_dir( $tmp_dir.$DS.$section_id ) ){

                        set_time_limit(120);

                        $results[$section_id] = apply_filters('wpmodules_import_items_'.$section_id, null, $tmp_dir.$DS.$section_id, array_keys($items[$section_id]), $info[$section_id]);
                    } elseif ( $section_id === 'demo_content' ) {

						if ( isset( $items[ $section_id ] ) && ! empty( $items[ $section_id ]) ) {
							$modman_wp_importer = Module_Manager_Wp_Import::get_instance ();
							$results[$section_id] = array(
								'updated' => 0,
								'new' => 0,
								'failed' => 0,
								'errors' => array()
							);
							foreach( $items[ $section_id ] as $dc_post_type => $dc_import ) {
								if ( strpos( $dc_post_type, 'relationships_data_' ) !== 0 ) {
									$result = $modman_wp_importer->import_posts ( $tmp_dir . $DS . 'demo_content_' . $dc_post_type . '.xml' );
								} else {
									continue;
								}
								if ( isset( $result['new'] ) && $result['new'] != 0 ){
									$results[$section_id]['new'] += $result['new'];
								}
								if ( isset( $result['failed'] ) && $result['failed'] != 0 ){
									$results[$section_id]['failed'] += $result['failed'];
								}
							}
							foreach( $items[ $section_id ] as $dc_post_type => $dc_import ) {
								if ( strpos( $dc_post_type, 'relationships_data_' ) === 0 ) {
									$result = self::import_relationships_data( $tmp_dir.$DS.'demo_content_' . $dc_post_type . '.xml', $dc_post_type, $modman_wp_importer );
								} else {
									continue;
								}
								if ( isset( $result['new'] ) && $result['new'] != 0 ){
									$results[$section_id]['new'] += $result['new'];
								}
								if ( isset( $result['failed'] ) && $result['failed'] != 0 ){
									$results[$section_id]['failed'] += $result['failed'];
								}
							}
						}
                    }

                    if (isset($results[$section_id])) {
                    	//Proceed to the following code block below if this is set
	                    if (null!==$results[$section_id]  && !empty($results[$section_id]['errors']))
	                    {
	                        $has_errors=true;
	                    }
	                    elseif (null!==$results[$section_id] && is_array($results[$section_id]))
	                    {
	                        // import new items into module
	                        if (isset($results[$section_id]['items']))
	                        {
	                            foreach ($info[$section_id] as $ii=>$_item_)
	                            {
	                                // set new item id
	                                if (isset(
	                                    $results[
	                                    $section_id][
	                                    'items'][
	                                    $info[
	                                    $section_id][
	                                    $ii][
	                                    'id']])
	                                )
	                                    $info[$section_id][$ii]['id']=$results[$section_id]['items'][$info[$section_id][$ii]['id']];
	                                // remove the not-imported items from module
	                                else
	                                    unset($info[$section_id][$ii]);
	                            }
	                            if (empty($info[$section_id]))
	                                unset($info[$section_id]);
	                        }
	                    }
                	}
                }

                if (!$has_errors)
                {
                    $imported_sections=array_merge(
                        array_keys($results),
                        array(MODMAN_MODULE_INFO)
                    );
                    // remove sections not imported
                    foreach ($info as $sect=>$sectdata)
                    {
                        if (!in_array($sect, $imported_sections))
                            unset($info[$sect]);
                    }
                    unset($imported_sections);
                }

                // remove tmp module folder
                @self::delTree($tmp_dir);

                // remove the tmp file variable from info
                unset($info[MODMAN_MODULE_TMP_FILE]);
                //remove demo content from the modules list
	            unset( $info['demo_content'] );
                //if ($has_errors)
                    //return new WP_Error('import_errors', implode('<br />',$errors));
                if (!$has_errors)
                {
                    // add module as a new module
                    $model=ModMan_Loader::get('MODEL/Modules');
                    $model->addNewModule($info[MODMAN_MODULE_INFO]['name'], $info);
                }
                // add module info
                $results[MODMAN_MODULE_INFO]=$info[MODMAN_MODULE_INFO];
                return $results;
                break;
            default:
                return new WP_Error('import_error', __('Wrong step', 'module-manager'));
                break;
        }
    }

	/**
	 *
	 * @param string $file File name
	 * @param string $post_type
	 * @param object $modman_wp_importer
	 *
	 * @return array
	 */
	public static function import_relationships_data( $file, $post_type, $modman_wp_importer ) {
		$post_type = str_replace( 'relationships_data_', '', $post_type );
		$result = array(
			'new' => 0,
			'failed' => 0
		);
		if ( file_exists ( $file ) ) {
			global $wpdb;
			$toolset_relationships = $wpdb->prefix . 'toolset_relationships';
			$toolset_type_sets_table = $wpdb->prefix . 'toolset_type_sets';
			if( $wpdb->get_var( "SHOW TABLES LIKE '{$toolset_relationships}'" ) != $toolset_relationships ||
				$wpdb->get_var( "SHOW TABLES LIKE '{$toolset_type_sets_table}'" ) != $toolset_type_sets_table ) {
				return $result;
			}
			$relationships = $wpdb->get_results( "SELECT slug, id from {$toolset_relationships}", OBJECT_K );
			$posts = $wpdb->get_results( "SELECT post_name, ID from {$wpdb->prefix}posts where post_status='publish'", OBJECT_K );
			$associations = $wpdb->get_results( "SELECT parent_id, child_id, relationship_id from {$wpdb->prefix}toolset_associations", ARRAY_A );
			$file_content = file_get_contents ( $file );

			$file_content = new SimpleXMLElement( $file_content );
			foreach( $file_content as $relationship ) {
				$relationship = (array)$relationship;
				if ( isset( $relationships[ $relationship['relationships_id'] ] ) ) {
					$relationship_id = $relationships[ $relationship['relationships_id'] ]->id;
					$parent_id = isset( $posts[ $relationship['parent_id'] ] ) ? $posts[ $relationship['parent_id'] ]->ID : '' ;
					$child_id = isset( $posts[ $relationship['child_id'] ] ) ? $posts[ $relationship['child_id'] ]->ID : '' ;
					if ( ! empty( $parent_id ) && ! empty( $child_id )
						 && ! self::is_relationship_associations_exists ( $associations, $child_id, $parent_id, $relationship_id ) ) {

						$wpdb->query ( $wpdb->prepare ( "INSERT INTO {$wpdb->prefix}toolset_associations VALUES('', %d, %d, %d, 0)",
							$relationship_id, $parent_id, $child_id ) );
						$result['new'] += 1;

					} else {
						$result['failed'] += 1;
					}
				}
			}

		}

		return $result;
	}

	/**
	 * Check if relationship exists
	 *
	 * @param $associations
	 * @param $child_id
	 * @param $parent_id
	 * @param $relationship_id
	 *
	 * @return bool
	 */
	public static function is_relationship_associations_exists( $associations, $child_id, $parent_id, $relationship_id ) {
		foreach( $associations as $association ) {
			if ( $association['child_id'] == $child_id && $association['parent_id'] == $parent_id &&
				$association['relationship_id'] == $relationship_id ) {
				return true;
			}
		}

		return false;
	}

    public static function importModule($file_path)
    {
        $xmls=self::importModuleZIP(array('name'=>$file_path,'tmp_name'=>$file_path));
        if (is_wp_error($xmls) || !is_array($xmls))
        {
            return array($xmls->get_error_message($xmls->get_error_code()));
        }
        if (!isset($xmls[MODMAN_MODULE_INFO]))
        {
            return array(__('Module Information does not exist in file', 'module-manager'));
        }

        $results=array();
        $info=maybe_unserialize($xmls[MODMAN_MODULE_INFO]);
        $has_errors=false;

        // get sections
        $sections=ModMan_Loader::get('MODEL/Modules')->getRegisteredSections();

        foreach (array_keys($sections) as $section_id)
        {
            // if file exists
            if (isset($xmls[$section_id]) && isset($info[$section_id]))
            {
                $all_items=array();
                foreach ($info[$section_id] as $ii=>$item)
                {
                    $all_items[]=$item['id'];
                }

                // make sure script does not timeout, 2 mins
                set_time_limit(120);
                $results[$section_id] = apply_filters('wpmodules_import_items_'.$section_id, null, $xmls[$section_id], $all_items, array() /* not ready yet */);

                if (null!==$results[$section_id]  && !empty($results[$section_id]['errors']))
                {
                    $has_errors=true;
                }
                elseif (null!==$results[$section_id] && is_array($results[$section_id]))
                {
                    // import new items into module
                    if (isset($results[$section_id]['items']))
                    {
                        foreach ($info[$section_id] as $ii=>$_item_)
                        {
                            // set new item id
                            if (isset(
                                $results[
                                    $section_id][
                                        'items'][
                                            $info[
                                                $section_id][
                                                    $ii][
                                                        'id']])
                            )
                                $info[$section_id][$ii]['id']=$results[$section_id]['items'][$info[$section_id][$ii]['id']];
                            // remove the not-imported items from module
                            else
                                unset($info[$section_id][$ii]);
                        }
                        if (empty($info[$section_id]))
                            unset($info[$section_id]);
                    }
                }
           }
        }

        if (!$has_errors)
        {
            $imported_sections=array_merge(
                array_keys($results),
                array(MODMAN_MODULE_INFO)
            );
            // remove sections not imported
            foreach ($info as $sect=>$sectdata)
            {
                if (!in_array($sect, $imported_sections))
                    unset($info[$sect]);
            }
            unset($imported_sections);
        }

        // remove the tmp file variable from info
        if (isset($info[MODMAN_MODULE_TMP_FILE]))
            unset($info[MODMAN_MODULE_TMP_FILE]);

        if (!$has_errors)
        {
            // add module as a new module
            $model=ModMan_Loader::get('MODEL/Modules');
            $model->addNewModule($info[MODMAN_MODULE_INFO]['name'], $info);
        }
        if ($has_errors)
        {
            $errors=array();
            foreach ($results as $sc=>$data)
            {
                if (isset($data['errors']))
                    $errors=array_merge($errors, $data['errors']);
            }
            return $errors;
        }
        return true;
    }

    private static function importModuleZIP($file)
    {
        $xmls = array();

        $not_one_xml=true;

        $info = pathinfo($file['name']);

        $is_zip = $info['extension'] == 'zip' ? true : false;

        if ($is_zip)
        {
            if (!class_exists( 'ZipArchive' ))
            {
                return new WP_Error('zip_not_supported', __('PHP does not support ZipArchive class', 'module-manager'));
            }

            $zip = new \ZipArchive();
            if ($zip->open(urldecode( $file['tmp_name'] )) !== false) {
                for ($index = 0; $index < $zip->numFiles; ++$index) {
                    $filename = $zip->getNameIndex($index);
                    $file_extension = self::get_extension($filename);
                    $file_info = pathinfo($filename);
                    $data = $zip->getFromIndex($index);

                    if (isset($file_info['filename'])) {
                        if (isset($file_info['extension']) && 'xml'== $file_info['extension']) {
                            $not_one_xml=false;
                            $xmls[$file_info['filename']] = $data;
                        } elseif( isset($file_info['extension']) && (
                            'ddl'== $file_info['extension']
                            || 'css' == $file_info['extension']
                            || 'js' == $file_info['extension']
                        ) ) {
                            $not_one_xml=false;
                            $xmls[$file_info['dirname']] = isset($xmls[$file_info['dirname']]) ? $xmls[$file_info['dirname']] : array();

                            if ( 'css' == $file_info['extension'] ) {
                                //CSS
                                $xmls[$file_info['dirname']][$file_info['filename']]['css']['extension'] = $file_info['extension'];
                                $xmls[$file_info['dirname']][$file_info['filename']]['css']['data'] = $data;
                            } elseif ( 'js' == $file_info['extension'] ) {
                                //JS
                                $xmls[$file_info['dirname']][$file_info['filename']]['js']['extension'] = $file_info['extension'];
                                $xmls[$file_info['dirname']][$file_info['filename']]['js']['data'] = $data;
                            } else {
                                $xmls[$file_info['dirname']][$file_info['filename']] = array();
                                $xmls[$file_info['dirname']][$file_info['filename']]['extension'] = $file_info['extension'];
                                $xmls[$file_info['dirname']][$file_info['filename']]['data'] = $data;
                            }


                        } elseif ( MODMAN_MODULE_INFO == $file_info['filename'] ) {
                            $xmls[$file_info['filename']] = $data;
                        }
                    }
                }
                $zip->close();
                if ($not_one_xml)
                {
                    return new WP_Error('no_xml', __('No xml files in .zip.','module-manager'));
                }
                else
                {
                	//Validate if we have valid $xmls before proceeding.
                	$valid_data	= self::check_if_valid_xmls( $xmls );
                	if ( true === $valid_data ) {
                		return $xmls;
                	} else {
                		return new WP_Error('invalid_module_manager_data', __('The file is not a valid Module Manager zip file. Please check.', 'module-manager'));
                	}
                }
            } else {
                return new WP_Error('could_not_open_file', __('Unable to open .zip file', 'module-manager'));
            }
        }
        else
        {
            return new WP_Error('file_not_zip_format', __('File is not .zip format', 'module-manager'));
        }

        return new WP_Error('unknown error', __('Unknown error during import','module-manager'));
    }

    //mm-78: Check if valid xmls
    private static function check_if_valid_xmls( $xmls = array() ) {
    	$valid	= false;
    	if ( is_array( $xmls ) ) {
    		$count	= count( $xmls );
    		//We validate to 2 items at a minimum since an module should contain module info and at least one element: 1 + 1 = 2.
    		if ( ( isset( $xmls['__module_info__'] ) ) && ( $count >= 2 ) ) {
    			$valid	= true;
    		}
    	}
    	return $valid;
    }
    // setup necessary DB model settings
    private static function prepareDB()
    {
        $modules_model = ModMan_Loader::get('MODEL/Modules');
        $modules_model->prepareDB();
    }

    private static function delTree($dir)
    {
        $DS=DIRECTORY_SEPARATOR;
        if (is_dir($dir)) {
        	$files = array_diff(scandir($dir), array('.','..'));
        	foreach ($files as $file)
        	{
         	   if (is_dir("${dir}${DS}${file}")) self::delTree("${dir}${DS}${file}");
         	   if (is_file("${dir}${DS}${file}")) @unlink("${dir}${DS}${file}");
        	}
        	return rmdir($dir);
		}
    }

    private static function purgeTmps()
    {
        $DS=DIRECTORY_SEPARATOR;
        $lock=MODMAN_TMP_PATH.$DS.MODMAN_TMP_LOCK;
        if (is_file($lock))
            return; // lock in progress
        // mutex pattern
        touch($lock);
        $dirs=array_diff(scandir(MODMAN_TMP_PATH), array('.','..'));
        foreach ($dirs as $dir)
        {
            $dtime=intval(substr($dir,0,strpos($dir,'.')+1));
            if ($dtime+MODMAN_PURGE_TIME < time())
            {
                @self::delTree(MODMAN_TMP_PATH.$DS.$dir);
            }
        }
        @unlink($lock); // clear mutex
    }

    // setup necessary folders
    private static function prepareFolders()
    {
        if (!is_dir(MODMAN_TMP_PATH))
        {
            if (! @mkdir(MODMAN_TMP_PATH, 0700))
            {
                self::$messages[]=__( 'Could not create TMP folder with necessary permissions', 'module-manager' );
                return false;
            }
            // create htacces if needed
            if (!is_file(MODMAN_TMP_PATH.'/.htaccess'))
            {
                $htaccess=array(
                    "order deny, allow",
                    "deny from all"
                );
                file_put_contents(MODMAN_TMP_PATH.'/.htaccess', implode(PHP_EOL, $htaccess));
            }
        }
        else
        {
            // create htacces if needed
            if (!is_file(MODMAN_TMP_PATH.'/.htaccess'))
            {
                $htaccess=array(
                    "order deny, allow",
                    "deny from all"
                );
                file_put_contents(MODMAN_TMP_PATH.'/.htaccess', implode(PHP_EOL, $htaccess));
            }
            @self::purgeTmps();
        }
    }

	//Check for new Toolset menu implementation
    public static function modulemanager_can_implement_unified_menu() {
    	$unified_menu = false;
    	$is_available = apply_filters( 'toolset_is_toolset_common_available', false );
    	if ( TRUE === $is_available ) {
    		$unified_menu = true;
    	}

    	return $unified_menu;
    }

    //Render new menu
    public static function modulemanager_unified_menu( $pages ) {

    	//New Toolset unified menu
    	$menu_label = __( 'Modules','module-manager' );
    	$mm_index = 'ModuleManager_Modules';
    	$toolset_any_embedded = mm_check_toolset_plugins( 'any_embedded' );
    	$toolset_all_disabled = mm_check_toolset_plugins( 'all_disabled' );
    	$toolset_all_full = mm_check_toolset_plugins( 'all_full' );
    	$toolset_all_full_types_views = mm_check_toolset_plugins( 'full_types_views' );

    	$pages[] = array(
    			'slug'                      => $mm_index,
    			'menu_title'                => $menu_label,
    			'page_title'                => $menu_label,
    			'callback'                  => array(__CLASS__, 'ModulesMenuPage'),
    			'capability'                => MODMAN_CAPABILITY
    	);

    	return $pages;

    }

    //Shared import screen
    public static function modulemanager_register_export_import_section( $sections ) {

    	$sections['modules_import'] = array(
    			'slug'      => 'modules_import',
    			'title'     => __( 'Modules','module-manager' ),
    			'icon'      => '<i class="icon-module-logo ont-icon-16"></i>',
				'items'		=> array(

							'export'	=> array(
											'title'		=> __( 'Export Modules','module-manager' ),
											'callback'	=> array( __CLASS__, 'modules_export_template' ),
										),
							'import'	=> array(
											'title'		=> __( 'Import Modules','module-manager' ),
											'callback'	=> array(__CLASS__, 'modules_import_template'),
										),
						),
    	);
	    wp_enqueue_style( 'module-manager-import', MODMAN_ASSETS_URL.'/css/import.css' );
	    wp_enqueue_script( 'module-manager-import', MODMAN_ASSETS_URL.'/js/import.js' );
    	$hide_export_sections = self::clean_up_modules_import_area();
    	if ( $hide_export_sections ) {
    		unset( $sections['modules_import']['items']['export'] );
    	}
    	return $sections;


    }

    //Callback for unified import screen
    public static function modules_import_template() {
    	$template = 'import';
    	$template_path = MODMAN_TEMPLATES_PATH . DIRECTORY_SEPARATOR . $template . '.tpl.php';
    	include $template_path;
    }

    //Callback for unified export screen
    public static function modules_export_template() {
    	$mm_url = admin_url( 'admin.php?page=ModuleManager_Modules' );
    	?>
    	<div class="import-module-header">
    	<p>
    	<?php echo __( 'You can export modules at','module-manager' ); ?> <a href="<?php echo $mm_url;?>"><?php echo __( 'Toolset modules admin screen','module-manager' )?>.</a>
    	</p>
    	</div>
    <?php
    }

    //Hide export module section when import module is in-progress
    private static function clean_up_modules_import_area() {
         $hide_export_module_section = false;

         if ( ( isset( $_GET['tab'] ) ) && ( isset( $_GET['step']) ) ) {
         	$tab 	= $_GET['tab'];
         	$step 	= $_GET['step'];
         	$step 	= trim($step);

         	if ( ( 'modules_import' == $tab) && ( ! (empty( $step ) ) ) ) {
         		$hide_export_module_section = true;
         	}
         }

         return $hide_export_module_section;

    }

    /**
     * Automatic release notes link
     * @since 1.6.5
     */
    public static function modulemanager_plugin_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {

    	if ( ( defined('MODMAN_PLUGIN_PATH') ) && ( defined('MODMAN_VERSION') ) && ( is_callable('curl_init') ) ) {
    		if ( ( MODMAN_PLUGIN_PATH ) && ( MODMAN_VERSION ) ) {
    			$this_plugin = basename( MODMAN_PLUGIN_PATH ) . '/plugin.php';
    			if ( $plugin_file == $this_plugin ) {
    				//This is Module manager
    				$version_slug = 'modulemanager-';
    				$current_plugin_version = MODMAN_VERSION;
    				$current_plugin_version_simplified = str_replace( '.', '-', $current_plugin_version );

    				//When releasing Module Manager, slug of version content should match with $article_slug
    				$article_slug = $version_slug.$current_plugin_version_simplified;
    				$linktitle = 'Module Manager'.' '.$current_plugin_version.' '.'release notes';

    				//Raw URL
    				//Override with Toolset domain constant if set
    				if ( defined('WPVDEMO_TOOLSET_DOMAIN') ) {
    					if (WPVDEMO_TOOLSET_DOMAIN) {
    						$raw_url = 'https://'.WPVDEMO_TOOLSET_DOMAIN.'/version/'.$article_slug.'/';

    						$modman_release_link = get_option( 'modman_release_link' );

    						//We don't need to check if release notes exist anytime a user accesses a plugin page
    						//Once the release note is proven to exist, we display
    						$exists = false;
    						if ( false === $modman_release_link) {
    							//Option value not yet defined, we need to check- one time event only
    							if ( self::modman_release_notes_exist( $raw_url ) ) {
    								//Now exists
    								$exists = true;
    							}
    						} elseif ( 'released' == $modman_release_link ) {
    							$exists = true;
    						}

    						if ( $exists ) {

    							//Now released, we append this link.
    							$url_with_ga = $raw_url.'?utm_source=modulemanagerplugin&utm_campaign=modulemanager&utm_medium=release-notes-link&utm_term='.$linktitle;
    							$plugin_meta[] = sprintf(
    									'<a href="%s" target="_blank">%s</a>',
    									$url_with_ga,
    									$linktitle
    									);
    							if ( !( $modman_release_link ) ) {
    								//We update to set this, one time event only.
    								update_option( 'modman_release_link', 'released');
    							}

    						}
    					}
    				}
    			}
    		}
    	}

    	return $plugin_meta;

    }

    /** Quick way to check if release notes exist in the site
     *
     * @param string $url
     * @return boolean
     * @since 1.6.5
     */
    private static function modman_release_notes_exist( $url ) {

    	$ch = curl_init($url);
    	curl_setopt( $ch, CURLOPT_NOBODY, true); // set to HEAD request
    	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true); // don't output the response
    	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_exec( $ch );
    	$valid = curl_getinfo( $ch, CURLINFO_HTTP_CODE ) == 200;
    	curl_close( $ch );

    	return $valid;

    }

    /**
     * mm-63 Improve the usability for WordPress Archive in Module Manager
     * @param array $items_array
     * @return array
     */
    public static function modulemanager_identify_wordpress_archives( $items_array = array() ) {

    	if ( defined('_VIEWS_MODULE_MANAGER_KEY_') ) {
    		//Views plugin activated and module manager integration is active
    		if ( isset( $items_array[_VIEWS_MODULE_MANAGER_KEY_]['title'] ) ) {
    			$items_array[_VIEWS_MODULE_MANAGER_KEY_]['title']	= __( 'Views / WordPress Archives', 'wpv-views' );
    		}
    	}

    	return $items_array;
    }

    /**
     * Add 'WordPress archives' identification to View module elements.
     * @param array $item_details_array
     * @return array
     */
    public static function modulemanager_identify_wordpress_archives_items( $item_details_array = array() ) {

    	/**
    	 * Loop over the Views items and identify Views archives
    	 */
    	global $WP_Views;
    	if ( ( ( defined('_VIEWS_MODULE_MANAGER_KEY_') ) &&
    			( is_object( $WP_Views ) ) ) &&
    			( method_exists( $WP_Views, 'is_archive_view') ) ) {

	    	foreach ( $item_details_array as $k => $view_item_details ) {
	    		if ( isset( $view_item_details['id']) ) {
	    			$view_element_id	= $view_item_details['id'];
	    			if ( ( !empty( $view_element_id ) ) && ( strpos( $view_element_id , _VIEWS_MODULE_MANAGER_KEY_ ) !== false ) ) {
	    				//Validated View element ID
	    				$view_numeric_id	= filter_var( $view_element_id, FILTER_SANITIZE_NUMBER_INT );
	    				$view_numeric_id	= intval( $view_numeric_id );
	    				if ( $view_numeric_id > 0 ) {
	    					//Let's check if this View is an archive
	    					$is_archive_view	= $WP_Views->is_archive_view( $view_numeric_id );
	    					if ( true === $is_archive_view ) {
	    						//Yes,
	    						if ( isset( $view_item_details['title'] ) ) {
	    							$old_title					=	$view_item_details['title'];
	    							$append						=	__( 'WordPress Archives', 'wpv-views' );
	    							$item_details_array[ $k ]['title']= $old_title.' - '.$append;
	    						}
	    					}
	    				}
	    			}
	    		}
	    	}
    	}
    	return $item_details_array;


    }
}
