<?php

class WPDDL_LayoutsCleaner
{
	protected $layout_id;
	protected $layout;
	protected $cell_type;
	protected $to_remove;
	protected $removed;
	protected $remapped = false;

	public function __construct($layout_id)
	{
		$this->remapped = false;
		$this->removed = array();
		$this->layout_id = $layout_id;
		$this->layout = WPDD_Layouts::get_layout_settings($this->layout_id, true);
	}

	public function remove_cells_of_type_by_property($cell_type, $property, $callable = array( 'WPDD_Utils', "is_post_published" ) )
	{
		$this->remapped = false;
		$this->cell_type = $cell_type;
		$this->property = $property;
		$rows = $this->get_rows();
		$rows = $this->remap_rows($rows, $callable);

		if( null !== $rows ){
			$this->layout->Rows = $rows;
			WPDD_Layouts::save_layout_settings( $this->layout_id, $this->layout );
		}

		return $this->removed;
	}


	function remove_unwanted($row, $remove)
	{
		$this->to_remove = $remove;

		if (in_array($remove, $row->Cells)) {

			$width = $remove->width;
			$divider = $remove->row_divider;
			$index = array_search($remove, $row->Cells);
			$spacers = WPDD_Utils::create_cells($width, $divider);
			array_splice($row->Cells, $index, 1, $spacers);

		}

		return $row;
	}


	public function remap_rows( $rows, $callable = array( 'WPDD_Utils', "is_post_published" ) )
	{
		foreach ($rows as $key => $row) {
			//$filtered = array_filter($row->Cells, array(&$this, 'filter_orphaned_cells_of_type'));
			if( !is_object($row) || property_exists($row, 'Cells') === false ){
				return null;
			}
			$filtered = $this->filtered_cells_recurse( $row->Cells, $callable );
			if (empty($filtered) === false) {
				foreach ($filtered as $ret) {
					$this->remapped = true;
					$this->removed[] = $ret->name;
					$rows[$key] = $this->remove_unwanted($row, $ret);
				}
			}
		}

		if ($this->remapped === true) {
			return $rows;
		}
		return null;
	}

	function filtered_cells_recurse( $cells, $callable = array( 'WPDD_Utils', "is_post_published" ) ){
		$array = array();
		foreach( $cells as $key => $cell ){
			if( is_object($cell) && $cell->kind === 'Container' ){
				$container_rows = $this->remap_rows( $cell->Rows );
				if( null !== $container_rows ){
					$cell->Rows = $container_rows;
				}
			} else if(
				is_object($cell) &&
				property_exists($cell, 'cell_type') &&
				$cell->cell_type === $this->cell_type &&
				$cell->content &&
				property_exists($cell->content, $this->property) &&
				$cell->content->{$this->property} &&
				call_user_func( $callable, $cell->content->{$this->property} ) === false
			){
				$array[] = $cell;
			}
		}

		return $array;
	}

	protected function get_rows()
	{
		if( $this->layout && $this->layout->Rows ){
			return $this->layout->Rows;
		} else {
			return array();
		}
	}

	function filter_cells_of_type($cell, $callable = array( 'WPDD_Utils', "is_post_published" ) )
	{
		if (is_object($cell) && property_exists($cell, 'cell_type') && $cell->cell_type === $this->cell_type && $cell->content && $cell->content->{$this->property}) {
			return call_user_func( $callable, $cell->content->{$this->property} ) === false;
		}
	}
}