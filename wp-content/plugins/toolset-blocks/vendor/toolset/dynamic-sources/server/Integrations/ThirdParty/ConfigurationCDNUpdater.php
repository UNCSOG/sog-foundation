<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty;

use WP_Http;

/**
 * Fetch the toolset config files for known plugins and themes
 */
class ConfigurationCDNUpdater {
	/** @var bool */
	private $has_errors;

	/** @var WP_Http */
	private $wp_http;

	/** @var ConfigurationUpdateLogger */
	private $config_update_logger;

	/** @var ActivePluginsProvider */
	private $active_plugins_provider;

	/**
	 * ToolsetConfigUpdate constructor.
	 *
	 * @param WP_Http                      $wp_http
	 * @param ConfigurationUpdateLogger    $config_update_logger
	 * @param ActivePluginsProvider        $active_plugins_provider
	 */
	public function __construct( WP_Http $wp_http, ConfigurationUpdateLogger $config_update_logger, ActivePluginsProvider $active_plugins_provider ) {
		$this->wp_http = $wp_http;
		$this->active_plugins_provider = $active_plugins_provider;
		$this->config_update_logger = $config_update_logger;
	}

	/**
	 * Runs the updating of the configuration data for the third-party block plugins automatic Dynamic Sources integration.
	 *
	 * @return bool
	 */
	public function run() {
		if ( ! $this->is_config_update_disabled() ) {
			$this->has_errors = false;
			$request_args = array( 'timeout' => 45 );

			$index_response = $this->wp_http->get( trailingslashit( TOOLSET_DYNAMIC_SOURCES_REMOTE_CONFIG_FILES_CDN ) . 'toolset-config/config-index.json', $request_args );

			if ( ! $this->is_a_valid_remote_response( $index_response ) ) {
				$this->log_response( $index_response, 'index', 'toolset-config/config-index.json' );
			} else {
				$arr = json_decode( $index_response['body'] );

				$plugins = isset( $arr->plugins ) ? $arr->plugins : array();
				$themes = isset( $arr->themes ) ? $arr->themes : array();

				if ( $plugins || $themes ) {
					update_option( 'toolset_dynamic_sources_config_index', $arr, false );
					update_option( 'toolset_dynamic_sources_config_index_updated', date_i18n( 'U' ), false );

					$config_files_original = get_option( 'toolset_dynamic_sources_config_files_arr', null );
					$config_files = maybe_unserialize( $config_files_original );

					$config_files_for_themes = array();
					$deleted_configs_for_themes = array();
					$config_files_for_plugins = array();
					$deleted_configs_for_plugins = array();
					if ( $config_files ) {
						if ( isset( $config_files->themes ) && $config_files->themes ) {
							$config_files_for_themes = $config_files->themes;
							$deleted_configs_for_themes = $config_files->themes;
						}
						if ( isset( $config_files->plugins ) && $config_files->plugins ) {
							$config_files_for_plugins = $config_files->plugins;
							$deleted_configs_for_plugins = $config_files->plugins;
						}
					}

					$current_theme_name = wp_get_theme()->get( 'Name' );
					$current_theme_parent = wp_get_theme()->parent_theme;

					$active_theme_names = array( $current_theme_name );
					if ( $current_theme_parent ) {
						$active_theme_names[] = $current_theme_parent;
					}

					foreach ( $themes as $theme ) {

						if ( in_array( $theme->name, $active_theme_names, true ) ) {

							unset( $deleted_configs_for_themes[ $theme->name ] );

							if ( ! isset( $config_files_for_themes[ $theme->name ] ) || md5( $config_files_for_themes[ $theme->name ] ) !== $theme->hash ) {
								$theme_response = $this->wp_http->get( trailingslashit( TOOLSET_DYNAMIC_SOURCES_REMOTE_CONFIG_FILES_CDN ) . $theme->path, $request_args );
								if ( ! $this->is_a_valid_remote_response( $theme_response ) ) {
									$this->log_response( $theme_response, 'index', $theme->name );
								} else {
									$config_files_for_themes[ $theme->name ] = $theme_response['body'];
								}
							}
						}
					}

					foreach ( $deleted_configs_for_themes as $key => $deleted_config ) {
						unset( $config_files_for_themes[ $key ] );
					}

					$active_plugins_names = $this->active_plugins_provider->get_active_plugin_names();

					foreach ( $plugins as $plugin ) {

						if ( in_array( $plugin->name, $active_plugins_names, true ) ) {

							unset( $deleted_configs_for_plugins[ $plugin->name ] );

							if ( ! isset( $config_files_for_plugins[ $plugin->name ] ) || md5( $config_files_for_plugins[ $plugin->name ] ) !== $plugin->hash ) {
								$plugin_response = $this->wp_http->get( trailingslashit( TOOLSET_DYNAMIC_SOURCES_REMOTE_CONFIG_FILES_CDN ) . $plugin->path, $request_args );

								if ( ! $this->is_a_valid_remote_response( $plugin_response ) ) {
									$this->log_response( $plugin_response, 'index', $plugin->name );
								} else {
									$config_files_for_plugins[ $plugin->name ] = $plugin_response['body'];
								}
							}
						}
					}

					foreach ( $deleted_configs_for_plugins as $key => $deleted_config ) {
						unset( $config_files_for_plugins[ $key ] );
					}

					if ( ! $config_files ) {
						$config_files = new \stdClass();
					}
					$config_files->themes = $config_files_for_themes;
					$config_files->plugins = $config_files_for_plugins;

					update_option( 'toolset_dynamic_sources_config_files_arr', $config_files, false );
				}
			}

			$toolset_dynamic_sources_config_files_arr = maybe_unserialize( get_option( 'toolset_dynamic_sources_config_files_arr', null ) );

			if ( ! $toolset_dynamic_sources_config_files_arr ) {
				$this->log_response( 'Missing data', 'get_option', 'toolset_dynamic_sources_config_files_arr' );
			}

			if ( ! $this->has_errors && $this->config_update_logger ) {
				$this->config_update_logger->clear();
			}

			if ( ! $this->has_errors ) {
				do_action( 'toolset/dynamic_sources/actions/remote_configuration_updated' );
			}
		}

		return ! $this->has_errors;
	}

