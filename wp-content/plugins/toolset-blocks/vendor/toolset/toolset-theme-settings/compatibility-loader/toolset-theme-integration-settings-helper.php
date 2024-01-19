<?php

class Toolset_Theme_Integration_Settings_Helper{

	const PREFERENCE_KEY = 'toolset_layout_to_cred_form';

	private $object_id = null;
	private $is_views_active = false;
	private $is_layouts_active = false;
	private $is_blocks_active = false;

	/**
	 * @var string
	 * holds one of three values view, view-template, or dd_layouts and indicates from where do we load the saved settings.
	 */
	private $current_object_type = null;

	/**
	 * @var object
	 * holds the complete settings object for either Views CT, WPA or a Layout and can be used to access saved theme options.
	 */
	private $current_settings_object;

	private $current_theme_data;
	private $current_theme_slug;

	public function __construct() {

		$views_condition       = new Toolset_Theme_Settings_Condition_Plugin_Views_Active();
		$this->is_views_active = $views_condition->is_met();


		$layouts_condition       = new Toolset_Theme_Settings_Condition_Plugin_Layouts_Active();
		$this->is_layouts_active = $layouts_condition->is_met();

		$blocks_condition = new Toolset_Theme_Settings_Condition_Plugin_Blocks_Active();
		$this->is_blocks_active = $blocks_condition->is_met();

	}

	public function get_current_object_type() {
		return $this->current_object_type;
	}

	public function is_layouts_active() {
		return $this->is_layouts_active;
	}

	public function is_blocks_active() {
		return $this->is_blocks_active;
	}

	public function is_views_active() {
		return $this->is_views_active;
	}

	public function set_current_theme_data( $theme_data ) {
		$this->current_theme_data = $theme_data;
		$this->current_theme_slug = $theme_data['theme_slug'];
	}

	public function get_current_theme_data() {
		return $this->current_theme_data;
	}

	public function get_current_theme_slug() {
		return $this->current_theme_slug;
	}

	/**
	 * @param null $post_id
	 * @param int|null $object_id
	 * loads the saved settings for either CT, WPA or Layout and can figure out settings even if no params are passed, but requires $_GET['page'] to be present with valid object ID
	 *
	 * @return array theme settings associative array
	 */
	public function load_current_settings_object( $post_id = null, $object_id = null ) {
		$object_type = null;

		if( isset( $_POST['layout_preview'] ) ) {
			return $this->handle_is_layout_preview( $_POST['layout_preview'] );
		}

		if ( $object_id  ) {
			$object_type = get_post_type( $object_id );
		} else {
			$arr = $this->get_object_id_and_type( $post_id );
			$object_id = $arr['object_id'];
			$object_type = $arr['object_type'];
		}

		if ( $object_type && $object_id ) {
			$this->current_object_type = $object_type;
			$this->object_id           = $object_id;
			return $this->handle_is_toolset_object();
		}

		return null;
	}

