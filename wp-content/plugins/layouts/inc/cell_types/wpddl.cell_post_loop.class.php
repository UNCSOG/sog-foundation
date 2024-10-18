<?php



if( ddl_has_feature('post-loop-cell') === false ){
    return;
}

class WPDD_layout_loop_cell extends WPDD_layout_cell
{

    function __construct($name, $width, $css_class_name = '', $content = null, $css_id, $tag)
    {
        parent::__construct($name, $width, $css_class_name, 'post-loop-cell', $content, $css_id, $tag);
        $this->set_cell_type('post-loop-cell');
    }

    function frontend_render_cell_content($target)
    {

        global $ddl_fields_api;
        $ddl_fields_api->set_current_cell_content($this->get_content());

        ob_start();

        if ($target->is_layout_argument_set('post-loop-callback') && function_exists($target->get_layout_arguments('post-loop-callback'))) {
            remove_all_actions('loop_start');
            remove_all_actions('loop_end');
            call_user_func($target->get_layout_arguments('post-loop-callback'));
        } else {

            if (have_posts()) {

                while (have_posts()) {
                    the_post();
                    get_template_part('content', get_post_format());
                }
            }
        }

        return $target->cell_content_callback(ob_get_clean(), $this);
    }
}

class WPDD_layout_loop_cell_factory extends WPDD_layout_cell_factory
{

    public function build($name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div')
    {
        return new WPDD_layout_loop_cell(null, $name, $width, $css_class_name, $content, $css_id, $tag);
    }

    public function get_cell_info($template)
    {
        $template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH . 'post-loop-cell.svg';
        //	$template['preview-image-url'] = WPDDL_RES_RELPATH . '/images/child-layout.png';
        $template['name'] = sprintf( __('Blog', 'ddl-layouts'), '' );
        $template['description'] = __('Display a WordPress ‘posts loop’ using styling from the theme. You need to include this cell, or a WordPress Archive cell, in layouts used for the blog, archives, search and other pages that display WordPress content lists.', 'ddl-layouts');
        $template['button-text'] = __('Assign Blog cell', 'ddl-layouts');
        $template['dialog-title-create'] = __('Create new Blog cell', 'ddl-layouts');
        $template['dialog-title-edit'] = __('Edit Blog cell', 'ddl-layouts');
        $template['dialog-template'] = $this->_dialog_template();
        $template['allow-multiple'] = false;
        $template['cell-class'] = 'post-loop-cell';
        $template['category'] = sprintf( __('%s elements', 'ddl-layouts'), WPDD_Layouts::get_theme_name() );
        $template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'post-loop_expand-image.png';
        $template['has_settings'] = false;
        return $template;
    }

    public function get_editor_cell_template()
    {
        global $current_user;
        $current_user = wp_get_current_user();
        ob_start();
        ?>
        <div class="cell-content">
            <p class="cell-name"><?php _e('Blog', 'ddl-layouts'); ?></p>

            <div class="cell-preview">
                <div class="ddl-post-loop-preview">
                    <img src="<?php echo DDL_ICONS_SVG_REL_PATH . 'post-loop.svg'; ?>" height="130px">
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }


    private function _dialog_template()
    {
        ob_start();
        ddl_add_help_link_to_dialog(WPDLL_LOOP_CONTENT_CELL, __('Learn about the Post Loop cell', 'ddl-layouts'));
        return ob_get_clean();
    }

    public function enqueue_editor_scripts()
    {
        //wp_register_script('post-loop-cell', WPDDL_RELPATH . '/inc/gui/editor/js/post-loop-cell.js', array('jquery'), WPDDL_VERSION, true);
        //wp_enqueue_script('post-loop-cell');
    }
}