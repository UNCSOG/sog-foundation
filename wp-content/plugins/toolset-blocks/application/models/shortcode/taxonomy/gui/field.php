<?php

namespace OTGS\Toolset\Views\Models\Shortcode\Taxonomy;

/**
 * Taxonomy term field shortcode GUI.
 *
 * @since 3.0.1
 */
class Field_Gui extends \WPV_Shortcode_Base_GUI {

	/**
	 * Register the shortcode in the GUI API.
	 *
	 * @param array
	 * @return array
	 * @since 3.0.1
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes[ Field::SHORTCODE_NAME ] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $views_shortcodes;
	}

	/**
	 * Get the shortcode attributes data.
	 *
	 * @return array
	 * @since 3.0.1
	 */
	public function get_shortcode_data() {
		$data = array(
			'name' => __( 'Taxonomy field', 'wpv-views' ),
			'label' => __( 'Taxonomy field', 'wpv-views' ),
			'attributes' => array(
				'display-options' => array(
					'label' => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'name' => array(
							'label' => __( 'Taxonomy field', 'wpv-views' ),
							'type' => 'suggest',
							'action' => 'wpv_suggest_wpv_taxonomy_field_name',
							'description' => __( 'The name of the field to display', 'wpv-views' ),
							'required' => true,
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
					),
				),
			),
		);
		return $data;
	}


}
