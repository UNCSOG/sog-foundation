<?php
/**
 * Compatibility class for Genesis theme
 * Class Toolset_Compatibility_Theme_genesis
 */
class Toolset_Compatibility_Theme_genesis extends Toolset_Compatibility_Theme_Handler {
	/**
	 * Settings for the secondary sidebar
	 *
	 * @var array
	 */
	const LAYOUT_SIDEBAR_PRIMARY = [
		'sidebar_title_sufix' => '',
		'sidebar_context_sufix' => '-primary',
		'sidebar_actions' => '',
	];
	/**
	 * Settings for the primary sidebar
	 *
	 * @var array
	 */
	const LAYOUT_SIDEBAR_SECONDARY = [
		'sidebar_title_sufix' => '-alt',
		'sidebar_context_sufix' => '-secondary',
		'sidebar_actions' => '_alt',
	];

	/**
	 * Adds frontend styles
	 *
	 * @param array<WPDDL_style>
	 * @return array
	 */
	public function add_register_styles( $styles ) {
		$styles['genesis-overrides-css'] = new Toolset_Style( 'genesis-overrides-css', TOOLSET_THEME_SETTINGS_URL . '/res/css/themes/genesis-overrides.css', array(), TOOLSET_THEME_SETTINGS_VERSION, 'screen' );

		return $styles;
	}

	public function frontend_enqueue() {
		do_action( 'toolset_enqueue_styles', array( 'genesis-overrides-css' ) );
	}

