<?php

function pmxi_wp_ajax_wpai_scheduling_dialog_content()
{

    if (!check_ajax_referer('wp_all_import_secure', 'security', false)) {
        exit(json_encode(array('html' => __('Security check', 'wp-all-import-pro'))));
    }

    if (!current_user_can(PMXI_Plugin::$capabilities)) {
        exit(json_encode(array('html' => __('Security check', 'wp-all-import-pro'))));
    }

    $import_id = $_POST['id'];
    $import = new PMXI_Import_Record();
    $import->getById($import_id);
    if (!$import) {
        throw new Exception('Import not found');
    }

    $post = $import->options;

    if (!isset($post['scheduling_enable'])) {
        $post['scheduling_enable'] = 0;
    }

    if (!isset($post['scheduling_timezone'])) {
        $post['scheduling_timezone'] = 'UTC';
    }

    if (!isset($post['scheduling_run_on'])) {
        $post['scheduling_run_on'] = 'weekly';
    }

    if (!isset($post['scheduling_times'])) {
        $post['scheduling_times'] = array();
    }

    if (!isset($post['scheduling_weekly_days'])) {
        $post['scheduling_weekly_days'] = '';
    }
	$is_dialog_context = true;
	?>
	<div id="post-preview" class="wpallimport-preview wpallimport-scheduling-dialog">
		<p class="wpallimport-preview-title scheduling-preview-title" style="font-size: 13px !important;"><strong>Scheduling Options for Import ID
                #<?php echo intval($import_id); ?></strong></p>
	<?php
    require_once(PMXI_Plugin::ROOT_DIR . '/views/admin/import/options/scheduling/_scheduling_ui.php');
	?>
	</div>
	<?php

    wp_die();
}
