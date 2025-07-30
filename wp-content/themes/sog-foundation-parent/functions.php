<?php

/**
 * Include Theme Customizer.
 *
 * @since v1.0
 */
$theme_customizer = __DIR__ . '/inc/customizer.php';
if (is_readable($theme_customizer)) {
    require_once $theme_customizer;
}

if (! function_exists('sog_foundation_parent_setup_theme')) {
    /**
     * General Theme Settings.
     *
     * @since v1.0
     *
     * @return void
     */
    function sog_foundation_parent_setup_theme()
    {
        // Make theme available for translation: Translations can be filed in the /languages/ directory.
        load_theme_textdomain('sog-foundation-parent', __DIR__ . '/languages');

        /**
         * Set the content width based on the theme's design and stylesheet.
         *
         * @since v1.0
         */
        global $content_width;
        if (! isset($content_width)) {
            $content_width = 800;
        }

        // Theme Support.
        add_theme_support('title-tag');
        add_theme_support('automatic-feed-links');
        add_theme_support('post-thumbnails');
        add_theme_support(
            'html5',
            [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'script',
                'style',
                'navigation-widgets',
            ]
        );

        // Add support for Block Styles.
        add_theme_support('wp-block-styles');
        // Add support for full and wide alignment.
        add_theme_support('align-wide');
        // Add support for Editor Styles.
        add_theme_support('editor-styles');
        // Enqueue Editor Styles.
        add_editor_style('style-editor.css');

        // Default attachment display settings.
        update_option('image_default_align', 'none');
        update_option('image_default_link_type', 'none');
        update_option('image_default_size', 'large');

        // Custom CSS styles of WorPress gallery.
        add_filter('use_default_gallery_style', '__return_false');
    }
    add_action('after_setup_theme', 'sog_foundation_parent_setup_theme');

    // Disable Block Directory: https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/filters/editor-filters.md#block-directory
    remove_action('enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets');
    remove_action('enqueue_block_editor_assets', 'gutenberg_enqueue_block_editor_assets_block_directory');
}

if (! function_exists('wp_body_open')) {
    /**
     * Fire the wp_body_open action.
     *
     * Added for backwards compatibility to support pre 5.2.0 WordPress versions.
     *
     * @since v2.2
     *
     * @return void
     */
    function wp_body_open()
    {
        do_action('wp_body_open');
    }
}

if (! function_exists('sog_foundation_parent_add_user_fields')) {
    /**
     * Add new User fields to User profile:
     * get_user_meta( $user->ID, 'facebook_profile', true );
     *
     * @since v1.0
     *
     * @param array $fields User fields.
     *
     * @return array
     */
    function sog_foundation_parent_add_user_fields($fields)
    {
        // Add new fields.
        $fields['facebook_profile'] = 'Facebook URL';
        $fields['twitter_profile'] = 'Twitter URL';
        $fields['linkedin_profile'] = 'LinkedIn URL';
        $fields['xing_profile'] = 'Xing URL';
        $fields['github_profile'] = 'GitHub URL';

        return $fields;
    }
    add_filter('user_contactmethods', 'sog_foundation_parent_add_user_fields');
}

/**
 * Test if a page is a blog page.
 * if ( is_blog() ) { ... }
 *
 * @since v1.0
 *
 * @return bool
 */
function is_blog()
{
    global $post;
    $posttype = get_post_type($post);

    return ((is_archive() || is_author() || is_category() || is_home() || is_single() || (is_tag() && ('post' === $posttype))) ? true : false);
}

/**
 * Disable comments for Media (Image-Post, Jetpack-Carousel, etc.)
 *
 * @since v1.0
 *
 * @param bool $open    Comments open/closed.
 * @param int  $post_id Post ID.
 *
 * @return bool
 */
function sog_foundation_parent_filter_media_comment_status($open, $post_id = null)
{
    $media_post = get_post($post_id);

    if ('attachment' === $media_post->post_type) {
        return false;
    }

    return $open;
}
add_filter('comments_open', 'sog_foundation_parent_filter_media_comment_status', 10, 2);

/**
 * Style Edit buttons as badges: https://getbootstrap.com/docs/5.0/components/badge
 *
 * @since v1.0
 *
 * @param string $link Post Edit Link.
 *
 * @return string
 */
function sog_foundation_parent_custom_edit_post_link($link)
{
    return str_replace('class="post-edit-link"', 'class="post-edit-link badge bg-secondary"', $link);
}
add_filter('edit_post_link', 'sog_foundation_parent_custom_edit_post_link');

