<?php

/* ***************************************************************
Options Page 
**************************************************************** */
 
function wdm_wpma_actionlinks ( $actions ) {
    $links = array(
       '<a href="'.admin_url('admin.php?page=wdm_wpma_mail_queue').'">Settings</a>',
       '<a href="'.admin_url('admin.php?page=wdm_wpma_mail_queue-tab-log').'">Log</a>',
       '<a href="'.admin_url('admin.php?page=wdm_wpma_mail_queue-tab-queue').'">Queue</a>',
       '<a href="'.admin_url('admin.php?page=wdm_wpma_mail_queue-tab-faq').'">FAQs</a>',
    );
    return array_merge($actions,$links );
}
add_filter('plugin_action_links_mail-queue/mail-queue.php','wdm_wpma_actionlinks');

// Options Page
function wdm_wpma_settings_page_menuitem() {
    add_menu_page('Mail Queue','Mail Queue','manage_options','wdm_wpma_mail_queue','wdm_wpma_settings_page','data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTc4IiBoZWlnaHQ9IjE3OCIgdmlld0JveD0iMCAwIDE3OCAxNzgiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNNzguNzI0MSA1LjI1MzA5Qzc2LjkwNzQgNC43MDgxIDc0Ljk0MDEgNS4wNTQxMyA3My40MTg0IDYuMTg2MjlDNzEuODk2OCA3LjMxODQ1IDcxIDkuMTAzNDEgNzEgMTEuMDAwMVYxNjdDNzEgMTY4Ljg5NyA3MS44OTY4IDE3MC42ODIgNzMuNDE4NCAxNzEuODE0Qzc0Ljk0MDEgMTcyLjk0NiA3Ni45MDc0IDE3My4yOTIgNzguNzI0MSAxNzIuNzQ3TDE1OC43MjQgMTQ4Ljc0N0MxNjEuMjYyIDE0Ny45ODYgMTYzIDE0NS42NSAxNjMgMTQzVjM1QzE2MyAzMi4zNTA0IDE2MS4yNjIgMzAuMDE0NSAxNTguNzI0IDI5LjI1MzFMNzguNzI0MSA1LjI1MzA5Wk04NS43ODg3IDIyLjM4NDZDODcuNzg1NCAyMS40Mzk0IDkwLjE3MDMgMjIuMjkxOSA5MS4xMTU0IDI0LjI4ODdMMTIyLjg1MiA5MS4zMzc4TDE0My4zMzQgNDQuNDAwMkMxNDQuMjE3IDQyLjM3NTUgMTQ2LjU3NSA0MS40NTAzIDE0OC42IDQyLjMzMzhDMTUwLjYyNSA0My4yMTc0IDE1MS41NSA0NS41NzUgMTUwLjY2NiA0Ny41OTk4TDEyNi42NjYgMTAyLjZDMTI2LjAzOSAxMDQuMDM3IDEyNC42MjkgMTA0Ljk3NiAxMjMuMDYxIDEwNUMxMjEuNDkzIDEwNS4wMjQgMTIwLjA1NiAxMDQuMTI5IDExOS4zODUgMTAyLjcxMUw4My44ODQ2IDI3LjcxMTNDODIuOTM5NCAyNS43MTQ2IDgzLjc5MTkgMjMuMzI5NyA4NS43ODg3IDIyLjM4NDZaIiBmaWxsPSIjYTdhYWFkIi8+CjxwYXRoIGQ9Ik00OSAxM0M1Mi4zMTM3IDEzIDU1IDE1LjY4NjMgNTUgMTlWMTU5QzU1IDE2Mi4zMTQgNTIuMzEzNyAxNjUgNDkgMTY1QzQ1LjY4NjMgMTY1IDQzIDE2Mi4zMTQgNDMgMTU5VjE5QzQzIDE1LjY4NjMgNDUuNjg2MyAxMyA0OSAxM1oiIGZpbGw9IiNhN2FhYWQiLz4KPHBhdGggZD0iTTIxIDIxQzI0LjMxMzcgMjEgMjcgMjMuNjg2MyAyNyAyN1YxNTFDMjcgMTU0LjMxNCAyNC4zMTM3IDE1NyAyMSAxNTdDMTcuNjg2MyAxNTcgMTUgMTU0LjMxNCAxNSAxNTFWMjdDMTUgMjMuNjg2MyAxNy42ODYzIDIxIDIxIDIxWiIgZmlsbD0iI2E3YWFhZCIvPgo8L3N2Zz4=');
    add_submenu_page('wdm_wpma_mail_queue','Settings','Settings','manage_options','wdm_wpma_mail_queue','wdm_wpma_settings_page');
    add_submenu_page('wdm_wpma_mail_queue','Log','Log','manage_options','wdm_wpma_mail_queue-tab-log','wdm_wpma_settings_page');
    add_submenu_page('wdm_wpma_mail_queue','Queue','Queue','manage_options','wdm_wpma_mail_queue-tab-queue','wdm_wpma_settings_page');
    add_submenu_page('wdm_wpma_mail_queue','FAQ','FAQ','manage_options','wdm_wpma_mail_queue-tab-faq','wdm_wpma_settings_page');
    if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
        add_submenu_page('wdm_wpma_mail_queue','Cron Information','Cron Information','manage_options','wdm_wpma_mail_queue-tab-croninfo','wdm_wpma_settings_page');
    }
}
add_action('admin_menu','wdm_wpma_settings_page_menuitem');