	protected function run_hooks() {
		add_action( 'get_header', array( $this, 'disable_title' ) );
		add_action( 'get_header', array( $this, 'disable_meta_before_content' ) );
		add_action( 'get_header', array( $this, 'disable_meta_after_content' ) );
		add_action( 'get_header', array( $this, 'disable_pagination' ) );

		add_filter( 'wc_get_template', [ $this, 'wc_get_template' ], 10, 5 );
		add_action( 'wp', [ $this, 'disable_sidebar' ] );
		add_action( 'toolset_genesis_woocommerce_before_main_content', [ $this, 'render_genesis_sidebar_before' ] );
		add_action( 'toolset_genesis_woocommerce_after_main_content', [ $this, 'render_genesis_sidebar_after' ] );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
		add_action( 'woocommerce_after_main_content', [ $this, 'genesis_entry_footer' ] );

		add_filter( 'toolset_add_registered_styles', array( &$this, 'add_register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );
	}

	/**
	 * Get value from theme integration settings filter and disable title for current page if option is enabled
	 */
	public function disable_title() {
		$toolset_disable_title = apply_filters( 'toolset_theme_integration_get_setting', null, 'toolset_disable_title' );

		if (
			"1" == $toolset_disable_title
			&& ( is_single() || is_page() )
		) {
			remove_action( 'genesis_entry_header', 'genesis_do_post_title' );
			return true;
		}
		return false;
	}

	/**
	 * Get value from theme integration settings filter and disable meta before content for current page if option is enabled
	 */
	public function disable_meta_before_content() {
		$toolset_disable_meta_before_content = apply_filters( 'toolset_theme_integration_get_setting', null, 'toolset_disable_meta_before_content' );

		if (
			"1" == $toolset_disable_meta_before_content
			&& ( is_single() || is_page() )
		) {
			remove_action( 'genesis_before_post_content', 'genesis_post_info' );
			remove_action( 'genesis_entry_header', 'genesis_post_info', 12 );
			return true;
		}
		return false;
	}

	/**
	 * Get value from theme integration settings filter and disable met after content for current page if option is enabled
	 */
	public function disable_meta_after_content() {
		$toolset_disable_meta_after_content = apply_filters( 'toolset_theme_integration_get_setting', null, 'toolset_disable_meta_after_content' );

		if (
			"1" == $toolset_disable_meta_after_content
			&& ( is_single() || is_page() )
		) {
			remove_action( 'genesis_after_post_content', 'genesis_post_meta' );
			remove_action( 'genesis_entry_footer', 'genesis_post_meta' );
			return true;
		}
		return false;
	}

	/**
	 * Get value from theme integration settings filter and disable pagination for current page if option is enabled
	 */
	public function disable_pagination() {
		$toolset_disable_pagination = apply_filters( 'toolset_theme_integration_get_setting', null, 'toolset_disable_pagination' );

		if (
			"1" == $toolset_disable_pagination
			&& ( is_archive() || is_home() || is_search() )
		) {
			remove_action( 'genesis_after_endwhile', 'genesis_posts_nav' );
		}
	}

	/**
	 * Sidebar in WooCommerce needs an extra class
	 *
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments. (default: array).
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 * @return string
	 */
	public function wc_get_template( $template, $template_name, $args, $template_path, $default_path ) {
		if ( in_array( $template_name, [ 'global/wrapper-start.php', 'global/wrapper-end.php' ] ) ) {
			return TOOLSET_THEME_SETTINGS_PATH . '/compatibility-modules/templates/woocommerce/' . $template_name;
		}
		return $template;
	}

	/**
	 * Removes the sidebar when it is full width layout
	 *
	 * @return null
	 */
	public function disable_sidebar() {
		global $post;
		if ( ! empty( $post->ID ) ) {
			$settings = $this->helper->load_current_settings_object( $post->ID );
			if ( isset( $settings['_genesis_layout'] ) && $settings['_genesis_layout'] === 'full-width-content' ) {
				remove_action( 'genesis_sidebar', 'genesis_do_sidebar' );
				remove_action( 'genesis_sidebar_alt', 'genesis_do_sidebar_alt' );
			}
		}
	}

	/**
	 * Gets the Layout from the theme settings for the CP or WPA
	 *
	 * @return string|null;
	 */
	private function get_layout() {
		global $post;

		if ( ! empty( $post->ID ) ) {
			if ( is_post_type_archive( 'product' ) ) {
				global $WPV_settings;
				if ( ! $WPV_settings || ! isset( $WPV_settings['view_cpt_product'] ) ) {
					return;
				}
				$settings = $this->helper->load_current_settings_object( $WPV_settings['view_cpt_product'] );
				if ( $settings && isset( $settings['site_layout'] ) ) {
					return $settings['site_layout'];
				}
			} else {
				$settings = $this->helper->load_current_settings_object( $post->ID );
				if ( $settings && isset( $settings['_genesis_layout'] ) ) {
					return $settings['_genesis_layout'];
				}
			}
		}
		return;
	}

	/**
	 * Renders Genesis sidebars for WooCommerce
	 *
	 * @param array<array> $sidebars Sidebas to be rendered
	 * @return null
	 */
	private function render_genesis_sidebars( $sidebars ) {
		foreach ( $sidebars as $sidebar ) {
			genesis_markup(
				[
					'open'    => '<aside %s>' . genesis_sidebar_title( 'sidebar' . $sidebar['sidebar_title_sufix'] ),
					'context' => 'sidebar' . $sidebar['sidebar_context_sufix'],
				]
			);
			do_action( 'genesis_before_sidebar' . $sidebar['sidebar_actions'] . '_widget_area' );
			do_action( 'genesis_sidebar' . $sidebar['sidebar_actions'] );
			do_action( 'genesis_after_sidebar' . $sidebar['sidebar_actions'] . '_widget_area' );
			genesis_markup(
				[
					'close'   => '</aside>',
					'context' => 'sidebar' . $sidebar['sidebar_context_sufix'],
				]
			);
		}
	}

	/**
	 * Renders Genesis left sidebars for WooCommerce
	 *
	 * @return null
	 */
	public function render_genesis_sidebar_before() {
		$layout = $this->get_layout();
		if ( ! $layout ) {
			return;
		}

		switch ( $layout ) {
			case 'sidebar-content':
				$this->render_genesis_sidebars( [ self::LAYOUT_SIDEBAR_PRIMARY ] );
				break;
			case 'sidebar-sidebar-content':
				$this->render_genesis_sidebars( [ self::LAYOUT_SIDEBAR_SECONDARY, self::LAYOUT_SIDEBAR_PRIMARY ] );
				break;
			case 'sidebar-content-sidebar':
				$this->render_genesis_sidebars( [ self::LAYOUT_SIDEBAR_SECONDARY ] );
				break;
		}
	}

	/**
	 * Renders Genesis right sidebars for WooCommerce
	 *
	 * @return null
	 */
	public function render_genesis_sidebar_after() {
		$layout = $this->get_layout();
		if ( ! $layout ) {
			return;
		}

		switch ( $layout ) {
			case 'content-sidebar':
				$this->render_genesis_sidebars( [ self::LAYOUT_SIDEBAR_PRIMARY ] );
				break;
			case 'content-sidebar-sidebar':
				$this->render_genesis_sidebars( [ self::LAYOUT_SIDEBAR_PRIMARY, self::LAYOUT_SIDEBAR_SECONDARY ] );
				break;
			case 'sidebar-content-sidebar':
				$this->render_genesis_sidebars( [ self::LAYOUT_SIDEBAR_PRIMARY ] );
				break;
		}
	}

	/**
	 * Renders Genesis entry footer in Woocommerce
	 */
	public function genesis_entry_footer() {
		do_action( 'genesis_entry_footer' );
	}
}
