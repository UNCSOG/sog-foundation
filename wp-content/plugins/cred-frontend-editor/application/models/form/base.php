<?php

use \OTGS\Toolset\CRED\Controller\FormAction\Message\Base as MessageBase;

/**
 * Base Class for CRED Post and User Forms
 */
abstract class CRED_Form_Base implements ICRED_Form_Base {

	protected $_form_id;
	protected $_form_count;
	protected $_post_id;
	protected $_preview;

	/**
	 * @var false|string [CRED_FORMS_CUSTOM_POST_NAME|CRED_USER_FORMS_CUSTOM_POST_NAME]
	 */
	protected $_type_form;
	/**
	 * @var CRED_Post_Data
	 */
	public $_postData;
	/**
	 * @var CRED_Form_Data
	 */
	public $_formData;
	/**
	 * @var CRED_Form_Rendering
	 */
	public $_cred_form_rendering;
	/**
	 * @var null|object
	 */
	public $_shortcodeParser;
	/**
	 * @var CRED_Form_Builder_Helper
	 */
	public $_formHelper;
	/**
	 * @var string
	 */
	public $_content;
	/**
	 * @var string
	 */
	protected $_post_type;
	/**
	 * @var bool|void
	 */
	protected $_disable_progress_bar;
	/**
	 * @var bool
	 */
	public static $_self_updated_form = false;

	/**
	 * Original Form content
	 */
	private $original_form_content;

	/**
	 * CRED_Form_Base constructor.
	 *
	 * @param int $form_id
	 * @param int|bool $post_id
	 * @param int $form_count
	 * @param bool $preview
	 */
	public function __construct( $form_id, $post_id = false, $form_count = 0, $preview = false ) {
		$this->_form_id = $form_id;
		$this->_post_id = $post_id;
		$this->_form_count = $form_count;
		$this->_preview = $preview;
		$this->_type_form = get_post_type( $form_id );
		$this->_formData = new CRED_Form_Data( $this->_form_id, $this->_type_form, $this->_preview );

		// shortcodes parsed by custom shortcode parser
		$this->_shortcodeParser = CRED_Loader::get( 'CLASS/Shortcode_Parser' );

		// various functions performed by custom form helper
		require_once CRED_ABSPATH . '/library/toolset/cred/embedded/classes/Form_Builder_Helper.php';
		$this->_formHelper = new CRED_Form_Builder_Helper( $this ); //CRED_Loader::get('CLASS/Form_Helper', $this);
		$this->_disable_progress_bar = (bool) apply_filters( 'cred_file_upload_disable_progress_bar', false );
	}

	/**
	 * @return int
	 */
	public function get_form_id() {
		return $this->_form_id;
	}

	/**
	 * @param int $form_id
	 */
	public function set_form_id( $form_id ) {
		$this->_form_id = $form_id;
	}

	/**
	 * @return int
	 */
	public function get_form_count() {
		return $this->_form_count;
	}

	/**
	 * @param int $form_count
	 */
	public function set_form_count( $form_count ) {
		$this->_form_count = $form_count;
	}

	/**
	 * @return bool|int
	 */
	public function get_post_id() {
		return $this->_post_id;
	}

	/**
	 * @param bool|int $post_id
	 */
	public function set_post_id( $post_id ) {
		$this->_post_id = $post_id;
	}

	/**
	 * @return bool
	 */
	public function is_preview() {
		return $this->_preview;
	}

	/**
	 * @param bool $preview
	 */
	public function set_preview( $preview ) {
		$this->_preview = $preview;
	}

	/**
	 * @return false|string
	 */
	public function get_type_form() {
		return $this->_type_form;
	}

	/**
	 * @param false|string $type_form
	 */
	public function set_type_form( $type_form ) {
		$this->_type_form = $type_form;
	}

	/**
	 * @return CRED_Form_Data
	 */
	public function get_form_data() {
		return $this->_formData;
	}

	/**
	 * @return CRED_Post_Data
	 */
	public function get_post_data() {
		return $this->_postData;
	}

	/**
	 * @param int $object_id
	 * @since 2.4
	 */
	abstract protected function set_object_data( $object_id );

	/**
	 * @return CRED_Form_Builder_Helper
	 */
	public function get_form_helper() {
		return $this->_formHelper;
	}

	/**
	 * @return CRED_Form_Rendering
	 */
	public function get_form_rendering() {
		return $this->_cred_form_rendering;
	}

	/**
	 * @param string $form_type
	 * @param int $form_id
	 * @param object|array|bool $object_data
	 * @return bool
	 */
	abstract public function check_form_access( $form_type, $form_id, $object_data );

	/**
	 * @param object $form_fields
	 * @return string
	 * @since 2.4
	 */
	protected function get_form_access_error_message( $form_fields ) {
		$message_id = 'cred_message_access_error_can_not_use_form';
		// Permission denied
		$form_extra_settings = toolset_getarr( $form_fields, 'extra', new stdClass() );
		$form_messages = isset( $form_extra_settings->messages ) ? $form_extra_settings->messages : array();
		$error_message = toolset_getarr( $form_messages, $message_id, '' );

		if ( empty( $error_message ) ) {
			return '';
		}

		return cred_translate(
			CRED_Form_Builder_Helper::MSG_PREFIX . $message_id, $error_message, 'cred-form-' . $this->_formData->getForm()->post_title . '-' . $this->_form_id
		);
	}

