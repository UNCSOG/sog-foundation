<?php

/**
 * Class CRED_Form_Rendering replace the old lib rendering zebraform
 *
 * @since @1.4+
 */
class CRED_Form_Rendering {

	public static $current_postid;
	public $controls;
	public $is_submitted;
	public $is_submit_success = false;
	public $attributes;
	public $language;
	public $method;
	public $actionUri;
	public $preview;
	public $form_properties;
	public $extra_parameters = array();
	public $top_messages = array();
	public $field_messages = array();
	/** @var array<string> */
	private $warning_messages = array();
	public $preview_messages = array();
	public $form_id;
	public $html_form_id;
	public $_post_id;
	public $_formHelper;
	public $_translate_field_factory;
	public $_formData;
	public $_shortcodeParser;
	public $_form_content;
	public $_js;
	public $_content;
	public $isForm = false;
	public $isUploadForm = false;

	/**
	 * @deprecated
	 * @var array $form_errors
	 */
	public $form_errors = array();

	/**
	 * @deprecated
	 * @var array $form_messages
	 */
	public $form_messages = array();

	/** @var mixed $_request */
	private $_request;

	/** @var array $_validation_errors */
	public $_validation_errors = array();

	/**
	 * CRED_Form_Rendering constructor.
	 *
	 * @param int $form_id
	 * @param string $html_form_id
	 * @param string $form_type
	 * @param int $current_postid
	 * @param string $actionUri
	 * @param bool $preview
	 */
	public function __construct( $form_id, $html_form_id, $form_type, $current_postid, $actionUri, $preview = false, $is_submitted = false ) {
		$this->form_id = $form_id;
		$this->html_form_id = $html_form_id;
		$this->form_type = $form_type;
		self::$current_postid = $current_postid;
		$this->actionUri = $actionUri;
		$this->preview = $preview;
		$this->method = CRED_StaticClass::METHOD;
		$req = $_REQUEST;
		$req = stripslashes_deep( $req );
		$this->_request = $req;
		$this->is_submitted = $is_submitted;
		return $this->getForm();
	}

	/**
	 * @param $formHelper
	 */
	public function setFormHelper( $formHelper ) {
		$this->_formHelper = $formHelper;
		$this->_translate_field_factory = new CRED_Translate_Field_Factory( $formHelper->_formBuilder, $formHelper, new CRED_Field_Configuration_Translated_Value(), new CRED_Translate_Command_Factory() );
	}

	/**
	 * @param $lang
	 */
	public function setLanguage( $lang ) {
		$this->language = $lang;
	}

	public function cred_filepath_class( $type, $class ) {
		return CRED_FIELDS_ABSPATH . "class.{$type}.php";
	}

	/**
	 * wptoolset_load_field_class_file Filter callback in order to handle CRED toolset fields inclusion
	 *
	 * @param $original_field_class_path
	 * @param $field_type
	 *
	 * @return string
	 */
	function set_correct_cred_fields_path( $original_field_class_path, $field_type ) {
		$field_file_path = CRED_ABSPATH . "/application/models/field/wptoolset/class.{$field_type}.php";
		return ( file_exists( $field_file_path ) ) ? $field_file_path : $original_field_class_path;
	}

	/**
	 * @deprecated function since 1.2.6
	 *
	 * @param array $params
	 */
	function set_extra_parameters( $params ) {
		$this->extra_parameters = array_merge( $this->extra_parameters, $params );
	}

	/**
	 * Add a field content to a form
	 *
	 * @param $type
	 * @param $name
	 * @param $value
	 * @param $attributes
	 * @param null $field
	 *
	 * @return array
	 */
	function add( $type, $name, $value, $attributes, $field = null ) {
		$computed_values = array(
			'type' => $type,
			'name' => $name,
			'value' => $value,
			'attributes' => $attributes,
			'field' => $field,
		);
		$type = apply_filters( 'cred_filter_field_type_before_add_to_form', $type, $computed_values );
		$name = apply_filters( 'cred_filter_field_name_before_add_to_form', $name, $computed_values );
		$value = apply_filters( 'cred_filter_field_value_before_add_to_form', $value, $computed_values );
		$attributes = apply_filters( 'cred_filter_field_attributes_before_add_to_form', $attributes, $computed_values );
		$field = apply_filters( 'cred_filter_field_before_add_to_form', $field, $computed_values );

		$title = $name;
		if (isset( $field['label'] )) {
			$title = $field['label'];
		} elseif (isset($field['title'])) {
			$title = $field['title'];
		}

		//Check the case when generic field checkbox does not have label property at all
		if ( $type == 'checkbox' && ! isset( $field['plugin_type'] ) ) {
			if ( ! isset( $field['label'] ) ) {
				$title = "";
			}
		}

		$returned_field = array();
		$returned_field['type'] = $type;
		$returned_field['name'] = $name;
		if ( isset( $field['cred_custom'] ) ) {
			$returned_field['cred_custom'] = true;
		}
		$returned_field['title'] = $title;
		$returned_field['value'] = $value;
		$returned_field['attr'] = $attributes;
		$returned_field['data'] = is_array( $field ) && array_key_exists( 'data', $field ) ? @$field['data'] : array();

		if ( isset( $field['plugin_type'] ) ) {
			$returned_field['plugin_type'] = $field['plugin_type'];
		}

		$this->form_properties['fields'][] = $returned_field;

		return $returned_field;
	}

