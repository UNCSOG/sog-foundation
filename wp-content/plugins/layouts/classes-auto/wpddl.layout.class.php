<?php

// This is used to create layouts from PHP.

use OTGS\Toolset\Layouts\Util\BootstrapColumnSizes;

class WPDD_layout {

	private $rows;
	private $width;
	private $name;
	private $parent_layout_name;
	private $post_id;
	private $post_slug;
	private $cssframework;
	protected $layout_type = '';
	protected $column_prefix = '';

	function __construct( $width, $cssframework = 'bootstrap', $layout_type = '', $args = array() ) {
		global $wpddlayout;
		$this->layout_type        = $layout_type;
		$this->rows               = array();
		$this->width              = $width;
		$this->name               = '';
		$this->parent_layout_name = '';
		$this->post_id            = 0;
		$this->post_slug          = '';
		$this->cssframework       = $wpddlayout->get_css_framework();

		$this->column_prefix = apply_filters(
			'ddl-get_default_column_prefix',
			BootstrapColumnSizes::get_instance()->get_column_class_prefix( BootstrapColumnSizes::DEFAULT_VALUE )
		);
	}

	function add_row( $row ) {
		if ( $row->get_layout_type() == 'fixed' && ( $row->get_width() != $this->width ) ) {
			global $wpddlayout;
			$wpddlayout->record_render_error( __( 'The row width is different from the layout width. This happens when the child layout does not contain the same number of columns as the child placeholder in the parent layout.', 'ddl-layouts' ) );
		}
		$this->rows[] = $row;
	}

	public function is_private() {
		return $this->layout_type === 'private';
	}

	function get_width() {
		return $this->width;
	}

	function set_column_prefix( $prefix ) {
		$this->column_prefix = $prefix;
	}

	function get_column_prefix( $prefix = null ) {
		return $this->column_prefix;
	}

	function get_css_framework() {
		return $this->cssframework;
	}

	function get_json() {
		return wp_json_encode( $this->get_as_array() );
	}

	function get_as_array() {
		$rows_array = array();
		foreach ( $this->rows as $row ) {
			$rows_array[] = $row->get_as_array();
		}

		return array( 'Rows' => $rows_array );
	}

	function set_context( $target ) {
		if ( $this->post_id ) {
			$context = $this->get_string_context();
			$target->set_context( $context );
		}
	}

	function frontend_render( $target ) {
		$out = '';
		$this->set_context( $target );

		do_action( 'ddl-before_layout_render', $this, $target );
		$target->push_current_layout( $this );
		foreach ( $this->rows as $row ) {
			$out = $row->frontend_render( $target );
		}
		$target->pop_current_layout( $this );
		do_action( 'ddl-after_layout_render', $this, $target );

		return $out;
	}

	function set_name( $name ) {
		$this->name = $name;
	}

	function get_name() {
		return $this->name;
	}

	function set_parent_name( $parent ) {
		$this->parent_layout_name = $parent;
	}

	function get_parent_name() {
		return $this->parent_layout_name;
	}

	function get_parent_layout() {
		global $wpddlayout;

		return apply_filters( 'ddl-get-parent-layout-for-render', $wpddlayout->get_layout( $this->parent_layout_name ), $this->parent_layout_name, $this );
	}

	function set_post_id( $id ) {
		$this->post_id = $id;
	}

	function get_post_id() {
		return $this->post_id;
	}

	function set_post_slug( $slug ) {
		$this->post_slug = $slug;

		foreach ( $this->rows as $row ) {
			$row->set_post_slug( $slug );
		}

	}

	function get_post_slug() {
		return $this->post_slug;
	}

	function get_width_of_child_layout_cell() {

		foreach ( $this->rows as $row ) {
			$child_width = $row->get_width_of_child_layout_cell();
			if ( $child_width > 0 ) {
				return $child_width;
			}
		}

		return 0;

	}

	function get_row_count() {
		return sizeof( $this->rows );
	}

	function get_children() {
		global $wpddlayout;

		$children = array();

		$layout_list = $wpddlayout->get_layout_list();

		foreach ( $layout_list as $layout_id ) {
			$layout = $wpddlayout->get_layout_settings( $layout_id, true );
			if ( $layout ) {
				if ( property_exists( $layout, 'parent' ) && $layout->parent == $this->get_post_slug() ) {
					$children[] = $layout_id;
				}
			}

		}

		return $children;

	}

