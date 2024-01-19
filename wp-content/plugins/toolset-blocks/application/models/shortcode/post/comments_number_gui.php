<?php

/**
 * Class WPV_Shortcode_Post_Comments_Number_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Comments_Number_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-comments-number shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-comments-number'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-comments-number shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post comments number', 'wpv-views' ),
			'label'          => __( 'Post comments number', 'wpv-views' ),
			'post-selection' => true,
			'attributes' => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'none' => array(
							'label' => __( 'Text to display when there are no comments', 'wpv-views' ),
							'type' => 'text',
							'default' => __( 'No Comments', 'wpv-views' ),
						),
						'one' => array(
							'label' => __( 'Text to display when there is one comment', 'wpv-views' ),
							'type' => 'text',
							'default' => __( '1 Comment', 'wpv-views' ),
						),
						'more' => array(
							'label' => __( 'Text to display when there is more than one comment', 'wpv-views' ),
							'type' => 'text',
							'default' => __( '% Comments', 'wpv-views' ),
							'description' => __( '% - placeholder for the number of comments', 'wpv-views' )
						),
					),
				),
			)
		);
		return $data;
	}
	
	
}