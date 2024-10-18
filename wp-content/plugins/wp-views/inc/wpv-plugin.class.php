<?php

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

class WP_Views_plugin extends WP_Views {

    function __construct() {
		parent::__construct();
    }

	// This happens on after_setup_theme:999
	function before_init() {
		parent::before_init();
	}

    function init() {

        add_filter( 'custom_menu_order', array( $this, 'enable_custom_menu_order' ) );
        add_filter( 'menu_order', array( $this, 'custom_menu_order' ) ); // @todo I really feel this is not used anymore

        parent::init();

		// Check whether we can require this only on editor pages
		require_once( WPV_PATH . '/inc/filters/editor-addon-parametric.class.php');

		// Actions to display buttons in edit screen textareas
		add_action( 'wpv_views_fields_button', array( $this, 'add_views_fields_button' ), 10 );
		add_action( 'wpv_cred_forms_button', array( $this, 'add_cred_forms_button' ) );

        if ( is_admin() ) {
			// This needs to happen as early as possible, since query filter assets are enqueued at wpv_admin_enqueue_scripts:10
			// and can not declare dependency of the main View or WPA edit script.
			add_action( 'admin_enqueue_scripts', array( $this,'wpv_admin_enqueue_scripts' ), 1 );
		}

        /**
        * Add hooks for backend Module Manager Integration
        */
        if ( defined( 'MODMAN_PLUGIN_NAME' ) ) {
			// Keep the part about registering elements in the plugin version
            add_filter( 'wpmodules_register_items_' . _VIEWS_MODULE_MANAGER_KEY_,			array( $this, 'register_modules_views_items' ), 30, 1 );
            add_filter( 'wpmodules_register_items_' . _VIEW_TEMPLATES_MODULE_MANAGER_KEY_,	array( $this, 'register_modules_view_templates_items' ), 20, 1 );
			// Add the section to Views and WPA edit pages
            add_action( 'wpv_action_view_editor_section_extra',	array( $this, 'add_view_module_manager_section' ), 20, 2 );
            add_action( 'wpv_action_wpa_editor_section_extra',	array( $this, 'add_view_module_manager_section' ), 20, 2 );
        }

        /**
         * Add hooks to append the relevant modals and the nonce in the footer of the Toolset Dashboard
         */
        add_action( 'admin_footer', array( $this, 'modify_toolset_dashboard' ) );

        /**
         * add some debug information
         */
        add_filter( 'icl_get_extra_debug_info', array( $this, 'add_config_to_toolset_extra_debug' ) );

		add_filter( 'toolset_filter_toolset_admin_bar_menu_insert', array( $this, 'extend_toolset_admin_bar_menu' ), 10, 3 );
    }



    /**
     * add extra debug information
     */
    public function add_config_to_toolset_extra_debug( $extra_debug ) {
        global $WPV_settings;
        $extra_debug['views'] = $WPV_settings->get();
        return $extra_debug;
    }

    function register_modules_views_items( $items ) {
        $views = $this->get_views();
        foreach ( $views as $view ) {
			$summary = '';
			$view_settings = $this->get_view_settings( $view->ID );
			if ( ! isset( $view_settings['view-query-mode'] ) ) { // Old views may not have this setting
				$view_settings['view-query-mode'] = 'normal';
			}
			switch ( $view_settings['view-query-mode'] ) {
				case 'normal':
					$summary .= '<h5>' . __('Content to load', 'wpv-views') . '</h5><p>' . apply_filters('wpv-view-get-content-summary', $summary, $view->ID, $view_settings) .'</p>';
					break;
				case 'archive':
				case 'layout-loop':
					$summary .= '<h5>' . __('Content to load', 'wpv-views') . '</h5><p>'. __('This View displays results for an <strong>existing WordPress query</strong>', 'wpv-views') . '</p>';
					break;
			}
            $items[] = array(
                'id' => _VIEWS_MODULE_MANAGER_KEY_ . $view->ID,
                'title' => esc_html( $view->post_title ),
                'details' => '<div style="padding:0 5px 5px;">' . $summary . '</div>'
            );
        }
        return $items;
    }

    function register_modules_view_templates_items( $items ) {
        global $WPV_settings;
        $viewtemplates = $this->get_view_templates();
        foreach ( $viewtemplates as $view ) {
			$summary = '';
			$used_as = wpv_get_view_template_defaults( $WPV_settings, $view->ID );
			if ( ! empty( $used_as ) ) {
				$summary .= '<h5>' . __('How this Content Template is used', 'wpv-views') . '</h5><p>' . $used_as . '</p>';
			}
			$description = get_post_meta( $view->ID, '_wpv-content-template-decription', true );
			if ( ! empty( $description ) ) {
				$summary .= '<h5>' . __('Description', 'wpv-views') . '</h5><p>' . $description . '</p>';
			}
			if ( empty( $summary ) ) {
				$summary = '<p>' . __('Content template', 'wpv-views') . '</p>';
			}
            $items[] = array(
                'id' => _VIEW_TEMPLATES_MODULE_MANAGER_KEY_ . $view->ID,
                'title' => esc_html( $view->post_title ),
                'details' => '<div style="padding:0 5px 5px;">' . $summary . '</div>'
            );
        }
        return $items;
    }

    /**
     * Creates the Views and Fields button for edit pages textareas.
     *
     * @param $textarea (string)
	 * @param $menus (array) Optional. Allows for custom set the menus that will be available. Since 1.9
     *
     * @since 1.7
     */
	function add_views_fields_button( $textarea ) {

		

		echo '<li class="wpv-vicon-codemirror-button">';
		wpv_add_v_icon_to_codemirror( $textarea );
		echo '</li>';
	}

	/**
	* add_cred_forms_button
	*
	* Creates a button for CRED forms when needd, in edit pages textareas
	*
	* @param $textarea (string)
	*
	* @return string Echo button
	*
	* @since 1.7
	*/
	function add_cred_forms_button( $textarea ) {
		$return = '';
		// This filter is only used by CRED to generate its button HTML
		$button = apply_filters( 'wpv_meta_html_add_form_button', '', '#' . $textarea );
		if ( ! empty( $button ) ) {
			$return .= '<li>' . $button . '</li>';
		}
		echo $return;
	}

    function enable_custom_menu_order($menu_ord) {
        return true;
    }


    /**
     * @deprecated This looks VERY deprecated.
     * @todo Check and remove.
     */
    function custom_menu_order( $menu_ord ) {
        $types_index = array_search('wpcf', $menu_ord);
        $views_index = array_search('edit.php?post_type=view', $menu_ord);

        if ($types_index !== false && $views_index !== false) {
            // put the types menu above the views menu.
            unset($menu_ord[$types_index]);
            $menu_ord = array_values($menu_ord);
            array_splice($menu_ord, $views_index, 0, 'wpcf');
        }

        return $menu_ord;
    }


    function is_embedded() {
        return false;
    }

	function register_views_pages_in_menu( $pages ) {
		global $pagenow;
		$page = wpv_getget( 'page' );

		$editing_experience = wpv_get_views_editing_experience();

		if (
			'classic' === $editing_experience
			|| 'mixed' === $editing_experience
		) {
			$pages[] = array(
				'slug'			=> 'views',
				'menu_title'	=> __( 'Views', 'wpv-views' ),
				'page_title'	=> __( 'Views', 'wpv-views' ),
				'callback'		=> 'wpv_admin_menu_views_listing_page'
			);
			if ( 'views-editor' == $page ) {
				add_filter( 'screen_options_show_screen', '__return_true', 99 );
				$pages[] = array(
					'slug'			=> 'views-editor',
					'menu_title'	=> __( 'Edit View', 'wpv-views' ),
					'page_title'	=> __( 'Edit View', 'wpv-views' ),
					'callback'		=> 'views_redesign_html'
				);
			}
		}
		$pages[] = array(
			'slug'			=> 'view-templates',
			'menu_title'	=> __( 'Content Templates', 'wpv-views' ),
			'page_title'	=> __( 'Content Templates', 'wpv-views' ),
			'callback'		=> 'wpv_admin_menu_content_templates_listing_page'
		);
        if (
			( 'admin.php' == $pagenow )
			&& ( WPV_CT_EDITOR_PAGE_NAME == $page )
		) {
			add_filter( 'screen_options_show_screen', '__return_false', 99 );
			$pages[] = array(
				'slug'			=> WPV_CT_EDITOR_PAGE_NAME,
				'menu_title'	=> __( 'Edit Content Template', 'wpv-views' ),
				'page_title'	=> __( 'Edit Content Template', 'wpv-views' ),
				'callback'		=> 'wpv_ct_editor_page'
			);
		}
		// Since 2.6 we need to fake registration of a CT create admin page to avoid issues with wpv_ct_editor_create_and_redirect
		if (
			( 'admin.php' == $pagenow )
			&& ( WPV_CT_CREATOR_PAGE_NAME == $page )
		) {
			add_filter( 'screen_options_show_screen', '__return_false', 99 );
			$pages[] = array(
				'slug'			=> WPV_CT_CREATOR_PAGE_NAME,
				'menu_title'	=> __( 'Edit Content Template', 'wpv-views' ),
				'page_title'	=> __( 'Edit Content Template', 'wpv-views' ),
				'callback'		=> 'wpv_ct_creator_page'
			);
        }
		$pages[] = array(
			'slug'			=> 'view-archives',
			'menu_title'	=> __( 'WordPress Archives', 'wpv-views' ),
			'page_title'	=> __( 'WordPress Archives', 'wpv-views' ),
			'callback'		=> 'wpv_admin_archive_listing_page'
		);
        if ( 'view-archives-editor' == $page ) {
			add_filter( 'screen_options_show_screen', '__return_true', 99 );
			$pages[] = array(
				'slug'			=> 'view-archives-editor',
				'menu_title'	=> __( 'Edit WordPress Archive', 'wpv-views' ),
				'page_title'	=> __( 'Edit WordPress Archive', 'wpv-views' ),
				'callback'		=> 'views_archive_redesign_html'
			);
		}
		// create a new submenu for specific update routines
		if ( 'views-update-help' == $page && function_exists( 'views_update_help_wpv_if' ) ) {
			$pages[] = array(
				'slug'			=> 'views-update-help',
				'menu_title'	=> __( 'Update changes', 'wpv-views' ),
				'page_title'	=> __( 'Update changes', 'wpv-views' ),
				'callback'		=> 'views_update_help'
			);
		}

        // Fake menu. Toolbar create a new X link
        $this->add_views_admin_create_ct_or_wpa_auto();
		return $pages;
	}