	function get_string_context() {

		return array(
			'kind'      => 'Layout',
			'name'      => $this->post_id,
			'slug'      => $this->post_slug,
			'title'     => $this->name,
			'edit_link' => admin_url( 'admin.php?page=dd_layouts_edit&amp;layout_id=' . $this->post_id ),
			'post_id'   => $this->is_private() ? $this->post_id : null,
		);

	}

	function register_strings_for_translation( $context = null, $main_layout = false ) {
		if ( ! $context ) {
			$context = $this->get_string_context();
		}

		if ( $main_layout ) {
			do_action( 'wpml_start_string_package_registration', $context );
		}

		foreach ( $this->rows as $row ) {
			$row->register_strings_for_translation( $context );
		}

		if ( $main_layout ) {
			do_action( 'wpml_delete_unused_package_strings', $context );
		}

	}

	function process_cells( $processor ) {
		foreach ( $this->rows as $row ) {
			$row->process_cells( $processor );
		}
	}

	function get_row_with_child() {
		$ret = null;

		foreach ( $this->rows as $row ) {
			if ( $row->is_row_with_child() ) {
				$ret = $row;
				break;
			}
		}

		return $ret;
	}

	function get_cells_of_type( $cell_type ) {
		$ret = array();

		foreach ( $this->rows as $row ) {
			$cell = $row->find_cell_of_type( $cell_type );

			if ( $cell ) {
				$ret[] = $cell;
			}
		}

		return $ret;
	}

	function get_cell_by_id( $cell_id ) {
		if ( ! $cell_id ) {
			return null;
		}

		$ret = null;

		foreach ( $this->rows as $row ) {
			$cell = $row->get_cell_by_id( $cell_id );

			if ( $cell ) {
				$ret = $cell;
				break;
			}
		}

		return $ret;
	}

	function get_row_by_id( $row_id ) {
		if ( ! $row_id ) {
			return null;
		}

		$ret = null;

		foreach ( $this->rows as $row ) {
			if ( $row->get_id() === $row_id ) {
				$ret = $row;

				return $ret;
			} else {
				$ret = $row->get_sub_row_by_id( $row_id );
				if ( $ret ) {
					return $row;
				}
			}
		}

		return $ret;
	}

	function get_all_cells_of_type( $cell_type ) {
		$ret = array();

		foreach ( $this->rows as $row ) {
			$cells = $row->find_cells_of_type( $cell_type );

			if ( is_array( $cells ) && count( $cells ) > 0 ) {
				foreach ( $cells as $cell ) {
					$ret[] = $cell;
				}
			}
		}

		return $ret;
	}


	function has_cell_of_type( $cell_type, $check_parent = false ) {
		if ( $check_parent === false ) {
			$ret = $this->get_cells_of_type( $cell_type );

			return count( $ret ) > 0 ? $ret[0] : false;
		} else {
			$parent = $this->get_parent_layout();
			$ret    = $this->get_cells_of_type( $cell_type );

			return count( $ret ) > 0 ? $ret[0] : false || ( $parent && $parent->has_cell_of_type( $cell_type, true ) );
		}
	}

	function change_full_width_child_layout_row( $child ) {
		// get the row with a child
		$parent_row = $this->get_row_with_child();

		// if the parent row is null don't do nothing
		if ( $parent_row === null ) {
			return false;
		}


		$children_rows        = $child->get_rows();
		$children_rows        = array_values( $children_rows ); // Re-index the array
		$children_rows_length = count( $children_rows );


		// if there are no rows in child don't do nothing
		if ( $children_rows_length === 0 ) {
			return false;
		}

		// Set the context for each row so string translation
		// works in the context of the child layout.
		$context = $child->get_string_context();
		for ( $i = 0; $i < $children_rows_length; $i ++ ) {
			$children_rows[ $i ]->set_context( $context );
		}

		// keep track of the parent's row position
		$index    = 0;
		$ret      = false;
		$count    = count( $this->rows );
		$preserve = array();

		for ( $i = 0; $i < $count; $i ++ ) {
			//remove the parent row we don't need
			if ( $parent_row === $this->rows[ $i ] ) {
				$ret   = true;
				$index = $i;
				unset( $this->rows[ $i ] );
			}

			// remove rows after the parent and store them
			if ( $i > $index && $ret === true ) {
				$preserve[] = $this->rows[ $i ];
				unset( $this->rows[ $i ] );
			}
		}

		// inject the children rows in the parent's rows array
		if ( $ret === true ) {
			for ( $i = 0; $i < $children_rows_length; $i ++ ) {
				$this->rows[ $index + $i ] = $children_rows[ $i ];
			}

			// inject originals rows after the child
			foreach ( $preserve as $row ) {
				$this->rows[] = $row;
			}
			// resort the array with new keys
			ksort( $this->rows );
		}

		// tell the caller we did the job
		// var_dump( $this->rows );
		return $ret;
	}

