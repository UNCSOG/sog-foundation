<?php global $wpddl_features;
$hide = $wpddl_features->is_feature('fixed-layout') ? '' : ' class="hidden" ';
?>

<fieldset <?php echo $hide;?> >
	<legend><?php _e('Layout type','ddl-layouts'); ?></legend>
	<div class="fields-group">
		<?php if ( $wpddl_features->is_feature('fixed-layout') ): ?>
			<label for="<?php echo $name; ?>-fixed">
				<input type="radio" name="<?php echo $name; ?>" id="<?php echo $name; ?>-fixed" class="js-dd-layout-type" value="fixed" checked>
				<?php _e('Fixed-width columns', 'ddl-layouts'); ?>
			</label>
		<?php endif; ?>
		<?php if ( $wpddl_features->is_feature('fluid-layout') ): ?>
			<label for="<?php echo $name; ?>-fluid">
				<input type="radio" name="<?php echo $name; ?>" id="<?php echo $name; ?>-fluid" class="js-dd-layout-type" value="fluid" <?php if ($wpddl_features->is_feature('fixed-layout') === false): ?> checked <?php endif;?> >
				<?php _e('Fluid columns', 'ddl-layouts'); ?>
			</label>
		<?php endif; ?>
	</div>
</fieldset>