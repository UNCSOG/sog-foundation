<?php

/**
 * Class WPDD_json2layout
 * converts Layout JSON object to WPDD_Layout php object
 */
class WPDD_json2layout {

	/** @var WPDD_layout */
	private $layout;
	private $_preset_mode;
	private $factories = null;
	private $registered_cell_factory = null;
	/** @var WPDD_LayoutFactory */
	private $layout_factory;

	/**
	 * WPDD_json2layout constructor.
	 *
	 * @param bool $preset
	 * @param WPDD_FactoryManager|null $factories
	 * @param WPDD_RegisteredCellFactory|null $registered_factory
	 */
	function __construct( $preset = false, WPDD_FactoryManager $factories = null, WPDD_LayoutRegisteredCellTypes $registered_cells = null ) {
		$this->_preset_mode = $preset;
		$this->layout       = null;

		/* retrocompatibility will be deprecated soon */
		$this->factories = $factories ? $factories : $this->get_factories();
		/* retrocompatibility will be deprecated soon */
		$this->registered_cell_factory = $registered_cells ? $registered_cells : $this->get_registered_factory();
	}

	function get_layout(){
		return $this->layout;
	}

	/**
	 * @return WPDD_RegisteredCellFactory
	 * retrocompatibility
	 */
	private function get_registered_factory() {
		return WPDD_RegisteredCellTypesFactory::build();
	}

	/**
	 * @return WPDD_FactoryManager
	 * retrocompatibility
	 */
	private function get_factories() {
		return new WPDD_FactoryManager();
	}

	/**
	 * @param $json
	 * @param bool $already_decoded
	 *
	 * @return null|object
	 */
	function json_decode( $json, $already_decoded = false ) {
		if ( $already_decoded ) {
			$json_array = $json;
		} else {
			$json_array = json_decode( $json, true );

			if( json_last_error() !== JSON_ERROR_NONE ){
				$this->layout = null;
				return $this->layout;
			}
		}

		$this->layout_factory = $this->factories->get_factory( 'Layout', '' );

		$this->layout = $this->layout_factory->build( $json_array['width'], $json_array['cssframework'], isset( $json_array['layout_type'] ) ? $json_array['layout_type'] : '' );

		$this->layout->set_name( $json_array['name'] );

		if ( isset( $json_array['slug'] ) ) {
			$this->layout->set_post_slug( $json_array['slug'] );
		}

		$this->set_layout_column_prefix( $json_array );

		if ( isset( $json_array['parent'] ) ) {
			$this->layout->set_parent_name( $json_array['parent'] );
		}

		$this->add_rows( $json_array['Rows'], $this->layout );

		return $this->layout;
	}

	/**
	 * @param $json_array
	 */
	private function set_layout_column_prefix( $json_array ){
		if( isset( $json_array['no_default_prefix'] ) && $json_array['no_default_prefix'] === true ){
			$this->layout->set_column_prefix( isset( $json_array['column_prefix'] ) ? $json_array['column_prefix'] : $this->layout->get_column_prefix() );
		} else {
			$this->layout->set_column_prefix( $this->get_default_column_prefix( ) );
		}
	}

	/**
	 * @param string $prefix
	 *
	 * @return array|mixed|string|void
	 */
	function get_default_column_prefix( $prefix = '' ){
		$options = $this->get_options_manager();
		return $options->get_options( WPDDL_Options::COLUMN_PREFIX );
	}

	/**
	 * @return WPDDL_Options_Manager
	 */
	function get_options_manager(){
		return new WPDDL_Options_Manager( WPDDL_Options::COLUMN_PREFIX, array( WPDDL_Options::COLUMN_PREFIX => $this->layout->get_column_prefix() ) );
	}

	/**
	 * @param $rows
	 * @param $target
	 * @param null $args
	 */
	private function add_rows( $rows, $target, $args = null ) {
		if ( $rows ) {
			foreach ( $rows as $row ) {

				if ( $this->_preset_mode ) {
					$row['layout_type'] = 'fluid';
				}

				if ( method_exists( $this, $row['kind'] ) ) {
					$row_object = $this->{$row['kind']}( $row, $target, $args );
					$target->add_row( $row_object );
				}
			}
		}
	}

