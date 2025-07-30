<?php



if( ddl_has_feature('cell-widget-area') === false ){
	return;
}

class WPDD_layout_cell_widget_area extends WPDD_layout_cell {

	function __construct($id, $name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		parent::__construct($unique_id, $name, $width, $css_class_name, 'cell-widget-area-template', $content, $css_id, $tag, $unique_id);

		$this->set_cell_type('cell-widget-area');
	}

	function frontend_render_cell_content($target) {
		ob_start();

		$widget_area = $this->get('widget_area');
		if ($widget_area != '') {
			dynamic_sidebar($widget_area);
		}

		$content = ob_get_clean();

		return $target->cell_content_callback($content, $this);
	}

}

class WPDD_layout_cell_widget_area_factory extends WPDD_layout_cell_factory{

	public function build($name, $width, $css_class_name = '', $content = null, $css_id = '', $tag = 'div', $unique_id = '') {
		return new WPDD_layout_cell_widget_area($unique_id, $name, $width, $css_class_name, $content, $css_id, $tag, $unique_id);
	}

	public function get_cell_info($template) {
		$template['cell-image-url'] = DDL_ICONS_SVG_REL_PATH.'widget-area.svg';
		$template['preview-image-url'] = DDL_ICONS_PNG_REL_PATH . 'widgets-area_expand-image.png';
		$template['name'] = __('Widget Area', 'ddl-layouts');
		$template['description'] = __('Display a WordPress Widget Area. You will be able to select Widget Area that comes from the theme.', 'ddl-layouts');
		$template['button-text'] = __('Assign Widget Area cell', 'ddl-layouts');
		$template['dialog-title-create'] = __('Create new Widget Area cell', 'ddl-layouts');
		$template['dialog-title-edit'] = __('Edit Widget Area cell', 'ddl-layouts');
		$template['dialog-template'] = $this->_dialog_template();
		$template['category'] = __('Site elements', 'ddl-layouts');
		$template['has_settings'] = true;
		$template['register-scripts'] = array(
			array('ddl-menu-cell-script', WPDDL_GUI_RELPATH . 'editor/js/widget-area.js', array('jquery'), WPDDL_VERSION, true),
		);
		return $template;
	}

