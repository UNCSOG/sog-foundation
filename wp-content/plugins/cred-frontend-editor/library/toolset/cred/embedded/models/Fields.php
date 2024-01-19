<?php

/**
 * Cred fields model
 * (get custom fields for post types)
 * 
 * @todo Cleanup this class and keep the relevant methods in a proper model
 */
class CRED_Fields_Model extends CRED_Fields_Abstract_Model implements CRED_Singleton {

	/** @var array  Is referred to native post fields */
	private static $basic_post_fields;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * @return mixed
	 */
	public static function get_basic_post_fields() {
		return self::$basic_post_fields;
	}

	/**
	 * @param string $text
	 * @param null $post_type
	 * @param int $limit
	 *
	 * @return mixed
	 *
	 * @deprecated since 1.9.4      use CRED_Field_Utils::get_instance()->get_potential_posts()
	 */
	public function suggestPostsByTitle( $text, $post_type = null, $limit = 20 ) {
        $post_status = "('publish','private')";
        $not_in_post_types = "('view','view-template','attachment','revision','" . CRED_FORMS_CUSTOM_POST_NAME . "')";
        $text = '%' . cred_wrap_esc_like( $text ) . '%';

		$values_to_prepare = array();

        $sql = "SELECT distinct ID, post_title FROM {$this->wpdb->posts} 
			WHERE post_title LIKE %s 
			AND post_status IN $post_status ";
		$values_to_prepare[] = $text;

        if ( $post_type !== null ) {
            if (is_array($post_type)) {
                $post_type_str = "";
                foreach ($post_type as $pt) {
                    $post_type_str .= "'$pt',";
                }
                $post_type_str = rtrim($post_type_str, ',');
                $sql .= " AND post_type in ($post_type_str)";
            } else {
	            $sql .= " AND post_type = %s";
				$values_to_prepare[] = $post_type;
            }
        }

        $sql .= " ORDER BY ID DESC ";

        $limit = intval($limit);
	    if ( $limit > 0 ) {
		    $sql .= " LIMIT 0, $limit";
	    }

        $results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				$sql,
				$values_to_prepare
			)
		);

        return $results;
    }

	/**
	 * @param array $custom_exclude
	 *
	 * @return array
	 */
    public function getPostTypes($custom_exclude = array()) {
        $exclude = array('revision', 'attachment', 'nav_menu_item');
	    if ( ! empty( $custom_exclude ) ) {
		    $exclude = array_merge( $exclude, $custom_exclude );
	    }

        $post_types = get_post_types(array('public' => true, 'publicly_queryable' => true, 'show_ui' => true), 'names');
        $post_types = array_merge($post_types, get_post_types(array('public' => true, '_builtin' => true,), 'names', 'and'));
        $post_types = array_diff($post_types, $exclude);
        sort($post_types, SORT_STRING);
        $returned_post_types = array();
        foreach ($post_types as $pt) {
            $pto = get_post_type_object($pt);
            $returned_post_types[] = array('type' => $pt, 'name' => $pto->labels->name);
        }
        unset($post_types);
        return $returned_post_types;
    }

	/**
	 * @return array
	 */
	public function getPostTypesWithoutTypes() {
		$wpcf_custom_types = get_option( 'wpcf-custom-types', false );
		if ( $wpcf_custom_types ) {
			return $this->getPostTypes( array_keys( $wpcf_custom_types ) );
		} else {
			return $this->getPostTypes();
		}
	}

	/**
	 * @param string $post_type
	 * @param array $exclude_fields
	 * @param bool $show_private
	 * @param int $paged
	 * @param int $perpage
	 * @param string $orderby
	 * @param string $order
	 *
	 * @return mixed
	 */
	public function getPostTypeCustomFields( $post_type, $exclude_fields = array(), $show_private = true, $paged = 1, $perpage = 10, $orderby = 'meta_key', $order = 'asc' ) {
        /*
          TODO:
          make search incremental to avoid large data issues
         */
		
		// TODO To optimize this query we need to be careful with the post type and sorting options.

        $exclude = array('_edit_last', '_edit_lock', '_wp_old_slug', '_thumbnail_id', '_wp_page_template',);
		if ( ! empty( $exclude_fields ) ) {
			$exclude = array_merge( $exclude, $exclude_fields );
		}
		
		$limit = 512 + count( $exclude );

        $exclude = "'" . implode("','", $exclude) . "'"; //wrap in quotes

        if ($paged < 0) {
	        if ( $show_private ) {
		        $sql = $this->wpdb->prepare(
					"SELECT COUNT(DISTINCT(pm.meta_key)) 
					FROM {$this->wpdb->postmeta} as pm, {$this->wpdb->posts} as p
					WHERE pm.post_id = p.ID
					AND p.post_type = %s
					AND pm.meta_key NOT IN ({$exclude})
					AND pm.meta_key NOT LIKE %s 
					LIMIT %d", 
					array( $post_type, "wpcf-%", $limit ) 
				);
	        } else {
		        $sql = $this->wpdb->prepare(
					"SELECT COUNT(DISTINCT(pm.meta_key)) 
					FROM {$this->wpdb->postmeta} as pm, {$this->wpdb->posts} as p
					WHERE pm.post_id = p.ID 
					AND p.post_type = %s
					AND pm.meta_key NOT IN ({$exclude})
					AND pm.meta_key NOT LIKE %s 
					AND pm.meta_key NOT LIKE %s 
					LIMIT %d", 
					array( $post_type, "wpcf-%", "\_%", $limit )
				);
	        }

            return $this->wpdb->get_var($sql);
        }
		
		// TODO To optimize this query we need to be careful with the post type and sorting options.
		
        $paged = intval($paged);
        $perpage = intval($perpage);
        $paged--;
        $order = strtoupper($order);
		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
			$order = 'ASC';
		}
		if ( ! in_array( $orderby, array( 'meta_key' ) ) ) {
			$orderby = 'meta_key';
		}

		if ( $show_private ) {
			$sql = $this->wpdb->prepare(
				"SELECT DISTINCT(pm.meta_key) 
				FROM {$this->wpdb->postmeta} as pm, {$this->wpdb->posts} as p
				WHERE pm.post_id = p.ID
				AND p.post_type = %s
				AND pm.meta_key NOT IN ({$exclude})
				AND pm.meta_key NOT LIKE %s 
				ORDER BY pm.{$orderby} {$order}
				LIMIT " . ( $paged * $perpage ) . ", " . $perpage,
				array( $post_type, "wpcf-%" )
			);
		} else {
			$sql = $this->wpdb->prepare(
				"SELECT DISTINCT(pm.meta_key) 
				FROM {$this->wpdb->postmeta} as pm, {$this->wpdb->posts} as p
				WHERE pm.post_id = p.ID
				AND p.post_type = %s
				AND pm.meta_key NOT IN ({$exclude})
				AND pm.meta_key NOT LIKE %s 
				AND pm.meta_key NOT LIKE %s
				ORDER BY pm.{$orderby} {$order}
				LIMIT " . ( $paged * $perpage ) . ", " . $perpage,
				array( $post_type, "wpcf-%", "\_%" )
			);
		}

        $fields = $this->wpdb->get_col($sql);

        return $fields;
    }

	/**
	 * @param null $post_type
	 * @param bool $force_all
	 *
	 * @return array|mixed
	 */
	public function getCustomFields( $post_type = null, $force_all = false ) {
        $custom_field_options = self::CUSTOM_FIELDS_OPTION;
        $custom_fields = get_option($custom_field_options, false);

        if ($force_all) {
	        if ( $custom_fields && ! empty( $custom_fields ) ) {
		        return $custom_fields;
	        }
        }

	    if ( $post_type !== null ) {
		    if ( $custom_fields && ! empty( $custom_fields ) && isset( $custom_fields[ $post_type ] ) ) {
			    return $custom_fields[ $post_type ];
		    }

		    return array();
	    } else {
		    if ( $custom_fields && ! empty( $custom_fields ) ) {
			    return $custom_fields;
		    }

		    return array();
	    }
    }

	/**
	 * Function responsible get all fields structure given a Post Type
	 * NOTE: Types controlled fields do not have prefix 'wpcf-'
	 *
	 * @param string $post_type
	 * @param bool $add_default
	 * @param null $localized_message_callback
	 *
	 * @return array
	 */
	public function getFields($post_type, $add_default = true, $localized_message_callback = null) {
		if ( empty( $post_type )
			|| $post_type == null
			|| $post_type == false
		) {
			return array();
		}

		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object ) {
			return array();
		}

		//m2m is enabled in order to disable old _wpcf_belongs parent shortcodes
		$is_m2m_enabled = CRED_Form_Relationship::get_instance()->is_m2m_enabled();

		// ALL FIELDS
		$all_fields = array();
		$groups = array();
		$fields = array();
		$groups_conditions = array();

		$post_type_original = $post_type;
		$post_type = '%,' . $post_type . ',%';

		$cred_fields_types_utils = new CRED_Fields_Types_Utils();

		$credCustomFields = $this->getCustomFields($post_type_original);
		$isCredCustomPost = (bool) (!empty($credCustomFields));

		// SET GROUPS FIELDS
		$cred_fields_types_utils->set_fields_groups_and_group_conditions( $post_type_object, $fields, $groups, $groups_conditions );

		// SET Toolset Forms CUSTOM FIELDS
		$cred_fields_types_utils->add_cred_custom_fields_in_groups( $isCredCustomPost, $credCustomFields, $fields, $groups );

		// PARENTS FIELDS
		$post_reference_fields = array();
		$relationships = array();
		$parents = $cred_fields_types_utils->get_parent_fields( $post_type_original );
		if ( $is_m2m_enabled ) {
			/*
			SET Post Reference Field Relationship
			NOTE: get_post_reference_fields must be always after add_cred_custom_fields_in_groups as it needs fields set by Types
			*/
			$post_reference_fields = $cred_fields_types_utils->get_post_reference_fields( $fields, $post_type_object );

			// MAP LEGACY PARENTS WITH RELATIONSHIPS
			CRED_Form_Relationship::get_instance()->map_parents_legacy_relationships( $parents, $post_type_object );

			// RELATIONSHIPS
			$relationships = CRED_Form_Relationship::get_instance()->get_relationships( $post_type_object );
		}

		// POST FIELDS
		$post_fields = $cred_fields_types_utils->get_post_fields( $add_default, $localized_message_callback, $post_type_object );

		// HIERARCHICAL PARENT FIELDS
		$hierarchical_parents = $cred_fields_types_utils->get_hierarchical_parent_fields( $post_type_object );

		// EXTRA FIELDS
		$extra_fields = $cred_fields_types_utils->get_extra_fields( $post_type_object );

		// BASIC FORM FIELDS
		$form_fields = $cred_fields_types_utils->get_form_fields();

		// TAXONOMIES FIELDS
		$taxonomies = $cred_fields_types_utils->get_taxonomies( $post_type_object );

		$all_fields['_post_data'] = $post_type_object->labels;
		$all_fields['groups'] = $groups;
		$all_fields['groups_conditions'] = $groups_conditions;
		$all_fields['form_fields'] = $form_fields;
		$all_fields['post_fields'] = $post_fields;
		$all_fields['custom_fields'] = $fields;
		$all_fields['post_reference_fields'] = $post_reference_fields;
		$all_fields['taxonomies'] = $taxonomies;
		$all_fields['parents'] = $parents;
		$all_fields['hierarchical_parents'] = $hierarchical_parents;
		$all_fields['relationships'] = $relationships;
		$all_fields['extra_fields'] = $extra_fields;
		$all_fields['form_fields_count'] = count($form_fields);
		$all_fields['post_fields_count'] = count($post_fields);
		$all_fields['custom_fields_count'] = count($fields);
		$all_fields['post_reference_fields_count'] = count($post_reference_fields);
		$all_fields['taxonomies_count'] = count($taxonomies);
		$all_fields['parents_count'] = count($parents);
		$all_fields['relationships_count'] = count($relationships);
		$all_fields['extra_fields_count'] = count($extra_fields);

		self::$basic_post_fields = $post_fields;

		return $all_fields;
	}
}
