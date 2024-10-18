<?php

/**
 * Class WPV_Shortcode_Post_Field_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Field_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-field shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-field'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-field shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post field', 'wpv-views' ),
			'label'          => __( 'Post field', 'wpv-views' ),
			'post-selection' => true,
			'attributes' => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'name' => array(
							'label'       => __( 'Custom field name', 'wpv-views' ),
							'type'        => 'suggest',
							'action'      => 'wpv_suggest_wpv_post_field_name',
							'required'    => true,
						),
						'index_info'	=> array(
							'label'		=> __( 'Index and separator', 'wpv-views' ),
							'type'		=> 'info',
							'content'	=> __( 'If the field has multiple values, you can display just one of them or all the values using a separator.', 'pv-views' )
						),
						'index_combo'	=> array(
							'type'		=> 'grouped',
							'fields'	=> array(
								'index' => array(
									'pseudolabel'	=> __( 'Index', 'wpv-views' ),
									'type'			=> 'number',
									'description'	=> __( 'Leave empty to display all values.', 'wpv-views' ),
								),
								'separator' => array(
									'type'			=> 'text',
									'pseudolabel'	=> __( 'Separator', 'wpv-views' ),
									'default'		=> ', ',
								),
							)
						),
						'parse_shortcodes' => array(
							'label'		=> __( 'Parse inner shortcodes', 'wpv-views' ),
							'type'		=> 'radio',
							'options'	=> array(
								'true'	=> __( 'Parse shortcodes inside the field values', 'wpv-views' ),
								''		=> __( 'Do not parse shortcodes inside the field values', 'wpv-views' ),
							),
							'default'	=> '',
						),
					),
				),
			)
		);
		return $data;
	}
	
	
}