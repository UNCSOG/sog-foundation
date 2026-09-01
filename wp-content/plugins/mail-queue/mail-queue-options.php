<?php

if (!defined('ABSPATH')) { exit; }

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
    $hook_log   = add_submenu_page('wdm_wpma_mail_queue','Log','Log','manage_options','wdm_wpma_mail_queue-tab-log','wdm_wpma_settings_page');
    $hook_queue = add_submenu_page('wdm_wpma_mail_queue','Queue','Queue','manage_options','wdm_wpma_mail_queue-tab-queue','wdm_wpma_settings_page');
    add_submenu_page('wdm_wpma_mail_queue','FAQ','FAQ','manage_options','wdm_wpma_mail_queue-tab-faq','wdm_wpma_settings_page');
    if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
        add_submenu_page('wdm_wpma_mail_queue','Cron Information','Cron Information','manage_options','wdm_wpma_mail_queue-tab-croninfo','wdm_wpma_settings_page');
    }
    // Bulk actions run on load-{page}: that hook fires before admin-header.php produces
    // any output, so the post-action redirect (PRG) can still send headers safely.
    add_action('load-'.$hook_log,'wdm_wpma_handle_list_table_actions');
    add_action('load-'.$hook_queue,'wdm_wpma_handle_list_table_actions');
}
add_action('admin_menu','wdm_wpma_settings_page_menuitem');

// Add test email hook
function wdm_wpma_add_testmail_action() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Sorry, you are not allowed to access this page.' );
	}

	check_admin_referer( 'wdm_wpma_add_testmail' );

	global $wpdb;
	$wdm_wpma_options = wdm_wpma_options();

	$tableName = wdm_wpma_table();
	$data = array(
		'timestamp' => current_time('mysql',false),
		'recipient' => $wdm_wpma_options['email'],
		'subject'   => 'Testmail #'.time(),
		'message'   => 'This is just a test email sent by the Mail Queue plugin.',
		'status'    => 'queue',
	);
	$wpdb->insert($tableName,$data);

	wp_safe_redirect(admin_url('admin.php?page=wdm_wpma_mail_queue-tab-queue&testmail_added=1'));
	exit;
}
add_action('admin_post_wdm_wpma_add_testmail', 'wdm_wpma_add_testmail_action');

function wdm_wpma_settings_page_assets () {
    global $wdm_wpma_version;
    $screen = get_current_screen();
    if ( preg_match( '#wdm_wpma_mail_queue#', $screen->base ) ) {
        wp_enqueue_style( 'wdm_wpma_style', plugins_url( 'assets/css/admin.css', __FILE__ ), /*deps*/array(), /*ver*/$wdm_wpma_version );
        wp_enqueue_script( 'wdm_wpma_script', plugins_url( 'assets/js/wdm-wpma-admin.js', __FILE__ ), /*deps*/array( 'jquery' ), /*ver*/$wdm_wpma_version, /*in_footer*/true );
		wp_add_inline_script( 'wdm_wpma_script', wdm_wpma_settings_page_inline_script(), 'before' );
    }
}
add_action( 'admin_enqueue_scripts', 'wdm_wpma_settings_page_assets' );

// Options Page Script
function wdm_wpma_settings_page_inline_script () {
    $d  = '';
    $d .= '( function ( global ) {';
    $d .=   '"use strict";';
    $d .=   'const wpma = global.wpma = global.wpma || {};';
    $d .=   'wpma.restUrl = "'.esc_url( wp_make_link_relative( rest_url() ) ).'";';
    $d .=   'wpma.restNonce = "'.esc_html( wp_create_nonce( 'wp_rest' ) ).'";';
    $d .= '}) ( this );';
    return $d;
}

function wdm_wpma_get_pause_on_alert_notice_message() {
    if (!wdm_wpma_is_pause_on_alert_active()) { return ''; }

    return 'The Queue was paused automatically because an alert was triggered. It will stay paused until you change the status manually.';
}

function wdm_wpma_render_pause_on_alert_notice() {
    $message = wdm_wpma_get_pause_on_alert_notice_message();
    if (!$message) { return; }

    echo '<div class="notice notice-warning"><p><b>The Queue is currently auto-paused.</b> <br />'.esc_html($message).'</p></div>';
}

// Runs the list-table bulk actions on load-{page} of the Log and Queue pages,
// i.e. before any page output — see wdm_wpma_settings_page_menuitem().
function wdm_wpma_handle_list_table_actions() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $table = new wdm_wpma_Log_Table();
    $table->process_bulk_action();
}

// PRG helper: after a bulk action always redirect back to a plugin page, carrying
// the result counts as query args for wdm_wpma_render_bulk_action_notices().
function wdm_wpma_bulk_action_redirect( $page, $action, $count_ok, $count_error ) {
    $url = add_query_arg(
        array(
            'page'        => $page,
            'wpma_notice' => $action,
            'wpma_ok'     => intval($count_ok),
            'wpma_error'  => intval($count_error),
        ),
        admin_url('admin.php')
    );
    wp_safe_redirect( $url );
    exit;
}

// Renders the result notices after a bulk action (delete / resend / sendnow).
// The counts arrive as query args set by the PRG redirect in process_bulk_action().
function wdm_wpma_render_bulk_action_notices() {
    if ( ! isset($_GET['wpma_notice']) || ! isset($_GET['page']) ) { return; }
    if ( strpos( sanitize_key($_GET['page']), 'wdm_wpma_mail_queue' ) !== 0 ) { return; }
    if ( ! current_user_can( 'manage_options' ) ) { return; }

    $action = sanitize_key($_GET['wpma_notice']);
    $ok     = isset($_GET['wpma_ok'])    ? max( 0, intval($_GET['wpma_ok']) )    : 0;
    $error  = isset($_GET['wpma_error']) ? max( 0, intval($_GET['wpma_error']) ) : 0;

    $success = '';
    $failure = '';
    if ($action == 'delete') {
        if ($ok > 0) { $success = $ok.' item(s) have been deleted.'; }
    } else if ($action == 'resend') {
        if ($ok > 0) { $success = $ok.' email(s) have been put again into the <a href="admin.php?page=wdm_wpma_mail_queue-tab-queue">Queue</a>.'; }
        // Covers both skip reasons: attachments gone, and rows that aren't resendable
        // at all (already queued/sending, queue bookkeeping, or no longer existing).
        if ($error > 0) { $failure = $error.' email(s) could not be resent. Only already sent or failed emails without attachments can be resent (attachments are deleted after the send attempt).'; }
    } else if ($action == 'sendnow') {
        if ($ok > 0) { $success = $ok.' email(s) have been sent.'; }
        if ($error > 0) { $failure = $error.' email(s) could not be sent.'; }
    }

    if ($success) { echo '<div class="notice notice-success is-dismissible"><p>'.$success.'</p></div>'; }
    if ($failure) { echo '<div class="notice notice-error is-dismissible"><p>'.esc_html($failure).'</p></div>'; }
}
add_action('admin_notices','wdm_wpma_render_bulk_action_notices');


