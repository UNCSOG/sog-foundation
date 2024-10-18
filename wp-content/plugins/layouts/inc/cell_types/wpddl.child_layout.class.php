<?php



if( ddl_has_feature('child-layout') === false ){
	return;
}
class WPDD_layout_cell_child_layout extends WPDD_layout_cell {

	function __construct($id, $name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		parent::__construct($id, $name, $width, $css_class_name, 'child-layout', $content, $css_id, $tag, $unique_id);

		$this->set_cell_type('child-layout');
	}

	function frontend_render_cell_content($target) {

		global $wpddlayout;

		if ($target->has_child_renderer()) {
			$target->cell_content_callback($target->render_child(), $this);
		} else {
			$layout_id = $wpddlayout->get_rendered_layout_id();
			$layout = WPDD_Layouts::get_layout_from_id ($layout_id);
			$children = $layout->get_children();

			ob_start();

			?>
			<div class="toolset-alert toolset-alert-error">
				<p><strong><?php _e('A child layout should display here', 'ddl-layouts'); ?></strong></p>
				<?php if (count($children)): ?>
					<p><?php echo sprintf(__('Instead of using the parent layout (%s), you should assign one of these child layouts to content:', 'ddl-layouts'), $layout->get_name()); ?></p>
					<ul>
						<?php
						foreach ($children as $child_id) {
							$child_layout = WPDD_Layouts::get_layout_from_id ($child_id);
							if( null === $child_layout ){
								continue;
							} else { ?>
								<li><?php echo $child_layout->get_name(); ?></li>
							<?php  }

						}
						?>
					</ul>
					<p><?php _e('Or, you can create new child layouts for other pages.', 'ddl-layouts'); ?></p>
				<?php else: ?>
					<p><?php echo sprintf(__('You should create child layouts that fit into this space and then assign them to content. The parent layout (%s) should not be assigned to content, but only its children layouts.', 'ddl-layouts'), $layout->get_name()); ?></p>
					<p><?php _e('If you did not intend to create multiple child layouts, you can simply delete the Child Layout cell. A Grid cell can be used to split a cell into several rows and columns.', 'ddl-layouts'); ?></p>
				<?php endif; ?>

				<p><?php ddl_add_help_link_to_dialog(WPDLL_CHILD_LAYOUT_CELL, __('Learn about designing hierarchical layouts using parents and children layouts.', 'ddl-layouts')); ?></p>
			</div>
			<?php

			return $target->cell_content_callback(ob_get_clean(), $this);
		}
	}

	function get_width_of_child_layout_cell() {
		return $this->get_width();
	}

}

class WPDD_layout_cell_child_layout_factory extends WPDD_layout_cell_factory{

	private $helper = null;
	private $dialog_handler = null;
	private $children;

	public function __construct(){

		$layout_id = null;

		if( ( is_admin() && isset($_GET['page']) && $_GET['page'] === 'dd_layouts_edit' ) ||
			isset($_POST['action']) && $_POST['action'] === 'view_layout_from_editor' ){

			if( isset( $_GET['layout_id'] ) ){

				$layout_id = $_GET['layout_id'];

			} elseif( isset( $_POST['layout_id'] ) ){

				$layout_id = $_POST['layout_id'];

			}

			$this->helper = new WPDDL_ParentLayoutHelper( $layout_id );
			$this->children = $this->helper->get_children();
		}

		$this->dialog_handler = new WPDD_Child_Layout_Cell( $this->helper );
	}

	public function build($name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		return new WPDD_layout_cell_child_layout($unique_id, $name, $width, $css_class_name, $content, $css_id, $tag, $unique_id);
	}

