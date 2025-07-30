<?php


if( ddl_has_feature('post-loop-views-cell') === false ) {
	return;
}
class WPDD_layout_loop_views_cell extends WPDD_layout_cell {

	function __construct($id, $name, $width, $css_class_name = '', $editor_visual_template_id = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		parent::__construct($id, $name, $width, $css_class_name, 'post-loop-views-cell', $content, $css_id, $tag, $unique_id);
		$this->set_cell_type('post-loop-views-cell');
	}

	function get_view_slug_from_id($id){
		return get_post_field( 'post_name', $id );
	}

	function frontend_render_cell_content($target) {

		global $ddl_fields_api;
		$ret = '';
		$ddl_fields_api->set_current_cell_content($this->get_content());

		if( $target->is_private_layout || apply_filters( 'ddl-is_front_end_editor_re_render', false ) ){
			$wpa_id = get_ddl_field('ddl_layout_view_id');
			$ct_slug = $this->get_view_slug_from_id($wpa_id);
			$ret = $target->cell_content_callback( '[wpv-view name="'.$ct_slug.'"]', $this);
		} else {

			if( function_exists('render_view') )
			{
                if( !$this->is_archive_page() )
                {
                    $ret = $target->cell_content_callback( WPDDL_Messages::archive_page_needed_message(), $this );
                }
                else {
                    $wpa_id = get_ddl_field('ddl_layout_view_id');
                    do_action( 'wpv_action_wpv_initialize_wordpress_archive_for_archive_loop', $wpa_id );
                    global $WPV_view_archive_loop, $wp_query;
                    $WPV_view_archive_loop->query = clone $wp_query;
                    $WPV_view_archive_loop->in_the_loop = true;
                    $ret = $target->cell_content_callback( render_view( array( 'id' => $wpa_id ) ), $this );
                    $WPV_view_archive_loop->in_the_loop = false;
                }
			}
			else
			{
				$ret = $target->cell_content_callback( WPDDL_Messages::views_missing_message(), $this );
			}
		}
		return $ret;

	}

	/**
	 * @return bool
	 */
	function is_archive_page() {

		// See if we have a WPA for the home page
		if ( is_home() ) {
			return true;
		}

		// Check if it's a post type archive and if we have a WPA for it
		if ( is_post_type_archive() ) {
			return true;
		}

		// Check taxonomy loops
		if ( is_archive() ) {
			return true;
		}

		// Check other archives
		if ( is_search() ) {
			return true;
		}

		if ( is_author() ) {
			return true;
		}

		if ( is_year() ) {
			return true;
		}

		if ( is_month() ) {
			return true;
		}

		if ( is_day() ) {
			return true;
		}

		return apply_filters( 'ddl-views_loop_cells_is_archive_get', false );
	}
}

class WPDD_layout_loop_views_cell_factory extends WPDD_layout_cell_factory{

	public function build($name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		return new WPDD_layout_loop_views_cell($unique_id, $name, $width, $css_class_name, '', $content, $css_id, $tag, $unique_id);
	}

	public function get_cell_info($template) {
		//$template['icon-url'] = WPDDL_RES_RELPATH .'/images/views-icon-color_16X16.png';
		//	$template['preview-image-url'] = WPDDL_RES_RELPATH . '/images/child-layout.png';
        $template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'views-post-loop.svg';
		$template['name'] = __('WordPress Archive (post archives, blog, search, etc.)', 'ddl-layouts');
		$template['description'] = __('Display the WordPress ‘loop’ with your styling. You need to include this cell in layouts used for the blog, archives, search and other pages that display WordPress content lists.', 'ddl-layouts');
		$template['button-text'] = __('Assign WordPress Archive cell', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create new WordPress Archive cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit WordPress Archive cell', 'ddl-layouts');
		$template['dialog-template'] = $this->_dialog_template();
		$template['allow-multiple'] = false;
		$template['cell-class'] = 'post-loop-views-cell';
		$template['category'] = __('Lists and loops', 'ddl-layouts');
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'views-post-loop_expand-image.png';
        $template['has_settings'] = false;
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start(); ?>
		<div class="cell-content">
			<p class="cell-name"><?php _e('WordPress Archive', 'ddl-layouts'); ?>: {{ name }}</p>
			<div class="cell-preview">

				<#
					if (content) {
						var preview = DDLayout.views_preview.get_preview( name,
											content,
											'<?php _e('Updating', 'ddl-layouts'); ?>...',
											'<?php _e('Loading', 'ddl-layouts'); ?>...',
											'<?php echo DDL_ICONS_SVG_REL_PATH . 'views-post-loop-preview.svg'; ?>',
											'views_archive'
											);
						print( preview );
					}
				#>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}


	private function _dialog_template() {
		global $WPV_templates, $WP_Views;

		//if( class_exists('Layouts_cell_views_content_grid') === false ) return;
		$dialog_provider = new Layouts_wordpress_archives_cell;
		$output = $dialog_provider->cell_dialogs_callback();

		// Fix the help link for Views Post Loop cell
		$output = str_replace(WPDLL_VIEWS_CONTENT_GRID_CELL, WPDLL_VIEWS_LOOP_CELL, $output);

		$output = str_replace(__('Learn about the Views cell', 'ddl-layouts'),__('Learn about the WordPress Archive cell', 'ddl-layouts'),$output);
		$output = str_replace(__('View:', 'ddl-layouts'), __('WordPress Archives:', 'ddl-layouts'),$output);
		$output = str_replace(__('Create new View', 'ddl-layouts'), __('Create new WordPress Archive', 'ddl-layouts'),$output);
		$output = str_replace(__('Use an existing View', 'ddl-layouts'), __('Use an existing WordPress Archive', 'ddl-layouts'),$output);
		$output = str_replace(__('Create new View', 'ddl-layouts'), __('Create new WordPress Archive', 'ddl-layouts'),$output);
		$output = str_replace(__('There are no Archive Views available.', 'ddl-layouts'), __('There are no WordPress Archives available.', 'ddl-layouts'),$output);
		$output = str_replace(__('--- Select View ---', 'ddl-layouts'), __('--- Select WordPress Archive ---', 'ddl-layouts'),$output);

		return $output;
	}

	public function enqueue_editor_scripts() {
	}
}

