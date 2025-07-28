<?php

	// Generate standardized text links
	if ( ! function_exists('wp_all_export_generate_link')){
		function wp_all_export_generate_link( $text, $href ){

			?>

			<span class="wp-all-export-link-wrapper">
				<a class="wp-all-export-link" href="<?php echo esc_url($href); ?>" target="_blank">
					<span class="wp-all-export-link-text"><?php echo $text; ?></span>
					<svg class="wp-all-export-link-arrow" width="13" height="11" viewBox="0 0 13 11" fill="none" xmlns="http://www.w3.org/2000/svg">
						<title>Arrow</title>
						<path d="M8 1.5L12 5.5L8 9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
						<path d="M11.5 5.5H1" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
					</svg>
				</a>
			</span>

			<?php

		}
	}
	
	if ( ! function_exists('wp_all_export_isValidMd5')){
		function wp_all_export_isValidMd5($md5 ='')
		{
		    return preg_match('/^[a-f0-9]{32}$/', $md5);
		}
	}	

if ( ! function_exists( 'wp_all_export_isValidMd5' ) ) {
	function wp_all_export_isValidMd5( $md5 = '' ) {
		return preg_match( '/^[a-f0-9]{32}$/', $md5 );
	}
}

if ( ! function_exists( 'wp_all_export_get_relative_path' ) ) {
	function wp_all_export_get_relative_path( $path ) {

		$uploads = wp_upload_dir();

		return str_replace( $uploads['basedir'], '', $path );

	}
}

if ( ! function_exists( 'wp_all_export_get_absolute_path' ) ) {
	function wp_all_export_get_absolute_path( $path ) {
		$uploads = wp_upload_dir();

        // If the path isn't http(s) and doesn't start with the basedir, add the basedir.
		return ( strncmp($path, $uploads['basedir'], strlen($uploads['basedir'])) !== 0  and ! preg_match( '%^https?://%i', $path ) ) ? $uploads['basedir'] . $path : $path;
	}
}

if ( ! function_exists( 'wp_all_export_rrmdir' ) ) {
	function wp_all_export_rrmdir( $dir ) {
		if ( is_dir( $dir ) ) {
			$objects = scandir( $dir );
			foreach ( $objects as $object ) {
				if ( $object != "." && $object != ".." ) {
					if ( filetype( $dir . "/" . $object ) == "dir" ) {
						wp_all_export_rrmdir( $dir . "/" . $object );
					} else {
						unlink( $dir . "/" . $object );
					}
				}
			}
			rmdir( $dir );
		}
	}
}

if ( ! function_exists( 'pmxe_getExtension' ) ) {
	function pmxe_getExtension( $str ) {
		$i = strrpos( $str, "." );
		if ( ! $i ) {
			return "";
		}
		$l   = strlen( $str ) - $i;
		$ext = substr( $str, $i + 1, $l );

		return ( strlen( $ext ) <= 4 ) ? $ext : "";
	}
}

if ( ! function_exists( 'wp_all_export_get_existing_meta_by_cpt' ) ) {
	function wp_all_export_get_existing_meta_by_cpt( $post_type = false ) {
		if ( empty( $post_type ) ) {
			return array();
		}

		if ( XmlExportEngine::get_addons_service()->isWooCommerceAddonActive() ) {
			$post_type = ( $post_type == 'product' and class_exists( 'WooCommerce' ) ) ? array(
				'product',
				'product_variation'
			) : array( $post_type );
		} else {
			$post_type = ( $post_type == 'product' and class_exists( 'WooCommerce' ) ) ? array( 'product' ) : array( $post_type );
		}
		global $wpdb;
		$table_prefix = $wpdb->prefix;

		$post_type = array_map( function ( $item ) use ( $wpdb ) {
			return $wpdb->prepare( '%s', $item );
		}, $post_type );

		$post_type_in = implode( ',', $post_type );

        $meta_query_limit = apply_filters('wp_all_export_meta_query_limit', 1000);
		// $wpdb->prepare isn't used as it's already covered above for the user provided values.
		$meta_keys = $wpdb->get_results( "SELECT DISTINCT {$table_prefix}postmeta.meta_key FROM {$table_prefix}postmeta, {$table_prefix}posts WHERE {$table_prefix}postmeta.post_id = {$table_prefix}posts.ID AND {$table_prefix}posts.post_type IN ({$post_type_in}) AND {$table_prefix}postmeta.meta_key NOT LIKE '_edit%' AND {$table_prefix}postmeta.meta_key NOT LIKE '_oembed_%' LIMIT $meta_query_limit" );

		$_existing_meta_keys = array();
		if ( ! empty( $meta_keys ) ) {
			$exclude_keys = array( '_first_variation_attributes', '_is_first_variation_created' );
			foreach ( $meta_keys as $meta_key ) {
				if ( strpos( $meta_key->meta_key, "_tmp" ) === false && strpos( $meta_key->meta_key, "_v_" ) === false && ! in_array( $meta_key->meta_key, $exclude_keys ) ) {
					$_existing_meta_keys[] = $meta_key->meta_key;
				}
			}
		}

		return $_existing_meta_keys;
	}
}

