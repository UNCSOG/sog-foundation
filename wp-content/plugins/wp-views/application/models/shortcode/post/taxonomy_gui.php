<?php

/**
 * Class WPV_Shortcode_Post_Taxonomy_GUI
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Taxonomy_GUI extends WPV_Shortcode_Base_GUI {

	/**
	 * Register the wpv-post-taxonomy shortcode in the GUI API.
	 *
	 * @param $views_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes['wpv-post-taxonomy'] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}

	/*
	 * Get the wpv-post-taxonomy shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since 2.5.0
	 */
	public function get_shortcode_data( $parameters = array(), $overrides = array() ) {
		$data = array(
			'post-selection' => true,
			'attributes' => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'format' => array(
							'label'   => __(  'Display format', 'wpv-views' ),
							'type'    => 'radio',
							'options' => array(
								'link'        => __( 'Link to term archive page', 'wpv-views' ),
								'url'         => __( 'URL of term archive page', 'wpv-views' ),
								'name'        => __( 'Term name', 'wpv-views' ),
								'id' => __( 'Term ID', 'wpv-views' ),
								'description' => __( 'Term description', 'wpv-views' ),
								'slug'        => __( 'Term slug', 'wpv-views' ),
								'count'       => __( 'Term post count', 'wpv-views' ),
								//'text' => __('Term related text', 'wpv-views'),
							),
							'default' => 'link',
						),
						'show' => array(
							'label'   => __( 'Anchor text when linking to the term archive page ', 'wpv-views' ),
							'type'    => 'select',
							'options' => array(
								'name'        => __( 'Term name', 'wpv-views' ),
								'description' => __( 'Term description', 'wpv-views' ),
								'slug'        => __( 'Term slug', 'wpv-views' ),
								'count'       => __( 'Number of terms', 'wpv-views' ),
							),
							'default' => 'name',
						),
						'separator' => array(
							'label'   => __( 'Separator between terms', 'wpv-views'),
							'type'    => 'text',
							'default' => ', ',
						),
						'order' => array(
							'label'   => __( 'Order ', 'wpv-views'),
							'type'    => 'radio',
							'options' => array(
								'asc'  => __('Ascending', 'wpv-views'),
								'desc' => __('Descending', 'wpv-views'),
							),
							'default' => 'asc',
						),
					),
				),
			),
		);

		$dialog_label = __( 'Post taxonomy', 'wpv-views' );
		$dialog_target = false;

		if ( isset( $parameters['attributes']['type'] ) ) {
			$dialog_target = $parameters['attributes']['type'];
		}
		if ( isset( $overrides['attributes']['type'] ) ) {
			$dialog_target = $overrides['attributes']['type'];
		}

		if ( $dialog_target ) {
			$dialog_label = $this->get_taxonomy_title( $dialog_target );
		}

		$data['name']	= $dialog_label;
		$data['label']	= $dialog_label;

		return $data;
	}

	/**
	 * Maybe get a taxonomy label based on its slug.
	 *
	 * @param $taxonomy_slug string|bool
	 *
	 * @return string
	 *
	 * @since 2.5.0
	 */
	private function get_taxonomy_title( $taxonomy_slug ) {

		$title = __( 'Post taxonomy', 'wpv-views' );

		$taxonomy_object = get_taxonomy( $taxonomy_slug );
		if ( $taxonomy_object ) {
			$title = $taxonomy_object->label;
		}

		return $title;

	}


}
