<?php

/**
 * Class WPV_Shortcode_Post_Featured_Image_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Featured_Image_GUI extends WPV_Shortcode_Base_GUI {
	
	/**
	 * Register the wpv-post-featured-image shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-featured-image'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}
	
	/*
	 * Get the wpv-post-featured-image shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data() {
		
		$size_options = array(
			'full' => __('Original image', 'wpv-views')
		);
		$template = '%s - (%dx%d)';
		$defined_sizes = array(
			'thumbnail' => __('Thumbnail', 'wpv-views'),
			'medium' => __('Medium', 'wpv-views'),
			'large' => __('Large', 'wpv-views')
		);
		foreach ( $defined_sizes as $ds_key => $ds_label ) {
			$size_options[ $ds_key ] = sprintf(
				$template,
				$ds_label,
				get_option(sprintf('%s_size_w', $ds_key)),
				get_option(sprintf('%s_size_h', $ds_key))
			);
		}
		global $_wp_additional_image_sizes;
		if ( ! empty( $_wp_additional_image_sizes) ) {
			foreach ( $_wp_additional_image_sizes as $key => $value ) {
				if ( 'post-thumbnail' == $key ) {
					continue;
				}
				$size_options[ $key ] = sprintf(
					$template,
					$key,
					$value['width'],
					$value['height']
				);
			}
		}

		/**
		 * Custom size support
		 *
		 * @since 2.2
		 */
		$size_options['custom'] = __( 'Custom size...', 'wpv-views' );
		
		$data = array(
			'name'           => __( 'Post featured image', 'wpv-views' ),
			'label'          => __( 'Post featured image', 'wpv-views' ),
			'post-selection' => true,
			'attributes' => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'size' => array(
							'label'		=> __( 'Featured image size', 'wpv-views' ),
							'type'		=> 'select',
							'options'	=> $size_options,
							'default'	=> 'thumbnail',
						),
						'dimensions_group'	=> array(
							'type'		=> 'grouped',
							'fields'	=> array(
								'width' => array(
									'pseudolabel'	=> __( 'Featured image width', 'wpv-views' ),
									'type'			=> 'text',
									'description'	=> __( 'Custom width of image in pixels.', 'wpv-views' )
								),
								'height' => array(
									'pseudolabel'	=> __( 'Featured image height', 'wpv-views' ),
									'type'			=> 'text',
									'description'	=> __( 'Custom height of image in pixels.', 'wpv-views' )
								)
							)
						),
						'crop' => array(
							'label'		=> __( 'Proportion', 'wpv-views' ),
							'type'		=> 'radio',
							'options'	=> array(
								'false'	=> __( 'Keep original proportion', 'wpv-views' ),
								'true'	=> __( 'Crop to exact dimensions', 'wpv-views' )
							),
							'default' => 'false',
						),
						'crop_group'	=> array(
							'type'			=> 'grouped',
							'fields'		=> array(
								'crop_horizontal' => array(
									'pseudolabel'	=> __( 'Horizontal crop position', 'wpv-views' ),
									'type'			=> 'select',
									'options'		=> array(
										'left'		=> __( 'Left', 'wpv-views' ),
										'center'	=> __( 'Center', 'wpv-views' ),
										'right'		=> __( 'Right', 'wpv-views' )
									),
									'default'		=> 'center',
								),
								'crop_vertical'	=> array(
									'pseudolabel'	=> __( 'Vertical crop position', 'wpv-views' ),
									'type'			=> 'select',
									'options'		=> array(
										'top'		=> __( 'Top', 'wpv-views' ),
										'center'	=> __( 'Center', 'wpv-views' ),
										'bottom'	=> __( 'Bottom', 'wpv-views' )
									),
									'default'		=> 'center',
								),
							)
						),
						'output'	=> array(
							'label'		=> __( 'What to display', 'wpv-views' ),
							'type'		=> 'select',
							'options'	=> array(
								'img'			=> __( 'Image HTML tag', 'wpv-views' ),
								'url'			=> __( 'URL of the image', 'wpv-views' ),
								'title'			=> __( 'Title of the image', 'wpv-views' ),
								'caption'		=> __( 'Caption of the image', 'wpv-views' ),
								'description'	=> __( 'Description of the image', 'wpv-views' ),
								'alt'			=> __( 'ALT text for the image', 'wpv-views' ),
								'author'		=> __( 'Author of the image', 'wpv-views' ),
								'date'			=> __( 'Date of the image', 'wpv-views' ),
								'id'			=> __( 'ID of the image', 'wpv-views' ),
							),
							'default'	=> 'img',
						),
						'class'		=> array(
							'label'			=> __( 'Class', 'wpv-views'),
							'type'			=> 'text',
							'description'	=> __( 'Space-separated list of classnames that will be added to the image HTML tag.', 'wpv-views' ),
							'placeholder'	=> 'class1 class2',
						),
						/*
						'attr' => array(
							'type' => 'text',
							'description' => __('Expects a query-string-like value : attr=”title=a&alt=b&classname=c” will add those attributes to the img HTML tag', 'wpv-views'),
							'label' => __('Attributes', 'wpv-views'),
						),
						*/
					),
				),
			)
		);
		return $data;
	}
	
	
}