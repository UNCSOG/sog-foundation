<?php
if ( ! function_exists('is_exists_term') ) {
	function is_exists_term( $term, $taxonomy = '', $parent = null ){

		$is_term_exist = pmxi_term_exists($term, $taxonomy, $parent);

        if ( ! $is_term_exist && ! empty($term) && is_numeric($term) ) {
            $is_term_exist = term_exists( (int) $term, $taxonomy, $parent );
        }
		return apply_filters( 'wp_all_import_term_exists', $is_term_exist, $taxonomy, $term, $parent );
	}
}

if( ! function_exists('pmxi_term_exists')){
	function pmxi_term_exists($term, $taxonomy = '', $parent = ''){

		// Search by slug.
		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'slug'       => sanitize_title( $term ),
			'parent'     => $parent ?? '',
			'hide_empty' => false,
		] );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			return (array) $terms[0];
		}

		// Search by name.
		$terms = get_terms([
			'taxonomy' => $taxonomy,
			'name' => $term,
			'parent' => $parent ?? '',
			'hide_empty' => false,
		]);

		if (!empty($terms) && !is_wp_error($terms)) {
			return (array) $terms[0];
		}

		// No term found.
		return false;
	}
}
