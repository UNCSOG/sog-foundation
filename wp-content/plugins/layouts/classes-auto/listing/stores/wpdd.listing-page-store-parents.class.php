<?php

class WPDD_Listing_Page_Store_Parents extends WPDD_Listing_Page_Store_Abstract implements WPDD_Listing_Page_Store_Interface{
	protected $assignments_key = 'parents';

	public function get_list(){
		$all_layout_ids = $this->get_all_layouts_ids();
		$list_of_layouts = $this->collect_layouts_details( array_values( $all_layout_ids ) );
		$list_to_return = $this->get_assignment_details( array_flip( $all_layout_ids ), $list_of_layouts );
		$parents = $this->get_current_layouts_parent_list();

		return $this->filter_parents( $parents );
	}

	protected function filter_parents( $parents ){
		$ret = array();
		$free = $this->get_layouts_db_store( self::FREE );
		$free_list = $free->get_free_layouts_list();


		if( ! $free_list || count( $free_list ) === 0 ) return $parents;

		$free_list = array_map( function( $item ){ return (int) $item->post_id; }, $free_list );

		foreach( $parents as $parent ){
			$children = $parent->children;

			if( ! $children || count( $children ) === 0 ) continue;

			$intersect = array_intersect( $children, $free_list );

			if( count( $intersect ) === 0 ){
				$ret[] = $parent;
			}
		}

		return $ret;
	}

	protected function get_all_layouts_ids(){
		$collected_layouts = $this->db->get_col(
			$this->db->prepare(
				"SELECT ID FROM {$this->db->prefix}posts WHERE post_type = %s AND post_status = '{$this->status}'",
				array( 'dd_layouts' )
			)
		);

		return $collected_layouts;
	}

	private function get_assignment_details( $layouts_for_archives, $list_of_layouts ){

		$list_to_return = array();

		$layouts_list = array();

		foreach ( $layouts_for_archives as $key => $value ){

			$tmp_layout = $this->return_layouts_defaults( $key, $list_of_layouts );

			if( $tmp_layout ){
				$layouts_list[$key] = $tmp_layout;
				$list_to_return[] = ( object ) $layouts_list[ $key ];
			}
		}

		return $list_to_return;
	}

	protected function filter_parents_list( $list ){
		return array_filter( $list, function( $item ){ return $item->has_child; } );
	}
}