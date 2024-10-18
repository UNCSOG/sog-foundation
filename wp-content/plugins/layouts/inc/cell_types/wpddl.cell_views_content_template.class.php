<?php



if( ddl_has_feature('cell-content-template') === false ) {
    return;
}

class WPDD_layout_cell_post_content_views extends WPDD_layout_cell {

	function __construct($id, $name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		parent::__construct($id, $name, $width, $css_class_name, 'cell-content-template', $content, $css_id, $tag, $unique_id);

		$this->set_cell_type('cell-content-template');
		add_filter( 'wpv_filter_wpv_render_view_template_force_suppress_filters', array($this, 'handle_woocommerce_tabs'), 999, 5 );
	}

    function handle_woocommerce_tabs( $bool, $ct_post, $post_in, $current_user_in, $args ){
        if( WPDD_Utils::is_woocommerce_page() && $this->check_if_cell_renders_post_content() && apply_filters( 'ddl-is_integrated_theme', false) === false ){
            $bool = true;
	        remove_all_filters( 'the_content' );
        }

        return $bool;
    }
    function get_view_slug_from_id($id){
        return get_post_field( 'post_name', $id );
    }

	function frontend_render_cell_content($target) {
		global $WPV_templates;

		$content = '';
		$ret = '';

		$cell_content = $this->get_content();
        if($target->is_private_layout){
            $ct_slug = $this->get_view_slug_from_id($cell_content['ddl_view_template_id']);

             if ($cell_content['page'] == 'this_page') {
	             $ret = $target->cell_content_callback( '[wpv-post-body view_template="' . sanitize_text_field( $ct_slug ) . '" item="' . sanitize_text_field( $cell_content['selected_post'] ) . '"]', $this);
            } else {
	             $ret = $target->cell_content_callback( '[wpv-post-body view_template="' . sanitize_text_field( $ct_slug ) . '"]', $this);
             }

        } else {

            if ($cell_content['page'] == 'current_page') {
                do_action( 'ddl-layouts-render-start-post-content', $this );
            }


            if (isset($WPV_templates) && isset($cell_content['ddl_view_template_id']) && $cell_content['ddl_view_template_id'] != 'None') {
                $content_template_id = $cell_content['ddl_view_template_id'];
                if ($cell_content['page'] == 'current_page') {
                    global $post;
                    $content = render_view_template($content_template_id, $post );
                } elseif ($cell_content['page'] == 'this_page') {
                    $get_post_query = new WP_Query( array('p' => $cell_content['selected_post'],
                                                          'post_type' => 'any'));
                    while ( $get_post_query->have_posts() ) {
                        $get_post_query->the_post();
                        $content = render_view_template($content_template_id);
                    }
                    wp_reset_postdata();
                }




                $content = do_shortcode( apply_filters('ddl-content-template-cell-do_shortcode', $content, $this) );
                //$content = apply_filters('ddl-content-template-cell-do_shortcode', $content, $this);
            }
            else{
                $content = WPDDL_Messages::views_missing_message();
            }

            $ret = $target->cell_content_callback( $content, $this);

            if ($cell_content['page'] == 'current_page') {
                do_action('ddl-layouts-render-end-post-content', $this);
            }
        }

        return $ret;
	}

    public function check_if_cell_renders_post_content()
    {

        if (class_exists('WPV_template') === false) {
            return false;
        }

        $content = $this->get_content();

        if (!$content) {
            return false;
        } else {
            $content = (object)$content;
        }

        $template_id = $content->ddl_view_template_id;

        global $WPV_templates;

        $template_content = $WPV_templates->get_template_content($template_id);

        if (!$template_content) return false;

		$checks = apply_filters('ddl-do-not-apply-overlay-for-post-editor', array('wpv-post-body', 'wpv-woo-display-tabs') );

		$bool = false;

		foreach( $checks as $check ){
			if( strpos($template_content, $check) !== false ){
				$bool = true;
				break;
			}
		}

        return apply_filters( 'ddl-show_post_edit_page_editor_overlay', $bool, $this );
    }

}

class WPDD_layout_cell_post_content_views_factory extends WPDD_layout_cell_factory{

	public $dialogs_holder;

	function __construct() {

		$this->dialogs_holder = new Layouts_views_content_template_cell();

	}