/**
 * Style Edit buttons as badges: https://getbootstrap.com/docs/5.0/components/badge
 *
 * @since v1.0
 *
 * @param string $link Comment Edit Link.
 */
function sog_foundation_parent_custom_edit_comment_link($link)
{
    return str_replace('class="comment-edit-link"', 'class="comment-edit-link badge bg-secondary"', $link);
}
add_filter('edit_comment_link', 'sog_foundation_parent_custom_edit_comment_link');

/**
 * Responsive oEmbed filter: https://getbootstrap.com/docs/5.0/helpers/ratio
 *
 * @since v1.0
 *
 * @param string $html Inner HTML.
 *
 * @return string
 */
function sog_foundation_parent_oembed_filter($html)
{
    return '<div class="ratio ratio-16x9">' . $html . '</div>';
}
add_filter('embed_oembed_html', 'sog_foundation_parent_oembed_filter', 10);

if (! function_exists('sog_foundation_parent_content_nav')) {
    /**
     * Display a navigation to next/previous pages when applicable.
     *
     * @since v1.0
     *
     * @param string $nav_id Navigation ID.
     */
    function sog_foundation_parent_content_nav($nav_id)
    {
        global $wp_query;

        if ($wp_query->max_num_pages > 1) {
            ?>
			<div id="<?php echo esc_attr($nav_id); ?>" class="d-flex mb-4 justify-content-between">
				<div><?php next_posts_link('<span aria-hidden="true">&larr;</span> ' . esc_html__('Older posts', 'sog-foundation-parent')); ?></div>
				<div><?php previous_posts_link(esc_html__('Newer posts', 'sog-foundation-parent') . ' <span aria-hidden="true">&rarr;</span>'); ?></div>
			</div><!-- /.d-flex -->
			<?php
        } else {
            echo '<div class="clearfix"></div>';
        }
    }

    /**
     * Add Class.
     *
     * @since v1.0
     *
     * @return string
     */
    function posts_link_attributes()
    {
        return 'class="btn btn-secondary btn-lg"';
    }
    add_filter('next_posts_link_attributes', 'posts_link_attributes');
    add_filter('previous_posts_link_attributes', 'posts_link_attributes');
}

/**
 * Init Widget areas in Sidebar.
 *
 * @since v1.0
 *
 * @return void
 */
function sog_foundation_parent_widgets_init()
{
    // Area 1.
    register_sidebar(
        [
            'name' => 'Primary Widget Area (Sidebar)',
            'id' => 'primary_widget_area',
            'before_widget' => '',
            'after_widget' => '',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ]
    );

    // Area 2.
    register_sidebar(
        [
            'name' => 'Secondary Widget Area (Header Navigation)',
            'id' => 'secondary_widget_area',
            'before_widget' => '',
            'after_widget' => '',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ]
    );

    // Area 3.
    register_sidebar(
        [
            'name' => 'Third Widget Area (Footer)',
            'id' => 'third_widget_area',
            'before_widget' => '',
            'after_widget' => '',
            'before_title' => '<h3 class="widget-title">',
            'after_title' => '</h3>',
        ]
    );
}
add_action('widgets_init', 'sog_foundation_parent_widgets_init');

if (! function_exists('sog_foundation_parent_article_posted_on')) {
    /**
     * "Theme posted on" pattern.
     *
     * @since v1.0
     */
    function sog_foundation_parent_article_posted_on()
    {
        printf(
            wp_kses_post(__('<span class="sep">Posted on </span><a href="%1$s" title="%2$s" rel="bookmark"><time class="entry-date" datetime="%3$s">%4$s</time></a><span class="by-author"> <span class="sep"> by </span> <span class="author-meta vcard"><a class="url fn n" href="%5$s" title="%6$s" rel="author">%7$s</a></span></span>', 'sog-foundation-parent')),
            esc_url(get_the_permalink()),
            esc_attr(get_the_date() . ' - ' . get_the_time()),
            esc_attr(get_the_date('c')),
            esc_html(get_the_date() . ' - ' . get_the_time()),
            esc_url(get_author_posts_url((int) get_the_author_meta('ID'))),
            sprintf(esc_attr__('View all posts by %s', 'sog-foundation-parent'), get_the_author()),
            get_the_author()
        );
    }
}

/**
 * Template for Password protected post form.
 *
 * @since v1.0
 *
 * @return string
 */
