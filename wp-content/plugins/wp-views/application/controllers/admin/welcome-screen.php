<?php
/**
 * Welcome screen when activating or updating the plugin
 *
 * @package Toolset Views
 * @since 3.0
 */

namespace OTGS\Toolset\Views\Controller\Admin;

/**
 * Controller for the plugin welcome screen.
 *
 * @since 3.0
 */
class WelcomeScreen {

	const PAGE_SLUG = 'views-welcome-screen';

	const TRANSIENT_FLAG = 'vpv_welcome_screen_redirect';

	/**
	 * @var \OTGS\Toolset\Views\Model\Wordpress\Transient
	 */
	private $transient_manager;

	/**
	 * @var \OTGS\Toolset\Common\Wordpress\WpSafeRedirect
	 */
	private $safe_redirect;

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Views\Model\Wordpress\Transient $transient_manager
	 * @param \OTGS\Toolset\Common\Wordpress\WpSafeRedirect $safe_redirect
	 */
	public function __construct(
		\OTGS\Toolset\Views\Model\Wordpress\Transient $transient_manager,
		\OTGS\Toolset\Common\Wordpress\WpSafeRedirect $safe_redirect
	) {
		$this->transient_manager = $transient_manager;
		$this->safe_redirect = $safe_redirect;
	}

	public function initialize() {
		$this->maybe_execute_redirect();
		$this->maybe_register_page();
	}

	private function maybe_execute_redirect() {
		// Return if already on the welcome page.
		if ( self::PAGE_SLUG === toolset_getget( 'page' ) ) {
			return;
		}

		// Bail if no activation redirect
		if ( ! $this->transient_manager->get_transient( self::TRANSIENT_FLAG ) ) {
			return;
		}

		// Delete the redirect transient
		$this->transient_manager->delete_transient( self::TRANSIENT_FLAG );

		// Bail if activating from network, or bulk.
		if (
			is_network_admin()
			|| isset( $_GET['activate-multi'] )
		) {
			return;
		}

		$redirect_url = add_query_arg( array( 'page' => self::PAGE_SLUG ), admin_url( 'index.php' ) );

		// Redirect to the welcome page.
		if ( $this->safe_redirect->wp_safe_redirect( $redirect_url ) ) {
			exit;
		}
	}

	private function maybe_register_page() {
		if ( self::PAGE_SLUG !== toolset_getget( 'page' ) ) {
			return;
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_head', array( $this, 'remove_page' ) );
	}

	public function enqueue_assets() {
		wp_register_style( 'wpv-welcome-screen', WPV_URL . '/res/css/welcome-screen.css', array(), WPV_VERSION );
		wp_enqueue_style( 'wpv-welcome-screen' );
	}

	public function register_page() {
		add_dashboard_page(
			__( 'Welcome to Toolset Blocks', 'wpv-views' ),
			__( 'Welcome to Toolset Blocks', 'wpv-views' ),
			'read',
			self::PAGE_SLUG,
			array( $this, 'page_content' )
		);
	}

	public function remove_page() {
		remove_submenu_page( 'index.php', self::PAGE_SLUG );
	}

	public function page_content() {
		$template_repository = \WPV_Output_Template_Repository::get_instance();
		$renderer = \Toolset_Renderer::get_instance();

		$renderer->render(
			$template_repository->get( \WPV_Output_Template_Repository::WELCOME_PAGE ),
			null
		);
	}

}
