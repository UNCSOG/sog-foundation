<div class="ddl-form js-css-styling-controls css-styling-controls">

	<div class="ddl-fields-description-wrap">
        <h4><?php printf( __('%s HTML tag', 'ddl-layouts'), $dialog_type === 'row' ? 'Row' : 'Cell' );?></h4>
        <p class="ddl-fields-description"><?php printf(__('All layout %s will appear inside an HTML tag. By default, this is a DIV tag. Here, you can change the HTML tag for the cell, add classes and an ID to it. Learn more about %sstyling layout cells%s.', 'ddl-layouts'), $dialog_type === 'row' ? 'rows' : 'cells', '<a href="'.WPDDL_CSS_STYLING_LINK.'" target="_blank" >', '<i class="fa fa-external-link ddl-help-link-external" aria-hidden="true"></i></a>');?></p>
    </div>

 <div class="ddl-fields-container float-left">
	<?php do_action('ddl-before_default_edit_fields', $dialog_type);?>

	<p>
		<label class="label-tag" for="ddl_tag_name"><?php _e('HTML Tag:', 'ddl-layouts'); ?></label>
		<select class="js-toolset_select2 js-ddl-tag-name" id="ddl_tag_name" name="ddl_tag_name">
            <option value="article">&lt;article&gt;</option>
			<option value="aside">&lt;aside&gt;</option>
			<option value="blockquote">&lt;blockquote&gt;</option>
			<option value="button">&lt;button&gt;</option>
			<option value="div" selected>&lt;div&gt;</option>
			<option value="figure">&lt;figure&gt;</option>
			<option value="footer">&lt;footer&gt;</option>
            <option value="h1">&lt;h1&gt;</option>
            <option value="h2">&lt;h2&gt;</option>
            <option value="h3">&lt;h3&gt;</option>
            <option value="h4">&lt;h4&gt;</option>
            <option value="h5">&lt;h5&gt;</option>
            <option value="h6">&lt;h6&gt;</option>
			<option value="header">&lt;header&gt;</option>
            <option value="nav">&lt;nav&gt;</option>
			<option value="section">&lt;section&gt;</option>
		</select>
		<span class="desc"><?php _e('Choose the HTML tag to use when rendering this cell.','ddl-layouts') ?></span>
	</p>
	<p>
		<label class="label-tag-id" for="ddl-<?php echo $dialog_type; ?>-edit-css-id"><?php _e('Tag ID', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>):</span></label>
		<input type="text" name="ddl-<?php echo $dialog_type; ?>-edit-css-id" id="ddl-<?php echo $dialog_type; ?>-edit-css-id" class="js-edit-css-id">
		<span class="desc"><?php _e('Set an ID for the cell if you want to specify a unique style for it.','ddl-layouts') ?></span>
	</p>
	<p>
		<label class="label-tag-classes" for="ddl-<?php echo $dialog_type; ?>-edit-class-name"><?php _e('Tag classes', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>):</span></label>
		<select name="ddl-<?php echo $dialog_type; ?>-edit-class-name" id="ddl-<?php echo $dialog_type; ?>-edit-class-name" multiple class="js-toolset-chosen-select"></select>
		<span class="desc" style="display:none"><?php _e('Separated class names by a single space.','ddl-layouts') ?></span>
	</p>

	<?php do_action('ddl-after_default_edit_fields', $dialog_type);?>

 </div>

    <div class="from-top-20 float-left">
        <div class="js-css-editor-message-container"></div>

        <div class="js-preset-layouts-rows row-not-render-message" id="js-child-not-render-message">
            <p class="toolset-alert toolset-alert-info">
                <?php _e('This cell in itself will not have a typical row structure on the front-end. It will directly output content of the child layout which is why you can not add classes and IDs to it. To add custom styling, edit the child layout instead and add custom classes and IDs there. For more information, see <a href="https://toolset.com/documentation/legacy-features/toolset-layouts/hierarchical-layouts/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">Using layout hierarchy for quick development</a>.', 'ddl-layouts'); ?>
            </p>
        </div>
    </div>
</div>

<script type="text/html" id="ddl-styles-extra-controls">
	<div class="ddl-form js-css-styling-controls css-styling-controls">
		<p>
			<label for="ddl_tag_name"><?php _e('HTML Tag:', 'ddl-layouts'); ?></label>
			<select class="js-toolset_select2 js-ddl-tag-name" id="ddl_tag_name" name="ddl_tag_name">
				<option value="article">&lt;article&gt;</option>
				<option value="aside">&lt;aside&gt;</option>
				<option value="blockquote">&lt;blockquote&gt;</option>
				<option value="button">&lt;button&gt;</option>
				<option value="div" selected>&lt;div&gt;</option>
				<option value="figure">&lt;figure&gt;</option>
				<option value="footer">&lt;footer&gt;</option>
				<option value="h1">&lt;h1&gt;</option>
				<option value="h2">&lt;h2&gt;</option>
				<option value="h3">&lt;h3&gt;</option>
				<option value="h4">&lt;h4&gt;</option>
				<option value="h5">&lt;h5&gt;</option>
				<option value="h6">&lt;h6&gt;</option>
				<option value="header">&lt;header&gt;</option>
				<option value="section">&lt;section&gt;</option>
			</select>
			<span class="desc"><?php _e('Choose the HTML tag to use when rendering this cell.','ddl-layouts') ?></span>
		</p>
		<p>
			<label for="ddl-default-edit-css-id"><?php _e('Tag ID:', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>)</span></label>
			<input type="text" name="ddl-default-edit-css-id" id="ddl-default-edit-css-id" class="js-edit-css-id css-id-control" value="{{{id}}}">
			<span class="desc"><?php _e('Set an ID for the cell if you want to specify a unique style for it.','ddl-layouts') ?></span>
		</p>
		<p>
			<div class="ddl-styling-inside-iframe">
				<label for="ddl-default-edit-class-name"><?php _e('Tag classes:', 'ddl-layouts'); ?> <span class="opt">(<?php _e('optional', 'ddl-layouts'); ?>)</span></label>
				<select name="ddl-default-edit-class-name" id="ddl-default-edit-class-name" multiple class="js-toolset-chosen-select-iframe" value="{{{css}}}"></select>
				<span class="desc" style="display:none"><?php _e('Separated class names by a single space.','ddl-layouts') ?></span>
			</div>
		</p>

		<div class="js-css-editor-message-container"></div>

	</div>
</script>
