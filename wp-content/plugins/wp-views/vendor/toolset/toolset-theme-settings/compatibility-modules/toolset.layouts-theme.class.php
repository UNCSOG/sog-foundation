<?php

/**
 * @since Layouts 2.0.2
 * Class Toolset_Compatibility_Theme_Handler
 *
 * This is only abstract class and it is here to be extended by compatibility classes for themes
 */
abstract class Toolset_Compatibility_Theme_Handler {
	/**
	 * @var
	 *  theme name
	 */
	protected $name;
	/**
	 * @var
	 * theme slug
	 */
	protected $slug;
	private $populate_manager;
	protected $collections = null;
	private $editors_slugs = array( 'dd_layouts_edit', 'ct-editor', 'view-archives-editor' );
	private $listings_slugs = array( 'dd_layouts', 'view-templates', 'view-archives', 'views' );
	private $condition_editors = false;
	private $condition_generic = false;
	private $condition_has_settings = false;
	/**
	 * @var null/int
	 * Stores the value of the $_GET['post'] variable if isset and passes to the object
	 */
	private $condition_post_edit = null;
	/**
	 * @var null/int
	 * Stores the value of the $_POST variable if any and passes to the object
	 */
	private $condition_is_post_request = null;

	protected $helper = null;

	/**
	 * Toolset_Compatibility_Theme_Handler constructor.
	 *
	 * @param $name
	 * @param $slug
	 * @param Toolset_Theme_Integration_Settings_Config_Populate|null $populate_manager
	 * @param Toolset_Theme_Integration_Settings_Helper|null $helper
	 */
	public function __construct( $name, $slug, Toolset_Theme_Integration_Settings_Config_Populate $populate_manager = null, Toolset_Theme_Integration_Settings_Helper $helper = null  ) {
		$this->helper = $helper;
		$this->name = $name;
		$this->slug = $slug;
		$this->populate_manager = $populate_manager;

		add_action( 'init', array( $this, 'run' ), 11 );
	}

	/**
	 *
	 */
	public function run() {
		$this->set_run_settings_integration_conditions();
		$this->run_settings_integration();
		$this->run_hooks();
	}

	/**
	 *
	 */
	private function run_settings_integration(){
		// run in front end or in admin in the pages we like and at the time we need depending when
		// the hooks we interact with are already running or not
		if( $this->condition_is_post_request ||
			$this->condition_generic ||
			$this->condition_post_edit
		){
			$this->run_settings_app();
		} elseif( $this->condition_editors ){
			add_action( 'admin_init', array( $this, 'run_settings_app'), 11 );
		} elseif( !is_admin() ){
			add_action( 'wp', array( $this, 'run_settings_app'), 0 );
		}
	}

	/**
	 *
	 */
	public function run_settings_app(){
		// if it is a content template and cannot be assigned, then leave it alone.
		if( $this->check_if_content_template_and_cannot_be_assigned() ){
			return;
		}
		$this->helper->load_current_settings_object();
		$this->populate_settings_collections( $this->helper->get_current_settings() );
		$this->set_condition_has_settings();
		$this->run_controllers();
	}

	/**
	 * @return bool
	 * check if we have a object_type and an object_id and in case it's a CT if can be assigned, if not then we might avoid loading settings
	 */
	private function check_if_content_template_and_cannot_be_assigned(){
		return $this->helper->check_if_content_template_and_cannot_be_assigned();
	}

	/**
	 * @param null $settings
	 *
	 * @return array|null
	 */
	protected function populate_settings_collections( $settings = null ){
		if ( null === $this->populate_manager ) return null;
		$this->collections = $this->populate_manager->run( $settings );
		return $this->collections;
	}

	/**
	 * @return null
	 */
	protected function run_controllers(){

		if( ! $this->condition_has_settings ){
			return null;
		}

		$controller = null;
		$controller_factory = Toolset_Theme_Integration_Settings_Controllers_Factory::getInstance();

		if( $this->condition_editors ){
			$controller = $controller_factory->build( 'Back_End', $this->helper, $this->editors_slugs );
		} //TODO: let's remove this one
		elseif( $this->condition_generic ){
			$controller = $controller_factory->build( 'Admin', $this->helper );
		} elseif( $this->condition_is_post_request ){
			$controller = $controller_factory->build( 'Post_Request', $this->helper, $this->condition_is_post_request  );
		} elseif( $this->condition_post_edit ){
			$controller = $controller_factory->build( 'Post_Edit', $this->helper, $this->condition_post_edit );
		} elseif( !is_admin() ){
			$controller = $controller_factory->build( 'Front_End', $this->helper );
		}

		return $controller;
	}

