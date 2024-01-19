<?php

/**
 * @since 1.9.1
 */
class CRED_Field_Configuration_Translated_Value {

	function __construct() {
	}

	/**
	 * This function takes field configuration data following a set of priority
	 * - urlparams
	 * - preset_value
	 * - db
	 * - $_POST
	 * - Types default value
	 *
	 * @param array $field_configuration_args
	 *     @param bool $can_accept_post_data
	 *     @param string $field_name
	 *     @param string $field_type
	 *     @param array $field_data
	 *     @param string $placeholder
	 *     @param string $urlparam
	 *     @param string $cred_form_prefix
	 *     @param mixed $preset_value
	 *     @param bool $can_accept_empty_preset_value
	 *     @param mixed $types_default_value
	 * @param array $additional_options
	 * @param object $field_db_data
	 *
	 * @return array|int|string
	 *
	 * @note The can_accept_empty_preset_value flag was added to ensure that empty default values for generic fields
	 *       are kept after an AJAX form has been successfully submitted.
	 *
	 * @since 1.9.1
	 */
	public function get_field_configuration( $field_configuration_args, &$additional_options, $field_db_data ) {

		$defaults = array(
			'can_accept_post_data'	=> false,
			'field_name'			=> '',
			'field_type'			=> '',
			'field_data'			=> array(),
			'placeholder'			=> '',
			'utl_parameter'			=> '',
			'form_prefix'			=> '',
			'preset_value'			=> '',
			'can_accept_empty_preset_value' => false,
			'types_default_value'	=> '',
			'save_empty'	=> false
		);
		$field_configuration_args = wp_parse_args( $field_configuration_args, $defaults );

		$can_accept_post_data = $field_configuration_args['can_accept_post_data'];
		$field_name = $field_configuration_args['field_name'];
		$field_type = $field_configuration_args['field_type'];
		$field_data = $field_configuration_args['field_data'];
		$placeholder = $field_configuration_args['placeholder'];
		$urlparam = $field_configuration_args['utl_parameter'];
		$cred_form_prefix = $field_configuration_args['form_prefix'];
		$preset_value = $field_configuration_args['preset_value'];
		$can_accept_empty_preset_value = $field_configuration_args['can_accept_empty_preset_value'];
		$types_default_value = $field_configuration_args['types_default_value'];
		$save_empty = $field_configuration_args['save_empty'];

		$field_configuration = "";

		if ( isset( $placeholder )
			&& ! empty( $placeholder )
			&& is_string( $placeholder )
		) {
			// use translated value by WPML if exists
			$placeholder = cred_translate(
				'Value: ' . $placeholder, $placeholder, $cred_form_prefix
			);
			$additional_options['placeholder'] = $placeholder;
		}

		//Urlparam shortcode attribute
		if ( is_string( $urlparam )
			&& ! empty( $urlparam )
			&& isset( $_GET[ $urlparam ] )
		) {
			// use translated value by WPML if exists
			$field_configuration = urldecode( $_GET[ $urlparam ] );

			// Value from the shortcode attribute for generic fields.
			// Mind that we only force this in when there is no POSTed data,
			// or when there is POSTed data but we are sure the form was
			// successfully posted using AJAX
			// (because that is what the flag CRED_Form_Base::_self_updated_form means).
			// Otherwise (when there is POSTed data but not for a successfull AJAX form).
			// we default to the following else statemnets to load the POSTed value as the AJAX
			// subission failed. For non-AJAX scenarios, when rendering the form we will never have
			// POSTed data so we always default to here.
		} elseif (
			(
				! isset( $_POST[ $field_name ] )
				|| CRED_Form_Base::$_self_updated_form
			) && (
				$can_accept_empty_preset_value
				|| (
					isset( $preset_value )
					&& (
						! empty( $preset_value )
						|| is_numeric( $preset_value )
					)
				)
			)
		) {
			// use translated value by WPML if exists, only for strings
			// For numeric values, just pass it
			if (
				! empty( $preset_value ) && is_string( $preset_value )
			) {
				$field_configuration = cred_translate(
					'Value: ' . $preset_value, $preset_value, $cred_form_prefix
				);

				$additional_options['preset_value'] = $placeholder;
			} else {
				$field_configuration = $preset_value;
			}

			//DB post data
			//This function is called every time we need to render a form field
			//because the flow create/edit post form is the same we need to remind
			//some cases we cannot get postData values like AJAX create form after valid submition
		} elseif ( $can_accept_post_data
			&& ( CRED_Form_Base::$_self_updated_form || ! isset( $_POST[ $field_name ] ) )
			&& $field_db_data
			&& isset( $field_db_data->fields[ $field_name ] )
		) {
			if ( is_array( $field_db_data->fields[ $field_name ] )
				&& count( $field_db_data->fields[ $field_name ] ) > 1
			) {
				if ( isset( $field_data['repetitive'] )
					&& $field_data['repetitive'] == 1
				) {
					$field_configuration = $field_db_data->fields[ $field_name ];
				}
			} else {
				$field_configuration = $field_db_data->fields[ $field_name ][0];

				//checkboxes needs to be different from from db
				if ( $field_type == 'checkboxes' ) {
					if ( isset( $field_db_data->fields[ $field_name ] ) &&
						isset( $field_db_data->fields[ $field_name ][0] )
						&& is_array( $field_db_data->fields[ $field_name ][0] )
					) {
						$field_configuration = array();
						foreach ( $field_db_data->fields[ $field_name ][0] as $key => $value ) {
							if ( $save_empty
								&& $value == 0 ) {
								continue;
							}
							$field_configuration[] = $key;
						}
					}
				}
			}

			//$_POST data
		} elseif ( $_POST
			&& isset( $_POST )
			&& isset( $_POST[ $field_name ] )
		) {
			$field_configuration = stripslashes_deep( $_POST[ $field_name ] );

			//Types default value
		} elseif ( ! empty( $types_default_value ) ) {
			$field_configuration = $types_default_value;
		} else {
			if ( ! isset( $preset_value ) ) {
				$field_configuration = null;
			}
		}

		return $field_configuration;
	}

}
