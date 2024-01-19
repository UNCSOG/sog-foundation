<?php

namespace ToolsetCommonEs\Rest\Route;


interface IRoute {
	public function get_name();

	public function get_version();

	public function get_method();

	public function callback( \WP_REST_Request $rest_request );

	public function permission_callback();
}