function wdm_wpma_settings_page_assets () {
    $screen = get_current_screen();
    if ( preg_match( '#wdm_wpma_mail_queue#', $screen->base ) ) {
        wp_enqueue_style( 'wdm_wpma_style', plugins_url( 'assets/css/admin.css', __FILE__ ) );
        wp_enqueue_script( 'wdm_wpma_script', plugins_url( 'assets/js/wdm-wpma-admin.js', __FILE__ ), [ 'jquery' ], false, true );
		wp_add_inline_script( 'wdm_wpma_script', wdm_wpma_settings_page_inline_script(), 'before' );
    }
}
add_action( 'admin_enqueue_scripts', 'wdm_wpma_settings_page_assets' );

// Options Page Script
function wdm_wpma_settings_page_inline_script () {
    $d  = '';
    $d .= '<script>';
    $d .= '( function ( global ) {';
    $d .=   '"use strict";';
    $d .=   'const wpma = global.wpma = global.wpma || {};';
    $d .=   'wpma.restUrl = "'.esc_url( wp_make_link_relative( rest_url() ) ).'";';
    $d .=   'wpma.restNonce = "'.esc_html( wp_create_nonce( 'wp_rest' ) ).'";';
    $d .= '}) ( this );';
    $d .= '</script>';
    return $d;
}

