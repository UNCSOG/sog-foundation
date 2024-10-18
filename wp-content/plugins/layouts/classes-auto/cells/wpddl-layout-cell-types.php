<?php

/**
 * Class WPDD_RegisteredCellTypesFactory
 * static class to build a cell types factory
 * Static isn't "bad", it's unmockable. You can still use it where mocking doesn't make sense.
 * http://softwareengineering.stackexchange.com/questions/66115/static-is-bad-but-what-about-the-factory-pattern
 */
class WPDD_RegisteredCellTypesFactory{
	/**
	 * @return WPDD_RegisteredCellFactory
	 */
	public static function build( ){
		return WPDD_LayoutRegisteredCellTypes::getInstance( new WPDD_registed_cell_types(), new WPDD_register_layout_theme_section() );
	}

	/**
	 * For unit testing, forces the object to be constructed again
	 */
	public static function tearDown(){
		WPDD_LayoutRegisteredCellTypes::tearDown();
	}
}
/**
 * Class WPDD_LayoutRegisteredCellTypes
 * Layouts API / provides functionalities to add cells/rows with external API
 */
class WPDD_LayoutRegisteredCellTypes{
	protected $registed_cells;
	protected $cell_factory = array();
	protected $registered_theme_sections = null;
	private static $instance = null;
	/**
	 * WPDD_LayoutRegisteredCellTypes constructor.
	 *
	 * @param WPDD_registed_cell_types|null $registed_cells
	 */
	private function __construct( WPDD_registed_cell_types $registed_cells = null, WPDD_register_layout_theme_section $registered_theme_sections = null ) {
		$this->registed_cells = $registed_cells;
		$this->registered_theme_sections = $registered_theme_sections;
		$this->add_hooks();
	}

	/**
	 * void
	 */
	public function set_cell_factory(){
		$this->cell_factory = apply_filters( 'dd_layouts_register_cell_factory', array() );
		$this->cell_factory = apply_filters( 'dd_layouts_de_register_cell_factory', $this->cell_factory );
	}

	/**
	 * @param $cell_type
	 * @param $data
	 *
	 * @return bool
	 */
	function register_dd_layout_cell_type( $cell_type, $data ) {
		return $this->registed_cells->register_dd_layout_cell_type( $cell_type, $data );
	}

	function enqueue_cell_scripts() {

		foreach ( $this->cell_factory as $factory ) {

			if ( method_exists( $factory, 'enqueue_editor_scripts' ) ) {
				$factory->enqueue_editor_scripts();
			}
		}

		$this->registed_cells->enqueue_cell_scripts();
	}

	function enqueue_cell_styles() {

		foreach ( $this->cell_factory as $factory ) {

			if ( method_exists( $factory, 'enqueue_editor_styles' ) ) {
				$factory->enqueue_editor_styles();
			}
		}

		$this->registed_cells->enqueue_cell_styles();
	}

	/**
	 * @param $cell_type
	 *
	 * @return mixed
	 */
	public function get_factory( $cell_type ) {
		return $this->cell_factory[$cell_type];
	}

	/**
	 * @return array|mixed
	 */
	function get_current_cell_info() {
		return $this->registed_cells->get_current_cell_info();
	}

	/**
	 * @param $cell_type
	 * @param $name
	 * @param $width
	 * @param string $css_class_name
	 * @param null $content
	 * @param string $cssId
	 * @param string $tag
	 * @param string $unique_id
	 *
	 * @return null|WPDD_registered_cell
	 */
	function create_cell( $cell_type, $name, $width, $css_class_name = '', $content = null, $cssId = '', $tag = 'div', $unique_id = '' ) {
		if ( isset( $this->cell_factory[ $cell_type ] ) ) {
			return $this->cell_factory[ $cell_type ]->build( $name, $width, $css_class_name, $content, $cssId, $tag, $unique_id );
		}

		return $this->registed_cells->create_cell( $cell_type, $name, $width, $css_class_name, $content, $cssId, $tag, $unique_id );
	}

	/**
	 * @return string
	 */
	function get_cell_templates() {
		$templates = '';

		foreach ( $this->cell_factory as $cell_type => $factory ) {
			$templates .= '<script type="text/html" id="' . $cell_type . '-template">' . "\n";
			$templates .= $factory->get_editor_cell_template() . "\n";
			$templates .= '</script>' . "\n";
		}

		$templates .= $this->registed_cells->get_cell_templates();

		return $templates;
	}

