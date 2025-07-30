<?php

function pmxi_wp_ajax_wpai_scheduling_subscribe_dialog_content()
{

    if (!check_ajax_referer('wp_all_import_secure', 'security', false)) {
        exit(json_encode(array('html' => __('Security check', 'wp-all-import-pro'))));
    }

    if (!current_user_can(PMXI_Plugin::$capabilities)) {
        exit(json_encode(array('html' => __('Security check', 'wp-all-import-pro'))));
    }

	$showAlreadyLicensed = false;

	?>
	<div id="post-preview" class="wpallimport-preview wpallimport-scheduling-dialog">
		<p class="wpallimport-preview-title scheduling-preview-title" style="font-size: 13px !important;"><strong>Subscribe to Automatic Scheduling</strong></p>
	<?php
    require_once(PMXI_Plugin::ROOT_DIR . '/views/admin/import/options/scheduling/_scheduling_subscribe_ui.php');
	?>
	</div>

	<?php

    wp_die();
}
