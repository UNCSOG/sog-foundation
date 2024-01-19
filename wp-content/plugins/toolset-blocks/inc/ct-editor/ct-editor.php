<?php
/**
 * Content template editor
 *
 * This is a starting point for new (1.9) CT edit page. All files or assets relevant to main functionality
 * should be referenced here.
 *
 * The page is rendered by wpv_ct_editor_page() with help of few filters and actions (described below).
 *
 * CT edit page expects that it's URL is "admin.php?page={$WPV_CT_EDITOR_PAGE_NAME}".
 *
 * @package Views
 * @since 1.9
 */

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * CT editor page name.
 *
 * This is the only place where this string should be hardcoded. Use the constant!
 *
 * @since 1.9
 */
define( 'WPV_CT_EDITOR_PAGE_NAME', 'ct-editor' );
define( 'WPV_CT_CREATOR_PAGE_NAME', 'ct-creator' );

add_action( 'admin_init', 'wpv_ct_editor_load_sections' );
add_action( 'admin_init', 'wpv_ct_editor_init' );


/**
 * Load Editor sections
 * runs on hook 'admin_init'
 *
 * @since 2.1
 */
function wpv_ct_editor_load_sections() {
	// Include additional Content Template Editor-related files.
	// All files within inc/ct-editor should be included here.
	$wpv_ct_editor_required_files = array(
		'section-content.php',
		'section-module-manager.php',
		'section-settings.php',
		'section-top.php',
		'section-top-bar.php',
		'section-usage.php',
	);

	foreach ( $wpv_ct_editor_required_files as $required_file ) {
		require_once plugin_dir_path( __FILE__ ) . $required_file;
	}
}

/**
 * Main init hook for the CT edit screen.
 *
 * @since 1.9
 */
function wpv_ct_editor_init() {
	global $pagenow;
	$page = wpv_getget( 'page' );

	if (
		'admin.php' === $pagenow
		&& WPV_CT_EDITOR_PAGE_NAME === $page
	) {

		// Force the Fields and Views dialog, as some CT integrations do not render the Template editor.
		add_filter( 'wpv_filter_wpv_force_generate_fields_and_views_dialog', '__return_true' );

		// Register main CT editor script
		// @todo remove the Colorbox dependency here, and move to using jQuery UI dialogs
		wp_register_script(
			'views-ct-editor-js',
			WPV_URL . "/res/js/ct-editor.js",
			array(
				'jquery',
				'wp-pointer',
				'underscore',
				'views-utils-script',
				'views-codemirror-conf-script',
				'views-redesign-media-manager-js',
				Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
				'icl_editor-script',
				'quicktags',
				'wplink',
				Toolset_Assets_Manager::SCRIPT_UTILS,
				'views-ct-dialogs-js',
				'toolset-uri-js',
				Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER,
				Toolset_Assets_Manager::SCRIPT_COLORBOX
			),
			WPV_VERSION,
			true
		);

		/**
		 * Gather localization data for the main script.
		 *
		 * Individual sections that need some l10n are supposed to hook onto this filter and append their
		 * localized strings for the wpv-ct-editor-js script.
		 *
		 * @param array $l10n_data Localization data. Each section is supposed to add an element identified by their
		 *     unique slug/name and put everything inside (it can be anything that can be encoded into JSON).
		 *
		 * @since 1.9
		 */
		$l10n_data = apply_filters( 'wpv_ct_editor_localize_script', array() );
		wp_localize_script( 'views-ct-editor-js', 'wpv_ct_editor_l10n', $l10n_data );

		// Remove query args when switching the editor to use to the legacy one.
		add_filter( 'removable_query_args', function( $query_vars ) {
			$query_vars[] = 'ct_origin';
			$query_vars[] = 'ct_editor_choice';
			$query_vars[] = 'reset';
			return $query_vars;
		} );
	}
}


// Note: Submenu entry for the Edit page is added in WP_Views_Plugin::admin_menu().


add_action( 'admin_enqueue_scripts', 'wpv_ct_editor_enqueue' );


/**
 * Register and enqueue assets for the CT edit page. Localize main script.
 *
 * This hook has lower than default priority, so we expect all the common Toolset and Views assets to be
 * already registered. No other assets should depend on this hook being executed, it's a leaf on the dependency tree.
 *
 * Main script is being registered here: wpv-ct-editor-js, located in /res/js/redesign/ct-editor.js.
 *
 * @since 1.9
 */