// Options Page Settings
function wdm_wpma_settings_page() {

    // Only Admins
    if ( ! current_user_can( 'manage_options' ) ) { return; }

    // Settings
    $wdm_wpma_options = wdm_wpma_options();

    // The active tab is identified by the ?page=... URL parameter set by WordPress
    // for each registered admin page (see add_menu_page / add_submenu_page calls above).
    $tab = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

    echo '<div class="wrap">';

    // Options Header
    echo '<h1 class="wdm-wpma-title"><img class="wdm-wpma-logo" src="'.esc_url(plugins_url('assets/img/mail-queue-logo-wordmark.svg', __FILE__)).'" alt="Mail Queue" width="308" height="56" /></h1>';

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
        // method="get": search/filter/pagination state lives in the URL so it
        // survives pagination; the hidden page field keeps us on this tab.
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="wdm_wpma_mail_queue-tab-log" />';
        $logtable = new wdm_wpma_Log_Table();
        $logtable->prepare_items();
        $logtable->display();
        echo '</form>';
    } else if ($tab == 'wdm_wpma_mail_queue-tab-queue') {
        $next_cron_timestamp = wp_next_scheduled('wp_mail_queue_hook');
        if ($wdm_wpma_options['enabled'] === 'paused') {
            // The auto-pause case already shows a warning via wdm_wpma_render_pause_on_alert_notice()
            // (hooked to admin_notices, rendered above the page), so only the manual pause needs a notice here.
            if (!wdm_wpma_is_pause_on_alert_active()) {
                echo '<div class="notice notice-warning"><p><b>The Queue is currently paused.</b> <br />New emails enter the queue but are not being sent. Re-enable the Queue in the <a href="admin.php?page=wdm_wpma_mail_queue">Settings</a> to start sending emails again.</p></div>';
            }
        } else if ($next_cron_timestamp) {
            if ($next_cron_timestamp > time()) {
                echo '<div class="notice notice-success"><p>Next Sending will be triggered in '.esc_html(human_time_diff($next_cron_timestamp)).' at '.esc_html(wp_date('H:i',$next_cron_timestamp)).'.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p><b>The Queue is not enabled at the moment.</b> <br />Enable it in the <a href="admin.php?page=wdm_wpma_mail_queue">Settings</a>.</p></div>';
        }
        if ( isset($_GET['testmail_added']) ) {
            echo '<div class="notice notice-success is-dismissible"><p>The test email has been added to the queue.</p></div>';
        }

        // method="get": search/filter/pagination state lives in the URL so it
        // survives pagination; the hidden page field keeps us on this tab.
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="wdm_wpma_mail_queue-tab-queue" />';
        $queuetable = new wdm_wpma_Log_Table();
        $queuetable->prepare_items();
        $queuetable->display();
        echo '</form>';
    } else if ($tab == 'wdm_wpma_mail_queue-tab-faq') {
        echo '<div class="wdm-wpma-box">';
        echo '<h3>How does this Plugin work?</h3>';
        echo '<p>When the Mail Queue is <b>enabled</b>, this plugin intercepts the <a target="_blank" href="https://developer.wordpress.org/reference/functions/wp_mail/"><i>wp_mail()</i></a> function. Instead of sending emails immediately, it stores them in the database and sends them step by step with a delay during the <i>WP Cron</i>.</p>';
        echo '<p>The plugin offers three states:</p>';
        echo '<p><b>Enabled</b>: emails are added to the queue and sent gradually.<br />';
        echo '<b>Paused</b>: emails are still added to the queue, but no queued emails are sent until you enable the queue again.<br />';
        echo '<b>Disabled</b>: the plugin does not intercept emails and has no effect on outgoing mail.</p>';
        echo '<p>Current state: ';
        if ($wdm_wpma_options['enabled'] === '1') {
            echo '<b class="wdm-wpma-ok">The plugin is enabled</b> All emails sent through <a target="_blank" href="https://developer.wordpress.org/reference/functions/wp_mail/"><i>wp_mail()</i></a> are added to the <a href="admin.php?page=wdm_wpma_mail_queue-tab-queue">Queue</a> and sent gradually.';
        } elseif ($wdm_wpma_options['enabled'] === 'paused') {
            echo '<b class="wdm-wpma-warning">The plugin is paused</b>. New emails are still added to the queue, but queued emails are not being sent at the moment.';
        } else {
            echo '<b>The plugin is disabled</b>. The plugin currently has no effect on outgoing emails.';
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
        echo '<p>Queue alerts are a simple and effective way to improve the security of your WordPress installation.</p>';
        echo '<p>Imagine your website starts sending spam through <a target="_blank" href="https://developer.wordpress.org/reference/functions/wp_mail/"><i>wp_mail()</i></a>. The Mail Queue would fill up quickly instead of sending everything at once. This gives you time to react, avoid a lot of trouble and can reduce the damage significantly.</p>';
        echo '<p>Queue alerts warn you when the queue grows longer than usual. You configure in the settings at which threshold you want to be alerted. This gives you the chance to review the queue and investigate whether something unusual is happening on the website.</p>';
        echo '<p>Current state: ';
        if ($wdm_wpma_options['alert_enabled'] === '1') {
            echo '<b class="wdm-wpma-ok">Alerts are enabled</b> If more than '.esc_html($wdm_wpma_options['email_amount']).' emails are waiting in the Queue, WordPress will send an alert email to <i>'.esc_html($wdm_wpma_options['email']).'</i>.';
        } else {
            echo '<b>Alerting is disabled</b>. No alerts will be sent.';
        }
        echo '</p>';
        echo '<p>Optionally, you can enable <b>Auto-Pause on Alert</b>. In that case the queue stops sending emails when an alert is triggered until you change the queue status manually.</p>';
        echo '<p>Current State: ';
        if ($wdm_wpma_options['alert_enabled'] === '1' && $wdm_wpma_options['pause_on_alert'] === '1') {
            echo '<b class="wdm-wpma-ok">Auto-Pause on Alert is enabled</b>. The Queue will be paused automatically when an alert is triggered.';
        } else {
            echo '<b>Auto-Pause on Alert</b> is disabled. The Queue will not be paused automatically when an alert is triggered.';
        }
        echo '</p>';
        echo '<p>Please note: This plugin sends at most one alert every six hours while the queue remains above the configured threshold.</p>';
        echo '</div>';

        echo '<div class="wdm-wpma-box">';
        echo '<h3>Can I pause the queue without disabling the plugin?</h3>';
        echo '<p>Yes. You can set the Mail Queue status to <b>Paused</b> in the settings.</p>';
        echo '<p>When paused, the plugin still intercepts <a target="_blank" href="https://developer.wordpress.org/reference/functions/wp_mail/"><i>wp_mail()</i></a> and stores outgoing emails in the queue, but no queued emails are sent until you change the status back to <b>Enabled</b>.</p>';
        echo '<p>This is useful if you want to temporarily stop outgoing delivery without disabling the plugin completely.</p>';
        echo '</div>';

        echo '<div class="wdm-wpma-box">';
            echo '<h3>Can I add emails with a high priority to the queue?</h3>';
            echo '<p>Yes, you can add the custom <i>`X-Mail-Queue-Prio`</i> header set to <i>`High`</i> to your email. High priority emails are still processed through the normal Mail Queue cycle, but they are sent before normal queued emails.</p>';
            echo '<p><b>Example 1 (add priority to WooCommerce emails):</b></p>';
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
        echo '</div>';
           
        echo '<div class="wdm-wpma-box">';
            echo '<h3>Can I send emails <i>instantly</i> without going through the queue?</h3>';
            echo '<p>Yes, this is possible (if you absolutely need to do this).</p>';
            echo '<p>For this you can add the custom <i>`X-Mail-Queue-Prio`</i> header set to <i>`Instant`</i> to your email. These emails are sent immediately and bypass the queue. They still appear in the Mail Queue log so that their delivery remains visible.</p>';
            echo '<p>Mind that this is a potential security risk and should be considered carefully. Please use only as an exception.</p>';
            echo '<p><b>Example 1 (instantly send WooCommerce emails):</b></p>';
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
        echo '</div>';

        echo '<div class="wdm-wpma-box">';
        echo '<h3>Can I send queued emails immediately from the backend?</h3>';
        echo '<p>Yes. In the <a href="admin.php?page=wdm_wpma_mail_queue-tab-queue">Queue</a> tab you can select one or more queued emails and use the <b>Send now</b> bulk action.</p>';
        echo '<p>This sends the selected queued emails immediately without waiting for the next WP-Cron cycle.</p>';
        echo '<p>The action is only available for queued emails that are still waiting in the queue.</p>';
        echo '</div>';

        echo '<div class="wdm-wpma-box">';
        echo '<h3>What are queue events in the log?</h3>';
        echo '<p>In addition to email entries the Mail Queue log also shows queue events.</p>';
        echo '<p>These entries document important queue state changes, for example when the queue was enabled, paused, disabled, or auto-paused automatically after an alert.</p>';
        echo '<p>They are not outgoing emails. They are informational log entries to help you understand what happened and when.</p>';
        echo '</div>';

        echo '<div class="wdm-wpma-box">';
        echo '<h3>How are WordPress password reset emails handled?</h3>';
        echo '<p>WordPress password reset emails are handled automatically by the plugin.</p>';
        echo '<p>Normally they are prioritized so they are sent before regular queued emails. If the Mail Queue is paused, password reset emails are sent instantly to reduce the risk of locking users out of their accounts.</p>';
        echo '<p>You do not need to add a custom code snippet for this behavior.</p>';
        echo '</div>';

        echo '<div class="wdm-wpma-box">';
        echo '<h3>Want to put a test email into the Queue?</h3>';        
        echo '<form method="post" action="'.esc_url( admin_url('admin-post.php') ).'">';
        echo '<input type="hidden" name="action" value="wdm_wpma_add_testmail" />';
        wp_nonce_field('wdm_wpma_add_testmail');
        echo '<p><button type="submit" class="button">Sure! Put a Test Email for '.esc_html($wdm_wpma_options['email']).' into the Queue</button></p>';
        echo '</form>';
        echo '</div>';

    } else if ($tab == 'wdm_wpma_mail_queue-tab-croninfo') {
        echo '<div class="wdm-wpma-box">';
            echo '<h3>Information: Your common WP Cron is disabled</h3>';
            echo '<p>It looks like you deactivated the WP-Cron with <i>define(\'DISABLE_WP_CRON\', true)</i>.</p>';
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
                            if (is_array($val) && array_key_exists('wp_mail_queue_hook', $val)) { $tasks_of_mailqueue_in_past = intval($key); }
                            $tasks_in_past = true;
                        }
                    }
                    if ($tasks_in_past) {
                        echo '<div class="wdm-wpma-box">';
                            echo '<h3>Attention: It seems that your WP-Cron is not running. There are some jobs waiting to be completed.</h3>';
                            if ($tasks_of_mailqueue_in_past) {
                                echo '<p><b>The Queue has not been able to run for '.esc_html(human_time_diff($tasks_of_mailqueue_in_past,time())).'.</b></p>';
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

    const PER_PAGE = 50;

    // Log view: one page of rows (paginated + filtered per request), newest first.
    // Terminal/log rows only — the queue-side states (queue/high and the transient
    // 'sending' claim) are excluded so an in-flight row never leaks onto the log tab.
    // Returns array( $items, $total_matching_rows ).
    public function get_log() {
        return $this->query_view( "`status` != 'queue' AND `status` != 'high' AND `status` != 'sending'", '`timestamp` DESC' );
    }

    // Queue view: one page of rows (paginated + filtered per request), high prio first.
    // Includes 'sending' (a row a dispatcher has claimed and is mid-sending) so an
    // in-flight — or, after a crash mid-send, a stuck — row stays visible here.
    // Returns array( $items, $total_matching_rows ).
    public function get_queue() {
        return $this->query_view( "`status` = 'queue' OR `status` = 'high' OR `status` = 'sending'", '`status` ASC, `timestamp` ASC' );
    }

    // Statuses that can appear on the current tab. Doubles as the allowlist the
    // status filter is validated against (an out-of-range value is ignored).
    private function tab_statuses() {
        $page = isset($_GET['page']) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( $page === 'wdm_wpma_mail_queue-tab-queue' ) {
            // 'sending' = a row a dispatcher has claimed and is mid-sending (transient);
            // it belongs to the queue side so it stays visible/filterable while in flight.
            return array( 'queue', 'high', 'sending' );
        }
        return array( 'sent', 'error', 'alert', 'event', 'instant' );
    }

    // Active free-text search term (matched against recipient + subject); '' when none.
    private function get_active_search() {
        if ( ! isset($_REQUEST['s']) ) { return ''; }
        return trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) );
    }

    // Active status filter, validated against the current tab; '' when none/invalid.
    private function get_active_status_filter() {
        if ( ! isset($_GET['filter_status']) ) { return ''; }
        $status = sanitize_key( wp_unslash( $_GET['filter_status'] ) );
        return in_array( $status, $this->tab_statuses(), /*strict*/true ) ? $status : '';
    }

    // Active date-range bound ('from'|'to') as Y-m-d, format-validated; '' when none/invalid.
    private function get_active_date_filter( $which ) {
        $key = ( $which === 'from' ) ? 'filter_date_from' : 'filter_date_to';
        if ( empty($_GET[$key]) ) { return ''; }
        $val = sanitize_text_field( wp_unslash( $_GET[$key] ) );
        return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $val ) ? $val : '';
    }

    // Builds the WHERE clause + prepare() args from $base_where (the tab's fixed
    // status scope) plus the active search/filter request params.
    // Returns array( $where_sql, $args ). $where_sql always has a base fragment,
    // so $args may be empty (no user filters active).
    private function build_list_where( $base_where ) {
        global $wpdb;
        $where = array( '('.$base_where.')' );
        $args  = array();

        $search = $this->get_active_search();
        if ( $search !== '' ) {
            // Bodies are intentionally not searched — they aren't loaded into the list query.
            $like    = '%'.$wpdb->esc_like( $search ).'%';
            $where[] = '(`recipient` LIKE %s OR `subject` LIKE %s)';
            $args[]  = $like;
            $args[]  = $like;
        }

        $status = $this->get_active_status_filter();
        if ( $status !== '' ) {
            $where[] = '`status` = %s';
            $args[]  = $status;
        }

        $date_from = $this->get_active_date_filter('from');
        if ( $date_from !== '' ) {
            $where[] = '`timestamp` >= %s';
            $args[]  = $date_from.' 00:00:00';
        }
        $date_to = $this->get_active_date_filter('to');
        if ( $date_to !== '' ) {
            $where[] = '`timestamp` <= %s';
            $args[]  = $date_to.' 23:59:59';
        }

        return array( implode( ' AND ', $where ), $args );
    }

    // Runs the count + single-page query for a view. The message body is
    // deliberately NOT selected (it can be large mediumtext and is lazy-loaded
    // via REST); only its byte length is needed for the list. This keeps memory
    // bounded even when the table holds tens of thousands of large rows (a spam
    // flood is exactly when this page gets opened).
    // Returns array( $items, $total_matching_rows ).
    private function query_view( $base_where, $order_by ) {
        global $wpdb;
        $tableName = wdm_wpma_table();
        list( $where_sql, $where_args ) = $this->build_list_where( $base_where );

        $count_sql   = "SELECT COUNT(*) FROM `$tableName` WHERE $where_sql";
        $total_items = (int) ( $where_args
            ? $wpdb->get_var( $wpdb->prepare( $count_sql, $where_args ) )
            : $wpdb->get_var( $count_sql ) );

        $offset = ( $this->get_pagenum() - 1 ) * self::PER_PAGE;

        $select    = "SELECT `id`,`timestamp`,`status`,`recipient`,`subject`,`headers`,`attachments`,`info`,LENGTH(`message`) AS `message_length`";
        $page_sql  = "$select FROM `$tableName` WHERE $where_sql ORDER BY $order_by LIMIT %d OFFSET %d";
        $page_args = array_merge( $where_args, array( self::PER_PAGE, $offset ) );
        $items     = $wpdb->get_results( $wpdb->prepare( $page_sql, $page_args ), 'ARRAY_A' );

        return array( is_array($items) ? $items : array(), $total_items );
    }

    public function get_columns() {
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
        $type = isset($_GET['page']) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        // Bulk actions are handled earlier, on load-{page} (see wdm_wpma_handle_list_table_actions()),
        // so a redirect is still possible — do not process them here mid-render.
        // get_log()/get_queue() paginate + filter in SQL and return [items, total].
        if ($type === 'wdm_wpma_mail_queue-tab-log') {
            list( $data, $total_items ) = $this->get_log();
        } else if ($type === 'wdm_wpma_mail_queue-tab-queue') {
            list( $data, $total_items ) = $this->get_queue();
        } else {
            $data = array();
            $total_items = 0;
        }
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => self::PER_PAGE,
        ) );
        $this->items = $data;
    }
 
    public function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'timestamp':
                return esc_html( $item[$column_name] );
                break;
            case 'subject':
                if ($item['status'] === 'event') {
                    return '';
                }
                return esc_html( $item[$column_name] );
                break;
            case 'recipient':
            case 'headers':
                $return = wdm_wpma_safe_unserialize($item[$column_name]);
                if (is_array($return)) {
                    return esc_html( implode(',',$return) );
                } else {
                    return esc_html( $return );
                }
                break;
            case 'attachments':
                $return = wdm_wpma_safe_unserialize($item[$column_name]);
                if (is_array($return)) {
                    $betterreturn = array();
                    foreach($return as $return_item) {
                        array_push($betterreturn,basename($return_item));
                    }
                    return implode('<br />',array_map('esc_html',$betterreturn));
                } else {
                    return esc_html( basename($return) );
                }
                break;
            case 'status':
                $display_status = $item[$column_name] === 'instant' ? 'sent' : $item[$column_name];
                $info = isset( $item[ 'info' ] ) && $item[ 'info' ] ? $item[ 'info' ] : '';
                $infodata = is_string($info) ? json_decode($info,/*associative*/true) : null;

                if ($item['status'] === 'event' ) {
                    $info = '';
                    switch ($item['subject']) {
                        case 'autopause-activated':
                            $info = 'The Queue was automatically paused because an alert was triggered.';
                            break;
                        case 'queue-enabled':
                            $info = 'The Queue was manually enabled by the user.';
                            break;
                        case 'queue-paused':
                            $info = 'The Queue was manually paused by the user.';
                            break;
                        case 'queue-disabled':
                            $info = 'The Queue was manually disabled by the user.';
                            break;
                        default: break;
                    }
                    $htmlInfo = $info ? '<span class="wdm-wpma-info">'.$info.'</span>' : '';
                    $cssStatus = $htmlInfo ? ' wdm-wpma-status-has-info' : '';
                    return '<span class="wdm-wpma-status wdm-wpma-status-event wdm-wpma-status-event-'.sanitize_title($item['subject']).esc_attr($cssStatus).'">'.$item['subject'].$htmlInfo.'</span>';
                }
                
                if ($item[$column_name] === 'alert') {
                    $alertData = is_array($infodata) ? $infodata : null;
                    if ( $alertData ) {
                        $display_in_seconds = $alertData['queue_interval'] < 60 || $alertData['queue_interval'] % 60 !== 0;
                        $queue_interval_display = $display_in_seconds ? esc_html($alertData['queue_interval']).' second(s)' : esc_html($alertData['queue_interval']/60).' minute(s)';
                        $info =  '<strong>Emails in queue</strong>: '.esc_html($alertData['in_queue']);
                        $info .= '<br /><strong>Queue settings</strong>: Send max '.esc_html($alertData['queue_amount']).' email(s) every '.$queue_interval_display.'.';
                        $info .= '<br /><strong>Alert settings</strong>: Send alert if more than '.esc_html($alertData['email_amount']).' email(s) in the queue.';
                    } else {
                        $info = '';
                    }
                } elseif ($item[$column_name] === 'instant') {
                    $info = 'Email was sent instantly, bypassing the queue';
                } elseif ($item[$column_name] === 'sent') {
                    $sentData = is_array($infodata) ? $infodata : null;
                    if ($sentData && isset($sentData['send_mode']) && $sentData['send_mode'] === 'manual') {
                        $info = 'Email was sent manually from the queue';
                    } elseif ($sentData && isset($sentData['prio']) && $sentData['prio'] === 'high') {
                        $info = 'Email was sent with high priority';
                    } else {
                        $info = '';
                    }
                }
                $htmlInfo = $info ? '<span class="wdm-wpma-info">'.$info.'</span>' : '';
                $cssStatus = $htmlInfo ? ' wdm-wpma-status-has-info' : '';
                return '<span class="wdm-wpma-status wdm-wpma-status-'.sanitize_title($display_status).esc_attr($cssStatus).'">'.$display_status.$htmlInfo.'</span>';
                break;
            case 'message':
                if ($item['status'] === 'event') {
                    return '';
                }
                // The list query selects LENGTH(message) AS message_length instead of the
                // body (bodies can be large and are lazy-loaded via REST). Fall back to
                // strlen() if a full 'message' column is ever present.
                $messageLen = isset($item['message_length'])
                    ? intval($item['message_length'])
                    : ( isset($item['message']) ? strlen($item['message']) : 0 );
                if ( $messageLen > 0 ) {
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
                return '';
                break;
        }
    }

    protected function column_cb ( $item ) {
        return '<input type="checkbox" name="id[]" value="'.esc_attr($item['id']).'" />';
    }
 
    public function get_bulk_actions() {
        if (isset($_GET['page']) && $_GET['page'] == 'wdm_wpma_mail_queue-tab-queue') {
            $actions = array(
                'delete'  => __( 'Delete'),
                'sendnow' => __( 'Send now'),
            );
        } elseif (isset($_GET['page']) && $_GET['page'] == 'wdm_wpma_mail_queue-tab-log') {
            $actions = array(
                'delete' => __( 'Delete'),
                'resend' => __( 'Resend'),
            );
        } else {
            $actions = array();
        }

        return $actions;
    }

    public function no_items() {
        echo 'No emails found.';
    }

    // Status dropdown + date-range inputs, rendered in the top table nav.
    // All controls submit through the surrounding method="get" list form, so the
    // active filters travel in the URL and survive pagination (WP_List_Table builds
    // its pagination links from the request URL). Values are re-read + validated
    // by the get_active_*() helpers before they touch SQL.
    protected function extra_tablenav( $which ) {
        if ( $which !== 'top' ) { return; }

        $statuses   = $this->tab_statuses();
        $sel_status = $this->get_active_status_filter();
        $date_from  = $this->get_active_date_filter('from');
        $date_to    = $this->get_active_date_filter('to');
        $search     = $this->get_active_search();

        // Hide the filters when there's nothing to filter — but only if no filter
        // or search is active. If an active filter/search returned zero rows we must
        // keep the controls (and the Clear filters button) visible so the user can
        // still reset. Mirrors WP_List_Table::search_box()'s show/hide logic, so the
        // search box and the filters appear and disappear together.
        $has_active_filter = ( $sel_status !== '' || $date_from !== '' || $date_to !== '' || $search !== '' );
        if ( ! $this->has_items() && ! $has_active_filter ) { return; }

        // Single row: filters on the left, search on the right. No Filter button —
        // the status dropdown and date inputs carry .wdm-wpma-autofilter and
        // auto-submit the (GET) form on change (see assets/js/wdm-wpma-admin.js);
        // the search input submits on Enter. One "Clear" resets everything.
        echo '<div class="alignleft actions wdm-wpma-filters">';

        echo '<label class="screen-reader-text" for="wdm-wpma-filter-status">Filter by status</label>';
        echo '<select name="filter_status" id="wdm-wpma-filter-status" class="wdm-wpma-autofilter">';
        echo '<option value="">All statuses</option>';
        foreach ( $statuses as $status ) {
            // 'instant' rows display as "sent" in the list — label the option to match.
            $label = $status === 'instant' ? 'Sent (instant)' : ucfirst($status);
            echo '<option value="'.esc_attr($status).'"'.selected($sel_status, $status, false).'>'.esc_html($label).'</option>';
        }
        echo '</select>';

        echo ' <label class="screen-reader-text" for="wdm-wpma-filter-date-from">From date</label>';
        echo '<input type="date" name="filter_date_from" id="wdm-wpma-filter-date-from" class="wdm-wpma-autofilter" value="'.esc_attr($date_from).'" />';
        echo ' <label class="screen-reader-text" for="wdm-wpma-filter-date-to">To date</label>';
        echo '<input type="date" name="filter_date_to" id="wdm-wpma-filter-date-to" class="wdm-wpma-autofilter" value="'.esc_attr($date_to).'" />';

        echo '<span class="wdm-wpma-search-box">';
        echo '<label class="screen-reader-text" for="wdm-wpma-search-input">Search emails</label>';
        echo '<input type="search" name="s" id="wdm-wpma-search-input" value="'.esc_attr($search).'" placeholder="Search recipient or subject" />';
        echo '<button type="submit" class="button" id="wdm-wpma-search-submit">Search</button>';
        echo '</span>';

        // One "Clear" for everything (status + dates + search). A link, not a submit:
        // the GET form would otherwise re-send the current values. Shown only when
        // something is active.
        if ( $has_active_filter ) {
            $clear_url = add_query_arg(
                array( 'page' => isset($_GET['page']) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '' ),
                admin_url('admin.php')
            );
            echo ' <a class="button wdm-wpma-clear" href="'.esc_url($clear_url).'">Clear</a>';
        }

        echo '</div>';
    }

    public function process_bulk_action() {

        // No action selected (e.g. the page is just being rendered) — nothing to do.
        $current_action = $this->current_action();
        if ( ! $current_action ) { return; }

        // Security check: any dispatched bulk action requires a valid nonce.
        // Read from $_REQUEST — the list forms submit via GET (see the method="get"
        // forms in wdm_wpma_settings_page()), so the WP_List_Table bulk nonce arrives
        // as a query arg, not a POST field.
        $nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
            wp_die( 'Security check failed. Please go back, reload the page, and try again.' );
        }

        // get IDs
        $request_ids = isset( $_REQUEST['id'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['id'] ) ) : array();
        if ( empty( $request_ids ) ) { return; }

        // Default page to return to after the action (PRG) — the tab the action ran on.
        $current_page = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'wdm_wpma_mail_queue';

        // Replay guard: these actions change state (delete / resend / send now) and
        // the list form submits via GET, so the dispatched action is a bookmarkable
        // URL. Without this, revisiting it (Back button, history restore, prefetch)
        // would run it again while the nonce is still valid. Claim the specific
        // action once — keyed on action + selected ids + nonce — and treat any exact
        // repeat as a no-op that just redirects back with a zero result, so the same
        // mails are never deleted, re-queued or re-sent twice. A different selection
        // under the same reusable nonce is a distinct fingerprint and runs normally.
        $action_fingerprint = $current_action . '|' . implode( ',', $request_ids ) . '|' . $nonce;
        if ( ! wdm_wpma_claim_action_once( $action_fingerprint ) ) {
            wdm_wpma_bulk_action_redirect( $current_page, $current_action, 0, 0 );
        }

        global $wpdb;
        $tableName = wdm_wpma_table();

        switch ( $current_action ) {
            case 'delete':
                $count_deleted = 0;
                foreach($request_ids as $id) {
                    // Read the attachments column before deleting the row: a deleted queued
                    // mail must also lose its attachment folder, or the folder is orphaned forever.
                    $attachments = $wpdb->get_var($wpdb->prepare("SELECT `attachments` FROM `$tableName` WHERE `id` = %d", $id));
                    if ( $wpdb->delete($tableName,array('id'=>intval($id)),'%d') ) {
                        $count_deleted++;
                        wdm_wpma_delete_attachment_folder( $attachments );
                    }
                }
                wdm_wpma_bulk_action_redirect( $current_page, 'delete', $count_deleted, 0 );
                break;
            case 'resend':
                // Security note: this re-queues the stored body verbatim. That body may have
                // originated from an unauthenticated caller (any wp_mail() the plugin intercepted),
                // so it is only safe because rows can't be edited here — the admin resends the exact
                // bytes, never crafts them. For any "edit before resend" feature this trust boundary
                // must be re-examined (treat the edited content as admin-supplied input).
                $count_resend = 0;
                $count_error  = 0;
                foreach($request_ids as $id) {
                    $maildata = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tableName` WHERE `id` = %d", $id));
                    // Skip rows that aren't resendable real mails. Allowlist, not denylist:
                    // an unknown/new status must never become silently resendable.
                    // - null: deleted or foreign id (PHP 8 fatals on ->attachments otherwise,
                    //   and an empty row would be dispatched as 'ERROR // ' junk to the alert address)
                    // - alert/event: queue bookkeeping, not outgoing mail (mirrors the REST 404 guard)
                    // - queue/high/sending: already pending or mid-flight — re-queuing duplicates them
                    if ( !$maildata || !in_array( $maildata->status, array( 'sent', 'error', 'instant' ), /*strict*/true ) ) {
                        $count_error++;
                        continue;
                    }
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
                        // Emails with attachments can't be resent — the attachment files
                        // are deleted after the original send attempt.
                        $count_error++;
                    }
                }
                // If anything was re-queued, land on the Queue tab (that's where the new rows are).
                $target = ($count_resend > 0) ? 'wdm_wpma_mail_queue-tab-queue' : $current_page;
                wdm_wpma_bulk_action_redirect( $target, 'resend', $count_resend, $count_error );
                break;
            case 'sendnow':
                $count_sent = 0;
                $count_error = 0;
                foreach($request_ids as $id) {
                    $mailitem = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tableName` WHERE `id` = %d", $id), ARRAY_A);
                    if ( wdm_wpma_really_send_mail($mailitem,['send_mode'=>'manual']) ) {
                        $count_sent++;
                    } else {
                        $count_error++;
                    }
                }
                // If anything was sent, land on the Log tab (that's where sent rows show up).
                $target = ($count_sent > 0) ? 'wdm_wpma_mail_queue-tab-log' : $current_page;
                wdm_wpma_bulk_action_redirect( $target, 'sendnow', $count_sent, $count_error );
                break;
            default:
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
    register_setting('wdm_wpma_settings','wdm_wpma_settings','wdm_wpma_sanitize_settings');

    add_settings_section('wdm_wpma_settings_section','Mail Queue',null,'wdm_wpma_settings_page');
    add_settings_field('wdm_wpma_status','Status','wdm_wpma_render_option_status','wdm_wpma_settings_page','wdm_wpma_settings_section');
    add_settings_field('wdm_wpma_queue','Sending frequency','wdm_wpma_render_option_queue','wdm_wpma_settings_page','wdm_wpma_settings_section');
    
    add_settings_section('wdm_wpma_settings_section_log','Log',null,'wdm_wpma_settings_page');
    add_settings_field('wdm_wpma_log','Keep log entries','wdm_wpma_render_option_log','wdm_wpma_settings_page','wdm_wpma_settings_section_log');
    
    add_settings_section('wdm_wpma_settings_section_alert','Alerts',null,'wdm_wpma_settings_page');
    add_settings_field('wdm_wpma_alert_status','Alerts enabled','wdm_wpma_render_option_alert_status','wdm_wpma_settings_page','wdm_wpma_settings_section_alert');
    add_settings_field('wdm_wpma_sensitivity','Alert Sensitivity','wdm_wpma_render_option_sensitivity','wdm_wpma_settings_page','wdm_wpma_settings_section_alert');
    add_settings_field('wdm_wpma_pause_on_alert','Auto-Pause on Alert','wdm_wpma_render_option_pause_on_alert','wdm_wpma_settings_page','wdm_wpma_settings_section_alert');
}
add_action('admin_init','wdm_wpma_settings_init');


