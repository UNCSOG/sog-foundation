<?php

namespace OTGS\Toolset\Views\Controller\Cache\Meta;

/**
 * Usermeta cache controller.
 *
 * @since 2.8.1
 */
class User extends Base {

	const VISIBLE_KEY = 'wpv_transient_usermeta_keys_visible512';
	const HIDDEN_KEY = 'wpv_transient_usermeta_keys_hidden512';

	/**
	 * @var array
	 */
	// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
	protected $excluded_visible = array(
		'first_name', 'last_name', 'name', 'nickname', 'description',
		'yim', 'jabber', 'aim',
		'rich_editing', 'comment_shortcuts', 'admin_color',
		'use_ssl', 'show_admin_bar_front',
		'capabilities', 'user_level', 'user-settings',
		'dismissed_wp_pointers', 'show_welcome_panel',
		'dashboard_quick_press_last_post_id', 'managenav-menuscolumnshidden',
		'primary_blog', 'source_domain',
		'closedpostboxes', 'metaboxhidden', 'meta-box-order_dashboard',
		'meta-box-order', 'nav_menu_recently_edited',
		'new_date', 'show_highlight', 'language_pairs',
		'module-manager',
		'screen_layout', 'session_tokens',
		'hide_wpcf_welcome_panel',
	);

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\User\Manager $manager
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\User\Invalidator $invalidator
	 * @since 2.8.1
	 */
	public function __construct(
		\OTGS\Toolset\Views\Controller\Cache\Meta\User\Manager $manager,
		\OTGS\Toolset\Views\Controller\Cache\Meta\User\Invalidator $invalidator
	) {
		$this->manager = $manager;
		$this->invalidator = $invalidator;
	}

}
