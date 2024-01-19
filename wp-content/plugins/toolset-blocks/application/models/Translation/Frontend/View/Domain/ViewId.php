<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain;

/**
 * Class ViewId
 *
 * Value object for the id of an view.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain
 *
 * @since TB 1.3
 */
class ViewId {
	/** @var int */
	private $id;

	/**
	 * ViewId constructor.
	 *
	 * @param $some_id
	 */
	public function __construct( $some_id ) {
		$view_id = is_numeric( $some_id ) && ! is_float( $some_id ) ? (int) $some_id : false;

		if( empty( $view_id ) ) {
			throw new \InvalidArgumentException( '$view_id must be an integer higher than 0.' );
		}

		$this->id = $view_id;
	}

	/**
	 * @return int
	 */
	public function get() {
		return $this->id;
	}
}