	/**
	 * Add virtual information about a field
	 *
	 * @param $type
	 * @param $name
	 * @param $value
	 * @param $attributes
	 * @param null $field
	 *
	 * @return array
	 */
	function noadd( $type, $name, $value, $attributes, $field = null ) {
		$computed_values = array(
			'type' => $type,
			'name' => $name,
			'value' => $value,
			'attributes' => $attributes,
			'field' => $field,
		);
		$type = apply_filters( 'cred_filter_field_type_before_noadd_to_form', $type, $computed_values );
		$name = apply_filters( 'cred_filter_field_name_before_noadd_to_form', $name, $computed_values );
		$value = apply_filters( 'cred_filter_field_value_before_noadd_to_form', $value, $computed_values );
		$attributes = apply_filters( 'cred_filter_field_attributes_before_noadd_to_form', $attributes, $computed_values );
		$field = apply_filters( 'cred_filter_field_before_noadd_to_form', $field, $computed_values );

		$title = isset( $field ) ? $field['name'] : $name;

		$returned_field = array();
		$returned_field['type'] = $type;
		$returned_field['name'] = $name;
		$returned_field['title'] = $title;
		$returned_field['value'] = $value;
		$returned_field['attr'] = $attributes;
		$returned_field['data'] = is_array( $field ) && array_key_exists( 'data', $field ) ? @$field['data'] : array();

		if ( isset( $field['plugin_type'] ) ) {
			$returned_field['plugin_type'] = $field['plugin_type'];
		}

		return $returned_field;
	}

	/**
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public function cred_form_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'class' => '',
		), $atts );

		// return a placeholder instead and store the content in _form_content var
		$this->_form_content = $content;
		$this->html_form_id = $this->form_properties['name'];
		$this->isForm = true;

		$form_classname = array( 'cred-form', 'cred-keep-original' );
		$this->set_form_class( $form_classname, $atts );

		return CRED_StaticClass::FORM_TAG . '_' . $this->form_properties['name'] . '%';
	}

	/**
	 * Callback function for [cred_user_form] shortcode
	 *
	 * @param $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public function cred_user_form_shortcode( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'class' => '',
		), $atts );

		// return a placeholder instead and store the content in _form_content var
		$this->_form_content = $content;
		$this->html_form_id = $this->form_properties['name'];
		$this->isForm = true;

		$form_classname = array( 'cred-user-form', 'cred-keep-original' );
		$this->set_form_class( $form_classname, $atts );

		return CRED_StaticClass::FORM_TAG . '_' . $this->form_properties['name'] . '%';
	}

	/**
	 * Set the form tag class attribute value,
	 * based on the form shortcode class attribute value
	 * plus some defaults for post and user forms.
	 *
	 * @param array $form_classname
	 * @param array $atts
	 *
	 * @since 2.1
	 */
	private function set_form_class( $form_classname = array(), $atts = array() ) {
		if ( ! empty( $atts['class'] ) ) {
			$extra_classname = explode( ' ', $atts['class'] );
			$form_classname = array_merge( $form_classname, $extra_classname );
		}

		$form_classname = array_unique( $form_classname );
		$form_classname_string = implode( ' ', $form_classname );
		$this->_attributes['class'] = esc_attr( $form_classname_string );
	}

	/**
	 * CRED-Shortcode: cred_field
	 *
	 * Description: Render a form field (using fields defined in wp-types plugin and / or Taxonomies)
	 * parse field shortcodes [cred_field]
	 *
	 * Parameters:
	 * 'field' => Field slug name
	 * 'post' => [optional] Post Type where this field is defined
	 * 'value'=> [optional] Preset value (does not apply to all field types, eg taxonomies)
	 * 'taxonomy'=> [optional] Used by taxonomy auxilliary fields (eg. "show_popular") to signify to which taxonomy
	 * this field belongs
	 * 'type'=> [optional] Used by taxonomy auxilliary fields (like show_popular) to signify which type of
	 * functionality it provides (eg. "show_popular")
	 * 'display'=> [optional] Used by fields for Hierarchical Taxonomies (like Categories) to signify the mode of
	 * display (ie. "select" or "checkbox")
	 * 'single_select'=> [optional] Used by fields for Hierarchical Taxonomies (like Categories) to signify that
	 * select field does not support multi-select mode
	 * 'max_width'=>[optional] Max Width for image fields
	 * 'max_height'=>[optional] Max Height for image fields
	 * 'max_results'=>[optional] Max results in parent select field
	 * 'order'=>[optional] Order for parent select field (title or date)
	 * 'ordering'=>[optional] Ordering for parent select field (asc, desc)
	 * 'required'=>[optional] Whether parent field is required, default 'false'
	 * 'no_parent_text'=>[optional] Text for no parent selection in parent field
	 * 'select_text'=>[optional] Text for required parent selection
	 * 'validate_text'=>[optional] Text for error message when parebt not selected
	 * 'placeholder'=>[optional] Text to be used as placeholder (HTML5) for text fields, default none
	 * 'readonly'=>[optional] Whether this field is readonly (cannot be edited, applies to text fields), default
	 * 'false'
	 * 'urlparam'=> [optional] URL parameter to be used to give value to the field
	 *
	 * Example usage:
	 *
	 *  Render the wp-types field "Mobile" defined for post type Agent
	 * [cred_field field="mobile" post="agent" value="555-1234"]
	 *
	 * Link:
	 *
	 *
	 * Note:
	 *  'value'> translated automatically if WPML translation exists
	 *  'taxonomy'> used with "type" option
	 *  'type'> used with "taxonomy" option
	 */
	public function cred_field_shortcodes( $atts ) {
		return CRED_Field_Factory::create_field( $atts, $this, $this->_formHelper, $this->_formData, $this->_translate_field_factory );
	}

