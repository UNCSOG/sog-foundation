<?php

class WPDD_layout_render {

    protected $layout;
    protected $child_renderer;
    protected $output;
    protected $offset = 0; //set offset member to 0
    protected $current_layout;
    protected $current_row_mode;
    protected $is_child;
    protected $layout_args = array();
    protected $context;
    protected $framework = null;
    public $run_content_filters = false;

    // TODO: this constant should be set from settings
    const MAX_WIDTH = 12;


    function __construct( $layout, $child_renderer = null, $is_private_layout = false ){
        $this->layout = $layout;
        $this->child_renderer = $child_renderer;
        $this->output = '';
        $this->current_layout = array($layout);
        $this->is_private_layout = $is_private_layout;
        $this->current_row_mode = array();
        $this->framework = $this->get_wpddl_framework();
        $this->is_child = false;
        $this->handle_full_widths();
    }

    protected function get_wpddl_framework( ){
	    return WPDDL_Framework::getInstance();
    }

    public function push_row_mode( $mode ){
        array_push($this->current_row_mode, $mode);
    }

    public function is_child(){
        return $this->is_child;
    }

    public function get_layout(){
        return $this->layout;
    }

    public function get_child_renderer(){
        return $this->child_renderer;
    }

    public function get_current_layout(){
        return $this->current_layout;
    }

    public function get_layout_args(){
        return $this->layout_args;
    }

    function handle_full_widths(){

        if(  $this->child_renderer === null ) return;

        $child = $this->child_renderer->current_layout[0];

        if( $child === null ) return;

        // check if the child row is set to max width
        if( self::MAX_WIDTH === $this->layout->get_width_of_child_layout_cell() )
        {
            // remove parent and inject children
            if( $this->layout->change_full_width_child_layout_row( $child ) )
            {
                // remove unneeded renderer, children rows will render with parent renderer
                if ($this->child_renderer->child_renderer) {
                    $this->child_renderer = $this->child_renderer->child_renderer;
                } else {
                    $this->child_renderer = $this->child_renderer ;
                }
            }
        }
    }

    function has_child_renderer() {
        return $this->child_renderer != null;
    }

    function render_child() {
        if ($this->child_renderer) {
            return $this->child_renderer->render_to_html(false);
        } else {
            return '';
        }
    }

    function get_container_class($mode){
        return $this->framework->get_container_class($mode, $this);
    }

    function get_container_fluid_class($mode){
        return $this->framework->get_container_fluid_class($mode);
    }

    function get_row_class($mode){
        return $this->framework->get_row_class($mode);
    }

    function get_offset_prefix(){
        return $this->framework->get_offset_prefix();
    }

    function get_image_responsive_class(){
        return $this->framework->get_image_responsive_class();
    }

    function get_additional_column_class(){
        return $this->framework->get_additional_column_class();
    }

    function render_to_html($render_parent = true) {

        if ($render_parent) {
            $parent_layout = $this->layout->get_parent_layout();
            $this->is_child = false;
        } else {
            $parent_layout = false;
            $this->is_child = true;
        }

        if ($parent_layout) {
            $manager = new WPDD_layout_render_manager($parent_layout, $this);
            $parent_render = $manager->get_renderer( );
            $parent_render->set_layout_arguments($this->layout_args);
            return $parent_render->render_to_html();
        } else {
            $this->layout->frontend_render($this);
            return $this->output;
        }
    }

