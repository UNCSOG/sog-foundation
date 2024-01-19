<?php

class CRED_Translate_Image_Command extends CRED_Translate_Field_Command_Base {

	function __construct( CRED_Translate_Field_Factory $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field ) {
		parent::__construct( $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field );

		$this->field_type = 'cred' . $this->field['type'];
	}

	public function execute() {
		$can_accept_post_data = isset($this->additional_args['can_accept_post_data']) ? $this->additional_args['can_accept_post_data'] : false;
		$postData = isset($this->additional_args['postData']) ? $this->additional_args['postData'] : null;

		// show previous post featured image thumbnail
		if (
			$can_accept_post_data
			&& ! isset( $_POST['_featured_image'] )
			&& '_featured_image' == $this->field_name
		) {
			$this->field_value = '';
			if ( isset( $postData->extra['featured_img_html'] ) ) {
				// TODO I do not see this being used anywhere...
				$this->field_attributes['display_featured_html'] = $this->field_value = $postData->extra['featured_img_html'];
			}
		}

		$values_to_compare = array();
		if ( is_array( $this->field_value ) ) {
			foreach ( $this->field_value as $single_value ) {
				if (
					isset( $single_value )
					&& ! empty( $single_value )
				) {
					$values_to_compare[] = $single_value;
				}
			}
		} elseif (
			isset( $this->field_value )
			&& ! empty( $this->field_value )
		) {
			$values_to_compare[] = $this->field_value;
		}

		if ( count( $values_to_compare ) > 0 ) {
			$this->field_attributes['preview_thumbnail_url'] = $this->get_preview_thumbnail_urls( $values_to_compare );
		}

		$this->field_attributes['has_media_manager'] = ( toolset_getarr( $this->form->fields['form_settings']->form, 'has_media_manager' ) ? 1 : 0 );

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}

	/**
	 * Get the URL of the thumbnail size for an image, given its full URL.
	 *
	 * @param array $values_to_compare
	 * @return array
	 * @since 2.4
	 */
	private function get_preview_thumbnail_urls( $values_to_compare ) {
		$in_placeholder_array = array_fill( 0, count( $values_to_compare ), '%s' );
		$in_placeholder_string = implode( ',', $in_placeholder_array );

		$attribute = array();

		global $wpdb;
		$attachments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, guid FROM {$wpdb->posts}
				WHERE post_type = 'attachment'
				AND guid IN ({$in_placeholder_string})",
				$values_to_compare
			)
		);

		foreach ( $attachments as $file_field ) {
			if ( ! wp_attachment_is_image( $file_field->ID ) ) {
				continue;
			}
			$file_field_thumbnail_array = wp_get_attachment_image_src( $file_field->ID );
			$file_field_thumbnail = isset( $file_field_thumbnail_array[0] ) ?
				$file_field_thumbnail_array[0] :
				$file_field->guid;
			$hash_value = md5( $file_field->guid );
			$attribute[ $hash_value ] = $file_field_thumbnail;
		}

		return $attribute;
	}
}
