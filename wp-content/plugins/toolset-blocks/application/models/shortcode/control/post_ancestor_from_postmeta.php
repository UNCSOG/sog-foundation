<?php

/**
 * Manage the wpv-control-post-ancestor shortcode and its legacy wpv-control-item alias, when m2m is not active.
 *
 * @since m2m
 */
class WPV_Shortcode_Control_Post_Ancestor_From_Postmeta extends WPV_Shortcode_Control_Post_Ancestor implements WPV_Shortcode_Interface {
	
	/**
	 * Get the shortcode output value.
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return string
	 *
	 * @since m2m
	 */
	public function get_value( $atts, $content = null ) {
		return parent::get_value( $atts, $content );
	}
	
	/**
	 * Get the posts to include in any ancestor selector but the tree roof.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	protected function get_relationship_tree_item_options() {
		global $wpdb;
		$ancestor_position_in_tree = array_keys( $this->relationship_pieces, $this->user_atts['ancestor_type'] );
		if ( empty( $ancestor_position_in_tree ) ) {
			array();
		}
		$ancestor_ancestors = array_slice( $this->relationship_pieces, 0, $ancestor_position_in_tree[0] );
		$ancestor_ancestors_types = array();
		foreach ( $ancestor_ancestors as $ancestor_item )  {
			$ancestor_item_data = explode( '@', $ancestor_item );
			$ancestor_ancestors_types[] = $ancestor_item_data[0];
			$this->class_array[] = 'js-wpv-' . $ancestor_item_data[0] . '-watch';
		}

		$ancestor_parents = wpcf_pr_get_belongs( $this->ancestor_data['type'] );
		if (
			$ancestor_parents != false
			&& is_array( $ancestor_parents )
		) {
			$ancestor_parents_types = array_merge( array_values( array_keys( $ancestor_parents ) ) );
		}

		$real_influencer_array = array_intersect( $ancestor_parents_types, $ancestor_ancestors_types );
		if ( empty( $real_influencer_array ) ) {
			return array();
		}
		
		$real_influencer = reset( $real_influencer_array );
		
		// We could merge those two queries with a proper database management...
		// But the first one does WPML automatically :-/ 
		// I could get posts without their postmeta or taxonomy cache, might be needed or expected?
		$query_here = array();
		$query_here['posts_per_page'] = -1;
		$query_here['paged'] = 1;
		$query_here['offset'] = 0;
		$query_here['fields'] = 'ids';
		$query_here['post_type'] = $this->ancestor_data['type'];
		$query_here['meta_query'] = array();
		

		if (
			isset( $_GET[ $this->user_atts['url_param'] . '-' . $real_influencer ] )
			&& ! empty( $_GET[ $this->user_atts['url_param'] . '-' . $real_influencer ] )
			&& $_GET[ $this->user_atts['url_param'] . '-' . $real_influencer ] != array( 0 )
		) {
			$real_influencer_selected = $_GET[ $this->user_atts['url_param'] . '-' . $real_influencer ];
			$real_influencer_selected = is_array( $real_influencer_selected ) 
				? $real_influencer_selected : 
				array( $real_influencer_selected );
			foreach ( $real_influencer_selected as $real_influencer_selected_item ) {
				$query_here['meta_query'][] = array(
					'key' => '_wpcf_belongs_' . $real_influencer . '_id',
					'value' => $real_influencer_selected_item
				);
			}
		}
		
		if ( empty( $query_here['meta_query'] ) ) {
			return array();
		}

		$query_here['meta_query']['relation'] = 'OR';
		$aux_relationship_query = new WP_Query( $query_here );
		if (
			is_array( $aux_relationship_query->posts )
			&& count( $aux_relationship_query->posts )
		) {
			// If there are posts with those requirements, get their ID and post_title
			// We do not really need sanitization here, as $aux_relationship_query->posts only contains IDs come from the database, but still
			$values_to_prepare = array();
			$aux_rel_count = count( $aux_relationship_query->posts );
			$aux_rel_placeholders = array_fill( 0, $aux_rel_count, '%d' );
			foreach ( $aux_relationship_query->posts as $aux_rel_id ) {
				$values_to_prepare[] = $aux_rel_id;
			}
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title
					FROM {$wpdb->posts}
					WHERE post_status = 'publish' AND ID IN (" . implode( ",", $aux_rel_placeholders ) . ")
					ORDER BY {$this->orderby} {$this->order}",
					$values_to_prepare
				)
			);
		}

		return array();
	}
	
	/**
	 * Get the cache target data for search dependency and counters.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	protected function set_target_for_cache_extended_for_post_relationship() {
		return array( 'cf' => array( '_wpcf_belongs_' . $this->relationship_tree_ground . '_id' ) );
	}
	
}