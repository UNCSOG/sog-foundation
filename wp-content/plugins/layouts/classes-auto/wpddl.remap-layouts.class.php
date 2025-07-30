<?php

class WPDDL_RemapLayouts{
	protected $layout;
	protected $poperty;
	protected $new_value;
	protected $cell_id;
	protected $old_value;
	protected $type;
	protected $remapped = false;
	protected $results = array();
	protected $old_name;
	protected $new_name;

	public function __construct( $args = array() ){

		$this->layout = $args['layout'];
		$this->property = $args['property'];
		$this->cell_id = $args['cell_id'];
		$this->new_value = $args['new_value'];
		$this->old_value = $args['old_value'];
		$this->type = $args['cell_type'];
		$this->old_name = $args['old_name'];
		$this->new_name = $args['new_name'];
	}

	function get_layout(){
		return $this->layout;
	}

	function get_process_results(){
		return $this->results;
	}

	private function get_rows()
	{
		if( $this->layout && $this->layout[Rows] ){
			return $this->layout[Rows];
		} else {
			return array();
		}
	}

	public function process_layouts_properties( )
	{
		$this->remapped = false;
		$rows = $this->get_rows();
		$rows = $this->remap_rows($rows);

		if( null !== $rows ){
			$this->layout[Rows] = $rows;
		}

		return $this->layout;
	}

	private function remap_rows( $rows ){
		foreach ($rows as $key => $row) {

			if( !is_array($row) || isset( $row['Cells'] ) === false ){
				return null;
			}
			$filtered = $this->filtered_cells_recurse( $row[Cells] );
			if (empty($filtered) === false) {
				foreach ($filtered as $ret) {
					$this->remapped = true;
					$rows[$key] = $this->replace_cell($row, $ret);
				}
			}
		}

		if ($this->remapped === true) {
			return $rows;
		}
		return null;
	}

	private function filtered_cells_recurse( $cells ){
		$array = array();
		foreach( $cells as $key => $cell ){
			if( is_array( $cell ) && $cell['kind'] === 'Container' ){
				$container_rows = $this->remap_rows( $cell[Rows] );
				if( null !== $container_rows ){
					$cell[Rows] = $container_rows;
				}
			} else if(
				is_array($cell) &&
				isset( $cell['cell_type'] ) &&
				$cell['cell_type'] === $this->type &&
				isset( $cell['content'] ) &&
				isset( $cell['content'][$this->property] ) &&
				$cell['content'][$this->property] == $this->old_value
			){
				$cell['content'][$this->property] = $this->new_value;
				$array[] = array('cell' => $cell, 'key' => $key, 'new_name' => $this->new_name);
				$this->results[] = (object) array(
					'old_value' => $this->old_value,
					'new_value' => $this->new_value,
					'property' => $this->property,
					'cell_type' => $this->type,
					'id' => $cell['id']
				);
			}
		}

		return $array;
	}

	function replace_cell($row, $cell_data)
	{
		$index = $cell_data['key'];
		$cell = $cell_data['cell'];
		$cell['name'] = $cell_data['new_name'];
		$row[Cells][$index] = $cell;
		return $row;
	}

}