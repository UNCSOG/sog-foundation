<?php
if ( ! function_exists( 'in_array_of_object_by_slug' ) ) {

	/**
	 * @param $slug
	 * @param $array_of_objects
	 *
	 * @return bool
	 */
	function in_array_of_object_by_slug( $slug, $array_of_objects ) {
		foreach ( $array_of_objects as $object ) {
			if ( isset( $object->slug ) && $slug === $object->slug ) {
				return true;
			}
		}

		return false;
	}

}

if ( ! function_exists( 'in_multidimensional_array_value' ) ) {

	function in_multidimensional_array_value( $needle, $haystack, $strict = false ) {
		foreach ( $haystack as $item ) {
			if ( ( $strict ? $item === $needle : $item == $needle ) ||
				( is_array( $item ) && in_multidimensional_array_value( $needle, $item, $strict ) )
			) {
				return true;
			}
		}

		return false;
	}

}

if ( ! function_exists( 'cred_sanitize_array' ) ) {

	/**
	 * array recursive sanitize_text_field
	 *
	 * @param mixed $array
	 *
	 * @return mixed
	 */
	function cred_sanitize_array( &$array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as &$value ) {
				if ( is_string( $value ) ) {
					$value = sanitize_text_field( $value );
				} else {
					cred_sanitize_array( $value );
				}
			}
		}

		return $array;
	}

}

if ( ! function_exists( 'cred__return_zero' ) ) {

	/**
	 * @return int
	 * @deprecated 2.5.7
	 */
	function cred__return_zero() {
		_deprecated_function( __FUNCTION__, '2.5.7', '__return_zero' );

		return __return_zero();
	}

}

if ( ! function_exists( 'cred__create_auto_draft' ) ) {

	/**
	 * Creates a auto draft post.
	 *
	 * @param $post_title
	 * @param $post_type
	 * @param string $user_id
	 * @return int|WP_Error
	 * @deprecated 2.5.7
	 */
	function cred__create_auto_draft( $post_title, $post_type, $user_id = "" ) {
		_deprecated_function( __FUNCTION__, '2.5.7', '\OTGS\Toolset\CRED\Model\Wordpress\AutoDraft::create()' );

		$dic = apply_filters( 'toolset_dic', false );
		$auto_draft_model = $dic->make( '\OTGS\Toolset\CRED\Model\Wordpress\AutoDraft' );

		$auto_draft_object = $auto_draft_model->create( $post_type, $user_id );

		if ( is_wp_error( $auto_draft_object ) ) {
			return $auto_draft_object;
		}

		return $auto_draft_object->ID;
	}

}

if ( ! function_exists( 'cred__parent_sort' ) ) {

	/**
	 * cred__parent_sort sort fields related to parents
	 *
	 * @param array $fields
	 * @param array $result
	 * @param int $parent
	 * @param int $depth
	 *
	 * @return array
	 */
	function cred__parent_sort( array $fields, array &$result = array(), $parent = 0, $depth = 0 ) {
		foreach ( $fields as $key => $field ) {
			if ( $field['parent'] == $parent ) {
				$field['depth'] = $depth;
				array_push( $result, $field );
				unset( $fields[ $key ] );
				cred__parent_sort( $fields, $result, $field['term_id'], $depth + 1 );
			}
		}

		return $result;
	}

}

if ( ! function_exists( 'is_cred_embedded' ) ) {

	/**
	 * is_cred_embedded
	 *
	 * @deprecated since version 1.9
	 */
	function is_cred_embedded() {
		return CRED_CRED::is_embedded();
	}

}

if ( function_exists( 'add_action' ) ) {

	add_action( 'init', 'cred_common_path' );

	/**
	 * cred_common_path
	 */
	function cred_common_path() {
		if ( ! defined( 'WPTOOLSET_FORMS_VERSION' ) ) {
			$toolset_common_bootstrap = Toolset_Common_Bootstrap::getInstance();
			$toolset_common_sections = array(
				'toolset_forms',
			);
			$toolset_common_bootstrap->load_sections( $toolset_common_sections );
		}
	}

}

if ( ! function_exists( 'cred_log' ) ) {

	/**
	 * @param mixed $message
	 * @param string $file
	 * @param string $type
	 * @param int $level
	 * @return boolean
	 * @deprecated 2.5.7 No debug function should be included in production, FGS.
	 */
	function cred_log( $message, $file = null, $type = null, $level = 1 ) {
		_deprecated_function( __FUNCTION__, '2.5.7' );

		return;
	}

}