function wpv_ct_editor_enqueue() {
	global $pagenow;
	$page = wpv_getget( 'page' );

	// Enqueue only if we're on the right page.
	if ( 'admin.php' == $pagenow && WPV_CT_EDITOR_PAGE_NAME == $page ) {

		// We will have the "Add media" button.
		wp_enqueue_media();
		wp_enqueue_script( 'views-ct-editor-js' );

		wp_enqueue_style( 'views-admin-css' );
		wp_enqueue_style( 'toolset-meta-html-codemirror-css' );

		// icl_editor (CodeMirror) styles
		// @todo 'views-admin-css' wil also be dependant of the common 'editor_addon_menu' and 'editor_addon_menu_scroll'
		wp_enqueue_style( 'editor_addon_menu' );
		wp_enqueue_style( 'editor_addon_menu_scroll' );

	}
}

/**
* CT editor page hack for creating CT.
*
* On CT listing pages by usage we have methods for creating CT for a specific purpose, on the fly.
* This results in CT edit pages lacking a ct_id URL parameter, which is expected by many parties.
* This also results in inconsistencies since we have two pages for editing a CT and we might create a new one by just reloading the page.
*
* @note This is a hacky solution, we should implment an AJAX-based creation workflow.
* @note Since 2.6 we have a dedicated admin create page that redirects to the edit one,
*       to avoid problems with theme settings for integrated themes.
*
* @since 2.2
*/

add_action( 'wp_loaded', 'wpv_ct_editor_create_and_redirect' );

function wpv_ct_editor_create_and_redirect() {

	$page = wpv_getget( 'page', 'unset' );
	$action = wpv_getget( 'action', 'edit', array( 'edit', 'create' ) );

	if (
		$page == WPV_CT_CREATOR_PAGE_NAME
		&& $action == 'create'
	) {
		if( !current_user_can( EDIT_VIEWS ) ) {
			wpv_die_toolset_alert_error( __( 'You have no permission to access this page.', 'wpv-views' ) );
		}

		$title = urldecode( wpv_getget( 'title' ) );

		$usage = wpv_getget( 'usage' );
		if( !is_array( $usage ) ) {
			$usage = array();
		}

		$ct = wpv_ct_editor_page_create( $title, $usage );
		if( $ct instanceof WPV_Content_Template ) {
			$url = esc_url_raw(
					add_query_arg(
					array( 'page' => WPV_CT_EDITOR_PAGE_NAME, 'ct_id' => esc_attr( $ct->id ), 'action' => 'edit' ),
					admin_url( 'admin.php' )
				)
			);
			wp_redirect( $url );
		} else {
			wpv_die_toolset_alert_error( __( 'An error ocurred while creating a new Content Template.', 'wpv-views' ) );
		}

	}
}

add_action( 'wp_loaded', 'maybe_redirect_to_user_editor_page' );

/**
 * Force redirect to the Gutenberg user editor page when the CT is using (or set to use) it.
 * We leave user permissions management to the edit page itself.
 *
 * @since 2.8
 */
function maybe_redirect_to_user_editor_page() {
	$do_redirect = false;

	// Translated CT doesn't load proper editor.
	$default_lang = apply_filters( 'wpml_default_language', null );
	if ( toolset_getget( 'post' ) && toolset_getget( 'lang' ) && toolset_getget( 'lang' ) !== $default_lang ) {
		$ct_id = toolset_getget( 'post' );
		if ( get_post_type( $ct_id ) === 'view-template' ) {
			$user_editor = get_post_meta( $ct_id, WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE, true );
			if ( ! $user_editor ) {
				$original_post_id = apply_filters( 'wpml_object_id', $ct_id, 'view-template', true, $default_lang );
				update_post_meta( $ct_id, WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE, Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID );
			}
		}
	}
	if (
		! is_admin()
		|| WPV_CT_EDITOR_PAGE_NAME !== toolset_getget( 'page' )
	) {
		return;
	}

	$ct_id = (int) toolset_getget( 'ct_id', 0 );

	if ( $ct_id === 0 ) {
		return;
	}

	do_action( 'wpv_action_content_template_edit', $ct_id );

	if ( toolset_getarr( $_REQUEST, 'ct_editor_choice', false ) ) {
		$current_editor = get_post_meta( $ct_id, WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE, true );
		$editor_choice = toolset_getarr( $_REQUEST, 'ct_editor_choice' );
		if ( Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID === $editor_choice ) {
			$content_template = get_post( $ct_id );
			// Can't use wp_save_post_revision because it returns null.
			update_post_meta( $ct_id, 'wpv-ct-snapshot', $content_template->post_content );
			wpv_ct_transform_content_classic_to_blocks( $content_template );
			update_post_meta( $ct_id, WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE, Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID );
			$do_redirect = true;
		} elseif ( Toolset_User_Editors_Editor_Basic::BASIC_SCREEN_ID === $editor_choice ) {
			if ( Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID === toolset_getget( 'ct_origin' ) ) {
				$content_template = get_post( $ct_id );
				$snapshot = get_post_meta( $ct_id, 'wpv-ct-snapshot', true );
				delete_post_meta( $ct_id, 'wpv-ct-snapshot' );
				$do_reset = toolset_getget( 'reset' );
				$content_template->post_content = ( ! $snapshot || $do_reset ) ? '' : $snapshot;
				wp_update_post( $content_template );
			}
		}
	} else {
		$user_editor = get_post_meta( $ct_id, WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE, true );
		if ( Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID === $user_editor ) {
			$do_redirect = true;
		}
	}

	if ( $do_redirect ) {
		$url = esc_url_raw(
			add_query_arg(
				array( 'post' => $ct_id, 'action' => 'edit' ),
				admin_url( 'post.php' )
			)
		);
		wp_redirect( $url );
	}
}

