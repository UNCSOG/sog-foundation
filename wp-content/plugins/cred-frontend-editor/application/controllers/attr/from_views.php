<?php

/**
 * Class Toolset_Shortcode_Attr_Item_From_Views
 *
 * Adds support for the $fromfilter and $current values for the item selector attributes.
 *
 * @since m2m
 */
class Toolset_Shortcode_Attr_Item_From_Views extends Toolset_Shortcode_Attr_Item_Id {
	/**
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	private $chain_link;

	/**
	 * @var Toolset_Relationship_Service
	 */
	private $service_relationship;

	/**
	 * Toolset_Shortcode_Attr_Item_From_Views constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $chain_link
	 * @param Toolset_Relationship_Service $service
	 *
	 * @internal param Types_Wordpress_Post $wp_post_api
	 */
	public function __construct( Toolset_Shortcode_Attr_Interface $chain_link, Toolset_Relationship_Service $service ) {
		$this->chain_link           = $chain_link;
		$this->service_relationship = $service;

	}

	/**
	 * @param array $data
	 *
	 * @return $this|int ->chain_link->get();
	 */
	public function get( array $data ) {
		if ( ! $role_slug = $this->handle_attr_synonyms( $data ) ) {
			return $this->chain_link->get( $data );
		}

		if ( 
			$role_slug != '$current' 
			&& $role_slug != '$fromfilter' 
		) {
			// legacy format must start with $
			return $this->chain_link->get( $data );
		}
		
		switch ( $role_slug ) {
			case '$current':
				global $post;
				if ( $post instanceof WP_Post ) {
					return $post->ID;
				}
				return false;
				break;
			case '$fromfilter':
				$post_owner_data = apply_filters( 'wpv_filter_wpv_get_current_post_relationship_frontend_filter_post_owner_data', false );
				if ( $post_owner_data ) {
					foreach( $post_owner_data as $post_type => $post_candidate_list ) {
						if ( count( $post_candidate_list ) > 0 ) {
							return current( $post_candidate_list );
						}
					}
				}
				return false;
				break;
		}
		
		return false;
	}
}