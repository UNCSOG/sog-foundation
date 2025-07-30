<?php

/*
 * Class for Layouts shortcodes
 *
 * @since 1.7
 */
Class LayoutsShortcodes{

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );

		// add filter for list of plugins that we can check using [toolset-plugin-active] shortcode
		add_filter( 'layouts_shortcodes_register_list_of_plugins', array( $this, 'register_plugins' ) );
	}

	/**
	 * Class initialization.
	 *
	 * @since 1.7
	 */
	public function init() {
		$this->register_shortcodes();
	}

	/**
	 * Register the shortcodes.
	 *
	 * @since 1.7
	 */
	private function register_shortcodes(){
		// add new shortcodes here
		add_shortcode( 'toolset-plugin-active', array( &$this, 'is_plugin_active' ) );
		add_shortcode( 'toolset-user-role-condition', array( &$this, 'check_user_role' ) );
	}

	/**
	 * Get the roles for the current user, lowercased.
	 *
	 * @return array
	 * @since 1.7
	 * @since 2.6.4 Return an array as users can have multiple roles.
	 */
	private function get_current_user_roles() {
		global $wp_roles;
		global $current_user;
		$current_user = wp_get_current_user();
		$roles = $current_user->roles;
		$return_roles = array();

		foreach ( $roles as $role_candidate ) {
			if ( isset( $wp_roles->role_names[ $role_candidate ] ) ) {
				$return_roles[] = strtolower( translate_user_role( $wp_roles->role_names[ $role_candidate ] ) );
			}
		}

		if ( empty( $return_roles ) ) {
			$return_roles[] = 'guest';
		}

		return $return_roles;
	}

	/**
	 * Show some content depended on user role.
	 *
	 * @param array $atts Sortcode attributes
	 * @param string $content Shortcode content
	 * @since 1.7
	 */
	public function check_user_role( $atts, $content = '' ) {
		$return_status = false;

		extract( shortcode_atts( array( 'roles' => null, 'status' => true ), $atts ) );

		$role_status = ( $status === 'true' || $status === 'yes' ) ? true : false;

		$get_roles = explode( ',', $roles );
		$get_roles = array_map( 'trim', $get_roles );
		$get_roles = array_map( 'strtolower', $get_roles );

		if ( ! empty( $get_roles ) ) {

			$current_user_roles = $this->get_current_user_roles();

			$matching_roles = array_intersect( $current_user_roles, $get_roles );

			if ( count( $matching_roles ) > 0 ) {
				$return_status = true;
			}

			if ( $role_status === $return_status ) {
				return do_shortcode( $content );
			}
		}

		return;
	}

	/**
	 * Register our own plugins in the list to check.
	 *
	 * @return rray
	 * @since 1.7
	 */
	public function register_plugins() {
		$plugins = array(
			'access' => 'TAccess_Loader' ,
			'types' => 'wpcf_bootstrap' ,
			'views' => 'WP_Views',
			'cred' => 'CRED_Loader',
			'maps' => 'Toolset_Addon_Maps_Types',
		);
		return $plugins;
	}

	/**
	 * Shortcode for checking if a set of plugins is active.
	 *
	 * Note that active="true" means that all plugins are active, while
	 * active="false" means that at least one plugin is not active.
	 * If you need to check more than one inactive plugin at once, this shortcode is not for you.
	 *
	 * @param array $atts Sortcode attributes
	 * @param string $content Shortcode content
	 * @since 1.7
	 */
	public function is_plugin_active( $atts, $content = '' ) {
		$active_status = false;
		$registered_plugins = apply_filters( 'layouts_shortcodes_register_list_of_plugins', array() );

		extract( shortcode_atts( array( 'active' => '', 'plugins' => '' ), $atts ) );

		// set check status
		$active = strtolower( $active );
		$plugin_should_be_active = ( $active === 'true' || $active === 'yes' ) ? true : false;

		$get_plugins = explode( ',', $plugins );
		$get_plugins = array_map( 'trim', $get_plugins );

		if ( $get_plugins ) {
			foreach ( $get_plugins as $one_plugin ) {
				if (
					isset( $registered_plugins[ $one_plugin ] )
					&& (
						class_exists( $registered_plugins[ $one_plugin ] )
						|| function_exists( $registered_plugins[ $one_plugin ] )
					)
				) {
					$active_status = true;
				} else {
					$active_status = false;
					// One required plugin is not available
					break;
				}
			}

			if ( $plugin_should_be_active === $active_status ) {
				return do_shortcode( $content );
			}
		}

		return;
	}

}
new LayoutsShortcodes();