	/**
	 * @global int $post
	 * @global WP_User $authordata
	 *
	 * @return boolean|WP_Error
	 */
	public function print_form() {
		add_filter( 'wp_revisions_to_keep', '__return_zero', 10, 2 );

		$bypass_form = apply_filters( 'cred_bypass_process_form_' . $this->_form_id, false, $this->_form_id, $this->_post_id, $this->_preview );
		$bypass_form = apply_filters( 'cred_bypass_process_form', $bypass_form, $this->_form_id, $this->_post_id, $this->_preview );

		if ( is_wp_error( $this->_formData ) ) {
			return $this->_formData;
		}

		$formHelper = $this->_formHelper;
		$form = $this->_formData;
		$form_fields = $form->getFields();
		$_form_type = $form_fields[ 'form_settings' ]->form[ 'type' ];
		$_post_type = $form_fields[ 'form_settings' ]->post[ 'post_type' ];

		$this->set_authordata();

		$this->set_object_data( $this->_post_id );

		// check if user has access to this form
		if ( ! $this->_preview
			&& ! $this->check_form_access( $_form_type, $this->_form_id, $this->_postData )
		) {
			return new WP_Error(
				'cred-permission',
				$this->get_form_access_error_message( $form_fields )
			);
		}

		$result = $this->create_new_post( $this->_form_id, $_form_type, $this->_post_id, $_post_type );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// set allowed file types
		CRED_StaticClass::$_staticGlobal[ 'MIMES' ] = $formHelper->getAllowedMimeTypes();

		// get custom post fields
		$fields_settings = $formHelper->getFieldSettings( $_post_type );

		// strip any unneeded parsms from current uri
		$actionUri = $formHelper->currentURI( array(
			'_tt' => time()       // add time get bypass cache
		), array(
				'_success', // remove previous success get if set
				'_success_message'   // remove previous success get if set
			)
		);

		$prg_form_id = $this->createPrgID( $this->_form_id );
		$html_form_id = $this->createFormID( $this->_form_id, $prg_form_id );

		$cred_form_rendering = new CRED_Form_Rendering( $this->_form_id, $html_form_id, $_form_type, $this->_post_id, $actionUri, $this->_preview, $this->is_submitted() );

		$this->_cred_form_rendering = $cred_form_rendering;
		$this->_cred_form_rendering->setFormHelper( $formHelper );
		$this->_cred_form_rendering->setLanguage( CRED_StaticClass::$_staticGlobal[ 'LOCALES' ] );

		if ( is_wp_error( $this->_cred_form_rendering ) ) {
			return $this->_cred_form_rendering;
		}

		// all fine here
		$this->_post_type = $_post_type;
		$this->_content = $form->getForm()->post_content;

		CRED_StaticClass::$out[ 'fields' ] = $fields_settings;
		CRED_StaticClass::$out[ 'prg_id' ] = $prg_form_id;

		//####################################################################################//

		$cred_form_rendering->_formData = $form;

		$fields = $form->getFields();
		$cred_form_rendering->extra_parameters = $form_fields[ 'extra' ];

		$form_id = $this->_form_id;
		$form_type = $fields[ 'form_settings' ]->form[ 'type' ];

		$form_use_ajax = ( isset( $fields[ 'form_settings' ]->form[ 'use_ajax' ] ) && $fields[ 'form_settings' ]->form[ 'use_ajax' ] == 1 ) ? true : false;
		$is_ajax = $this->is_cred_ajax( $form_use_ajax );

		$prg_id = CRED_StaticClass::$out[ 'prg_id' ];
		$form_name = $html_form_id;
		$post_type = $fields[ 'form_settings' ]->post[ 'post_type' ];

		// This will change once the form rendering and saving are separated
		// and moved to the frontend shortcode outcome for each form type.
		// This is indeed a sensitive part of the form shortcode output itself.
		$form_action_factory = new \OTGS\Toolset\CRED\Controller\FormAction\Factory();
		$message_controller = $form_action_factory->get_message_controller_by_form_type( $this->_type_form );

		// show display message from previous submit of same create form (P-R-G pattern)
		if (
			! $cred_form_rendering->preview
			&&  isset( $_GET[ '_success_message' ] )
			&& $_GET[ '_success_message' ] == $prg_id
			&& 'message' == $form_fields[ 'form_settings' ]->form[ 'action' ]
		) {
			$cred_form_rendering->is_submit_success = true;

			if ( $message_controller instanceof MessageBase ) {
				return $is_ajax
					// Return something that the JSON rsponse can deal with,
					// similar to what CRED_Generic_Response produces for AJAX
					? array(
						'output' => $message_controller->get_action_message( $form ),
						'result' => 'ok',
					)
					: $message_controller->get_action_message( $form );
			}

			return '';
		}

		$cred_form_rendering->is_submit_success = $this->is_submitted();

		// no message to display if not submitted
		$message = false;

		$current_form = array(
			'id' => $form_id,
			'post_type' => $post_type,
			'form_type' => $form_type,
			'form_html_id' => '#' . $form_name,
		);

		CRED_StaticClass::$_current_post_title = $form->getForm()->post_title;
		CRED_StaticClass::$_current_form_id = $form_id;

		/**
		 * fix dates
		 */
		$this->adodb_date_fix_date_and_time();

		$mime_types = wp_get_mime_types();
		CRED_StaticClass::$_allowed_mime_types = array_merge( $mime_types, array( 'xml' => 'text/xml' ) );
		CRED_StaticClass::$_allowed_mime_types = apply_filters( 'upload_mimes', CRED_StaticClass::$_allowed_mime_types );

		/**
		 * sanitize input data
		 */
		if ( ! array_key_exists( 'post_fields', CRED_StaticClass::$out[ 'fields' ] ) ) {
			CRED_StaticClass::$out[ 'fields' ][ 'post_fields' ] = array();
		}

		/**
		 * fixed Server side error messages should appear next to the field with the problem
		 */
		$formHelper->checkFilePost( $cred_form_rendering, CRED_StaticClass::$out[ 'fields' ][ 'post_fields' ] );
		if ( isset( CRED_StaticClass::$out[ 'fields' ][ 'post_fields' ] ) && isset( CRED_StaticClass::$out[ 'form_fields_info' ] ) ) {
			$formHelper->checkFilesType( CRED_StaticClass::$out[ 'fields' ][ 'post_fields' ], CRED_StaticClass::$out[ 'form_fields_info' ], $cred_form_rendering, $validation_errors );
		}

		CRED_StaticClass::$_reset_file_values = ( $is_ajax && $form_type == 'new' && $form_fields[ 'form_settings' ]->form[ 'action' ] == 'form' && self::$_self_updated_form );

		$cloned = false;
		if ( isset( $_POST ) && ! empty( $_POST ) ) {
			$cloned = true;
			$temp_post = $_POST;
		}

		if ( ! self::$_self_updated_form ) {
			CRED_Frontend_Preserve_Taxonomy_Input::initialize();
		} else {
			CRED_Frontend_Preserve_Taxonomy_Input::get_instance()->remove_filters();
		}

		$this->try_to_reset_submit_post_fields();

		// Sadly, I have to get the original form content in order to rebuild the shortcodes if something is wrong during validation
		// @refactor EVERYTHING
		$this->original_form_content = $this->_content;
		$this->build_form();

		if (
			// We are not redirecting to itself a successfully POSTed form
			$prg_id != toolset_getget( '_success' )
			// Or the POSTed form, if any, does not match the currently rendering one
			&& (
				$form_id != toolset_getpost( CRED_StaticClass::PREFIX . 'form_id' )
				|| apply_filters( 'toolset_forms_frontend_flow_get_form_index', 1 ) != toolset_getpost( CRED_StaticClass::PREFIX . 'form_count' )
			)
		) {
			$output = $this->do_render_form();
			$cred_response = new CRED_Generic_Response( CRED_Generic_Response::CRED_GENERIC_RESPONSE_RESULT_OK, $output, $is_ajax, $current_form, $formHelper );
			return $cred_response->show();
		}

		if ( $cloned ) {
			$_POST = $temp_post;
		}

		$num_errors = 0;

		// TODO Check why would we need to validated a post that has succeeded POSTing and is redirecting to itself!?
		// Apparently i that case validation fails anyway, so everything is not lost
		$validate = ( self::$_self_updated_form ) ? true : $this->validate_form( $validation_errors );

		// I am ashamed of adding this code here, but after 1 day trying to find a proper place, I realize it is impossible to do that
		// This method is called print_form but it handles everything: validation, saving, sending notifications, ...
		// I can't do it in a different way because vars by reference are all around
		if ( ! $validate ) {
			// Cleanup submitting data
			$this->cleanup_submitted_fields_data();
		}

		if ( $form_use_ajax ) {
			$bypass_form = self::$_self_updated_form;
		}

		if ( ! $bypass_form
			&& $validate
		) {
			if ( ! $cred_form_rendering->preview ) {
				// save post data
				$bypass_save_form_data = apply_filters( 'cred_bypass_save_data_' . $form_id, false, $form_id, $this->_post_id, $current_form );
				$bypass_save_form_data = apply_filters( 'cred_bypass_save_data', $bypass_save_form_data, $form_id, $this->_post_id, $current_form );

				if ( ! $bypass_save_form_data ) {
					$model = CRED_Loader::get( 'MODEL/Forms' );

					$attachedData = $this->get_attached_data( $form, $fields, $model );

					$post_id = $this->save_form( $this->_post_id );

					// enable notifications and notification events if any
					$this->notify( $post_id, $attachedData );
					unset( $attachedData );
				}

				if ( is_wp_error( $post_id ) ) {
					$num_errors ++;
					$cred_form_rendering->add_field_message( $post_id->get_error_message(), 'Post Title' );
				} else {
					// Relationships
					// We need to update relationship always after creating/updating posts specially for create post
					// The downside of this is that relatinship/post reference fields will never produce submit error even if they fail
					$this->save_any_relationships_by_id( $post_id, $cred_form_rendering );

					// Set the target URL parameter so custom sucess messages
					// can get data for the right object, when the form is submittd with AJAX
					if ( $is_ajax ) {
						$_GET['_target'] = $post_id;
					}

					$result = $this->check_redirection( $post_id, $form_id, $form, $fields, $current_form, $formHelper, $is_ajax );
					if ( $result != false ) {
						return $result;
					} else {
						$this->add_field_messages_by_files( $cred_form_rendering, $formHelper );
					}
				}
			} else {
				$cred_form_rendering->add_field_message( __( 'Preview Form submitted', 'wp-cred' ) );
			}
		} elseif ( $this->is_submitted() ) {
			//Reset form_count in case of failed validation
			$this->set_submitted_form_messages( $form_id, $form_name, $num_errors, $cred_form_rendering, $formHelper, $message_controller );
		}

		if (
			(
				isset( $_GET[ '_success' ] )
				&& $_GET[ '_success' ] == $prg_id
			)
			|| (
				$is_ajax
				&& self::$_self_updated_form
			)
		) {
			if ( isset( $_GET[ '_target' ] )
				&& is_numeric( $_GET[ '_target' ] )
			) {
				$post_id = $_GET[ '_target' ];
			}

			$saved_message = $message_controller->get_message_by_id( $formHelper->get_form_data(), 'post_saved' );

			if ( isset( $post_id )
				&& is_int( $post_id )
			) {
				// add success message from previous submit of same any form (P-R-G pattern)
				$saved_message = apply_filters( 'cred_data_saved_message_' . $form_id, $saved_message, $form_id, $post_id, $this->_preview );
				$saved_message = apply_filters( 'cred_data_saved_message', $saved_message, $form_id, $post_id, $this->_preview );
			}

			//$zebraForm->add_form_message('data-saved', $saved_message);
			$cred_form_rendering->add_success_message( $saved_message );
		}

		if ( $validate
			&& $is_ajax
			&& ! self::$_self_updated_form
		) {
			self::$_self_updated_form = true;
			return $this->print_form();
		} else {

			$messages = $cred_form_rendering->getFieldsSuccessMessages( ( $is_ajax ? $form_name : "" ) );
			$messages .= $cred_form_rendering->getFieldsErrorMessages();
			$messages .= $cred_form_rendering->get_warning_messages();
			$js_messages = $cred_form_rendering->getFieldsErrorMessagesJs();

			$output = ( false !== $message ) ? $message : $this->do_render_form( $messages, $js_messages );

			$cred_response = new CRED_Generic_Response( $num_errors > 0 ? CRED_Generic_Response::CRED_GENERIC_RESPONSE_RESULT_KO : CRED_Generic_Response::CRED_GENERIC_RESPONSE_RESULT_OK, $output, $is_ajax, $current_form, $formHelper );

			return $cred_response->show();
		}
	}