function sog_foundation_parent_password_form()
{
    global $post;
    $label = 'pwbox-' . (empty($post->ID) ? rand() : $post->ID);

    $output = '<div class="row">';
    $output .= '<form action="' . esc_url(site_url('wp-login.php?action=postpass', 'login_post')) . '" method="post">';
    $output .= '<h4 class="col-md-12 alert alert-warning">' . esc_html__('This content is password protected. To view it please enter your password below.', 'sog-foundation-parent') . '</h4>';
    $output .= '<div class="col-md-6">';
    $output .= '<div class="input-group">';
    $output .= '<input type="password" name="post_password" id="' . esc_attr($label) . '" placeholder="' . esc_attr__('Password', 'sog-foundation-parent') . '" class="form-control" />';
    $output .= '<div class="input-group-append"><input type="submit" name="submit" class="btn btn-primary" value="' . esc_attr__('Submit', 'sog-foundation-parent') . '" /></div>';
    $output .= '</div><!-- /.input-group -->';
    $output .= '</div><!-- /.col -->';
    $output .= '</form>';
    $output .= '</div><!-- /.row -->';

    return $output;
}
add_filter('the_password_form', 'sog_foundation_parent_password_form');

if (! function_exists('sog_foundation_parent_comment')) {
    /**
     * Style Reply link.
     *
     * @since v1.0
     *
     * @param string $class Link class.
     *
     * @return string
     */
    function sog_foundation_parent_replace_reply_link_class($class)
    {
        return str_replace("class='comment-reply-link", "class='comment-reply-link btn btn-outline-secondary", $class);
    }
    add_filter('comment_reply_link', 'sog_foundation_parent_replace_reply_link_class');

    /**
     * Template for comments and pingbacks:
     * add function to comments.php ... wp_list_comments( array( 'callback' => 'sog_foundation_parent_comment' ) );
     *
     * @since v1.0
     *
     * @param object $comment Comment object.
     * @param array  $args    Comment args.
     * @param int    $depth   Comment depth.
     */
    function sog_foundation_parent_comment($comment, $args, $depth)
    {
        $GLOBALS['comment'] = $comment;
        switch ($comment->comment_type) :
            case 'pingback':
            case 'trackback':
                ?>
		<li class="post pingback">
			<p>
				<?php
                    esc_html_e('Pingback:', 'sog-foundation-parent');
                comment_author_link();
                edit_comment_link(esc_html__('Edit', 'sog-foundation-parent'), '<span class="edit-link">', '</span>');
                ?>
			</p>
				<?php
                break;
            default:
                ?>
		<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
			<article id="comment-<?php comment_ID(); ?>" class="comment">
				<footer class="comment-meta">
					<div class="comment-author vcard">
						<?php
                            $avatar_size = ('0' !== $comment->comment_parent ? 68 : 136);
                echo get_avatar($comment, $avatar_size);

                /* Translators: 1: Comment author, 2: Date and time */
                printf(
                    wp_kses_post(__('%1$s, %2$s', 'sog-foundation-parent')),
                    sprintf('<span class="fn">%s</span>', get_comment_author_link()),
                    sprintf(
                        '<a href="%1$s"><time datetime="%2$s">%3$s</time></a>',
                        esc_url(get_comment_link($comment->comment_ID)),
                        get_comment_time('c'),
                        /* Translators: 1: Date, 2: Time */
                        sprintf(esc_html__('%1$s ago', 'sog-foundation-parent'), human_time_diff((int) get_comment_time('U'), current_time('timestamp')))
                    )
                );

                edit_comment_link(esc_html__('Edit', 'sog-foundation-parent'), '<span class="edit-link">', '</span>');
                ?>
					</div><!-- .comment-author .vcard -->

					<?php if ('0' === $comment->comment_approved) { ?>
						<em class="comment-awaiting-moderation">
							<?php esc_html_e('Your comment is awaiting moderation.', 'sog-foundation-parent'); ?>
						</em>
						<br />
					<?php } ?>
				</footer>

				<div class="comment-content"><?php comment_text(); ?></div>

				<div class="reply">
					<?php
                comment_reply_link(
                    array_merge(
                        $args,
                        [
                            'reply_text' => esc_html__('Reply', 'sog-foundation-parent') . ' <span>&darr;</span>',
                            'depth' => $depth,
                            'max_depth' => $args['max_depth'],
                        ]
                    )
                );
                ?>
				</div><!-- /.reply -->
			</article><!-- /#comment-## -->
				<?php
                break;
        endswitch;
    }

    /**
     * Custom Comment form.
     *
     * @since v1.0
     * @since v1.1: Added 'submit_button' and 'submit_field'
     * @since v2.0.2: Added '$consent' and 'cookies'
     *
     * @param array $args    Form args.
     * @param int   $post_id Post ID.
     *
     * @return array
     */
    function sog_foundation_parent_custom_commentform($args = [], $post_id = null)
    {
        if (null === $post_id) {
            $post_id = get_the_ID();
        }

        $commenter = wp_get_current_commenter();
        $user = wp_get_current_user();
        $user_identity = $user->exists() ? $user->display_name : '';

        $args = wp_parse_args($args);

        $req = get_option('require_name_email');
        $aria_req = ($req ? " aria-required='true' required" : '');
        $consent = (empty($commenter['comment_author_email']) ? '' : ' checked="checked"');
        $fields = [
            'author' => '<div class="form-floating mb-3">
							<input type="text" id="author" name="author" class="form-control" value="' . esc_attr($commenter['comment_author']) . '" placeholder="' . esc_html__('Name', 'sog-foundation-parent') . ($req ? '*' : '') . '"' . $aria_req . ' />
							<label for="author">' . esc_html__('Name', 'sog-foundation-parent') . ($req ? '*' : '') . '</label>
						</div>',
            'email' => '<div class="form-floating mb-3">
							<input type="email" id="email" name="email" class="form-control" value="' . esc_attr($commenter['comment_author_email']) . '" placeholder="' . esc_html__('Email', 'sog-foundation-parent') . ($req ? '*' : '') . '"' . $aria_req . ' />
							<label for="email">' . esc_html__('Email', 'sog-foundation-parent') . ($req ? '*' : '') . '</label>
						</div>',
            'url' => '',
            'cookies' => '<p class="form-check mb-3 comment-form-cookies-consent">
							<input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" class="form-check-input" type="checkbox" value="yes"' . $consent . ' />
							<label class="form-check-label" for="wp-comment-cookies-consent">' . esc_html__('Save my name, email, and website in this browser for the next time I comment.', 'sog-foundation-parent') . '</label>
						</p>',
        ];

        $defaults = [
            'fields' => apply_filters('comment_form_default_fields', $fields),
            'comment_field' => '<div class="form-floating mb-3">
											<textarea id="comment" name="comment" class="form-control" aria-required="true" required placeholder="' . esc_attr__('Comment', 'sog-foundation-parent') . ($req ? '*' : '') . '"></textarea>
											<label for="comment">' . esc_html__('Comment', 'sog-foundation-parent') . '</label>
										</div>',
            /** This filter is documented in wp-includes/link-template.php */
            'must_log_in' => '<p class="must-log-in">' . sprintf(wp_kses_post(__('You must be <a href="%s">logged in</a> to post a comment.', 'sog-foundation-parent')), wp_login_url(esc_url(get_the_permalink(get_the_ID())))) . '</p>',
            /** This filter is documented in wp-includes/link-template.php */
            'logged_in_as' => '<p class="logged-in-as">' . sprintf(wp_kses_post(__('Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>', 'sog-foundation-parent')), get_edit_user_link(), $user->display_name, wp_logout_url(apply_filters('the_permalink', esc_url(get_the_permalink(get_the_ID()))))) . '</p>',
            'comment_notes_before' => '<p class="small comment-notes">' . esc_html__('Your Email address will not be published.', 'sog-foundation-parent') . '</p>',
            'comment_notes_after' => '',
            'id_form' => 'commentform',
            'id_submit' => 'submit',
            'class_submit' => 'btn btn-primary',
            'name_submit' => 'submit',
            'title_reply' => '',
            'title_reply_to' => esc_html__('Leave a Reply to %s', 'sog-foundation-parent'),
            'cancel_reply_link' => esc_html__('Cancel reply', 'sog-foundation-parent'),
            'label_submit' => esc_html__('Post Comment', 'sog-foundation-parent'),
            'submit_button' => '<input type="submit" id="%2$s" name="%1$s" class="%3$s" value="%4$s" />',
            'submit_field' => '<div class="form-submit">%1$s %2$s</div>',
            'format' => 'html5',
        ];

        return $defaults;
    }
    add_filter('comment_form_defaults', 'sog_foundation_parent_custom_commentform');
}

