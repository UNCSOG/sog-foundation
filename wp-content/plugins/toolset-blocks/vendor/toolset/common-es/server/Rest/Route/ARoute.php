<?php

namespace ToolsetCommonEs\Rest\Route;


use ToolsetCommonEs\Library\WordPress\User;

abstract class ARoute implements IRoute {
	/** @var string Name of the Route */
	protected $name;

	/** @var int Version of the Route */
	protected $version = 1;

	/** @var User */
	protected $wp_user;

	public function __construct( User $wp_user ) {
		$this->wp_user = $wp_user;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_method() {
		return 'POST';
	}

	public function get_version() {
		return $this->version;
	}
}
