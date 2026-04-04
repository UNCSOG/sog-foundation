<?php
/*
Plugin Name: SOG Help Tabs
Description: Provides a tabbed page to add instructions or whatever else you want to display. 
Version: 1.1
Author: Darren Goroski
*/

namespace sog_help_posts;

use sog_settings;

ini_set('display_errors', 0);
// error_reporting(E_ALL);
// error_reporting(E_ERROR);

//==== ************ Load stylesheets ******************//
function sog_help_tabs_styles() {
    wp_enqueue_style( 'sog_help_tabs_css',  plugin_dir_url( __FILE__ ) . 'inc/css/style.css' );     //some styling for the plugin
    wp_enqueue_style( 'sog_branding_css',  plugin_dir_url( __FILE__ ) . 'inc/css/style_sog.css' );  //SOG branded colors                  
}
add_action( 'admin_enqueue_scripts', 'sog_help_posts\sog_help_tabs_styles' );
add_action( 'wp_enqueue_scripts', 'sog_help_posts\sog_help_tabs_styles' );

//Load js
function sog_help_tabs_scripts() {
    wp_enqueue_script( 'sog_help_tabs_js',  plugin_dir_url( __FILE__ ) . 'inc/js/js.js',array('jquery'),time() );                

    //this makes certain php values available as an js object, may not be needed for this project
	wp_localize_script('sog_help_tabs_js', 'sog_help_tabs_js_wordpress_vars', array('plugin_path' => plugin_dir_url(__FILE__),"siteurl"=>get_option('siteurl')));
}
add_action( 'admin_enqueue_scripts', 'sog_help_posts\sog_help_tabs_scripts' );
add_action( 'wp_enqueue_scripts', 'sog_help_posts\sog_help_tabs_styles' );


//==== ************ Create Custom Post ******************//

function create_posttype() {
    register_post_type( 'sog_help_posts',
        array(
            'labels' => array(
                'name' => __( 'SOG Help Posts' ),
                'singular_name' => __( 'SOG Help Post' )
            ),
            'public' => true,
			'menu_icon' => 'dashicons-editor-help',
            'has_archive' => true,
            'rewrite' => array('slug' => 'sog_help_posts'),
            'show_in_rest' => true,
 
        )
    );
}
add_action( 'init', 'sog_help_posts\create_posttype' );
 
function custom_post_type() {
    $labels = array(
        'name'                => _x( 'SOG Help Posts', 'Post Type General Name' ),
        'singular_name'       => _x( 'SOG Help Post', 'Post Type Singular Name' ),
        'menu_name'           => __( 'SOG Help Posts' ),
        'all_items'           => __( 'All SOG Help Posts' ),
        'view_item'           => __( 'View SOG Help Post' ),
        'add_new_item'        => __( 'Add New SOG Help Post' ),
        'add_new'             => __( 'Add New' ),
        'edit_item'           => __( 'Edit SOG Help Post' ),
        'update_item'         => __( 'Update SOG Help Post' ),
        'search_items'        => __( 'Search SOG Help Post' ),
        'not_found'           => __( 'Not Found' ),
        'not_found_in_trash'  => __( 'Not found in Trash' ),
    );
    $args = array(
        'label'               => __( 'SOG Help Posts' ),
        'description'         => __( 'SOG Help Posts' ),
        'labels'              => $labels,
        // Features this CPT supports in Post Editor
        'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields', ),
        'public'              => true,
        'capability_type'     => 'post',
        'map_meta_cap' => true,
        'show_in_rest' => true,
		'capabilities' => array(
			'edit_post'          => 'edit_other_posts',
			'read_post'          => 'edit_other_posts',
			'delete_post'        => 'edit_other_posts',
			'edit_posts'         => 'edit_other_posts',
			'edit_others_posts'  => 'edit_other_posts',
			'delete_posts'       => 'edit_other_posts',
			'publish_posts'      => 'edit_other_posts',
			'read_private_posts' => 'edit_other_posts'
		),
 
    );
     
    register_post_type( 'sog_help_posts', $args );
}
add_action( 'init', 'sog_help_posts\custom_post_type', 0 );

//add custom menus
function help_tabs_menu_item() { 
    // echo "<br>read_help_post".current_user_can( 'read_help_post' );

    add_submenu_page( 
        'edit.php?post_type=sog_help_posts',    //parent_slug
        'Help Tabs',                            //page_title
        'The Tabs',                             //menu_title
        'edit_posts',                           //capability
        'tabs_menu_slug',                       //menu_slug
        'sog_help_posts\display_help_tabs',                    //callback
        10                                      //position
       );
       
       //instructions page for admins
       add_submenu_page( 
        'edit.php?post_type=sog_help_posts',    //parent_slug
        'Instructions',                        //page_title
        'Instructions',                        //menu_title
        'manage_options',                         //capability This menu is only for admins
        'instructions_slug',                   //menu_slug
        'sog_help_posts\display_help_tabs_instructions',      //callback
        11                                      //position
       );
  
  }
