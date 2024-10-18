<?php
$wpddl_features = apply_filters( 'ddl-get_global_features', null );

if( !$wpddl_features ){
    return;
}

function display_breadcrumb_tree ( $parent_id, $available_parents ) {

	if ($parent_id == 0) {
		if ( isset($available_parents[$parent_id]) && sizeof($available_parents[$parent_id]) > 0 ) {

			// Add No Parent to the start.

			?>
			<li class="js-tree-category-item">
				<p class="item-name-wrap js-item-name-wrap">
					<a href="#">
						<span class="js-item-name"
							  data-layout-slug=""
							  data-layout-id=""><?php _e('(no parent)', 'ddl-layouts'); ?></span>
					</a>
				</p>
			<?php
			foreach ( $available_parents[$parent_id] as $parent) {
				display_breadcrumb_tree ( $parent, $available_parents );
			}
		}
	} else {

		$layout = WPDD_Layouts::get_layout_settings($parent_id, true);

		?>
			<li class="js-tree-category-item <?php if ( isset($available_parents[$parent_id]) ) : ?>js-tree-category<?php endif; ?>">
				<p class="item-name-wrap js-item-name-wrap">
					<?php if ( isset($available_parents[$parent_id]) ) : ?>
					<i class="js-tree-toggle fa fa-minus-square-o icon-collapse-alt" data-expanded="true" data-text-expanded="Collapse" data-text-collapsed="Expand" title="Collapse"></i>
					<?php endif; ?>
					<a href="#">
						<span class="js-item-name"
							  data-layout-slug="<?php echo $layout->slug; ?>"
							  data-layout-id="<?php echo $layout->id; ?>"><?php echo esc_html($layout->name); ?></span>
					</a>
				</p>

				<?php if ( isset($available_parents[$parent_id]) && sizeof($available_parents[$parent_id]) > 0 ): ?>
					<ul class="js-tree-category-items">
						<?php
							foreach ( $available_parents[$parent_id] as $parent) {
								display_breadcrumb_tree ( $parent, $available_parents );
							}
						?>
					</ul> <!-- .js-tree-category-items -->
				<?php endif; ?>
			</li> <!-- .js-tree-category-item -->

		<?php
	}
}

function get_all_available_parent_layouts(){
    $parent_ID = apply_filters('ddl-get-default-'.WPDDL_Options::PARENTS_OPTIONS, 0, WPDDL_Options::PARENTS_OPTIONS );
    $parents = WPDD_Layouts::get_available_parents();
    ?>
    <option value=""><?php _e("None", 'ddl-layouts'); ?></option>
    <?php
    for ( $i=0,$total_parents=count($parents); $i<$total_parents; $i++){
        $selected = '';
        if ( $parents[$i]->ID == $parent_ID ){
            $selected = ' selected';
        }
        echo '<option value="'.$parents[$i]->ID.'"'.$selected.'>'.$parents[$i]->post_title.'</option>';
    }
}
$wpddl_settings = WPDDL_Settings::getInstance();
$wpddl_settings->init();
?>