	/**
	 * Check whether the current form has been submitted with AJAX.
	 *
	 * @param bool $form_uses_ajax
	 * @return bool
	 * @since unknown
	 * @since 2.4 Make sure it only returns TRUE if the current form can be and has been submitted with AJAX.
	 */
	private function is_cred_ajax( $form_uses_ajax ) {
		if ( false === $form_uses_ajax ) {
			return false;
		}

		return ( $this->is_ajax_submitted() && $this->is_form_submitted() );
	}

	/**
	 * Function used to reset $_POST during a AJAX CRED Form submition elaboration
	 */
	private function try_to_reset_submit_post_fields() {
		if ( CRED_StaticClass::$_reset_file_values ) {

			//Reset post fields
			foreach ( CRED_StaticClass::$out[ 'fields' ][ 'post_fields' ] as $field_key => $field_value ) {
				$field_name = isset( $field_value[ 'plugin_type_prefix' ] ) ? $field_value[ 'plugin_type_prefix' ] . $field_key : $field_key;
				if ( isset( $_POST[ $field_name ] ) ) {
					unset( $_POST[ $field_name ] ); // = array();
				}
			}

			if ( isset ( CRED_StaticClass::$out[ 'fields' ][ 'user_fields' ] ) ) {
				//Reset user fields
				foreach ( CRED_StaticClass::$out[ 'fields' ][ 'user_fields' ] as $field_key => $field_value ) {
					$field_name = isset( $field_value[ 'plugin_type_prefix' ] ) ? $field_value[ 'plugin_type_prefix' ] . $field_key : $field_key;
					if ( isset( $_POST[ $field_name ] ) ) {
						unset( $_POST[ $field_name ] ); // = array();
					}
				}
			}

			if ( isset ( CRED_StaticClass::$out[ 'fields' ][ 'post_reference_fields' ] ) ) {
				//Reset pr fields
				foreach ( CRED_StaticClass::$out[ 'fields' ][ 'post_reference_fields' ] as $field_key => $field_value ) {
					if ( isset( $_POST[ $field_key ] ) ) {
						unset( $_POST[ $field_key ] ); // = array();
					}
				}
			}

			if ( isset ( CRED_StaticClass::$out[ 'fields' ][ 'relationships' ] ) ) {
				//Reset relationships fields
				foreach ( CRED_StaticClass::$out[ 'fields' ][ 'relationships' ] as $field_key => $field_value ) {
					$field_name = str_replace( '.', '_', $field_key );
					if ( isset( $_POST[ $field_name ] ) ) {
						unset( $_POST[ $field_name ] ); // = array();
					}
				}
			}

			if ( isset( $_POST[ '_featured_image' ] ) ) {
				unset( $_POST[ '_featured_image' ] );
			}

			foreach ( CRED_StaticClass::$out[ 'fields' ][ 'taxonomies' ] as $field_key => $field_value ) {
				if ( isset( $_POST[ $field_key ] ) ) {
					unset( $_POST[ $field_key ] ); // = array();
				}
			}

			/**
			 * According to $_reset_file_values we need to force reseting taxonomy/taxonomyhierarchical
			 */
			add_filter( 'toolset_filter_taxonomyhierarchical_terms', '__return_empty_array', 10, 0 );
			add_filter( 'toolset_filter_taxonomy_terms', '__return_empty_array', 10, 0 );
		}
	}