    function row_start_callback( $row = null ) {

        if( !$row ) return;

        $layout_type = $row->get_layout_type();
        $cssId = $row->get_css_id();
        $additionalCssClasses = $row->get_additional_css_classes();
        $tag = $row->get_tag();
        $mode = $row->get_mode();

        $layout_type = $layout_type ? $layout_type : 'fixed';
        $cssId = $row->get_css_id() ? $cssId : '';
        $additionalCssClasses = $additionalCssClasses ? $additionalCssClasses : '';
        $tag = $tag ? $tag : 'div';
        $mode = $mode ? $mode : 'normal';
        $containerPadding = $row->get_container_padding();

        $containerPaddingClass = '';
        if(false === $containerPadding){
            $containerPaddingClass .='ddl-remove-bs-padding';
        }


        $this->offset = 0; // reset offset at the beginning of the row

        // if this is not a top level row then we should force full width.
        if (sizeof($this->current_row_mode) > 0 || $this->is_child) {
            $mode = 'sub-row';
        }

        array_push($this->current_row_mode, $mode);

        switch ($layout_type) {
            case 'fixed':
            case '';
                $type = '';
                break;

            default:
                $type = '-'.$layout_type;
                break;
        }

        $type = apply_filters('ddl-get_fluid_type_class_suffix', $type, $mode );

        $additionalCssClasses = apply_filters('ddl-get_row_additional_css_classes', $additionalCssClasses, $mode);

        $additionalAttributes = apply_filters('ddl-get_row_additional_attributes', '', $row, $this);

        ob_start();

        switch ($mode) {
            case 'normal':

                $additionalCssClasses = ($additionalCssClasses) ? $additionalCssClasses : '';
                $cssId = ($cssId) ? 'id="' . $cssId . '"' : '';
                ?>
                <div class="<?php printf('%s', $this->get_container_class($mode)); ?> <?php echo $containerPaddingClass;?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode);?> <?php echo $additionalCssClasses;?>" <?php echo $cssId; ?> <?php echo $additionalAttributes; ?>>
                <?php
                break;

            case 'full-width-background':

                $additionalCssClasses = ($additionalCssClasses) ? $additionalCssClasses : '';
                $cssId = ($cssId) ? 'id="' . $cssId . '"' : '';
                ?>
                <<?php echo $tag; ?> class="full-bg <?php echo $additionalCssClasses; ?>" <?php echo $cssId; ?> <?php echo $additionalAttributes; ?>>
                <div class="<?php printf('%s', $this->get_container_class($mode)); ?> <?php echo $containerPaddingClass;?>">
                <div class="<?php echo $this->get_row_class($mode); ?>">
                <?php
                break;

            case 'full-width':

                $additionalCssClasses = ($additionalCssClasses) ? $additionalCssClasses : '';
                $cssId = ($cssId) ? 'id="' . $cssId . '"' : '';
                ?>
                <div class="<?php printf('%s', $this->get_container_fluid_class($mode)); ?> <?php echo $containerPaddingClass;?>">
                <<?php echo $tag; ?> class="ddl-full-width-row <?php echo $this->get_row_class($mode);?> <?php echo $additionalCssClasses;?>" <?php echo $cssId; ?> <?php echo $additionalAttributes; ?>>
                <?php
                break;

            case 'sub-row':

                $additionalCssClasses = ($additionalCssClasses) ? $additionalCssClasses : '';
                $cssId = ($cssId) ? 'id="' . $cssId . '"' : '';
                ?>
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode);?> <?php echo $additionalCssClasses;?>" <?php echo $cssId; ?> <?php echo $additionalAttributes; ?>>
                <?php
                break;
            default:

                $additionalCssClasses = ($additionalCssClasses) ? $additionalCssClasses : '';
                $cssId = ($cssId) ? 'id="' . $cssId . '"' : '';
                ?>
                <div class="<?php printf('%s', $this->get_container_class($mode)); ?> <?php echo $containerPaddingClass;?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode);?> <?php echo $additionalCssClasses;?>" <?php echo $cssId; ?> <?php echo $additionalAttributes; ?>>
            <?php
                break;
        }

        $args = array(
            'mode' => $mode,
            'type' => $type,
            'tag' => $tag,
            'additionalAttributes' => $additionalAttributes,
            'additionalCssClasses' => $additionalCssClasses,
            'cssId' => $cssId,
            'container_class' => $this->get_container_class($mode),
            'row_class' => $this->get_row_class($mode),
            'container_fluid_class' => $this->get_container_fluid_class($mode)
        );

        $this->output .= apply_filters( 'ddl_render_row_start', ob_get_clean(), $args, $row, $this );

