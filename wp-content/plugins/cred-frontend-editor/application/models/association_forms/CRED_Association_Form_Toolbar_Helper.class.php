<?php

use OTGS\Toolset\CRED\Model\FormEditorToolbar\Helper;

/**
 * Helper for the association forms content editor toolbar.
 *
 * Provides convenient methods for gathering relevant data, like fields for a given relationship.
 *
 * @since 2.1
 */
class CRED_Association_Form_Toolbar_Helper extends Helper {

	/**
	 * Relationship to get field for.
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
    private $relationship = '';

    /**
     * Relationship definition to get field for.
     *
     * @var null|Toolset_Relationship_Definition
     *
     * @since 2.1
     */
	private $relationship_definition = null;

    function __construct( $relationship = '' ) {
		$this->relationship = $relationship;
		$this->items = array(
			'roles' => array(),
			'meta' => array()
		);

		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$this->relationship_definition = $relationship_repository->get_definition( $this->relationship );
    }

    /**
     * Populate and return the list of items.
     *
     * @return array
     *
     * @since 2.1
     */
	public function populate_items() {
		if ( $this->relationship_definition ) {
			$this->populate_basic_fields();
			$this->populate_relationship_types_fields();
		}
		return $this->items;
    }

    /**
     * Populate the list of basic fields.
     *
     * @since 2.1
     */
    protected function populate_basic_fields() {
		$parent_types = $this->relationship_definition->get_parent_type()->get_types();
		$parent_type = $parent_types[0];
		$parent_type_object = get_post_type_object( $parent_type );
		$child_types = $this->relationship_definition->get_child_type()->get_types();
		$child_type = $child_types[0];
		$child_type_object = get_post_type_object( $child_type );

		$this->items['roles'] = array(
			'parent' => array(
				'label' => $parent_type_object->label,
				'shortcode' => CRED_Shortcode_Association_Role::SHORTCODE_NAME,
				'requiredItem' => true,
				'attributes' => array(
					'role' => Toolset_Relationship_Role::PARENT,
					\CRED_Form_Builder::SCAFFOLD_FIELD_ID => Toolset_Relationship_Role::PARENT
				),
				'options' => $this->get_role_options(),
				'fieldType' => 'relationship',
			),
			'child' => array(
				'label' => $child_type_object->label,
				'shortcode' => CRED_Shortcode_Association_Role::SHORTCODE_NAME,
				'requiredItem' => true,
				'attributes' => array(
					'role' => Toolset_Relationship_Role::CHILD,
					\CRED_Form_Builder::SCAFFOLD_FIELD_ID => Toolset_Relationship_Role::CHILD
				),
				'options' => $this->get_role_options(),
				'fieldType' => 'relationship',
			)
		);
	}

	/**
	 * Get the attribute options for a given meta field based on its type.
	 *
	 * @param string $field_type Field Type.
	 * @return array
	 * @since 2.4
	 */
	private function get_field_options( $field_type ) {
		if ( in_array( $field_type, array( 'audio', 'file', 'image', 'video' ) ) ) {
			return $this->get_preview_options( $field_type );
		}
		if ( in_array( $field_type, $this->field_types_without_value_and_url_options, true ) ) {
			return array();
		}
		return $this->get_value_and_url_options();
	}

    /**
     * Populate the list of Types fields.
     *
     * @since 2.1
     */
    private function populate_relationship_types_fields() {
		if ( empty( $this->relationship ) ) {
			return;
		}

		if ( $this->relationship_definition->has_association_field_definitions() ) {
			$association_fields_definitions = $this->relationship_definition->get_association_field_definitions();
			foreach ( $association_fields_definitions as $field_definition ) {
				$definition_type = $field_definition->get_type();
				$field = $field_definition->get_definition_array();
				$field_type = $field_definition->get_type()->get_slug();
				$this->items['meta'][ $field_definition->get_slug() ] = array(
					'label' => $field_definition->get_name(),
					'shortcode' => CRED_Shortcode_Association_Field::SHORTCODE_NAME,
					'requiredItem' => ( isset( $field['data']['validate']['required']['active'] ) && $field['data']['validate']['required']['active'] ),
					'attributes' => array(
						'name' => $field_definition->get_slug(),
						\CRED_Form_Builder::SCAFFOLD_FIELD_ID => $field_definition->get_slug()
					),
					'icon_class' => $definition_type ? $definition_type->get_icon_classes() : '',
					'options' => $this->get_field_options( $field_type ),
				);
			}
		}
    }

	/**
	 * Get the options for each role selector shortcode.
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	private function get_role_options() {
		return array(
			'sortingCombo' => array(
				'label' => __( 'Sorting', 'wp-cred' ),
				'type'   => 'group',
				'fields' => array(
					'orderby' => array(
						'type' => 'select',
						'options'      => array(
							'ID' => __( 'Order options by post ID', 'wp-cred' ),
							'date' => __( 'Order options by post date', 'wp-cred' ),
							'title' => __( 'Order options by post title', 'wp-cred' )
						),
						'defaultValue' => 'ID'
					),
					'order' => array(
						'type' => 'select',
						'options'      => array(
							'DESC' => __( 'Descending', 'wp-cred' ),
							'ASC' => __( 'Ascending', 'wp-cred' )
						),
						'defaultValue' => 'DESC'
					)
				)
			),
			'filteringCombo' => array(
				'label' => __( 'Filtering', 'wp-cred' ),
				'type'   => 'group',
				'fields' => array(
					'author' => array(
						'type' => 'select',
						'options'      => array(
							'' => __( 'Options by any author', 'wp-cred' ),
							'$current' => __( 'Options only by the current visitor', 'wp-cred' )
						),
						'defaultValue' => ''
					)
				),
				'description' => __( 'Include options created by any or a specific author. Not logged in visitors might not be able to submit the form.', 'wp-cred' )
			),
			'stylingCombo' => array(
				'type'   => 'group',
				'fields' => array(
					'class' => array(
						'label' => __( 'Additional classnames', 'wp-cred' ),
						'type'        => 'text'
					),
					'style' => array(
						'label' => __( 'Additional inline styles', 'wp-cred' ),
						'type'        => 'text'
					)
				),
				'description' => __( 'Include specific classnames in the selector, or add your own inline styles.', 'wp-cred' )
			)
		);
	}

}