	/**
	 * @param $preview_data
	 *
	 * @return mixed
	 * Set current settings object member variable when it's a Layout preview.
	 * Note that the $preview_data provides raw theme settings, that need to me pushed under the current theme key.
	 * Note that we do not need to care about other themes existing data: just populate the current one.
	 */
	private function handle_is_layout_preview( $preview_data ) {
		$current_settings_object = json_decode( stripslashes( $preview_data ) );
		if ( is_object( $current_settings_object ) ) {
			$current_settings_object = (array) $current_settings_object;
			if (
				array_key_exists( TOOLSET_THEME_SETTINGS_DATA_KEY, $current_settings_object )
				&& is_object( $current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ] )
			) {
				$this->current_settings_object = array(
					TOOLSET_THEME_SETTINGS_DATA_KEY => array(
						$this->current_theme_slug => (array) $current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ]
					)
				);
			}
		}

		return isset( $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ] )
			? $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ]
			: null;
	}

	/**
	 * @return mixed
	 * set settings object member variables depending on the current object type
	 */
	private function handle_is_toolset_object(){

		$this->set_current_settings_object( $this->current_object_type );

		if (
			$this->current_settings_object
			&& is_object( $this->current_settings_object )
		) {
			$this->current_settings_object = get_object_vars( $this->current_settings_object );

			// Avoid fatal errors when using data generated on the beta process
			if (
				array_key_exists( TOOLSET_THEME_SETTINGS_DATA_KEY, $this->current_settings_object )
				&& is_object( $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ] )
			) {
				$this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ] = get_object_vars( $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ] );
			}

			if (
				array_key_exists( TOOLSET_THEME_SETTINGS_DATA_KEY, $this->current_settings_object )
				&& array_key_exists( $this->current_theme_slug, $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ] )
				&& is_object( $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ] )
			) {
				$this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ] = get_object_vars( $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ] );
			}
		}

		return isset( $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ] )
			? $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ]
			: null;
	}

	/**
	 * @param $current_settings_type
	 * @return current_settings_object
	 * Set current settings object depends on settings type
	 */
	public function set_current_settings_object( $current_settings_type ) {
		switch ( $current_settings_type ) {
			case 'view':
				$this->current_settings_object = apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $this->object_id );
				break;
			case 'view-template':
				$this->current_settings_object = get_post_meta( $this->object_id, '_views_template_theme_settings', true );
				break;
			case 'dd_layouts':
				$this->current_settings_object = apply_filters( 'ddl-get_layout_settings', $this->object_id, true );
				if ( is_object( $this->current_settings_object ) === false ) {
					$this->current_settings_object = null;
				}
				break;
		}

		return $this->current_settings_object;
	}

	/**
	 * @return bool
	 * check if we have a object_type and an object_id and in case it's a CT if can be assigned, if not then we might avoid loading settings
	 */
	public function check_if_content_template_and_cannot_be_assigned(){

		if( isset( $_GET['page'] ) && $_GET['page'] === 'ct-editor' && isset( $_GET['ct_id'] ) ){
			$object_id = $_GET['ct_id'];
			$_view_loop_id = (int) get_post_meta( $object_id, '_view_loop_id', true );
			return $_view_loop_id !== 0;
		}

		return false;
	}

	/**
	 * Get the ID of the CT assigned to a given post.
	 *
	 * @param int $post_id
	 * @return int|false
	 * @todo Make sure that we adjust this once the decision mechanism is done!.
	 */
	public function get_assigned_content_template_id( $post_id ) {
		// apply_filters( 'wpv_content_template_for_post', '', get_post( $post_id ) );
		// See the code on conditional CTs and wait until it gets merged.
		// Note that it returns zero when no usage is found. It is consistent :-)
		return apply_filters( 'wpv_content_template_for_post', get_post_meta( $post_id, '_views_template', true ), get_post( $post_id ) );
	}

	/**
	 * @param null $post_id
	 *
	 * @return array
	 * Returns an associative array where 'object_id' is the id of the rendering object (layout, ct, wpa) and 'object_type' its type being (dd_layouts, view-template, view)
	 */
	private function get_object_id_and_type( $post_id = null ) {

		// in front-end first check query string, in case if we are providing resource ID there - Use it!
		$id_from_query_string = $this->get_object_data_from_query_string();
		if ( $id_from_query_string ) {
			return $id_from_query_string;
		}

		// return layout object and type if we are on Layouts page
		if ( null !== $this->get_object_and_type_for_layout() ){
			return $this->get_object_and_type_for_layout();
		}

		$post_id = $this->get_current_post_id( $post_id );
		$object_data_from_post_id = $this->get_object_data_from_post_id( $post_id );

		if ( null != $object_data_from_post_id ) {
			$object_type = $object_data_from_post_id['object_type'];
			$object_id   = $object_data_from_post_id['object_id'];
		}

		$object_data_from_page = $this->get_object_data_from_page();
		if ( null != $object_data_from_page ) {
			$object_type = $object_data_from_page['object_type'];
			$object_id   = $object_data_from_page['object_id'];
		}


		if ( ! isset( $object_id ) || !$object_id ) {
			$object_id   = $this->fetch_queried_object_id();
			$object_type = $this->fetch_queried_object_type( $object_id );
		}

		return array( 'object_id' => $object_id, 'object_type' => $object_type );
	}

	/**
	 *
	 */
	public function get_object_data_from_query_string(){

		if ( is_admin() ) {
			return null;
		}

		// return layout id if provided in url
		$layout_id = $this->layout_id_provided_in_query_string();
		if ( $layout_id ) {
			return array( 'object_id' => $layout_id, 'object_type'=>'dd_layouts');
		}

		// return CT id if provided in url
		$ct_id = $this->content_template_id_provided_in_query_string();
		if ( $ct_id ) {
			return array( 'object_id' => $ct_id, 'object_type'=>'view-template');
		}

		// return View id if provided in url
		$ct_id = $this->content_template_slug_provided_in_query_string();
		if ( $ct_id ) {
			return array( 'object_id' => $ct_id, 'object_type'=>'view');
		}

		return null;

	}

	public function content_template_slug_provided_in_query_string() {

		if ( isset( $_GET ) && isset( $_GET['view-template'] ) ) {
			$ct_id = apply_filters( 'wpv_get_template_id_by_name', 0, $_GET['view-template']  );
			return (int) $ct_id;
		}

		return false;
	}

	public function content_template_id_provided_in_query_string() {

		if ( isset( $_GET ) && isset( $_GET['content-template-id'] ) && is_numeric( $_GET['content-template-id'] ) ) {
			return (int) $_GET['content-template-id'];
		}

		return false;
	}

	public function layout_id_provided_in_query_string() {

		if ( isset( $_GET ) && isset( $_GET['layout_id'] ) && is_numeric( $_GET['layout_id'] ) ) {
			return (int) $_GET['layout_id'];
		}

		return false;
	}

	/**
	 * Try to get object id and type for current page
	 * @return array|null
	 */
	public function get_object_data_from_page() {
		if ( isset( $_GET['page'] ) ) {
			$object_id = null;
			$object_type = null;
			switch ( $_GET['page'] ) {
				case 'view-archives-editor':
					$object_id   = (int) $_GET['view_id'];
					$object_type = 'view';
					break;
				case 'ct-editor':
					$object_id   = (int) $_GET['ct_id'];
					$object_type = 'view-template';
					break;
				case 'dd_layouts_edit':
					$object_id   = (int) $_GET['layout_id'];
					$object_type = 'dd_layouts';
					break;
			}
			if ( $object_id && $object_type ) {
				return array( 'object_id' => $object_id, 'object_type' => $object_type );
			}
		}
		return null;
	}

	/**
	 * Try to get object id and type from post ID
	 * @param $post_id
	 *
	 * @return array|null
	 */
	public function get_object_data_from_post_id( $post_id ) {
		if ( $post_id ) {
			if ( $this->is_blocks_active ) {
				$object_id = $post_id;
				if ( 'view-template' !== get_post_type( $post_id ) ) {
					$object_id = $this->get_assigned_content_template_id( $post_id );
				}
				$object_type = 'view-template';
			} elseif ( $this->is_layouts_active ) {
				$object_type = 'dd_layouts';
				$layout_slug = get_post_meta( (int) $post_id, WPDDL_LAYOUTS_META_KEY, true );
				$object_id   = apply_filters( 'ddl-get_layout_id_by_slug', null, $layout_slug );
			} elseif ( $this->is_views_active ) {
				$object_id = $post_id;
				if ( 'view-template' !== get_post_type( $post_id ) ) {
					$object_id = $this->get_assigned_content_template_id( $post_id );
				}
				$object_type = 'view-template';
			}

			return array( 'object_type' => $object_type, 'object_id' => $object_id );
		}

		return null;
	}

	/**
	 * Returns layout object ID and type when we are on Layouts edit page
	 * @return array|null
	 */
	public function get_object_and_type_for_layout() {

		if ( isset( $_GET['layout_id'] ) ) {
			$object_id   = (int) $_GET['layout_id'];
			$object_type = 'dd_layouts';

			return array( 'object_id' => $object_id, 'object_type' => $object_type );
		}
		return null;
	}

	/**
	 * Return current post ID
	 * @param $post_id
	 *
	 * @return int
	 */
	public function get_current_post_id( $post_id ) {

		if ( ! $post_id && isset( $_GET['post'] ) ) {
			$post_id = (int) $_GET['post'];
		} elseif ( ! $post_id && ( is_single() || is_page() ) ) {
			$post_id = (int) get_the_ID();
		}

		return $post_id;
	}

	/**
	 * @since 2.5
	 * Returns the ID for the queried object either a Layout or CT.
	 */
	public function fetch_queried_object_id() {
		$object_id = null;
		if ( $this->is_blocks_active ) {
			if ( is_archive() || is_search() || is_home() || is_tax() ) {
				$object_id = apply_filters( 'wpv_filter_wpv_get_current_archive', null );
			}

			if ( is_single() ) {
				global $post;
				$object_id = $this->get_assigned_content_template_id( $post->ID );
			}
		} elseif ( $this->is_layouts_active ) {
			$object_id = apply_filters( 'ddl-rendered_layout_id', null );
		} elseif ( $this->is_views_active ) {
			if ( is_archive() || is_search() || is_home() || is_tax() ) {
				$object_id = apply_filters( 'wpv_filter_wpv_get_current_archive', null );
			}

			if ( is_single() ) {
				global $post;
				$object_id = $this->get_assigned_content_template_id( $post->ID );
			}
		}

		return $object_id;
	}

	/**
	 * @since 2.5
	 * @var $object_id - Layout, CT or WPA ID to fetch its type
	 * @return null|string
	 * Returns the post type for the queried object.
	 */
	public function fetch_queried_object_type( $object_id ) {
		if ( ! empty( $object_id ) ) {
			return get_post_type( $object_id );
		}

		return null;
	}

	/**
	 * @since 2.5
	 * @return bool
	 * checks whether the settings object has a toolset_theme_settings property
	 */
	public function has_theme_settings() {
		return (
			is_array( $this->current_settings_object )
			&& isset( $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ] )
		);
	}

	/**
	 * @since 2.5
	 * @return bool
	 * checks whether the settings object has a specific settings key
	 */
	public function has_theme_setting_by_key( $key ) {
		return (
			$this->has_theme_settings()
			&& array_key_exists( $key, $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ] )
		);
	}

	public function get_object_id(){
		return $this->object_id;
	}

	public function get_current_settings_object(){
		return $this->current_settings_object;
	}

	public function get_current_settings(){
		return $this->has_theme_settings()
			? $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ]
			: null;
	}

	public function get_user_visibility_preference_for_gui(){
		return (
			$this->has_theme_setting_by_key(self::PREFERENCE_KEY )
			&& $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ][self::PREFERENCE_KEY]
		) ? $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ][self::PREFERENCE_KEY]
		: null;
	}

	public function get_current_settings_by_key($key){
		return (
			$this->has_theme_settings()
			&& isset($this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ][$key] )
		) ? $this->current_settings_object[ TOOLSET_THEME_SETTINGS_DATA_KEY ][ $this->current_theme_slug ][$key]
		: null;
	}
}
