<div class="ddl-dialogs-container">


	<div class="ddl-dialog wp-core-ui" id="ddl-default-edit">

        <div class="ddl-dialog-header">
            <h2 class="js-dialog-title"><?php _e('Edit Cell', 'ddl-layouts'); ?></h2>
            <i class="fa fa-remove icon-remove js-edit-dialog-close js-remove-video"></i>
        </div>

        <div class="ddl-dialog-content js-ddl-dialog-content">

            <div class="js-video-toolbar-container ddl-video-toolbar"></div>
            <div class="js-video-container ddl-video-container"></div>

            <?php $unique_id = uniqid(); ?>
            <div class="js-popup-tabs">

                <div class="ddl-dialog-content-main ddl-popup-tab" id="js-cell-content-<?php echo $unique_id; ?>">

                    <?php //require_once( WPDDL_GUI_ABSPATH . 'dialogs/js/templates/info-box.php') ?>
                    <div id="js-info-box-container"></div>
                    <input type="text" name="ddl-default-edit-cell-name" id="ddl-default-edit-cell-name" class="hidden">
                    <!--<div class="ddl-form">
						<p>
							<label for="ddl-default-edit-cell-name" class="js-change-name" data-row="<?php _e('Grid name:', 'ddl-layouts'); ?>" data-cell="<?php _e('Cell name:', 'ddl-layouts'); ?>"><?php _e('Cell name:', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>)</span></label>
							<input type="text" name="ddl-default-edit-cell-name" id="ddl-default-edit-cell-name">
						</p>
					</div>-->
                    <div class="js-default-dialog-content">
                        <div class="spinner">

                        </div>
                    </div>
                </div> <!-- .ddl-popup-tab -->
                <?php do_action('ddl-before_cell_markup_controls'); ?>
                <div class="ddl-popup-tab ddl-markup-controls"
                " id="js-cell-settings-<?php echo $unique_id; ?>">
                <?php
                $dialog_type = 'default';
                do_action('ddl-before_cell_default_edit_fields');
                include 'cell_display_settings_tab.tpl.php';
                do_action('ddl-after_cell_default_edit_fields');
                ?>
            </div> <!-- .ddl-popup-tab -->

        </div> <!-- .js-popup-tabs -->

    </div>

    <div class="ddl-dialog-footer js-dialog-footer">
        <?php wp_nonce_field('wp_nonce_edit_css', 'wp_nonce_edit_css'); ?>
        <input id="ddl-layout-cell-unique-id-<?php echo $unique_id; ?>" type="hidden"
               name=<?php the_ddl_name_attr('unique_id'); ?> value=""/>
        <button class="button js-edit-dialog-close ddl-edit-dialog-close"><?php _e('Cancel', 'ddl-layouts') ?></button>
        <button data-close="yes"
                class="button button-primary js-dialog-edit-save js-save-dialog-settings-and-close hidden"
                data-update-text="<?php _e('Apply', 'ddl-layouts') ?>"
                data-create-text="<?php _e('Create', 'ddl-layouts') ?>"><?php _e('Apply', 'ddl-layouts') ?></button>
    </div>

</div>

</div>


<?php require_once(WPDDL_GUI_ABSPATH . 'dialogs/js/templates/child-layout-manager.box.tpl.php') ?>

<div class="ddl-dialogs-container">
    <div class="ddl-dialog auto-width" id="js-child-layout-box-container"></div>
    <textarea
        id="js-layout-children" <?php if (!WPDDL_DEBUG) echo 'style="display:none"'; ?>>
        <?php if (isset($_GET['layout_id'])) {
            echo wp_json_encode(array('children_layouts' => WPDD_Layouts::get_layout_children($_GET['layout_id'])));
        } ?>
    </textarea>
</div>

