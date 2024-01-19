<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Class WPV_Shortcode_Base_View
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Base_View implements WPV_Shortcode_Interface_View  {

	/**
	 * @var WPV_Shortcode_Interface
	 */
	private $shortcode;


	public function __construct( WPV_Shortcode_Interface $shortcode ) {
		$this->shortcode = $shortcode;
	}

	/**
	 * Shortcode callback
	 *
	 * @param $atts
	 * @param null $content
	 *
	 * @return string|void
	 */
	public function render( $atts, $content = null ) {
		try {
			return $this->shortcode->get_value( $atts, $content );
		} catch( WPV_Exception_Invalid_Shortcode_Attr_Item $e_invalid_item ) {
			if ( current_user_can( EDIT_VIEWS ) ) {
				// todo implement response for admins, see toolsetcommon-174
				// msg: No valid item
				return '';
			}

			// invalid shortcode, don't show anything to users
			return;

		}
	}
}
