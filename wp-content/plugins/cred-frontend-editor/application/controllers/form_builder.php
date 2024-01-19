<?php

/**
 * Class CRED_Form_Builder responsible of Toolset Form creations
 */
class CRED_Form_Builder extends CRED_Form_Builder_Base {

	/**
	 * Used as a common id for all fields, comming from different sources.
	 *
	 * @var string
	 * @since 2.2
	 */
	const SCAFFOLD_FIELD_ID = 'scaffold_field_id';

	private static $instance;

	public function __construct() {
		parent::__construct();
	}

	public static function initialize() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