	/**
	 * Determines if the specified response is valid.
	 *
	 * @param array| \WP_Error $response
	 *
	 * @return bool
	 */
	private function is_a_valid_remote_response( $response ) {
		return $response && ! is_wp_error( $response ) && ! $this->is_http_error( $response );
	}

	/**
	 * Determines if the specified response has an HTTP error code specified.
	 *
	 * @param array| \WP_Error $response
	 *
	 * @return bool
	 */
	private function is_http_error( $response ) {
		return $response &&
			is_array( $response ) &&
			(
				(
					array_key_exists( 'response', $response ) &&
					array_key_exists( 'code', $response['response'] ) &&
					200 !== (int) $response['response']['code']
				) ||
				! array_key_exists( 'body', $response ) ||
				'' === trim( $response['body'] )
			);
	}

	/**
	 * Logs the specified response in case of an error.
	 *
	 * @param string|array|\WP_Error $response
	 * @param string                 $request_type
	 * @param string|null            $component
	 * @param array|\stdClass|null   $extra_data
	 */
	private function log_response( $response, $request_type = 'unknown', $component = null, $extra_data = null ) {
		if ( ! $this->config_update_logger ) {
			return;
		}

		$message_type = 'message';

		if ( ! defined( 'JSON_PRETTY_PRINT' ) ) {
			// Fallback -> Introduced in PHP 5.4.0
			define( 'JSON_PRETTY_PRINT', 128 );
		}

		$response_data = null;
		if ( is_scalar( $response ) ) {
			$message_type = 'app_error';
			$response_data = $response;
		} elseif ( is_wp_error( $response ) ) {
			$message_type = 'wp_error';
			$response_data = array(
				'code' => $response->get_error_code(),
				'message' => $response->get_error_message(),
			);
		} elseif ( $this->is_http_error( $response ) ) {
			$message_type = 'http_error';
			if ( array_key_exists( 'response', $response ) ) {
				if ( array_key_exists( 'code', $response['response'] ) ) {
					$response_data['code'] = $response['response']['code'];
				}
				if ( array_key_exists( 'message', $response['response'] ) ) {
					$response_data['message'] = $response['response']['message'];
				}
			}
			$response_data['body'] = 'Missing!';
			if ( array_key_exists( 'body', $response ) ) {
				$response_data['body'] = 'Empty!';
				if ( $response['body'] ) {
					// The errors will be suppressed below as most probably "simplexml_load_string" will trigger a few.
					$response_data['body'] = @json_decode( wp_json_encode( simplexml_load_string( $response['body'] ) ), true ); //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}
		} elseif ( is_array( $response ) ) {
			$response_data = $response;
		} else {
			$response_data = array( wp_json_encode( $response, JSON_PRETTY_PRINT ) );
		}

		$serialized_extra_data = null;
		if ( $extra_data ) {
			$serialized_extra_data = $extra_data;
			if ( is_object( $serialized_extra_data ) ) {
				$serialized_extra_data = get_object_vars( $serialized_extra_data );
			}
			if ( ! is_array( $serialized_extra_data ) ) {
				$serialized_extra_data = array( wp_json_encode( $serialized_extra_data, JSON_PRETTY_PRINT ) );
			}
		}

		$timestamp = date_i18n( 'U' );

		$entry = array(
			'request' => $request_type,
			'type' => $message_type,
			'component' => $component,
			'response' => $response_data,
			'extra' => $serialized_extra_data,
			'timestamp' => $timestamp,
		);
		$this->config_update_logger->insert( $timestamp, $entry );
		$this->has_errors = true;
	}

	/**
	 * Determines if the updating of the automatic Dynamic Sources configuration is disabled.
	 *
	 * @return bool
	 */
	private function is_config_update_disabled() {
		if ( defined( 'TOOLSET_DYNAMIC_SOURCES_REMOTE_CONFIG_DISABLED' ) ) {
			delete_option( 'toolset_dynamic_sources_config_index' );
			delete_option( 'toolset_dynamic_sources_config_index_updated' );
			delete_option( 'toolset_dynamic_sources_config_files_arr' );

			return true;
		}

		return false;
	}
}