	/**
	 * @param null $cell_types
	 *
	 * @return array|null
	 */
	function get_cell_types( $cell_types = null ) {

		$wpddl_features = apply_filters( 'ddl-get_global_features', null );

		$cell_types = array_keys( $this->cell_factory );
		$cell_types = array_merge( $cell_types, $this->registed_cells->get_cell_types() );

		foreach ( $cell_types as $index => $cell_type ) {
			/* Enable cell-post-content again
			if ($cell_type == 'cell-post-content' && !$wpddl_features->is_feature('post-content-cell')) {
				unset($cell_types[$index]);
			}
			*/
			if ( $cell_type == 'post-loop-cell' && ! $wpddl_features->is_feature( 'post-loop-cell' ) ) {
				unset( $cell_types[ $index ] );
			}
		}

		return $cell_types;
	}

	/**
	 * @param $cell_type
	 * @param bool $print_dialog
	 *
	 * @return mixed
	 */
	function get_cell_info( $cell_type, $print_dialog = false ) {
		static $cell_info_cache = array();

		if ( !isset( $cell_info_cache[ $cell_type ] ) || $print_dialog === true) {
			$template['cell-image-url']  = '';
			$template['name']            = '';
			$template['description']     = '';
			$template['button-text']     = '';
			$template['dialog-template'] = '';
			$template['allow-multiple']  = true;

			if ( isset( $this->cell_factory[ $cell_type ] ) ) {
				$cell_info_cache[ $cell_type ] = $this->cell_factory[ $cell_type ]->get_cell_info( $template, $print_dialog );
			} else {
				$cell_info_cache[ $cell_type ] = $this->registed_cells->get_cell_info( $cell_type );
			}

			if ( ! isset( $cell_info_cache[ $cell_type ]['category'] ) ) {
				$cell_info_cache[ $cell_type ]['category'] = __( 'Fields, text and media', 'ddl-layouts' );
			}

			if ( ! isset( $cell_info_cache[ $cell_type ]['icon-url'] ) ) {
				$cell_info_cache[ $cell_type ]['icon-url'] = '';
			}

			if ( ! $cell_info_cache[ $cell_type ]['cell-image-url'] && ! $cell_info_cache[ $cell_type ]['icon-url'] ) {
				$cell_info_cache[ $cell_type ]['cell-image-url'] = 'icon-circle-blank';
			}

		}

		return $cell_info_cache[ $cell_type ];
	}

	/**
	 * @return mixed|void
	 */
	function get_cell_categories() {
		$categories = array();

		foreach ( $this->get_cell_types() as $cell_type ) {
			$cell_info = $this->get_cell_info( $cell_type );

			$categories[ $cell_info['category'] ] = array( 'name' => $cell_info['category'] );
		}

		return apply_filters( 'ddl-get_cell_categories', $categories );
	}

	/**
	 * :void
	 */
	public function add_hooks(){
		add_action( 'init', array($this, 'set_cell_factory'), 10 );
		add_filter( 'ddl-get_theme_section_info', array( $this->registered_theme_sections, 'get_theme_section_info'), 10, 1 );
		add_filter( 'ddl-get_cell_types', array( $this, 'get_cell_types' ), 10, 1 );
		add_filter( 'ddl-get_theme_sections', array( $this->registered_theme_sections, 'get_theme_sections'), 10, 1 );
	}

	/**
	 * @param $theme_section
	 * @param $args
	 */
	function register_dd_layout_theme_section( $theme_section, $args ) {
		$this->registered_theme_sections->register_dd_layout_theme_section( $theme_section, $args );
	}

	/**
	 * @return bool
	 */
	function has_theme_sections() {
		return sizeof( $this->registered_theme_sections->get_theme_sections() ) > 0;
	}

	/**
	 * @return null|WPDD_registed_cell_types
	 */
	function get_registered_cells(){
		return $this->registed_cells;
	}

	/**
	 * @return null|WPDD_register_layout_theme_section
	 */
	function get_registered_theme_sections(){
		return $this->registered_theme_sections;
	}

	/**
	 * @param WPDD_registed_cell_types|null $registed_cells
	 * @param WPDD_register_layout_theme_section|null $registered_theme_sections
	 *
	 * @return null|WPDD_LayoutRegisteredCellTypes
	 * https://gonzalo123.com/2012/09/24/the-reason-why-singleton-is-a-problem-with-phpunit/
	 */
	public static function getInstance( WPDD_registed_cell_types $registed_cells = null, WPDD_register_layout_theme_section $registered_theme_sections = null ) {
		if ( !self::$instance ) {
			self::$instance = new self( $registed_cells, $registered_theme_sections );
		}

		return self::$instance;
	}

	/**
	 * For unit testing, forces the object to be constructed again
	 */
	public static function tearDown(){
		self::$instance = null;
	}
}