<?php

/**
 * Class WPDD_Layouts_Users_Profiles
 * @since 2.4.3
 * Use it as a singleton with Toolset_Singleton_Factory::get( 'WPDD_Layouts_Users_Profiles' );
 */
class WPDD_Layouts_Users_Profiles implements WPDD_Layouts_Users_Profiles_Interface{

    protected $user_option = 'users_options';
    private $update_option = 'updated_profiles';
    protected $current_user;
    protected $wp_roles;
    protected $ddl_users_settings;
	protected $default_cap = DDL_EDIT;
    protected $wp_relative_cap = 'manage_options';

    protected $perms_to_pages = array(
        'admin.php?page=dd_layouts&amp;new_layout=true' => DDL_CREATE,
        'dd_layouts_edit' => DDL_EDIT,
        'dd_layouts' => DDL_EDIT,
        'dd_layouts_debug' => DDL_EDIT,
        'dd_tutorial_videos' => DDL_EDIT,
        'dd_layouts_troubleshoot' => DDL_EDIT,
        'toolset-settings' => DDL_EDIT
    );

	public function __construct( WP_Roles $wp_roles = null, WP_User $current_user = null ){
		$this->wp_roles = $wp_roles;
		$this->current_user = $current_user;
        $this->ddl_users_settings = new WPDDL_Options_Manager( $this->get_user_option() );
    }

    public function get_user_option(){
		return $this->user_option;
    }

    public function add_hooks(){
	    add_filter( 'wpcf_access_custom_capabilities', array( $this, 'wpddl_layouts_capabilities'), 12, 1 );
	    // clean up the database when deactivate plugin
	    //register_activation_hook( WPDDL_ABSPATH . DIRECTORY_SEPARATOR . 'dd-layouts.php', array(&$this, 'add_caps') );
	    add_action( 'init', array( $this, 'add_caps'), 99 );
	    register_deactivation_hook( WPDDL_ABSPATH . DIRECTORY_SEPARATOR . 'dd-layouts.php', array( $this, 'disable_all_caps') );
	    add_action( 'profile_update', array( $this, 'clean_the_mess_in_nonadmin_user_caps' ), 10, 1 );
    }

    public function get_wp_relative_cap(){
    	return $this->wp_relative_cap;
    }

    public function get_perms_to_page( ){
		return $this->perms_to_pages;
    }

    public function get_label(){
		return __( 'Layouts capabilities', 'wpcf_access' );
    }

	function wpddl_layouts_capabilities( $data ) {
		$wp_roles['label']        = $this->get_label();
		$wp_roles['capabilities'] = static::ddl_get_capabilities();
		$data[]                   = $wp_roles;

		return $data;
	}

	/**
	 * @return array
	 * Cannot be turned into a member method since Access uses it statically in wpcf_access_layouts_capabilities API function
	 */
	public static function ddl_get_capabilities(){
		return array(
			DDL_CREATE => "Create layouts",
			DDL_ASSIGN => "Assign layouts to content",
			DDL_EDIT => "Edit layouts",
			DDL_DELETE => "Delete layouts"
		);
	}


    public function get_cap_for_page( $slug ){
		$permissions = $this->get_perms_to_page( );
        return isset( $permissions[$slug] ) ? $permissions[$slug] : $this->default_cap;
    }

	/**
	 * @return void
	 */
    public function add_caps(){

        if( $this->ddl_users_settings->get_options($this->update_option) === true ){
            return;
        }

        if ( ! $this->wp_roles || ! is_object( $this->wp_roles ) ) {
	        $this->wp_roles = new WP_Roles();
        }

        $ddl_capabilities = array_keys( static::ddl_get_capabilities() );

        $this->update_roles( $this->wp_roles, $ddl_capabilities );

        $this->update_super_admins_caps( $ddl_capabilities );
        
        // We need to refresh $current_user caps to display the entire Layouts menu
        $this->update_current_user_caps( $ddl_capabilities );

        $this->ddl_users_settings->update_options( $this->update_option, true, true );
    }

	/**
	 * @param WP_Roles $wp_roles
	 * @param array $ddl_capabilities
	 */
    protected function update_roles( WP_Roles $wp_roles, $ddl_capabilities = array() ){
	    $roles = $wp_roles->get_names();
	    foreach ( $roles as $current_role => $role_name ) {
		    $capability_can = apply_filters( 'ddl_capability_can', $this->get_wp_relative_cap() );
		    if ( isset( $wp_roles->roles[ $current_role ][ 'capabilities' ][ $capability_can ] ) ) {
			    $role = get_role( $current_role );
			    if ( isset( $role ) && is_object( $role ) ) {
				    for ( $i = 0, $caps_limit = count( $ddl_capabilities ); $i < $caps_limit; $i ++ ) {

					    if ( ! isset( $wp_roles->roles[ $current_role ][ 'capabilities' ][ $ddl_capabilities[ $i ] ] ) ) {
						    $role->add_cap( $ddl_capabilities[ $i ] );

					    }
				    }
			    }

		    }
	    }
    }

