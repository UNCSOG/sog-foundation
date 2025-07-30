<?php
class WPDD_Listing_Page_Store_Free extends WPDD_Listing_Page_Store_Abstract implements WPDD_Listing_Page_Store_Interface {
	protected $assignments_key = 'not_assigned';

	public function __construct( $status = 'publish' ) {
		parent::__construct( $status );
	}

	/**
	 * Get Ids of layouts assigned to post types
	 * @return array
	 */
	protected function get_post_types_assigned_ids(){
		$store = $this->get_layouts_db_store( 'post_types' );

		if( ! $store || ! $store instanceof WPDD_Listing_Page_Store_Post_Types ) return array();

		$post_types_array = $store->get_available_layouts();

		return array_keys( $post_types_array  );
	}
	/**
	 * Get Ids of layouts assigned to archives
	 * @return array
	 */
	protected function get_archives_assigned_ids(){
		$store = $this->get_layouts_db_store( 'archives' );

		if( ! $store || ! $store instanceof WPDD_Listing_Page_Store_Archives ) return array();

		$archives_array = $store->get_archive_layouts();

		$archives_array = array_map( 'intval', array_unique( array_values( $archives_array ? $archives_array : array()  ) ) );

		return array_values( $archives_array  );
	}

	/**
	 * Return ids of all layouts used for archives of post types
	 * @return array
	 */
	protected function get_assigned_layouts_ids_list(){
		$archives = $this->get_archives_assigned_ids();
		$post_types = $this->get_post_types_assigned_ids();

		return array_merge( $post_types, $archives );
	}

	/**
	 * Return prepared array with all items that we need to list inside
	 * Unassigned layouts tab on Layouts listing page
	 * @return array
	 */
	public function get_list(){
		$list = $this->get_free_layouts_list();

		if( ! $list || count( $list ) === 0 ) return array();

		$available_layouts = $this->get_available_layouts( $list );

		// Update list with all details
		$list_to_return = $this->get_layouts_details( $available_layouts );

		return array_values( $list_to_return );
	}

	/**
	 * Collect layout details
	 * @param $available_layouts
	 *
	 * @return array
	 */
	private function get_layouts_details( $available_layouts ){
		$list_to_return = array();
		foreach ( $available_layouts as $key => $value ){
			$tmp_layout = $this->return_layouts_defaults( $key, $available_layouts );
			if( $tmp_layout  ){
				$layouts_list[$key] = $tmp_layout;
				$list_to_return[] = ( object ) $layouts_list[ $key ];
			}
		}

		return $list_to_return;
	}

	/**
	 * Returns a list of available layouts with layout settings
	 * @param $layouts_list
	 *
	 * @return array
	 */
	private function get_available_layouts( $layouts_list ){

		if( ! $layouts_list || count( $layouts_list ) === 0 ) return array();

		$output = array();

		foreach( $layouts_list as $layout ){
			$output[$layout->post_id] = json_decode( $layout->layout_settings );
		}

		return $output;
	}

	private function get_post_ids_statement(){
		$post_ids_statement = '';

		$post_ids = $this->get_assigned_layouts_ids_list();

		if( count( $post_ids ) !== 0 ){
			$post_ids = implode( ',', $this->sanitise_array_of_numbers( $post_ids ) );
			$post_ids_statement = "AND post.ID NOT IN ( {$post_ids} )";
		}

		return $post_ids_statement;
	}

	/**
	 * Query to return layout that are unassigned
	 * @return array
	 */
	private function get_free_query(){
		$post_ids_statement = $this->get_post_ids_statement();
		$slugs_statement = '';
		$values_to_prepare  = array( );
		$slugs_placeholders = array();

		$values_to_prepare[] = WPDDL_LAYOUTS_POST_TYPE;

		$post_names = $this->get_all_posts_assigned_layouts_slugs( );

		if( count( $post_names ) !== 0 ){

			foreach ( $post_names as $slug ) {
				$slugs_placeholders[] = '%s';
				$values_to_prepare[]  = $slug;
			}

			$slugs_placeholders_string = implode( ',', $slugs_placeholders );
			$slugs_statement = "AND post.post_name NOT IN ( {$slugs_placeholders_string} )";

		}

		$values_to_prepare[] = WPDDL_LAYOUTS_POST_TYPE;
		$values_to_prepare[] = WPDDL_LAYOUTS_SETTINGS;

		$query = $this->db->prepare(
			"SELECT -- use the inner query to get all the information necessary about the layout as well as the post 
				post.ID as post_id, 
				layout.meta_value as layout_settings
			FROM (
			 
				SELECT 
					post.ID AS post_id,
					post.post_name AS post_name
					
				FROM {$this->db->posts} AS post
						WHERE post.post_type = %s
						AND post.post_status = '{$this->status}'
						". $slugs_statement ."
						". $post_ids_statement ."
			) AS result
				JOIN {$this->db->posts} AS post
					ON ( result.post_id = post.ID )
				JOIN {$this->db->postmeta} AS layout
					ON ( result.post_id = layout.post_id )
			WHERE post.post_type = %s AND layout.meta_key = %s
			",
			$values_to_prepare
		);

		return $query;
	}

	public function get_free_layouts_list(){
		$query = $this->get_free_query();

		$results = $this->db->get_results( $query );

		return $results;
	}

	function get_all_posts_assigned_layouts_slugs( ) {
		$result = $this->db->get_col(
			$this->db->prepare( "
			SELECT DISTINCT pm.meta_value FROM {$this->db->postmeta} AS pm
			WHERE pm.meta_key = '%s'", WPDDL_LAYOUTS_META_KEY
			)
		);

		return $result;
	}
}