<?php

/**
 * Class CRED_Shortcode_Association_Title
 *
 * @since m2m
 */
class CRED_Shortcode_Association_Title extends CRED_Shortcode_Association_Base implements CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-relationship-title';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'class' => '', // classnames
		'style' => '' // extra inline styles
	);

	/**
	 * @var string|null
	 */
	private $user_content;
	
	/**
	 * @var array
	 */
	private $user_atts;

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	*
	* @return string
	*
	* @since m2m
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;
		
		$current_association = $this->helper->get_current_association();
		
		if ( ! $current_association instanceof Toolset_Post ) {
			return;
		}
		
		return $current_association->get_title();
	}
	
	
}