<?php

namespace ToolsetBlocks\Rest;

use ToolsetBlocks\Rest\Route\IRoute;
use ToolsetBlocks\Rest\Route\IShortcode;
use ToolsetBlocks\Library\WordPress\Rest;
use ToolsetBlocks\Utils\ScriptData;

class API {
	/** @var Rest */
	private $wp_rest;

	/** @var string */
	private $namespace = 'ToolsetBlocks/Rest/API';

	/** @var IRoute[] */
	private $routes = array();

	/** @var ScriptData  */
	private $script_data;

	/**
	 * API constructor.
	 *
	 * @param Rest $wp_rest
	 * @param ScriptData $script_data
	 */
	public function __construct( Rest $wp_rest, ScriptData $script_data ) {
		$this->wp_rest = $wp_rest;
		$this->script_data = $script_data;
	}

	/**
	 * @param IRoute $route
	 */
	public function add_route( IRoute $route ) {
		$this->routes[] = $route;
	}

	/**
	 * @action rest_api_init 1
	 */
	public function rest_api_init() {
		if ( empty( $this->routes ) ) {
			return;
		}

		// add nonce to script data
		$this->script_data->add_data(
			'wp_rest_nonce',
			$this->wp_rest->wp_create_nonce( 'wp_rest' )
		);

		global $post; // add current post id to script data
		if ( is_object( $post ) && property_exists( $post, 'ID' ) ) {
			$this->script_data->add_data(
				'current_post_id',
				$post->ID
			);
		}

		// add toolset plugins information to script data
		$toolset_plugins = array(
			'views' => array(
				'name' => 'Toolset Views',
				'active' => defined( 'WPV_VERSION' ),
			),
			'forms' => array(
				'name' => 'Toolset Forms',
				'active' => defined( 'CRED_FE_VERSION' ),
			),
			'types' => array(
				'name' => 'Toolset Types',
				'active' => defined( 'TYPES_VERSION' ),
			),
			'wooviews' => array(
				'name' => 'WooCommerce Views',
				'active' => defined( 'WC_VIEWS_VERSION' ),
			),
		);

		$this->script_data->add_data( 'plugins', $toolset_plugins );

		foreach ( $this->routes as $route ) {
			$namespace_w_version = $this->namespace . '/v' . $route->get_version();
			$route_w_slash = '/' . $route->get_name();

			$this->expose_route_on_script_data( $namespace_w_version, $route_w_slash );
			$this->wp_rest->register_rest_route(
				$namespace_w_version,
				$route_w_slash,
				array(
					'methods' => $route->get_method(),
					'callback' => array( $route, 'callback' ),
					'permission_callback' => array( $route, 'permission_callback' ),
				)
			);
		}

	}

	/**
	 * Makes route accessible on scriptData via 'Route/[route->get_name()]'
	 *
	 * @param string $namespace_w_version
	 * @param string $route_w_slash
	 */
	private function expose_route_on_script_data( $namespace_w_version, $route_w_slash ) {
		$this->script_data->add_data( 'Route' . $route_w_slash, $namespace_w_version . $route_w_slash );
	}
}
