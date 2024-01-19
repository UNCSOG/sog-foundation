<?php
/**
 * Helper for toolbar post forms
 *
 * @package Toolset/Forms
 */

namespace OTGS\Toolset\CRED\Model\Forms\Post;

use OTGS\Toolset\Common\Settings\BootstrapSetting;
use OTGS\Toolset\CRED\Controller\Forms\Post\Main as PostFormMain;
use OTGS\Toolset\CRED\Model\FormEditorToolbar\Helper as BaseHelper;
use OTGS\Toolset\CRED\Controller\FieldsControl\Db as FieldsControlDb;
use Toolset_Condition_Plugin_Types_Active;
use Toolset_Field_Type_Definition_Factory;

/**
 * Helper for the post forms content editor toolbar.
 *
 * Provides convenient methods for gathering relevant data, like fields for a given post type.
 *
 * @since 2.1
 */
class Helper extends BaseHelper {

	/**
	 * Post type to get fields for.
	 *
	 * @var string
	 *
	 * @since 2.1
	 */
	private $post_type = '';

	/**
	 * Post type object to get field for.
	 *
	 * @var null|\WP_Post_Type
	 *
	 * @since 2.1
	 */
	private $post_type_object = null;

	/**
	 * For testing purposes only
	 *
	 * @var Toolset_Condition_Plugin_Types_Active
	 *
	 * @since 2.1
	 */
	private $di_toolset_types_condition = null;

	/**
	 * Is post type from RFG
	 *
	 * @var bool
	 *
	 * @since 2.1
	 */
	private $is_post_type_from_rfg = false;


	/** @var \Toolset_Settings */
	private $toolset_settings;


	/**
	 * Constructor
	 *
	 * @param object $post_type_object Post Type object.
	 * @param Toolset_Condition_Plugin_Types_Active $di_toolset_types_condition Types Conditions.
	 * @param \Toolset_Settings|null $toolset_settings_di
	 */
	public function __construct( $post_type_object, $di_toolset_types_condition = null, \Toolset_Settings $toolset_settings_di = null ) {
		$this->post_type_object = $post_type_object;
		$this->post_type        = $this->post_type_object->name;
		$this->items            = array(
			'basic'              => array(),
			'taxonomy'           => array(),
			'meta'               => array(),
			'legacyParent'       => array(),
			'hierarchicalParent' => array(),
			'relationship'       => array(),
		);

		$this->di_toolset_types_condition = ( null === $di_toolset_types_condition )
			? new Toolset_Condition_Plugin_Types_Active()
			: $di_toolset_types_condition;

		$this->is_post_type_from_rfg = (
			property_exists( $this->post_type_object, \Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP )
			&& $this->post_type_object->{\Toolset_Post_Type_From_Types::DEF_IS_REPEATING_FIELD_GROUP}
		);

		$this->toolset_settings = $toolset_settings_di ?: \Toolset_Settings::get_instance();
	}

	/**
	 * Populate and return the list of items.
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	public function populate_items() {
		$this->populate_basic_fields();
		$this->populate_taxonomies();
		$this->populate_types_fields_by_post_type();
		$this->populate_non_types_fields_by_post_type();
		$this->populate_legacy_parents();
		$this->populate_hierarchical_parents();
		$this->populate_relationships();

		$this->add_label_option_to_fields();

		return $this->items;
	}

	/**
	 * Populate the list of basic fields.
	 *
	 * @since 2.1
	 */
	protected function populate_basic_fields() {
		$this->items['basic']['post_title'] = array(
			// translators: placeholder refers to Post Type singular name.
			'label'      => sprintf( __( '%s Title', 'wp-cred' ), $this->post_type_object->labels->singular_name ),
			'shortcode'  => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
			'attributes' => array(
				\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'post_title',
				'field'                               => 'post_title',
				'class'                               => 'form-control',
				'output'                              => 'bootstrap',
			),
			'options'    => $this->get_value_and_url_options(),
		);

		if ( post_type_supports( $this->post_type, 'editor' ) ) {
			$this->items['basic']['post_content'] = array(
				// translators: placeholder refers to Post Type singular name.
				'label'      => sprintf( __( '%s Content', 'wp-cred' ), $this->post_type_object->labels->singular_name ),
				'shortcode'  => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
				'attributes' => array(
					\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'post_content',
					'field'                               => 'post_content',
					'output'                              => 'bootstrap',
				),
				'options'    => $this->get_value_and_url_options(),
			);
		}

		if ( post_type_supports( $this->post_type, 'excerpt' ) ) {
			$this->items['basic']['post_excerpt'] = array(
				// translators: placeholder refers to Post Type singular name.
				'label'      => sprintf( __( '%s Excerpt', 'wp-cred' ), $this->post_type_object->labels->singular_name ),
				'shortcode'  => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
				'attributes' => array(
					\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'post_excerpt',
					'field'                               => 'post_excerpt',
					'class'                               => 'form-control',
					'output'                              => 'bootstrap',
				),
				'options'    => $this->get_value_and_url_options(),
			);
		}

		if ( post_type_supports( $this->post_type, 'thumbnail' ) ) {
			$this->items['basic']['_featured_image'] = array(
				'label'      => __( 'Featured Image', 'wp-cred' ),
				'shortcode'  => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
				'attributes' => array(
					\CRED_Form_Builder::SCAFFOLD_FIELD_ID => '_featured_image',
					'field'                               => '_featured_image',
					'output'                              => 'bootstrap',
				),
				'options' => $this->get_preview_options( 'image' ),
			);
		}

	}

