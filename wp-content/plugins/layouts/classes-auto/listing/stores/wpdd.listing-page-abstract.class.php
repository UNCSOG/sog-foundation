<?php
abstract class WPDD_Listing_Page_Store_Abstract implements WPDD_Listing_Page_Store_Interface{
	const SINGLE = 'single';
	const POST_TYPES = 'post_types';
	const ARCHIVES = 'archives';
	const FREE = 'free';
	const PARENTS = 'parents';
	public $db = null;
	protected $status = 'publish';
	protected $assignments_key = null;
	protected $parents_ids = array();
	protected $fetched_ids = array();
	protected $children = array();
	protected $post_type_manager = null;
	protected $archives_loop_manager = null;
	protected $kind = 'Item';

	public function __construct( $status = 'publish' ) {
		$this->init_db();
		$this->status = $status;
	}

	private function init_db(){
		global $wpdb;
		$this->db = $wpdb;
	}

	/**
	 * Return array with key value pairs where key is layout id and value layout name
	 * This is helper function so we don't have to run query for each layout
	 * @param $ids
	 * @return array
	 */
	public function collect_layout_names( $ids ){

		if( isset($ids) && is_array($ids) && count($ids) > 0 ){
			$ids_statement = '';
			$list_of_ids = implode(',', $this->sanitise_array_of_numbers( $ids ) );

			if( $list_of_ids ){
				$ids_statement = "AND ID IN ({$list_of_ids})";
			}

			$collected_layouts = $this->db->get_results(
				$this->db->prepare(
					"SELECT ID, post_title FROM {$this->db->prefix}posts WHERE post_type = %s ". $ids_statement ."",
					array( 'dd_layouts' )
				),
				ARRAY_A
			);
			
			$return = array();
			
			foreach( $collected_layouts as $layout ){
				$return[$layout['ID']] = $layout['post_title'];
			}

			return $return;
		}

		return array();
	}

	/**
	 * @param $ids
	 *
	 * @return array
	 */
	protected function sanitise_array_of_numbers( $ids ){
		if( ! $ids || count( $ids ) === 0 ) return array();
		$ids = array_map( 'esc_attr', $ids );
		$ids = array_map( 'trim', $ids );
		$ids = array_filter( $ids, 'is_numeric' );
		$ids = array_map( 'intval', $ids );
		return $ids;
	}