	/**
	 * Add field messages from $_FILES
	 *
	 * @param $zebraForm
	 * @param $formHelper
	 */
	private function add_field_messages_by_files( $zebraForm, $formHelper ) {
		if ( isset( $_FILES ) && count( $_FILES ) > 0 ) {
			// TODO check if this wp_list_pluck works with repetitive files... maybe in_array( array(1), $errors_on_files ) does the trick...
			$errors_on_files = wp_list_pluck( $_FILES, 'error' );
			$zebraForm->add_field_message( ( in_array( 1, $errors_on_files ) || in_array( 2, $errors_on_files ) ) ? $formHelper->getLocalisedMessage( 'no_data_submitted' ) : $formHelper->getLocalisedMessage( 'post_not_saved' ) );
		} else {
			// else just show the form again
			$zebraForm->add_field_message( $formHelper->getLocalisedMessage( 'post_not_saved' ) );
		}
	}

	/**
	 * Set field messages on submitted form
	 *
	 * @param $form_id
	 * @param $form_name
	 * @param $num_errors
	 * @param $zebraForm
	 * @param $formHelper
	 * @param MessageBase $message_controller Message controller
	 */
	private function set_submitted_form_messages( $form_id, $form_name, &$num_errors, $zebraForm, $formHelper, $message_controller ) {
		$top_messages = isset( $zebraForm->top_messages[ $form_name ] ) ? $zebraForm->top_messages[ $form_name ] : array();
		$num_errors = count( $top_messages );
		$form_data = $formHelper->get_form_data();
		if ( empty( $_POST ) ) {
			$num_errors ++;
			$not_saved_message = $message_controller->get_message_by_id( $form_data, 'no_data_submitted' );
		} else {
			if ( count( $top_messages ) == 1 ) {
				$temporary_messages = str_replace( "<br />%PROBLEMS_UL_LIST", "", $message_controller->get_message_by_id( $form_data, 'post_not_saved_singular' ) );
				$not_saved_message = $temporary_messages . "<br />%PROBLEMS_UL_LIST";
			} else {
				$temporary_messages = str_replace( "<br />%PROBLEMS_UL_LIST", "", $message_controller->get_message_by_id( $form_data, 'post_not_saved_plural' ) );
				$not_saved_message = $temporary_messages . "<br />%PROBLEMS_UL_LIST";
			}

			$error_list = '<ul>';
			foreach ( $top_messages as $id_field => $text ) {
				$error_list .= '<li>' . $text . '</li>';
			}
			$error_list .= '</ul>';
			$not_saved_message = str_replace( array( '%PROBLEMS_UL_LIST', '%NN' ), array(
				$error_list,
				count( $top_messages ),
			), $not_saved_message );
		}
		$not_saved_message = apply_filters( 'cred_data_not_saved_message_' . $form_id, $not_saved_message, $form_id, $this->_post_id, $this->_preview );
		$not_saved_message = apply_filters( 'cred_data_not_saved_message', $not_saved_message, $form_id, $this->_post_id, $this->_preview );

		$zebraForm->add_field_message( $not_saved_message );
	}

