<?php
/**
 * Class WPDD_Private_Layout_User_Helper
 * @since: 2.0.2
 * It allows to provide temporary "unfiltered_html" cap to non-super admin users when edit a Private Layout
 */
class WPDD_Private_Layout_User_Helper{
	private $needs_caps_update = false;
	private $current_user = null;

	const DEFAULT_CAP = 'edit_others_pages';
	const GRANT_CAP = 'unfiltered_html';

	/**
	 * WPDD_Private_Layout_User_Helper constructor.
	 * do something if the user can edit Private Layout but he can't save unfiltered html, otherwise don't
	 */
	public function __construct( WP_User $current_user ){
		$this->needs_caps_update = $this->set_user_needs_update();
		$this->setup_current_user( $current_user );
	}

	public function maybe_add_hooks(){
		if( $this->current_user !== null && $this->needs_caps_update ){
			$this->add_hooks();
		}
	}

	public function add_user_has_cap_hook(){
		add_filter( 'user_has_cap', array( $this, 'author_cap_filter' ), 10, 3 );
	}

	public function author_cap_filter( $allcaps, $cap, $args ){

		if( $cap[0] === self::GRANT_CAP && $this->needs_caps_update ){
			$allcaps[$cap[0]] = true;
		}

		return $allcaps;
	}

	/**
	 * store $current_user object in a member var
	 * inspired by https://core.trac.wordpress.org/browser/tags/4.8/src/wp-includes/user.php#L2496
	 */
	private function setup_current_user( WP_User $current_user ){

		if ( property_exists( $current_user, 'ID' ) &&  $current_user->ID !== 0 ) {
			$this->current_user = $current_user;
		}

		// return null in any other case

		return $this->current_user;
	}


	public function get_current_user(){
		return $this->current_user;
	}

	/**
	 * @return bool
	 */
	private function set_user_needs_update(){
		return current_user_can(self::DEFAULT_CAP ) && !current_user_can( self::GRANT_CAP );
	}

	/**
	 * @return bool
	 */
	public function get_user_needs_update(){
		return $this->needs_caps_update;
	}

	protected function add_hooks() {
		add_filter( 'map_meta_cap', array($this, 'add_unfiltered_html_capability_to_admins' ), 1, 3 );
	}

	/**
	 * Enable unfiltered_html capability for Editors.
	 *
	 * @param  array  $caps    The user's capabilities.
	 * @param  string $cap     Capability name.
	 * @param  int    $user_id The user ID.
	 * @return array  $caps    The user's capabilities, with 'unfiltered_html' potentially added.
	 */
	function add_unfiltered_html_capability_to_admins( $caps, $cap, $user_id ) {

		if ( self::GRANT_CAP === $cap && $this->get_user_needs_update() ) {

			$caps = array( self::GRANT_CAP );

		}

		return $caps;

	}
}