	protected function set_condition_editors(){
		if( is_admin() && isset( $_GET['page'] ) &&
			in_array( $_GET['page'], $this->editors_slugs )
		){
			$this->condition_editors = true;
		}

		/**
		 * Let third parties force this library to behave as in a backend editor.
		 *
		 * The CTs integration with Gutenberg needs to force this
		 * so we can display theme settings there as a metabox.
		 *
		 * @param bool Current condition status
		 * @return bool
		 * @since 1.3.3
		 */
		$this->condition_editors = apply_filters( 'toolset_theme_settings_force_backend_editor', $this->condition_editors );
	}

	protected function set_condition_generic(){

		if( ( is_admin() && isset( $_GET['page'] ) && in_array( $_GET['page'], $this->listings_slugs ) )
		){
			$this->condition_generic = true;
		}
	}

	protected function set_condition_post_edit() {
		global $pagenow;

		if ( is_admin() && $pagenow == 'post.php' && isset( $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
			$this->condition_post_edit = $_GET['post'];
		} elseif ( is_admin() && isset( $_POST['post_id'] ) ) {
			$this->condition_post_edit = $_POST['post_id'];
		} elseif ( is_admin() && isset( $_POST['post_ID'] )  ) {
			$this->condition_post_edit = $_POST['post_ID'];
		}
	}

	/**
	 *
	 */
	protected function set_condition_is_post_request(){
		if( ( is_admin() && isset( $_POST ) && isset( $_POST['layout_id'] ) ) ){
			$this->condition_is_post_request = $_POST['layout_id'];
		} elseif( is_admin() && isset( $_POST ) && isset( $_POST['id'] ) ){
			$this->condition_is_post_request = $_POST['id'];
		}
	}

	/**
	 *
	 */
	protected function set_condition_has_settings(){
		if( null !== $this->collections &&
			count( $this->collections ) > 0 ){
			$this->condition_has_settings = true;
		}
	}

	/**
	 *
	 */
	protected function set_run_settings_integration_conditions(){
		$this->set_condition_editors();
		$this->set_condition_is_post_request();
		$this->set_condition_post_edit();
		$this->set_condition_generic();
	}

	/**
	 * @return mixed
	 */
	protected abstract function run_hooks();

	/**
	 * Get truthy/falsy values for Toolset custom theme binary options.
	 *
	 * @param string $option_name
	 * @return bool
	 */
	protected function get_toolset_custom_theme_option( $option_name ) {
		$get_toolset_theme_option = apply_filters( 'toolset_theme_integration_get_setting', null, $option_name );
		return ( strval( $get_toolset_theme_option ) === "1" ) ? true : false;
	}

	/**
	 * @return bool
	 * check if Woocommerce is actice
	 * by checking active plugins in options table
	 * using build in filter
	 */
	public function is_woocommerce_active(){
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

	/**
	 * @param $title
	 *
	 * @return string
	 * adjust Woocommerce page title depending if it's a product category or the shop page
	 * this is generic and used by any theme which needs this adjustment
	 * must be implemented using "get_the_archive_title" filter to override the title
	 */
	public function toolset_woocommerce_show_page_title( $title ) {
		if ( $this->is_woocommerce_active() && is_woocommerce() ) {
			if ( is_shop() ) {

				 // WooCommerce shop plays dual; as a shop page and an archive. By default, Views short code for archive title output different stuff, while, theme shows Shop Page title. Here, the title is modified to return the title of Shop Page.
				$shop_page_id = get_option( 'woocommerce_shop_page_id' );
				$title = sprintf( __( '%s', 'ddl-layouts' ), get_the_title( $shop_page_id ) );
			} else if ( is_product_category() ) {

				// Just like the above, we need to strip-off the stuff other than the category name, from the title
				$title = sprintf( __( '%s', 'ddl-layouts' ), single_cat_title( '', false ) );
			}
		}

		return $title;

	}
}
