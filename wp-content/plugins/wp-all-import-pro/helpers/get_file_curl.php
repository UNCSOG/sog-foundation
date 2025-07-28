<?php

if ( ! function_exists( 'get_file_curl' ) ):

	function get_file_curl( $url, $fullpath, $to_variable = false, $iteration = false ) {
		if ( ! preg_match( '%^(http|ftp)s?://%i', $url ) || pmxi_is_private_ip( $url ) ) {
			return false;
		}
		
		$mimicBrowserUserAgent = apply_filters('pmxi_mimic_browser_user_agent', false);

		\Wpai\WordPress\AttachmentHandler::$user_agent = $mimicBrowserUserAgent ? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36' : \Wpai\WordPress\AttachmentHandler::$user_agent;

		\Wpai\WordPress\AttachmentHandler::$user_agent = apply_filters('pmxi_user_agent', \Wpai\WordPress\AttachmentHandler::$user_agent);

		$response = wp_remote_get( $url, array(
			'timeout' => PMXI_Plugin::getInstance()->getOption( 'pmxi_file_download_timeout' ),
			'headers' => array(
				'User-Agent' => \Wpai\WordPress\AttachmentHandler::$user_agent,
				'Accept' => apply_filters('pmxi_accept_header', 'application/xml;q=1.0, */*;q=0.8', $url)
			),
		) );

		if ( ! is_wp_error( $response ) and ( ! isset( $response['response']['code'] ) or isset( $response['response']['code'] ) and ! in_array( $response['response']['code'], array(
					401,
					403,
					404
				) ) ) ) {
			$rawdata = wp_remote_retrieve_body( $response );

			if ( empty( $rawdata ) ) {
				$result = pmxi_curl_download( $url, $fullpath, $to_variable );
				if ( ! $result and $iteration === false ) {
					$new_url = wp_all_import_translate_uri( $url );

					return ( $new_url !== $url ) ? get_file_curl( $new_url, $fullpath, $to_variable, true ) : $result;
				}

				return $result;
			}else{
				if(preg_match( '%\W(svg)$%i', basename( $fullpath ))){
					$rawdata = wp_all_import_sanitize_svg($rawdata, false);
					if( empty( $rawdata )){
						return false;
					}
				}
			}

			// Ensure we don't have a .php extension as it's often blocked on hosts in the uploads folder.
			$fullpath = str_replace( '.php', '.tmp', $fullpath );

			if ( ! @file_put_contents( $fullpath, $rawdata ) ) {
				$fp = fopen( $fullpath, 'w' );
				// Ensure the file is actually open before trying to write.
				if ( false !== $fp ) {
					fwrite( $fp, $rawdata );
					fclose( $fp );
				}
			}

			if ( preg_match( '%\W(svg)$%i', basename( $fullpath ) ) or preg_match( '%\W(' . wp_all_import_supported_image_extensions() . ')$%i', basename( $fullpath ) ) and ( ! ( $image_info = apply_filters( 'pmxi_getimagesize', @getimagesize( $fullpath ), $fullpath ) ) or ! in_array( $image_info[2], wp_all_import_supported_image_types() ) ) ) {
				$result = pmxi_curl_download( $url, $fullpath, $to_variable );
				if ( ! $result and $iteration === false ) {
					$new_url = wp_all_import_translate_uri( $url );

					return ( $new_url !== $url ) ? get_file_curl( $new_url, $fullpath, $to_variable, true ) : $result;
				}

				return $result;
			}

			return ( $to_variable ) ? $rawdata : true;
		} else {
			$use_only_wp_http_api = apply_filters( 'wp_all_import_use_only_wp_http_api', false );

			if ( false == $use_only_wp_http_api ) {
				$curl = pmxi_curl_download( $url, $fullpath, $to_variable );

				if ( $curl === false and $iteration === false ) {
					$new_url = wp_all_import_translate_uri( $url );

					return ( $new_url !== $url ) ? get_file_curl( $new_url, $fullpath, $to_variable, true ) : ( is_wp_error( $response ) ? $response : false );
				}

				return ( $curl === false ) ? ( is_wp_error( $response ) ? $response : false ) : $curl;
			}

			return $response;
		}
	}

endif;

if ( ! function_exists( 'pmxi_is_private_ip' ) ) {
	function pmxi_is_private_ip( $url ) {

		$url_components = parse_url( $url );
		$host           = $url_components['host'] ?? '';
		$is_private_ip  = false;

		if ( empty( $host ) ) {
			return false;
		}

		$resolved_ip = gethostbyname( $host );
		$local_ip    = gethostbyname( php_uname( 'n' ) );

		$private_ranges = [
			'10.0.0.0|10.255.255.255',
			'172.16.0.0|172.31.255.255',
			'192.168.0.0|192.168.255.255',
			'169.254.0.0|169.254.255.255',  // link-local
			'127.0.0.0|127.255.255.255',    // loopback
		];

		$long_ip = ip2long( $resolved_ip );
		foreach ( $private_ranges as $range ) {
			list( $start, $end ) = explode( '|', $range );
			if ( $long_ip >= ip2long( $start ) && $long_ip <= ip2long( $end ) ) {
				$is_private_ip = true;
			}
		}

		if ( $resolved_ip !== $local_ip && filter_var( $resolved_ip, FILTER_VALIDATE_IP ) && $is_private_ip ) {
			return ! apply_filters( 'http_request_host_is_external', false, $host, $url );
		}

		return false;
	}
}