	/**
	 * @param $cell
	 *
	 * @return mixed|null|WPDD_registered_cell
	 */
	private function create_cell( $cell ) {
		if ( ! isset( $cell['tag'] ) || $cell['tag'] == '' ) {
			$cell['tag'] = 'div';
		}
		/**
		 * Comas separator problem in additionalCssClasses is known and it is
		 * caused by select2 that every once in a while fails data parsing
		 * when passing the select data for save.
		 */
		if ( isset( $cell['additionalCssClasses'] ) ) {
			$cell['additionalCssClasses'] = preg_replace( '/,/', ' ', $cell['additionalCssClasses'] );
		}

		$id                   = isset( $cell['id'] ) ? $cell['id'] : '';
		$additionalCSSClasses = isset( $cell['additionalCssClasses'] ) ? $cell['additionalCssClasses'] : '';

		switch ( $cell['kind'] ) {

			case 'Container':
			case 'Tabs':
			case 'Accordion':
				return $this->build_container( $id, $additionalCSSClasses, $cell );

			case 'Cell':

				$layout = $this->registered_cell_factory->create_cell( isset( $cell['cell_type'] ) ? $cell['cell_type'] : 'spacer', isset( $cell['name'] ) ? $cell['name'] : '', isset( $cell['width'] ) ? $cell['width'] : 1, $additionalCSSClasses, isset( $cell['content'] ) ? $cell['content'] : '', isset( $cell['cssId'] ) ? $cell['cssId'] : '', isset( $cell['tag'] ) ? $cell['tag'] : '', $id );

				if ( ! $layout ) {
					$layout = $this->build_cell( $id, $additionalCSSClasses, $cell );
				}

				$layout->set_column_prefix( $this->layout->get_column_prefix() );

				return $layout;

			default:
				$layout = $this->build_cell( $id, $additionalCSSClasses, $cell );
				$layout->set_column_prefix( $this->layout->get_column_prefix() );

				return $layout;
		}

	}

	/**
	 * @param $row
	 * @param null $target
	 * @param null $args
	 *
	 * @return mixed
	 *
	 * the following methods take their names from the kind attribute of our Row models
	 * so to have js file name == js model class name == 'kind' == render method name
	 */
	private function Row( $row, $target = null, $args = null ) {
		$id = isset( $row['id'] ) ? $row['id'] : null;

		$row_factory = $this->factories->get_factory( 'Row', 'Row' );

		$row_object = $row_factory->build( $id, $row['name'], $row['cssClass'], $row['editorVisualTemplateID'], isset( $row['layout_type'] ) ? $row['layout_type'] : '', isset( $row['cssId'] ) ? $row['cssId'] : '', isset( $row['additionalCssClasses'] ) ? $row['additionalCssClasses'] : '', isset( $row['tag'] ) ? $row['tag'] : '', isset( $row['mode'] ) ? $row['mode'] : 'normal', isset( $row['containerPadding'] ) ? $row['containerPadding'] : true );

		if ( isset( $row['Cells'] ) ) {
			foreach ( $row['Cells'] as $cell ) {
				if ( isset( $cell['row_divider'] ) ) {
					$cell['width'] *= $cell['row_divider'];
				}

				$row_object->add_cell( $this->create_cell( $cell ) );

			}
		}

		return $row_object;
	}

	/**
	 * @param $id
	 * @param $additionalCSSClasses
	 * @param $cell
	 *
	 * @return mixed
	 */
	private function build_container( $id, $additionalCSSClasses, $cell ) {

		$container_factory = $this->factories->get_factory( 'Container', $cell['kind'] );

		$container = $container_factory->build( $id, $cell['name'], $cell['width'], $additionalCSSClasses, $cell['editorVisualTemplateID'], $cell['cssId'], $cell['tag'], $this->layout->get_css_framework(), $cell );

		$this->add_rows( $cell['Rows'], $container, $cell );

		$container->set_column_prefix( $this->layout->get_column_prefix() );

		return $container;
	}

