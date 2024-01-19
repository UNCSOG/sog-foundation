<?php

namespace OTGS\Toolset\CRED\Controller;

use OTGS\Toolset\CRED\Controller\Condition\ClassExists;

use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\BasePage;

use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\PostFieldsList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\PostFormEdit;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\PostFormList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\PostFormNew;

use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\RelationshipFormList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\RelationshipFormEdit;

use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\UserFieldsList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\UserFormEdit;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\UserFormList;
use OTGS\Toolset\CRED\Controller\ToolsetFormsMenu\ToolsetFormsMenuPages\UserFormNew;

/**
 * Manage the Forms entries inside the Toolset menu.
 */
class ToolsetFormsMenu {

	const POST_LISTING_SLUG = 'CRED_Forms';
	const USER_LISTING_SLUG = 'CRED_User_Forms';

	const POST_FIELDS_LISTING_SLUG = 'CRED_Fields';
	const USER_FIELDS_LISTING_SLUG = 'CRED_User_Fields';

	/** @var ToolsetFormsMenu\ToolsetFormsMenuFactory */
	private $factory;

	/** @var ClassExists */
	private $class_exists;

	/** @var string */
	private $menu_mode;

	/**
	 * Constructor.
	 *
	 * @param ToolsetFormsMenu\ToolsetFormsMenuFactory $factory
	 * @param ClassExists $class_exists
	 */
	public function __construct(
		ToolsetFormsMenu\ToolsetFormsMenuFactory $factory,
		ClassExists $class_exists
	) {
		$this->factory = $factory;
		$this->class_exists = $class_exists;

		$this->menu_mode = BasePage::MODE_FULL;
	}

	/**
	 * Force a given menu mode instead of relying on whether Forms is embedded or not.
	 *
	 * @param bool|string $force_menu_mode
	 */
	public function initialize( $force_menu_mode = false ) {
		if ( false !== $force_menu_mode ) {
			$this->menu_mode = $force_menu_mode;
		} else {
			add_action( 'init', function() {
				$this->menu_mode = $this->class_exists->is_met( 'CRED_Admin' )
					? BasePage::MODE_FULL
					: BasePage::MODE_EMBEDDED;
			}, 1 );
		}

		add_filter( 'toolset_filter_register_menu_pages', [ $this, 'register_menu_pages' ], 50);
	}

	/**
	 * Get skeleton for menu pages.
	 *
	 * @return mixed[]
	 */
	private function get_menu_pages() {
		return [
			PostFormList::ID => [
				'menu_title' => __( 'Post Forms', 'wp-cred' ),
				'page_title' => __( 'Post Forms', 'wp-cred' ),
			],
			PostFormNew::ID => [
				'menu_title' => __( 'New Post Form', 'wp-cred' ),
				'page_title' => __( 'New Post Form', 'wp-cred' ),
			],
			PostFormEdit::ID => [
				'menu_title' => __( 'Edit Post Form', 'wp-cred' ),
				'page_title' => __( 'Edit Post Form', 'wp-cred' ),
			],
			PostFieldsList::ID => [
				'menu_title' => __( 'Toolset Forms Custom Fields', 'wp-cred' ),
				'page_title' => __( 'Toolset Forms Custom Fields', 'wp-cred' ),
			],
			UserFormList::ID => [
				'menu_title' => __( 'User Forms', 'wp-cred' ),
				'page_title' => __( 'User Forms', 'wp-cred' ),
			],
			UserFormNew::ID => [
				'menu_title' => __( 'New User Form', 'wp-cred' ),
				'page_title' => __( 'New User Form', 'wp-cred' ),
			],
			UserFormEdit::ID => [
				'menu_title' => __( 'Edit User Form', 'wp-cred' ),
				'page_title' => __( 'Edit User Form', 'wp-cred' ),
			],
			UserFieldsList::ID => [
				'menu_title' => __( 'Toolset Forms User Fields', 'wp-cred' ),
				'page_title' => __( 'Toolset Forms User Fields', 'wp-cred' ),
			],
			RelationshipFormList::ID => [
				'menu_title' => __( 'Relationship Forms', 'wp-cred' ),
				'page_title' => __( 'Relationship Forms', 'wp-cred' ),
			],
			RelationshipFormEdit::ID => [
				'menu_title' => __( 'Relationship Form Editor', 'wp-cred' ),
				'page_title' => __( 'Relationship Form Editor', 'wp-cred' ),
			],
		];
	}

	/**
	 * Register the menu pages.
	 *
	 * Each page candidate must provide a valid callback.
	 * - null means that the page will nto be registered,
	 *   which is useful when conditional pages will only be registered on specific modes
	 *     or when accessed by their URL.
	 * - empty callback means that WordPress manages the page content,
	 *   which happens for post/user form edit pages using native post editors.
	 *
	 * In addition, pages must return a slug, whicf is checked by WP to set proper menu current page styles,
	 * and a capability, to ecide whether the current user can access it or not.
	 *
	 * Finally, pages can provide extra actions to take when rendered,
	 * which is usefull for initializing tables or addons, or registering screen options.
	 *
	 * @param mixed[] $pages
	 * @return mixed[]
	 */
	public function register_menu_pages( $pages ) {
		$this->factory->set_mode( $this->menu_mode );

		$pages_candidates = $this->get_menu_pages();

		foreach ( $pages_candidates as $page_id => $page_data ) {
			$page_object = $this->factory->get_page( $page_id );
			if ( null === $page_object ) {
				continue;
			}

			$page_callback = $page_object->get_callback();
			if ( null === $page_callback ) {
				continue;
			}

			$page_object->execute_maybe_current_page();

			$page_data['callback'] = $page_callback;
			$page_data['slug'] = $page_object->get_slug();
			$page_data['capability'] = $page_object->get_capability();

			$pages[] = $page_data;
		}

		return $pages;
	}
}
