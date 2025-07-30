 <div class="ddl-dialogs-container">
	<div class="ddl-dialog auto-width" id="ddl-dialog-assign-layout-to-post-type-<?php echo $type?>">
		<div class="ddl-dialog-header">
			<h2><?php _e('Do you want to apply to all?','ddl-layouts'); ?></h2>
			<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
		</div>
		<div class="ddl-dialog-content">
			<p>
				<?php echo $data['message']; ?>
			</p>
			<?php if( $data['template_exists'] === false ): ?>
				<p class=" toolset-alert toolset-alert-warning">
					<?php echo sprintf(__("A template file that supports layouts is not available for the %s post type.", 'ddl-layouts'), '<strong>"' . $post_type->labels->singular_name . '"</strong>') ?><br>
					<?php printf(__('Please check %shere%s to see how to set one up.', 'ddl-layouts'), '<a href="' . WPDLL_LEARN_ABOUT_SETTING_UP_TEMPLATE . '" target="_blank">', '</a>'); ?>
				</p>
			<?php endif; ?>
		</div>

		<div class="ddl-dialog-footer">
			<button class="button js-edit-dialog-close"><?php _e('Cancel', 'ddl-layouts') ?></button>
			<button class="button button-primary js-ddl-update-posts-process">
				<?php echo sprintf(__('Update %s now', 'ddl-layouts'), $post_type->labels->name) ?></button>
		</div>
	</div>
</div>

<script type="text/html" id="ddl-layout-to-meta-confirm-box">

	<div class="ddl-dialog-header">
		<h2><?php _e('Success', 'ddl-layouts');?></h2>
		<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
	</div>
	<div class="ddl-dialog-content">
		<?php printf(__('All %s were updated.', 'ddl-layouts'), '{{{ label }}}'); ?>
	</div>
	<div class="ddl-dialog-footer">
		<button class="button js-edit-dialog-close"><?php _e('Close', 'ddl-layouts'); ?></button>
	</div>

</script>

<div class="ddl-dialogs-container">
	<div id="ddl-layout-to-meta-confirm-box-wrap" class="ddl-dialog"></div>
</div>