add_action('admin_menu', 'sog_help_posts\help_tabs_menu_item');


//create 2 new menu colums for Admin and Front
function custom_columns($columns){
    return array_merge(
        $columns,
        array(
            'admin' => __('Admin Page'),
            'front' => __('Front End'),
        )
    );
}
add_filter('manage_sog_help_posts_posts_columns', 'sog_help_posts\custom_columns');

function display_sog_help_posts_custom_columns($column, $post_id)
{
    switch ($column) {
        case 'admin':
            if (get_post_meta($post_id, '_checkbox_admin_page', true)) {echo "Yes";};
            break;
        case 'front':
            if (get_post_meta($post_id, '_checkbox_front_end', true)) {echo "Yes";};
            break;
    }
}
add_action('manage_sog_help_posts_posts_custom_column', 'sog_help_posts\display_sog_help_posts_custom_columns', 10, 2);



//==== ************ Create meta checkbox ******************//
//This add meta box code came from ChatGPT
    function sog_help_tabs_add_meta_box() {
        add_meta_box(
            'sog_help_posts_checkbox',
            'Additional Option',
            'sog_help_posts\sog_help_tabs_checkbox_callback',
            'sog_help_posts', // Custom post type
            'normal',
            'high'
        );
    }add_action( 'add_meta_boxes', 'sog_help_posts\sog_help_tabs_add_meta_box' );

    // Meta Box Callback
    function sog_help_tabs_checkbox_callback( $post ) {
        // Get the current value of the checkbox
        $checkbox_admin_page = get_post_meta( $post->ID, '_checkbox_admin_page', true );
        $checkbox_front_end = get_post_meta( $post->ID, '_checkbox_front_end', true );
    
        // Use nonce for verification
        wp_nonce_field( plugin_basename( __FILE__ ), 'sog_help_tabs_nonce' );

        // Output the checkboxes

        ?>
            <div>
                <label for="checkbox_admin_page">
                    <input type="checkbox" id="checkbox_admin_page" name="checkbox_admin_page" <?php echo checked( $checkbox_admin_page, 1, false );?> />
                    Admin Help Page
                </label>
            </div>
            <div>
                <label for="_checkbox_front_end">
                    <input type="checkbox" id="_checkbox_front_end" name="_checkbox_front_end" <?php echo checked( $checkbox_front_end, 1, false );?> />
                    Front End Help Page
                </label>
            </div>
        <?php
    }

    // Save Meta Box Data
    function sog_help_tabs_save_meta_box_data( $post_id ) {
        // Check if nonce is set
        if ( ! isset( $_POST['sog_help_tabs_nonce'] ) ) {
            return;
        }
    
        // Verify that the nonce is valid
        if ( ! wp_verify_nonce( $_POST['sog_help_tabs_nonce'], plugin_basename( __FILE__ ) ) ) {
            return;
        }
    
        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
    
        // Check the user's permissions
        if ( isset( $_POST['post_type'] ) && 'sog_help_tabs' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }
    
        // Save the checkbox values
        $checkbox_admin_page_value = isset( $_POST['checkbox_admin_page'] ) ? 1 : 0;
        update_post_meta( $post_id, '_checkbox_admin_page', $checkbox_admin_page_value );
    
        $checkbox_front_end_value = isset( $_POST['_checkbox_front_end'] ) ? 1 : 0;
        update_post_meta( $post_id, '_checkbox_front_end', $checkbox_front_end_value );
    }
    add_action( 'save_post', 'sog_help_posts\sog_help_tabs_save_meta_box_data' );



//==== ************ Create shortcode to display on front end ******************//

//add shortcode and pass atts through to display function
add_shortcode('add_help_posts_here', function($atts){

    //if there are no atts, create an empty array so i can add from_shortcode to it
    if (!isset($atts) or !is_array($atts)){
        $atts=[];
    }

    $atts['from_shortcode']=1;

    //buffer the output so it can be returned instead of echo'd
    ob_start();
        display_help_tabs($atts);
    $html = ob_get_clean();
return $html;
});

