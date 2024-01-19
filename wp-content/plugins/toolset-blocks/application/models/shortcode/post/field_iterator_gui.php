<?php

/**
 * Class WPV_Shortcode_Post_Field_Iterator_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Field_Iterator_GUI extends WPV_Shortcode_Base_GUI {

	/**
	 * Register the wpv-post-field-iterator / wpv-for-each shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-for-each'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}

	/**
	 * Get the wpv-post-field-iterator / wpv-for-each shortcode attributes data.
	 *
	 * @return array
	 * @since 2.5.0
	 * @since 2.8.1 Added the separator attribute.
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post field iterator', 'wpv-views' ),
			'label'          => __( 'Post field iterator', 'wpv-views' ),
			'post-selection' => true,
			'attributes' => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'field' => array(
							'label'       => __( 'Custom field', 'wpv-views' ),
							'type'        => 'suggest',
							'action'      => 'wpv_suggest_wpv_post_field_name',
							'description' => __( 'The name of the custom field to display', 'wpv-views' ),
							'required'   => true,
						),
						'iteration_boundaries'	=> array(
							'label'		=> __( 'Loop options', 'wpv-views' ),
							'type'		=> 'grouped',
							'fields'	=> array(
								'separator'	=> array(
									'pseudolabel'	=> __( 'Separator between values', 'wpv-views'),
									'type'			=> 'text',
									'default'		=> '',
									'description'	=> __( 'Sepatator between each field value.', 'wpv-views' ),
								),
								'start'	=> array(
									'pseudolabel'	=> __( 'Index to start', 'wpv-views'),
									'type'			=> 'number',
									'default'		=> '1',
									'description'	=> __( 'Defaults to 1.', 'wpv-views' ),
								),
								'end'	=> array(
									'pseudolabel'	=> __( 'Index to end', 'wpv-views'),
									'type'			=> 'number',
									'default'		=> '',
									'description'	=> __( 'No value means all the way until the last index.', 'wpv-views' ),
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
					'content' => array(
						'hidden' => true,
						'label' => __( 'Content of each iteration', 'wpv-views' ),
						'description' => __( 'This will be displayed on each iteration. The usual content is <code>[wpv-post-field name="field-name"]</code> where field-name is the custom field selected above.', 'wpv-views' )
					)
				),
			)
		);
		return $data;
	}


}