	/**
	 * Check whether the saved object is valid before moving forward.
	 *
	 * Currently used in check_redirection before calculating the action tot ake.
	 *
	 * @param mixed $object_id
	 * @return bool|int
	 */
	abstract protected function validate_saved_object( $object_id );

	/**
	 * Decide whether the form will be redirected, and execute the right action.
	 *
	 * If the form is to be redirected on submit, compose the URL to redirect to,
	 * and pass the information to a proper CRED_Generic_Response, return what it shows.
	 *
	 * If there should be no redirection, just print the right form message on re-rendering.
	 *
	 * Note that a form will be redirected when:
	 * - Set to redirect to the submitted post (for post forms), or to a specific post or page.
	 * - Set to load a message or re-render the form, when the form was not submitted with AJAX.
	 *     (this redirects to the current page with some special URL parameters).
	 * - Set to execute a custom action which ends up being a redirection.
	 *     (like when Forms Commerce redirects to the cart or the checkout page).
	 *
	 * In other words: the form will NOT redirect when printing a message or re-printing the form,
	 * but only if it is submitted with AJAX.
	 *
	 * @param int|bool $object_id
	 * @param int $form_id
	 * @param object $form
	 * @param object $_fields
	 * @param object $thisform
	 * @param CRED_Helper $formHelper
	 * @param bool $is_ajax
	 * @return bool|string
	 */
	public function check_redirection( $object_id, $form_id, $form, $_fields, $thisform, $formHelper, $is_ajax ) {
		$object_id = $this->validate_saved_object( $object_id );
		if ( false === $object_id ) {
			return false;
		}

		$form_slug = $form->getForm()->post_name;
		do_action( 'cred_submit_complete_form_' . $form_slug, $object_id, $thisform );
		do_action( 'cred_submit_complete_' . $form_id, $object_id, $thisform );
		do_action( 'cred_submit_complete', $object_id, $thisform );

		// Bypass the action to execute after submitting the form:
		// make any of those filters return a truthy value will halt redirection/message,
		// and the action will default to "Keep displaying the form".
		$bypass_credaction = apply_filters( 'cred_bypass_credaction_' . $form_id, false, $form_id, $object_id, $thisform );
		$bypass_credaction = apply_filters( 'cred_bypass_credaction', $bypass_credaction, $form_id, $object_id, $thisform );

		$credaction = ( false !== $bypass_credaction )
			? \OTGS\Toolset\CRED\Controller\Redirection\RedirectionManager::ACTION_FORM
			: $_fields['form_settings']->form['action'];
		$url = false;

		$redirection_manager = new \OTGS\Toolset\CRED\Controller\Redirection\RedirectionManager(
			$object_id,
			$form_id,
			$form,
			$is_ajax
		);

		if ( ! in_array( $credaction, $redirection_manager->get_native_redirection_actions(), true ) ) {
			// Backward compatibility: hooks when executing a custom action.
			// Note that redirection can not happen there because orms can be submitted with and without AJAX.
			do_action( 'cred_custom_success_action_' . $form_id, $credaction, $object_id, $thisform, $is_ajax );
			do_action( 'cred_custom_success_action', $credaction, $object_id, $thisform, $is_ajax );
		}

		$url = $redirection_manager->get_redirection_url( $credaction );
		if (
			false === $url
			&& ! $is_ajax
		) {
			// If we did not manage to compose an URL to redirect to yet,
			// and this is not an AJAX submitted form,
			// act like in 'form' === $credaction.
			$url = $redirection_manager->get_redirection_url( \OTGS\Toolset\CRED\Controller\Redirection\RedirectionManager::ACTION_FORM );
		}

		// Do the redirection if we managed to compose an URL.
		// Note that CRED_Generic_Response will take care of AJAX submitted forms.
		if ( false !== $url ) {
			if ( ! in_array( $credaction, $redirection_manager->get_reload_redirection_actions(), true ) ) {
				// Filter the redirection URL, if any, when a proper redirection action is taken.
				// In other words, do not modify reload redirections.
				$url = apply_filters( 'cred_success_redirect_form_' . $form_slug, $url, $object_id, $thisform );
				$url = apply_filters( 'cred_success_redirect_' . $form_id, $url, $object_id, $thisform );
				$url = apply_filters( 'cred_success_redirect', $url, $object_id, $thisform );
			}

			if ( false !== $url ) {
				$url = add_query_arg( 'cred_referrer_form_id', $form_id, $url );
				$redirect_delay = $_fields['form_settings']->form['redirect_delay'];
				$cred_response = new CRED_Generic_Response( CRED_Generic_Response::CRED_GENERIC_RESPONSE_RESULT_REDIRECT, $url, $is_ajax, $thisform, $formHelper, $redirect_delay );
				return $cred_response->show();
			}
		}

		// Otherwise, tell the form to generate the right message and print it.
		$saved_message = $formHelper->getLocalisedMessage( 'post_saved' );
		$saved_message = apply_filters( 'cred_data_saved_message_' . $form_id, $saved_message, $form_id, $object_id, $this->_preview );
		$saved_message = apply_filters( 'cred_data_saved_message', $saved_message, $form_id, $object_id, $this->_preview );
		$this->_cred_form_rendering->add_success_message( $saved_message );

		return false;
	}