/**
 * Transform classic content to blocks (Field and Text and Field blocks)
 *
 * When switching from CT classic editor to blocks, content is transformed into a Classic block but we can improve that creating some blocks instead
 *
 * @param int $content_template CT Post.
 * @since 3.0
 */
function wpv_ct_transform_content_classic_to_blocks( $content_template ) {
	if ( ! preg_match( '/<!-- wp:/', $content_template->post_content ) ) {
		$content_template->post_content = '<!-- wp:toolset-blocks/fields-and-text --><div class="tb-fields-and-text" data-toolset-blocks-fields-and-text="1">' . $content_template->post_content . '</div><!-- /wp:toolset-blocks/fields-and-text -->';
		wp_update_post( $content_template );
	}
}

/**
 * CT editor page handler.
 *
 * Based on the 'action' GET parameter, either create a new CT and show the edit page for it,
 * or show the edit page for an existing CT.
 *
 * For the 'create' action, following GET parameters are expected:
 * - title: Title of the new Content Template.
 * - usage: An associative array that can contains keys "single_post_types", "post_archives" and
 *   "taxonomy_archives" (others will be ignored) with arrays of post type or taxonomy slugs where this Content
 *   Template should be used. Only existing slugs are allowed (see WPV_Content_Template::_set_assigned_* methods).
 *
 * @since 1.9
 */
function wpv_ct_editor_page() {

	if ( ! current_user_can( EDIT_VIEWS ) ) {
		wpv_die_toolset_alert_error( __( 'You have no permission to access this page.', 'wpv-views' ) );
	}

	$action = wpv_getget( 'action', 'edit', array( 'edit', 'create' ) );

	switch ( $action ) {

		// show edit page
		case 'edit':
			$ct_id = (int) wpv_getget( 'ct_id' );

			// Set the global post as some third parties need to access it from get_the_ID();
			global $post;
			$post = get_post( $ct_id );

			wpv_ct_editor_page_edit( $ct_id );
			break;

		// create a new content template and continue to edit page on success.
		case 'create':
			$title = urldecode( wpv_getget( 'title' ) );

			$usage = wpv_getget( 'usage' );
			if ( ! is_array( $usage ) ) {
				$usage = array();
			}

			$ct = wpv_ct_editor_page_create( $title, $usage );
			if ( $ct instanceof WPV_Content_Template ) {
				wpv_ct_editor_page_edit( $ct );
			} else {
				wpv_die_toolset_alert_error( __( 'An error ocurred while creating a new Content Template.', 'wpv-views' ) );
			}

			break;
	}

}


/**
 * Fake function for the faked CT creator page.
 *
 * @return void
 *
 * @since 2.6.0
 */
function wpv_ct_creator_page() {}


/**
 * Handle creating a new Content Template with given parameters.
 *
 * @param string $title Title for the CT.
 * @param array $usage See wpv_ct_editor_page() description.
 * @return null|WPV_Content_Template A CT object or null if the creation has failed.
 *
 * @since 1.9
 */
