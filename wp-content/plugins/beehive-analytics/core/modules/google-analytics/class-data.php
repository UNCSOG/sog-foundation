<?php
/**
 * The Google general data class.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics
 */

namespace Beehive\Core\Modules\Google_Analytics;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Helpers\Cache;
use Beehive\Core\Helpers\General;
use Beehive\Core\Modules\Google_Auth;
use Beehive\Core\Utils\Abstracts\Google_API;
use Beehive\Google\Service\GoogleAnalyticsAdmin;
use Beehive\Google\Service\Exception as Google_Exception;

/**
 * Class Data
 *
 * @package Beehive\Core\Modules\Google_Analytics
 */
class Data extends Google_API {

	/**
	 * Constant representing Google Analytics account type.
	 */
	public const TYPE_ACCOUNTS = 'accounts';

	/**
	 * Constant representing Google Analytics property type.
	 */
	public const TYPE_PROPERTIES = 'properties';

	/**
	 * Constant representing Google Analytics stream type.
	 */
	public const TYPE_STREAMS = 'streams';

	/**
	 * Gets the default property ID.
	 *
	 * @param bool $network Is network wide?.
	 * @param bool $force Should skip cache?.
	 *
	 * @return string|null
	 * @since 3.4.14
	 */
	public function default_property( bool $network = false, bool $force = false ): ?string {
		$options  = array(
			'type'    => self::TYPE_ACCOUNTS,
			'page'    => 1,
			'network' => $network,
			'force'   => $force,
		);
		$accounts = $this->paginated_data( $options );
		if ( empty( $accounts[ self::TYPE_ACCOUNTS ] ) ) {
			return null;
		}
		$options['type']   = self::TYPE_PROPERTIES;
		$options['filter'] = array_key_first( $accounts[ self::TYPE_ACCOUNTS ] );
		$properties        = $this->paginated_data( $options );
		if ( empty( $properties[ self::TYPE_PROPERTIES ] ) ) {
			return null;
		}
		$options['type']   = self::TYPE_STREAMS;
		$options['filter'] = array_key_first( $properties[ self::TYPE_PROPERTIES ] );
		$streams           = $this->paginated_data( $options );
		if ( empty( $streams[ self::TYPE_STREAMS ] ) ) {
			return null;
		}
		foreach ( $streams[ self::TYPE_STREAMS ] as $stream ) {
			if ( $this->is_url_matching( $stream['url'] ) ) {
				$this->sync_stream( $stream, $network );
				return array_key_first( $properties[ self::TYPE_PROPERTIES ] );
			}
		}
		return null;
	}

