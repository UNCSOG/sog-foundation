<?php

/**
 * Form Builder Helper Class
 */
class CRED_Form_Builder_Helper {

	public static $_current_post_title;
	public static $_current_prefix;
	public static $_current_form_id;

	// CONSTANTS
	const MSG_PREFIX = 'Message_';                                 // Message prefix for WPML localization

	public $_formBuilder = null;
	// for delayed redirection, if needed
	private $_uri_ = '';
	private $_delay_ = 0;

	private $label_translations = []; // This class is a mess, and I have to do this

	/* =============================== INSTANCE METHODS ======================================== */

	public function __construct( $formBuilder ) {
		$this->_formBuilder = $formBuilder;
	}

	/**
	 * Returns form data
	 *
	 * TODO: it should be refactored, it is used every where. The chances to break anything are 99%
	 */
	public function get_form_data() {
		return $this->_formBuilder->_formData;
	}

	/**
	 * Get current url under which this is executed
	 *
	 * @param array $replace_get
	 * @param array $remove_get
	 *
	 * @return array|mixed|string
	 */
	public function currentURI($replace_get = array(), $remove_get = array()) {
		$request_uri = esc_html($_SERVER["REQUEST_URI"]);
		if (!empty($replace_get)) {
			$request_uri = explode('?', $request_uri, 2);
			$request_uri = $request_uri[0];

			parse_str($_SERVER['QUERY_STRING'], $get_params);
			if (empty($get_params))
				$get_params = array();

			foreach ($replace_get as $key => $value) {
				$get_params[$key] = $value;
			}
			if (!empty($remove_get)) {
				foreach ($get_params as $key => $value) {
					if (isset($remove_get[$key]))
						unset($get_params[$key]);
				}
			}
			if (!empty($get_params))
				$request_uri.='?' . http_build_query($get_params, '', '&');
		}
		return $request_uri;
	}

	/**
	 * @param int $id
	 * @param string|null $type
	 *
	 * @return mixed
	 */
	public function getLocalisedPermalink($id, $type = null) {
		static $_cache = array();

		if (!isset($_cache[$id])) {
			// WPML localised ID
			// function icl_object_id($element_id, $element_type='post',
			// $return_original_if_missing=false, $ulanguage_code=null)
			if (function_exists('icl_object_id')) {
				if (null === $type)
					$type = get_post_type($id);
				$loc_id = icl_object_id($id, $type, true);
			}
			else {
				$loc_id = $id;
			}
			$_cache[$id] = get_permalink($loc_id);
		}
		return $_cache[$id];
	}

	/**
	 * @param string $msg
	 *
	 * @return WP_Error
	 */
	public function error($msg = '') {
		return new WP_Error($msg);
	}

	/**
	 * @param $obj
	 *
	 * @return bool
		* @deprecated function since 1.9.4
	 */
	public function isError($obj) {
		return is_wp_error($obj);
	}

	/**
	 * @param $obj
	 *
	 * @return string
	 */
	public function getError($obj) {
		if ( is_wp_error( $obj ) ) {
			return $obj->get_error_message( $obj->get_error_code() );
		}
		return '';
	}

	/**
	 * @used by CRED_Form_Base::print_form
	 * @staticvar type $mimes
	 * @return type
	 */
	public function getAllowedMimeTypes() {
		static $mimes = null;

		if (null == $mimes) {
			$mimes = array();
			$wp_mimes = get_allowed_mime_types();
			foreach ($wp_mimes as $exts => $mime) {
				$exts_a = explode('|', $exts);
				foreach ($exts_a as $single_ext) {
					$mimes[$single_ext] = $mime;
				}
			}
			//$mimes=implode(',',$mimes);
			unset($wp_mimes);
		}
		return $mimes;
	}

