<?php

namespace OTGS\Toolset\CRED\Model\Field\Generic;

class Gui {

	const SHORTCODE_NAME_FORM_GENERIC_FIELD = 'cred_generic_field';

	/**
	 * Generate the attribute options to fill a set of fields values
	 * from a shortcode outcome.
	 *
	 * @return array
	 * @since 2.1.1
	 */
	private function get_shortcode_source_options() {
		return array(
			'type'  => 'text',
			/* translators: Placeholder for the input to put your shortcode, to generate options for a given generic field in a frontend form */
			'placeholder' => __( 'Add your shortcode here', 'wp-cred' ),
			/* translators: Description for the input to put your shortcode, to generate options for a given generic field in a frontend form */
			'description' => __( 'Note that this shortcode needs to generate a valid list of JSON objects with the right format:', 'wp-cred' )
				. '<br />'
				. '<code>'
				. '{"value": "value1", "label": "Label 1"}, {"value": "value2", "label": "Label 2", "default": true}'
				. '</code>'
		);
	}

	/**
	 * Get the data for the field attribute.
	 *
	 * @return array
	 * @since 2.4
	 */
	private function get_field_slug_option_data() {
		return array(
			/* translators: Label of the option to set a slug for a generic field in a frontend form */
			'label' => __( 'Field slug', 'wp-cred' ),
			'type'  => 'text',
			'required' => true
		);
	}

	/**
	 * Get the data for the required attribute.
	 *
	 * @return array
	 * @since 2.4
	 */
	private function get_field_required_option_data() {
		return array(
			/* translators: Label of the option to ask about making a generic field required in a frontend form */
			'label' => __( 'Should this field be required?', 'wp-cred' ),
			'type'  => 'radio',
			'options' => array(
				/* translators: Label of the option to not set a slug for a generic field in a frontend form */
				'no' => __( 'No, do not make this field required', 'wp-cred' ),
				/* translators: Label of the option to set a slug for a generic field in a frontend form */
				'yes' => __( 'Yes, make this field required', 'wp-cred' )
			),
			'defaultValue' => 'no'
		);
	}

	/**
	 * Get the data for the class attribute.
	 *
	 * @return array
	 * @since 2.4
	 */
	private function get_field_classname_option_data() {
		return array(
			/* translators: Label of the option to apply some specific classnames to a generic field in a frontend form */
			'label' => __( 'Additional CSS classnames', 'wp-cred' ),
			'type'  => 'text'
		);
	}

	/**
	 * Get the data for the default attribute.
	 *
	 * @return array
	 * @since 2.4
	 */
	private function get_field_default_option_data() {
		return array(
			/* translators: Label of the option to set a default value for a generic field in a frontend form */
			'label' => __( 'Default field value', 'wp-cred' ),
			'type'  => 'text',
		);
	}


	/**
	 * Get the data for the validate_format attribute.
	 *
	 * @return array
	 * @since 2.4
	 */
	private function get_field_validate_format_option_data() {
		return array(
			/* translators: Label of the option to ask about forcing some formatting validation for a generic field in a frontend form */
			'label' => __( 'Validate Format', 'wp-cred' ),
			'type'  => 'radio',
			'options' => array(
				/* translators: Label of the option to not force any formatting validation for a generic field in a frontend form */
				'no' => __( 'Do not validate this field', 'wp-cred' ),
				/* translators: Label of the option to force formatting validation for a generic field in a frontend form */
				'yes' => __( 'Validate this field', 'wp-cred' )
			),
			'defaultValue' => 'no'
		);
	}

	/**
	 * Get the data for the generic_type attribute.
	 *
	 * @return array
	 * @since 2.4
	 */
	private function get_field_generic_type_option_data() {
		return array(
			/* translators: Label of the option to ask about using a generic field value as a valud user ID to offer as recipient for notifications */
			'label' => __( 'Notifications recipient source', 'wp-cred' ),
			'type'  => 'radio',
			'options' => array(
				/* translators: Label of the option to not include a generic field value as a valud user ID to offer as recipient email for notifications */
				'' => __( 'Do not use this field on notifications', 'wp-cred' ),
				/* translators: Label of the option to include a generic field value as a valud user ID to offer as recipient email for notifications */
				'user_id' => __( 'This field value is an user ID, and should be included in the list of available recipients for notifications', 'wp-cred' ),
			),
			'defaultValue' => '',
			/* translators: Description of the option to ask about using a generic field value as a valud user ID to offer as recipient for notifications */
			'description' => __( 'Toolset Forms can send notifications to an user whose ID is saved in a generic field, if you select it in the notification settings.', 'wp-cred' )
		);
	}


	/**
	 * Get data for the preview attribute on media fields.
	 *
	 * @param string $field_type
	 * @return array
	 * @since 2.4
	 */
	private function get_preview_options_data( $field_type = 'file' ) {
		return array(
			/* translators: Label of the option to ask about how a generic media field should be previewed */
			'label' => __( 'How should this field be previewed', 'wp-cred' ),
			'type' => 'radio',
			'options' => $this->get_preview_mode_options_per_field_type( $field_type ),
			'defaultValue' => $this->get_default_preview_mode_by_field_type( $field_type ),
		);
	}