// Sanitize and validate plugin settings. Rebuilds the array from an allowlist:
// only known keys survive, each validated — unexpected keys from a crafted
// POST are dropped. tableName is deliberately NOT accepted from input (the
// settings form never submits it); it is carried over from the stored
// settings, so a forged request cannot repoint the plugin at another table.
// Also compares old and new settings to detect manual changes of the "enabled"
// status and clear the "pause on alert" state if necessary.
function wdm_wpma_sanitize_settings ($settings) {
    $settings = is_array($settings) ? $settings : array();

    $old_settings = get_option('wdm_wpma_settings', array());
    if (!is_array($old_settings)) { $old_settings = array(); }

    $old_enabled = isset($old_settings['enabled']) ? $old_settings['enabled'] : '0';
    $new_enabled = isset($settings['enabled']) ? $settings['enabled'] : '0';
    if ( !in_array($new_enabled, array('0', '1', 'paused'), true) ) {
        $new_enabled = '0';
    }

    $clean = array();
    $clean['enabled'] = $new_enabled;

    $clean['pause_on_alert'] = (isset($settings['pause_on_alert']) && (string) $settings['pause_on_alert'] === '1') ? '1' : '0';
    $clean['alert_enabled']  = (isset($settings['alert_enabled'])  && (string) $settings['alert_enabled']  === '1') ? '1' : '0';

    // Positive integers; on a missing key fall back to the stored value (the
    // sanitizer also runs on programmatic update_option() calls that may not
    // carry the full form field set), then to the shipped default.
    $int_defaults = array('queue_amount' => '1', 'queue_interval' => '5', 'email_amount' => '10', 'clear_queue' => '14');
    foreach ($int_defaults as $key => $default) {
        if (isset($settings[$key])) {
            $raw = $settings[$key];
        } elseif (isset($old_settings[$key])) {
            $raw = $old_settings[$key];
        } else {
            $raw = $default;
        }
        $clean[$key] = (string) max(1, intval($raw));
    }

    $unit = isset($settings['queue_interval_unit']) ? $settings['queue_interval_unit'] : (isset($old_settings['queue_interval_unit']) ? $old_settings['queue_interval_unit'] : 'minutes');
    $clean['queue_interval_unit'] = in_array($unit, array('minutes', 'seconds'), true) ? $unit : 'minutes';

    $email = isset($settings['email']) ? sanitize_email($settings['email']) : '';
    if ($email === '') {
        $old_email = isset($old_settings['email']) ? sanitize_email($old_settings['email']) : '';
        $email = $old_email !== '' ? $old_email : sanitize_email(get_option('admin_email'));
    }
    $clean['email'] = $email;

    // Carried over, never taken from input. Same charset filter as
    // wdm_wpma_table(); empty result → omit, the defaults fill it in.
    if (isset($old_settings['tableName'])) {
        $tableName = preg_replace('/[^A-Za-z0-9_]/', '', (string) $old_settings['tableName']);
        if ($tableName !== '') { $clean['tableName'] = $tableName; }
    }

    if ($new_enabled !== 'paused' || ($new_enabled === 'paused' && $old_enabled !== 'paused')) {
        wdm_wpma_clear_pause_on_alert_state();
    }

    if ($new_enabled !== $old_enabled) {
        if ($new_enabled === '1') {
            wdm_wpma_push_queue_event(['name' => 'queue-enabled']);
        } elseif ($new_enabled === 'paused') {
            wdm_wpma_push_queue_event(['name' => 'queue-paused']);
        } else {
            wdm_wpma_push_queue_event(['name' => 'queue-disabled']);
        }
    }

    return $clean;
}