	function register_export_import_section( $sections ) {
		// @todo check assets...
		// @todo move this to a template

		// Disable the export/import section for Toolset Blocks.
		if ( 'blocks' === wpv_get_views_flavour() ) {
			return $sections;
		}
		$icon_classname = ( 'blocks' === wpv_get_views_flavour() ) ? 'icon-toolset-blocks' : 'icon-views-logo';

		$sections['wpv-views'] = array(
			'slug'		=> 'wpv-views',
			'title'		=> __( 'Views', 'wpv-views' ),
			'icon'		=> '<i class="' . esc_attr( $icon_classname ) . ' ont-icon-16"></i>',
			'items'		=> array(
				'export'	=> array(
								'title'		=> __( 'Export Views, WordPress Archives and Content Templates', 'wpv-views' ),
								'content'	=> '<form name="View_export" action="' . admin_url('edit.php') . '" method="post">'
													. '<p>'
														. __( 'You can export the Views settings as a .zip file.', 'wpv-views' )
													. '</p>'
													. '<p>'
														. __( 'That file will contain all the data related to Views, Content Templates and WordPress Archives, as well as the general Views settings.', 'wpv-views' )
													. '</p>'
													. '<input type="checkbox" id="wpv-affiliate-data" class="js-toolset-control-hidden-setting" name="wpv-affiliate-data" value="1" data-target="wpv-affiliate-data" autocomplete="off" />'
													. '<label for="wpv-affiliate-data">' . __( 'I am a theme designer and I want to receive affiliate commission', 'wpv-views' ) . '</label>'
													. '<div class="js-toolset-control-hidden-setting-target-wpv-affiliate-data" style="display:none">'
														. '<ul>'
															. '<li>'
																. '<label for="aid">' . __( 'Affiliate ID:', 'wpv-views' ) . '</label><br>'
																. '<input type="text" name="aid" id="aid" />'
															. '</li>'
															. '<li>'
																. '<label for="akey">' . __( 'Affiliate Key:', 'wpv-views' ) . '</label><br>'
																. '<input type="text" name="akey" id="akey" />'
															. '</li>'
														. '</ul>'
														. '<p>'
															. __( 'To receive affiliate commission you have to provide your affiliate ID and Key.', 'wpv-views' )
															. WPV_MESSAGE_SPACE_CHAR
															. sprintf(
																__( 'Log into <a href="%s">your account</a> and go to <a href="%s">affiliate settings</a> for details.', 'wpv-views' ),
																WPV_Admin_Messages::get_documentation_promotional_link(
																	array(
																		'query' => array(
																			'utm_source'	=> 'viewsplugin',
																			'utm_campaign'	=> 'views',
																			'utm_medium'	=> 'import-export-login-to-wp-types-com',
																			'utm_term'		=> 'your account'
																		)
																	),
																	'https://toolset.com'
																),
																WPV_Admin_Messages::get_documentation_promotional_link(
																	array(
																		'query' => array(
																			'utm_source'	=> 'viewsplugin',
																			'utm_campaign'	=> 'views',
																			'utm_medium'	=> 'import-export-get-affiliate-link',
																			'utm_term'		=> 'affiliate settings'
																		)
																	),
																	'https://toolset.com/account/affiliate/'
																)
															)
														. '</p>'
													. '</div>'
													. '<p class="toolset-update-button-wrap">'
														. '<input id="wpv-export" type="hidden" value="wpv-export" name="export" />'
														. '<button id="wpv-export-button" class="button-primary">' . __( 'Export', 'wpv-views' ) . '</button>'
													. '</p>'
													. wp_nonce_field( 'wpv-export-nonce', 'wpv-export-nonce', true, false )
												. '</form>'
							),
				'import'	=> array(
								'title'		=> __( 'Import Views, WordPress Archives and Content Templates', 'wpv-views' ),
								'content'	=> '<form name="View_import" enctype="multipart/form-data" action="' . admin_url('admin.php') . '?page=toolset-export-import&tab=wpv-views" method="post">'
													. '<p>'
														. __( 'You can upload a .zip or .xml file from your computer:', 'wpv-views' )
													. '</p>'
													. '<p>'
														. '<input type="file" id="upload-views-file" name="import-file" />'
														. '<input type="hidden" name="page" value="views-import-export" />'
													. '</p>'
													. '<ul>'
														. '<li>'
															. '<input id="checkbox-1" type="checkbox" name="views-overwrite" />'
															. '<label for="checkbox-1">' . __( 'Bulk overwrite if View or WordPress Archive exists', 'wpv-views' ) . '</label>'
														. '</li>'
														. '<li>'
															. '<input id="checkbox-2" type="checkbox" name="views-delete" />'
															. '<label for="checkbox-2">' . __( 'Delete any existing Views or WordPress Archives that are not in the import', 'wpv-views' ) . '</label>'
														. '</li>'
														. '<li>'
															. '<input id="checkbox-3" type="checkbox" name="view-templates-overwrite" />'
															. '<label for="checkbox-3">' . __( 'Bulk overwrite if Content Template exists', 'wpv-views' ) . '</label>'
														. '</li>'
														. '<li>'
															. '<input id="checkbox-4" type="checkbox" name="view-templates-delete" />'
															. '<label for="checkbox-4">' . __( 'Delete any existing Content Templates that are not in the import', 'wpv-views' ) . '</label>'
														. '</li>'
														. '<li>'
															. '<input id="checkbox-5" type="checkbox" name="view-settings-overwrite" />'
															. '<label for="checkbox-5">' . __( 'Overwrite Views settings', 'wpv-views' ) . '</label>'
														. '</li>'
													. '</ul>'
													. '<p class="toolset-update-button-wrap">'
														. '<input id="wpv-import" type="hidden" value="wpv-import" name="import" />'
														. '<button id="wpv-import-button" class="button-primary">' . __( 'Import', 'wpv-views' ) . '</button>'
													. '</p>'
													. wp_nonce_field( 'wpv-import-nonce', 'wpv-import-nonce', true, false )
												. '</form>'
							),
			),
		);
		return $sections;
	}

    public function add_views_admin_create_ct_or_wpa_auto() {
        $parent_slug = 'options.php'; // Invisible. See WordPress documentation. todo add link
        $page_title = __( 'Create a new Template', 'wpv-views' );
        $menu_title = __( 'Create a new Template', 'wpv-views' );
        $capability = EDIT_VIEWS;
        $menu_slug = 'views_create_auto';
        $function = array( $this, 'create_ct_or_wpa_auto' );
        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
    }

    /**
     * Creates a Content Template for WordPress Archive for a Post Type,
     * Taxonomy or WordPress Page with default settings and assigns it to the
     * right item.
     *
     * Used by Toolset_Admin_Bar_Menu.
     *
     * Expected $_GET parameters
     * type: post type, taxonomy or special wordpress archive
     * class: is it an archive page or a content template page
     * post: post_id or empty
     *
     */
    public function create_ct_or_wpa_auto() {

        // verify permissions
        if( ! current_user_can( EDIT_VIEWS ) ) {
            die( __( 'Untrusted user', 'wpv-views' ) );
        }

        // verify nonce
        check_admin_referer( 'create_auto' );

        // validate parameters
        $b_type = isset( $_GET['type'] ) && preg_match( '/^([-a-z0-9_]+)$/', $_GET['type'] );
        $b_class = isset( $_GET['class'] ) && preg_match( '/^(archive|page)$/', $_GET['class'] );
        $b_post_id = isset( $_GET['post'] ) && (int) $_GET['post'] >= 0;

        // validate request
        if( ! ( $b_type && $b_class && $b_post_id ) ) {
            die( __( 'Invalid parameters', 'wpv-views' ) );
        }

        // get parameters
        $type = $_GET['type'];
        $class = $_GET['class'];
        $post_id = (int) $_GET['post'];

        // enforce rules
        $b_page_archive = 'page' === $type && 'archive' === $class;
        $b_404 = '404' === $type;
        if( $b_page_archive || $b_404 ) {
            die( __( 'Not allowed', 'wpv-views' ) );
        }

        // prepare processing
        if( $post_id === 0 ) {
            $post_id = null;
        }

        $wpa_id = 0;
        $ct_id = 0;

        global $WPV_settings;
        global $toolset_admin_bar_menu;
        $post_title = $toolset_admin_bar_menu->get_name_auto( 'views', $type, $class, $post_id );
        $title = sanitize_text_field( $post_title );
		$name = sanitize_text_field( sanitize_title( $post_title ) );

        $taxonomy = get_taxonomy( $type );
        $is_tax = $taxonomy !== false;

        $post_type_object = get_post_type_object( $type );
        $is_cpt = $post_type_object != null;

        // route request
        if( 'archive' === $class ) {

            // Create a new WordPress Archive
            global $wpdb, $WPV_view_archive_loop;

            // Is there another WordPress Archive with the same name?
            $already_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
                    WHERE ( post_title = %s OR post_name = %s )
                    AND post_type = 'view'
                    LIMIT 1",
                    $title,
                    $title
                )
            );