/**
 * @param bool $post_id
 * @param string $text
 * @param string $action
 * @param string $class
 * @param string $style
 * @param string $message
 * @param string $message_after
 * @param int $message_show
 * @param bool $redirect
 * @param bool $return
 *
 * @return bool|mixed|string
 */
function cred_delete_post_link( $post_id = false, $text = '', $action = '', $class = '', $style = '', $message = '', $message_after = '', $message_show = 1, $redirect = false, $return = false ) {
	$output = CRED_Helper::cred_delete_post_link( $post_id, $text, $action, $class, $style, $message, $message_after, $message_show, $redirect );
	if ( $return ) {
		return $output;
	}
	echo $output;
}

/**
 * @param $form
 * @param bool $post_id
 * @param string $text
 * @param string $class
 * @param string $style
 * @param string $target
 * @param string $attributes
 * @param bool $return
 *
 * @return string
 */
function cred_edit_post_link( $form, $post_id = false, $text = '', $class = '', $style = '', $target = '', $attributes = '', $return = false ) {
	$output = CRED_Helper::cred_edit_post_link( $form, $post_id, $text, $class, $style, $target, $attributes );
	if ( $return ) {
		return $output;
	}
	echo $output;
}

/**
 * @param $form
 * @param bool $post_id
 * @param bool $return
 *
 * @return bool|string
 */
function cred_form( $form, $post_id = false, $return = false ) {
	$output = CRED_Helper::cred_form( $form, $post_id );
	if ( $return ) {
		return $output;
	}
	echo $output;
}

/**
 * @param $form
 * @param bool $user_id
 * @param bool $return
 *
 * @return bool|mixed|string|void
 */
function cred_user_form( $form, $user_id = false, $return = false ) {
	$output = CRED_Helper::cred_user_form( $form, $user_id );
	if ( $return ) {
		return $output;
	}
	echo $output;
}

/**
 * @return boolean
 * @deprecated 2.5.7 It was referencing a method that does not even exist!
 */
function has_cred_form() {
	_deprecated_function( __FUNCTION__, '2.5.7' );

	return false;
}

/**
 * public API to import from XML string
 *
 * @param string $xml
 * @param array $options
 *     'overwrite_forms'=>(0|1)             // Overwrite existing forms
 *     'overwrite_settings'=>(0|1)          // Import and Overwrite Toolset Forms Settings
 *     'overwrite_custom_fields'=>(0|1)     // Import and Overwrite Toolset Forms Custom Fields
 *     'force_overwrite_post_name'=>array   // Skip all, overwrite only forms from array
 *     'force_skip_post_name'=>array        // Skip forms from array
 *     'force_duplicate_post_name'=>array   // Skip all, duplicate only from array
 *
 * @return array
 *     'settings'=>(int),
 *     'custom_fields'=>(int),
 *     'updated'=>(int),
 *     'new'=>(int),
 *     'failed'=>(int),
 *     'errors'=>array()
 *
 * example:
 *   $result = cred_import_xml_from_string($import_xml_string, array('overwrite_forms'=>1, 'overwrite_settings'=>0,
 *     'overwrite_custom_fields'=>1)); note: force_duplicate_post_name, force_skip_post_name, force_overwrite_post_name
 *     - can work together
 */
function cred_import_xml_from_string( $xml, $options = array() ) {
	CRED_Loader::load( 'CLASS/XML_Processor' );
	$result = CRED_XML_Processor::importFromXMLString( $xml, $options );

	return $result;
}

/**
 * cred_user_import_xml_from_string
 *
 * @param string $xml
 * @param array $options
 *
 * @return string
 */
function cred_user_import_xml_from_string( $xml, $options = array() ) {
	CRED_Loader::load( 'CLASS/XML_Processor' );
	$result = CRED_XML_Processor::importUsersFromXMLString( $xml, $options );

	return $result;
}

/**
 * @param $forms
 *
 * @return string
 */
function cred_export_to_xml_string( $forms ) {
	CRED_Loader::load( 'CLASS/XML_Processor' );
	$xmlstring = CRED_XML_Processor::exportToXMLString( $forms );

	return $xmlstring;
}

/**
 * Maybe traslate a string, only if it is already registered.
 * This way we avoid registering it by defalt.
 *
 * @param string $name
 * @param string $string
 * @param string $context
 * @return string
 * @since 2.3.2
 */
function cred_maybe_translate( $name, $string, $context = 'CRED_CRED' ) {
	if ( ! apply_filters( 'toolset_is_wpml_active_and_configured', false ) ) {
		return $string;
	}

	if ( ! function_exists( 'icl_st_is_registered_string' ) ) {
		return $string;
	}

	if ( ! icl_st_is_registered_string( $context, $name ) ) {
		return $string;
	}

	return cred_translate( $name, $string, $context );
}

