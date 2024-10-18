<?php
global $wpddlayout;
?>

<div class="wrap">
	<div id="icon-tools" class="icon32 icon32-posts-dd_layouts"><br></div>
	<h2><?php _e('Layouts settings', 'ddl-layouts'); ?></h2>

	<div class="ddl-settings-wrap">

		<!--////////// CSS FRAMEWORK ////////////-->
		<div class="ddl-settings">

			<div class="ddl-settings-header">
				<h3><?php _e('CSS Framework', 'ddl-layouts');?></h3>
			</div>

			<div class="ddl-settings-content">

				<?php $wpddlayout->frameworks_options_manager->print_frameworks_settings();?>

			</div> <!-- .ddl-settings-content -->

		</div> <!-- .ddl-settings -->

		<?php
		/* Uncomment this section if needed

		<div class="ddl-settings">

			<div class="ddl-settings-content">
				<p class="buttons-wrap">
					<button class="button-primary js-save-layouts-css-options"><?php _e('Save all settings', 'ddl-layouts');?></button>
				</p>
			</div> <!-- .ddl-settings-content -->

		</div> <!-- .ddl-settings -->

		*/
		?>

	</div> <!-- .ddl-settings-wrap -->

<div class="clear"></div>