	/**
	 * Populate the list of taxonomy fields.
	 *
	 * @since 2.1
	 */
	private function populate_taxonomies() {
		$taxonomy_objects = get_object_taxonomies( $this->post_type, 'objects' );

		foreach ( $taxonomy_objects as $taxonomy_slug => $taxonomy_data ) {
			if ( ! $taxonomy_data->public ) {
				continue;
			}
			if ( ! $taxonomy_data->show_ui ) {
				continue;
			}
			$this->items['taxonomy'][ $taxonomy_slug ] = array(
				'label'      => $taxonomy_data->label,
				'shortcode'  => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
				'attributes' => array(
					\CRED_Form_Builder::SCAFFOLD_FIELD_ID => $taxonomy_slug,
					'field'                               => $taxonomy_slug,
					'force_type'                          => 'taxonomy',
					'output'                              => 'bootstrap',
				),
				'options'    => array(),
			);
			if ( $taxonomy_data->hierarchical ) {
				$this->items['taxonomy'][ $taxonomy_slug ]['options']['display'] = array(
					'label'             => __( 'Display options', 'wp-cred' ),
					'type'              => 'radio',
					'options'           => array(
						'checkbox'    => __( 'Display taxonomy as a set of checkboxes', 'wp-cred' ),
						'select'      => __( 'Display taxonomy as a select dropdown', 'wp-cred' ),
						'multiselect' => __( 'Display taxonomy as a select dropdown with multiple selection', 'wp-cred' ),
					),
					'defaultForceValue' => 'checkbox',
				);
				$this->items['taxonomy'][ $taxonomy_slug ]['options']['add_new'] = array(
					'label'             => __( 'Add new', 'wp-cred' ),
					'type'              => 'radio',
					'options'           => array(
						'yes' => __( 'Let visitors create new terms for this taxonomy', 'wp-cred' ),
						'no'  => __( 'Do not let visitors add new terms', 'wp-cred' ),
					),
					'defaultForceValue' => 'yes',
				);
			} else {
				$this->items['taxonomy'][ $taxonomy_slug ]['attributes']['class']     = 'form-control';
				$this->items['taxonomy'][ $taxonomy_slug ]['options']['show_popular'] = array(
					'label'             => __( 'Show popular', 'wp-cred' ),
					'type'              => 'radio',
					'options'           => array(
						'yes' => __( 'Let visitors select popular terms from this taxonomy', 'wp-cred' ),
						'no'  => __( 'Do not offer popular terms', 'wp-cred' ),
					),
					'defaultForceValue' => 'yes',
				);
			}
		}
	}