<div class="ddl-dialogs-container">

	<div class="ddl-dialog" id="ddl-layout-settings-dialog">

		<div class="ddl-dialog-header">
			<h2 class="js-dialog-edit-title"><?php _e('Set parent layout', 'ddl-layouts'); ?></h2>
			<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
		</div>

		<div class="ddl-dialog-content">

			<ul class="ddl-form">
				<li>

					<?php $name = 'ddl-layout-settings-layout-type';
					require WPDDL_GUI_ABSPATH . 'templates/layout-layout-type-selector.box.tpl.php';
					?>
					<?php if ($wpddl_features->is_feature('fixed-layout')): ?>
					<div class="fields-group">
						<p class="toolset-alert toolset-alert-info js-diabled-fluid-rows-info">
							<?php _e("This layout can't be changed to fluid because it contains a grid with fixed-width columns.", 'ddl-layouts'); ?>
						</p>
					</div>
					<?php endif; ?>
				</li>
				<li style="display:none">
					<label for="ddl-layout-width"><?php _e('Layout width','ddl-layouts'); ?></label>
					<div class="fields-group">
						<select name="ddl-layout-width" id="ddl-layout-width">
							<?php for ($i = 1; $i <= 12; $i++): ?>
								<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
							<?php endfor; ?>
						</select>
						<?php // FIXME: This message should be shown using .wpvToolsetMessage() ?>
						<p class="toolset-alert toolset-alert-info js-diabled-width">
							<?php _e("The layout width is always 12 columns for fluid layouts.", 'ddl-layouts'); ?>
						</p>
					</div>
				</li>

				<?php
				$current = isset( $_GET['layout_id'] ) ? (int) $_GET['layout_id'] : null;
				$available_parents = apply_filters( 'ddl-get_available_parent_layouts', $current );?>

				<?php if (sizeof($available_parents)): ?>

					<li>
						<fieldset>
							<legend for="breadcrumbs-tree-search"><?php _e('Parent layout', 'ddl-layouts'); ?></legend>
							<div class="fields-group">

								<p>
									<input class="js-breadcrumbs-tree-search" type="text" id="breadcrumbs-tree-search"
										data-default-val="<?php _e('Search', 'ddl-layouts'); ?>&hellip;"
										data-message-container=".js-breadcrumbs-message-container"
										data-target="#js-layouts-tree"
										value="<?php _e('Search', 'ddl-layouts'); ?>&hellip;
									">
								</p>

								<ul class="tree" id="js-layouts-tree">
									<li class="js-tree-category">
										<h3 class="tree-category-title js-tree-category-title">
											<?php _e('Layouts:', 'ddl-layouts'); ?>
										</h3>
										<ul class="js-tree-category-items">
											<?php
												$available_parents = @array_diff($available_parents, array($_GET['layout_id']));
												display_breadcrumb_tree(0, $available_parents)
											?>
										</ul>
									</li>
								</ul>

								<div class="js-breadcrumbs-message-container" data-message-text="<?php _e( 'Nothing found', 'ddl-layouts' ); ?>"></div>

							</div>
						</fieldset>
					</li>
				<?php else: ?>
					<li>
						<fieldset>
							<legend for="breadcrumbs-tree-search"><?php _e('Parent layout', 'ddl-layouts'); ?></legend>
							<div class="fields-group">
								<p>
									<?php echo sprintf(__('There are no parent layouts available for current layout. Click %shere%s to learn more about hierarchical layouts.', 'ddl-layouts'),
													   '<a href="' . WPDLL_CHILD_LAYOUT_CELL . '" target="_blank">',
													   '</a>'); ?>
								</p>
							</div>
						</fieldset>
					</li>
				<?php endif; ?>
                                        <li>
                                            <fieldset>
                                                <legend for="breadcrumbs-tree-search"><?php _e('Default parent layout', 'ddl-layouts'); ?></legend>
                                                <div class="fields-group">
                                                    <label for="set_as_default_layout">
                                                        <input type="checkbox" name="set_as_default_layout" class="js-ddl-set_as_default_layout" value="1" id="set_as_default_layout">
                                                        <?php _e('Set selected layout as default parent layout	', 'ddl-layouts'); ?>
                                                    </label>
                                                </div>
                                            </fieldset>
                                        </li>
			</ul>

		</div>

		<div class="ddl-dialog-footer">
			<?php wp_nonce_field('wp_nonce_layout_settings_edit', 'wp_nonce_layout_settings_edit'); ?>
			<button class="button js-edit-dialog-close"><?php _e('Cancel','ddl-layouts') ?></button>
			<button class="button button-primary js-row-dialog-edit-save js-save-dialog-settings"><?php _e('Save','ddl-layouts') ?></button>
		</div>

	</div>

</div>