	abstract public function set_authordata();

	abstract public function build_form();

	/**
	 * Adding for each cred form all the relative custom cred form assets css and js
	 * in a common cache, in order to be used by CRED_Asset_Manager
	 *
	 * @param array $fields_extra
	 *
	 * @since 1.9.3
	 */
	protected function cache_css_and_js_assets( $fields_extra ) {
		// Set cache variable for all forms ( Custom JS)
		$js_content = $fields_extra->js;
		if ( ! empty( $js_content ) ) {
			static $custom_js_cache;
			if ( ! isset( $custom_js_cache ) ) {
				$custom_js_cache = array();
			}
			$custom_js_cache[ $this->_form_id ] = "\n\n" . $js_content;
			wp_cache_set( 'cred_custom_js_cache', $custom_js_cache );
		}

		// Set cache variable for all forms ( Custom CSS)
		$css_content = $fields_extra->css;
		if ( ! empty( $css_content ) ) {
			static $custom_css_cache;
			if ( ! isset( $custom_css_cache ) ) {
				$custom_css_cache = array();
			}
			$custom_css_cache[ $this->_form_id ] = "\n\n" . $css_content;
			wp_cache_set( 'cred_custom_css_cache', $custom_css_cache );
		}
	}

	/**
	 * @param string $messages
	 * @param string $js_messages
	 *
	 * @return mixed
	 */
	public function do_render_form( $messages = "", $js_messages = "" ) {
		$shortcodeParser = $this->_shortcodeParser;
		$zebraForm = $this->_cred_form_rendering;

		$shortcodeParser->remove_all_shortcodes();

		$zebraForm->render();
		// post content area might contain shortcodes, so return them raw by replacing with a dummy placeholder
		//By Gen, we use placeholder <!CRED_ERROR_MESSAGE!> in content for errors

		$this->_content = str_replace( CRED_StaticClass::FORM_TAG . '_' . $zebraForm->form_properties[ 'name' ] . '%', $zebraForm->_form_content, $this->_content ) . $js_messages;
		$this->_content = str_replace( '<!CRED_ERROR_MESSAGE!>', $messages, $this->_content );
		// parse old shortcode first (with dashes)
		$shortcodeParser->add_shortcode( 'cred-post-parent', array( &$this, 'cred_parent' ) );
		$this->_content = $shortcodeParser->do_shortcode( $this->_content );
		$shortcodeParser->remove_shortcode( 'cred-post-parent', array( &$this, 'cred_parent' ) );
		// parse new shortcode (with underscores)
		$shortcodeParser->add_shortcode( 'cred_post_parent', array( &$this, 'cred_parent' ) );
		$this->_content = $shortcodeParser->do_shortcode( $this->_content );
		$shortcodeParser->remove_shortcode( 'cred_post_parent', array( &$this, 'cred_parent' ) );

		return $this->_content;
	}

	/**
	 * @param string $_form_type
	 *
	 * @return boolean
	 */
	abstract public function create_new_post( $_form_type, $form_type, $post_id, $post_type );

	/**
	 * @param int|null $post_id
	 * @param string $post_type
	 *
	 * @return mixed
	 */
	abstract public function save_form( $post_id = null, $post_type = "" );

	/**
	 * getFieldSettings important function that fill $out with all post fields in order to render forms
	 *
	 * @staticvar type $fields
	 * @staticvar type $_post_type
	 *
	 * @param $post_type
	 *
	 * @return mixed
	 */
	public function getFieldSettings( $post_type ) {
		static $fields = null;
		static $_post_type = null;
		if ( null === $fields || $_post_type != $post_type ) {
			$_post_type = $post_type;
			if ( $post_type == 'user' ) {
				$ffm = CRED_Loader::get( 'MODEL/UserFields' );
				$fields = $ffm->getFields( false, '', '', true, array( $this, 'getLocalisedMessage' ) );
			} else {
				$ffm = CRED_Loader::get( 'MODEL/Fields' );
				$fields = $ffm->getFields( $post_type, true, array( $this, 'getLocalisedMessage' ) );
			}

			// in CRED 1.1 post_fields and custom_fields are different keys, merge them together to keep consistency

			if ( array_key_exists( 'post_fields', $fields ) ) {
				$fields[ '_post_fields' ] = $fields[ 'post_fields' ];
			}
			if (
				array_key_exists( 'custom_fields', $fields ) && is_array( $fields[ 'custom_fields' ] )
			) {
				if ( isset( $fields[ 'post_fields' ] ) && is_array( $fields[ 'post_fields' ] ) ) {
					$fields[ 'post_fields' ] = array_merge( $fields[ 'post_fields' ], $fields[ 'custom_fields' ] );
				} else {
					$fields[ 'post_fields' ] = $fields[ 'custom_fields' ];
				}
			}
		}

		return $fields;
	}

	/**
	 * @param $id
	 * @param $count
	 *
	 * @return string
	 */
	public function createFormID( $id, $prg_id ) {
		return str_replace( '-', '_', CRED_StaticClass::$_current_prefix ) . $prg_id;
	}

	/**
	 * @param $id
	 * @param $count
	 *
	 * @return string
	 */
	public function createPrgID( $id ) {
		return $id
			. '_' . apply_filters( 'toolset_forms_frontend_flow_get_form_index', 1 )
			. '_' . toolset_getget( 'wpv_paged', 1 );
	}

