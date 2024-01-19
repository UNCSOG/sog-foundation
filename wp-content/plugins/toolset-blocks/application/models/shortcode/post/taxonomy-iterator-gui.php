<?php

namespace OTGS\Toolset\Views\Models\Shortcode\Post;

/**
 * Taxonomy iterator shortcode GUI.
 *
 * @since 3.0.1
 */
class Taxonomy_Iterator_Gui extends \WPV_Shortcode_Base_GUI {

	/**
	 * @var \Toolset_Condition_Plugin_Types_Active
	 */
	private $types_condition;

	/**
	 * Constructor.
	 *
	 * @param \Toolset_Condition_Plugin_Types_Active $types_condition
	 * @since 3.0.1
	 */
	public function __construct( \Toolset_Condition_Plugin_Types_Active $types_condition = null ) {
		parent::__construct();
		$this->types_condition = ( null === $types_condition )
			? new \Toolset_Condition_Plugin_Types_Active()
			: $types_condition;
	}

	/**
	 * Register the shortcode in the GUI API.
	 *
	 * @param array
	 * @return array
	 * @since 3.0.1
	 */
	public function register_shortcode_data( $views_shortcodes ) {
		$views_shortcodes[ Taxonomy_Iterator::SHORTCODE_NAME ] = array(
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
			'name'           => __( 'Taxonomy iterator', 'wpv-views' ),
			'label'          => __( 'Taxonomy iterator', 'wpv-views' ),
			'post-selection' => true,
			'attributes' => array(
				'display-options' => array(
					'label'  => __( 'Display options', 'wpv-views' ),
					'header' => __( 'Display options', 'wpv-views' ),
					'fields' => array(
						'object_group'	=> array(
							'label'		=> __( 'Taxonomy and field', 'wpv-views' ),
							'type'		=> 'grouped',
							'fields'	=> array(
								'taxonomy' => array(
									'pseudolabel' => __( 'Taxonomy', 'wpv-views' ),
									'type' => 'select',
									'options' => $this->get_available_taxonomies(),
									'description' => __( 'The taxonomy to loop over', 'wpv-views' ),
									'required' => true,
								),
								'field' => array(
									'pseudolabel' => __( 'Term field', 'wpv-views' ),
									'type' => 'select',
									'options' => $this->get_available_term_fields(),
									'description' => __( 'The field to display for each term', 'wpv-views' ),
									'required'   => true,
								),
							),
						),
						'separator'	=> array(
							'label'	=> __( 'Separator between the field from each term', 'wpv-views'),
							'type' => 'text',
							'default' => '',
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

	/**
	 * Get the available taxonomies.
	 *
	 * @return array
	 * @since 3.0.1
	 */
	private function get_available_taxonomies() {
		$options = array();

		$taxonomies = get_taxonomies( '', 'objects' );
		$exclude_tax_slugs = array();
		$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
			if (
				in_array( $taxonomy_slug, $exclude_tax_slugs )
				|| ! $taxonomy->show_ui
			) {
				continue;
			}
			$options[ $taxonomy_slug ] = $taxonomy->label;
		}

		return $options;
	}

	/**
	 * Get the fields that can be used inside the terms loop.
	 *
	 * @return array
	 * @since 3.0.1
	 */
	private function get_available_term_fields() {
		$options = array();

		$term_native_data = array(
			'views|shortcode|single|wpv-taxonomy-title' => __( 'Taxonomy title', 'wpv-views' ),
			'views|shortcode|single|wpv-taxonomy-link' => __( 'Taxonomy link', 'wpv-views' ),
			'views|shortcode|single|wpv-taxonomy-url' => __( 'Taxonomy URL', 'wpv-views' ),
			'views|shortcode|single|wpv-taxonomy-slug' => __( 'Taxonomy slug', 'wpv-views' ),
			'views|shortcode|single|wpv-taxonomy-id' => __( 'Taxonomy ID', 'wpv-views' ),
			'views|shortcode|single|wpv-taxonomy-description' => __( 'Taxonomy description', 'wpv-views' ),
			'views|shortcode|single|wpv-taxonomy-post-count' => __( 'Taxonomy post count', 'wpv-views' ),
		);

		foreach ( $term_native_data as $value => $title ) {
			$options[ $value ] = $title;
		}

		if ( ! $this->types_condition->is_met() ) {
			return $options;
		}

		/**
		 * Types API filter to get termmeta field groups with their relevant fields.
		 *
		 * Originally created for the Types shortcodes GUI, handy to be used here.
		 *
		 * @see \OTGS\Toolset\Types\Controller\Cache\ShortcodeGenerator\ManagerBase::add_hooks
		 */
		$termmeta_field_groups = apply_filters( 'types_get_sg_' . \Toolset_Element_Domain::TERMS . '_meta_cache', array() );

		if ( empty( $termmeta_field_groups ) ) {
			return $options;
		}

		foreach ( $termmeta_field_groups as $termmeta_group ) {
			$group_fields = toolset_getarr( $termmeta_group, 'fields', array() );
			$group_name = toolset_getarr( $termmeta_group, 'name' );
			foreach( $group_fields as $field ) {
				$field_type = toolset_getnest( $field, array( 'parameters', 'metaType' ) );
				$field_slug = toolset_getnest( $field, array( 'parameters', 'termmeta' ) );
				$field_nature = toolset_getnest( $field, array( 'parameters', 'metaNature' ) );
				$field_name = toolset_getarr( $field, 'name' );
				$options[ 'types|' . $field_type . '|' . $field_nature . '|' . $field_slug ] = sprintf(
					__( '%s - %s', 'wpv-views' ),
					$group_name,
					$field_name
				);
			}
		}

		return $options;
	}

}
