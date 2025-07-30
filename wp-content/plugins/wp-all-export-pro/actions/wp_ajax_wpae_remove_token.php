<?php

function pmxe_wp_ajax_wpae_remove_token()
{

    if (!check_ajax_referer('wp_all_export_secure', 'security', false)) {
        exit(json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))));
    }

    if (!current_user_can(PMXE_Plugin::$capabilities)) {
        exit(json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))));
    }

    $input = new PMXE_Input();

    $export_id = $input->post('data', 0);

    $export = new PMXE_Export_Record();
    $export->getById($export_id);

    $exportOptions = $export->options;
    $exportOptions['security_token'] = '';

    $export->set(['options' => $exportOptions])->update();

    die;
}
