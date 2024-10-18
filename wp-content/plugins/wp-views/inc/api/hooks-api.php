<?php
/**
 * Public filter hook API to be used by other Toolset plugins.
 *
 * In optimal case, all interaction with Views would happen through these hooks.
 */
class WPV_API_Legacy {

	protected $filter_hooks = array();

	public function __construct() {
		$this->filter_hooks = array(
			array( 'get_setting', 2 ),
			array( 'update_setting', 3 ),
			array( 'get_posts_by_content_template', 2 ),
			array( 'duplicate_content_template', 4 ),
			array( 'duplicate_wordpress_archive', 4 ),
			array( 'duplicate_view', 4 ),
			array( 'get_template_id_by_name', 2 )
		);
	}

	public function initialize() {
		$this->register_hooks( $this->filter_hooks );
	}

	/**
	 * Register all API hooks.
	 *
	 * Filter hooks are defined by their name and number of arguments. Each filter gets the wpv_prefix.
	 * Name of the handler function equals filter name.
	 *
	 * @param array $filter_hooks Array of two-element arrays, where first element is hook name and the secon one is
	 *     number of arguments they accept.
	 * @since 1.12
	 */
	protected function register_hooks( $filter_hooks ) {
		foreach( $filter_hooks as $filter_hook ) {
			$hook_name = $filter_hook[0];
			$argument_count = $filter_hook[1];
			add_filter( 'wpv_' . $hook_name, array( $this, $hook_name ), 10, $argument_count );
		}
	}


	/**
	 * Retrieve a setting value from WPV_Settings.
	 *
	 * @param mixed $default_value Return value when the setting is not recognized.
	 * @param string $setting_name Name of the setting.
	 * @return mixed If the setting exists (it has some value or WPV_Settings has either default value
	 *     or a custom getter defined for it), return the value from WPV_Settings. Otherwise fall back to $default_value.
	 * @since 1.12
	 */
	public function get_setting( $default_value, $setting_name ) {
		$settings = WPV_Settings::get_instance();
		if( !$settings->has_setting( $setting_name ) ) {
			return $default_value;
		}
		return $settings[ $setting_name ];
	}


	/**
	 * Update a setting value in WPV_Settings.
	 *
	 * @param mixed $default_value Return value when the filter is not applied.
	 * @param string $setting_name Name of the setting.
	 * @param mixed $setting_value New value.
	 * @return mixed Value stored in WPV_Settings after the update (as it is possible $setting_value will be changed or
	 *    discarded by some custom setter in WPV_Settings).
	 * @since 1.12
	 */
	public function update_setting(
		/** @noinspection PhpUnusedParameterInspection */ $default_value, $setting_name, $setting_value
	) {
		$settings = WPV_Settings::get_instance();
		$settings[ $setting_name ] = $setting_value;
		$settings->save();
		return $settings[ $setting_name ];
	}

	/**
	 * Get the IDs of all single posts that got assigned a given Content Template.
	 *
	 * @param array $default_value
	 * @param int   $content_template_id
	 *
	 * @return mixed Array of post IDs using this Content Template, $default_value on failure.
	 *
	 * @since 2.4.1
	 */
	public function get_posts_by_content_template( $default_value, $content_template_id ) {
		$content_template_id = intval( $content_template_id );
		if ( ! $content_template_id > 0 ) {
			return $default_value;
		}

		$content_template = WPV_Content_Template::get_instance( $content_template_id );
		if ( null == $content_template ) {
			return $default_value;
		}

		$posts_using_ct = $content_template->get_posts_using_this( '*', 'flat_array' );
		return $posts_using_ct;
	}



	/**
	 * Duplicate a WordPress archive and return ID of the duplicate.
	 *
	 * Note that this may also involve duplication of it's loop template. Refer to WPV_View_Base::duplicate() for
	 * detailed description.
	 *
	 * @param mixed $default_result Value to return on error.
	 * @param int $original_wpa_id ID of the original WPA. It must exist and must be a WPA.
	 * @param string $new_title Unique title for the duplicate.
	 * @param bool $adjust_duplicate_title If true, the title might get changed in order to ensure it's uniqueness.
	 *     Otherwise, if $new_title is not unique, the duplication will fail.
	 *
	 * @return mixed|int ID of the duplicate or $default_result on error.
	 *
	 * @since 1.11
	 */
	public function duplicate_wordpress_archive( $default_result, $original_wpa_id, $new_title, $adjust_duplicate_title ) {

		$original_wpa = WPV_View_Base::get_instance( $original_wpa_id );
		if( null == $original_wpa || !( $original_wpa instanceof WPV_WordPress_Archive ) ) {
			return $default_result;
		}

		$duplicate_wpa_id = $original_wpa->duplicate( $new_title, $adjust_duplicate_title );

		return ( false == $duplicate_wpa_id ) ? $default_result : $duplicate_wpa_id;
	}


	/**
	 * Duplicate a View and return ID of the duplicate.
	 *
	 * Note that this may also involve duplication of it's loop template. Refer to WPV_View_Base::duplicate() for
	 * detailed description.
	 *
	 * @param mixed $default_result Value to return on error.
	 * @param int $original_view_id ID of the original View. It must exist and must be a View.
	 * @param string $new_title Unique title for the duplicate.
	 * @param bool $adjust_duplicate_title If true, the title might get changed in order to ensure it's uniqueness.
	 *     Otherwise, if $new_title is not unique, the duplication will fail.
	 *
	 * @return mixed|int ID of the duplicate or $default_result on error.
	 *
	 * @since 1.12
	 */
	public function duplicate_view( $default_result, $original_view_id, $new_title, $adjust_duplicate_title ) {

		$original_view = WPV_View_Base::get_instance( $original_view_id );
		if( null == $original_view || !( $original_view instanceof WPV_View ) ) {
			return $default_result;
		}

		$duplicate_view_id = $original_view->duplicate( $new_title, $adjust_duplicate_title );

		return ( false == $duplicate_view_id ) ? $default_result : $duplicate_view_id;
	}


	/**
	 * Duplicate a Content Template and return ID of the duplicate.
	 *
	 * @param mixed $default_result Value to return on error.
	 * @param int $original_ct_id ID of the original Content Template. It must exist and must be a Content Template.
	 * @param string $new_title Unique title for the duplicate.
	 * @param bool $adjust_duplicate_title If true, the title might get changed in order to ensure it's uniqueness.
	 *     Otherwise, if $new_title is not unique, the duplication will fail.
	 *
	 * @return mixed|int ID of the duplicate or $default_result on error.
	 *
	 * @since 1.12
	 */
	public function duplicate_content_template( $default_result, $original_ct_id, $new_title, $adjust_duplicate_title ) {

		$original_ct = WPV_Content_Template::get_instance( $original_ct_id );
		if( null == $original_ct ) {
			return $default_result;
		}

		$duplicate_ct = $original_ct->duplicate( $new_title, $adjust_duplicate_title );

		return ( null == $duplicate_ct ) ? $default_result : $duplicate_ct->id;
	}


	/**
	 * Get a Content Template ID given its title or slug.
	 *
	 * @param mixed $default_result Value to return on error.
	 * @param string $template_name Title or slug of the Content Template.
	 *
	 * @return int ID of the duplicate or 0 if it does not exist.
	 *
	 * @since 2.5.0
	 */
	public function get_template_id_by_name( $default_result, $template_name ) {
		return WPV_Content_Template_Embedded::get_template_id_by_name( $template_name );
	}

}