// Options Page Settings
function wdm_wpma_settings_page() {

    // Only Admins
    if ( ! current_user_can( 'manage_options' ) ) { return; }

    // Settings
    global $wdm_wpma_options;

    // Get the active tab from the $_GET param
    $default_tab = null;
    $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default_tab;

    echo '<div class="wrap">';

    // Options Header
    echo '<h1 class="wdm-wpma-title"><img class="wdm-wpma-logo" src="'.esc_url(plugins_url('assets/img/mail-queue-logo-wordmark.svg', __FILE__)).'" alt="Mail Queue" width="308" height="56" /></h1>';

    $tab = sanitize_key($_GET['page']);
    if ($tab != 'wdm_wpma_mail_queue-tab-croninfo' ) {
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            echo '<div class="notice notice-warning notice-large">';
            $url = esc_url(get_option('siteurl').'/wp-cron.php');
            echo '<p><strong>Please note:</strong><br />Your normal WP Cron is disabled. Please make sure you\'re running the Cron manually by calling <a href="'.$url.'" target="_blank">'.$url.'</a> every couple of minutes.</p>';
            echo '<p><a href="?page=wdm_wpma_mail_queue-tab-croninfo">More information</a></p>';
            echo '</div>';
        }
    }
    wdm_wpma_settings_page_navi($tab); // Tabs
 
    // Options Page Content
    if ($tab == 'wdm_wpma_mail_queue') {
        echo '<form action="options.php" method="post">';
        settings_fields('wdm_wpma_settings');
        do_settings_sections('wdm_wpma_settings_page');
        submit_button();
        echo '</form>';
    } else if ($tab == 'wdm_wpma_mail_queue-tab-log') {      
        echo '<form method="post">';          
        $logtable = new wdm_wpma_Log_Table();
        $logtable->prepare_items(); 
        $logtable->display();
        echo '</form>';
    } else if ($tab == 'wdm_wpma_mail_queue-tab-queue') {

        if (isset($_GET['addtestmail'])) {
            global $wpdb;
            $tableName = $wpdb->prefix.$wdm_wpma_options['tableName'];
            $data = array(
                'timestamp'=>current_time('mysql',false),
                'recipient'=>$wdm_wpma_options['email'],
                'subject'=>'Testmail #'.time(),
                'message'=>'This is just a test email send by the Mail Queue plugin.',
                'status' => 'queue'
            );
            $wpdb->insert($tableName,$data);
        }
 
        $next_cron_timestamp = wp_next_scheduled('wp_mail_queue_hook');
        if ($next_cron_timestamp) {
            if ($next_cron_timestamp > time()) {
                echo '<div class="notice notice-success"><p>Next Sending will be triggered in '.esc_html(human_time_diff($next_cron_timestamp)).' at '.esc_html(wp_date('H:i',$next_cron_timestamp)).'.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>The Queue is not enabled at the moment. Enable it in the <a href="admin.php?page=wdm_wpma_mail_queue">Settings</a>.</p></div>';
        }

        echo '<form method="post" action="admin.php?page=wdm_wpma_mail_queue-tab-queue">';
        $queuetable = new wdm_wpma_Log_Table();
        $queuetable->prepare_items(); 
        $queuetable->display();
        echo '</form>';
    } else if ($tab == 'wdm_wpma_mail_queue-tab-faq') {
        echo '<div class="wdm-wpma-box">';
        echo '<h3>How does this Plugin work?</h3>';
        echo '<p>If enabled this plugin intercepts the <a target="_blank" href="https://developer.wordpress.org/reference/functions/wp_mail/"><i>wp_mail()</i></a> function. Instead of sending the mails directly, it stores them in the database and sends them step by step with a delay during the <i>WP Cron</i>.</p>';
        echo '<p>Current state: ';
        if ($wdm_wpma_options['enabled'] == '1') {
            echo '<b class="wdm-wpma-ok">The plugin is enabled</b> All Mails sent through <a target="_blank" href="https://developer.wordpress.org/reference/functions/wp_mail/"><i>wp_mail()</i></a> are delayed by the <a href="admin.php?page=wdm_wpma_mail_queue-tab-queue">Queue</a>.';
        } else {
            echo '<b>The plugin is disabled</b>. The plugin has no impact at the moment, no Mails inside the Queue are going to be sent.';
        }
        echo '</p>';
        echo '</div>';
        echo '<div class="wdm-wpma-box">';
        echo '<h3>Does this plugin change the way <b>HOW</b> emails are sent?</h3>';
        echo '<p>No, don\'t worry. This plugin only affects <b>WHEN</b> emails are sent, not how. It delays the sending (by the Queue), nonetheless all emails are sent through the standard <a target="_blank" href="https://developer.wordpress.org/reference/functions/wp_mail/"><i>wp_mail()</i></a> function.</p>';
        echo '<p>If you use SMTP for sending, or an external service like Mailgun, everything will still work as expected.</p>';
        echo '</div>';
        echo '<div class="wdm-wpma-box">';
        echo '<h3>Does this plugin work, if I have a Caching Plugin installed? E.g. <i>W3 Total Cache</i> or similar?</h3>';
        echo '<p>If you\'re using a Caching plugin like <i>W3 Total Cache</i>, <i>WP Rocket</i> or any other caching solution which generates static html-files and serves them to visitors, you\'ll have to make sure you\'re calling the <a href="'.esc_url(get_option('siteurl')).'/wp-cron.php" target="_blank">wp-cron file</a> manually every couple of minutes.</p>';
        echo '<p>Otherwise your normal WP Cron wouldn\'t be called as often as it should be and scheduled messages would be sent with big delays.</p>';
        echo '</div>';
        echo '<div class="wdm-wpma-box">';
        echo '<h3>What about Proxy-Caching, e.g. NGINX?</h3>';
        echo '<p>Same situation here. Please make sure you\'re calling the <a href="'.esc_url(get_option('siteurl')).'/wp-cron.php" target="_blank">WordPress Cron</a> by an external service or your webhoster every couple of minutes.</p>';
        echo '</div>';
        echo '<div class="wdm-wpma-box">';
        echo '<h3>My form builder supports attachments. What about them?</h3>';
        echo '<p>You are covered. All attachments are stored temporarily in the queue until they are sent along with their corresponding emails.</p>';
        echo '</div>';
        echo '<div class="wdm-wpma-box">';
        echo '<h3>What are Queue alerts?</h3>';
        echo '<p>This is a simple and effective way to improve the security of your WordPress installation.</p><p>Imagine: In case your website is sending spam through <a target="_blank" href="https://developer.wordpress.org/reference/functions/wp_mail/"><i>wp_mail()</i></a>, the email Queue would fill up very quickly preventing your website from sending so many spam emails at once. This gives you time and avoids a lot of trouble.</p><p>Queue Alerts warn you, if the Queue is longer than usal. You decide at which point you want to be alerted. So you get the chance to have a look if there might be something wrong on the website.</p>';
        echo '<p>Current state: ';
        if ($wdm_wpma_options['alert_enabled'] == '1') {
            echo '<b class="wdm-wpma-ok">Alerts are enabled</b> If more than '.esc_html($wdm_wpma_options['email_amount']).' emails are waiting in the Queue, WordPress will sent an alert email to <i>'.esc_html($wdm_wpma_options['email']).'</i>.';
        } else {
            echo '<b>Alerting is disabled</b>. No alerts will be sent.';
        }
        echo '</p>';
        echo '<p>Please note: This plugin will only send one alert every six hours.</p>';
        echo '</div>';

        echo '<div class="wdm-wpma-box">';
            echo '<h3>Can I add emails with a high priority to the queue?</h3>';
            echo '<p>Yes, you can add the custom <i>`X-Mail-Queue-Prio`</i> header set to <i>`High`</i> to your email. High priority emails will be sent through the standard Mail Queue sending cycle but before all normal emails lacking a priority header in the queue.</p>';
            echo '<p><b>Example 1 (add priority to Woocommerce emails):</b></p>';
            echo '<pre><code>add_filter(\'woocommerce_mail_callback_params\',function ( $array ) {
    $prio_header = \'X-Mail-Queue-Prio: High\';
    if (is_array($array[3])) {
        $array[3][] = $prio_header;
    } else {
        $array[3] .= $array[3] ? "\r\n" : \'\';
        $array[3] .= $prio_header;
    }
    return $array;
},10,1);</code></pre>';
            echo '<p><b>Example 2 (add priority to Contact Form 7 form emails):</b></p>';
            echo '<p>When editing a form in Contact Form 7 just add an additional line to the <i>`Additional Headers`</i> field under the <i>`Mail`</i> tab panel.</p>';
            echo '<pre><code>X-Mail-Queue-Prio: High</code></pre>';
            echo '<p><b>Example 3 (add priority to WordPress reset password emails):</b></p>';
            echo '<pre><code>add_filter(\'retrieve_password_notification_email\', function ($defaults, $key,$user_ login, $user_data) {
    $prio_header = \'X-Mail-Queue-Prio: High\';
    if (is_array($defaults[\'headers\'])) {
        $defaults[\'headers\'][] = $prio_header;
    } else {
        $defaults[\'headers\'] .= $defaults[\'headers\'] ? "\r\n" : \'\';
        $defaults[\'headers\'] .= $prio_header;
    }
    $return $defaults;
}, 10, 4);</code></pre>';
        echo '</div>';
           
        echo '<div class="wdm-wpma-box">';
            echo '<h3>Can I send emails <i>instantly</i> without going through the queue?</h3>';
            echo '<p>Yes, this is possible (if you absolutely need to do this).</p>';
            echo '<p>For this you can add the custom <i>`X-Mail-Queue-Prio`</i> header set to <i>`Instant`</i> to your email. These emails are sent instantly circumventing the mail queue. They still appear in the Mail Queue log flagged as `instant`.</p>';
            echo '<p>Mind that this is a potential security risk and should be considered carefully. Please use only as an exception.</p>';
            echo '<p><b>Example 1 (instantly send Woocommerce emails):</b></p>';
            echo '<pre><code>add_filter(\'woocommerce_mail_callback_params\',function ( $array ) {
    $prio_header = \'X-Mail-Queue-Prio: Instant\';
    if (is_array($array[3])) {
        $array[3][] = $prio_header;
    } else {
        $array[3] .= $array[3] ? "\r\n" : \'\';
        $array[3] .= $prio_header;
    }
    return $array;
},10,1);</code></pre>';
            echo '<p><b>Example 2 (instantly send Contact Form 7 form emails):</b></p>';
            echo '<p>When editing a form in Contact Form 7 just add an additional line to the <i>`Additional Headers`</i> field under the <i>`Mail`</i> tab panel.</p>';
            echo '<pre><code>X-Mail-Queue-Prio: Instant</code></pre>';
            echo '<p><b>Example 3 (instantly send WordPress reset password emails):</b></p>';
            echo '<pre><code>add_filter(\'retrieve_password_notification_email\', function ($defaults, $key,$user_ login, $user_data) {
    $prio_header = \'X-Mail-Queue-Prio: Instant\';
    if (is_array($defaults[\'headers\'])) {
        $defaults[\'headers\'][] = $prio_header;
    } else {
        $defaults[\'headers\'] .= $defaults[\'headers\'] ? "\r\n" : \'\';
        $defaults[\'headers\'] .= $prio_header;
    }
    $return $defaults;
}, 10, 4);</code></pre>';
        echo '</div>';

        echo '<div class="wdm-wpma-box">';
        echo '<h3>Want to put a test email into the Queue?</h3>';
        echo '<p><a class="button" href="admin.php?page=wdm_wpma_mail_queue-tab-queue&addtestmail">Sure! Put a Test Email for '.esc_html($wdm_wpma_options['email']).' into the Queue</a></p>';
        echo '</div>';
         
    } else if ($tab == 'wdm_wpma_mail_queue-tab-croninfo') {
        echo '<div class="wdm-wpma-box">';
            echo '<h3>Information: Your common WP Cron is disabled</h3>';
            echo '<p>It look\'s like you deactived the WP Cron by <i>define( \' DISABLE_WP_CRON \' , true )</i>.</p>';
            $url = esc_url(get_option('siteurl').'/wp-cron.php');
            echo '<p>In general, this is no problem at all. We just want to remind you to make sure you\'re running the Cron manually by calling <a href="'.$url.'" target="_blank">'.$url.'</a> every couple of minutes.</p>';
        echo '</div>';

        
            if (function_exists('_get_cron_array')) {
                $next_tasks = _get_cron_array();
                if ($next_tasks) {
                    $tasks_in_past = false;
                    $tasks_of_mailqueue_in_past = false;
                    foreach($next_tasks as $key => $val) {
                        if (time() > intval($key) + intval($wdm_wpma_options['queue_interval'])) {
                            if (array_keys($val)[0] == 'wp_mail_queue_hook') { $tasks_of_mailqueue_in_past = intval($key); }
                            $tasks_in_past = true;
                        }
                    }
                    if ($tasks_in_past) {
                        echo '<div class="wdm-wpma-box">';
                            echo '<h3>Attention: It seams that your WP Cron is not running. There are some jobs waiting to be completed.</h3>';
                            if ($tasks_of_mailqueue_in_past) {
                                echo '<p><body>The Queue wasn\'t been able to be executed since '.esc_html(human_time_diff($tasks_of_mailqueue_in_past,time())).'.</b></p>';
                            }
                        echo '</div>';
                    }
                }
            }
            
        
    }

    echo '</div>';

}
 
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/screen.php' );
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
 
class wdm_wpma_Log_Table extends WP_List_Table {

    function get_log() {
        global $wpdb, $wdm_wpma_options;
        $tableName = $wpdb->prefix.$wdm_wpma_options['tableName'];
        return $wpdb->get_results("SELECT * FROM `$tableName` WHERE `status` != 'queue' AND `status` != 'high' ORDER BY `timestamp` DESC",'ARRAY_A');
    }

    function get_queue() {
        global $wpdb, $wdm_wpma_options;
        $tableName = $wpdb->prefix.$wdm_wpma_options['tableName'];
        return $wpdb->get_results("SELECT * FROM `$tableName` WHERE `status` = 'queue' OR `status` = 'high' ORDER BY `status` ASC, `timestamp` ASC",'ARRAY_A');
    }

    function get_columns() {
        $columns = array(
            'cb'          => '<label><span class="screen-reader-text">Select all</span><input class="wdm-wpma-select-all" type="checkbox"></label>',
            'timestamp'   => 'Time',
            'status'      => 'Status',
            'recipient'   => 'Recipient',
            'subject'     => 'Subject',
            'message'     => 'Message',
            'headers'     => 'Headers',
            'attachments' => 'Attachments',
        );
        return $columns;
    }
 
    public function prepare_items() {
        $type = sanitize_key($_GET['page']);
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
        if ($type == 'wdm_wpma_mail_queue-tab-log') {
            $data = $this->get_log();
        } else if ($type == 'wdm_wpma_mail_queue-tab-queue') {
            $data = $this->get_queue();
        }
        if ($data && is_array($data)) {
            $perPage = 50;
            $currentPage = $this->get_pagenum();
            $totalItems = count($data);
            $this->set_pagination_args( array(
                'total_items' => $totalItems,
                'per_page'    => $perPage
            ) );
            $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        }
        $this->items = $data;
    }
 
    public function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'timestamp':
            case 'subject':
            case 'info':
                return esc_html( maybe_unserialize($item[$column_name]) );
                break;
            case 'recipient':
            case 'headers':
                $return = maybe_unserialize($item[$column_name]);
                if (is_array($return)) { 
                    return esc_html( implode(',',$return) );
                } else {
                    return esc_html( $return );
                }
                break;
            case 'attachments':
                $return = maybe_unserialize($item[$column_name]);
                //var_dump($return);
                if (is_array($return)) { 
                    $betterreturn = array();
                    foreach($return as $item) {
                        array_push($betterreturn,basename($item));
                    }
                    return esc_html( implode('<br />',$betterreturn) );
                } else {
                    return esc_html( basename($return) );
                }
                break;
            case 'status':
                $info = isset( $item[ 'info' ] ) && $item[ 'info' ] ? $item[ 'info' ] : '';
                if ( $item[$column_name] === 'alert' ) {
                    $alertData = json_decode($info,/*associative*/true);
                    if ( $alertData ) {
                        $info =  '<strong>Emails in queue</strong>: '.esc_html($alertData['in_queue']);
                        $info .= '<br /><strong>Queue settings</strong>: Send max '.esc_html($alertData['queue_amount']).' email(s) every '.esc_html($alertData['queue_interval']/60).' minute(s).';
                        $info .= '<br /><strong>Alert settings</strong>: Send alert if more than '.esc_html($alertData['email_amount']).' email(s) in the queue.';
                    } else {
                        $info = '';
                    }
                }
                $htmlInfo = $info ? '<span class="wdm-wpma-info">'.$info.'</span>' : '';
                $cssStatus = $htmlInfo ? ' wdm-wpma-status-has-info' : '';
                return '<span class="wdm-wpma-status wdm-wpma-status-'.sanitize_title($item[$column_name]).esc_html($cssStatus).'">'.$item[$column_name].$htmlInfo.'</span>';
                break;
            case 'message':          
                $message = $item[$column_name];
                if ( $message ) {
                    $messageLen = strlen($message);
                    $return  = '<details>';
                    $return .=   '<summary class="wdm-wpma-view-source" data-wdm-wpma-list-message-toggle="'.esc_attr($item['id']).'">View message <i>('.esc_html($messageLen).' bytes)</i></summary>';
                    $return .=   '<div class="wdm-wpma-email-source" data-wdm-wpma-list-message-content>Loading …</div>';
                    $return .= '</details>';
                } else {
                    $return = '<em>Empty</em>';
                }
                return $return;
                break;
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

    protected function column_cb ( $item ) {
        return '<input type="checkbox" name="id[]" value="'.esc_attr($item['id']).'" />';
    }
 
    public function get_bulk_actions() {
        if (isset($_GET['page']) && $_GET['page'] == 'wdm_wpma_mail_queue-tab-queue') {
            $actions = array(
                'delete' => __( 'Delete')
            );
        } else {
            $actions = array(
                'delete' => __( 'Delete'),
                'resend' => __( 'Resend'),
            );
        }
        
        return $actions;
    }
 
    public function process_bulk_action() {       
         
        // security check!
        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
            $nonce  = sanitize_key( $_POST['_wpnonce'] );
            $action = 'bulk-' . $this->_args['plural'];
            if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Nope! Security check failed!' );
        }

        // get IDs
        $request_ids = isset( $_REQUEST['id'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['id'] ) ) : array();
        if ( empty( $request_ids ) ) { return; }

        global $wpdb, $wdm_wpma_options;
        $tableName = $wpdb->prefix.$wdm_wpma_options['tableName'];

        switch ( $this->current_action() ) {
            case 'delete':
                foreach($request_ids as $id) {
                    $wpdb->delete($tableName,array('id'=>intval($id)),'%d');
                }                
                break;
            case 'resend':
                foreach($request_ids as $id) {
                    $count_resend = 0;
                    $count_error  = 0;
                    $maildata = $wpdb->get_row("SELECT * FROM `$tableName` WHERE `id` = '$id'");
                    if (!$maildata->attachments || $maildata->attachments == '') {
                        $count_resend++;
                        $data = array(
                            'timestamp'=> current_time('mysql',false),
                            'recipient'=> $maildata->recipient,
                            'subject'=> $maildata->subject,
                            'message'=> $maildata->message,
                            'status' => 'queue',
                            'attachments' => '',
                            'headers' => $maildata->headers,
                        );
                        $wpdb->insert($tableName,$data);
                    } else {
                        $count_error++;
                        $notice = '<div class="notice notice-error is-dismissible">';
                        $notice .= '<p><b>Sorry, your email to '.$maildata->recipient.' can\'t be sent again.</b></p>';
                        $notice .= '<p>The email used to have attachments, which are not available anymore. Only emails without attachments can be resend.</p>';
                        $notice .= '</div>';
                        echo $notice;
                    }
                }
                if ($count_error == 0 && $count_resend > 0) {
                    wp_redirect('admin.php?page=wdm_wpma_mail_queue-tab-queue');
                    exit;
                } else if ($count_error > 0 && $count_resend > 0) {
                    $notice = '<div class="notice notice-success is-dismissible">';
                    $notice .= '<p>The other emails have been put again into the <a href="admin.php?page=wdm_wpma_mail_queue-tab-queue">Queue</a>.</p>';
                    $notice .= '</div>';
                    echo $notice;
                }              
                break;
        }

        return;
 
    }
 
}
 
function wdm_wpma_settings_page_navi($tab) {
    echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=wdm_wpma_mail_queue" class="nav-tab'; if($tab==='wdm_wpma_mail_queue') { echo ' nav-tab-active'; } echo '">Settings</a>';
        echo '<a href="?page=wdm_wpma_mail_queue-tab-log" class="nav-tab'; if($tab==='wdm_wpma_mail_queue-tab-log') { echo ' nav-tab-active'; } echo '">Log</a>';
        echo '<a href="?page=wdm_wpma_mail_queue-tab-queue" class="nav-tab'; if($tab==='wdm_wpma_mail_queue-tab-queue') { echo ' nav-tab-active'; } echo '">Queue</a>';
        echo '<a href="?page=wdm_wpma_mail_queue-tab-faq" class="nav-tab'; if($tab==='wdm_wpma_mail_queue-tab-faq') { echo ' nav-tab-active'; } echo '">FAQ</a>';
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            echo '<a href="?page=wdm_wpma_mail_queue-tab-croninfo" class="nav-tab'; if($tab==='wdm_wpma_mail_queue-tab-croninfo') { echo ' nav-tab-active'; } echo '">Cron Information</a>';
        }
    echo '</nav>';
}
 
