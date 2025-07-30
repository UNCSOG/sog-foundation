<?php

function pmxe_user_register($user_id) {

    $addonsService = new \Wpae\App\Service\Addons\AddonService();

    if(!$addonsService->isUserAddonActive()) {
        return false;
    }

    $list = new PMXE_Export_List();

    $exportList = $list->setColumns($list->getTable() . '.*')->getBy();

    foreach($exportList as $export) {
        if(isset($export['options']['enable_real_time_exports']) && $export['options']['enable_real_time_exports'] && $export['options']['enable_real_time_exports_running']) {
            if(in_array('users', $export['options']['cpt'])) {

                if($user_id) {
                    $exportRecord = new PMXE_Export_Record();
                    $exportRecord->getById($export['id']);
                    $exportRecord->execute(false, true, $user_id);
                }
            }
        }
    }
}