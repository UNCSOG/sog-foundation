<?php

/**
 * Class WPV_Shortcode_Post_Edit_Link_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Edit_Link_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-edit-link shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-edit-link'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-edit-link shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post edit link', 'wpv-views' ),
			'label'          => __( 'Post edit link', 'wpv-views' ),
			'post-selection' => true,
			'attributes' => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'text' => array(
							'label'       => __( 'Edit link text', 'wpv-views' ),
							'type'        => 'text',
							'description' => __( 'Set the text for the link. Defaults to "Edit This".', 'wpv-views' ),
						),
						'class' => array(
							'label'       => __( 'Class', 'wpv-views' ),
							'type'        => 'text',
							'description' => __( 'Space-separated list of classnames that will be added to the anchor HTML tag.', 'wpv-views' ),
							'placeholder' => 'class1 class2',
						),
						'style' => array(
							'label'       => __( 'Style', 'wpv-views'),
							'type'        => 'text',
							'description' => __( 'Inline styles that will be added to the anchor HTML tag.', 'wpv-views' ),
							'placeholder' => 'border: 1px solid red; font-size: 2em;',
						),
					),
				),
			)
		);
		return $data;
	}
	
	
}