function wdm_wpma_render_option_status() {
    $wdm_wpma_options = wdm_wpma_options();

    echo '<select name="wdm_wpma_settings[enabled]">';
    echo   '<option value="1" '.($wdm_wpma_options['enabled'] === '1' ? 'selected' : '').'>Enabled</option>';
    echo   '<option value="paused" '.($wdm_wpma_options['enabled'] === 'paused' ? 'selected' : '').'>Paused</option>';
    echo   '<option value="0" '.(!in_array($wdm_wpma_options['enabled'], ['1','paused'], /*strict*/true) ? 'selected' : '').'>Disabled</option>';
    echo '</select>';

    if ($wdm_wpma_options['enabled'] === 'paused') {
        echo ' &nbsp; &nbsp; <span class="wdm-wpma-warning"> &larr; The Queue is paused. </span> &nbsp;No emails are currently being sent, but all emails are still being added to the Queue and can be sent later by changing the status to "Enabled".';
    } elseif ($wdm_wpma_options['enabled'] !== '1') {
        echo ' &nbsp; &nbsp; <span class="wdm-wpma-warning"> &larr; Check this to enable the Queue. </span> &nbsp;Otherwise this plugin won\'t have any effect on your website.';
    }
}

function wdm_wpma_render_option_alert_status() {
    $wdm_wpma_options = wdm_wpma_options();
    if ($wdm_wpma_options['alert_enabled'] === '1') {
        echo '<input type="checkbox" name="wdm_wpma_settings[alert_enabled]" value="1" checked />';
    } else {
        echo '<input type="checkbox" name="wdm_wpma_settings[alert_enabled]" value="1" />';
    }
}

