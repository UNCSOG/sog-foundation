<?php

// @todo comment
abstract class WPDDL_Cell_Abstract_Cell_Factory  extends WPDD_layout_cell_factory {

	protected $cell_class;

	protected $name = '';
	protected $description = '';
	protected $btn_text = '';
	protected $dialog_title_create;
	protected $dialog_title_edit;
	protected $allow_multiple = true;
	protected $category;
	protected $has_settings = true;

	protected $cell_image_url;
	protected $preview_image_url;



	public function build( $name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '' ) {
		return new $this->cell_class( $unique_id, $name, $width, $css_class_name, '', $content, $css_id, $tag, $unique_id );

	}

	public function get_cell_info( $template ) {
		$this->setDialogTitleCreate();
		$this->setDialogTitleEdit();
		$this->setCategory();
		$this->setCellImageUrl();

		$template['cell-image-url']      = $this->cell_image_url;
		$template['preview-image-url']   = $this->preview_image_url;
		$template['name']                = $this->name;
		$template['description']         = $this->description;
		$template['button-text']         = $this->btn_text;
		$template['dialog-title-create'] = $this->dialog_title_create;
		$template['dialog-title-edit']   = $this->dialog_title_edit;
		$template['dialog-template']     = $this->_dialog_template();
		$template['allow-multiple']      = $this->allow_multiple;
		$template['category']            = $this->category;
		$template['has_settings']        = $this->has_settings;

		return $template;
	}

	public function get_editor_cell_template() {
		$this->setCategory();

		ob_start();
		?>
			<div class="cell-content">
			<p class="cell-name from-bot-10"><?php echo $this->category . ' - ' . $this->name; ?></p>
				<div class="cell-preview">
	                <div class="ddl-genesis-widget-header-right-preview">

					</div>
				</div>
			</div>
			</div>
		<?php
		return ob_get_clean();
	}

	protected function _dialog_template() {
		/* Cell Dialog Output */
	}

	public function enqueue_editor_scripts() {
		//wp_register_script( 'wp-genesis-widget-header-right-editor', ( WPDDL_GUI_RELPATH . "editor/js/child-cell.js" ), array('jquery'), null, true );
		//wp_enqueue_script( 'wp-genesis-widget-header-right-editor' );
	}

	protected function setCategory() {
		if( $this->category === null ) {
			$this->category = defined( 'LAYOUTS_INTEGRATION_THEME_NAME' )
				? LAYOUTS_INTEGRATION_THEME_NAME
				: 'Theme Integration';
		}
	}

	private function setDialogTitleEdit() {
		if( $this->dialog_title_edit === null ) {
			$this->dialog_title_edit = 'Edit ' . $this->name;
		}
	}

	private function setDialogTitleCreate() {
		if( $this->dialog_title_create === null ) {
			$this->dialog_title_create = 'Place ' . $this->name;
		}
	}

	protected function setCellImageUrl() {
		if( $this->cell_image_url === null ) {
			$this->cell_image_url = DDL_ICONS_SVG_REL_PATH . 'generic-one-cell.svg';
			}
	}

	/*
	 * Content can be a cell settings field or a a string.
	 * The function checks if a field with the name $content is available, if not it handle it as a custom string.
	 * @param $content
	 * @param bool|true $output
	 */
	protected function sanitizeContentForJS( $content, $output = true ) {

		// remove links (the check if the field exists is via js, so we simply run this also for field names)
		$content = preg_replace( '#(<a[^>]*>)([^<]*)(</a>)#', '$2', $content );

		/*
		 * JS rendering
		 * Try to use $content as field name, if that doesn't work handle it as custom content
		 * In the worst case, using a variable name which doesn't exists, you get the variable name as output
		 */
		ob_start();
		?>

		<?php
			// if whitespaces in $content we skip the "check for variable"
			if( strpos( $content, ' ' ) !== false ) {
		?>
                print( DDL_Helper.sanitizeHelper.sanitizeOutput( '<?php echo $content; ?>' ) );
		<?php
			} else {
		?>
				try {
                    
                    var field = DL_Helper.sanitizeHelper.get_field_sanitised_value( '<?php echo $content; ?>' );
                    print( DDL_Helper.sanitizeHelper.sanitizeOutput( field ) );
				} catch( error ) {
                    print( DDL_Helper.sanitizeHelper.sanitizeOutput( '<?php echo $content; ?>' ) );
				}
		<?php
			}

		$script = ob_get_clean();

		if( $output )
			echo '<# ' . $script . '#>';
		else
			return $script;
	}
}