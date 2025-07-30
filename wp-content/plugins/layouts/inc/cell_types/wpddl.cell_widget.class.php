<?php



if( ddl_has_feature('widget-cell') === false ){
	return;
}


if ( !class_exists( 'Layouts_cell_widget', false ) ) {
    class Layouts_cell_widget{


        private $cell_type = 'widget-cell';
        private $widget_factory;
        
        function __construct() {

            // set global
            global $wp_widget_factory;
            $this->widget_factory = $wp_widget_factory;

            add_action( 'init', array(&$this,'register_widget_cell_init'), 12 );
            add_action('wp_ajax_get_widget_controls', array(&$this,'widget_cell_get_controls') );

	        if( $this->is_WP_four_seven_or_less() && $this->is_editor_page() ) {
		        add_action( 'admin_print_scripts', array( $this, 'initiliase_media_widgets_scripts' ) );
	        }
        }

        public function initiliase_media_widgets_scripts(){
	        $audio_media_widget = new WP_Widget_Media_Audio();
	        $audio_media_widget->enqueue_admin_scripts();

	        $video_media_widget = new WP_Widget_Media_Video();
	        $video_media_widget->enqueue_admin_scripts();

	        $image_media_widget = new WP_Widget_Media_Image();
	        $image_media_widget->enqueue_admin_scripts();

			add_action( 'admin_footer', array( $audio_media_widget, 'render_control_template_scripts' ) );
			add_action( 'admin_footer', array( $video_media_widget, 'render_control_template_scripts' ) );
			add_action( 'admin_footer', array( $image_media_widget, 'render_control_template_scripts' ) );

        }
        
        private function is_editor_page(){
            return isset( $_GET['page'] ) && $_GET['page'] === WPDDL_LAYOUTS_EDITOR_PAGE;
        }

        function register_widget_cell_init() {

            $widget_scripts = apply_filters('wpdll_cell_widget_scripts', array(
                array('widget_cell_js', WPDDL_RELPATH . $this->get_js_file_path(), array('jquery'), WPDDL_VERSION, true)
            ));
            
            register_dd_layout_cell_type($this->cell_type, 
                array(
                    'name' => __('Single Widget', 'ddl-layouts'),
                    'cell-image-url' => DDL_ICONS_SVG_REL_PATH . 'single-widget.svg',
                    'description' => __('Display a single WordPress Widget. You will be able to choose which widget to display, without having to create new Widget Areas.', 'ddl-layouts'),
                    'button-text' => __('Assign Single Widget cell', 'ddl-layouts'),
                    'dialog-title-create' => __('Create new Single Widget cell', 'ddl-layouts'),
                    'dialog-title-edit' => __('Edit Single Widget cell', 'ddl-layouts'),
                    'dialog-template-callback' => array(&$this,'widget_cell_dialog_template_callback'),
                    'cell-content-callback' => array(&$this,'widget_cell_content_callback'),
                    'cell-template-callback' => array(&$this,'widget_cell_template_callback'),
                    'cell-class' => 'widget-cell',
                    'has_settings' => true,
                    'preview-image-url' => DDL_ICONS_PNG_REL_PATH . 'widget_expand-image.png',
                    'register-scripts' => $widget_scripts,
                    'category' => __('Site elements', 'ddl-layouts'),
                    'translatable_fields' => array(
                        'widget' => array('title' => 'Widget title', 'type' => 'LINE', 'child_field' => 'title'),
                    )
                )
            );
        }

	    /**
	     * @return string
	     */
        private function get_js_file_path(){
            $new = '/inc/gui/editor/js/widget-cell.js';
            $old = '/inc/gui/editor/js/widget-cell-ajax.js';

            if( !$this->is_WP_four_seven_or_less() ){
                return $old;
            }

            return $new;
        }

	    private function is_WP_four_seven_or_less(){
		    return class_exists( 'WP_Widget_Media_Audio' );
	    }

        function widget_cell_dialog_template_callback() {
            
            if ( ! empty ( $GLOBALS['wp_widget_factory'] ) ) {
                    $widgets = $GLOBALS['wp_widget_factory']->widgets;
            } else {
                    $widgets = array();
            }

            

            ob_start();
            ?>
            <?php
                    /*
                     * Use the the_ddl_name_attr function to get the
                     * name of the text box. Layouts will then handle loading and
                     * saving of this UI element automatically.
                     */
            ?>
            <ul class="ddl-form widget-cell">
                <li>
                    <label for="<?php the_ddl_name_attr('widget_type'); ?>"><?php _e('Widget type:', 'ddl-layouts' ); ?></label>
                    <select name="<?php the_ddl_name_attr('widget_type'); ?>" data-nonce="<?php echo wp_create_nonce( 'ddl-get-widget' ); ?>">
                        <option value="" class="ddl-no-selection">--- <?php _e( 'Select Widget Type', 'ddl-layouts' );?> ---</option>
                        <?php foreach($widgets as $widget): ?>
                                <?php if(  !is_array($widget->widget_options['classname'] ) &&  !is_array( $widget->name ) ): ?>
                                        <option value="<?php echo $widget->widget_options['classname']; ?>"><?php echo $widget->name; ?></option>
                                <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </li>
                <li>
                    <fieldset class="js-widget-cell-fieldset hidden">
                        <legend><?php _e('Widget settings', 'ddl-layouts' ); ?>:</legend>
                        <div class="fields-group widget-cell-controls js-widget-cell-controls">
                        </div>
                    </fieldset>
                </li>
                <li>
                    <?php ddl_add_help_link_to_dialog(WPDLL_WIDGET_CELL,__('Learn about the Widget cell', 'ddl-layouts'));?>
                </li>			

            </ul>
            <?php
            return ob_get_clean();
        }


	    /**
	     * This method will render widget controls using standard form method that is part of the core class for WP widgets
         * We have small workaround for WPML lang selector widget here since it is not possible to use standard form method
         * in this case, because this widget expect that sidebar is defined which is in our case not possible.
	     */
	    function widget_cell_get_controls() {

		    if ( WPDD_Utils::user_not_admin() ) {
			    die( __( "You don't have permission to perform this action!", 'ddl-layouts' ) );
		    }

		    if ( wp_verify_nonce( $_POST['nonce'], 'ddl-get-widget' ) ) {
			    foreach ( $this->widget_factory->widgets as $widget ) {

				    if ( $widget->widget_options['classname'] != $_POST['widget'] ) {
					    continue;
				    }

				    // Small workaround for WPML Language selector widget, since in layout we don't have sidebar for widgets defined
				    // it is necessary to use different approach for rendering widget settings.
				    if ( 'widget_icl_lang_sel_widget' !== $widget->widget_options['classname'] ) {
					    // make sure that we are not loading tinyMCE here for text cell
					    $widget_options = ( $_POST['widget'] === 'widget_text' ) ? array( "visual" => false ) : null;
					    $widget->form( $widget_options );
				    } else {
					    $this->controls_for_wpml_lang_selector();
				    }

				    // Output a field so we can work out how the fields are named.
				    // We use this in JS to load and save the settings to the layout.
				    ?>
                    <input type="hidden" id="ddl-widget-name-ref"
                           value="<?php echo $widget->get_field_name( 'ddl-layouts' ); ?>">
				    <?php
				    break;
			    }
		    }

		    die();
	    }

	    function controls_for_wpml_lang_selector() {

	        if( ! defined( 'ICL_SITEPRESS_VERSION' ) || ! class_exists('WPML_LS_Admin_UI' ) ){
	            return '';
            }

            $section_url  = admin_url( 'admin.php?page=' . WPML_LS_Admin_UI::get_page_hook() . '#wpml-language-switcher-shortcode-action' );
	        $message = '<div class="toolset-alert toolset-alert-info">';
	        $message .= sprintf( __( 'To customize WPML language selector please follow %sthis link%s. Please notice, to customize selector you have to enable Custom language switchers first.', 'ddl-layouts' ), '<a href="' . $section_url . '" target="_blank">', '</a>' );
	        $message .= '</div>';
	        echo $message;
        }

        // Callback function for displaying the cell in the editor.
        function widget_cell_template_callback() {

            ob_start();
            ?>
            <div class="cell-content">
                <div class="cell-preview">
                    <div class="ddl-widget-preview">
                        <p><strong><#
                                var element = DDLayout.widget_cell.get_widget_name( content.widget_type );
                                print( element );
                        #></strong></p>
                        <img src="<?php echo DDL_ICONS_SVG_REL_PATH . 'widget.svg'; ?>" height="130px">
                    </div>
                </div>
            </div>
            <?php 
            return ob_get_clean();

        }

        // Callback function for display the cell in the front end.
        function widget_cell_content_callback($cell_settings) {


	        // In case of WPML Lang Selector widget render custom selector
	        if( $cell_settings['widget_type'] === 'widget_icl_lang_sel_widget' ){
		        $ret = do_shortcode( '[wpml_language_selector_widget]' );
		        return $ret;
            }

			$settings = null;
	        $current_widget = null;

            foreach ($this->widget_factory->widgets as $widget) {
                if ($widget->widget_options['classname'] == $cell_settings['widget_type']) {
                    $settings = $cell_settings['widget'];
	                $current_widget = $widget;
                    break;
                }
            }

            if( $current_widget ){
	            ob_start();
	            the_widget(get_class($current_widget), $settings, array('before_title' => '<h3 class="widgettitle">', 'after_title' => '</h3>', 'widget_id' => $current_widget->id));
	            return ob_get_clean();
            }

            return '';
        }

    }
    new Layouts_cell_widget();

}