function wdm_wpma_render_option_queue() {
    $wdm_wpma_options = wdm_wpma_options();
    if ($wdm_wpma_options['queue_interval_unit'] === 'seconds') {
        $number = intval($wdm_wpma_options['queue_interval']);
    } else {
        $number = intval($wdm_wpma_options['queue_interval']) / 60;
    }
    
    echo 'Send max. <input name="wdm_wpma_settings[queue_amount]" type="number" min="1" value="'.esc_attr($wdm_wpma_options['queue_amount']).'" /> email(s) every <input name="wdm_wpma_settings[queue_interval]" type="number" min="1" value="'.esc_attr($number).'" />';
    
    if ($wdm_wpma_options['queue_interval_unit'] === 'seconds')  {
        echo '<select name="wdm_wpma_settings[queue_interval_unit]"><option value="minutes">minute(s)</option><option selected value="seconds">second(s)</option></select>';
    } else {
        echo '<select name="wdm_wpma_settings[queue_interval_unit]"><option selected value="minutes">minute(s)</option><option value="seconds">second(s)</option></select>';
    }
    
    echo ' by <i><a href="https://developer.wordpress.org/plugins/cron/" target="_blank">WP Cron</a></i>. ';

    // Shown/hidden by JS (assets/js/wdm-wpma-admin.js) when the configured rate
    // exceeds 20 mails/minute (minutes unit) or 1 mail per 2 seconds (seconds unit).
    echo '<p id="wdm-wpma-rate-notice" class="wdm-wpma-rate-notice" hidden><b>That\'s a lot of email.</b> One of this plugin\'s jobs is to act as a safety net: if your site is ever compromised or a form gets abused, the queue is what keeps mass spam from leaving your server unnoticed. At this rate that safety net has little effect. Consider a slower rate unless you really need it.</p>';
}

