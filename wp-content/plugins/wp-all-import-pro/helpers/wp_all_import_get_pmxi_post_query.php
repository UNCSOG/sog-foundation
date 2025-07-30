<?php

function wp_all_import_get_pmxi_post_query( array $args, $return_as_array = true, $return_data = false ) {
	global $wpdb;

	$query  = "SELECT post_id FROM {$wpdb->prefix}pmxi_posts WHERE 1=1";
	$params = [];

	foreach ( $args as $key => $value ) {
		if ( strpos( $key, '!=' ) !== false ) {
			$column   = trim( str_replace( '!=', '', $key ) );
			$query    .= " AND {$column} != %s";
			$params[] = $value;
		} elseif ( strpos( $key, '<>' ) !== false ) {
			$column   = trim( str_replace( '<>', '', $key ) );
			$query    .= " AND {$column} != %s";
			$params[] = $value;
		} elseif ( strpos( $key, '<=' ) !== false ) {
			$column   = trim( str_replace( '<=', '', $key ) );
			$query    .= " AND {$column} <= %s";
			$params[] = $value;
		} elseif ( strpos( $key, '>=' ) !== false ) {
			$column   = trim( str_replace( '>=', '', $key ) );
			$query    .= " AND {$column} >= %s";
			$params[] = $value;
		} elseif ( strpos( $key, '<' ) !== false ) {
			$column   = trim( str_replace( '<', '', $key ) );
			$query    .= " AND {$column} < %s";
			$params[] = $value;
		} elseif ( strpos( $key, '>' ) !== false ) {
			$column   = trim( str_replace( '>', '', $key ) );
			$query    .= " AND {$column} > %s";
			$params[] = $value;
		} else {
			$column   = trim( $key );
			$query    .= " AND {$column} = %s";
			$params[] = $value;
		}
	}

	$prepared_query = $wpdb->prepare( $query, ...$params );
	
	if ( $return_data ) {
		return $wpdb->get_results( $prepared_query, ARRAY_A );
	}

	if ( $return_as_array ) {
		return [$prepared_query];
	}

	return $prepared_query;
}