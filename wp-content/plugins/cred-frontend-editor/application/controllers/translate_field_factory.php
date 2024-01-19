<?php

/**
 * Class that translates field during cred shortcodes elaboration.
 *
 * @since unknown
 */
class CRED_Translate_Field_Factory {

	public $_formBuilder;
	public $_formHelper;
	protected $cred_field_configuration_class;
	protected $cred_translate_command_factory;

	/**
	 * CRED_Translate_Field_Factory constructor.
	 *
	 * @param CRED_Form_Base $formBuilder
	 * @param CRED_Form_Builder_Helper $formHelper
	 * @param CRED_Field_Configuration_Translated_Value $cred_field_configuration_class
	 * @param CRED_Translate_Command_Factory $cred_translate_command_factory
	 */
	public function __construct(
		CRED_Form_Base $formBuilder,
		CRED_Form_Builder_Helper $formHelper,
		CRED_Field_Configuration_Translated_Value $cred_field_configuration_class,
		CRED_Translate_Command_Factory $cred_translate_command_factory
	) {
		$this->_formBuilder = $formBuilder;
		$this->_formHelper = $formHelper;
		$this->cred_field_configuration_class = $cred_field_configuration_class;
		$this->cred_translate_command_factory = $cred_translate_command_factory;
	}


	/**
	 * Function to retrieve the unique html form field_id.
	 * This ID is composed by the concatenated string belonged form_id and field_id itself
	 *
	 * @param $field
	 *
	 * @return string
	 */
	public function get_html_form_field_id( $field ) {
		// allow multiple submit buttons
		static $_count_ = array(
			'submit' => 0,
		);

		$count = ( $field['type'] == 'form_submit' ) ? '_' . ( $_count_['submit'] ++ ) : "";
		$result_field = "";

		if ( $field['type'] == 'taxonomy_hierarchical' || $field['type'] == 'taxonomy_plain' ) {
			$result_field = "_" . $field['name'];
		} else {
			if ( isset( $field['master_taxonomy'] ) && isset( $field['type'] ) ) {
				$result_field = "_" . $field['master_taxonomy'] . "_" . $field['type'];
			} elseif ( isset( $field['id'] ) ) {
				$result_field = "_" . $field['id'];
			}
		}

		return "cred_form_" . CRED_StaticClass::$out['prg_id'] . $count . $result_field;
	}

