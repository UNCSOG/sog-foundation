<?php

class WPDD_Listing_Page_Store_Post_Types extends WPDD_Listing_Page_Store_Abstract implements WPDD_Listing_Page_Store_Interface{
	protected $assignments_key = 'types';

	public function __construct( $status = 'publish' ) {
		parent::__construct( $status );
	}

	public function get_list(){

		$this->set_post_types_manager_instance( WPDD_Layouts_PostTypesManager::getInstance() );

		$available_layouts = $this->get_available_layouts();

		// get layout names for all layouts assigned to archives
		$list_of_layouts = $this->collect_layouts_details( array_keys( $available_layouts ) );

		// Update list with all details
		$list_to_return = $this->get_assignment_details( $available_layouts, $list_of_layouts );

		return array_values( $list_to_return );

	}

	/**
	 * Get list of layouts assigned to post types
	 * @return array
	 */
	public function get_available_layouts(){
		$types_manager = $this->get_post_types_manager_instance();
		$options = $types_manager->get_post_types_options();
		$available_layouts = $this->reformat_options_list( $options );
		return $available_layouts;
	}

	private function reformat_options_list( $options ){

		$list_of_layouts = array();

		foreach( $options as $layout_id => $assigned_type){
			if( $assigned_type && count( $assigned_type ) > 0 ){
				$numeric_id = (int) str_replace('layout_','', $layout_id);
				$list_of_layouts[$numeric_id] = $assigned_type;
			}
		}

		return $list_of_layouts;
	}

	/**
	 * Get details about assignments for all passed layouts
	 * @param $layouts_for_archives
	 * @param $list_of_layouts
	 *
	 * @return array
	 */
	private function get_assignment_details( $layouts_for_archives, $list_of_layouts ){

		$list_to_return = array();

		$layouts_list = array();

		$post_type_manager = $this->get_post_types_manager_instance();

		foreach ( $layouts_for_archives as $key => $value ){

			$tmp_layout = $this->return_layouts_defaults( $key, $list_of_layouts );

			if( $tmp_layout ){

				$layouts_list[$key] = $tmp_layout;

				$loop_display_object = $post_type_manager->get_layout_post_types_object( $key );

				if( is_array( $loop_display_object ) ){
					foreach( $loop_display_object as $post_type_data ){
						$layouts_list[ $key ][$this->assignments_key][] = (object) array(
							'href' => "#", // TODO: do we really need url for listing page, it will cost us additional query
							'label' => $post_type_data['label'],
							'group' => $post_type_data['post_type'],
							'group_name' => 'post_type',
							'missing' => $post_type_data['missing'],
							'post_list' => $post_type_data['post_list'],
							'post_type' => $post_type_data['post_type'],
							'type' => $post_type_data['post_type'],
							'nonce' => $post_type_data['nonce'],
							'layout_id' => $post_type_data['layout_id'],
							'post_num' => $post_type_data['post_num'],
							'message' => $post_type_data['message']
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

}