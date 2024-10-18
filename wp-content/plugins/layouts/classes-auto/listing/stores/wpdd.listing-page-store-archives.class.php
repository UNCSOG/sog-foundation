<?php

class WPDD_Listing_Page_Store_Archives extends WPDD_Listing_Page_Store_Abstract implements WPDD_Listing_Page_Store_Interface{

	private $options_key = 'ddlayouts_options';
	protected $assignments_key = 'loops';

	/**
	 * Return prepared array with all items that we need to list inside
	 * Archives tab on Layouts listing page
	 * @return array
	 */
	public function get_list(){

		$this->set_loop_manager_instance( WPDD_layout_post_loop_cell_manager::getInstance() );

		// get list of layouts assigned to archives from wp_options
		$get_archive_layouts = $this->get_archive_layouts();
		// create array format we can work with
		$layouts_for_archives = $this->reformat_array_with_layouts_assignments( $get_archive_layouts );
		// get layout names for all layouts assigned to archives
		$list_of_layouts = $this->collect_layouts_details( $get_archive_layouts );
		// Update list with all details
		$list_to_return = $this->get_assignment_details( $layouts_for_archives, $list_of_layouts );
		//var_dump($list_to_return);
		return array_values( $list_to_return );
	}

	/**
	 * Get list of layouts assigned to archives from options table
	 * @return array
	 */
	public function get_archive_layouts(){
		// get list of layouts assigned to archives from wp_options
		$get_archive_layouts = get_option( $this->options_key );
		return $get_archive_layouts;
	}

	/**
	 * Return assignment details
	 * @param $layouts_for_archives
	 * @param $list_of_layouts
	 *
	 * @return array
	 */
	private function get_assignment_details( $layouts_for_archives, $list_of_layouts ){

		$list_to_return = array();

		$layouts_list = array();

		$loop_manager = $this->get_loop_manager_instance();

		foreach ( $layouts_for_archives as $key => $value ){

			$tmp_layout = $this->return_layouts_defaults( $key, $list_of_layouts );

			if( $tmp_layout ){

				$layouts_list[$key] = $tmp_layout;

				foreach( $value as $assignment ){

					$loop_display_object = $loop_manager->get_loop_display_object( $assignment );

					$helper = new WPDD_Listing_Page_Store_Archives_Filter_Helper( $loop_display_object['title'] );
					// make sure archive appears only once, regardless how many post types uses it
					$filtered = ! isset( $layouts_list[ $key ][$this->assignments_key] ) ? false : array_filter( $layouts_list[ $key ][$this->assignments_key], array( $helper, 'array_filter' ) );

					if( ! $filtered || count( $filtered ) === 0 ){
						$layouts_list[ $key ][$this->assignments_key][] = (object) array(
							'href' => $loop_display_object['href'],
							'title' => $loop_display_object['title'],
							'group' => $loop_display_object['type'],
							'group_name' => $loop_display_object['types'],
						);
					}
				}

				if( isset( $layouts_list[ $key ][$this->assignments_key] ) ){
					$list_to_return[] = ( object ) $layouts_list[ $key ];
				}
			}
		}

		return $list_to_return;
	}


	/**
	 * Helper function to change structure of array with archives layouts
	 * @param $layouts_assigned_to_archives
	 *
	 * @return array
	 */
	private function reformat_array_with_layouts_assignments( $layouts_assigned_to_archives ){

		if( isset( $layouts_assigned_to_archives ) && is_array( $layouts_assigned_to_archives ) ){
			$layouts_for_archives = array();
			foreach( $layouts_assigned_to_archives as $key => $value ){
				$layouts_for_archives[ $value ][] = $key;
			}
			return $layouts_for_archives;
		}

		return array();

	}
}

class WPDD_Listing_Page_Store_Archives_Filter_Helper{
	protected $search_value;

	public function __construct( $value ){
		$this->search_value = $value;
	}

	public function array_filter( $item ){
		return $item->title === $this->search_value;
	}
}