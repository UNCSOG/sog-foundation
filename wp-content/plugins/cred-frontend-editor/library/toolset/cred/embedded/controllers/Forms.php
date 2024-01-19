<?php

final class CRED_Forms_Controller extends CRED_Abstract_Controller {

    public function testNotification() {
        if (
                isset($_POST['cred_test_notification_form_id']) &&
                isset($_POST['cred_test_notification_data'])
        //&& verify nonce
        ) {
			$notification = $_POST['cred_test_notification_data'];
			// Remove slashes from the POSTed data
			// so shortcodes with attributs do work
			$notification = wp_unslash( $notification );
            $form_id = intval($_POST['cred_test_notification_form_id']);

            $results = CRED_Notification_Manager::get_instance()->sendTestNotification($form_id, $notification);
            echo json_encode($results);
            die();
        }
        echo json_encode(array('error' => 'not allowed'));
        die();
    }

    public function suggestUserMail($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        $current_blog_id = get_current_blog_id();
        $search = cred_wrap_esc_like($post['user']);
        $args = array(
            'blog' => $current_blog_id,
            'search' => "*" . $search . "*",
            'search_columns' => array('user_nicename'),
            'fields' => array('user_nicename','user_email')
        );
        $user_query = new WP_User_Query($args);
        $results = $user_query->get_results();

        $new_results = array();
        $count = 0;
        foreach ($results as $result) {
            $new_results[$count] = new stdClass();
            $new_results[$count]->label = $result->user_nicename;
            $new_results[$count]->value = $result->user_email;
            $count++;
        }
        unset($results);
        echo json_encode($new_results);
        die;
    }

    public function updateFormFields($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        $form_id = intval($post['form_id']);
        $fields = $post['fields'];
        $fm = CRED_Loader::get('MODEL/Forms');
        $fm->updateFormCustomFields($form_id, $fields);

        echo json_encode(true);
        die();
    }

    public function updateFormField($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (!isset($post['_wpnonce']) ||
                !wp_verify_nonce($post['_wpnonce'], '_cred_wpnonce')) {
            echo json_encode("wpnonce error");
            die();
        }

        if (!isset($post['form_id'])) {
            echo json_encode(false);
            die();
        }

        $form_id = intval($post['form_id']);
        $field = sanitize_text_field($post['field']);
        $value = sanitize_text_field($post['value']);
        $fm = CRED_Loader::get('MODEL/Forms');
        $fm->updateFormCustomField($form_id, $field, $value);

        echo json_encode(true);
        die();
    }

    public function getFormFields($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (!isset($post['form_id'])) {
            die();
        }
        $form_id = intval($post['form_id']);
        $fm = CRED_Loader::get('MODEL/Forms');
        $fields = $fm->getFormCustomFields($form_id);

        echo json_encode($fields);
        die();
    }

    public function getFormField($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (!isset($post['form_id'])) {
            die();
        }
        $form_id = intval($post['form_id']);
        $field = sanitize_text_field($post['field']);
        $fm = CRED_Loader::get('MODEL/Forms');
        $value = $fm->getFormCustomField($form_id, $field);

        echo json_encode($value);
        die();
    }

    // export forms to XML and download
    public function exportForm($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (isset($get['form']) && isset($get['_wpnonce'])) {
            if (wp_verify_nonce($get['_wpnonce'], 'cred-export-' . $get['form'])) {
                CRED_Loader::load('CLASS/XML_Processor');
                //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196173458/comments
                //Security Fix added validate_file and sanitize
                $filename = isset($get['filename']) && validate_file($get['filename']) ? urldecode($get['filename']) : '';
                if (isset($get['type']) && $get['type'] == 'user')
                    CRED_XML_Processor::exportUsersToXML(array($get['form']), isset($get['ajax']), $filename);
                else
                    CRED_XML_Processor::exportToXML(array($get['form']), isset($get['ajax']), $filename);
                die();
            }
        }
        die();
    }

    public function exportSelected($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (isset($_REQUEST['checked']) && is_array($_REQUEST['checked'])) {
            check_admin_referer('cred-bulk-selected-action', 'cred-bulk-selected-field');
            CRED_Loader::load('CLASS/XML_Processor');
            //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196173458/comments
            //Security Fix added validate_file and sanitize
            $filename = isset($_REQUEST['filename']) && validate_file($_REQUEST['filename']) ? urldecode($_REQUEST['filename']) : '';
            if (isset($get['type']) && $get['type'] == 'user')
                CRED_XML_Processor::exportUsersToXML((array) $_REQUEST['checked'], isset($get['ajax']), $filename);
            else
                CRED_XML_Processor::exportToXML((array) $_REQUEST['checked'], isset($get['ajax']), $filename);
            die();
        }
        die();
    }

    public function exportAll($get, $post) {
        if (!current_user_can(CRED_CAPABILITY))
            wp_die();

        if (isset($get['all']) && isset($get['_wpnonce'])) {
            if (wp_verify_nonce($get['_wpnonce'], 'cred-export-all')) {
                CRED_Loader::load('CLASS/XML_Processor');
                //https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/196173458/comments
                //Security Fix added validate_file and sanitize
                $filename = isset($get['filename']) && validate_file($get['filename']) ? urldecode($get['filename']) : '';
                if (isset($get['type']) && $get['type'] == 'user')
                    CRED_XML_Processor::exportUsersToXML('all', isset($get['ajax']), $filename);
                else
                    CRED_XML_Processor::exportToXML('all', isset($get['ajax']), $filename);
                die();
            }
        }
        die();
    }

}
