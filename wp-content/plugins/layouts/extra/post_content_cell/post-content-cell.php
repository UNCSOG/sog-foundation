<?php

class WPDD_layout_cell_post_content extends WPDD_layout_cell {

	function __construct($name, $width, $css_class_name = '', $content = null, $css_id = '') {
		parent::__construct($name, $width, $css_class_name, 'cell-post-content', $content, $css_id);

		$this->set_cell_type('cell-post-content');
	}

	function frontend_render_cell_content($target) {
		global $WPV_templates, $post, $id, $authordata;

		$cell_content = $this->get_content();

		if ($cell_content['page'] == 'current_page') {
			do_action('ddl-layouts-render-start-post-content');
		}

		// View template support is only here for backwards support before 0.9.2.
		// It's not used for post content cells for 0.9.2 and later.
		if (isset($WPV_templates) && isset($cell_content['ddl_view_template_id']) && $cell_content['ddl_view_template_id'] != 'None') {
			$content_template_id = $cell_content['ddl_view_template_id'];
			if ($cell_content['page'] == 'current_page') {
				global $post;
				$content = render_view_template($content_template_id, $post );
			} elseif ($cell_content['page'] == 'this_page') {
				$post = get_post( $cell_content['selected_post'] );
				$content = render_view_template($content_template_id, $post );
			}
		} else {

			if (isset($WPV_templates)) {
				remove_filter('the_content', array($WPV_templates, 'the_content'), 1, 1);
			}

			$content = '';
			if( $target->is_layout_argument_set( 'post-content-callback' ) && function_exists( $target->get_layout_arguments( 'post-content-callback' ) ) ) {

	            global $wp_query;

				// in case of issue uncomment this line
				$wp_query->current_post = -1;

                // prevent any other override to bother
                remove_all_actions( 'loop_start' );
                remove_all_actions('loop_end' );

				if ($cell_content['page'] == 'this_page') {
					// need to switch the post.
					$original_query = isset( $wp_query ) ? clone $wp_query : null;
					$original_post = isset( $post ) ? clone $post : null;
					$original_authordata = isset ($authordata) ? clone $authordata : null;
					$original_id = $id;


					$wp_query = new WP_Query(array('post_type' => 'any',
                                                    'ignore_sticky_posts' => true,
												   'post__in' => array( $cell_content['selected_post'] ) ) );


				    }

				ob_start();

				call_user_func( $target->get_layout_arguments( 'post-content-callback' ) );
				$content = ob_get_clean();

				if ($cell_content['page'] == 'this_page') {
					// restore the global wp_query.
					$wp_query = isset( $original_query ) ? clone $original_query : null;
					$post = isset( $original_post ) ? clone $original_post : null;
					$authordata = isset( $original_authordata ) ? clone $original_authordata : null;
					$id = $original_id;
				}

			} else {

				if ( $cell_content['page'] == 'current_page' && is_object($post) && property_exists($post, 'post_content')) {
					$content = apply_filters('the_content', $post->post_content);
				} elseif ($cell_content['page'] == 'this_page') {
					$other_post = get_post($cell_content['selected_post']);
					if ( is_object($other_post) && property_exists($other_post, 'post_content') ) {
						$content = apply_filters('the_content', $other_post->post_content);
					}
				}

			}

			if (isset($WPV_templates)) {
				add_filter('the_content', array($WPV_templates, 'the_content'), 1, 1);
			}


		}
		$ret = $target->cell_content_callback($content, $this);

		if ($cell_content['page'] == 'current_page') {
			do_action('ddl-layouts-render-end-post-content');
		}

		return $ret;

	}

}

class WPDD_layout_cell_post_content_factory extends WPDD_layout_cell_factory{

	function __construct() {

	}

	public function build($name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div') {
		return new WPDD_layout_cell_post_content($name, $width, $css_class_name, $content, $css_id, $tag);
	}

