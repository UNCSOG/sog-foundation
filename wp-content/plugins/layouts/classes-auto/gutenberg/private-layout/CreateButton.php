<?php

namespace OTGS\Toolset\Layouts\ClassesAuto\Gutenberg\PrivateLayout;

use OTGS\Toolset\Common\Utils\RequestMode as RequestMode;

/**
 * Class Create_Button
 *
 * @since 2.5.2
 * @author Riccardo Strobbia
 * @package OTGS\Toolset\Layouts\Classes_Auto\Gutenberg\Private_Layout
 */
class CreateButton {

	const JS_HANDLE = 'private-layout-create-button';

	const CSS_HANDLE = 'private-layout-create-button-css';

	const JS_NAMESPACED_OBJECT = 'PrivateLayoutCreateButtonSettings';

	/**
	 * @var int
	 */
	private $post_id;
	/**
	 * @var \Toolset_Condition_Interface
	 */
	private $condition;
	/**
	 * @var RequestMode
	 */
	private $request_mode;
	/**
	 * @var string
	 * Override this value if you want to run it earlier or later
	 */
	protected $default_action = 'the_post';
	/**
	 * @var \Toolset_Constants
	 */
	private $constants;
	/**
	 * @var mixed
	 */
	private $scripts_url_path;

	/**
	 * Create_Button constructor.
	 *
	 * @param \Toolset_Condition_Interface $toolset_condition
	 * @param RequestMode $request_mode
	 * @param \Toolset_Constants|null $constants
	 * @param int $post_id
	 */
	public function __construct( \Toolset_Condition_Interface $toolset_condition, RequestMode $request_mode, \Toolset_Constants $constants = null, $post_id = 0 ) {
		$this->condition = $toolset_condition;
		$this->request_mode = $request_mode;
		$this->constants = $constants;
		$this->post_id = (int) $post_id;
		$this->scripts_url_path = $this->constants->constant( 'WPDDL_PUBLIC_RELPATH' );
	}

	/**
	 * @return bool
	 * By default we want this to work in the admin and in a non-ajax request
	 * override this method to change requirements
	 */
	public function minimum_requirements() {
		return ( $this->request_mode->get() === RequestMode::ADMIN );
	}

	/**
	 * @return string
	 */
	public function get_default_action() {
		return $this->default_action;
	}

	/**
	 * @return void
	 * Registers scripts and styles and run them on Gutenberg the_post action, we can't register and check if we are in
	 *     Gutenberg at the same time, so we need to register first, then check if we are in Gutenberg, if we aren't we
	 *     don't enqueue
	 */
	public function add_hooks() {
		if ( ! $this->minimum_requirements() ) {
			return;
		}
		add_filter( 'toolset_add_registered_script', array( $this, 'register_scripts' ), 10, 1 );
		add_filter( 'toolset_add_registered_styles', array( $this, 'register_styles' ), 10, 1 );
		// Action added in Gutenberg editor page only: https://github.com/WordPress/gutenberg/issues/1316
		add_action( $this->get_default_action(), array( $this, 'run' ) );
	}

	/**
	 * @return mixed|void
	 */
	private function has_private_layout() {
		return apply_filters( 'ddl-page_has_private_layout', $this->get_post_id() );
	}

	/**
	 * @return mixed|void
	 */
	private function is_private_layout_in_use() {
		return apply_filters( 'ddl-is_private_layout_in_use', $this->get_post_id() );
	}

	/**
	 * @return int
	 */
	public function get_post_id() {
		if ( ! $this->post_id ) {
			$this->post_id = $this->get_post_id_from_global();
		}

		return $this->post_id;
	}

	/**
	 * @return int
	 */
	private function get_post_id_from_global() {
		$post = $GLOBALS['post'];

		return (int) $post->ID;
	}

	/**
	 * @return mixed
	 */
	public function get_scripts_url_path() {
		return $this->scripts_url_path;
	}

	/**
	 * @param $scripts
	 *
	 * @return array
	 */
	public function register_scripts( $scripts ) {

		$scripts[ self::JS_HANDLE ] = new \Toolset_Script( self::JS_HANDLE, $this->get_scripts_url_path() . '/js/private-layout-create-button.js', array(
			'wp-element',
			'ddl_private_layout',
			'ddl_create_new_layout',
			'toolset-event-manager',
		), $this->constants->constant( 'WPDDL_VERSION' ), true );

		return $scripts;
	}

	/**
	 * @param $styles
	 *
	 * @return array
	 */
	public function register_styles( $styles ) {

		$styles[ self::CSS_HANDLE ] = new \Toolset_Style( self::CSS_HANDLE, $this->get_scripts_url_path() . '/css/private-layout-create-button.css', array(), $this->constants->constant( 'WPDDL_VERSION' ), 'screen' );

		return $styles;
	}

	/**
	 * @return $this
	 */
	public function run() {
		if ( ! $this->condition->is_met() ) {
			return $this;
		}
		add_action( 'admin_footer', array(
			$this,
			'create_layout_nonce',
		), 10 );
		add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );

		return $this;
	}

	/**
	 * @return void
	 */
	public function create_layout_nonce() {
		wp_nonce_field( 'wp_nonce_create_layout', 'wp_nonce_create_layout' );
	}

	/**
	 * @return void
	 */
	public function enqueue_scripts() {
		do_action( 'toolset_enqueue_styles', array( self::CSS_HANDLE ) );
		do_action( 'toolset_enqueue_scripts', array( self::JS_HANDLE ) );
		do_action( 'toolset_localize_script', self::JS_HANDLE, self::JS_NAMESPACED_OBJECT, $this->get_localised_object() );
	}

	/**
	 * @return array
	 */
	public function get_localised_object() {
		return array(
			'hasPrivateLayout' => $this->has_private_layout(),
			'isPrivateLayoutInUse' => $this->is_private_layout_in_use(),
			'postId' => $this->get_post_id(),
			'userCanEditPrivate' => user_can_edit_private_layouts(),
			'postType' => get_post_type( $this->get_post_id() ),
			'editorUrl' => sprintf( '%sadmin.php?page=dd_layouts_edit&layout_id=%s&action=edit', admin_url(), $this->get_post_id() ),
		);
	}
}