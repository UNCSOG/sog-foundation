<?php

namespace OTGS\Toolset\Views\Controller;

use OTGS\Toolset\Views\Controller\Compatibility\BlockEditorWPA;

/**
 * Handles the enqueuing of the assets needs for the WordPress Archive block editor to work that are not WPA block related.
 */
class WordPressArchiveHelper {
	/** @var \Toolset_Assets_Manager */
	private $toolset_assets_manager;

	/** @var \Toolset_Constants */
	private $toolset_constants;

	const SCRIPT_HANDLE = 'wpa-block-editor';

	/**
	 * WordPressArchiveHelper constructor.
	 *
	 * @param \Toolset_Assets_Manager $toolset_assets_manager
	 * @param \Toolset_Constants      $toolset_constants
	 */
	public function __construct( \Toolset_Assets_Manager $toolset_assets_manager, \Toolset_Constants $toolset_constants ) {
		$this->toolset_assets_manager = $toolset_assets_manager;
		$this->toolset_constants = $toolset_constants;
	}

	/**
	 * Initializes the class by initializing the hooks only when a WordPress Archive helper post is been edited.
	 */
	public function initialize() {
		if (
			get_post_type( toolset_getget( 'post' ) ) !== BlockEditorWPA::WPA_HELPER_POST_TYPE ||
			toolset_getget( 'action' ) !== 'edit'
		) {
			return;
		}

		add_action( 'admin_init', array( $this, 'register_assets' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Handles the assets registration.
	 */
	public function register_assets() {
		$this->toolset_assets_manager->register_script(
			self::SCRIPT_HANDLE,
			$this->toolset_constants->constant( 'WPV_URL' ) . '/public/js/wordpressArchive.js',
			[ 'wp-plugins', 'wp-edit-post', 'register_view_editor_assets' ],
			$this->toolset_constants->constant( 'WPV_VERSION' ),
			false
		);
	}

	/**
	 * Handles the assets enqueueing.
	 */
	public function enqueue_assets() {
		do_action( 'toolset_enqueue_scripts', array( self::SCRIPT_HANDLE ) );
	}
}
