<div id="functions_editor_container" class="functions_editor_container">
    <?php if(\Wpae\Integrations\CodeBox::isUsingCodeBox($functions_content)): 
        if( \Wpae\Integrations\CodeBox::isCodeBoxInstalled() ):
	        echo sprintf(
		        '<a style="color:#fff;" href="%1$s" class="%2$s" target="_blank" aria-label="%3$s">%4$s</a>',
		        esc_url( admin_url( 'admin.php?page=wpcodebox2' ) ),
		        esc_attr( 'button button-primary' ),
		        esc_attr__( 'Go to WPCodeBox settings', 'text-domain' ),
		        esc_html__( 'Manage Functions in WPCodeBox', 'text-domain' )
	        );
        endif;
    else:?>
        <textarea id="<?php echo $wpae_editor_ref ?? 'wp_all_export_code';?>" name="<?php echo $wpae_editor_ref ?? 'wp_all_export_code';?>"><?php echo (empty($functions_content)) ? "<?php\n\n?>": esc_textarea($functions_content);?></textarea>
    <?php endif;?>
</div>
<?php if( ! \Wpae\Integrations\CodeBox::isUsingCodeBox($functions_content)):?>
<div id="wpae_function_editor_buttons" class="input wpae_function_editor_buttons" style="margin-top: 10px;">
	<div class="input" style="display:inline-block; margin-right: 20px;">
		<input type="button" class="button-primary wp_all_export_save_functions <?php echo $wpae_editor_save_ref ?? '';?>" value="<?php _e("Save Functions", 'wp-all-export-pro'); ?>"/>
		<input type="button" class="button-secondary wp_all_export_send_to_codebox <?php echo $wpae_editor_ref ?? 'wp_all_export_code';?>" style="font-family:inherit; font-size:13px;" value="<?php _e("Send to CodeBox", 'wp-all-export-pro'); ?>"/>
		<a href="#help" class="wpallexport-help" title="<?php printf(__("Add functions here for use during your export. You can access this file at %s", "wp-all-export-pro"), preg_replace("%.*wp-content%", "wp-content", $functions));?>" style="top: 0;">?</a>
		<div class="wp_all_export_functions_preloader"></div>
	</div>
	<input type="hidden" name="is_wp_codebox_active" value="<?php echo \Wpae\Integrations\CodeBox::isCodeBoxInstalled() ? '1' : '0'; ?>"/>
	<div class="input wp_all_export_saving_status"></div>
    <div class="cross-sale-notice codebox" style="display: none;">
        <div class="codebox-inner">
            <div class="codebox-left">
                <h1>
                    Install WPCodeBox to Continue
                </h1>
                <p>
                    WPCodeBox allows you to save all your Code Snippets to the Cloud and share them across your WordPress sites. The Code Snippet Repository provides you with a library of tested and ready-to-use Code Snippets for your WordPress site.
                </p>
                <div class="codebox-button-container">
                    <a href="https://wpcodebox.com/" target="_blank">
                        <span>Get WPCodeBox</span>
                    </a>
                </div>
            </div>
            <div class="codebox-right">
                <p><em>"WPCodeBox is a fantastic plugin that makes it easy to organize your code snippets and load them only when needed, helping you to eliminate bloated plugins and speed up your site. The interface is incredibly snappy and is a joy to use."</em></p>
                <div class="codebox-image-container" style="">
                    <img src="<?php echo PMXE_Plugin::ROOT_URL; ?>/static/img/rob-carter.webp" alt="Rob Carter">
                    <p><strong>Rob Carter<br>Megademic</strong></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif;?>
<?php if(\Wpae\Integrations\CodeBox::isUsingCodeBox($functions_content) && !\Wpae\Integrations\CodeBox::isCodeBoxInstalled()): ?>
    <div id="wpae_revert_to_functions_file" style="margin-top: 10px;">
        <input type="button"
               class="button-secondary wp_all_export_revert_functions"
               value="<?php _e("Revert to Functions File", 'wp-all-export-pro'); ?>" />
        <p class="custom-error-notice" style="margin-top: 5px;"><?php _e("WPCodeBox is not active. Click 'Revert to Functions File' to attempt to restore the previous functions file. Alternatively, activate WPCodeBox for continued use.", 'wp-all-export-pro'); ?></p>
    </div>
<?php endif; ?>

<?php echo sprintf(
	'<a id="wpae_go_to_codebox" style="color:#fff;display:none;" href="%1$s" target="_blank" class="%2$s wpae_go_to_codebox" aria-label="%3$s">%4$s</a>',
	esc_url( admin_url( 'admin.php?page=wpcodebox2' ) ),
	esc_attr( 'button button-primary' ),
	esc_attr__( 'Go to WPCodeBox settings', 'text-domain' ),
	esc_html__( 'Manage Functions in WPCodeBox', 'text-domain' )
);
?>

<?php
if(!\Wpae\Integrations\CodeBox::isCodeBoxInstalled()){
	\Wpae\Ads\AdManager::display('Wpcb');
}
?>
