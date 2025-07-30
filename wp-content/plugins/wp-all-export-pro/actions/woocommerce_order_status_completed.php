<?php

function pmxe_woocommerce_order_status_completed($order_id) {

    $addonService = new \Wpae\App\Service\Addons\AddonService();

    if(!$addonService->isWooCommerceOrderAddonActive() && !$addonService->isWooCommerceAddonActive()) {
        return;
    }


    $list = new PMXE_Export_List();

    $exportList = $list->setColumns($list->getTable() . '.*')->getBy();

    foreach ($exportList as $export) {
        if (isset($export['options']['enable_real_time_exports']) && $export['options']['enable_real_time_exports'] && $export['options']['enable_real_time_exports_running']) {
            if (in_array('shop_order', $export['options']['cpt'])) {

                if ($order_id) {

                    $exportRecord = new PMXE_Export_Record();
                    $exportRecord->getById($export['id']);
                    $exportRecord->execute(false, true, $order_id);
                }
            }
        }
    }
}