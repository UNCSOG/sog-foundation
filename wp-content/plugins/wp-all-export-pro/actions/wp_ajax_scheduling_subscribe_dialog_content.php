<?php

function pmxe_wp_ajax_scheduling_subscribe_dialog_content()
{

	if (!check_ajax_referer('wp_all_export_secure', 'security', false)) {
		exit(json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))));
	}

	if (!current_user_can(PMXE_Plugin::$capabilities)) {
		exit(json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))));
	}

	$showAlreadyLicensed = false;

	?>
    <div id="post-preview" class="wpallexport-preview wpallexport-scheduling-dialog">
        <p class="wpallexport-preview-title scheduling-preview-title" style="font-size: 13px !important;"><strong>Subscribe to Automatic Scheduling</strong></p>
		<?php
		require_once(PMXE_Plugin::ROOT_DIR . '/src/Scheduling/views/SchedulingSubscribeUI.php');
		?>
    </div>
	<?php

	wp_die();
}
