<?php

/**
 * Class CRED_Shortcode_Association_Field
 *
 * @since m2m
 */
class CRED_Shortcode_Association_Field extends CRED_Shortcode_Association_Base implements CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-relationship-field';

	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'name'  => '',// field name
		'class' => '',// classnames
		'style' => '',// extra inline styles
		'preview' => '',// preview mode for media fields
		'previewsize' => '',
		'value' => '',
		'urlparam' => '',
	);

	/**
	 * @var string|null
	 */
	private $user_content;

	/**
	 * @var array
	 */
	private $user_atts;

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	*
	* @return string
	*
	* @since m2m
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( empty( $this->user_atts['name'] ) ) {
			return;
		}

		$current_association = $this->helper->get_current_association();

		$definition_factory = Toolset_Field_Definition_Factory_Post::get_instance();
		$field_definition = $definition_factory->load_field_definition( $this->user_atts['name'] );

		if ( null === $field_definition ) {
			return;
		}

		$field = $field_definition->get_definition_array();

		if ( ! is_array( $field ) || ! isset( $field['id'] ) ) {
			// repeatable field group container, skip
			return;
		}

		if ( $field['type'] == 'post' ) {
			// post reference field: not supported for IPTs managed by relationship forms
			// follow Types development in case this changes
			return;
		}

		if ( in_array( $field['type'], array( 'audio', 'file', 'image', 'video' ) ) ) {
			$field['type'] = 'cred' . $field['type'];
		}

		if ( $current_association instanceof Toolset_Post ) {
			$meta   = get_post_meta( $current_association->get_id(), $field['meta_key'] );
			$config = wptoolset_form_filter_types_field( $field, $current_association->get_id() );
		} else {
			$meta = array();
			if ( ! empty( $this->user_atts['value'] ) ) {
				$meta[] = $this->user_atts['value'];
			} else if ( ! empty( $this->user_atts['urlparam'] ) ) {
				$url_value = toolset_getget( $this->user_atts['urlparam'] );
				if ( ! empty( $url_value ) ) {
					$meta[] = $url_value;
				}
			}
			$config = wptoolset_form_filter_types_field( $field );
		}
		$config = apply_filters( 'cred_form_field_config', $config );

		// Include the attributes in the field configuration,
		// especially needed in case of media fields and the preview attribute
		$config['attribute'] = $this->user_atts;

		$current_form_id = $this->get_frontend_form_flow()->get_current_form_id();

		return wptoolset_form_field( 'cred_relationship_form_' . $current_form_id, $config, $meta );

	}


}