	function get_rows() {
		return $this->rows;
	}

	function get_layout_type() {
		return $this->layout_type;
	}

	//FIXME: this method is deprecated remove it.
	function get_cells_with_images() {
		$ret = array();

		foreach ( $this->get_rows() as $row ) {
			$cell = $row->find_cells_with_images();

			if ( $cell ) {
				$ret[] = $cell;
			}
		}

		return $ret;
	}

	function get_cells_with_content_field( $field_name ) {

		$ret = array();

		foreach ( $this->get_rows() as $row ) {
			$cell = $row->find_cells_with_content_field( $field_name );

			if ( $cell ) {
				$ret[] = $cell;
			}
		}

		return $ret;
	}

	function convert_sidebar_grid_for_preset() {
		foreach ( $this->get_rows() as $row ) {
			$row->convert_sidebar_grid_for_preset();
		}
	}

}

// Base class for all elements

class WPDD_layout_element {
	protected $id;
	private $name;
	private $css_class_name;
	private $editor_visual_template_id;
	protected $additionalCssClasses = '';
	protected $slug;
	protected $css_id;
	protected $tag;
	protected $layout_type;

	function __construct( $id, $name, $css_class_name = '', $editor_visual_template_id = '', $css_id = '', $tag = 'div' ) {
		$this->set_id( $id );
		$this->name                      = $name;
		$this->css_class_name            = $css_class_name;
		$this->editor_visual_template_id = $editor_visual_template_id;
		$this->css_id                    = $css_id;
		if ( ! $tag ) {
			$tag = 'div';
		}
		$this->tag = $tag;
	}

	private function set_id( $id ) {
		$this->id = $id;
	}

	public function get_id() {
		return $this->id;
	}

	function get_as_array() {
		return array(
			'name'                   => $this->name,
			'cssClass'               => $this->css_class_name,
			'cssId'                  => $this->css_id,
			'editorVisualTemplateID' => $this->editor_visual_template_id,
			'kind'                   => null
		);
	}

	function get_name() {
		return $this->name;
	}

	function set_name( $name ) {
		$this->name = $name;
	}

	function get_css_class_name() {
		return $this->css_class_name;
	}

	function get_css_id() {
		return $this->css_id;
	}

	function get_tag() {
		return $this->tag;
	}

	function getKind() {
		$obj = (object) $this->get_as_array();

		return $obj->kind;
	}

	function register_strings_for_translation( $context ) {

		// do nothing by default
	}

	function process( $processor ) {

	}

	function set_post_slug( $slug ) {
		$this->slug = $slug;
	}

	function get_post_slug( ) {
		return $this->slug;
	}

	function set_column_prefix( $prefix ) {
		$this->column_prefix = $prefix;
	}

	function get_column_prefix() {
		return $this->column_prefix;
	}
}


class WPDD_layout_row extends WPDD_layout_element {

	private $cells;
	public $mode;
	protected $containerPadding;

	function __construct( $id, $name, $css_class_name = '', $editor_visual_template_id = '', $layout_type = 'fluid', $css_id = '', $additionalCssClasses = '', $tag = 'div', $mode = 'normal', $containerPadding = '' ) {
		parent::__construct( $id, $name, $css_class_name, $editor_visual_template_id, $css_id, $tag );
		$this->cells                = array();
		$this->additionalCssClasses = $additionalCssClasses;
		$this->set_layout_type( $layout_type );
		$this->mode             = $mode;
		$this->containerPadding = $containerPadding;
		$this->context          = null;
	}

	function add_cell( $cell ) {
		$this->cells[] = $cell;
	}

	function set_context( $context ) {
		$this->context = $context;
	}

	function get_additional_css_classes() {
		return $this->additionalCssClasses;
	}

	function get_container_padding() {
		return $this->containerPadding;
	}

