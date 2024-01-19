<?php
/**
 * Plugin utils.
 *
 * A mix of helper functions that needs to be placed somewhere.
 *
 * @package Toolset Forms
 */

/**
 * Function to return zero, useful as hooks callback.
 *
 * @return int
 */
function cred__return_zero() {
	return 0;
}

/**
 * Array recursive version of sanitize_text_field.
 *
 * @param mixed $array
 * @return mixed
 */
function cred_sanitize_array( &$array ) {
	if ( is_array( $array ) ) {
		foreach ( $array as &$value ) {
			if ( is_string( $value ) ) {
				$value = sanitize_text_field( $value );
			} else {
				cred_sanitize_array( $value );
			}
		}
	}

	return $array;
}

/**
 * Get a WP_Post instance of a given form.
 *
 * @param mixed $form Form slug, title or ID
 * @param string $type (CRED_FORMS_CUSTOM_POST_NAME|CRED_USER_FORMS_CUSTOM_POST_NAME)
 * @return bool|WP_Post
 */
function cred_get_object_form( $form, $type ) {
	// Check whether the passed value matches a form ID
	if ( is_numeric( $form ) ) {
		$result = get_post( $form );
		if ( $result instanceof WP_Post ) {
			return $result;
		}
	}

	// Check whether the passed value matches a form slug
	$result = get_page_by_path( wp_specialchars_decode( $form ), OBJECT, $type );
	if ( $result instanceof WP_Post ) {
		return $result;
	}

	$results = get_posts(
		array(
			'post_type'              => $type,
			'title'                  => wp_specialchars_decode( $form ),
			'post_status'            => 'all',
			'numberposts'            => 1,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'orderby'                => 'post_date ID',
			'order'                  => 'ASC',
		)
	);

	if ( ! empty( $results ) && $results[0] instanceof WP_Post ) {
		return $results[0];
	}

	return false;
}

/**
 * Stupid method to get somethign that is already here.
 *
 * @param $form
 * @return bool|int
 */
function cred_get_form_id_by_form( $form ) {
	if ( isset( $form ) && ! empty( $form ) && isset( $form->ID ) ) {
		return $form->ID;
	}

	return false;
}

/**
 * Creates cred form html selector ID by form type, form_id and form_count
 *
 * @param string $form_type
 * @param string $form_id
 * @param string $form_count
 * @return string
 * @since 1.9
 */
function get_cred_html_form_id( $form_type, $form_id, $form_count ) {
	$html_form_type = str_replace( "-", "_", $form_type );
	$wpv_pagination_mod = toolset_getget( 'wpv_paged', 1 );
	return "{$html_form_type}_{$form_id}_{$form_count}_{$wpv_pagination_mod}";
}

/**
 * Helper to check if a value lives in a multidimensional array.
 *
 * @param mixed $needle
 * @param array $haystack
 * @param bool $strict
 * @return bool
 */
function cred__in_multidimensional_array_value( $needle, $haystack, $strict = false ) {
	foreach ( $haystack as $item ) {
		if (
			( $strict ? $item === $needle : $item == $needle )
			|| (
				is_array( $item )
				&& cred__in_multidimensional_array_value( $needle, $item, $strict )
			)
		) {
			return true;
		}
	}

	return false;
}
