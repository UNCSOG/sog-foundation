<?php

class CRED_Validator_Toolset_Forms implements ICRED_Validator {

    protected $_zebraForm;
    protected $_post_id;
    protected $_values;
    protected $_is_user_form;

    public function __construct($zebraForm, $post_id, $is_user_form = false) {
        $this->_zebraForm = $zebraForm;
        $this->_post_id = $post_id;
        $this->_values = $zebraForm->form_properties['fields'];
        $this->_is_user_form = $is_user_form;
    }

    public function validate() {
        $result = true;

        $is_user_form = $this->_is_user_form;
        $post_id = $this->_post_id;
        $values = $this->_values;
        $zebraForm = $this->_zebraForm;
        $form_id = $zebraForm->html_form_id;
        // Loop over fields
        $form_source_data = $zebraForm->_formData->getForm()->post_content;
        preg_match_all("/\[cred_show_group.*cred_show_group\]/Uism", $form_source_data, $res);
        $conditional_group_fields = array();

        $len_res = count($res[0]);
        if ($len_res > 0) {
            for ($i = 0, $res_limit = $len_res; $i < $res_limit; $i++) {
                preg_match_all("/field=[\"|']([^\"]+)['|\"]/Uism", $res[0][$i], $parsed_fields);
                $len_parse_fields = count($parsed_fields[1]);
                if ($len_parse_fields > 0) {
                    for ($j = 0, $count_parsed_fields = $len_parse_fields; $j < $count_parsed_fields; $j++) {
                        if (!empty($parsed_fields[1][$j])) {
                            $conditional_group_fields[] = trim($parsed_fields[1][$j]);
                        }
                    }
                }
            }
        }

        foreach ($zebraForm->form_properties['fields'] as $field) {
            if (in_array(str_replace('wpcf-', '', $field['name']), $conditional_group_fields)) {
                continue;
			}

			if ( in_array( $field['name'], array( 'user_pass2' ) ) ) {
				continue;
			}

            // If Types field
            if (isset($field['plugin_type']) && $field['plugin_type'] == 'types') {
                $field_name = $field['name'];

                if (function_exists('wpcf_fields_get_field_by_slug')) {
                    $field = wpcf_fields_get_field_by_slug(str_replace('wpcf-', '', $field['name']), $is_user_form ? 'wpcf-usermeta' : 'wpcf-fields');
                    if (empty($field)) {
                        continue;
                    }
                }

                // Skip copied fields
                if (isset($_POST['wpcf_repetitive_copy'][$field['slug']])) {
                    continue;
                }

                // Set field config
				$config = wptoolset_form_filter_types_field($field, $post_id);
				$config = apply_filters( 'cred_form_field_config', $config );

				$config['conditional']['values'] = isset( $config['conditional']['values'] )
					? (array) $config['conditional']['values']
					: array();

				foreach ( $config['conditional']['values'] as $post_key => $post_value ) {
					if ( isset($zebraForm->form_properties['fields'][ $post_key ] ) ) {
						$config['conditional']['values'][ $post_key ] = $zebraForm->form_properties['fields'][ $post_key ]['value'];
					}
				}

				// Types conditions are stored here:
				// Make sure we do apply them based on the posted data for the fields that control conditions
				if ( isset( $config['conditional']['conditions'] ) ) {
					foreach ( $config['conditional']['conditions'] as $condition_data ) {
						if ( isset( $zebraForm->form_properties['fields'][ $condition_data['id'] ] ) ) {
                            $config['conditional']['values'][ $condition_data['id'] ] = $zebraForm->form_properties['fields'][ $condition_data['id'] ]['value'];
                        }
					}
                }
                // Set values to loop over, and clear empty
                $_values = !empty($values[$field_name]) ? $values[$field_name]['value'] : null;
                if (empty($config['repetitive'])) {
                    $_values = array( $_values );
                }
				if ( is_null( $_values ) ) {
					$_values = array();
				}
				$_values = array_filter( $_values );

				// url2 validation exists only on media fields
				// and we do not need it:
				// - with the new media manager, validation is done before uploading
				// - without the media manager, we validate file uploads
				// so we just skip url2 validation on submitted forms :-)
	            if (
					isset( $config[ 'validation' ][ 'url2' ] )
					&& (
						(
							// Posted as $_FILES
							isset( $field[ 'file_data' ] )
							&& ! empty( $field[ 'file_data' ] )
						) || (
							// Posted as $_POST
							isset( $_POST[ $field[ 'meta_key' ] ] )
							&& ! empty( $_POST[ $field[ 'meta_key' ] ] )
						)
					)
	            ) {
		            unset( $config[ 'validation' ][ 'url2' ] );
	            }

                // Loop over each value
                if (is_array($_values)) {
                    foreach ($_values as $value) {
                        $validation = wptoolset_form_validate_field($form_id, $config, $value);
                        $conditional = wptoolset_form_conditional_check($config);

                        /**
                         * add form_errors messages
                         */
                        if (is_wp_error($validation) && $conditional) {
                            $error_data = $validation->get_error_data();
                            if (isset($error_data[0])) {
                                $zebraForm->add_top_message($error_data[0], $config['id']);
                            } else {
                                $zebraForm->add_top_message($validation->get_error_message(), $config['id']);
                            }
                            $result = false;
                            if (empty($ret_validation)) {
                                continue;
                            }
                        }
                    }
                }
            } elseif (!isset($field['plugin_type']) && isset($field['validation'])) {

                if (!isset($_POST[$field['name']]))
                    continue;

                $config = array(
                    'id' => $field['name'],
                    'type' => $field['type'],
                    'slug' => $field['name'],
                    'title' => $field['name'],
                    'description' => '',
                    'name' => $field['name'],
                    'repetitive' => $field['repetitive'],
                    'validation' => $field['validation'],
                    'conditional' => array()
                );

                $value = $field['value'];
                require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.types.php';
                $validation = array();
                foreach ($field['validation'] as $rule => $settings) {
                    if ($settings['active']) {
                        $id = $config['slug'];
                        $validation[$rule] = array(
                            'args' => isset($settings['args']) ? array_unshift($value, $settings['args']) : array($value, true),
                            'message' => WPToolset_Types::translate('field ' . $id . ' validation message ' . $rule, $settings['message'])
                        );
                    }
                }
                $config['validation'] = $validation;

                $validation = wptoolset_form_validate_field($form_id, $config, $value);
                if (is_wp_error($validation)) {
                    $error_data = $validation->get_error_data();
                    //TODO: replace id with name
                    if (isset($error_data[0])) {
                        $zebraForm->add_top_message($error_data[0], $config['id']);
                    } else {
                        $zebraForm->add_top_message($validation->get_error_message(), $config['id']);
                    }
                    $result = false;
                    if (empty($ret_validation)) {
                        continue;
                    }
                }
            }
        }
        return $result;
    }

}