			if( $already_exists ) {
				$new_name = wp_unique_post_slug( $name, 0, 'publish', 'view', 0 );
				$suffix = str_replace( $name . '-', '', $new_name );
				$title .= ' (' . $suffix . ')';
				$name = $new_name;
			}

			if ( empty( $name ) ) {
				$name = 'view-rand-' . uniqid();
			}

            $args = array(
                'post_title'    => $title,
				'post_name'    => $name,
                'post_type'      => 'view',
                'post_content'  => "[wpv-filter-meta-html]\n[wpv-layout-meta-html]",
                'post_status'   => 'publish',
                'post_author'   => get_current_user_id(),
                'comment_status' => 'closed'
            );
            $wpa_id = wp_insert_post( $args );
            $wpa_type = '';

            if( in_array( $type, Toolset_Admin_Bar_Menu::$default_wordpress_archives )  ) {

                // Create a new WordPress Archive for X archives

                /* assign WordPress Archive to X archives */
                $wpa_type = sprintf( 'wpv-view-loop-%s-page', $type );

            } else if( $is_tax ) {

                // Create a new WordPress Archive for Ys

                /* assign WordPress Archive to Y */
                $wpa_type = sprintf( 'wpv-view-taxonomy-loop-%s', $type );

            } else if( $is_cpt ) {

                // Create a new WordPress Archive for Zs

                /* assign WordPress Archive to Z */
                $wpa_type = sprintf( 'wpv-view-loop-cpt_%s', $type );

            } else {
                die( __( 'An unexpected error happened.', 'wpv-views' ) );
            }

            $archive_defaults = wpv_wordpress_archives_defaults( 'view_settings' );
            $archive_layout_defaults = wpv_wordpress_archives_defaults( 'view_layout_settings' );
            update_post_meta( $wpa_id, '_wpv_settings', $archive_defaults );
            update_post_meta( $wpa_id, '_wpv_layout_settings', $archive_layout_defaults );

            $data = array( $wpa_type => 'on' );
			$WPV_view_archive_loop->update_view_archive_settings( $wpa_id, $data );