	/**
	 * CRED-Shortcode: cred_show_group
	 *
	 * Description: Show/Hide a group of fields based on conditional logic and values of form fields
	 *
	 * Parameters:
	 * 'if' => Conditional Expression
	 * 'mode' => Effect for show/hide group, values are: "fade-slide", "fade", "slide", "none"
	 *
	 *
	 * Example usage:
	 *
	 *    [cred_show_group if="$(date) gt TODAY()" mode="fade-slide"]
	 *       //rest of content to be hidden or shown
	 *      // inside the shortcode body..
	 *    [/cred_show_group]
	 *
	 * Link:
	 *
	 *
	 * Note:
	 * parse conditional shortcodes (nested allowed) [cred_show_group]
	 */
	public function cred_conditional_shortcodes( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'if' => '',
			'mode' => 'fade-slide',
		), $atts );

		if ( empty( $atts['if'] ) || ! isset( $content ) || empty( $content ) ) {
			return '';
		}

		if ( defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
			$form = &$this->_formData;
			$conditional_index = md5( $atts['if'] );
			WPToolset_Types::$is_user_meta = $form->getForm()->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME;
			$conditional = self::filterConditional( $atts['if'], $this->_post_id );
			$id = $form->getForm()->ID . '_condition_' . $conditional_index;
			$config = array( 'id' => $id, 'conditional' => $conditional );
			$passed = wptoolset_form_conditional_check( $config );
			wptoolset_form_add_conditional( $this->html_form_id, $config );

			$style = ( $passed ) ? "" : " style='display:none;'";
			$effect = " data-effectmode='" . esc_attr( $atts['mode'] ) . "'";

			$html = "<div class='cred-group {$id}'{$style}{$effect}>";
			$html .= do_shortcode( $content );
			$html .= "</div>";

			return $html;
		}

		return '';
	}

	/**
	 * CRED-Shortcode: cred_generic_field
	 *
	 * Description: Render a form generic field (general fields not associated with types plugin)
	 *
	 * Parameters:
	 * 'field' => Field name (name like used in html forms)
	 * 'type' => Type of input field (eg checkbox, email, select, radio, checkboxes, date, file, image etc..)
	 * 'class'=> [optional] Css class to apply to the element
	 * 'urlparam'=> [optional] URL parameter to be used to give value to the field
	 * 'placeholder'=>[optional] Text to be used as placeholder (HTML5) for text fields, default none
	 *
	 *  Inside shortcode body the necessary options and default values are defined as JSON string (autogenerated by
	 * GUI)
	 *
	 * Example usage:
	 *
	 *    [cred_generic_field field="gmail" type="email" class=""]
	 *    {
	 *    "required":0,
	 *    "validate_format":0,
	 *    "default":""
	 *    }
	 *    [/cred_generic_field]
	 *
	 * Link:
	 *
	 *
	 * Note:
	 *
	 *
	 * */
	// parse generic input field shortcodes [cred_generic_field]
	public function cred_generic_field_shortcodes( $atts, $content = '' ) {
		return CRED_Field_Factory::create_generic_field( $atts, $content, $this, $this->_formHelper, $this->_formData, $this->_translate_field_factory );
	}
	//########### CALLBACKS

	/**
	 * function used to set controls in order to do not lost filled field values after a failed form submition
	 */
	public function setControls() {
		$this->controls = array();
		$pattern = get_shortcode_regex();
		foreach ( $this->_request as $key => $value ) {
			$value = $this->clearControl( $value, $pattern );
			$this->controls[ $key ] = $value;
		}
		//No need anymore
		unset( $this->_request );
	}

	/**
	 * @param $value
	 * @param $pattern
	 *
	 * @return string
	 */
	private function clearControl( $value, $pattern ) {
		if ( is_array( $value ) ) {
			foreach ( $value as & $value_entry ) {
				$value_entry = $this->clearControl( $value_entry, $pattern );
			}
		} elseif ( is_string( $value ) ) {
			preg_match_all( '/' . $pattern . '/', $value, $matches, PREG_SET_ORDER );
			if ( ! empty( $matches ) ) {
				$value = strip_shortcodes( $value );
			}
		}

		return $value;
	}

	/**
	 * Get the current form
	 *
	 * @return $this|bool
	 */
	public function getForm() {
		add_filter( 'cred_form_action_uri_querystring_array', array( $this, 'add_wpv_parameters_value_to_querystring' ), 10, 3 );

		$this->form_properties = array();

		$this->form_properties['doctype'] = 'xhtml';
		$this->form_properties['action'] = esc_html( $_SERVER['REQUEST_URI'] );
		//Fix for todo https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193382255/comments#303088273
		if ( preg_match( "/admin-ajax.php/", $this->form_properties['action'] ) ) {
			$this->form_properties['action'] = ( wp_get_referer() ) ? wp_get_referer() : get_home_url();
		}
		$this->set_form_action_uri();
		$this->form_properties['method'] = 'post';
		$this->form_properties['name'] = $this->html_form_id;
		$this->form_properties['fields'] = array();

		return $this;
	}

	/**
	 * Add querystring wpv_paged if exists to querystring created as action uri of a cred form
	 *
	 * @param array $querystring_array
	 * @param int $post_id
	 * @param int $form_id
	 *
	 * @return array
	 * @since 1.8.9
	 */
	public function add_wpv_parameters_value_to_querystring( $querystring_array, $post_id, $form_id ) {
		if ( isset( $_POST['action'] ) &&
			( 'wpv_get_view_query_results' === $_POST['action'] ||
				'wpv_get_archive_query_results' === $_POST['action'] )
		) {
			if ( isset( $_GET ) && count( $_GET ) > 0 ) {
				foreach ( $_GET as $name => $value ) {
					$querystring_array[ $name ] = sanitize_text_field( $value );
				}
			}
		}

		return $querystring_array;
	}

	/**
	 * Create the Form action uri
	 */
	private function set_form_action_uri() {
		$url_string_parsed_array = parse_url( $this->form_properties['action'] );
		$querystring_array = array();
		if ( isset( $url_string_parsed_array['query'] ) && ! empty( $url_string_parsed_array['query'] ) ) {
			parse_str( wp_specialchars_decode( $url_string_parsed_array['query'] ), $querystring_array );
		}
		if ( isset( $querystring_array['_success'] ) ) {
			$querystring_array['_success'] = $this->html_form_id;
		}
		$querystring_array['_tt'] = time();

		/**
		 * cred_form_action_uri_querystring_array.
		 *
		 * Hook filter used in order to manipulate action form uri querystring
		 *
		 * @since 1.9
		 *
		 * @param array $querystring Query String.
		 * @param int $post_id Post ID related to the current cred form
		 * @param int $form_id CRED Form ID.
		 */
		$querystring_array = apply_filters( 'cred_form_action_uri_querystring_array', $querystring_array, $this->_post_id, $this->form_id );

		$new_query_string = http_build_query( $querystring_array, '', '&' );
		$new_query_string = urldecode( $new_query_string );
		$this->form_properties['action'] = $url_string_parsed_array['path'] . "?" . $new_query_string;
	}

	/**
	 * @param array $controls
	 * @param object $objs
	 *
	 * @return mixed
	 */
	public function render_callback( $controls, &$objs ) {
		$shortcodeParser = $this->_shortcodeParser;
		CRED_StaticClass::$out['controls'] = $controls;
		// render shortcodes, _form_content is being continuously replaced recursively
		$this->_form_content = $shortcodeParser->do_shortcode( $this->_form_content );

		return $this->_form_content;
	}

	/**
	 * Render the form
	 *
	 * @return string
	 */
	public function render() {
		if ( $this->isForm ) {
			$this->isForm = false;
			$enctype = "";
			if ( $this->isUploadForm ) {
				$this->isUploadForm = false;
				$enctype = 'enctype="multipart/form-data"';
			}

			$form = $this->_formData;
			$fields = $form->getFields();
			$form_use_ajax = (
				isset( $fields[ 'form_settings' ]->form[ 'use_ajax' ] )
				&& $fields[ 'form_settings' ]->form[ 'use_ajax' ] == 1
				) ? true
				: false;

			$form_data = array(
				'form_id' => $this->form_properties['name'],
				'use_ajax' => $form_use_ajax ? '1' : '0',
				// We need the current Views page, if any, since AJAX forms build their unique ID based on recurrence plus current View page.
				'wpv_page' => toolset_getget( 'wpv_paged', 1 ),
			);

			$conditional_partial_data = apply_filters( 'wptoolset_forms_get_partial_conditionals', [] );
			/**
			 * cred_before_html_form_{html_form_id}
			 *
			 * action hook in order to print anything up to the cred form with html_form_id is going to be created
			 *
			 * @since 1.9.4
			 */
			do_action( 'cred_before_html_form_' . $this->form_properties['name'] );

			$this->_form_content = '<form ' . $enctype . ' ' .
				( $this->form_properties['doctype'] == 'html' ? 'name="' . esc_attr( $this->form_properties['name'] ) . '" ' : '' ) .
				'id="' . esc_attr( $this->form_properties['name'] ) . '" ' .
				'class="' . ( ( isset( $this->_attributes['class'] ) && ! empty( $this->_attributes['class'] ) ) ? esc_attr( $this->_attributes['class'] ) : "" ) . '" ' .
				'action="' . esc_attr( $this->form_properties['action'] ) . '" ' .
				// Include the form data as an attribute, so it is available after AJAX events.
				'data-form="' . esc_js( json_encode( $form_data ) ) . '" ' .
				// Include the conditionals data as an attribute, so it is available after AJAX events.
				// Note that conditionals might wrap values with single quotes,
				// and esc_js turns them into escaped single quotes,
				// which breaks the JSOn encoding. Let's turn them into HTML entities.
				// Later, the script will get them as proper single quotes ready to be used.
				'data-conditionals="' . str_replace( "\'", "&#39;", esc_js( json_encode( $conditional_partial_data ) ) ) . '" ' .
				'method="' . esc_attr( strtolower( $this->form_properties['method'] ) ) . '">' . $this->_form_content . "</form>";
		}

		return $this->_form_content;
	}

	/**
	 * @param $txt
	 *
	 * @return string
	 */
	private function typeMessage2textMessage( $txt ) {
		switch ( $txt ) {
			case "date":
				return "cred_message_enter_valid_date";
			case "embed":
			case "url":
				return "cred_message_enter_valid_url";
			case "email":
				return "cred_message_enter_valid_email";
			case "integer":
			case "number":
				return "cred_message_enter_valid_number";
			case "captcha":
				return "cred_message_enter_valid_captcha";
			case "button":
				return "cred_message_edit_skype_button";
			case "image":
				return "cred_message_not_valid_image";
			default:
				return "cred_message_field_required";
		}
	}

	/**
	 * @param $txt
	 *
	 * @return string
	 * @deprecated since 1.4
	 */
	private function typeMessage2id( $txt ) {
		switch ( $txt ) {
			case "date":
				return "cred_message_enter_valid_date";
			case "embed":
			case "url":
				return "cred_message_enter_valid_url";
			case "email":
				return "cred_message_enter_valid_email";
			case "integer":
			case "number":
				return "cred_message_enter_valid_number";
			case "captcha":
				return "cred_message_enter_valid_captcha";
			case "button":
				return "cred_message_edit_skype_button";
			case "image":
				return "cred_message_not_valid_image";
			default:
				return "cred_message_field_required";
		}
	}

	/**
	 * fixCredCustomFieldMessages
	 * Fix CRED controlled custom fields validation message
	 * replace with cred form settings messages and localize messages
	 * Client-side validation is not using the custom messages provided in CRED forms for CRED custom fields
	 *
	 * @param $field
	 */
	public function fixCredCustomFieldMessages( &$field ) {
		if ( ! isset( $field['cred_custom'] ) || isset( $field['cred_custom'] ) && ! $field['cred_custom'] ) {
			return;
		}
		$cred_messages = $this->extra_parameters->messages;
		foreach ( $field['data']['validate'] as $type_message => &$message ) {
			$id_message = $this->typeMessage2textMessage( $type_message );
			$message['message'] = $cred_messages[ $id_message ];
			$message['message'] = cred_translate(
				CRED_Form_Builder_Helper::MSG_PREFIX . $id_message, $cred_messages[ $id_message ], 'cred-form-' . $this->_formData->getForm()->post_title . '-' . $this->_formData->getForm()->ID
			);
		}
	}

	/**
	 * This function renders the single field
	 *
	 * @param array $field
	 * @param bool $add2form_content
	 *
	 * @return mixed
	 */
	public function renderField( $field, $add2form_content = false ) {
		global $post;

		if ( defined( 'WPTOOLSET_FORMS_ABSPATH' ) &&
			function_exists( 'wptoolset_form_field' )
		) {
			require_once WPTOOLSET_FORMS_ABSPATH . '/api.php';
			require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.types.php';

			//Hook Filter 'wptoolset_load_field_class_file' is defined only since Toolset Common 2.5.2+
			if ( defined( TOOLSET_COMMON_VERSION )
				&& version_compare( TOOLSET_COMMON_VERSION, '2.5.2', '>=' ) ) {
				add_filter( "wptoolset_load_field_class_file", array( $this, "set_correct_cred_fields_path" ), 10, 2 );
			} else {
				//Classic requirement of CRED owner fields
				$cred_owner_fields_type = array('credfile', 'credaudio', 'credvideo', 'credimage');
				foreach ($cred_owner_fields_type as $cred_owner_field_type) {
					$file_path = CRED_ABSPATH . "/application/models/field/wptoolset/class.{$cred_owner_field_type}.php";
					if ( file_exists( $file_path ) ) {
						require_once $file_path;
					}
				}
			}

			$form_id = $this->form_id;
			$id = $this->html_form_id;

			$field['id'] = $id;

			if ( $field['type'] == 'messages' ) {
				$this->isForm = true;

				return;
			}

			if ( $this->form_type == 'edit'
				&& $field['name'] == 'user_login'
			) {
				$field['attr']['readonly'] = "readonly";
				$field['attr']['style'] = "background-color:#ddd;";
				$field['attr']['onclick'] = "blur();";
			}

			if ( in_array( $field['type'], array( 'credfile', 'credaudio', 'credvideo', 'credimage', 'file' ) )
			) {
				$this->isUploadForm = true;
			}

			//#############################################################################################################################################################
			//Client-side validation is not using the custom messages provided in CRED forms for CRED custom fields
			$this->fixCredCustomFieldMessages( $field );
			//#############################################################################################################################################################

			$mytype = $this->transType( $field['type'] );

			$fieldConfig = new CRED_Field_Config();
			$fieldConfig->setValueAndDefaultValue( $field, $_curr_value, $_default_value );

			$fieldConfig->setOptions( $field['name'], $field['type'], $field['value'], $field['attr'] );
			$fieldConfig->setId( $this->form_properties['name'] . "_" . $field['name'] );
			$fieldConfig->setName( $field['name'] );
			$this->cleanAttr( $field['attr'] );
			$fieldConfig->setAttr( $field['attr'] );
			$fieldConfig->setDefaultValue( $_default_value );
			$fieldConfig->setValue( $_curr_value );
			$fieldConfig->setDescription( ! empty( $field['description'] ) ? $field['description'] : "" );
			$fieldConfig->setTitle( $field['title'] );
			$fieldConfig->setType( $mytype );

			if ( isset( $field['data'] )
				&& isset( $field['data']['repetitive'] )
			) {
				$fieldConfig->setRepetitive( (bool) $field['data']['repetitive'] );
			}

			if ( isset( $field['attr'] )
				&& isset( $field['attr']['type'] )
			) {
				$fieldConfig->setDisplay( $field['attr']['type'] );
			}

			$forms_model = CRED_Loader::get( 'MODEL/Forms' );
			$form_settings = $forms_model->getFormCustomField( $form_id, 'form_settings' );
			$fieldConfig->setForm_settings( $form_settings );

			$config = $fieldConfig->createConfig();

			// Cache the global post to restore it afterwards
			$form_post = null;
			if ( $post ) {
				$form_post = clone $post;
			}
			// Set the global post to the one being created or edited
			$post = get_post( $this->_post_id );

			// Modified by Srdjan
			// Validation and conditional filtering
			if ( isset( $field['plugin_type'] ) && $field['plugin_type'] == 'types' ) {
				// This is not set in DB
				$field['meta_key'] = WPToolset_Types::getMetakey( $field );
				$config['validation'] = WPToolset_Types::filterValidation( $field );

				if ( $post ) {
					$config['conditional'] = WPToolset_Types::filterConditional( $field, $post->ID );
				}
			} else {
				$config['validation'] = self::filterValidation( $field );
			}

			// Added by Srdjan
			/*
			 * Use $_validation_errors
			 * set in $this::validate_form()
			 */
			if ( isset( $this->_validation_errors['fields'][ $config['id'] ] ) ) {
				$config['validation_error'] = $this->_validation_errors['fields'][ $config['id'] ];
			}

			if ( $this->form_type == 'edit'
				&& $mytype == 'checkbox'
			) {
				unset( $config['default_value'] );
			}

			// Added by Srdjan END
			$config = apply_filters( 'cred_form_field_config', $config );

			$_values = array();
			if ( isset( $field['data']['repetitive'] )
				&& $field['data']['repetitive'] == 1
			) {
				$_values = $config['value'];
			} else {
				$_values = array( $config['value'] );
			}

			$html = wptoolset_form_field( $this->html_form_id, $config, $_values );

			// Restore the original global post
			$post = $form_post;
			unset( $form_post );

			if ( $add2form_content ) {
				$this->_form_content .= $html;
			} else {
				return $html;
			}
		}
	}

	/**
	 * Clean attributes variables
	 *
	 * @param array $attrs
	 *
	 * @return array
	 */
	public function cleanAttr( &$attrs ) {
		if ( empty( $attrs ) ) {
			return;
		}
		$key_attr_to_exclude = array('select_text');
		foreach ( $attrs as $key_attr => $value_attr ) {
			if ( in_array($key_attr, $key_attr_to_exclude)
				|| is_array( $value_attr ) ) {
				continue;
			}
			$attrs[ $key_attr ] = esc_attr( $value_attr );
		}
	}


	/**
	 * Filters validation.
	 *
	 * Loop over validation settings and create array of validation rules.
	 * array( $rule => array( 'args' => array, 'message' => string ), ... )
	 *
	 * @param array|string $field settings array (as stored in DB) or field ID
	 *
	 * @return array array( $rule => array( 'args' => array, 'message' => string ), ... )
	 */
	public static function filterValidation( $config ) {
		/* Placeholder for field value '$value'.
		 *
		 * Used for validation settings.
		 * Field value is not processed here, instead string '$value' is used
		 * to be replaced with actual value when needed.
		 *
		 * For example:
		 * validation['rangelength'] = array(
		 *     'args' => array( '$value', 5, 12 ),
		 *     'message' => 'Value length between %s and %s required'
		 * );
		 * validation['required'] = array(
		 *     'args' => array( '$value', true ),
		 *     'message' => 'This field is required'
		 * );
		 *
		 * Types have default and custom messages defined on it's side.
		 */
		$value = '$value';
		$validation = array();
		if ( isset( $config['data']['validate'] ) ) {
			foreach ( $config['data']['validate'] as $rule => $settings ) {
				if ( $settings['active'] ) {
					$validation[$rule] = array(
						'args' => isset( $settings['args'] ) ? $settings['args'] : array($value, true),
						'message' => $settings['message']
					);
				}
			}
		}

		return $validation;
	}

	/**
	 * Filters conditional.
	 *
	 * We'll just handle this as a custom conditional
	 *
	 * Custom conditional
	 * Main properties:
	 * [custom] - custom statement made by user, note that $xxxx should match
	 *      IDs of fields that passed this filter.
	 * [values] - same as for regular conditional
	 *
	 * [conditional] => Array(
	 * [custom] => ($wpcf-my-date = DATE(01,02,2014)) OR ($wpcf-my-date > DATE(07,02,2014))
	 * [values] => Array(
	 * [wpcf-my-date] => 32508691200
	 * )
	 * )
	 *
	 * @param array|string $field settings array (as stored in DB) or field ID
	 * @param int $post_id Post or user ID to fetch meta data to check against
	 *
	 * @return array
	 */
	public static function filterConditional( $if, $post_id ) {
		// Types fields specific
		// @todo is this needed?
		require_once WPTOOLSET_FORMS_ABSPATH . '/classes/class.conditional.php';

		$data = WPToolset_Types::getCustomConditional( $if, '', WPToolset_Types::getConditionalValues( $post_id ) );

		return $data;
	}

	/**
	 * Add field content to the form
	 *
	 * @param string $type
	 * @param string $name
	 * @param string|array $value
	 * @param array $attributes
	 * @param null $field
	 *
	 * @return type
	 */
	public function add2form_content( $type, $name, $value, $attributes, $field = null ) {
		$objField2Render = $this->add( $type, $name, $value, $attributes );

		return $this->renderField( $objField2Render, true );
	}

	/**
	 * Add js content to the form
	 * @param $js
	 */
	public function addJsFormContent( $js ) {
		//$js = str_replace("'","\'",$js);
		$this->_js = "<script language='javascript'>{$js}</script>";
	}

	/**
	 * @param $s
	 *
	 * @return string
	 */
	function sanitize_me( $s ) {
		return htmlspecialchars( $s, ENT_QUOTES, 'utf-8' );
	}

	/**
	 * Translate special field types
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private function transType( $type ) {
		switch ( $type ) {

			case 'text':
				$ret = 'textfield';
				break;

			default:
				$ret = $type;
				break;
		}

		return $ret;
	}



	/**
	 * Set the current submitted fields values
	 *
	 * @param type $fields
	 */
	public function set_submitted_values( $fields ) {
		$this->form_properties['fields'] = $fields;
	}

	/**
	 * @deprecated deprecated since version 1.2.6
	 */
	function get_submitted_values() {
		return true;
	}

	/**
	 * Get all POST/FILES values and set to class object variable
	 *
	 * @return array
	 */
	public function get_form_field_values() {
		$fields = array();

		//FIX validation for files elements
		$files = array();
		foreach ( $_FILES as $name => $value ) {
			$files[ $name ] = $value['name'];
		}
		$reqs = array_merge( $_REQUEST, $files );

		foreach ( $this->form_properties['fields'] as $n => $field ) {
			if ( $field['type'] != 'messages' ) {
				$value = isset( $reqs[ $field['name'] ] ) ? $reqs[ $field['name'] ] : "";

				$fields[ $field['name'] ] = array(
					'value' => $value,
					'name' => $field['name'],
					'type' => $field['type'],
					'repetitive' => isset( $field['data']['repetitive'] ) ? $field['data']['repetitive'] : false,
				);
			}
		}

		return $fields;
	}

	/**
	 * New validation using API calls from toolset-forms.
	 *
	 * Uses API cals
	 *
	 * @uses wptoolset_form_validate_field()
	 * @uses wptoolset_form_conditional_check()
	 *
	 * @todo Make it work with other fields (generic)
	 *
	 * @param $post_id
	 * @param $values
	 * @param bool $is_user_form
	 *
	 * @return boolean
	 * @deprecated since 1.9
	 */
	function validate_form( $post_id, $values, $is_user_form = false ) {
		$recaptcha_validator = new CRED_Validator_Recaptcha( $this->_base_form );
		$result[] = $recaptcha_validator->validate();
	}

	/**
	 * @deprecated deprecated since version 1.2.6
	 */
	function add_repeatable( $type ) {
		return;
	}

	/**
	 * @deprecated deprecated since version 1.2.6
	 */
	function add_conditional_group( $id ) {
		return;
	}

	/**
	 * Function that handles warning/error message to top form
	 *
	 * @param $message
	 * @param string $field_slug
	 */
	function add_top_message( $message, $field_slug = 'generic' ) {
		$form_id = $this->html_form_id;
		if ( $message == '' ) {
			return;
		}
		if ( ! isset( $this->top_messages[ $form_id ] ) ) {
			$this->top_messages[ $form_id ] = array();
		}
		//Fix slug with name
		$message = str_replace( "post_title", "Post Name", $message );
		$message = str_replace( "post_content", "Description", $message );
		$message = str_replace( "user_email", "Email", $message );
		if ( ! empty( $message ) && ! in_array( trim( $message ), $this->top_messages[ $form_id ] ) ) {
			$this->top_messages[ $form_id ][] = $message;
		}
	}

	/**
	 * Function that handles warning/error message to a field or a form
	 *
	 * @param $message
	 * @param string $field_slug
	 */
	function add_field_message( $message, $field_slug = 'generic' ) {
		$form_id = $this->html_form_id;
		if ( $message == '' ) {
			return;
		}
		if ( ! isset( $this->field_messages[ $form_id ] ) ) {
			$this->field_messages[ $form_id ] = array();
		}
		if ( ! isset( $this->field_messages[ $form_id ][ $field_slug ] ) ) {
			$this->field_messages[ $form_id ][ $field_slug ] = array();
		}
		if ( ! empty( $message ) && ! in_array( trim( $message ), $this->field_messages[ $form_id ] ) ) {
			$this->field_messages[ $form_id ][ $field_slug ] = $message;
		}
	}

	/**
	 * Adds a warning message
	 *
	 * @param string $message
	 */
	function add_warning_message( $message ) {
		$form_id = $this->html_form_id;
		if ( $message == '' ) {
			return;
		}
		if ( ! isset( $this->warning_messages[ $form_id ] ) ) {
			$this->warning_messages[ $form_id ] = array();
		}
		$this->warning_messages[ $form_id ][] = $message;
	}

	/**
	 * @param string $message
	 * @param string $field_slug
	 */
	function add_success_message( $message, $field_slug = 'generic' ) {
		$form_id = $this->html_form_id;
		if ( $message == '' ) {
			return;
		}
		if ( ! isset( $this->succ_messages[ $form_id ] ) ) {
			$this->succ_messages[ $form_id ] = array();
		}
		if ( ! isset( $this->succ_messages[ $form_id ][ $field_slug ] ) ) {
			$this->succ_messages[ $form_id ][ $field_slug ] = array();
		}
		if ( ! empty( $message ) && ! in_array( trim( $message ), $this->succ_messages[ $form_id ] ) ) {
			$this->succ_messages[ $form_id ][ $field_slug ] = $message;
		}
	}

	/**
	 * @param string $message
	 */
	function add_preview_message( $message ) {
		$this->preview_messages[] = $message;
	}

	/**
	 * @param string $target_form_id
	 *
	 * @return string
	 */
	function getFieldsSuccessMessages( $target_form_id = "" ) {
		$form_id = $this->html_form_id;

		$form_html_id_attribute = "";
		if ( ! empty( $target_form_id ) ) {
			$form_html_id_attribute = $target_form_id;
		} else {
			$submitted_form_id = isset( $_GET['_success'] ) ? $_GET['_success'] : "";
			if ( ! empty( $submitted_form_id ) ) {
				$form_html_id_attribute = str_replace( '-', '_', CRED_StaticClass::$_current_prefix ) . $submitted_form_id;
			}
		}

		if ( $form_html_id_attribute != $this->html_form_id ) {
			return "";
		}

		$messages = "";
		if ( ! isset( $this->succ_messages ) || ( isset( $this->succ_messages ) && empty( $this->succ_messages ) ) ) {
			return $messages;
		}

		$field_messages = $this->succ_messages[ $form_id ];
		foreach ( $field_messages as $id_field => $text ) {
			$messages .= '<div class="alert alert-success"><p>'. $text .'</p></div>';
		}

		return $messages;
	}

	/**
	 * Function to grep all error messages
	 *
	 * @return string
	 */
	function getFieldsErrorMessages() {
		$form_id = $this->html_form_id;
		//Created separated preview message
		$msgs = "";
		if ( ! empty( $this->preview_messages ) ) {
			$msgs .= "<label id=\"lbl_preview\" style='background-color: #ffffe0;
                border: 1px solid #e6db55;
                display: block;
                margin: 10px 0;
                padding: 5px 10px;
                width: auto;'>" . $this->preview_messages[0] . "</label><div style='clear:both;'></div>";
		}
		if ( ! isset( $this->field_messages ) || ( isset( $this->field_messages ) && empty( $this->field_messages ) ) ) {
			return $msgs;
		}

		$field_messages = $this->field_messages[ $form_id ];
		foreach ( $field_messages as $id_field => $text ) {
			$msgs .= '<div id="lbl_' . esc_attr( $id_field ) . '" class="wpt-form-error alert alert-danger">' . $text . '</div><div style="clear:both;"></div>';
		}

		return $msgs;
	}

	/**
	 * Gets warning messages to be displayed
	 *
	 * @return string
	 * @since 2.6.8
	 */
	function get_warning_messages() {
		$form_id = $this->html_form_id;

		if ( empty( $this->warning_messages ) || empty( $this->warning_messages[ $form_id ] ) ) {
			return '';
		}

		$messages = $this->warning_messages[ $form_id ];
		$result = '';
		foreach ( $messages as $id_field => $text ) {
			$result .= '<div class="wpt-form-warning">' . $text . '</div>';
		}

		return $result;
	}

	/**
	 * Javascript functions that moves error messages close to related field
	 *
	 * @return string
	 */
	function getFieldsErrorMessagesJs() {
		$form_id = $this->html_form_id;
		$js = "";
		if ( ! isset( $this->field_messages )
			|| ( isset( $this->field_messages )
				&& empty( $this->field_messages ) )
		) {
			return $js;
		}
		$field_messages = $this->field_messages[ $form_id ];
		$js .= '<script language="javascript">
            jQuery(function(){';
		foreach ( $field_messages as $id_field => $text ) {
			if ( $id_field != 'generic' ) {
				$js .= 'var fieldTarget = jQuery(\'[data-wpt-name="' . $id_field . '"]\').first();';
				$js .= 'jQuery(\'#lbl_' . $id_field . '\').insertBefore( fieldTarget );';
			}
		}
		$js .= '});
            </script>';

		return $js;
	}

	/**
	 * @deprecated function since CRED 1.3b3
	 *
	 * @param string $error_block
	 * @param string $error_message
	 */
	function add_form_error( $error_block, $error_message ) {
		// if the error block was not yet created, create the error block
		if ( ! isset( $this->form_errors[ $error_block ] ) ) {
			$this->form_errors[ $error_block ] = array();
		}
		if ( is_array( $error_message ) ) {
			$error_message = isset( $error_message[0] ) ? $error_message[0] : "";
		}
		// if the same exact message doesn't already exists
		if ( ! empty( $error_message ) && ! in_array( trim( $error_message ), $this->form_errors[ $error_block ] ) ) {
			$this->form_errors[ $error_block ][] = trim( $error_message );
		}
	}

	/**
	 * @deprecated function since CRED 1.3b3
	 *
	 * @param string $msg_block
	 * @param string $message
	 */
	function add_form_message( $msg_block, $message ) {
		// if the error block was not yet created, create the error block
		if ( ! isset( $this->form_messages[ $msg_block ] ) ) {
			$this->form_messages[ $msg_block ] = array();
		}

		if ( is_array( $message ) ) {
			$message = isset( $message[0] ) ? $message[0] : "";
		}

		// if the same exact message doesn't already exists
		if ( ! empty( $message ) && ! in_array( trim( $message ), $this->form_messages[ $msg_block ] ) ) {
			$this->form_messages[ $msg_block ][] = trim( $message );
		}
	}

	/**
	 * @return mixed|string|void
	 * @deprecated Use Toolset_Date_Utils::get_supported_date_format() instead.
	 */
	public static function getDateFormat() {
		$date_utils = Toolset_Date_Utils::get_instance();

		return $date_utils->get_supported_date_format();
	}

	/**
	 * Get a field information from id
	 *
	 * @param int $id
	 * @param string $field
	 *
	 * @return array
	 */
	function getFileData( $id, $field ) {
		$ret = array();
		$ret['value'] = $field['name'];
		$ret['file_data'] = array();
		$ret['file_data'][ $id ] = $field;
		$ret['file_upload'] = "";

		return $ret;
	}

}

?>
