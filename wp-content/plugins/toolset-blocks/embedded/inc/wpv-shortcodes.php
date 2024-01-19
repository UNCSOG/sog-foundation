<?php

require_once( dirname( __FILE__ ) . "/classes/wpv-wp-filter-state.class.php" );
require_once( dirname( __FILE__ ) . "/classes/wpv-render-filters.class.php" );

/**
 * Array of shortcodes that will be offered in the Views dialog popup.
 *
 * Each element must be an array with three elements:
 * 1. shortcode slug
 * 2. shortcode display name
 * 3. callback function
 *
 * @since unknown
 */
global $wpv_shortcodes;

$wpv_shortcodes = array();

$wpv_shortcodes['wpv-comment-title'] = array('wpv-comment-title', __('Comment title', 'wpv-views'), 'wpv_shortcode_wpv_comment_title');
$wpv_shortcodes['wpv-comment-body'] = array('wpv-comment-body', __('Comment body', 'wpv-views'), 'wpv_shortcode_wpv_comment_body');
$wpv_shortcodes['wpv-comment-author'] = array('wpv-comment-author', __('Comment Author', 'wpv-views'), 'wpv_shortcode_wpv_comment_author');
$wpv_shortcodes['wpv-comment-date'] = array('wpv-comment-date', __('Comment Date', 'wpv-views'), 'wpv_shortcode_wpv_comment_date');

$wpv_shortcodes['wpv-bloginfo'] = array('wpv-bloginfo', __('Site information', 'wpv-views'), 'wpv_bloginfo');
$wpv_shortcodes['wpv-search-term'] = array('wpv-search-term', __('Search term', 'wpv-views'), 'wpv_search_term');
$wpv_shortcodes['wpv-archive-title'] = array('wpv-archive-title', __('Archive title', 'wpv-views'), 'wpv_archive_title');
$wpv_shortcodes['wpv-archive-link'] = array('wpv-archive-link', __('Post archive link', 'wpv-views'), 'wpv_archive_link');

//User shortcodes
$wpv_shortcodes['wpv-current-user'] = array('wpv-current-user', __('Current user info', 'wpv-views'), 'wpv_current_user');
$wpv_shortcodes['wpv-user'] = array('wpv-user', __('Show user data', 'wpv-views'), 'wpv_user');
$wpv_shortcodes['wpv-login-form'] = array('wpv-login-form', __('Login form', 'wpv-views'), 'wpv_shortcode_wpv_login_form');
$wpv_shortcodes['wpv-logout-link'] = array('wpv-logout-link', __('Logout link', 'wpv-views'), 'wpv_shortcode_wpv_logout_link');
$wpv_shortcodes['wpv-forgot-password-form'] = array('wpv-forgot-password-form', __('Forgot password form', 'wpv-views'), 'wpv_shortcode_wpv_forgot_password_form');
$wpv_shortcodes['wpv-forgot-password-link'] = array('wpv-forgot-password-link', __('Forgot password link', 'wpv-views'), 'wpv_shortcode_wpv_forgot_password_link');
$wpv_shortcodes['wpv-reset-password-form'] = array('wpv-reset-password-form', __('Reset password form', 'wpv-views'), 'wpv_shortcode_wpv_reset_password_form');

if (defined('WPV_WOOCOMERCE_VIEWS_SHORTCODE')) {
	$wpv_shortcodes['wpv-wooaddcart'] = array('wpv-wooaddcart', __('Add to cart button', 'wpv-views'), 'wpv-wooaddcart');
}
if (defined('WPV_WOOCOMERCEBOX_VIEWS_SHORTCODE')) {
	$wpv_shortcodes['wpv-wooaddcartbox'] = array('wpv-wooaddcartbox', __('Add to cart box', 'wpv-views'), 'wpv-wooaddcartbox');
}

// register the short codes
foreach ($wpv_shortcodes as $shortcode) {
	if (function_exists($shortcode[2])) {
		add_shortcode($shortcode[0], $shortcode[2]);
	}
}

/**
 * Views-Shortcode: wpv-bloginfo
 *
 * Description: Display bloginfo values.
 *
 * Parameters:
 * 'show' => parameter for show.
 *   "name" displays site title (Ex. "Testpilot")(Default)
 *   "description" displays tagline (Ex. Just another WordPress blog)
 *   "admin_email" displays (Ex. admin@example.com)
 *   "url" displays site url (Ex. http://example/home)
 *   "wpurl" displays home url (Ex. http://example/home/wp)
 *   "stylesheet_directory" displays stylesheet directory (Ex. http://example/home/wp/wp-content/themes/child-theme)
 *   "stylesheet_url" displays stylesheet url (Ex. http://example/home/wp/wp-content/themes/child-theme/style.css)
 *   "template_directory" displays template directory (Ex. http://example/home/wp/wp-content/themes/parent-theme)
 *   "template_url" displays template url (Ex. http://example/home/wp/wp-content/themes/parent-theme)
 *   "atom_url" displays url to feed in atom format (Ex. http://example/home/feed/atom)
 *   "rss2_url" displays url to feed in rss2 format (Ex. http://example/home/feed)
 *   "rss_url" displays url to feed in rss format (Ex. http://example/home/feed/rss)
 *   "pingback_url" displays pingback url (Ex. http://example/home/wp/xmlrpc.php)
 *   "rdf_url" displays rdf url(Ex. http://example/home/feed/rdf)
 *   "comments_atom_url" displays comments atom url (Ex. http://example/home/comments/feed/atom)
 *   "comments_rss2_url" displays comments rss2 url (Ex. http://example/home/comments/feed)
 *   "charset" displays site charset (Ex. UTF-8)
 *   "html_type" displays site html type (Ex. text/html)
 *   "language" displays site language (Ex. en-US)
 *   "text_direction" displays site text direction (Ex. ltr)
 *   "version" displays WordPress version (Ex. 3.1)
 *
 * Example usage:
 * url: [vpw-bloginfo show="url"]
 *
 * Link:
 * List of available parameters <a href="http://codex.wordpress.org/Function_Reference/bloginfo#Parameters">http://codex.wordpress.org/Function_Reference/bloginfo#Parameters</a>
 *
 * Note:
 *
 */

function wpv_bloginfo( $atts ){

	$atts = shortcode_atts(
		array(
			'show' => 'name'
		),
		$atts
	);
	$out = '';

	switch ( $atts['show'] ) {
		case 'name':
		case 'description':
		case 'admin_email':
		case 'url':
		case 'wpurl':
		case 'stylesheet_directory':
		case 'stylesheet_url':
		case 'template_directory':
		case 'template_url':
		case 'atom_url':
		case 'rss2_url':
		case 'rss_url':
		case 'pingback_url':
		case 'rdf_url':
		case 'comments_atom_url':
		case 'comments_rss2_url':
		case 'charset':
		case 'html_type':
		case 'language':
		case 'version':
			$out = get_bloginfo( $atts['show'], 'display' );
			break;
		case 'text_direction':
			if ( function_exists( 'is_rtl' ) ) {
                $out = is_rtl() ? 'rtl' : 'ltr';
            } else {
                $out = 'ltr';
            }
			break;
		default:
			$out = '';
			break;
	}

	apply_filters( 'wpv_shortcode_debug','wpv-bloginfo', json_encode( $atts ), '', 'Data received from cache', $out );
	return $out;
}

/**
* wpv_shortcodes_register_wpv_bloginfo_data
*
* Register the wpv-bloginfo shortcode in the GUI API.
*
* @since 1.9
*/

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_bloginfo_data' );

