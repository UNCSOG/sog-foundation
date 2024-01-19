<?php

require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.textfield.php';

/**
 * Base Class that include the common behavior between audio/video/image/file Classes
 * @since 1.9.3
 */
class CRED_Abstract_WPToolset_Field_Credfile extends WPToolset_Field_Textfield {

	/**
	 * @var \OTGS\Toolset\CRED\Model\Wordpress\Media
	 */
	protected $media_model = null;

	/**
	 * Get the Forms WordPress Media model.
	 *
	 * @return \OTGS\Toolset\CRED\Model\Wordpress\Media
	 * @since 2.4
	 */
	protected function get_media_model() {
		if ( null === $this->media_model ) {
			$this->media_model = new \OTGS\Toolset\CRED\Model\Wordpress\Media();
		}

		return $this->media_model;
	}

	/**
	 * Check whether the native media manager can be used with this field.
	 *
	 * It depends on:
	 * - the user status: guest users can not use the native media manager.
	 * - the AJAX upload status: disabled AJAX upload can not use the native media manager.
	 *
	 * @todo Provide a filter or SETTING to control this by form.
	 *
	 * @return boolean
	 * @since 2.2
	 */
	private function can_use_native_media_manager() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		/**
		 * Legacy filter that disabled the deprecated jQuery upload plugin for AJAX uploads.
		 *
		 * While its name suggests it just disabled the upload bar,
		 * it disabled the whole frontend AJAX file upload mechanism.
		 *
		 * Keeping it for backwards compatibility, so it also disables the native media manager.
		 *
		 * @since 1.3.6.3
		 * @deprecated 2.2
		 */
		if ( apply_filters( 'cred_file_upload_disable_progress_bar', false ) ) {
			return false;
		}

		/**
		 * Globally disable the media manager for file fields.
		 *
		 * @param bool
		 * @since 2.4
		 */
		if ( apply_filters( 'toolset_forms_force_basic_file_fields', false ) ) {
			return false;
		}

		if ( ! toolset_getarr( $this->_data, 'has_media_manager', false ) ) {
			return false;
		}