	function get_as_array() {
		$data = parent::get_as_array();

		$cells_array = array();
		foreach ( $this->cells as $cell ) {
			$cells_array[] = $cell->get_as_array();
		}

		$data['kind']                 = $this->get_kind();
		$data['Cells']                = $cells_array;
		$data['layout_type']          = $this->get_layout_type();
		$data['additionalCssClasses'] = $this->get_additional_css_classes();
		$data['mode']                 = $this->get_mode();
		$data['tag']                  = $this->get_tag();
		$data['css_id']               = $this->get_css_id();

		return $data;
	}

	function get_kind() {
		return 'Row';
	}

	function get_mode() {
		return $this->mode;
	}

	function get_width() {
		$width = 0;
		foreach ( $this->cells as $cell ) {
			$width += $cell->get_width();
		}

		return $width;
	}

	function frontend_render( $target ) {
		do_action( 'ddl-row_start_callback', $this, $target );

		$target->row_start_callback( $this );

		// see if we should use a context for the row.
		$old_context = null;
		if ( $this->context ) {
			$old_context = $target->get_context();
			$target->set_context( $this->context );
		}

		do_action( 'ddl-row_cells_render_start', $this, $this->cells, $target );

		foreach ( $this->cells as $cell ) {
			$cell->frontend_render( $target );
		}

		if ( $old_context ) {
			$target->set_context( $old_context );
		}

		$out = $target->row_end_callback( $this );

		do_action( 'ddl-row_end_callback', $this, $target );

		return $out;
	}

	function set_layout_type( $layout_type ) {
		$this->layout_type = $layout_type;
	}

	function get_layout_type() {
		return $this->layout_type;
	}

	function get_width_of_child_layout_cell() {
		foreach ( $this->cells as $cell ) {
			$width = $cell->get_width_of_child_layout_cell();
			if ( $width > 0 ) {
				return $width;
			}
		}

		return 0;
	}

	public function get_cells() {
		return $this->cells;
	}

	function register_strings_for_translation( $context ) {
		foreach ( $this->cells as $cell ) {
			$cell->register_strings_for_translation( $context );
		}
	}

	function process_cells( $processor ) {
		foreach ( $this->cells as $cell ) {
			$cell->process( $processor );
		}
	}

	function set_post_slug( $slug ) {
	    $this->slug = $slug;
		foreach ( $this->cells as $cell ) {
			$cell->set_post_slug( $slug );
		}
	}

	function is_row_with_child() {
		$bool = false;
		foreach ( $this->cells as $cell ) {
			if ( $cell->get_cell_type() === 'child-layout' ) {
				$bool = true;
				break;
			}
		}

		return $bool;
	}

	function find_cell_of_type( $cell_type ) {
		$cells = $this->get_cells();

		if ( count( $cells ) === 0 ) {
			return false;
		}

		foreach ( $cells as $cell ) {
			if ( $cell->get_cell_type() == $cell_type ) {
				return $cell;
			}

			if ( $cell instanceof WPDD_layout_container ) {
				foreach ( $cell->get_rows() as $row ) {
					$ret = $row->find_cell_of_type( $cell_type );
					if ( $ret ) {
						return $ret;
					}
				}
			}
		}

		return false;
	}

	function get_cell_by_id( $cell_id ) {
		$cells = $this->get_cells();

		if ( count( $cells ) === 0 ) {
			return false;
		}

		foreach ( $cells as $cell ) {
			if ( $cell->get_unique_id() == $cell_id || $cell->get_id() == $cell_id ) {
				return $cell;
			}

			if ( $cell instanceof WPDD_layout_container ) {
				foreach ( $cell->get_rows() as $row ) {
					$ret = $row->get_cell_by_id( $cell_id );
					if ( $ret ) {
						return $ret;
					}
				}
			}
		}

		return false;
	}

	function get_sub_row_by_id( $row_id ) {
		$cells = $this->get_cells();

		if ( count( $cells ) === 0 ) {
			return false;
		}

		foreach ( $cells as $cell ) {
			if ( $cell instanceof WPDD_layout_container ) {
				$row = $cell->get_row_by_id( $row_id );
				if ( $row ) {
					return $row;
				}
			}
		}

		return false;
	}

	function find_cells_of_type( $cell_type ) {
		$cells = $this->get_cells();

		if ( count( $cells ) === 0 ) {
			return false;
		}

		$ret = array();

		foreach ( $cells as $cell ) {
			if ( $cell->get_cell_type() == $cell_type ) {
				$ret[] = $cell;
			}

			if ( $cell instanceof WPDD_layout_container ) {

				foreach ( $cell->get_rows() as $row ) {
					$ret = array_merge( $ret, $row->find_cells_of_type( $cell_type ) );
				}
			}
		}

		return $ret;
	}

