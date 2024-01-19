<?php

class CRED_Translate_File_Command extends CRED_Translate_Field_Command_Base {

	function __construct( CRED_Translate_Field_Factory $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field ) {
		parent::__construct( $cred_translate_field_factory, $field_configuration, $field_type, $field_name, $field_value, $field_attributes, $field );

		$this->field_type = 'cred' . $this->field['type'];
	}

	public function execute() {
		global $post;
		if ( isset( $post ) ) {
			$attachments = get_children(
				array(
					'post_parent' => $post->ID,
					//'post_mime_type' => 'image',
					'post_type' => 'attachment',
				)
			);
		}
		if ( isset( $attachments ) ) {
			foreach ( $attachments as $attachment_post_id => $attachment ) {
				$file_url = $attachment->guid;
				if ( is_array( $this->field_value ) ) {
					foreach ( $this->field_value as $n => &$single_value ) {
						if ( ( isset( $single_value )
								&& ! empty( $single_value ) )
							&& basename( $file_url ) == basename( $single_value )
						) {
							$single_value = $file_url;
							break;
						}
					}
				} else {
					if ( ( isset( $this->field_value )
							&& ! empty( $this->field_value ) )
						&& basename( $file_url ) == basename( $this->field_value )
					) {
						$this->field_value = $file_url;
					}
				}
			}
		}

		$this->field_attributes['has_media_manager'] = ( toolset_getarr( $this->form->fields['form_settings']->form, 'has_media_manager' ) ? 1 : 0 );

		return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
	}
}
