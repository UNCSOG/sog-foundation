<?php

function pmxe_wp_ajax_wpae_get_scheduling_connection_icon() {
	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false ) ){
		wp_send_json_error(array('html' => __('Security check failed.', 'wp-all-import-pro')));
	}

	if ( ! current_user_can( PMXI_Plugin::$capabilities ) ){
		wp_send_json_error(array('html' => __('Insufficient permissions.', 'wp-all-import-pro')));
	}

	ob_start();
	require_once(PMXI_Plugin::ROOT_DIR . '/views/admin/import/options/scheduling/_connection_icon.php');
	$html_output = ob_get_clean();
	wp_send_json_success(array('html' => $html_output));
}