	/**
	 * @param array $replace_get
	 * @param array $remove_get
	 *
	 * @return array|mixed|string
	 */
	public function currentURI( $replace_get = array(), $remove_get = array() ) {
		$request_uri = $_SERVER[ "REQUEST_URI" ];
		if ( ! empty( $replace_get ) ) {
			$request_uri = explode( '?', $request_uri, 2 );
			$request_uri = $request_uri[ 0 ];

			parse_str( $_SERVER[ 'QUERY_STRING' ], $get_params );
			if ( empty( $get_params ) ) {
				$get_params = array();
			}

			foreach ( $replace_get as $key => $value ) {
				$get_params[ $key ] = $value;
			}
			if ( ! empty( $remove_get ) ) {
				foreach ( $get_params as $key => $value ) {
					if ( isset( $remove_get[ $key ] ) ) {
						unset( $get_params[ $key ] );
					}
				}
			}
			if ( ! empty( $get_params ) ) {
				$request_uri .= '?' . http_build_query( $get_params, '', '&' );
			}
		}

		return $request_uri;
	}

	/**
	 * @param $error_files
	 *
	 * @return bool
	 */
	public function validate_form( $error_files ) {
		$form_validator = new CRED_Validator_Form( $this, $error_files );

		return $form_validator->validate();
	}

	/**
	 * @param $object_id
	 * @param null $attachedData
	 */
	abstract public function notify( $object_id, $attachedData = null );

	/**
	 * @param $lang
	 *
	 * @return null|string
	 */
	public function wpml_save_post_lang( $lang ) {
		if ( apply_filters( 'toolset_is_wpml_active_and_configured', false ) ) {
			if ( empty( $_POST[ 'icl_post_language' ] ) ) {
				if ( isset( $_GET[ 'lang' ] ) ) {
					$lang = sanitize_text_field( toolset_getget( 'lang' ) );
				} else {
					$lang = apply_filters( 'wpml_current_language', '' );
				}
			}
		}

		return $lang;
	}

	/**
	 * @param $clauses
	 *
	 * @return mixed
	 */
	public function terms_clauses( $clauses ) {
		if ( apply_filters( 'toolset_is_wpml_active_and_configured', false ) ) {
			if ( isset( $_GET[ 'source_lang' ] ) ) {
				$src_lang = sanitize_text_field( toolset_getget( 'source_lang' ) );
			} else {
				$src_lang = apply_filters( 'wpml_current_language', '' );
			}
			if ( isset( $_GET[ 'lang' ] ) ) {
				$lang = sanitize_text_field( toolset_getget( 'lang' ) );
			} else {
				$lang = $src_lang;
			}
			$clauses[ 'where' ] = str_replace(
				"icl_t.language_code = '" . $src_lang . "'",
				"icl_t.language_code = '" . $lang . "'",
				$clauses[ 'where' ]
			);
		}

		return $clauses;
	}


	/**
	 * @return boolean
	 */
	protected function is_submitted() {
		return $this->is_ajax_submitted() || $this->is_form_submitted();
	}

	/**
	 * @return boolean
	 */
	protected function is_form_submitted() {
		foreach ( $_POST as $name => $value ) {
			if (
				strpos( $name, 'form_submit') !== false
				&& $this->_form_id == toolset_getpost( '_cred_cred_prefix_form_id' )
			) {
				return true;
			}
		}
		if ( empty( $_POST ) && isset( $_GET[ '_tt' ] ) && ! isset( $_GET[ '_success' ] ) && ! isset( $_GET[ '_success_message' ] ) ) {
			// HACK in this case, we have used the form to try to upload a file with a size greater then the maximum allowed by PHP
			// The form was indeed submitted, but no data was passed and no redirection was performed
			// We return true here and handle the error in the Form_Builder::form() method

			return true;
		}

		return false;
	}

