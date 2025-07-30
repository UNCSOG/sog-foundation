<?php



use OTGS\Toolset\Common\Settings\BootstrapSetting;

if( ddl_has_feature('accordion-cell') === false ){
    return;
}

class WPDD_layout_accordion extends WPDD_layout_container{

    protected $random_id = 0;

    function __construct( $id, $name, $width, $css_class_name = '', $editor_visual_template_id = '', $css_id = '', $tag = 'div', $cssframework = 'bootstrap', $args = array() ) {
        parent::__construct( $id, $name, $width, $css_class_name, 'accordion-cell', $css_id, $tag );
        $this->set_cell_type('accordion-cell');
        $this->layout = new WPDD_layout( $width, $cssframework);
        $this->random_id = uniqid();
    }

    function get_as_array() {
        $data = parent::get_as_array();
        $data['kind'] = 'Accordion';
        $data = array_merge($data, $this->layout->get_as_array());

        return $data;
    }

    public function accordion_open(){
        ob_start();
        switch( Toolset_Settings::get_instance()->get_bootstrap_version_numeric() ) {
			case BootstrapSetting::NUMERIC_BS4:
				$container_class = 'accordion';
				$additional_attributes = '';
				break;
			default:
				$container_class = 'panel-group';
				$additional_attributes = 'role="tablist" aria-multiselectable="true"';
				break;
		}

		printf(
			'<div class="%s" id="%s" %s>',
			$container_class,
			$this->get_unique_identifier(),
			$additional_attributes
		);

		return apply_filters('ddl-accordion_open', ob_get_clean(), $this );
    }

    public function accordion_close(){
        ob_start();?>
        </div>
        <?php
        return apply_filters('ddl-accordion_close', ob_get_clean(), $this );
    }

    public function get_unique_identifier(){
    	$prefix = (
			Toolset_Settings::get_instance()->get_bootstrap_version_numeric() === BootstrapSetting::NUMERIC_BS4
			? 'accordion'
			: 'panel'
		);
        return apply_filters(
        	'ddl-accordion-get_unique_identifier',
			'ddl-' . $prefix . '_' . $this->get_id() . '_' . $this->random_id,
			$this
		);
    }

    public function get_id(){
        return $this->id;
    }

}

class WPDD_layout_accordion_cell_factory extends WPDD_layout_container_factory
{
    public function get_cell_info($template)
    {
        $template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'svg-collapse.svg';
        $template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'layout-cells_accordion.png';
        $template['name'] = __('Accordion', 'ddl-layouts');
        $template['description'] = __('Auto-collapsing and expanding boxes.', 'ddl-layouts');
        $template['button-text'] = __('Assign Accordion cell', 'ddl-layouts');
        $template['dialog-title-create'] = __('Create new Accordion cell', 'ddl-layouts');
        $template['dialog-title-edit'] = __('Edit Accordion cell', 'ddl-layouts');
        $template['dialog-template'] = $this->_dialog_template();
        $template['category'] = __('Layout structure', 'ddl-layouts');
        $template['has_settings'] = false;
        return $template;
    }

    protected function _dialog_template()
    {
        ob_start();
        echo '';
        return ob_get_clean();
    }

    public function build( ){
        // prevents possible error for not
    }
}


class WPDD_layout_accordion_panel extends WPDD_layout_container_row{

    protected $panel_classes = '';

    function __construct( $id, $name, $css_class_name = '', $editor_visual_template_id = '', $layout_type = 'fixed', $css_id = '', $additionalCssClasses = '', $tag = 'div', $mode = 'panel', $row = array(), $args = array() ){
        parent::__construct( $id, $name, $css_class_name, $editor_visual_template_id, $layout_type, $css_id, $additionalCssClasses, $tag, $mode, $row, $args);
	    $this->mode = 'panel';
        $this->panel_classes = isset( $row['panelClasses'] ) ? $row['panelClasses'] : $this->panel_classes;
    }

    function get_kind() {
        return 'Panel';
    }

    function get_anchor(){
        return apply_filters('ddl-accordion-get_panel_anchor', 'ddl-panel_'.$this->get_id().'_'.$this->random_id, $this );
    }

