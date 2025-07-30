<?php
	// When editing a Content Template with Layouts, the hierarchy settings should be hidden because there are no such settings
	// for Content Templates.
	$maybe_hidden_class_for_cts = $this->is_editing_ct() || $this->is_editing_inline_ct() ?
		' hidden' :
		'';

	// When editing an inline Content Template with Layouts, the buttons "Close", "Previe" and "Save and Close" doesn't have
	// a meaning of existence, so they need to be hidden.
	$maybe_hidden_class_for_inline_cts = $this->is_editing_inline_ct() ? ' hidden' : '';

?>
<div class="dd-layouts-wrap">
    <?php $icon_object = $this->print_layout_icon();?>
    <div id="iconwrap" class="<?php echo ! $this->is_editing_ct() && ! $this->is_editing_inline_ct() ? esc_attr( 'js-layouts-icon-wrap' ) : ''; ?>">
        <img src="<?php echo WPDDL_RES_RELPATH;?>/images/<?php echo esc_attr( $icon_object->icon );?>"  />
    </div>

    <div class="toolbar_for_private_layout">

        <div class="editor-toolbar editor-toolbar-private js-editor-toolbar">
            <div class="save-button-wrap">
                <a class="button button-secondary button-large<?php echo esc_attr( $maybe_hidden_class_for_inline_cts ); ?>" id="js-private-layout-cancel-button"><?php _e( 'Close', 'ddl-layouts' ); ?></a>
                <button class="button button-large button-secondary hide-if-no-js js-view-layout<?php echo esc_attr( $maybe_hidden_class_for_cts ); ?>"><?php _e( 'Preview', 'ddl-layouts' ); ?></button>
                <button name="only_save_private_layout" class="button button-primary button-large" id="js-private-layout-only-save-button"><?php _e('Save','ddl-layouts'); ?></button>
                <button name="save_private_layout" class="button button-primary button-large<?php echo esc_attr( $maybe_hidden_class_for_inline_cts ); ?>" id="js-private-layout-done-button"><?php _e('Save & Close','ddl-layouts'); ?></button>
            </div>
            <div class="undo_redo_private  js-hide-for-private-layout" >
                <button class="js-undo-button button button-large hidden" value="Undo" name="undo"><i class="icon-undo fa fa-undo"></i></button>
                <button class="js-redo-button button button-large hidden" name="redo"><i class="fa fa-repeat icon-repeat"></i></button>
            </div>
            <div class="pl_title"><?php _e('Title:', 'dd-layouts');?> <?php echo $post_title;?></div>
        </div>
    </div>
    <div class="private-layout-buttons">
        <button class="ddl-js-info-tooltip-button button button-large" name="show_info_tooltip"
                data-status="hidden"><i class="fa fa-code icon-code"></i> <span
                id="ddl-js-info-tooltip-button-text"><?php _e( 'Show styling info', 'ddl-layouts' ); ?></span>
        </button>
        <button class="ddl-bootstrap-base-button js-ddl-bootstrap-base-button button button-large" name="ddl-bootstrap-base"
                data-status="hidden"><i class="icon-bootstrap-original-logo"></i> <span
                id="js-ddl-bootstrap-base-button-text"><?php _e( 'Column width', 'ddl-layouts' ); ?></span>
        </button>
        <button class="ddl-layout-storage js-ddl-layout-storage button button-large" title="<?php _e( 'Open layout storage', 'ddl-layouts' ); ?>" name="ddl-layout-storage"
                data-status="hidden"><i class="fa fa-file-code-o"></i>
        </button>
    </div>
</div>


<?php if( $this->check_help_video_watched() === false ) : ?>
<div class="ddl-video-private-container dd-layouts-wrap">
	<div class="toolset-video-box-private-wrap"></div>
	<?php $this->track_help_video_watched(); ?>
</div>
<?php endif; ?>


<div class="dd-layouts-wrap main-ddl-editor">

    <div class="layout-container js-layout-container rows">

        <div class="progress progress-striped active">
            <div class="bar"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
        </div>

    </div>

</div> <!-- .main-ddl-editor -->

<div class="clear"></div>

<textarea id="layouts-hidden-content" name="layouts-hidden-content"  class="js-hidden-json-textarea hidden-json-textarea" <?php if(!WPDDL_DEBUG) echo 'style="display:none"'; ?>><?php echo $layout_json; ?></textarea>

