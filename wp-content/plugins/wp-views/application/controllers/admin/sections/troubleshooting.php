<?php

namespace OTGS\Toolset\Views\Controller\Admin\Section;

/**
 * Handles the Views related sections in the Troubleshooting page on the Toolset Settings.
 */
class Troubleshooting {
	/**
	 * Initializes the class.
	 */
	public function initialize() {
		add_action( 'toolset_page_toolset-debug-information', array( $this, 'list_ds_errors_table' ), PHP_INT_MAX );
	}

	/**
	 * Triggers the action to print the Dynamic Sources Configuration update errors list table.
	 */
	public function list_ds_errors_table() {
		do_action( 'toolset/dynamic_sources/actions/third_party_integration/print_configuration_update_error_list_table' );
	}
}
