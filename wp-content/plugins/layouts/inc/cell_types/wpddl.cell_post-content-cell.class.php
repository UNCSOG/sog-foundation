<?php

if( ddl_has_feature('cell-post-content') === false ){
	return;
}

define('private_layout', 'private_layout');
define('beaver', 'beaver');
define('wordpress', 'wordpress');
define('divi', 'divi');
define('visual_composer', 'visual_composer');
define('thrive', 'thrive');

class WPDD_layout_cell_post_content extends WPDD_layout_cell {

	function __construct($id, $name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		parent::__construct( $id, $name, $width, $css_class_name, '', $content, $css_id, $tag, $unique_id );
		$this->set_cell_type('cell-post-content');
        add_action('ddl_layout_data_saved', array(&$this, 'save_post_data_callback'), 10, 3);
		add_filter('wpv_filter_wpv_render_view_template_force_suppress_filters', array(&$this, 'return_false') );
	}

	public function save_post_data_callback( $send, $post_data, $class_object  ){

            if( isset( $post_data['post_id'] ) && $post_data['post_id'] && isset( $post_data['post_content'] ) ){

	            $updated_id = false;

	            if( $post_data['action'] === 'save_layout_data_front_end' ){

		            $postarr = array(
			            'ID' => $post_data['post_id'],
			            'post_content' => $post_data['post_content']
		            );

		            $updated_id = wp_update_post($postarr);
	            }


                if( !isset( $send['message'] ) ){
                    $send['message'] = array();
                }

                if( $updated_id ){

                    $send['message']['layout_changed'] = true;
                    $send['message']['post_updated'] = $updated_id;

                    global $post;
                    $post = get_post($updated_id);

                } else {

	                global $post;
	                $post = get_post( $post_data['post_id'] );
	                $post->post_content = $post_data['post_content'];

                    $send['message']['layout_changed'] = false;
                    $send['message']['post_updated'] = 0;

                }

            } else if( isset( $post_data['post_id'] ) && $post_data['post_id'] && !isset( $post_data['post_content'] ) ) {

	            global $post;
	            $post = get_post( $post_data['post_id'] );

	            $send['message']['post_updated'] = 0;

            } else {

	            return $send;

            }

            return $send;
    }

	function frontend_render_cell_content($target) {
        
        global $post;
		
        
        $cell_content['page'] = 'current_page';
		
        do_action('ddl-layouts-render-start-post-content');

        $layout_id = sanitize_text_field( toolset_getpost( 'layout_id', '' ) );

        $content = '';
        if( $target->is_layout_argument_set( 'post-content-callback' ) && function_exists( $target->get_layout_arguments( 'post-content-callback' ) ) ) {


            // prevent any other override to bother
            remove_all_actions( 'loop_start' );
            remove_all_actions('loop_end' );

            ob_start();

            call_user_func( $target->get_layout_arguments( 'post-content-callback' ) );
            $content = ob_get_clean();

        } elseif (
				null === $post &&
				/**
				 * Filter for the case where a Post Content cell is used in a Content Template designed with Layouts. Returns
				 * true if $layout_id represents a Content Template designed with Layouts, false otherwise.
				 *
				 * @param bool   $is_ct_designed_with_layouts The boolean value that determines if a Content Template is designed
				 *                                            with Layouts.
				 * @param string $ct_id
				 *
				 * @return bool
				 */
				apply_filters( 'wpv_filter_maybe_ct_designed_with_layouts', false, $layout_id )
		) {
	        /**
	         * Filter the content return by a Post Content cell is used in a Content Template designed with Layouts.
	         *
	         * @param string $content The content that will take the place of the "Post Content" cell in a Content Template
	         *                        designed with Layouts.
	         *
	         * @return string
	         */
			$content = apply_filters( 'wpv_filter_post_content_for_post_content_cell', $content );
        } else {
            if (is_object($post) && property_exists($post, 'post_content') && apply_filters( 'ddl_apply_the_content_filter_in_post_content_cell', true, $this )) {
                $content = apply_filters( 'the_content', $post->post_content );
            }
        }


		$ret = $target->cell_content_callback($content, $this);
		do_action('ddl-layouts-render-end-post-content');
		return $ret;
	}

