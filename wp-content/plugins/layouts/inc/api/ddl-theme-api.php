<?php
/**
 *
 * the_ddlayout
 *
 * Renders and echos the layout.
 *
 */



function the_ddlayout($layout = '', $args = array() ) {
    echo get_the_ddlayout($layout, $args);
}
add_action('the_ddlayout', 'the_ddlayout');
/**
 * get_the_ddlayout
 *
 * Gets the layout
 *
 */

function get_the_ddlayout($layout = '', $args = array()) {

    global $wpddlayout;

    $queried_object = $wpddlayout->get_queried_object();
    $post = $wpddlayout->get_query_post_if_any( $queried_object);

    if( null !== $post && $post->post_type === 'page' )
    {
        $template = basename( get_page_template() );

        $wpddlayout->save_option(array('templates' => array($template => $layout)));
    }

    if (!isset($args['initialize_loop']) || $args['initialize_loop'] != false) {
        
        // setup the loop
        if ( is_single() || is_page() ) {
            have_posts();
            the_post();
            rewind_posts();
        }
    }
    
    $content = $wpddlayout->get_layout_content_for_render( $layout, $args );

    return $content;
}
add_filter('get_the_ddlayout', 'get_the_ddlayout', 10, 2);

/**
 * @return bool
 * to be used in template files or with template redirect hook to check whether current page has a layout template
 */
function is_ddlayout_template( )
{

    $temp = get_page_template();

    $pos = strrpos ( $temp , '/' );

    $template = substr ($temp , $pos+1 );

    return apply_filters( 'ddl-is_ddlayout_template', in_array( $template, WPDD_Layouts::templates_have_layout( array( $template => 'name') ) ) );
}

/**
 * generic version of the preceeding
 * @return bool
 */
function has_current_post_ddlayout_template( )
{
    global $template;
    $template = basename($template);
    return apply_filters( 'ddl-has_current_post_ddlayout_template', in_array( $template, WPDD_Layouts::templates_have_layout( array( $template => 'name') ) ) );
}

function is_ddlayout_assigned()
{
    return apply_filters( 'ddl-is_ddlayout_assigned', WPDD_Layouts_RenderManager::getInstance()->item_has_ddlayout_assigned() );
}

function has_ddlprivate_assigned(){
	if( is_single() === false && is_page() === false ){
		return false;
	}

	global $post;

	if( WPDD_Utils::is_wp_post_object( $post ) === false ){
		return false;
	}

	return apply_filters( 'ddl-page_has_private_layout', $post->ID );
}

function is_private_ddlayout_assigned(){
	return apply_filters( 'ddl-is_private_ddlayout_assigned', has_ddlprivate_assigned() );
}

add_filter('is_ddlayout_assigned', 'is_ddlayout_assigned');
add_filter('is_private_ddlayout_assigned', 'is_private_ddlayout_assigned');

function ddlayout_set_framework ( $framework ) {
    $framework_manager = WPDD_Layouts_CSSFrameworkOptions::getInstance();

    $framework_manager->theme_set_framework( $framework );
}

function user_can_create_layouts(){
    return WPDD_Layouts_Users_Profiles::user_can_create();
}
function user_can_assign_layouts(){
    return WPDD_Layouts_Users_Profiles::user_can_assign();
}
function user_can_edit_layouts(){
    return WPDD_Layouts_Users_Profiles::user_can_edit();
}
function user_can_edit_content_layouts( $layout_id ){
	return WPDD_Layouts_Users_Profiles::user_can_edit_content_layout( $layout_id );
}
function user_can_delete_layouts(){
    return WPDD_Layouts_Users_Profiles::user_can_delete();
}
function user_can_create_private_layouts(){
	return WPDD_Layouts_Users_Profiles_Private::user_can_create();
}
function user_can_delete_private_layouts(){
	return WPDD_Layouts_Users_Profiles_Private::user_can_delete();
}
function user_can_edit_private_layouts(){
	return WPDD_Layouts_Users_Profiles_Private::user_can_edit();
}
function ddl_layout_slug_exists( $slug ){
    $res = WPDD_Layouts::get_post_ID_by_slug( $slug, WPDDL_LAYOUTS_POST_TYPE );
    return $res != null && $res != 0 && $res != false;
}
function get_layout_for_post_object( ){
    global $post, $wpddlayout;

    if( is_object($post) === false ) return null;

    return $wpddlayout->get_layout_slug_for_post_object( $post->ID );
}