function display_help_tabs($atts){
    /*
        The shortcode attribute "which" will hold "front" or "admin" and will pull those posts accordingly
        If called by the shortcode it will display whichever is passed in, and if nothing, it assumes the shortcode is being used on the front end and will default to front.
        If not called by the shortcode it assumes this is the built in help page on the admin screens and show the Admin posts.
    */

    $which_checkbox=$active_class=$posts=$selected_post_id=$selected_post_content="";

   	//determine which posts to display, admin or front
	if (isset($atts['from_shortcode']) and $atts['from_shortcode']) {
        //Is coming from shortcode, so check which to pull 
        if (isset($atts['where']) and $atts['where']=="front"){
            //which is front so pull front end posts
            $which_checkbox="_checkbox_front_end";
        }elseif (isset($atts['where']) and $atts['where']=="admin"){
            //which is admin so pull admin posts
            $which_checkbox="_checkbox_admin_page";
        }else{
            //neither so default to front end
            $which_checkbox="_checkbox_front_end";
        }
	}else{
        //Is not coming from shortcode to default to admin psots
        $which_checkbox="_checkbox_admin_page";
	}

    // echo "<br>which_checkbox:".$which_checkbox;

	//Get the selected tab from the $_GET param
	if (isset($_GET['tab']) and $_GET['tab']) {
		$tab_selected=$_GET['tab'];
	}else{
		$tab_selected=null;
	}

	//gather all posts of this type
	$posts=get_help_posts(array("which_checkbox"=>$which_checkbox));
	// echo "<pre>";print_r($posts);echo "</pre>";

?>
	<!-- // create header on page -->
	<div class="help_tabs_header">
		<h1>
			SOG Help Tabs
		</h1>
		<h2>
			UNC School of Government
		</h2>
	</div>

	<!-- create wrapper for tabs to display -->
	<div class="wrap" id="sog_help_posts">
		<nav class="nav-tab-wrapper nav-tabs">

			<!-- Loop through all posts and output the title as the tab content -->
			<?php 
				$count=0;
				foreach ($posts as $post) { 
					$count++;

					//If nothing is selected then set default
					if ($tab_selected==null) {
						if ($count==1){
							//if this is the first one, then use as the defeault
							$active_class			="nav-tab-active bg-sog_blue bg-primary text-white";
							$selected_post_id		=$posts[0]->ID;
							$selected_post_content	=$posts[0]->post_content;
						}else{
							//this is not the 1st so remove the active class
							$active_class="";
						}
					}else{
						//something is selected so check which one
						if($tab_selected==$post->post_name) {
							//if this is the chosen one, set it's ID and Content to use later
							$active_class			="nav-tab-active bg-sog_blue bg-primary text-white";
							$selected_post_id		=$post->ID;
							$selected_post_content	=$post->post_content;
						}else{
							//if none are selected, defaullt to the first post
							$active_class		="";
							$selected_post_id	="";
						}
					}

				?>
					<!-- Output the tab link and name -->
					<a href="<?php echo sog_help_append_qv("tab",$post->post_name,$_SERVER['REQUEST_URI']);?>"
						class="btn btn-secondary nav-tab <?php echo $active_class;?>">
							<?php echo $post->post_title;?>
					</a>
			<?php } ?>
		</nav>

		<!-- Output the content of the help post using the saved content from the loop above -->
		<div class="tab-content">
			<?php echo $selected_post_content;?>
		</div>
	</div>

<?php
}

function get_help_posts($data) {
	//this is a standard WP query to gather posts of a custom post type
	$args = array(
		'post_type' 		=> 'sog_help_posts',
		'orderby' 			=> 'post_date',
		'order' 			=> 'ASC',
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
        'meta_query'     => array(
            'relation' => 'AND', // Use 'AND' or 'OR' as per your needs
            array(
                'key'     => $data['which_checkbox'],
                'value'   => 1, // Check if checkbox1 is checked
                'compare' => '=',
                'type'    => 'NUMERIC',
            ),
        ),
	);

	$query = new \WP_Query($args);
return $query->posts;
}

function display_help_tabs_instructions(){

    ?>
        <div class="admin_header_help">
            <h3>Shortcode</h3>
            <p>
                You only need this if you want to display help posts on the front end, or if you want the help posts to appear somewhere else in the WordPress dashboard.
            </p>
            <ul>
                <li>To use on front end call the shortcode <code>[add_help_posts_here]</code>
                <li>When using the Shortcode it defaults to showing front end posts but you can optionally add a "where" attribute to change this.
                        <ul>
                            <li><code>[add_help_posts_here where=admin]</code> will display the posts marked as admin.
                            <li><code>[add_help_posts_here where=front]</code> will display the posts marked as front end.
                        </ul>
            </ul>
        </div>
    <?php

}

// this is a helper function to create urls with query variables on urls
function sog_help_append_qv($qv_name,$qv_value,$url_to_append) {
    $url_parts = parse_url($url_to_append);
    parse_str($url_parts['query'] ?? "", $params);
    
    $params[$qv_name] = $qv_value;     // Overwrite if exists
    
    // Note that this will url_encode all values
    $url_parts['query'] = http_build_query($params);
    $url_to_return=$url_parts['path'] . '?' . $url_parts['query'];
    $url_to_return=urldecode($url_to_return);
    return $url_to_return;	
}