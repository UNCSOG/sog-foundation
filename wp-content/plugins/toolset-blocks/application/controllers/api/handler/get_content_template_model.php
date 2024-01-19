<?php

/**
 * Handler for wpv_get_content_template_model
 *
 * @since 2.8.5
 */
class WPV_Api_Handler_Get_Content_Template_Model implements WPV_Api_Handler_Interface {


	public function __construct() { }


	/**
	 * @param array $arguments Original action/filter arguments.
	 *
	 * @return WPV_Content_Template|null
	 */
	function process_call( $arguments ) {
		$source = toolset_getarr( $arguments, 1, null );

		if( null === $source ) {
			return null;
		}

		$content_template = WPV_Content_Template::get_instance( $source );

		return $content_template;
	}

}