	/**
	 * Get valid options for the preview attribute based on the media field type.
	 *
	 * @param string $field_type
	 * @return array
	 * @since 2.4
	 */
	private function get_preview_mode_options_per_field_type( $field_type = 'file' ) {
		$options = array(
			/* translators: Label for the option to preview generic media fields in frontend forms by displaying the URL of the field value */
			'url' => __( 'As the complete URL of the file', 'wp-cred' ),
			/* translators: Label for the option to preview generic media fields in frontend forms by displaying the filename of the field value */
			'filename' => __( 'As the filename', 'wp-cred' ),
		);
		if ( 'image' === $field_type ) {
			/* translators: Label for the option to preview generic image fields in frontend forms by displaying a thumbnail */
			$options['img'] = __( 'As an image HTML tag', 'wp-cred' );
		}
		return $options;
	}

	/**
	 * Get the default value for the preview attribute based on the media field type.
	 *
	 * @param string $field_type
	 * @return string
	 * @since 2.4
	 */
	private function get_default_preview_mode_by_field_type( $field_type = 'file' ) {
		switch ( $field_type ) {
			case 'image':
				return 'img';
			default:
				return 'filename';
		}
	}

    /**
	 * Gather a list of generic fields available togther with their attributes.
     *
     * Used to add generic fields to a form editor, and also to add or edit options
     * for non Toolset fields under Forms control.
     *
     * @note Do not modify grouped attributes, since the fields controls GUI needs to
     *       filter out some of the top-level items.
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	public function get_generic_fields() {
		$fields = array(
			'audio' => array(
				'label' => __( 'Audio', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'audio'
				),
				'options' => array(
					'field' => $this->get_field_slug_option_data(),
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'preview' => $this->get_preview_options_data( 'audio' ),
							'required' => $this->get_field_required_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
				)
			),
			'checkboxes' => array(
				'label' => __( 'Checkboxes', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'checkboxes'
				),
				'options' => array(
					'field' => $this->get_field_slug_option_data(),
					'sourceGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'source' => array(
								'label' => __( 'Options source', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'manual' => __( 'Fill options manually', 'wp-cred' ),
									'shortcode' => __( 'Get options from a shortcode', 'wp-cred' )
								),
								'defaultValue' => 'manual'
							),
							'options' => array(
								'label' => '&nbsp;',
								'type'  => 'text'
							)
						)
					),
					'shortcode' => $this->get_shortcode_source_options(),
					'manual' => array(
						'type'  => 'text',
						'placeholder' => 'manual'
					)
				)
			),
			'checkbox' => array(
				'label' => __( 'Checkbox', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'checkbox'
				),
				'options' => array(
                    'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
					'label' => array(
                        'label' => __( 'Field label', 'wp-cred' ),
                        'type'  => 'text'
                    ),
					'class' => $this->get_field_classname_option_data(),
                    'required' => $this->get_field_required_option_data(),
				)
			),
			'colorpicker' => array(
				'label' => __( 'Colorpicker', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'colorpicker'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => $this->get_field_required_option_data(),
							'validate_format' => $this->get_field_validate_format_option_data(),
						)
					)
				)
			),
			'date' => array(
				'label' => __( 'Date', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'date'
				),
				'options' => array(
					'field' => $this->get_field_slug_option_data(),
                    'class' => $this->get_field_classname_option_data(),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => $this->get_field_required_option_data(),
							'validate_format' => $this->get_field_validate_format_option_data(),
						)
					)
				)
			),
			'email' => array(
				'label' => __( 'Email', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'email'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => $this->get_field_required_option_data(),
							'validate_format' => $this->get_field_validate_format_option_data(),
						)
					)
				)
			),
			'embed' => array(
				'label' => __( 'Embedded Media', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'embed'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => $this->get_field_required_option_data(),
							'validate_format' => $this->get_field_validate_format_option_data(),
						)
					)
				)
			),
			'file' => array(
				'label' => __( 'File', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'file'
				),
				'options' => array(
					'field' => $this->get_field_slug_option_data(),
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'preview' => $this->get_preview_options_data( 'file' ),
							'required' => $this->get_field_required_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
				)
			),
			'hidden' => array(
				'label' => __( 'Hidden', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'hidden'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
					),
					'generic_type' => $this->get_field_generic_type_option_data(),
				)
			),
			'image' => array(
				'label' => __( 'Image', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'image'
				),
				'options' => array(
					'field' => $this->get_field_slug_option_data(),
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'preview' => $this->get_preview_options_data( 'image' ),
							'previewsize' => array(
								'type' => 'select',
								'options' => array(
									'thumbnail' => __( 'Use an image thumbnail', 'wp-cred' ),
									'full' => __( 'Use the full image', 'wp-cred' ),
								),
								'defaultValue' => 'thumbnail',
								'defaultForceValue' => 'thumbnail',
							),
							'required' => $this->get_field_required_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
				)
			),
			'integer' => array(
				'label' => __( 'Integer', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'integer'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => $this->get_field_required_option_data(),
							'validate_format' => $this->get_field_validate_format_option_data(),
						)
					)
				)
			),
			'multiselect' => array(
				'label' => __( 'Multiselect', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'multiselect'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'required' => $this->get_field_required_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'sourceGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'source' => array(
								'label' => __( 'Options source', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'manual' => __( 'Fill options manually', 'wp-cred' ),
									'shortcode' => __( 'Get options from a shortcode', 'wp-cred' )
								),
								'defaultValue' => 'manual'
							),
							'options' => array(
								'label' => '&nbsp;',
								'type'  => 'text'
							)
						)
					),
					'shortcode' => $this->get_shortcode_source_options(),
					'manual' => array(
						'type'  => 'text',
						'placeholder' => 'manual'
					)
				)
			),
			'numeric' => array(
				'label' => __( 'Numeric', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'numeric'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => $this->get_field_required_option_data(),
							'validate_format' => $this->get_field_validate_format_option_data(),
						)
					)
				)
			),
			'password' => array(
				'label' => __( 'Password', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'password'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'required' => $this->get_field_required_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
				)
			),
			'phone' => array(
				'label' => __( 'Phone', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'phone'
				),
				'options' => array(
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
                    'required' => $this->get_field_required_option_data(),
                    'class' => $this->get_field_classname_option_data(),
				)
			),
			'radio' => array(
				'label' => __( 'Radio', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'radio'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'required' => $this->get_field_required_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'sourceGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'source' => array(
								'label' => __( 'Options source', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'manual' => __( 'Fill options manually', 'wp-cred' ),
									'shortcode' => __( 'Get options from a shortcode', 'wp-cred' )
								),
								'defaultValue' => 'manual'
							),
							'options' => array(
								'label' => '&nbsp;',
								'type'  => 'text'
							)
						)
					),
					'shortcode' => $this->get_shortcode_source_options(),
					'manual' => array(
						'type'  => 'text',
						'placeholder' => 'manual'
					),
					'generic_type' => $this->get_field_generic_type_option_data(),
				)
			),
			'select' => array(
				'label' => __( 'Select', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'select'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'required' => $this->get_field_required_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'sourceGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'source' => array(
								'label' => __( 'Options source', 'wp-cred' ),
								'type'  => 'radio',
								'options' => array(
									'manual' => __( 'Fill options manually', 'wp-cred' ),
									'shortcode' => __( 'Get options from a shortcode', 'wp-cred' )
								),
								'defaultValue' => 'manual'
							),
							'options' => array(
								'label' => '&nbsp;',
								'type'  => 'text'
							)
						)
					),
					'shortcode' => $this->get_shortcode_source_options(),
					'manual' => array(
						'type'  => 'text',
						'placeholder' => 'manual'
					),
					'generic_type' => $this->get_field_generic_type_option_data(),
				)
			),
			'skype' => array(
				'label' => __( 'Skype', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'skype'
				),
				'options' => array(
					'slugAndDefault' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
                    'required' => $this->get_field_required_option_data(),
				)
			),
			'textarea' => array(
				'label' => __( 'Multiple Lines', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'textarea'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'required' => $this->get_field_required_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'default' => array(
						'label' => __( 'Default field value', 'wp-cred' ),
						'type'  => 'textarea',
					)
				)
			),
			'textfield' => array(
				'label' => __( 'Single Line', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'textfield'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
                    'required' => $this->get_field_required_option_data(),
                    'class' => $this->get_field_classname_option_data(),
				)
			),
			'url' => array(
				'label' => __( 'URL', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'url'
				),
				'options' => array(
					'slugAndMore' => array(
						'type'   => 'group',
						'fields' => array(
							'field' => $this->get_field_slug_option_data(),
							'default' => $this->get_field_default_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
					'requireAndValidate' => array(
						'type'   => 'group',
						'fields' => array(
							'required' => $this->get_field_required_option_data(),
							'validate_format' => $this->get_field_validate_format_option_data(),
						)
					)
				)
			),
			'video' => array(
				'label' => __( 'Video', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'video'
				),
				'options' => array(
					'field' => $this->get_field_slug_option_data(),
					'optionsGroup' => array(
						'type'   => 'group',
						'fields' => array(
							'preview' => $this->get_preview_options_data( 'video' ),
							'required' => $this->get_field_required_option_data(),
						)
                    ),
                    'class' => $this->get_field_classname_option_data(),
				)
			),
			'wysiwyg' => array(
				'label' => __( 'WYSIWYG', 'wp-cred' ),
				'shortcode' => self::SHORTCODE_NAME_FORM_GENERIC_FIELD,
				'attributes' => array(
					'type' => 'wysiwyg'
				),
				'options' => array(
					'field' => $this->get_field_slug_option_data(),
					'default' => array(
						'label' => __( 'Default field value', 'wp-cred' ),
						'type'  => 'textarea',
					)
				)
			)
		);

		return $fields;
    }

    public function get_generic_fields_labels() {
        $labels = array();

        $generic_fields = $this->get_generic_fields();
        foreach ( $generic_fields as $field_slug => $field_data ) {
            $labels[ $field_slug ] = $field_data['label'];
        }

        return $labels;
    }

}