	/**
	 * @return boolean
	 */
	protected function is_ajax_submitted() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			return false;
		}

		if ( ! DOING_AJAX ) {
			return false;
		}

		if ( 'cred_submit_form' !== toolset_getpost( 'action' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Fix date and time using adodb date
	 */
	private function adodb_date_fix_date_and_time() {
		if ( isset( $_POST ) && ! empty( $_POST ) ) {
			foreach ( $_POST as $name => &$value ) {
				if ( $name == CRED_StaticClass::NONCE ) {
					continue;
				}
				if (
					is_array( $value )
					&& isset( $value[ 'datepicker' ] )
					&& ! empty( $value[ 'datepicker' ] )
				) {
					if ( ! function_exists( 'adodb_date' ) ) {
						require_once WPTOOLSET_FORMS_ABSPATH . '/lib/adodb-time.inc.php';
					}
					$date_format = get_option( 'date_format' );
					$date = $value[ 'datepicker' ];
					$value[ 'datetime' ] = adodb_date( "Y-m-d", $date );
					$value[ 'hour' ] = isset( $value[ 'hour' ] ) ? $value[ 'hour' ] : "00";
					$value[ 'minute' ] = isset( $value[ 'minute' ] ) ? $value[ 'minute' ] : "00";
					$value[ 'timestamp' ] = strtotime( $value[ 'datetime' ] . " " . $value[ 'hour' ] . ":" . $value[ 'minute' ] . ":00" );
				}
			}
		}
	}

	/**
	 * CRED-Shortcode: cred_parent
	 *
	 * Description: Render data relating to pre-selected parent of the post the form will manipulate
	 *
	 * Parameters:
	 * 'post_type' => [optional] Define a specifc parent type
	 * 'get' => Which information to render (title, url)
	 *
	 * Example usage:
	 *
	 *
	 * [cred_parent get="url"]
	 *
	 * Link:
	 *
	 *
	 * Note:
	 *  'post_type'> necessary if there are multiple parent types
	 *
	 * */
	public function cred_parent( $atts ) {
		extract( shortcode_atts( array(
			'post_type' => null,
			'get' => 'title',
		), $atts ) );

		$parent_id = null;
		if ( $post_type ) {
			if ( isset( CRED_StaticClass::$out[ 'fields' ][ 'parents' ][ '_wpcf_belongs_' . $post_type . '_id' ] ) && isset( $_GET[ 'parent_' . $post_type . '_id' ] ) ) {
				$parent_id = intval( $_GET[ 'parent_' . $post_type . '_id' ] );
			}
		} else {
			if ( isset( CRED_StaticClass::$out[ 'fields' ][ 'parents' ] ) ) {
				foreach ( CRED_StaticClass::$out[ 'fields' ][ 'parents' ] as $key => $parentdata ) {
					if ( isset( $_GET[ 'parent_' . $parentdata[ 'data' ][ 'post_type' ] . '_id' ] ) ) {
						$parent_id = intval( $_GET[ 'parent_' . $parentdata[ 'data' ][ 'post_type' ] . '_id' ] );
						break;
					} else {
						global $post;
						if ( isset( $post ) && ! empty( $post ) ) {
							$parent_id = get_post_meta( $post->ID, $key, true );
							break;
						} else {
							if ( isset( $_GET[ '_id' ] ) ) {
								$parent_id = get_post_meta( intval( $_GET[ '_id' ] ), $key, true );
								break;
							}
						}
					}
				}
			}
		}

		if ( $parent_id !== null ) {
			switch ( $get ) {
				case 'title':
					return get_the_title( $parent_id );
				case 'url':
					return get_permalink( $parent_id );
				case 'id':
					return $parent_id;
				default:
					return '';
			}
		} else {
			switch ( $get ) {
				case 'title':
					return _( 'Previous Page' );
				case 'url':
					$back = $_SERVER[ 'HTTP_REFERER' ];

					return ( isset( $back ) && ! empty( $back ) ) ? $back : '';
				case 'id':
					return '';
				default:
					return '';
			}
		}

		return '';
	}

	/**
	 * @param WP_Post $form
	 * @param array $fields
	 * @param CRED_Forms_Model $model
	 *
	 * @return mixed
	 */
	abstract protected function get_attached_data( $form, $fields, $model );

	/**
	 * Function responsible to manage relationship association by post_id/user_id
	 * as well as post reference fields
	 *
	 * @param int $object_id {post_id|user_id}
	 * @param $cred_form_rendering
	 *
	 * @return array
	 */
	abstract public function save_any_relationships_by_id( $object_id, &$cred_form_rendering );

	/**
	 * Cleanup fields data after validation fails and before the form is printed
	 *
	 * DISCLAIMER: this model has a print method (why not) that handles validation, saving, sending notifications, ...
	 * The validation occurs in a magic object that contains everything related to the form. This is what I have noticed:
	 * - The form is built
	 * - In a validation controller for fields that is called by this model, POST data is added to the magical object
	 * - The model evaluates the data and validates it
	 * - If the validation fails, it is needed to clean up some submitted data so it is not displayed
	 *
	 * Data that shouldn't be rendered:
	 * - File based fields: images, files, videos and audios. If validation fails, we can't display them again in the form because the
	 *   browser can't access to them again. But previous files should be respected
	 */
	private function cleanup_submitted_fields_data() {
		if ( is_admin() ) {
			return;
		}

		$there_are_changes = false;
		// _postData contains the original form data or empty is a new form
		// _cred_form_rendering stores the fields definitions
		if ( isset( $this->_cred_form_rendering->form_properties['fields'] ) && ( ! $this->_postData || isset( $this->_postData->fields ) ) ) {
			foreach ( $this->_cred_form_rendering->form_properties['fields'] as $field ) {
				if ( in_array( $field['type'], [ 'credimage', 'credvideo', 'credaudio', 'credfile' ] ) ) {
					$is_empty = isset( $_POST[ $field['name'] ] ) && is_array( $_POST[ $field['name'] ] )
						? ! array_filter( $_POST[ $field['name'] ] )
						: empty( $_POST[ $field['name'] ] );
					if ( ! $is_empty && ( ! $this->_postData || isset( $this->_postData->fields[ $field['name'] ] ) ) ) {
						// I really hate to do this, but it is the only way unless the plugin is fully refactored
						if ( ! $this->_postData ) {
							unset( $_POST[ $field['name'] ] );
							$there_are_changes = true;
						} else if ( $_POST[ $field['name'] ] !== $this->_postData->fields[ $field['name'] ] ) {
							// Note that the stored values might be gathered as an array,
							//  but the posted value should be a string, if the field is not repeating!
							$field_name = $field['name'];
							$_POST[ $field_name ] =
								is_string( $_POST[ $field_name ] )
								? (
									is_string( $this->_postData->fields[ $field_name ] )
										? $this->_postData->fields[ $field_name ]
										: reset( $this->_postData->fields[ $field_name ] )
								)
								: $this->_postData->fields[ $field_name ];
							$there_are_changes = true;
						}
					}
				}
			}
		}
		if ( ! empty( $_POST[ '_featured_image' ] ) && ( ! $this->_postData || empty( $this->_postData->extra['featured_img_html'] ) ) ) {
			$there_are_changes = true;
			unset( $_POST[ '_featured_image' ] );
		}
		if ( $there_are_changes ) {
			// Why I have to rebuild the form (shortcodes)?, because `build_form` generates the HTML and a bunch of other things,
			// and somewhere in the darkness of Forms, `CRED_Form_Rendering::set_submitted_values` is called and it replaces the magical form object fields by $_POST values,
			// so I have to generate again the shortcodes because that data is different
			// I can't do it somewhere else because if I do, validation fails
			$this->_content = $this->original_form_content;
			$this->_cred_form_rendering->add_warning_message( __( 'Because validation failed, please upload the files again.', 'wp-cred' ) );
			$this->build_form();
		}
	}
}
