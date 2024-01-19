<?php

/**
 * Class CRED_Field class handler of field and its attributes
 */
class CRED_Field extends CRED_Field_Abstract {

	private $cred_field_shortcode_attribute_filter;

	/**
	 * CRED_Field constructor.
	 *
	 * @param array $atts
	 * @param CRED_Form_Rendering $cred_form_rendering
	 * @param CRED_Form_Builder_Helper $formHelper
	 * @param CRED_Form_Data $formData
	 * @param CRED_Translate_Field_Factory $translate_field_factory
	 */
	public function __construct( $atts, CRED_Form_Rendering $cred_form_rendering, CRED_Form_Builder_Helper $formHelper, CRED_Form_Data $formData, CRED_Translate_Field_Factory $translate_field_factory, CRED_Field_Shortcode_Attribute_Filter $cred_field_shortcode_attribute_filter = null ) {
		parent::__construct( $atts, $cred_form_rendering, $formHelper, $formData, $translate_field_factory );
		if ( null === $this->cred_field_shortcode_attribute_filter ) {
			$this->cred_field_shortcode_attribute_filter = CRED_Field_Shortcode_Attribute_Filter::get_instance();
		}
	}

	/**
	 * Return field object array elaborating relative shortcode attributes
	 * It is used by CRED_Form_Rendering class in order to display the field
	 *
	 * @return string
	 */
	public function get_field() {
		$formHelper = $this->_formHelper;
		$form = $this->_formData;

		$filtered_attributes = $this->cred_field_shortcode_attribute_filter->get_filtered_attributes( $this->_atts, $formHelper );
		$filtered_attributes[ 'html_form_id' ] = $this->cred_form_rendering->html_form_id;

		$allowed_fields_type = array(
			'form_fields',
			'user_fields',
			'extra_fields',
			'custom_fields',
			'post_fields',
			'parents',
			'hierarchical_parents',
			'relationships',
			'post_reference_fields',
			'taxonomies',
		);

		$value = $filtered_attributes[ 'value' ];
		$type = $filtered_attributes[ 'type' ];
		$taxonomy = $filtered_attributes[ 'taxonomy' ];
		$field_name = $filtered_attributes[ 'field' ];
		$force_type = $filtered_attributes[ 'force_type' ];

		//Special Taxonomy case
		if ( $taxonomy
			&& array_key_exists( 'taxonomies', CRED_StaticClass::$out[ 'fields' ] )
			&& is_array( CRED_StaticClass::$out[ 'fields' ][ 'taxonomies' ] )
			&& in_array( $taxonomy, array_keys( CRED_StaticClass::$out[ 'fields' ][ 'taxonomies' ] ) )
			&& in_array( $type, array( 'show_popular', 'add_new' ) )
			&& (
				(
					$type == 'show_popular'
					&& ! CRED_StaticClass::$out[ 'fields' ][ 'taxonomies' ][ $taxonomy ][ 'hierarchical' ]
				)
				|| (
					$type == 'add_new'
					&& CRED_StaticClass::$out[ 'fields' ][ 'taxonomies' ][ $taxonomy ][ 'hierarchical' ]
				)
			)
		) {
			// add a placeholder for the 'show_popular' or 'add_new' buttons.
			// the real buttons will be copied to this position via js
			// added data-label text from value shortcode attribute
			switch ( $type ) {
				case 'show_popular':
					// BS4 compatibility
					return '<div class="btn btn-secondary js-taxonomy-button-placeholder" data-taxonomy="' . esc_attr( $taxonomy ) . '" data-label="' . esc_attr( $value ) . '" style="display:none"></div>';
				case 'add_new':
					// BS4 compatibility
					return '<div class="btn btn-secondary js-taxonomy-hierarchical-button-placeholder" data-taxonomy="' . esc_attr( $taxonomy ) . '" data-label="' . esc_attr( $value ) . '" style="display:none"></div>';
				default:
					return '';
			}
		}

		switch ( $field_name ) {
			case 'form_messages':
				$form_action_factory = new \OTGS\Toolset\CRED\Controller\FormAction\Factory();
				$form_data = $formHelper->get_form_data();
				$message_controller = $form_action_factory->get_message_controller_by_form_type( $form_data->getForm()->post_type );

				$post_not_saved_singular = str_replace( "%PROBLEMS_UL_LIST", "", $message_controller->get_message_by_id( $form_data, 'post_not_saved_singular' ) );
				$post_not_saved_plural = str_replace( "%PROBLEMS_UL_LIST", "", $message_controller->get_message_by_id( $form_data, 'post_not_saved_plural' ) );

				$class = 'wpt-top-form-error wpt-form-error wpt-form-error-wrapper alert alert-danger';
				$class .= ' ' . toolset_getarr( $filtered_attributes, 'class', '' );

				return '<div id="wpt-form-message-' . $form->getForm()->ID . '"
			              data-message-single="' . esc_attr( $post_not_saved_singular ) . '"
			              data-message-plural="' . esc_attr( $post_not_saved_plural ) . '"
			              style="display:none;" class="' . esc_attr( $class ) . '"></div><!CRED_ERROR_MESSAGE!>';
				break;

			default:
				if ( ! empty( $force_type )
					&& $force_type === 'taxonomy'
				) {
					$field_type = 'taxonomies';
				} else {
					$field_type = '';
					foreach ( $allowed_fields_type as $allowed_field_type ) {
						if ( array_key_exists( $allowed_field_type, CRED_StaticClass::$out[ 'fields' ] )
							&& is_array( CRED_StaticClass::$out[ 'fields' ][ $allowed_field_type ] )
							&& in_array( $field_name, array_keys( CRED_StaticClass::$out[ 'fields' ][ $allowed_field_type ] ) )
						) {
							$field_type = $allowed_field_type;
							break;
						}
					}
				}
				break;
		}

		$class_name = $this->get_field_command_class_name_by_field_type( $field_type );
		$field_object = $this->get_field_object( $class_name, $filtered_attributes, $form, $formHelper );

		if ( $field_object ) {
			return $this->cred_form_rendering->renderField( $field_object );
		} elseif ( current_user_can( 'manage_options' ) ) {
			return sprintf(
				'<p class="alert">%s</p>', sprintf(
					__( 'There is a problem with %s field. Please check CRED form.', 'wp-cred' ), $field_name
				)
			);
		}

		return '';
	}

	/**
	 * Create class name by field type
	 *
	 * @param $field_type
	 *
	 * @return string
	 */
	public function get_field_command_class_name_by_field_type( $field_type ) {
		$field_type_name = str_replace( "_", " ", $field_type );
		$field_type_name = ucwords( $field_type_name );
		$prefix_command_class_name = "CRED_Field_Command_";
		$post_command_fix_class_name = str_replace( " ", "_", $field_type_name );
		$class = $prefix_command_class_name . $post_command_fix_class_name;

		return $class;
	}

	/**
	 * Return field_object to render by field command class
	 *
	 * @param string $class_name
	 * @param array $filtered_attributes
	 * @param CRED_Form_Data $form
	 * @param CRED_Form_Builder_Helper $form_helper
	 *
	 * @return array|string
	 */
	private function get_field_object( $class_name, $filtered_attributes, $form, $form_helper ) {
		if ( isset( $class_name ) && class_exists( $class_name ) ) {
			$command = new $class_name( $filtered_attributes, $form, $form_helper, $this->_translate_field_factory, $this->cred_form_rendering );

			return $command->execute();
		} else {
			return false;
		}
	}

}
