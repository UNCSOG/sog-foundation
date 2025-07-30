<?php

/**
 * Class WPV_Shortcode_Post_Date_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Date_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-date shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-date'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-date shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$default_format = get_option( 'date_format' );
		$data = array(
			'name'           => __( 'Post date', 'wpv-views' ),
			'label'          => __( 'Post date', 'wpv-views' ),
			'post-selection' => true,
			'attributes'     => array(
				'display-options' => array(
					'label'  => __('Display options', 'wpv-views'),
					'header' => __('Display options', 'wpv-views'),
					'fields' => array(
						'format' => array(
							'label'         => __( 'Date format', 'wpv-views'),
							'type'          => 'radio',
							'default'       => $default_format,
							'documentation' => '<a href="http://codex.wordpress.org/Formatting_Date_and_Time" target="_blank">' . __( 'WordPress Formatting Date and Time', 'wpv-views' ) . '</a>',
							'options' => array(
								$default_format => $default_format . ' - ' . date_i18n( $default_format ),
								'F j, Y g:i a'  => 'F j, Y g:i a - ' . date_i18n( 'F j, Y g:i a' ),
								'F j, Y'        => 'F j, Y - ' . date_i18n( 'F j, Y' ),
								'd/m/y'         => 'd/m/y - ' . date_i18n( 'd/m/y' ),
								'custom-combo' => array(
									'label'       => __( 'Custom', 'wpv-views' ),
									'type'        => 'text',
									'placeholder' => 'l, F j, Y',
								)
							),
						),
						'type' => array(
							'label'   => __( 'What to display', 'wpv-views' ),
							'type'    => 'radio',
							'default' => 'created',
							'options' => array(
								'created'  => __( 'Display the date when the post was created', 'wpv-views' ),
								'modified' => __( 'Display the date when the post was last modified', 'wpv-views' )
							)
						)
					),
				),
			),
		);
		return $data;
	}
	
	
}