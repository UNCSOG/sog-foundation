<?php

function pmxe_wp_insert_comment($comment_id, $comment) {

    $list = new PMXE_Export_List();

    $exportList = $list->setColumns($list->getTable() . '.*')->getBy();

    $post_parent = get_post($comment->comment_post_ID);

    foreach($exportList as $export) {
        if(isset($export['options']['enable_real_time_exports']) && $export['options']['enable_real_time_exports'] && $export['options']['enable_real_time_exports_running']) {


            // Run comment exports
            if(in_array('comments', $export['options']['cpt'])) {
                wpae_run_comment_export($comment_id, $export);
            }

            // Run shop review exports
            if($post_parent->post_type === 'product' && in_array('shop_review', $export['options']['cpt'])) {
                wpae_run_comment_export($comment_id, $export);

            }
        }
    }
}

/**
 * @param $comment_id
 * @param $export
 */
function wpae_run_comment_export($comment_id, $export)
{
    if ($comment_id) {
        $exportRecord = new PMXE_Export_Record();
        $exportRecord->getById($export['id']);
        $exportRecord->execute(false, true, $comment_id);
    }
}