function wdm_wpma_settings_init() {
    global $wdm_wpma_options;
    register_setting('wdm_wpma_settings','wdm_wpma_settings');
    add_settings_section('wdm_wpma_settings_section','',null,'wdm_wpma_settings_page');
    add_settings_field('wdm_wpma_status','Queue + Log enabled','wdm_wpma_render_option_status','wdm_wpma_settings_page','wdm_wpma_settings_section');
    add_settings_field('wdm_wpma_queue','Queue','wdm_wpma_render_option_queue','wdm_wpma_settings_page','wdm_wpma_settings_section');
    add_settings_field('wdm_wpma_log','Log','wdm_wpma_render_option_log','wdm_wpma_settings_page','wdm_wpma_settings_section');
    add_settings_field('wdm_wpma_alert_status','Alert enabled','wdm_wpma_render_option_alert_status','wdm_wpma_settings_page','wdm_wpma_settings_section');
    add_settings_field('wdm_wpma_sensitivity','Alert Sensitivity','wdm_wpma_render_option_sensitivity','wdm_wpma_settings_page','wdm_wpma_settings_section');
}
add_action('admin_init','wdm_wpma_settings_init');
 
function wdm_wpma_render_option_status() {
    global $wdm_wpma_options;
    if ($wdm_wpma_options['enabled'] == '1') {
        echo '<input type="checkbox" name="wdm_wpma_settings[enabled]" value="1" checked />';
    } else {
        echo '<input type="checkbox" name="wdm_wpma_settings[enabled]" value="1" /> &nbsp; &nbsp; <span class="wdm-wpma-warning"> &larr; Check this to enable the Queue. </span> Otherwise this plugin won\'t have any effect on your website.';
    }
}
 