<div class="hidden">
    <?php // TODO: We should move it to separate template file ?>

    <div class="js-context-menu ddl-context-menu">

        <ul>
            <li class="js-edit-params"><i class="icon-edit fa fa-pencil-square-o"></i> <?php _e('Edit cell','ddl-layouts') ?></li>
            <li class="js-edit-css"><i class="fa fa-css3 icon-css3"></i> <?php _e('Edit CSS','ddl-layouts') ?></li>
            <li class="js-remove-cell"><i class="icon-trash fa fa-trash-o"></i> <?php _e('Remove','ddl-layouts') ?></li>
        </ul>

    </div>

    <div class="js-add-row-menu ddl-context-menu">

        <ul>
            <li class="row12 add-row js-add-row js-add-row-item" data-row-type="normal-row" data-cell-width="1"><?php _e('12 columns','ddl-layouts') ?></li>
            <li class="row6 add-row js-add-row js-add-row-item" data-row-type="normal-row" data-cell-width="2"><?php _e('6 columns','ddl-layouts') ?></li>
            <li class="row4 add-row js-add-row js-add-row-item" data-row-type="normal-row" data-cell-width="3"><?php _e('4 columns','ddl-layouts') ?></li>
            <li class="row3 add-row js-add-row js-add-row-item" data-row-type="normal-row" data-cell-width="4"><?php _e('3 columns','ddl-layouts') ?></li>
            <li class="row2 add-row js-add-row js-add-row-item" data-row-type="normal-row" data-cell-width="6"><?php _e('2 columns','ddl-layouts') ?></li>
            <li class="row1 add-row js-add-row js-add-row-item" data-row-type="normal-row" data-cell-width="12"><?php _e('1 column','ddl-layouts') ?></li>
            <li class="js-add-row js-add-row-item js-copy-row add-row add-row-duplicate" data-row-type="copy-row"><?php _e('Copy row','ddl-layouts') ?></li>
            <li class="js-add-row js-add-row-item disabled js-paste-row add-row add-row-duplicate" data-row-type="paste-row"><?php _e('Paste row','ddl-layouts') ?></li>
        </ul>

    </div>

    <div class="js-add-special-row-menu ddl-context-menu ddl-special-row-context-menu">

        <ul>
            <li class="js-add-row js-add-row-item" data-row-type="normal-row"><?php _e('Add cells row','ddl-layouts') ?></li>

            <?php
            global $wpddlayout;
            if( $wpddlayout->has_theme_sections() ):?>
                <li class="js-add-row js-add-row-item" data-row-type="theme-section-row"><?php _e('Add custom row','ddl-layouts') ?></li>
            <?php endif;?>

            <li class="js-add-row js-add-row-item js-copy-row" data-row-type="copy-row"><?php _e('Copy row','ddl-layouts') ?></li>
            <li class="js-add-row js-add-row-item disabled js-paste-row" data-row-type="paste-row"><?php _e('Paste row','ddl-layouts') ?></li>

        </ul>

    </div>

    <div class="js-add-special-tab-menu ddl-context-menu ddl-special-row-context-menu">

        <ul>
            <li class="js-add-row js-add-row-item" data-row-type="normal-row"><?php _e('Add tab','ddl-layouts') ?></li>

            <li class="js-add-row js-add-row-item js-copy-row" data-row-type="copy-row"><?php _e('Copy tab','ddl-layouts') ?></li>
            <li class="js-add-row js-add-row-item disabled js-paste-row" data-row-type="paste-row"><?php _e('Paste tab','ddl-layouts') ?></li>

        </ul>

    </div>

    <div class="js-add-special-panel-menu ddl-context-menu ddl-special-row-context-menu">

        <ul>
            <li class="js-add-row js-add-row-item" data-row-type="normal-row"><?php _e('Add accordion panel','ddl-layouts') ?></li>

            <li class="js-add-row js-add-row-item js-copy-row" data-row-type="copy-row"><?php _e('Copy panel','ddl-layouts') ?></li>
            <li class="js-add-row js-add-row-item disabled js-paste-row" data-row-type="paste-row"><?php _e('Paste panel','ddl-layouts') ?></li>

        </ul>

    </div>

</div>

<?php // $layout_json_not_decoded_debug = WPDD_Layouts::get_layout_settings($post->ID); ?>
<!--  <div class="hidden DEBUG"><?php //print $layout_json_not_decoded_debug;?></div> -->