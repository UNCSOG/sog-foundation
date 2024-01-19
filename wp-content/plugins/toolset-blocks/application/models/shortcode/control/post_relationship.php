<?php

/**
 * Class WPV_Shortcode_Control_Post_Relationship
 *
 * @since m2m
 */
class WPV_Shortcode_Control_Post_Relationship implements WPV_Shortcode_Interface {

	const SHORTCODE_NAME = 'wpv-control-post-relationship';
	const SHORTCODE_NAME_ALIAS = 'wpv-control-set';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'url_param' => '',
		'ancestors' => '',
		'format' => ''
	);
	
	/**
	 * @var array
	 */
	private $required_atts = array(
		'url_param', 'ancestors'
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
	 * @var string
	 */
	private $shortcode;
	
	/**
	 * @var WPV_Filter_Base
	 */
	private $filter;


	/**
	 * Constructor.
	 *
	 * @param $shortcode
	 */
	public function __construct( $shortcode ) {
		$this->shortcode = $shortcode;
	}

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
		
		$filter_manager = WPV_Filter_Manager::get_instance();
		$this->filter = $filter_manager->get_filter( Toolset_Element_Domain::POSTS, 'relationship' );
		
		if ( ! $this->filter->is_types_installed() ) {
			return __( 'You need the Types plugin to render this custom search control.', 'wpv-views' );
		}
		
		foreach ( $this->required_atts as $required_att ) {
			if ( empty( $this->user_atts[ $required_att ] ) ) {
				return sprintf( __( 'The %s attribute is missing.', 'wpv-views' ), $required_att );
			}
		}

		$this->filter->set_filter_data( 'url_param', $this->user_atts['url_param'] );
		$this->filter->set_filter_data( 'ancestors', $this->user_atts['ancestors'] );
		$this->filter->set_filter_data( 'format', $this->user_atts['format'] );
		
		$return = wpv_do_shortcode( $this->user_content );
		
		$this->filter->clear_current_object_settings();
		
		return $return;
	}
	
}
