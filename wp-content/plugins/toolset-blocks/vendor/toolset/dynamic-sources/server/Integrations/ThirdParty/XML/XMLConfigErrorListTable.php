<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty\XML;

use Toolset\DynamicSources\Integrations\ThirdParty\ConfigurationUpdateLogger;
use Toolset\DynamicSources\Utils\WPListTable;

/**
 * Handles the rendering of a table to list the errors occurred since the last successful try, during the updating of the
 * configuration data of the third-party plugins for the automatic Dynamic Sources integration from the Toolset CDN.
 */
class XMLConfigErrorListTable extends WPListTable {
	const ERRORS_PER_PAGE = 5;

	/**
	 * Prepares the data for the table.
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$data = $this->table_data();

		usort( $data, array( $this, 'sort_data' ) );

		$current_page = $this->get_pagenum();

		$total_items = count( $data );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => self::ERRORS_PER_PAGE,
			)
		);

		$data = array_slice( $data, ( ( $current_page - 1 ) * self::ERRORS_PER_PAGE ), self::ERRORS_PER_PAGE );

		$this->items = $data;
	}

	/**
	 * Populates the column data.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'timestamp' => __( 'Timestamp', 'wpv-views' ),
			'request' => __( 'Request', 'wpv-views' ),
			'type' => __( 'Type', 'wpv-views' ),
			'component' => __( 'Component', 'wpv-views' ),
			'response' => __( 'Response', 'wpv-views' ),
			'extra' => __( 'Extra', 'wpv-views' ),
		);
	}

	/**
	 * Populates the columns that are sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'timestamp' => array(
				'timestamp',
				false,
			),
		);
	}

	/**
	 * Populates the hidden columns, if any.
	 *
	 * @return array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Fetches the data for the table.
	 *
	 * @return array
	 */
	private function table_data() {
		$logger = new ConfigurationUpdateLogger();
		return $logger->get();
	}

	/**
	 * Gets the default data for each column.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( ! is_array( $item ) ) {
			return print_r( $item, true );
		}

		switch ( $column_name ) {
			case 'timestamp':
				return date_i18n( 'Y-m-d H:i:s', $item[ $column_name ] );
			case 'response':
				$column_data = $item[ $column_name ];

				if ( ! is_array( $column_data ) ) {
					$column_data = array( $column_data );
				}

				return $this->get_iterative_column_data( $column_data );
			case 'request':
			case 'type':
			case 'component':
			case 'extra':
				return $item[ $column_name ];
			default:
				//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				return print_r( $item, true );
		}
	}

	/**
	 * Parses an array of iterative error data and produces a formatted string out of it.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	private function get_iterative_column_data( $data ) {
		$iterative_column_data_content = '<ol>';

		foreach ( $data as $key => $value ) {
			if ( ! is_array( $value ) ) {
				$iterative_column_data_content .= "<li>$key: <b>$value</b></li>";
			} else {
				$sub_content = $this->get_iterative_column_data( $value );
				$iterative_column_data_content .= "<li>$key: $sub_content</li>";
			}
		}

		$iterative_column_data_content .= '</ol>';

		return $iterative_column_data_content;
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @param array $error_a
	 * @param array $error_b
	 *
	 * @return int
	 */
	private function sort_data( $error_a, $error_b ) {
		// If orderby is set, use this as the sort column

		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'timestamp';

		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';

		$result = strcmp( $error_a[ $orderby ], $error_b[ $orderby ] );

		if ( 'asc' === $order ) {
			return $result;
		}

		return -$result;
	}
}
