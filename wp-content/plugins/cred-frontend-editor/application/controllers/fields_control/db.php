<?php

namespace OTGS\Toolset\CRED\Controller\FieldsControl;

/**
 * Get and set non Toolset fields.
 * 
 * @since 2.1
 */
class Db {

    const CUSTOM_FIELDS_OPTION = '__CRED_CUSTOM_FIELDS';

    /**
     * Get all the non Toolset fields controlled by Forms.
     * 
     * Fields are arranged per post type in a multidimensional array.
     * Public access is granted per post type by the get_fields_per_post_type method.
     *
     * @return array
     * 
     * @since 2.1
     */
    private function get_fields() {
        $fields = get_option( self::CUSTOM_FIELDS_OPTION, array() );
        if ( ! is_array( $fields ) ) {
            return array();
        }
        return $fields;
    }

    /**
     * Get all the non Toolset fields assigned to a given post type and controlled by Forms.
     *
     * @param string $post_type
     * @return array
     * @since 2.1
     */
    public function get_fields_per_post_type( $post_type ) {
        $fields = $this->get_fields();
        return toolset_getarr( $fields, $post_type, array() );
    }

    /**
     * Save all non Toolset fields controller by Forms.
     *
     * @param array $fields
     * @return bool
     * @since 2.1
     */
    private function save_fields( $fields ) {
        return update_option( self::CUSTOM_FIELDS_OPTION, $fields );
    }

    /**
     * Save the non Toolset fields assigned to a given post type and controlled by Forms.
     *
     * @param array $fields
     * @param string $post_type
     * @return bool
     * @since 2.1
     */
    public function save_fields_per_post_type( $fields, $post_type ) {
        $all_fields = $this->get_fields();
        $all_fields[ $post_type ] = $fields;
        return $this->save_fields( $all_fields );
    }

    /**
     * Get a given non Toolset field controller by Forms, give the post type it belongs to.
     *
     * @param string $field_key
     * @param string $post_type
     * @return array|false
     * @since 2.1
     */
    public function get_field( $field_key, $post_type ) {
        $fields = $this->get_fields_per_post_type( $post_type );
        return toolset_getarr( $fields, $field_key, false );
    }

    /**
     * Get a given non Toolset field controller by Forms, give the post type it belongs to.
     *
     * @param array $field_data
     * @param string $post_type
     * @return bool
     * @since 2.1
     */
    public function set_field( $field_data, $post_type ) {
        $field_key = toolset_getarr( $field_data, 'name' );

        if ( empty( $field_key ) ) {
            return false;
        }

        $fields = $this->get_fields_per_post_type( $post_type );
        $field_data = $this->normalize_field_data_to_save( $field_key, $field_data, $post_type );
        $fields[ $field_key ] = $field_data;
        return $this->save_fields_per_post_type( $fields, $post_type );
    }

    /**
     * Remove a given field from the list of non Toolset fields controlled by Forms for a given post type.
     *
     * @param string $field_key
     * @param string $post_type
     * @return bool
     * @since 2.1
     */
    public function remove_field( $field_key, $post_type ) {
        $fields = $this->get_fields_per_post_type( $post_type );
        if ( ! array_key_exists( $field_key, $fields ) ) {
            return false;
        }
        unset( $fields[ $field_key ] );
        return $this->save_fields_per_post_type( $fields, $post_type );
    }