	public function get_cell_info($template) {
		$template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'post-content.svg';
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'post-content_expand-image.png';
		$template['name'] = sprintf( __('Post', 'ddl-layouts'), '' );
		$template['description'] = __('Display the content of a page, post or custom type using styling from the theme.', 'ddl-layouts');
		$template['button-text'] = __('Assign Post content Box', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create a new Post content Cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit Post content Cell', 'ddl-layouts');
		$template['dialog-template'] = $this->_dialog_template();
		$template['category'] = sprintf( __('%s elements', 'ddl-layouts'), WPDD_Layouts::get_theme_name() );
        $template['has_settings'] = false;
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start();
		?>
			<div class="cell-content">
				<p class="cell-name">{{ name }}</p>
	                <div class="cell-preview">
	                    <#
							if (content) {
								var preview = DDLayout.post_content_cell.get_preview(content,
											'<?php _e('Displays the content of the current page', 'ddl-layouts'); ?>',
											'<?php _e('Displays the content of %s', 'ddl-layouts'); ?>',
											'<?php _e('Loading', 'ddl-layouts'); ?>...',
											'<?php echo DDL_ICONS_SVG_REL_PATH . 'post-content-preview.svg'; ?>',
											this );
								print( preview );
							}
	                    #>
	                </div>
			</div>
		<?php
		return ob_get_clean();
	}

	private function _dialog_template() {

		ob_start();
		?>
		<ul class="ddl-form">
			<li>
				<fieldset>
					<legend><?php _e('Display content for:', 'ddl-layouts'); ?></legend>
					<div class="fields-group">
						<ul>
							<li>
								<label class="post-content-page">
									<input type="radio" name="<?php the_ddl_name_attr('page'); ?>" value="current_page" checked="checked"/>
									<?php _e('Current page', 'ddl-layouts'); ?>
								</label>
							</li>
							<li>
								<label class="post-content-page">
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
									$keys = array_keys( $post_types );
									$post_types_array = array_shift(  $keys  );
									$this->show_posts_dropdown( $post_types_array, get_ddl_name_attr( 'selected_post' ) );
								?>
							</li>
						</ul>
					</div>
				</fieldset>
			</li>


		</ul>
		<?php ddl_add_help_link_to_dialog(WPDLL_POST_CONTENT_CELL, __('Learn about the Post Content cell', 'ddl-layouts')); ?>
		<?php wp_nonce_field( 'wpv-ct-inline-edit', 'wpv-ct-inline-edit' ); ?>

		<?php
		return ob_get_clean();
	}


	public function enqueue_editor_scripts() {
		wp_register_script(
			'wp-post-content-editor',
			( WPDDL_GUI_RELPATH . "editor/js/post-content-cell.js" ),
			array(
				'jquery',
				Toolset_Assets_Manager::SCRIPT_ICL_EDITOR,
				Toolset_Assets_Manager::SCRIPT_TOOLSET_QUICKTAGS,
			),
			null,
			true
		);
		wp_enqueue_script( 'wp-post-content-editor' );

		wp_localize_script('wp-post-content-editor', 'DDLayout_post_content_strings', array(
				'current_post' => __('This cell will display the content of the post which uses the layout.', 'ddl-layouts'),
				'this_post' => __('This cell will display the content of a specific post.', 'ddl-layouts'),
				)
		);
	}

	private function show_posts_dropdown($post_type, $name, $selected = 0) {
		if ($post_type == 'ddl-all-post-types') {
			$post_type = 'any';
		}

		$attr = array('name'=> $name,
					  'post_type' => $post_type,
					  'show_option_none' => __('None', 'ddl-layouts'),
					  'selected' => $selected);


		add_filter('posts_clauses_request', array($this, 'posts_clauses_request_filter'), 10, 2 );

		$defaults = array(
			'depth' => 0, 'child_of' => 0,
			'selected' => $selected, 'echo' => 1,
			'name' => 'page_id', 'id' => '',
			'show_option_none' => '', 'show_option_no_change' => '',
			'option_none_value' => ''
		);
		$r = wp_parse_args( $attr, $defaults );
		extract( $r, EXTR_SKIP );

		$pages = get_posts(array('posts_per_page' => -1, 'post_type' => $post_type, 'suppress_filters' => false));
		$output = '';
		// Back-compat with old system where both id and name were based on $name argument
		if ( empty($id) )
			$id = $name;

		if ( ! empty($pages) ) {
			$output = "<select name='" . esc_attr( $name ) . "' id='" . esc_attr( $id ) . "' data-post-type='" . esc_attr( $post_type ). "'>\n";
			if ( $show_option_no_change )
				$output .= "\t<option value=\"-1\">$show_option_no_change</option>";
			if ( $show_option_none )
				$output .= "\t<option value=\"" . esc_attr($option_none_value) . "\">$show_option_none</option>\n";
			$output .= walk_page_dropdown_tree($pages, $depth, $r);
			$output .= "</select>\n";
		}

		echo $output;

		remove_filter('posts_clauses_request', array($this, 'posts_clauses_request_filter'), 10, 2 );

	}

	function posts_clauses_request_filter($pieces, $query ) {
		global $wpdb;
		// only return the fields required for the dropdown.
		$pieces['fields'] = "$wpdb->posts.ID, $wpdb->posts.post_parent, $wpdb->posts.post_title";

		return $pieces;
	}


}

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_cell_post_content_factory');
function dd_layouts_register_cell_post_content_factory($factories) {
	$factories['cell-post-content'] = new WPDD_layout_cell_post_content_factory;
	return $factories;
}


add_action('wp_ajax_ddl_post_content_get_post_title', 'ddl_post_content_get_post_title_callback');
function ddl_post_content_get_post_title_callback() {
    if ( !isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'ddl_layout_view_nonce') ) die("Undefined Nonce.");

    global $wpdb;

	echo $wpdb->get_var($wpdb->prepare( "SELECT post_title FROM {$wpdb->posts} WHERE ID=%s", $_POST['post_id']) );

	die();
}

