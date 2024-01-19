<?php
namespace Toolset\DynamicSources\Integrations\ThirdParty;

/**
 * Handles the logging for errors caused by the Toolset Dynamic Sources config update process.
 */
class ConfigurationUpdateLogger {
	const OPTION_NAME = 'toolset-dynamic-sources-xml-config-update-log';

	/**
	 * Gets the logged configuration update errors.
	 *
	 * @param int $page_size
	 * @param int $page
	 *
	 * @return array
	 */
	public function get( $page_size = 0, $page = 0 ) {
		$data = get_option( self::OPTION_NAME );
		if ( ! $data ) {
			$data = array();
		}

		return $this->paginate( $data, $page_size, $page );
	}

	/**
	 * Logs a new error.
	 *
	 * @param string|int|float $timestamp
	 * @param array            $entry
	 */
	public function insert( $timestamp, array $entry ) {
		if ( $entry && is_array( $entry ) ) {
			$log = $this->get();
			if ( ! $log ) {
				$log = array();
			}
			$log[ (string) $timestamp ] = $entry;
			$this->save( $log );
		}
	}

	/**
	 * Empties the queue of logged errors.
	 */
	public function clear() {
		$this->save( array() );
	}

	/**
	 * Save the new queue of logged errors.
	 *
	 * @param array $data
	 */
	public function save( array $data ) {
		if ( empty( $data ) ) {
			delete_option( self::OPTION_NAME );
			return;
		}

		update_option( self::OPTION_NAME, $data, false );
	}

	/**
	 * Checks if the error log queue is empty.
	 *
	 * @return bool
	 */
	public function is_empty() {
		return ! $this->get();
	}

	/**
	 * Paginates the list of errors already in the log.
	 *
	 * @param array $data
	 * @param int   $page_size
	 * @param int   $page
	 *
	 * @return array
	 */
	protected function paginate( array $data, $page_size, $page ) {
		if ( (int) $page_size > 0 ) {
			$total = count( $data ); // total items in array
			$limit = $page_size; // per page
			$total_pages = ceil( $total / $limit ); // calculate total pages
			$page = max( $page, 1 ); // get 1 page when$page <= 0
			$page = min( $page, $total_pages ); // get last page when$page > $totalPages
			$offset = ( $page - 1 ) * $limit;
			if ( $offset < 0 ) {
				$offset = 0;
			}

			$data = array_slice( $data, $offset, $limit );
		}

		return $data;
	}
}