function wdm_wpma_render_option_alert_status() {
    global $wdm_wpma_options;
    if ($wdm_wpma_options['alert_enabled'] == '1') {
        echo '<input type="checkbox" name="wdm_wpma_settings[alert_enabled]" value="1" checked />';
    } else {
        echo '<input type="checkbox" name="wdm_wpma_settings[alert_enabled]" value="1" />';
    }
}
 
function wdm_wpma_render_option_queue() {
    global $wdm_wpma_options;
    if ($wdm_wpma_options['queue_interval_unit'] == 'seconds') {
        $number = intval($wdm_wpma_options['queue_interval']);
    } else {
        $number = intval($wdm_wpma_options['queue_interval']) / 60;
    }
    
    echo 'Send max. <input name="wdm_wpma_settings[queue_amount]" type="number" min="1" value="'.esc_attr($wdm_wpma_options['queue_amount']).'" /> email(s) every <input name="wdm_wpma_settings[queue_interval]" type="number" min="1" value="'.esc_attr($number).'" />';
    
    if ($wdm_wpma_options['queue_interval_unit'] == 'seconds')  {
        echo '<select name="wdm_wpma_settings[queue_interval_unit]"><option value="minutes">minute(s)</option><option selected value="seconds">second(s)</option></select>';
    } else {
        echo '<select name="wdm_wpma_settings[queue_interval_unit]"><option selected value="minutes">minute(s)</option><option value="seconds">second(s)</option></select>';
    }
    
    echo ' by <i><a href="https://developer.wordpress.org/plugins/cron/" target="_blank">WP Cron</a></i>. ';
    
}