	/**
	 * @param $id
	 * @param $additionalCSSClasses
	 * @param $cell
	 *
	 * @return mixed
	 */
	private function build_cell( $id, $additionalCSSClasses, $cell ) {

		if ( 'spacer' == $cell['cell_type'] || 'undefined' == $cell['cell_type'] ) {
			$cell_factory = $this->factories->get_factory( 'Cell', $cell['cell_type'] );
			$layout       = $cell_factory->build( $id, $cell['name'], $cell['width'], $additionalCSSClasses, '', $this->_preset_mode );
		} else {
			$cell_factory = $this->factories->get_factory( 'Cell', 'Cell' );
			$layout       = $cell_factory->build( $id, $cell['name'], $cell['width'], $additionalCSSClasses, '', $cell['content'], $cell['cssId'], $cell['tag'], $id );
		}

		return $layout;
	}

	/**
	 * @param $row
	 * @param null $target
	 * @param null $args
	 *
	 * @return mixed
	 */
	private function ThemeSectionRow( $row, $target = null, $args = null ) {
		$factory = $this->factories->get_factory( 'Layout', 'theme_section' );

		$row_object = $factory->build( $row['type'], $row['name'], $row, $this->registered_cell_factory->get_registered_theme_sections()->get_theme_section_info( $row['type'] ) );

		return $row_object;
	}

	/**
	 * @param $row
	 * @param null $target
	 * @param null $args
	 *
	 * @return mixed
	 */
	private function Tab( $row, $target = null, $args = null ) {
		$row_factory = $this->factories->get_factory( 'Row', 'tabs_pane' );

		$row_object = $row_factory->build( $row['id'], $row['name'], $row['cssClass'], $row['editorVisualTemplateID'], isset( $row['layout_type'] ) ? $row['layout_type'] : '', isset( $row['cssId'] ) ? $row['cssId'] : '', isset( $row['additionalCssClasses'] ) ? $row['additionalCssClasses'] : '', isset( $row['tag'] ) ? $row['tag'] : '', isset( $row['mode'] ) ? $row['mode'] : 'tab', $row, $args );

		if ( isset( $row['Cells'] ) ) {
			foreach ( $row['Cells'] as $cell ) {
				if ( isset( $cell['row_divider'] ) ) {
					$cell['width'] *= $cell['row_divider'];
				}

				$row_object->add_cell( $this->create_cell( $cell ) );

			}
		}

		return $row_object;
	}

	/**
	 * @param $row
	 * @param null $target
	 * @param null $args
	 *
	 * @return mixed
	 */
	private function Panel( $row, $target = null, $args = null ) {
		$row_factory = $this->factories->get_factory( 'Row', 'accordion_panel' );

		$row_object = $row_factory->build( $row['id'], $row['name'], $row['cssClass'], $row['editorVisualTemplateID'], isset( $row['layout_type'] ) ? $row['layout_type'] : '', isset( $row['cssId'] ) ? $row['cssId'] : '', isset( $row['additionalCssClasses'] ) ? $row['additionalCssClasses'] : '', isset( $row['tag'] ) ? $row['tag'] : '', isset( $row['mode'] ) ? $row['mode'] : 'tab', $row, $args );

		if ( isset( $row['Cells'] ) ) {
			foreach ( $row['Cells'] as $cell ) {
				if ( isset( $cell['row_divider'] ) ) {
					$cell['width'] *= $cell['row_divider'];
				}

				$row_object->add_cell( $this->create_cell( $cell ) );

			}
		}

		return $row_object;
	}
}

/**
 * Class WPDD_ElementFactory
 * provides a factory object to build layout element
 */
abstract class WPDD_ElementFactory {
	protected $prefix = 'WPDD_layout';
	protected $name;

	/**
	 * WPDD_ElementFactory constructor.
	 *
	 * @param string $kind
	 */
	public function __construct( $kind = '' ) {
		$this->name = $this->build_name( $kind );
	}

