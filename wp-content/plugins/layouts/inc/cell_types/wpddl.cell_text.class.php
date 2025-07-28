<?php



use OTGS\Toolset\Common\Settings\BootstrapSetting;

if( ddl_has_feature('cell-text') === false ){
	return;
}

class WPDD_layout_cell_text extends WPDD_layout_text_based_cell {

	function __construct($id, $name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		parent::__construct($id, $name, $width, $css_class_name, 'cell-text-template', $content, $css_id, $tag, $unique_id);
		$this->set_cell_type('cell-text');
		add_filter( 'ddl_apply_the_content_filter_in_cells', array( $this, 'handle_woocommerce_tabs' ), 999, 2 );
	}

	function frontend_render_cell_content($target) {

		$content = $this->get_translated_content( $target->get_context() );
		$content = $content['content'];

		if ($this->get('responsive_images')) {
			if ( Toolset_Settings::get_instance()->get_bootstrap_version_numeric() !== BootstrapSetting::NUMERIC_BS4 ) {
				// stript hieght="xx" and width="xx" from images.
				$regex = '/<img[^>]*?(width="[^"]*")/siU';
				if ( preg_match_all( $regex, $content, $matches, PREG_SET_ORDER ) ) {
					foreach ( $matches as $val ) {
						$found = str_replace( $val[1], '', $val[0] );
						$content = str_replace( $val[0], $found, $content );
					}
				}
				$regex = '/<img[^>]*?(height="[^"]*")/siU';
				if ( preg_match_all( $regex, $content, $matches, PREG_SET_ORDER ) ) {
					foreach ( $matches as $val ) {
						$found = str_replace( $val[1], '', $val[0] );
						$content = str_replace( $val[0], $found, $content );
					}
				}
			}

			// Process the caption shortcode
			$regex = '/\[caption.*?\[\/caption\]/siU';
			if(preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $val) {
					$shortcode = $val[0];
					$result = do_shortcode($shortcode);

					// set the generated div to 100% width
					$regex = '/<div[^>]*?width:([^"^;]*?)/siU';
					if(preg_match_all($regex, $result, $new_matches, PREG_SET_ORDER)) {
						foreach ($new_matches as $val) {
							$found = str_replace($val[1], '100%', $val[0]);
							$result = str_replace($val[0], $found, $result);
						}
					}
					$content = str_replace($shortcode, $result, $content);
				}

			}

			$content = $target->make_images_responsive($content);

		}


		if( $target->is_private_layout && $target->run_content_filters === false ){
			if ( !$this->get( 'disable_auto_p' ) ){
				$content = wpautop( $content );
            }
			return $target->cell_content_callback( $content, $this );

		} else {
			$content = $this->handle_content_filters( $content );
			return $target->cell_content_callback( $content, $this );
		}
	}

	function handle_content_filters( $content ){

		add_filter( 'icl_post_alternative_languages', '__return_empty_string' );

		$enable_content_filter = $this->the_content_enabled();

		if ($this->get( 'disable_auto_p' ) && has_filter( 'the_content', 'wpautop' )) {
			remove_filter( 'the_content', 'wpautop' );
			if( apply_filters( 'ddl_apply_the_content_filter_in_cells', $enable_content_filter, $this ) ) {
				$content = apply_filters( 'the_content', $content );
			}
			add_filter( 'the_content', 'wpautop' );
		} else {
			if( apply_filters( 'ddl_apply_the_content_filter_in_cells', $enable_content_filter, $this ) ) {
				$content = apply_filters( 'the_content', $content );
			} else {
				$content = wpautop( $content );
			}
		}

		remove_filter( 'icl_post_alternative_languages', '__return_empty_string' );

		$content = apply_filters( 'wpv-pre-process-shortcodes', $content );

		return do_shortcode( $content );
	}

	private function the_content_enabled(){
	    return $this->get('disable_the_content_filter') !== true;
    }

	function handle_woocommerce_tabs( $bool, $me ){
		$content = $this->get_content();
		if( WPDD_Utils::is_woocommerce_page() && is_woocommerce() && WPDD_Utils::content_content_has_views_tag( $content ) && apply_filters( 'ddl-is_integrated_theme', false) === false ){
			$bool = false;
			remove_all_filters( 'the_content' );
		}

		return $bool;
	}
}

class WPDD_layout_cell_text_factory extends WPDD_layout_text_cell_factory{