function wdm_wpma_render_option_log() {
    $wdm_wpma_options = wdm_wpma_options();
    echo 'for <input name="wdm_wpma_settings[clear_queue]" type="number" min="1" value="'.esc_attr(intval($wdm_wpma_options['clear_queue']) / 24).'" /> days.';
}

function wdm_wpma_render_option_sensitivity() {
    $wdm_wpma_options = wdm_wpma_options();
    echo 'Send alert to <input type="text" name="wdm_wpma_settings[email]" value="'.esc_attr(sanitize_email($wdm_wpma_options['email'])).'" /> if more than <input name="wdm_wpma_settings[email_amount]" type="number" min="1" value="'.esc_attr(intval($wdm_wpma_options['email_amount'])).'" /> emails are in the <a href="admin.php?page=wdm_wpma_mail_queue-tab-queue">Queue</a>.';
}



function wdm_wpma_render_option_pause_on_alert() {
    $wdm_wpma_options = wdm_wpma_options();
    $is_enabled = isset($wdm_wpma_options['pause_on_alert']) && $wdm_wpma_options['pause_on_alert'] === '1';

    echo '<select name="wdm_wpma_settings[pause_on_alert]">';
    echo   '<option value="0" '.(!$is_enabled ? 'selected' : '').'>Disabled</option>';
    echo   '<option value="1" '.($is_enabled ? 'selected' : '').'>Enabled</option>';
    echo '</select>';

    if (!$is_enabled) {
        echo ' &nbsp; <span> &larr; Automatically pause the Queue if an alert is triggered. </span> This could help to prevent e.g. spam emails being sent in case your website is compromised.';
    } else {
        echo ' &nbsp; <span> &larr; The Queue will stay paused until you manually change the Mail Queue status to "Enabled".</span>';
    }
}





