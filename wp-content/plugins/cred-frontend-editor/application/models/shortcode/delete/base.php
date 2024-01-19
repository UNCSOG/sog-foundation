<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Delete;

/**
 * Base class for form edit links.
 *
 * @since m2m
 */
class Base {

	/**
	 * @var \Toolset_Shortcode_Attr_Interface
	 */
	protected $item;
	
	/**
	 * @param \Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct( \Toolset_Shortcode_Attr_Interface $item ) {
		$this->item = $item;
	}
	
}