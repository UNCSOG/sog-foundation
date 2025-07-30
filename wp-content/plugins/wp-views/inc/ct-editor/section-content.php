<?php

/**
 * Content section for Content Template edit page
 *
 * @since 1.9
 */


/* ************************************************************************* *\
        Request WPV_Content_Template properties for the JS side
\* ************************************************************************* */


add_filter( 'wpv_ct_editor_request_properties', 'wpv_ct_editor_content_section_request_properties' );


function wpv_ct_editor_content_section_request_properties( $property_names ) {
    return array_merge( $property_names, array( 'content', 'template_extra_css', 'template_extra_js' ) );
}


/* ************************************************************************* *\
        Request CRED assets for this page
\* ************************************************************************* */


/**
 * Determine if CRED version is high enough to support CRED button on the CT edit page.
 *
 * @return bool True if we should render the button.
 * @since 1.9
 */
function wpv_ct_editor_is_cred_button_supported() {
    $last_unsupporting_cred_version = '1.3.6.1';

    if( !defined( 'CRED_FE_VERSION' ) ) {
        // seems like no CRED at all
        return false;
    }

    // true if current CRED version is HIGHER than 1.3.6.1.
    return ( version_compare( CRED_FE_VERSION, $last_unsupporting_cred_version ) == 1 );
}



add_filter( 'cred_get_custom_pages_to_load_assets', 'wpv_ct_editor_content_section_request_cred_assets' );

/**
 * Tell CRED that it should load it's assets to display the CRED Forms button
 * on CT edit page, too.
 *
 * @param array $set_on_pages Page names where CRED assets should be loaded.
 * @return array The modified array of page names.
 * @since 1.9
 */
function wpv_ct_editor_content_section_request_cred_assets( $set_on_pages ) {
    if( wpv_ct_editor_is_cred_button_supported() ) {
        if (!is_array($set_on_pages)) {
            $set_on_pages = array();
        }
        $set_on_pages[] = WPV_CT_EDITOR_PAGE_NAME;
    }
    return $set_on_pages;
}


/* ************************************************************************* *\
        Localize the section in JS
\* ************************************************************************* */


add_filter( 'wpv_ct_editor_localize_script', 'wpv_ct_editor_content_section_localize_script' );


function wpv_ct_editor_content_section_localize_script( $l10n_data ) {
    $l10n_data['content_section'] = array(
        'saved' => esc_attr( __( 'Template updated.', 'wpv-views' ) ),
        'unsaved' => esc_attr(__( 'Template not saved.', 'wpv-views' ) ),
        'ptr_section' => array(
            'title' => __( 'Template', 'wpv-views' ),
            'paragraphs' => array(
                __( 'Add fields to the template to display the content. Use HTML tags for styling.', 'wpv-views' )
            )
        ),
		'codemirror_autoresize' => apply_filters( 'wpv_filter_wpv_codemirror_autoresize', false )
    );
    return $l10n_data;
}


/* ************************************************************************* *\
        Render section content
\* ************************************************************************* */

add_action( 'wpv_ct_editor_sections', 'wpv_ct_editor_content_section_template_section', 20 );

function wpv_ct_editor_content_section_template_section() {
	$editor_select = apply_filters( 'toolset_user_editors_backend_html_editor_select', '' );
    $content = apply_filters( 'toolset_user_editors_backend_html_active_editor', false );
    if( $content ) {
        wpv_ct_editor_render_section(
            __( 'Template', 'wpv-views' ),
            'js-wpv-content-section',
            $editor_select . $content,
            true,
            '',
            '',
            array( 'section' => 'content_section', 'pointer_slug' => 'ptr_section' )
        );
    }
}


/**
 * Render media button for a CodeMirror editor.
 *
 * @param int $ct_id Content Template ID
 * @param string $context Editor context (id of the underlying textarea)
 *
 * @since 1.9
 */
function wpv_ct_editor_content_add_media_button( $ct_id, $context ) {
    ?>
    <li>
        <button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager"
                data-id="<?php echo $ct_id; ?>" data-content="<?php echo $context; ?>">
            <i class="icon-picture fa fa-picture-o"></i>
            <span class="button-label"><?php _e( 'Media', 'wpv-views' ); ?></span>
        </button>
    </li>
    <?php
}