	public function get_editor_cell_template() {
		ob_start();
		?>
		<div class="cell-content">
			<div class="cell-preview">
				<div class="ddl-widget-area-preview">
					<p><strong>
							<# if( content.widget_area ){ #>
								{{ content.widget_area }}
								<# } #>
						</strong></p>
					<img src="<?php echo DDL_ICONS_SVG_REL_PATH . 'widgets-area.svg'; ?>" height="130px">
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function enqueue_editor_scripts() {
		wp_register_script( 'wp-widget-area-editor', ( WPDDL_GUI_RELPATH . "editor/js/widget-area.js" ), array('jquery'), null, true );
		wp_enqueue_script( 'wp-widget-area-editor' );
	}

	private function _dialog_template() {

		global $wp_registered_sidebars;

		ob_start();
		?>

		<div class="ddl-form existing-sidebars-div">
			<p class="js-widget-area-seelct">
				<label for="<?php echo $this->element_name('widget_area'); ?>"><?php _e('Select an existing widget area', 'ddl-layouts'); ?></label>
				<select data-type="select" class="js-widget-area-select-el" name="<?php echo $this->element_name('widget_area'); ?>">
					<?php foreach($wp_registered_sidebars as $sidebar): ?>
						<option val="<?php echo $sidebar['id']; ?>"><?php echo $sidebar['name']; ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php // Don't show the create new widget area button in this version
			// We'll complete this later ?>
			<p class="js-create-new-widget-area-button ddl-form">
				<label><?php _e('Or', 'ddl-layouts'); ?></label>
				<button class="js-create-new-sidebar button-secondary"><?php _e('Create a new widget area', 'ddl-layouts'); ?></button> <a href="<?php echo admin_url("post.php?post={##}&action=edit"); ?>" class="button-secondary js-edit-existing-area" target="_blank"><?php _e('Edit/Delete selected area', 'ddl-layouts'); ?></a>
			</p>
		</div>

		<div class="create-new-sidebar-div js-create-new-sidebar-div ddl-form hidden">
			<div class="js-create-new-sidebar-message"></div>
			<ul>
				<li>
					<label for="ddl-sidebar-name"><?php _e('New Widget Area name:', 'ddl-layouts'); ?></label>
					<input type='text' name="ddl-sidebar-name">
				</li>
				<!--<li>
				<label for="ddl-sidebar-description"><?php _e('Description:', 'ddl-layouts'); ?></label>
				<input type='text' name="ddl-sidebar-description">
			</li>
			<li>
				<label for="ddl-sidebar-class"><?php _e('Class:', 'ddl-layouts'); ?></label>
				<input type='text' name="ddl-sidebar-class">
			</li>
			<li>
				<label for="ddl-sidebar-before-widget"><?php _e('Before widget:', 'ddl-layouts'); ?></label>
				<input type='text' name="ddl-sidebar-before-widget" value="">
			</li>
			<li>
				<label for="ddl-sidebar-after-widget"><?php _e('After widget:', 'ddl-layouts'); ?></label>
				<input type='text' name="ddl-sidebar-after-widget" value="">
			</li>
			<li>
				<label for="ddl-sidebar-before-title"><?php _e('Before title:', 'ddl-layouts'); ?></label>
				<input type='text' name="ddl-sidebar-before-title" value="">
			</li>
			<li>
				<label for="ddl-sidebar-after-title"><?php _e('After title:', 'ddl-layouts'); ?></label>
				<input type='text' name="ddl-sidebar-after-title" value="">
			</li>-->
			</ul>
			<?php
			//TODO: Find a nice way to show this
			/*<a href="http://codex.wordpress.org/Function_Reference/register_sidebar" target="_blank"><?php _e('Information about these settings', 'ddl-layouts'); ?></a>*/
			?>
		</div>

		<div class="ddl-dialog-footer no-style-dialog-footer save-new-sidebar-buttons js-widget-area-footer hidden">
			<input type="button" style="text-transform: none !important;" class="button-secondary js-cancel-create-new-sidebar" value="<?php _e('Cancel', 'ddl-layouts'); ?>">
			<input type="button" style="text-transform: none !important;" class="button-primary js-create-the-new-sidebar" value="<?php _e('Create the new widget area', 'ddl-layouts'); ?>">
		</div>

		<?php
		//TODO: Check constant undefined error caused by this part
		ddl_add_help_link_to_dialog(WPDLL_WIDGET_AREA_CELL,
			__('Learn about the Widget Area cell', 'ddl-layouts'));
		?>

		<?php

		return ob_get_clean();
	}
}

/**
 * Class WPDD_Widget_Area_Helper
 */

class WPDD_Widget_Area_Helper{
	public static $instance;

	private function __construct(){
		add_action( 'wp_ajax_register_widget_area', array(&$this, "register_widget_area_ajax") );
		$this->register_widget_area_post_type();
		$this->load_registered_sidebars();
	}

	private function register_widget_area_post_type(){

		$labels = array(
			'name'               => _x( 'Widget Areas', 'post type general name', 'ddl-layouts' ),
			'singular_name'      => _x( 'Widget Area', 'post type singular name', 'ddl-layouts' ),
			'menu_name'          => _x( 'Widget Areas', 'admin menu', 'ddl-layouts' ),
			'name_admin_bar'     => _x( 'Widget Area', 'add new on admin bar', 'ddl-layouts' ),
			'add_new'            => _x( 'Add New', 'book', 'ddl-layouts' ),
			'add_new_item'       => __( 'Add New Widget Areas', 'ddl-layouts' ),
			'new_item'           => __( 'New Widget Area', 'ddl-layouts' ),
			'edit_item'          => __( 'Edit Widget Area', 'ddl-layouts' ),
			'view_item'          => __( 'View Widget Area', 'ddl-layouts' ),
			'all_items'          => __( 'All Widget Areas', 'ddl-layouts' ),
			'search_items'       => __( 'Search Widget Areas', 'ddl-layouts' ),
			'parent_item_colon'  => __( 'Parent Widget Areas:', 'ddl-layouts' ),
			'not_found'          => __( 'No widget areas found.', 'ddl-layouts' ),
			'not_found_in_trash' => __( 'No widget areas found in Trash.', 'ddl-layouts' )
		);

		$args = array(
			'labels' => $labels,
			'description' => __( 'Custom sidebars used by Widget Area Cell ', 'ddl-layouts' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => array( 'slug' => 'widget-area' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title')
		);

		register_post_type( 'widget-area', $args );
	}


	private function load_registered_sidebars(){
		global $wp_registered_sidebars;
		$args = array(
			'posts_per_page'   => 100,
			'offset'           => 0,
			'orderby'          => 'id',
			'order'            => 'DESC',
			'post_type'        => 'widget-area',
			'post_status'      => 'publish',
			'suppress_filters' => true
		);

		$registered_sidebars = get_posts( $args );

		//Get theme registered sidebars and use their style for the custom widget areas
		$before_widget = null;
		$after_widget = null;
		$before_title = null;
		$after_title = null;
		$sidebars_keys = array_keys($wp_registered_sidebars);

		if( isset($sidebars_keys[0]) && isset($wp_registered_sidebars[$sidebars_keys[0]]) ){
			$before_widget = $wp_registered_sidebars[$sidebars_keys[0]]["before_widget"];
			$after_widget = $wp_registered_sidebars[$sidebars_keys[0]]["after_widget"];
			$before_title = $wp_registered_sidebars[$sidebars_keys[0]]["before_title"];
			$after_title = $wp_registered_sidebars[$sidebars_keys[0]]["after_title"];
		}

		foreach($registered_sidebars as $sidebar){
			register_sidebar(array(
				"name" => $sidebar->post_title,
				"id"   => "sidebar-".$sidebar->ID,
				"before_widget" => $before_widget,
				"after_widget" => $after_widget,
				"before_title" => $before_title,
				"after_title" => $after_title
			));
		}

	}

	public function register_widget_area_ajax(){
		if(isset($_POST["sidebar_name"]) && trim($_POST["sidebar_name"]) != ""){
			if(isset($_POST["sidebar_id"]))
				$sidebar_id = wp_strip_all_tags($_POST["sidebar_id"]);
			else $sidebar_id = null;

			$insert_new_sidebar = wp_insert_post(array(
				"post_title" => wp_strip_all_tags($_POST["sidebar_name"]),
				"post_type"  => "widget-area",
				"ID"         => $sidebar_id,
				'post_status'   => 'publish'
			), true);

			if($insert_new_sidebar && !is_wp_error($insert_new_sidebar) ){
				$send = wp_json_encode(array("Data" => array("message" => __("The sidebar has been ".($sidebar_id == null ? "created" : "updated") . " successfully!"),
				                                             "data" => array(
					                                             "new_sidebar_id" => $insert_new_sidebar,
					                                             "new_sidebar_name" => wp_strip_all_tags($_POST["sidebar_name"])
				                                             ))));
			}else{
				$send = wp_json_encode(array("error" => array("message" => __("Error occurred while ".($sidebar_id == null ? "creating" : "updating") . " the sidebar!"))));
			}
		}else {
			$send = wp_json_encode(array("error" => array("message" => __("Sidebar name isn't set or empty!"))));
		}

		die($send);
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new WPDD_Widget_Area_Helper();
		}
		return self::$instance;
	}

}

add_filter('dd_layouts_register_cell_factory', 'dd_layouts_register_cell_widget_area_factory');
function dd_layouts_register_cell_widget_area_factory($factories) {
	$factories['cell-widget-area'] = new WPDD_layout_cell_widget_area_factory;
	return $factories;
}

add_action('init', array("WPDD_Widget_Area_Helper", "getInstance"));
