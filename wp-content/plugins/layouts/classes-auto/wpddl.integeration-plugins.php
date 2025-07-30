<?php

/**
 *
 * @description has info about Toolset made theme integration plugins.
 * @since 1.9
 */

class WPDD_Layouts_IntegrationPlugins{
	private $current_theme;
	private $integration_plugins = array(
		'Avada' => array('theme_name'  => 'Avada',
		                 'plugin_name' => 'Toolset Avada Integration',
		                 'doc_link'    => 'https://toolset.com/course-lesson/using-toolset-with-avada/' .
											'?utm_source=plugin' .
											'&utm_campaign=layouts' .
											'&utm_medium=gui'),

		'Cornerstone, for WordPress' => array('theme_name'  => 'Cornerstone',
		                                      'plugin_name' => 'Toolset Cornerstone Integration',
		                                      'doc_link'    => 'https://toolset.com/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts'),

		'Divi' => array('theme_name'  => 'Divi',
		                'plugin_name' => 'Toolset Divi Integration',
		                'doc_link'    => 'https://toolset.com/course-lesson/using-toolset-with-divi/' .
											'?utm_source=plugin' .
											'&utm_campaign=layouts' .
											'&utm_medium=gui'),

		'Genesis' => array('theme_name'  => 'Genesis',
		                   'plugin_name' => 'Toolset Genesis Integration',
		                   'doc_link'    => 'https://toolset.com/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts'),

		'Customizr' => array('theme_name'  => 'Customizr',
		                     'plugin_name' => 'Toolset Customizr Integration',
		                     'doc_link'    => 'https://toolset.com/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts'),

		'Twenty Sixteen' => array('theme_name'  => 'Twenty Sixteen',
		                          'plugin_name' => 'Toolset Twenty Sixteen Integration',
		                          'doc_link'    => 'https://toolset.com/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts'),

		'Twenty Fifteen' => array('theme_name'  => 'Twenty Fifteen',
		                          'plugin_name' => 'Toolset Twenty Fifteen Integration',
		                          'doc_link'    => 'https://toolset.com/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts')
	);
	public $toolset_downloads_link = "https://toolset.com/account/downloads/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts";

	public function __construct() {
		$this->current_theme = wp_get_theme();
	}

	final public static function get_instance() {
		static $instances = array();
		$called_class = get_called_class();
		if( !isset( $instances[ $called_class ] ) ) {
			$instances[ $called_class ] = new $called_class();
		}
		return $instances[ $called_class ];
	}

	public function get_integration_info($theme_name = null){
		$theme_name = trim($theme_name);

		if($theme_name == null){
			$theme_name = $this->current_theme->name;
		}

		if($this->is_theme_integrated($theme_name)){
			return $this->integration_plugins[$theme_name];
		}
		return null;
	}

	public function is_theme_integrated($theme_name = null){
		if($theme_name == null){
			$theme_name = $this->current_theme->name;
		}
		return array_key_exists($theme_name, $this->integration_plugins);
	}

}

WPDD_Layouts_IntegrationPlugins::get_instance();

?>