	/**
	 * Fetches a list of Google Analytics data with pagination support, network-wide, and cache-bypass options.
	 *
	 * @param array $options {
	 *      Optional. An array of options to configure the data fetching.
	 *
	 *      @type string $type Required. Type of data to fetch. Accepts 'accounts', 'properties', or 'streams'.
	 *      @type string $token Optional. Next page token. Default empty.
	 *      @type string $filter Optional. Filter to apply, typically used for properties and streams.
	 *      @type bool $network Optional. Whether to fetch data network-wide. Default false.
	 *      @type bool $force Optional. Whether to bypass the cache and force a fresh fetch. Default false.
	 * }
	 * @return array {
	 *      Contains the fetched data and pagination information.
	 *
	 *      @type array $type The list of fetched items, keyed by their unique identifiers.
	 *      @type bool $hasMore Indicates if there are more pages of data to fetch.
	 * }
	 *
	 * @since 3.4.14
	 */
	public function paginated_data( array $options = array() ): array {
		// Set default values for options.
		$options = wp_parse_args(
			$options,
			array(
				'type'    => '',
				'token'   => '',
				'filter'  => '',
				'network' => false,
				'force'   => false,
			)
		);

		// Generate a unique transient key based on options using JSON.
		$transient_key = wp_json_encode( $options );

		// Attempt to retrieve cached data unless force is true.
		$cached_data = $this->cache( $transient_key, $options['network'], $options['force'] );

		$type = $options['type'];
		if ( false !== $cached_data ) {
			return apply_filters(
				"beehive_google_analytics_{$type}",
				$cached_data,
				$options['network'],
				$options['force']
			);
		}
		// Default page size is set to 10 for accounts, 100 for properties, and 25 for streams.
		switch ( $type ) {
			case self::TYPE_ACCOUNTS:
				$page_size = (int) apply_filters(
					"beehive_google_analytics_ga4_{$type}_page_size",
					10 // Default value for accounts.
				);
				break;
			case self::TYPE_PROPERTIES:
				$page_size = (int) apply_filters(
					"beehive_google_analytics_ga4_{$type}_page_size",
					100 // Default value for properties.
				);
				break;
			case self::TYPE_STREAMS:
				$page_size = (int) apply_filters(
					"beehive_google_analytics_ga4_{$type}_page_size",
					25 // Default value for streams.
				);
				break;
			default:
				$page_size = 25;
				break;
		}
		// Fetch new data.
		$new_data = $this->fetch_page_data(
			$options,
			array(
				'pageSize'  => $page_size,
				'pageToken' => $options['token'],
			)
		);

		// Store the fetched data in a transient.
		if ( empty( $new_data[ $type ] ) ) {
			// If the data is empty, clear the transient.
			Cache::delete_transient( $transient_key, $options['network'] );
			return array(
				$type       => array(),
				'pageToken' => '',
			);
		}

		Cache::set_transient( $transient_key, $new_data, $options['network'], 0 );

		/**
		 * Filter the fetched data.
		 *
		 * @since 3.4.14
		 *
		 * @param array $new_data The list of fetched items, keyed by their unique identifiers.
		 * @param bool $network Whether to fetch data network-wide.
		 * @param bool $force Whether to bypass the cache and force a fresh fetch.
		 *
		 * @return array The list of fetched items, keyed by their unique identifiers.
		 */
		return apply_filters(
			"beehive_google_analytics_{$type}",
			$new_data,
			$options['network'],
			$options['force']
		);
	}