function wpv_ct_editor_page_create( $title, $usage ) {

	

	// Create new Content Template
	$ct = WPV_Content_Template::create( $title );

	if ( ! $ct instanceof WPV_Content_Template ) {
		return null;
	}

	// Process the assignments to post types and taxonomies
	$single_post_types_assigned = wpv_ct_editor_assign_usage( $ct, 'single_post_types', $usage );
	$post_archives_assigned = wpv_ct_editor_assign_usage( $ct, 'post_archives', $usage );
	$taxonomy_archives_assigned = wpv_ct_editor_assign_usage( $ct, 'taxonomy_archives', $usage );

	if (
		! $single_post_types_assigned
		|| ! $post_archives_assigned
		|| ! $taxonomy_archives_assigned
	) {
		return null;
	}

	return $ct;
}


/**
 * Safely process assignment (setting the usage) of a Content Template.
 *
 * @param WPV_Content_Template $ct
 * @param string $assignment_type One of three possible assignment types: 'single_post_types', 'post_archives'
 *     or 'taxonomy_archives'.
 * @param $usage Array of existing post type or taxonomy slugs where this CT should be assigned.
 *
 * @return bool True on success, false on failure.
 *
 * @since 1.9
 */
function wpv_ct_editor_assign_usage( $ct, $assignment_type, $usage ) {

	$selected_items = wpv_getarr( $usage, $assignment_type, null );
	if ( is_array( $selected_items ) ) {
		try {
			$property_name = 'assigned_' . $assignment_type;
			$ct->$property_name = $selected_items;
			return true;
		} catch( Exception $e ) {
			return false;
		}
	} else {
		return true;
	}

}


/**
 * Render the editor page.
 *
 * Renders the individual sections, action bar with "Save all sections" button, collects Content Template properties
 * required by the sections (as a value of #js-wpv-ct) and creates a renders nonce for updating properties
 * ("wpv_ct_{$ct->id}_update_properties_by_{$uid}" stored as a value of #js-wpv-ct-update-nonce) for the main JS script.
 *
 * @param WPV_Content_Template|int Content Template object or ID.
 *
 * @since 1.9
 */
function wpv_ct_editor_page_edit( $ct ) {

	

	// Get the Content Template
	if ( ! $ct instanceof WPV_Content_Template ) {
		$ct = WPV_Content_Template::get_instance($ct);

		try {
			if ( null === $ct ) {
				throw new InvalidArgumentException( 'Invalid Content Template ID' );
			}
			$ct->post();
		} catch ( Exception $ex ) {
			// Either not existent ID or ID of a post that is not a CT was given
			wpv_die_toolset_alert_error( __( 'You attempted to edit a Content Template that doesn&#8217;t exist. Perhaps it was deleted?', 'wpv-views' ) );
		}
	}

	// Do not allow editing trashed CTs
	if ( 'trash' === $ct->post_status ) {
		wpv_die_toolset_alert_error( __( 'You can’t edit this Content Template because it is in the Trash. Please restore it and try again.', 'wpv-views' ) );
	}

	// Don't allow to edit CT translations
	if ( ! apply_filters( 'wpml_is_original_content', true, $ct->post()->ID, 'post_' . $ct->post()->post_type ) ) {
		wpv_die_toolset_alert_error( __( 'You are trying to edit a Content Template translation. Only original laguage can be edited here. Please edit the translation through WPML Translation Management.', 'wpv-views' ) );
	}

	// Wrapper for the edit page
	echo '<div class="wrap toolset-views toolset-views-editor js-toolset-views-editor">';

	echo '<hr class="wp-header-end"><!-- This item keeps admin notices in place -->';

	// Gather Content Template properties and pass them as l10n to JS.

	/**
	 * Gather names of Content Template properties that should be passed as a JSON to the main JS script.
	 *
	 * @param array $property_names Array of property names that can be retrieved from an instance of
	 *     WPV_Content_Template. If CT throws an exception while getting the property, null will be passed.
	 *
	 * @since 1.9
	 */
	$requested_property_names = array_unique( apply_filters( 'wpv_ct_editor_request_properties', array() ) );

	// Retrieve the requested properties into $ct_data.
	$ct_data = array( 'id' => $ct->id );
	foreach ( $requested_property_names as $property_name ) {
		try {
			$ct_data[ $property_name ] = $ct->$property_name;
		} catch ( Exception $e ) {
			$ct_data[ $property_name ] = null;
		}
	}

	// Add nonce for updating properties
	$uid = get_current_user_id();
	$ct_data['update_nonce'] = wp_create_nonce( "wpv_ct_{$ct->id}_update_properties_by_{$uid}" );
	$ct_data['trash_nonce'] = wp_create_nonce( 'wpv_view_listing_actions_nonce' );

	$ct_data['listing_page_url'] = esc_url( add_query_arg( array( 'page' => 'view-templates' ), admin_url( 'admin.php' ) ) );

	/**
	 * Allow individual sections to attach custom data to ct_data.
	 *
	 * @param array $ct_data Associative array with CT properties (keys are property names, obviously) or other custom
	 *     data attached by other page sections. Each section should choose keys that minimize the risk of conflict (e.g
	 *     prepend it by "_{$section_slug}_", etc.).
	 * @param WPV_Content_Template $ct Content Template to be edited.
	 *
	 * @since 1.9
	 */
	$ct_data = apply_filters( 'wpv_ct_editor_add_custom_properties', $ct_data, $ct );

	// Pass CT data as l10n variable.
	wp_localize_script( 'views-ct-editor-js', 'wpv_ct_editor_ct_data', $ct_data );

	/**
	 * Render the fixed top bar.
	 *
	 * The top bar contains the title, slug and description management,
	 * as well as triggers to move to trash and save the Content Template.
	 *
	 * @since 2.7
	 */
	do_action( 'wpv_ct_editor_top_bar', $ct );

	/**
	 * Render individual sections.
	 *
	 * Each section is supposed to hook onto this action and at some point render it's content by
	 * calling wpv_ct_editor_render_section().
	 *
	 * @since 1.9
	 */
	do_action( 'wpv_ct_editor_sections', $ct );

	// Wrapper end
	echo '</div>';
}