	/**
	 * @param array $ddl_capabilities
	 * Set new caps for all Super Admins
	 * Note that on non-multisite, get_super_admins might return false positives:
	 * https://developer.wordpress.org/reference/functions/get_super_admins/
	 */
    public function update_super_admins_caps( $ddl_capabilities = array() ){
	    if ( is_multisite() ) {
		    $super_admins = get_super_admins();
		    foreach ( $super_admins as $admin ) {
			    $updated_current_user = $this->create_user( $admin );
			    $this->add_caps_to_user( $updated_current_user, $ddl_capabilities );
		    }
	    }
    }

    public function add_caps_to_user( WP_User $updated_current_user, $ddl_capabilities = array() ){
	    for ( $i = 0, $caps_limit = count( $ddl_capabilities ); $i < $caps_limit; $i ++ ) {
		    $updated_current_user->add_cap( $ddl_capabilities[ $i ] );
	    }
    }

	public function add_caps_to_current_user( WP_User $updated_current_user, $ddl_capabilities = array() ){
		for ( $i = 0, $caps_limit = count( $ddl_capabilities ); $i < $caps_limit; $i ++ ) {
			if ( $updated_current_user->has_cap($ddl_capabilities[$i]) ) {
				$this->current_user->add_cap($ddl_capabilities[$i]);
			}
		}
	}

	/**
	 * @param array $ddl_capabilities
	 */
    protected function update_current_user_caps( $ddl_capabilities = array() ){
	    // If $current_user has not been updated yet with the new capabilities, but it's a valid user
	    if ( $this->current_user instanceof WP_User) {

		    // Insert the capabilities for the current execution
		    $updated_current_user = $this->create_user( $this->current_user->ID );

		    $this->add_caps_to_current_user( $updated_current_user, $ddl_capabilities );

		    // Refresh $current_user->allcaps
		    $this->current_user->get_role_caps();
	    }
    }
	
	/**
	 * In WPDD_Layouts_Users_Profiles::add_caps() we're adding extra capabilities to superadmins.
	 *
	 * When the superadmin status is revoked, we need to take those caps back, otherwise we might create a security
	 * issue.
	 *
	 * This is a temporary workaround for toolsetcommon-248 inspired by types-768 until a better solution is provided.
	 *
	 * @param int|WP_User $user ID of the user or a WP_User instance that is currently being edited.
	 * @since 2.0.3
	 */
	public function clean_the_mess_in_nonadmin_user_caps( $user ) {
		
		if( ! $user instanceof WP_User ) {
			$user = $this->create_user( $user );
			if( ! $user->exists() ) {
				return;
			}
		}

		// True if the user is network (super) admin. Also returns True if network mode is disabled and the user is an admin.
		$is_superadmin = is_super_admin( $user->ID );

		if( ! $is_superadmin ) {
			// We'll remove the extra Types capabilities. If the user has a role that adds those capabilities, nothing
			// should change for them.
			$ddl_get_capabilities = array_keys( static::ddl_get_capabilities() );
			foreach( $ddl_get_capabilities as $capability ) {
				$user->remove_cap( $capability );
			}
		}

	}

	/**
	 *
	 */
    public function disable_all_caps(){

        if ( ! $this->wp_roles instanceof WP_Roles ) {
	        $this->wp_roles = new WP_Roles();
        }

        $ddl_capabilities = array_keys( static::ddl_get_capabilities() );

        $this->remove_role_caps( $this->wp_roles, $ddl_capabilities );

        $this->remove_super_admin_caps( $ddl_capabilities );

        $this->ddl_users_settings->update_options( $this->update_option, false, true );

    }

	/**
	 * @param WP_Roles $wp_roles
	 * @param array $ddl_capabilities
	 */
    protected function remove_role_caps( WP_Roles $wp_roles, $ddl_capabilities = array() ){
	    foreach ( $ddl_capabilities as $cap ) {
		    foreach (array_keys($wp_roles->roles) as $role) {
			    $wp_roles->remove_cap($role, $cap);
		    }
	    }
    }

	/**
	 * @param array $ddl_capabilities
	 * Remove caps for all Super Admin
	 * Note that on non-multisite, get_super_admins might return false positives:
	 * https://developer.wordpress.org/reference/functions/get_super_admins/
	 */
    public function remove_super_admin_caps( $ddl_capabilities = array() ){
	    if ( is_multisite() ) {
		    $super_admins = get_super_admins();
		    foreach ( $super_admins as $admin ) {
			    $user = $this->create_user( $admin );
			    $this->remove_user_caps( $user, $ddl_capabilities );
		    }
	    }
    }

    public function remove_user_caps( WP_User $user, $ddl_capabilities = array() ){
	    for ( $i = 0, $caps_limit = count( $ddl_capabilities ); $i < $caps_limit; $i ++ ) {
		    $user->remove_cap( $ddl_capabilities[ $i ] );
	    }
    }

    public function create_user( $role_or_user ){
    	return new WP_User( $role_or_user );
    }

    public static function user_can_create(){
        return current_user_can( DDL_CREATE );
    }

    public static function user_can_assign(){
        return current_user_can( DDL_ASSIGN );
    }

    public static function user_can_edit(){
        return current_user_can( DDL_EDIT );
    }

	public static function user_can_edit_content_layout($layout_id){

		$is_private = WPDD_Utils::is_private( $layout_id );

		if( true === $is_private ){
			return WPDD_Layouts_Users_Profiles_Private::user_can_edit();
		}

		return current_user_can( DDL_EDIT );
	}

    public static function user_can_delete(){
        return current_user_can( DDL_DELETE );
    }
}