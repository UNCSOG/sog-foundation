<?php

if ( ! class_exists( 'WP_Import' ) ) {
	require_once MODMAN_PLUGIN_PATH . '/library/wordpress-importer/wordpress-importer.php';
}

/**
 * Class Modman_Importer
 * Import class extends WP_Import
 */
class Modman_Importer extends WP_Import {
	var $processed_terms_wpml = array();
	function __construct( $site_url ) {

		$this->site_url = $site_url;
		$this->post_number = 0;

		$this->wpml = get_option( 'wpvdemo_wpml_data', null );

	}


	/**
	 * Registered callback function for the WordPress Importer
	 *
	 * Manages the three separate stages of the WXR import process
	 *
	 * @param $file
	 */
	function dispatch( $file = null ) {

		$this->fetch_attachments = true;


		$this->id = mt_rand();

		set_time_limit( 0 );
		$this->import( $file );

	}

	/**
	 * The main controller for the actual import stage.
	 *
	 * @param string $file Path to the WXR file for importing
	 */
	function import( $file ) {

		$check_if_wpml_implemented = false;

		add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
		add_filter( 'http_request_timeout',
			array( &$this, 'bump_request_timeout' ) );

		$this->import_start( $file );
		$this->get_author_mapping();

		wp_suspend_cache_invalidation( true );
		$this->process_categories( array( 'has_wpml_implementation' => $check_if_wpml_implemented ) );
		$this->process_tags();
		$this->process_terms( array( 'has_wpml_implementation' => $check_if_wpml_implemented ) );
		$result = $this->process_posts( array( 'has_wpml_implementation' => $check_if_wpml_implemented ) );

		$processed_post = $this->processed_posts;
		update_option( 'wpvdemo_processed_posts_imported', $processed_post );

		$processed_terms = $this->processed_terms;
		update_option( 'wpvdemo_processed_terms_imported', $processed_terms );

		wp_suspend_cache_invalidation( false );

		$this->backfill_parents();
		$this->backfill_attachment_urls();
		$this->remap_featured_images();

		$this->import_end();
		return $result;
	}


	/**
	 * Parses the WXR file and prepares us for the task of processing parsed data
	 *
	 * @param string $file Path to the WXR file for importing
	 */
	function import_start( $file ) {

		$import_data = $this->parse( $file );

		if ( is_wp_error( $import_data ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.',
					'wordpress-importer' ) . '</strong><br />';
			echo esc_html( $import_data->get_error_message() ) . '</p>';
			$this->footer();
			die();
		}

		$this->version = $import_data['version'];
		$this->get_authors_from_import( $import_data );
		$this->posts      = $import_data['posts'];
		$this->terms      = $import_data['terms'];
		$this->categories = $import_data['categories'];
		$this->tags       = $import_data['tags'];
		$this->base_url   = esc_url( $import_data['base_url'] );

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		remove_action( 'import_start', 'icl_import_xml_start', 0 );
		do_action( 'import_start' );
	}