	//FIXME: this method is deprecated remove it.
	function find_cells_with_images() {
		$cells = $this->get_cells();

		if ( count( $cells ) === 0 ) {
			return false;
		}

		foreach ( $cells as $cell ) {

			if ( method_exists( $cell, 'has_image' ) === false ) {
				continue;
			}

			if ( $cell->has_image() ) {
				return $cell;
			}

			if ( $cell instanceof WPDD_layout_container ) {
				foreach ( $cell->get_rows() as $row ) {
					$ret = $row->find_cells_with_images();
					if ( $ret ) {
						return $ret;
					}
				}
			}
		}

		return false;
	}


	function find_cells_with_content_field( $field_name ) {
		$cells = $this->get_cells();

		if ( count( $cells ) === 0 ) {
			return false;
		}

		foreach ( $this->get_cells() as $cell ) {
			if ( $cell->get_content_field_value( $field_name ) !== null ) {
				return $cell;
			}

			if ( $cell instanceof WPDD_layout_container ) {
				foreach ( $cell->get_rows() as $row ) {
					$ret = $row->find_cells_with_content_field( $field_name );
					if ( $ret ) {
						return $ret;
					}
				}
			}
		}

		return false;
	}

	function convert_sidebar_grid_for_preset() {

		for ( $i = 0; $i < count( $this->cells ); $i ++ ) {
			$cell = $this->cells[ $i ];
			if ( $cell instanceof WPDD_layout_container ) {
				if ( $cell->get_name() == 'Sidebar' ) {
					$new_cell = new WPDD_layout_spacer( $cell->get_name(), $cell->get_width(), $cell->get_css_class_name(), '', true );

					$this->cells[ $i ] = $new_cell;
				} else {
					foreach ( $cell->get_rows() as $row ) {
						$row->convert_sidebar_grid_for_preset();
					}
				}
			}
		}
	}


}

class WPDD_layout_container_row extends WPDD_layout_row {

	protected $random_id = 0;

	function __construct( $id, $name, $css_class_name = '', $editor_visual_template_id = '', $layout_type = 'fixed', $css_id = '', $additionalCssClasses = '', $tag = 'div', $mode = 'normal', $row = array(), $args = array() ) {
		parent::__construct( $id, $name, $css_class_name, $editor_visual_template_id, $layout_type, $css_id, $additionalCssClasses, $tag, $mode );
		$this->random_id = uniqid();
	}

	function register_strings_for_translation( $context ) {

		if ( $this->get_id() ) {
			do_action( 'wpml_register_string', $this->get_name(), $this->get_id() . '_title', $context, $this->get_name() . ' - Title', 'LINE' );
		}
		parent::register_strings_for_translation( $context );
	}

	function get_translated_content( $context, $translate_method = null ) {

		$title = $this->get_name();

		if ( ! $translate_method ) {
			$translate_method = new WPDDL_Translate_String_Via_Filter();
		}

		$title = $translate_method->translate( $title, $this->get_id() . '_title', $context );

		return array( 'title' => $title );
	}

	function set_content( $content ) {
		$this->set_name( $content['title'] );
	}
}

class WPDD_layout_cell extends WPDD_layout_element {

	protected $width;
	protected $content;
	protected $cell_type;
	protected $unique_id;
	protected $column_prefix = '';

	function __construct(
		$id, $name, $width, $css_class_name = '', $editor_visual_template_id = '', $content = null, $css_id = '', $tag = 'div', $unique_id = ''
	) {

		parent::__construct( $id, $name, $css_class_name, $editor_visual_template_id, $css_id, $tag );

		$this->width         = $width;
		$this->content       = $content;
		$this->cell_type     = null;
		$this->unique_id     = $unique_id;

		$this->column_prefix = apply_filters(
			'ddl-get_default_column_prefix',
			BootstrapColumnSizes::get_instance()->get_column_class_prefix( BootstrapColumnSizes::DEFAULT_VALUE )
		);

		$this->set_additional_css_classes( $css_class_name );
	}

	function set_content( $content ) {
		$this->content = $content;
	}

	function get_content() {
		return $this->content;
	}

	function get_width() {
		return $this->width;
	}

	function set_cell_type( $cell_type ) {
		$this->cell_type = $cell_type;
	}

