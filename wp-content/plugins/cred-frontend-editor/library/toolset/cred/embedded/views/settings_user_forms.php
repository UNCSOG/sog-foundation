<?php
$settings_model = CRED_Loader::get('MODEL/Settings');
$settings = $settings_model->getSettings();
?>
<h3><?php _e('Email Auto-generation', 'wp-cred'); ?></h3>
<p>
    <?php _e('If auto-generate username and/or password is set in Toolset User Form settings the following Email will be auto-generated.', 'wp-cred'); ?>
</p>
<p>
	<label>
		<?php _e('Email Subject', 'wp-cred'); ?>
		<input type="text" name="settings[autogeneration_email][subject]" value="<?php if (isset($settings['autogeneration_email']['subject'])) echo $settings['autogeneration_email']['subject']; ?>" />
	</label>
</p>
<p>
	<?php _e('Email Body', 'wp-cred'); ?>
	<textarea cols="50" rows="10" name="settings[autogeneration_email][body]"><?php if (isset($settings['autogeneration_email']['body'])) echo $settings['autogeneration_email']['body']; ?></textarea>
</p>