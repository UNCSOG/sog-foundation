<?php
/**************************************************

Module Manager modules model

**************************************************/

final class ModMan_Modules_Model implements ModMan_Singleton
{

    protected $wpdb = null;
    private $option_name = 'modman_modules';
    
    /**
    * Class constructor
    */     
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }

    public function getWPDB()
    {
        return $this->wpdb;
    }
    
    public function prepareDB()
    {
        /*$defaults=array();
        
        $settings = get_option($this->option_name);
        
        if ($settings==false || $settings==null)
            update_option($this->option_name,$defaults);*/
    }

	public function getModules()
	{
		$modules = get_option( $this->option_name, array() );
		$modules = $this->clear_fields_from_types_groups( $modules );
		return $modules;
	}

	/**
	 * Remove Fields from Types fields groups array
	 * @param $modules
	 *
	 * @return mixed
	 */
	public function clear_fields_from_types_groups ( $modules ){
		foreach( $modules as $module => $module_data ) {
			if ( isset( $module_data['groups']['__fields'] ) ) {
				unset( $modules[ $module ]['groups']['__fields'] );
			}
		}
		return $modules;
	}
    
    public function addNewModule($moduleName, $moduleData)
    {
        $modules=$this->getModules();
        //modman_log($modules);
        $modules[$moduleName]=$moduleData;
        $this->updateModules($modules);
        //modman_log($modules);
    }
    
    public function updateModules($mods)
    {
        if (isset($mods[0]) && $mods[0]==0)
            $mods=array(); // empty
        else
        {
            foreach ($mods as $mod=>$data)
            {
                if (0==$mod && 0==$data)
                {
                    $mods[$mod]=array();
                }
                if (isset($data[MODMAN_MODULE_INFO]))
                {
                    if (isset($data[MODMAN_MODULE_INFO]['description']))
                        // sanitize description
                        $data[MODMAN_MODULE_INFO]['description']=ModuleManager::sanitizeTags(stripslashes($data[MODMAN_MODULE_INFO]['description']));
                }
                else
                {
                    $data[MODMAN_MODULE_INFO]=array();
                }
                $mods[$mod]=$data;
                
            }
        }
        //modman_log($mods);
        return update_option($this->option_name,$mods);
    }
    
    public function addToModule($module_name, $element, $create=false)
    {
        if (empty($module_name)) return;
        
        $modules=$this->getModules();
        if (!isset($modules[$module_name]) && $create)
            $modules[$module_name]=array();
        else if (!$create) return;
        
        if (!isset($modules[$module_name][$element['section']]))
            $modules[$module_name][$element['section']]=array();
        
        $modules[$module_name][$element['section']][]=array('id'=>$element['id'],'title'=>$element['title'],'details'=>$element['details']);
        $this->updateModules($modules);
    }
    
    public function toggleItem($module_name, $element, $set=false)
    {
        if (empty($module_name)) return;
        
        $modules=$this->getModules();
        if (!isset($modules[$module_name]))  return;
        
        if (!$set)
        {
            foreach ($modules[$module_name][$element['section']] as $key=>$elem)
            {
                if ($elem['id']==$element['id'])
                {
                    unset($modules[$module_name][$element['section']][$key]);
                    //break;
                }
            }
        }
        else
        {
             if (!isset($modules[$module_name][$element['section']]))
                $modules[$module_name][$element['section']]=array();
            $modules[$module_name][$element['section']][]=array('id'=>$element['id'],'title'=>$element['title']);
        }
        $this->updateModules($modules);
    }
    
    public function getRegisteredSections()
    {
        $sections = apply_filters('wpmodules_register_sections',array());

        //modman_log($sections);
        self::sortByArbitraryKeys($sections);

	    $sections['demo_content'] = array(
		    'title' => __('Demo Content','module-manager'),
		    'icon' => '',
		    'icon_css' => ''
	    );

        return $sections;
    }
    
    public function getRegisteredItemsPerSection($sections)
    {
        $items=array();
        if (is_array($sections) && !empty($sections))
        {
            foreach ($sections as $id=>$info)
            {
                $tmp=apply_filters('wpmodules_register_items_'.$id,array());
                $items[$id]=array(
                    'info'=>$info,
                    'items'=>$tmp
                    );
            }
        }
        return $items;
    }

    /**
     * @param $inputArray
     * @return mixed
     * the main purpose of this one is to have a certain order during import
     */
    public static function sortByArbitraryKeys(&$inputArray) {
        uksort($inputArray, array(new MM_ArrayUtils, 'sort_array_callback'));
        return $inputArray;
    }
}

class MM_ArrayUtils{
    private $property;
    public static $sort_array = array(
        'types',
	    'm2m_relationships',
        'taxonomies',
        'groups',
        'cred',
        'cred-user',
        'view-templates',
        'views',
        'dd_layouts'
    );
    private $sort_order;


    public function __construct( $property = null ){
        $this->sort_order = array_flip(MM_ArrayUtils::$sort_array);
    }

    public function sort_array_callback( $a, $b){
        return $this->sort_order[$a] - $this->sort_order[$b];
    }
}