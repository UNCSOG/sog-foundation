<?php
/**
 * Plugin constants.
 *
 * @package Toolset Views
 * @since 3.0
 */

/**
 * Embedded directory.
 */
define( 'WPV_PATH_EMBEDDED', WPV_PATH . '/embedded' );

/**
 * Plugin folder name.
 */
define( 'WPV_FOLDER', basename( WPV_PATH ) );

/**
 * General URLs: root URL, embedded URL, frontend embedded URL.
 */
if (
	(
		defined( 'FORCE_SSL_ADMIN' )
		&& FORCE_SSL_ADMIN
	) || is_ssl()
) {
	define( 'WPV_URL', rtrim( str_replace( 'http://', 'https://', plugins_url() ), '/' ) . '/' . WPV_FOLDER );
} else {
	define( 'WPV_URL', plugins_url() . '/' . WPV_FOLDER );
}

define( 'WPV_URL_EMBEDDED', WPV_URL . '/embedded' );
if ( is_ssl() ) {
	define( 'WPV_URL_EMBEDDED_FRONTEND', WPV_URL_EMBEDDED );
} else {
	define( 'WPV_URL_EMBEDDED_FRONTEND', str_replace( 'https://', 'http://', WPV_URL_EMBEDDED ) );
}

/**
 * Views Lite.
 *
 * Note that the value if this constant is changed during the Views Lite build:
 * any change on WPV_LITE needs to be synced in ./make/build_lite.sh
 */
define( 'WPV_LITE', false );
define( 'WPV_LITE_UPGRADE_LINK', 'https://wpml.org/documentation/developing-custom-multilingual-sites/types-and-views-lite/' );

/**
 * Toolset Blocks
 */
if( ! defined( 'WPV_FLAVOUR' ) ) {
	define( 'WPV_FLAVOUR', 'blocks' );
}

/**
 * Space char used in documentation.
 */
if ( ! defined( 'WPV_MESSAGE_SPACE_CHAR' ) ) {
	define( 'WPV_MESSAGE_SPACE_CHAR', '&nbsp;' );
}

/**
 * Listing screens default items per page.
 */
define( 'WPV_ITEMS_PER_PAGE', 20 );

/**
 * Documentation links.
 */
if ( ! defined( 'WPV_LINK_CREATE_PAGINATED_LISTINGS' ) ) {
	define( 'WPV_LINK_CREATE_PAGINATED_LISTINGS', 'https://toolset.com/course-lesson/creating-a-view/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
}
if ( ! defined( 'WPV_LINK_CREATE_SLIDERS' ) ) {
	define( 'WPV_LINK_CREATE_SLIDERS', 'https://toolset.com/course-lesson/creating-sliders-with-dynamic-post-content/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
}
if ( ! defined( 'WPV_LINK_CREATE_PARAMETRIC_SEARCH' ) ) {
	define( 'WPV_LINK_CREATE_PARAMETRIC_SEARCH', 'https://toolset.com/course-lesson/creating-a-custom-search/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
}
if ( ! defined( 'WPV_LINK_DESIGN_SLIDER_TRANSITIONS' ) ) {
	define( 'WPV_LINK_DESIGN_SLIDER_TRANSITIONS', 'https://toolset.com/course-lesson/creating-sliders-with-dynamic-post-content/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
}
if ( ! defined( 'WPV_LINK_LOOP_DOCUMENTATION' ) ) {
	define( 'WPV_LINK_LOOP_DOCUMENTATION', 'https://toolset.com/documentation/user-guides/views/digging-into-view-outputs/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
}
if ( ! defined( 'WPV_LINK_CONTENT_TEMPLATE_DOCUMENTATION' ) ) {
	define( 'WPV_LINK_CONTENT_TEMPLATE_DOCUMENTATION', 'https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks' );
}
if ( ! defined( 'WPV_LINK_WORDPRESS_ARCHIVE_DOCUMENTATION' ) ) {
	define( 'WPV_LINK_WORDPRESS_ARCHIVE_DOCUMENTATION', 'https://toolset.com/course-lesson/creating-a-custom-archive-page/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks' );
}
if ( ! defined( 'WPV_LINK_FRAMEWORK_INTEGRATION_DOCUMENTATION' ) ) {
	// Deprecated in 3.3, link removed.
	define( 'WPV_LINK_FRAMEWORK_INTEGRATION_DOCUMENTATION', '' );
}

// Deprecated in 3.3, link removed.
define( 'WPV_SUPPORT_LINK', '' );

define( 'WPV_FILTER_BY_TAXONOMY_LINK', 'https://toolset.com/documentation/user-guides/filtering-views-by-taxonomy/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
define( 'WPV_FILTER_BY_CUSTOM_FIELD_LINK', 'https://toolset.com/documentation/user-guides/filtering-views-by-custom-fields/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
define( 'WPV_ADD_FILTER_CONTROLS_LINK', 'https://toolset.com/course-lesson/creating-a-custom-search/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
define( 'WPV_FILTER_BY_AUTHOR_LINK', 'https://toolset.com/documentation/user-guides/filtering-views-query-by-author/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
define( 'WPV_FILTER_BY_POST_PARENT_LINK', 'https://toolset.com/documentation/user-guides/displaying-brother-pages/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
define( 'WPV_FILTER_BY_SPECIFIC_TEXT_LINK', 'https://toolset.com/documentation/user-guides/views/filtering-views-for-a-specific-text-string-search/?utm_source=plugin&utm_medium=gui&utm_campaign=blocks' );
define( 'WPV_FILTER_BY_POST_ID_LINK', 'https://toolset.com/documentation/user-guides/filtering-views-query-by-post-id/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
define( 'WPV_FILTER_BY_USERS_LINK', 'https://toolset.com/documentation/user-guides/filtering-views-query-by-author/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
define( 'WPV_FILTER_BY_USER_FIELDS_LINK', 'https://toolset.com/documentation/user-guides/filtering-views-by-custom-fields/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
define( 'WPV_FILTER_BY_POST_DATE_LINK', 'https://toolset.com/documentation/user-guides/filtering-views-query-by-date/?utm_source=plugin&utm_medium=gui&utm_campaign=views' );