		//return false;
		return true;
	}

	/**
	 * Get the default label for the Media Manager button when selecting a value.
	 *
	 * @return string
	 * @since 2.4
	 */
	protected function get_select_label() {
		return '';
	}

	/**
	 * Get the default label for the Media Manager button when editing a value.
	 *
	 * @return string
	 * @since 2.4
	 */
	protected function get_edit_label() {
		return '';
	}

	/**
	 * Get the preview default format.
	 *
	 * @return string
	 * @since 2.4
	 */
	protected function get_preview_format() {
		return 'filename';
	}

	protected function get_preview_size() {
		$attributes = $this->getAttr();
		$preview_size = toolset_getarr( $attributes, 'previewsize', '', array( '', 'thumbnail', 'full' ) );
		return $preview_size;
	}


	/**
	 * Get the field preview, based on the existence of a value, the field type and the field format.
	 *
	 * @param $value
	 * @param array $preview_thumbnails
	 *
	 * @return string
	 * @since 2.4
	 */
	private function get_preview( $value, $preview_thumbnails = array() ) {
		if ( empty( $value ) ) {
			return '';
		}

		switch ( $this->get_preview_format() ) {
			case 'url':
				return $value;
			case 'filename':
				return wp_basename( $value );
			case 'img':
				$image_src = $value;
				$preview_size = $this->get_preview_size();
				// Default preview size is thumbnail for existing images.
				if (
					'thumbnail' === $preview_size
					|| '' === $preview_size
				) {
					$image_hash = md5( $value );
					$image_src = isset( $preview_thumbnails[ $image_hash ] ) ?
						$preview_thumbnails[ $image_hash ] :
						$value;

				}
				return '<img src="' . esc_attr( $image_src ) . '" title="' . esc_attr( $value ) . '" alt="' . esc_attr( $value ) . '" />';
		}

		return '';
	}

	/**
	 * Generate the markup for the preview contaner and content.
	 *
	 * @param string $value
	 * @param array $preview_thumbnails
	 * @param string $mode
	 * @return array
	 * @since 2.4
	 */
	private function get_preview_markup( $value, $preview_thumbnails = array(), $mode = 'basic' ) {
		return array(
			'#type' => 'markup',
			'#markup' => sprintf(
				'<span class="wpt-credfile-preview js-wpt-credfile-preview js-toolset-media-field-preview" style="margin-bottom:10px;%s">'
					. '<span class="wpt-credfile-preview-item js-wpt-credfile-preview-item js-toolset-media-field-preview-item">%s</span>'
				. '</span>',
				( empty( $value ) ? 'display:none;' : '' ),
				$this->get_preview( $value, $preview_thumbnails )
			),
		);
	}

	/**
	 * Get the remove button that is placed inside the preview container.
	 *
	 * @param string $mode
	 * @return string
	 * @since 2.4
	 */
	private function get_remove_button( $mode, $value, $attributes ) {
		$events_classname = ( 'basic' === $mode )
			? 'js-toolset-media-basic-field-delete'
			: 'js-toolset-media-field-delete';
		$attributes = $this->getAttr();

		// Put the inline styles in anothr placeholder for sprint,
		// since the 100% bit can cause problems if used in the $markup.
		$inline_style = ( 'bootstrap' == toolset_getarr( $attributes, 'output' )
			? ''
			: 'width:100%;margin-top:2px;margin-bottom:2px;'
		);

		$markup = ( 'bootstrap' === toolset_getarr( $attributes, 'output' )
			? '<span role="button" data-action="delete" class="dashicons-before dashicons-no wpt-credfile-delete js-wpt-credfile-delete ' . esc_attr( $events_classname ) . '" title="%s" style="%s%s"></span>'
			: '<input type="button" data-action="delete" class="wpt-credfile-delete js-wpt-credfile-delete ' . esc_attr( $events_classname ) . '" value="%s" style="%s%s" />'
		);

		return sprintf(
			$markup,
			/* translators: Label for the button to delete the value of a media field in a frontend form */
			esc_attr( __( 'Clear value', 'wp-cred' ) ),
			$inline_style,
			( empty( $value ) ? 'display:none;' : '' )
		);
	}

	/**
	 * Build the field metaform.
	 *
	 * @return array
	 * @since unknown
	 */
	public function metaform() {
		if ( $this->can_use_native_media_manager() ) {
			return $this->metaform_with_native_media_manager();
		} else {
			return $this->metaform_with_native_html_file_inputs();
		}
	}


	/**
	 * Build the metaform for not logged in users, or when the native media manages is disabled.
	 *
	 * File fields will be rendered as native HTML file inputs.
	 * The structure will look like this:
	 * - File input, hidden if there is already a value for this field.
	 * - Field preview, hidden if the field holds no value yet.
	 *
	 * @return array
	 * @since 2.4
	 */
	public function metaform_with_native_html_file_inputs() {
		do_action( 'toolset_enqueue_scripts', array( CRED_Asset_Manager::SCRIPT_MEDIA_MANAGER_BASIC ) );

		$attributes = $this->getAttr();
		$value = $this->getValue();
		// Normalize an empty value: sometimes it is NULL on repeating fields.
		$value = ( empty( $value ) ) ? '' : $value;
		$name = $this->getName();
		$title = ( isset( $this->_data['title'] ) ) ? $this->_data['title'] : $name;
		$unique_id = sanitize_key( str_replace( 'wpcf-', '', $this->_data['id'] ) );

		// CRED_Translate_Image_Command generated a set of thumbnail previews for this field
		$preview_thumbnails = isset( $attributes['preview_thumbnail_url'] ) ? $attributes['preview_thumbnail_url'] : array();

		$form = array();

		// File field
		$form[] = array(
			'#type' => 'file',
			'#name' => $name,
			'#value' => $value,
			'#title' => $title,
			'#before' => '',
			'#after' => '',
			'#attributes' => array(
				'id' => $unique_id,
				'class' => "wpt-credfile-upload-file js-wpt-credfile-upload-file js-toolset-media-input-file-upload " . toolset_getarr( $attributes, 'class' ),
				'alt' => $value,
				'res' => $value,
				'style' => ( empty( $value ) ? '' : 'display:none' ),
			),
			'#validate' => $this->getValidationData(),
			'#repetitive' => $this->isRepetitive(),
		);

		// Hidden companion field, in case we are editing
		// MUST BE after the file input, so when the form is AJAX submitted
		// this one forces its value over the related file input
		$form[] = array(
			'#type' => 'hidden',
			'#name' => $name,
			'#value' => $value,
			'#attributes' => array(
				'id' => $unique_id . "_hidden",
				'class' => 'js-wpv-credfile-hidden',
				'data-wpt-type' => 'file',
			),
		);

		// Preview container, content and remove button
		$form[] = $this->get_preview_markup( $value, $preview_thumbnails );

		$form[] = array(
			'#type' => 'markup',
			'#markup' => '<div class="wpt-credfile-action js-wpt-credfile-action js-toolset-media-field-action">'
					. $this->get_remove_button( 'basic', $value, $attributes )
				. '</div>',
		);

		return $form;
	}

	/**
	 * Build the metaform for logged in users when the native media manager is not disabled.
	 *
	 * File fields will be rendered as with the following HTML struvture.
	 * - Field value input, hidden.
	 * - File preview, hidden if the field holds no value yet.
	 * - Button to set or modify the field value.
	 *
	 * @return array
	 * @since 2.4
	 */
	public function metaform_with_native_media_manager() {
		// Make sure we have the required assets
		wp_enqueue_media();
		wp_enqueue_style( 'media' );
		do_action( 'toolset_enqueue_scripts', array( CRED_Asset_Manager::SCRIPT_MEDIA_MANAGER ) );

		$attributes = $this->getAttr();
		$value = $this->getValue();
		// Normalize an empty value: sometimes it is NULL on repeating fields.
		$value = ( empty( $value ) ) ? '' : $value;
		$name = $this->getName();
		$title = ( isset( $this->_data['title'] ) ) ? $this->_data['title'] : $name;

		// CRED_Translate_Image_Command generated a set of thumbnail previews for this field
		$preview_thumbnails = isset( $attributes['preview_thumbnail_url'] ) ? $attributes['preview_thumbnail_url'] : array();

		$post_id = 0;

		// On user and relationship forms, the media attachments parent is zero;
		// on post forms, the media attachment parent is the current global post.
		if ( \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE === apply_filters( 'toolset_forms_frontend_flow_get_current_form_type', null ) ) {
			global $post;
			if (
				is_object( $post )
				&& property_exists( $post, 'ID' )
			) {
				$post_id = $post->ID;
			}
		}

		$form = array();

		$form[] = array(
			'#type' => 'hidden',
			'#name' => $name,
			'#value' => $value,
			'#attributes' => array(
				'class' => 'js-toolset-media-field-hidden',
				'data-wpt-type' => 'file',
			),
			'#validate' => $this->getValidationData(),
		);

		$form[] = $this->get_preview_markup( $value, $preview_thumbnails );

		$meta_data = array(
			'metakey' => $name,
			'title' => $title,
			'parent' => $post_id,
			'type' => str_replace( 'cred', '', $this->_data['type'] ),
			'multiple' => $this->isRepetitive(),
			'preview' => $this->get_preview_format(),
			'previewsize' => $this->get_preview_size(),
			'select_label' => $this->get_select_label(),
			'edit_label' => $this->get_edit_label(),
		);

		$form[] = array(
			'#type' => 'markup',
			'#markup' => sprintf(
				'<div class="wpt-credfile-action js-wpt-credfile-action js-toolset-media-field-action">'
					. '%s'
					. '<input type="button" class="js-toolset-media-field-trigger" data-meta="%s" value="%s" />'
				. '</div>',
				$this->get_remove_button( 'managed', $value, $attributes ),
				esc_attr( json_encode( $meta_data ) ),
				( empty( $value ) ? esc_attr( $this->get_select_label() ) : esc_attr( $this->get_edit_label() ) )
			),
		);

		return $form;
	}

	/**
	 * Get an error message qhen trying to upload a file in a non supported format.
	 *
	 * @return string
	 * @since 2.4
	 */
	protected function get_extension_error() {
		$media_model = $this->get_media_model();
		return $media_model->get_upload_validation_error_message();
	}

	/**
	 * Get an error message qhen trying to upload a file that it too big for the server.
	 *
	 * @return string
	 * @since 2.4
	 */
	protected function get_file_size_error() {
		/* translators: Validation message when trying to upload a file that is too big */
		return __( 'You cannot upload a file of this size', 'wp-cred' );
	}

	/**
	 * Define validation methods for file-related fields, including:
	 * - Validation per mime type.
	 * - Validation per file extension.
	 *
	 * @param array $validation Validation Array used in Metaform Field
	 * @param string $field_upload_type Type of Field Upload (video|audio|image|file)
	 * @since 1.9.3
	 */
	protected function set_allowed_extensions_validation_by_field_upload_type( &$validation, $field_upload_type = 'file' ) {
		$media_model = $this->get_media_model();

		$allowed_field_upload_mime_types = $media_model->get_valid_mime_types( $field_upload_type );
		$allowed_field_upload_extensions = $media_model->get_valid_extensions( $field_upload_type );

		// Set validation mime_type array
		$validation['mime_type'] = array(
			'args' => array(
				'mime_type',
				implode( '|', $allowed_field_upload_mime_types ),
			),
			'message' => $this->get_extension_error(),
		);

		// Set validation extension array
		// Types uses a custom list of valid extensions,
		// But we are better off using the supported WordPress one.
		$validation['extension'] = array(
			'args' => array(
				'extension',
				implode( '|', $allowed_field_upload_extensions ),
			),
			'message' => $this->get_extension_error(),
		);

		// Include size validation when using HTML file inputs
		if ( ! $this->can_use_native_media_manager() ) {
			$validation['credfilesize'] = array(
				'args' => array(
					'credsize',
					wp_max_upload_size(),
				),
				'message' => $this->get_file_size_error(),
			);
			$validation['credfiletype'] = array(
				'args' => array(
					'credsize',
					$field_upload_type,
				),
				'message' => $this->get_extension_error(),
			);
		}
	}

}
