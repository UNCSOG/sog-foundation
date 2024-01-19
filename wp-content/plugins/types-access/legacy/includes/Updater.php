<?php
/*
*   Access updater
*/

final class Access_Updater
{

    private static $db_ver=false;
    
    public static function maybeUpdate()
    {
	    $access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();

	    self::$db_ver=$access_settings->getAccessVersion();
        
        if (!self::$db_ver)
            self::$db_ver=array();
        
        //taccess_log(array('updater', self::$db_ver, TACCESS_VERSION));
        
        if (!isset(self::$db_ver[TACCESS_VERSION]))
            self::update();
    }
    
    private static function update()
    {
        if (!isset(self::$db_ver['1.1.6']))
            // update to 1.1.6
            self::update_to_116();
	    $access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
        self::$db_ver = array_merge(self::$db_ver, array(TACCESS_VERSION => 1));
        $access_settings->updateAccessVersion( self::$db_ver );
    }
    
    // 1.1.6 uses its own DB options to save all settings and does not depend on Types options
    private static function update_to_116()
    {
        $access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();

        // Post Types
        $access_types = $access_settings->get_types_settings( true, true );
        $wpcf_types = $access_settings->getWpcfTypes();
        
        // merge with Access settings saved in Types tables, since Access is standalone now
        foreach ($wpcf_types as $t=>$d)
        {
            if (isset($d['_wpcf_access_capabilities']))
            {
                if (!isset($access_types[$t]))
                    $access_types[$t] = $d['_wpcf_access_capabilities'];
                unset($wpcf_types[$t]['_wpcf_access_capabilities']);
            }
        }
        $access_settings->updateWpcfTypes($wpcf_types);
        $access_settings->updateAccessTypes($access_types);
        unset($wpcf_types);
        unset($access_types);
        
        // Taxonomies
        $access_taxonomies = $access_settings->get_tax_settings( true, true );
        $wpcf_taxonomies = $access_settings->getWpcfTaxonomies();
        
        // merge with Access settings saved in Types tables, since Access is standalone now
        foreach ($wpcf_taxonomies as $t=>$d)
        {
            if (isset($d['_wpcf_access_capabilities']))
            {
                if (!isset($access_taxonomies[$t]))
                    $access_taxonomies[$t] = $d['_wpcf_access_capabilities'];
                unset($wpcf_taxonomies[$t]['_wpcf_access_capabilities']);
            }
        }
	    $access_settings->updateWpcfTaxonomies($wpcf_taxonomies);
	    $access_settings->updateAccessTaxonomies($access_taxonomies);
        unset($wpcf_taxonomies);
        unset($access_taxonomies);
        
        self::$db_ver = array_merge(self::$db_ver, array('1.1.6' => 1));
    }
}