function wdm_wpma_render_option_log() {
    global $wdm_wpma_options;
    echo 'Delete Log entries older than <input name="wdm_wpma_settings[clear_queue]" type="number" min="1" value="'.esc_attr(intval($wdm_wpma_options['clear_queue']) / 24).'" /> days.';
}

function wdm_wpma_render_option_sensitivity() {
    global $wdm_wpma_options;
    echo 'Send alert to <input type="text" name="wdm_wpma_settings[email]" value="'.esc_attr(sanitize_email($wdm_wpma_options['email'])).'" /> if more than <input name="wdm_wpma_settings[email_amount]" type="number" min="1" value="'.esc_attr(intval($wdm_wpma_options['email_amount'])).'" /> email(s) in the <a href="admin.php?page=wdm_wpma_mail_queue-tab-queue">Queue</a>.';
}





/* ***************************************************************
Alert WordPress User if last email in log could not be sent
**************************************************************** */
function wdm_wpma_checkLogForErrors() {

    global $wpdb,$wdm_wpma_options;
    if ($wdm_wpma_options['enabled'] != '1') { return; }

    $tableName = $wpdb->prefix.$wdm_wpma_options['tableName'];
    $last_mail = $wpdb->get_row("SELECT * FROM `$tableName` WHERE `status` != 'queue' ORDER BY `id` DESC",'ARRAY_A');
    if (!$last_mail) { return; }

    if ($last_mail['status'] == 'error') {
        if (current_user_can('manage_options')) {
            $notice = '<div class="notice notice-error is-dismissible">';
            $notice .= '<h1>Attention: Your website has problems sending e-mails</h1>';
            $notice .= '<p>This is an important message from your <i>Mail Queue</i> plugin. Please take a look at your <a href="admin.php?page=wdm_wpma_mail_queue-tab-log">Mail Log</a>. The last email(s) couldn\'t be sent properly.</p>';
            $notice .= '<p>Last error message was: <b>'.$last_mail['info'].'</b></p>';
            $notice .= '</div>';
            echo $notice;
        } else if (current_user_can('edit_posts')) {
            $notice = '<div class="notice notice-error is-dismissible">';
            $notice .= '<h1>Attention: Your website has problems sending e-mails</h1>';
            $notice .= '<p>Please contact your Administrator. It seems that WordPress is not able to send emails.</p>';
            $notice .= '<p>Last error message: <b>'.$last_mail['info'].'</b></p>';
            $notice .= '</div>';
            echo $notice;
        }
    }

    // notices for the plugin options page
    $currentScreen = get_current_screen();
    if ($currentScreen->base == 'toplevel_page_wdm_wpma_mail_queue') {
        $wpMailOmittingPlugins = [
            'mailpoet/mailpoet.php' => 'MailPoet',
        ];
        $wpMailOmittingPluginsInstalled = [];
        foreach (array_keys($wpMailOmittingPlugins) as $plugin) {
            if(is_plugin_active($plugin)) {
                $wpMailOmittingPluginsInstalled[] = $plugin;
            }
        }
        if (count($wpMailOmittingPluginsInstalled) > 0) {
            $notice  = '<div class="notice notice-warning is-dismissible">';
            $notice .=   '<p>';
            $notice .=     '<strong>Please note:</strong>';
            $notice .=     '<br />This plugin is not supported when using in combination with plugins that do not use the standard <i>wp_mail()</i> function.';
            $notice .=   '</p>';
            $notice .=   '<p>';
            $notice .=     'It seems you are using the following plugin(s) that do not use <i>wp_mail()</i>:';
            $notice .=     '<br />'.implode(', ', array_map(function($plugin) use ($wpMailOmittingPlugins) { return $wpMailOmittingPlugins[$plugin]; },$wpMailOmittingPluginsInstalled));
            $notice .=   '</p>';
            $notice .=   '<p><a href="'.get_admin_url(null,'admin.php?page=wdm_wpma_mail_queue-tab-faq').'">More information</a></p>';
            $notice .= '</div>';
            echo $notice;
        }
    }
}
add_action('admin_notices', 'wdm_wpma_checkLogForErrors');