	/**
	 * @param $post_type
	 *
	 * @return null|array
	 */
	public function getFieldSettings($post_type) {
		static $fields = null;
		static $_post_type = null;

		if (null === $fields || $_post_type != $post_type) {
			$_post_type = $post_type;
			if ($post_type == 'user') {
				$ffm = CRED_Loader::get('MODEL/UserFields');
				$fields = $ffm->getFields(false, '', '', true, array($this, 'getLocalisedMessage'));
			} else {
				$ffm = CRED_Loader::get('MODEL/Fields');
				$fields = $ffm->getFields($post_type, true, array($this, 'getLocalisedMessage'));
			}

			// in CRED 1.1 post_fields and custom_fields are different keys, merge them together to keep consistency
			if (array_key_exists('post_fields', $fields)) {
				$fields['_post_fields'] = $fields['post_fields'];
			}
			if (
					array_key_exists('custom_fields', $fields) && is_array($fields['custom_fields'])
			) {
				if (isset($fields['post_fields']) && is_array($fields['post_fields'])) {
					$fields['post_fields'] = array_merge($fields['post_fields'], $fields['custom_fields']);
				} else {
					$fields['post_fields'] = $fields['custom_fields'];
				}
			}
		}
		return $fields;
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function getRecaptchaSettings($settings) {
		if (!$settings) {
			$sm = CRED_Loader::get('MODEL/Settings');
			$generic_settings = $sm->getSettings();
			if (
				isset( $generic_settings['recaptcha']['public_key'] ) &&
				isset( $generic_settings['recaptcha']['private_key'] ) &&
				! empty( $generic_settings['recaptcha']['public_key'] ) &&
				! empty( $generic_settings['recaptcha']['private_key'] )
			) {
				$settings = $generic_settings['recaptcha'];
			}
		}
		return $settings;
	}

	/**
	 * Function used to translate e message from extra message list by message_id
	 * It is used as callable function in UserFields/getFields function
	 *
	 * @param string $extra_messsage_id
	 *
	 * @return string
	 * @deprecated 2.6
	 */
	public function getLocalisedMessage($extra_messsage_id) {
		static $messages = null;
		static $formData = null;
		$formData = $this->get_form_data(); //$this->friendGet($this->_formBuilder, '_formData');
		$fields = $formData->getFields();
		$messages = $fields['extra']->messages;
		$messages['cred_message_no_recaptcha_keys'] = __( 'no recaptcha keys found', 'wp-cred' );

		$extra_messsage_id = 'cred_message_' . $extra_messsage_id;
		if ( ! isset( $messages[ $extra_messsage_id ] ) ) {
			return '';
		}
		return cred_translate(
			self::MSG_PREFIX . $extra_messsage_id, $messages[$extra_messsage_id], 'cred-form-' . $formData->getForm()->post_title . '-' . $formData->getForm()->ID
		);
	}

	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function getUserRolesByID($user_id) {
		$user = get_userdata($user_id);
		return empty($user) ? array() : $user->roles;
	}

	/**
	 * @param int $post_id
	 * @param bool $track
	 *
	 * @return object
	 */
	public function CRED_extractPostFields($post_id, $track = false) {
		global $user_ID;
		// reference to the form submission method
		$method = & $_POST;

		// get refs here
		$form = $this->get_form_data();

		$form_id = $form->getForm()->ID;
		$zebraForm = $this->_formBuilder->_cred_form_rendering;
		$_fields = $form->getFields();
		$form_type = $_fields['form_settings']->form['type'];

		$p = get_post($post_id);

		//Fix Problem with using 2 forms in the same page - saving to the wrong post type
		$post_type = $_fields['form_settings']->post['post_type'];
		//$post_type= isset($this->(isset($p)) ? get_post($post_id)->post_type : '';
		//###############################################################################

		$fields = CRED_StaticClass::$out['fields'];
		$form_fields = CRED_StaticClass::$out['form_fields'];

		// extract main post fields
		$post = new stdClass;
		// ID
		$post->ID = $post_id;
		// author
		if ('new' == $form_type)
			$post->post_author = $user_ID;
		// title
		if (
				array_key_exists('post_title', $form_fields) &&
				array_key_exists('post_title', $method)
		) {
			$post->post_title = stripslashes($method['post_title']);
			unset($method['post_title']);
		}
		// content
		if (
				array_key_exists('post_content', $form_fields) &&
				array_key_exists('post_content', $method)
		) {
			$post->post_content = stripslashes($method['post_content']);
			unset($method['post_content']);
		}
		// excerpt
		if (
				array_key_exists('post_excerpt', $form_fields) &&
				array_key_exists('post_excerpt', $method)
		) {
			$post->post_excerpt = stripslashes($method['post_excerpt']);
			unset($method['post_excerpt']);
		}
		// parent
		if (
			array_key_exists( 'post_parent', $form_fields )
			&& array_key_exists( 'post_parent', $method )
			&& ( isset( $fields[ 'parents' ] ) && isset( $fields[ 'parents' ][ 'post_parent' ] )
				|| isset( $fields[ 'hierarchical_parents' ] ) && isset( $fields[ 'hierarchical_parents' ][ 'post_parent' ] ) )
			&& intval( $method[ 'post_parent' ] ) >= 0
		) {
			$post->post_parent = intval( $method[ 'post_parent' ] );
			unset( $method[ 'post_parent' ] );
		}

		// type
		$post->post_type = $post_type;
		// status
		$_fields['form_settings']->post['post_status'] = toolset_getarr( $_fields['form_settings']->post, 'post_status', 'draft' );

		if ( 'original' === $_fields['form_settings']->post['post_status'] ) {
			if ( 'edit' !== $form_type ) {
				$_fields['form_settings']->post['post_status'] = 'draft';
			}
		} elseif ( null === get_post_status_object( $_fields['form_settings']->post['post_status'] ) ) {
			$_fields['form_settings']->post['post_status'] = 'draft';
		}

		if ( 'original' !== $_fields['form_settings']->post['post_status'] ) {
			$post->post_status = $_fields['form_settings']->post['post_status'];
		}

		if ($track) {
			$basic_post_fields = CRED_Fields_Model::get_basic_post_fields();

			// track the data, eg for notifications
			if ( isset( $post->post_title ) ) {
				$this->trackData( array( $basic_post_fields['post_title']['name'] => $post->post_title ) );
			}
			if ( isset( $post->post_content ) ) {
				$this->trackData( array( $basic_post_fields['post_content']['name'] => $post->post_content ) );
			}
			if ( isset( $post->post_excerpt ) ) {
				$this->trackData( array( $basic_post_fields['post_excerpt']['name'] => $post->post_excerpt ) );
			}
		}

		// return them
		return $post;
	}

	/**
	 * @param int $user_id
	 * @param string $user_role
	 * @param bool $track
	 *
	 * @return array
	 */
	public function CRED_extractUserFields($user_id, $user_role, $track = false) {
		global $user_ID;
		// reference to the form submission method
		$method = & $_POST;

		// get refs here
		$form = $this->get_form_data();
		$form_id = $form->getForm()->ID;
		$_fields = $form->getFields();
		$form_type = $_fields['form_settings']->form['type'];

		$autogenerate_user = (boolean) $_fields['form_settings']->form['autogenerate_username_scaffold'] ? true : false;
		$autogenerate_nick = (boolean) $_fields['form_settings']->form['autogenerate_nickname_scaffold'] ? true : false;
		$autogenerate_pass = (boolean) $_fields['form_settings']->form['autogenerate_password_scaffold'] ? true : false;

		$u = get_user_by('ID', $user_id);

		//user
		$post_type = $_fields['form_settings']->post['post_type'];

		$fields = CRED_StaticClass::$out['fields'];
		$form_fields = $fields['form_fields'];

		// extract main post fields
		$user = array();
		$user['ID'] = $user_id;
		$user['user_role'] = $user_role;
		foreach ($form_fields as $name => $field) {
			if (array_key_exists($name, $method)) {
				$user[$name] = stripslashes($method[$name]);
			}
		}

		if ($form_type == 'new' && isset($_POST['user_pass'])) {
			CRED_StaticClass::$_password_generated = $_POST['user_pass'];
		}

		if ($form_type == 'new' &&
				isset($user['user_email']) &&
				(
				($autogenerate_user || !isset($_POST['user_login'])) ||
				($autogenerate_nick || !isset($_POST['nickname'])) ||
				($autogenerate_pass || !isset($_POST['user_pass'])))
		) {

			$settings_model = CRED_Loader::get('MODEL/Settings');
			$settings = $settings_model->getSettings();

			if ($autogenerate_pass || !isset($_POST['user_pass'])) {
				$password_generated = wp_generate_password(10, false);
				CRED_StaticClass::$_password_generated = $password_generated;
				$user["user_pass"] = $password_generated;
			}

			$username_generated = CRED_StaticClass::generateUsername($user['user_email']);

			if (!isset($_POST['nickname'])) {
				if ($autogenerate_nick) {
					$nick_generated = $username_generated;
					CRED_StaticClass::$_nickname_generated = $nick_generated;
					$user["nickname"] = $nick_generated;
				} else {
					$user["nickname"] = $user['user_email'];
				}
			}

			//user_login is mandatory
			if (!isset($_POST['user_login'])) {
				if ($autogenerate_user) {
					CRED_StaticClass::$_username_generated = $username_generated;
					$user["user_login"] = $username_generated;
				} else {
					$user["user_login"] = $user['user_email'];
				}
			}
		}

		if ( $track ) {
			$fields_to_track = array(
				'user_login' => __( 'Username', 'wp-cred' ),
				'user_email' => __( 'User email', 'wp-cred' ),
				'user_pass' => __( 'User password', 'wp-cred' ),
				'nickname' => __( 'Nickname', 'wp-cred' )
			);
			foreach ( $fields_to_track as $field => $label ) {
				// track the data, eg for notifications
				if ( isset( $user[ $field ] ) ) {
					$this->trackData( array( $label => $user[ $field ] ) );
				}
			}
		}

		// return them
		return $user;
	}

	/**
	 * Check if a file has a expected filetype
	 *
	 * @param $filename
	 * @param $filetype
	 * @param $expected_filetypes
	 *
	 * @return bool
	 */
    private function is_correct_filetype($filename, $filetype, $expected_filetypes) {
        $filetypes = array();
        $filetypes['audio'] = array('mp3|m4a|m4b' => 'audio/mpeg',
            'ra|ram' => 'audio/x-realaudio',
            'wav' => 'audio/wav',
            'ogg|oga' => 'audio/ogg',
            'mid|midi' => 'audio/midi',
            'wma' => 'audio/x-ms-wma',
            'wax' => 'audio/x-ms-wax',
            'mka' => 'audio/x-matroska');
        $filetypes['audio'] = apply_filters('audio_upload_mimes', $filetypes['audio']);
        $filetypes['video'] = array('asf|asx' => 'video/x-ms-asf',
            'wmv' => 'video/x-ms-wmv',
            'wmx' => 'video/x-ms-wmx',
            'wm' => 'video/x-ms-wm',
            'avi' => 'video/avi',
            'divx' => 'video/divx',
            'flv' => 'video/x-flv',
            'mov|qt' => 'video/quicktime',
            'mpeg|mpg|mpe' => 'video/mpeg',
            'mp4|m4v' => 'video/mp4',
            'ogv' => 'video/ogg',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            '3gp|3gpp' => 'video/3gpp', // Can also be audio
            '3g2|3gp2' => 'video/3gpp2');
        $filetypes['video'] = apply_filters('video_upload_mimes', $filetypes['video']);
        $filetypes['image'] = array('jpg|jpeg|jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'bmp' => 'image/bmp',
            'tif|tiff' => 'image/tiff',
            'ico' => 'image/x-icon');
        $filetypes['image'] = apply_filters('image_upload_mimes', $filetypes['image']);

        $filetypes['file'] = array();
        $filetypes['file'] = CRED_StaticClass::$_allowed_mime_types;
        $filetypes['file'] = apply_filters('file_upload_mimes', $filetypes['file']);

        CRED_StaticClass::$_allowed_mime_types = $filetypes['file'];

        add_filter('upload_mimes', array('CRED_StaticClass', 'cred__add_custom_mime_types'));

        $filename_to_check = "";

        if (is_array($filename)) {
            if (isset($filename[0]) && is_string($filename[0])) {
                $filename_to_check = sanitize_file_name($filename[0]);
            }
        } else {
            $filename_to_check = sanitize_file_name($filename);
        }

        $ret = wp_check_filetype($filename_to_check, CRED_StaticClass::$_allowed_mime_types);

        return !empty($ret['ext']);
    }

	/**
	 * @param $zebraForm
	 * @param $fields
	 */
    public function checkFilePost($zebraForm, $fields) {
        $method = & $_POST;
        foreach ($_FILES as $k => $v) {
            $fk = str_replace("wpcf-", "", $k);
            // TODO Maybe worth is to add error messages based on cases
            // http://www.php.net/manual/en/features.file-upload.errors.php
            if (!is_array($v['name'])) {
                // This means this is a single file-related field
                if (isset($v['error'])) {
                    if ($v['error'] == 0) {
                        $method[$k] = $v['name'];
                    } else if ($v['error'] == 1 || $v['error'] == 2) {
                        $error_files[] = $v['name'];
                        $zebraForm->add_field_message(__('File Error Code: ', 'wp-cred') . $v['error'] . ', ' . __('file too big ', 'wp-cred'), $v['name']);
                        $zebraForm->add_top_message(__('File Error Code: ', 'wp-cred') . $v['error'] . ', ' . __('file too big ', 'wp-cred'), $v['name']);
                    } else {
                        if (isset($fields[$fk]['data']['validate']['required']['active']) &&
                                $fields[$fk]['data']['validate']['required']['active'] == 1 &&
                                $v['error'] == 4
                        ) {
                            $zebraForm->add_field_message(__($fields[$fk]['name'] . ' Field is required', 'wp-cred'), $k);
                        }
                    }
                }
            } else {
                // This means this is a repetitive file-related field
                // Although it can be passed just one value, it is always posted as an array
                // We need to be careful because we might be posting also data for existing field values!
                foreach ($v['name'] as $key => $value) {
                    if (isset($v ['error'][$key])) {
                        if ($v['error'][$key] == 0) {
                            if (isset($method[$k])) {
                                if (!is_array($method[$k])) {
                                    $method[$k] = array($method[$k]);
                                }
                                if (isset($method[$k][$key])) {
                                    $method[$k][] = $v['name'][$key];
                                } else {
                                    $method[$k][$key] = $v['name'][$key];
                                }
                            } else {
                                $method[$k] = array($key => $v['name'][$key]);
                            }
                        } else if ($v['error'][$key] == 1 || $v['error'][$key] == 2) {
                            $error_files[] = $v['name'][$key];
                            $zebraForm->add_field_message(__('File Error Code: ', 'wp-cred') . $v['error'][$key] . ', ' . __('file too big ', 'wp-cred') . ' (' . __('file', 'wp-cred') . ' ' . $key . ')', $v['name'][$key]);
                            $zebraForm->add_top_message(__('File Error Code: ', 'wp-cred') . $v['error'][$key] . ', ' . __('file too big ', 'wp-cred') . ' (' . __('file', 'wp-cred') . ' ' . $key . ')', $v['name'][$key]);
                        } else {
                            if (isset($fields[$fk]['data']['validate']['required']['active']) &&
                                    $fields[$fk]['data']['validate']['required']['active'] == 1 &&
                                    $v['error'][$key] == 4
                            ) {
                                $zebraForm->add_field_message(__($fields[$fk]['name'] . ' Field is required', 'wp-cred'), $k);
                            }
                        }
                    }
                }
            }
        }
    }

	/**
     * Function used to check if files uploaded have correct type field
     *
	 * @param $_fields
	 * @param $_form_fields_info
	 * @param $zebraForm
	 * @param $error_files
	 */
    public function checkFilesType($_fields, $_form_fields_info, &$zebraForm, &$error_files) {
	    if ( ! isset( $_fields ) ) {
		    return;
	    }
        //Fix upload filetypes not repetitive one
        foreach ($_fields as $key => $field) {
            if (
                    ('audio' == $field['type'] ||
                    'video' == $field['type'] ||
                    'file' == $field['type'] ||
                    'image' == $field['type'])
            ) {
                $mykey = isset($field['plugin_type_prefix']) ? $field['plugin_type_prefix'] . $key : $key;
	            if ( isset( $_form_fields_info[ $key ] )
		            && isset( $_form_fields_info[ $key ]['repetitive'] )
		            && $_form_fields_info[ $key ]['repetitive']
	            ) {
                    if (isset($_FILES[$mykey])) {
                        $rep_files_array = array();
                        $n = 0;
                        foreach ($_FILES[$mykey]['name'] as $n => $fname) {
	                        if ( empty( $fname ) ) {
		                        continue;
	                        }
	                        if ( ! isset( $rep_files_array[ $n ] ) ) {
		                        $rep_files_array[ $n ] = array();
	                        }
                            $rep_files_array[$n]['name'] = $fname;
                            $n++;
                        }

                        $n = 0;
                        foreach ($_FILES[$mykey]['type'] as $n => $ftype) {
	                        if ( empty( $ftype ) ) {
		                        continue;
	                        }
                            $rep_files_array[$n]['type'] = $ftype;
                            $n++;
                        }
                        foreach ($rep_files_array as $n => $cfile) {
	                        if ( ! empty( $cfile['name'] )
		                        && ! $this->is_correct_filetype( $cfile['name'], $cfile['type'], $field['type'] )
	                        ) {
                                $error_files[] = $mykey;
                                $zebraForm->add_field_message($field['name'] . ' ' . __("File Type Error", 'wp-cred'), $mykey);
                                $zebraForm->add_top_message($field['name'] . ' ' . __("File Type Error", 'wp-cred'), $mykey);
                                continue;
                            }
                        }
                        unset($rep_files_array);
                    }
                } else {
		            if ( isset( $_FILES[ $mykey ] )
			            && ! empty( $_FILES[ $mykey ]['type'] )
			            && ( isset( $_FILES[ $mykey ]['error'][0] )
				            && $_FILES[ $mykey ]['error'][0] != 4 )
			            && ! $this->is_correct_filetype( $_FILES[ $mykey ]['name'], $_FILES[ $mykey ]['type'], $field['type'] )
		            ) {
                        $error_files[] = $mykey;
                        $zebraForm->add_field_message($field['name'] . ' ' . __("File Type Error", 'wp-cred'), $mykey);
                        $zebraForm->add_top_message($field['name'] . ' ' . __("File Type Error", 'wp-cred'), $mykey);
                    }
                }
            }
        }
    }

	/**
	 * @param $post_id
	 * @param bool $track
	 *
	 * @return array
	 */
    public function CRED_extractCustomFields($post_id, $track = false) {
        global $user_ID;
        // reference to the form submission method
        $method = & $_POST;

        $error_files = array();

        $form = $this->get_form_data();

        $form_id = $form->getForm()->ID;
        $form_fields = $form->getFields();
        $form_type = $form_fields['form_settings']->form['type'];
        $post_type = $form_fields['form_settings']->post['post_type'];

        $_fields = CRED_StaticClass::$out['fields'];
        $_form_fields = CRED_StaticClass::$out['form_fields'];
        $_current_form_fields = CRED_StaticClass::$out['current_form_fields'];
        $_form_fields_info = CRED_StaticClass::$out['form_fields_info'];
        $zebraForm = $this->_formBuilder->_cred_form_rendering;

        // custom fields
        $fields = array();
        $removed_fields = array();
        // taxonomies
        $taxonomies = array('flat' => array(), 'hierarchical' => array());
        $fieldsInfo = array();
        // files, require extra care to upload correctly
        $files = array();

        if (count($error_files) > 0) {
            // Bail out early if there are errors when uploading files
            return array($fields, $fieldsInfo, $taxonomies, $files, $removed_fields, $error_files);
        }

        foreach ($_fields['post_fields'] as $key => $field) {
			$field_label = $field['name'];
			$translated_field_label = apply_filters( 'cred_translate_content', $field['name'], $field['slug'] . '-label' );
			// I need to save them because the way it is implemented would need a full refactor to make it work with WPML
			$this->label_translations[ $translated_field_label ] = $field['name'];

            $done_data = false;

            // use the key as was rendered (with potential prefix)
            $key11 = $key;
            if (isset($field['plugin_type_prefix'])) {
                $key = $field['plugin_type_prefix'] . $key;
            }

            // if this field was not rendered in this specific form, bypass it
            if (!array_key_exists($key11, $_current_form_fields)) {
                continue;
            }

            $fieldsInfo[$key] = array('save_single' => false);

	        if (
	        ( 'audio' == $field['type']
		        || 'video' == $field['type']
		        || 'file' == $field['type']
		        || 'image' == $field['type'] )
	        ) {
                if (
                        !array_key_exists($key, $method)
                ) {
                    // remove the fields
                    $removed_fields[] = $key;
                    unset($fieldsInfo[$key]);
                } else {
                    $fields[$key] = $method[$key];
				}
            }

	        if ( 'checkboxes' == $field[ 'type' ]
                && !array_key_exists( $key, $method ) ) {

                if ( isset( $field[ 'data' ][ 'save_empty' ] )
                    && $field[ 'data' ][ 'save_empty' ] == 'yes'
                ) {
                    $values = array();
                    foreach ( $field[ 'data' ][ 'options' ] as $optionkey => $optiondata ) {
                        $values[ $optionkey ] = '0';
                    }

                    // let model serialize once, fix Types-CRED mapping issue with checkboxes
                    $fieldsInfo[ $key ][ 'save_single' ] = true;
                    $fields[ $key ] = $values;
                } else {
                    // remove the fields
                    $removed_fields[] = $key;
                    unset( $fieldsInfo[ $key ] );
                }

	        } elseif (
		        'checkbox' == $field['type']
		        && ! array_key_exists( $key, $method )
	        ) {

		        if ( isset( $field[ 'data' ][ 'save_empty' ] )
			        && 'yes' == $field[ 'data' ][ 'save_empty' ] ) {
			        $fields[ $key ] = '0';
		        } else {
			        // remove the fields
			        $removed_fields[] = $key;
			        unset( $fieldsInfo[ $key ] );
		        }

            } elseif (array_key_exists($key, $method)) {
                // normalize repetitive values out of sequence
		        if ( $_form_fields_info[ $key11 ]['repetitive']
			        || 'multiselect' == $_form_fields_info[ $key11 ]['type']
		        ) {
                    if (is_array($method[$key])) {
						$original_values = $method[ $key ];
						ksort( $original_values );
                        $values = array_values( $original_values );
                    } else {
                        $aux_value_array = array($method[$key]);
                        $values = array_values($aux_value_array);
                    }
                } else {
                    $values = $method[$key];
                }

		        if ( 'audio' == $field['type']
			        || 'video' == $field['type']
			        || 'file' == $field['type']
			        || 'image' == $field['type']
		        ) {
                    //TODO check this
                    if (isset($_FILES) && !empty($_FILES[$key])) {
                        $files[$key] = $zebraForm->getFileData($key, $_FILES[$key]);
                        $files[$key]['name_orig'] = $key11;
                        $files[$key]['label'] = $field['name'];
                        $files[$key]['repetitive'] = $_form_fields_info[$key11]['repetitive'];
                    } else {
						// Avoid empty instances on repeating fields
						// when POSTed insted of FILEs-ed
						if (
							toolset_getnest( $_form_fields_info, array( $key11, 'repetitive' ), false )
							&& is_array( $values )
						) {
							$values = array_filter( $values );
						}
					}
		        } elseif ( 'textarea' == $field['type']
			        || 'wysiwyg' == $field['type']
		        ) {
                    // stripslashes for textarea, wysiwyg fields
                    if (is_array($values))
                        $values = array_map('stripslashes', $values);
                    else
                        $values = stripslashes($values);
                } elseif ( 'textfield' == $field['type']
			        || 'text' == $field['type']
		        ) {
                    // stripslashes for text fields
					if ( is_array( $values ) ) {
				        $values = array_map( 'stripslashes', $values );
			        } else {
				        $values = stripslashes( $values );
			        }
                }

                // track form data for notification mail
                if ($track) {
                    $tmp_data = null;
                    if ('checkbox' == $field['type']) {
                        if (
                            ! isset( $field['data']['display'] )
                            || 'db' == $field['data']['display']
                        ) {
		                    $tmp_data = $values;
	                    } else {
                            $tmp_data = isset( $field['data']['display_value_selected'] )
                                ? $field['data']['display_value_selected']
                                : null;
	                    }
                    }
                    elseif ('radio' == $field['type'] || 'select' == $field['type']) {
                        //$tmp_data = $field['data']['options'][$values]['title'];
                        foreach ($field['data']['options'] as $_key => $_val) {
                            if (isset($_val['value']) && $_val['value'] == $values) {
                                $tmp_data = apply_filters( 'cred_translate_content', $_val['title'], $_key );
                            }
                        }
                    } elseif ( 'checkboxes' == $field['type']
	                    || 'multiselect' == $field['type']
                    ) {
                        $tmp_data = array();
	                    foreach ( $values as $tmp_val ) {
		                    $tmp_data[] = $field['data']['options'][ $tmp_val ]['title'];
	                    }

                        unset($tmp_val);
                    }
                    if (isset($tmp_data)) {
                        $this->trackData(array($translated_field_label => $tmp_data));
                        $done_data = true;
                    }
                }

		        if ( 'checkboxes' == $field['type']
			        || 'multiselect' == $field['type']
		        ) {
                    if (!is_array($values)) {
                        $values = array($values);
                    }
                    $result = array();
                    foreach ($field['data']['options'] as $optionkey => $optiondata) {
                        if (in_array($optionkey, $values)) {
                            if (array_key_exists('set_value', $optiondata) && isset($optiondata['set_value'])) {
                                $result[$optionkey] = array($optiondata['set_value']);
                            } elseif ('multiselect' == $field['type']) {
                                $result[$optionkey] = array($optionkey);
                            }
                        }
                    }

                    $values = $result;

                    $fieldsInfo[$key]['save_single'] = true;
				} elseif ( $field['type'] === 'radio' ) {
					$values = str_replace( [ '"', '\\\'' ], [ '\\"', '\\\'' ], $values );
				} elseif ( $field['type'] === 'select' ) {
					$values = str_replace( [ '\\"', '\\\\\'' ], [ '"', '\'' ], $values );
                } elseif ('date' == $field['type']) {

                    /*
                     * Single/repetitive values for Date are not set right,
                     * because CRED used Date as string - not array
                     *
                     * NOTE: There is no general method in CRED to check if repetitive?
                     * Types have types_is_repetitive() function.
                     * If it's types fiels - repetitive flag is in
                     * $field['data']['repetitive']
                     */
                    $_values = empty($_form_fields_info[$key11]['repetitive']) ? array($values) : $values;
                    $new_values = array();
                    foreach ($_values as $values) {
                        if (!empty($values['datepicker'])) {
                            $date_format = $zebraForm->getDateFormat();

	                        if ( ! is_array( $values ) ) {
		                        $tmp = array( $values );
	                        } else {
		                        $tmp = $values;
	                        }

                            // track form data for notification mail
                            if ($track) {
                                $this->trackData(array($translated_field_label => $tmp));
                                $done_data = true;
                            }

                            $timestamp = $tmp['datepicker'];

	                        if ( ! isset( $tmp['hour'] ) ) {
		                        $tmp['hour'] = "00";
	                        }
	                        if ( ! isset( $tmp['minute'] ) ) {
		                        $tmp['minute'] = "00";
	                        }

	                        if ( $tmp['hour'] < 10 && strlen( $tmp['hour'] ) == 1 ) {
		                        $tmp['hour'] = "0{$tmp['hour']}";
	                        }
	                        if ( $tmp['minute'] < 10 && strlen( $tmp['minute'] ) == 1 ) {
		                        $tmp['minute'] = "0{$tmp['minute']}";
	                        }

                            $timestamp_date = adodb_date('dmY', $timestamp);
                            $date = adodb_mktime(intval($tmp['hour']), intval($tmp['minute']), 0, substr($timestamp_date, 2, 2), substr($timestamp_date, 0, 2), substr($timestamp_date, 4, 4));
                            $timestamp = $date;

	                        if ( isset( $tmp['hour'] ) ) {
		                        unset( $tmp['hour'] );
	                        }
	                        if ( isset( $tmp['minute'] ) ) {
		                        unset( $tmp['minute'] );
	                        }

                            $new_values[] = $timestamp;
                        } else {
	                        if ( isset( $values['hour'] ) ) {
		                        unset( $values['hour'] );
	                        }
	                        if ( isset( $values['minute'] ) ) {
		                        unset( $values['minute'] );
	                        }
                        }
                    }
                    $values = $new_values;
                    unset($new_values);
                }
                elseif ('skype' == $field['type']) {

                    //TODO: check this could be no need array($values)
                    $values = isset($_form_fields_info[$key11]['repetitive']) && $_form_fields_info[$key11]['repetitive'] == 1 ? $values : array($values);

                    if ($track) {
                        $this->trackData(array($translated_field_label => $values));
                        $done_data = true;
                    }
                }
                // Modified by Srdjan END
                // dont track file/image data now but after we upload them..
		        if (
			        $track
			        && ! $done_data
			        && 'audio' != $field['type']
			        && 'video' != $field['type']
			        && 'file' != $field['type']
			        && 'image' != $field['type']
		        ) {
                    $this->trackData(array($translated_field_label => $values));
                }

				$values = apply_filters( 'cred_extracted_field_value', $values, $field );
				$values = apply_filters( 'cred_extracted_post_field_value', $values, $field );
				$values = apply_filters( 'cred_extracted_' . $field['type'] . '_field_type_value', $values, $field );
                $fields[$key] = $values;
            }
        }

        // custom parents (Types feature)
        foreach ($_fields['parents'] as $key => $field) {
            $field_label = $field['name'];

            // overwrite parent setting by url, even though no fields might b e set
	        if (
		        ! array_key_exists( $key, $_current_form_fields )
		        && array_key_exists( 'parent_' . $field['data']['post_type'] . '_id', $_GET )
		        && is_numeric( $_GET[ 'parent_' . $field['data']['post_type'] . '_id' ] )
	        ) {
                $fieldsInfo[$key] = array('save_single' => false);
                $fields[$key] = intval($_GET['parent_' . $field['data']['post_type'] . '_id']);
                continue;
            }

            // if this field was not rendered in this specific form, bypass it
	        if ( ! array_key_exists( $key, $_current_form_fields ) ) {
		        continue;
	        }

	        if (
		        array_key_exists( $key, $method )
		        && intval( $method[ $key ] ) >= -1
	        ) {
                $fieldsInfo[$key] = array('save_single' => false);
				$value = intval( $method[ $key ] );
				$value = apply_filters( 'cred_extracted_field_value', $value, $field );
				$value = apply_filters( 'cred_extracted_parent_field_value', $value, $field );
                $fields[$key] = $value;
            }
        }

        // taxonomies
        foreach ($_fields['taxonomies'] as $key => $field) {
            // if this field was not rendered in this specific form, bypass it
	        if ( ! array_key_exists( $key, $_current_form_fields ) ) {
		        continue;
	        }

	        if (
		        array_key_exists( $key, $method )
		        || ( $field['hierarchical'] && isset( $method[ $key . '_hierarchy' ] ) )
	        ) {
                if ($field['hierarchical'] /* && is_array($method[$key]) */) {
                    $values = isset($method[$key]) ? $method[$key] : array();
                    if (isset($method[$key . '_hierarchy'])) {
                        $add_new = array();
                        preg_match_all("/\{([^\{\}]+?),([^\{\}]+?)\}/", $method[$key . '_hierarchy'], $tmp_a_n);
                        for ($ii = 0; $ii < count($tmp_a_n[1]); $ii++) {
                            $add_new[] = array(
                                'parent' => $tmp_a_n[1][$ii],
                                'term' => $tmp_a_n[2][$ii]
                            );
                        }
                        unset($tmp_a_n);
                    } else {
                        $add_new = array();
                    }

                    $new_numeric_values = array();
                    foreach ($add_new as $one) {
                        if (is_numeric($one['term'])) {
                            $new_numeric_values[] = $one['term'];
                        }
                    }

                    $taxonomies['hierarchical'][] = array(
                        'name' => $key,
                        'terms' => $values,
                        'add_new' => $add_new,
                        'remove' => ''
                    );
                    // track form data for notification mail
                    if ($track) {

                        $result = array();
                        $result = cred__parent_sort($field['all'], $result, 0, 0);

                        $tmp_data = array();
                        foreach ($result as $tmp_tax) {
                            //if (in_array($tmp_tax['term_taxonomy_id'],$values))
	                        if ( in_array( $tmp_tax['term_id'], $values ) ) {
		                        $tmp_data[] = str_repeat( "- ", $tmp_tax['depth'] ) . $tmp_tax['name'];
	                        }
                        }
                        // add also new terms created
                        foreach ($values as $val) {
	                        if (
		                        ( is_string( $val ) && ! is_numeric( $val ) )
		                        || in_array( $val, $new_numeric_values )
	                        ) {
                                $tmp_data[] = $val;
                            }
                        }
                        unset($new_numeric_values);

                        $this->trackData(array($field['label'] => $tmp_data));
                        unset($tmp_data);
                    }
                } elseif (!$field['hierarchical']) {
                    $values = $method[$key];

                    // find which to add and which to remove
                    $tax_add = $values;
                    //TODO: use remove ??
                    $tax_remove = "";

                    // allow white space in tax terms
                    $taxonomies['flat'][] = array('name' => $key, 'add' => $tax_add, 'remove' => $tax_remove);

                    // track form data for notification mail
	                if ( $track ) {
		                $this->trackData( array( $field['label'] => array( 'added' => $tax_add, 'removed' => $tax_remove ) ) );
	                }
                }
            }
        }
        return array($fields, $fieldsInfo, $taxonomies, $files, $removed_fields, $error_files);
    }

	/**
	 * @param int $user_id
	 * @param bool $track
	 *
	 * @return array
	 */
    public function CRED_extractCustomUserFields($user_id, $track = false) {
        global $user_ID;
        // reference to the form submission method
        $method = & $_POST;

        $error_files = array();

        $form = $this->get_form_data();

        $form_id = $form->getForm()->ID;
        $form_fields = $form->getFields();
        $form_type = $form_fields['form_settings']->form['type'];
        $post_type = $form_fields['form_settings']->post['post_type'];

        $_fields = CRED_StaticClass::$out['fields'];
        $_form_fields = CRED_StaticClass::$out['form_fields'];
        $_current_form_fields = CRED_StaticClass::$out['current_form_fields'];
        $_form_fields_info = CRED_StaticClass::$out['form_fields_info'];
        $zebraForm = $this->_formBuilder->_cred_form_rendering;

        // custom fields
        $fields = array();
        $removed_fields = array();
        $fieldsInfo = array();
        // files, require extra care to upload correctly
        $files = array();

        if (count($error_files) > 0) {
            // Bail out early if there are errors when uploading files
            return array($fields, $fieldsInfo, $files, $removed_fields, $error_files);
        }

        foreach ($_fields['post_fields'] as $key => $field) {
			$field_label = $field['name'];
			$translated_field_label = apply_filters( 'cred_translate_content', $field['name'], $field['slug'] . '-label' );
			// I need to save them because the way it is implemented would need a full refactor to make it work with WPML
			$this->label_translations[ $translated_field_label ] = $field['name'];
            $done_data = false;

            // use the key as was rendered (with potential prefix)
            $key11 = $key;
	        if ( isset( $field['plugin_type_prefix'] ) ) {
		        $key = $field['plugin_type_prefix'] . $key;
	        }

            // if this field was not rendered in this specific form, bypass it
	        if ( ! array_key_exists( $key11, $_current_form_fields ) ) {
		        continue;
	        }

            $fieldsInfo[$key] = array('save_single' => false);

			if ( 'audio' == $field['type']
				|| 'video' == $field['type']
				|| 'file' == $field['type']
				|| 'image' == $field['type']
			) {
				if ( ! array_key_exists( $key, $method ) ) {
					// remove the fields
					$removed_fields[] = $key;
					unset($fieldsInfo[$key]);
				} else {
					$fields[$key] = $method[$key];
				}
			}

	        if (
		        'checkboxes' == $field['type']
		        && isset( $field['data']['save_empty'] )
		        && 'yes' == $field['data']['save_empty']
		        && ! array_key_exists( $key, $method )
	        ) {
                $values = array();
                foreach ($field['data']['options'] as $optionkey => $optiondata) {
                    $values[$optionkey] = '0';
                }
                // let model serialize once, fix Types-CRED mapping i ssue with chec kboxes
                $fieldsInfo[$key]['save_single'] = true;
                $fields[$key] = $values;
	        } elseif (
		        'checkboxes' == $field['type']
		        && ( ! isset( $field['data']['save_empty'] )
			        || 'yes' != $field['data']['save_empty'] )
		        && ! array_key_exists( $key, $method )
	        ) {
						// remove the fields
						$removed_fields[] = $key;
						unset( $fieldsInfo[ $key ] );
	        } elseif (
		        'checkbox' == $field['type']
		        && isset( $field['data']['save_empty'] )
		        && 'yes' == $field['data']['save_empty']
		        && ! array_key_exists( $key, $method )
	        ) {
                $fields[$key] = '0';
	        } elseif (
		        'checkbox' == $field['type']
		        && ( ! isset( $field['data']['save_empty'] )
			        || 'yes' != $field['data']['save_empty'] )
		        && ! array_key_exists( $key, $method )
	        ) {
							// remove the fields
							$removed_fields[] = $key;
							unset( $fieldsInfo[ $key ] );
            } elseif (array_key_exists($key, $method)) {
                // normalize repetitive values out of sequence
		        if ( $_form_fields_info[ $key11 ]['repetitive']
			        || 'multiselect' == $_form_fields_info[ $key11 ]['type']
		        ) {
                    if (is_array($method[$key])) {
						$original_values = $method[ $key ];
						ksort( $original_values );
                        $values = array_values( $original_values );
                    } else {
                        $aux_value_array = array($method[$key]);
                        $values = array_values($aux_value_array);
                    }
                } else {
                    $values = $method[$key];
                }

		        if ( 'audio' == $field['type']
			        || 'video' == $field['type']
			        || 'file' == $field['type']
			        || 'image' == $field['type']
		        ) {
                    //TODO check this
			        if ( isset( $_FILES )
				        && ! empty( $_FILES[ $key ] )
			        ) {
                        $files[$key] = $zebraForm->getFileData($key, $_FILES[$key]);
                        $files[$key]['name_orig'] = $key11;
                        $files[$key]['label'] = $field['name'];
                        $files[$key]['repetitive'] = $_form_fields_info[$key11]['repetitive'];
                    }
		        } elseif ( 'textarea' == $field['type']
			        || 'wysiwyg' == $field['type']
		        ) {
                    // stripslashes for textarea, wysiwyg fields
			        if ( is_array( $values ) ) {
				        $values = array_map( 'stripslashes', $values );
			        } else {
				        $values = stripslashes( $values );
			        }
                } elseif ( 'textfield' == $field['type']
			        || 'text' == $field['type']
		        ) {
                    // stripslashes for text fields
			        if ( is_array( $values ) ) {
				        $values = array_map( 'stripslashes', $values );
			        } else {
				        $values = stripslashes( $values );
			        }
                }

                // track form data for notification mail
                if ($track) {
                    $tmp_data = null;
                    if ('checkbox' == $field['type']) {
	                    if (
                            ! isset( $field['data']['display'] )
                            || 'db' == $field['data']['display']
                        ) {
		                    $tmp_data = $values;
	                    } else {
                            $tmp_data = isset( $field['data']['display_value_selected'] )
                                ? $field['data']['display_value_selected']
                                : null;
	                    }
                    }
                    elseif ('radio' == $field['type'] || 'select' == $field['type']) {
                        //$tmp_data = $field['data']['options'][$values]['title'];
                        foreach ($field['data']['options'] as $_key => $_val) {
                            if (isset($_val['value']) && $_val['value'] == $values) {
                                $tmp_data = apply_filters( 'cred_translate_content', $_val['title'], $_key );
                            }
                        }
                    } elseif ('checkboxes' == $field['type'] || 'multiselect' == $field['type']) {
                        $tmp_data = array();
	                    foreach ( $values as $tmp_val ) {
		                    $tmp_data[] = $field['data']['options'][ $tmp_val ]['title'];
	                    }
                        //$tmp_data=implode(', ',$tmp_data);
                        unset($tmp_val);
                    }
                    if (isset($tmp_data)) {
                        $this->trackData( array( $translated_field_label => $tmp_data ) );
                        $done_data = true;
                    }
                }

		        if ( 'checkboxes' == $field['type']
			        || 'multiselect' == $field['type']
		        ) {
                    if (!is_array($values)) {
                        $values = array($values);
                    }

                    $result = array();
                    foreach ($field['data']['options'] as $optionkey => $optiondata) {
                        if (in_array($optionkey, $values)) {
                            if (array_key_exists('set_value', $optiondata) && isset($optiondata['set_value'])) {
                                $result[$optionkey] = array($optiondata['set_value']);
                            } elseif ('multiselect' == $field['type']) {
                                $result[$optionkey] = array($optionkey);
                            }
                        }
                    }

                    $values = $result;
                    $fieldsInfo[$key]['save_single'] = true;
		        } elseif ( 'radio' == $field['type']
			        || 'select' == $field['type']
		        ) {
                } elseif ('date' == $field['type']) {
                    // Modified by Srdjan
                    /*
                     * Single/repetitive values for Date are not set right,
                     * because CRED used Date as string - not array
                     *
                     * NOTE: There is no general method in CRED to check if repetitive?
                     * Types have types_is_repetitive() function.
                     * If it's types fiels - repetitive flag is in
                     * $field['data']['repetitive']
                     */
                    $_values = empty($_form_fields_info[$key11]['repetitive']) ? array($values) : $values;
                    $new_values = array();
                    foreach ($_values as $values) {
                        if (!empty($values['datepicker'])) {
                            $date_format = $zebraForm->getDateFormat();

	                        if ( ! is_array( $values ) ) {
		                        $tmp = array( $values );
	                        } else {
		                        $tmp = $values;
	                        }

                            // track form data for notification mail
                            if ($track) {
                                $this->trackData( array( $translated_field_label => $tmp ) );
                                $done_data = true;
                            }

                            $timestamp = $tmp['datepicker'];

	                        if ( ! isset( $tmp['hour'] ) ) {
		                        $tmp['hour'] = "00";
	                        }
	                        if ( ! isset( $tmp['minute'] ) ) {
		                        $tmp['minute'] = "00";
	                        }

	                        if ( $tmp['hour'] < 10 && strlen( $tmp['hour'] ) == 1 ) {
		                        $tmp['hour'] = "0{$tmp['hour']}";
	                        }
	                        if ( $tmp['minute'] < 10 && strlen( $tmp['minute'] ) == 1 ) {
		                        $tmp['minute'] = "0{$tmp['minute']}";
	                        }

                            $timestamp_date = adodb_date('dmY', $timestamp);
                            $date = adodb_mktime(intval($tmp['hour']), intval($tmp['minute']), 0, substr($timestamp_date, 2, 2), substr($timestamp_date, 0, 2), substr($timestamp_date, 4, 4));
                            $timestamp = $date;

	                        if ( isset( $tmp['hour'] ) ) {
		                        unset( $tmp['hour'] );
	                        }
	                        if ( isset( $tmp['minute'] ) ) {
		                        unset( $tmp['minute'] );
	                        }

                            $new_values[] = $timestamp;
                        } else {
	                        if ( isset( $values['hour'] ) ) {
		                        unset( $values['hour'] );
	                        }
	                        if ( isset( $values['minute'] ) ) {
		                        unset( $values['minute'] );
	                        }
                        }
                    }
                    $values = $new_values;
                    unset($new_values);
                    // Modified by Srdjan END
                }

                elseif ('skype' == $field['type']) {
                    //TODO: check this could be no need array($values)
                    $values = isset($_form_fields_info[$key11]['repetitive']) && $_form_fields_info[$key11]['repetitive'] == 1 ? $values : array($values);

                    if ($track) {
                        $this->trackData( array( $translated_field_label => $values ) );
                        $done_data = true;
                    }
                }

		        if (
			        $track
			        && ! $done_data
			        && 'audio' != $field['type']
			        && 'video' != $field['type']
			        && 'file' != $field['type']
			        && 'image' != $field['type']
		        ) {
                    $this->trackData( array( $translated_field_label => $values ) );
                }

				$values = apply_filters( 'cred_extracted_field_value', $values, $field );
				$values = apply_filters( 'cred_extracted_user_field_value', $values, $field );
				$values = apply_filters( 'cred_extracted_' . $field['type'] . '_field_type_value', $values, $field );
                $fields[$key] = $values;
            }
        }

        return array($fields, $fieldsInfo, $files, $removed_fields, $error_files);
    }

	/**
	 * @param int $post_id
	 * @param bool $track
	 *
	 * @return array
	 * @deprecated Not used anywhere, seems to be the old previous version of CRED_extractCustomFields WTF!
	 */
    public function extractCustomFields($post_id, $track = false) {
        global $user_ID;
        // reference to the form submission method
        $method = & $_POST;

        // get refs here
        $globals = CRED_StaticClass::$_staticGlobal;
        $form = $this->get_form_data();

        $form_id = $form->getForm()->ID;
        $form_fields = $form->getFields();
        $form_type = $form_fields['form_settings']->form['type'];
        $post_type = $form_fields['form_settings']->post['post_type'];

        $_fields = CRED_StaticClass::$out['fields'];
        $_form_fields = CRED_StaticClass::$out['form_fields'];
        $_form_fields_info = CRED_StaticClass::$out['form_fields_info'];
        $zebraForm = $this->_formBuilder->_cred_form_rendering;

        // custom fields
        $fields = array();
        $removed_fields = array();
        // taxonomies
        $taxonomies = array('flat' => array(), 'hierarchical' => array());
        $fieldsInfo = array();
        // files, require extra care to upload correctly
        $files = array();
        foreach ($_fields['post_fields'] as $key => $field) {
            $field_label = $field['name'];
            $done_data = false;

            // use the key as was rendered (with potential prefix)
            $key11 = $key;
	        if ( isset( $field['plugin_type_pr efix'] ) ) {
		        $key = $field['plugin_type_prefix'] . $key;
	        }

	        // if this field was not rendered in this specific form, bypass it
	        if ( ! array_key_exists( $key11, $_form_fields ) ) {
		        continue;
	        }

	        // if this field was discarded due to some conditional logic, bypass it
	        if ( isset( $zebraForm->controls ) && $zebraForm->controls[ $_form_fields[ $key11 ][0] ]->isDiscarded() ) {
		        continue;
	        }

            $fieldsInfo[$key] = array('save_single' => false);

	        if (
	        ( 'audio' == $field['type']
		        || 'video' == $field['type']
		        || 'file' == $field['type']
		        || 'image' == $field['type'] )
	        ) {
		        if (
		        ! array_key_exists( $key, $method )
		        ) {
                    // remove the fields
                    $removed_fields[] = $key;
                    unset($fieldsInfo[$key]);
                } else {
                    $fields[$key] = $method[$key];
                }
            }

	        if (
		        'checkboxes' == $field['type']
		        && isset( $field['data']['save_empty'] )
		        && 'yes' == $field['data']['save_empty']
		        && ! array_key_exists( $key, $method )
	        ) {
                $values = array();
                foreach ($field['data']['options'] as $optionkey => $optiondata) {
                    $values[$optionkey] = '0';
                }

                // let model serialize once, fix Types-CRED mapping issue with checkboxes
                $fieldsInfo[$key]['save_single'] = true;
                $fields[$key] = $values;
	        } elseif (
		        'checkboxes' == $field['type']
		        && ( ! isset( $field['data']['save_empty'] )
			        || 'yes' != $field['data']['save_empty'] )
		        && ! array_key_exists( $key, $method )
	        ) {
                // remove the fields
                $removed_fields[] = $key;
                unset($fieldsInfo[$key]);
	        } elseif (
		        'checkbox' == $field['type']
		        && isset( $field['data']['save_empty'] )
		        && 'yes' == $field['data']['save_empty']
		        && ! array_key_exists( $key, $method )
	        ) {
                $fields[$key] = '0';
	        } elseif (
		        'checkbox' == $field['type']
		        && ( ! isset( $field['data']['save_empty'] )
			        || 'yes' != $field['data']['save_empty'] )
		        && ! array_key_exists( $key, $method )
	        ) {
                // remove the fields
                $removed_fields[] = $key;
                unset($fieldsInfo[$key]);
            } elseif (array_key_exists($key, $method)) {
                // normalize repetitive values out  of sequence
                // NOTE this seems deprecated as we are using the method above... why is this still here?
                if ($_form_fields_info[$key11]['repetitive']) {
                    if (is_array($method[$key])) {
                        $values = array_values($method[$key]);
                    } else {
                        $aux_value_array = array($method[$key]);
                        $values = array_values($aux_value_array);
                    }
                } else {
                    $values = $method[$key];
                }

		        if ( 'audio' == $field['type']
			        || 'video' == $field['type']
			        || 'file' == $field['type']
			        || 'image' == $field['type']
		        ) {
                    $files[$key] = $zebraForm->controls[$_form_fields[$key11][0]]->get_values();
                    $files[$key]['name_orig'] = $key11;
                    $files[$key]['label'] = $field['name'];
                    $files[$key]['repetitive'] = $_form_fields_info[$key11]['repetitive'];
		        } elseif ( 'textarea' == $field['type']
			        || 'wysiwyg' == $field['type']
		        ) {
                    // stripslashes for textarea, wysiwyg fields
			        if ( is_array( $values ) ) {
				        $values = array_map( 'stripslashes', $values );
			        } else {
				        $values = stripslashes( $values );
			        }
                } elseif ( 'textfield' == $field['type']
			        || 'text' == $field['type']
			        || 'date' == $field['type']
		        ) {
                    // stripslashes for text fields
			        if ( is_array( $values ) ) {
				        $values = array_map( 'stripslashes', $values );
			        } else {
				        $values = stripslashes( $values );
			        }
                }

                // track form data for notification mail
                if ($track) {

                    $tmp_data = null;
                    if ('checkbox' == $field['type']) {
	                    if ( 'db' == $field['data']['display'] ) {
		                    $tmp_data = $values;
	                    } else {
		                    $tmp_data = $field['data']['display_value_selected'];
	                    }
                    } elseif ( 'radio' == $field['type']
	                    || 'select' == $field['type']
                    ) {

                        $tmp_data = $field['data']['options'][$values]['title'];
                    } elseif ( 'checkboxes' == $field['type']
	                    || 'multiselect' == $field['type']
                    ) {
                        $tmp_data = array();
	                    foreach ( $values as $tmp_val ) {
		                    $tmp_data[] = $field['data']['options'][ $tmp_val ]['title'];
	                    }
                        //$tmp_data=implode(', ',$tmp_data);
                        unset($tmp_val);
                    }
                    if (isset($tmp_data)) {
                        $this->trackData(array($field_label => $tmp_data));
                        $done_data = true;
                    }
                }
		        if ( 'checkboxes' == $field['type']
			        || 'multiselect' == $field['type']
		        ) {
                    $result = array();
                    foreach ($field['data']['options'] as $optionkey => $optiondata) {
	                    if ( in_array( $optionkey, $values )
		                    && isset( $optiondata['set_value'] )
	                    ) {
		                    $result[ $optionkey ] = $optiondata['set_value'];
	                    }
                    }

                    $values = $result;
                    $fieldsInfo[$key]['save_single'] = true;
                } elseif ( 'radio' == $field['type']
			        || 'select' == $field['type']
		        ) {
                    $values = $field['data']['options'][$values]['value'];
                } elseif ('date' == $field['type']) {
                    $date_format = null;
			        if ( isset( $field['data'] ) && isset( $field['data']['validate'] ) ) {
				        $date_format = $field['data']['validate']['date']['format'];
			        }
			        if ( ! in_array( $date_format, CRED_StaticClass::$_supported_date_formats ) ) {
				        $date_format = 'F j, Y';
			        }
			        if ( ! is_array( $values ) ) {
				        $tmp = array(
					        $values,
				        );
			        } else {
				        $tmp = $values;
			        }

                    // track form data for notification mail
                    if ($track) {
                        $this->trackData(array($field_label => $tmp));
                        $done_data = true;
                    }

                    MyZebra_DateParser::setDateLocaleStrings($globals['LOCALES']['days'], $globals['LOCALES']['months']);
                    foreach ($tmp as $ii => $val) {
                        $val = MyZebra_DateParser::parseDate($val, $date_format);
	                    if ( false !== $val )  // succesfull
	                    {
		                    $val = $val->getNormalizedTimestamp();
	                    } else {
		                    continue;
	                    }

                        $tmp[$ii] = $val;
                    }

			        if ( ! is_array( $values ) ) {
				        $values = $tmp[0];
			        } else {
				        $values = $tmp;
			        }
                } elseif ( 'skype' == $field['type'] ) {
	                if (
		                array_key_exists( 'skypename', $values )
		                && array_key_exists( 'style', $values )
	                ) {
                        $new_values = array();
                        $values['skypename'] = (array) $values['skypename'];
                        $values['style'] = (array) $values['style'];
                        foreach ($values['skypename'] as $ii => $val) {
                            $new_values[] = array(
                                'skypename' => $values['skypename'][$ii],
                                'style' => $values['style'][$ii]
                            );
                        }
                        $values = $new_values;
                        unset($new_values);
                        if ($track) {
                            $this->trackData(array($field_label => $values));
                            $done_data = true;
                        }
                    }
                }
                // dont track file/image data now but after we upload them..
		        if (
			        $track
			        && ! $done_data
			        && 'file' != $field['type']
			        && 'image' != $field['type']
		        ) {
                    $this->trackData(array($field_label => $values));
                }
                $fields[$key] = $values;
            }
        }
        // custom parents (Types feature)
        foreach ($_fields['parents'] as $key => $field) {
            $field_label = $field['name'];

            // overwrite parent setting by url, even though no fields might be set
	        if (
		        ! array_key_exists( $key, $_form_fields )
		        && array_key_exists( 'parent_' . $field['data']['post_type'] . '_id', $_GET )
		        && is_numeric( $_GET[ 'parent_' . $field['data']['post_type'] . '_id' ] )
	        ) {
                $fieldsInfo[$key] = array('save_single' => false);
                $fields[$key] = intval($_GET['parent_' . $field['data']['post_type'] . '_id']);
                continue;
            }
	        // if this field was not rendered in this specific form, bypass it
	        if ( ! array_key_exists( $key, $_form_fields ) ) {
		        continue;
	        }

	        // if this field was discarded due to some conditional logic, bypass it
	        if ( $zebraForm->controls[ $_form_fields[ $key ][0] ]->isDiscarded() ) {
		        continue;
	        }

	        if (
		        array_key_exists( $key, $method )
		        && intval( $method[ $key ] ) >= -1
	        ) {
                $fieldsInfo[$key] = array('save_single' => false);
                $fields[$key] = intval($method[$key]);
            }
        }

        // taxonomies
        foreach ($_fields['taxonomies'] as $key => $field) {
            // if this field was not rendered in this specific form, bypass it
	        if ( ! array_key_exists( $key, $_form_fields ) ) {
		        continue;
	        }

	        if (
		        array_key_exists( $key, $method )
		        || ( $field['hierarchical']
			        && isset( $method[ $key . '_hierarchy' ] ) )
	        ) {
		        if ( $field['hierarchical'] ) {
                    $values = isset($method[$key]) ? $method[$key] : array();
			        if ( isset( $method[ $key . '_hierarchy' ] ) ) {
				        $add_new = array();
				        preg_match_all( "/\{([^\{\}]+?),([^\{\}]+?)\}/", $method[ $key . '_hierarchy' ], $tmp_a_n );
				        for ( $ii = 0; $ii < count( $tmp_a_n[1] ); $ii++ ) {
					        $add_new[] = array(
						        'parent' => $tmp_a_n[1][ $ii ],
						        'term' => $tmp_a_n[2][ $ii ],
					        );
				        }
				        unset( $tmp_a_n );
			        } else {
				        $add_new = array();
			        }

                    $taxonomies['hierarchical'][] = array(
                        'name' => $key,
                        'terms' => $values,
                        'add_new' => $add_new
                    );
                    // track form data for notification mail
                    if ($track) {
                        $tmp_data = array();
                        foreach ($field['all'] as $tmp_tax) {
                            //if (in_array($tmp_tax['term_taxonomy_id'],$values))
                            if (in_array($tmp_tax['term_id'], $values))
                                $tmp_data[] = $tmp_tax['name'];
                        }
                        // add also new terms created
                        foreach ($values as $val) {
	                        if ( is_string( $val ) && ! is_numeric( $val ) ) {
		                        $tmp_data[] = $val;
	                        }
                        }
                        $this->trackData(array($field['label'] => $tmp_data));
                        unset($tmp_data);
                    }
		        } elseif ( ! $field['hierarchical'] ) {
                    $values = $method[$key];

                    // find which to add and which to remove
                    $tax_add = $values;
                    $tax_remove = "";

                    // allow white space in tax terms
                    $taxonomies['flat'][] = array('name' => $key, 'add' => $tax_add, 'remove' => $tax_remove);

                    // track form data for notification mail
			        if ( $track ) {
				        $this->trackData( array( $field['label'] => array( 'added' => $tax_add, 'removed' => $tax_remove ) ) );
			        }
                }
            }
        }

        return array($fields, $fieldsInfo, $taxonomies, $files, $removed_fields);
    }

	/**
	 * @param int $post_id
	 * @param array $fields
	 * @param array $files
	 * @param array $extra_files
	 * @param bool $track
	 *
	 * @return bool
	 */
	public function CRED_uploadAttachments( $post_id, &$fields, &$files, &$extra_files, $track = false ) {
		// dependencies
		require_once( ABSPATH . '/wp-admin/includes/file.php' );

		$all_ok = true;
		$all_ok = $this->elaborate_featured_image_upload( $post_id, $fields, $extra_files, $all_ok, $track );
		$files = $this->get_transformed_files_in_cred_compatible_format( $files );
		$all_ok = $this->set_fields_by_files_elaboration( $fields, $files, $all_ok, $track );
		return $all_ok;
	}

	/**
     * @deprecated Probably using just CRED_uploadAttachments
     *
	 * @param $user_id
	 * @param $fields
	 * @param $files
	 * @param $extra_files
	 * @param bool $track
	 *
	 * @return bool
	 */
	public function CRED_userUploadAttachments( $user_id, &$fields, &$files, &$extra_files, $track = false ) {
		// dependencies
		require_once( ABSPATH . '/wp-admin/includes/file.php' );

		$all_ok = true;
		$files = $this->get_transformed_files_in_cred_compatible_format( $files );
		return $this->set_fields_by_files_elaboration( $fields, $files, $all_ok, $track );
	}

	/**
	 * @param $fields
	 * @param $files
	 * @param $track
	 *
	 * @return bool
	 */
	protected function set_fields_by_files_elaboration( &$fields, &$files, &$all_ok, $track ) {
		foreach ( $files as $file_key => $files_data ) {
			if ( (
			        isset( $files_data[ 'repetitive' ] )
					&& $files_data[ 'repetitive' ]
                )
				&& isset( $files_data[ 'elements' ] )
			) {
				if ( ! isset( $fields[ $file_key ] ) ) {
					$fields[ $file_key ] = array();
				} else {
					if ( is_array( $fields[ $file_key ] ) ) {
						$fields[ $file_key ] = array_filter( $fields[ $file_key ] );
					} else {
						$aux_value_array = array( $fields[ $file_key ] );
						$fields[ $file_key ] = array_filter( $aux_value_array );
					}
				}

				foreach ( $files_data[ 'elements' ] as $element_position => $element ) {
					$main_count = 0;
					foreach ( $element as $element_key => $element_data ) {
						// We aim to read the $element_data
						if ( ! is_array( $element_data ) ) {
							continue;
						}

						if ( ! isset( $element_data[ $file_key ] )
                            || ! is_array( $element_data[ $file_key ] )
                        ) {
							continue;
						}

						if ( $element_data[ $file_key ][ 'error' ] !== UPLOAD_ERR_OK ) {
							continue;
						}

						if ( $track ) {
							$tmp_data = array();
						}

						$file_data = $element_data[ $file_key ];

						$upload = wp_handle_upload( $file_data, array(
							'test_form' => false,
							'test_upload' => false,
							'mimes' => CRED_StaticClass::$_allowed_mime_types,
						) );
						if ( ! isset( $upload[ 'error' ] )
                            && isset( $upload[ 'file' ] )
                        ) {
							$files[ $file_key ][ 'elements' ][][ 'wp_upload' ] = $upload;
							// Try to replace the file name in the field values with the uploaded file URL.
							// Otherwise, just append the uploaded file URL to the field values.
							if (
								array_key_exists( $element_position, $fields[ $file_key ] )
								&& $fields[ $file_key ][ $element_position ] === $file_data[ 'name' ]
							) {
								$fields[ $file_key ][ $element_position ] = $upload[ 'url' ];
							} else {
								$fields[ $file_key ][] = $upload[ 'url' ];
							}
							if ( $track ) {
								$tmp_data[] = $upload[ 'url' ];
							}
							$fields = $this->removeFromArray( $fields, $file_key, $file_data[ 'name' ] );
						} else {
							$all_ok = false;
							$files[ $file_key ][ 'elements' ][ $main_count ][ 'upload_fail' ] = true;
							if ( $track ) {
								$tmp_data[] = $this->getLocalisedMessage( 'upload_failed' );
							}

							$files[ $file_key ][ 'elements' ][ $main_count ] = '';
							$files[ $file_key ][ 'elements' ][ $main_count ][ 'upload_fail' ] = true;
						}

						if ( $track ) {
							$this->trackData( array( $files[ $file_key ][ 'elements' ][ $main_count ][ 'label' ] => $tmp_data ) );

							unset( $tmp_data );
						}
						$main_count ++;
					}
				}
			} else {
				if ( ! isset( $files_data[ 'file_data' ][ $file_key ] )
					|| ! is_array( $files_data[ 'file_data' ][ $file_key ] )
				) {
					continue;
				}

				if ( $files_data[ 'file_data' ][ $file_key ][ 'error' ] !== UPLOAD_ERR_OK
					&& isset( $_POST[ $file_key ] )
				) {
					continue;
				}

				$file_data = $files_data[ 'file_data' ][ $file_key ];

				$upload = wp_handle_upload( $file_data, array(
					'test_form' => false,
					'test_upload' => false,
					'mimes' => CRED_StaticClass::$_allowed_mime_types,
				) );
				if ( ! isset( $upload[ 'error' ] )
					&& isset( $upload[ 'file' ] )
				) {
					$files[ $file_key ][ 'wp_upload' ] = $upload;
					$fields[ $file_key ] = $upload[ 'url' ];
					if ( $track ) {
						$tmp_data = $upload[ 'url' ];
					}
				} else {
					//Fix if there a File generic cred field not required
					$data_field = CRED_StaticClass::$out[ 'fields' ][ 'post_fields' ][ $file_key ];
					if ( isset( $data_field[ 'cred_generic' ] ) && $data_field[ 'cred_generic' ] == 1
						&& ( isset( $data_field[ 'data' ][ 'validate' ][ 'required' ][ 'active' ] )
							&& $data_field[ 'data' ][ 'validate' ][ 'required' ][ 'active' ] == 0 )
					) {
					} else {
						$all_ok = false;
						if ( $track ) {
							$tmp_data = $this->getLocalisedMessage( 'upload_failed' );
						}

						$fields[ $file_key ] = '';
						$files[ $file_key ][ 'upload_fail' ] = true;
					}
				}
				if ( $track ) {
					$this->trackData( array( $files[ $file_key ][ 'label' ] => $tmp_data ) );
					unset( $tmp_data );
				}
			}
		}

		return $all_ok;
	}

	/**
	 * @param $files
	 *
	 * @return mixed
	 */
	protected function get_transformed_files_in_cred_compatible_format( &$files ) {
		$support_array = array();
		$main_count = 0;
		foreach ( $files as $support_file_key => $support_file_data ) {
			if ( $support_file_data[ 'repetitive' ] ) {
				$file_count = 0;

				if ( ! isset( $support_array[ 'elements' ] ) ) {
					$support_array[ 'elements' ] = array();
				}

				foreach ( $support_file_data[ 'value' ] as $value ) {
					if ( ! isset( $support_array[ 'elements' ][ $file_count ] ) ) {
						$support_array[ 'elements' ][ $file_count ] = array();
					}
					$support_array[ 'elements' ][ $file_count ][ 'value' ] = $value;
					$file_count ++;
				}

				foreach ( $support_file_data[ 'file_data' ][ $support_file_key ] as $support_file_name => $values ) {
					$value_count = 0;
					foreach ( $values as $single_value ) {
						if ( ! isset( $support_array[ 'elements' ][ $value_count ][ 'filedata' ] ) ) {
							$support_array[ 'elements' ][ $value_count ][ 'filedata' ] = array();
						}
						if ( ! isset( $support_array[ 'elements' ][ $value_count ][ 'filedata' ][ $support_file_key ] ) ) {
							$support_array[ 'elements' ][ $value_count ][ 'filedata' ][ $support_file_key ] = array();
						}
						$support_array[ 'elements' ][ $value_count ][ 'filedata' ][ $support_file_key ][ $support_file_name ] = $single_value;
						$value_count ++;
					}
				}


				$sub_count = 0;
				foreach ( $support_file_data[ 'value' ] as $value ) {
					if ( ! isset( $support_array[ 'elements' ][ $sub_count ] ) ) {
						$support_array[ 'elements' ][ $sub_count ] = array();
					}
					$support_array[ 'elements' ][ $sub_count ][ 'file_upload' ] = $support_file_data[ 'file_upload' ];
					$support_array[ 'elements' ][ $sub_count ][ 'name_orig' ] = $support_file_data[ 'name_orig' ];
					$support_array[ 'elements' ][ $sub_count ][ 'label' ] = $support_file_data[ 'label' ];
					$sub_count ++;
				}

				$main_count ++;

				if ( ! isset( $support_array[ 'repetitive' ] ) ) {
					$support_array[ 'repetitive' ] = $support_file_data[ 'repetitive' ];
				}

				$files[ $support_file_key ] = $support_array;
			}
		}
		unset( $support_array );

		return $files;
	}

	/**
	 * @param $post_id
	 * @param $fields
	 * @param $extra_files
	 * @param $all_ok
	 * @param $track
	 *
	 * @return bool
	 */
	protected function elaborate_featured_image_upload( $post_id, &$fields, &$extra_files, &$all_ok, $track ) {
		$_current_form_fields = CRED_StaticClass::$out[ 'current_form_fields' ];

		$_featured_image_key = '_featured_image';
		$extra_files = array();

		if ( isset( $_POST[ $_featured_image_key ] ) ) {
			$this->trackData( array( __( 'Featured Image', 'wp-cred' ) => "<img src='" . $_POST[ $_featured_image_key ] . "'>" ) );
		}

		if (
			array_key_exists( $_featured_image_key, $_FILES )
			&& isset( $_FILES[ $_featured_image_key ][ 'name' ] )
		) {
			// Featured image is being uploaded on submit,
			// meaning that the form is not using the native media manager
			if (
				empty( $_FILES[ $_featured_image_key ]['name'] )
				&& is_int( $post_id )
				&& $post_id > 0
			) {
				$parsed_url = isset( $_POST[ $_featured_image_key ] ) ? parse_url( $_POST[ $_featured_image_key ] ) : null;
				$parsed_site_url = parse_url( site_url() );
				// The URL can be stored in a CDN, in that case it shouldn't be deleted.
				// Note that parse_url might fail on some local environments.
				if (
					$parsed_url
					&& $parsed_site_url
					&& toolset_getarr( $parsed_url, 'host' ) === toolset_getarr( $parsed_site_url, 'host' )
				) {
					// The feature image image field is being uploaded empty
					delete_post_meta( $post_id, '_thumbnail_id' );
				}
				// Maybe we are POSTing the existing one?
				$this->maybe_save_posted_feature_image( false );
				return $all_ok;
			}

			if (
				! array_key_exists( $_featured_image_key, $_current_form_fields )
				|| empty( $_FILES[ $_featured_image_key ]['name'] )
			) {
				// The featured image field is not part of the form
				// TODO This might be the first thing to check, maybe?
				// Even outside of the first if/else
				// Also, when the featured image is posted empty
				// in a form not linked to a post ID
				return $all_ok;
			}

			// The featured image is posted as a file input
			$upload = wp_handle_upload( $_FILES[ $_featured_image_key ], array(
				'test_form' => false,
				'test_upload' => false
			) );

			if (
				! isset( $upload['error'] )
				&& isset( $upload['file'] )
			) {
				$extra_files[ $_featured_image_key ]['wp_upload'] = $upload;
				if ( $track ) {
					$this->trackData( array( __( 'Featured Image', 'wp-cred' ) => $upload['url'] ) );
				}
			} else {
				$all_ok = false;
				if ( $track ) {
					$this->trackData( array( __( 'Featured Image', 'wp-cred' ) => $this->getLocalisedMessage( 'upload_failed' ) ) );
				}
				$fields[ $_featured_image_key ] = '';
				$extra_files[ $_featured_image_key ][ 'upload_fail' ] = true;
			}

			return $all_ok;
		}

		// For forms using the native media manager,
		// the featured image is just POSTed.
		$this->maybe_save_posted_feature_image();

		return $all_ok;
	}

	/**
	 * Maybe save the featured image from POST data.
	 *
	 * @param bool $force_delete
	 * @return void
	 */
	private function maybe_save_posted_feature_image( $force_delete = true ) {
		if (
			isset( $_POST['_featured_image'] )
			&& isset( $_POST['_cred_cred_prefix_post_id'] )
		) {
			// Featured image is posted as a regular text field,
			// so the form is using the native media manager
			$post_id = intval( $_POST[ '_cred_cred_prefix_post_id' ] );
			if ( $force_delete ) {
				delete_post_meta( $post_id, '_thumbnail_id' );
			}

			if ( empty( $_POST['_featured_image'] ) ) {
				return;
			}

			$featured_image_id = 0;

			global $wpdb;
			$feature_image_candidates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID from $wpdb->posts
					WHERE post_type = 'attachment'
					AND guid = %s
					LIMIT 1",
					toolset_getpost('_featured_image')
				)
			);

			if ( ! empty( $feature_image_candidates ) ) {
				foreach ( $feature_image_candidates as $featured_image ) {
					$featured_image_id = $featured_image->ID;
				}
				update_post_meta( $post_id, '_thumbnail_id', $featured_image_id );
				return;
			}

			// The guid might not be a reliable:
			// some attachments are loaded from external sources, or imported,
			// and their guid points to somewhere different fro the upload URL.
			// Check against the posted file URL in this case.
			$featured_image_id_candidate = attachment_url_to_postid( toolset_getpost('_featured_image') );
			if ( $featured_image_id_candidate > 0 ) {
				update_post_meta( $post_id, '_thumbnail_id', $featured_image_id_candidate );
				return;
			}
		}
	}


	/**
     * @deprecated since version 1.3.6.3
     *
	 * @param $result
	 * @param $fields
	 * @param $files
	 * @param $extra_files
	 */
    public function attachUploads($result, &$fields, &$files, &$extra_files) {
        // you must first include the image.php file
        // for the function wp_generate_attachment_metadata() to work
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        //CRED_Loader::loadThe('wp_generate_attachment_metadata');
        // get ref here
        $form = $this->get_form_data();

        foreach ($files as $fkey => $fdata) {
            if ($files[$fkey]['repetitive']) {
                foreach ($fdata['elements'] as $ii => $fdata2) {
                    if (array_key_exists('wp_upload', $fdata2)) {
                        $attachment = array(
                            'post_mime_type' => $fdata2['wp_upload']['type'],
                            'post_title' => basename($fdata2['wp_upload']['file']),
                            'post_content' => '',
                            'post_status' => 'inherit',
                            'post_parent' => $result,
                            'post_type' => 'attachment',
                            'guid' => $fdata2['wp_upload']['url'],
                        );
                        $attach_id = wp_insert_attachment($attachment, $fdata2['wp_upload']['file']);
                        $attach_data = wp_generate_attachment_metadata($attach_id, $fdata2['wp_upload']['file']);
                        wp_update_attachment_metadata($attach_id, $attach_data);
                        continue;
                    }
                    if (!isset($fdata2['file_data'][$fkey]) || !is_array($fdata2['file_data'][$fkey])) {
                        continue;
                    }
                    //if (!isset($files[$fkey][$ii]['upload_fail']) || !$files[$fkey][$ii]['upload_fail'])
                    if (!isset($fdata2['upload_fail']) || !$fdata2['upload_fail']) {
                        //$filetype   = wp_check_filetype(basename($files[$fkey][$ii]['wp_upload']['file']), null);
                        $filetype = wp_check_filetype(basename($fdata2['wp_upload']['file']), null);
                        //$title      = $files[$fkey][$ii]['file_data'][$fkey]['name'];
                        $title = $fdata2['file_data'][$fkey]['name'];
                        $ext = strrchr($title, '.');
                        $title = ($ext !== false) ? substr($title, 0, -strlen($ext)) : $title;
                        $attachment = array(
                            'post_mime_type' => $filetype['type'],
                            'post_title' => addslashes($title),
                            'post_content' => '',
                            'post_status' => 'inherit',
                            'post_parent' => $result,
                            'post_type' => 'attachment',
                            //'guid' => $files[$fkey][$ii]['wp_upload']['url']
                            'guid' => $fdata2['wp_upload']['url']
                        );
                        //$attach_id  = wp_insert_attachment($attachment, $files[$fkey][$ii]['wp_upload']['file']);
                        //$attach_data = wp_generate_attachment_metadata( $attach_id, $files[$fkey][$ii]['wp_upload']['file'] );
                        $attach_id = wp_insert_attachment($attachment, $fdata2['wp_upload']['file']);
                        $attach_data = wp_generate_attachment_metadata($attach_id, $fdata2['wp_upload']['file']);
                        wp_update_attachment_metadata($attach_id, $attach_data);
                    }
                }
            } else {
                if (
					! isset( $fdata['file_data'][ $fkey ] )
					|| ! is_array( $fdata['file_data'][ $fkey ] )
					|| ! array_key_exists( 'wp_upload', $files[ $fkey ] )
				)
                    continue;

                if (!isset($files[$fkey]['upload_fail']) || !$files[$fkey]['upload_fail']) {
                    $filetype = wp_check_filetype(basename($files[$fkey]['wp_upload']['file']), null);
                    $title = $files[$fkey]['file_data'][$fkey]['name'];
                    $ext = strrchr($title, '.');
                    $title = ($ext !== false) ? substr($title, 0, -strlen($ext)) :
                            $title;
                    $attachment = array(
                        'post_mime_type' => $filetype['type'],
                        'post_title' => addslashes($title),
                        'post_content' => '',
                        'post_status' => 'inherit',
                        'post_parent' => $result,
                        'post_type' => 'attachment',
                        'guid' => $files[$fkey]['wp_upload']['url']
                    );
                    $attach_id = wp_insert_attachment($attachment, $files[$fkey]['wp_upload']['file']);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $files[$fkey]['wp_upload']['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                }
            }
        }

        foreach ($extra_files as $fkey => $fdata) {
            if (!isset($extra_files[$fkey]['upload_fail']) || !$extra_files[$fkey]['upload_fail']) {
                $filetype = wp_check_filetype(basename($extra_files[$fkey]['wp_upload']['file']), null);
                $title = $_FILES[$fkey]['name'];
                $ext = strrchr($title, '.');
                $title = ($ext !== false) ? substr($title, 0, -strlen($ext)) : $title;
                $attachment = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title' => addslashes($title),
                    'post_content' => '',
                    'post_status' => 'inherit',
                    'post_parent' => $result,
                    'post_type' => 'attachment',
                    'guid' => $extra_files[$fkey]['wp_upload']['url']
                );
                $attach_id = wp_insert_attachment($attachment, $extra_files[$fkey]['wp_upload']['file']);
                $attach_data = wp_generate_attachment_metadata($attach_id, $extra_files[$fkey]['wp_upload']['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);

                if ($fkey == '_featured_image') {
                    // set current thumbnail
                    update_post_meta($result, '_thumbnail_id', $attach_id);
                }
            }
        }
    }

    public function setCookie($name, $data) {
        $result = false;
        if (!headers_sent()) {
            $result = setcookie($name, urlencode(serialize($data)));
        }
        return $result;
    }

    public function readCookie($name) {
        $data = false;
        if (isset($_COOKIE[$name])) {
            $data = maybe_unserialize(urldecode($_COOKIE[$name]));
        }
        return $data;
    }

    public function clearCookie($name) {
	    if ( isset( $_COOKIE[ $name ] ) ) {
		    unset( $_COOKIE[ $name ] );
	    }
	    if ( ! headers_sent() ) {
		    $result = setcookie( $name, ' ', time() - 5832000 );
	    }
    }

	/**
     * Tracking data to be used on notifications, probably on %%FORM_DATA%% placeholders
	 * @param $data
	 * @param bool $return
	 *
	 * @return string
	 */
    public function trackData($data, $return = false) {
        static $track = array();
        if ($return) {
            // format data for output
            $trackRet = $this->formatData($track);
            // reset track data
            $track = array();
            return $trackRet;
        }
        $track = array_merge($track, $data);
    }

	/**
     * Format the data to replace the %%FORM_DATA%% placeholders
     *
	 * @param $data
	 * @param int $level
	 * @param string $parent_key Key for parent fields like Checkboxes
	 *
	 * @return string
	 */
    public function formatData($data, $level = 0, $parent_key = null ) {
        // tabular output format ;)
        $keystyle = ' style="background:#676767;font-weight:bold;color:#e1e1e1"';
        $valuestyle = ' style="background:#ddd;font-weight:normal;color:#121212"';
        $output = '';
		$data = (array) $data;

		foreach ($data as $k => &$v) {
            $output.='<tr>';
	        if ( ! is_numeric( $k ) ) {
		        $output .= '<td' . $keystyle . '>' . $k . '</td><td' . $valuestyle . '>';
	        } else {
		        $output .= '<td colspan=2' . $valuestyle . '>';
	        }

	        if ( is_array( $v ) || is_object( $v ) ) {
		        $output .= $this->formatData( (array) $v, $level + 1, isset( $this->label_translations[ $k ] ) ? $this->label_translations[ $k ] : $k );
	        } else {
                $out = CRED_StaticClass::$out;

                //########### START # String Tra nslati on WPML ##################################################
                $new_v = cred_maybe_translate($k . " " . $v, $v, CRED_StaticClass::$_current_prefix . CRED_StaticClass::$_current_post_title . '-' . CRED_StaticClass::$_current_form_id);

                if ($v == $new_v) {
                    $field_id = "";

					$field_slug = '';
					$field_key = $parent_key ? $parent_key : $k;
					foreach ( [ 'post_fields', 'user_fields' ] as $form_type ) {
						if ( isset( $out['fields'][ $form_type ] ) ) {
							foreach ( $out['fields'][ $form_type ] as $id => $field ) {
								// It is a mess, I know, but I am forced to do this or refactor the whole class
								$field_name = isset( $this->label_translations[ $field['name'] ] ) ? $this->label_translations[ $field['name'] ] : $field['name'];
								if ( $field_name === $field_key ) {
									$field_slug = $id;
								}
							}

							if ( ! empty( $field_slug ) && ! $field_id && isset( $out['fields'][ $form_type ][ $field_slug ] ) ) {
								$field = $out['fields'][ $form_type ][ $field_slug ];
								if ( $field['type'] == 'select'
									|| $field['type'] == 'radio'
									|| $field['type'] == 'checkboxes'
								) {
									if ( isset( $field['data']['options'] ) ) {
										foreach ( $field['data']['options'] as $id => $values ) {
											if ( isset( $values['title'] ) && $values['title'] == $v ) {
												$field_id = $id;
												break;
											}
										}
									}
								}
							}
						}
					}

					if (!empty($field_id)) {
						$new_v = cred_maybe_translate('field ' . $field_id . ' option ' . $k . ' title', $v, 'plug in Types');
						$new_v = apply_filters( 'cred_translate_content', $new_v, $field_id );
					}
                }
                //########### END # String Translation WPML ##################################################

                $output.=$new_v;
			}

            $output.= '</td></tr>';
        }
	    if ( 0 == $level ) {
		    $output = '<table style="position:relative;width:100%;"><tbody>' . $output . '</tbody></table>';
	    } else {
		    $output = '<table><tbody>' . $output . '</tbody></table>';
	    }
        return $output;
		exit();
	}

	/**
	 * Check whether an array entry is empty or made of empty levels.
	 *
	 * @param string $needle
	 * @param array $haystack
	 * @return bool
	 */
	private function empty_deep( $needle, $haystack ) {
		if ( ! isset( $haystack[ $needle ] ) ) {
			return true;
		}

		if ( empty( $haystack[ $needle ] ) ) {
			return true;
		}

		if ( is_array( $haystack[ $needle ] ) ) {
			foreach ( $haystack[ $needle ] as $inner_entry ) {
				if ( ! empty( $inner_entry ) ) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
     * Get all form field values to be used in validation hooks
     *
	 * @return array
	 */
    public function get_form_field_values() {
        $fields = array();

        $files = array();
        foreach ( $_FILES as $name => $value ) {
			// Empty repeating media fields submitted without AJAX in file HTML fields do generate an empty entry in an array inside $_REQUEST.
			$files[ $name ] = $this->empty_deep( $name, $_REQUEST )
				? $value['name']
				: $_REQUEST[ $name ];
        }
        $reqs = array_merge( $_REQUEST, $files );

        $zebraForm = $this->_formBuilder->_cred_form_rendering;

        foreach ($zebraForm->form_properties['fields'] as $n => $field) {
            if ($field['type'] != 'messages') {
                $value = isset($reqs[$field['name']]) ? $reqs[$field['name']] : "";
                $fields[$field['name']] = array(
                    'value' => $value,
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'repetitive' => isset($field['data']['repetitive']) ? $field['data']['repetitive'] : false
                );
                //Fix https://icanloc alize. basecamphq.com/projects/7393061-toolset/todo_items/192856893/comments
                //Added file_data for validation
                if (isset($_FILES) && !empty($_FILES)) {
                    if (isset($_FILES[$field['name']])) {
                        $fields[$field['name']]['file_data'] = $_FILES[$field['name']];
                    }
                }
                //##############################################################################################
                if (isset($field['plugin_type']) && !empty($field['plugin_type'])) {
                    $fields[$field['name']]['plugin_type'] = $field['plugin_type'];
                }
                if (isset($field['data']['validate']) && !empty($field['data']['validate'])) {
                    $fields[$field['name']]['validation'] = $field['data']['validate'];
                }
            }
        }
        return $fields;
    }

	/**
	 * @param $array
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
    private function removeFromArray($array, $key, $value) {
        if (!array_key_exists($key, $array)) {
            return $array;
        }
        if (!count($array[$key])) {
            return $array;
        }
        $array[$key] = array_diff($array[$key], array($value));
        return $array;
    }
}
