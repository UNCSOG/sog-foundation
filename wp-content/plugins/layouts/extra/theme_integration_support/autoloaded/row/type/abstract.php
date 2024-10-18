<?php

abstract class WPDDL_Row_Type_Abstract {

    protected $unique_id = '';
	protected $id;
	protected $name;
	protected $desc;
	protected $image;
	private $css_classes = array();
	private $data_attr = array();
	protected $css_id;
	protected $same_height_columns = false;
	protected $html_id;

	protected function setup() {
		add_filter( 'ddl-get_rows_modes_gui_list',
			array( &$this, 'addRowMode' ) );

		add_filter( 'ddl_render_row_start',
			array( &$this, 'htmlOpen'), 99, 4 );

		add_filter( 'ddl_render_row_end',
			array( &$this, 'htmlClose'), 99, 3 );

	    }

	public function htmlOpen( $markup, $args, $row = null, $renderer = null ) {}

	public function htmlClose( $output, $mode, $tag ) {}

	public function addRowMode( $lists_html ){
		ob_start();?>
		<li>
			<figure class="row-type">
				<img class="item-preview" data-name="row_<?php echo $this->id; ?>" src="<?php echo $this->image; ?>" alt="<?php echo $this->name; ?>">
				<span><?php echo $this->desc; ?></span>
			</figure>
			<label class="radio" data-target="row_<?php echo $this->id; ?>" for="row_<?php echo $this->id; ?>" style="display:none">
				<input type="radio" name="row_type" id="row_<?php echo $this->id; ?>" value="<?php echo $this->id; ?>">
				<?php echo $this->name; ?>
			</label>
		</li>
		<?php
		$lists_html .= ob_get_clean();

		return $lists_html;
	}

	protected function getCssClasses() {
		return $this->css_classes;
	}

	protected function addCssClass( $css_class ) {
		$this->css_classes[] = $css_class;

		return $this;
	}

	protected function addDataAttributes( $key, $data ) {
		$this->data_attr[$key] = $data;
		return $this;
	}

    protected function getDataAttributes(){
        return $this->data_attr;
    }

    protected function renderDataAttributes( $row = null, $renderer = null ){
        $data_string = '';

        foreach( $this->getDataAttributes() as $data => $value ){
            $data_string .= sprintf(' data-%s="%s" ', $data, $value);
        }

        return apply_filters('ddl-get_row_additional_attributes', $data_string, $row, $renderer);
    }

	protected function getCssid() {
		return $this->css_id;
	}

	protected function setCssId( $css_id ) {
		$this->css_id = $css_id;

		return $this;
	}

	protected function enableSameHeightColumns() {
		$this->same_height_columns = true;
	}

	protected function setHtmlId( $id ) {
		$this->html_id = $id;
	}
	public function get_name(){
        return $this->name;
    }
}
