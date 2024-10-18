<?php
class WPDD_Listing_Page_Store_Single extends WPDD_Listing_Page_Store_Abstract implements WPDD_Listing_Page_Store_Interface{

	protected $assignments_key = 'posts';
	protected $post_types_assigned_objects = array();

	/**
	 * @return array|mixed
	 */
	public function get_list(){

		$this->set_post_types_manager_instance( WPDD_Layouts_PostTypesManager::getInstance() );

		$assigned_to_post_types_ids = $this->get_assigned_to_post_types_ids();
		$assigned_to_archives_ids = $this->get_assigned_to_archives_ids();
		$get_excluded_layouts_slugs = $this->get_layouts_to_exclude_list( $assigned_to_archives_ids, $assigned_to_post_types_ids );

		// get layouts used only for single items
		$collection = $this->get_layouts_assigned_to_single_items( $get_excluded_layouts_slugs );
		$available_layouts = array_keys( $collection );

		// collect layout details
		$list_of_layouts = $this->collect_layouts_details_from_slugs( $available_layouts );

		// Update list with all details
		$list_to_return = $this->get_assignment_details( $collection, $list_of_layouts );

		return $list_to_return;
	}

	/**
	 * @return array
	 */
	private function get_assigned_to_post_types_ids(){
		// get assigned to post types
		$types_manager                     = $this->get_post_types_manager_instance();
		$post_types_assigned_objects = $types_manager->get_post_types_options();
		$assigned_to_post_types_ids        = array();
		foreach( $post_types_assigned_objects as $id => $post_type){
			$layout_id = (int) str_replace('layout_','', $id );
			if( $this->check_if_not_full_assigned( $types_manager, $layout_id,  $post_type ) === false ){
				$assigned_to_post_types_ids[] = $layout_id;
			}
		}

		return $assigned_to_post_types_ids;
	}

	protected function post_type_objects_to_post_types( $layout_id ){

		if( ! isset( $this->post_types_assigned_objects[$layout_id] ) ) return null;

		$post_types_assigned = array_map( array( $this, 'post_types_slugs' ), $this->post_types_assigned_objects[$layout_id] );
		$post_types_assigned = array_values( array_unique( $post_types_assigned ) );
		return $post_types_assigned;

	}

	/**
	 * @param $post_type_manager
	 * @param $layout_id
	 * @param $post_type
	 *
	 * @return bool
	 * This method has a side effect of populating an array where $keys is $layout_id and value its corresponding $post_types_assigned_object if any
	 * since the operation is quite expensive we store the obejcts while checking the assignments to save resources
	 */
	private function check_if_not_full_assigned( $post_type_manager, $layout_id, $post_type ){
		$post_types_assigned_objects = $post_type_manager->get_layout_post_types_object( $layout_id, false );

		if( ! $post_types_assigned_objects ) return true;

		$missing = false;

		foreach( $post_types_assigned_objects as $post_type_object ){
			if( in_array( $post_type_object['post_type'], $post_type ) && (int) $post_type_object['missing'] !== 0 ){
				$missing = true;
				break;
			}
		}

		// store post types object assigned foreach layout which has them
		$this->post_types_assigned_objects[$layout_id] = $post_types_assigned_objects;

		return $missing;
	}

	/**
	 * @return array
	 */
	private function get_assigned_to_archives_ids(){
		// get assigned to archives
		$assigned_to_archives = get_option( WPDDL_GENERAL_OPTIONS );
		$assigned_to_archives_ids = array();

		if( ! $assigned_to_archives ) return $assigned_to_archives_ids;

		foreach( $assigned_to_archives as $archive => $id){
			$assigned_to_archives_ids[] = (int) $id;
		}
		return $assigned_to_archives_ids;
	}

	/**
	 * @param $assigned_to_archives_ids
	 * @param $assigned_to_post_types_ids
	 *
	 * @return array
	 */
	private function get_layouts_to_exclude_list( $assigned_to_archives_ids, $assigned_to_post_types_ids ){
		// craft exclude list
		$layouts_to_exclude = array_merge( $assigned_to_archives_ids, $assigned_to_post_types_ids );
		$layouts_to_exclude = array_unique( $layouts_to_exclude );
		$get_excluded_layouts_slugs = $this->collect_layout_slugs( $layouts_to_exclude );
		return $get_excluded_layouts_slugs;
	}