/**
 * Render CT editor section.
 *
 * All sections should use this method for rendering their final output, in order to reduce code redundancy.
 *
 * @param string $section_title Title of the section
 * @param string $class Class name to be added to div.wpv-settings-section, e.g. selector that's used by jQuery.
 * @param string $content HTML content of the section.
 * @param bool $wide_container If true, this section's container (main content) will get more space. This is
 *     meant mainly for sections with CodeMirror editors.
 * @param string $container_class Additional class(es) for the 'wpv-setting-container' div.
 * @param string $setting_class Additional class(es) for the 'wpv-setting' div.
 * @param null|array $pointer_args Optional arguments for rendering a pointer inside the section. Should contain
 *     'section' and 'pointer_slug' keys.
 * @param array $display Display options to show/hide parts of section. Allowed keys are 'title' and 'setting', while
 *     value should be either 'show' or 'hide'. Default is 'show'.
 *
 * @since 1.9
 */
function wpv_ct_editor_render_section(
	$section_title,
	$class,
	$content,
	$wide_container = false,
	$container_class = '',
	$setting_class = '',
	$pointer_args = null,
	$display = array()
) {
	$display = wp_parse_args(
		$display,
		array(
			'title' => 'show',
			'setting' => 'show',
		)
	);

	$container_class = $wide_container ? "$container_class wpv-setting-container-horizontal" : $container_class;

	foreach ( $display as $key => $value ) {
		if ( 'hide' === $value ) {
			$container_class .= sprintf( ' wpv-setting-container-no-%s', $key );
		}
	}

	$pointer = '';
	if ( is_array( $pointer_args ) ) {
		$pointer_section = wpv_getarr( $pointer_args, 'section', null );
		$pointer_slug = wpv_getarr( $pointer_args, 'pointer_slug', null );
		if (
			null !== $pointer_section
			&& null !== $pointer_slug
		) {
			$pointer = sprintf(
				' <i class="icon-question-sign fa fa-question-circle js-wpv-show-pointer" data-section="%s" data-pointer-slug="%s"></i>',
				esc_attr( $pointer_section ),
				esc_attr( $pointer_slug )
			);
		}
	}

	?>

	<div class="wpv-settings-section <?php echo $class; ?> hidden">
		<div class="wpv-setting-container <?php echo $container_class; ?>">
			<?php
			if ( 'hide' != $display['title'] ) {
				?>
				<div class="wpv-settings-header">
					<h2><?php echo $section_title . $pointer ?></h2>
				</div>
				<?php
			}

			if ( 'hide' !== $display['setting'] ) {
				?>
				<div class="wpv-setting <?php echo $setting_class; ?>">
					<?php echo $content; ?>
				</div>
				<?php
			}
			?>
		</div>
	</div>

	<?php
}


