<?php
/**************************************************
 *
 * Cred settings model
 **************************************************/

/**
 *
 * $HeadURL: https://www.onthegosystems.com/misc_svn/crud/trunk_new/embedded/models/Settings.php $
 * $LastChangedDate: 2014-08-18 16:03:21 +0200 (lun, 18 ago 2014) $
 * $LastChangedRevision: 26052 $
 * $LastChangedBy: riccardo $
 *
 * @deprecated Use OTGS\Toolset\CRED\Model\Settings instead
 */
class CRED_Settings_Model extends CRED_Abstract_Model implements CRED_Singleton
{

    private $option_name = 'cred_cred_settings';

	public function get_object_fields( $object_field, $include_fields_only = null ) {
		return;
	}

    public function prepareDB()
    {
        $defaults = array(
            'wizard' => 1,
            'cache_notice' => 1,
            'export_settings' => 1,
            'export_custom_fields' => 1,
            'recaptcha' => array(
                'public_key' => '',
                'private_key' => ''
            ),
            'autogeneration_email' => array(
                'subject' => 'Welcome new user',
                'body' => '[username]Your username is: %cuf_username%[/username]\\n[nickname]Your nickname is: %cuf_nickname%[/nickname]\\n[password]Your password is: %cuf_password%[/password]'
            )
        );

        $current_settings = $this->getSettings();
        if ( !isset($settings[ 'dont_load_cred_css' ]) ) {
            $defaults[ 'dont_load_cred_css' ] = 1;
        }

        $defaults = apply_filters('cred_ext_general_settings_options', $defaults);

        $settings = get_option($this->option_name);

        if ( $settings == false || $settings == null ) {
            update_option($this->option_name, $defaults);
        }

    }

    public function getSettings()
    {
        return get_option($this->option_name);
    }

    public function updateSettings( $settings )
    {
        return update_option($this->option_name, $settings);
    }
}
