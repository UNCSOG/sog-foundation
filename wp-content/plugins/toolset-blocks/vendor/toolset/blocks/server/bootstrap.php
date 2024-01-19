<?php

namespace ToolsetBlocks;

/**
 * Dependency Injection Wrapper
 * The one and only Singelton
 */
class DicLoader {

	/** @var DicLoader */
	private static $instance;

	/** @var \Auryn\Injector */
	private $dic;


	/**
	 * @returns DicLoader
	 */
	public static function get_instance() {
		return self::$instance = self::$instance ?: new self();
	}

	/**
	 * DicLoader constructor.
	 */
	private function __construct() {
		$this->dic = apply_filters( 'toolset_common_es_dic', false );
	}

	/**
	 * @return \Auryn\Injector
	 */
	public function get_dic() {
		return $this->dic;
	}
}

// Routes
require_once( TB_PATH . '/server/routes.php' );
require_once( TB_PATH . '/server/WPML/AttributesSetter.php' );
