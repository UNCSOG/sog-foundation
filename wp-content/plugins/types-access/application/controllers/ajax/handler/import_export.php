<?php
/**
 * Class Access_Ajax_Handler_Import_Export
 * Export Access settings
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Import_Export extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Import_Export constructor.
	 *
	 * @param \OTGS\Toolset\Access\Ajax $access_ajax
	 */
	public function __construct( \OTGS\Toolset\Access\Ajax $access_ajax ) {
		parent::__construct( $access_ajax );
	}


	/**
	 * @param mixed $arguments
	 *
	 * @return array
	 */
	function process_call( $arguments ) {

		$this->ajax_begin( array(
			'nonce' => 'access-export-form',
			'nonce_parameter' => 'access-export-form',
			'parameter_source' => 'post',
		) );
		\TAccess_Loader::load( 'CLASS/XML_Processor' );
		Access_XML_Processor::exportToXML( 'all' );
	}
}