	public function __construct() {
		parent::__construct();
		$backend_editor = isset($_GET['page']) && $_GET['page'] == 'dd_layouts_edit';
		$frontend_editor = isset($_GET['toolset_editor']) && ( user_can_edit_layouts() || user_can_edit_private_layouts() );

		if($backend_editor || $frontend_editor){
			add_filter( 'cred-register_cred_editor_scripts_and_styles', array(&$this, 'return_true'), 10, 1 );
			add_filter('gform_display_add_form_button', array($this, 'add_gf_support'));
		}
	}

	public function add_gf_support($value) {
		$value = true;
		return $value;
	}

	public function return_true( $bool ){
		return true;
	}

	public function return_false( $bool ){
		return false;
	}

	public function build($name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		$this->cell = new WPDD_layout_cell_text(null, $name, $width, $css_class_name, $content, $css_id, $tag, $unique_id);
		return $this->cell;
	}

	public function get_cell_info( $template, $print_dialog = false ) {
		$template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'rich-content.svg';
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'visual-editor_expand-image.png';
		$template['name'] = __('Visual Editor (post fields, text, images)', 'ddl-layouts');
		$template['description'] = __('Display post fields, static text, images and any other media that you can include using the WordPress visual editor.', 'ddl-layouts');
		$template['button-text'] = __('Assign Visual Editor cell', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create new Visual Editor cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit Visual Editor cell', 'ddl-layouts');

		if( $print_dialog === true ){
			$template['dialog-template'] = $this->_dialog_template();
		}

		$template['has_settings'] = true;
		$template['category'] = __('Fields, text and media', 'ddl-layouts');
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start();
		?>
        <div class="cell-content clearfix">

            <p class="cell-name"><?php _e('Visual Editor', 'ddl-layouts'); ?></p>

            <# if( content.content ){ #>
                <div class="cell-preview">
                    <#
                            var preview = content.content;
                            if (typeof content.disable_auto_p != 'undefined' && !content.disable_auto_p) {
                            // display the content with auto paragraphs
                            preview = window.switchEditors.wpautop(preview);
                            }
                            preview = DDL_Helper.sanitizeHelper.strip_srcset_attr(preview);
                            preview = DDL_Helper.sanitizeHelper.stringToDom( preview );
                            print( DDL_Helper.sanitizeHelper.transform_caption_shortcode(preview.innerHTML) );
                            #>
                </div>
                <# } #>
        </div>
		<?php
		return ob_get_clean();
	}

	public function enqueue_editor_scripts() {
		wp_enqueue_script('cred_cred');
		wp_enqueue_script('page');
		wp_enqueue_script('editor');
		add_thickbox();
		wp_enqueue_script('media-upload');
		wp_enqueue_script('word-count');
		$deps = array(
			'jquery',
			Toolset_Assets_Manager::SCRIPT_ICL_EDITOR,
			Toolset_Assets_Manager::SCRIPT_TOOLSET_QUICKTAGS,
		);
		if( WPDD_Layouts::views_available() ) {
			$deps[] = 'views-shortcodes-gui-script';
		}
		wp_register_script(
			'text_cell_js',
			WPDDL_RELPATH . '/inc/gui/editor/js/text-cell.js',
			$deps,
			WPDDL_VERSION,
			true
		);
		wp_enqueue_script('text_cell_js');
	}

	public function enqueue_editor_styles(){
		wp_enqueue_style('cred_cred_style');
	}

	protected function _dialog_template() {
		add_filter('user_can_richedit', array(__CLASS__, 'true'), 100);
		ob_start();

		?>
        <div class="ddl-form from-top-0 pad-top-0">

            <div class="js-visual-editor-views-shortcode-notification visual-editor-views-shortcode-notification from-top-0 pad-top-0"
                 data-view="<?php esc_attr_e("It looks like you are trying to display a View. For your convenience, Layouts now comes with a View cell, which will let you achieve this much easier. We suggest that you try the new 'Views Content Grid' cell. You will be able to insert an existing View or create a new View.", 'ddl-layouts');?>"
                 data-content-template="<?php esc_attr_e("It looks like you are trying to display fields. For your convenience, Layouts now comes with a Content Template cell, which will let you achieve this much easier.", 'ddl-layouts');?>"
                 data-cred="<?php esc_attr_e("It looks like you are trying to display a Form. For your convenience, Layouts now comes with a Toolset form cell, which will let you achieve this much easier. We suggest that you try the new 'Toolset Form' cell. You will be able to insert an existing Form or create a new Form.", 'ddl-layouts');?>">
            </div>



            <div id="visual-editor-editor-container" class="js-visual-editor-editor-container ddl-visual-editor-editor-container from-top-0 pad-top-0">
                <div class="wp-editor-tabs">
                    <a class="js-visual-editor-toggle wp-switch-editor" data-editor="tinymce"><?php esc_attr_e( 'Visual', 'ddl-layouts' ); ?></a>
                    <a class="js-visual-editor-toggle wp-switch-editor" data-editor="codemirror"><?php esc_attr_e( 'HTML', 'ddl-layouts' ); ?></a>
                    <input type="hidden" id="preferred_editor" value="<?php $ddl_preferred_editor = get_user_option( 'ddl_preferred_editor', get_current_user_id() ); echo esc_attr( $ddl_preferred_editor !== false ? $ddl_preferred_editor : 'tinymce' ); ?>">
                </div>
                <div id="visual-editor-editor-switch-message"></div>


                <div id="js-visual-editor-tinymce">
					<?php
					$this->do_tinymce();
					?>
                </div>
                <div id="js-visual-editor-codemirror" class="ddl-visual-editor-codemirror">
					<?php
					$this->do_codemirror();
					?>
                </div>
                <p class="ddl-learn-more">
					<?php ddl_add_help_link_to_dialog(WPDLL_RICH_CONTENT_CELL,
						__('Learn about the Visual editor cell', 'ddl-layouts'), true);
					?>
                </p>
            </div>

            <div class="ddl-form-item">
                <fieldset>
                    <p class="fields-group">
                        <label class="checkbox" for="<?php the_ddl_name_attr('responsive_images'); ?>">
                            <input type="checkbox" name="<?php the_ddl_name_attr('responsive_images'); ?>" id="<?php the_ddl_name_attr('responsive_images'); ?>">
							<?php _e('Display images with responsive size', 'ddl-layouts'); ?>
                        </label>
                    </p>
                </fieldset>
            </div>

            <div class="ddl-form-item">
                <fieldset>
                    <p class="fields-group">
                        <label class="checkbox" for="<?php the_ddl_name_attr('disable_auto_p'); ?>">
                            <input type="checkbox" name="<?php the_ddl_name_attr('disable_auto_p'); ?>" id="<?php the_ddl_name_attr('disable_auto_p'); ?>">
							<?php _e('Disable automatic paragraphs', 'ddl-layouts'); ?>
                        </label>
                    </p>

                </fieldset>
            </div>

            <div class="ddl-form-item">
                <fieldset>
                    <p class="fields-group">
                        <label class="checkbox" for="<?php the_ddl_name_attr('disable_the_content_filter'); ?>">
                            <input type="checkbox" name="<?php the_ddl_name_attr('disable_the_content_filter'); ?>" id="<?php the_ddl_name_attr('disable_the_content_filter'); ?>">
					        <?php printf( __('Disable %sthe_content%s filter', 'ddl-layouts'), '<em>', '</em>'); ?>
                        </label>
                    </p>

                </fieldset>
            </div>

			<?php echo parent::_dialog_template();?>
        </div>


		<?php

		return ob_get_clean();
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

	function do_tinymce() {

		$options = array(
			// See text-cell.js for editor height too
			'editor_height' => 300,
			'dfw' => false,
			'drag_drop_upload' => true,
			'tabfocus_elements' => 'insert-media-button,save-post',
			'textarea_name' => $this->element_name( 'content' ),
			'wpautop' => true, /* Also as TinyMCE setting */
		);
		add_filter( 'tiny_mce_before_init', array( $this, 'configure_tinymce_editor' ), 999 );

		wp_editor( '', 'celltexteditor', $options );
		remove_filter( 'user_can_richedit', array( __CLASS__, 'true' ), 100 );

	}

	function do_codemirror() {
		?>
        <div class="code-editor-toolbar js-code-editor-toolbar">
            <ul class="js-wpv-v-icon">
                <li>
                    <button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="" data-content="visual-editor-html-editor">
                        <i class="fa fa-picture-o icon-picture"></i>
                        <span class="button-label"><?php _e('Media','ddl-layouts'); ?></span>
                    </button>
                </li>
				<?php
				// Action to add Toolset buttons to the Visual Editor cell editor in HTML mode
				do_action( 'toolset_action_toolset_editor_toolbar_add_buttons', 'visual-editor-html-editor', 'layouts' );
				echo apply_filters( 'ddl-meta_html_add_form_button', '', 'visual-editor-html-editor' );
				?>
            </ul>
        </div>
        <textarea name="name" rows="10" class="js-visual-editor-html-editor-textarea" data-id="" id="visual-editor-html-editor"></textarea>
		<?php
	}

	// auxiliary functions
	public static function true()
	{
		return true;
	}

	public static function false()
	{
		return false;
	}

}

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_cell_text_factory');
function dd_layouts_register_cell_text_factory($factories) {
	$factories['cell-text'] = new WPDD_layout_cell_text_factory;
	return $factories;
}
