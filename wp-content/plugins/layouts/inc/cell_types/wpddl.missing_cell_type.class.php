<?php



class WPDD_layout_missing_cell_type_factory extends WPDD_layout_cell_factory{

	public function build($name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
	}

	public function get_cell_info($template) {
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start();
		?>
			<# if( ddl_missing_cell_type == 'undefined'){

			} else { #>
			<div class="cell-content readonly toolset-alert toolset-alert-error missing-cell-alert">
				<p class="cell-name">

					<#
						var message = "<?php _e('The %s cell type is no longer available.<br> (Make sure that the cell type is available and that you have the required user permissions to handle that cell type.', 'ddl-layouts'); ?>";
						message = message.replace('%s', ddl_missing_cell_type);
						print( message );
					#>
				</p>
			</div>
				<#	} #>
		<?php
		return ob_get_clean();
	}

}

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_missing_cell_type_factory');
function dd_layouts_register_missing_cell_type_factory($factories) {
	$factories['ddl_missing_cell_type'] = new WPDD_layout_missing_cell_type_factory;
	return $factories;
}
