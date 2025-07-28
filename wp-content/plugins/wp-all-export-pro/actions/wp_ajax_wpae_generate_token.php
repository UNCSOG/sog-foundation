<?php

function pmxe_wp_ajax_wpae_generate_token()
{

    if (!check_ajax_referer('wp_all_export_secure', 'security', false)) {
        exit(json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))));
    }

    if (!current_user_can(PMXE_Plugin::$capabilities)) {
        exit(json_encode(array('html' => esc_html__('Security check', 'wp_all_export_plugin'))));
    }

    $input = new PMXE_Input();

    $export_id = $input->post('data', 0);
    $cron_job_key = PMXE_Plugin::getInstance()->getOption('cron_job_key');

    $security_token = substr(md5($cron_job_key . $export_id . microtime() . uniqid()), 0, 16);
    $export = new PMXE_Export_Record();
    $export->getById($export_id);

    $exportOptions = $export->options;
    $exportOptions['security_token'] = $security_token;

    $export->set(['options' => $exportOptions])->update();

    $urlToExport = site_url() . '/wp-load.php?security_key=' . $security_token . '&export_id=' . $export_id . '&action=get_data';

    echo $urlToExport;

    die;
}