	/**
	 * Get the attribute options for a given postmeta field based on its type.
	 *
	 * @param string $field_type Field Type.
	 * @return array
	 * @since 2.1
	 */
	private function get_field_options( $field_type ) {
		if ( in_array( $field_type, array( 'audio', 'file', 'image', 'video' ) ) ) {
			return $this->get_preview_options( $field_type );
		}
		if ( 'post' === $field_type ) {
			$sorting_and_author_options = $this->get_sorting_and_author_options(
				array(
					'select_text' => array(
						'label'             => __( 'Select text', 'wp-cred' ),
						'type'              => 'text',
						'defaultForceValue' => __( '--- not set ---', 'wp-cred' ),
					),
				),
				'order',
				'ordering'
			);
			return $this->get_value_and_url_options( $sorting_and_author_options );
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
	private function populate_types_fields_by_post_type() {
		// Only populate fields if Types is active.
		if ( ! $this->di_toolset_types_condition->is_met() ) {
			return;
		}

		$groups_by_post_type = array();
		$post_fields_group_factory = \Toolset_Field_Group_Post_Factory::get_instance();

		if (
			apply_filters( 'toolset_is_m2m_enabled', false )
			&& $this->is_post_type_from_rfg
		) {
			do_action( 'toolset_do_m2m_full_init' );
			$groups_for_rfg = $post_fields_group_factory->query_groups(
				array(
					'purpose'     => \Toolset_Field_Group_Post::PURPOSE_FOR_REPEATING_FIELD_GROUP,
					'post_status' => 'hidden',
				)
			);
			foreach ( $groups_for_rfg as $group_for_rfg ) {
				if (
					$group_for_rfg instanceof \Toolset_Field_Group_Post
					&& get_post_meta( $group_for_rfg->get_id(), \Types_Field_Group_Repeatable::OPTION_NAME_LINKED_POST_TYPE, true ) === $this->post_type_object->name
				) {
					$groups_by_post_type[] = $group_for_rfg;
				}
			}
		} else {
			$groups_by_post_type = $post_fields_group_factory->get_groups_by_post_type( $this->post_type_object->name );
		}

		if ( empty( $groups_by_post_type ) ) {
			return;
		}

		foreach ( $groups_by_post_type as $field_group ) {
			$fields_in_group = $field_group->get_field_definitions();

			foreach ( $fields_in_group as $field_definition ) {
				$field      = $field_definition->get_definition_array();
				$field_type = $field_definition->get_type()->get_slug();
				$this->items['meta'][ $field_definition->get_slug() ] = array(
					'groupName'    => $field_group->get_name(),
					'label'        => $field_definition->get_name(),
					'shortcode'    => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
					'requiredItem' => ( isset( $field['data']['validate']['required']['active'] ) && $field['data']['validate']['required']['active'] ),
					'type'         => $field_type,
					'isRepetitive' => $field_definition->get_is_repetitive(),
					'metaKey'      => $field_definition->get_meta_key(),
					'attributes'   => array(
						\CRED_Form_Builder::SCAFFOLD_FIELD_ID => $field_definition->get_slug(),
						'field'      => $field_definition->get_slug(),
						'force_type' => 'field',
						'class'      => $this->get_field_input_default_class( $field_definition ),
						'output'     => 'bootstrap',
					),
					'icon_class'   => $field_definition->get_type()->get_icon_classes(),
					'options'      => $this->get_field_options( $field_type ),
				);
			}
		}

	}


	private function get_field_input_default_class( \Toolset_Field_Definition $field_definition ) {
		if( $this->toolset_settings->get_bootstrap_version_numeric() !== BootstrapSetting::NUMERIC_BS4 ) {
			return 'form-control'; // default until BS4 support - do not affect existing sites
		}
		switch( $field_definition->get_type_slug() ) {
			case Toolset_Field_Type_Definition_Factory::CHECKBOX:
			case Toolset_Field_Type_Definition_Factory::CHECKBOXES:
			case Toolset_Field_Type_Definition_Factory::RADIO:
				return 'form-check-input';
			case Toolset_Field_Type_Definition_Factory::AUDIO:
			case Toolset_Field_Type_Definition_Factory::FILE:
			case Toolset_Field_Type_Definition_Factory::IMAGE:
			case Toolset_Field_Type_Definition_Factory::VIDEO:
				// It would be ideal having a "file browser" here, but for now let's just stick with not breaking
				// the inputs as they are with BS4.
				// See https://getbootstrap.com/docs/4.0/components/forms/#file-browser
				// See https://onthegosystems.myjetbrains.com/youtrack/issue/cred-1533#focus=streamItem-102-352220.0-0
				return '';
			default:
				return 'form-control';
		}
	}

	/**
	 * Populates non types fields by post type
	 */
	private function populate_non_types_fields_by_post_type() {
		$fields_control_db = new FieldsControlDb();
		$cred_fields = $fields_control_db->get_fields_per_post_type( $this->post_type );

		foreach ( $cred_fields as $field ) {
			if ( toolset_getarr( $field, '_cred_ignore', false ) ) {
				continue;
			}
			$this->items['meta'][ $field['slug'] ] = array(
				'groupName'    => __( 'Non-Toolset fields under Forms control', 'wp-cred' ),
				'label'        => $field['slug'],
				'shortcode'    => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
				'requiredItem' => ( isset( $field['data']['validate']['required']['active'] ) && $field['data']['validate']['required']['active'] ),
				'type'         => $field['type'],
				'isRepetitive' => false,
				'metaKey'      => $field['slug'],
				'attributes'   => array(
					\CRED_Form_Builder::SCAFFOLD_FIELD_ID => $field['slug'],
					'field'                               => $field['slug'],
					'force_type'                          => 'field',
					'class'                               => 'form-control',
					'output'                              => 'bootstrap',
				),
				'options' => $this->get_field_options( $field['type'] ),
			);
		}
	}

	/**
	 * Populate the list of legacy parent fields.
	 *
	 * @since 2.1
	 *
	 * @todo Review sorting and author filtering, as well as value and urlparam options
	 */
	private function populate_legacy_parents() {
		if ( ! $this->di_toolset_types_condition->is_met() ) {
			return;
		}

		if ( apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}

		$wpcf_custom_types = get_option( 'wpcf-custom-types' );
		if ( empty( $wpcf_custom_types ) ) {
			// no cpts registered by types.
			return;
		}

		$maybe_relationship_belongs_data = toolset_getnest( $wpcf_custom_types, array( $this->post_type, 'post_relationship', 'belongs' ) );
		if ( ! empty( $maybe_relationship_belongs_data ) ) {
			// get parents defined via 'belongs' relationship.
			foreach ( $wpcf_custom_types[ $this->post_type ]['post_relationship']['belongs'] as $post_type_slug => $belong ) {
				if ( $belong ) {
					$this->items['legacyParent'][ "_wpcf_belongs_{$post_type_slug}_id" ] = array(
						// translators: placeholder refers to Post Type name.
						'label' => sprintf( __( '%s Parent', 'wp-cred' ), $post_type_slug ),
						'shortcode' => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
						'attributes' => array(
							\CRED_Form_Builder::SCAFFOLD_FIELD_ID => "_wpcf_belongs_{$post_type_slug}_id",
							'field' => "_wpcf_belongs_{$post_type_slug}_id",
							'class' => 'form-control',
							'output' => 'bootstrap',
						),
					);
				}
			}
		}

		// get parents defined via 'has' relationship (reverse).
		foreach ( $wpcf_custom_types as $post_type_slug => $post_type_data ) {
			$maybe_relationship_has_data = toolset_getnest( $post_type_data, array( 'post_relationship', 'has', $this->post_type ) );
			if ( ! empty( $maybe_relationship_has_data ) ) {
				$this->items['legacyParent'][ "_wpcf_belongs_{$post_type_slug}_id" ] = array(
					// translators: placeholder refers to Post Type name.
					'label'      => sprintf( __( '%s Parent', 'wp-cred' ), $post_type_slug ),
					'shortcode'  => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
					'attributes' => array(
						\CRED_Form_Builder::SCAFFOLD_FIELD_ID => "_wpcf_belongs_{$post_type_slug}_id",
						'field'  => "_wpcf_belongs_{$post_type_slug}_id",
						'class'  => 'form-control',
						'output' => 'bootstrap',
					),
					'options' => array(
						'select_text' => array(
							'label'             => __( 'Select text', 'wp-cred' ),
							'type'              => 'text',
							'defaultForceValue' => __( '--- not set ---', 'wp-cred' ),
						),
						'order' => array(
							'label'        => __( 'Order', 'wp-cred' ),
							'type'         => 'radio',
							'options'      => array(
								'title' => __( 'Title', 'wp-cred' ),
								'ID'    => __( 'ID', 'wp-cred' ),
								'date'  => __( 'Date', 'wp-cred' ),
							),
							'defaultValue' => 'title',
						),
						'ordering' => array(
							'label'        => __( 'Ordering', 'wp-cred' ),
							'type'         => 'radio',
							'options'      => array(
								'asc'  => __( 'ASC', 'wp-cred' ),
								'desc' => __( 'DESC', 'wp-cred' ),
							),
							'defaultValue' => 'asc',
						),
						'author' => array(
							'label'        => __( 'Filtering', 'wp-cred' ),
							'type'         => 'radio',
							'options'      => array(
								'$current' => __( 'Current', 'wp-cred' ),
								''         => __( 'Any', 'wp-cred' ),
							),
							'defaultValue' => '',
						),
					),
				);
			}
		}

	}

	/**
	 * Populate the list of hierarchical parent fields.
	 *
	 * @since 2.1
	 *
	 * @todo Review sorting and author filtering
	 */
	private function populate_hierarchical_parents() {
		if ( ! is_post_type_hierarchical( $this->post_type ) ) {
			return;
		}

		if ( ! post_type_supports( $this->post_type, 'page-attributes' ) ) {
			return;
		}

		$options = array(
			'select_text' => array(
				'label'             => __( 'Select text', 'wp-cred' ),
				'type'              => 'text',
				'defaultForceValue' => __( '--- not set ---', 'wp-cred' ),
			),
		);
		$options = $this->get_sorting_and_author_options( $options, 'order', 'ordering' );
		$options = $this->get_value_and_url_options( $options );

		$this->items['hierarchicalParent']['post_parent'] = array(
			// translators: Placeholder referes to a post type name.
			'label'      => sprintf( __( '%s Parent', 'wp-cred' ), $this->post_type_object->labels->singular_name ),
			'shortcode'  => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
			'attributes' => array(
				\CRED_Form_Builder::SCAFFOLD_FIELD_ID => 'post_parent',
				'field'                               => 'post_parent',
				'class'                               => 'form-control',
				'output'                              => 'bootstrap',
			),
			'options'    => $options,
		);
	}

	/**
	 * Populate the list of relationship parent fields.
	 *
	 * @since 2.1
	 */
	private function populate_relationships() {
		if ( ! $this->di_toolset_types_condition->is_met() ) {
			return;
		}

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return;
		}

		do_action( 'toolset_do_m2m_full_init' );

		$relationships = array();
		$relationship_query = new \Toolset_Relationship_Query_V2();

		if ( $this->is_post_type_from_rfg ) {
			$relationship_query_result = $relationship_query
				->add( $relationship_query->origin( \Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD ) )
				->add( $relationship_query->has_domain_and_type( $this->post_type, \Toolset_Element_Domain::POSTS, new \Toolset_Relationship_Role_Child() ) )
				->add( $relationship_query->is_active( true ) )
				->get_results();
		} else {
			$relationship_query_result = $relationship_query
				->add( $relationship_query->has_domain_and_type( $this->post_type, \Toolset_Element_Domain::POSTS ) )
				->add( $relationship_query->is_active( true ) )
				->get_results();
		}

		foreach ( $relationship_query_result as $relationship ) {
			if ( $relationship->get_cardinality()->is_many_to_many() ) {
				continue;
			}

			$parent_post_types = $relationship->get_parent_type()->get_types();
			$child_post_types = $relationship->get_child_type()->get_types();

			if (
				(
					in_array( $this->post_type, $parent_post_types )
					&& $relationship->get_cardinality()->is_one_to_many()
				)
				|| (
					in_array( $this->post_type, $child_post_types )
					&& $relationship->get_cardinality()->is_many_to_one()
				)
			) {
				continue;
			}

			$relationship_label = $relationship->get_display_name();
			if ( $this->is_post_type_from_rfg ) {
				$parent_post_type_object = get_post_type_object( $parent_post_types[0] );
				$relationship_label = $parent_post_type_object->labels->singular_name;
			}

			$field_value = '@' . $relationship->get_slug() . '.' . (
					in_array( $this->post_type, $parent_post_types )
					? \Toolset_Relationship_Role::CHILD
					: \Toolset_Relationship_Role::PARENT
				);
			$this->items['relationship'][ $relationship->get_slug() ] = array(
				'label' => $relationship_label,
				'shortcode' => PostFormMain::SHORTCODE_NAME_FORM_FIELD,
				'attributes' => array(
					\CRED_Form_Builder::SCAFFOLD_FIELD_ID => $field_value,
					'field' => $field_value,
					'class' => 'form-control',
					'output' => 'bootstrap',
				),
				'requiredItem' => $this->is_post_type_from_rfg,
				'options' => $this->get_value_and_url_options(
					$this->get_sorting_and_author_options(
						array(
							'select_text' => array(
								'label' => __( 'Select text', 'wp-cred' ),
								'type'  => 'text',
								'defaultForceValue' => __( '--- not set ---', 'wp-cred' ),
							),
							'required' => array(
								/* translators: Label of the option to set as required the field to set a related post in a frontend post form */
								'label' => __( 'Should this field be required?', 'wp-cred' ),
								'type'  => 'radio',
								'options' => $this->is_post_type_from_rfg
									? array(
										/* translators: Option to set as required the field to set the parent of a repeatable field group in a frontend post form */
										'true' => sprintf(
												__( 'Required - all repeatable field groups need to be related to a %s', 'wp-cred' ),
												$relationship_label
											)
										)
									: array(
										/* translators: Option to not set as required the field to set a related post in a frontend post form */
										'false' => __( 'Not required', 'wp-cred' ),
										/* translators: Option to set as required the field to set a related post in a frontend post form */
										'true' => __( 'Required', 'wp-cred' ),
									),
								'defaultValue' => $this->is_post_type_from_rfg
									? 'true'
									: 'false',
							),
						),
						'order',
						'ordering'
					)
				),
			);
		}
	}

}
