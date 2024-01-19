<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Gui;

/**
 * Base class for form edit links GUI.
 *
 * @since 2.1
 */
class Base extends \CRED_Shortcode_Base_GUI {

    const EDIT_LINK_DOCUMENTATION	= 'https://toolset.com/course-lesson/front-end-forms-for-editing-content/?utm_source=plugin&utm_medium=gui&utm_campaign=forms';

    /**
	 * Generate the basic fields for the Toolset edit links.
	 *
	 * This method is shared by the toolset-edit-post-link and toolset-edit-user-link shortcodes,
	 * and generates the style/class combo, as well as the target attribute GUI
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	protected function get_edit_link_shortcodes_gui_basic_fields() {
		$basic_fields = array(
			'class_style_combo' => array(
				'label'		=> __( 'Element styling', 'wp-cred' ),
				'type'		=> 'group',
				'fields'	=> array(
					'class' => array(
						'pseudolabel'	=> __( 'Input classnames', 'wp-cred'),
						'type'			=> 'text',
						'description'	=> __( 'Space-separated list of classnames to apply. For example: classone classtwo', 'wp-cred' )
					),
					'style' => array(
						'pseudolabel'	=> __( 'Input style', 'wp-cred'),
						'type'			=> 'text',
						'description'	=> __( 'Raw inline styles to apply. For example: color:red;background:none;', 'wp-cred' )
					),
				),
			),
			'target'		=> array(
				'label'			=> __( 'Open the edit form in', 'wp-cred' ),
				'type'			=> 'select',
				'options'		=> array(
					'self'		=> __( 'The current window', 'wp-cred' ),
					'top'		=> __( 'The parent window', 'wp-cred' ),
					'blank'		=> __( 'A new window' )
				),
				'default'		=> 'self'
            ),
            'content' => $this->get_default_link_text_options()
		);
		return $basic_fields;
    }

	/**
	 * Get options for the text of the link shortcode.
	 *
	 * Each subclass will expand the supported options.
	 *
	 * @return array
	 */
    protected function get_default_link_text_options() {
        return array(
            'label' => __( 'Link text', 'wp-cred' ),
            'type' => 'content',
            'defaultForceValue' => __( 'Edit', 'wp-cred' )
        );
    }

