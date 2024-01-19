<?php

/**
 * Class WPV_Shortcode_Post_Next_Link_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Next_Link_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-next-link shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-next-link'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-next-link shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post next link', 'wpv-views' ),
			'label'          => __( 'Post next link', 'wpv-views' ),
			'post-selection' => true,
			'attributes' => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'format' => array(
							'type'        => 'text',
							'label'       => __( 'Format', 'wpv-views' ),
							'description' => __( 'The link anchor format. Should contain \'%%LINK%%\' in order to display a link, otherwise it will create plain text. Default \'%%LINK%% &raquo;\'.', 'wpv-views' ),
							'default'     => '%%LINK%% &raquo;',
						),
						'link' => array(
							'type'        => 'text',
							'label'       => __( 'Link', 'wpv-views' ),
							'description' => __( 'The link permalink format. Can contain \'%%TITLE%%\' for the next post title or \'%%DATE%%\' for the next post date. Default \'%%TITLE%%\'.', 'wpv-views' ),
							'default'     => '%%TITLE%%',
						),
					),
				),
			)
		);

		$default_context = 'wpv-post-next-link';
		/** This filter is documented in application/models/shortcode/post/previous_link_gui.php */
		$data = apply_filters( 'wpv_filter_wpv_shortcodes_gui_wpml_context_data' , $data, $default_context );

		return $data;
	}
}
