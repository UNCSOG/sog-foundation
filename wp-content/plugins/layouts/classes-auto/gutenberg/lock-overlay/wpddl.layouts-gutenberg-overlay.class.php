<?php

use OTGS\Toolset\Common\Utility\Lock_Overlay as Lock_Overlay;
use OTGS\Toolset\Common\Utils\RequestMode as RequestMode;

class WPDD_Layouts_Gutenberg_Overlay extends Lock_Overlay\Builder{

	/**
	 * @var string
	 * Override to provide a different _.template
	 */
	protected $template_selector = 'js-ddl-post-content-message-in-post-editor-private-tpl';
	/**
	 * @var string
	 * Override to provide a different message _.template
	 */
	protected $message_template_selector = 'js-ddl-post-content-message-in-post-editor-html';

	/**
	 * WPDD_Layouts_Gutenberg_Overlay constructor.
	 *
	 * @param $name
	 * @param $selector
	 * @param $toolset_condition
	 * @param int $post_id
	 * @param array $options
	 * @override
	 */
	public function __construct( $name, $selector, \Toolset_Condition_Interface $toolset_condition, RequestMode $request_mode, $post_id = 0, array $options = array() ) {
		parent::__construct( $name, $selector, $toolset_condition, $request_mode, $post_id, $options );
	}

	/**
	 * @return void
	 * @override
	 */
	public function add_hooks() {
		parent::add_hooks();
		add_filter( 'toolset_lock_overlay_get_scripts_to_enqueue', array( $this, 'push_scripts_to_enqueue' ), 10, 1 );
	}

	/**
	 * @override
	 * @return void
	 */
	public function lock_overlay_template(){
		$post = $this->get_post();
		$post_type = $this->get_post_type();
		$layout_slug           = apply_filters( 'ddl-page_has_private_layout', $this->get_post_id() );
		$private_layout_in_use = apply_filters( 'ddl-is_private_layout_in_use', $this->get_post_id() );
		$has_private_layout = $private_layout_in_use && $layout_slug;

		ob_start();
		include WPDDL_GUI_ABSPATH . 'templates/layout-post-edit-page-post-content-cell-overlay.tpl.php';
		echo ob_get_clean();
	}

	/**
	 * @param $options
	 * @override
	 * @return array
	 */
	protected function set_up_default_options( $options ) {
		$layout = $this->get_layout_settings();

		if( ! $layout ) return parent::set_up_default_options( $options );

		return wp_parse_args( $options, array(
			'overlay_name'      => $this->get_name(),
			'post_id'   => $this->get_post_id(),
			'post_type' => $this->get_post_type(),
			'layout' => $layout,
			'name' => $layout->name
		) );
	}

	/**
	 * @return mixed|null
	 */
	public function get_layout_settings(){
		$layout_id = $this->get_post_id();
		if( $layout_id === 0 ) return null;
		return apply_filters( 'ddl-get_layout_settings', $this->get_post_id(), true, false );
	}

	/**
	 * @param $scripts
	 *
	 * @return array
	 */
	public function push_scripts_to_enqueue( $scripts ) {
		$scripts[] = 'ddl_private_layout';
		$scripts[] = 'private-layout-gutenberg-script';
		return $scripts;
	}

	/**
	 * @param $scripts
	 * @override
	 * @return mixed
	 */
    protected function register_additional_scripts( $scripts ){
	    $scripts['private-layout-gutenberg-script'] = new \Toolset_Script( 'private-layout-gutenberg-script', WPDDL_RES_RELPATH . '/js/ddl-gutenberg-private-layout-helper.js', array('jquery'), WPDDL_VERSION, true );
		return $scripts;
    }
}