	/**
	 * Performs post-import cleanup of files and the cache
	 */
	function import_end() {
		wp_import_cleanup( $this->id );

		wp_cache_flush();
		foreach ( get_taxonomies() as $tax ) {
			delete_option( "{$tax}_children" );
			_get_term_hierarchy( $tax );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		$this->fix_types_image_urls();

		do_action( 'import_end' );
	}

	function fix_types_image_urls() {
		global $wpdb;
		uksort( $this->url_remap, array( &$this, 'cmpr_strlen' ) );

		foreach ( $this->url_remap as $from_url => $to_url ) {
			$result = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = '%s' WHERE meta_value = '%s'", $to_url, $from_url ) );
		}
	}

	function next_post() {
		$this->post_number ++;
		update_option( 'wpvdemo-post-count', $this->post_number );
		update_option( 'wpvdemo-post-total', sizeof( $this->posts ) );
	}

	/**
	 * Attempt to download a remote file attachment
	 *
	 * @param string $url URL of item to fetch
	 * @param array $post Attachment details
	 *
	 * @return array|WP_Error Local file location details on success, WP_Error otherwise
	 */
	function fetch_remote_file( $url, $post ) {

		$file_name = basename( $url );

		$upload = wp_upload_bits( $file_name, 0, '', $post['upload_date'] );
		if ( $upload['error'] ) {
			return new WP_Error( 'upload_dir_error', $upload['error'] );
		}

		$headers = $this->wp_get_http_wpvdemo( $url, $upload['file'], $upload['url'] );

		if ( ! $headers ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', __( 'Remote server did not respond', 'wordpress-importer' ) );
		}

		if ( ! ( strpos( $headers[0], '200 OK' ) ) ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', sprintf( __( 'Remote server returned error response %1$d %2$s', 'wordpress-importer' ), esc_html( $headers['response'] ), get_status_header_desc( $headers['response'] ) ) );
		}

		$filesize = filesize( $upload['file'] );

		if ( isset( $headers['content-length'] ) && $filesize != $headers['content-length'] ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', __( 'Remote file is incorrect size', 'wordpress-importer' ) );
		}

		if ( 0 == $filesize ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', __( 'Zero size file downloaded', 'wordpress-importer' ) );
		}

		$max_size = (int) $this->max_attachment_size();
		if ( ! empty( $max_size ) && $filesize > $max_size ) {
			@unlink( $upload['file'] );

			return new WP_Error( 'import_file_error', sprintf( __( 'Remote file is too large, limit is %s', 'wordpress-importer' ), size_format( $max_size ) ) );
		}

		$this->url_remap[ $url ]          = $upload['url'];
		$this->url_remap[ $post['guid'] ] = $upload['url'];
		if ( isset( $headers['x-final-location'] ) && $headers['x-final-location'] != $url ) {
			$this->url_remap[ $headers['x-final-location'] ] = $upload['url'];
		}

		return $upload;
	}

	/*EMERSON rewrite importing of WPML icl_translations
	 *
	*/

	function get_existing_original_ids() {
		global $wpdb;
		$table_name              = $wpdb->prefix . 'icl_translations';
		$existing_ids            = $wpdb->get_results( "SELECT translation_id FROM $table_name", ARRAY_A );
		$orig_clean_existing_ids = array();
		$output_array_combined   = array();

		if ( ( is_array( $existing_ids ) ) && ( ! ( empty( $existing_ids ) ) ) ) {
			foreach ( $existing_ids as $key => $inner_array ) {
				foreach ( $inner_array as $innert_key => $value ) {
					$orig_clean_existing_ids[] = $value;
				}
			}
			$maximum_ids                             = max( $orig_clean_existing_ids );
			$output_array_combined['max']            = $maximum_ids;
			$output_array_combined['clean_id_array'] = $orig_clean_existing_ids;

			return $output_array_combined;

		} elseif ( ( is_array( $existing_ids ) ) && ( empty( $existing_ids ) ) ) {
			$output_array_combined['max']            = 0;
			$output_array_combined['clean_id_array'] = array();

			return $output_array_combined;
		}

	}

	function get_existing_original_trids() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'icl_translations';
		$existing_ids            = $wpdb->get_results( "SELECT DISTINCT trid FROM $table_name WHERE element_type NOT LIKE 'post_dd_layouts'", ARRAY_A );
		$orig_clean_existing_ids = array();
		$output_array_combined   = array();

		if ( ( is_array( $existing_ids ) ) && ( ! ( empty( $existing_ids ) ) ) ) {
			foreach ( $existing_ids as $key => $inner_array ) {
				foreach ( $inner_array as $innert_key => $value ) {
					$orig_clean_existing_ids[] = $value;
				}
			}
			$maximum_ids                             = max( $orig_clean_existing_ids );
			$output_array_combined['max']            = $maximum_ids;
			$output_array_combined['clean_id_array'] = $orig_clean_existing_ids;

			return $output_array_combined;

		} elseif ( ( is_array( $existing_ids ) ) && ( empty( $existing_ids ) ) ) {
			$output_array_combined['max']            = 0;
			$output_array_combined['clean_id_array'] = array();

			return $output_array_combined;
		}

	}



	/*EMERSON rewrite method of fetching attachments to minimize issues with timeout and WP 3.6 issues.
	 * Uses native PHP functions like file_put_contents and fopen
	*/
	function wp_get_http_wpvdemo( $url, $file_path, $target_url ) {
		$context = stream_context_set_default( array(
				'http' => array(
					'timeout' => 1200
				),
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
				)
			)
		);

