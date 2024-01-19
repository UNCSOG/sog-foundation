<?php

/**
 * Class WPV_Shortcode_Post_Author_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Author_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-author shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-author'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-author shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		$data = array(
			'name'           => __( 'Post author', 'wpv-views' ),
			'label'          => __( 'Post author', 'wpv-views' ),
			'post-selection' => true,
			'attributes'     => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'format' => array(
							'label'       => __( 'Author information', 'wpv-views' ),
							'type'        => 'radio',
							'options'     => array(
								'name'            => __( 'Author name', 'wpv-views' ),
								'link'            => __( 'Author archive link', 'wpv-views' ),
								'url'             => __( 'Author archive URL', 'wpv-views' ),
								'meta'            => __( 'Author metadata', 'wpv-views' ),
								'profile_picture' => __( 'Author profile picture', 'wpv-views' ),
							),
							'default'     => 'name',
							'description' => __( 'Display this information about the current post author.', 'wpv-views' )
						),
						'meta' => array(
							'label'         => __( 'Author metadata', 'wpv-views'),
							'type'          => 'select',
							'default_force' => 'nickname',
							'options'       => array(
								'display_name'        => __( 'Author display name', 'wpv-views' ),
								'first_name'          => __( 'Author first name', 'wpv-views' ),
								'last_name'           => __( 'Author last name', 'wpv-views' ),
								'nickname'            => __( 'Author nickname', 'wpv-views' ),
								'user_nicename'       => __( 'Author nicename', 'wpv-views' ),
								'description'         => __( 'Author description', 'wpv-views' ),
								'user_login'          => __( 'Author login', 'wpv-views' ),
								'user_pass'           => __( 'Author password', 'wpv-views' ),
								'ID'                  => __( 'Author ID', 'wpv-views' ),
								'user_email'          => __( 'Author email', 'wpv-views' ),
								'user_url'            => __( 'Author URL', 'wpv-views' ),
								'user_registered'     => __( 'Author registered date', 'wpv-views' ),
								'user_activation_key' => __( 'Author activation key', 'wpv-views' ),
								'user_status'         => __( 'Author status', 'wpv-views' ),
								'jabber'              => __( 'Author jabber', 'wpv-views' ),
								'aim'                 => __( 'Author aim', 'wpv-views' ),
								'yim'                 => __( 'Author yim', 'wpv-views' ),
								'user_level'          => __( 'Author level', 'wpv-views' ),
							),
							'description'   => __( 'Display this metadata if that option was selected on the previous section', 'wpv-views' )
						),
						'profile-picture-size' => array(
							'label'       => __( 'Size', 'wpv-views' ),
							'type'        => 'text',
							'description' => __( 'Size of the post author\'s profile picture in pixels.', 'wpv-views' ),
						),
						'profile-picture-alt' => array(
							'label'       => __( 'Alternative text', 'wpv-views' ),
							'type'        => 'text',
							'description' => __( 'Alternative text for the post author\'s profile picture.', 'wpv-views' ),
						),
						'profile-picture-shape' => array(
							'label'       => __( 'Shape', 'wpv-views'),
							'type'        => 'select',
							'options'     => array(
								'circle' => __( 'Circle', 'wpv-views' ),
								'square' => __( 'Square', 'wpv-views' ),
								'custom' => __( 'Custom', 'wpv-views' ),
							),
							'default'     => 'circle',
							'description' => __( 'Display the post author\'s profile picture in this shape. For "custom" shape, custom CSS is needed for "wpv-profile-picture-shape-custom" CSS class.', 'wpv-views' ),
						),
						'profile-picture-default-url' => array(
							'label'       => __( 'Default URL', 'wpv-views' ),
							'type'        => 'text',
							'description' => __( 'Default image when there is no profile picture. Leave blank for the "Mystery Man".', 'wpv-views' )
						),
					),
				)
			)
		);
		return $data;
	}
	
	
}