    function filter_panel_title_classes( $classes ){
        $classes = str_replace(' ','', $classes);
        $classes = str_replace(',',' ', $classes);
        $classes = preg_replace('/[^A-Za-z0-9 _-]/', '', $classes);
        return apply_filters('ddl-accordion-get_panel_heading_classes', $classes, $this );
    }

    function get_panel_title_classes(){
        return $this->panel_classes;
    }

    function render_panel_open( $accordion_id, $renderer ){
        $count = WPDD_layouts_layout_accordion::$panel_count;
        $suffix = '_'.$count . '_' . $this->random_id;
        $cssId = apply_filters('ddl-accordion-get_panel_row_css_id', $this->get_css_id() ? 'id="'.esc_attr( $this->get_css_id() ).'"' : '', $this, WPDD_layouts_layout_accordion::$panel_count );
        $panel_heading_classes = $this->filter_panel_title_classes( $this->panel_classes );
        $anchor_class = $count > 1 ? 'collapsed' : '';
        $expanded = $count === 1 ? 'true' : 'false';
	    $content = $this->get_translated_content( $renderer->get_context() );
        $title = $content['title'];

        ob_start();

        switch( Toolset_Settings::get_instance()->get_bootstrap_version_numeric() ) {
			case BootstrapSetting::NUMERIC_BS4:
				$collapse_active_class = $count === 1 ? 'show' : '';
				?>
				<div class="card">
					<div class="card-header <?php echo esc_attr( $panel_heading_classes );?>" id="heading<?php echo $suffix;?>">
						<button class="btn btn-link <?php echo esc_attr( $anchor_class );?>"
							type="button"
							data-toggle="collapse"
							data-parent="#<?php echo esc_attr( $accordion_id );?>"
							data-target="#<?php echo esc_attr( $this->get_anchor() );?>"
							aria-expanded="<?php echo esc_attr( $expanded );?>"
							aria-controls="<?php echo esc_attr( $this->get_anchor() );?>"
						>
							<?php echo $title;?>
						</button>
					</div>
					<?php
					$panel_class = apply_filters( 'ddl-accordion-get_panel_class', $this->get_panel_css_class(), $this, $renderer, WPDD_layouts_layout_tabs::$tab_count );
					str_replace( 'panel-collapse', 'collapse', $panel_class );

					$panel_data_attributes = apply_filters( 'ddl-accordion-get_panel_data_attributes', '', $this, $renderer, WPDD_layouts_layout_tabs::$tab_count );
					?>
					<div id="<?php echo esc_attr( $this->get_anchor() );?>"
							class="<?php echo esc_attr( $panel_class );?> <?php echo esc_attr( $collapse_active_class );?>"
							role="tabpanel"
							aria-labelledby="heading<?php echo esc_attr( $suffix );?>"
							data-parent="#<?php echo esc_attr( $accordion_id ); ?>"
							<?php echo $panel_data_attributes; ?>
					>
						<div class="card-body">
				<?php
				break;
			default:
				// Bootstrap 3 or below
				$collapse_active_class = $count === 1 ? 'in' : '';
				?>
				<div class="panel panel-default">
				<div class="panel-heading <?php echo esc_attr( $panel_heading_classes );?>" role="tab" id="heading<?php echo $suffix;?>">
					<h4 class="panel-title">
						<a class="<?php echo esc_attr( $anchor_class );?>" role="button" data-toggle="collapse" data-parent="#<?php echo esc_attr( $accordion_id );?>" href="#<?php echo esc_attr( $this->get_anchor() );?>" aria-expanded="<?php echo esc_attr( $expanded );?>" aria-controls="<?php echo esc_attr( $this->get_anchor() );?>"><?php echo $title;?></a>
					</h4>
				</div>
				<?php
				$panel_class = apply_filters( 'ddl-accordion-get_panel_class', $this->get_panel_css_class(), $this, $renderer, WPDD_layouts_layout_tabs::$tab_count );

				$panel_data_attributes = apply_filters( 'ddl-accordion-get_panel_data_attributes', '', $this, $renderer, WPDD_layouts_layout_tabs::$tab_count );
				?>
				<div id="<?php echo esc_attr( $this->get_anchor() );?>" class="<?php echo esc_attr( $panel_class );?> <?php echo esc_attr( $collapse_active_class );?>" role="tabpanel" aria-labelledby="heading<?php echo esc_attr( $suffix );?>" <?php echo $panel_data_attributes;?> >

				<div class="panel-body">
				<?php
				break;
		}
        $css_classes = apply_filters( 'ddl-accordion-get_panel_row_element_classes', $this->additionalCssClasses, $this, $renderer,WPDD_layouts_layout_accordion::$panel_count );
        $data_attributes = apply_filters( 'ddl-accordion-get_panel_row_element_data_attributes', '', $this, $renderer, WPDD_layouts_layout_accordion::$panel_count );

        echo apply_filters('ddl-accordion-get_panel_row_element_open', '<'.$this->tag.' class="row '.esc_attr( $css_classes ).'" '.$cssId.' '.$data_attributes.'>', $this, WPDD_layouts_layout_accordion::$panel_count);

        $content = apply_filters('ddl-accordion_panel_open', ob_get_clean(), $this );

        return $content;
    }