if ( ! function_exists( 'pmxi_curl_download' ) ) {

	function pmxi_curl_download( $url, $fullpath, $to_variable ) {
		if ( ! function_exists( 'curl_version' ) ) {
			return false;
		}

		if ( pmxi_is_private_ip( $url ) ) {
			return false;
		}

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_NOBODY, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'User-Agent: '. \Wpai\WordPress\AttachmentHandler::$user_agent, 'Accept: '.apply_filters('pmxi_accept_header', 'application/xml;q=1.0, */*;q=0.8', $url) ));
		curl_setopt( $ch, CURLOPT_TIMEOUT, PMXI_Plugin::getInstance()->getOption( 'pmxi_file_download_timeout' ));

		$header = curl_exec( $ch );
		$finalUrl = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );
		curl_close( $ch );

		if ( pmxi_is_private_ip( $finalUrl ) ) {
			return false;
		}

		$ch = curl_init( $finalUrl );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'User-Agent: ' . \Wpai\WordPress\AttachmentHandler::$user_agent, 'Accept: '.apply_filters('pmxi_accept_header', 'application/xml;q=1.0, */*;q=0.8', $url) ));
		$rawdata = curl_exec( $ch );
		$result  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( empty( $rawdata ) ) {
			return false;
		}

		if(preg_match( '%\W(svg)$%i', basename( $fullpath ))){
			$rawdata = wp_all_import_sanitize_svg($rawdata, false);
			if( empty( $rawdata )){
				return false;
			}
		}

		$fp = fopen( $fullpath, 'w' );
		if ( false !== $fp ) {
			fwrite( $fp, $rawdata );
			fclose( $fp );
		}

		if ( preg_match( '%\\.(' . wp_all_import_supported_image_extensions() . ')$%i', basename( $fullpath ) ) && ( ! ( $image_info = apply_filters( 'pmxi_getimagesize', @getimagesize( $fullpath ), $fullpath ) ) || ! in_array( $image_info[2], wp_all_import_supported_image_types() ) ) ) {
			return false;
		}

		return ( $result == 200 ) ? ( ( $to_variable ) ? $rawdata : true ) : false;
	}
}

if ( ! function_exists( 'curl_exec_follow' ) ):

	function curl_exec_follow( $ch, &$maxredirect = null ) {

		$mr = $maxredirect === null ? 5 : intval( $maxredirect );

		if ( ini_get( 'open_basedir' ) == '' && ini_get( 'safe_mode' == 'Off' ) ) {

			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, $mr > 0 );
			curl_setopt( $ch, CURLOPT_MAXREDIRS, $mr );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		} else {

			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );

			if ( $mr > 0 ) {
				$original_url = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );
				$newurl       = $original_url;

				$url_data = parse_url( $newurl );

				if ( ! empty( $url_data['user'] ) and ! empty( $url_data['pass'] ) ) {
					curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
					curl_setopt( $ch, CURLOPT_USERPWD, $url_data['user'] . ":" . $url_data['pass'] );
					$newurl = $url_data['scheme'] . '://' . $url_data['host'];
					if ( ! empty( $url_data['port'] ) ) {
						$newurl .= ':' . $url_data['port'];
					}
					$newurl .= $url_data['path'];
					if ( ! empty( $url_data['query'] ) ) {
						$newurl .= '?' . $url_data['query'];
					}
				}

				$rch = curl_copy_handle( $ch );

				curl_setopt( $rch, CURLOPT_HEADER, true );
				curl_setopt( $rch, CURLOPT_NOBODY, true );
				curl_setopt( $rch, CURLOPT_FORBID_REUSE, false );

				do {
					curl_setopt( $rch, CURLOPT_URL, $newurl );
					$header = curl_exec( $rch );
					if ( curl_errno( $rch ) ) {
						$code = 0;
					} else {
						$code = curl_getinfo( $rch, CURLINFO_HTTP_CODE );
						if ( $code == 301 || $code == 302 ) {
							preg_match( '/Location:(.*?)\n/', $header, $matches );
							$newurl = trim( array_pop( $matches ) );

							// if no scheme is present then the new url is a
							// relative path and thus needs some extra care
							if ( ! preg_match( "/^https?:/i", $newurl ) ) {
								$newurl = $original_url . $newurl;
							}
						} else {
							$code = 0;
						}
					}
				} while ( $code && -- $mr );

				curl_close( $rch );

				if ( ! $mr ) {
					if ( $maxredirect !== null ) {
						$maxredirect = 0;
					}

					return false;
				}
				curl_setopt( $ch, CURLOPT_URL, $newurl );
			}
		}

		return curl_exec( $ch );
	}

endif;