        return $this->output;
    }

    function row_end_callback( $row = null ) {
        if( !$row ) return '';

        $tag = $row->get_tag();
        $tag = $tag ? $tag : 'div';
        $mode = end( $this->current_row_mode );
        $output = '';

        switch($mode) {
            case 'normal':
                $output .= '</' . $tag . '>';
                $output .= '</div>';
                break;

            case 'full-width-background':
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</' . $tag . '>';
                break;

            case 'full-width':
                $output .= '</' . $tag . '>';
                $output .= '</div>';
                break;

            case 'sub-row':
                $output .= '</' . $tag . '>';
                break;
            default:
                $output .= '</' . $tag . '>';
                $output .= '</div>';
                break;
        }

        $this->output .= apply_filters('ddl_render_row_end', $output, $mode, $tag, $row);

        array_pop($this->current_row_mode);

	    return $this->output;
    }

    function cell_start_callback( $cssClass, $width, $cssId = '', $tag = 'div', $cell = null) {

		/**
		* Filter the output before the cell starts rendering.
		*
		* @param	string	Output before the cell starts rendering
		* @param	object	Cell object
		*
		* @return	Output before the cell starts rendering
		*
		* @since 1.9.0
		*/

		$this->output = apply_filters( 'ddl-cell_render_output_before_cell', $this->output, $cell );

        $this->output .= '<' . $tag . ' class="' . $this->get_class_name_for_width( $width, $cell );

        if ($cssClass) {
            $this->output .= ' ' . $cssClass;
        }

        $this->output .= apply_filters('ddl-get_cell_element_classes', '', $this, $cell);

        $this->output .= $this->set_cell_offset_class().'"';

        if( $cssId )
        {
            $this->output .= ' id="' . $cssId .'"';
        }

        $this->output .= apply_filters('ddl-additional_cells_tag_attributes_render', '', $this, $cell);

        $this->output .= '>';

        $this->output = apply_filters( 'ddl-cell_render_output_before_content', $this->output, $cell, $this );

        return $this->output;
    }

    function get_class_name_for_width ($width) {
        return 'span' . (string)$width;
    }

    function cell_end_callback($tag = 'div', $cell = null) {

        $this->output = apply_filters( 'ddl-cell_render_output_after_content', $this->output, $cell );

        $out = '</' . $tag . '>';

        $this->output .= $out;

		/**
		* Filter the output after the cell finishes rendering.
		*
		* @param	string	Output after the cell finishes rendering
		* @param	object	Cell object
		*
		* @return	Output after the cell finishes rendering
		*
		* @since 1.9.0
		*/

		$this->output = apply_filters( 'ddl-cell_render_output_after_cell', $this->output, $cell );

        $this->offset = 0; //reset offset after the cell is rendered

        return $out;
    }

    function cell_content_callback($content, $cell = null) {
        $cell_content =  apply_filters( 'ddl_render_cell_content', $content, $cell, $this );
        $this->output .= $cell_content;
        return $cell_content;
    }

    function theme_section_content_callback($content)
    {
        $this->output .=  $content;
    }

    function spacer_start_callback($width){
        $this->offset += $width; //keep track of the spaces and calculate offset for following content cell
    }

    function set_cell_offset_class( )
    {
        $offset_class = '';

        if( $this->offset > 0 )
        {
            switch( $this->layout->get_css_framework() )
            {
                case 'bootstrap':
                    $offset_class .= ' offset'.$this->offset;
                    break;
                case 'bootstrap3':
                    $offset_class .= ' '.$this->get_offset_prefix().$this->offset;
                    break;
                default:
                    $offset_class .= ' '.$this->get_offset_prefix().$this->offset;
                    break;
            }
        }
        return $offset_class;
    }

    function push_current_layout($layout) {
        array_push($this->current_layout, $layout);
    }

    function pop_current_layout() {
        array_pop($this->current_layout);
    }

    function get_row_count() {
        $last = end($this->current_layout);
        return $last->get_row_count();
    }

    function make_images_responsive ($content) {
        return $content;
    }

    function set_property( $property, $value )
    {
        if( is_numeric($property) )
        {
           throw new InvalidArgumentException('Property should be valid string and not a numeric index. Input was: '.$property);
        }
        $this->{$property} = $value;
    }

    function set_layout_arguments( $args ) {
        $this->layout_args = $args;
    }

    function get_layout_arguments( $property ) {
        if (isset($this->layout_args[$property])) {
            return $this->layout_args[$property];
        } else {
            return null;
        }
    }

    function is_layout_argument_set( $property )
    {
        return isset( $this->layout_args[$property] );
    }

    function render( )
    {
        return $this->render_to_html();
    }

    function set_context($context) {
        $this->context = $context;
    }

    function get_context() {
        return $this->context;
    }
}

// for rendering presets in the new layout dialog
class WPDD_layout_preset_render extends WPDD_layout_render {

    function __construct($layout){
        $layout->convert_sidebar_grid_for_preset();
        parent::__construct($layout);
    }

    function cell_start_callback($cssClass, $width, $cssId = '', $tag = 'div', $cell = null ) {

        return parent::cell_start_callback($cssClass . ' holder', $width, $cssId, $tag, $this);
    }