    /**
	 * Adjust the fields for the Toolset edit links GUI.
	 *
	 * @param array  $attributes The shortcode GUI attributes already set
	 * @param string $form_type  Whether this belongs to the toolset-edit-post-link or the toolset-edit-user-link shortcode: 'post'|'user'
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	protected function adjust_edit_link_shortcodes_gui_fields( $attributes, $form_type = 'post' ) {

		if ( ! in_array( $form_type, array( 'post', 'user' ) ) ) {
			return array();
        }

        $layouts_condition = new \Toolset_Condition_Plugin_Layouts_Active();

		if ( $layouts_condition->is_met() ) {
			$filter_args = array(
				'property'	=> 'cell_type',
				'value'		=> ( 'user' == $form_type ) ? 'cred-user-cell' : 'cred-cell'
			);
			$layouts_available = apply_filters( 'ddl-filter_layouts_by_cell_type', array(), $filter_args );
			if ( count( $layouts_available ) > 0 ) {
				$available_options = array(
					''	=> __( '-- Select a layout --', 'wp-cred' )
				);

				foreach ( $layouts_available as $layout_for_edit ) {
					$available_options[ $layout_for_edit->slug ] = $layout_for_edit->name;
				}
				$layouts_data = array(
					'layout_slug' => array(
						'label'		=> __( 'Using this layout', 'wp-cred' ),
						'type'		=> 'select',
						'options'	=> $available_options,
						'default'	=> '',
						'description'	=> __( 'Select a layout that contains a form cell', 'wp-cred' ),
						'documentation'	=> '<a href="' . self::EDIT_LINK_DOCUMENTATION . '" target="_blank">' . __( 'Toolset edit links', 'wp-cred' ) . '</a>',
						'required'	=> true,
					)
				);

				$layouts_data = array_merge( $layouts_data, $attributes['display-options']['fields'] );
				$attributes['display-options']['fields'] = $layouts_data;
			} else {
				$attributes = array(
                    'display-options' => array(
                        'fields' => array(
                            'instructions'	=> array(
                                'type'		=> 'information',
                                'content'	=> '<p>'
                                                . __( 'Create a new Layout that will include the editing form. You can start from scratch or copy the template you use to display the content and modify it.', 'wp-cred' )
                                                . '</p>'
                                                . '<p>'
                                                . '<a href="' . self::EDIT_LINK_DOCUMENTATION . '" target="_blank">' . __( 'Documentation on Toolset edit links', 'wp-cred' ) . '</a>'
                                                . CRED_STRING_SPACE
                                                . '&bull;'
                                                . CRED_STRING_SPACE
                                                . '<a href="' . admin_url( 'admin.php?page=dd_layouts' ) . '" target="_blank">' . __( 'See all available layouts, or create a new one', 'wp-cred' ) . '</a>'
                                                . '</p>'
                            )
                        )
                    )
				);
			}
		} else {
			$content_templates_available = $this->get_content_templates_with_edit_forms( $form_type );
			if ( count( $content_templates_available ) > 0 ) {
				$available_options = array(
					''	=> __( '-- Select a Content Template --', 'wp-cred' )
				);

				foreach ( $content_templates_available as $content_templates_for_edit ) {
					$available_options[ $content_templates_for_edit->post_name ] = $content_templates_for_edit->post_title;
				}
				$content_templates_data = array(
					'content_template_slug' => array(
						'label'		=> __( 'Using this Content Template', 'wp-cred' ),
						'type'		=> 'select',
						'options'	=> $available_options,
						'default'	=> '',
						'description'	=> __( 'Select a Content Template that contains a form shortcode', 'wp-cred' ),
						'documentation'	=> '<a href="' . self::EDIT_LINK_DOCUMENTATION . '" target="_blank">' . __( 'Toolset edit links', 'wp-cred' ) . '</a>',
						'required'	=> true,
					)
				);

				$content_templates_data = array_merge( $content_templates_data, $attributes['display-options']['fields'] );
				$attributes['display-options']['fields'] = $content_templates_data;
			} else {
				$attributes = array(
                    'display-options' => array(
                        'fields' => array(
                            'instructions'	=> array(
                                'type'		=> 'information',
                                'content'	=> '<p>'
                                                . __( 'Create a new Content Template that will include the editing form. You can start from scratch or copy the template you use to display the content and modify it.', 'wp-cred' )
                                                . '</p>'
                                                . '<p>'
                                                . '<a href="' . self::EDIT_LINK_DOCUMENTATION . '" target="_blank">' . __( 'Documentation on Toolset edit links', 'wp-cred' ) . '</a>'
                                                . CRED_STRING_SPACE
                                                . '&bull;'
                                                . CRED_STRING_SPACE
                                                . '<a href="' . admin_url( 'admin.php?page=view-templates' ) . '" target="_blank">' . __( 'See all available Content Templates, or create a new one', 'wp-cred' ) . '</a>'
                                                . '</p>'
                            )
                        )
                    )
				);
			}
		}

		return $attributes;
    }

    /**
	 * Auxiliar method to get Content Templates that contain a form shortcode.
	 *
	 * Used by toolset-edit-post-link and toolset-edit-user-link shortcodes, to get
	 * Content Templates that contain [cred_form or [cred_user_form shortcodes
	 *
	 * @param string $form_type Whether this belongs to the toolset-edit-post-link or the toolset-edit-user-link shortcode: 'post'|'user'
	 *
	 * @return array
	 *
	 * @note When not using WPML, or when Content Templates are not translatable, the expensive query is cached in a transient
	 *     that gets invalidated every time a Content Template is created, edited, or deleted.
	 *
	 * @see WPV_Cache::delete_shortcodes_gui_transients_action
	 *
	 * @since 2.1
	 *
	 * @todo Move the transient management to CRED_Cache.
	 */
	protected function get_content_templates_with_edit_forms( $form_type = 'post' ) {

		if ( ! in_array( $form_type, array( 'post', 'user' ) ) ) {
			return array();
		}

		$content_templates_translatable = apply_filters( 'wpml_is_translated_post_type', false, 'view-template' );

		$transient_key = 'wpv_transient_pub_cts_for_cred_' . $form_type;

		$content_templates_available = get_transient( $transient_key );

		if (
			$content_templates_available !== false
			&& $content_templates_translatable === false
		) {
			return $content_templates_available;
		}

		global $wpdb;
		$values_to_prepare = array();
		$wpml_join = $wpml_where = "";

		if ( $content_templates_translatable ) {
			$wpml_current_language = apply_filters( 'wpml_current_language', '' );
			$wpml_join = " JOIN {$wpdb->prefix}icl_translations icl_t ";
			$wpml_where = " AND p.ID = icl_t.element_id AND icl_t.language_code = %s AND icl_t.element_type LIKE 'post_%' ";
			$values_to_prepare[] = $wpml_current_language;
		}

		switch ( $form_type ) {
			case 'post':
				$values_to_prepare[] = '%[cred_form %';
				$values_to_prepare[] = '%{!{cred_form %';
				break;
			case 'user':
				$values_to_prepare[] = '%[cred_user_form %';
				$values_to_prepare[] = '%{!{cred_user_form %';
				break;
		}

		$values_to_prepare[] = 'view-template';
		$content_templates_available = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_name, p.post_title
				FROM {$wpdb->posts} p {$wpml_join}
				WHERE p.post_status = 'publish'
				{$wpml_where}
				AND ( p.post_content LIKE '%s' OR p.post_content LIKE '%s' )
				AND p.post_type = %s
				ORDER BY p.post_title",
				$values_to_prepare
			)
		);

		if ( $content_templates_translatable === false ) {
			set_transient( $transient_key, $content_templates_available, WEEK_IN_SECONDS );
		}

		return $content_templates_available;
	}

}
