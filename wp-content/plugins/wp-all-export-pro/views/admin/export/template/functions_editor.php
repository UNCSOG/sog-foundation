<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<?php
$uploads = wp_upload_dir();
$functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
$functions = apply_filters( 'wp_all_export_functions_file_path', $functions );
$functions_content = file_get_contents($functions);
?>
<style type="text/css">
    .wpae-collapser {
        float: right;
        height: 20px;
        width: 20px;
        background: url('<?php echo esc_url(PMXE_ROOT_URL . '/static/img/collapser.png'); ?>') 0 0;
        margin-right: 10px;
        background-size: cover;
    }

    .closed .wpae-collapser{
        background: url('<?php echo esc_url(PMXE_ROOT_URL . '/static/img/collapser.png'); ?>') 0 20px;
        background-size: cover;
    }
</style>
<div class="wpallexport-collapsed wpallexport-section wpallexport-file-options functions-editor" style="margin-top: 0px;">
    <div class="wpallexport-content-section" style="padding-bottom: 0; margin-bottom: 10px;">
        <div class="wpallexport-collapsed-header edit-functions-collapsed-header" style="padding-left: 25px; background: none;">
            <div class="wpae-collapser"></div>
            <h3 style="font-size: 14px; line-height: normal; margin-top: 11px; color: #464646;"><?php esc_html_e('Function Editor', 'wp_all_export_plugin');?><a href="#help" class="wpallexport-help" title="<?php printf(esc_html__("Add functions here for use during your export. You can access this file at %s", "wp_all_export_plugin"), esc_html(preg_replace("%.*wp-content%", "wp-content", $functions)));?>" style="top: -1px;">?</a></h3>
        </div>
        <div class="wpallexport-collapsed-content" style="padding: 0; overflow: hidden; height: auto; display: block;">
            <div class="wpallexport-collapsed-content-inner wpallexport-modal-container" style="padding-top:0;">
	            <?php
                    $wpae_editor_ref = 'wp_all_export_main_code';
                    $wpae_editor_save_ref = 'wp_all_export_save_main_code'; 
                    require(PMXE_Plugin::ROOT_DIR . '/views/admin/shared/function_editor.php');
                ?>
            </div>
        </div>
    </div>
</div>
