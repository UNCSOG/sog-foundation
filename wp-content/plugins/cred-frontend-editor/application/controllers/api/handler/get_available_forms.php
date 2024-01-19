<?php

/**
 * Handler for the cred_get_available_forms filter API.
 *
 * @since m2m
 */
class CRED_Api_Handler_Get_Available_Forms extends CRED_Api_Handler_Abstract implements CRED_Api_Handler_Interface {

	/**
	 * @var \OTGS\Toolset\CRED\Cache\Model\Forms\Factory
	 */
	private $cache_factory;

	public function __construct( \OTGS\Toolset\CRED\Model\Cache\Forms\Factory $cache_factory ) {
		$this->cache_factory = $cache_factory;
	}

	/**
	 * In the case of post and user forms, this filter returns an array with two main entries, 'new' and 'edit',
	 * holding the forms to create a new or to edit a specific object type.
	 *
	 * For association forms, the array contains the existing forms as top level elements.
	 *
	 * Each form is returned as an object with the following properties:
	 * - ID
	 * - post_title
	 * - post_name
	 *
	 * @param array $arguments Original action/filter arguments.
	 *
	 * @return array
	 */
	function process_call( $arguments ) {

		$domain = toolset_getarr( $arguments, 1 );

		if ( ! $caching = $this->cache_factory->create_by_domain( $domain ) ) {
			return array();
		}

		if ( false !== ( $existing_transient = $caching->get_transient() ) ) {
			return $existing_transient;
		}

		return $caching->generate_transient();
	}

}