	/**
	 * @param $layouts_for_singles
	 * @param $list_of_layouts
	 *
	 * @return array
	 */
	private function get_assignment_details( $layouts_for_singles, $list_of_layouts ){

		$list_to_return = array();
		$layouts_list = array();

		foreach ( $list_of_layouts as $key => $value ){

			$tmp_layout = $this->return_layouts_defaults( $key, $list_of_layouts );

			if( $tmp_layout ){

				$layouts_list[ $value->id ] = $tmp_layout;
				// get the list of entirely assigned post types for this layout
				$post_types_assigned = $this->post_type_objects_to_post_types( $key );

				foreach( $layouts_for_singles[ $value->slug ] as $key => $single_post ){
					// if the post type of the current post is not assigned as a whole to it, then list the post as an individual assignment
					if( null === $post_types_assigned || ! in_array(  $single_post['post_type'], $post_types_assigned ) ){
						$layouts_list[ $value->id ][$this->assignments_key][] = (object) array(
							'href' => "#", // TODO: do we really need url for listing page, it will cost us additional query
							'post_title' => $single_post['post_title'] ? $single_post['post_title'] : __('(no title)', 'ddl-layouts'),
							'group' => $single_post['post_type'],
							'group_name' => 'single',
						);
					}
				}

				if( isset( $layouts_list[ $value->id ][$this->assignments_key] ) ){
					$list_to_return[] = ( object ) $layouts_list[ $value->id ];
				}
			}
		}

		return $list_to_return;
	}

	/**
	 * @param $excluded_list
	 *
	 * @return array
	 */
	public function get_layouts_assigned_to_single_items( $excluded_list ){

		$collected_layouts = $this->fetch_collected_layouts( $excluded_list );

		$list_to_return = array();

		foreach( $collected_layouts as $one_layout ){
			$list_to_return[ $one_layout['meta_value'] ][]= $one_layout;
		}

		return $list_to_return;

	}

	/**
	 * @param $excluded_list
	 *
	 * @return mixed
	 */
	public function fetch_collected_layouts( $excluded_list ){
		$excluded_list_string = $this->prepare_exluded_list_string( $excluded_list );

		$query = $this->get_query( $excluded_list_string );

		$collected_layouts = $this->db->get_results(
			$query,
			ARRAY_A
		);

		return $collected_layouts;
	}

	/**
	 * @param $excluded_list
	 *
	 * @return string
	 */
	public function prepare_exluded_list_string( $excluded_list ){
		$excluded_list_string = "";
		foreach($excluded_list as $layout){
			$excluded_list_string .="'{$layout}',";
		}
		$excluded_list_string = rtrim($excluded_list_string, ',');
		return $excluded_list_string;
	}

	/**
	 * @param $excluded_list_string
	 *
	 * @return mixed
	 */
	protected function get_single_query( $excluded_list_string /* Exclude from the result all layouts assigned to an entire post type with no single assignment and archives layouts with no single assignment  */ ){
		$meta_value_statement = '';

		if( $excluded_list_string ){
			$meta_value_statement = "AND postmeta.meta_value NOT IN ({$excluded_list_string})";
		}
		return $this->db->prepare(
			"SELECT 
					postmeta.post_id, 
					postmeta.meta_value,
					post.post_title,
					post.post_name,
					post.post_date_gmt,
					post.post_type
				FROM {$this->db->postmeta} as postmeta
				JOIN {$this->db->posts} as post ON ( postmeta.post_id = post.ID )
				WHERE 
					postmeta.meta_key = %s
					". $meta_value_statement ."",
			WPDDL_LAYOUTS_META_KEY
		);
	}

	/**
	 * @param $excluded_list_string
	 *
	 * @return mixed
	 */
	protected function get_single_wpml_query( $excluded_list_string ){
		$meta_value_statement = '';

		if( $excluded_list_string ){
			$meta_value_statement = "AND postmeta.meta_value NOT IN ({$excluded_list_string})";
		}

		return $this->db->prepare(
			"SELECT 
					postmeta.post_id, 
					postmeta.meta_value,
					post.post_title,
					post.post_name,
					post.post_date_gmt,
					post.post_type
				FROM {$this->db->postmeta} as postmeta
				JOIN {$this->db->posts} as post ON ( postmeta.post_id = post.ID )
				JOIN {$this->db->prefix}icl_translations AS translation_origin
					    ON ( postmeta.post_id = translation_origin.element_id ) 
				WHERE 
					postmeta.meta_key = %s  
					". $meta_value_statement ."
					AND translation_origin.element_type LIKE 'post_%' AND 
					translation_origin.language_code = %s
",
			WPDDL_LAYOUTS_META_KEY,
			apply_filters( 'wpml_current_language', null )
		);
	}

	/**
	 * @param $excluded_list_string
	 *
	 * @return mixed
	 */
	public function get_query( $excluded_list_string ){
		if( $this->is_wpml_active_and_configured() ){
			return $this->get_single_wpml_query( $excluded_list_string );
		} else {
			return $this->get_single_query( $excluded_list_string );
		}
	}

	public function post_types_slugs( $post_type ){
		return $post_type['post_type'];
	}
}