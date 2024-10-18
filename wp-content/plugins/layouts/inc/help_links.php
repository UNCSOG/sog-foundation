<?php

define ( 'WPDDL_CSS_STYLING_LINK', 'https://toolset.com/documentation/legacy-features/toolset-layouts/adding-custom-styling-to-a-layout/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDDL_GLOBAL_JS_LINK', 'https://toolset.com/documentation/legacy-features/toolset-layouts/layouts-css-and-js-editor/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_LEARN_ABOUT_SETTING_UP_TEMPLATE', 'https://toolset.com/documentation/legacy-features/toolset-layouts/layouts-theme-integration/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_LEARN_ABOUT_ROW_MODES', 'https://toolset.com/documentation/legacy-features/toolset-layouts/learn-how-rows-can-displayed-different-ways/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_LEARN_ABOUT_GRIDS', 'https://toolset.com/documentation/legacy-features/toolset-layouts/learn-creating-using-grids/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_RICH_CONTENT_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/rich-content-cell-text-images-html/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_WIDGET_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/widget-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_CHILD_LAYOUT_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/hierarchical-layouts/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_THEME_INTEGRATION_QUICK', 'https://toolset.com/documentation/legacy-features/toolset-layouts/layouts-theme-integration/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_CONTENT_TEMPLATE_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/content-template-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_VIEWS_CONTENT_GRID_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/view-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_VIEWS_LOOP_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/wordpress-archive-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_COMMENTS_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/comments-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define ( 'WPDLL_CRED_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/cred-form-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts' );
define('WPDLL_WIDGET_AREA_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/widget-area-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');
define('WPDLL_ACCORDION_CELL_HELP', 'https://toolset.com/documentation/legacy-features/toolset-layouts/accordion-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');
define('WPDLL_TABS_CELL_HELP', 'https://toolset.com/documentation/legacy-features/toolset-layouts/tabs-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');
define('WPDLL_FRONT_EDITOR', 'https://toolset.com/documentation/legacy-features/toolset-layouts/front-end-editing/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');
define('WPDLL_POST_CONTENT_CELL', 'https://toolset.com/documentation/legacy-features/toolset-layouts/the-content-cell/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');
define('WPDLL_PRIVATE_LAYOUT', 'https://toolset.com/documentation/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');
define('WPDLL_PARENT_LAYOUT', 'https://toolset.com/documentation/legacy-features/toolset-layouts/hierarchical-layouts/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');
define('WPDLL_LAYOUTS_STARTER', 'https://toolset.com/documentation/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');
define( 'WPDDL_VIEWS_ARCHIVE_LEARN', "https://toolset.com/course-lesson/creating-a-custom-archive-page/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts");
define( 'WPDDL_DISPLAY_POST_TYPES_LEARN', "https://toolset.com/course-lesson/creating-templates-to-display-custom-posts/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts");
define( 'WPDDL_BOOTSTRAP_GRID_SIZE', 'https://toolset.com/documentation/legacy-features/toolset-layouts/selecting-the-base-size-of-your-layouts-grid/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');
define( 'WPDDL_CRED_EDIT_FORMS', 'https://toolset.com/course-lesson/front-end-forms-for-editing-content/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts');


function ddl_add_help_link_to_dialog($link, $text, $same_line = false) {
    if( $same_line == false ):?>
        <div class="clear"></div>
    <?php
    endif;
    ?>
            <a href="<?php echo $link; ?>" target="_blank" class="ddl-help-link-link">
                <?php echo $text; ?> &raquo;
            </a>

    <?php
    if( $same_line == false ):?>
        <div class="clear"></div>
        <?php
    endif;
}