	public function build($name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		return new WPDD_layout_cell_post_content_views($unique_id,$name, $width, $css_class_name, $content, $css_id, $tag, $unique_id);
	}

	public function get_cell_info($template) {
		//$template['icon-url'] = WPDDL_RES_RELPATH .'/images/views-icon-color_16X16.png';
        $template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'content-template.svg';
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'content-tamplate_expand-image.png';
		$template['name'] = __('Content Template (the content of another page)', 'ddl-layouts');
		$template['description'] = __('Display fields and content that belongs to any post, with your HTML styling.', 'ddl-layouts');
		$template['button-text'] = __('Assign Content Template cell', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create new Content Template cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit Content Template cell', 'ddl-layouts');
		$template['dialog-template'] = $this->_dialog_template();
		$template['category'] = __('Lists and loops', 'ddl-layouts');
        $template['has_settings'] = true;
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start();
		?>
			<div class="cell-content cell-preview-fadeout">
				<p class="cell-name"><?php _e('Content Template', 'ddl-layouts'); ?>: {{ name }}</p>
				<#

					if( content ) { #>
				<div class="cell-preview">

					<#
					var preview = DDLayout.content_template_cell.display_post_content_info(content,
									'<?php _e('Displays the content of the current page', 'ddl-layouts'); ?>',
									'<?php _e('Displays the content of %s', 'ddl-layouts'); ?>',
									'<?php _e('Loading...', 'ddl-layouts'); ?>',
									'',
									this);
					preview = DDL_Helper.sanitizeHelper.transform_caption_shortcode(preview);
					print( preview );
					#>
				</div>
			<# } #>
			</div>
		<?php
		return ob_get_clean();
	}

	private function _dialog_template() {
		$dialogs_holder = new Layouts_views_content_template_cell( 'cell-content-template' );
		return $dialogs_holder->cell_dialogs_callback();
	}

	public function enqueue_editor_scripts() {
		$this->dialogs_holder->cell_edit_script();
	}

}

class Layouts_views_content_template_cell extends Layouts_toolset_based_cell{

	protected $cell_type = 'cell-content-template';

	public function __construct() {
		if( is_admin()){
			add_action('wp_ajax_dll_refresh_ct_list', array(&$this, 'get_ct_select_box'));
			add_action('wp_ajax_ddl_content_template_preview', array(&$this, 'get_content_template'));
			add_action('wp_ajax_ddl_ct_loader_inline_preview', array(&$this, 'get_ct_editor_preview'));
			add_action('wp_ajax_get_posts_for_post_content', array(&$this, 'get_posts_for_post_content_callback') );
			add_action('wp_ajax_posts_for_post_content_json', array(&$this, 'get_posts_for_post_content_json') );
			add_action('wp_ajax_ddl_post_content_get_post_content', array(&$this, 'ddl_post_content_get_post_content_callback') );
			add_action('wp_ajax_dll_add_view_template', array(&$this, 'ddl_add_view_template_callback') );
			add_action('wp_ajax_ddl_delete_content_templates', array(&$this, 'ddl_delete_content_templates_callback') );

			if( isset($_GET['page']) and $_GET['page'] == 'dd_layouts_edit' ){
				add_filter( 'cred-register_cred_editor_scripts_and_styles', array(&$this, 'return_true'), 10, 1 );
			}
		}
	}

	public function return_true( $bool ){
		return true;
	}

	public function return_false( $bool ){
		return false;
	}

	public function cell_edit_script() {
		// the Quicktags fallback implementation is contained into this script
		// It should be loaded from the icl_editor_addon_plugin.js script in common//
		// But is may not pack the method in older common versions, which also loaded it too late to use as a dependency
		// After a couple of join releases, remove the fallback from this script
		// And make it dependant of icl_editor-script
		// NOTE we still do not have a fallback for the Media Manager here...
		if( is_admin() ){
			if( WPDD_Layouts::views_available() ){
				$deps = array('jquery', 'quicktags', 'wplink', 'views-shortcodes-gui-script');
			} else {
				$deps = array('jquery', 'quicktags', 'wplink');
			}
			wp_register_script( 'wp-content-template-editor', ( WPDDL_GUI_RELPATH . "editor/js/content-template-cell.js" ), $deps, null, true );
			wp_enqueue_script( 'wp-content-template-editor' );

			wp_localize_script('wp-content-template-editor', 'DDLayout_content_template_strings', array(
					'current_post' => __('This cell will display the content of the post which uses the layout.', 'ddl-layouts'),
					'this_post' => __('This cell will display the content of a specific post.', 'ddl-layouts'),
				)
			);
		}

	}

	protected function back_end_dialog() {
		global $WPV_templates, $WP_Views;

		$views_1_6_available = defined('WPV_VERSION') && version_compare(WPV_VERSION, '1.6.1', '>') && isset($WP_Views) && class_exists('WP_Views') && !$WP_Views->is_embedded();
		$views_1_6_embedded_available = defined('WPV_VERSION') && version_compare(WPV_VERSION, '1.6.1', '>') && isset($WP_Views) && class_exists('WP_Views') && $WP_Views->is_embedded();
		$view_tempates_available = $this->_get_view_templates_available();
		ob_start();
		?>
		<script type="text/javascript">
			var ddl_new_ct_default_name = "<?php echo esc_js( __('Content Template for %s Layout', 'ddl-layouts') ); ?>";
			var ddl_views_1_6_available = <?php echo $views_1_6_available ? 'true' : 'false'; ?>;
			var ddl_views_1_6_embedded_available = <?php echo $views_1_6_embedded_available ? 'true' : 'false'; ?>;
		</script>

		<ul class="ddl-form">
			<li>
				<?php if ($views_1_6_available || (defined('WPV_VERSION') && sizeof($view_tempates_available) > 0)): ?>
					<fieldset>
						<legend><?php _e('Display content for:', 'ddl-layouts'); ?></legend>
						<div class="fields-group">
							<ul>
								<li>
									<label class="post-content-page radio align-left">
										<input type="radio" name="<?php the_ddl_name_attr('page'); ?>" value="current_page" checked="checked"/>
										<?php _e('A page using this layout', 'ddl-layouts'); ?>
									</label>
								</li>
								<li>
									<label class="post-content-page radio align-left">
										<input type="radio" name="<?php the_ddl_name_attr( 'page' ); ?>" value="this_page" />
										<?php _e( 'A specific page:', 'ddl-layouts' ); ?>
									</label>
								</li>
								<li id="js-post-content-specific-page">
									<select name="<?php the_ddl_name_attr( 'post_content_post_type' ); ?>" class="js-ddl-post-content-post-type" data-nonce="<?php echo wp_create_nonce( 'ddl-post-content-post-type-select' ); ?>">
										<option value="ddl-all-post-types"><?php _e('All post types', 'ddl-layouts'); ?></option>
										<?php
										$post_types = get_post_types( array( 'public' => true ), 'objects' );
										foreach ( $post_types as $post_type ) {
											$count_posts = wp_count_posts($post_type->name);
											if ($count_posts->publish > 0) {
												?>
												<option value="<?php echo $post_type->name; ?>"<?php if($post_type->name == 'page') { echo ' selected="selected"';} ?>>
													<?php echo $post_type->labels->singular_name; ?>
												</option>
												<?php
											}
										}
										?>
									</select>
									<?php
									//	$keys = array_keys( $post_types );
									//	$post_types_array = array_shift(  $keys  );
									//	$this->show_posts_dropdown( $post_types_array, get_ddl_name_attr( 'selected_post' ) );
									?>
								</li>
							</ul>
						</div>
					</fieldset>
				<?php endif;?>
			</li>

			<?php if ($views_1_6_available || (defined('WPV_VERSION') && sizeof($view_tempates_available) > 0)): ?>
				<li>
					<fieldset>

						<ul>
							<li class="js-post-content-ct js-ct-selector js-ct-select-box">
								<?php echo $this->_get_view_template_select_box($view_tempates_available); ?>
							</li>
							<?php if ($views_1_6_available): ?>
								<li class="js-post-content-ct js-ct-selector" style="text-align: right;">
									<span class="ddl-dialog-form-span-inner"><?php _e('or', 'ddl-layouts'); ?></span>
									<a href="#" class="js-create-new-ct button button-large ddl-align-right"><?php _e('Create a new one', 'ddl-layouts'); ?></a>
								</li>
							<?php endif; ?>
						</ul>

					</fieldset>
				</li>

			<?php endif; ?>


			<?php if( $views_1_6_available ): ?>
				<li class="js-post-content-ct js-ct-edit ddl-ct-cell-content-controls-box">
					<input class="js-ct-edit-name" type="text" style="float: left; width: 50%" /><span class="js-ct-editing"><strong><?php _e('New content template name', 'ddl-layouts'); ?> :</strong> <span class="js-ct-name ct-name-span"></span></span>
					<!--<span class="ddl-dialog-form-span-inner"><?php _e('or', 'ddl-layouts'); ?></span>--><a style="float: right" href="#" class="js-load-different-ct button button-small"><?php _e('Load existing Content Template', 'ddl-layouts'); ?></a>
				</li>
				<li class="js-post-content-ct js-ct-edit js-ct-editor-wrapper">
					<div class="js-wpv-ct-inline-edit wpv-ct-inline-edit wpv-ct-inline-edit hidden"><?php echo '';?></div>
				</li>
			<?php else: ?>
				<li>
					<div class="toolset-alert toolset-alert-info js-ddl-views-not-activated">

						<p>
							<i class="icon-views-logo ont-color-orange ont-icon-24"></i>
							<?php _e('This cell can display the post content using a Content Template, but your site does not have any Content Template yet. Install and activate the Views plugin, and you will be able to create Content Templates to display post fields', 'ddl-layouts'); ?>

							&nbsp;&nbsp;
							<a class="fieldset-inputs" href="https://toolset.com/home/views-create-elegant-displays-for-your-content/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
								<?php _e('About Views', 'ddl-layouts');?>
							</a>
						</p>

					</div>
				</li>
				<li class="js-post-content-ct js-ct-edit">
					<div class="js-wpv-ct-inline-edit wpv-ct-inline-edit wpv-ct-inline-edit hidden"><?php echo '';?></div>
				</li>

			<?php endif; ?>

			<li class="from-top-20">
				<?php ddl_add_help_link_to_dialog(WPDLL_CONTENT_TEMPLATE_CELL, __('Learn about the Content Template cell', 'ddl-layouts')); ?>
			</li>

		</ul>

		<?php wp_nonce_field( 'wpv-ct-inline-edit', 'wpv-ct-inline-edit' ); ?>
		<?php wp_nonce_field( 'wpv_inline_content_template', 'wpv_inline_content_template' ); ?>

		<?php
		return ob_get_clean();
	}

	private function _get_view_template_select_box($view_tempates_available) {

		// Add a "None" type to the list.
		$none = new stdClass();
		$none->ID = 0;
		$none->post_name = 'None';
		$none->post_title = __('Select content template', 'ddl-layouts');
		array_unshift($view_tempates_available, $none);

		ob_start();
		?>
		<label class="ddl-align-left ddl-dialog-label ont-manual-width-202" for="post-content-view-template"><?php _e('Load content template:', 'ddl-layouts'); ?> </label>
		<select class="views_template_select ont-manual-width-396 ddl-align-left" name="<?php echo $this->element_name('ddl_view_template_id'); ?>" id="post-content-view-template">';

			<?php
			foreach($view_tempates_available as $template) {
				$title = $template->post_title;
				if (!$title) {
					$title = $template->post_name;
				}

				?>
				<option value="<?php echo $template->ID; ?>" data-ct-id="<?php echo $template->ID; ?>" ><?php echo $template->post_title; ?></option>
				<?php
			}
			?>
		</select>

		<?php

		return ob_get_clean();
	}

	function get_ct_select_box () {

		if( WPDD_Utils::user_not_admin() ){
			die( __("You don't have permission to perform this action!", 'ddl-layouts') );
		}

		if ( !isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'wpv-ct-inline-edit') ) die("Undefined Nonce.");

		$view_tempates_available = $this->_get_view_templates_available();
		echo $this->_get_view_template_select_box($view_tempates_available);

		die();
	}

	private function _get_view_templates_available() {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title, post_name FROM $wpdb->posts WHERE post_type=%s AND post_status = '%s' ORDER BY post_title", 'view-template', 'publish') );
	}

	private function show_posts_dropdown($post_type, $name, $selected = 0, $page = 1, $paged = 1) {
		if ($post_type == 'ddl-all-post-types') {
			$post_type = 'any';
		}

		$attr = array('name'=> $name,
		              'post_type' => $post_type,
		              'show_option_none' => __('None', 'ddl-layouts'),
		              'selected' => $selected);


		add_filter('posts_clauses_request', array($this, 'posts_clauses_request_filter'), 10, 2 );

		$defaults = array(
			'depth' => 0,
			'child_of' => 0,
			'selected' => $selected,
			'echo' => 1,
			'name' => 'page_id',
			'id' => '',
			'show_option_none' => '',
			'show_option_no_change' => '',
			'option_none_value' => ''
		);
		$r = wp_parse_args( $attr, $defaults );
		extract( $r, EXTR_SKIP );


		$pages = get_posts(
			array(
				'posts_per_page' => self::POSTS_PER_PAGE,
				'post_type' => $post_type,
				'suppress_filters' => false,
				'paged' => $page,
				'page' => $paged
			)
		);

		$count = count($pages);
		$total = $this->count_type($post_type);

		$output = '';
		// Back-compat with old system where both id and name were based on $name argument
		if ( empty($id) )
			$id = $name;

		if ( ! empty($pages) ) {
			$output = "<select name='" . esc_attr( $name ) . "' id='" . esc_attr( $id ) . "' data-post-type='" . esc_attr( $post_type ). "'>\n";
			if ( $show_option_no_change ){
				$output .= "\t<option value=\"-1\">$show_option_no_change</option>";
			}

			if ( $show_option_none ){
				$output .= "\t<option value=\"" . esc_attr($option_none_value) . "\">$show_option_none</option>\n";
				$output .= walk_page_dropdown_tree($pages, $depth, $r);
			}

			if( $total > $count * $page ){
				$output .= "\t<option value=\"-2\" class=\"js-show-more-posts-options show-more-posts-options\">". __('Show more', 'ddl-layouts') ."</option>";
				$output .= "</select>\n";
			}

		}

		echo $output;

		remove_filter('posts_clauses_request', array($this, 'posts_clauses_request_filter'), 10, 2 );

	}

	private function show_posts_options($post_type, $name, $selected = 0, $page = 1, $paged = 1) {
		if ($post_type == 'ddl-all-post-types') {
			$post_type = 'any';
		}

		$attr = array('name'=> $name,
		              'post_type' => $post_type,
		              'show_option_none' => __('None', 'ddl-layouts'),
		              'selected' => $selected);


		add_filter('posts_clauses_request', array($this, 'posts_clauses_request_filter'), 10, 2 );

		$defaults = array(
			'depth' => 0,
			'child_of' => 0,
			'selected' => $selected,
			'echo' => 1,
			'name' => 'page_id',
			'id' => '',
			'show_option_none' => '',
			'show_option_no_change' => '',
			'option_none_value' => ''
		);
		$r = wp_parse_args( $attr, $defaults );
		extract( $r, EXTR_SKIP );


		$pages = get_posts(
			array(
				'posts_per_page' => self::POSTS_PER_PAGE,
				'post_type' => $post_type,
				'suppress_filters' => false,
				'paged' => $page,
				'page' => $paged
			)
		);

		$count = count($pages);
		$total = $this->count_type($post_type);

		$output = '';
		// Back-compat with old system where both id and name were based on $name argument
		if ( empty($id) )
			$id = $name;

		if ( ! empty($pages) ) {
			$output = walk_page_dropdown_tree($pages, $depth, $r);
		}

		remove_filter('posts_clauses_request', array($this, 'posts_clauses_request_filter'), 10, 2 );

		return array(
			'html' => $output,
			'total' => $total,
			'count' => $count
		);
	}

	private function count_type($post_type){
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = '%s' AND post_status = 'publish'", $post_type) );
	}

	function posts_clauses_request_filter($pieces, $query ) {
		global $wpdb;
		// only return the fields required for the dropdown.
		$pieces['fields'] = "$wpdb->posts.ID, $wpdb->posts.post_parent, $wpdb->posts.post_title";

		return $pieces;
	}

	function get_content_template () {

		if( WPDD_Utils::user_not_admin() ){
			die( __("You don't have permission to perform this action!", 'ddl-layouts') );
		}

		if ( !isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'wpv-ct-inline-edit') ) {
			$send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
		}

		global $WPV_templates;
		if (isset($WPV_templates) && isset($_POST['view_template'])) {
			$content_template_id = $_POST['view_template'];
			$content = $WPV_templates->get_template_content($content_template_id);

			$send = wp_json_encode( array( 'Data' => array( 'content' => $content ) )  );

		}

		die($send);

	}