if (function_exists('register_nav_menus')) {
    /**
     * Nav menus.
     *
     * @since v1.0
     *
     * @return void
     */
    register_nav_menus(
        [
            'main-menu' => 'Main Navigation Menu',
            'footer-menu' => 'Footer Menu',
        ]
    );
}

// Custom Nav Walker: wp_bootstrap_navwalker().
$custom_walker = __DIR__ . '/inc/wp-bootstrap-navwalker.php';
if (is_readable($custom_walker)) {
    require_once $custom_walker;
}

$custom_walker_footer = __DIR__ . '/inc/wp-bootstrap-navwalker-footer.php';
if (is_readable($custom_walker_footer)) {
    require_once $custom_walker_footer;
}

/**
 * Loading All CSS Stylesheets and Javascript Files.
 *
 * @since v1.0
 *
 * @return void
 */
function sog_foundation_parent_scripts_loader()
{
    $theme_version = wp_get_theme()->get('Version');

    // 1. Styles.
    wp_enqueue_style('style', get_theme_file_uri('style.css'), [], $theme_version, 'all');
    wp_enqueue_style('main', get_theme_file_uri('build/main.css'), [], $theme_version, 'all'); // main.scss: Compiled Framework source + custom styles.

    if (is_rtl()) {
        wp_enqueue_style('rtl', get_theme_file_uri('build/rtl.css'), [], $theme_version, 'all');
    }

    // 2. Scripts.
    wp_enqueue_script('mainjs', get_theme_file_uri('build/main.js'), [], $theme_version, true);

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}
add_action('wp_enqueue_scripts', 'sog_foundation_parent_scripts_loader');