	/**
	 * @param $kind
	 *
	 * @return string
	 * @throws Exception
	 */
	private function build_name( $kind ) {
		if ( $kind ) {
			$class = $this->prefix . '_' . strtolower( $kind );
		} else {
			$class = $this->prefix;
		}

		if ( class_exists( $class ) ) {
			return $class;
		} else {
			throw new Exception( sprintf( "Class %s Does Not Exist in %s::%s ", $class, __CLASS__, __METHOD__ ) );
		}
	}

	/**
	 * @param int $id
	 *
	 * @return mixed
	 */
	abstract function build( $id = 0 );
}

/**
 * Class WPDD_LayoutFactory
 */
class WPDD_LayoutFactory extends WPDD_ElementFactory {
	/**
	 * @param int $width
	 * @param string $cssframework
	 * @param string $layout_type
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function build( $width = 0, $cssframework = 'bootstrap', $layout_type = '', $args = array() ) {
		return new $this->name( $width, $cssframework, $layout_type, $args );
	}
}

/**
 * Class WPDD_ContainerFactory
 * builds container object (grid, tabs, accordion)
 */
class WPDD_ContainerFactory extends WPDD_ElementFactory {
	/**
	 * @param int $id
	 * @param string $name
	 * @param string $width
	 * @param string $css_class_name
	 * @param string $editor_visual_template_id
	 * @param string $css_id
	 * @param string $tag
	 * @param string $cssframework
	 * @param string $unique_id
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function build( $id = 0, $name = '', $width = '', $css_class_name = '', $editor_visual_template_id = '', $css_id = '', $tag = 'div', $cssframework = 'bootstrap', $unique_id = '', $args = array() ) {
		return new $this->name( $id, $name, $width, $css_class_name, $editor_visual_template_id, $css_id, $tag, $cssframework, $unique_id, $args );
	}
}

/**
 * Class WPDD_CellFactory
 * builds a cell object
 */
class WPDD_CellFactory extends WPDD_ElementFactory {
	/**
	 * @param int $id
	 * @param string $name
	 * @param string $width
	 * @param string $css_class_name
	 * @param string $editor_visual_template_id
	 * @param null $content
	 * @param string $css_id
	 * @param string $tag
	 * @param string $unique_id
	 *
	 * @return mixed
	 */
	public function build(
		$id = 0, $name = '', $width = '', $css_class_name = '', $editor_visual_template_id = '', $content = null, $css_id = '', $tag = 'div', $unique_id = ''
	) {
		return new $this->name( $id, $name, $width, $css_class_name, $editor_visual_template_id, $content, $css_id, $tag, $unique_id );
	}
}

/**
 * Class WPDD_RowFactory
 * builds a row object
 */
class WPDD_RowFactory extends WPDD_ElementFactory {
	/**
	 * @param int $id
	 * @param string $name
	 * @param string $css_class_name
	 * @param string $editor_visual_template_id
	 * @param string $layout_type
	 * @param string $css_id
	 * @param string $additionalCssClasses
	 * @param string $tag
	 * @param string $mode
	 * @param string $containerPadding
	 * @param array $row
	 *
	 * @return mixed
	 */
	public function build( $id = 0, $name = '', $css_class_name = '', $editor_visual_template_id = '', $layout_type = 'fluid', $css_id = '', $additionalCssClasses = '', $tag = 'div', $mode = 'normal', $containerPadding = '', $row = array() ) {
		return new $this->name( $id, $name, $css_class_name, $editor_visual_template_id, $layout_type, $css_id, $additionalCssClasses, $tag, $mode, $containerPadding, $row );
	}
}

/**
 * Class WPDD_FactoryManager
 * provides the right factory object given the element to build
 */
class WPDD_FactoryManager {

	public function __construct() {
	}

	/**
	 * @param string $name
	 * @param string $kind
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_factory( $name = 'Layout', $kind = '' ) {

		if ( ! class_exists( "WPDD_{$name}Factory" ) ) {
			throw new Exception( sprintf( "Class %s Does Not Exist in %s::%s ", "WPDD_{$name}Factory", __CLASS__, __METHOD__ ) );
		}

		$class = "WPDD_{$name}Factory";

		return new $class( $kind );

	}
}