    function get_class_name_for_width ($width) {
        return 'span-preset' . (string)$width;
    }

    function row_start_callback( $row = null ) {

        if( !$row ) return;

        $layout_type = $row->get_layout_type();
        $cssId = $row->get_css_id();
        $additionalCssClasses = $row->get_additional_css_classes();
        $tag = $row->get_tag();
        $mode = $row->get_mode();

        $layout_type = $layout_type ? $layout_type : 'fixed';
        $cssId = $row->get_css_id() ? $cssId : '';
        $additionalCssClasses = $additionalCssClasses ? $additionalCssClasses : '';
        $tag = $tag ? $tag : 'div';
        $mode = $mode ? $mode : 'normal';

        $row_count = $this->get_row_count();
        $additionalCssClasses .= ' row-count-' . $row_count;
        $this->offset = 0; // reset offset at the beginning of the row

        $this->output .= '<' . $tag . ' class="row ' . $additionalCssClasses . '">';

	    return $this->output;

    }

    function row_end_callback($tag = 'div') {
        $this->output .= '</' . $tag . '>';
	    return $this->output;
    }
}

class WPDD_BootstrapTwo_render extends WPDD_layout_render
{

    function __construct($layout, $child_layout = null, $is_private_layout = false){

        parent::__construct($layout, $child_layout, $is_private_layout);
    }

    function get_class_name_for_width ($width) {
        return 'span' . (string)$width;
    }

    function set_cell_offset_class( )
    {
        $offset_class = '';

        if( $this->offset > 0 )
        {
            $offset_class .= ' offset'.$this->offset;
        }
        return $offset_class;
    }

    function row_start_callback( $row = null ) {
        if( !$row ) return;

        $layout_type = $row->get_layout_type();
        $cssId = $row->get_css_id();
        $additionalCssClasses = $row->get_additional_css_classes();
        $tag = $row->get_tag();
        $mode = $row->get_mode();

        $layout_type = $layout_type ? $layout_type : 'fixed';
        $cssId = $row->get_css_id() ? $cssId : '';
        $additionalCssClasses = $additionalCssClasses ? $additionalCssClasses : '';
        $tag = $tag ? $tag : 'div';
        $mode = $mode ? $mode : 'normal';

        $this->offset = 0; // reset offset at the beginning of the row

        // if this is not a top level row then we should force full width.
        if (sizeof($this->current_row_mode) > 0) {
            $mode = 'full-width';
        }

        array_push($this->current_row_mode, $mode);

        $type = '';
        switch ($layout_type) {
            case 'fixed':
            case '';
                if ($mode == 'full-width' && count($this->current_row_mode) == 1) {
                    $type = '-fluid';
                } else {
                    $type = '';
                }
                break;

            default:
                $type = '-'.$layout_type;
                break;
        }

        $type = apply_filters('ddl-get_fluid_type_class_suffix', $type, $mode );


        ob_start();

        switch($mode) {
            case 'normal':
                ?>
                <div class="<?php printf( '%s', $this->get_container_class($mode) );?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode).$type; if( $additionalCssClasses ) {echo ' '.$additionalCssClasses;} ?>"<?php if( $cssId ) { echo ' id="' . $cssId .'"'; }?>>
                <?php
                break;

            case 'full-width-background':
                ?>
                <<?php echo $tag; ?> class="<?php if( $additionalCssClasses ) {echo $additionalCssClasses;} ?>"<?php if( $cssId ) { echo ' id="' . $cssId .'"'; }?>>
                <div class="<?php printf( '%s', $this->get_container_class($mode) );?>">
                <div class="<?php echo $this->get_row_class($mode).$type; ?>">
                <?php
                break;

            case 'full-width':
                ?>
                <div class="<?php printf( '%s', $this->get_container_fluid_class($mode) );?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode).$type; if( $additionalCssClasses ) {echo ' '.$additionalCssClasses;} ?>"<?php if( $cssId ) { echo ' id="' . $cssId .'"'; }?>>
                <?php
                break;
            default:?>
                <div class="<?php printf( '%s', $this->get_container_class($mode) );?>">
                <<?php echo $tag; ?> class="<?php echo $this->get_row_class($mode).$type; if( $additionalCssClasses ) {echo ' '.$additionalCssClasses;} ?>"<?php if( $cssId ) { echo ' id="' . $cssId .'"'; }?>>
                <?php
                break;
        }

        $this->output .= ob_get_clean();

	    return $this->output;
    }


}