	function get_cell_type() {
		return $this->cell_type;
	}

	function get_as_array() {
		$data                         = parent::get_as_array();
		$data['kind']                 = 'Cell';
		$data['width']                = $this->width;
		$data['content']              = $this->content;
		$data['cell_type']            = $this->cell_type;
		$data['tag']                  = $this->get_tag();
		$data['additionalCssClasses'] = $this->get_additional_css_classes();
		$data['css_id']               = $this->get_css_id();
		$data['column_prefix']        = $this->get_column_prefix();

		return $data;
	}

	function get_cell_data() {
		return $this->get_as_array();
	}

	function get_additional_css_classes() {
		return $this->additionalCssClasses;
	}

	function set_additional_css_classes( $additionalCssClasses ) {
		$this->additionalCssClasses = $additionalCssClasses;
	}

	function set_column_prefix( $prefix ) {
		$this->column_prefix = $prefix;
	}

	function get_column_prefix() {
		return $this->column_prefix;
	}

	/**
 * @param $param
 *
 * @return null
 */
	function get( $param ) {
		if ( isset( $this->content[ $param ] ) ) {
			return $this->content[ $param ];
		} else {
			return null;
		}
	}

	/**
	 * @param $param
	 * @param $value
	 *
	 * @return null
	 */
	function get_where( $param, $value ) {
		if ( isset( $this->content[ $param ] ) && $this->content[ $param ] === $value) {
			return $this->content[ $param ];
		} else {
			return null;
		}
	}

	/**
	 * @param $param
	 * @param $value
	 *
	 * @return bool
	 *
	 */
	function set( $param, $value ) {
		if ( ! is_array( $this->content ) ) {
			return false;
		}
		$this->content[ $param ] = $value;

		return $value;
	}

	function frontend_render( $target ) {

		do_action( 'ddl_before_cell_start_callback', $this, $target );

		$out = $target->cell_start_callback( $this->get_css_class_name(), $this->width, $this->get_css_id(), $this->get_tag(), $this );

		do_action( 'ddl_before_frontend_render_cell', $this, $target );

		$out .= $this->frontend_render_cell_content( $target );

		do_action( 'ddl_after_frontend_render_cell', $this, $target );

		$out .= $target->cell_end_callback( $this->get_tag(), $this );

		do_action( 'ddl_after_cell_end_callback', $this, $target );

		return $out;
	}

	function content_content_contains( $strings = array() ) {

		$bool = false;

		$content = $this->get( 'content' );

		if ( null === $content ) {
			return $bool;
		}

		$search = apply_filters( 'ddl-content_content_contains_search_strings', $strings, $strings, $this );

		foreach ( $search as $check ) {
			if ( strpos( $content, $check ) !== false ) {
				$bool = true;
				break;
			}
		}

		return apply_filters( 'ddl-content_content_contains', $bool, $strings, $this );
	}

	function frontend_render_cell_content( $target ) {

	}

	function get_width_of_child_layout_cell() {
		return 0;
	}

	function get_unique_id() {
		return $this->unique_id;
	}

	//FIXME: this method is deprecated remove it.
	function has_image() {
		$regex   = '/<img[^>]*?/siU';
		$content = $this->get_content();

		if ( ! $content ) {
			return false;
		}

		if ( is_string( $content ) ) {
			$check = $content;
		} else {
			$check = isset( $content['content'] ) ? $content['content'] : '';
		}


		if ( preg_match_all( $regex, $check, $matches, PREG_SET_ORDER ) ) {
			return true;
		} else {
			return false;
		}

		return false;
	}

	function get_content_field_value( $field_name ) {
		$content = $this->get_content();

		if ( ! is_array( $content ) ) {
			return null;
		}

		if ( ! array_key_exists( $field_name, $content ) ) {
			return null;
		}

		if ( isset( $content[ $field_name ] ) ) {
			return $content[ $field_name ];
		}

		return null;
	}

	function set_content_field_value( $field_name, $field_value ) {
		$content                = $this->get_content();
		$content[ $field_name ] = $field_value;
		$this->set_content( $content );
	}

	function check_if_cell_renders_post_content() {
		return apply_filters( 'ddl-cell-check_if_cell_renders_post_content', false, $this );
	}
}


class WPDD_layout_container extends WPDD_layout_cell {

	protected $layout;

