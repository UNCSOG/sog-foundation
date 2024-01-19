<?php

/**
 * Class that contains some callback functions used to preserve Taxonomy Input on frontend when form submit fails
 *
 * @since 1.8.8
 */
class CRED_Frontend_Preserve_Taxonomy_Input {

	private static $instance;

	public static function initialize() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
	}

	/**
	 * @return CRED_Frontend_Preserve_Taxonomy_Input
	 */
	public static function get_instance() {
		return self::$instance;
	}



	/**
	 * @param $name
	 * @param $terms
	 *
	 * @return int
	 */
	private function get_term_id_by_name( $name, $terms ) {
		foreach ( $terms as $term ) {
			if ( $term['name'] == $name ) {
				return $term['term_id'];
			}
		}

		return -1;
	}

	/**
	 * CRED_Frontend_Preserve_Taxonomy_Input constructor.
	 */
	public function __construct() {
		//init taxonomy flat filter
		add_filter( 'toolset_filter_taxonomy_terms', array( $this, 'place_post_taxonomy_terms' ), 10, 2 );
		//init taxonomy hierarchical filters
		add_filter( 'toolset_filter_taxonomyhierarchical_values', array( $this, 'taxonomyhierarchical_values', ), 10, 3 );
		add_filter( 'toolset_filter_taxonomyhierarchical_children', array( $this, 'taxonomyhierarchical_children', ), 10, 2 );
		add_filter( 'toolset_filter_taxonomyhierarchical_names', array( $this, 'taxonomyhierarchical_names' ), 10, 2 );
		add_filter( 'toolset_filter_taxonomyhierarchical_terms', array( $this, 'place_post_taxonomyhierarchical_terms', ), 10, 2 );
		add_filter( 'toolset_filter_taxonomyhierarchical_metaform', array( $this, 'place_post_taxonomyhierarchical_metaform', ), 10, 2 );
	}

	public function remove_filters() {
		//init taxonomy flat filter
		remove_filter( 'toolset_filter_taxonomy_terms', array( $this, 'place_post_taxonomy_terms' ), 10 );
		//init taxonomy hierarchical filters
		remove_filter( 'toolset_filter_taxonomyhierarchical_values', array( $this, 'taxonomyhierarchical_values', ), 10 );
		remove_filter( 'toolset_filter_taxonomyhierarchical_children', array( $this, 'taxonomyhierarchical_children', ), 10 );
		remove_filter( 'toolset_filter_taxonomyhierarchical_names', array( $this, 'taxonomyhierarchical_names' ), 10 );
		remove_filter( 'toolset_filter_taxonomyhierarchical_terms', array( $this, 'place_post_taxonomyhierarchical_terms', ), 10 );
		remove_filter( 'toolset_filter_taxonomyhierarchical_metaform', array( $this, 'place_post_taxonomyhierarchical_metaform', ), 10 );
	}

	/**
	 * Force replacing my taxonomy terms from $_POST[ taxonomy_field_name ] "tag1, tag2, ..."
	 *
	 * @param $db_terms
	 * @param $field_name
	 *
	 * @return array
	 */
	public function place_post_taxonomy_terms( $db_terms, $field_name ) {
		if ( ! isset( $_POST[ $field_name ] ) ) {
			return $db_terms;
		}

		$i = 0;
		$terms = array();
		$post_terms = explode( ',', sanitize_text_field( $_POST[ $field_name ] ) );
		foreach ( $post_terms as $post_term ) {
			$term = new stdClass();
			$term->name = $post_term;
			$term->slug = sanitize_title( $post_term );
			$term->count = $i;
			$terms[] = $term;
			$i++;
		}

		return $terms;
	}

	/**
	 * @param $current_children
	 * @param $field_name
	 *
	 * @return mixed
	 */
	public function taxonomyhierarchical_children( $current_children, $field_name ) {
		if ( ! isset( $_POST[ $field_name . '_hierarchy' ] ) ) {
			return $current_children;
		}
		$new_post_terms = sanitize_text_field( $_POST[ $field_name . '_hierarchy' ] );
		$new_terms = $this->create_new_terms_by_post_terms( $new_post_terms );
		//new terms standardized
		$terms = array();
		/*
		 * Because terms are dinamically created and do not have terms_id yet name and terms_id will be same
		 */
		foreach ( $new_terms as $post_term ) {
			$term = array();
			$term['name'] = $post_term['term'];
			$term['term_taxonomy_id'] = $post_term['term'];
			$term['term_id'] = $post_term['term'];
			$term['parent'] = $post_term['parent'];
			$term['count'] = 1;
			$terms[ $post_term['term'] ] = $term;
		}
		$new_children = $current_children;
		foreach ( $terms as $child_term ) {

			if ( $child_term['parent'] == -1 ) {
				$new_children[0][] = $child_term['term_id'];
			} else {
				$new_children[ $child_term['parent'] ] = array();
				$new_children[ $child_term['parent'] ][] = $child_term['term_id'];
			}
		}

		return $new_children;
	}

	/**
	 * @param $names
	 * @param $field_name
	 *
	 * @return mixed
	 */
	public function taxonomyhierarchical_names( $names, $field_name ) {
		if ( ! isset( $_POST[ $field_name . '_hierarchy' ] ) ) {
			return $names;
		}
		$new_post_terms = sanitize_text_field( $_POST[ $field_name . '_hierarchy' ] );
		$new_terms = $this->create_new_terms_by_post_terms( $new_post_terms );

		$terms = array();
		/*
		 * Because terms are dinamically created and do not have terms_id yet name and terms_id will be same
		 */
		foreach ( $new_terms as $post_term ) {
			$term = array();
			$term['name'] = $post_term['term'];
			$term['term_taxonomy_id'] = $post_term['term'];
			$term['term_id'] = $post_term['term'];
			$term['parent'] = $post_term['parent'];
			$term['count'] = 1;
			$terms[] = $term;
		}
		$new_names = $names;
		foreach ( $terms as $term ) {
			$new_names[ $term['term_id'] ] = $term['name'];
		}

		return $new_names;
	}

	/**
	 * @param $current_values_id
	 * @param $all_terms
	 * @param $field_name
	 *
	 * @return array
	 */
	public function taxonomyhierarchical_values( $current_values_id, $all_terms, $field_name ) {
		if ( ! isset( $_POST[ $field_name . '_hierarchy' ] ) ) {
			return $current_values_id;
		}
		$new_post_terms = sanitize_text_field( $_POST[ $field_name . '_hierarchy' ] );
		$new_terms = $this->create_new_terms_by_post_terms( $new_post_terms );

		$submitted_term_ids = array();
		if ( isset( $_POST[ $field_name ] ) && ! empty( $_POST[ $field_name ] ) ) {
			$submitted_term_ids = cred_sanitize_array( $_POST[ $field_name ] );
		}

		$new_values_id = array();
		foreach ( $new_terms as $new_term ) {
			if ( in_array( $new_term['term'], $submitted_term_ids ) ) {
				$new_values_id[] = $new_term['term'];
			}
		}
		foreach ( $all_terms as $all_term ) {
			if ( in_array( $all_term->term_id, $submitted_term_ids ) ) {
				$new_values_id[] = $all_term->term_id;
			}
		}

		return $new_values_id;
	}

	/**
	 * @param $metaform
	 * @param $field_name
	 *
	 * @return array
	 */
	public function place_post_taxonomyhierarchical_metaform( $metaform, $field_name ) {
		$new_metaform = $metaform;
		$new_metaform[] = array(
			'#type' => 'hidden',
			'#title' => '',
			'#description' => '',
			'#name' => $field_name . '_hierarchy',
			'#value' => isset( $_POST[ $field_name . '_hierarchy' ] ) ? sanitize_text_field( $_POST[ $field_name . '_hierarchy' ] ) : "",
			'#attributes' => array(),
			'#validate' => '',
		);

		return $new_metaform;
	}

	/**
	 * @param $db_terms
	 * @param $field_name
	 *
	 * @return array
	 */
	public function place_post_taxonomyhierarchical_terms( $db_terms, $field_name ) {
		if ( ! isset( $_POST[ $field_name . '_hierarchy' ] ) ) {
			return $db_terms;
		}
		$new_post_terms = sanitize_text_field( $_POST[ $field_name . '_hierarchy' ] );
		$new_terms = $this->create_new_terms_by_post_terms( $new_post_terms );

		$terms = array();
		$processed_terms_slugs = array();

		/*
		 * Because terms are dinamically created and do not have terms_id yet name and terms_id will be same
		 */
		foreach ( $new_terms as $post_term ) {
			if ( in_array( $post_term['term'], $processed_terms_slugs, true ) ) {
				continue;
			}
			$term = new stdClass();
			$term->name = $post_term['term'];
			$term->slug = sanitize_title( $post_term['term'] );
			$term->term_taxonomy_id = $post_term['term'];
			$term->term_id = $post_term['term'];
			$term->parent = $post_term['parent'];
			$term->count = 1;

			$terms[] = $term;
			$processed_terms_slugs[] = $post_term['term'];
		}

		return $terms;
	}

	/**
	 * get new $_POST terms as json string ({-1,cat1}{2,cat2}...) and put in the expected array name/parent structure
	 *
	 * @param $new_post_terms
	 *
	 * @return array
	 */
	private function create_new_terms_by_post_terms( $new_post_terms ) {
		$new_terms = array();
		if ( ! empty( $new_post_terms ) ) {
			preg_match_all( '/\{([^\{\}]+?),([^\{\}]+?)\}/', $new_post_terms, $matches );
			for ( $i = 0; $i < count( $matches[1] ); $i++ ) {
				if ( cred__in_multidimensional_array_value( $matches[2][ $i ], $new_terms ) ) {
					continue;
				}
				$new_terms[] = array(
					'parent' => $matches[1][ $i ],
					'term' => $matches[2][ $i ],
				);
			}
			unset( $matches );
		}

		return $new_terms;
	}

}