	public function return_false(){
		return false;
	}

	public function check_if_cell_renders_post_content(){
		return apply_filters( 'ddl-cell-check_if_cell_renders_post_content', true, $this );
	}

}

class WPDD_layout_cell_post_content_factory extends WPDD_layout_cell_factory{

	public function build($name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		return new WPDD_layout_cell_post_content($unique_id, $name, $width, $css_class_name, $content, $css_id, $tag, $unique_id);
	}

	public function get_cell_info($template) {
		$template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'post-content.svg';
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'post-content_expand-image.png';
		$template['name'] =  __('Post Content', 'ddl-layouts');
		$template['description'] = __('Display content of your post. You need to include this cell in Template Layouts, to display the main content area.', 'ddl-layouts');
		$template['button-text'] = __('Assign Post Content cell', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create new Post Content cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit Post Content cell', 'ddl-layouts');
		$template['dialog-template'] = $this->_dialog_template();
		$template['category'] = __('Fields, text and media', 'ddl-layouts');
        $template['has_settings'] = false;
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start();
		?>

            <div class="cell-content">
                <p class="cell-name"><?php _e('Post Content', 'ddl-layouts'); ?></p>
                <div class="cell-preview">
                    <p class="cell-preview-desc"><?php _e('Displays the content of the current page', 'ddl-layouts'); ?></p>
                    <div class="ddl-video-preview">
                        <img src="<?php echo DDL_ICONS_SVG_REL_PATH . 'post-content-preview.svg'; ?>" height="130px">
                    </div>
                </div>
            </div>
		<?php
		return ob_get_clean();
	}

	private function _dialog_template() {
		$out = $this->switch_dialog_template();
		$out .= sprintf( __('%s%sLearn more about Post Content cell%s%s', 'ddl-layouts'), '<p class="padding-5">', '<a href="'.WPDLL_POST_CONTENT_CELL.'" target="_blank" >', '</a>', '</p>');
		return $out;
	}

    
	public function enqueue_editor_scripts() {

        if( $this->which_editor() === wordpress ){
            wp_enqueue_script('cred_cred');
            wp_enqueue_script('page');
            wp_enqueue_script('editor');
            add_thickbox();
            wp_enqueue_script('media-upload');
            wp_enqueue_script('word-count');
	        if( WPDD_Layouts::views_available() ){
		        $deps = array('jquery', 'views-shortcodes-gui-script');
	        } else {
		        $deps = array('jquery');
	        }
	        wp_register_script( 'wp-post-content-editor', ( WPDDL_GUI_RELPATH . "editor/js/post-content-cell.js" ), $deps, null, true );
	        wp_enqueue_script( 'wp-post-content-editor' );

	        wp_localize_script('wp-post-content-editor', 'DDLayout_post_content', array(
			        'current_post' => get_the_ID(),
		        )
	        );
        }
	}

	function which_editor(){
		if( is_admin() || !isset($_GET['toolset_editor']) ) return null;

        global $post;

        if( !is_object( $post ) ){
            return wordpress;
        } else if( WPDD_Utils::is_private_layout_in_use( $post->ID ) ) {
            return private_layout;
        } else if( class_exists('FLBuilderModel') && FLBuilderModel::is_builder_enabled() ){
            return beaver;
        } else if( get_post_meta( $post->ID , '_wpb_vc_js_status', true) ){
            return visual_composer;
        } else if( function_exists('et_pb_is_pagebuilder_used') && et_pb_is_pagebuilder_used( $post->ID ) ){
            return divi;
        } else {
            return wordpress;
        }

        return wordpress;
	}

	function switch_dialog_template(){
        global $post;

        $editor = $this->which_editor();

        if( $editor === null || $editor === private_layout ){
            return '';
        } elseif( $editor === wordpress && is_object( $post ) && post_type_supports($post->post_type, 'editor') ){
            return $this->do_tiny_mce();
        } else if( $editor === divi || $editor === beaver || $editor === visual_composer ){
            return $this->editor_render_redirect_button( $editor );
        } else {
            return '';
        }

        return '';
    }

    function editor_render_redirect_button( $editor ){

        $editor_object = $this->visual_editors_objects( $editor );

        if( is_null( $editor_object ) ){
            return '';
        }

        $builder = $editor_object->label;
        $url = $editor_object->url;

        ob_start();
        ?>
	    <div class="post-content-cell-button-wrap">
        <a href="<?php echo $url;?>" target="_blank"><span class="button button-primary btn-large button-large large">
                <?php printf( __('Edit in %s', 'ddl-layouts'),  $builder); ?>
        </span></a></div>
        <?php
        return ob_get_clean();
    }

    protected function visual_editors_objects( $editor ){
        global $wp, $post;

        $editors = array(
            beaver => array(
                'url' => home_url( add_query_arg( array('fl-builder' => ''), $wp->request ) ),
                'label' => 'Beaver Builder'
            ),
            divi => array(
                //'url' => home_url( add_query_arg( array('et_fb' => '1'), $wp->request ) ),
	            'url' => get_edit_post_link( $post->ID ),
                'label' => 'Divi Builder'
            ),
            visual_composer => array(
	            'url' => get_edit_post_link( $post->ID ),
	           // 'url' => admin_url( add_query_arg( array('vc_action' => 'vc_inline', 'post_id' => $post->ID, 'post_type' => $post->post_type) ), 'post.php'),
                'label' => 'Visual Composer'
            )
        );

        return isset( $editors[$editor] ) ? (object) $editors[$editor]  : null;
    }

    function do_tinymce() {
        global $post;

        if( null === $post ){
            return;
        }

        $options = array(
            // See text-cell.js for editor height too
            'editor_height' => 300,
            'dfw' => false,
            'drag_drop_upload' => true,
            'tabfocus_elements' => 'insert-media-button,save-post',
            'textarea_name' => $this->element_name( 'post-content' ),
            'wpautop' => true, /* Also as TinyMCE setting */
        );
        add_filter( 'tiny_mce_before_init', array( $this, 'configure_tinymce_editor' ), 999 );

        wp_editor( $post->post_content, 'cell-post-content-editor', $options );
        remove_filter( 'user_can_richedit', array( __CLASS__, 'true' ), 100 );
    }

    private function do_tiny_mce(){
        add_filter('user_can_richedit', array(__CLASS__, 'true'), 100);
        ob_start();
        ?>
        <div class="ddl-form from-top-0 pad-top-0">
            <div id="cell-post-content-editor-container" class="js-cell-post-content-editor-container from-top-0 pad-top-0">
                <div id="js-cell-post-content-cell-tinymce">
                    <?php
                    $this->do_tinymce();
                    ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function true(){
        return true;
    }

    function configure_tinymce_editor( $in ) {

        $in['add_unload_trigger'] = false;
        $in['entities'] = '34,quot,39,apos'; // Unaffected; Special cases
        $in['entity_encoding'] = 'raw';
        $in['forced_root_block'] = 'p';
        $in['mode'] = 'exact';
        $in['protect'] = '[ /\r\n/g ]'; // Avoid joining lines when switching to Visual mode
        $in['remove_linebreaks'] = false;
        $in['remove_trailing_brs'] = false;
        $in['resize'] = false;
        $in['wpautop'] = true; /* Also as wp_editor param */

        return $in;
    }

    public function enqueue_editor_styles(){
        wp_enqueue_style('cred_cred_style');
    }
}

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_cell_post_content_factory');
function dd_layouts_register_cell_post_content_factory($factories) {
	$factories['cell-post-content'] = new WPDD_layout_cell_post_content_factory;
	return $factories;
}
