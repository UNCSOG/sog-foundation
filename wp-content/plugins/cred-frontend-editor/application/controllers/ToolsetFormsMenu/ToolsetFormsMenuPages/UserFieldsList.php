<?php

namespace OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages;

class UserFieldsList extends BasePage {

	const ID = 'CRED_User_Fields';
	const SLUG = 'CRED_User_Fields';

	/**
	 * Whether the current page meets some conditions to define the callback.
	 *
	 * @return bool
	 */
	protected function meet_conditions() {
		return (
			self::MODE_FULL === $this->mode
			&& static::SLUG == toolset_getget( 'page' )
		);
	}

	/**
	 * Get the callback to show when Forms runs in embedded mode.
	 *
	 * @return callable|null
	 */
	protected function get_embedded_callback() {
		return null;
	}

	/**
	 * Get the callback to show when Forms runs in full mode.
	 *
	 * @return callable|null
	 */
	protected function get_full_callback() {
		return $this->meet_conditions()
			? array( 'CRED_Admin_Helper', 'UserFieldsMenuPage' )
			: null;
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
				$fields_control = new \OTGS\Toolset\CRED\Controller\Forms\User\FieldsControl\Main();
            	$fields_control->initialize();
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
	}

}
