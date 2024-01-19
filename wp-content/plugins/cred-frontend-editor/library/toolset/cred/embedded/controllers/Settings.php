<?php
final class CRED_Settings_Controller extends CRED_Abstract_Controller
{
    public function disableWizard($get, $post)
    {
        if ( !current_user_can(CRED_CAPABILITY) ) wp_die();
        
		if (isset($post['cred_wizard']) && $post['cred_wizard']=='false')
        {
            $sm=CRED_Loader::get('MODEL/Settings');
            $settings=$sm->getSettings();
            $settings['wizard']=false;
            $sm->updateSettings($settings);
            
            echo "true";
            die(0);
        }
    } 
    
}