		$file_headers_image = @get_headers( $url );

		if ( strpos( $file_headers_image[0], '200 OK' ) ) {
			$success = @file_put_contents( $file_path, fopen( $url, 'r', false, $context ) );

			if ( $success ) {
				$network_site_url                       = network_site_url();
				$network_site_url                       = rtrim( $network_site_url, '/' );
				$is_using_bedrock_boilerplate_framework = false;

				if ( $is_using_bedrock_boilerplate_framework ) {
					$alternative_url_via_root = $target_url;
				} else {
					$views_demo_installation_abs_path = $this->viewsdemo_get_wordpress_base_path();
					$alternative_url_via_root         = str_replace( $views_demo_installation_abs_path, $network_site_url, $file_path );
				}

				$file_headers_image_return = @get_headers( $alternative_url_via_root );

				if ( ( !$file_headers_image_return ) && ( !is_multisite() ) ) {
					if ( file_exists( $file_path ) ) {
						$file_headers_image_return[0]	= 'HTTP/1.1 200 OK';
					} else {
						return false;
					}
				}
				return $file_headers_image_return;
			} else {
				return false;
			}

		} else {

			return false;
		}
	}

	function viewsdemo_get_wordpress_base_path() {
		$dir = dirname( __FILE__ );
		do {
			if ( file_exists( $dir . "/wp-load.php" ) ) {
				return $dir;
			}
		} while ( $dir = realpath( "$dir/.." ) );

		return null;
	}

	function wpvdemo_need_to_fetch_images( $file ) {

		/** Since Framework Installer 1.8, this will return TRUE by default */
		return true;
	}

	/**
	 * Override default process post functionality
	 *
	 * @param array $args
	 */
	function process_posts( $args = array() ) {
		/** Require Framework installer import API to allow us override any processes here */
		/** Since 1.8.7 */
		$result = array(
			'new' => 0,
			'failed' => 0
		);
		$has_wpml_implementation = false;

		foreach ( $this->posts as $post ) {

			$this->next_post();

			if ( ! post_type_exists( $post['post_type'] ) ) {
				printf( __( 'Failed to import &#8220;%s&#8221;: Invalid post type %s', 'wordpress-importer' ),
					esc_html( $post['post_title'] ), esc_html( $post['post_type'] ) );
				echo '<br />';
				continue;
			}

			if ( isset( $this->processed_posts[ $post['post_id'] ] ) && ! empty( $post['post_id'] ) ) {
				continue;
			}

			if ( $post['status'] == 'auto-draft' ) {
				continue;
			}

			$post_type_object = get_post_type_object( $post['post_type'] );

			$post_exists = post_exists( $post['post_title'], '', $post['post_date'] );

			if ( ( $post_exists && get_post_type( $post_exists ) == $post['post_type'] ) && ( ! ( $has_wpml_implementation ) ) ) {
				$result['failed'] += 1;
				$comment_post_ID = $post_id = $post_exists;
			} else {
				$result['new'] += 1;
				$post_parent = (int) $post['post_parent'];
				if ( $post_parent ) {
					if ( isset( $this->processed_posts[ $post_parent ] ) ) {
						$post_parent = $this->processed_posts[ $post_parent ];
					} else {
						$this->post_orphans[ intval( $post['post_id'] ) ] = $post_parent;
						$post_parent                                      = 0;
					}
				}

				$author = sanitize_user( $post['post_author'], true );
				if ( isset( $this->author_mapping[ $author ] ) ) {
					$author = $this->author_mapping[ $author ];
				} else {
					$author = (int) get_current_user_id();
				}

				$postdata = array(
					'import_id'      => $post['post_id'],
					'post_author'    => $author,
					'post_date'      => $post['post_date'],
					'post_date_gmt'  => $post['post_date_gmt'],
					'post_content'   => $post['post_content'],
					'post_excerpt'   => $post['post_excerpt'],
					'post_title'     => $post['post_title'],
					'post_status'    => $post['status'],
					'post_name'      => $post['post_name'],
					'comment_status' => $post['comment_status'],
					'ping_status'    => $post['ping_status'],
					'guid'           => $post['guid'],
					'post_parent'    => $post_parent,
					'menu_order'     => $post['menu_order'],
					'post_type'      => $post['post_type'],
					'post_password'  => $post['post_password']
				);

				if ( 'attachment' == $postdata['post_type'] ) {
					$remote_url = ! empty( $post['attachment_url'] ) ? $post['attachment_url'] : $post['guid'];

					$postdata['upload_date'] = $post['post_date'];
					if ( isset( $post['postmeta'] ) ) {
						foreach ( $post['postmeta'] as $meta ) {
							if ( $meta['key'] == '_wp_attached_file' ) {
								if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta['value'], $matches ) ) {
									$postdata['upload_date'] = $matches[0];
								}
								break;
							}
						}
					}

					$comment_post_ID = $post_id = $this->process_attachment( $postdata, $remote_url );
				} else {
					$comment_post_ID = $post_id = wp_insert_post( $postdata, true );
				}

				if ( is_wp_error( $post_id ) ) {
					continue;
				}

				if ( $post['is_sticky'] == 1 ) {
					stick_post( $post_id );
				}
			}

			$this->processed_posts[ intval( $post['post_id'] ) ] = (int) $post_id;

			if ( ! empty( $post['terms'] ) ) {
				$terms_to_set = array();
				foreach ( $post['terms'] as $term ) {
					$taxonomy    = ( 'tag' == $term['domain'] ) ? 'post_tag' : $term['domain'];
					$term_exists = term_exists( $term['slug'], $taxonomy );
					$term_id     = is_array( $term_exists ) ? $term_exists['term_id'] : $term_exists;
					if ( ! $term_id ) {
						$t = wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
						if ( ! is_wp_error( $t ) ) {
							$term_id = $t['term_id'];
						} else {
							printf( __( 'Failed to import %s %s', 'wordpress-importer' ), esc_html( $taxonomy ), esc_html( $term['name'] ) );
							if ( defined( 'IMPORT_DEBUG' ) && IMPORT_DEBUG ) {
								echo ': ' . $t->get_error_message();
							}
							echo '<br />';
							continue;
						}
					}
					$terms_to_set[ $taxonomy ][] = intval( $term_id );
				}

				foreach ( $terms_to_set as $tax => $ids ) {
					$tt_ids = wp_set_post_terms( $post_id, $ids, $tax );
				}
				unset( $post['terms'], $terms_to_set );
			}

			if ( ! empty( $post['comments'] ) ) {
				$num_comments      = 0;
				$inserted_comments = array();
				foreach ( $post['comments'] as $comment ) {
					$comment_id                                         = $comment['comment_id'];
					$newcomments[ $comment_id ]['comment_post_ID']      = $comment_post_ID;
					$newcomments[ $comment_id ]['comment_author']       = $comment['comment_author'];
					$newcomments[ $comment_id ]['comment_author_email'] = $comment['comment_author_email'];
					$newcomments[ $comment_id ]['comment_author_IP']    = $comment['comment_author_IP'];
					$newcomments[ $comment_id ]['comment_author_url']   = $comment['comment_author_url'];
					$newcomments[ $comment_id ]['comment_date']         = $comment['comment_date'];
					$newcomments[ $comment_id ]['comment_date_gmt']     = $comment['comment_date_gmt'];
					$newcomments[ $comment_id ]['comment_content']      = $comment['comment_content'];
					$newcomments[ $comment_id ]['comment_approved']     = $comment['comment_approved'];
					$newcomments[ $comment_id ]['comment_type']         = $comment['comment_type'];
					$newcomments[ $comment_id ]['comment_parent']       = $comment['comment_parent'];
					$newcomments[ $comment_id ]['commentmeta']          = isset( $comment['commentmeta'] ) ? $comment['commentmeta'] : array();
					if ( isset( $this->processed_authors[ $comment['comment_user_id'] ] ) ) {
						$newcomments[ $comment_id ]['user_id'] = $this->processed_authors[ $comment['comment_user_id'] ];
					}
				}
				ksort( $newcomments );

				foreach ( $newcomments as $key => $comment ) {
					if ( ! $post_exists || ! comment_exists( $comment['comment_author'], $comment['comment_date'] ) ) {
						if ( isset( $inserted_comments[ $comment['comment_parent'] ] ) ) {
							$comment['comment_parent'] = $inserted_comments[ $comment['comment_parent'] ];
						}
						$comment                   = wp_filter_comment( $comment );
						$inserted_comments[ $key ] = wp_insert_comment( $comment );

						foreach ( $comment['commentmeta'] as $meta ) {
							$value = maybe_unserialize( $meta['value'] );
							add_comment_meta( $inserted_comments[ $key ], $meta['key'], $value );
						}

						$num_comments ++;
					}
				}
				unset( $newcomments, $inserted_comments, $post['comments'] );
			}

			if ( isset( $post['postmeta'] ) ) {

				foreach ( $post['postmeta'] as $meta ) {
					$key   = apply_filters( 'import_post_meta_key', $meta['key'] );
					$value = false;

					if ( '_edit_last' == $key ) {
						if ( isset( $this->processed_authors[ intval( $meta['value'] ) ] ) ) {
							$value = $this->processed_authors[ intval( $meta['value'] ) ];
						} else {
							$key = false;
						}
					}

					if ( $key ) {
						if ( ! $value ) {
							$value = maybe_unserialize( $meta['value'] );
						}
						if ( is_string( $value ) && ( modman_isJson( $value ) ) ) {
							$value	= wp_slash( $value );

						}
						add_post_meta( $post_id, $key, $value );
						do_action( 'import_post_meta', $post_id, $key, $value );

						if ( '_thumbnail_id' == $key ) {
							$this->featured_images[ $post_id ] = (int) $value;
						}
					}
				}

				toolset_import_associations_of_child( $post_id );
			}
		}

		unset( $this->posts );
		return $result;
	}

	/**
	 * Override process terms function to correctly import WPML terms to database
	 *
	 * @param array $args
	 */
	function process_categories( $args = array() ) {

		$has_wpml_implementation = false;
		if ( isset( $args['has_wpml_implementation'] ) ) {
			$has_wpml_implementation = $args['has_wpml_implementation'];
		}

		if ( $has_wpml_implementation ) {

			$wpml_term_taxonomy_array = $this->wpml_term_taxonomy_data_func();

		}

		if ( empty( $this->categories ) ) {
			return;
		}

		foreach ( $this->categories as $cat ) {
			$term_id = term_exists( $cat['category_nicename'], 'category' );

			if ( $term_id ) {
				if ( is_array( $term_id ) ) {
					$term_id = $term_id['term_id'];
				}
				if ( isset( $cat['term_id'] ) ) {
					$this->processed_terms[ intval( $cat['term_id'] ) ] = (int) $term_id;

					if ( $has_wpml_implementation ) {
						$term_taxonomy_id_from_referencex_site                                = $wpml_term_taxonomy_array[ $term_id ];
						$this->processed_terms_wpml[ $term_taxonomy_id_from_referencex_site ] = $term_id;
					}
				}
				continue;
			}

			$category_parent      = empty( $cat['category_parent'] ) ? 0 : category_exists( $cat['category_parent'] );
			$category_description = isset( $cat['category_description'] ) ? $cat['category_description'] : '';
			$catarr               = array(
				'category_nicename'    => $cat['category_nicename'],
				'category_parent'      => $category_parent,
				'cat_name'             => $cat['cat_name'],
				'category_description' => $category_description
			);

			$id = wp_insert_category( $catarr );
			if ( ! is_wp_error( $id ) ) {

				if ( isset( $cat['term_id'] ) ) {
					$this->processed_terms[ intval( $cat['term_id'] ) ] = $id;

					if ( $has_wpml_implementation ) {

						$term_taxonomy_id_from_reference_site = $wpml_term_taxonomy_array[ intval( $cat['term_id'] ) ];

						$term_taxonomy_id_from_term_insertion = $id;

						$this->processed_terms_wpml[ $term_taxonomy_id_from_reference_site ] = $term_taxonomy_id_from_term_insertion;
					}
				}
			} else {
				printf( __( 'Failed to import category %s', 'wordpress-importer' ), esc_html( $cat['category_nicename'] ) );
				if ( defined( 'IMPORT_DEBUG' ) && IMPORT_DEBUG ) {
					echo ': ' . $id->get_error_message();
				}
				echo '<br />';
				continue;
			}
		}

		unset( $this->categories );
	}

	/**
	 * @param array $args
	 */
	function process_terms( $args = array() ) {

		$has_wpml_implementation = false;
		if ( isset( $args['has_wpml_implementation'] ) ) {
			$has_wpml_implementation = $args['has_wpml_implementation'];
		}

		if ( $has_wpml_implementation ) {

			$wpml_term_taxonomy_array = $this->wpml_term_taxonomy_data_func();

		}

		if ( empty( $this->terms ) ) {
			return;
		}

		foreach ( $this->terms as $term ) {
			$term_id = term_exists( $term['slug'], $term['term_taxonomy'] );

			if ( $term_id ) {
				if ( is_array( $term_id ) ) {
					$term_id = $term_id['term_id'];
				}
				if ( isset( $term['term_id'] ) ) {
					$this->processed_terms[ intval( $term['term_id'] ) ] = (int) $term_id;

					if ( $has_wpml_implementation ) {
						$this->processed_terms_wpml[ intval( $term['term_id'] ) ] = (int) $term_id;
					}
				}
				continue;
			}

			if ( empty( $term['term_parent'] ) ) {
				$parent = 0;
			} else {
				$parent = term_exists( $term['term_parent'], $term['term_taxonomy'] );
				if ( is_array( $parent ) ) {
					$parent = $parent['term_id'];
				}
			}
			$description = isset( $term['term_description'] ) ? $term['term_description'] : '';
			$termarr     = array( 'slug'        => $term['slug'],
			                      'description' => $description,
			                      'parent'      => intval( $parent )
			);

			$id = wp_insert_term( $term['term_name'], $term['term_taxonomy'], $termarr );
			if ( ! is_wp_error( $id ) ) {
				if ( isset( $term['term_id'] ) ) {
					$this->processed_terms[ intval( $term['term_id'] ) ] = $id['term_id'];

					if ( $has_wpml_implementation ) {

						$term_taxonomy_id_from_reference_site = $wpml_term_taxonomy_array[ intval( $term['term_id'] ) ];

						$term_taxonomy_id_from_term_insertion = $id['term_taxonomy_id'];

						$this->processed_terms_wpml[ $term_taxonomy_id_from_reference_site ] = $term_taxonomy_id_from_term_insertion;
					}
				}
			} else {
				printf( __( 'Failed to import %s %s', 'wordpress-importer' ), esc_html( $term['term_taxonomy'] ), esc_html( $term['term_name'] ) );
				if ( defined( 'IMPORT_DEBUG' ) && IMPORT_DEBUG ) {
					echo ': ' . $id->get_error_message();
				}
				echo '<br />';
				continue;
			}
		}

		unset( $this->terms );
	}

	function wpml_term_taxonomy_data_func() {

		$current_site_settings = $this->current_site_settings;
		$download_url_settings = $current_site_settings->download_url;
		$download_url_settings = (string) $download_url_settings;

		$remote_xml_url         = $download_url_settings . '/wpml_term_taxonomy_data.xml';
		$remote_xml_url_headers = @get_headers( $remote_xml_url );

		if ( strpos( $remote_xml_url_headers[0], '200 OK' ) ) {

			$data_wpml_terms_remote = wpv_remote_xml_get( $remote_xml_url );

			if ( ! ( $data_wpml_terms_remote ) ) {
				return false;
			}

			$xml_wpml_terms_settings         = simplexml_load_string( $data_wpml_terms_remote );
			$import_data_wpml_terms_taxonomy = wpv_admin_import_export_simplexml2array( $xml_wpml_terms_settings );

			foreach ( $import_data_wpml_terms_taxonomy as $key_map => $values_map ) {
				$import_data_wpml_terms_map[] = $values_map;
				unset( $import_data_wpml_terms_map[ $key_map ] );
			}

			$wp_terms_taxonomy_reference_site = array();

			foreach ( $import_data_wpml_terms_map as $key => $inner_array ) {
				$wp_terms_taxonomy_reference_site[ $inner_array['term_id'] ] = $inner_array['term_taxonomy_id'];
			}

			return $wp_terms_taxonomy_reference_site;

		}

	}
}
