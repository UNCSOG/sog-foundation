<?php
/**
 * Plugin compatibility layer for our own inline support plugin.
 *
 * @package Toolset Views
 * @since 3.0
 */

if ( did_action( 'inline_doc_help_viewquery' ) == 0 ) {
	do_action('inline_doc_help_viewquery', 'admin_screen_view_query_init');
}
if ( did_action( 'inline_doc_help_viewfilter' ) == 0 ) {
	do_action('inline_doc_help_viewfilter', 'admin_screen_view_filter_init');
}
if ( did_action( 'inline_doc_help_viewpagination' ) == 0 ) {
	do_action('inline_doc_help_viewpagination', 'admin_screen_view_pagination_init');
}
if ( did_action( 'inline_doc_help_viewlayout' ) == 0 ) {
	do_action('inline_doc_help_viewlayout', 'admin_screen_view_layout_init');
}
if ( did_action( 'inline_doc_help_viewlayoutmetahtml' ) == 0 ) {
	do_action('inline_doc_help_viewlayoutmetahtml', 'admin_screen_view_layoutmetahtml_init');
}
if ( did_action( 'inline_doc_help_viewtemplate' ) == 0 ) {
	do_action('inline_doc_help_viewtemplate', 'admin_screen_view_template_init');
}
