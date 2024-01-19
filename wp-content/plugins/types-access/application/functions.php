<?php

/**
 * Update old access settings when Access activated
 *
 * @link https://developer.wordpress.org/reference/functions/register_activation_hook/#process-flow
 */
function taccess_on_activate() {
	add_option( 'taccess_on_activate', true );
}

function taccess_on_activate_action() {
	if ( get_option( 'taccess_on_activate', false ) ) {
		delete_option( 'taccess_on_activate' );
		TAccess_Loader::load( 'CLASS/Updater' );
		Access_Updater::maybeUpdate();
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$access_settings->remove_depricated_settings();
	}
}

function toolset_access_get_server_protocol() {
	if (
		isset($_SERVER['REQUEST_SCHEME']) &&
		$_SERVER['REQUEST_SCHEME'] === 'https'
	) {
		return 'https://';
	}
	if (
		isset($_SERVER['HTTPS']) &&
		(
			$_SERVER['HTTPS'] == 'on' ||
			$_SERVER['HTTPS'] == 1
		)
	) {
		return 'https://';
	}
	if (
		isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
		$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
	) {
		return 'https://';
	}
	return 'http://';
}


/**
 * Get current post id
 *
 * @return bool|string
 */
function toolset_access_get_current_page_id() {
	// Avoid breaking CLI
	if ( ! isset( $_SERVER['HTTP_HOST'] ) || ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return '';
	}
	//phpcs:ignore
	$protocol = toolset_access_get_server_protocol();
	//phpcs:ignore
	$url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	$post_types = get_post_types( '', 'names' );
	$stored_post_types = Access_Cacher::get( 'wpcf-access-current-post-types' );
	if ( false === $stored_post_types ) {
		Access_Cacher::set( 'wpcf-access-current-post-types', $post_types );
		$check_post_id = true;
	} else {
		if ( $post_types === $stored_post_types ) {
			$check_post_id = false;
		} else {
			Access_Cacher::set( 'wpcf-access-current-post-types', $post_types );
			$check_post_id = true;
		}
	}

	$post_id = Access_Cacher::get( 'wpcf-access-current-post-id' );
	if ( false === $post_id || $check_post_id ) {
		global $sitepress;
		if ( is_object( $sitepress ) ) {
			remove_filter( 'url_to_postid', array( $sitepress, 'url_to_postid' ) );
			$post_id = url_to_postid( $url );
			add_filter( 'url_to_postid', array( $sitepress, 'url_to_postid' ) );
			if ( empty( $post_id ) ) {
				$post_id = url_to_postid( $url );
			}
			if ( empty( $post_id ) ) {
				$post_id = \OTGS\Toolset\Access\Models\WPMLSettings::get_instance()->get_translated_homepage_id( $url );
			}
		} else {
			$post_id = url_to_postid( $url );
		}

		if ( ! isset( $post_id ) || empty( $post_id ) || 0 === $post_id ) {
			//phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 1 === count( $_GET ) && '' === get_option( 'permalink_structure' ) ) {
				global $wpdb;
				//phpcs:ignore WordPress.Security.NonceVerification.Recommended
				foreach ( $_GET as $key => $val ) {
					$val = $wpdb->esc_like( $val );
					$key = $wpdb->esc_like( $key );
					if ( post_type_exists( $key ) ) {
						//phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
						$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s and post_type=%s", $val, $key ) );
					}
				}
			}
		}

		if ( empty( $post_id ) ) {
			$homepage = get_option( 'page_on_front' );
			if ( get_home_url() . '/' === $url && '' !== $homepage ) {
				$post_id = $homepage;
			}
		}

		if ( ! isset( $post_id ) || empty( $post_id ) ) {
			$post_id = '';
		} else {
			Access_Cacher::set( 'wpcf-access-current-post-id', $post_id );
		}

		$post_id = Access_Cacher::get( 'wpcf-access-current-post-id' );

	}

	return $post_id;
}


/**
 * Get post type singular and plural names
 *
 * @param string $post_type
 *
 * @return array
 * @example array( 'singular_post_type_name', 'plural_post_type_name' )
 */
function toolset_access_get_post_type_names( $post_type ) {
	global $wp_post_types;
	$plural = '';
	$singular = '';
	if ( isset( $wp_post_types[ $post_type ] ) ) {
		// Force map meta caps, if not builtin
		if ( in_array( $post_type, array( 'post', 'page' ), true ) ) {
			switch ( $post_type ) {
				case 'page':
					$singular = 'page';
					$plural = 'pages';
					break;
				case 'post':
				default:
					$singular = 'post';
					$plural = 'posts';
					break;
			}
		} else {
			// else use singular/plural names
			$singular = sanitize_title( $wp_post_types[ $post_type ]->labels->singular_name );
			$plural = sanitize_title( $wp_post_types[ $post_type ]->labels->name );
			if ( $singular === $plural ) {
				$plural = $plural . '_s';
			}
		}
	}

	return array( $plural, $singular );
}
