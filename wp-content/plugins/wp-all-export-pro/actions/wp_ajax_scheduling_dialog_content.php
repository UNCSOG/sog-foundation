<?php

function pmxe_wp_ajax_scheduling_dialog_content()
{

	if (!check_ajax_referer('wp_all_export_secure', 'security', false)) {
		exit(json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))));
	}

	if (!current_user_can(PMXE_Plugin::$capabilities)) {
		exit(json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))));
	}

	$export_id = $_POST['id'];
	$export = new PMXE_Export_Record();
	$export->getById($export_id);
	if (!$export) {
		throw new Exception('Export not found');
	}

	$post = $export->options;

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

    $is_dialog_context = 1;
	?>
    <div id="post-preview" class="wpallexport-preview wpallexport-scheduling-dialog">
        <p class="wpallexport-preview-title scheduling-preview-title" style="font-size: 13px !important;"><strong>Scheduling Options for Export ID
                #<?php echo intval($export_id); ?></strong></p>
		<?php
		require_once(PMXE_Plugin::ROOT_DIR . '/src/Scheduling/views/SchedulingUI.php');
		?>
    </div>
	<?php

	wp_die();
}
