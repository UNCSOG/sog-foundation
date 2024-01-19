<?php
/**
 * Class WPV_Page_Slug
 *
 * todo: Actually the class provides Screen ID constants instead of page slugs. We need to refactor it, so that we can
 * have a class that offers real page slugs as well.
 */

class WPV_Page_Slug {

	const VIEWS_LISTING_PAGE_DEPRECATED = 'toplevel_page_views'; //DEPRECATED
	const VIEWS_LISTING_PAGE = 'toolset_page_views';
	const VIEWS_EDIT_PAGE_DEPRECATED = 'views_page_views-editor'; //DEPRECATED
	const VIEWS_EDIT_PAGE = 'toolset_page_views-editor';
	const CONTENT_TEMPLATES_LISTING_PAGE_DEPRECATED = 'views_page_view-templates'; //DEPRECATED
	const CONTENT_TEMPLATES_LISTING_PAGE = 'toolset_page_view-templates';
	const CONTENT_TEMPLATES_EDIT_PAGE_DEPRECATED = 'views_page_ct-editor'; //DEPRECATED
	const CONTENT_TEMPLATES_EDIT_PAGE = 'toolset_page_ct-editor';
	const WORDPRESS_ARCHIVES_LISTING_PAGE_DEPRECATED = 'views_page_view-archives'; //DEPRECATED
	const WORDPRESS_ARCHIVES_LISTING_PAGE = 'toolset_page_view-archives';
	const WORDPRESS_ARCHIVES_EDIT_PAGE_DEPRECATED = 'views_page_view-archives-editor'; //DEPRECATED
	const WORDPRESS_ARCHIVES_EDIT_PAGE = 'toolset_page_view-archives-editor';
	const VIEWS_SETTINGS_DEPRECATED = 'views_page_views-settings'; //DEPRECATED
	const VIEWS_IMPORT_EXPORT_DEPRECATED = 'views_page_views-import-export'; //DEPRECATED
	const VIEWS_FRAMEWORK_INTEGRATION_DEPRECATED = 'views_page_views-framework-integration'; //DEPRECATED

	public static function all() {
		return array(
			self::VIEWS_LISTING_PAGE_DEPRECATED,
			self::VIEWS_LISTING_PAGE,
			self::VIEWS_EDIT_PAGE_DEPRECATED,
			self::VIEWS_EDIT_PAGE,
			self::CONTENT_TEMPLATES_LISTING_PAGE_DEPRECATED,
			self::CONTENT_TEMPLATES_LISTING_PAGE,
			self::CONTENT_TEMPLATES_EDIT_PAGE_DEPRECATED,
			self::CONTENT_TEMPLATES_EDIT_PAGE,
			self::WORDPRESS_ARCHIVES_LISTING_PAGE_DEPRECATED,
			self::WORDPRESS_ARCHIVES_LISTING_PAGE,
			self::WORDPRESS_ARCHIVES_EDIT_PAGE_DEPRECATED,
			self::WORDPRESS_ARCHIVES_EDIT_PAGE,
			self::VIEWS_SETTINGS_DEPRECATED,
			self::VIEWS_IMPORT_EXPORT_DEPRECATED,
			self::VIEWS_FRAMEWORK_INTEGRATION_DEPRECATED,
		);
	}
}