if ( ! function_exists( 'wp_all_export_get_existing_taxonomies_by_cpt' ) ) {
	function wp_all_export_get_existing_taxonomies_by_cpt( $post_type = false ) {
		if ( empty( $post_type ) ) {
			return array();
		}

		$post_taxonomies      = array_diff_key( get_taxonomies_by_object_type( array( $post_type ), 'object' ), array_flip( array( 'post_format' ) ) );
		$_existing_taxonomies = array();
		if ( ! empty( $post_taxonomies ) ) {
			foreach ( $post_taxonomies as $tx ) {
				if ( strpos( $tx->name, "pa_" ) !== 0 ) {
					$_existing_taxonomies[] = array(
						'name'  => empty( $tx->label ) ? $tx->name : $tx->label,
						'label' => $tx->name,
						'type'  => 'cats'
					);
				}
			}
		}

		return $_existing_taxonomies;
	}
}

if ( ! function_exists( 'wp_all_export_get_taxonomies' ) ) {
	function wp_all_export_get_taxonomies() {
		// get all taxonomies
		$taxonomies = get_taxonomies( false, 'objects' );
		$ignore     = array( 'nav_menu', 'link_category' );
		$r          = array();
		// populate $r
		foreach ( $taxonomies as $taxonomy ) {
			if ( in_array( $taxonomy->name, $ignore ) ) {
				continue;
			}
			if ( ! empty( $taxonomy->labels->name ) && strpos( $taxonomy->labels->name, "_" ) === false ) {
				$r[ $taxonomy->name ] = $taxonomy->labels->name;
			} else {
				$r[ $taxonomy->name ] = empty( $taxonomy->labels->singular_name ) ? $taxonomy->name : $taxonomy->labels->singular_name;
			}
		}
		asort( $r, SORT_FLAG_CASE | SORT_STRING );

		// return
		return $r;

	}
}

if ( ! function_exists( 'wp_all_export_cmp_custom_types' ) ) {
	function wp_all_export_cmp_custom_types( $a, $b ) {
		return strcmp( $a->labels->name, $b->labels->name );
	}
}

if ( ! function_exists( 'prepare_date_field_value' ) ) {
	function prepare_date_field_value( $fieldOptions, $timestamp, $defaultFormat = false ) {

		if ( ! empty( $fieldOptions ) ) {
			switch ( $fieldOptions ) {
				case 'unix':
					$post_date = $timestamp;
					break;
				default:
					$post_date = date( $fieldOptions, $timestamp );
					break;
			}
		} else {
			// Check if export was created before v1.4.2-beta-2.0
			if ( PMXE_Plugin::isExistingExport( "1.4.2-beta-2.0" ) ) {
				// Do not change date fields for exports created before v1.4.2-beta-2.0
				$post_date = $defaultFormat ? date( $defaultFormat, $timestamp ) : $timestamp;
			} else {
				$post_date = date( "Y-m-d H:i:s", $timestamp );
			}
		}

		return $post_date;
	}
}

