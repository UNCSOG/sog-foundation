<?php

namespace OTGS\Toolset\Layouts\Classes_Auto\Cells\Preview;

/**
 * Class Main
 *
 * @package OTGS\Toolset\Layouts\Classes_Auto\Cells\Preview
 * @since 2.5.2
 * A wrapper class for the preview functionality, added here for testers to prove React works in the editor without conflicts
 */
class Main{
	private $assets_manager;
	private $scripts_url_path;
	private $constants;

	/**
	 * Main constructor.
	 *
	 * @param \Toolset_Assets_Manager $assets_manager
	 * @param \Toolset_Constants|null $costants
	 */
	public function __construct( \Toolset_Assets_Manager $assets_manager, \Toolset_Constants $costants = null ) {
		$this->assets_manager = $assets_manager;
		$this->constants = $costants;
		$this->scripts_url_path = $this->constants->constant('WPDDL_PUBLIC_RELPATH');
	}

	/**
	 * @return void
	 */
	public function add_hooks(){
		//add_action( 'admin_enqueue_scripts', array( $this, 'admin_register_scripts' ) );
	}

	/**
	 * @return void
	 */
	public function admin_register_scripts() {
		$this->assets_manager->register_script( 'layouts-cells-preview', $this->scripts_url_path . '/js/layouts.cells.preview.js', array( 'wp-element' ), $this->constants->constant('WPDDL_VERSION'), true );

		$this->assets_manager->enqueue_scripts( array('layouts-cells-preview') );

		$this->assets_manager->localize_script( 'layouts-cells-preview', 'LAYOUTS_CELLS_PREVIEW_SETTINGS', array() );
	}

}