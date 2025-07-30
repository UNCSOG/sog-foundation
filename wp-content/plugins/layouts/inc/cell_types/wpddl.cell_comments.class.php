<?php
/*
 * Comments cell type.
 * Displays form and coments
 *
 */
 if( ddl_has_feature('comments-cell') === false ){
    return;
}

if (!class_exists('Layouts_cell_comments', false )) {
    class Layouts_cell_comments{
        
        private $cell_type = 'comments-cell';
        
        function __construct() {
            add_action( 'init', array(&$this,'register_comments_cell_init'), 12 );
            add_action('wp_ajax_ddl_load_comments_page_content', array(&$this,'ddl_load_comments_page_content'));
            add_action('wp_ajax_nopriv_ddl_load_comments_page_content', array(&$this,'ddl_load_comments_page_content'));
        }
        
        
        function register_comments_cell_init() {
            if (function_exists('register_dd_layout_cell_type')) {
                register_dd_layout_cell_type($this->cell_type, 
                    array(
                        'name' => __('Comments', 'ddl-layouts'),
                        'description' => __('Display the comments section. This cell is typically used in layouts for blog posts and pages that need comments enable.', 'ddl-layouts'),
                        'category' => __('Site elements', 'ddl-layouts'),
                        'cell-image-url' => DDL_ICONS_SVG_REL_PATH . 'layouts-comments-cell.svg',
                        'button-text' => __('Assign comments cell', 'ddl-layouts'),
                        'dialog-title-create' => __('Create a new comments cell', 'ddl-layouts'),
                        'dialog-title-edit' => __('Edit comments cell', 'ddl-layouts'),
                        'dialog-template-callback' => array(&$this,'comments_cell_dialog_template_callback'),
                        'cell-content-callback' => array(&$this,'comments_cell_content_callback'),
                        'cell-template-callback' => array(&$this,'comments_cell_template_callback'),
                        'has_settings' => true,
                        'cell-class' => '',
                        'preview-image-url' => DDL_ICONS_PNG_REL_PATH . 'comments_expand-image.png',
                        'allow-multiple' => false,
                        'register-scripts' => array(
                            array('ddl-comments-cell-script', WPDDL_GUI_RELPATH . 'editor/js/ddl-comments-cell-script.js', array('jquery'), WPDDL_VERSION, true),
                        ),
                        'translatable_fields' => array(
                            'title_one_comment' => array('title' => 'One comment text', 'type' => 'LINE'),
                            'title_multi_comments' => array('title' => 'Multiple comments text', 'type' => 'LINE'),
                            'ddl_prev_link_text' => array('title' => 'Older Comments text', 'type' => 'LINE'),
                            'ddl_next_link_text' => array('title' => 'Newer Comments text', 'type' => 'LINE'),
                            'comments_closed_text' => array('title' => 'Comments are closed text', 'type' => 'LINE'),
                            'reply_text' => array('title' => 'Reply text', 'type' => 'LINE'),
                            'password_text' => array('title' => 'Password protected post text', 'type' => 'LINE')
                        )
                    )
                );
                
            }
        }
        
        
        
        function comments_cell_dialog_template_callback() {
            ob_start();
            ?>

            <div class="ddl-form">
                <p>
                    <label for="<?php the_ddl_name_attr('avatar_size'); ?>"><?php _e( 'Avatar size', 'ddl-layouts' ) ?>:</label>
                    <input type="number" value="24" placeholder="<?php _e( '32', 'ddl-layouts' ) ?>" name="<?php  the_ddl_name_attr('avatar_size'); ?>" id="<?php  the_ddl_name_attr('avatar_size'); ?>" class="ddl-narrow-width" onkeypress='return event.charCode >= 48 && event.charCode <= 57'>
                </p>			
            </div>

            <div class="ddl-form">
                <p>
                    <label for="<?php the_ddl_name_attr('title_one_comment'); ?>"><?php _e( 'For one comment', 'ddl-layouts' ) ?>:</label>
                    <input type="text" name="<?php the_ddl_name_attr('title_one_comment'); ?>" id="<?php the_ddl_name_attr('title_one_comment'); ?>" value="<?php _e( 'One thought on %TITLE%', 'ddl-layouts' ) ?>">
                    <div id="title_one_comment_message"></div>
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('title_multi_comments'); ?>"><?php _e( 'For two or more', 'ddl-layouts' ) ?>:</label>
                    <input type="text" name="<?php the_ddl_name_attr('title_multi_comments'); ?>" id="<?php the_ddl_name_attr('title_multi_comments'); ?>" value="<?php _e( '%COUNT% thoughts on %TITLE%', 'ddl-layouts' ) ?>">
                    <div id="title_multi_comments_message"></div>
                    <span class="desc"><?php _e( 'Use the %TITLE% placeholder to display the post title and the %COUNT% placeholder to display the number of comments.', 'ddl-layouts' ) ?></span>
                </p>
            </div>

            <div class="ddl-form">
                <p>
                    <label for="<?php the_ddl_name_attr('ddl_comment_nav_position'); ?>"><?php _e( 'Comments Navigation', 'ddl-layouts' ) ?>:</label>
                    <span class="indent-30"><input type="radio" name="<?php the_ddl_name_attr('ddl_comment_nav_position'); ?>" id="<?php the_ddl_name_attr('ddl_comment_nav_position'); ?>" value="above" checked> <?php _e( 'Above', 'ddl-layouts' ) ?></span>
                    <span class="indent-10"><input type="radio" name="<?php the_ddl_name_attr('ddl_comment_nav_position'); ?>" id="<?php the_ddl_name_attr('ddl_comment_nav_position'); ?>" value="below"> <?php _e( 'Below', 'ddl-layouts' ) ?></span>
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('ddl_prev_link_text'); ?>"><?php _e( 'Previous link text', 'ddl-layouts' ) ?>:</label>
                    <input type="text" name="<?php the_ddl_name_attr('ddl_prev_link_text'); ?>" id="<?php the_ddl_name_attr('ddl_prev_link_text'); ?>" value="<?php _e( '<< Older Comments', 'ddl-layouts' ) ?>">
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('ddl_next_link_text'); ?>"><?php _e( 'Next link text', 'ddl-layouts' ) ?>:</label>
                    <input type="text" name="<?php the_ddl_name_attr('ddl_next_link_text'); ?>" id="<?php the_ddl_name_attr('ddl_next_link_text'); ?>" value="<?php _e( 'Newer Comments >>', 'ddl-layouts' ) ?>">
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('comments_closed_text'); ?>"><?php _e( 'Comments closed text', 'ddl-layouts' ) ?>:</label>
                    <input type="text" name="<?php the_ddl_name_attr('comments_closed_text'); ?>" id="<?php the_ddl_name_attr('comments_closed_text'); ?>" value="<?php _e( 'Comments are closed', 'ddl-layouts' ) ?>">
                    <div id="comments_closed_text_message"></div>
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('reply_text'); ?>"><?php _e( 'Reply text', 'ddl-layouts' ) ?>:</label>
                    <input type="text" name="<?php the_ddl_name_attr('reply_text'); ?>" id="<?php the_ddl_name_attr('reply_text'); ?>" value="<?php _e( 'Reply', 'ddl-layouts' ) ?>">
                    <div id="reply_text_message"></div>
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('password_text'); ?>"><?php _e( 'Password protected text', 'ddl-layouts' ) ?>:</label>
                    <input type="text" name="<?php the_ddl_name_attr('password_text'); ?>" id="<?php the_ddl_name_attr('password_text'); ?>" value="<?php _e( 'This post is password protected. Enter the password to view any comments.', 'ddl-layouts' ) ?>">
                    <div id="password_text_message"></div>
                </p>
            </div>
            <div class="ddl-form">
                <div class="ddl-form-item">
                    <br />
                    <?php ddl_add_help_link_to_dialog(WPDLL_COMMENTS_CELL, __('Learn about the Comments cell', 'ddl-layouts')); ?>
                </div>
            </div>
            <?php
            global $current_user;
            $current_user = wp_get_current_user();
            ?>

            <?php
            return ob_get_clean();
	}
        
        
        // Callback function for displaying the cell in the editor.
	function comments_cell_template_callback() {
            ob_start();
            ?>
                    <div class="cell-content">

                            <p class="cell-name"><?php _e('Comment Cell', 'ddl-layouts'); ?></p>
                            <div class="cell-preview">
                    <div class="ddl-comments-preview">
                                            <img src="<?php echo WPDDL_RES_RELPATH . '/images/cell-icons/svg/comments.svg'; ?>" height="130px">
                                    </div>
                            </div>
                    </div>
            <?php
            return ob_get_clean();
	}

	//Hook for previous page link, add post id and prev page to link
	function ddl_add_previous_comments_link_data(){
            global $post;
            if ( !isset($post->ID)){
                    return;	
            }
            $page = get_query_var('cpage');
            if ( intval($page) <= 1 ){
                    return;
            }
            $prevpage = intval($page) - 1;
            return ' data-page="'.$prevpage.'" data-postid="'.$post->ID.'" ';	
	}
	
	//Hook for next page link, add post id and next page to link
	function ddl_add_next_comments_link_data(){
            global $post;
            if ( !isset($post->ID)){
                    return;	
            }
            $page = get_query_var('cpage');
            $nextpage = intval($page) + 1;
            return ' data-page="'.$nextpage.'" data-postid="'.$post->ID.'" ';	
	}
	
	
	
	//Load page content, Ajax pagination. Most of code same, so we can do one function for this
	function ddl_load_comments_page_content(){
		
            $nonce = $_POST["wpnonce"];
            if (! wp_verify_nonce( $nonce, 'ddl_comments_listing_page' ) ) {
                    echo 'Error';
            } else {
                global $wpddlayout, $wp_query, $withcomments, $wpdb, $id, $post, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;


                if ( !isset($_POST['page']) && !isset($_POST['postid']) ){
                    echo 'Error';	
                }
                $load_page = $_POST['page']; // Current page to load
                $post_id = $_POST['postid'];
                $post = get_post($post_id); // Get post
                setup_postdata( $post ); // load global $post

                $comments = $this->ddl_load_comments_array( $post_id );

                set_query_var('cpage' ,$load_page); //Set query page
                $overridden_cpage = true;
                $wp_query->query = array( /*'page_id' => $post->ID,*/ 'cpage' => $load_page);
                set_query_var( 'post_id', $post->ID);
                set_query_var( 'p', $post->ID);
                $wp_query->is_singular = 1;
                $layout_id = $_POST['layout_name'];
                $cell_id = $_POST['cell_id'];
                get_the_ddlayout($layout_id);
                global $wpddlayout;
                $wpddlayout->set_up_cell_fields_by_id( $cell_id, $layout_id );

                $post = get_post($post_id); // Get post
                setup_postdata( $post ); // load global $post
            }

            $comments_list =  $this->ddl_render_comments_list( $comments, $post, $load_page);

            echo $comments_list;
            die();
	}
	
	// Callback function for display the cell in the front end.
	function comments_cell_content_callback() {		
            global $wpddlayout, $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;

            if ( !(is_single() || is_page() || $withcomments) || empty($post) )
            return;

            $wpddlayout->enqueue_scripts('ddl-comment-cell-front-end');
            $wpddlayout->localize_script('ddl-comment-cell-front-end', 'DDL_Comments_cell', 
                array(
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'security' => wp_create_nonce( 'ddl_comments_listing_page' ),
                    'layout_name' => $wpddlayout->get_rendered_layout_id(),
                    'cell_id' => get_ddl_field('unique_id')
                )
            );

            $comments = $this->ddl_load_comments_array();

            $overridden_cpage = false;
            if ( '' == get_query_var('cpage') && get_option('page_comments') ) {
                set_query_var( 'cpage', 'newest' == get_option('default_comments_page') ? get_comment_pages_count() : 1 );
                $overridden_cpage = true;
            }
            $load_page = '';
            if ( isset($_GET['cpage']) ){
                $load_page = $_GET['cpage'];
            }
            $comments_list =  $this->ddl_render_comments_list( $comments, $post, $load_page);

            return $comments_list;
	}
	
	//Get comments array for current post
	function ddl_load_comments_array( $post_id = null){
		
            global $wpddlayout, $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;
            $id = $post_id ? $post_id : $post->ID;
            $commenter = wp_get_current_commenter();
            $comment_author = $commenter['comment_author'];
            $comment_author_email = $commenter['comment_author_email'];
            $comment_author_url = esc_url($commenter['comment_author_url']);
            $comments_order = 'ASC';
            
            if ( $user_ID) {
                $comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND (comment_approved = '1' OR ( user_id = %d AND comment_approved = '0' ) )  ORDER BY comment_date_gmt ".$comments_order, $id, $user_ID));
            } else if ( empty($comment_author) ) {
                $comments = get_comments( array('post_id' => $post->ID, 'status' => 'approve', 'order' => $comments_order) );
            } else {
                $comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND ( comment_approved = '1' OR ( comment_author = %s AND comment_author_email = %s AND comment_approved = '0' ) ) ORDER BY comment_date_gmt ".$comments_order, $id, wp_specialchars_decode($comment_author,ENT_QUOTES), $comment_author_email));
            }

            $wp_query->comments = apply_filters( 'comments_array', $comments, $id );
            $comments = &$wp_query->comments;
            $wp_query->comment_count = count($wp_query->comments);
            update_comment_cache($wp_query->comments);
            
            return $comments;
	}

	    private function print_comments_navigation( $comments, $class ) {
            ob_start();
		    if ( get_comment_pages_count( $comments ) > 1 && get_option( 'page_comments' ) ):?>
			    <?php
			    add_filter( 'previous_comments_link_attributes', array(
				    &$this,
				    'ddl_add_previous_comments_link_data'
			    ) );
			    add_filter( 'next_comments_link_attributes', array( &$this, 'ddl_add_next_comments_link_data' ) );
			    ?>
                <nav id="comment-nav-<?php echo $class;?>">
                    <div class="nav-previous js-ddl-previous-link"><?php previous_comments_link( get_ddl_field( 'ddl_prev_link_text' ) ); ?></div>
                    <div class="nav-next js-ddl-next-link"><?php next_comments_link( get_ddl_field( 'ddl_next_link_text' ) ); ?></div>
                </nav>
		    <?php endif; // check for comment navigation
            echo ob_get_clean();
	    }

        
        //Generate comments listing
	function ddl_render_comments_list($comments, $post, $load_page=''){
            ob_start();
            ?>

            <div id="comments">
            <?php if ( post_password_required($post) ) : ?>
                <p class="nopassword"><?php the_ddl_field('password_text'); ?></p>
                <p><?php
                    if( ! apply_filters( 'ddl-is_integrated_theme', false ) ):
                        echo get_the_password_form( $post );
                    endif;
                    ?></p>
            </div><!-- #comments -->
            <?php
                return ob_get_clean();
                endif;
            ?>	

            <?php if ( comments_open() ):
                $num_comments = get_comments_number();
                ?>
                <?php if ( $num_comments > 0 ): ?>


                    <h2 id="comments-title">
                    <?php					
                    $one_comment_text = str_replace( array('%TITLE%', '%COUNT%'), array('%2$s','%1$s'), get_ddl_field('title_one_comment'));
                    $two_comments_text = str_replace( array('%TITLE%', '%COUNT%'), array('%2$s','%1$s'), get_ddl_field('title_multi_comments'));
                    printf( _n( $one_comment_text, $two_comments_text, $num_comments ),
                    number_format_i18n( $num_comments ), '<span>' . $post->post_title . '</span>' );
                    ?>
                    </h2>
                    <?php
                    if ( !get_ddl_field( 'ddl_comment_nav_position' ) || get_ddl_field( 'ddl_comment_nav_position' ) === 'above' ){
	                    $this->print_comments_navigation( $comments, 'above' );
                    }
                    $comments_defaults = array();
                    //Set comments style
                    $comments_defaults['style'] = apply_filters('ddl_comment_cell_style', 'ul');

                    //Avatar Size
                    if ( get_ddl_field('avatar_size') != '' ){
                            $comments_defaults['avatar_size'] = get_ddl_field('avatar_size');	
                    }
                    //Reply text
                    if ( get_ddl_field('reply_text') != '' ){
                            $comments_defaults['reply_text'] = get_ddl_field('reply_text');	
                    }
                    if ( get_comment_pages_count($comments) > 1 && get_option( 'page_comments' )  ):
                            $comments_defaults['per_page'] = get_option('comments_per_page');
                            if ( empty($load_page) ){
                                    if ('newest' == get_option('default_comments_page')){
                                            $comments_defaults['page'] = get_comment_pages_count($comments);
                                    }else{
                                            $comments_defaults['page'] = 1;	
                                    }
                            }else{
                                    //Current page
                                    $comments_defaults['page'] = $load_page;
                            }
                    endif;
                    $before_comments_list = '<ul class="commentlist">';
                    $after_comments_list = '</ul>';

                    if ( $comments_defaults['style'] == 'ol'  ){
                            $before_comments_list = '<ol class="commentlist">';
                            $after_comments_list = '</ol>';
                    }
                    if ( $comments_defaults['style'] == 'div'  ){
                            $before_comments_list = '<div class="commentlist">';
                            $after_comments_list = '</div>';
                    }
                           
                    $comments_order_db = get_option( 'comment_order', 'desc' );
                    
                    if($comments_order_db === 'desc'){
                        $comments_defaults['reverse_top_level'] = true;
                        $comments_defaults['reverse_children'] = false;
                    } else {
                        $comments_defaults['reverse_top_level'] = false;
                        $comments_defaults['reverse_children'] = false;
                    }

                    echo '<div id="comments-listing">';
                    echo $before_comments_list;
                    //Generate and print comments listing
                    wp_list_comments( $comments_defaults, $comments );
                    echo $after_comments_list;
                    echo '</div>';
                    if ( get_ddl_field( 'ddl_comment_nav_position' ) === 'below' ){
	                    $this->print_comments_navigation( $comments, 'below' );
                    }
                    ?>


                <?php endif;?>
                <?php // Generate comment form
                comment_form(); ?>

            <?php else:?>
                    <p class="nocomments"><?php the_ddl_field('comments_closed_text'); ?></p>
            <?php endif;?>

            </div>
            <?php		
            return ob_get_clean();
	}




    }
    new Layouts_cell_comments();
}