if ( ! function_exists( 'wpae_wp_enqueue_code_editor' ) ) {
	function wpae_wp_enqueue_code_editor( $args ) {

		// We need syntax highlighting to work in the plugin regardless of user setting.
		// Function matches https://developer.wordpress.org/reference/functions/wp_enqueue_code_editor/ otherwise.
		/*if ( is_user_logged_in() && 'false' === wp_get_current_user()->syntax_highlighting ) {
			return false;
		}*/

		$settings = wp_get_code_editor_settings( $args );

		if ( empty( $settings ) || empty( $settings['codemirror'] ) ) {
			return false;
		}

		wp_enqueue_script( 'code-editor' );
		wp_enqueue_style( 'code-editor' );

		if ( isset( $settings['codemirror']['mode'] ) ) {
			$mode = $settings['codemirror']['mode'];
			if ( is_string( $mode ) ) {
				$mode = array(
					'name' => $mode,
				);
			}

			if ( ! empty( $settings['codemirror']['lint'] ) ) {
				switch ( $mode['name'] ) {
					case 'css':
					case 'text/css':
					case 'text/x-scss':
					case 'text/x-less':
						wp_enqueue_script( 'csslint' );
						break;
					case 'htmlmixed':
					case 'text/html':
					case 'php':
					case 'application/x-httpd-php':
					case 'text/x-php':
						wp_enqueue_script( 'htmlhint' );
						wp_enqueue_script( 'csslint' );
						wp_enqueue_script( 'jshint' );
						if ( ! current_user_can( 'unfiltered_html' ) ) {
							wp_enqueue_script( 'htmlhint-kses' );
						}
						break;
					case 'javascript':
					case 'application/ecmascript':
					case 'application/json':
					case 'application/javascript':
					case 'application/ld+json':
					case 'text/typescript':
					case 'application/typescript':
						wp_enqueue_script( 'jshint' );
						wp_enqueue_script( 'jsonlint' );
						break;
				}
			}
		}

		wp_add_inline_script( 'code-editor', sprintf( 'jQuery.extend( wp.codeEditor.defaultSettings, %s );', wp_json_encode( $settings ) ) );

		/**
		 * Fires when scripts and styles are enqueued for the code editor.
		 *
		 * @param array $settings Settings for the enqueued code editor.
		 *
		 * @since 4.9.0
		 *
		 */
		do_action( 'wp_enqueue_code_editor', $settings );

		return $settings;
	}

    if(!function_exists('wp_all_export_is_array_nested')){
	    function wp_all_export_is_array_nested($array) {
		    if (!is_array($array)) {
			    return false;
		    }

            $nested_count = 0;

		    foreach ($array as $value) {
			    if (is_array($value)) {
				    $nested_count++;
			    }
		    }

            if(count($array) === $nested_count){
                return true;
            }

		    return false;
	    }
    }
}

if (!function_exists('pmxe_encode_parenthesis')) {
	function pmxe_encode_parenthesis($input) {
		return strtr($input, [
			'(' => '&lpar;',
			')' => '&rpar;',
			'[' => '&lsqb;',
			']' => '&rsqb;'
		]);
	}
}

if (!function_exists('pmxe_decode_parenthesis')) {
	function pmxe_decode_parenthesis($input) {
		return strtr($input, [
			'&lpar;' => '(',
			'&rpar;' => ')',
			'&lsqb;' => '[',
			'&rsqb;' => ']'
		]);
	}
}

if (!function_exists('pmxe_encode_parenthesis_within_strings')) {
	function pmxe_encode_parenthesis_within_strings(string $code): string {
		$tokens = token_get_all($code);
		$result = '';

		foreach ($tokens as $token) {
			if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
				$token[1] = pmxe_encode_parenthesis($token[1]);
			}
			$result .= is_array($token) ? $token[1] : $token;
		}

		return $result;
	}
}

if (!function_exists('pmxe_decode_parenthesis_within_strings')) {
	function pmxe_decode_parenthesis_within_strings(string $code): string {
		$tokens = token_get_all($code);
		$result = '';

		foreach ($tokens as $token) {
			if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
				$token[1] = pmxe_decode_parenthesis($token[1]);
			}
			$result .= is_array($token) ? $token[1] : $token;
		}

		return $result;
	}
}

if(!function_exists('pmxe_is_writable')){
    function pmxe_is_writable($path){
        if(apply_filters('pmxe_use_alternative_is_writable_check', false)){
	        if(is_dir($path)) {
		        $test_file = $path . '/test_file.tmp';
		        $handle = @fopen($test_file, 'a');
		        if($handle === false) {
			        $result = false;
		        } else {
			        fclose($handle);
			        unlink($test_file);
			        $result = true;
		        }
	        } else {
		        $handle = @fopen($path, 'a');
		        if($handle === false) {
			        $result = false;
		        } else {
			        fclose($handle);
			        $result = true;
		        }
	        }
        }else{
            $result = is_writable($path);
        }

        return $result;
    }
}