/**
* Setup Theme Thumbnail Sizes
*
* @since v3.5.1
*
* @return void
*
* Register custom thumbnail sizes.
*/
function sog_foundation_parent_photo_sizes()
{
    add_image_size('8.5x11', 850, 1100, true); // word and pdf.
    add_image_size('16x9', 1024, 576, true); // powerpoint.
}

register_activation_hook(__FILE__, 'sog_foundation_parent_photo_sizes');


// Add Theme Settings page to WordPress admin
function parent_theme_add_admin_page() {
    add_menu_page(
        'Theme Settings',         // Page title
        'Theme Settings',         // Menu title
        'manage_options',         // Capability
        'parent_theme_settings',  // Menu slug
        'parent_theme_settings_page',  // Callback function
        'dashicons-admin-generic', // Icon
        100                        // Position
    );
}
add_action('admin_menu', 'parent_theme_add_admin_page');

// Settings page callback
function parent_theme_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Theme Settings', 'parent-theme'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('parent_theme_settings');
            do_settings_sections('parent_theme_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function parent_theme_register_custom_settings() {
    // Register settings
    register_setting('parent_theme_settings', 'selected_header');
    register_setting('parent_theme_settings', 'selected_footer');
    register_setting('parent_theme_settings', 'sidebar_enabled');

    // Add sections and fields for Headers, Footers, and Sidebar
    add_settings_section('parent_theme_layout_section', 'Layout Options', function() {}, 'parent_theme_settings');

    // Header selection
    add_settings_field(
        'selected_header',
        'Select Site Header',
        'parent_theme_header_selection_callback',
        'parent_theme_settings',
        'parent_theme_layout_section'
    );

    // Footer selection
    add_settings_field(
        'selected_footer',
        'Select Site Footer',
        'parent_theme_footer_selection_callback',
        'parent_theme_settings',
        'parent_theme_layout_section'
    );

    // Sidebar toggle
    add_settings_field(
        'sidebar_enabled',
        'Enable Sidebar for Content Type',
        'parent_theme_sidebar_toggle_callback',
        'parent_theme_settings',
        'parent_theme_layout_section'
    );
}
add_action('admin_init', 'parent_theme_register_custom_settings');

// Callbacks for each setting
function parent_theme_header_selection_callback() {
    $selected_header = get_option('selected_header');
    ?>
    <select name="selected_header">
        <option value="header1" <?php selected($selected_header, 'header1'); ?>>Header 1</option>
        <option value="header2" <?php selected($selected_header, 'header2'); ?>>Header 2</option>
        <option value="header3" <?php selected($selected_header, 'header3'); ?>>Header 3</option>
        <option value="header4" <?php selected($selected_header, 'header4'); ?>>Header 4</option>
        <option value="header5" <?php selected($selected_header, 'header5'); ?>>Header 5</option>
    </select>
    <?php
}

function parent_theme_footer_selection_callback() {
    $selected_footer = get_option('selected_footer');
    ?>
    <select name="selected_footer">
        <option value="footer1" <?php selected($selected_footer, 'footer1'); ?>>Footer 1</option>
        <option value="footer2" <?php selected($selected_footer, 'footer2'); ?>>Footer 2</option>
        <option value="footer3" <?php selected($selected_footer, 'footer3'); ?>>Footer 3</option>
    </select>
    <?php
}

function parent_theme_sidebar_toggle_callback() {
    $sidebar_enabled = get_option('sidebar_enabled');
    ?>
    <input type="checkbox" name="sidebar_enabled" value="1" <?php checked($sidebar_enabled, 1); ?> />
    <label for="sidebar_enabled">Enable sidebar for selected content types</label>
    <?php
}