    /**
     * Normalize a field before saving it, filling the gaps of missing properties.
     *
     * @param string $field_key
     * @param array $field_data
     * @param string $post_type
     * @return array
     * @since 2.1
     */
    private function normalize_field_data_to_save( $field_key, $field_data, $post_type ) {
        // Basic field data
        $field_data_normalized = array(
            'id' => toolset_getarr( $field_data, 'name' ),
            'post_type' => $post_type,
            'cred_custom' => true,
            'slug' => toolset_getarr( $field_data, 'name' ),
            'type' => toolset_getarr( $field_data, 'type' ),
            'name' => toolset_getarr( $field_data, 'name' ),
            'default' => toolset_getarr( $field_data, 'default' ),
            'data' => array(
                'repetitive' => 0,
                'validate' => array(
                    'required' => array(
                        'active' => $field_data['required'],
                        'value' => $field_data['required'],
                        'message' => __( 'This field is required', 'wp-cred' ),
                    ),
                ),
                'validate_format' => $field_data['validate_format'],
            ),
            '_cred_ignore' => ( ! $field_data['include_scaffold'] )
        );

        // Previous versions of this method dealed with strings as option_default values
        // for radio and select field types.
        if ( isset( $field_data['options']['option_default'] ) ) {
            if ( ! is_array( $field_data['options']['option_default'] ) ) {
                $field_data['options']['option_default'] = array( $field_data['options']['option_default'] );
            }
        } else {
            $field_data['options']['option_default'] = array();
        }

        // Field type related data
        switch ( $field_data_normalized['type'] ) {
            case 'checkbox':
                $field_data_normalized['data']['set_value'] = $field_data_normalized['default'];
                break;
            case 'checkboxes':
                $field_data_normalized['data']['options'] = array();
                if ( ! isset( $field_data['options']['value'] ) ) {
                    $field_data['options'] = array( 'value' => array(), 'label' => array(), 'option_default' => array() );
                }
                foreach ( $field_data['options']['value'] as $ii => $option ) {
                    $option_id = $option;
                    $field_data_normalized['data']['options'][ $option_id ] = array(
                        'title' => $field_data['options']['label'][ $ii ],
                        'set_value' => $option,
                    );
                    if ( isset( $field_data['options']['option_default'] ) && in_array( $option, $field_data['options']['option_default'] ) ) {
                        $field_data_normalized['data']['options'][ $option_id ]['checked'] = true;
                    }
                }
                break;
            case 'date':
                $field_data_normalized['data']['validate']['date'] = array(
                    'active' => $field_data['validate_format'],
                    'format' => 'mdy',
                    'message' => __( 'Please enter a valid date', 'wp-cred' ),
                );
                break;
            case 'radio':
            case 'select':
                $field_data_normalized['data']['options'] = array();
                $default_option = 'no-default';
                if ( ! isset( $field_data['options']['value'] ) ) {
                    $field_data['options'] = array( 'value' => array(), 'label' => array(), 'option_default' => array() );
                }
                foreach ( $field_data['options']['value'] as $ii => $option ) {
                    $option_id = $option;
                    $field_data_normalized['data']['options'][ $option_id ] = array(
                        'title' => $field_data['options']['label'][ $ii ],
                        'value' => $option,
                        'display_value' => $option,
                    );
                    if ( isset( $field_data['options']['option_default'] ) && in_array( $option, $field_data['options']['option_default'] ) ) {
                        $default_option = $option_id;
                    }
                }
                $field_data_normalized['data']['options']['default'] = $default_option;
                break;
            case 'email':
                $field_data_normalized['data']['validate']['email'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => __( 'Please enter a valid email address', 'wp-cred' ),
                );
                break;
            case 'numeric':
                $field_data_normalized['data']['validate']['number'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => __( 'Please enter numeric data', 'wp-cred' ),
                );
                break;
            case 'integer':
                $field_data_normalized['data']['validate']['integer'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => __( 'Please enter integer data', 'wp-cred' ),
                );
                break;
            case 'embed':
            case 'url':
                $field_data_normalized['data']['validate']['url'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => __( 'Please enter a valid URL address', 'wp-cred' ),
                );
                break;
            case 'colorpicker':
                $field_data_normalized['data']['validate']['hexadecimal'] = array(
                    'active' => $field_data['validate_format'],
                    'message' => __( 'Please use a valid hexadecimal value', 'wp-cred' ),
                );
                break;
            default:
                break;
        }

        return $field_data_normalized;
    }

}