/* ***************************************************************
Alert WordPress User if last email in log could not be sent
**************************************************************** */
function wdm_wpma_check_log_for_errors() {

    global $wpdb;
    $wdm_wpma_options = wdm_wpma_options();
    $currentScreen = function_exists('get_current_screen') ? get_current_screen() : null;

    if ($currentScreen && preg_match('#wdm_wpma_mail_queue#', $currentScreen->base) && $wdm_wpma_options['enabled'] === 'paused' && wdm_wpma_is_pause_on_alert_active()) {
        wdm_wpma_render_pause_on_alert_notice();
    }

    if ( !in_array($wdm_wpma_options['enabled'], ['1', 'paused'], true) ) { return; }

    $tableName = wdm_wpma_table();
    // Only the newest non-queue/non-event row is needed, and only its status + info.
    // LIMIT 1 lets MySQL reverse-walk the PRIMARY key and stop at the first match
    // instead of get_row() pulling the whole matching set into memory; selecting
    // just the two used columns avoids fetching the mediumtext body. This runs on
    // admin_notices for every wp-admin page, so it must stay cheap under a flood.
    $last_mail = $wpdb->get_row("SELECT `status`,`info` FROM `$tableName` WHERE `status` != 'queue' AND `status` != 'high' AND `status` != 'event' ORDER BY `id` DESC LIMIT 1",'ARRAY_A');
    if (!$last_mail) { return; }

    if ($last_mail['status'] == 'error') {
        if (current_user_can('manage_options')) {
            $notice = '<div class="notice notice-error is-dismissible">';
            $notice .= '<h1>Attention: Your website has problems sending e-mails</h1>';
            $notice .= '<p>This is an important message from your <i>Mail Queue</i> plugin. Please take a look at your <a href="admin.php?page=wdm_wpma_mail_queue-tab-log">Mail Log</a>. The last email(s) couldn\'t be sent properly.</p>';
            $notice .= '<p>Last error message was: <b>'.esc_html($last_mail['info']).'</b></p>';
            $notice .= '</div>';
            echo $notice;
        } else if (current_user_can('edit_posts')) {
            $notice = '<div class="notice notice-error is-dismissible">';
            $notice .= '<h1>Attention: Your website has problems sending e-mails</h1>';
            $notice .= '<p>Please contact your Administrator. It seems that WordPress is not able to send emails.</p>';
            $notice .= '<p>Last error message: <b>'.esc_html($last_mail['info']).'</b></p>';
            $notice .= '</div>';
            echo $notice;
        }
    }

    // notices for the plugin options page
    if ($currentScreen && $currentScreen->base == 'toplevel_page_wdm_wpma_mail_queue') {
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
add_action('admin_notices', 'wdm_wpma_check_log_for_errors');