    function render_panel_close(){
        ob_start();
            echo apply_filters('ddl-accordion-get_panel_row_element_close', '</'.$this->tag.'>', $this, WPDD_layouts_layout_accordion::$panel_count);
        ?>
                </div>
            </div>
        </div>
        <?php
	    $content = apply_filters('ddl-accordion_panel_close', ob_get_clean(), $this );
        return $content;
    }

    function get_panel_css_class(){
        return apply_filters( 'ddl-accordion-get_panel_css_class', $this->get_css_class_name(), $this, WPDD_layouts_layout_accordion::$panel_count );
    }
}

class WPDD_layouts_layout_accordion{

    private static $instance;
    static $panel_count = 1;
    static $supported = array('accordion-cell');
    private $mode = 'panel';
    private $data_parent = '';

    private function __construct(){
        $this->init();
    }

    protected function init(){
        add_filter('dd_layouts_register_cell_factory', array(&$this, 'dd_layouts_register_layout_accordion_cell_factory') );
        add_filter( 'ddl-cell_render_output_before_content', array(&$this, 'render_accordion_open'), 99, 2 );
        add_filter( 'ddl-cell_render_output_after_content', array(&$this, 'render_accordion_close'), 99, 2 );
        add_filter( 'ddl_render_row_start', array(&$this, 'panel_start_render'), 99, 4 );
        add_filter( 'ddl_render_row_end', array(&$this, 'panel_end_render'), 99, 4 );
    }

    public function dd_layouts_register_layout_accordion_cell_factory($factories){
        $factories['accordion-cell'] = new WPDD_layout_accordion_cell_factory;
        return $factories;
    }
    
    public function render_accordion_open( $output, $cell ){
        if( $cell && in_array( $cell->get_cell_type(), self::$supported ) ){
            $output .= $cell->accordion_open();
            $this->data_parent = $cell->get_unique_identifier();
        }

        return $output;
    }
    
    public function render_accordion_close( $output, $cell){
        if( $cell && in_array( $cell->get_cell_type(), self::$supported ) ){
            $output .= $cell->accordion_close();
            self::$panel_count = 1;
        }

        return $output;
    }

    public function panel_start_render( $out, $args, $row, $renderer ){

        if( method_exists( $row, 'render_panel_open') ){
            $out = $row->render_panel_open( $this->data_parent, $renderer );
            self::$panel_count++;
        }

        return $out;
    }

    public function panel_end_render( $out, $mode, $tag, $row ){

        if( method_exists( $row, 'render_panel_close')  ){
            $out = $row->render_panel_close();
        }

        return $out;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDD_layouts_layout_accordion();
        }

        return self::$instance;
    }
}
add_action( 'ddl-before_init_layouts_plugin', array('WPDD_layouts_layout_accordion', 'getInstance') );
