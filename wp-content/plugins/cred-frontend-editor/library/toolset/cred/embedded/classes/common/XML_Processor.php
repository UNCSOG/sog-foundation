<?php

use OTGS\Toolset\CRED\Controller\FieldsControl\Db;

final class CRED_XML_Processor {

	public static $use_zip_if_available = true;
	private static $add_CDATA = false;
	private static $root = 'forms';
	private static $filename = '';

	/**
	 * Helper to get the post expiration settings.
	 *
	 * @return array
	 * @since 2.3
	 */
	private static function get_normalized_post_expiration_settings() {
		$settings = apply_filters( 'toolset_forms_get_post_expiration_settings', array() );
		return $settings;
	}

	/**
	 * Helper to set the normalized post expiration settings.
	 *
	 * @param array $settings
	 * @since 2.3
	 */
	private static function set_post_exporation_settings( $settings ) {
		$settings = cred_sanitize_array( $settings );
		do_action( 'toolset_forms_set_post_expiration_settings', $settings );
		if ( apply_filters( 'toolset_forms_is_post_expiration_enabled', false ) ) {
			do_action( 'toolset_forms_setup_post_expiration_schedule' );
		} else {
			do_action( 'toolset_forms_clear_post_expiration_schedule' );
		}
	}

	/**
	 * Merge recursive arrays.
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 * @since 2.3
	 */
	private static function array_merge_distinct( array $array1, array &$array2 ) {
		$merged = $array1;
		foreach ( $array2 as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
				$merged[ $key ] = self::array_merge_distinct( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * @param $array
	 * @param $depth
	 * @param $parent
	 *
	 * @return string
	 */
	private static function arrayToXml( $array, $depth, $parent ) {
		$output = '';
		$indent = str_repeat( ' ', $depth * 4 );
		$child_key = false;
		if ( isset( $array['__key'] ) ) {
			$child_key = $array['__key'];
			unset( $array['__key'] );
		}
		foreach ( $array as $key => $value ) {
			if ( empty( $key ) && $key !== 0 ) {
				continue;
			}

			if ( ! ( in_array( $key, array(
					'settings',
					'post_expiration_settings',
					'custom_fields',
				) ) && $parent == self::$root )
			) {
				$key = $child_key ? $child_key : $key;
			}
			if ( is_numeric( $key ) ) {
				$key = $parent . '_item';
			} //.$key;
			if ( ! is_array( $value ) && ! is_object( $value ) ) {
				if ( self::$add_CDATA && ! is_numeric( $value ) && ! empty( $value ) ) {
					$output .= $indent . "<$key><![CDATA[" . esc_html( $value ) . "]]></$key>\r\n";
				} else {
					$output .= $indent . "<$key>" . esc_html( $value ) . "</$key>\r\n";
				}
			} else {
				if ( is_object( $value ) ) {
					$value = (array) $value;
				}

				//$depth++;
				$output_temp = self::arrayToXml( $value, $depth + 1, $key );
				if ( ! empty( $output_temp ) ) {
					$output .= $indent . "<$key>\r\n";
					$output .= $output_temp;
					$output .= $indent . "</$key>\r\n";
				}
				//$depth--;
			}
		}

		return $output;
	}

	/**
	 * @param $array
	 * @param $root_element
	 *
	 * @return string
	 */
	private static function toXml( $array, $root_element ) {
		if ( empty( $array ) ) {
			return "";
		}
		$xml = "";
		$xml .= "<?xml version=\"1.0\" encoding=\"" . get_option( 'blog_charset' ) . "\"?>\r\n";
		$xml .= "<$root_element>\r\n";
		$xml .= self::arrayToXml( $array[ $root_element ], 1, $root_element );
		$xml .= "</$root_element>";

		return $xml;
	}

	/**
	 * @param $element
	 *
	 * @return array|string
	 */
	private static function toArray( $element ) {
		$element = is_string( $element ) ? htmlspecialchars_decode( trim( $element ), ENT_QUOTES ) : $element;
		if ( ! empty( $element ) && is_object( $element ) ) {
			$element = (array) $element;
		}
		if ( empty( $element ) ) {
			$element = '';
		}
		if ( is_array( $element ) ) {
			foreach ( $element as $k => $v ) {
				$v = is_string( $v ) ? htmlspecialchars_decode( trim( $v ), ENT_QUOTES ) : $v;
				if ( empty( $v ) ) {
					$element[ $k ] = '';
					continue;
				}
				$add = self::toArray( $v );
				if ( ! empty( $add ) ) {
					$element[ $k ] = $add;
				} else {
					$element[ $k ] = '';
				}
				// numeric arrays when -> toXml take '_item' suffixes
				// do reverse process here, now it is generic
				// not used here yet
				/* if (is_array($element[$k]) && isset($element[$k][$k.'_item']))
                  {
                  $element[$k] = array_values((array)$element[$k][$k.'_item']);
                  } */
			}
		}

		if ( empty( $element ) ) {
			$element = '';
		}

		return $element;
	}

	/**
	 * @param $data
	 * @param $image_data
	 *
	 * @return mixed
	 */
	private static function denormalizeData( $data, $image_data ) {
		global $_wp_additional_image_sizes;
		static $attached_images_sizes = null;
		//static $home_url=null;

		if ( null === $attached_images_sizes ) {
			if ( isset( $_wp_additional_image_sizes ) ) {
				// all possible thumbnail sizes for attached images
				$attached_images_sizes = array_merge(
				// additional thumbnail sizes
					array_keys( $_wp_additional_image_sizes ),
					// wp default thumbnail sizes
					array( 'thumbnail', 'medium', 'large' )
				);
			} else {
				// all possible thumbnail sizes for attached images
				$attached_images_sizes = array( 'thumbnail', 'medium', 'large' );
			}
			//$home_url=home_url('/');
		}

		// which fields need normalization replacements
		$denormalizedFields = array( 'post_content' );

		foreach ( $image_data as $media ) {
			// used to replace actual urls with hash placeholders
			$image_replace_map = array();

			$mediaid = $media['id'];
			foreach ( $attached_images_sizes as $ts ) {
				$mediathumbdata = wp_get_attachment_image_src( $mediaid, $ts );
				if ( ! empty( $mediathumbdata ) && isset( $mediathumbdata[0] ) ) {
					// custom size hash placeholder
					$image_replace_map[ '%%' . $media['image_hash'] . '_' . $ts . '%%' ] = $mediathumbdata[0];
				}
			}
			$pattern = '%%' . preg_quote( $media['image_hash'], '/' ) . '_[a-zA-Z0-9\-_]*?%%';

			// do replacements
			foreach ( $denormalizedFields as $field ) {
				$matched = preg_match_all( '/' . $pattern . '/', $data[ $field ], $matches );
				if ( false !== $matched && 0 < $matched ) {
					if ( isset( $matches[0] ) ) {
						$replacements = array();
						foreach ( $matches[0] as $match ) {
							if ( isset( $image_replace_map[ $match ] ) ) {
								$replacements[ $match ] = $image_replace_map[ $match ];
							} else // fallback to default size, 'medium'
							{
								$replacements[ $match ] = $image_replace_map[ '%%' . $media['image_hash'] . '_medium%%' ];
							}
						}
						$before = array_keys( $replacements );
						$after = array_values( $replacements );
						$data[ $field ] = str_replace( $before, $after, $data[ $field ] );
					}
				}
			}
		}

		return $data;
	}

	/**
	 * @param $data
	 *
	 * @return array|object
	 */
	private static function normalizeOrdering( $data ) {
		$dataobject = false;
		if ( is_object( $data ) ) {
			$dataobject = true;
			$data = (array) $data;
		}
		if ( is_array( $data ) ) {
			ksort( $data, SORT_STRING );
			foreach ( $data as $k => $v ) {
				$isobject = false;
				if ( is_object( $v ) ) {
					$isobject = true;
					$v = (array) $v;
				}

				if ( is_array( $v ) ) {
					$v = self::normalizeOrdering( $v );
				}

				if ( $isobject ) {
					$v = (object) $v;
				}

				$data[ $k ] = $v;
			}
		}
		if ( $dataobject ) {
			$data = (object) $data;
		}

		return $data;
	}

	/**
	 * @param $data
	 *
	 * @return array|object
	 */
	private static function normalizeData( $data ) {
		global $_wp_additional_image_sizes;
		static $attached_images_sizes = null;
		static $home_url = null;

		if ( null === $attached_images_sizes ) {
			if ( isset( $_wp_additional_image_sizes ) ) {
				// all possible thumbnail sizes for attached images
				$attached_images_sizes = array_merge(
				// additional thumbnail sizes
					array_keys( $_wp_additional_image_sizes ),
					// wp default thumbnail sizes
					array( 'thumbnail', 'medium', 'large' )
				);
			} else {
				// all possible thumbnail sizes for attached images
				$attached_images_sizes = array( 'thumbnail', 'medium', 'large' );
			}
			$home_url = home_url( '/' );
		}

		// which fields need normalization replacements
		$normalizedFields = array( 'post_content' );

		// used to replace actual urls with hash placeholders
		$image_replace_map = array();

		// handle media/image attachments
		if ( isset( $data['media'] ) && ! empty( $data['media'] ) ) {
			$attached_media = $data['media'];
			// re-create media array without ordering that breaks hash
			$data['media'] = array();
			foreach ( $attached_media as $ii => $media ) {
				$mediaid = $media['ID'];
				foreach ( $attached_images_sizes as $ts ) {
					$mediathumbdata = wp_get_attachment_image_src( $mediaid, $ts );
					if ( ! empty( $mediathumbdata ) && isset( $mediathumbdata[0] ) ) {
						// custom size hash placeholder
						$image_replace_map[ $mediathumbdata[0] ] = '%%' . $media['image_hash'] . '_' . $ts . '%%';
					}
				}

				// normalize guid
				$media['guid'] = $media['image_hash'];
				//$media['base_name']=$media['image_hash'];
				// re-create media array without ordering that breaks hash
				$data['media'][ $media['image_hash'] ] = $media;
				// free some memory
				unset( $attached_media[ $ii ] );
			}
			// free some memory
			unset( $attached_media );

			// NOTE: notifications also have numeric ordering, which may not matter
			// however right now the notifications ordering matters in computing the hash
			// do any image replacements to normalize content
			if ( ! empty( $image_replace_map ) ) {
				$before = array_keys( $image_replace_map );
				$after = array_values( $image_replace_map );
				foreach ( $normalizedFields as $field ) {
					// normalize field by using placeholders
					if ( isset( $data[ $field ] ) ) {
						$data[ $field ] = str_replace( $before, $after, $data[ $field ] );
					}
				}
			}
		}

		// normalize post/page ids, by using placeholders of slugs (a little more generic)
		// use  get_page_by_path( $page_path, $output, $post_type ); to reverse this transformation
		if ( ! empty( $data['meta'] ) ) {
			//if (isset($data['meta']['form_settings']) && is_numeric($data['meta']['form_settings']->form_action_page))
			//    $data['meta']['form_settings']->form_action_page=/*basename(*/ untrailingslashit(str_replace($home_url, '' /*'%%HOME_URL%%'*/, get_permalink($data['meta']['form_settings']->form_action_page))); //);
			if ( isset( $data['meta']['form_settings'] ) && isset( $data['meta']['form_settings']->form['action_page'] ) && is_numeric( $data['meta']['form_settings']->form['action_page'] ) ) {
				$_page_id = intval( $data['meta']['form_settings']->form['action_page'] );
				$data['meta']['form_settings']->form['action_page'] = untrailingslashit( str_replace( $home_url, '', get_permalink( $_page_id ) ) );
			}
		}

		// normalize ordering
		$data = self::normalizeOrdering( $data );

		return $data;
	}

	/**
	 * @param $data
	 * @param $include
	 *
	 * @return array|object
	 */
	private static function excludeFields( $data, $include ) {
		$dataobject = false;
		if ( is_object( $data ) ) {
			$data = (array) $data;
			$dataobject = true;
		}

		foreach ( $data as $k => $v ) {
			if ( ! isset( $include[ $k ] ) && ! isset( $include['*'] ) ) {
				unset( $data[ $k ] );
				continue;
			}
			if ( isset( $include[ $k ] ) && is_array( $include[ $k ] ) ) {
				$data[ $k ] = self::excludeFields( $data[ $k ], $include[ $k ] );
			} elseif ( isset( $include['*'] ) && is_array( $include['*'] ) ) {
				$data[ $k ] = self::excludeFields( $data[ $k ], $include['*'] );
			}
		}

		if ( $dataobject ) {
			$data = (object) $data;
		}

		return $data;
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	private static function cloneData( $data ) {
		return unserialize( serialize( $data ) );
	}

	private static function doHash( $data1, $normalizeData = true ) {
		// STEP 0: if using reference clone the data
		$data = self::cloneData( $data1 );

		// STEP 1: normalize placeholders, ordering etc..
		// normalized data if needed
		if ( $normalizeData ) {
			$data = self::normalizeData( $data );
		}

		// STEP 2: exclude fields not relevant to hash computation
		// hash computed on only these data fields
		$hashFields = array(
			'post_content' => true,
			'post_title' => true,
			'post_type' => true,
			'meta' => true,
			'media' => array(
				'*' => array(
					//'post_title' => true,
					'post_content' => true,
					'post_excerpt' => true,
					'post_status' => true,
					'post_type' => true,
					'post_mime_type' => true,
					//'guid' => true,
					'alt' => true,
					/* 'image_data' => true, */
					'image_hash' => true,
				),
			),
		);

		// if field is NOT relevant to hash computation, ignore (remove)
		$data = self::excludeFields( $data, $hashFields );

		// STEP 3: normalize spaces, new lines etc..
		// collapse spaces, new lines etc.. ghost new lines break hash comparisons
		if ( isset( $data['post_content'] ) ) {
			$data['post_content'] = preg_replace( '/\s+/', '', $data['post_content'] );
		}

		if ( isset( $data['meta'] ) ) {
			if ( isset( $data['meta']['extra'] ) ) {
				if ( isset( $data['meta']['extra']->css ) ) {
					$data['meta']['extra']->css = preg_replace( '/\s+/', '', $data['meta']['extra']->css );
				}
				if ( isset( $data['meta']['extra']->js ) ) {
					$data['meta']['extra']->js = preg_replace( '/\s+/', '', $data['meta']['extra']->js );
				}
			}
			if ( isset( $data['meta']['form_settings'] ) ) {
				if ( isset( $data['meta']['form_settings']->form['action_message'] ) ) {
					$data['meta']['form_settings']->form['action_message'] = preg_replace( '/\s+/', '', $data['meta']['form_settings']->form['action_message'] );
				}
			}
			if ( isset( $data['meta']['notification'] ) ) {
				if ( isset( $data['meta']['notification']->notifications ) && is_array( $data['meta']['notification']->notifications ) ) {
					foreach ( $data['meta']['notification']->notifications as $ii => $notif ) {
						$data['meta']['notification']->notifications[ $ii ]['mail']['body'] = preg_replace( '/\s+/', '', $data['meta']['notification']->notifications[ $ii ]['mail']['body'] );
					}
				}
			}
			//EMERSON: Increase consistency of hashes check in module manager 1.1
			/* START */
			$data['meta']['form_settings'] = get_object_vars( $data['meta']['form_settings'] );
			$data['meta']['notification'] = get_object_vars( $data['meta']['notification'] );

			if ( ( isset( $data['meta']['form_settings']['form'] ) ) && ( ! ( empty( $data['meta']['form_settings']['form'] ) ) ) ) {

				$set_to_integer_hashing = array(
					'has_media_button',
					'has_toolset_buttons',
					'has_media_manager',
					'hide_comments',
					'include_captcha_scaffold',
					'include_wpml_scaffold',
					'redirect_delay',
				);

				foreach ( $data['meta']['form_settings']['form'] as $k1 => $v1 ) {

					if ( ( $k1 == 'action_page' ) || ( $k1 == 'action_message' ) ) {

						unset( $data['meta']['form_settings']['form'][ $k1 ] );
					}

					if ( in_array( $k1, $set_to_integer_hashing ) ) {

						$data['meta']['form_settings']['form'][ $k1 ] = (integer) $data['meta']['form_settings']['form'][ $k1 ];
					}
				}
			}
			if ( ( isset( $data['meta']['notification']['notifications'] ) ) && ( ! ( empty( $data['meta']['notification']['notifications'] ) ) ) ) {

				foreach ( $data['meta']['notification']['notifications'] as $k2 => $v2 ) {

					foreach ( $v2 as $k3 => $v3 ) {

						if ( $k3 == 'to' ) {

							if ( ( isset( $data['meta']['notification']['notifications'][ $k2 ]['to']['type'] ) ) && ( ! ( empty( $data['meta']['notification']['notifications'][ $k2 ]['to']['type'] ) ) ) ) {

								if ( ! ( is_array( $data['meta']['notification']['notifications'][ $k2 ]['to']['type'] ) ) ) {
									$data['meta']['notification']['notifications'][ $k2 ]['to']['type'] = array( $data['meta']['notification']['notifications'][ $k2 ]['to']['type'] );
								}
							}
						}
					}
				}
			}
			/* END */
		}

		// STEP 4: compute and return actual hash now, on normalized data
		$hash = sha1( serialize( $data ) );

		return $hash;
	}

	/**
	 * @param array $form_ids
	 * @param array $options
	 * @param $mode
	 * @param bool $hashes
	 *
	 * @return array
	 */
	public static function getSelectedFormsForExport( $form_ids = array(), $options = array(), &$mode = '', &$hashes = false ) {
		if ( empty( $form_ids ) ) {
			return array();
		}

		$data = array();

		$forms = CRED_Loader::get( 'MODEL/Forms' )->getFormsForExport( $form_ids );

		$mode = 'forms';
		if ( ! empty( $forms ) && count( $forms ) > 0 ) {
			if ( 'all' == $form_ids ) {
				$mode = 'all-post-forms';
			} elseif ( count( $forms ) == 1 ) {
				$mode = sanitize_title( $forms[0]->post_title );
			} else {
				$mode = 'selected-forms';
			}
		}
		// hashes data
		if ( false !== $hashes ) {
			$hashes = array();
		}

		if ( ! empty( $forms ) ) {
			$export_tags = array( 'ID', 'post_content', 'post_title', 'post_name', 'post_type' );
			$data[ self::$root ] = array( '__key' => 'form' );

			// allow 3rd-party to add extra data on export
			$forms = apply_filters( 'cred_export_forms', $forms );

			foreach ( $forms as $key => $form ) {
				$form = (array) $form;
				// normalize data

				$form = self::normalizeData( $form );
				// compute and store (unique) hash
				if ( isset( $options['hash'] ) && $options['hash'] && false !== $hashes ) {
					// compute hash without doing additional normalization
					$hashes[ $form['ID'] ] = self::doHash( $form, false );
				}

				if ( $form['post_name'] ) {
					$form_data = array();
					foreach ( $export_tags as $e_tag ) {
						if ( isset( $form[ $e_tag ] ) ) {
							$form_data[ $e_tag ] = $form[ $e_tag ];
						}
					}
					$data[ self::$root ][ 'form-' . $form['ID'] ] = $form_data;
					if ( ! empty( $form['meta'] ) ) {
						$data[ self::$root ][ 'form-' . $form['ID'] ]['meta'] = array();
						foreach ( $form['meta'] as $meta_key => $meta_value ) {
							$data[ self::$root ][ 'form-' . $form['ID'] ]['meta'][ $meta_key ] = maybe_unserialize( $meta_value );
						}
						if ( empty( $data[ self::$root ][ 'form-' . $form['ID'] ]['meta'] ) ) {
							unset( $data[ self::$root ][ 'form-' . $form['ID'] ]['meta'] );
						}
					}
					if ( ! empty( $form['media'] ) ) {
						// covert back to numeric array ordering for xml export (changed when data were normalized)
						$form['media'] = array_values( $form['media'] );

						$data['form'][ 'form-' . $form['ID'] ]['media'] = array();
						foreach ( $form['media'] as $media_key => $media_value ) {
							$data[ self::$root ][ 'form-' . $form['ID'] ]['media'][ $media_key ] = maybe_unserialize( $media_value );
						}
						if ( empty( $data[ self::$root ][ 'form-' . $form['ID'] ]['media'] ) ) {
							unset( $data[ self::$root ][ 'form-' . $form['ID'] ]['media'] );
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 * @param array $form_ids
	 * @param array $options
	 * @param $mode
	 * @param bool $hashes
	 *
	 * @return array
	 */
	public static function getSelectedUserFormsForExport( $form_ids = array(), $options = array(), &$mode = '', &$hashes = false ) {
		if ( empty( $form_ids ) ) {
			return array();
		}

		$data = array();

		$forms = CRED_Loader::get( 'MODEL/UserForms' )->getFormsForExport( $form_ids );

		$mode = 'forms';
		if ( ! empty( $forms ) && count( $forms ) > 0 ) {
			if ( 'all' == $form_ids ) {
				$mode = 'all-user-forms';
			} elseif ( count( $forms ) == 1 ) {
				$mode = sanitize_title( $forms[0]->post_title );
			} else {
				$mode = 'selected-forms';
			}
		}
		// hashes data
		if ( false !== $hashes ) {
			$hashes = array();
		}

		if ( ! empty( $forms ) ) {
			$export_tags = array( 'ID', 'post_content', 'post_title', 'post_name', 'post_type', 'user_role' );
			$data[ self::$root ] = array( '__key' => 'form' );

			// allow 3rd-party to add extra data on export
			$forms = apply_filters( 'cred_export_forms', $forms );

			foreach ( $forms as $key => $form ) {
				$form = (array) $form;
				// normalize data

				$form = self::normalizeData( $form );
				// compute and store (unique) hash
				if ( isset( $options['hash'] ) && $options['hash'] && false !== $hashes ) {
					// compute hash without doing additional normalization
					$hashes[ $form['ID'] ] = self::doHash( $form, false );
				}

				if ( $form['post_name'] ) {
					$form_data = array();
					foreach ( $export_tags as $e_tag ) {
						if ( isset( $form[ $e_tag ] ) ) {
							$form_data[ $e_tag ] = $form[ $e_tag ];
						}
					}
					$data[ self::$root ][ 'form-' . $form['ID'] ] = $form_data;
					if ( ! empty( $form['meta'] ) ) {
						$data[ self::$root ][ 'form-' . $form['ID'] ]['meta'] = array();
						foreach ( $form['meta'] as $meta_key => $meta_value ) {
							$data[ self::$root ][ 'form-' . $form['ID'] ]['meta'][ $meta_key ] = maybe_unserialize( $meta_value );
						}
						if ( empty( $data[ self::$root ][ 'form-' . $form['ID'] ]['meta'] ) ) {
							unset( $data[ self::$root ][ 'form-' . $form['ID'] ]['meta'] );
						}
					}
					if ( ! empty( $form['media'] ) ) {
						// covert back to numeric array ordering for xml export (changed when data were normalized)
						$form['media'] = array_values( $form['media'] );

						$data['form'][ 'form-' . $form['ID'] ]['media'] = array();
						foreach ( $form['media'] as $media_key => $media_value ) {
							$data[ self::$root ][ 'form-' . $form['ID'] ]['media'][ $media_key ] = maybe_unserialize( $media_value );
						}
						if ( empty( $data[ self::$root ][ 'form-' . $form['ID'] ]['media'] ) ) {
							unset( $data[ self::$root ][ 'form-' . $form['ID'] ]['media'] );
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 * @param $xml
	 * @param $ajax
	 * @param $mode
	 */
	private static function output( $xml, $ajax, $mode ) {
		$sitename = sanitize_key( get_bloginfo( 'name' ) );
		if ( ! empty( $sitename ) ) {
			$sitename .= '-';
		}

		$filename = $sitename . $mode . '-' . date( 'Y-m-d' ) . '.xml';

		$data = $xml;

		if ( self::$use_zip_if_available && class_exists( 'ZipArchive' ) ) {
			$zipname = $filename . '.zip';
			$zip = new ZipArchive();

			$wp_upload_dir = wp_upload_dir();
			$upload_path = $wp_upload_dir['basedir'];
			$cred_temp_directory_path = $upload_path . DIRECTORY_SEPARATOR . '__cred__tmp__';

			if ( ! is_dir( $cred_temp_directory_path ) ) {
				mkdir( $cred_temp_directory_path );
			}

			$file = tempnam( $cred_temp_directory_path, "zip" );
			$zip->open( $file, ZipArchive::OVERWRITE );

			$res = $zip->addFromString( $filename, $xml );
			$zip->close();

			$data = file_get_contents( $file );
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: public" );
			header( "Content-Description: File Transfer" );
			header( "Content-Disposition: attachment; filename=" . $zipname );
			header( "Content-Type: application/zip" );
			header( "Content-length: " . strlen( $data ) . "\n\n" );
			header( "Content-Transfer-Encoding: binary" );
			if ( $ajax ) {
				header( "Set-Cookie: __CREDExportDownload=true; path=/" );
			}
			echo $data;
			unset( $data );
			unset( $xml );
			unlink( $file );
			die();
		} else {
			// download the xml.
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: public" );
			header( "Content-Description: File Transfer" );
			header( "Content-Disposition: attachment; filename=" . $filename );
			header( "Content-Type: application/xml" );
			header( "Content-length: " . strlen( $data ) . "\n\n" );
			if ( $ajax ) {
				header( "Set-Cookie: __CREDExportDownload=true; path=/" );
			}
			echo $data;
			unset( $data );
			unset( $xml );
			die();
		}
	}

	/**
	 * @param $file_name
	 *
	 * @return string|WP_Error
	 */
	public static function getXMLArrayByZip( $file_name ) {
		if ( class_exists( 'ZipArchive' ) ) {
			$zip = new \ZipArchive();
			if ($zip->open($file_name) !== false) {
				$xml_array = $zip->getFromIndex( 0 );
				$zip->close();

				return $xml_array;
			} else {
				return new WP_Error( 'could_not_open_file', __( 'Unable to open zip file', 'wp-cred' ) );
			}
		} else {
			return new WP_Error( 'could_not_open_file', __( 'Unable to open zip file', 'wp-cred' ) );
		}
	}

	/**
	 * @param $file_name
	 *
	 * @return bool|string|WP_Error
	 */
	public static function getXMLArrayByXml( $file_name ) {
		if ( file_exists( $file_name ) ) {
			return file_get_contents( $file_name );
		} else {
			return new WP_Error( 'could_not_open_file', __( 'Unable to open xml file', 'wp-cred' ) );
		}
	}

	/**
	 * @param $file
	 * Array
	 * (
	 * [name] => [path of the file]
	 * [tmp_name] => [temp path of the file]
	 * [type] => [file type extension]
	 * [size] => [file size]
	 * [error] => [0]
	 * )
	 *
	 * @return array|string|WP_Error
	 */
	public static function readXML( $file ) {
		$info = pathinfo( $file['name'] );
		$is_zip = ( $info['extension'] == 'zip' ) ? true : false;
		if ( $is_zip ) {
			$xml_array = self::getXMLArrayByZip( $file['tmp_name'] );
		} else {
			$is_xml = ( $info['extension'] == 'xml' ) ? true : false;
			if ( $is_xml ) {
				$xml_array = self::getXMLArrayByXml( $file['tmp_name'] );
			} else {
				return new WP_Error( 'could_not_open_file', __( 'Unable to open xml file', 'wp-cred' ) );
			}
		}

		if ( is_wp_error( $xml_array ) ) {
			return $xml_array;
		}

		if ( ! empty( $xml_array ) ) {
			if ( ! function_exists( 'simplexml_load_string' ) || ! function_exists( 'libxml_use_internal_errors' ) ) {
				return new WP_Error( 'xml_missing', __( 'The Simple XML library is missing.', 'wp-cred' ) );
			}
			libxml_use_internal_errors( true );

			// Make sure that HTML named entities do not break XML importing
			$html_named_entities = array(
				'&times;',
				'&nbsp;',
				'&eacute;',
				'&agrave;',
				'&ugrave;',
				'&egrave;',
				'&ocirc;',
				'&ndash;',
			);
			foreach ( $html_named_entities as $named_entity ) {
				// Wrap in CDATA to escape
				$xml_array = str_replace( $named_entity, "<![CDATA[$named_entity]]>", $xml_array );
			}
			$xml = simplexml_load_string( $xml_array );

			if ( ! $xml ) {
				return new WP_Error( 'not_xml_file', sprintf( __( 'The XML file (%s) could not be read.', 'wp-cred' ), $file['name'] ) );
			}

			$import_data = self::toArray( $xml );
			unset( $xml );

			return $import_data;
		} else {
			return new WP_Error( 'could_not_open_file', __( 'Could not read the import file.', 'wp-cred' ) );
		}
	}

	/**
	 * @param $form_data
	 * @param $fmodel
	 * @param $options
	 * @param $results
	 *
	 * @return bool|int
	 */
	public static function importSingleForm( $form_data, $fmodel, &$options, &$results ) {
		$form = new stdClass;
		$form->ID = '';
		$form->post_title = $form_data['post_title'];
		$form->post_content = isset( $form_data['post_content'] ) ? $form_data['post_content'] : '';

		$form->post_status = 'private';
		$form->post_type = CRED_FORMS_CUSTOM_POST_NAME;

		$slug = get_sample_permalink( $form, null, $form_data['post_name'] );
		$slug = $slug[1];

		$form->post_name = $slug;

		$fields = array();
		if ( isset( $form_data['meta'] ) && is_array( $form_data['meta'] ) && ! empty( $form_data['meta'] ) ) {
			// old format, backwards compatibility
			if (
				isset( $form_data['meta']['form_settings']['form_type'] ) ||
				isset( $form_data['meta']['form_settings']['post_type'] ) ||
				isset( $form_data['meta']['form_settings']['cred_theme_css'] )
			) {
				$fields['form_settings'] = new stdClass;
				$fields['form_settings']->form_type = isset( $form_data['meta']['form_settings']['form_type'] ) ? $form_data['meta']['form_settings']['form_type'] : '';
				$fields['form_settings']->form_action = isset( $form_data['meta']['form_settings']['form_action'] ) ? $form_data['meta']['form_settings']['form_action'] : '';
				$fields['form_settings']->form_action_page = isset( $form_data['meta']['form_settings']['form_action_page'] ) ? $form_data['meta']['form_settings']['form_action_page'] : '';
				$fields['form_settings']->redirect_delay = isset( $form_data['meta']['form_settings']['redirect_delay'] ) ? intval( $form_data['meta']['form_settings']['redirect_delay'] ) : 0;
				$fields['form_settings']->message = isset( $form_data['meta']['form_settings']['message'] ) ? $form_data['meta']['form_settings']['message'] : '';
				$fields['form_settings']->hide_comments = ( isset( $form_data['meta']['form_settings']['hide_comments'] ) && $form_data['meta']['form_settings']['hide_comments'] == '1' ) ? 1 : 0;
				$fields['form_settings']->include_captcha_scaffold = ( isset( $form_data['meta']['form_settings']['include_captcha_scaffold'] ) && $form_data['meta']['form_settings']['include_captcha_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->include_wpml_scaffold = ( isset( $form_data['meta']['form_settings']['include_wpml_scaffold'] ) && $form_data['meta']['form_settings']['include_wpml_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->has_media_button = ( isset( $form_data['meta']['form_settings']['has_media_button'] ) && $form_data['meta']['form_settings']['has_media_button'] == '0' ) ? 0 : 1;
				$fields['form_settings']->has_toolset_buttons = ( isset( $form_data['meta']['form_settings']['has_toolset_buttons'] ) && $form_data['meta']['form_settings']['has_toolset_buttons'] == '0' ) ? 0 : 1;
				$fields['form_settings']->has_media_manager = ( isset( $form_data['meta']['form_settings']['has_media_manager'] ) && $form_data['meta']['form_settings']['has_media_manager'] == '0' ) ? 0 : 1;
				$fields['form_settings']->post_type = isset( $form_data['meta']['form_settings']['post_type'] ) ? $form_data['meta']['form_settings']['post_type'] : '';
				$fields['form_settings']->post_status = isset( $form_data['meta']['form_settings']['post_status'] ) ? $form_data['meta']['form_settings']['post_status'] : 'draft';
				$fields['form_settings']->cred_theme_css = isset( $form_data['meta']['form_settings']['cred_theme_css'] ) ? $form_data['meta']['form_settings']['cred_theme_css'] : 'minimal';
				$fields['form_settings']->use_ajax = ( isset( $form_data['meta']['form_settings']['use_ajax'] ) && $form_data['meta']['form_settings']['use_ajax'] == '1' ) ? 1 : 0;

				$fields['wizard'] = isset( $form_data['meta']['wizard'] ) ? intval( $form_data['meta']['wizard'] ) : -1;

				$fields['extra'] = new stdClass;
				$fields['extra']->css = isset( $form_data['meta']['extra']['css'] ) ? $form_data['meta']['extra']['css'] : '';
				$fields['extra']->js = isset( $form_data['meta']['extra']['js'] ) ? $form_data['meta']['extra']['js'] : '';

				$fields['extra']->messages = $fmodel->getDefaultMessages();

				if ( isset( $form_data['meta']['extra']['messages']['messages_item'] ) ) {
					// make it array
					if ( ! isset( $form_data['meta']['extra']['messages']['messages_item'][0] ) ) {
						$form_data['meta']['extra']['messages']['messages_item'] = array( $form_data['meta']['extra']['messages']['messages_item'] );
					}

					foreach ( $form_data['meta']['extra']['messages']['messages_item'] as $msg ) {
						foreach ( array_keys( $fields['extra']->messages ) as $msgid ) {
							if ( isset( $msg[ $msgid ] ) ) {
								$fields['extra']->messages[ $msgid ] = $msg;
							}
						}
					}
				}

				if ( isset( $form_data['meta']['extra']['scaffold'] ) ) {
					$fields['extra']->scaffold = $form_data['meta']['extra']['scaffold'];
				}

				$fields['notification'] = new stdClass;
				$fields['notification']->notifications = array();
				if ( isset( $form_data['meta']['notification']['notifications']['notifications_item'] ) ) {
					// make it array
					if ( ! isset( $form_data['meta']['notification']['notifications']['notifications_item'][0] ) ) {
						$form_data['meta']['notification']['notifications']['notifications_item'] = array( $form_data['meta']['notification']['notifications']['notifications_item'] );
					}

					foreach ( $form_data['meta']['notification']['notifications']['notifications_item'] as $notif ) {
						$tmp = array();
						$tmp['mail_to_type'] = isset( $notif['mail_to_type'] ) ? $notif['mail_to_type'] : '';
						$tmp['mail_to_user'] = isset( $notif['mail_to_user'] ) ? $notif['mail_to_user'] : '';
						$tmp['mail_to_field'] = isset( $notif['mail_to_field'] ) ? $notif['mail_to_field'] : '';
						$tmp['mail_to_specific'] = isset( $notif['mail_to_specific'] ) ? $notif['mail_to_specific'] : '';
						// add new fields From Addr, From Name
						$tmp['from_addr'] = isset( $notif['from_addr'] ) ? $notif['from_addr'] : '';
						$tmp['from_name'] = isset( $notif['from_name'] ) ? $notif['from_name'] : '';
						$tmp['subject'] = isset( $notif['subject'] ) ? $notif['subject'] : '';
						$tmp['body'] = isset( $notif['body'] ) ? $notif['body'] : '';
						$fields['notification']->notifications[] = $tmp;
					}
				}
				$fields['notification']->enable = ( isset( $form_data['meta']['notification']['enable'] ) && $form_data['meta']['notification']['enable'] == '1' ) ? 1 : 0;
			} // new cred fields format here
			else {
				$fields['form_settings'] = (object) array(
					'form' => array(),
					'post' => array(),
				);
				$fields['form_settings']->form['type'] = isset( $form_data['meta']['form_settings']['form']['type'] ) ? $form_data['meta']['form_settings']['form']['type'] : '';
				$fields['form_settings']->form['action'] = isset( $form_data['meta']['form_settings']['form']['action'] ) ? $form_data['meta']['form_settings']['form']['action'] : '';
				$fields['form_settings']->form['action_page'] = isset( $form_data['meta']['form_settings']['form']['action_page'] ) ? $form_data['meta']['form_settings']['form']['action_page'] : '';
				$fields['form_settings']->form['redirect_delay'] = isset( $form_data['meta']['form_settings']['form']['redirect_delay'] ) ? intval( $form_data['meta']['form_settings']['form']['redirect_delay'] ) : 0;
				$fields['form_settings']->form['action_message'] = isset( $form_data['meta']['form_settings']['form']['action_message'] ) ? $form_data['meta']['form_settings']['form']['action_message'] : '';
				$fields['form_settings']->form['hide_comments'] = ( isset( $form_data['meta']['form_settings']['form']['hide_comments'] ) && $form_data['meta']['form_settings']['form']['hide_comments'] == '1' ) ? 1 : 0;
				$fields['form_settings']->form['include_captcha_scaffold'] = ( isset( $form_data['meta']['form_settings']['form']['include_captcha_scaffold'] ) && $form_data['meta']['form_settings']['form']['include_captcha_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->form['include_wpml_scaffold'] = ( isset( $form_data['meta']['form_settings']['form']['include_wpml_scaffold'] ) && $form_data['meta']['form_settings']['form']['include_wpml_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->form['has_media_button'] = ( isset( $form_data['meta']['form_settings']['form']['has_media_button'] ) && $form_data['meta']['form_settings']['form']['has_media_button'] == '0' ) ? 0 : 1;
				$fields['form_settings']->form['has_toolset_buttons'] = ( isset( $form_data['meta']['form_settings']['form']['has_toolset_buttons'] ) && $form_data['meta']['form_settings']['form']['has_toolset_buttons'] == '0' ) ? 0 : 1;
				$fields['form_settings']->form['has_media_manager'] = ( isset( $form_data['meta']['form_settings']['form']['has_media_manager'] ) && $form_data['meta']['form_settings']['form']['has_media_manager'] == '0' ) ? 0 : 1;
				$fields['form_settings']->post['post_type'] = isset( $form_data['meta']['form_settings']['post']['post_type'] ) ? $form_data['meta']['form_settings']['post']['post_type'] : '';
				$fields['form_settings']->post['post_status'] = isset( $form_data['meta']['form_settings']['post']['post_status'] ) ? $form_data['meta']['form_settings']['post']['post_status'] : 'draft';
				$fields['form_settings']->form['theme'] = isset( $form_data['meta']['form_settings']['form']['theme'] ) ? $form_data['meta']['form_settings']['form']['theme'] : 'minimal';
				$fields['form_settings']->form['use_ajax'] = ( isset( $form_data['meta']['form_settings']['form']['use_ajax'] ) && $form_data['meta']['form_settings']['form']['use_ajax'] == '1' ) ? 1 : 0;

				$fields['wizard'] = isset( $form_data['meta']['wizard'] ) ? intval( $form_data['meta']['wizard'] ) : -1;

				$fields['extra'] = (object) array(
					'css' => '',
					'js' => '',
					'messages' => $fmodel->getDefaultMessages(),
				);
				$fields['extra']->css = isset( $form_data['meta']['extra']['css'] ) ? $form_data['meta']['extra']['css'] : '';
				$fields['extra']->js = isset( $form_data['meta']['extra']['js'] ) ? $form_data['meta']['extra']['js'] : '';

				//EMERSON: Fix bug on Form text messages value not imported in Toolset Forms 1.2.2
				//This will cause the hash to be different after import,e.g. in Module manager 1.1
				//Commented are old codes
				/* START */

				//if (isset($form_data['meta']['extra']['messages']['messages_item']))
				if ( isset( $form_data['meta']['extra']['messages'] ) ) {
					// make it array
					/*
                      if (!isset($form_data['meta']['extra']['messages']['messages_item'][0]))
                      $form_data['meta']['extra']['messages']['messages_item']=array($form_data['meta']['extra']['messages']['messages_item']);
                     */
					if ( ! isset( $form_data['meta']['extra']['messages'] ) ) {
						$form_data['meta']['extra']['messages'] = array( $form_data['meta']['extra']['messages'] );
					}

					//foreach ($form_data['meta']['extra']['messages']['messages_item'] as $msg)
					foreach ( $form_data['meta']['extra']['messages'] as $msg ) {
						/*
                          foreach (array_keys($fields['extra']->messages) as $msgid)
                          {
                          if (isset($msg[$msgid]))
                          $fields['extra']->messages[$msgid]=$msg;
                          }
                         */
						foreach ( ( $fields['extra']->messages ) as $msgid_key => $msgid_value ) {

							if ( isset( $form_data['meta']['extra']['messages'][ $msgid_key ] ) && $form_data['meta']['extra']['messages'][ $msgid_key ] != $msgid_value ) {

								$fields['extra']->messages[ $msgid_key ] = $form_data['meta']['extra']['messages'][ $msgid_key ];
							}
						}
					}
				}
				/* END */

				if ( isset( $form_data['meta']['extra']['scaffold'] ) ) {
					$fields['extra']->scaffold = $form_data['meta']['extra']['scaffold'];
				}

				$fields['notification'] = (object) array(
					'enable' => 0,
					'notifications' => array(),
				);
				if ( isset( $form_data['meta']['notification']['notifications']['notifications_item'] ) ) {
					// make it array
					if ( ! isset( $form_data['meta']['notification']['notifications']['notifications_item'][0] ) ) {
						$form_data['meta']['notification']['notifications']['notifications_item'] = array( $form_data['meta']['notification']['notifications']['notifications_item'] );
					}

					foreach ( $form_data['meta']['notification']['notifications']['notifications_item'] as $notif ) {
						$tmp = array();
						$tmp['event'] = isset( $notif['event'] ) ? $notif['event'] : array();
						if ( isset( $tmp['event']['condition']['condition_item'] ) ) {
							if ( ! isset( $tmp['event']['condition']['condition_item'][0] ) ) {
								$tmp['event']['condition']['condition_item'] = array( $tmp['event']['condition']['condition_item'] );
							}
							$tmp['event']['condition'] = $tmp['event']['condition']['condition_item'];
						}
						$tmp['to'] = isset( $notif['to'] ) ? $notif['to'] : array();
						if ( isset( $tmp['to']['type']['type_item'] ) ) {
							if ( ! is_array( $tmp['to']['type']['type_item'] ) ) {
								$tmp['to']['type']['type_item'] = array( $tmp['to']['type']['type_item'] );
							}
							$tmp['to']['type'] = $tmp['to']['type']['type_item'];
						}
						// add new fields From Addr, From Name
						$tmp['from'] = isset( $notif['from'] ) ? $notif['from'] : array();
						$tmp['mail'] = isset( $notif['mail'] ) ? $notif['mail'] : array();
						$tmp['name'] = isset( $notif['name'] ) ? $notif['name'] : '(notification-name)';
						$tmp[ 'disabled' ] = ( isset( $notif[ 'disabled' ] ) && $notif[ 'disabled' ] == 1 ) ? $notif[ 'disabled' ] : 0;
						$fields['notification']->notifications[] = $tmp;
					}
				}
				$fields['notification']->enable = ( isset( $form_data['meta']['notification']['enable'] ) && $form_data['meta']['notification']['enable'] == '1' ) ? 1 : 0;

				// Toolset Forms post expiration import (new version 1.2.6)
				/* START */

				$fields['post_expiration'] = array(
					'action' => array(),
					'enable' => 0,
					'expiration_time' => array(),
				);

				if ( isset( $form_data['meta']['post_expiration'] ) ) {

					if ( isset( $form_data['meta']['post_expiration']['action'] ) ) {
						$fields['post_expiration']['action'] = $form_data['meta']['post_expiration']['action'];
					}
					if ( isset( $form_data['meta']['post_expiration']['enable'] ) ) {
						$fields['post_expiration']['enable'] = $form_data['meta']['post_expiration']['enable'];
					}
					if ( isset( $form_data['meta']['post_expiration']['expiration_time'] ) ) {
						$fields['post_expiration']['expiration_time'] = $form_data['meta']['post_expiration']['expiration_time'];
					}
				}
				/* END */
			}

			// change format here and provide defaults also
			$fields = array_merge( array(
				'form_settings' => (object) array(
					'form' => array(),
					'post' => array(),
				),
				'notification' => (object) array(
					'enable' => 0,
					'notifications' => array(),
				),
				'extra' => (object) array(
					'css' => '',
					'js' => '',
					'messages' => $fmodel->getDefaultMessages(),
					'scaffold' => '',
				),
			), $fmodel->changeFormat( $fields )
			);
		}

		// de-normalize fields
		if ( ! empty( $fields['form_settings']->form['action_page'] ) && ! is_numeric( $fields['form_settings']->form['action_page'] ) ) {
			$_page_ = get_page_by_path( $fields['form_settings']->form['action_page'], OBJECT, 'page' );
			if ( $_page_ && isset( $_page_->ID ) ) {
				$fields['form_settings']->form['action_page'] = $_page_->ID;
			} else {
				$fields['form_settings']->form['action_page'] = '';
			}
		}
		$_form_id = false;
		if (
			( isset( $options['overwrite_forms'] ) && $options['overwrite_forms'] )
			|| isset( $options['toolset-themes'] ) // Activate overwrite for toolset-thee
		) {
			$old_form = false;
			if ( isset( $options['force_skip_post_name'] ) || isset( $options['force_overwrite_post_name'] ) || isset( $options['force_duplicate_post_name'] ) ) {
				$old_form = get_page_by_path( $form->post_name, OBJECT, CRED_FORMS_CUSTOM_POST_NAME );
			} else {
				$forms = get_posts(
					array(
						'post_type'              => CRED_FORMS_CUSTOM_POST_NAME,
						'title'                  => $form->post_title,
						'post_status'            => 'all',
						'numberposts'            => 1,
						'update_post_term_cache' => false,
						'update_post_meta_cache' => false,
						'orderby'                => 'post_date ID',
						'order'                  => 'ASC',
					)
				);
				if ( ! empty ( $forms ) ) {
					$old_form = $forms[0];
				}
			}
			if ( $old_form ) {
				$form->ID = $old_form->ID;
				$_form_id = $form->ID;
				if ( $fmodel->updateForm( $form, $fields ) ) {
					$results['updated']++;
				} else {
					$results['failed']++;
					$results['errors'][] = sprintf( __( 'Item %s could not be saved', 'wp-cred' ), $form->post_title );
				}
			} else {
				$_form_id = $fmodel->saveForm( $form, $fields, true );
				if ( $_form_id ) {
					$results['new']++;
				} else {
					$results['failed']++;
					$results['errors'][] = sprintf( __( 'Item %s could not be saved', 'wp-cred' ), $form->post_title );
				}
			}
		} else {
			$_form_id = $fmodel->saveForm( $form, $fields, true );
			$results['new']++;
		}

		if ( $_form_id ) // allow 3rd-party to import extra data per form and update results variable accordingly
		{
			$results = apply_filters( 'cred_import_form', $results, $_form_id, $form_data );
		}

		return $_form_id;
	}

	private static function importSingleUserForm( $form_data, $fmodel, &$options, &$results ) {
		$form = new stdClass;
		$form->ID = '';
		$form->post_title = $form_data['post_title'];
		$form->post_content = isset( $form_data['post_content'] ) ? $form_data['post_content'] : '';

		$form->post_status = 'private';
		$form->post_type = CRED_USER_FORMS_CUSTOM_POST_NAME;

		$slug = get_sample_permalink( $form, $form_data['post_title'], $form_data['post_name'] );
		$slug = $slug[1];

		$form->post_name = $slug;

		$fields = array();
		if ( isset( $form_data['meta'] ) && is_array( $form_data['meta'] ) && ! empty( $form_data['meta'] ) ) {
			// old format, backwards compatibility
			if (
				isset( $form_data['meta']['form_settings']['form_type'] ) ||
				isset( $form_data['meta']['form_settings']['post_type'] ) ||
				isset( $form_data['meta']['form_settings']['cred_theme_css'] )
			) {
				$fields['form_settings'] = new stdClass;
				$fields['form_settings']->form_type = isset( $form_data['meta']['form_settings']['form_type'] ) ? $form_data['meta']['form_settings']['form_type'] : '';
				$fields['form_settings']->form_action = isset( $form_data['meta']['form_settings']['form_action'] ) ? $form_data['meta']['form_settings']['form_action'] : '';
				$fields['form_settings']->form_action_page = isset( $form_data['meta']['form_settings']['form_action_page'] ) ? $form_data['meta']['form_settings']['form_action_page'] : '';
				$fields['form_settings']->redirect_delay = isset( $form_data['meta']['form_settings']['redirect_delay'] ) ? intval( $form_data['meta']['form_settings']['redirect_delay'] ) : 0;
				$fields['form_settings']->message = isset( $form_data['meta']['form_settings']['message'] ) ? $form_data['meta']['form_settings']['message'] : '';
				$fields['form_settings']->hide_comments = ( isset( $form_data['meta']['form_settings']['hide_comments'] ) && $form_data['meta']['form_settings']['hide_comments'] == '1' ) ? 1 : 0;
				$fields['form_settings']->include_captcha_scaffold = ( isset( $form_data['meta']['form_settings']['include_captcha_scaffold'] ) && $form_data['meta']['form_settings']['include_captcha_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->include_wpml_scaffold = ( isset( $form_data['meta']['form_settings']['include_wpml_scaffold'] ) && $form_data['meta']['form_settings']['include_wpml_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->has_media_button = ( isset( $form_data['meta']['form_settings']['has_media_button'] ) && $form_data['meta']['form_settings']['has_media_button'] == '0' ) ? 0 : 1;
				$fields['form_settings']->has_toolset_buttons = ( isset( $form_data['meta']['form_settings']['has_toolset_buttons'] ) && $form_data['meta']['form_settings']['has_toolset_buttons'] == '0' ) ? 0 : 1;
				$fields['form_settings']->has_media_manager = ( isset( $form_data['meta']['form_settings']['has_media_manager'] ) && $form_data['meta']['form_settings']['has_media_manager'] == '0' ) ? 0 : 1;
				$fields['form_settings']->post_type = isset( $form_data['meta']['form_settings']['post_type'] ) ? $form_data['meta']['form_settings']['post_type'] : '';
				$fields['form_settings']->post_status = isset( $form_data['meta']['form_settings']['post_status'] ) ? $form_data['meta']['form_settings']['post_status'] : 'draft';
				$fields['form_settings']->cred_theme_css = isset( $form_data['meta']['form_settings']['cred_theme_css'] ) ? $form_data['meta']['form_settings']['cred_theme_css'] : 'minimal';
				$fields['form_settings']->use_ajax = ( isset( $form_data['meta']['form_settings']['use_ajax'] ) && $form_data['meta']['form_settings']['use_ajax'] == '1' ) ? 1 : 0;

				$fields['wizard'] = isset( $form_data['meta']['wizard'] ) ? intval( $form_data['meta']['wizard'] ) : -1;

				$fields['extra'] = new stdClass;
				$fields['extra']->css = isset( $form_data['meta']['extra']['css'] ) ? $form_data['meta']['extra']['css'] : '';
				$fields['extra']->js = isset( $form_data['meta']['extra']['js'] ) ? $form_data['meta']['extra']['js'] : '';

				$fields['extra']->messages = $fmodel->getDefaultMessages();

				if ( isset( $form_data['meta']['extra']['messages']['messages_item'] ) ) {
					// make it array
					if ( ! isset( $form_data['meta']['extra']['messages']['messages_item'][0] ) ) {
						$form_data['meta']['extra']['messages']['messages_item'] = array( $form_data['meta']['extra']['messages']['messages_item'] );
					}

					foreach ( $form_data['meta']['extra']['messages']['messages_item'] as $msg ) {
						foreach ( array_keys( $fields['extra']->messages ) as $msgid ) {
							if ( isset( $msg[ $msgid ] ) ) {
								$fields['extra']->messages[ $msgid ] = $msg;
							}
						}
					}
				}

				if ( isset( $form_data['meta']['extra']['scaffold'] ) ) {
					$fields['extra']->scaffold = $form_data['meta']['extra']['scaffold'];
				}

				$fields['notification'] = new stdClass;
				$fields['notification']->notifications = array();
				if ( isset( $form_data['meta']['notification']['notifications']['notifications_item'] ) ) {
					// make it array
					if ( ! isset( $form_data['meta']['notification']['notifications']['notifications_item'][0] ) ) {
						$form_data['meta']['notification']['notifications']['notifications_item'] = array( $form_data['meta']['notification']['notifications']['notifications_item'] );
					}

					foreach ( $form_data['meta']['notification']['notifications']['notifications_item'] as $notif ) {
						$tmp = array();
						$tmp['mail_to_type'] = isset( $notif['mail_to_type'] ) ? $notif['mail_to_type'] : '';
						$tmp['mail_to_user'] = isset( $notif['mail_to_user'] ) ? $notif['mail_to_user'] : '';
						$tmp['mail_to_field'] = isset( $notif['mail_to_field'] ) ? $notif['mail_to_field'] : '';
						$tmp['mail_to_specific'] = isset( $notif['mail_to_specific'] ) ? $notif['mail_to_specific'] : '';
						// add new fields From Addr, From Name
						$tmp['from_addr'] = isset( $notif['from_addr'] ) ? $notif['from_addr'] : '';
						$tmp['from_name'] = isset( $notif['from_name'] ) ? $notif['from_name'] : '';
						$tmp['subject'] = isset( $notif['subject'] ) ? $notif['subject'] : '';
						$tmp['body'] = isset( $notif['body'] ) ? $notif['body'] : '';
						$fields['notification']->notifications[] = $tmp;
					}
				}
				$fields['notification']->enable = ( isset( $form_data['meta']['notification']['enable'] ) && $form_data['meta']['notification']['enable'] == '1' ) ? 1 : 0;
			} // new cred fields format here
			else {
				$fields['form_settings'] = (object) array(
					'form' => array(),
					'post' => array(),
				);
				$fields['form_settings']->form['type'] = isset( $form_data['meta']['form_settings']['form']['type'] ) ? $form_data['meta']['form_settings']['form']['type'] : '';
				$fields['form_settings']->form['action'] = isset( $form_data['meta']['form_settings']['form']['action'] ) ? $form_data['meta']['form_settings']['form']['action'] : '';
				$fields['form_settings']->form['action_page'] = isset( $form_data['meta']['form_settings']['form']['action_page'] ) ? $form_data['meta']['form_settings']['form']['action_page'] : '';
				$fields['form_settings']->form['redirect_delay'] = isset( $form_data['meta']['form_settings']['form']['redirect_delay'] ) ? intval( $form_data['meta']['form_settings']['form']['redirect_delay'] ) : 0;
				$fields['form_settings']->form['action_message'] = isset( $form_data['meta']['form_settings']['form']['action_message'] ) ? $form_data['meta']['form_settings']['form']['action_message'] : '';
				$fields['form_settings']->form['hide_comments'] = ( isset( $form_data['meta']['form_settings']['form']['hide_comments'] ) && $form_data['meta']['form_settings']['form']['hide_comments'] == '1' ) ? 1 : 0;

				$fields['form_settings']->form['autogenerate_username_scaffold'] = ( isset( $form_data['meta']['form_settings']['form']['autogenerate_username_scaffold'] ) && $form_data['meta']['form_settings']['form']['autogenerate_username_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->form['autogenerate_nickname_scaffold'] = ( isset( $form_data['meta']['form_settings']['form']['autogenerate_nickname_scaffold'] ) && $form_data['meta']['form_settings']['form']['autogenerate_nickname_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->form['autogenerate_password_scaffold'] = ( isset( $form_data['meta']['form_settings']['form']['autogenerate_password_scaffold'] ) && $form_data['meta']['form_settings']['form']['autogenerate_password_scaffold'] == '1' ) ? 1 : 0;

				$fields['form_settings']->form['include_captcha_scaffold'] = ( isset( $form_data['meta']['form_settings']['form']['include_captcha_scaffold'] ) && $form_data['meta']['form_settings']['form']['include_captcha_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->form['include_wpml_scaffold'] = ( isset( $form_data['meta']['form_settings']['form']['include_wpml_scaffold'] ) && $form_data['meta']['form_settings']['form']['include_wpml_scaffold'] == '1' ) ? 1 : 0;
				$fields['form_settings']->form['has_media_button'] = ( isset( $form_data['meta']['form_settings']['form']['has_media_button'] ) && $form_data['meta']['form_settings']['form']['has_media_button'] == '0' ) ? 0 : 1;
				$fields['form_settings']->form['has_toolset_buttons'] = ( isset( $form_data['meta']['form_settings']['form']['has_toolset_buttons'] ) && $form_data['meta']['form_settings']['form']['has_toolset_buttons'] == '0' ) ? 0 : 1;
				$fields['form_settings']->form['has_media_manager'] = ( isset( $form_data['meta']['form_settings']['form']['has_media_manager'] ) && $form_data['meta']['form_settings']['form']['has_media_manager'] == '0' ) ? 0 : 1;
				$fields['form_settings']->form['user_role'] = isset( $form_data['meta']['form_settings']['form']['user_role'] ) ? $form_data['meta']['form_settings']['form']['user_role'] : 'subscriber';
				$fields['form_settings']->post['post_type'] = isset( $form_data['meta']['form_settings']['post']['post_type'] ) ? $form_data['meta']['form_settings']['post']['post_type'] : '';
				$fields['form_settings']->post['post_status'] = isset( $form_data['meta']['form_settings']['post']['post_status'] ) ? $form_data['meta']['form_settings']['post']['post_status'] : 'draft';
				$fields['form_settings']->form['theme'] = isset( $form_data['meta']['form_settings']['form']['theme'] ) ? $form_data['meta']['form_settings']['form']['theme'] : 'minimal';
				$fields['form_settings']->form['use_ajax'] = ( isset( $form_data['meta']['form_settings']['form']['use_ajax'] ) && $form_data['meta']['form_settings']['form']['use_ajax'] == '1' ) ? 1 : 0;

				$fields['wizard'] = isset( $form_data['meta']['wizard'] ) ? intval( $form_data['meta']['wizard'] ) : -1;

				$fields['extra'] = (object) array(
					'css' => '',
					'js' => '',
					'messages' => $fmodel->getDefaultMessages(),
				);
				$fields['extra']->css = isset( $form_data['meta']['extra']['css'] ) ? $form_data['meta']['extra']['css'] : '';
				$fields['extra']->js = isset( $form_data['meta']['extra']['js'] ) ? $form_data['meta']['extra']['js'] : '';

				//EMERSON: Fix bug on Form text messages value not imported in Toolset Forms 1.2.2
				//This will cause the hash to be different after import,e.g. in Module manager 1.1
				//Commented are old codes
				/* START */

				//if (isset($form_data['meta']['extra']['messages']['messages_item']))
				if ( isset( $form_data['meta']['extra']['messages'] ) ) {
					// make it array
					/*
                      if (!isset($form_data['meta']['extra']['messages']['messages_item'][0]))
                      $form_data['meta']['extra']['messages']['messages_item']=array($form_data['meta']['extra']['messages']['messages_item']);
                     */
					if ( ! isset( $form_data['meta']['extra']['messages'] ) ) {
						$form_data['meta']['extra']['messages'] = array( $form_data['meta']['extra']['messages'] );
					}

					//foreach ($form_data['meta']['extra']['messages']['messages_item'] as $msg)
					foreach ( $form_data['meta']['extra']['messages'] as $msg ) {
						/*
                          foreach (array_keys($fields['extra']->messages) as $msgid)
                          {
                          if (isset($msg[$msgid]))
                          $fields['extra']->messages[$msgid]=$msg;
                          }
                         */
						foreach ( ( $fields['extra']->messages ) as $msgid_key => $msgid_value ) {

							if ( isset( $form_data['meta']['extra']['messages'][ $msgid_key ] ) && $form_data['meta']['extra']['messages'][ $msgid_key ] != $msgid_value ) {

								$fields['extra']->messages[ $msgid_key ] = $form_data['meta']['extra']['messages'][ $msgid_key ];
							}
						}
					}
				}
				/* END */

				if ( isset( $form_data['meta']['extra']['scaffold'] ) ) {
					$fields['extra']->scaffold = $form_data['meta']['extra']['scaffold'];
				}

				$fields['notification'] = (object) array(
					'enable' => 0,
					'notifications' => array(),
				);
				if ( isset( $form_data['meta']['notification']['notifications']['notifications_item'] ) ) {
					// make it array
					if ( ! isset( $form_data['meta']['notification']['notifications']['notifications_item'][0] ) ) {
						$form_data['meta']['notification']['notifications']['notifications_item'] = array( $form_data['meta']['notification']['notifications']['notifications_item'] );
					}

					foreach ( $form_data['meta']['notification']['notifications']['notifications_item'] as $notif ) {
						$tmp = array();
						$tmp['event'] = isset( $notif['event'] ) ? $notif['event'] : array();
						if ( isset( $tmp['event']['condition']['condition_item'] ) ) {
							if ( ! isset( $tmp['event']['condition']['condition_item'][0] ) ) {
								$tmp['event']['condition']['condition_item'] = array( $tmp['event']['condition']['condition_item'] );
							}
							$tmp['event']['condition'] = $tmp['event']['condition']['condition_item'];
						}
						$tmp['to'] = isset( $notif['to'] ) ? $notif['to'] : array();
						if ( isset( $tmp['to']['type']['type_item'] ) ) {
							if ( ! is_array( $tmp['to']['type']['type_item'] ) ) {
								$tmp['to']['type']['type_item'] = array( $tmp['to']['type']['type_item'] );
							}
							$tmp['to']['type'] = $tmp['to']['type']['type_item'];
						}
						// add new fields From Addr, From Name
						$tmp['from'] = isset( $notif['from'] ) ? $notif['from'] : array();
						$tmp['mail'] = isset( $notif['mail'] ) ? $notif['mail'] : array();
						$tmp['name'] = isset( $notif['name'] ) ? $notif['name'] : '(notification-name)';
						$tmp[ 'disabled' ] = ( isset( $notif[ 'disabled' ] ) && $notif[ 'disabled' ] == 1 ) ? $notif[ 'disabled' ] : 0;
						$fields['notification']->notifications[] = $tmp;
					}
				}
				$fields['notification']->enable = ( isset( $form_data['meta']['notification']['enable'] ) && $form_data['meta']['notification']['enable'] == '1' ) ? 1 : 0;

				// Toolset Forms post expiration import (new version 1.2.6)
				/* START */

				$fields['post_expiration'] = array(
					'action' => array(),
					'enable' => 0,
					'expiration_time' => array(),
				);

				if ( isset( $form_data['meta']['post_expiration'] ) ) {

					if ( isset( $form_data['meta']['post_expiration']['action'] ) ) {
						$fields['post_expiration']['action'] = $form_data['meta']['post_expiration']['action'];
					}
					if ( isset( $form_data['meta']['post_expiration']['enable'] ) ) {
						$fields['post_expiration']['enable'] = $form_data['meta']['post_expiration']['enable'];
					}
					if ( isset( $form_data['meta']['post_expiration']['expiration_time'] ) ) {
						$fields['post_expiration']['expiration_time'] = $form_data['meta']['post_expiration']['expiration_time'];
					}
				}
				/* END */
			}

			// change format here and provide defaults also
			$fields = array_merge( array(
				'form_settings' => (object) array(
					'form' => array(),
					'post' => array(),
				),
				'notification' => (object) array(
					'enable' => 0,
					'notifications' => array(),
				),
				'extra' => (object) array(
					'css' => '',
					'js' => '',
					'messages' => $fmodel->getDefaultMessages(),
					'scaffold' => '',
				),
			), $fmodel->changeFormat( $fields )
			);
		}

		// de-normalize fields
		if ( ! empty( $fields['form_settings']->form['action_page'] ) && ! is_numeric( $fields['form_settings']->form['action_page'] ) ) {
			$_page_ = get_page_by_path( $fields['form_settings']->form['action_page'], OBJECT, 'page' );
			if ( $_page_ && isset( $_page_->ID ) ) {
				$fields['form_settings']->form['action_page'] = $_page_->ID;
			} else {
				$fields['form_settings']->form['action_page'] = '';
			}
		}
		$_form_id = false;
		if (
			( isset( $options['overwrite_forms'] ) && $options['overwrite_forms'] )
			|| isset( $options['toolset-themes'] ) // Activate overwrite for toolset-themes
		) {
			$old_form = false;
			if ( isset( $options['force_skip_post_name'] ) ||
				isset( $options['force_overwrite_post_name'] ) ||
				isset( $options['force_duplicate_post_name'] )
			) {
				$old_form = get_page_by_path( $form->post_name, OBJECT, CRED_USER_FORMS_CUSTOM_POST_NAME );
			} else {
				$forms = get_posts(
					array(
						'post_type'              => CRED_USER_FORMS_CUSTOM_POST_NAME,
						'title'                  => $form->post_title,
						'post_status'            => 'all',
						'numberposts'            => 1,
						'update_post_term_cache' => false,
						'update_post_meta_cache' => false,
						'orderby'                => 'post_date ID',
						'order'                  => 'ASC',
					)
				);
				if ( ! empty( $forms ) ) {
					$old_form = $forms[0];
				}
			}
			if ( $old_form ) {
				$form->ID = $old_form->ID;
				$_form_id = $form->ID;
				if ( $fmodel->updateForm( $form, $fields ) ) {
					$results['updated']++;
				} else {
					$results['failed']++;
					$results['errors'][] = sprintf( __( 'Item %s could not be saved', 'wp-cred' ), $form->post_title );
				}
			} else {
				$_form_id = $fmodel->saveForm( $form, $fields, true );
				if ( $_form_id ) {
					$results['new']++;
				} else {
					$results['failed']++;
					$results['errors'][] = sprintf( __( 'Item %s could not be saved', 'wp-cred' ), $form->post_title );
				}
			}
		} else {
			$_form_id = $fmodel->saveForm( $form, $fields, true );
			$results['new']++;
		}

		if ( $_form_id ) // allow 3rd-party to import extra data per form and update results variable accordingly
		{
			$results = apply_filters( 'cred_import_form', $results, $_form_id, $form_data );
		}

		return $_form_id;
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	private static function updateSettings( $data ) {
		$settings_model = CRED_Loader::get( 'MODEL/Settings' );
		$new_settings = array();

		$allowed_fields = array(
			'dont_load_bootstrap_cred_css',
			'dont_load_cred_css',
			'enable_post_expiration',
			'export_custom_fields',
			'export_settings',
			'recaptcha',
			'use_bootstrap',
			'wizard',
			'allowed_tags',
		);
		foreach ( $allowed_fields as $key ) {
			$new_settings[ $key ] = null;
			if ( array_key_exists( $key, $data['settings'] ) && isset( $data['settings'][ $key ] ) ) {
				//dont_load_cred_css: Because of reversed checked/unchecked behavior and back compatibility behavior when not isset force casting on import
				if ( in_array( $key, array( 'dont_load_cred_css', 'dont_load_bootstrap_cred_css' ) ) ) {
					$new_settings[ $key ] = (int) $data['settings'][ $key ];
				} else {
					$new_settings[ $key ] = $data['settings'][ $key ];
				}
			}
		}
		$settings_model->updateSettings( $new_settings );

		return $new_settings;
	}

	/**
	 * @param $data
	 * @param $options
	 *
	 * @return array
	 */
	private static function importForms( $data, $options ) {
		$results = array(
			'settings' => 0,
			'custom_fields' => 0,
			'updated' => 0,
			'new' => 0,
			'failed' => 0,
			'errors' => array(),
		);

		$new_items = array();

		if ( isset( $data['settings'] ) && isset( $options['overwrite_settings'] ) && $options['overwrite_settings'] ) {
			$new_settings = self::updateSettings( $data );

			// Import Toolset Forms Post Expiration to options table
			if ( $new_settings['enable_post_expiration'] && isset( $data['post_expiration_settings'] ) && ! ( empty( $data['post_expiration_settings'] ) ) ) {
				$oldsettings_expiration = self::get_normalized_post_expiration_settings();
				$newsettings_expiration = $data['post_expiration_settings'];
				$newsettings_expiration['post_expiration_post_types'] = array();
				if ( isset( $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] ) ) {
					// make it array
					if ( ! is_array( $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] ) ) {
						$data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] = array( $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] );
					}
					$newsettings_expiration['post_expiration_post_types'] = $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'];
				}
				$newsettings_expiration = self::array_merge_distinct( $oldsettings_expiration, $newsettings_expiration );
				self::set_post_exporation_settings( $newsettings_expiration );
			} else {
				do_action( 'toolset_forms_remove_post_expiration_settings' );
			}

			$results['settings'] = 1;
		}

		if ( isset( $data['settings'] ) ) {
			unset( $data['settings'] );
		}

		if ( isset( $data['post_expiration_settings'] ) ) {
			unset( $data['post_expiration_settings'] );
		}

		if ( isset( $data['custom_fields'] ) && isset( $options['overwrite_custom_fields'] ) && $options['overwrite_custom_fields'] ) {
			$fields_control_db_manager = new Db();
			foreach ( $data['custom_fields'] as $post_type => $field ) {
				foreach ( $field as $field_slug => $field_data ) {
					$fields_control_db_manager->set_field( $field_data, $post_type );
					$results['custom_fields']++;
				}
			}
		}

		if ( isset( $data['custom_fields'] ) ) {
			unset( $data['custom_fields'] );
		}

		$form_model = CRED_Loader::get( 'MODEL/Forms' );

		if ( isset( $data['form'] ) && ! empty( $data['form'] ) && is_array( $data['form'] ) ) {
			if ( ! isset( $options['items'] ) ) {
				$items = false;
			} else {
				$items = $options['items'];
			}

			if (
				is_array( $data['form'] )
				&& array_key_exists( 'toolset-themes', $options )
				&& ! array_key_exists( 'ID', $data['form'] )
			) // make sure not doing it for a single item import
			{
				// re-index array for the next condition
				// this is needed for data coming from user-choice update
				// (just for toolset themes, don't want to change more than needed on this function)
				$data['form'] = array_values( $data['form'] );
			}

			if ( ! isset( $data['form'][0] ) ) {
				$data['form'] = array( $data['form'] );
			} // make it array

			// create tmp upload dir, to handle imported media attached to forms
			$upload_dir = wp_upload_dir();
			$upload_path = $upload_dir['basedir'];
			$upload_directory = $upload_dir['baseurl'];
			$temporary_directory = $upload_path . DIRECTORY_SEPARATOR . '__cred__tmp__';
			$temporary_upload_path = $upload_directory . '/__cred__tmp__';

			if ( ! is_dir( $temporary_directory ) ) {
				mkdir( $temporary_directory );
			}

			if ( is_dir( $temporary_directory ) ) {
				// include only if necessary
				include_once( ABSPATH . 'wp-admin/includes/file.php' );
				include_once( ABSPATH . 'wp-admin/includes/media.php' );
				include_once( ABSPATH . 'wp-admin/includes/image.php' );
			}

			foreach ( $data['form'] as $key => $form_data ) {
				if ( ! isset( $form_data['post_title'] ) ) {
					continue;
				}
				// import only selected items
				if ( false !== $items && ! in_array( $form_data['ID'], $items ) ) {
					continue;
				}

				$_form_id = self::importSingleForm( $form_data, $form_model, $options, $results );

				if ( $_form_id ) {
					//Remove is_edited flag
					delete_post_meta( $_form_id, '_toolset_edit_last' );
					//Update post slug
					$form_model->updateFormData( array(
						'ID' => $_form_id,
						'post_name'    => $form_data['post_name']
					) );

					// add attached media (only images)
					if ( isset( $form_data['media']['media_item'] ) && is_array( $form_data['media']['media_item'] ) && ! empty( $form_data['media']['media_item'] ) && is_dir( $temporary_directory ) ) {
						$_att_results = self::importAttachedMedia( $_form_id, $form_data['media']['media_item'], $temporary_directory, $temporary_upload_path );

						if ( ! empty( $_att_results['errors'] ) ) {
							$results['errors'] = array_merge( $results['errors'], $_att_results['errors'] );
							$results['failed']++;
						}
						if ( ! empty( $_att_results['data'] ) ) {
							// denormalize image hash placeholders
							$form_data = self::denormalizeData( $form_data, $_att_results['data'] );
							$form_model->updateFormData( array(
								'ID' => $_form_id,
								'post_content' => $form_data['post_content'],
								'post_name'    => $form_data['post_name']
							) );
						}
					}

					// for module manager
					if ( isset( $options['return_ids'] ) && $options['return_ids'] ) {
						$new_items[ $form_data['ID'] ] = $_form_id;
					}
				}
			}

			if ( is_dir( $temporary_directory ) ) {
				// remove custom tmp dir
				@rmdir( $temporary_directory );
			}
		}
		// for module manager
		if ( isset( $options['return_ids'] ) && $options['return_ids'] ) {
			$results['items'] = $new_items;
		}

		return $results;
	}

	/**
	 * @param $data
	 * @param $options
	 *
	 * @return array
	 */
	private static function importUserForms( $data, $options ) {
		$results = array(
			'settings' => 0,
			'custom_fields' => 0,
			'updated' => 0,
			'new' => 0,
			'failed' => 0,
			'errors' => array(),
		);

		$new_items = array();

		if ( isset( $data['settings'] ) && isset( $options['overwrite_settings'] ) && $options['overwrite_settings'] ) {
			$new_settings = self::updateSettings( $data );

			//Import Toolset Forms Post Expiration to options table
			if ( $new_settings['enable_post_expiration'] && isset( $data['post_expiration_settings'] ) && ! ( empty( $data['post_expiration_settings'] ) ) ) {
				$oldsettings_expiration = self::get_normalized_post_expiration_settings();
				$newsettings_expiration = $data['post_expiration_settings'];
				$newsettings_expiration['post_expiration_post_types'] = array();
				if ( isset( $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] ) ) {
					// make it array
					if ( ! is_array( $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] ) ) {
						$data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] = array( $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'] );
					}
					$newsettings_expiration['post_expiration_post_types'] = $data['post_expiration_settings']['post_expiration_post_types']['post_expiration_post_types_item'];
				}
				$newsettings_expiration = self::array_merge_distinct( $oldsettings_expiration, $newsettings_expiration );
				self::set_post_exporation_settings( $newsettings_expiration );
			} else {
				do_action( 'toolset_forms_remove_post_expiration_settings' );
			}

			$results['settings'] = 1;
		}

		if ( isset( $data['settings'] ) ) {
			unset( $data['settings'] );
		}

		if ( isset( $data['post_expiration_settings'] ) ) {
			unset( $data['post_expiration_settings'] );
		}

		if ( isset( $data['custom_fields'] ) && isset( $options['overwrite_custom_fields'] ) && $options['overwrite_custom_fields'] ) {
			$fields_control_db_manager = new Db();
			foreach ( $data['custom_fields'] as $post_type => $field ) {
				foreach ( $field as $field_slug => $field_data ) {
					$fields_control_db_manager->set_field( $field_data, $post_type );
					$results['custom_fields']++;
				}
			}
		}

		if ( isset( $data['custom_fields'] ) ) {
			unset( $data['custom_fields'] );
		}

		$user_form_model = CRED_Loader::get( 'MODEL/UserForms' );

		if ( isset( $data['form'] ) && ! empty( $data['form'] ) && is_array( $data['form'] ) ) {
			if ( ! isset( $options['items'] ) ) {
				$items = false;
			} else {
				$items = $options['items'];
			}

			if (
				is_array( $data['form'] )
				&& array_key_exists( 'toolset-themes', $options )
				&& ! array_key_exists( 'ID', $data['form'] )
			) // make sure not doing it for a single item import
			{
				// re-index array for the next condition
				// this is needed for data coming from user-choice update
				// (just for toolset themes, don't want to change more than needed on this function)
				$data['form'] = array_values( $data['form'] );
			}

			if ( ! isset( $data['form'][0] ) ) {
				$data['form'] = array( $data['form'] );
			} // make it array


			// create tmp upload dir, to handle imported media attached to forms
			$upload_dir = wp_upload_dir();
			$upload_path = $upload_dir['basedir'];
			$upload_directory = $upload_dir['baseurl'];
			$temporary_directory = $upload_path . DIRECTORY_SEPARATOR . '__cred__tmp__';
			$temporary_upload_path = $upload_directory . '/__cred__tmp__';

			if ( ! is_dir( $temporary_directory ) ) {
				mkdir( $temporary_directory );
			}

			if ( is_dir( $temporary_directory ) ) {
				// include only if necessary
				include_once( ABSPATH . 'wp-admin/includes/file.php' );
				include_once( ABSPATH . 'wp-admin/includes/media.php' );
				include_once( ABSPATH . 'wp-admin/includes/image.php' );
			}

			foreach ( $data['form'] as $key => $form_data ) {
				if ( ! isset( $form_data['post_title'] ) ) {
					continue;
				}
				// import only selected items
				if ( false !== $items && ! in_array( $form_data['ID'], $items ) ) {
					continue;
				}
				$_form_id = self::importSingleUserForm( $form_data, $user_form_model, $options, $results );

				if ( $_form_id ) {
					//Remove is_edited flag
					delete_post_meta( $_form_id, '_toolset_edit_last' );

					//Update post slug
					$user_form_model->updateFormData( array(
						'ID' => $_form_id,
						'post_name'    => $form_data['post_name']
					) );

					// add attached media (only images)
					if ( isset( $form_data['media']['media_item'] ) && is_array( $form_data['media']['media_item'] ) && ! empty( $form_data['media']['media_item'] ) && is_dir( $temporary_directory ) ) {
						$_att_results = self::importAttachedMedia( $_form_id, $form_data['media']['media_item'], $temporary_directory, $temporary_upload_path );

						if ( ! empty( $_att_results['errors'] ) ) {
							$results['errors'] = array_merge( $results['errors'], $_att_results['errors'] );
							$results['failed']++;
						}
						if ( ! empty( $_att_results['data'] ) ) {
							// denormalize image hash placeholders
							$form_data = self::denormalizeData( $form_data, $_att_results['data'] );
							$user_form_model->updateFormData( array(
								'ID' => $_form_id,
								'post_content' => $form_data['post_content'],
							) );
						}
					}

					// for module manager
					if ( isset( $options['return_ids'] ) && $options['return_ids'] ) {
						$new_items[ $form_data['ID'] ] = $_form_id;
					}
				}
			}

			if ( is_dir( $temporary_directory ) ) {
				// remove custom tmp dir
				@rmdir( $temporary_directory );
			}
		}
		// for module manager
		if ( isset( $options['return_ids'] ) && $options['return_ids'] ) {
			$results['items'] = $new_items;
		}

		return $results;
	}

	/**
	 * @param $id
	 * @param $media
	 * @param $_tmp
	 * @param $_tmpuri
	 *
	 * @return array
	 */
	private static function importAttachedMedia( $id, $media, $_tmp, $_tmpuri ) {
		$errors = array();
		$id = intval( $id );
		$data = array();

		//###################################################################################################
		//Fix: https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/186012110/comments
		if ( count( $media ) > 0 && ! isset( $media[0] ) ) {
			$attach = $media;
			$hasError = false;
			if (
				isset( $attach['image_data'] ) &&
				isset( $attach['base_name'] ) &&
				isset( $attach['post_mime_type'] ) &&
				in_array( $attach['post_mime_type'], array( 'image/png', 'image/gif', 'image/jpg', 'image/jpeg' ) )
			) {
				//  decode attachment data and create the file
				$imgdata = base64_decode( $attach['image_data'] );
				file_put_contents( $_tmp . DIRECTORY_SEPARATOR . $attach['base_name'], $imgdata );
				// upload the file using WordPress API and add it to the post as attachment
				// preserving all fields but alt
				$tmpfile = download_url( $_tmpuri . '/' . $attach['base_name'] );

				if ( is_wp_error( $tmpfile ) ) {
					try {
						@unlink( $tmpfile );
						@unlink( $_tmp . DIRECTORY_SEPARATOR . $attach['base_name'] );
					} catch ( Exception $e ) {

					}
					$errors[] = $tmpfile->get_error_message( $tmpfile->get_error_code() );
					$hasError = true;
					array( 'errors' => $errors, 'data' => $data );
				}
				$file_array['name'] = $attach['base_name'];
				$file_array['tmp_name'] = $tmpfile;
				$att_data = array();
				if ( isset( $attach['post_title'] ) ) {
					$att_data['post_title'] = $attach['post_title'];
				}
				if ( isset( $attach['post_content'] ) ) {
					$att_data['post_content'] = $attach['post_content'];
				}
				if ( isset( $attach['post_excerpt'] ) ) {
					$att_data['post_excerpt'] = $attach['post_excerpt'];
				}
				if ( isset( $attach['post_status'] ) ) {
					$att_data['post_status'] = $attach['post_status'];
				}
				$att_id = media_handle_sideload( $file_array, $id, null, $att_data );
				if ( is_wp_error( $att_id ) ) {
					try {
						@unlink( $file_array['tmp_name'] );
						@unlink( $_tmp . DIRECTORY_SEPARATOR . $attach['base_name'] );
					} catch ( Exception $e ) {

					}
					$errors[] = $att_id->get_error_message( $att_id->get_error_code() );
					$hasError = true;
					array( 'errors' => $errors, 'data' => $data );
				}
				// update alt field
				if ( isset( $attach['alt'] ) ) {
					update_post_meta( $att_id, '_wp_attachment_image_alt', $attach['alt'] );
				}

				// remove custom tmp file
				@unlink( $_tmp . DIRECTORY_SEPARATOR . $attach['base_name'] );

				// return data for replacements if needed
				if ( isset( $attach['image_hash'] ) ) {
					$data[ $attach['image_hash'] ] = array(
						'guid' => $attach['guid'],
						'image_hash' => $attach['image_hash'],
						'id' => $att_id,
					);
				}
			}
		} else {
			//###################################################################################################
			foreach ( $media as $ii => $attach ) {
				$hasError = false;
				if (
					isset( $attach['image_data'] ) &&
					isset( $attach['base_name'] ) &&
					isset( $attach['post_mime_type'] ) &&
					in_array( $attach['post_mime_type'], array( 'image/png', 'image/gif', 'image/jpg', 'image/jpeg' ) )
				) {
					//  decode attachment data and create the file
					$imgdata = base64_decode( $attach['image_data'] );
					file_put_contents( $_tmp . DIRECTORY_SEPARATOR . $attach['base_name'], $imgdata );
					// upload the file using WordPress API and add it to the post as attachment
					// preserving all fields but alt
					$tmpfile = download_url( $_tmpuri . '/' . $attach['base_name'] );

					if ( is_wp_error( $tmpfile ) ) {
						try {
							@unlink( $tmpfile );
							@unlink( $_tmp . DIRECTORY_SEPARATOR . $attach['base_name'] );
						} catch ( Exception $e ) {

						}
						$errors[] = $tmpfile->get_error_message( $tmpfile->get_error_code() );
						$hasError = true;
						continue;
					}
					$file_array['name'] = $attach['base_name'];
					$file_array['tmp_name'] = $tmpfile;
					$att_data = array();
					if ( isset( $attach['post_title'] ) ) {
						$att_data['post_title'] = $attach['post_title'];
					}
					if ( isset( $attach['post_content'] ) ) {
						$att_data['post_content'] = $attach['post_content'];
					}
					if ( isset( $attach['post_excerpt'] ) ) {
						$att_data['post_excerpt'] = $attach['post_excerpt'];
					}
					if ( isset( $attach['post_status'] ) ) {
						$att_data['post_status'] = $attach['post_status'];
					}
					$att_id = media_handle_sideload( $file_array, $id, null, $att_data );
					if ( is_wp_error( $att_id ) ) {
						try {
							@unlink( $file_array['tmp_name'] );
							@unlink( $_tmp . DIRECTORY_SEPARATOR . $attach['base_name'] );
						} catch ( Exception $e ) {

						}
						$errors[] = $att_id->get_error_message( $att_id->get_error_code() );
						$hasError = true;
						continue;
					}
					// update alt field
					if ( isset( $attach['alt'] ) ) {
						update_post_meta( $att_id, '_wp_attachment_image_alt', $attach['alt'] );
					}

					// remove custom tmp file
					@unlink( $_tmp . DIRECTORY_SEPARATOR . $attach['base_name'] );

					// return data for replacements if needed
					if ( isset( $attach['image_hash'] ) ) {
						$data[ $attach['image_hash'] ] = array(
							'guid' => $attach['guid'],
							'image_hash' => $attach['image_hash'],
							'id' => $att_id,
						);
					}
				}
			}
		}

		return array( 'errors' => $errors, 'data' => $data );
	}

	/**
	 * @param $id
	 *
	 * @return bool|string
	 */
	public static function computeHashForForm( $id ) {
		$forms = CRED_Loader::get( 'MODEL/Forms' )->getFormsForExport( array( $id ) );
		if ( $forms && isset( $forms[0] ) ) {
			// get first element
			$form = $forms[0];

			return self::doHash( (array) $form );
		}

		return false;
	}

	/**
	 * @param $id
	 *
	 * @return bool|string
	 */
	public static function computeHashForUserForm( $id ) {
		$forms = CRED_Loader::get( 'MODEL/UserForms' )->getFormsForExport( array( $id ) );
		if ( $forms && isset( $forms[0] ) ) {
			// get first element
			$form = $forms[0];

			return self::doHash( (array) $form );
		}

		return false;
	}

	/**
	 * @param $forms
	 * @param bool $ajax
	 */
	public static function exportUsersToXML( $forms, $ajax = false ) {
		$mode = 'forms';
		$data = self::getSelectedUserFormsForExport( $forms, array( 'media' => true ), $mode );
		$setts = CRED_Loader::get( 'MODEL/Settings' )->getSettings();

		// Export Toolset Forms post expiration settings
		$cred_post_expiration_setts = self::get_normalized_post_expiration_settings();

		if ( isset( $setts['export_settings'] ) && $setts['export_settings'] ) {
			$data[ self::$root ]['settings'] = $setts;
		}

		if ( isset( $cred_post_expiration_setts['post_expiration_cron'] ) && $cred_post_expiration_setts['post_expiration_cron'] ) {
			$data[ self::$root ]['post_expiration_settings'] = $cred_post_expiration_setts;
		}

		if ( isset( $setts['export_custom_fields'] ) && $setts['export_custom_fields'] ) {
			$custom_fields = CRED_Loader::get( 'MODEL/UserFields' )->getCustomFields();
			$data[ self::$root ]['custom_fields'] = $custom_fields;
		}
		$xml = self::toXml( $data, self::$root );
		self::output( $xml, $ajax, $mode );
	}

	/**
	 * @param $forms
	 * @param bool $ajax
	 */
	public static function exportToXML( $forms, $ajax = false ) {
		$mode = 'forms';
		$data = self::getSelectedFormsForExport( $forms, array( 'media' => true ), $mode );
		$setts = CRED_Loader::get( 'MODEL/Settings' )->getSettings();

		// Export Toolset Forms post expiration settings
		$cred_post_expiration_setts = self::get_normalized_post_expiration_settings();

		if ( isset( $setts['export_settings'] ) && $setts['export_settings'] ) {
			$data[ self::$root ]['settings'] = $setts;
		}

		if ( isset( $cred_post_expiration_setts['post_expiration_cron'] ) && $cred_post_expiration_setts['post_expiration_cron'] ) {
			$data[ self::$root ]['post_expiration_settings'] = $cred_post_expiration_setts;
		}

		if ( isset( $setts['export_custom_fields'] ) && $setts['export_custom_fields'] ) {
			$custom_fields = CRED_Loader::get( 'MODEL/Fields' )->getCustomFields();
			$data[ self::$root ]['custom_fields'] = $custom_fields;
		}
		$xml = self::toXml( $data, self::$root );
		self::output( $xml, $ajax, $mode );
	}

	/**
	 * @param $forms
	 * @param array $options
	 * @param bool $extra
	 *
	 * @return string
	 */
	public static function exportToXMLString( $forms, $options = array(), &$extra = false ) {
		$mode = 'forms';
		// add hashes as extra
		$data = self::getSelectedFormsForExport( $forms, $options, $mode, $extra );
		$setts = CRED_Loader::get( 'MODEL/Settings' )->getSettings();

		// Export Toolset Forms post expiration settings
		$cred_post_expiration_setts = self::get_normalized_post_expiration_settings();

		if ( isset( $setts['export_settings'] ) && $setts['export_settings'] ) {
			$data[ self::$root ]['settings'] = $setts;
		}

		if ( isset( $cred_post_expiration_setts['post_expiration_cron'] ) && $cred_post_expiration_setts['post_expiration_cron'] ) {
			$data[ self::$root ]['post_expiration_settings'] = $cred_post_expiration_setts;
		}

		if ( isset( $setts['export_custom_fields'] ) && $setts['export_custom_fields'] ) {
			$custom_fields = CRED_Loader::get( 'MODEL/Fields' )->getCustomFields();
			$data[ self::$root ]['custom_fields'] = $custom_fields;
		}
		$xml = self::toXml( $data, self::$root );

		return $xml;
	}

	/**
	 * @param $forms
	 * @param array $options
	 * @param bool $extra
	 *
	 * @return string
	 */
	public static function exportUsersToXMLString( $forms, $options = array(), &$extra = false ) {
		$mode = 'forms';
		// add hashes as extra
		$data = self::getSelectedUserFormsForExport( $forms, $options, $mode, $extra );
		$setts = CRED_Loader::get( 'MODEL/Settings' )->getSettings();

		if ( isset( $setts['export_custom_fields'] ) && $setts['export_custom_fields'] ) {
			$custom_fields = CRED_Loader::get( 'MODEL/UserFields' )->getFields();
			$data[ self::$root ]['custom_fields'] = $custom_fields;
		}
		$xml = self::toXml( $data, self::$root );

		return $xml;
	}

	/**
	 * @param $file
	 * @param array $options
	 *
	 * @return array|string|WP_Error
	 */
	public static function importFromXML( $file, $options = array() ) {
		$dataresult = self::readXML( $file );
		if ( $dataresult !== false && ! is_wp_error( $dataresult ) ) {
			if ( isset( $dataresult['form'] ) ) {
				if ( isset( $dataresult['form']['post_type'] ) ) {
					if ( $dataresult['form']['post_type'] != CRED_FORMS_CUSTOM_POST_NAME ) {
						return new WP_Error( 'not_xml_file', __( 'The XML file does not contain valid Post Forms.', 'wp-cred' ) );
					}
				} else {
					foreach ( $dataresult['form'] as $n => $f ) {
						if ( $f['post_type'] != CRED_FORMS_CUSTOM_POST_NAME ) {
							return new WP_Error( 'not_xml_file', __( 'The XML file does not contain valid Post Forms.', 'wp-cred' ) );
						}
					}
				}
			}
			$results = self::importForms( $dataresult, $options );

			return $results;
		} else {
			return $dataresult;
		}
	}

	/**
	 * @param $file
	 * @param array $options
	 *
	 * @return array|string|WP_Error
	 */
	public static function importUserFromXML( $file, $options = array() ) {
		$dataresult = self::readXML( $file );
		if ( $dataresult !== false && ! is_wp_error( $dataresult ) ) {

			if ( isset( $dataresult['form'] ) ) {
				if ( isset( $dataresult['form']['post_type'] ) ) {
					if ( $dataresult['form']['post_type'] != CRED_USER_FORMS_CUSTOM_POST_NAME ) {
						return new WP_Error( 'not_xml_file', __( 'The XML file does not contain valid User Forms.', 'wp-cred' ) );
					}
				} else {
					foreach ( $dataresult['form'] as $n => $f ) {
						if ( $f['post_type'] != CRED_USER_FORMS_CUSTOM_POST_NAME ) {
							return new WP_Error( 'not_xml_file', __( 'The XML file does not contain valid User Forms.', 'wp-cred' ) );
						}
					}
				}
			}

			$results = self::importUserForms( $dataresult, $options );

			return $results;
		} else {
			return $dataresult;
		}
	}

	/**
	 * @param $xmlstring
	 * @param array $options
	 *
	 * @return array|string|WP_Error
	 */
	public static function importFromXMLString( $xmlstring, $options = array() ) {
		if ( ! function_exists( 'simplexml_load_string' ) ) {
			return new WP_Error( 'xml_missing', __( 'The Simple XML library is missing.', 'wp-cred' ) );
		}
		$xml = simplexml_load_string( $xmlstring );

		$dataresult = self::toArray( $xml );

		if ( ! isset( $dataresult['form'][0] ) && ( isset( $options['force_skip_post_name'] ) || isset( $options['force_overwrite_post_name'] ) || isset( $options['force_duplicate_post_name'] ) ) ) {
			$dataresult['form'] = array( $dataresult['form'] );
		}

		//Installer/Importer skip, duplicate, owerwrite
		$new_list = array();
		if ( isset( $options['force_skip_post_name'] ) ) {
			foreach ( $dataresult['form'] as $key => $form_data ) {
				if ( in_array( $form_data['post_name'], $options['force_skip_post_name'] ) ) {
					unset( $dataresult['form'][ $key ] );
				} else {
					$new_list[ $key ] = $form_data;
				}
			}
		}

		//Skip all forms, import only selected
		if ( isset( $options['force_overwrite_post_name'] ) ) {
			foreach ( $dataresult['form'] as $key => $form_data ) {
				if ( in_array( $form_data['post_name'], $options['force_overwrite_post_name'] ) ) {
					$new_list[ $key ] = $form_data;
				}
			}
		}

		if ( isset( $options['force_duplicate_post_name'] ) ) {
			foreach ( $dataresult['form'] as $key => $form_data ) {
				if ( in_array( $form_data['post_name'], $options['force_duplicate_post_name'] ) ) {
					$form_data['post_title'] .= ' ' . date( 'l jS \of F Y h:i:s A' );
					$form_data['post_name'] = sanitize_title_with_dashes( $form_data['post_title'] );
					$new_list[ $key ] = $form_data;
				}
			}
		}
		//print_r($new_list);exit;
		if ( count( $new_list ) > 0 ) {

			$dataresult['form'] = $new_list;
		}

		if ( false !== $dataresult && ! is_wp_error( $dataresult ) ) {
			$results = self::importForms( $dataresult, $options );

			return $results;
		} else {
			return $dataresult;
		}
	}

	/**
	 * @param $xmlstring
	 * @param array $options
	 *
	 * @return array|string|WP_Error
	 */
	public static function importUsersFromXMLString( $xmlstring, $options = array() ) {
		if ( ! function_exists( 'simplexml_load_string' ) ) {
			return new WP_Error( 'xml_missing', __( 'The Simple XML library is missing.', 'wp-cred' ) );
		}
		$xml = simplexml_load_string( $xmlstring );

		$dataresult = self::toArray( $xml );

		if ( ! isset( $dataresult['form'][0] ) && ( isset( $options['force_skip_post_name'] ) || isset( $options['force_overwrite_post_name'] ) || isset( $options['force_duplicate_post_name'] ) ) ) {
			$dataresult['form'] = array( $dataresult['form'] );
		}

		//Installer/Importer skip, duplicate, owerwrite
		$new_list = array();
		if ( isset( $options['force_skip_post_name'] ) ) {
			foreach ( $dataresult['form'] as $key => $form_data ) {
				if ( in_array( $form_data['post_name'], $options['force_skip_post_name'] ) ) {
					unset( $dataresult['form'][ $key ] );
				} else {
					$new_list[ $key ] = $form_data;
				}
			}
		}

		//Skip all forms, import only selected
		if ( isset( $options['force_overwrite_post_name'] ) ) {
			foreach ( $dataresult['form'] as $key => $form_data ) {
				if ( in_array( $form_data['post_name'], $options['force_overwrite_post_name'] ) ) {
					$new_list[ $key ] = $form_data;
				}
			}
		}


		if ( isset( $options['force_duplicate_post_name'] ) ) {
			foreach ( $dataresult['form'] as $key => $form_data ) {
				if ( in_array( $form_data['post_name'], $options['force_duplicate_post_name'] ) ) {
					$form_data['post_title'] .= ' ' . date( 'l jS \of F Y h:i:s A' );
					$form_data['post_name'] = sanitize_title_with_dashes( $form_data['post_title'] );
					$new_list[ $key ] = $form_data;
				}
			}
		}
		//print_r($new_list);exit;
		if ( count( $new_list ) > 0 ) {

			$dataresult['form'] = $new_list;
		}

		if ( false !== $dataresult && ! is_wp_error( $dataresult ) ) {
			$results = self::importUserForms( $dataresult, $options );

			return $results;
		} else {
			return $dataresult;
		}
	}

}