	/**
	 * Query to return entire layouts objects for every passed ID
	 * @param $ids
	 *
	 * @return array
	 */
	public function collect_layouts_details( $ids ) {
		if ( isset( $ids ) && is_array( $ids ) && count( $ids ) > 0 ) {

			$list_of_ids = implode( ',', $this->sanitise_array_of_numbers( $ids ) );
			$ids_statement = '';

			if( $list_of_ids ){
				$ids_statement = "AND post_id IN ({$list_of_ids})";
			}

			$collected_layouts = $this->db->get_results(
				$this->db->prepare(
					"SELECT post_id, meta_value FROM {$this->db->prefix}postmeta WHERE meta_key = %s ". $ids_statement ."",
					array( '_dd_layouts_settings' )
				),
				ARRAY_A
			);

			$return = array();

			foreach( $collected_layouts as $layout ){
				$return[$layout['post_id']] = $layout['meta_value'];
			}

			$return = array_map(
				function ( $option ) {
					return json_decode( $option );
				},
				$return
			);

			return $return;
		}

		return array();
	}
	/**
	 * Query to return entire layouts objects for every passed slug
	 * @param $list_of_slugs
	 *
	 * @return array
	 */
	public function collect_layouts_details_from_slugs( $list_of_slugs ) {
		if ( isset( $list_of_slugs ) && is_array( $list_of_slugs ) && count( $list_of_slugs ) > 0 ) {

			$values_to_prepare  = array( WPDDL_LAYOUTS_SETTINGS, WPDDL_LAYOUTS_POST_TYPE );
			$slugs_placeholders = array();

			foreach ( $list_of_slugs as $slug ) {
				$slugs_placeholders[] = '%s';
				$values_to_prepare[]  = $slug;
			}

			$slugs_placeholders_string = implode( ',', $slugs_placeholders );
			$collected_layouts         = $this->db->get_results( $this->db->prepare( "SELECT 
            post.ID, 
            meta.meta_value 
            FROM {$this->db->prefix}posts AS post 
            JOIN {$this->db->prefix}postmeta AS meta 
            ON ( post.id = meta.post_id AND meta.meta_key = %s )
            WHERE 
                post.post_type = %s AND 
                post.post_name IN ({$slugs_placeholders_string })", $values_to_prepare ), ARRAY_A );

			$return = array();

			foreach( $collected_layouts as $layout ){
				$return[$layout['ID']] = $layout['meta_value'];
			}

			$return = array_map( function ( $option ) {
				return json_decode( $option );
			}, $return );

			return $return;
		}

		return array();
	}

	/**
	 * Return array with key value pairs where key is layout id and value layout slug
	 * This is helper function so we don't have to run query for each layout
	 * @param $ids
	 *
	 * @return array
	 */
	public function collect_layout_slugs( $ids ){

		if( isset($ids) && is_array($ids) && count($ids) > 0 ){
			$ids_statement = '';
			$list_of_ids = implode(',', $this->sanitise_array_of_numbers( $ids ) );

			if( $list_of_ids ){
				$ids_statement = "AND ID IN ({$list_of_ids})";
			}

			$collected_layouts = $this->db->get_results(
				$this->db->prepare(
					"SELECT ID, post_name FROM {$this->db->prefix}posts WHERE post_type = %s ". $ids_statement ."",
					array( 'dd_layouts' )
				),
				ARRAY_A
			);

			$return = array();

			foreach( $collected_layouts as $layout ){
				$return[$layout['ID']] = $layout['post_name'];
			}

			return $return;
		}

		return array();
	}

	/**
	 * @param $key
	 * @param $list_of_layouts
	 *
	 * @return array
	 */
	protected function return_layouts_defaults( $key, $list_of_layouts ){

		if( ! isset( $list_of_layouts[ $key ] ) || ! is_object( $list_of_layouts[ $key ] ) ) return null;

		$return = array(
			'post_title' => property_exists( $list_of_layouts[ $key ], 'name' ) ? $list_of_layouts[ $key ]->name : '',
			'parent' => $this->maybe_get_parent_id( property_exists( $list_of_layouts[ $key ], 'parent' ) ? $list_of_layouts[ $key ]->parent : null ),
			'kind' => property_exists( $list_of_layouts[ $key ], 'kind' ) ? $list_of_layouts[ $key ]->kind : $this->kind,
			'post_name' => property_exists( $list_of_layouts[ $key ], 'slug' ) ? $list_of_layouts[ $key ]->slug : '',
			'post_status' => $this->status,
			'has_loop' => property_exists( $list_of_layouts[ $key ], 'has_loop' ) ? $list_of_layouts[ $key ]->has_loop : false,
			'has_post_content_cell' => property_exists( $list_of_layouts[ $key ], 'has_post_content_cell' ) ? $list_of_layouts[ $key ]->has_post_content_cell : false,
			'has_child' => property_exists( $list_of_layouts[ $key ], 'has_child' ) ? $list_of_layouts[ $key ]->has_child : false,
			'is_parent' => property_exists( $list_of_layouts[ $key ], 'has_child' ) ? $list_of_layouts[ $key ]->has_child : false,
			'is_child' => property_exists( $list_of_layouts[ $key ], 'parent' ) && $list_of_layouts[ $key ]->parent ? true : false,
			'type' => property_exists( $list_of_layouts[ $key ], 'layout_type' ) ? $list_of_layouts[ $key ]->layout_type : 'normal',
			'date_formatted' => get_the_time( get_option('date_format'), $key ),
			'ID' => $key,
		);

		 // collect ancestors for this Layout recursively
		$this->maybe_collect_parents_ids( $return['parent'] );
		 // collect all children ids in an array if any so that we can manipulate them in Backbone when we change assignments
		$this->maybe_collect_parents_children( $return['parent'], $key );

		return $return;
	}

	/**
	 * @param $parent_slug
	 *
	 * @return int
	 */
	protected function maybe_get_parent_id( $parent_slug ){

		if( $parent_slug ){
			$parent = WPDD_Layouts_Cache_Singleton::get_id_by_name( $parent_slug );

			if ( ! $parent ) {
				$parent = 0;
			}

			return (int) $parent;
		}

		return 0;
	}

	/**
	 * @param $parent_id
	 *
	 * @return array
	 */
	protected function maybe_collect_parents_ids( $parent_id ){
		if( $parent_id && ! isset( $this->parents_ids[$parent_id] ) ){
			$this->parents_ids[$parent_id] = $parent_id;
		}
		return $this->parents_ids;
	}

	/**
	 * @param $parent_id
	 * @param $child_id
	 *
	 * @return array
	 */
	protected function maybe_collect_parents_children( $parent_id, $child_id ){
		if( $parent_id && isset( $this->parents_ids[$parent_id] ) ){

			if( ! isset( $this->children[$parent_id] ) ){
				$this->children[$parent_id] = array();
			}

			if( $child_id ){
				$this->children[$parent_id][] = $child_id;
			}
		}
		return $this->children;
	}

	/**
	 * @return array|null
	 */
	protected function fetch_parents( ){
		// make sure when you recurse there ain't no ids we've already fetched
		$parent_ids = array_diff_assoc( $this->parents_ids, $this->fetched_ids );
		// if there ain't nothing to query don't
		if( count( $parent_ids ) === 0 ) return null;
		// do with parents what you are doing with any other layout
		$parents = $this->collect_layouts_details( $parent_ids );

		$this->fetched_ids = array_merge( $this->fetched_ids, $parent_ids );

		return $parents;
	}

	/**
	 * @param $parents_array
	 * @param $list_of_parents
	 *
	 * @return array
	 */
	protected function set_up_parents_data( $parents_array, $list_of_parents ){

		$list_to_return = array();

		$parents_list = array();

		foreach ( $parents_array as $key => $value ){

			$tmp_layout = $this->return_layouts_defaults( $key, $list_of_parents );

			if( $tmp_layout ){
				$parents_list[$key] = $tmp_layout;

				if( isset( $this->children[$key] ) ){
					$parents_list[$key]['children'] = $this->children[$key];
				}

				$list_to_return[] = ( object ) $parents_list[ $key ];
			}

			// once you are done with parent and its ancestors remove it
			if( isset( $this->parents_ids[$key] ) ) unset( $this->parents_ids[$key] );
			// remove parent's children list too thus we don't need it anymore
			if( isset( $this->children[$key] ) ) unset( $this->children[$key] );
		}

		return $list_to_return;
	}

	/**
	 * @return array
	 */
	protected function recurse_parents(){
		$parents = array();

		// if the parent has a parent fetch it as well
		while( count( $this->parents_ids ) > 0 ){
			$list_of_parents = $this->fetch_parents( );
			// if there are no more parents stop looping
			if( null !== $list_of_parents && count( $list_of_parents ) ) {
				$parents = array_merge( $parents, $this->set_up_parents_data( $this->parents_ids, $list_of_parents ) );

			}
		}

		return $parents;
	}

	/**
	 * @return array
	 */
	public function get_current_layouts_parent_list(){
		return $this->recurse_parents();
	}

	/**
	 * @return mixed
	 */
	abstract public function get_list();

	protected function get_factory_by_group_slug( $group_slug = 'post_types', $status = 'publish' ){

		if( ! $group_slug ) return null;

		try{
			$store_factory = new WPDD_Listing_Stores_Factory( $group_slug, $status );
		} catch( InvalidArgumentException $exception ){
			error_log( $exception->getMessage() );
			$store_factory = null;
		}

		return $store_factory;
	}

	protected function get_layouts_db_store( $group_slug ){

		if( ! $group_slug ) return null;

		$factory = $this->get_factory_by_group_slug( $group_slug, $this->status );

		if( ! $factory ) return null;

		$store = $factory->build();

		return $store;
	}

	/**
	 * Get post type manager instance
	 * @return $this->post_type_manager
	 */
	protected function get_post_types_manager_instance(){
		if( ! $this->post_type_manager ){
			$this->set_post_types_manager_instance();
		}

		return $this->post_type_manager;
	}

	public function set_post_types_manager_instance( $post_types_manager_instance = null ){
		if( null !== $post_types_manager_instance ){
			$this->post_type_manager = $post_types_manager_instance;
		} else {
			$this->post_type_manager = WPDD_Layouts_PostTypesManager::getInstance();
		}
	}

	/**
	 * Get archives loop manager instance
	 * @return $this->archives_loop_manager
	 */
	protected function get_loop_manager_instance(){
		return $this->archives_loop_manager;
	}

	/**
	 * Set archives loop manager instance
	 * @param $archives_loop_manager_instance
	 */
	public function set_loop_manager_instance( $archives_loop_manager_instance ){
		$this->archives_loop_manager = $archives_loop_manager_instance;
	}

	protected function is_wpml_active_and_configured(){
		$wpml = $this->get_wpml_compatibility_instance();
		return $wpml->is_wpml_active_and_configured();
	}

	public function get_wpml_compatibility_instance(){
		return Toolset_WPML_Compatibility::get_instance();
	}
}