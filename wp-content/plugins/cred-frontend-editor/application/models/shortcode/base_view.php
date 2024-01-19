<?php

/**
 * Class CRED_Shortcode_Base_View
 *
 * @since m2m
 */
class CRED_Shortcode_Base_View implements CRED_Shortcode_Interface_View  {

	/**
	 * @var WPV_Shortcode_Interface
	 */
	private $shortcode;


	public function __construct( CRED_Shortcode_Interface $shortcode ) {
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
	public function render( $atts, $content = null, $tag = '' ) {
		try {
			do_action( 'cred_do_shortcode_' . $tag );
			return $this->shortcode->get_value( $atts, $content );
		} catch( CRED_Exception_Invalid_Shortcode_Attr_Item $e_invalid_item ) {
			if ( current_user_can( 'manage_options' ) ) {
				// todo implement response for admins, see toolsetcommon-174
				// msg: No valid item
				return '';
			}

			// invalid shortcode, don't show anything to users
			return;

		}
	}
}