/**
 * cred_translate
 *
 * @param string $name
 * @param string $string
 * @param string $context
 *
 * @return string
 */
function cred_translate( $name, $string, $context = 'CRED_CRED' ) {
	if ( ! apply_filters( 'toolset_is_wpml_active_and_configured', false ) ) {
		return $string;
	}

	if ( ! function_exists( 'icl_t' ) ) {
		return $string;
	}

	if ( strpos( $context, 'cred-form-' ) !== false ) {
		$tmp = explode( "-", $context );
		$form_id = $tmp[ count( $tmp ) - 1 ];
		$is_user_form = get_post_type( $form_id ) == CRED_USER_FORMS_CUSTOM_POST_NAME;
		if ( $is_user_form ) {
			$context = str_replace( 'cred-form-', 'cred-user-form-', $context );
		}
	}

	return icl_t( $context, $name, stripslashes( $string ) );
}

/**
 * Registers WPML translation string.
 *
 * @param $context
 * @param $name
 * @param $value
 * @param bool $allow_empty_value
 * @deprecated 2.6
 */
function cred_translate_register_string( $context, $name, $value, $allow_empty_value = false ) {
	if ( ! apply_filters( 'toolset_is_wpml_active_and_configured', false ) ) {
		return;
	}

	if ( strpos( $context, 'cred-form-' ) !== false ) {
		$tmp = explode( "-", $context );
		$form_id = $tmp[ count( $tmp ) - 1 ];
		$is_user_form = get_post_type( $form_id ) == CRED_USER_FORMS_CUSTOM_POST_NAME;
		if ( $is_user_form ) {
			$context = str_replace( 'cred-form-', 'cred-user-form-', $context );
		}
	}

	do_action(
		'wpml_register_single_string',
		$context,
		$name,
		stripslashes( $value ),
		$allow_empty_value
	);
}

/**
 * Filter the_content tag
 * Added support for resolving third party shortcodes in cred shortcodes
 */
function cred_do_shortcode( $content ) {
	$shortcodeParser = CRED_Loader::get( 'CLASS/Shortcode_Parser' );
	$content = $shortcodeParser->parse_content_shortcodes( $content );

	return $content;
}

/**
 * @return array
 */
function cred_disable_shortcodes() {
	global $shortcode_tags;

	$shortcode_back = $shortcode_tags;
	$shortcode_tags = array();

	return ( $shortcode_back );
}

/**
 * @param $shortcode_back
 */
function cred_re_enable_shortcodes( $shortcode_back ) {
	global $shortcode_tags;

	$shortcode_tags = $shortcode_back;
}

/**
 * @param $hook
 * @deprecated 2.5.7
 */
function cred_disable_filters_for( $hook ) {
	_deprecated_function( __FUNCTION__, '2.5.7', '\OTGS\Toolset\CRED\Model\Wordpress\Hook::remove_all_callbacks()' );

	$hook_model = new \OTGS\Toolset\CRED\Model\Wordpress\Hook();

	return $hook_model->remove_all_callbacks( $hook );
}

/**
 * @param $hook
 * @param $back
 * @deprecated 2.5.7
 */
function cred_re_enable_filters_for( $hook, $back ) {
	_deprecated_function( __FUNCTION__, '2.5.7', '\OTGS\Toolset\CRED\Model\Wordpress\Hook::restore_hook_callbacks()' );

	$hook_model = new \OTGS\Toolset\CRED\Model\Wordpress\Hook();

	$hook_model->restore_hook_callbacks( $hook, $back );
}

/**
 * Init Toolset Forms
 */
function cred_start() {
	CRED_Loader::load( 'CLASS/CRED' );
	$cred = new CRED_CRED();
	$cred->init();
}

/**
 * Checks if we have a real ajax call.
 *
 * We have 2 important note:
 * 1. this function must be declared in plugin.php of Toolset Forms
 * 2. contains also HTTP_X_REQUESTED_WITH because in Toolset Forms we still make external ajax calls
 *
 * @return boolean
 * @note This function is highly misleading: it checks nothing like its name suggests.
 *     Since Forms 2.4 we NO LONGER make "external AJAX calls", whatever it means, and we manage our
 *     AJAXed forms using proper, real, actual AJAX calls.
 *     In any way, this function is barely telling you that an AJAX call is ongoing, but you CAN NEVER
 *     know whom originated it.
 *     Right now, this is only used on frontend select2 related affairs. Avoid using it anywhere else,
 *     and try to clean up the current usage, then remove.
 */
function cred_is_ajax_call() {
	return ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest';
}
