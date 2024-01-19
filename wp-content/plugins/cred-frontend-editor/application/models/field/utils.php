<?php

/**
 * Fields Model contains all methods referred to form fields
 *
 * @since 1.9.3
 */
class CRED_Field_Utils {

	private static $instance;
	private $wpml_compatibility;
	private $query_posts_by_title;

	/**
	 * CRED_Field_Utils constructor.
	 *
	 * @param CRED_Frontend_Select2_Query_Posts_By_Title|null $query_posts_by_title
	 * @param Toolset_WPML_Compatibility|null $toolset_wpml_compatibility
	 */
	public function __construct( CRED_Frontend_Select2_Query_Posts_By_Title $query_posts_by_title = null, Toolset_WPML_Compatibility $toolset_wpml_compatibility = null ) {
		$this->wpml_compatibility = ( null == $toolset_wpml_compatibility ) ? Toolset_WPML_Compatibility::get_instance() : $toolset_wpml_compatibility;
		$this->query_posts_by_title = ( null == $query_posts_by_title ) ? CRED_Frontend_Select2_Query_Posts_By_Title::get_instance() : $query_posts_by_title;
	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Retrieves WP_Post by post_id
	 *
	 * @param int $post_id
	 *
	 * @return WP_Post
	 */
	public function get_parent_by_post_id( $post_id ) {
		$src_lang = isset( $_REQUEST[ 'source_lang' ] ) ? sanitize_text_field( $_REQUEST[ 'source_lang' ] ) : apply_filters( 'wpml_current_language', null );
		$lang = isset( $_REQUEST[ 'lang' ] ) ? sanitize_text_field( $_REQUEST[ 'lang' ] ) : $src_lang;

		do_action( 'wpml_switch_language', $lang );

		return get_post( $post_id );
	}

	/**
	 * Retrieves the number of posts on a given type.
	 *
	 * @param string $post_type
	 * @param int $limit
	 * @return int|null Number of posts, null on failure.
	 */
	public function get_count_posts( $post_type, $limit = 0 ) {
		$src_lang = isset( $_REQUEST[ 'source_lang' ] ) ? sanitize_text_field( $_REQUEST[ 'source_lang' ] ) : apply_filters( 'wpml_current_language', null );
		$lang = isset( $_REQUEST[ 'lang' ] ) ? sanitize_text_field( $_REQUEST[ 'lang' ] ) : $src_lang;

		//TODO: trying to use wpml_switch_language action hook and wp_count_posts function
		//do_action( 'wpml_switch_language', $lang );
		//$result = wp_count_posts( $post_type );
		//return $result->publish;

		$is_wpml_active_and_configured = $this->wpml_compatibility->is_wpml_active_and_configured( false );

		global $wpdb;
		$values_to_prepare = array();
		$sql_join = "";
		$sql_where = "";
		$sql_limit = "";

		//$lang control is not enough because will be set if i have wpml and i disable it later.
		if ( isset( $lang )
			&& $is_wpml_active_and_configured
			&& ! is_admin()
		) {
			$sql_join .= " JOIN {$wpdb->prefix}icl_translations icl_t ";
			$sql_where .= " AND p.ID = icl_t.element_id AND icl_t.language_code = %s ";
			$values_to_prepare[] = $lang;
		}

		$values_to_prepare[] = $post_type;

		if ( $limit > 0 ) {
			$sql_limit .= "LIMIT %d";
			$values_to_prepare[] = $limit;
		}

		$query = $wpdb->prepare( "SELECT count(DISTINCT p.ID) FROM {$wpdb->posts} as p {$sql_join} WHERE 1=1 {$sql_where} AND p.post_type=%s and p.post_status='publish' {$sql_limit}", $values_to_prepare );
		$count = $wpdb->get_var( $query );

		return ( null === $count ) ? null : (int) $count;
	}

	/**
	 * Function responsible to get posts by post_type and limit option
	 *
	 * @param string $post_type
	 * @param int $limit
	 *
	 * @return WP_Post[]
	 */
	public function get_posts_by_post_type( $post_type, $limit = - 1 ) {
		$src_lang = isset( $_REQUEST[ 'source_lang' ] ) ? sanitize_text_field( $_REQUEST[ 'source_lang' ] ) : apply_filters( 'wpml_current_language', null );
		$lang = isset( $_REQUEST[ 'lang' ] ) ? sanitize_text_field( $_REQUEST[ 'lang' ] ) : $src_lang;

		do_action( 'wpml_switch_language', $lang );

		$args = array(
			'numberposts' => $limit,
			'post_type' => $post_type,
			'order' => 'DESC',
		);

		$results = get_posts( $args );

		return $results;
	}

	/**
	 * Retrieves potential parents referred to a specific post_type that include
	 * WPML translation. This function can be called directly or as ajax callback.
	 *
	 * @param $post_type
	 * @param string $wpml_name
	 * @param string $wpml_context
	 * @param int $limit
	 * @param string $query
	 * @param array $forced_args
	 *
	 * @return mixed|void|WP_Post[]
	 */
	public function get_potential_parents( $post_type, $wpml_name = '', $wpml_context = '', $limit = - 1, $query = '', $forced_args = array() ) {
		/**
		 * cred_get_potential_parents_post_status
		 *
		 * Filter used to handle post status in get_potential_posts
		 *
		 * @param array
		 *
		 * @since 1.9.3
		 */
		$post_status = apply_filters( 'cred_get_potential_parents_post_status', array( 'publish', 'private' ) );

		$src_lang = isset( $_REQUEST[ 'source_lang' ] ) ? sanitize_text_field( $_REQUEST[ 'source_lang' ] ) : apply_filters( 'wpml_current_language', null );
		$lang = isset( $_REQUEST[ 'lang' ] ) ? sanitize_text_field( $_REQUEST[ 'lang' ] ) : $src_lang;

		do_action( 'wpml_switch_language', $lang );

		$args = array(
			'orderby' => 'ID',
			'order' => 'DESC',
			'post_type' => $post_type,
			'post_status' => $post_status,
			'suppress_filters' => false,
		);
		if ( $limit >= 0 ) {
			$args[ 'posts_per_page' ] = $limit;
		} else {
			$args[ 'posts_per_page' ] = -1;
		}
		if (!empty($query)) {
			$args[ 's' ] = $query;
		}

		$maybe_forced_args = array( 'orderby', 'order', 'author', 'post__in' );
		foreach ( $maybe_forced_args as $maybe_arg ) {
			if ( isset( $forced_args[ $maybe_arg ] ) ) {
				$args[ $maybe_arg ] = $forced_args[ $maybe_arg ];
			}
		}

		$parents = $this->query_posts_by_title->get_posts( $args );

		/**
		 * wpml_cred_potential_parents_filter
		 *
		 * Filter that can be used for parents WPML string translation
		 *
		 * @param WP_Post[] $parents
		 * @param string $wpml_name usually is the field slug or post_name used in wpml translation field
		 * @param string $wpml_context form context in order to allow wpml translation fields
		 * @param array $args
		 *
		 * @since 1.9.3
		 */
		$parents = apply_filters( 'wpml_cred_potential_parents_filter', $parents, $wpml_name, $wpml_context, $args );

		return $parents;
	}

	/**
	 * Get potential posts by post type with limit and query search options
	 *
	 * @param $post_type
	 * @param string $query
	 *
	 * @return array
	 */
	public function get_potential_posts( $post_type, $limit = - 1, $query = '' ) {
		/**
		 * cred_get_potential_posts_post_status
		 *
		 * Filter used to handle post status in get_potential_posts
		 *
		 * @param array
		 *
		 * @since 1.9.3
		 */
		$post_status = apply_filters( 'cred_get_potential_posts_post_status', array( 'publish', 'private' ) );

		$src_lang = isset( $_REQUEST[ 'source_lang' ] ) ? sanitize_text_field( $_REQUEST[ 'source_lang' ] ) : apply_filters( 'wpml_current_language', null );
		$lang = isset( $_REQUEST[ 'lang' ] ) ? sanitize_text_field( $_REQUEST[ 'lang' ] ) : $src_lang;

		do_action( 'wpml_switch_language', $lang );

		$args = array(
			'nopaging' => false,
			'orderby' => 'ID',
			'order' => 'DESC',
			'post_type' => $post_type,
			'post_status' => $post_status,
			'suppress_filters' => false,
		);
		if ( $limit >= 0 ) {
			$args[ 'posts_per_page' ] = $limit;
		}
		if ( ! empty( $query ) ) {
			$args[ 's' ] = $query;
		}

		$posts = $this->query_posts_by_title->get_posts( $args );

		return $posts;
	}

}