	function __construct( $id, $name, $width, $css_class_name = '', $editor_visual_template_id = '', $css_id = '', $tag = 'div', $cssframework = 'bootstrap', $unique_id = '' ) {
		parent::__construct( $id, $name, $width, $css_class_name, $editor_visual_template_id, null, $css_id, $tag, $id );
		$this->layout = new WPDD_layout( $width, $cssframework );
	}

	function add_row( $row ) {
		if ( $row->get_layout_type() == 'fixed' && ( $row->get_width() != $this->layout->get_width() ) ) {
			global $wpddlayout;
			$wpddlayout->record_render_error( __( 'The row width is different from the layout width. This happens when the child layout does not contain the same number of columns as the child placeholder in the parent layout.', 'ddl-layouts' ) );
		}
		$this->layout->add_row( $row );
	}

	function set_post_slug( $slug ) {
		$this->layout->set_post_slug( $slug );
	}

	function get_width() {
		return $this->layout->get_width();
	}


	function get_as_array() {
		$data = parent::get_as_array();

		$data['kind'] = 'Container';
		$data         = array_merge( $data, $this->layout->get_as_array() );

		return $data;
	}

	function frontend_render_cell_content( $target ) {
		return apply_filters( 'ddl-frontend_render_cell_content', $this->layout->frontend_render( $target ), $this, $target );
	}

	function get_width_of_child_layout_cell() {
		return $this->layout->get_width_of_child_layout_cell();
	}

	function register_strings_for_translation( $context ) {
		$this->layout->register_strings_for_translation( $context );
	}

	function process_cells( $processor ) {
		$this->layout->process_cells( $processor );
	}

	function process( $processor ) {
		$this->layout->process_cells( $processor );
	}

	function get_rows() {
		return $this->layout->get_rows();
	}

	function get_row_by_id( $row_id ) {
		$rows = $this->get_rows();

		if ( count( $rows ) === 0 ) {
			return false;
		}

		foreach ( $rows as $row ) {
			if ( $row->get_id() == $row_id ) {
				return $row;
			}
		}

		return false;
	}

	function frontend_render( $target ) {

		do_action( 'ddl_before_cell_start_callback', $this, $target );

		$target->cell_start_callback( $this->get_css_class_name(), $this->width, $this->get_css_id(), $this->get_tag(), $this );

		do_action( 'ddl_before_frontend_render_cell', $this, $target );

		$out = $this->frontend_render_cell_content( $target );

		do_action( 'ddl_after_frontend_render_cell', $this, $target );

		$out .= $target->cell_end_callback( $this->get_tag(), $this );

		do_action( 'ddl_after_cell_end_callback', $this, $target );

		return $out;
	}

}

class WPDD_layout_spacer extends WPDD_layout_element {

	private $width;
	private $_preset_mode;
	private $cell_type = 'spacer';
	private $unique_id;

	function __construct( $id, $name, $width, $css_class_name = '', $css_id = '', $preset_mode = false, $unique_id = '' ) {
		parent::__construct( $id, $name, $css_class_name, $css_id );
		$this->width        = $width;
		$this->_preset_mode = $preset_mode;
		$this->unique_id    = $unique_id;
	}

	/**
	 * @return mixed
	 * added for compatibility
	 */
	function get_column_prefix() {
		return $this->get_column_prefix();
	}

	function get_width() {
		return $this->width;
	}

	function get_unique_id() {
		return $this->unique_id;
	}

	function get_as_array() {
		$data = parent::get_as_array();

		$data['kind']      = 'Cell';
		$data['width']     = $this->width;
		$data['cell_type'] = 'spacer';

		return $data;
	}

	function frontend_render( $target ) {
		if ( $this->_preset_mode ) {
			// render as a div for display in the preset selection on new layouts dialog.
			$out = $target->cell_start_callback( $this->get_css_class_name(), $this->width, $this->get_css_id(), 'div', $this );

			$out .= $target->cell_content_callback( $this->get_name(), $this );

			$out .= $target->cell_end_callback( 'div' );

			return $out;
		} else {
			return $target->spacer_start_callback( $this->width );
		}
	}

	function get_cell_type() {
		return $this->cell_type;
	}

	function get_width_of_child_layout_cell() {
		return 0;
	}
}

class WPDD_layout_undefined extends WPDD_layout_spacer {
	// prevents factories errors
}

// Cell factory class to be extended
class WPDD_layout_cell_factory {

	public function get_editor_cell_template() {
		// return an empty cell template if this function is not
		// overriden
		return '';
	}