	/**
	 * Fetches a page of Google Analytics data.
	 *
	 * @param array $options {
	 *      Optional. An array of options to configure the data fetching.
	 *
	 *      @type string $type Required. Type of data to fetch. Accepts 'accounts', 'properties', or 'streams'.
	 *      @type int $page Optional. Page number to fetch. Default 1.
	 *      @type string $filter Optional. Filter to apply, typically used for properties and streams.
	 *      @type bool $network Optional. Whether to fetch data network-wide. Default false.
	 *      @type bool $force Optional. Whether to bypass the cache and force a fresh fetch. Default false.
	 * }
	 * @param array $params {
	 *      Optional. An array of API request parameters.
	 *
	 *      @type int $page_size Page size to fetch.
	 *      @type string $next_page_token Next page token to fetch.
	 * }
	 * @return array {
	 *      Contains the fetched data and pagination information.
	 *
	 *      @type array $data The list of fetched items, keyed by their unique identifiers.
	 *      @type string $pageToken The next page token for pagination.
	 * }
	 *
	 * @since 3.4.14
	 */
	public function fetch_page_data( array $options, array $params ): array {
		$type    = $options['type'];
		$network = $options['network'];
		$filter  = $options['filter'];
		$data    = array();
		$token   = false;
		// Prepare API client.
		General::vendor_autoload();
		Google_Auth\Helper::instance()->setup_auth( $network );
		$analytics_admin = new GoogleAnalyticsAdmin(
			Google_Auth\Auth::instance()->client()
		);
		try {
			switch ( $type ) {
				case self::TYPE_ACCOUNTS:
					// Fetch accounts for the current page.
					$response = $analytics_admin->accounts->listAccounts( $params );
					$token    = $response->getNextPageToken();
					foreach ( $response->getAccounts() as $account ) {
						$data[ $account->getName() ] = $account->getDisplayName();
					}
					// Set the accounts count.
					$count = ! empty( $data ) ? count( $data ) : 0;
					if ( $count > 0 ) {
						beehive_analytics()->settings->update( 'accounts_count', $count, 'google_login', $network );
					}
					break;

				case self::TYPE_PROPERTIES:
					$params['filter'] = 'parent:' . $filter;
					// Fetch properties for the current page.
					$response = $analytics_admin->properties->listProperties( $params );
					$token    = $response->getNextPageToken();
					foreach ( $response->getProperties() as $property ) {
						$data[ $property->getName() ] = $property->getDisplayName();
					}
					break;
				case self::TYPE_STREAMS:
					// Fetch streams for the current page.
					$response = $analytics_admin->properties_dataStreams->listPropertiesDataStreams( $filter, $params ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$token    = $response->getNextPageToken();
					foreach ( $response->getDataStreams() as $stream ) {
						if ( 'WEB_DATA_STREAM' !== $stream->getType() ) {
							continue;
						}
						$web_stream  = $stream->getWebStreamData();
						$name        = $stream->getName();
						$stream_data = array(
							'title'       => $stream->getDisplayName(),
							'name'        => $name,
							'url'         => $web_stream->getDefaultUri(),
							'property'    => $filter,
							'measurement' => $web_stream->getMeasurementId(),
						);

						if ( $this->is_url_matching( $stream_data['url'] ) ) {
							$this->sync_stream( $stream_data, $network );
						}

						$data[ $name ] = $stream_data;
					}
					break;
				default:
					break;
			}

			/**
			 * Filter to modify the list of fetched items before caching.
			 *
			 * @param array $data The list of fetched items, keyed by their unique identifiers.
			 * @since 3.4.14
			 */
			$data = apply_filters( 'beehive_google_' . $type, $data );

			/**
			 * Action hook to execute after fetching data.
			 *
			 * @param array $data The list of fetched items, keyed by their unique identifiers.
			 * @param bool $network Network flag.
			 * @since 3.4.14
			 */
			do_action( 'beehive_after_google_' . $type, $data, $network );
		} catch ( Google_Exception | \Throwable $e ) {
			$data = array();
			// Process the exception.
			$this->error( $e );
		}

		return array(
			$type       => $data,
			'pageToken' => $token,
		);
	}

	/**
	 * Check if a URL matches the current site's URL.
	 *
	 * @param string $url URL to check.
	 *
	 * @return bool
	 * @since 3.4.0
	 */
	private function is_url_matching( string $url ): bool {
		// Get and normalize the current site's URL.
		$current_site_url = untrailingslashit( get_site_url( get_current_blog_id() ) );
		$current_host     = wp_parse_url( $current_site_url, PHP_URL_HOST );

		// Normalize the input URL.
		$input_host = wp_parse_url( esc_url( $url ), PHP_URL_HOST );

		// Normalize hosts by removing 'www.' prefix.
		$normalized_current_host = preg_replace( '/^www\./', '', $current_host );
		$normalized_input_host   = preg_replace( '/^www\./', '', $input_host );

		// Allow modification of the current site's normalized host through a filter.
		$filtered_current_host = apply_filters( 'beehive_ga_data_current_url_host', $normalized_current_host );

		// Determine if the normalized hosts match.
		$is_matching = strcasecmp( $filtered_current_host, $normalized_input_host ) === 0;

		/**
		 * Filter to modify URL matching logic.
		 *
		 * @since 3.3.8
		 * @since 3.4.0 $network param is deprecated.
		 *
		 * @param bool   $is_matching  Whether the URLs match.
		 * @param string $current_site_url Current site's URL.
		 * @param string $url         URL to check.
		 * @param bool   $network     Deprecated.
		 */
		return (bool) apply_filters( 'beehive_ga_data_current_url_host_matching', $is_matching, $current_site_url, $url, false );
	}

	/**
	 * Sync stream to auto-update stream ID.
	 *
	 * @param array $stream_data Stream data.
	 * @param bool  $is_network_wide Is network wide?.
	 *
	 * @return void
	 * @since 3.4.0
	 */
	private function sync_stream( array $stream_data, bool $is_network_wide ): void {
		// Retrieve current settings.
		$current_settings = beehive_analytics()->settings->get_options( false, $is_network_wide );

		// Update tracking ID if necessary.
		if ( empty( $current_settings['misc']['auto_track_ga4'] ) || $stream_data['measurement'] !== $current_settings['misc']['auto_track_ga4'] ) {
			$current_settings['misc']['auto_track_ga4'] = $stream_data['measurement'];
		}

		// Update stream ID if website URL matches.
		if ( empty( $current_settings['google']['stream'] ) ) {
			$current_settings['google']['stream'] = $stream_data['name'];
		}

		beehive_analytics()->settings->update_options( $current_settings, $is_network_wide );
	}

	/**
	 * Fetches a single piece of Google Analytics data by its ID.
	 *
	 * @param string $type Type of data to fetch. Accepts 'account', 'property', or 'stream'.
	 * @param string $id ID of the data to fetch.
	 * @param bool   $is_network_wide Whether to fetch data network-wide. Default false.
	 *
	 * @return array Name of the fetched data.
	 *
	 * @since 3.4.15
	 */
	public function fetch_name_by_id( string $type, string $id, bool $is_network_wide ): array {
		// Prepare API client.
		General::vendor_autoload();
		Google_Auth\Helper::instance()->setup_auth( $is_network_wide );
		$analytics_admin = new GoogleAnalyticsAdmin(
			Google_Auth\Auth::instance()->client()
		);

		switch ( $type ) {
			case 'account':
				$response = $analytics_admin->accounts->get( $id );
				return array(
					'id'   => $id,
					'text' => $response->getDisplayName(),
				);
			case 'property':
				$response = $analytics_admin->properties->get( $id );
				return array(
					'id'      => $id,
					'text'    => $response->getDisplayName(),
					'account' => $response->getAccount(),
				);

			case 'stream':
				$response = $analytics_admin->properties_dataStreams->get( $id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				return array(
					'id'   => $id,
					'text' => $response->getWebStreamData()->getDefaultUri() . '(' . $response->getDisplayName() . ')',
				);

			default:
				return array();
		}
	}

	/**
	 * Fetches the property associated with a given stream and updates settings.
	 *
	 * This function retrieves the stream name using the stream ID, updates the
	 * stream settings, and then extracts the property from the stream. It fetches
	 * the property name and account associated with the property, updating the
	 * relevant settings for each.
	 *
	 * @param string $stream_id  The ID of the stream to fetch the property for.
	 * @param bool   $is_network_wide Whether to fetch data network-wide. Default false.
	 *
	 * @return string|null The ID of the property fetched, or null if not found.
	 *
	 * @since 3.4.15
	 */
	public function fetch_property( string $stream_id, bool $is_network_wide ): ?string {
		$stream = Cache::get_transient( $stream_id, $is_network_wide );
		if ( empty( $stream ) ) {
			$stream = $this->fetch_name_by_id( 'stream', $stream_id, $is_network_wide );
			if ( ! empty( $stream ) ) {
				Cache::set_transient( $stream_id, $stream, $is_network_wide );
			}
		}

		beehive_analytics()->settings->update( 'stream', $stream, 'misc', $is_network_wide );
		$stream_parts = explode( '/dataStreams', $stream_id, 2 );
		$property_id  = $stream_parts[0];
		if ( ! isset( $property_id ) ) {
			return null;
		}
		$property = Cache::get_transient( $property_id, $is_network_wide );
		if ( empty( $property ) ) {
			$property = $this->fetch_name_by_id( 'property', $property_id, $is_network_wide );
			if ( ! empty( $property ) ) {
				Cache::set_transient( $property_id, $property, $is_network_wide );
			}
		}

		beehive_analytics()->settings->update( 'property', $property_id, 'google', $is_network_wide );
		beehive_analytics()->settings->update( 'property', $property, 'misc', $is_network_wide );
		$account = Cache::get_transient( $property['account'], $is_network_wide );
		if ( empty( $account ) ) {
			$account = $this->fetch_name_by_id( 'account', $property['account'], $is_network_wide );
			if ( ! empty( $account ) ) {
				Cache::set_transient( $property['account'], $account, $is_network_wide );
			}
		}
		beehive_analytics()->settings->update( 'account', $account, 'misc', $is_network_wide );
		beehive_analytics()->settings->update( 'account', $property['account'], 'google', $is_network_wide );
		return $property_id;
	}
}
