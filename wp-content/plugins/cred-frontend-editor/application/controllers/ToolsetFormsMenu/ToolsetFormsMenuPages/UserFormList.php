<?php

namespace OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages;

class UserFormList extends BasePage {

	const ID = 'CRED_User_Forms';
	const SLUG = 'CRED_User_Forms';

	/**
	 * Get the callback to show when Forms runs in embedded mode.
	 *
	 * @return callable|null
	 */
	protected function get_embedded_callback() {
		return array( 'CRED_Helper', 'UserFormsMenuPage' );
	}

	/**
	 * Get the callback to show when Forms runs in full mode.
	 *
	 * @return callable|null
	 */
	protected function get_full_callback() {
		return array( 'CRED_Admin_Helper', 'UserFormsMenuPage' );
	}

	/**
	 * Maybe execute some extra logic for some specific pages, if conditions are met.
	 */
	public function execute_maybe_current_page() {
		if ( static::SLUG !== toolset_getget( 'page' ) ) {
			return;
		}

		switch ( $this->mode ) {
			case self::MODE_FULL:
				add_action( 'load-toplevel_page_' . static::SLUG, array( $this, 'load_page_callback' ) );
				add_action( 'load-toolset_page_' . static::SLUG, array( $this, 'load_page_callback' ) );
				break;
			case self::MODE_EMBEDDED:
				add_action( 'load-toplevel_page_' . static::SLUG, array( $this, 'load_page_callback' ) );
				add_action( 'load-toolset_page_' . static::SLUG, array( $this, 'load_page_callback' ) );
				break;
		}
	}

	/**
	 * Load page callback.
	 */
	public function load_page_callback() {
		$this->set_posts_per_page_option();
		switch ( $this->mode ) {
			case self::MODE_FULL:
				\CRED_Loader::get( 'TABLE/UserForms' );
				break;
			case self::MODE_EMBEDDED:
				\CRED_Loader::get( 'TABLE/EmbeddedUserForms' );
				break;
		}

	}

}
