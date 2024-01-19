<?php

/**
 * Abstract Class that contains common shared methods used by CRED_Forms_Model (Toolset Post Forms) and
 * CRED_User_Forms_Model (Toolset User Forms) model classes
 */
abstract class CRED_Abstract_Model
{
    protected $wpdb = null;
	protected $prefix = '_cred_';
	protected $post_type_name = '';
	protected $form_meta_fields = array('form_settings', 'wizard', 'post_expiration', 'notification', 'extra');

	/** @var array */
	protected $notification_data;

	protected $allowed_tags = null;

	const FORM_TYPE_NEW = 'new';
	const FORM_TYPE_EDIT = 'edit';
	const FORM_SETTINGS_POST_META = '_cred_form_settings';

    function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }

	abstract public function get_object_fields($object_field, $include_fields_only = null);

	/**
	 * @param object $form
	 * @param array $fields
	 * @param bool $from_xml_processor
	 * @param string $old_id
	 * @param string $old_title
	 *
	 * @return int|WP_Error
	 */
	public function saveForm($form, $fields = array(), $from_xml_processor = false, $old_id = "", $old_title = "") {
		global $user_ID;

		$new_post = array(
			'ID' => '',
			'post_title' => $form->post_title,
			'post_content' => $form->post_content,
			'post_status' => 'private',
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_date' => date( 'Y-m-d H:i:s' ),
			'post_author' => $user_ID,
			'post_type' => $this->post_type_name
		);
		$post_id = wp_insert_post( $new_post );

		if ( !empty( $old_id ) && !empty( $old_title ) ) {
			$new_post_content = str_replace( $old_title . "-" . $old_id, $form->post_title . "-" . $post_id, $form->post_content );
			$my_post = array(
				'ID' => $post_id,
				'post_content' => $new_post_content,
			);
			wp_update_post( $my_post );
		}

		$this->addFormCustomFields( $post_id, $fields );

		if ( !$from_xml_processor ) {
			$cfp = CRED_Loader::get( 'CLASS/Form_Translator' );
			$cfp->processAllForms( array($post_id) );
		}

		return ($post_id);
	}

	public function prepareDB() {
		$this->register_form_type();
	}

	/**
	 * @return array|mixed|object|void
	 */
	public function merge() {
		if ( func_num_args() < 1 ) {
			return;
		}

		$arrays = func_get_args();
		$merged = array_shift( $arrays );

		$is_target_object = false;
		if ( is_object( $merged ) ) {
			$is_target_object = true;
			$merged = (array) $merged;
		}

		foreach ( $arrays as $arr ) {
			$is_object = false;
			if ( is_object( $arr ) ) {
				$is_object = true;
				$arr = (array) $arr;
			}

			foreach ( $arr as $key => $val ) {
				if ( array_key_exists( $key, $merged ) && ( is_array( $val ) || is_object( $val ) ) ) {
					$merged[ $key ] = $this->merge( $merged[ $key ], $arr[ $key ] );
					if ( is_object( $val ) ) {
						$merged[ $key ] = (object) $merged[ $key ];
					}
				} else {
					$merged[ $key ] = $val;
				}
			}
		}
		if ( $is_target_object ) {
			$is_target_object = false;
			$merged = (object) $merged;
		}

		return $merged;
	}

	/**
	 * @param array $arr
	 * @param array $defaults
	 *
	 * @return mixed
	 */
	public function applyDefaults( $arr, $defaults = array() ) {
		if ( ! empty( $defaults ) ) {
			foreach ( $arr as $key => $item ) {
				$arr[ $key ] = $this->merge( $defaults, $arr[ $key ] );
			}
		}

		return $arr;
	}

	/**
	 * @param $first_array
	 * @param $flip_array
	 *
	 * @return array
	 */
	public function filterByKeys( $first_array, $flip_array ) {
		return array_intersect_key( (array) $first_array, array_flip( (array) $flip_array ) );
	}

	/**
	 * @param array|object $data
	 *
	 * @return array|mixed
	 */
	protected function esc_data( $data ) {
		if ( is_array( $data )
			|| is_object( $data ) ) {
			foreach ( $data as $key => $data_val ) {
				if ( is_object( $data ) ) {
					$data->$key = $this->esc_data( $data_val );
				} elseif ( is_array( $data ) ) {
					$data[ $key ] = $this->esc_data( $data_val );
				}
			}
		} else {
			$data = str_replace( array( '\r', '\n' ), array( "\r", "\n" ), cred_wrap_esc_sql( $data ) );
		}

		return $data;
	}

	/**
	 * @param array|object $data
	 *
	 * @return array|string
	 */
	protected function esc_meta_data( $data ) {
		//special escape for meta data to prevent serialize eliminate CRLF (\r\n)
		if ( is_array( $data ) || is_object( $data ) ) {
			foreach ( $data as $key => $data_val ) {
				if ( is_object( $data ) ) {
					$data->$key = $this->esc_meta_data( $data_val );
				} elseif ( is_array( $data ) ) {
					$data[ $key ] = $this->esc_meta_data( $data_val );
				}
			}
		} else {
			$data = cred_wrap_esc_sql( preg_replace( '/\r\n?|\n/', '%%CRED_NL%%', $data ) );
		}

		return $data;
	}

	/**
	 * @param array|object $data
	 *
	 * @return array|mixed|string
	 */
	protected function unesc_meta_data( $data ) {
		//reverse special escape for meta data to prevent serialize eliminate CRLF (\r\n)
		if ( is_array( $data )
			|| is_object( $data ) ) {
			foreach ( $data as $key => $data_val ) {
				if ( is_object( $data ) ) {
					$data->$key = $this->unesc_meta_data( $data_val );
				} elseif ( is_array( $data ) ) {
					$data[ $key ] = $this->unesc_meta_data( $data_val );
				}
			}
		} else {
			$data = preg_replace( '/%%CRED_NL%%/', "\r\n", $data );
			$data = stripslashes( $data );
		}

		return $data;
	}

	/**
	 * @param array $params
	 *
	 * @return array|bool|mixed|null|object
	 */
	public function getPostBy( $params ) {
		$run_query = false;
		$query = "SELECT p.* FROM {$this->wpdb->posts} AS p ";
		$where = " WHERE 1=1 ";
		$values_to_prepare = array();

        if ( isset( $params['meta'] ) ) {
			$run_query = true;
            $count = 0;
            foreach ( $params['meta'] as $mkey => $mval ) {
                $count++;
                $query .= ", {$this->wpdb->postmeta} AS pm{$count}";
                $where .= " AND ( p.ID = pm{$count}.post_id AND pm{$count}.meta_key = %s AND pm{$count}.meta_value = %s )";
				$values_to_prepare[] = $mkey;
				$values_to_prepare[] = $mval;
            }
        }

        if ( isset( $params['post'] ) ) {
			$run_query = true;
            foreach ( $params['post'] as $pkey => $pval ) {
                if ( in_array( $pkey, array( 'ID', 'post_title', 'post_status', 'post_type' ) ) ) {
                    $where .= " AND ( p.$pkey = %s )";
					$values_to_prepare[] = $pval;
                }
            }
        }

        if (
			$run_query
			&& ! empty( $values_to_prepare )
		) {
            $sql = $query . $where;
            return $this->wpdb->get_results(
				$this->wpdb->prepare(
					$sql,
					$values_to_prepare
				)
			);
        }

		return false;
	}

	/**
	 * @param string $with_quotes
	 * @param bool $with_prefix
	 *
	 * @return array
	 */
	protected function getFieldkeys($with_quotes = '', $with_prefix = true) {
		$prefix = ( $with_prefix ) ? $this->prefix : '';

		$keys = array();
		foreach ($this->form_meta_fields as $fkey) {
			$keys[] = $with_quotes . $prefix . $fkey . $with_quotes;
		}
		return $keys;
	}

	/**
	 * @param string $id_or_title
	 * @param array $include
	 *
	 * @return bool|object
	 */
	public function getForm( $id_or_title, $include = array() ) {
		$form = false;
		if ( is_string( $id_or_title )
			&& ! is_numeric( $id_or_title ) ) {
			$forms = get_posts(
				array(
					'post_type'              => $this->post_type_name,
					'title'                  => $id_or_title,
					'post_status'            => 'all',
					'numberposts'            => 1,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'orderby'                => 'post_date ID',
					'order'                  => 'ASC',
				)
			);
			if ( ! empty( $forms ) ) {
				$form = $forms[0];
			}
		} elseif ( is_numeric( $id_or_title ) ) {
			$form = get_post( intval( $id_or_title ) );
		}

		if ( $form ) {
			$id = $form->ID;
		} else {
			return false;
		}

		$formObj = new stdClass;
		$formObj->form = $form;
		$formObj->fields = $this->getFormCustomFields( $id, $include );

		return $formObj;
	}

	/**
	 * @param int $id
	 * @param string $field
	 *
	 * @return array
	 */
	public function getFormCustomField( $id, $field ) {
		$field_db = $this->prefix . $field;
		$field_value = get_post_meta( intval( $id ), $field_db, true );
		if ( false != $field_value
			&& ! empty( $field_value ) ) {
			$field_value = $this->unesc_meta_data( maybe_unserialize( $field_value ) );
		}
		// change format here
		$fields = $this->changeFormat( array( $field => $field_value ) );

		return $fields[ $field ];
	}

	/**
	 * @param int $page
	 * @param int $per_page
	 * @param string $order_by
	 * @param string $order
	 * @param string $src
	 *
	 * @return object
	 */
	public function getFormsForTable($page, $per_page, $order_by = 'post_title', $order = 'asc', $src = '') {
		$p = intval( $page );
		if ( $p <= 0 ) {
			$p = 1;
		}
		$pp = intval( $per_page );
		$limit = '';
		if ( $pp != - 1 && $pp <= 0 ) {
			$pp = 10;
		}
		if ( $pp != - 1 ) {
			$limit = 'LIMIT ' . ( $p - 1 ) * $pp . ',' . $pp;
		}

		if ( ! in_array( $order_by, array( 'post_title', 'post_date', 'post_modified' ) ) ) {
			$order_by = 'post_title';
		}

		$order = strtoupper( $order );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
			$order = 'ASC';
		}

		$values_to_prepare = array();
		$values_to_prepare[] = $this->prefix . "form_settings";
		$values_to_prepare[] = $this->post_type_name;

		$sql_src_add = '';
		if (
			isset( $src )
			&& ! empty( $src )
		) {
			$sql_src_add = " AND ( p.post_name LIKE %s || p.post_title LIKE %s ) ";
			$values_to_prepare[] = '%' . $src . '%';
			$values_to_prepare[] = '%' . $src . '%';
		}

		$sql = $this->wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_name, pm.meta_value as meta
			FROM {$this->wpdb->posts}  p, {$this->wpdb->postmeta} pm
			WHERE p.ID=pm.post_id
            AND pm.meta_key = %s
            AND p.post_type = %s
			{$sql_src_add}
            AND p.post_status='private'
			ORDER BY p.{$order_by} {$order} {$limit}",
			$values_to_prepare
		);

		$forms = $this->wpdb->get_results( $sql );
		foreach ( $forms as $key => $form ) {
			$fields = $this->changeFormat( array( 'form_settings' => maybe_unserialize( $forms[ $key ]->meta ) ) );
			$forms[ $key ]->meta = $fields['form_settings'];
		}
		return $forms;
	}

	/**
	 * @param string $default
	 *
	 * @return bool
	 */
	public function disable_richedit_for_cred_forms($default) {
		global $post;
		if ( $this->post_type_name == get_post_type( $post ) ) {
			return false;
		}
		return $default;
	}

	/**
	 * @param int $id
	 * @param array $include
	 *
	 * @return array
	 */
	public function getFormCustomFields( $id, $include = array() ) {
		$fields_raw = get_post_custom( intval( $id ) );
		$fields_raw = is_array( $fields_raw ) ? $fields_raw : array();
		$fields = array();
		$form_fields = array_merge( $include, $this->form_meta_fields );

		$prefix = '/^' . preg_quote( $this->prefix, '/' ) . '/';
		foreach ( $fields_raw as $key => $field_raw ) {
			$key = preg_replace( $prefix, '', $key );
			if ( in_array( $key, $form_fields ) ) {
				$fields[ $key ] = $this->unesc_meta_data( maybe_unserialize( $field_raw[0] ) );
			}
		}
		unset( $fields_raw );

		// change format here and provide defaults also
		$fields = $this->changeFormat( $fields );

		return $fields;
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function deleteForm( $id ) {
		return ! ( wp_delete_post( $id, true ) === false );
	}

	/**
	 * @param int $id
	 * @param array $fields
	 */
	public function addFormCustomFields( $id, $fields ) {
		if ( empty( $fields )
			|| ! is_array( $fields ) ) {
			return;
		}
		$fields = $this->esc_data( $fields );
		foreach ( $fields as $meta_key => $meta_value ) {
			if ( isset ( $meta_value->scaffold ) ) {
				$meta_value->scaffold = wp_slash( $meta_value->scaffold );
			}

			add_post_meta( $id, $this->prefix . $meta_key, wp_slash( $meta_value ), false );
		}
	}

	/**
	 * @param int $page
	 * @param int $per_page
	 * @param bool $with_fields
	 *
	 * @return array
	 */
	public function getForms($page = 0, $per_page = 10, $with_fields = false) {
		$args = array(
			'numberposts' => intval( $per_page ),
			'offset' => intval( $page ) * intval( $per_page ),
			'category' => 0,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'include' => array(),
			'exclude' => array(),
			'meta_key' => '_cred_form_settings', // prevent drafts
			'post_type' => $this->post_type_name,
			'post_status' => 'private',
			'suppress_filters' => true
		);
		$forms = get_posts( $args );

		return $forms;
	}

	/**
	 * @return array
	 */
	public function getAllForms() {
		$args = array(
			'numberposts' => -1,
			'category' => 0,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'include' => array(),
			'exclude' => array(),
			'meta_key' => '_cred_form_settings', // prevent drafts
			'post_type' => $this->post_type_name,
			'post_status' => 'private',
			'suppress_filters' => true
		);
		$forms = get_posts( $args );

		return $forms;
	}

	/**
	 * @param string $src
	 *
	 * @return int
	 */
	public function getFormsCount($src = '') {

		$values_to_prepare = array();
		$values_to_prepare[] = $this->prefix . "form_settings";
		$values_to_prepare[] = $this->post_type_name;

		$sql_src_add = '';
		if (
			isset( $src )
			&& !empty( $src )
		) {
			$sql_src_add = ' AND ( p.post_name like %s || p.post_title like %s ) ';
			$values_to_prepare[] = '%' . $src . '%';
			$values_to_prepare[] = '%' . $src . '%';
		}

		$sql = $this->wpdb->prepare(
			'SELECT count(p.ID)
			FROM ' . $this->wpdb->posts . ' as p, ' . $this->wpdb->postmeta . ' as pm
            WHERE p.ID = pm.post_id
            AND pm.meta_key = %s
            AND p.post_type = %s
            AND p.post_status="private"
			' . $sql_src_add . '
			ORDER BY p.post_date DESC',
			$values_to_prepare
		);

		$count = $this->wpdb->get_var( $sql );
		return intval( $count );
	}

	/**
	 * @param $ids
	 *
	 * @return object
	 */
	public function getFormsForExport($ids) {
		if ( 'all' != $ids ) {
			$ids = implode( ',', array_map( 'intval', $ids ) );
		}
		$meta_keys = implode( ',', $this->getFieldkeys( '"', true ) );

		// AND p.post_status="private"
		if ( 'all' != $ids ) {
			$form_query = $this->wpdb->prepare(
				'SELECT p.* FROM ' . $this->wpdb->posts . ' as p, ' . $this->wpdb->postmeta . ' as pm
				WHERE p.ID = pm.post_id
                AND pm.meta_key = %s
                AND p.post_type = %s
                AND p.post_status = "private"
                AND p.ID IN (' . $ids . ')
				ORDER BY p.post_date DESC',
				array( $this->prefix . 'form_settings', $this->post_type_name )
			);
		} else {
			$form_query = $this->wpdb->prepare(
				'SELECT p.* FROM ' . $this->wpdb->posts . ' as p, ' . $this->wpdb->postmeta . ' as pm
				WHERE p.ID = pm.post_id
                AND pm.meta_key = %s
                AND p.post_type = %s
                AND p.post_status = "private"
				ORDER BY p.post_date DESC',
				array( $this->prefix . 'form_settings', $this->post_type_name )
			);
		}

		if ( 'all' != $ids ) {
			$form_postmeta_query = $this->wpdb->prepare(
				'SELECT p.ID, pm.meta_key, pm.meta_value
				FROM ' . $this->wpdb->posts . ' AS p
				INNER JOIN ' . $this->wpdb->postmeta . ' AS pm
				ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND p.post_status = "private"
				AND p.ID IN (' . $ids . ')
				AND pm.meta_key IN (' . $meta_keys . ')',
				$this->post_type_name
			);
		} else {
			$form_postmeta_query = $this->wpdb->prepare(
				'SELECT p.ID, pm.meta_key, pm.meta_value
				FROM ' . $this->wpdb->posts . ' AS p
				INNER JOIN ' . $this->wpdb->postmeta . ' AS pm
				ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND p.post_status = "private"
				AND pm.meta_key IN (' . $meta_keys . ')',
				$this->post_type_name
			);
		}

		$forms = $this->wpdb->get_results( $form_query );
		$meta = $this->wpdb->get_results( $form_postmeta_query );
		$prefix = '/^' . preg_quote( $this->prefix, '/' ) . '/';
		foreach ( $forms as $key => $form ) {
			$forms[ $key ]->meta = array();
			$forms[ $key ]->media = $this->getFormAttachedMediaForExport( $form->ID );
			foreach ( $meta as $m ) {
				if ( $form->ID == $m->ID ) {
					$meta_key = preg_replace( $prefix, '', $m->meta_key );
					$forms[ $key ]->meta[ $meta_key ] = maybe_unserialize( $m->meta_value );
				}
			}
			// change format here
			$forms[ $key ]->meta = $this->changeFormat( $forms[ $key ]->meta );
		}
		unset( $meta );

		return $forms;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function getFormAttachedMediaForExport($id) {
		$att_args = array('post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => intval( $id ));
		$attachments = get_posts( $att_args );
		$media = array();

		if ( $attachments ) {
			foreach ( $attachments as $key => $attachment ) {
				// if media is image mime type
				if ( in_array( $attachment->post_mime_type, array('image/png', 'image/gif', 'image/jpg', 'image/jpeg') ) ) {
					$idata = base64_encode( file_get_contents( $attachment->guid ) );
					$ihash = sha1( $idata );
					$media[ $key ] = array(
						'ID' => $attachment->ID,
						'post_title' => $attachment->post_title,
						'post_content' => $attachment->post_content,
						'post_excerpt' => $attachment->post_excerpt,
						'post_status' => $attachment->post_status,
						'post_type' => $attachment->post_type,
						'post_mime_type' => $attachment->post_mime_type,
						'guid' => $attachment->guid,
						'alt' => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
						'image_data' => $idata,
						'image_hash' => $ihash,
						'base_name' => basename( $attachment->guid )
					);
				} else
					unset( $attachments[$key] );
			}
		}
		unset( $attachments );

		return $media;
	}

	/**
	 * @param object $form
	 * @param array $fields
	 *
	 * @return int|WP_Error
	 */
	public function updateForm($form, $fields = array()) {
		global $user_ID;

		$up_post = array(
			'ID' => $form->ID,
			'post_title' => $form->post_title,
			'post_content' => $form->post_content,
			'post_status' => 'private',
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'post_date' => date( 'Y-m-d H:i:s' ),
			'post_author' => $user_ID,
			'post_type' => $this->post_type_name
		);
		$post_id = wp_insert_post( $up_post );
		$this->updateFormCustomFields( $post_id, $fields );

		return ($post_id);
	}

	/**
	 * @param int $id
	 * @param array $fields
	 */
	public function updateFormCustomFields($id, $fields) {
		if ( empty( $fields )
			|| ! is_array( $fields ) ) {
			return;
		}
		$fields = $this->esc_meta_data( $fields );
		foreach ( $fields as $meta_key => $meta_value ) {
			// Why it was deleted before updated?
			//delete_post_meta( $id, $this->prefix . $meta_key );
			update_post_meta( $id, $this->prefix . $meta_key, $meta_value, false /* $unique */ );
		}
	}

	/**
	 * @param int $id
	 * @param string $field
	 * @param string $value
	 */
	public function updateFormCustomField($id, $field, $value) {
		// Why it was deleted before updated?
		//delete_post_meta( $id, $this->prefix . $field );
		update_post_meta( $id, $this->prefix . $field, $this->esc_meta_data( $value ), false );
		//added postmeta for installer
		if ( isset( $_REQUEST['action'] )
			&& $_REQUEST['action'] == 'editpost'
			&& isset( $_REQUEST['_wp_http_referer'] ) ) {
			update_post_meta( $id, '_toolset_edit_last', time(), get_post_meta( $id, '_toolset_edit_last', true ) );
		}
	}

	/**
	 * @param object $form_data
	 *
	 * @return int|WP_Error
	 */
	public function updateFormData($form_data) {
		$post_id = wp_update_post( $form_data );
		return ($post_id);
	}

	/**
	 * @param int $form_id
	 * @param string|null $cloned_form_title
	 *
	 * @return bool|int|WP_Error
	 */
	public function cloneForm($form_id, $cloned_form_title = null) {
		$form = $this->getForm( $form_id, array('commerce') );
		if ( $form ) {
			$old_title = $form->form->post_title;
			if ( $cloned_form_title == null
				|| empty( $cloned_form_title ) ) {
				$cloned_form_title = $form->form->post_title . ' Copy';
			}
			$form->form->post_title = sanitize_text_field( $cloned_form_title );
			$form->form->ID = '';
			return $this->saveForm( $form->form, $form->fields, false, $form_id, $old_title );
		}

		return false;
	}

	/**
	 * Get the list of allowed HTML tags, based on the allowed HTL tags for posts,
	 * maybe modified by our local settings.
	 *
	 * @return array
	 * @since 2.5.1
	 */
	protected function get_allowed_html_tags() {
		if ( null !== $this->allowed_tags ) {
			return $this->allowed_tags;
		}

        $__allowed_tags = wp_kses_allowed_html( 'post' );
        $settings_model = CRED_Loader::get( 'MODEL/Settings' );
        $settings = $settings_model->getSettings();
        $allowed_tags = isset( $settings['allowed_tags'] ) ? $settings['allowed_tags'] : $__allowed_tags;
        foreach ( $__allowed_tags as $key => $value ) {
            if ( ! isset( $allowed_tags[ $key ] ) ) {
                unset( $__allowed_tags[ $key ] );
            }
		}

		$this->allowed_tags = $__allowed_tags;
        return $__allowed_tags;
    }

	/**
	 * Clean values before getting them saved.
	 * - Make HTML safe.
	 *
	 * @param string $meta_value
	 * @return string
	 * @since 2.5.2
	 */
	protected function clean_value_before_saving( $meta_value ) {
		// In WP 5.3.1+ wp_kses only accepts strings
		if ( ! is_string( $meta_value ) ) {
			return $meta_value;
		}
		$allowed_tags = $this->get_allowed_html_tags();
		$allowed_protocols = array( 'http', 'https', 'mailto', 'tel' );
		$meta_value = wp_kses( $meta_value, $allowed_tags, $allowed_protocols );
		return $meta_value;
	}

	/**
	 * Clean meta values before getting them saved.
	 * - Sanitize double quotes.
	 * - Adjust ampersands.
	 * - Make HTML safe.
	 *
	 * @param string $meta_value
	 * @return string
	 * @since 2.5.1
	 */
	protected function clean_meta_value_before_saving( $meta_value ) {
		$meta_value = str_replace( '\\\\"', '"', $meta_value );
		$meta_value = $this->clean_value_before_saving( $meta_value );
		$meta_value = str_replace( "&amp;", "&", $meta_value );

		return $meta_value;
	}


	/**
	 * Gets the type of the form: edit or new
	 *
	 * Not to be confused with get_form_type that returns user or post
	 *
	 * @param int $form_id Form ID.
	 * @return string
	 */
	public function get_type( $form_id ) {
		if ( ! isset( $this->form_settings[ $form_id ] ) ) {
			$this->form_settings[ $form_id ] = get_post_meta( $form_id, self::FORM_SETTINGS_POST_META, true );
		}
		if ( ! isset( $this->form_settings[ $form_id ]->form['type'] ) ) {
			return self::FORM_TYPE_NEW;
		}
		return $this->form_settings[ $form_id ]->form['type'];
	}

	/**
	 * Returns if it is a editing form
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	public function is_editing_form( $form_id ) {
		return $this->get_type( $form_id ) === self::FORM_TYPE_EDIT;
	}

	/**
	 * Returns if it is a creating form
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	public function is_creating_form( $form_id ) {
		return $this->get_type( $form_id ) === self::FORM_TYPE_NEW;
	}
}
