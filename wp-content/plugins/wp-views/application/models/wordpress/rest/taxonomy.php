<?php

namespace OTGS\Toolset\Views\Model\Wordpress\REST;

/**
 * Wrapper for the WordPress WP_REST_Taxonomy class.
 *
 * @since 3.2
 */
class Taxonomy {

	const API_PREFIX = '/wp/v2/%s';

	const ALLOWED_PARAMETERS = [ 'exclude', 'include', 'order', 'orderby', 'post', 'hide_empty', 'per_page', 'search', 'slug', 'parent' ];
	const DEFAULT_PARAMETERS = [
		'per_page' => 10,
		'page' => 1,
		'hide_empty' => false,
	];

	/** @var string[] */
	private $parameters;

	/**
	 * REST API Constructor.
	 *
	 * @param string[] $parameters
	 */
	public function __construct( $parameters = [] ) {
		$this->parameters = $parameters;
	}


	/**
	 * Get items for WP_REST_Terms_Controller, given the instantiated parameters.
	 *
	 * @return array
	 */
	public function get_items() {

		if ( ! isset( $this->parameters[ 'taxonomy' ] ) ) {
			return [];
		}

		$controller = new \WP_REST_Terms_Controller( $this->parameters[ 'taxonomy' ] );
		$request = new \WP_REST_Request(
			'GET',
			sprintf( self::API_PREFIX, $this->parameters[ 'taxonomy' ] )
		);

		$query_params = self::DEFAULT_PARAMETERS;
		foreach( self::ALLOWED_PARAMETERS as $allowed_parameter ) {
			if ( isset( $this->parameters[ $allowed_parameter ] ) ) {
				$query_params[ $allowed_parameter ] = $this->parameters[ $allowed_parameter ];
			}
		}

		$request->set_query_params( $query_params );

		return $controller->get_items( $request )->data;
	}
}