function wpv_shortcodes_register_wpv_bloginfo_data( $views_shortcodes ) {
	$views_shortcodes['wpv-bloginfo'] = array(
		'callback' => 'wpv_shortcodes_get_wpv_bloginfo_data'
	);
	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_bloginfo_data() {
    $data = array(
        'name' => __( 'Site information', 'wpv-views' ),
        'label' => __( 'Site information', 'wpv-views' ),
        'attributes' => array(
            'display-options' => array(
                'label' => __('Display options', 'wpv-views'),
                'header' => __('Display options', 'wpv-views'),
                'fields' => array(
                    'show' => array(
                        'label' => __( 'Show this information', 'wpv-views'),
                        'type' => 'select',
                        'options' => array(
                            'name' => __( 'Site name', 'wpv-views' ),
							'description' => __( 'Site description', 'wpv-views' ),
							'admin_email' => __( 'Administration email', 'wpv-views' ),
							'url' => __( 'Site address (URL)', 'wpv-views' ),
							'wpurl' => __( 'WordPress address (URL)', 'wpv-views' ),
							'stylesheet_directory' => __( 'Stylesheet directory URL of the active theme', 'wpv-views' ),
                            'stylesheet_url' => __( 'Primary CSS file URL of the active theme', 'wpv-views' ),
							'template_directory' => __( 'URL of the active theme\'s directory', 'wpv-views' ),
							'atom_url' => __( 'Atom feed URL', 'wpv-views' ),
							'rss2_url' => __( 'RSS 2.0 feed URL', 'wpv-views' ),
                            'rss_url' => __( 'RSS 0.92 feed URL', 'wpv-views' ),
							'pingback_url' => __( 'Pingback XML-RPC file URL', 'wpv-views' ),
							'rdf_url' => __( 'RDF/RSS 1.0 feed URL', 'wpv-views' ),
							'comments_atom_url' => __( 'Comments Atom feed URL ', 'wpv-views' ),
							'comments_rss2_url' => __( 'Comments RSS 2.0 feed URL', 'wpv-views' ),
                            'charset' => __( 'Encoding for pages and feeds', 'wpv-views' ),
							'html_type' => __( 'Content-Type of WordPress HTML pages', 'wpv-views' ),
							'language' => __( 'Language', 'wpv-views' ),
							'text_direction' => __( 'Text direction', 'wpv-views' ),
							'version' => __( 'WordPress version', 'wpv-views' )
                        ),
                        'default' => 'name',
						'documentation' => '<a href="http://codex.wordpress.org/Function_Reference/bloginfo" target="_blank">' . __( 'WordPress bloginfo function', 'wpv-views' ) . '</a>'
                    ),
                ),
            ),
        ),
    );
    return $data;
}

/**
 * Views-Shortcode: wpv-search-term
 *
 * Description: Display search term value
 *
 * Parameters:
 * 'param' => Default = s
 *
 * Example usage:
 * url: [wpv-search-term param="my-field"]
 *
 */

function wpv_search_term( $attr ) {
	extract(
		shortcode_atts(
			array(
				'param' => 's',
				'separator' => ', '
			),
			$attr
		)
	);
	$out = '';
	if ( isset( $_GET[$param] ) ) {
		$term = $_GET[$param];
		if ( is_array( $term ) ) {
			$out = implode( $separator, $term );
		} else {
			$out = $term;
		}
		$out = esc_attr( urldecode( wp_unslash( $out ) ) );
	}
	return $out;
}

/**
* wpv_shortcodes_register_wpv_search_term_data
*
* Register the wpv-search-term shortcode in the GUI API.
*
* @since 1.9
*/

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_search_term_data' );

function wpv_shortcodes_register_wpv_search_term_data( $views_shortcodes ) {
	$views_shortcodes['wpv-search-term'] = array(
		'callback' => 'wpv_shortcodes_get_wpv_search_term_data'
	);
	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_search_term_data() {
	$data = array(
        'name' => __( 'Search term', 'wpv-views' ),
        'label' => __( 'Search term', 'wpv-views' ),
        'attributes' => array(
            'display-options' => array(
                'label' => __('Display options', 'wpv-views'),
                'header' => __('Display options', 'wpv-views'),
                'fields' => array(
                    'param' => array(
                        'label' => __( 'URL parameter', 'wpv-views'),
                        'type' => 'text',
						'description' => __( 'Watch this URL parameter. Defaults to "s", which is the natural search parameter.', 'wpv-views' ),
						'default' => 's'
                    ),
					'separator' => array(
                        'label' => __( 'Separator when multiple', 'wpv-views'),
                        'type' => 'text',
						'default' => ', ',
						'description' => __( 'When there are more than one values on that URL parameter, display this separator between them.', 'wpv-views' )
                    ),
                ),
            ),
        ),
    );
	return $data;
}

/**
 * Views-Shortcode: wpv-archive-title
 *
 * Description: Display archive title for current type of archive.
 *
 * Parameters: None
 *
 * Example usage:
 * At title of the archive. [wpv-archive-title]
 *
 * Link:
 *
 * Note: Inspired partly by https://developer.wordpress.org/reference/functions/the_archive_title/
 *
 */
function wpv_archive_title( $attr ) {
	$out = '';

	$page_for_posts = get_option( 'page_for_posts' );

	if (
		is_home()
		&& $page_for_posts
	) {
		$out = get_the_title( $page_for_posts );
	} else if ( function_exists( 'get_the_archive_title' ) /* WP 4.1+ */ ) {
        $out = get_the_archive_title();
    } else {
        $out = wpv_get_the_archive_title();
    }

    apply_filters( 'wpv_shortcode_debug', 'wpv-archive-title', json_encode( $attr ), '', '', $out );

    return $out;
}

/**
 * Views-Shortcode: wpv-archive-link
 *
 * Description: Display archive link for selected post type.
 *
 * Parameters:
 * 'name' => post_type_name for show (Default = current post type).
 *
 * Example usage:
 * Archive link for places is on [wpv-archive-link name="places"]
 *
 * Link:
 *
 * Note:
 *
 */
function wpv_archive_link($attr){
	extract(
		shortcode_atts( array('name' => ''), $attr )
	);
	$out = '';
	if($name != ''){
		$out  = get_post_type_archive_link($name);
	}
	if($out==''){
		global $post;// @todo check if instaceof Post
		if(isset($post->post_type) and $post->post_type!=''){
			$out = get_post_type_archive_link($post->post_type);
		}
	}
	apply_filters('wpv_shortcode_debug','wpv-archive-link', json_encode($attr), '', '', $out);
	return $out;
}

/**
* wpv_shortcodes_register_wpv_archive_link_data
*
* Register the wpv-archive-link shortcode in the GUI API.
*
* @since 1.9
*/

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_archive_link_data' );

function wpv_shortcodes_register_wpv_archive_link_data( $views_shortcodes ) {
	$views_shortcodes['wpv-archive-link'] = array(
		'callback' => 'wpv_shortcodes_get_wpv_archive_link_data'
	);
	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_archive_link_data() {
	$options = array(
		'' => __( 'Current post', 'wpv-views' )
	);
	$post_types_with_archive = get_post_types(
		array(
			'public' => true,
			'has_archive' => true
		),
		'objects'
	);
    foreach ( $post_types_with_archive as $post_type_slug => $post_type_data ) {
        $options[$post_type_slug] = $post_type_data->labels->singular_name;
    }
    $data = array(
        'name' => __( 'Link to WordPress archive page', 'wpv-views' ),
        'label' => __( 'Link to WordPress archive page', 'wpv-views' ),
        'attributes' => array(
            'display-options' => array(
                'label' => __('Display options', 'wpv-views'),
                'header' => __('Display options', 'wpv-views'),
                'fields' => array(
                    'name' => array(
                        'label' => __( 'Post type archive', 'wpv-views'),
                        'type' => 'select',
                        'options' => $options,
						'default' => '',
						'description' => __( 'Display the link to the selected post type archive page', 'wpv-views' )
                    ),
                ),
            ),
        ),
    );
    return $data;
}

/**
 * Views-Shortcode: wpv-current-user
 *
 * Description: Display information about current user.
 *
 * Parameters:
 * 'info' => parameter for show.
 *   "display_name" displays user's display name (Default)
 *   "login" displays user's login
 *   "firstname" displays user's first name
 *   "lastname" displays user's last name
 *   "email" displays user's email
 *   "id" displays user's user_id
 *   "logged_in" displays true if user is logged in, false if not
 *   "role" displays user's role
 *
 * Example usage:
 * Current user is [wpv-current-user info="display_name"]
 *
 * Link:
 *
 * Note:
 *
 * @since 2.4.0 Added the option to use [wpv-user field="profile_picture"] to fetch the user profile picture. The "field"
 *              attribute of the shortcode can take several values. If those values match a user column, we get that data.
 *              If not, we default to a usermeta field with that key. The "profile_picture" for the "field" attribute is
 *              neither a user column nor a usermeta field key, so we are reserving this value for a purpose that has no
 *              database match.
 *
 */

function wpv_current_user($attr){
	global $current_user;

    $default_size = 96;

    extract(
        $attr = shortcode_atts(
            array(
                'info' => 'display_name',
                'profile-picture-size' => $default_size,
                'profile-picture-default-url' => '',
                'profile-picture-alt' => false,
                'profile-picture-shape' => 'circle',
            ),
            $attr
        )
    );

	$out = '';

	if ( $current_user->ID > 0 ) {
		switch ($info) {
			case 'login':
				$out = $current_user->user_login;
				break;
			case 'firstname':
				$out = $current_user->user_firstname;
				break;
			case 'lastname':
				$out = $current_user->user_lastname;
				break;
			case 'email':
				$out = $current_user->user_email;
				break;
			case 'id':
				$out = $current_user->ID;
				break;
			case 'display_name':
				$out = $current_user->display_name;
				break;
            case 'profile_picture':
                $out = wpv_get_avatar( $current_user->ID, $attr['profile-picture-size'], $attr['profile-picture-default-url'], $attr['profile-picture-alt'], $attr['profile-picture-shape'] );
                break;
			case 'logged_in':
				$out = 'true';
				break;
			case 'role':
				if (
					isset( $current_user->roles )
					&& is_array( $current_user->roles )
					&& isset( $current_user->roles[0] )
				) {
					$out = $current_user->roles[0];
				}
				break;
			default:
				$out = $current_user->display_name;
				break;
		}
	} else {
		switch ($info) {
			case 'logged_in':
				$out = 'false';
				break;
			default:
				$out = '';
				break;
		}
	}
	apply_filters('wpv_shortcode_debug','wpv-current-user', json_encode($attr), '', 'Data received from cache', $out);
	return $out;
}

/**
* wpv_shortcodes_register_wpv_current_user_data
*
* Register the wpv-current-user shortcode in the GUI API.
*
* @since 1.9
*/

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_current_user_data' );

function wpv_shortcodes_register_wpv_current_user_data( $views_shortcodes ) {
	$views_shortcodes['wpv-current-user'] = array(
		'callback' => 'wpv_shortcodes_get_wpv_current_user_data'
	);
	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_current_user_data() {
    $data = array(
        'name' => __( 'Current user information', 'wpv-views' ),
        'label' => __( 'Current user information', 'wpv-views' ),
        'attributes' => array(
            'display-options' => array(
                'label' => __('Display options', 'wpv-views'),
                'header' => __('Display options', 'wpv-views'),
                'fields' => array(
                    'info' => array(
                        'label' => __( 'Information', 'wpv-views'),
                        'type' => 'radio',
                        'options' => array(
                            'display_name'	=> __('Display name', 'wpv-views'),
							'firstname'		=> __('First name', 'wpv-views'),
							'lastname'		=> __('Last name', 'wpv-views'),
							'login'			=> __('User Login Name', 'wpv-views'),
                            'email'			=> __('Email', 'wpv-views'),
                            'id'			=> __('User ID', 'wpv-views'),
                            'logged_in'		=> __('Logged in', 'wpv-views'),
                            'role'			=> __('User role', 'wpv-views'),
                            'profile_picture' => __( 'Profile picture', 'wpv-views' ),
                        ),
                        'default' => 'display_name',
						'description' => __( 'Display the selected information for the current user', 'wpv-views' ),
						'documentation' => '<a href="http://codex.wordpress.org/Function_Reference/get_userdata" target="_blank">' . __( 'WordPress get_userdata function', 'wpv-views' ) . '</a>'
                    ),
                    'profile-picture-size' => array(
                        'label' => __( 'Size', 'wpv-views' ),
                        'type' => 'text',
                        'description' => __( 'Size of the current user\'s profile picture in pixels.', 'wpv-views' ),
                    ),
                    'profile-picture-alt' => array(
                        'label' => __( 'Alternative text', 'wpv-views' ),
                        'type' => 'text',
                        'description' => __( 'Alternative text for the current user\'s profile picture.', 'wpv-views' ),
                    ),
                    'profile-picture-shape' => array(
                        'label' => __( 'Shape', 'wpv-views'),
                        'type' => 'select',
                        'options' => array(
                            'circle' => __( 'Circle', 'wpv-views' ),
                            'square' => __( 'Square', 'wpv-views' ),
                            'custom' => __( 'Custom', 'wpv-views' ),
                        ),
                        'default' => 'circle',
                        'description' => __( 'Display the current user\'s profile picture in this shape. For "custom" shape, custom CSS is needed for "wpv-profile-picture-shape-custom" CSS class.', 'wpv-views' ),
                    ),
                    'profile-picture-default-url' => array(
                        'label' => __( 'Default URL', 'wpv-views' ),
                        'type' => 'text',
                        'description' => __( 'Default url for an image. Leave blank for the "Mystery Man".', 'wpv-views' )
                    ),
                ),
            ),
        ),
    );
    return $data;
}

/**
 * Views-Shortcode: wpv-login-form
 *
 * Description: Display WordPress login form.
 *
 * Parameters:
 *  "redirect_url" redirects to this URL after successful login. Absolute URL.
 *  "allow_remember" displays the "Remember me" feature (checkbox)
 *  "remember_default" sets "allow_remember" checked status by default
 *
 * Example usage:
 *  [wpv-if evaluate="[wpv-current-user info="logged_in"]" condition="true"]
 *  [/wpv-if]
 *  [wpv-login-form]
 *
 * Link:
 *
 * Note:
 *  Façade for http://codex.wordpress.org/Function_Reference/wp_login_form
 */
function wpv_shortcode_wpv_login_form( $atts ) {

    if ( is_user_logged_in() ) {
        /* Do not display anything if a user is already logged in */
        return '';
    }

    // WordPress gets the current URL this way
    $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	if (
		defined( 'DOING_AJAX' )
		&& DOING_AJAX
		&& isset( $_REQUEST['action'] )
		&& (
			$_REQUEST['action'] == 'wpv_get_view_query_results'
			|| $_REQUEST['action'] == 'wpv_get_archive_query_results'
		)
	) {
		$current_url = wp_get_referer();
	}

    extract(
		shortcode_atts(
            array(
				'redirect_url'		=> $current_url,
				'redirect_url_fail'	=> '',
				'allow_remember'	=> false,
				'remember_default'	=> false,
            ),
			$atts
		)
    );

    $args = array(
        'echo'				=> false,
        'redirect'			=> $redirect_url, /* Use absolute URLs */
		'redirect_fail'		=> $redirect_url_fail,
        'remember'			=> $allow_remember,
        'value_remember'	=> $remember_default
    );

    $out = wpv_login_form( $args );
    apply_filters( 'wpv_shortcode_debug', 'wpv-login-form', json_encode( $atts ), '', '', $out );
    return $out;
}

/**
* Provides a simple login form for use anywhere within WordPress.
*
* The login format HTML is echoed by default. Pass a false value for `$echo` to return it instead.
* Borrowed from wp_login_form almost entirely.
*
* @since 2.1
*
* @param array $args {
*     Optional. Array of options to control the form output. Default empty array.
*
*     @type bool   $echo           Whether to display the login form or return the form HTML code.
*                                  Default true (echo).
*     @type string $redirect       URL to redirect to. Must be absolute, as in "https://example.com/mypage/".
*                                  Default is to redirect back to the request URI.
*     @type string $redirect_fail  URL to redirect to on failure. Must be absolute, as in "https://example.com/mypage/".
*                                  Default is to redirect to the login page.
*     @type string $form_id        ID attribute value for the form. Default 'loginform'.
*     @type string $label_username Label for the username or email address field. Default 'Username or Email'.
*     @type string $label_password Label for the password field. Default 'Password'.
*     @type string $label_remember Label for the remember field. Default 'Remember Me'.
*     @type string $label_log_in   Label for the submit button. Default 'Log In'.
*     @type string $id_username    ID attribute value for the username field. Default 'user_login'.
*     @type string $id_password    ID attribute value for the password field. Default 'user_pass'.
*     @type string $id_remember    ID attribute value for the remember field. Default 'rememberme'.
*     @type string $id_submit      ID attribute value for the submit button. Default 'wp-submit'.
*     @type bool   $remember       Whether to display the "rememberme" checkbox in the form.
*     @type string $value_username Default value for the username field. Default empty.
*     @type bool   $value_remember Whether the "Remember Me" checkbox should be checked by default.
*                                  Default false (unchecked).
*
* }
* @return string|void String when retrieving.
*/
function wpv_login_form( $args = array() ) {
	$defaults = array(
		'echo'				=> true,
		'redirect'			=> ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'redirect_fail'		=> '',
		'form_id'			=> 'loginform',
		'label_username'	=> __( 'Username or Email', 'wpv-views' ),
		'label_password'	=> __( 'Password' ),
		'label_remember'	=> __( 'Remember Me' ),
		'label_log_in'		=> __( 'Log In' ),
		'id_username'		=> 'user_login',
		'id_password'		=> 'user_pass',
		'id_remember'		=> 'rememberme',
		'id_submit'			=> 'wp-submit',
		'remember'			=> true,
		'value_username'	=> isset( $_REQUEST['username'] ) ? $_REQUEST['username'] : '',
		'value_remember'	=> false,
	);

	/**
	* Filters the default login form output arguments.
	*
	* @see wp_login_form()
	*
	* @param array $defaults An array of default login form arguments.
	*/
	$args = wp_parse_args( $args, apply_filters( 'login_form_defaults', $defaults ) );

	/**
	* Filters content to display at the top of the login form.
	*
	* The filter evaluates just following the opening form tag element.
	*
	* @param string $content Content to display. Default empty.
	* @param array  $args    Array of login form arguments.
	*/
	$login_form_top = apply_filters( 'login_form_top', '', $args );

	/**
	* Filters content to display in the middle of the login form.
	*
	* The filter evaluates just following the location where the 'login-password'
	* field is displayed.
	*
	* @param string $content Content to display. Default empty.
	* @param array  $args    Array of login form arguments.
	*/
	$login_form_middle = apply_filters( 'login_form_middle', '', $args );

	/**
	* Filters content to display at the bottom of the login form.
	*
	* The filter evaluates just preceding the closing form tag element.
	*
	* @param string $content Content to display. Default empty.
	* @param array  $args    Array of login form arguments.
	*/
	$login_form_bottom = apply_filters( 'login_form_bottom', '', $args );

	$login_form_bottom .= '<input type="hidden" name="wpv_login_form" value="on"/>';
	if ( $args['redirect_fail'] != '' ) {
		$login_form_bottom .= '<input type="hidden" name="wpv_login_form_redirect_on_fail" value="' . esc_url( $args['redirect_fail'] ) . '" />';
	}

	$action_url = site_url( 'wp-login.php', 'login_post' );
	// WPEngine requires an extra GET parameter on the login page.
	// Otherwise this form wil fail on an insecure connection error.
	// It seems to require the install name PWP_NAME set on wp-config.php.
	// I have seen this also working on a "true" value; using as a fallback.
	if ( defined( 'PWP_NAME' ) ) {
		$action_url = add_query_arg( 'wpe-login', PWP_NAME, $action_url );
	} elseif ( function_exists( 'wpe_site' ) ) {
		$action_url = add_query_arg( 'wpe-login', 'true', $action_url );
	}

	$form = '
		<form class="wpv-form" name="' . $args['form_id'] . '" id="' . $args['form_id'] . '" action="' . esc_url( $action_url ) . '" method="post">
			' . $login_form_top . '
			<p class="login-username">
				<label for="' . esc_attr( $args['id_username'] ) . '">' . esc_html( $args['label_username'] ) . '</label>
				<input type="text" name="log" id="' . esc_attr( $args['id_username'] ) . '" class="input" value="' . esc_attr( $args['value_username'] ) . '" size="20" />
			</p>
			<p class="login-password">
				<label for="' . esc_attr( $args['id_password'] ) . '">' . esc_html( $args['label_password'] ) . '</label>
				<input type="password" name="pwd" id="' . esc_attr( $args['id_password'] ) . '" class="input" value="" size="20" />
			</p>
			' . $login_form_middle . '
			' . ( $args['remember'] ? '<p class="login-remember"><label><input name="rememberme" type="checkbox" id="' . esc_attr( $args['id_remember'] ) . '" value="forever"' . ( $args['value_remember'] ? ' checked="checked"' : '' ) . ' /> ' . esc_html( $args['label_remember'] ) . '</label></p>' : '' ) . '
			<p class="login-submit">
				<input type="submit" name="wp-submit" id="' . esc_attr( $args['id_submit'] ) . '" class="button-primary" value="' . esc_attr( $args['label_log_in'] ) . '" />
				<input type="hidden" name="redirect_to" value="' . esc_url( $args['redirect'] ) . '" />
			</p>
			' . $login_form_bottom . '
		</form>';

	if ( $args['echo'] )
		echo $form;
	else
		return $form;
}

/**
 * The authenticate filter hook is used to perform additional validation/authentication any time a user logs in to WordPress.
 *
 * @param $user (null|WP_User|WP_Error) WP_User if the user is authenticated. WP_Error or null otherwise.
 * @param $username (string) Username or email address.
 * @param $password (string) User password
 * @return mixed either a WP_User object if authenticating the user or, if generating an error, a WP_Error object.
 *
 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/authenticate
 *
 * More info: http://wordpress.stackexchange.com/a/183208
 */

add_filter( 'authenticate', 'wpv_authenticate', 30, 3 );

function wpv_authenticate ( $user, $username, $password ) {
	// forcefully capture login failed to forcefully open wpv_wp_login_failed action,
	// so that this event can be captured
	if ( is_wp_error( $user ) ) {
		do_action( 'wpv_wp_login_failed', $username, $user );
	}
	return $user;
};

/**
 * Action to forcefully redirect the user on failed authentication.
 * Redirects to the page where the [wpv-login-form] short code is inserted, if 'redirect_fail_url' attribute is not defined.
 *
 * @param $username (string) Username or email address.
 * @param $user (WP_Error) WP_Error object.
 */

add_action( 'wpv_wp_login_failed', 'wpv_login_form_fail_redirect', 30, 2 );

function wpv_login_form_fail_redirect( $username, $user ) {
	$redirect_url = '';

	if ( isset( $_REQUEST['wpv_login_form'] ) ) {
		if ( isset( $_REQUEST['wpv_login_form_redirect_on_fail'] ) && $_REQUEST['wpv_login_form_redirect_on_fail'] != '' ) {
			$redirect_url = $_REQUEST['wpv_login_form_redirect_on_fail'];
		} elseif ( wp_get_referer() ) {
			$redirect_url = wp_get_referer();
		}
	}

	if( !empty( $redirect_url ) ) {
		$redirect_url = add_query_arg(
			array(
				'username' => $username,
				'fail_reason' => $user->get_error_code()
			),
			$redirect_url
		);

		$dic = apply_filters( 'toolset_dic', false );
		$safe_redirect = $dic->make( '\OTGS\Toolset\Common\Wordpress\WpSafeRedirect' );

		if ( $safe_redirect->wp_safe_redirect( $redirect_url ) ) {
			exit;
		}
	}
}

/**
 * Filter to add error messages on top of the login form.
 *
 * @param $content (string) HTML content.
 * @param $args (array) Default arguments array.
 *
 * @return string
 *
 * @see wpv_login_form()
 */

add_filter( 'login_form_top', 'wpv_authenticate_errors', 30, 2 );

function wpv_authenticate_errors ( $content, $args ) {
	if (
		isset( $_REQUEST['fail_reason'] )
		&& $_REQUEST['fail_reason'] != ''
	) {
		$error_string = '<strong>' . __( 'ERROR', 'wpv-views' ) . '</strong>: ';

		switch( $_REQUEST['fail_reason'] ) {
			case 'invalid_username':
				$error_string .= __( 'Invalid username.', 'wpv-views' );
				break;

			case 'incorrect_password':
				$error_string .= sprintf( __( 'The password you entered for the username %s is incorrect.', 'wpv-views' ), '<strong>' . $args['value_username'] . '</strong>' );
				break;

			case 'empty_password':
				$error_string .= __( 'The password field is empty.', 'wpv-views' );
				break;

			case 'empty_username':
				$error_string .= __( 'The username field is empty.', 'wpv-views' );
				break;

			default:
				$error_string .= __( 'Unknown error.', 'wpv-views' );
				break;
		}

		$content .= apply_filters('wpv_filter_override_auth_errors' , $error_string, 'wp-error', $_REQUEST[ 'fail_reason' ]);
	}

	return $content;
}

/**
 * Filter to override default error messages, with own message strings and/or to add some CSS cosmetics.
 *
 * @param string $message Error message.
 * @param string $class (optional) CSS class to highlight the error message. If supplied, $message is encapsulated in <div>...</div>
 * @param string $code (optional) An error code to identify supplied errors.
 *
 * @return string
 *
 * @see wpv_authenticate_errors() for failed login error codes.
 */
add_filter( 'wpv_filter_override_auth_errors', 'wpv_override_auth_errors', 10, 3 );

function wpv_override_auth_errors( $message, $class = '', $code = '' ) {
	if( !empty( $class ) ) {
		$message = '<div class="' . $class . '">' . $message . '</div>';
	}

	return $message;
}

/**
 * Filter to add general/success messages on top of the login form.
 *
 * @param $content (string) HTML content.
 * @param $args (array) Default arguments array.
 *
 * @return string
 *
 * @since 2.2
 * @see wpv_login_form()
 */

add_filter( 'login_form_top', 'wpv_shortcodes_wpv_login_messages', 30, 2 );
add_filter( 'forgot_password_form_top', 'wpv_shortcodes_wpv_login_messages', 30, 2 );
add_filter( 'reset_password_form_top', 'wpv_shortcodes_wpv_login_messages', 30, 2 );

function wpv_shortcodes_wpv_login_messages ( $content, $args ) {
	$msg_code = '';
	$msg_string = '';

	if ( isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] != '' ) {
		$msg_code = $_REQUEST['checkemail'];
	}

	if ( isset( $_REQUEST['password'] ) && $_REQUEST['password'] != '' ) {
		$msg_code = $_REQUEST['password'];
	}

	switch( $msg_code ) {
		case 'confirm':
			$msg_string .= __( 'Check your email for the confirmation link.', 'wpv-views' );
			break;

		case 'changed':
			$msg_string .= __( 'Your password has been reset.', 'wpv-views' );
			break;
	}

	$content .= apply_filters( 'wpv_filter_override_auth_errors' , $msg_string, 'wp-success', $msg_code );

	return $content;
}

/**
* wpv_shortcodes_register_wpv_login_form_data
*
* Register the wpv-login-form shortcode in the GUI API.
*
* @since 1.9
*/

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_login_form_data' );

function wpv_shortcodes_register_wpv_login_form_data( $views_shortcodes ) {
	$views_shortcodes['wpv-login-form'] = array(
		'callback' => 'wpv_shortcodes_get_wpv_login_form_data'
	);
	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_login_form_data()  {
    $data = array(
        'name' => __( 'Login Form', 'wpv-views' ),
        'label' => __( 'Login Form', 'wpv-views' ),
        'attributes' => array(
            'display-options' => array(
                'label' => __('Display options', 'wpv-views'),
                'header' => __('Display options', 'wpv-views'),
                'fields' => array(
                    'redirect_url' => array(
                        'label' => __( 'Redirect to this URL on success', 'wpv-views'),
                        'type' => 'url',
						'description' => __( 'URL to redirect users after login in. Defaults to the current URL.', 'wpv-views' ),
                    ),
					'redirect_url_fail' => array(
                        'label' => __( 'Redirect to this URL on failure', 'wpv-views'),
                        'type' => 'url',
						'description' => __( 'URL to redirect users when the login fails. Defaults to the current URL.', 'wpv-views' ),
                    ),
					'remember_me_combo'	=> array(
						'label'		=> __( 'Remember me checkbox', 'wpv-views' ),
						'type'		=> 'grouped',
						'fields'	=> array(
							'allow_remember'	=> array(
								'type'			=> 'radio',
								'options'		=> array(
									'true'	=> __( 'Show checkbox', 'wpv-views' ),
									'false'	=> __( 'Hide checkbox', 'wpv-views' ),
								),
								'default'		=> 'false',
							),
							'remember_default' => array(
								'pseudolabel'	=> __( 'Default state', 'wpv-views' ),
								'type'			=> 'radio',
								'options'		=> array(
									'true'	=> __( 'Checked', 'wpv-views' ),
									'false'	=> __( 'Unchecked', 'wpv-views' ),
								),
								'default'		=> 'false',
							),
						)
					),
                ),
            ),
        ),
    );
    return $data;
}

/**
 * Views-Shortcode: wpv-logout-link
 *
 * Description: Display WordPress logout link and uses supplied content as a link label.
 * If no label is supplied, it outputs 'Logout' as a default label.
 *
 * Parameters:
 *  "redirect_url" redirects to this URL after successful logout. Absolute URL.
 *  "class" HTML class attribute for generated A tag
 *  "style" HTML style attribute for generated A tag
 *
 * Example usage:
 *  [wpv-logout-link]Logout[/wpv-logout-link]
 *  [wpv-logout-link]Sign Out[/wpv-logout-link]
 *  [wpv-logout-link class="my-class" style="text-decoration: none;" redirect_url="http://example.com"]
 *  [wpv-logout-link redirect_url="[wpv-post-url]"]Sign out and go to [wpv-post-title][/wpv-logout-link]
 *
 *
 * User Guide:
 *
 * @todo: find a way to allow redirect to external links
 *
 * Note:
 *  http://codex.wordpress.org/Template_Tags/wp_logout_url
 *
 * @since 2.1
 */
function wpv_shortcode_wpv_logout_link( $atts, $content = '' ) {
	global $current_user;

	if((int)$current_user->ID <= 0) {
		/* Do not display anything if a user is already logged out */
		return '';
	}

	if (
		defined( 'DOING_AJAX' )
		&& DOING_AJAX
		&& isset( $_REQUEST['action'] )
		&& (
			$_REQUEST['action'] == 'wpv_get_view_query_results'
			|| $_REQUEST['action'] == 'wpv_get_archive_query_results'
		)
	) {
		// It's an AJAX request - Views AJAX Pagination or Parametric Search Request
		$current_url = wp_get_referer();
	} else {
		// It's non-AJAX request
		// WordPress gets the current URL this way
		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	}

	extract( shortcode_atts(
		array(
			'redirect_url' => $current_url,
			'class' => '',
			'style' => '',
		), $atts )
	);

	// Get logout URL
	$url = wp_logout_url( $redirect_url );

	// Parse the content (if any) for inline short codes
	$outContent = !empty( $content ) ? wpv_do_shortcode( $content ) : '';

	// Assemble the output
	$out = '<a href="' . $url . '"';
	$out .= !empty( $class ) ? ' class="' . esc_attr( $class ) . '"' : '';
	$out .= !empty( $style ) ? ' style="' . esc_attr( $style ) . '"' : '';
	$out .= '>';
	$out .= $outContent;
	$out .= '</a>';

	apply_filters( 'wpv_shortcode_debug', 'wpv-logout-link', json_encode( $atts ), '', '', $out );
	return $out;
}

/**
 * Checks if the supplied URL points to an external site or not.
 *
 * @param $url URL to check
 * @return bool
 * @since 2.1
 *
 * Notes:
 *  - www.example.com and example.com are treated as 2 different URLs (domains).
 *  - This function implements simple check and compares with 'host' of current blog URL.
 *  - Relative paths are of course treated as internal URLs.
 *
 * @todo: Improve the function if needed.
 * @todo: Left for future reference.
 */
function wpv_is_external_url( $url ) {
	$external = false;
	$url_parts = parse_url( $url );
	$blog_url_parts = parse_url( get_bloginfo( 'url' ) );

	if(
		isset( $url_parts['host'] )
		&& !empty( $url_parts['host'] )
		&& $url_parts['host'] != $blog_url_parts['host']
	) {
		$external = true;
	}

	return $external;
}

/**
 * wpv_shortcodes_register_wpv_logout_link_data
 *
 * Register the wpv-logout-link shortcode in the GUI API.
 *
 * @since 2.1
 */

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_logout_link_data' );

function wpv_shortcodes_register_wpv_logout_link_data( $views_shortcodes ) {
	$views_shortcodes['wpv-logout-link'] = array(
			'callback' => 'wpv_shortcodes_get_wpv_logout_link_data'
	);
	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_logout_link_data()  {
	$data = array(
		'name' => __( 'Logout Link', 'wpv-views' ),
		'label' => __( 'Logout Link', 'wpv-views' ),
		'attributes' => array(
			'display-options' => array(
				'label' => __( 'Display options', 'wpv-views' ),
				'header' => __( 'Display options', 'wpv-views' ),
				'fields' => array(
					'redirect_url' => array(
						'label' => __( 'Redirect target URL', 'wpv-views' ),
						'type' => 'url',
						'description' => __( 'URL to redirect users after logout. Defaults to the current URL. Redirect is only supported to the URLs within the current blog (or site). Redirection to external URLs (or sites) is not supported.', 'wpv-views' ),
					),
					'class' => array(
						'label' => __( 'Class', 'wpv-views' ),
						'type' => 'text',
						'description' => __( 'Space-separated list of class names that will be added to the anchor HTML tag.', 'wpv-views' ),
						'placeholder' => 'class1 class2',
					),
					'style' => array(
						'label' => __( 'Style', 'wpv-views' ),
						'type' => 'text',
						'description' => __( 'Inline styles that will be added to the anchor HTML tag.', 'wpv-views' ),
						'placeholder' => 'border: 1px solid red; font-size: 2em;',
					),
				),
				'content' => array(
					'label' => __( 'Link label', 'wpv-views' ),
					'description' => __( 'This will be displayed as a text or label for the link.', 'wpv-views' ),
					'default' => __('Logout', 'wpv-views'),
				),
			),
		),
	);
	return $data;
}

////////////////////////// Forgot/Reset Password Flow Starts ///////////////////////////////////
/**
 * Views-Shortcode: wpv-forgot-password-form
 *
 * Description: Display WordPress forgot password form.
 *
 * Parameters:
 *  "redirect_url" redirects to this URL after successful operation. Absolute URL.
 *  "redirect_fail" redirects to this URL after failed operation. Absolute URL.
 *  "reset_password_url" redirects to this URL to reset the password. Absolute URL. This link is sent in email.
 *
 * Example usage:
 *     [wpv-forgot-password-form]
 *
 * Link:
 *
 * @since 2.2
 */
function wpv_shortcode_wpv_forgot_password_form( $atts ) {

	if ( is_user_logged_in() ) {
		/* Do not display anything if a user is already logged in */
		return '';
	}

	// WordPress gets the current URL this way
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	if (
		defined( 'DOING_AJAX' )
		&& DOING_AJAX
		&& isset( $_REQUEST['action'] )
		&& (
			$_REQUEST['action'] == 'wpv_get_view_query_results'
			|| $_REQUEST['action'] == 'wpv_get_archive_query_results'
		)
	) {
		$current_url = wp_get_referer();
	}

	extract(
		shortcode_atts(
			array(
				'redirect_url'		=> remove_query_arg( array( 'wpv_error' ), $current_url ), //$current_url, //wp_login_url(),
				'redirect_url_fail'	=> remove_query_arg( array( 'checkemail' ), $current_url ), //$current_url,
				'reset_password_url' => ''
			),
			$atts
		)
	);

	$args = array(
		'redirect'			=> $redirect_url, /* Use absolute URLs */
		'redirect_fail'		=> $redirect_url_fail,
		'reset_password' => $reset_password_url
	);

	$out = wpv_forgot_password_form( $args );

	apply_filters( 'wpv_shortcode_debug', 'wpv-forgot-password-form', json_encode( $atts ), '', '', $out );
	return $out;
}

/**
 * Provides a simple forgot password form for use anywhere within WordPress.
 *
 * @since 2.2
 *
 * @param array $args {
 *     Optional. Array of options to control the form output. Default empty array.
 *
 *     @type string $redirect       URL to redirect to. Must be absolute, as in "https://example.com/mypage/".
 *     @type string $redirect_fail  URL to redirect to on failure. Must be absolute, as in "https://example.com/mypage/".
 *     @type string $reset_password URL to redirect to custom reset password page. Must be absolute URL.
 *     @type string $form_id        ID attribute value for the form. Default 'forgotpasswordform'.
 *     @type string $label_username Label for the username or email address field. Default 'Username or Email'.
 *     @type string $id_username    ID attribute value for the username field. Default 'user_login'.
 *     @type string $label_submit	Label for submit buttion. Default 'Get New Password'.
 *     @type string $id_submit      ID attribute value for the submit button. Default 'wp-submit'.
 *     @type string $value_username Default value for the username field. Default empty.
 *
 * }
 * @return string|void String when retrieving.
 */
function wpv_forgot_password_form( $args = array() ) {
	$defaults = array(
		'redirect'			=> ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'redirect_fail'		=> ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'reset_password'	=> '',
		'form_id'			=> 'forgotpasswordform',
		'label_username'	=> __( 'Username or Email', 'wpv-views' ),
		'id_username'		=> 'user_login',
		'label_submit'		=> __( 'Get New Password', 'wpv-views' ),
		'id_submit'			=> 'wp-submit',
		'value_username'	=> isset( $_REQUEST['username'] ) ? $_REQUEST['username'] : '',
	);

	/**
	 * Filters the default forgot password form output arguments.
	 *
	 * @param array $defaults An array of default login form arguments.
	 */
	$args = wp_parse_args( $args, apply_filters( 'forgot_password_form_defaults', $defaults ) );

	/**
	 * Filters content to display at the top of the form.
	 *
	 * The filter evaluates just following the opening form tag element.
	 *
	 * @param string $content Content to display. Default empty.
	 * @param array  $args    Array of form arguments.
	 */
	$form_top = apply_filters( 'forgot_password_form_top', '', $args );

	/**
	 * Filters content to display in the middle of the form.
	 *
	 * The filter evaluates just before the submit button.
	 *
	 * @param string $content Content to display. Default empty.
	 * @param array  $args    Array of form arguments.
	 */
	$form_middle = apply_filters( 'forgot_password_form_middle', '', $args );

	/**
	 * Filters content to display at the bottom of the login form.
	 *
	 * The filter evaluates just preceding the closing form tag element.
	 *
	 * @param string $content Content to display. Default empty.
	 * @param array  $args    Array of form arguments.
	 */
	$form_bottom = apply_filters( 'forgot_password_form_bottom', '', $args );

	$form_bottom .= '<input type="hidden" name="wpv_forgot_password_form" value="on"/>';
	if ( $args['redirect_fail'] != '' ) {
		$form_bottom .= '<input type="hidden" name="wpv_forgot_password_form_redirect_on_fail" value="' . esc_url( $args['redirect_fail'] ) . '" />';
	}

	if ( $args['reset_password'] != '' ) {
		$form_bottom .= '<input type="hidden" name="wpv_forgot_password_form_reset_password_url" value="' . esc_url( $args['reset_password'] ) . '" />';
	}

	do_action( 'wpv_action_wpv_before_forgot_password_form');

	$lost_password_url = wp_lostpassword_url();

	// WPEngine requires an extra GET parameter on the login page.
	// Otherwise this form wil fail on an insecure connection error.
	// It seems to require the install name PWP_NAME set on wp-config.php.
	// I have seen this also working on a "true" value; using as a fallback.
	if ( defined( 'PWP_NAME' ) ) {
		$lost_password_url = add_query_arg( 'wpe-login', PWP_NAME, $lost_password_url );
	} elseif ( function_exists( 'wpe_site' ) ) {
		$lost_password_url = add_query_arg( 'wpe-login', 'true', $lost_password_url );
	}

	$form = '
		<form class="wpv-form" name="' . esc_attr( $args['form_id'] ) . '" id="' . esc_attr( $args['form_id'] ) . '" action="' . esc_url( $lost_password_url ) . '" method="post">
			' . $form_top . '
			<p class="login-username">
				<label for="' . esc_attr( $args['id_username'] ) . '">' . esc_html( $args['label_username'] ) . '</label>
				<input type="text" name="' . esc_attr( $args['id_username'] ) . '" id="' . esc_attr( $args['id_username'] ) . '" class="input" value="' . esc_attr( $args['value_username'] ) . '" size="20" />
			</p>
			' . $form_middle . '
			<p class="login-submit">
				<input type="submit" name="wp-submit" id="' . esc_attr( $args['id_submit'] ) . '" class="button-primary" value="' . esc_attr( $args['label_submit'] ) . '" />
				<input type="hidden" name="redirect_to" value="' . esc_url( $args['redirect'] ) . '" />
			</p>
			' . $form_bottom . '
		</form>';

	do_action( 'wpv_action_wpv_after_forgot_password_form');

	return $form;
}

/**
 * wpv_shortcodes_register_wpv_forgot_password_form_data
 *
 * Register the wpv-forgot-password-form shortcode in the GUI API.
 *
 * @since 2.2
 */

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_forgot_password_form_data' );

function wpv_shortcodes_register_wpv_forgot_password_form_data( $views_shortcodes ) {
	$views_shortcodes['wpv-forgot-password-form'] = array(
		'callback' => 'wpv_shortcodes_get_wpv_forgot_password_form_data'
	);

	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_forgot_password_form_data()  {
	$data = array(
		'name' => __( 'Forgot Password Form', 'wpv-views' ),
		'label' => __( 'Forgot Password Form', 'wpv-views' ),
		'attributes' => array(
			'redirect-options' => array(
				'label' => __('Redirect options', 'wpv-views'),
				'header' => __('Redirect options', 'wpv-views'),
				'fields' => array(
					'redirect_url' => array(
						'label' => __( 'Redirect to this URL on success', 'wpv-views'),
						'type' => 'url',
						'description' => __( 'URL to redirect users after sending password retrieval link. Defaults to the current URL.', 'wpv-views' ),
					),
					'redirect_url_fail' => array(
						'label' => __( 'Redirect to this URL on failure', 'wpv-views'),
						'type' => 'url',
						'description' => __( 'URL to redirect users after failed password retrieval operation. Defaults to the current URL.', 'wpv-views' ),
					),
					'reset_password_url' => array(
						'label' => __( 'URL to custom password reset page', 'wpv-views'),
						'type' => 'url',
						'description' => __( 'URL to custom password reset page when reset password link is clicked in reset password email. Defaults to WordPress reset password URL.', 'wpv-views' ),
					)
				),
			),
		),
	);

	return $data;
}

/**
 * wpv_shortcodes_wpv_do_password_lost
 *
 * Handles custom forgot password form errors.
 *
 * @since 2.2
 */

add_action( 'login_form_lostpassword', 'wpv_shortcodes_wpv_do_password_lost' );

function wpv_shortcodes_wpv_do_password_lost() {
	if (
		'POST' == $_SERVER['REQUEST_METHOD']
		&& isset( $_REQUEST['wpv_forgot_password_form'] )
		&& 'on' == $_REQUEST['wpv_forgot_password_form']
	) {
		$redirect_to = $_REQUEST['redirect_to'];
		$redirect_fail = $_REQUEST['wpv_forgot_password_form_redirect_on_fail'];

		$errors = retrieve_password();

		if ( is_wp_error( $errors ) ) {
			// Errors found
			$redirect_url = add_query_arg( 'wpv_error', join( ',', $errors->get_error_codes() ), $redirect_fail );
		} else {
			// Email sent
			$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_to );
		}

		$dic = apply_filters( 'toolset_dic', false );
		$safe_redirect = $dic->make( '\OTGS\Toolset\Common\Wordpress\WpSafeRedirect' );

		if ( $safe_redirect->wp_safe_redirect( $redirect_url ) ) {
			exit;
		}
	}
}

/**
 * Returns the message body for the password reset email.
 * Called through the retrieve_password_message filter.
 *
 * @param string  $message    Default mail message.
 * @param string  $key        The activation key.
 * @param string  $user_login The username for the user.
 * @param WP_User $user_data  WP_User object.
 *
 * @return string   The mail message to send.
 *
 * @since 2.2
 * @see https://developer.wordpress.org/reference/hooks/retrieve_password_message/
 */

add_filter( 'retrieve_password_message', 'wpv_filter_wpv_replace_retrieve_password_email_body', 10, 4 );

function wpv_filter_wpv_replace_retrieve_password_email_body( $message, $key, $user_login, $user_data ) {
	$reset_password = '';

	if(
		isset( $_REQUEST['wpv_forgot_password_form_reset_password_url'] )
		&& !empty( $_REQUEST['wpv_forgot_password_form_reset_password_url'] )
	) {
		$reset_password = add_query_arg(
			array(
				'action' => 'rp',
				'key' => $key,
				'login' => rawurlencode( $user_login )
			),
			$_REQUEST['wpv_forgot_password_form_reset_password_url']
		);

		// Create new message
		$message  = __( 'Someone has requested a password reset for the following account:', 'wpv-views' ) . "\r\n\r\n";
		$message .= get_home_url() . "\r\n\r\n";
		$message .= sprintf( __( 'Username: %s.', 'wpv-views' ), $user_login ) . "\r\n\r\n";
		$message .= __( "If this was a mistake, just ignore this email and nothing will happen.", 'wpv-views' ) . "\r\n\r\n";
		$message .= __( 'To reset your password, visit the following address:', 'wpv-views' ) . "\r\n\r\n";
		$message .= $reset_password . "\r\n\r\n";
	}

	return $message;
}

/**
 * Views-Shortcode: wpv-reset-password-form
 *
 * Description: Display custom reset password form.
 *
 * Parameters:
 *  "redirect_url" redirects to this URL after successful operation. Absolute URL.
 *  "redirect_fail" redirects to this URL after failed operation. Absolute URL.
 *
 * Example usage:
 *     [wpv-reset-password-form]
 *
 * Link:
 *
 * @since 2.2
 */
function wpv_shortcode_wpv_reset_password_form( $atts ) {

	if ( is_user_logged_in() ) {
		/* Do not display anything if a user is already logged in */
		return '';
	}

	// WordPress gets the current URL this way
	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	if (
		defined( 'DOING_AJAX' )
		&& DOING_AJAX
		&& isset( $_REQUEST['action'] )
		&& (
			$_REQUEST['action'] == 'wpv_get_view_query_results'
			|| $_REQUEST['action'] == 'wpv_get_archive_query_results'
		)
	) {
		$current_url = wp_get_referer();
	}

	extract(
		shortcode_atts(
			array(
				'redirect_url'		=> remove_query_arg( array( 'wpv_error' ), $current_url ), //wp_login_url(),
				'redirect_url_fail'	=> remove_query_arg( array( 'password' ), $current_url )
			),
			$atts
		)
	);

	$login = isset( $_REQUEST['login'] ) ? $_REQUEST['login'] : '';
	$key = isset( $_REQUEST['key'] ) ? $_REQUEST['key'] : '';

	$args = array(
		'redirect'			=> $redirect_url, /* Use absolute URLs */
		'redirect_fail'		=> $redirect_url_fail,
		'rp_login'				=> $login,
		'rp_key'				=> $key
	);

	$out = wpv_reset_password_form( $args );

	apply_filters( 'wpv_shortcode_debug', 'wpv-reset-password-form', json_encode( $atts ), '', '', $out );
	return $out;
}

/**
 * Provides a simple reset password form for use anywhere within WordPress.
 *
 * @since 2.2
 *
 * @param array $args {
 *     Optional. Array of options to control the form output. Default empty array.
 *
 *     @type string $redirect       URL to redirect to. Must be absolute, as in "https://example.com/mypage/".
 *     @type string $redirect_fail  URL to redirect to on failure. Must be absolute, as in "https://example.com/mypage/".
 *     @type string $form_id        ID attribute value for the form. Default 'resetpasswordform'.
 *     @type string $label_pass1 	Label for the new password field. Default 'New password'.
 *     @type string $id_pass1 		ID for the new password field. Default 'pass1'.
 *     @type string $label_pass2 	Label for repeat password field. Default 'Repeat new password'.
 *     @type string $id_pass2   	ID for repeat password field. Default 'pass2'.
 *     @type string $label_submit	Label for submit button. Default 'Reset Password'.
 *     @type string $id_submit      ID attribute value for the submit button. Default 'wp-submit'.
 *     @type string $rp_login       Login name for reset password hidden field. Default empty.
 *     @type string $rp_key		 	Reset password key for the hidden field. Default empty.
 *
 * }
 * @return string|void String when retrieving.
 */
function wpv_reset_password_form( $args = array() ) {
	$defaults = array(
		'redirect'			=> ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'redirect_fail'		=> ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		'form_id'			=> 'resetpasswordform',
		'label_pass1'		=> __( 'New password', 'wpv-views' ),
		'id_pass1'			=> 'pass1',
		'label_pass2'		=> __( 'Repeat new password', 'wpv-views' ),
		'id_pass2'			=> 'pass2',
		'label_submit'		=> __( 'Reset Password', 'wpv-views' ),
		'id_submit'			=> 'wp-submit',
		'rp_login'			=> '',
		'rp_key'			=> ''
	);

	/**
	 * Filters the default reset password form output arguments.
	 *
	 * @param array $defaults An array of default login form arguments.
	 */
	$args = wp_parse_args( $args, apply_filters( 'reset_password_form_defaults', $defaults ) );

	/**
	 * Filters content to display at the top of the form.
	 *
	 * The filter evaluates just following the opening form tag element.
	 *
	 * @param string $content Content to display. Default empty.
	 * @param array  $args    Array of form arguments.
	 */
	$form_top = apply_filters( 'reset_password_form_top', '', $args );

	// Add required hidden fields to form top
	$form_top .= '<input type="hidden" id="user_login" name="rp_login" value="' . esc_attr( $args['rp_login'] ) . '" />';
	$form_top .= '<input type="hidden" name="rp_key" value="' . esc_attr( $args['rp_key'] ) . '" autocomplete="off" />';

	/**
	 * Filters content to display in the middle of the form.
	 *
	 * The filter evaluates just before the submit button.
	 *
	 * @param string $content Content to display. Default empty.
	 * @param array  $args    Array of form arguments.
	 */
	$form_middle = apply_filters( 'reset_password_form_middle', '', $args );

	/**
	 * Filters content to display at the bottom of the login form.
	 *
	 * The filter evaluates just preceding the closing form tag element.
	 *
	 * @param string $content Content to display. Default empty.
	 * @param array  $args    Array of form arguments.
	 */
	$form_bottom = apply_filters( 'reset_password_form_bottom', '', $args );

	$form_bottom .= '<input type="hidden" name="wpv_reset_password_form" value="on"/>';

	if ( $args['redirect_fail'] != '' ) {
		$form_bottom .= '<input type="hidden" name="wpv_reset_password_form_redirect_on_fail" value="' . esc_url( $args['redirect_fail'] ) . '" />';
	}

	$action_url = site_url( 'wp-login.php?action=resetpass' );
	// WPEngine requires an extra GET parameter on the login page.
	// Otherwise this form wil fail on an insecure connection error.
	// It seems to require the install name PWP_NAME set on wp-config.php.
	// I have seen this also working on a "true" value; using as a fallback.
	if ( defined( 'PWP_NAME' ) ) {
		$action_url = add_query_arg( 'wpe-login', PWP_NAME, $action_url );
	} elseif ( function_exists( 'wpe_site' ) ) {
		$action_url = add_query_arg( 'wpe-login', 'true', $action_url );
	}

	$form = '
		<form class="wpv-form" name="' . esc_attr( $args['form_id'] ) . '" id="' . esc_attr( $args['form_id'] ) . '" action="' . esc_url( $action_url ) . '" method="post">
			' . $form_top . '
			<p class="reset-pass">
				<label for="' . esc_attr( $args['id_pass1'] ) . '">' . esc_html( $args['label_pass1'] ) . '</label>
				<input type="password" name="' . esc_attr( $args['id_pass1'] ) . '" id="' . esc_attr( $args['id_pass1'] ) . '" class="input" value="" size="20" />
			</p>
			<p class="reset-pass">
				<label for="' . esc_attr( $args['id_pass2'] ) . '">' . esc_html( $args['label_pass2'] ) . '</label>
				<input type="password" name="' . esc_attr( $args['id_pass2'] ) . '" id="' . esc_attr( $args['id_pass2'] ) . '" class="input" value="" size="20" />
			</p>

			<p class="description">' . wp_get_password_hint() . '</p>
			' . $form_middle . '
			<p class="login-submit">
				<input type="submit" name="wp-submit" id="' . esc_attr( $args['id_submit'] ) . '" class="button-primary" value="' . esc_attr( $args['label_submit'] ) . '" />
				<input type="hidden" name="redirect_to" value="' . esc_url( $args['redirect'] ) . '" />
			</p>
			' . $form_bottom . '
		</form>';

	return $form;
}

/**
 * wpv_shortcodes_wpv_do_password_reset
 *
 * Performs password reset based on custom reset password form.
 * Also handles errors and redirects accordingly.
 *
 * @since 2.2
 */

add_action( 'login_form_rp', 'wpv_shortcodes_wpv_do_password_reset' );
add_action( 'login_form_resetpass', 'wpv_shortcodes_wpv_do_password_reset' );

function wpv_shortcodes_wpv_do_password_reset() {
	if (
		'POST' == $_SERVER['REQUEST_METHOD']
		&& isset( $_REQUEST['wpv_reset_password_form'] )
		&& 'on' == $_REQUEST['wpv_reset_password_form']
	) {
		$rp_key = isset( $_REQUEST['rp_key'] ) ? $_REQUEST['rp_key'] : '';
		$rp_login = isset( $_REQUEST['rp_login'] ) ? $_REQUEST['rp_login'] : '';
		$pass1 = isset( $_REQUEST['pass1'] ) ? $_REQUEST['pass1'] : '';
		$pass2 = isset( $_REQUEST['pass2'] ) ? $_REQUEST['pass2'] : '';

		$redirect_to = $_REQUEST['redirect_to'];
		$redirect_fail = $_REQUEST['wpv_reset_password_form_redirect_on_fail'];

		$fail_code = '';

		$user = check_password_reset_key( $rp_key, $rp_login );

		$dic = apply_filters( 'toolset_dic', false );
		$safe_redirect = $dic->make( '\OTGS\Toolset\Common\Wordpress\WpSafeRedirect' );

		if ( ! $user || is_wp_error( $user ) ) {
			if ( $user && $user->get_error_code() ) {
				$fail_code = $user->get_error_code();
			}

			$redirect_fail = add_query_arg(
				array(
					'login' => $rp_login,
					'key' => $rp_key,
					'wpv_error' => $fail_code
				),
				$redirect_fail
			);

			if ( $safe_redirect->wp_safe_redirect( $redirect_fail ) ) {
				exit;
			}

			return;
		}

		if ( empty( $pass1 ) || empty( $pass2 ) ) {
			// Password is empty
			$redirect_fail = add_query_arg(
				array(
					'login' => $rp_login,
					'key' => $rp_key,
					'wpv_error' => 'password_reset_empty'
				),
				$redirect_fail
			);

			if ( $safe_redirect->wp_safe_redirect( $redirect_fail ) ) {
				exit;
			}

			return;
		}

		if ( !empty( $pass1 ) && !empty( $pass2 ) ) {
			if ( $pass1 != $pass2 ) {
				// Passwords don't match
				$redirect_fail = add_query_arg(
					array(
						'login' => $rp_login,
						'key' => $rp_key,
						'wpv_error' => 'password_reset_mismatch'
					),
					$redirect_fail
				);

				if ( $safe_redirect->wp_safe_redirect( $redirect_fail ) ) {
					exit;
				}

				return;
            }

			// Parameter checks OK, reset password
			reset_password( $user, $pass1 );

			$redirect_to = add_query_arg(
				array(
					'password' => 'changed'
				),
				$redirect_to
			);

			if ( $safe_redirect->wp_safe_redirect( $redirect_to ) ) {
				exit;
			}
		}
	}
}

/**
 * wpv_shortcodes_register_wpv_reset_password_form_data
 *
 * Register the wpv-forgot-password-form shortcode in the GUI API.
 *
 * @since 2.2
 */

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_reset_password_form_data' );

function wpv_shortcodes_register_wpv_reset_password_form_data( $views_shortcodes ) {
	$views_shortcodes['wpv-reset-password-form'] = array(
		'callback' => 'wpv_shortcodes_get_wpv_reset_password_form_data'
	);

	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_reset_password_form_data()  {
	$data = array(
		'name' => __( 'Reset Password Form', 'wpv-views' ),
		'label' => __( 'Reset Password Form', 'wpv-views' ),
		'attributes' => array(
			'redirect-options' => array(
				'label' => __('Redirect options', 'wpv-views'),
				'header' => __('Redirect options', 'wpv-views'),
				'fields' => array(
					'redirect_url' => array(
						'label' => __( 'Redirect to this URL on success', 'wpv-views'),
						'type' => 'url',
						'description' => __( 'URL to redirect users after resetting the password. Defaults to the current URL.', 'wpv-views' ),
					),
					'redirect_url_fail' => array(
						'label' => __( 'Redirect to this URL on failure', 'wpv-views'),
						'type' => 'url',
						'description' => __( 'URL to redirect users after failed password reset operation. Defaults to the current URL.', 'wpv-views' ),
					)
				),
			),
		),
	);

	return $data;
}

/**
 * Filter to add error messages on top of the custom forgot/reset password forms.
 *
 * @param $content (string) HTML content.
 * @param $args (array) Default arguments array.
 *
 * @return string
 *
 * @see wpv_forgot_password_form()
 * @see wpv_reset_password_form()
 */

add_filter( 'forgot_password_form_top', 'wpv_resetpass_errors', 30, 2 );
add_filter( 'reset_password_form_top', 'wpv_resetpass_errors', 30, 2 );

function wpv_resetpass_errors ( $content, $args ) {
	$error_code = '';

	if (
		isset( $_REQUEST['wpv_error'] )
		&& $_REQUEST['wpv_error'] != ''
	) {
		$error_string = __( '<strong>ERROR</strong>: ', 'wpv-views' );
		$error_code = $_REQUEST['wpv_error'];

		switch( $error_code ) {
			case 'expiredkey':
			case 'invalidkey':
			case 'invalid_key':
				$error_string .= __( 'Your password reset link appears to be invalid. Please request a new link.', 'wpv-views' );
				break;

			case 'invalid_email':
				$error_string .= __( 'There is no user registered with that email address.', 'wpv-views' );
				break;

			case 'invalidcombo':
				$error_string .= __( 'Invalid username or email.', 'wpv-views' );
				break;

			case 'password_reset_mismatch':
				$error_string .= __( 'Your entered passwords don\'t match.', 'wpv-views' );
				break;

			case 'password_reset_empty':
				$error_string .= __( 'The password field is empty.', 'wpv-views' );
				break;

			case 'empty_username':
				$error_string .= __( 'Enter a username or email address.', 'wpv-views' );
				break;

			default:
				$error_string .= __( 'Unknown error.', 'wpv-views' );
				break;
		}

		$content .= apply_filters( 'wpv_filter_override_auth_errors' , $error_string, 'wp-error', $error_code );
	}

	return $content;
}
////////////////////////// Forgot/Reset Password Flow Ends ///////////////////////////////////

/**
 * Views-Shortcode: wpv-forgot-password-link
 *
 * Description: Display WordPress forgot password link and uses supplied content as a link label.
 * If no label is supplied, it outputs 'Lost password?' as a default label.
 *
 * Parameters:
 *  "redirect_url" URL to redirect to after retrieving the lost password. Absolute URL.
 *  "class" HTML class attribute for generated A tag
 *  "style" HTML style attribute for generated A tag
 *
 * Example usage:
 *  [wpv-forgot-password-link]Forgot password[/wpv-forgot-password-link]
 *  [wpv-forgot-password-link class="my-class" style="text-decoration: none;" redirect_url="http://example.com"]
 *  [wpv-forgot-password-link redirect_url="[wpv-post-url]"]Forgot password?[/wpv-forgot-password-link]
 *
 *
 * Link:
 * @todo: public documentation link?
 * @todo: find a way to allow redirect to external links
 *
 * Note:
 *  https://codex.wordpress.org/Function_Reference/wp_lostpassword_url
 *
 * @since 2.2
 */
function wpv_shortcode_wpv_forgot_password_link( $atts, $content = '' ) {
	global $current_user;

	if((int)$current_user->ID > 0) {
		/* Do not display anything if a user is already logged in */
		return '';
	}

	// Check for current URL, either it's an AJAX request or a non-AJAX request
	$url_request = $_SERVER['REQUEST_URI'];

	if (
		defined( 'DOING_AJAX' )
		&& DOING_AJAX
		&& isset( $_REQUEST['action'] )
		&& (
			$_REQUEST['action'] == 'wpv_get_view_query_results'
			|| $_REQUEST['action'] == 'wpv_get_archive_query_results'
		)
	) {
		// It's an AJAX request - Views AJAX Pagination or Parametric Search Request
		$current_url = wp_get_referer();
	} else {
		// It's non-AJAX request
		// WordPress gets the current URL this way
		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	}

	extract( shortcode_atts(
			array(
				'redirect_url' => $current_url,
				'class' => '',
				'style' => '',
			), $atts )
	);

	// Get forgot password URL
	$url = wp_lostpassword_url( $redirect_url );

	// Parse the content (if any) for inline short codes
	$outContent = !empty( $content ) ? wpv_do_shortcode( $content ) : '';

	// Assemble the output
	$out = '<a href="' . $url . '"';
	$out .= !empty( $class ) ? ' class="' . esc_attr( $class ) . '"' : '';
	$out .= !empty( $style ) ? ' style="' . esc_attr( $style ) . '"' : '';
	$out .= '>';
	$out .= $outContent;
	$out .= '</a>';

	apply_filters( 'wpv_shortcode_debug', 'wpv-forgot-password-link', json_encode( $atts ), '', '', $out );
	return $out;
}

/**
 * wpv_shortcodes_register_wpv_forgot_password_link_data
 *
 * Register the wpv-forgot-password-link shortcode in the GUI API.
 *
 * @since 2.1
 */

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_forgot_password_link_data' );

function wpv_shortcodes_register_wpv_forgot_password_link_data( $views_shortcodes ) {
	$views_shortcodes['wpv-forgot-password-link'] = array(
		'callback' => 'wpv_shortcodes_get_wpv_forgot_password_link_data'
	);
	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_forgot_password_link_data()  {
	$data = array(
		'name' => __( 'Forgot Password Link', 'wpv-views' ),
		'label' => __( 'Forgot Password Link', 'wpv-views' ),
		'attributes' => array(
			'display-options' => array(
				'label' => __( 'Options', 'wpv-views' ),
				'header' => __( 'Options', 'wpv-views' ),
				'fields' => array(
					'redirect_url' => array(
						'label' => __( 'Redirect URL', 'wpv-views' ),
						'type' => 'url',
						'description' => __( 'URL to redirect to after retrieving the lost password. Defaults to the current URL. Redirect is only supported to the URLs within the current blog (or site). Redirection to external URLs (or sites) is not supported.', 'wpv-views' ),
					),
					'class' => array(
						'label' => __( 'Class', 'wpv-views' ),
						'type' => 'text',
						'description' => __( 'Space-separated list of class names that will be added to the anchor HTML tag.', 'wpv-views' ),
						'placeholder' => 'class1 class2',
					),
					'style' => array(
						'label' => __( 'Style', 'wpv-views' ),
						'type' => 'text',
						'description' => __( 'Inline styles that will be added to the anchor HTML tag.', 'wpv-views' ),
						'placeholder' => 'border: 1px solid red; font-size: 2em;',
					),
				),
				'content' => array(
					'label' => __( 'Link label', 'wpv-views' ),
					'description' => __( 'This will be displayed as a text or label for the link.', 'wpv-views' ),
					'default' => __('Lost password?', 'wpv-views'),
				),
			),
		),
	);
	return $data;
}

/**
 * wpv_whitelisted_domains
 *
 * Adds external domains to allowed redirect hosts, for safe redirection.
 * Required for all redirection attributes to work correctly for external domains.
 *
 * @param $content (array)
 * @return mixed
 *
 * @since 2.3
 */
function wpv_whitelisted_domains( $content, $location ) {
	$settings = WPV_Settings::get_instance();

	if ( isset( $settings->wpv_whitelist_domains ) && $settings->wpv_whitelist_domains != '' ) {
		$whitelisted = $settings->wpv_whitelist_domains;

		foreach( $whitelisted as $domain ) {
			// Check for wildcard characters
			// Only * is supported at this time.
			// @todo: Make it more robust by using Regex or probably the more intelligent solution
			$pos = strpos( $domain, '*' );

			if( false !== $pos ) {
				// If wildcard is in the beginning and followed by a . (dot)
				// there may be a chance of following use case:
				// - xyz.com
				// While the same wildcard is true for:
				// - www.xyz.com
				// - subdomain.xyz.com
				// So we need to get rid of the . (dot) in this case (only first dot after the wildcard)
				if ( $pos == 0 ) {
					$domain = substr_replace( $domain, '', $pos + 1, 1 );
				}

				// Create REGEX pattern.
				// 1) . (dot) in domain name should be escaped
				$pattern = str_replace('.', '\.', $domain);
				// 2) * should be tranlated to (.*?)
				$pattern = str_replace('*', '(.*?)', $pattern);

				// Test the pattern on $location
				preg_match('/'.$pattern.'+$/i', $location, $matches);

				if( isset( $matches[0] ) && !empty( $matches[0] ) ) {
					$content[] = $location;
				}
			} else {
				$content[] = $domain;
			}
		}
	}

	return $content;
}
add_filter('allowed_redirect_hosts', 'wpv_whitelisted_domains', 10, 2);

/**
 * Views-Shortcode: wpv-user
 *
 * Description: Display information for user from the user.
 *
 * Parameters:
 * 'field' => field_key

 *
 * Example usage:
 * Current user is [wpv-user name="custom_name"]
 * specified ID [wpv-user name="custom_name" id="1"]
 *
 * Link:
 *
 * Note:
 *
 * @since 2.4.0 Added the option to use [wpv-user field="profile_picture"] to fetch the user profile picture. The "field"
 *              attribute of the shortcode can take several values. If those values match a user column, we get that data.
 *              If not, we default to a usermeta field with that key. The "profile_picture" for the "field" attribute is
 *              neither a user column nor a usermeta field key, so we are reserving this value for a purpose that has no
 *              database match.
 *
 */

function wpv_user( $attr ) {

    $default_size = 96;

	extract(
        $attr = shortcode_atts(
			array(
			    'field' => 'display_name',
			    'id' => '',
                'size' => $default_size,
                'default-url' => '',
                'alt' => false,
                'shape' => 'circle',
			),
			$attr
		)
	);
	//Get data for specified ID
	if (
		isset( $id )
		&& ! empty( $id )
	) {
		if ( is_numeric( $id ) ) {
			$data = get_user_by( 'id', $id );
			if ( $data ) {
				$user_id = $id;
				if ( isset( $data->data ) ) {
					$data = $data->data;
					$meta = get_user_meta( $id );
				} else {
					return;
				}
			} else {
				return;
			}
		} else {
			return;
		}
	} else {
		global $WP_Views;
		if (
			isset( $WP_Views->users_data['term']->ID )
			&& ! empty( $WP_Views->users_data['term']->ID )
		) {
			$user_id = $WP_Views->users_data['term']->ID;
			$data = $WP_Views->users_data['term']->data;
			$meta = $WP_Views->users_data['term']->meta;
		} else {
			global $current_user;
			if ( $current_user->ID > 0 ) {
				$user_id = $current_user->ID;
				$data = new WP_User( $user_id );
				if ( isset( $data->data ) ) {
					$data = $data->data;
					$meta = get_user_meta( $user_id );
				} else {
					return;
				}
			} else {
				return;
			}
		}
	}
	$out = '';
	switch ( $field ) {
		case 'display_name':
			$out = $data->$field;
			break;
        case 'profile_picture':
            $out = wpv_get_avatar( $data->ID, $attr['size'], $attr['default-url'], $attr['alt'], $attr['shape'] );
            break;
		case 'user_login':
			$out = $data->$field;
			break;
		case 'first_name':
		case 'user_firstname':
			if ( isset( $meta['first_name']) ){
				$out = $meta['first_name'][0];
			}
			break;
		case 'last_name':
		case 'user_lastname':
			if ( isset( $meta['last_name']) ){
				$out = $meta['last_name'][0];
			}
			break;
		case 'nickname':
			if ( isset( $meta['nickname']) ){
				$out = $meta['nickname'][0];
			}
			break;
		case 'email':
		case 'user_email':
			$field = 'user_email';
			$out = $data->$field;
			break;
		case 'nicename':
		case 'user_nicename':
			$field = 'user_nicename';
			$out = $data->$field;
			break;
		case 'user_url':
			$out = $data->$field;
			break;
		case 'user_registered':
			$out = $data->$field;
			break;
		case 'user_status':
			$out = $data->$field;
			break;
		case 'spam':
			$out = isset( $data->$field ) ? $data->$field : '';
			break;
		case 'user_id':
		case 'ID':
			$out = $user_id;
			break;
		default:
			if ( isset( $meta[$field] ) ) {
				$out = $meta[$field][0];
			}
			break;
	}
	apply_filters( 'wpv_shortcode_debug','wpv-user', json_encode( $attr ), '', 'Data received from $WP_Views object', $out );
	return $out;
}

/**
* wpv_shortcodes_register_wpv_user_data
*
* Register the wpv-user shortcode in the GUI API.
*
* @since 1.9
*/

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_shortcodes_register_wpv_user_data' );

function wpv_shortcodes_register_wpv_user_data( $views_shortcodes ) {
	$views_shortcodes['wpv-user'] = array(
		'callback' => 'wpv_shortcodes_get_wpv_user_data'
	);
	return $views_shortcodes;
}

function wpv_shortcodes_get_wpv_user_data( $parameters = array(), $overrides = array() ) {

    $data = array(
        'user-selection'	=> true
    );

    $profile_picture_in_parameters = isset( $parameters['attributes'] )
        && isset( $parameters['attributes']['field'] )
        && 'profile_picture' == $parameters['attributes']['field'];

    $profile_picture_in_overrides = isset( $overrides['attributes'] )
        && isset( $overrides['attributes']['field'] )
        && 'profile_picture' == $overrides['attributes']['field'];

    if ( $profile_picture_in_parameters || $profile_picture_in_overrides ) {
        $data['attributes'] = array(
            'display-options' => array(
                'label' => __( 'Display options', 'wpv-views' ),
                'header' => __( 'Display options', 'wpv-views' ),
                'fields' => array(
                    'size' => array(
                        'label' => __( 'Size', 'wpv-views' ),
                        'type' => 'text',
                        'description' => __( 'Size of the profile picture in pixels.', 'wpv-views' ),
                    ),
                    'alt' => array(
                        'label' => __( 'Alternative text', 'wpv-views' ),
                        'type' => 'text',
                        'description' => __( 'Alternative text for the profile picture.', 'wpv-views' ),
                    ),
                    'shape' => array(
                        'label' => __( 'Shape', 'wpv-views'),
                        'type' => 'select',
                        'options' => array(
                            'circle' => __( 'Circle', 'wpv-views' ),
                            'square' => __( 'Square', 'wpv-views' ),
                            'custom' => __( 'Custom', 'wpv-views' ),
                        ),
                        'default' => 'circle',
                        'description' => __( 'Display the profile picture in this shape. For "custom" shape, custom CSS is needed for "wpv-profile-picture-shape-custom" CSS class.', 'wpv-views' ),
                    ),
                    'default-url' => array(
                        'label' => __( 'Default URL', 'wpv-views' ),
                        'type' => 'text',
                        'description' => __( 'Default url for an image. Leave blank for the "Mystery Man".', 'wpv-views' )
                    ),
                ),
            ),
        );
    }

	$dialog_label = __( 'User data', 'wpv-views' );
	$dialog_target = false;

	if ( isset( $parameters['attributes']['field'] ) ) {
		$dialog_target = $parameters['attributes']['field'];
	}
	if ( isset( $overrides['attributes']['field'] ) ) {
		$dialog_target = $overrides['attributes']['field'];
	}

	if ( $dialog_target ) {
		$dialog_label = wpv_shortcodes_get_wpv_user_data_title( $dialog_target );
	}

	$data['name'] 	= $dialog_label;
	$data['label']	= $dialog_label;

    return $data;
}

function wpv_shortcodes_get_wpv_user_data_title( $field ) {

	$title = __( 'User data', 'wpv-views' );

	switch ( $field ) {
		case 'ID':
			$title = __( 'User ID', 'wpv-views' );
			break;
		case 'user_email':
			$title = __( 'User Email', 'wpv-views' );
			break;
		case 'user_login':
			$title = __( 'User Login', 'wpv-views' );
			break;
		case 'user_firstname':
			$title = __( 'First Name', 'wpv-views' );
			break;
		case 'user_lastname':
			$title = __( 'Last Name', 'wpv-views' );
			break;
		case 'nickname':
			$title = __( 'Nickname', 'wpv-views' );
			break;
		case 'display_name':
			$title = __( 'Display Name', 'wpv-views' );
			break;
        case 'profile_picture':
            $title = __( 'Profile Picture', 'wpv-views' );
            break;
		case 'user_nicename':
			$title = __( 'Nicename', 'wpv-views' );
			break;
		case 'description':
			$title = __( 'Description', 'wpv-views' );
			break;
		case 'yim':
			$title = __( 'Yahoo IM', 'wpv-views' );
			break;
		case 'jabber':
			$title = __( 'Jabber', 'wpv-views' );
			break;
		case 'aim':
			$title = __( 'AIM', 'wpv-views' );
			break;
		case 'user_url':
			$title = __( 'User URL', 'wpv-views' );
			break;
		case 'user_registered':
			$title = __( 'Registration Date', 'wpv-views' );
			break;
		case 'user_status':
			$title = __( 'User Status', 'wpv-views' );
			break;
		case 'spam':
			$title = __( 'User Spam Status', 'wpv-views' );
			break;
	}

	return $title;

}

/**
 * Get permalink for given post with respect to it's status.
 *
 * Appends "preview=true" argument to the permalink for drafts and pending posts. In all other aspects it behaves
 * exactly like get_permalink().
 *
 * @see http://codex.wordpress.org/Function_Reference/get_permalink
 * @see https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/190442712/comments#comment_296475746
 *
 * @param $post_id int ID of an existing post.
 *
 * @return The permalink URL or false on failure.
 *
 * @since 1.7
 *
 * @todo Add support for custom post types.
 * @todo Move to an Utils class/file
 */
function wpv_get_post_permalink( $post_id ) {

	$post_link = get_permalink( $post_id );
	if( false == $post_link ) {
		return false;
	}

	$post_status = get_post_status( $post_id );

	switch( $post_status ) {

		case 'draft':
		case 'pending':
			// append preview=true argument to permalink
			$post_link = esc_url( add_query_arg( array( 'preview' => 'true' ), $post_link ) );
			break;

		default: // also when get_post_status fails and returns false, which should never happen
			// do nothing
			break;
	}

	return $post_link;
}

/**
 * wpv_get_avatar
 *
 * Return the avatar based on the provided arguments
 *
 * @param $user_id (integer) The ID of the user, either post author or user from a users listing
 * @param $atts (atts) Shortcode arguments like "size", "alt", "default_url" and "shape".
 *
 * @return (string) Image HTML element that contains the avatar of the selected user.
 *
 * @since 2.4.0
 *
 * @todo Move to an Utils class/file
 */

function wpv_get_avatar( $user_id, $size, $default_url, $alt, $shape ) {

    $default_size = 96;

    $size = intval( $size );
    if ( $size <= 0 || $size > 512 ) {
        $size = $default_size;
    }

    $args = array();

    if ( isset( $shape ) && '' != $shape && in_array( $shape, array( 'circle', 'square', 'custom' ) ) ) {
        $args['class'] = 'wpv-profile-picture-shape-' . $shape;
    }

    return get_avatar( $user_id, $size, $default_url, $alt, $args );
}

/**
 * Scale down an image to fit a particular size and save a new copy of the image.
 * Uses WP_Image_Editor class.
 *
 * @param string $file Image file path.
 * @param int $max_w Maximum width to resize to.
 * @param int $max_h Maximum height to resize to.
 * @param bool $crop Optional. Whether to crop image or resize.
 * @param string $suffix Optional. File suffix.
 * @param string $dest_path Optional. New image file path.
 * @param int $jpeg_quality Optional, default is 90. Image quality percentage.
 * @return string|WP_Error WP_Error on failure. String with new destination path.
 * @since 2.2.0
 * @deprecated 2.8.1 Use \OTGS\Toolset\Views\Model\Wordpress\Media::resize_image instead
 * @see https://codex.wordpress.org/Class_Reference/WP_Image_Editor
 */
function wpv_image_resize( $file, $max_w, $max_h, $crop = false,
							$suffix = null, $dest_path = null, $jpeg_quality = 90 ) {

	$image = wp_get_image_editor( $file ); // Return an implementation that extends WP_Image_Editor

	if ( ! is_wp_error( $image ) ) {
		if ( !$suffix ) {
			$suffix = $image->get_suffix();
		}

		$new_file = $image->generate_filename( $suffix, $dest_path );

		$image->set_quality( $jpeg_quality );
		$image->resize( $max_w, $max_h, $crop );
		$image->save( $new_file );

		return $new_file;
	} else {
		return new WP_Error( 'error_loading_image', $image, $file );
	}
}

/**
 * Generate an HTML tag with attributes.
 * Also supports self-enclosure of tags or tags enclosing content.
 *
 * @param $tag              string An HTML tag
 * @param $attrs            array  Array of HTML tag attributes (i.e. class, src, width, height, alt and etc)
 * @param $self_enclosure   bool   Is the tag self enclosed (i.e. <img ... />
 * @param $optional_content string In case if tag isn't self enclosed (i.e. <p>...</p>), the content can be passed to be surrounded by the opening/closing tags as-it-is.
 *
 * @return string
 *
 * @since 2.2.0
 *
 * @todo Move to an Utils class/file
 */
function wpv_get_html_tag( $tag = 'img', $attrs = array(), $self_enclosure = true, $optional_content = '' ) {
	$out = '<' . $tag . ' ';

	foreach( $attrs as $key => $val ) {
		$out .= $key . '="' . $val . '" ';
	}

	if( $self_enclosure ) {
		$out .= '/>';
	} else {
		$out .= '>' . $optional_content . '</' . $tag . '>';
	}

	return $out;
}

/**
 * Register the wpv-post-next-link shortcode in the GUI API.
 *
 * @param string $format 	The link anchor format.
 * @param string $link		The link permalink format.
 *
 * @return array The array containing the link anchor and permalink format.
 *
 * @since 2.4.1
 */
function process_post_navigation_shortcode_placeholders( $format, $link ) {
	$format = str_replace( '%%LINK%%', '%link', $format );
	$link = str_replace( '%%TITLE%%', '%title', $link );
	$link = str_replace( '%%DATE%%', '%date', $link );
	return array(
		'format' => $format,
		'link' => $link,
	);
}

function wpv_shortcode_wpv_comment_title($atts){
	$post_id_atts = new WPV_wpcf_switch_post_from_attr_id($atts);


}

function wpv_shortcode_wpv_comment_body($atts){
	$post_id_atts = new WPV_wpcf_switch_post_from_attr_id($atts);


}

function wpv_shortcode_wpv_comment_author($atts){
	$post_id_atts = new WPV_wpcf_switch_post_from_attr_id($atts);


}

function wpv_shortcode_wpv_comment_date($atts){
	$post_id_atts = new WPV_wpcf_switch_post_from_attr_id($atts);


}

function wpv_do_shortcode($content, $doing_excerpt = false ) {

  $content = apply_filters('wpv-pre-do-shortcode', $content);

  // HACK HACK HACK
  // fix up a problem where shortcodes are not handled
  // correctly by WP when there a next to each other
  $content = str_replace('][', ']###SPACE###[', $content);
  $content = str_replace(']###SPACE###[/', '][/', $content);
  $content = do_shortcode($content);
  $content = str_replace('###SPACE###', '', $content);

	// Repair block attributes.
	$content = preg_replace_callback(
		'/<!--(.*?)(-->?)/s',
		function( $matches ) {
			return html_entity_decode( $matches[0] );
		},
		$content
	);

	// Do blocks of shortcodes return.
	$content = do_blocks( $content );

  // This filter is used to inject <style> for TB blocks inside a view on the backend
  $content = apply_filters( 'wpv-post-do-shortcode', $content, $doing_excerpt );

  return $content;
}

add_shortcode('wpv-filter-order', 'wpv_filter_shortcode_order');
function wpv_filter_shortcode_order($atts){
	extract(
		shortcode_atts( array(), $atts )
	);

	global $WP_Views;
	$view_settings = $WP_Views->get_view_settings();

	$view_settings = apply_filters( 'wpv_filter_wpv_apply_post_view_sorting', $view_settings, $view_settings, null );
	$order_selected = $view_settings['order'];

	$orders = array('DESC', 'ASC');
	return wpv_filter_show_user_interface('wpv_order', $orders, $order_selected, $atts['style']);
}

add_shortcode('wpv-filter-types-select', 'wpv_filter_shortcode_types');
function wpv_filter_shortcode_types($atts){
	extract(
		shortcode_atts( array(), $atts )
	);

	global $WP_Views;
	$view_settings = $WP_Views->get_view_settings();

	$view_settings = wpv_filter_get_post_types_arg($view_settings, $view_settings);
	$post_types_selected = $view_settings['post_type'];

	$post_types = get_post_types(array('public'=>true));
	return wpv_filter_show_user_interface('wpv_post_type', $post_types, $post_types_selected, $atts['style']);
}

/**
 * Add a shortcode for the search input from the user
 *
 */

add_shortcode('wpv-filter-search-box', 'wpv_filter_search_box');
function wpv_filter_search_box($atts){
	$atts = shortcode_atts( array(
		'style'		=> '',
		'class'		=> '',
		'output'	=> 'legacy',
		'placeholder' => ''
	), $atts );

	// @refactoring please don't rely on this!
	extract( $atts );
	$class = toolset_getarr( $atts, 'class' );

	$view_settings = apply_filters( 'wpv_filter_wpv_get_object_settings', array() );

	$return = '';

    if ( ! empty( $style ) ) {
        $style = ' style="'. esc_attr( $style ) .'"';
    }

	if ( ! empty( $class ) ) {
		$class = ' ' . esc_attr( $class ) . '';
	}
	if ( 'bootstrap' == toolset_getarr( $atts, 'output' ) ) {
		$class .= ' form-control';
	}

	if ( ! empty( $placeholder ) ) {
		$view_id     = apply_filters( 'wpv_filter_wpv_get_current_view', null );
		$view_name   = get_post_field( 'post_name', $view_id );
		$item_value  = wpv_translate( 'search_input_placeholder', $atts['placeholder'], false, 'View ' . $view_name );
		$placeholder = ' placeholder="' . esc_attr( $item_value ) . '"';
	}

	$query_mode = 'posts';

	if (
		! isset( $view_settings['view-query-mode'] )
		|| 'normal' == $view_settings['view-query-mode']
	) {
		$query_mode = $view_settings['query_type'][0];
	}

	switch ( $query_mode ) {
		case 'posts':
			if (
				isset( $view_settings['post_search_value'] )
				&& isset( $view_settings['search_mode'] )
				&& $view_settings['search_mode'] == 'specific'
			) {
				$value = 'value="' . $view_settings['post_search_value'] . '"';
			} else {
				$value = '';
			}
			if ( isset( $_GET['wpv_post_search'] ) ) {
				$value = 'value="' . esc_attr( wp_unslash( $_GET['wpv_post_search'] ) ) . '"';
			}
			$return = '<input type="text" name="wpv_post_search" ' . $value . ' class="js-wpv-filter-trigger-delayed'.  $class . '"'. $style . $placeholder .' />';
			break;
		case 'taxonomy':
			if (
				isset( $view_settings['taxonomy_search_value'] )
				&& isset( $view_settings['taxonomy_search_mode'] )
				&& $view_settings['taxonomy_search_mode'] == 'specific'
			) {
				$value = 'value="' . $view_settings['taxonomy_search_value'] . '"';
			} else {
				$value = '';
			}
			if ( isset( $_GET['wpv_taxonomy_search'] ) ) {
				$value = 'value="' . esc_attr( $_GET['wpv_taxonomy_search'] )  . '"';
			}
			$return = '<input type="text" name="wpv_taxonomy_search" ' . $value . ''.  $class . $style . $placeholder .'/>';
			break;
	}

	return $return;
}

/**
 * Add the wpv-filter-search-box shortcode to the shortcodes GUI API.
 *
 * @since 2.4.0
 */

add_filter( 'wpv_filter_wpv_shortcodes_gui_data', 'wpv_filter_search_box_shortcode_register_gui_data' );

function wpv_filter_search_box_shortcode_register_gui_data( $views_shortcodes ) {
	$views_shortcodes['wpv-filter-search-box'] = array(
		'callback' => 'wpv_filter_search_box_shortcode_get_gui_data'
	);
	return $views_shortcodes;
}

function wpv_filter_search_box_shortcode_get_gui_data( $parameters = array(), $overrides = array() ) {

	$post_search_content_options = WPV_Search_Frontend_Filter::get_post_search_content_options();
	$post_search_content_options_gui = array();
	foreach ( $post_search_content_options as $post_search_content_options_key => $post_search_content_options_data ) {
		$post_search_content_options_gui[ $post_search_content_options_key ] = $post_search_content_options_data['label'];
	}

	$data = array(
		'attributes' => array(
			'target-options' => array(
				'label' => __( 'Filter options', 'wpv-views' ),
				'header' => __( 'Filter options', 'wpv-views' ),
				'fields' => array(
					'value_where' => array(
						'label'		=> __( 'Where to search', 'wpv-views'),
						'type'		=> 'radio',
						'default'	=> 'full_content',
						'options'	=> $post_search_content_options_gui,
						'description' => __( 'Adjust whether you want to search only in post titles or also in posts content.', 'wpv-views' ),
					),
					'value_label' => array(
						'label'		=> __( 'Label for the search box', 'wpv-views' ),
						'type'		=> 'text',
						'placeholder'	=> __( 'Search', 'wpv-views' ),
						'hidden'	=> true,
					),
					'placeholder' => array(
						'label'			=> __( 'Placeholder', 'wpv-views'),
						'type'			=> 'text',
						'default'	    => '',
						'required'		=> false
					),
				),
			),
			'style-options' => array(
				'label' => __( 'Style options', 'wpv-views' ),
				'header' => __( 'Style options', 'wpv-views' ),
				'fields' => array(
					'output' => array(
						'label'		=> __( 'Output style', 'wpv-views' ),
						'type'		=> 'radio',
						'options'	=> array(
							'legacy'	=> __( 'Raw search input', 'wpv-views' ),
							'bootstrap'	=> __( 'Fully styled search input', 'wpv-views' )
						),
						'default_force'	=> 'bootstrap'
					),
					'class_style_combo' => array(
						'label'		=> __( 'Element styling', 'wpv-views' ),
						'type'		=> 'grouped',
						'fields'	=> array(
							'class' => array(
								'pseudolabel'	=> __( 'Input classnames', 'wpv-views'),
								'type'			=> 'text',
								'description'	=> __( 'Use this to add your own classnames to the text input.', 'wpv-views' )
							),
							'style' => array(
								'pseudolabel'	=> __( 'Input style', 'wpv-views'),
								'type'			=> 'text',
								'description'	=> __( 'Use this to add your own styling to the text input.', 'wpv-views' )
							),
						),
					),
				),
			),
		),
	);

	$gui_action = isset( $_GET['gui_action'] ) ? sanitize_text_field( $_GET['gui_action'] ) : '';

	if ( 'insert' === $gui_action ) {
		if ( 'true' === toolset_getget( 'has_shortcode' ) ) {
			// If we are inserting (meaning, coming from the toolbar button), but a shortcode already exists
			// then we need not to offer styling attributes, nor label inputs.
			unset( $data['attributes']['style-options'] );
			unset( $data['attributes']['target-options']['fields']['value_label'] );
		}
	} else if ( 'edit' === $gui_action ) {
		// We need not to offer label inputs
		unset( $data['attributes']['target-options']['fields']['value_label'] );
	}



	$dialog_label = __( 'Text search filter', 'wpv-views' );

	$data['name']	= $dialog_label;
	$data['label']	= $dialog_label;

	return $data;
};

/*
add_shortcode('wpml-breadcrumbs', 'wpv_wpml_breadcrumbs');
function wpv_wpml_breadcrumbs($atts, $value){
	ob_start();

	global $iclCMSNavigation;
	if (isset($iclCMSNavigation)) {
		$iclCMSNavigation->cms_navigation_breadcrumb('');
	}

	$result = ob_get_clean();

	return $result;
}
*/

add_shortcode('yoast-breadcrumbs', 'wpv_yoast_breadcrumbs');
function wpv_yoast_breadcrumbs($atts, $value){

	if ( function_exists('yoast_breadcrumb') ) {
		return yoast_breadcrumb("","",false);
	}

	return '';
}


/** Output value of current View's attribute.
 *
 * @param array $atts {
 *	 Shortcode attributes.
 *
 *	 @string $name Name of the attribute of current View.
 * }
 *
 * @return Attribute value or an empty string if no such attribute is set.
 *
 * @since 1.7
 */
add_shortcode( 'wpv-attribute', 'wpv_attribute' );
function wpv_attribute( $atts, $value ) {
	global $WP_Views;
	extract( shortcode_atts(
			array( 'name' => '' ),
			$atts ) );

	$view_atts = $WP_Views->get_view_shortcodes_attributes();

	if( '' == $name || !array_key_exists( $name, $view_atts ) ) {
		return '';
	}

	return $view_atts[ $name ];
}