	public function get_cell_info($template) {
		$template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'child-layout-cell.svg';
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'child-layout_expand-image2.png';
		$template['name'] = __('Child Layout (hierarchical layouts tree)', 'ddl-layouts');
		$template['description'] = __('Insert a placeholder for a Child Layout. Use this cell to design hierarchical layouts, where different child layouts inherit page-elements from a parent layout.', 'ddl-layouts');
		$template['button-text'] = __('Assign Child Layout cell', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create new Child Layout cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit Child Layout cell', 'ddl-layouts');
		$template['dialog-template'] = $this->_dialog_template();
		$template['allow-multiple'] = false;
		$template['category'] = __('Layout structure', 'ddl-layouts');
		$template['has_settings'] = false;
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start();
		$children_count = $this->helper->get_children_count();
		$text = '';
		if( $children_count === 1 ){
			$text = sprintf( __('This layout has currently %s child, edit this cell to access and edit it.', 'ddl-layouts'),  $children_count );
		} elseif( $children_count > 1 ){
			$text = sprintf( __('This layout has currently %s children, edit this cell to access and edit them.', 'ddl-layouts'),  $children_count );
		}
		?>
		<div class="cell-content">
			<p class="cell-name from-bot-3"><?php _e('Child Layout', 'ddl-layouts'); ?></p>
			<p class="cell-name cell-extra-info"><?php echo $text; ?></p>
			<div class="cell-preview">
				<div class="ddl-child-layout-preview">
					<img src="<?php echo DDL_ICONS_SVG_REL_PATH . 'child-layout-preview.svg'; ?>" height="130px">
				</div>
			</div>
		</div>
		</div>
		<?php
		return ob_get_clean();
	}


	private function _dialog_template() {
		return $this->dialog_handler->cell_dialogs_callback();
	}

	public function enqueue_editor_scripts() {
		return $this->dialog_handler->cell_edit_script();
	}


}

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_cell_child_layout_factory');
function dd_layouts_register_cell_child_layout_factory($factories) {
	$factories['child-layout'] = new WPDD_layout_cell_child_layout_factory;
	return $factories;
}

class WPDD_Child_Layout_Cell extends Layouts_toolset_based_cell{
	protected $cell_type = 'child-layout';
	protected $helper = null;

	public function __construct( $helper = null) {
		$this->helper = $helper;
	}

	public function back_end_dialog() {
		global $wpddlayout;
		ob_start();
		?>

		<?php if (isset($_GET['layout_id'])) {

			$layout = $this->helper;

			if ( $layout->is_parent() ) {

				$layouts_children = $this->helper->get_children();

				?>

				<ul class="tree js-child-layout-list">
					<li class="js-tree-category js-tree-category-title">
						<h3 class="tree-category-title">
							<?php _e( 'Select Child layout for editing', 'ddl-layouts' ); ?>
						</h3>

						<ul class="js-tree-category-items">

							<?php

							foreach ( $layouts_children as $child ) {
								?>

								<li class="js-tree-category-item">
									<p class="item-name-wrap js-item-name-wrap">
										<a href="#" class="js-switch-to-layout" data-layout-id="<?php echo $child->id; ?>">
											<span class="js-item-name js-child-layout-item"><?php echo $child->name; ?></span>
										</a>
									</p>
								</li> <!-- .js-tree-category-item -->

								<?php
							}
							?>

							<?php // ( while ( has_child_ddl_layouts() ) : the_child_layout(); ) ?>
						</ul> <!-- . js-tree-category-items -->
					</li>

				</ul> <!-- .js-tree-category-items -->
				<?php
			}
		}?>
		<div class="ddl-box">
			<?php if( $wpddlayout->is_embedded() === false ) : ?>
				<span class="ddl-dialog-button-wrap alignright">
					<input type="button" class="button js-create-new-child-layout" data-url="<?php echo admin_url().'admin.php?page=dd_layouts&new_layout=true'; ?>" value="<?php _e('Create a new child layout', 'ddl-layouts'); ?>">
					</span>
			<?php endif; ?>

			<span class="ddl-learn-more alignleft">
					<?php ddl_add_help_link_to_dialog(WPDLL_CHILD_LAYOUT_CELL,
						__('Learn about the Child layout cell', 'ddl-layouts'), true);
					?></span>

		</div>
		<?php
		return ob_get_clean();
	}
	public function cell_edit_script() {
		wp_register_script( 'wp-child-layout-editor', ( WPDDL_GUI_RELPATH . "editor/js/child-cell.js" ), array('jquery'), null, true );
		wp_enqueue_script( 'wp-child-layout-editor' );
	}
}