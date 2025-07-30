<?php
Class WPDDL_Private_Layout{

    public function add_hooks() {

	    // execute only in case if user is inside wp-admin
	    if ( is_admin() ) {
		    add_action( 'init', array( $this, 'init' ) );
		    add_action( 'admin_enqueue_scripts', array( $this, 'private_layout_scripts' ) );
		    add_action( 'admin_notices', array( $this, 'private_layout_enable_bootstrap_msg' ), 1 );
		    add_action( 'wp_ajax_ddl_dismiss_bootstrap_message', array( $this, 'dismiss_bootstrap_message' ) );
		    add_action( 'ddl_action_layout_has_been_saved', array( $this, 'layout_has_been_saved_callback'), 10, 3 );
	    }
        // apply this filter the latter possible in the process to ensure it is not overwritten, let only 'clean_up_empty_paragraphs' callback go afterwards
        add_filter('the_content', array(&$this, 'handle_private_layout_preview'), 99998, 1 );
	    // apply this filter before the default 10 to ensure default filters are applied on a clean markup
	    add_filter('the_content', array(&$this, 'clean_up_extra_spaces'), 9, 1 );
	    // apply this filter as the latest to ensure everything added before is cleaned up including our own 'handle_private_layout_preview'
	    add_filter('the_content', array(&$this, 'clean_up_empty_paragraphs'), 99999, 1 );
	    add_filter('ddl_apply_the_content_filter_in_cells', array(&$this, 'is_private_layout_preview_visual_editor'), 10, 1 );
	    add_filter( 'ddl_apply_the_content_filter_in_post_content_cell', array(&$this, 'is_private_layout_preview'), 10, 1 );
	    add_filter( 'wpv_filter_wpv_render_view_template_force_suppress_filters', array( $this, 'suppress_filters_private_layout_preview' ), 99, 1 );

		add_action( 'wpv_action_wpv_loop_before_display_item', array( $this, 'disable_preview_inside_view_loop') );
		add_action( 'wpv_action_wpv_loop_after_display_item', array( $this, 'restore_preview_after_view_loop') );
    }

	/**
	 * Disable the private layout preview from replacing the post content when inside a View loop.
	 *
	 * Inside View loops, users might be using wpv-post-body shortcodes, which execute the the_content filter
	 * over their output. In the event that the current global post inside that loop uses a private layout,
	 * the output of such wpv-post-body shortcode might be replaced by the private layout output.
	 * This action prevents that problem.
	 *
	 * @since 2.0.0
	 */
	public function disable_preview_inside_view_loop() {
		remove_filter('the_content', array(&$this, 'handle_private_layout_preview'), 99998, 1 );
	}

	/**
	 * Restore the private layout preview rendering after a View loop.
	 *
	 * Inside View loops, users might be using wpv-post-body shortcodes, which execute the the_content filter
	 * over their output. In the event that the current global post inside that loop uses a private layout,
	 * the output of such wpv-post-body shortcode might be replaced by the private layout output.
	 * The method disable_preview_inside_view_loop prevents that problem,
	 * and this one restores the original behavior after the loop.
	 *
	 * @since 2.0.0
	 */
	public function restore_preview_after_view_loop() {
		add_filter('the_content', array(&$this, 'handle_private_layout_preview'), 99998, 1 );
	}

	public function handle_private_layout_preview( $content ){

		$page_id = get_the_ID();

		if( isset( $_POST['private_layout_preview'] ) &&
		    $_POST['private_layout_preview'] &&
		    ( WPDD_Utils::is_private( $page_id ) === true || apply_filters( 'tlm_private_layout_preview', false, $page_id ) )
		){

			$content = WPDD_Utils::get_layout_HTML_from_json( stripslashes( $_POST['private_layout_preview'] ), true, true  );

			$content = do_shortcode( $content );

			/*
			 * check if Yoast or cache run and do not disable the filter after first run if it does, it needs to run a couple of times to print the right content.
			 */
			if( !defined('WPSEO_VERSION') && ( !defined('WP_CACHE') || !WP_CACHE ) ){
				unset( $_POST['private_layout_preview'] );
				remove_filter('the_content', array(&$this, 'handle_private_layout_preview'), 99998, 1 );
			}
		}

		return $content;
	}

	public function clean_up_extra_spaces( $content ){
		$page_id = get_the_ID();

		if( $page_id && WPDD_Utils::is_private( $page_id ) && !isset( $_POST['private_layout_preview'] ) ){
		    $content = apply_filters( 'ddl-remove_unwanted_breaks', $content );
			$content = apply_filters( 'ddl-remove_extra_lines', $content );
        }

        return $content;
    }

	public function clean_up_empty_paragraphs( $content ){
		$page_id = get_the_ID();

		if( $page_id && WPDD_Utils::is_private( $page_id ) && !isset( $_POST['private_layout_preview'] ) ){
			$content = preg_replace("/<p><script\s(.+?)>/is", "<script $1>", $content);
			$content = str_replace('</script></p>', '</script>', $content );
			$content = preg_replace("/<p><style\s(.+?)>/is", "<style $1>", $content);
			$content = str_replace('</style></p>', '</style>', $content );
			$content = apply_filters( 'ddl-remove_empty_paragraphs', $content );
		}

		return $content;
	}

	public function is_private_layout_preview( $bool ){

		if( apply_filters( 'ddl-is_integrated_theme', false ) && isset( $_POST['private_layout_preview'] ) ) {
			return true;
		} else {
			return isset( $_POST['private_layout_preview'] ) === false;
		}

		return $bool;
	}

	public function is_private_layout_preview_visual_editor( $bool ){
		if( isset( $_POST['private_layout_preview'] ) ){
		    $bool = false;
        }

        return $bool;
	}

	function private_layout_enable_bootstrap_msg(){

		global $post, $pagenow;

		$show_notice = false;

		if( in_array($pagenow, $this->allow_private_layout_on_pages()) ){

			$layout_slug = WPDD_Utils::page_has_private_layout( $post->ID );
			$private_layout_in_use = WPDD_Utils::is_private_layout_in_use( $post->ID );

			if( $layout_slug && $private_layout_in_use ){
				$bootstrap_version = Toolset_Settings::get_instance();
				if( isset( $bootstrap_version->toolset_bootstrap_version ) && $bootstrap_version->toolset_bootstrap_version === "-1" ){
					$show_notice = true;
				} else {
					$show_notice = false;
				}

			}
		}

		$current_user = wp_get_current_user();
		$is_message_dissmised = get_user_meta( $current_user->ID, '_load_bootstrap_message_dissmised', true );

		if( $show_notice && $is_message_dissmised !='yes' ) {
			?>
            <div class="notice notice-warning is-dismissible js_bootstrap_not_loaded">
                <p>
                    <i class="icon-layouts-logo ont-color-orange ont-icon-24"></i>
					<?php printf(__("We noticed that you don't have Bootstrap enabled on your front-end. To use content layouts, please enable this option from the %sSettings page%s.", 'ddl-layouts'), '<a href="' . admin_url() . 'admin.php?page=toolset-settings">', '</a>'); ?>
                </p>
            </div>
			<?php
		}
	}

	public function dismiss_bootstrap_message(){
		if (!isset($_POST['wpnonce']) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_update_private_layout_status' )  ) {
			die('verification failed');
		}
		$current_user = wp_get_current_user();
		update_user_meta( $current_user->ID, '_load_bootstrap_message_dissmised', 'yes' );
	}


	/*
	* Add Create layouts button on edit page/post pages
	*/
	function init(){
		global $pagenow;

		if ( false === in_array( $pagenow, $this->allow_private_layout_on_pages() ) ) {
			return;
		}

		add_action('media_buttons', array($this, 'add_private_layout_buttons'), 11, 2);
		$this->load_dialog_box();
	}


	/*
	 * Ajax callback
	 * Get private layout data, check for what is layout assigned and call function that will update content with private layout output
	 * @deprecated
	 */
	public function update_post_content_for_private_layout_callback(){

		if (!isset($_POST['wpnonce']) || ! wp_verify_nonce( $_POST['wpnonce'], 'ddl_update_private_layout_status' )  ) {
			die('verification failed');
		}

		echo $this->do_post_content_update( $_POST['layout_id'] );

		die();
	}

	/**
	 * @param $saved
	 * @param $layout_id
	 * @param $json
	 *
	 * @return mixed|string|void
	 */
	public function layout_has_been_saved_callback( $saved, $layout_id, $json ){
	    if( apply_filters( 'ddl-is_layout_private', false, $layout_id) === false ){
	        return null;
        }
		return $this->do_post_content_update( $layout_id );
    }

	/**
	 * @param $layout_id
	 *
	 * @return mixed|string|void
	 */
	private function do_post_content_update( $layout_id ){

		$post = get_post( $layout_id );
		$post_content =  apply_filters('the_content', $post->post_content);

		$original_content_meta = get_post_meta( $layout_id,WPDDL_PRIVATE_LAYOUTS_ORIGINAL_CONTENT_META_KEY,true);

		if( $post_content !='' && $original_content_meta === '' ) {
			add_post_meta( $layout_id, WPDDL_PRIVATE_LAYOUTS_ORIGINAL_CONTENT_META_KEY, $post->post_content );
		}

		$update_content = $this->update_post_content_for_private_layout( $layout_id );

		return $update_content;
    }

	public function update_post_content_for_private_layout( $post_id ){

		$html_output = WPDD_Utils::get_private_layout_HTML_output( $post_id, false );

		// TODO: in future private layout will be able to replace CRED forms and etc,
		// so we will have to call different functions for content update
		$update_content = $this->update_post_content_with_private_layout_output( $html_output, $post_id );

		$this->update_in_use_status( $post_id, "yes" );

		do_action( 'ddl_private_layout_updated', $post_id );

		return $update_content;
	}


	/*
	 * Update post_content in posts table with private layout HTML output
	 */
	public function update_post_content_with_private_layout_output($content, $post_id){
		$content = str_replace(array("\t"), "", $content);

		$post_data = array(
			'ID'           => $post_id,
			'post_content' => trim($content)
		);

		// let users manipulate environment before post saves
		do_action( 'ddl-private_layout_before_post_update');
		// Update the post into the database with standard post API otherwise tinyMCE autop won't work
		$result = wp_update_post( apply_filters( 'ddl-private_layout_update_post', $post_data, $this ) );
		// let users restore manipulated environment after post saves
		do_action( 'ddl-private_layout_after_post_update');


		$status = ( $result === 0 ) ? false : true;

		return json_encode(array('status'=>$status));
	}

	/*
	 * Update is private layout in use status
	 */
	public function update_in_use_status($content_id, $status){

		$status_value = ($status === 'yes') ? 'yes' : false;
		$update_status = update_post_meta($content_id, WPDDL_PRIVATE_LAYOUTS_IN_USE, $status_value);
		return $update_status;
	}


	public function load_dialog_box(){

		$post_types = array();
		$post_types = get_post_types(array('_builtin'=>false));

		$dialogs = array();
		$dialogs[] = new WPDDL_PrivateLayoutsDialog( array_merge( array( 'post','page' ), $post_types ) );


		foreach( $dialogs as &$dialog ){
			add_action('current_screen', array(&$dialog, 'init_screen_render') );
		}
		return $dialogs;

	}

	/*
	 * Load css scripts and localize variables
	 */
	function private_layout_scripts() {
		global $pagenow;

		if ( false === in_array( $pagenow, $this->allow_private_layout_on_pages() ) ) {
			return;
		}

		global $wpddlayout;
		$wpddlayout->enqueue_scripts( array (
				'ddl_private_layout'
			)
		);
		wp_enqueue_script( 'jquery-ui-tooltip' );
		wp_enqueue_style( 'jquery_ui_styles' );

		do_action('toolset_enqueue_styles', array(
			'toolset-dialogs-overrides-css',
			'font-awesome'
		));

		$wpddlayout->localize_script('ddl_private_layout', 'DDL_Private_layout', array(
		        'user_can_delete_private' => user_can_delete_private_layouts(),
				'private_layout_nonce' => wp_create_nonce('ddl_update_private_layout_status'),
				'stop_using_layout_dialog_title' => __('Choose what to have in the WordPress editor','dd-layouts'),
				'stop_using_layout_dialog_edit' => __('Return to the WordPress editor','dd-layouts'),
				'stop_using_layout_dialog_close' => __('Cancel','dd-layouts'),
				'stop_using_layout_dialog_closeme' => __('Close','dd-layouts'),
				'you_have_unsaved_changes' => __('You have unsaved changes','dd-layouts'),
				'save_post_before_creating_content_layout' => __('Please publish post or save it as Draft before creating Content layout','dd-layouts')
			)
		);
	}


	/*
	 * Array with list of pages were we will show private layouts button
	 */
	public function allow_private_layout_on_pages(){

		$allowed_pages = array();
		$allowed_pages = array("post.php","post-new.php");

		return apply_filters('allow_private_layout_on_pages', $allowed_pages);
	}

	/*
	 * Add buttons for create layout and for stop using Layouts for current post/page
	 */
	function add_private_layout_buttons( $context, $text_area = '' ) {


		global $wp_version, $post, $pagenow;
		if(!in_array($pagenow, $this->allow_private_layout_on_pages())){
			return;
		}

		if (empty( $context ) && $text_area == '') {
			return;
		}

		// skip adding button for cred forms
		if((isset($_GET['post_type']) && $_GET['post_type'] === 'cred-form') ||
		   (isset($_GET['post_type']) && $_GET['post_type'] === 'cred-user-form') ||
		   $post->post_type === 'cred-form' ||
		   $post->post_type === 'cred-user-form'){
			return;
		}

		// check has this post layout assigned
		$page_has_private_layout = WPDD_Utils::page_has_private_layout( $post->ID );
		$private_layout_in_use = WPDD_Utils::is_private_layout_in_use($post->ID);

		$post_type = get_post_type($post);

		// WP 3.3 changes ($context arg is actually a editor ID now)
		if (version_compare( $wp_version, '3.1.4', '>' )  && ! empty( $context ) ) {
			$text_area = $context;
		}

		if( $text_area !== 'content' ){
			return;
		}

		$addon_button = '';
		$button_label = __( 'Content Layout Editor', 'ddl-layouts' );
		if(false === $page_has_private_layout){
			$classes = user_can_edit_private_layouts() ? 'button-primary-toolset js-layout-private-add-new-top'  : 'disabled';
			$addon_button = '<a href="#editor" class="button ' . $classes . '" data-layout_type="private" data-post_type="'.$post_type.'" data-content_id="'.$post->ID.'" data-editor="' . esc_attr( $text_area ) . '"><i class="icon-layouts-logo fa fa-wpv-custom ont-icon-18 ont-color-white"></i><span class="button-label">' . $button_label . '</span></a>';
		} else if($private_layout_in_use === false && $page_has_private_layout === true){
			$href = sprintf('%sadmin.php?page=dd_layouts_edit&layout_id=%s&action=edit', admin_url(), $post->ID);
			$classes = user_can_edit_private_layouts() ? 'button-primary-toolset js-layout-private-use-again'  : 'disabled';
			$addon_button = '<a href="'.$href.'" class="button ' . $classes . '" data-layout_type="private" data-layout_id="'.$post->ID.'" data-post_type="'.$post_type.'" data-content_id="'.$post->ID.'" data-editor="' . esc_attr( $text_area ) . '"><i class="icon-layouts-logo fa fa-wpv-custom ont-icon-18 ont-color-white"></i><span class="button-label">' . $button_label . '</span></a>';
		} else if($private_layout_in_use === "yes" && $page_has_private_layout === true){
			$href = sprintf('%sadmin.php?page=dd_layouts_edit&layout_id=%s&action=edit', admin_url(), $post->ID);
			$classes = user_can_edit_private_layouts() ? 'button-primary-toolset js-layout-private-use-again'  : 'disabled';
			$addon_button = '<a href="'.$href.'" style="display:none;" class="button ' . $classes . '" data-layout_type="private" data-layout_id="'.$post->ID.'" data-post_type="'.$post_type.'" data-content_id="'.$post->ID.'" data-editor="' . esc_attr( $text_area ) . '"><i class="icon-layouts-logo fa fa-wpv-custom ont-icon-18 ont-color-white"></i><span class="button-label">' . $button_label . '</span></a>';
		}

		// WP 3.3 changes

		if ( version_compare( $wp_version, '3.1.4', '>' ) ) {
			echo apply_filters( 'wpv_add_media_buttons', $addon_button );
		} else {
			return apply_filters( 'wpv_add_media_buttons', $context . $addon_button );
		}

	}

	/*
	 * Suppress WPV View Template render filters, if this is a private layout preview mode.
	 */
	public function suppress_filters_private_layout_preview( $bool ){
		if(  isset( $_POST['private_layout_preview'] ) && $_POST['private_layout_preview'] ) {
			$bool = true;
		}

		return $bool;
	}
}
