<?php
/**
 * Top section for Content Template edit page
 *
 * @package Views
 * @since 2.7
 */

/*
*************************************************************************
Render section content
*************************************************************************
*/

add_action( 'wpv_ct_editor_sections', 'wpv_ct_editor_top_section', 10 );

/**
 * Render the Content Template editor top section
 *
 * @param WPV_Content_Template $ct
 * @since 1.9
 */
function wpv_ct_editor_top_section( $ct ) {
	?>
	<div class="toolset-video-box-wrap"></div>
	<?php
}