class WPDD_BootstrapThree_render extends WPDD_layout_render
{

    protected $column_prefix;

    function __construct($layout, $child_layout = null, $is_private_layout = false){
        parent::__construct($layout, $child_layout, $is_private_layout);
	    $this->column_prefix = $layout->get_column_prefix();
    }

    function row_start_callback( $row = null ) {
        return parent::row_start_callback($row);
    }

	function get_layout_column_prefix( $cell ){
        if( is_object( $cell ) && property_exists( $cell, 'column_prefix' ) ) {
            return $cell->get_column_prefix();
        } else {
            return $this->column_prefix;
        }
	}

    function get_class_name_for_width ($width, $cell = null ) {
        $ret = '';

        // Set column to sm. This will causes cells to be stacked on mobile devices
        // and then becomes horizontal on tablets and desktops.

        $this->column_prefix = $this->get_layout_column_prefix( $cell );

        if( is_array( $this->column_prefix ) ){

            foreach( $this->column_prefix as $column_prefix ){

                $w = apply_filters('ddl-get_column_width', $width, $column_prefix, $this );

                $ret .= $column_prefix.(string)$w.' ';
            }

        } else if( is_string( $this->column_prefix ) ){

            $w = apply_filters('ddl-get_column_width', $width, $this->column_prefix, $this );

            $ret = $this->column_prefix.(string)$w;

        }

        $ret .= $this->get_additional_column_class();

        return $ret;
    }

    function set_cell_offset_class( )
    {
        $offset_class = '';

        if( $this->offset > 0 )
        {
            if( is_array( $this->column_prefix ) ){

                foreach( $this->column_prefix as $column_prefix ){

                    $o = apply_filters('ddl-get_column_offset', $this->offset, $column_prefix, $this );

                    $offset_class .= sprintf(' %s%s%s ',  $column_prefix, $this->get_offset_prefix(), (string) $o);

                }

            } else if( is_string( $this->column_prefix ) ){

                $o = apply_filters('ddl-get_column_offset', $this->offset, $this->column_prefix, $this );

                $offset_class .= sprintf(' %s%s%s',  $this->column_prefix, $this->get_offset_prefix(), (string) $o);

            }

        }
        return $offset_class;
    }

    function make_images_responsive ($content) {

        $regex = '/<img[^>]*?/siU';
        if(preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $image) {
                $image = $image[0];
                $regex = '/<img[^>]*?class="([^"]*)"/siU';
                if(preg_match_all($regex, $image, $image_match, PREG_SET_ORDER)) {
                    foreach ($image_match as $val) {
                        // add img-responsive to the class.
                        $new_image = str_replace($val[1], $val[1] . ' '.$this->get_image_responsive_class(), $val[0]);
                        $content = str_replace($val[0], $new_image, $content);
                    }
                } else {
                    // no class attribute on img. we need to add one.
                    $new_image = str_replace('<img ', '<img class="'.$this->get_image_responsive_class().'" ', $image);
                    $content = str_replace($image, $new_image, $content);
                }
            }
        }

        return $content;
    }

}

class WPDD_layout_render_manager{

    private $layout = null;
    private $child_renderer = null;

    public function __construct($layout, $child_renderer = null, $is_private_layout = false)
    {
        $this->layout = $layout;
        $this->child_renderer = $child_renderer;
        $this->is_private_layout = $is_private_layout;
    }

    public function get_renderer( )
    {
        $framework = $this->layout->get_css_framework();

        if( $this->is_private_layout ){
	        $framework .= '-private';
        }

        $renderer = null;

        switch( $framework )
        {
            case 'bootstrap-2':
	        case 'bootstrap-2-private':
                $renderer = new WPDD_BootstrapTwo_render(  $this->layout, $this->child_renderer, $this->is_private_layout );
                break;
			case 'bootstrap-3':
			case 'bootstrap-3-private':
                $renderer = new WPDD_BootstrapThree_render(  $this->layout, $this->child_renderer, $this->is_private_layout );
                break;
			case 'bootstrap-4':
			case 'bootstrap-4-private':
                $renderer = new WPDD_BootstrapFour_render(  $this->layout, $this->child_renderer, $this->is_private_layout );
                break;
            default:
                $renderer = new WPDD_BootstrapThree_render(  $this->layout, $this->child_renderer, $this->is_private_layout );
        }

        return apply_filters('get_renderer',$renderer, $this);
    }
}