add_filter( 'wpv_ct_editor_localize_script', 'wpv_ct_editor_general_localize_script' );

/**
 * Add general CT edit page localizations for the main JS script.
 *
 * Slug "editor" is used.
 *
 * @param array $l10n_data Localization data
 * @return array Updated localization data.
 *
 * @since 1.9
 */
function wpv_ct_editor_general_localize_script( $l10n_data ) {
	$wpv_ajax = WPV_Ajax::get_instance();
    $l10n_data['editor'] = array(
        'saved' => __( 'Content template saved', 'wpv-views' ),
        'unsaved' => __( 'Content template not saved', 'wpv-views' ),
        'pending_changes' => __( 'There are some unsaved changes.', 'wpv-views' ),
        'confirm_unload' => __( 'You have entered new data on this page.', 'wpv-views' ),
        'pointer_close' => __( 'Close', 'wpv-views' ),
		'ajax' => array(
			'action' => array(
				'update_content_template_properties' => $wpv_ajax->get_action_js_name( WPV_Ajax::CALLBACK_UPDATE_CONTENT_TEMPLATE_PROPERTIES ),
			),
			'nonce' => array(
				'update_content_template_properties' => wp_create_nonce( WPV_Ajax::CALLBACK_UPDATE_CONTENT_TEMPLATE_PROPERTIES ),
			),
		),
    );
    return $l10n_data;
}

/* ************************************************************************* *\
		WPML integration
\* ************************************************************************* */


//add_filter( 'wpml_translation_validation_data', 'wpv_ct_wpml_translation_validation_data', 10, 2 );

/**
 *
 * Details about the filter here: https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1626#comment=102-34759
 *
 * @param array $validation_results
 * @param array $data_to_validate
 * @return mixed
 */
/*function wpv_ct_wpml_translation_validation_data( $validation_results, $data_to_validate ) {

	$validation_results['messages'][] = "Test message";
	$validation_results['is_valid'] = false;

	return $validation_results;
}*/



/* ************************************************************************* *\
		Helper functions
\* ************************************************************************* */


/**
 * Render a link leading to Content Template edit page.
 *
 * @param int $ct_id ID of the Content Template.
 * @param string $label Link label (content of the a tag).
 * @param bool $echo If true (default), echoes the link HTML.
 * @return string Link HTML.
 *
 * @since 1.9
 */
function wpv_ct_editor_render_link( $ct_id, $label, $echo = true ) {

	$link = sprintf(
		'<a href="%s">%s</a>',
		wpv_ct_editor_url( $ct_id, false ),
		$label
	);

	if( $echo ) {
		echo $link;
	}

	return $link;
}


/**
 * Render an URL leading to Content Template edit page.
 *
 * @param int $ct_id ID of the Content Template.
 * @param bool $echo If true, echoes the URL. Default is false
 * @return string URL.
 *
 * @since 1.9
 */
function wpv_ct_editor_url( $ct_id, $echo = false ) {
	$url = esc_url_raw(
			add_query_arg(
			array( 'page' => WPV_CT_EDITOR_PAGE_NAME, 'ct_id' => esc_attr( $ct_id ), 'action' => 'edit' ),
			admin_url( 'admin.php' )
		)
	);

	if( $echo ) {
		echo $url;
	}
	return $url;
}


add_filter( 'icl_post_link', 'wpv_ct_post_link', 10, 4 );


/**
 * Adjust link to Content Template edit page for full Views.
 *
 * See icl_post_link for parameter description.
 *
 * @param $link
 * @param $post_type
 * @param $post_id
 * @param $link_purpose
 * @return array
 *
 * @since 1.10
 */
function wpv_ct_post_link( $link, $post_type, $post_id, $link_purpose ) {
	global $WP_Views;
	if( !$WP_Views->is_embedded() && ( WPV_Content_Template_Embedded::POST_TYPE == $post_type ) && ( 'edit' == $link_purpose ) ) {
		// Full Views, CT edit link is requested
		if( !is_array( $link ) ) {
			$link = array();
		}

		// If CT is trashed or non-existent, disable the link.
		$ct = WPV_Content_Template::get_instance( $post_id );
		if( ( null == $ct ) || $ct->is_trashed ) {
			$link['is_disabled'] = true;
		} else {
			$link['is_disabled'] = false;
			$link['url'] = wpv_ct_editor_url( $post_id, false );
		}
	}
	return $link;
}