			/**
			 * Filter for getting the ID of the created WordPress Archive post.
			 *
			 * Used here to generate the helper WPA post.
			 *
			 * @param int                   $wpa_id
			 * @param WPV_WordPress_Archive $wpa
			 * @since 3.0
			 */
			$wpa_helper_id = apply_filters( 'wpv_filter_wpa_created', $wpa_id, \WPV_WordPress_Archive::get_instance( $wpa_id ) );

        } else if( 'page' === $class ) {

            // Create a new Content Template
            $create_template = wpv_create_content_template( $title, '', true, '' );
            if ( isset( $create_template['error'] ) ) {
                die( __( 'A Content Template with that name already exists. Please use another name.', 'wpv-views' ) );
            }

            if( ! isset( $create_template['success'] ) || (int) $create_template['success'] == 0 ) {
                die( __( 'An unexpected error happened.', 'wpv-views' ) );
            }

            $ct_id = $create_template['success'];
            $ct_type = '';

            if( 'page' === $type ) {

                // Create a new Content Template for 'Page Title'

                /* assign Content Template to Page */
	            WPV_Content_Template_Embedded::assign_ct_to_post_object( $post_id, $ct_id );

            } else if( $is_cpt ) {

                // Create a new Content Template for Ys

                /* assign Content Template to Y */
                $ct_type = sanitize_text_field( sprintf( 'views_template_for_%s', $type ) );
                $WPV_settings[$ct_type] = $ct_id;

            } else {
                die( __( 'An unexpected error happened.', 'wpv-views' ) );
            }

        }

        // update changes
        $WPV_settings->save();

        // redirect to editor or die
        $template_id = max( array( $wpa_id, $ct_id ) );

        if( $template_id === 0 ) {
            die( __( 'Unexpected error. Nothing was changed.', 'wpv-views' ) );
        }

        // redirect to editor (headers already sent)
        $edit_link = $toolset_admin_bar_menu->get_edit_link( 'views', false, $type, $class, $template_id );
        $exit_string = '<script type="text/javascript">'.'window.location = "' . $edit_link . '";'.'</script>';
        exit( $exit_string );
    }


	/**
	 * Optionally add an item to edit View to the "Design with Toolset" admin bar menu.
	 *
	 * See the toolset_filter_toolset_admin_bar_menu_insert filter.
	 *
	 * @param array|mixed $menu_item_definitions
	 * @param string $context
	 * @param int $post_id
	 * @return array Menu item definitions.
	 * @since 1.12
	 */
    public function extend_toolset_admin_bar_menu( $menu_item_definitions,
		/** @noinspection PhpUnusedParameterInspection */ $context,
		/** @noinspection PhpUnusedParameterInspection */ $post_id )
	{
        if( !is_array( $menu_item_definitions ) ) {
            $menu_item_definitions = array();
        }

        $used_view_ids = array_unique( $this->view_used_ids );

		foreach( $used_view_ids as $view_id ) {

			// Take only Views, not WPAs
			$view = WPV_View_Base::get_instance( $view_id );

			if (
				null != $view
				&& $view->is_a_view()
				&& method_exists( $view, 'get_is_gutenberg_view' )
				&& ! $view->get_is_gutenberg_view()
			) {
				// Try to get a post edit link
				$link = apply_filters( 'icl_post_link', null, WPV_View_Base::POST_TYPE, $view->id, 'edit' );
				$is_disabled = wpv_getarr( $link, 'is_disabled', false );
				$url = wpv_getarr( $link, 'url' );
				if( !$is_disabled && !empty( $url ) ) {

					// We got a valid post edit link to a View, now we can add the submenu item.
					$menu_item_definitions[] = array(
						'title' => sprintf( '%s: %s', __( 'Edit View', 'wpv-views' ), $view->title ),
						'menu_id' => sprintf( 'toolset_design_view_%s', $view->slug ),
						'href' => $url
					);
				}
			}
		}

		return $menu_item_definitions;
    }

    function settings_box_load(){
    // DEPRECATED, check Module Manager

		global $pagenow;
        if ($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == WPV_FOLDER . '/menu/main.php') {
            $this->include_admin_css();
        }
        if ($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == 'wpv-import-theme') {
            $this->include_admin_css();
        }

    }


	/**
	 * @param array $view_settings
	 * @param int $view_id
	 */
	public function add_view_module_manager_section(
		/** @noinspection PhpUnusedParameterInspection */ $view_settings, // phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$view_id
	) {
		$section_help_pointer = WPV_Admin_Messages::edit_section_help_pointer( 'module_manager' );
		?>
		<div class="wpv-setting-container wpv-setting-container-module-manager js-wpv-settings-content">

			<div class="wpv-settings-header">
				<h3>
					<?php _e( 'Module Manager', 'wpv-views' ) ?>
					<i class="icon-question-sign fa fa-question-circle js-display-tooltip"
						data-header="<?php echo esc_attr( $section_help_pointer['title'] ); ?>"
						data-content="<?php echo esc_attr( $section_help_pointer['content'] ); ?>">
					</i>
				</h3>
			</div>

			<div class="wpv-setting wpv-setting-module-manager">
				<?php
				$element = array(
					'id' => _VIEWS_MODULE_MANAGER_KEY_ . $view_id,
					'title' => get_the_title( $view_id ),
					'section' => _VIEWS_MODULE_MANAGER_KEY_,
				);
				do_action( 'wpmodules_inline_element_gui', $element );
				?>
			</div>

		</div>
		<?php
	}


	/**
	 * Callback function for the 'wpv_action_types_dashboard_extra_body action, which registers and enqueues all the
	 * needed dependencies that need to be placed on the body of the Types Dashboard
	 *
	 * @since 2.3.0
	 */
	public static function modify_toolset_dashboard() {
		// Types Dashboard
		if ( 'toolset-dashboard' === toolset_getget( 'page' ) ) {
			wpv_render_view_listing_dialog_templates();

			wpv_render_wpa_listing_dialog_templates_arrangeby_usage();

			wp_nonce_field( 'work_views_listing', 'work_views_listing' );
		}
	}


	/**
	 * Action fired on wpv_action_wpv_save_item to update a postmeta flag storing the last modified time
	 *
	 * @param int $item_id
	 * @since 1.8.0
	 */
	function after_save_item( $item_id ) {
		if (
			! is_numeric( $item_id )
			|| intval( $item_id ) < 1
		) {
			return;
		}
		$now = time();
		$last = intval( get_post_meta( $item_id, '_toolset_edit_last', true ) );
		if ( $last >= $now ) {
			return;
		}
		update_post_meta( $item_id, '_toolset_edit_last', $now, $last );

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->posts
				SET post_modified = %s, post_modified_gmt = %s
				WHERE ID = %d",
				array(
					current_time( 'mysql' ),
					current_time( 'mysql', 1 ),
					$item_id,
				)
			)
		);

	}


	/**
	 * Get the available View in a select box
	 *
	 * @param int|string $row
	 * @param int|string $page_selected
	 * @param bool $archives_only
	 *
	 * @return string
	 */
	function get_view_select_box( $row, $page_selected, $archives_only = false ) {
		global $wpdb, $sitepress;

		static $views_available = null;

		if ( ! $views_available ) {
			$views_available = $wpdb->get_results(
				"SELECT ID, post_title, post_name FROM {$wpdb->posts}
				WHERE post_type = 'view'
				AND post_status = 'publish'"
			);

			if ( $archives_only ) {
				foreach ( $views_available as $index => $view ) {
					$view_settings = $this->get_view_settings( $view->ID );
					if ( 'archive' !== $view_settings['view-query-mode'] ) {
						unset( $views_available[ $index ] );
					}
				}
			}

			// Add a "None" type to the list.
			$none = new stdClass();
			$none->ID = '0';
			$none->post_title = __( 'None', 'wpv-views' );
			$none->post_content = '';
			array_unshift( $views_available, $none );
		}

		$view_box = '';
		if ( '' === $row ) {
			$view_box .= '<select class="view_select" name="view" id="view">';
		} else {
			$view_box .= '<select class="view_select" name="view_' . $row . '" id="view_' . $row . '">';
		}

		if ( isset( $sitepress ) && function_exists( 'icl_object_id' ) ) {
			$page_selected = icl_object_id( $page_selected, 'view', true );
		}

		foreach ( $views_available as $view ) {

			if ( isset( $sitepress ) ) { // TODO maybe DEPRECATED check how to translate Views
				// See if we should only display the one for the correct lanuage.
				$lang_details = $sitepress->get_element_language_details( $view->ID, 'post_view' );
				if ( $lang_details ) {
					$translations = $sitepress->get_element_translations( $lang_details->trid, 'post_view' );
					if ( count( $translations ) > 1 ) {
						$lang = $sitepress->get_current_language();
						if ( isset( $translations[ $lang ] ) ) {
							// Only display the one in this language.
							if ( $view->ID != $translations[ $lang ]->element_id ) {
								continue;
							}
						}
					}
				}
			}

			if ( $page_selected == $view->ID ) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}

			if ( $view->post_title ) {
				$post_name = $view->post_title;
			} else {
				$post_name = $view->post_name;
			}

			$view_box .= '<option value="' . $view->ID . '"' . $selected . '>' . $post_name . '</option>';

		}
		$view_box .= '</select>';

		return $view_box;
	}

	function wpv_register_assets() {
		parent::wpv_register_assets();

		/*
		* Backend scripts
		* @note all scripts for the edit pages need to be registered for the header. Loading them in the footer breaks the Layouts cells in Firefox just because :-O
		*/

		// Views, WPA and CT edit screens JS
		// @todo on a future revision, once common is spread, make **_editor.js depend on icl_editor-script and remove fallbacks

		$editor_translations = array(
			'screen_options'                           => array(
				'pagination_needs_filter'        => __( 'Pagination requires the Filter HTML section to be visible.', 'wpv-views' ),
				'parametric_search_needs_filter' => __( 'The custom search settings require the Filter HTML section to be visible.', 'wpv-views' ),
				'filter_needs_parametric_search' => __( 'The Filter HTML section requires the custom search settings to be visible.', 'wpv-views' ),
				'can_not_hide'                   => __( 'This section has unsaved changes, so you can not hide it', 'wpv-views' ),
				'nonce'                          => wp_create_nonce( 'wpv_view_show_hide_nonce' )
			),
			'event_trigger_callback_comments'          => array(
				'view_unique_id'                          => __( '(string) The View unique ID hash', 'wpv-views' ),
				'effect'                                  => __( '(string) The View AJAX pagination effect', 'wpv-views' ),
				'speed'                                   => __( '(integer) The View AJAX pagination speed in miliseconds', 'wpv-views' ),
				'form'                                    => __( '(object) The jQuery object for the View form', 'wpv-views' ),
				'form_updated'                            => __( '(object) The jQuery object for the View form after being updated', 'wpv-views' ),
				'layout'                                  => __( '(object) The jQuery object for the View layout wrapper', 'wpv-views' ),
				'update_form'                             => __( '(bool) Whether the custom search form will be updated', 'wpv-views' ),
				'update_results'                          => __( '(bool) Whether the custom search results will be updated', 'wpv-views' ),
				'view_changed_form_additional_forms_only' => __( '(object) The jQuery object containing additional forms from other instances of the same View inserted using the [wpv-form-view] shortcode', 'wpv-views' ),
				'view_changed_form_additional_forms_full' => __( '(object) The jQuery object containing additional forms from other instances of the same View inserted using the [wpv-view] shortcode', 'wpv-views' )
			),
			'dialog'                                   => array(
				'close'                       => __( 'Close', 'wpv-views' ),
				'cancel'                      => __( 'Cancel', 'wpv-views' ),
				'restore'                     => __( 'Restore defaults', 'wpv-views' ),
				'apply'                       => __( 'Apply', 'wpv-views' ),
				'post_types_for_archive_loop' => array(
					'title' => __( 'Choose post types', 'wpv-views' )
				),
			),
			'dialog_pagination'                        => array(
				'title'  => __( 'Insert transition controls for the pagination', 'wpv-views' ),
				'insert' => __( 'Insert controls', 'wpv-views' ),
			),
			'dialog_sorting'                           => array(
				'title'      => __( 'Insert controls for frontend sorting', 'wpv-views' ),
				'insert'     => __( 'Insert controls', 'wpv-views' ),
				'option_row' => '<tr class="wpv-editable-list-item js-wpv-frontend-sorting-orderby-options-list-item">'
				                . '<td>%%orderby_sortable%%</td>'
				                . '<td>%%orderby_options_select%%%%orderby_as%%</td>'
				                . '<td>%%orderby_set_order%%</td>'
				                . '<td>%%orderby_label%%</td>'
				                . '<td>%%orderby_delete%%</td>'
				                . '</tr>',
				'labels'     => array(
					'sort_as_native' => __( 'as a native custom field', 'wpv-views' ),
					'sort_as_string' => __( 'as a string', 'wpv-views' ),
					'sort_as_number' => __( 'as a number', 'wpv-views' ),
					'sort_order'     => __( 'Labels for the sorting direction control:', 'wpv-views' ),
					'ascending'      => __( 'Ascending', 'wpv-views' ),
					'descending'     => __( 'Descending', 'wpv-views' ),
					'direction_asc'  => __( 'ascending', 'wpv-views' ),
					'direction_desc' => __( 'descending', 'wpv-views' ),
					'asc_alphabet'   => __( 'A to Z', 'wpv-views' ),
					'desc_alphabet'  => __( 'Z to A', 'wpv-views' ),
					'asc_time'       => __( 'Older first', 'wpv-views' ),
					'desc_time'      => __( 'Newer first', 'wpv-views' )
				),
				'warnings'   => array(
					'unsupported_field' => __( 'This field will not appear in the sorting options because its name includes characters that we don’t support.', 'wpv-views' ),
					'missing_options'   => __( 'You need to add at least one valid sorting option', 'wpv-views' ),
					'first_row'         => __( 'The first mandatory sorting option is synchronized with the current stored sorting settings. Any change that you do to it will be pushed to the Ordering section.', 'wpv-views' )
				),
			),
			'pointer' => array(
				'close'                       => __( 'Close', 'wpv-views' ),
				'viewsLiteTooltipTitle'       => __( 'Full Views Feature', 'wpv-views' ),
				'tooltipPaginationDisabled'   => __( 'Display the results with pagination is not available in the Lite version. To use it, get the full Views.', 'wpv-views' ),
				'tooltipCustomSearchDisabled' => __( 'Display the results using a custom search is not available in the Lite version. To use it, get the full Views.', 'wpv-views' ),
				'tooltipPriceLinkTitle'       => __( 'Compare versions and buy', 'wpv-views' ),
				'tooltipPriceLinkUrl'         => __( WPV_LITE_UPGRADE_LINK, 'wpv-views' )

			),
			'toolset_alert'                            => array(
				'content_missing_filter_editor'                => sprintf(
					__( '%s This WordPress Archive will not display the Search and Pagination editor unless you add a <code>[wpv-filter-meta-html]</code> shortcode to the Output Editor', 'wpv-views' ),
					'<i class="fa fa-warning fa-lg"></i>'
				),
				'content_missing_filter_editor_for_pagination' => sprintf(
					__( '%s To enable pagination for this WordPress Archive you need to add a <code>[wpv-filter-meta-html]</code> shortcode to the Output Editor', 'wpv-views' ),
					'<i class="fa fa-warning fa-lg"></i>'
				),
			),
			'toolbar_buttons'                          => array(
				'pagination' => array(
					'tooltip' => array(
						'disabled' => __( 'Pagination controls are disabled because this View or WordPress Archive doesn\'t use pagination', 'wpv-views' ),
						'already'  => __( 'Pagination controls were already added', 'wpv-views' ),
						'missing'  => __( 'Pagination is enabled but there are no pagination controls', 'wpv-views' ),
						'infinite' => __( 'Pagination controls are not needed for infinite scrolling', 'wpv-views' ),
					)
				)
			),
			'frontend_events_dialog_title'             => __( 'Insert Views frontend event handler', 'wpv-views' ),
			'add_event_trigger_callback_dialog_insert' => __( 'Insert event trigger callback', 'wpv-views' ),
			'codemirror_autoresize'                    => apply_filters( 'wpv_filter_wpv_codemirror_autoresize', false ),
			'sections_saved' => __( 'View saved', 'wpv-views' ),
			'some_section_unsaved'                     => __( 'One or more sections haven\'t been saved.', 'wpv-views' ),
			'editor_nonce'                             => wp_create_nonce( 'wpv_nonce_editor_nonce' ),
			//@todo maybe add a $current_user->ID here for unique nonces
			'is_views_lite'                            => apply_filters( 'wpv_is_views_lite', false ),
		);

		$wpv_ajax = WPV_Ajax::get_instance();

		$editor_ajax_info = array(
			'ajax' => array(
				'action' => array(
					'update_view_title' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_VIEW_TITLE ),
					'update_description' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_DESCRIPTION ),
					'view_wrapper_section_update' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_VIEW_WRAPPER_SECTION ),
					'scan_view_usage' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_SCAN_VIEW_USAGE ),

				),
				'nonce' => array(
					'update_view_title' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_VIEW_TITLE ),
					'update_description' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_DESCRIPTION ),
					'view_wrapper_section_update' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_VIEW_WRAPPER_SECTION ),
					'scan_view_usage_nonce' => wp_create_nonce( WPV_Ajax::CALLBACK_SCAN_VIEW_USAGE ),
				)
			),
			'ajaxCallbacks' => array(
				'createPageForView' => array(
					'action' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_CREATE_PAGE_FOR_VIEW ),
					'nonce' => wp_create_nonce( WPV_Ajax::CALLBACK_CREATE_PAGE_FOR_VIEW ),
				),
			),
		);

		wp_register_script(
			'views-editor-js',
			WPV_URL . "/res/js/redesign/views_editor.js",
			array( 'jquery', 'suggest', 'wp-pointer', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-tooltip', 'views-codemirror-conf-script', 'views-utils-script', 'toolset-utils', 'toolset-event-manager', 'underscore', 'quicktags', 'wplink'),
			WPV_VERSION,
			false
		);
		wp_localize_script( 'views-editor-js', 'wpv_editor_strings', array_merge( $editor_translations, $editor_ajax_info ) );

		$editor_translations['sections_saved'] = __( 'WordPress Archive saved', 'wpv-views' );
		wp_register_script(
			'views-archive-editor-js',
			WPV_URL . "/res/js/redesign/views_archive_editor.js",
			array( 'jquery', 'suggest', 'wp-pointer', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-tooltip', 'views-codemirror-conf-script', 'views-utils-script', 'toolset-utils', 'toolset-event-manager', 'underscore', 'quicktags', 'wplink' ),
			WPV_VERSION,
			false
		);
		wp_localize_script( 'views-archive-editor-js', 'wpv_editor_strings', array_merge( $editor_translations, $editor_ajax_info ) );

		$filters_strings = array(
			'add_filter_dialog'				=> array(
				'title'			=> __( 'Add a query filter to this View','wpv-views' ),
				'cancel'		=> __( 'Cancel', 'wpv-views' ),
				'insert'		=> __( 'Add query filter', 'wpv-views' ),
				'select_empty'	=> __( "Please select an option", 'wpv-views' ),
				'loading'		=> __( 'Loading...', 'wpv-views' ),
			),
			'validation'					=> array(
				'param_missing'					=> __( "This field can not be empty", 'wpv-views' ),
				'param_forbidden_wordpress'		=> __( "This is a word reserved by WordPress", 'wpv-views' ),
				'param_forbidden_toolset'		=> __( "This is a word reserved by any of the Toolset plugins", 'wpv-views' ),
				'param_forbidden_toolset_attr'	=> __( "This is an attribute reserved by any of the Toolset plugins", 'wpv-views' ),
				'param_forbidden_post_type'		=> __( "There is a post type named like that", 'wpv-views' ),
				'param_forbidden_taxonomy'		=> __( "There is a taxonomy named like that", 'wpv-views' ),
				'param_ilegal'					=> array(
					'url'				=> __( "Only lowercase letters, numbers, hyphens and underscores allowed as URL parameters", 'wpv-views' ),
					'shortcode'			=> __( "Only lowercase letters and numbers allowed as shortcode attributes", 'wpv-views' ),
					'year'				=> __( 'Years can only be a four digits number', 'wpv-views' ),
					'month'				=> __( 'Months can only be a number between 1 and 12', 'wpv-views' ),
					'week'				=> __( 'Weeks can only be numbers between 1 and 53', 'wpv-views' ),
					'day'				=> __( 'Days can only be a number between 1 and 31', 'wpv-views' ),
					'hour'				=> __( 'Hours can only be numbers between 0 and 23', 'wpv-views' ),
					'minute'			=> __( 'Minutes can only be numbers between 0 and 59', 'wpv-views' ),
					'second'			=> __( 'Seconds can only be numbers between 0 and 59', 'wpv-views' ),
					'dayofyear'			=> __( 'Days of the year can only be numbers between 1 and 366', 'wpv-views' ),
					'dayofweek'			=> __( 'Days of the week can only be numbers between 1 and 7', 'wpv-views' ),
					'numeric_natural'	=> __( 'This needs to be a non-negative number', 'wpv-views' ),

				),
			),
			'warning'						=> array(

											),
			'parent_type_not_hierarchical'	=> __("The posts you want to display are not hierarchical, so this filter will not work", 'wpv-views'),
			'taxonomy_parent_changed'		=> __("The taxonomy you want to display has changed, so this filter needs some action", 'wpv-views'),
			'taxonomy_term_changed'			=> __("The taxonomy you want to display has changed, so this filter needs some action", 'wpv-views'),
			'add_filter_nonce'				=> wp_create_nonce( 'wpv_view_filters_add_filter_nonce' ),
			'nonce'							=> wp_create_nonce( 'wpv_view_filters_nonce' ),
		);
		wp_register_script(
			'views-filters-js',
			WPV_URL . "/res/js/redesign/views_section_filters.js",
			array( 'jquery', 'jquery-ui-dialog', 'views-utils-script', 'toolset-event-manager', 'underscore' ),
			WPV_VERSION,
			false
		);
		wp_localize_script( 'views-filters-js', 'wpv_filters_strings', $filters_strings );

		wp_register_script(
			'wpv-parametric-admin-script' ,
			WPV_URL . '/res/js/redesign/views_parametric.js',
			array( 'jquery', 'jquery-ui-dialog', 'toolset-utils', 'toolset-event-manager', 'underscore', 'icl_editor-script', 'views-codemirror-conf-script', 'views-shortcodes-gui-script' ),
			WPV_VERSION
		);
		$form_filters_shortcodes_data = apply_filters( 'wpv_filter_wpv_get_form_filters_shortcodes', array() );
		$form_filters_shortcodes = array_keys( $form_filters_shortcodes_data );
		$form_filter_shrtcodes_with_define_callback = array_filter( $form_filters_shortcodes_data, function( $filter_data ) {
			return array_key_exists( 'query_filter_define_callback', $filter_data );
		});
		$form_filter_shrtcodes_with_define_callback = array_keys( $form_filter_shrtcodes_with_define_callback );

		$current_admin_page = toolset_getget( 'page' );

		wp_localize_script(
			'wpv-parametric-admin-script',
			'wpv_parametric_i18n',
			array(
				'ajaxurl' => wpv_get_views_ajaxurl(),
				'nonce' => wp_create_nonce( 'wpv_parametric_general_nonce' ),
				'is_views_lite' => apply_filters( 'wpv_is_views_lite', false ),
				'is_legacy_editor' => in_array( $current_admin_page, array( 'views-editor', 'view-archives-editor' ), true ),
				'form_filters_shortcodes' => $form_filters_shortcodes,
				'form_filters_shortcodes_with_define_callback' => $form_filter_shrtcodes_with_define_callback,
				'generic'	=> array(
					'select_one'	=> __( 'Select one', 'wpv-views' ),
					'legacy_relationship' => __( 'Legacy relationship', 'wpv-views' ),
					'parent' => __( 'Parent', 'wpv-views' ),
					'child' => __( 'Child', 'wpv-views' )
				),
				'data' => array(
					'is_m2m_enabled' => apply_filters( 'toolset_is_m2m_enabled', false )
				),
				'dialogs'	=> array(
					'loading'	=> __( 'Loading', 'wpv-views' ),
					'close'		=> __( 'Close', 'wpv-views' ),
					'cancel'	=> __( 'Cancel', 'wpv-views' ),
					'dialog_select'	=> array(
						'title'	=> __( 'Insert a custom search filter', 'wpv-views' )
					),
				),
				'toolbar_buttons'	=> array(
					'text_search'	=> array(
						'nonce'		=> wp_create_nonce( 'wpv_view_filter_post_search_nonce' ),
						'dialog_title'	=> array(
							'create'	=> __( 'Text search filter', 'wpv-views' ),
							'complete'	=> __( 'Complete the search filter for this custom search', 'wpv-views' )
						),
						'warning'	=> array(
							'valid'		=> __( 'This View already has a valid search filter, but it is missing the text search shortcode. You can override the filter settings and add the shortcode here.', 'wpv-views' ),
							'specific'	=> __( 'This View already has a filter set to filter by a specific string. You can fix this filter here.', 'wpv-views' ),
							'missing'	=> __( 'This View already has a content text search, but the relevant filter is missing. You can add this filter here.', 'wpv-views' )
						),
						'tooltip'		=> array(
							'original'	=> __( 'You can add a text search to this form', 'wpv-views' ),
							'complete'	=> __( 'This form contains a text search already', 'wpv-views' ),
							'missing'	=> __( 'You have a text search in this form, click here to create the search filter that is missing', 'wpv-views' ),
							'wrong'		=> __( 'You have a text search in this form, but it is linked to a broken search filter', 'wpv-views' ),
						),
					),
					'submit'	=> array(
						'dialog_title'	=> __( 'Submit button for this custom search', 'wpv-views' ),
						'tooltip'		=> array(
							'original'		=> __( 'Use the submit button to get results based on the form values', 'wpv-views' ),
							'complete'		=> __( 'This form has a submit button already', 'wpv-views' ),
							'incomplete'	=> __( 'You need to add a submit button', 'wpv-views' ),
							'irrelevant'	=> __( 'You do not need a submit button in this form', 'wpv-views' ),
							'irrelevant_added' => __( 'You do not need a submit button in this form, although you already have one', 'wpv-views' )
						),
					),
					'reset'		=> array(
						'dialog_title'	=> __( 'Reset button for this custom search', 'wpv-views' ),
						'tooltip'		=> array(
							'original'		=> __( 'You can use a reset button that will take the search form to its original state', 'wpv-views' ),
							'complete'		=> __( 'This form has a reset button already', 'wpv-views' ),
							'incomplete'	=> __( 'You can add a reset button to this form', 'wpv-views' ),
							'irrelevant'	=> __( 'You do not need a reset button in this form', 'wpv-views' )
						),
					),
					'spinner'	=> array(
						'dialog_title'	=> __( 'Spinner container for this custom search', 'wpv-views' ),
						'tooltip'		=> array(
							'original'		=> __( 'You can use a spinner container that will be shown when performing any automatic update', 'wpv-views' ),
							'complete'		=> __( 'This form has a spinner container already', 'wpv-views' ),
							'useless'		=> __( 'No spinner container will be shown as this custom search is not performing any automatic update', 'wpv-views' ),
						)
					)
				)
			)
		);

		$inline_content_templates_translations = array(
			'pointer'		=> array(
								'close'					=> __( 'Close', 'wpv-views' ),
								'scroll_to_template'	=> __( 'Scroll to the Content Template', 'wpv-views' )
							),
			'dialog'		=> array(
								'cancel'				=> __( 'Cancel', 'wpv-views' ),
								'close'					=> __( 'Close', 'wpv-views' ),
								'remove'				=> __( 'Remove', 'wpv-views' ),
								'loading'				=> __( 'Loading', 'wpv-views' ),
								'unassign'				=> array(
															'view_title'	=> __( 'Remove the Content Template from the View', 'wpv-views' ),
															'wpa_title'		=> __( 'Remove the Content Template from the WordPress Archive', 'wpv-views' ),
															'action'		=> __( 'Remove', 'wpv-views' ),
														),
								'assign'				=> array(
															'view_title'	=> __( 'Assign a Content Template to this View', 'wpv-views' ),
															'wpa_title'		=> __( 'Assign a Content Template to this WordPress Archive', 'wpv-views' ),
															'action'		=> __( 'Assign Content Template', 'wpv-views' )
														),
								'saveAndGo' => array(
									'title' => __( 'There are some pending changes', 'wpv-views' ),
									'body' => __( 'Please save your recent changes before moving to edit this template.', 'wpv-views' ),
									'action' => __( 'Save and go', 'wpv-views' ),
								),
							),
			'error'			=> array(
								'name_in_use'			=> __( 'A Content Template with that name already exists. Please try with another name.', 'wpv-views' )
							),
			'settings'		=> array(
								'codemirror_autoresize'	=> apply_filters( 'wpv_filter_wpv_codemirror_autoresize', false ),
							)
		);

		$inline_content_templates_ajax_info = array(
			'ajax' => array(
				'action' => array(
					'add_inline_content_template' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_ADD_INLINE_CONTENT_TEMPLATE ),
				),
				'nonce' => array(
					'add_inline_content_template' => wp_create_nonce( WPV_Ajax::CALLBACK_ADD_INLINE_CONTENT_TEMPLATE ),
				),
			),
		);

		wp_register_script(
			'views-layout-template-js',
			WPV_URL . "/res/js/redesign/views_section_layout_template.js",
			array( 'jquery', 'underscore', 'views-codemirror-conf-script', 'toolset-event-manager' ),
			WPV_VERSION,
			false
		);

		wp_localize_script(
				'views-layout-template-js',
				'wpv_inline_templates_i18n',
				array_merge(
						$inline_content_templates_translations,
						$inline_content_templates_ajax_info
				)
		);

		$media_manager_translations = array(
			'only_img_allowed_here' => __( "You can only use an image file here", 'wpv-views' )
		);
		wp_register_script(
			'views-redesign-media-manager-js',
			WPV_URL . "/res/js/redesign/views_media_manager.js",
			array( 'jquery'),
			WPV_VERSION,
			false
		);
		wp_localize_script( 'views-redesign-media-manager-js', 'wpv_media_manager', $media_manager_translations );

		$layout_wizard_translations = array(
			'button_next' => __( 'Next', 'wpv-views' ),
			'button_insert' => __( 'Finish', 'wpv-views' ),
			'unknown_error' => __( 'Something wrong happened, please try again', 'wpv-views' ),
			'bootstrap_not_set' => __( 'You need to set the Bootstrap version used in your theme.', 'wpv-views' )
				. ' '
				.
				sprintf(
					// translators: Link in a Views Loop wizard that shows after "You need to set the Bootstrap version used in your theme."
					__( "<a href='%s' target='_blank'>Go to the Settings page &raquo;</a>", 'wpv-views' ),
					esc_url( add_query_arg( array( 'page' => 'toolset-settings' ), admin_url( 'admin.php' ) ) )
				),
			'bootstrap_2' => __( 'This site is using Bootstrap 2 (deprecated)', 'wpv-views' ),
			'bootstrap_3' => __( 'This site is using Bootstrap 3', 'wpv-views' ),
			'bootstrap_4' => __( 'This site is using Bootstrap 4', 'wpv-views' ),
			'bootstrap_not_used' => __( 'This site is not using Bootstrap CSS.', 'wpv-views' ),
			'wpnonce' => wp_create_nonce( 'wpv_loop_wizard_nonce' ),
		);

		$layout_wizard_ajax_info = array(
			'ajax' => array(
				'action' => array(
					'generate_view_loop_output' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_GENERATE_VIEW_LOOP_OUTPUT ),
					'create_layout_content_template' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_CREATE_LAYOUT_CONTENT_TEMPLATE ),
				),
				'nonce' => array(
					'generate_view_loop_output' => wp_create_nonce( WPV_Ajax::CALLBACK_GENERATE_VIEW_LOOP_OUTPUT ),
					'create_layout_content_template' => wp_create_nonce( WPV_Ajax::CALLBACK_CREATE_LAYOUT_CONTENT_TEMPLATE ),
				),
			),
		);

		wp_register_script(
			'views-layout-wizard-script' ,
			WPV_URL . '/res/js/redesign/views_layout_edit_wizard.js',
			array('jquery', 'views-layout-template-js', 'views-shortcodes-gui-script', 'toolset-colorbox'),
			WPV_VERSION,
			false
		);
		wp_localize_script( 'views-layout-wizard-script', 'wpv_layout_wizard_strings', array_merge( $layout_wizard_translations, $layout_wizard_ajax_info ) );

        // Reusable Content Template dialogs
		$views_ct_dialogs_texts = array(
            'dialog_cancel'						=> __( 'Cancel', 'wpv-views' ),
            'dialog_trash_warning_dialog_title'	=> __( 'Content Template in use', 'wpv-views' ),
            'dialog_trash_warning_action'		=> __( 'Trash', 'wpv-views' ),
            'view_listing_actions_nonce'		=> wp_create_nonce( 'wpv_view_listing_actions_nonce' )
        );
        wp_register_script(
			'views-ct-dialogs-js',
			WPV_URL . '/res/js/ct-dialogs.js',
			array( 'jquery', 'underscore', 'jquery-ui-dialog', 'views-utils-script', 'toolset-utils' ),
			WPV_VERSION
		);
        wp_localize_script( 'views-ct-dialogs-js', 'wpv_ct_dialogs_l10n', $views_ct_dialogs_texts );

        // Suggestion Script for Views edit screen
		// @todo deprecate this, FGS
		wp_register_script(
			'views-suggestion_script',
			WPV_URL . "/res/js/redesign/suggestion_script.js",
			array(),
			WPV_VERSION,
			false
		);
		wp_register_style( 'views_suggestion_style', WPV_URL . '/res/css/token-input.css', array(), WPV_VERSION );
		wp_register_style( 'views_suggestion_style2', WPV_URL . '/res/css/token-input-wpv-theme.css', array(), WPV_VERSION );

		// Listing JS

		$listing_common_i18n = array(
			'ajax' => array(
				'clearViewCache' => array(
					'action' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_CLEAR_VIEW_CACHE ),
					'nonce' => wp_create_nonce( WPV_Ajax::CALLBACK_CLEAR_VIEW_CACHE ),
					'replaceMessage' => __( 'Not cached', 'wpv-views' ),
				),
			),
		);
		wp_register_script(
			'views-listing-common-script' ,
			WPV_URL . '/res/js/redesign/wpv_listing_common.js',
			array( 'jquery', 'jquery-ui-dialog', 'views-utils-script' ),
			WPV_VERSION,
			true
		);
		wp_localize_script( 'views-listing-common-script', 'wpv_listing_common_i18n', $listing_common_i18n );

		$views_listing_texts = array(
			'dialog_cancel'                  => __( 'Cancel', 'wpv-views' ),
			'loading_options'                => __( 'Loading', 'wpv-views' ),
			'scan_no_results'                => __( 'Nothing found', 'wpv-views' ),
			'dialog_create_add_title_hint'   => __( 'Now give this View a name', 'wpv-views' ),// DEPRECATED
			'dialog_create_dialog_title'     => __( 'Add a new View', 'wpv-views' ),
			'dialog_create_action'           => __( 'Create View', 'wpv-views' ),
			'dialog_duplicate_dialog_title'  => __( 'Duplicate a View', 'wpv-views' ),
			'dialog_duplicate_action'        => __( 'Duplicate', 'wpv-views' ),
			'dialog_bulktrash_dialog_title'  => __( 'Trash Views', 'wpv-views' ),
			'dialog_bulktrash_action'        => __( 'Trash', 'wpv-views' ),
			'dialog_bulktrash_nonce'         => wp_create_nonce( 'wpv_view_listing_actions_nonce' ),
			'dialog_bulkdel_dialog_title'    => __( 'Delete Views', 'wpv-views' ),
			'dialog_bulkdel_action'          => __( 'Delete', 'wpv-views' ),
			'dialog_bulkdel_nonce'           => wp_create_nonce( 'wpv_bulk_remove_view_permanent_nonce' ),
			'is_views_lite'                  => apply_filters( 'wpv_is_views_lite', false ),
			'viewsLiteTooltipTitle'       => __( 'Full Views Feature', 'wpv-views' ),
			'tooltipPaginationDisabled'    => __( 'Display the results with pagination is not available in the Lite version. To use it, get the full Views.', 'wpv-views' ),
			'tooltipSliderDisabled'        => __( 'Display the results as a slider is not available in the Lite version. To use it, get the full Views.', 'wpv-views' ),
			'tooltipCustomSearchDisabled' => __( 'Display the results using a custom search is not available in the Lite version. To use it, get the full Views.', 'wpv-views' ),
			'tooltipPriceLinkTitle'       => __( 'Compare versions and buy', 'wpv-views' ),
			'tooltipPriceLinkURL'         => __( WPV_LITE_UPGRADE_LINK, 'wpv-views' )
		);

		$views_listing_callbacks = array(
			'ajax' => array(
				'action' => array(
					'scan_view_usage' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_SCAN_VIEW_USAGE ),
				),
				'nonce' => array(
					'scan_view_usage_nonce' => wp_create_nonce( WPV_Ajax::CALLBACK_SCAN_VIEW_USAGE ),
				),
			),
			'ajaxCallbacks' => array(
				'createView' => array(
					'action' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_CREATE_VIEW ),
					'nonce' => wp_create_nonce( WPV_Ajax::CALLBACK_CREATE_VIEW ),
				),
				'duplicateView' => array(
					'action' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_DUPLICATE_VIEW ),
					'nonce' => wp_create_nonce( WPV_Ajax::CALLBACK_DUPLICATE_VIEW ),
				),
			),
		);

		wp_register_script(
			'views-listing-script' ,
			WPV_URL . '/res/js/redesign/views_listing_page.js',
			array( 'jquery', 'views-listing-common-script', OTGS_Assets_Handles::POPOVER_TOOLTIP ),
			WPV_VERSION,
			true
		);
		wp_localize_script( 'views-listing-script', 'views_listing_texts', array_merge( $views_listing_texts, $views_listing_callbacks ) );

		/**
		 * Filters the edit URL of WordPress Archives posts displayed on the WordPress Archive listing page.
		 *
		 * @param string $edit_link
		 *
		 * @since 3.0
		 */
		$edit_url = apply_filters( 'wpv_filter_wpa_edit_link', admin_url( 'admin.php?page=view-archives-editor&amp;view_id=' ) );

		$wpa_listing_texts = array(
			'dialog_cancel'                                   => __( 'Cancel', 'wpv-views' ),
			'loading_options'                                 => __( 'Loading', 'wpv-views' ),
			'edit_url'                                        => $edit_url,
			'dialog_create_dialog_title'                      => __( 'Add a new WordPress Archive', 'wpv-views' ),
			'dialog_create_action'                            => __( 'Create WordPress Archive', 'wpv-views' ),
			'dialog_bulk_trash_dialog_title'                  => __( 'Trash WordPress Archives', 'wpv-views' ),
			'dialog_bulk_trash_action'                        => __( 'Trash', 'wpv-views' ),
			'dialog_delete_dialog_title'                      => __( 'Delete WordPress Archive', 'wpv-views' ),
			'dialog_bulk_delete_dialog_title'                 => __( 'Delete WordPress Archives', 'wpv-views' ),
			'dialog_delete_action'                            => __( 'Delete', 'wpv-views' ),
			'dialog_create_wpa_for_archive_loop_dialog_title' => __( 'Add a new WordPress Archive', 'wpv-views' ),
			'dialog_create_wpa_for_archive_loop_action'       => __( 'Create WordPress Archive', 'wpv-views' ),
			'dialog_bulktrash_nonce'                          => wp_create_nonce( 'wpv_view_listing_actions_nonce' ),
			'dialog_bulkdel_nonce'                            => wp_create_nonce( 'wpv_bulk_remove_view_permanent_nonce' ),
			'is_views_lite'                                   => apply_filters( 'wpv_is_views_lite', false ),
			'viewsLiteTooltipTitle'                           => __( 'Full Views Feature', 'wpv-views' ),
			'tooltipParametricDisabled'                       => __( 'Display the items using a custom search is not available in the Lite version. To use it, get the full Views.', 'wpv-views' ),
			'tooltipPriceLinkTitle'                           => __( 'Compare versions and buy', 'wpv-views' ),
			'tooltipPriceLinkURL'                             => __( WPV_LITE_UPGRADE_LINK, 'wpv-views' ) // TODO: add correct link here
		);

		$wpa_listing_ajax_info = array(
			'ajax' => array(
				'action' => array(
					'create_wordpress_archive' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_CREATE_WORDPRESS_ARCHIVE ),
				),
				'nonce' => array(
					'create_wordpress_archive' => wp_create_nonce( WPV_Ajax::CALLBACK_CREATE_WORDPRESS_ARCHIVE ),
				)
			),
		);

		wp_register_script(
			'views-archive-listing-script' ,
			WPV_URL . '/res/js/redesign/views_wordpress_archive_listing_page.js',
			array( 'jquery', 'views-listing-common-script' ),
			WPV_VERSION,
			true
		);

		wp_localize_script( 'views-archive-listing-script', 'wpa_listing_texts', array_merge( $wpa_listing_texts, $wpa_listing_ajax_info ) );

		$ct_listing_texts = array(
			'dialog_cancel'										=> __( 'Cancel', 'wpv-views' ),
			'dialog_update'										=> __( 'Update', 'wpv-views' ),
			'loading_options'									=> __( 'Loading', 'wpv-views' ),
			'scan_no_results'									=> __( 'Nothing found', 'wpv-views' ),
			'update_completed'									=> __( 'Update completed!', 'wpv-views' ),
			'action_nonce'										=> wp_create_nonce( 'wpv_view_listing_actions_nonce' ),
			'dialog_create_dialog_title'						=> __( 'Add new Content Template', 'wpv-views' ),
			'dialog_create_action'								=> __( 'Create Content Template', 'wpv-views' ),
			'dialog_duplicate_dialog_title'						=> __( 'Duplicate a Content Template', 'wpv-views' ),
			'dialog_duplicate_action'							=> __( 'Duplicate', 'wpv-views' ),
			'dialog_trash_warning_dialog_title'					=> __( 'Content Template in use', 'wpv-views' ), // todo remove
			'dialog_trash_warning_action'						=> __( 'Trash', 'wpv-views' ),
			'dialog_bulktrash_dialog_title'						=> __( 'Trash Content Templates', 'wpv-views' ),
			'dialog_bulktrash_action'							=> __( 'Trash', 'wpv-views' ),
			'dialog_bulktrash_nonce'							=> wp_create_nonce( 'wpv_view_listing_actions_nonce' ),
			'dialog_bulkdel_dialog_title'						=> __( 'Delete Content Template', 'wpv-views' ),
			'dialog_bulkdel_dialog_title_plural'				=> __( 'Delete Content Templates', 'wpv-views' ),
			'dialog_bulkdel_action'								=> __( 'Delete', 'wpv-views' ),
			'dialog_bulkdel_nonce'								=> wp_create_nonce( 'wpv_bulk_remove_view_permanent_nonce' ),
			'dialog_bind_ct_dialog_title'						=> __( 'Do you want to apply to all?', 'wpv-views' ),
			'dialog_change_ct_usage_dialog_title'				=> __( 'Change how this Content Template is used', 'wpv-views' ),
			'dialog_change_ct_usage_action'						=> __( 'Change usage', 'wpv-views' ),
			'dialog_unlink_dialog_title'						=> __( 'Clear a post type', 'wpv-views' ),
			'dialog_unlink_action'								=> __( 'Clear', 'wpv-views' ),
			'dialog_unlink_nonce'								=> wp_create_nonce( 'wpv_clear_cpt_from_ct_nonce' ),
			'dialog_change_ct_assigned_to_sth_dialog_title'		=> __( 'Change the Content Template assigned to this', 'wpv-views' ),
		);

		$ct_listing_ajax_info = array(
			'ajax' => array(
				'action' => array(
					'create_content_template' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_CREATE_CONTENT_TEMPLATE ),
					'duplicate_content_template' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_DUPLICATE_CONTENT_TEMPLATE ),
				),
				'nonce' => array(
					'create_content_template' => wp_create_nonce( WPV_Ajax::CALLBACK_CREATE_CONTENT_TEMPLATE ),
					'duplicate_content_template' => wp_create_nonce( WPV_Ajax::CALLBACK_DUPLICATE_CONTENT_TEMPLATE ),
				)
			),
		);

		wp_register_script(
			'views-content-template-listing-script' ,
			WPV_URL . '/res/js/redesign/wpv_content_template_listing.js',
			array('jquery', 'views-listing-common-script', 'views-ct-dialogs-js' ),
			WPV_VERSION,
			true
		);
		wp_localize_script( 'views-content-template-listing-script', 'ct_listing_texts', array_merge( $ct_listing_texts , $ct_listing_ajax_info ) );

		// Update help

		wp_register_script(
			'views-update-help-js', WPV_URL . '/res/js/views_admin_update_help.js',
			array( 'jquery' ),
			WPV_VERSION,
			false
		);

	}

	function wpv_admin_enqueue_scripts( $hook ) {// echo $hook; TODO this function needs a lot of love

		parent::wpv_admin_enqueue_scripts( $hook );

        $page = wpv_getget( 'page' );

        // Basic WordPress scripts & styles

		if ( ! wp_script_is( 'wp-pointer' ) ) {
			wp_enqueue_script('wp-pointer');
		}
		if ( ! wp_style_is( 'wp-pointer' ) ) {
			wp_enqueue_style('wp-pointer');
		}
		if ( ! wp_script_is( 'thickbox' ) ) {
			wp_enqueue_script('thickbox'); // TODO maybe DEPRECATED
		}
		if ( ! wp_style_is( 'thickbox' ) ) {
			wp_enqueue_style('thickbox'); // TODO maybe DEPRECATED
		}

		$wpv_custom_admin_pages = array(
			'views', 'view-archives', 'view-templates',
			'views-editor', 'view-archives-editor', WPV_CT_EDITOR_PAGE_NAME,
			// DEPRECATED:
			'views-settings', 'views-import-export', 'views-update-help',
			'toolset-dashboard',
		);
		$wpv_custom_admin_pages = apply_filters( 'wpv_filter_wpv_custom_admin_pages', $wpv_custom_admin_pages );

		$wpv_custom_admin_edit_pages = array( 'views-editor', 'view-archives-editor', WPV_CT_EDITOR_PAGE_NAME );
		$wpv_custom_admin_edit_pages = apply_filters( 'wpv_filter_wpv_custom_admin_edit_pages', $wpv_custom_admin_edit_pages );

		if (
			in_array( $page, $wpv_custom_admin_pages )
			|| strpos( $_SERVER['QUERY_STRING'], 'help.php') !== false
		) {
			if ( ! wp_script_is( 'views-utils-script' ) ) {
				wp_enqueue_script( 'views-utils-script');
			}
			if ( ! wp_style_is( 'views-admin-css' ) ) {
				wp_enqueue_style( 'views-admin-css' );
			}
		}
		if (
			$page == 'views'
			&& ! wp_script_is( 'views-listing-script' )
		) {
			wp_enqueue_script( 'views-listing-script' );
		}
		if (
			$page == 'view-archives'
			&& ! wp_script_is( 'views-archive-listing-script' )
		) {
			wp_enqueue_script( 'views-archive-listing-script' );
		}
		if (
			$page == 'view-templates'
			&& ! wp_script_is( 'views-content-template-listing-script' )
		) {
			wp_enqueue_script( 'views-content-template-listing-script' );
		}
		if ( $page == 'views-editor' ) {// TODO WTF is it doing here?
			delete_transient('wpv_layout_wizard_save_settings');
		}
		if ( in_array( $page, $wpv_custom_admin_edit_pages ) ) {
			// Custom edit pages need the shortcodes GUI and Codemirror
			if ( ! wp_script_is( 'views-shortcodes-gui-script' ) ) {
				wp_enqueue_script( 'views-shortcodes-gui-script' );
			}
			if ( ! wp_script_is( 'views-codemirror-conf-script' ) ) {
				wp_enqueue_script( 'views-codemirror-conf-script' );
			}
			if ( ! wp_style_is( 'toolset-meta-html-codemirror-css' ) ) {
				wp_enqueue_style( 'toolset-meta-html-codemirror-css' );
			}
			if ( ! wp_style_is( 'views-admin-css' ) ) {
				wp_enqueue_style( 'views-admin-css' );
			}
			// As we do not have a proper stylesheet for edit pages, adding here the frontend sorting controls previews inline styles.
			$style_options_for_list_controls = apply_filters( 'wpv_filter_wpv_get_styles_for_list_controls', array() );
			$css_options_for_list_controls_preview = '';
			foreach ( $style_options_for_list_controls as $style_option_slug => $style_option_data ) {
				$css_options_for_list_controls_preview .= ''
					. '.wpv-sort-list-dropdown.wpv-sort-list-dropdown-style-' . esc_attr( $style_option_slug ) . ' > span.wpv-sort-list,'
					. '.wpv-sort-list-dropdown.wpv-sort-list-dropdown-style-' . esc_attr( $style_option_slug ) . ' .wpv-sort-list-item {'
					. ( isset( $style_option_data['border-color'] ) ? ( 'border-color: ' . $style_option_data['border-color'] . ';' ) : '' )
					. '}'
					. '.wpv-sort-list-dropdown.wpv-sort-list-dropdown-style-' . esc_attr( $style_option_slug ) . ' .wpv-sort-list-item a {'
					. ( isset( $style_option_data['color'] ) ? ( 'color: ' . $style_option_data['color'] . ';' ) : '' )
					. ( isset( $style_option_data['background-color'] ) ? ( 'background-color: ' . $style_option_data['background-color'] . ';' ) : '' )
					. '}'
					. '.wpv-sort-list-dropdown.wpv-sort-list-dropdown-style-' . esc_attr( $style_option_slug ) . ' a:hover {'
					. ( isset( $style_option_data['color-hover'] ) ? ( 'color: ' . $style_option_data['color-hover'] . ';' ) : '' )
					. ( isset( $style_option_data['background-color-hover'] ) ? ( 'background-color: ' . $style_option_data['background-color-hover'] . ';' ) : '' )
					. '}'
					. '.wpv-sort-list-dropdown.wpv-sort-list-dropdown-style-' . esc_attr( $style_option_slug ) . ' .wpv-sort-list-item.wpv-sort-list-current a {'
					. ( isset( $style_option_data['color-current'] ) ? ( 'color: ' . $style_option_data['color-current'] . ';' ) : '' )
					. ( isset( $style_option_data['background-color-current'] ) ? ( 'background-color: ' . $style_option_data['background-color-current']  . ';' ) : '' )
					. '}'
					. '';

			}
			wp_add_inline_style( 'views-admin-css', $css_options_for_list_controls_preview );
			// Quicktags styles
			if ( ! wp_style_is( 'editor-buttons' ) ) {
				wp_enqueue_style( 'editor-buttons' );
			}
		}

		// Views help screen
		// @todo transform this into a real page

		if ( $hook == 'wp-views/menu/help.php' ) {
			wp_enqueue_style( 'views-admin-css' );
		}

		if ( 'views-editor' == $page ) {
			wp_enqueue_script( 'views-editor-js' );
			wp_enqueue_script( 'views-filters-js' );
			wp_enqueue_script( 'wpv-parametric-admin-script' );
			wp_enqueue_script( 'views-layout-template-js' );
			wp_enqueue_script( 'views-layout-wizard-script' );
			if (
				function_exists( 'wp_enqueue_media' )
				&& ! wp_script_is( 'icl_media-manager-js' )
			) {
				wp_enqueue_media();
				if ( ! wp_script_is( 'views-redesign-media-manager-js' ) ) {
					wp_enqueue_script( 'views-redesign-media-manager-js' );
				}
			}

			//Enqueue suggestion script
			wp_enqueue_script( 'views-suggestion_script' );
			wp_enqueue_style ('views_suggestion_style');
			wp_enqueue_style ('views_suggestion_style2');

			wp_enqueue_style( Toolset_Assets_Manager::STYLE_NOTIFICATIONS );

        }

		if ( 'view-archives-editor' == $page ) {
			wp_enqueue_script( 'views-archive-editor-js' );
			wp_enqueue_script( 'views-filters-js' );
			wp_enqueue_script( 'wpv-parametric-admin-script' );
			wp_enqueue_script( 'views-layout-template-js' );
			wp_enqueue_script( 'views-layout-wizard-script' );
			if (
				function_exists( 'wp_enqueue_media' )
				&& ! wp_script_is( 'icl_media-manager-js' )
			) {
				wp_enqueue_media();
				if ( ! wp_script_is( 'views-redesign-media-manager-js' ) ) {
					wp_enqueue_script( 'views-redesign-media-manager-js' );
				}
			}

			//Enqueue suggestion script
			wp_enqueue_script( 'views-suggestion_script' );
			wp_enqueue_style ('views_suggestion_style');
			wp_enqueue_style ('views_suggestion_style2');

			wp_enqueue_style( Toolset_Assets_Manager::STYLE_NOTIFICATIONS );

		}

		if ( $page == 'views-update-help' ) {
			wp_enqueue_script( 'views-update-help-js' );
		}

        // Types Dashboard
        if ( $page == 'toolset-dashboard' ) {
            wp_enqueue_style ( 'views-admin-dialogs-css' );
            wp_enqueue_style ( Toolset_Assets_Manager::STYLE_NOTIFICATIONS );

            wp_enqueue_script ( 'views-listing-script' );
            wp_enqueue_script ( 'views-archive-listing-script' );
        }
	}

}