	function get_ct_editor_preview() {

		if( WPDD_Utils::user_not_admin() ){
			die( __("You don't have permission to perform this action!", 'ddl-layouts') );
		}

		if ( !isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'wpv-ct-inline-edit') ) die("Undefined Nonce.");

		global $WPV_templates;
		if (isset($WPV_templates) && isset($_POST['id'])) {
			$content_template_id = $_POST['id'];
			$content = $WPV_templates->get_template_content($content_template_id);

			$content;
			?>
			<textarea name="name" rows="10" id="wpv-ct-inline-editor-<?php echo $content_template_id; ?>"><?php echo $content;?></textarea>
			<?php
		}

		die();

	}

	function get_posts_for_post_content_callback() {
		if (wp_verify_nonce( $_POST['nonce'], 'ddl-post-content-post-type-select' )) {
			$this->show_posts_dropdown($_POST['post_type'], get_ddl_name_attr('selected_post'));
		}
		die();
	}

	function get_posts_for_post_content_json(){

		if( user_can_edit_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}
		if ($_POST && wp_verify_nonce($_POST['nonce'], 'ddl-post-content-post-type-select')) {

			$send = wp_json_encode( array( 'Data' => $this->show_posts_options( $_POST['post_type'], get_ddl_name_attr('selected_post'), $_POST['selected'], $_POST['page'], $_POST['page'] ) )  );
		} else {
			$send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
		}

		die($send);
	}

	function ddl_post_content_get_post_content_callback() {

		if( WPDD_Utils::user_not_admin() ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}

		if ( !isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'ddl_layout_view_nonce') ) die("Undefined Nonce.");

		//global $wpdb;

		//$content = $wpdb->get_var("SELECT post_content FROM {$wpdb->posts} WHERE ID={$_POST['post_id']}");
		$post = get_post($_POST['post_id']);
		$content = array( 'title' => '' );
		if ( isset($post->ID) ){
			$content['title'] = $post->post_title;
		}
		echo wp_json_encode($content);
		die();
	}

	function ddl_add_view_template_callback() {
		global $wpdb;

		if( WPDD_Utils::user_not_admin() ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}

		//add new content template
		if ( !isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'wpv-ct-inline-edit') ) die("Undefined Nonce.");

		$template_content = '';
		// Prevent backwards compatibility issues
		if ( function_exists( 'wpv_create_content_template' ) ) {
			// set the template title
			// empty suffix - Layouts handles the need of ta suffix and adds it automatically to the passed title
			// no need to force - Layouts handles the uniqueness of the title
			// set the template content
			$content_template = wpv_create_content_template( $_POST['ct_name'], '', true, $template_content );
			$ct_post_id = $content_template['success'];
		} else {
			$new_template = array(
				'post_title'	=> $_POST['ct_name'],
				'post_type'		=> 'view-template',
				'post_status'	=> 'publish',
				'post_content'	=> $template_content
			);
			$ct_post_id = wp_insert_post( $new_template );
			update_post_meta( $ct_post_id, '_wpv_view_template_mode', 'raw_mode');
			update_post_meta( $ct_post_id, '_wpv-content-template-decription', '');
		}
		echo wp_json_encode(array('id' => $ct_post_id));

		die();
	}

	function ddl_delete_content_templates_callback() {
		global $wpdb;

		if( WPDD_Utils::user_not_admin() ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}

		//add new content template
		if ( !isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'wpv-ct-inline-edit') ) die("Undefined Nonce.");

		if ( isset($_POST['content_templates']) && is_array($_POST['content_templates']) ){
			$ct_list = $_POST['content_templates'];
			for ( $i=0,$ct_count = count($ct_list); $i<$ct_count; $i++ ){
				wp_delete_post( $ct_list[$i], true );
			}
		}
		die();
	}
}

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_cell_post_content_views_factory');
function dd_layouts_register_cell_post_content_views_factory($factories) {
	$factories['cell-content-template'] = new WPDD_layout_cell_post_content_views_factory;
	return $factories;
}