	/**
	 * @param $name
	 * @param $field
	 * @param array $additional_options
	 *
	 * @return mixed
	 *
	 * @since unknown
	 * @since 1.9.1 The can_accept_empty_preset_value flag was added to ensure that empty default values for generic fields
	 *              are kept after an AJAX form has been successfully submitted.
	 */
	public function cred_translate_field( $name, &$field, $additional_options = array() ) {
		static $_count_ = array(
			'submit' => 0,
		);

		static $wpExtensions = false;
		// get refs here
		$globals = CRED_StaticClass::$_staticGlobal;
		if ( false === $wpExtensions ) {
			$wpMimes = $globals['MIMES'];
			$wpExtensions = implode( ',', array_keys( $wpMimes ) );
		}

		// get refs here
		$form = $this->_formBuilder->get_form_data();
		$postData = $this->_formBuilder->get_post_data();
		$cred_form_rendering = $this->_formBuilder->get_form_rendering();

		$filtered_attributes = shortcode_atts(
			array(
				'preset_value' => null,
				'placeholder' => null,
				'value_escape' => false,
				'make_readonly' => false,
				'is_tax' => false,
				'max_width' => null,
				'max_height' => null,
				'single_select' => false,
				'generic_type' => null,
				'urlparam' => '',
			),
			$additional_options
		);

		$show_popular = ( isset( $filtered_attributes['show_popular'] ) ) ? $filtered_attributes['show_popular'] : false;
		$preset_value = ( isset( $filtered_attributes['preset_value'] ) ) ? $filtered_attributes['preset_value'] : null;
		$placeholder = ( isset( $filtered_attributes['placeholder'] ) ) ? $filtered_attributes['placeholder'] : null;
		$value_escape = ( isset( $filtered_attributes['value_escape'] ) ) ? $filtered_attributes['value_escape'] : false;
		$make_readonly = ( isset( $filtered_attributes['make_readonly'] ) ) ? $filtered_attributes['make_readonly'] : false;
		$is_tax = ( isset( $filtered_attributes['is_tax'] ) ) ? $filtered_attributes['is_tax'] : false;
		$max_width = ( isset( $filtered_attributes['max_width'] ) ) ? $filtered_attributes['max_width'] : null;
		$max_height = ( isset( $filtered_attributes['max_height'] ) ) ? $filtered_attributes['max_height'] : null;
		$single_select = ( isset( $filtered_attributes['single_select'] ) ) ? $filtered_attributes['single_select'] : false;
		$generic_type = ( isset( $filtered_attributes['generic_type'] ) ) ? $filtered_attributes['generic_type'] : null;
		$urlparam = ( isset( $filtered_attributes['urlparam'] ) ) ? $filtered_attributes['urlparam'] : '';

		$type = 'text';

		$attributes = array();

		if ( isset( $class ) ) {
			$attributes['class'] = $class;
		}

		$types_default_value = "";
		$value = "";
		$field_name = $name;

		$field['field_name'] = $field_name;
		//TODO: just try to make $field['title'] = cred_translate()...
		$field["name"] = cred_translate( $field["name"], $field["name"], $form->getForm()->post_type . "-" . $form->getForm()->post_title . "-" . $form->getForm()->ID );
		/*
		 * Set field title to the translated field name.
		 * Title is actually used by only checkbox field the other fields will use autogenerated label custom code.
		 */
		$field['title'] = $field["name"];

		if (
			isset( $field['data']['user_default_value'] )
			&& (
				! empty( $field['data']['user_default_value'] )
				|| is_numeric( $field['data']['user_default_value'] )
			)
		) {
			$types_default_value = $field['data']['user_default_value'];
		}

		$save_empty = isset( $field['data']['save_empty'] ) && $field['data']['save_empty'] == 'yes' ? true : false;

		$can_accept_post_data = ! ( CRED_StaticClass::$_reset_file_values && CRED_Form_Base::$_self_updated_form );
		$cred_form_prefix = 'cred-form-' . $form->getForm()->post_title . '-' . $form->getForm()->ID;

		// if not taxonomy field
		if ( ! $is_tax ) {

			$field_data = isset( $field['data'] ) ? $field['data'] : "";

			$field_configuration_args = array(
				'can_accept_post_data' => $can_accept_post_data,
				'field_name' => $field_name,
				'field_type' => $field['type'],
				'field_data' => $field_data,
				'placeholder' => $placeholder,
				'utl_parameter' => $urlparam,
				'form_prefix' => $cred_form_prefix,
				'preset_value' => $preset_value,
				'can_accept_empty_preset_value' => ( isset( $field['cred_generic'] ) && $field['cred_generic'] && $cred_form_rendering->is_submit_success ),
				'types_default_value' => $types_default_value,
				'save_empty' => $save_empty,
			);

			$field_configuration = $this->cred_field_configuration_class->get_field_configuration( $field_configuration_args, $additional_options, $postData );

			$this->set_field_values_map_slug( $field );

			if ( isset( $field_configuration ) ) {
				$value = $field_configuration;
			}

			$field['field_configuration'] = $field_configuration;
			$field['field_value'] = $value;
			$all_attributes = array_merge( $attributes, $additional_options );

			$command_field = $this->cred_translate_command_factory->get_command_class_instance( $this, $field, $all_attributes );
			if ( ! $command_field ) {
				return null;
			}
			$command_field->set_additional_args( 'can_accept_post_data', $can_accept_post_data );
			$command_field->set_additional_args( 'postData', $postData );
			$command_field->set_additional_args( 'preset_value', $preset_value );
			$command_field->set_additional_args( 'placeholder', $placeholder );
			$command_field_result = $command_field->execute();

			$field_configuration = $command_field_result->get_field_configuration();
			$type = $command_field_result->get_field_type();
			$name = $command_field_result->get_field_name();
			$value = $command_field_result->get_field_value();
			$attributes = $command_field_result->get_field_attributes();
			$field = $command_field_result->get_field();

			if ( isset( $attributes['make_readonly'] )
				&& ! empty( $attributes['make_readonly'] )
			) {
				unset( $attributes['make_readonly'] );
				if ( ! is_array( $attributes ) ) {
					$attributes = array();
				}
				$attributes['readonly'] = 'readonly';
			}

			if ( isset( $field['data']['repetitive'] ) && $field['data']['repetitive'] ) {
				$value = (
					isset( $value )
						? $value
						: ( isset( $postData->fields[ $field_name ] ) ? $postData->fields[ $field_name ] : array() )
				);
			}

			$field_result = $cred_form_rendering->add( $type, $name, $value, $attributes, $field );

		} else {

			$field['hierarchical'] = isset( $field['hierarchical'] ) ? $field['hierarchical'] : false;

			// taxonomy field or auxilliary taxonomy field (eg popular terms etc..)
			if ( ! array_key_exists( 'master_taxonomy', $field ) ) { // taxonomy field
				if ( $field['hierarchical'] ) {
					if ( in_array( $preset_value, array( 'checkbox', 'select' ) ) ) {
						$tax_display = $preset_value;
					} else {
						$tax_display = 'checkbox';
					}
				}

				if ( $postData
					&& isset( $postData->taxonomies[ $field_name ] )
				) {
					if ( ! $field['hierarchical'] ) {
						$field_attributes = array(
							'terms' => $postData->taxonomies[ $field_name ]['terms'],
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'remove_text' => $this->_formHelper->getLocalisedMessage( 'remove_taxonomy' ),
							'ajax_url' => admin_url( 'admin-ajax.php' ),
							'auto_suggest' => true,
							'show_popular_text' => $this->_formHelper->getLocalisedMessage( 'show_popular' ),
							'hide_popular_text' => $this->_formHelper->getLocalisedMessage( 'hide_popular' ),
							'show_popular' => $show_popular,
						);
					} else {
						$field_attributes = array(
							'terms' => $postData->taxonomies[ $field_name ]['terms'],
							'all' => $field['all'],
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'add_new_text' => $this->_formHelper->getLocalisedMessage( 'add_new_taxonomy' ),
							'parent_text' => __( '-- Parent --', 'wp-cred' ),
							'type' => $tax_display,
							'single_select' => $single_select,
						);
					}

				} elseif ( $_POST
					&& isset( $_POST )
				) {

					if ( ! $field['hierarchical'] ) {
						$field_attributes = array(
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'remove_text' => $this->_formHelper->getLocalisedMessage( 'remove_taxonomy' ),
							'ajax_url' => admin_url( 'admin-ajax.php' ),
							'auto_suggest' => true,
							'show_popular_text' => $this->_formHelper->getLocalisedMessage( 'show_popular' ),
							'hide_popular_text' => $this->_formHelper->getLocalisedMessage( 'hide_popular' ),
							'show_popular' => $show_popular,
						);
					} else {
						$field_attributes = array(
							'all' => $field['all'],
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'add_new_text' => $this->_formHelper->getLocalisedMessage( 'add_new_taxonomy' ),
							'parent_text' => __( '-- Parent --', 'wp-cred' ),
							'type' => $tax_display,
							'single_select' => $single_select,
						);
					}
				} else {

					if ( ! $field['hierarchical'] ) {
						$field_attributes = array(
							//'terms'=>array(),
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'remove_text' => $this->_formHelper->getLocalisedMessage( 'remove_taxonomy' ),
							'ajax_url' => admin_url( 'admin-ajax.php' ),
							'auto_suggest' => true,
							'show_popular_text' => $this->_formHelper->getLocalisedMessage( 'show_popular' ),
							'hide_popular_text' => $this->_formHelper->getLocalisedMessage( 'hide_popular' ),
							'show_popular' => $show_popular,
						);
					} else {
						$field_attributes = array(
							'all' => $field['all'],
							'add_text' => $this->_formHelper->getLocalisedMessage( 'add_taxonomy' ),
							'add_new_text' => $this->_formHelper->getLocalisedMessage( 'add_new_taxonomy' ),
							'parent_text' => __( '-- Parent --', 'wp-cred' ),
							'type' => $tax_display,
							'single_select' => $single_select,
						);
					}
				}

				$field_configuration['title'] = toolset_getnest( CRED_StaticClass::$out, array( 'fields', 'taxonomies', $field_name, 'label' ), $field["name"] );

				$field_attributes['class'] = isset( $additional_options['class'] ) ? $additional_options['class'] : "";
				$field_attributes['output'] = isset( $additional_options['output'] ) ? $additional_options['output'] : "";

				$field_result = $cred_form_rendering->add( ! $field['hierarchical'] ? 'taxonomy' : 'taxonomyhierarchical', $name, $value, $field_attributes, $field_configuration );

				// register this taxonomy field for later use by auxilliary taxonomy fields
				CRED_StaticClass::$out['taxonomy_map']['taxonomy'][ $field_name ] = &$field_result;
				// if a taxonomy auxiliary field exists attached to this taxonomy, add this taxonomy id to it
				if ( isset( CRED_StaticClass::$out['taxonomy_map']['aux'][ $field_name ] ) ) {
					CRED_StaticClass::$out['taxonomy_map']['aux'][ $field_name ]->set_attributes( array( 'master_taxonomy_id' => $field_result->attributes['id'] ) );
				}

			} else { // taxonomy auxilliary field (eg most popular etc..)

				if ( isset( $preset_value ) ) // use translated value by WPML if exists
				{
					$field_configuration = cred_translate(
						'Value: ' . $preset_value, $preset_value, 'cred-form-' . $form->form->post_title . '-' . $form->form->ID
					);
				} else {
					$field_configuration = null;
				}
			}
		}

		return $field_result;
	}

	/**
	 * Save a map between options / actual values for these types to be used later
	 *
	 * @param $field
	 *
	 * @since 1.9.1
	 */
	private function set_field_values_map_slug( $field ) {
		if ( in_array( $field['type'], array( 'checkboxes', 'radio', 'select', 'multiselect' ) ) ) {
			$field_values_map_slug = array();
			if ( isset( $field['data']['options'] ) && ! empty( $field['data']['options'] ) ) {
				foreach ( $field['data']['options'] as $optionKey => $optionData ) {
					if ( $optionKey !== 'default' && is_array( $optionData ) ) {
						$field_values_map_slug[ $optionKey ] = ( 'checkboxes' == $field['type'] ) ? @$optionData['set_value'] : $optionData['value'];
					}
				}
			}
			CRED_StaticClass::$out['field_values_map'][ $field['slug'] ] = $field_values_map_slug;
			unset( $field_values_map_slug );
			unset( $optionKey );
			unset( $optionData );
		}
	}
}
