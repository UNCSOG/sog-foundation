<?php
final class ModMan_Modules_Controller
{
    protected function redirectTo($url)
    {
        /*header("location: $url");*/
        wp_redirect($url);
        exit();
    }
    
    public function saveModules($get,$post)
    {
        if (
            isset($post['modman-save-modules-field']) && 
            wp_verify_nonce( $post['modman-save-modules-field'], 'modman-save-modules-action' ) &&
            isset($post['modules'])
        )
        {
            ModMan_Loader::get('MODEL/Modules')->updateModules($post['modules']);
            echo 'true';
        }
        else
            echo 'false';
        die();
    }
    
    public function addToModule($get,$post)
    {
        if (
            isset($post['modman-add-to-module-field']) && 
            wp_verify_nonce( $post['modman-add-to-module-field'], 'modman-add-to-module-action' ) &&
            isset($post['mod_name']) && isset($post['elem']) && is_array($post['elem'])
        )
        {
            $create=false;
            if (isset($post['mod_creat']))
                $create=true;
            ModMan_Loader::get('MODEL/Modules')->addToModule($post['mod_name'],$post['elem'],$create);
            echo 'true';
        }
        else
            echo 'false';
        die();
    }
    
    public function toggleItem($get,$post)
    {
        if (
            isset($post['modman-add-to-module-field']) && 
            wp_verify_nonce( $post['modman-add-to-module-field'], 'modman-add-to-module-action' ) &&
            isset($post['mod_name']) && isset($post['elem']) && is_array($post['elem']) &&
            isset($post['mod_set'])
        )
        {
            $set=false;
            if ('1'==$post['mod_set'])
                $set=true;
                
            ModMan_Loader::get('MODEL/Modules')->toggleItem($post['mod_name'],$post['elem'], $set);
            echo 'true';
        }
        else
            echo 'false';
        die();
    }
    
    public function exportModule($get,$post)
    {
        if (isset($post['module']))
        {
            $modules=ModMan_Loader::get('MODEL/Modules')->getModules();
            $modules[$post['module']['name']]=$post['module']['module'];
            ModMan_Loader::get('MODEL/Modules')->updateModules($modules);
            
            // export module
            $result=ModuleManager::exportModule($post['module']['name']);
        }
        die();
    }
}