	public function element_name( $param ) {
		// returns the name of the input element used in the dialog
		return 'ddl-layout-' . $param;
	}

}

// Cell factory class to be extended
abstract class WPDD_layout_text_cell_factory extends WPDD_layout_cell_factory {
	/* the build method must fill this variable*/
	protected $cell = null;

	abstract function build( $name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '' );

	public function __construct() {

	}

	protected function _dialog_template() {
		$wpml_status = new WPDDL_WPML_Status();
		if ( $wpml_status->is_string_translation_active() ) {
			$this->print_controls();
		}
	}

	protected function print_controls() {
		$user_selected            = $this->cell instanceof WPDD_layout_text_based_cell ? $this->cell->get_user_option_for_how_to_handle_wpml_strings() : 'all';
		$match_settings_with_data = true;

		if ( $user_selected === 'all' ) {
			$has_translatable_strings = false;
			$match_settings_with_data = true;
		} else if ( $user_selected === 'selected' ) {
			$has_translatable_strings = true;
			$match_settings_with_data = true;
		} else if ( $user_selected === 'none' ) {
			$has_translatable_strings = false;
			$match_settings_with_data = false;
		}

		$option_name = get_ddl_name_attr( 'wpml_strings_option' );

		?>
        <div class="ddl-form-item">
            <fieldset>
                <div class="fields-group">
                    <h4><?php _e( 'Translation options', 'ddl-layouts' ); ?></h4>
                    <label class="radio" for="<?php echo $option_name; ?>">
                        <p><input type="radio" name="<?php echo $option_name; ?>"
                                  id="<?php the_ddl_name_attr( 'wpml_strings_option_all' ); ?>"
                                  value="all" <?php echo $match_settings_with_data && ! $has_translatable_strings ? 'checked' : ''; ?> />
							<?php _e( 'The entire cell will appear as a field for translation', 'ddl-layouts' ); ?>
                        </p>
                        <p><input type="radio" name="<?php echo $option_name; ?>"
                                  id="<?php the_ddl_name_attr( 'wpml_strings_option_select' ); ?>"
                                  value="selected" <?php echo $match_settings_with_data && $has_translatable_strings ? 'checked' : ''; ?> />
							<?php _e( 'Only text in [wpml-string] will appear for translation', 'ddl-layouts' ); ?>
                        </p>
                        <p><input type="radio" name="<?php echo $option_name; ?>"
                                  id="<?php the_ddl_name_attr( 'wpml_strings_option_none' ); ?>"
                                  value="none" <?php echo ! $match_settings_with_data && ! $has_translatable_strings ? 'checked' : ''; ?> />
							<?php _e( 'Don\'t translate anything in this cell', 'ddl-layouts' ); ?></p>
                    </label>
                </div>
            </fieldset>
        </div>
		<?php
	}
}


class WPDDL_CellLoader {
	private static $instance;

	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new WPDDL_CellLoader();
		}

		return self::$instance;
	}

	private function __construct() {
		// this should happen at least after "after_setup_theme" -> 11 because of features API which happens at 11 for Toolset Common which happens at "after_setup_theme" -> 10
		add_action( 'after_setup_theme', array( &$this, 'load_cells' ), 12 );
	}

	function dd_layouts_register_container_factory( $factories ) {
		$factories['ddl-container'] = new WPDD_layout_container_factory;

		return apply_filters( 'ddl-dd_layouts_register_container_factory', $factories );
	}

	function load_cells() {
		// include abstract types
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_toolset_based.class.php';
		// include real cell types
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_cell-grid-cell.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_text.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_slider.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_video.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_comments.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_cred.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_cred_user.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_cred_relationship.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_imagebox.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.missing_cell_type.class.php';

		add_filter( 'dd_layouts_register_cell_factory', array( &$this, 'dd_layouts_register_container_factory' ) );
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.child_layout.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_tabs-cell.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_accordion-cell.class.php';

		//require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_post_content.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_views_content_template.class.php';
		//require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_post_loop.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_views_loop.class.php';

		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_menu.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_widget.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_widget_area.class.php';

		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_views-grid-cell.class.php';
		require_once WPDDL_CELL_TYPES_ABSPATH . 'wpddl.cell_post-content-cell.class.php';
		//require_once WPDDL_ABSPATH . '/reference-cell/reference-cell.php';
	}
}

WPDDL_CellLoader::getInstance();
