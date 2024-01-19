<?php
$settings_model = CRED_Loader::get('MODEL/Settings');
$settings = $settings_model->getSettings();
?>
<div class="js-cred-settings-wrapper">
	<p>
		<?php _e('If you are willing to use reCAPTCHA to protect your Forms against bots\' entries please provide public and private keys for reCAPTCHA API:', 'wp-cred'); ?>
	</p>
	<p>
		<label>
			<?php _e('Site Key', 'wp-cred'); ?>
			<input type="text" autocomplete="off" class="js-cred-recaptcha-setting" size='50' name="cred_recaptcha_public_key" value="<?php if (isset($settings['recaptcha']['public_key'])) echo $settings['recaptcha']['public_key']; ?>"  />
		</label>
	</p>
	<p>
		<label>
			<?php _e('Secret Key', 'wp-cred'); ?>
			<input type="text" autocomplete="off" class="js-cred-recaptcha-setting" size='50' name="cred_recaptcha_private_key" value="<?php if (isset($settings['recaptcha']['private_key'])) echo $settings['recaptcha']['private_key']; ?>"  />
		</label>
	</p>
	<p>
		<?php
		echo sprintf(
			__('Do not have reCAPTCHA API Keys? %sSign Up to use reCAPTCHA API%s', 'wp-cred' ),
			'<a target="_blank" href="https://www.google.com/recaptcha/admin#whyrecaptcha">',
			'</a>'
		);
		?>
	</p>
</div>
<?php wp_nonce_field( 'cred-recaptcha-settings', 'cred-recaptcha-settings' ); ?>
