<?php

namespace OTGS\Toolset\Views\Controller\Filters;

/**
 * Shared methods and routines for the GUI component of filters.
 *
 * @todo Port the static methods on WPV_Filter_Item.
 * @todo Turn HTML output into proper templates.
 * @todo Add unit tests for the template printing.
 */
abstract class AbstractGui {

	const SCRIPT_BACKEND = '';
	const SCRIPT_BACKEND_FILENAME = '';
	const SCRIPT_BACKEND_I18N = '';
	const NONCE = '';

	/** @var \Toolset_Assets_Manager */
	protected $assets_manager;

	public function __construct( \Toolset_Assets_Manager $assets_manager ) {
		$this->assets_manager = $assets_manager;
	}

	/**
	 * Filter GUI initialization.
	 */
	public function initialize() {
		add_action( 'admin_init', array( $this, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ), 20 );

		add_action( 'init', array( $this, 'load_hooks' ) );

		add_action( 'admin_footer', array( $this, 'render_footer_templates' ) );
	}

	/**
	 * Extend the dependencies for the script managing the filter in the editor.
	 *
	 * @param string[] $dependencies
	 * @return string[]
	 */
	protected function set_script_dependencies( $dependencies ) {
		// Child classes can extend the list of dependencies.
		return $dependencies;
	}

	/**
	 * Extend the i18n  for the script managing the filter in the editor.
	 *
	 * @param mixed[] $i18n
	 * @return mixed[]
	 */
	protected function set_script_i18n( $i18n ) {
		// Child classes can extend the i18n data.
		return $i18n;
	}

	/**
	 * Register the filter backend assets.
	 */
	public function register_assets() {
		if (
			empty( static::SCRIPT_BACKEND )
			|| empty( static::SCRIPT_BACKEND_FILENAME )
		) {
			// Child classes should define their own constants if they require assets.
			return;
		}

		$this->assets_manager->register_script(
			static::SCRIPT_BACKEND,
			WPV_URL . '/public/js/admin/filters/' . static::SCRIPT_BACKEND_FILENAME . '.js',
			$this->set_script_dependencies( array( 'views-filters-js', 'underscore' ) ),
			WPV_VERSION,
			false
		);

		$script_i18n = $this->set_script_i18n( [] );
		if ( ! empty( $script_i18n ) ) {
			$this->assets_manager->localize_script(
				static::SCRIPT_BACKEND,
				static::SCRIPT_BACKEND_I18N,
				$script_i18n
			);
		}
	}

	/**
	 * Enqueue the filter backend assets.
	 */
	public function enqueue_assets() {
		if (
			'views-editor' === toolset_getget( 'page' )
			|| 'view-archives-editor' === toolset_getget( 'page' )
		) {
			do_action( 'toolset_enqueue_scripts', array( static::SCRIPT_BACKEND ) );
		}
	}

	/**
	 * Enqueue the filter backend assets in the blocks editor.
	 */
	public function enqueue_block_editor_assets() {
		do_action( 'toolset_enqueue_scripts', array( static::SCRIPT_BACKEND ) );
	}

	/**
	 * Render filter templates, if any.
	 * @todo Check whether this is loaded in the blocks editor, and how/why.
	 */
	public function render_footer_templates() {}

	/**
	 * Set callbacks for registering and printing the filter.
	 *
	 * This requires callbacks in the following hooks:
	 * - wpv_filters_add_filter for the dialog to add a new filter on general Views.
	 * - wpv_filters_add_archive_filter for the dialog to add a new filter on WPAs.
	 * - wpv_add_filter_list_item for printing the filter in the edit page.
	 * - wpv_filter_register_shortcode_attributes_for_posts for declaring the shortcode attribute used by this filter.
	 * - wpv_filter_register_url_parameters_for_posts for declaring the URL parameter used by this filter.
	 */
	abstract public function load_hooks();

}
