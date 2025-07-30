<?php

/**
 * Plugin Name:       Mail Queue
 * Plugin URI:        https://www.webdesign-muenchen.de/wordpress-plugin-mail-queue/
 * Description:       Take Control and improve Security of wp_mail(). Queue and log outgoing emails, and get alerted, if your website wants to send more emails than usual.
 * Version:           1.4.5
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            WDM
 * Author URI:        https://www.webdesign-muenchen.de
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) { exit; }


/* ***************************************************************
PLUGIN VERSION
**************************************************************** */

$wdm_wpma_version = '1.4.5';





/* ***************************************************************
PLUGIN DEFAULT SETTINGS
**************************************************************** */
function wdm_wpma_get_settings() {
    $defaults = array(
        'enabled'        => '0',
        'alert_enabled'  => '0',
        'email'          => get_option('admin_email'),
        'email_amount'   => '10',
        'queue_amount'   => '1',
        'queue_interval' => '5',
        'queue_interval_unit' => 'minutes',
        'clear_queue'    => '14',
        'tableName'      => 'mail_queue',
        'triggercount'   => 0,
    );
    $args = get_option('wdm_wpma_settings');
    $options = wp_parse_args($args,$defaults);

    if ($options['queue_interval_unit'] == 'seconds') {
        $options['queue_interval'] = intval($options['queue_interval']);
        if ($options['queue_interval'] < 10) { $options['queue_interval'] = 10; } // Minimum Interval 10 Seconds
    } else {
        $options['queue_interval'] = intval($options['queue_interval']) * 60;
    }

    $options['clear_queue'] = intval($options['clear_queue']) * 24;
    return $options;
}





/* ***************************************************************
Overwrite wp_mail() if Plugin enabled and no Cron is running
**************************************************************** */
$wdm_wpma_mailid = 0;
$wdm_wpma_options = wdm_wpma_get_settings(); // Get Settings

if ($wdm_wpma_options['enabled'] == '1' && wp_doing_cron() == false) {
    add_filter( 'pre_wp_mail' , 'wdm_wpma_prewpmail', 10, 2);
}

// pre WP Mail Filter
function wdm_wpma_prewpmail($null, $atts) {

    global $wpdb, $wdm_wpma_options;

    // Mail Variables
    $to          = $atts['to'];
    $subject     = $atts['subject'];
    $message     = $atts['message'];            
    $headers     = $atts['headers'];
    $attachments = $atts['attachments'];
    $status      = 'queue';

    // Make sure that $headers always is an array
    if ($headers) {
        if (!is_array($headers)) {
            $headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
        }
    } else {
        $headers = [];
    }

    // Loop through email headers
    // - Instant Sending or Prio Mail?
    // - Track if ContentType header is set
    $hasContentTypeHeader = false;
    $hasFromHeader = false;
    foreach($headers as $index => $val) {
        $val = trim($val);
        if (preg_match("#^X-Mail-Queue-Prio: +Instant *$#i",$val)) {
            array_splice($headers,$index,1);
            $status = 'instant';
            break;
        } else if (preg_match("#^X-Mail-Queue-Prio: +High *$#i",$val)) {
            array_splice($headers,$index,1);
            $status = 'high';
            break;
        } else if (preg_match('#^Content-Type:#i',$val)) {
            $hasContentTypeHeader = true;
        } else if (preg_match('#^From:#i',$val)) {
            $hasFromHeader = true;
        }
    }

    // For all emails that are stored in the queue to be sent later:
    // Store custom filtered values in headers if available.
    // Support the following hooks used in wp_mail:
    // - wp_mail_content_type
    // - wp_mail_charset
    // - wp_mail_from
    // - wp_mail_from_name
    if ($status !== 'instant') {
        if (!$hasContentTypeHeader) {
            $contentType = apply_filters('wp_mail_content_type','text/plain');
            if ( $contentType ) {
                if (stripos($contentType,'multipart') === false) {
                    $charset = apply_filters('wp_mail_charset',get_bloginfo('charset'));
                } else {
                    $charset = '';
                }
                $headers[] = 'Content-Type: '.$contentType.($charset ? '; charset="'.$charset.'"' : '');
            }
        }
        if (!$hasFromHeader) {
            $from_Email = apply_filters('wp_mail_from','');
            if ($from_Email) {
                $fromName = apply_filters('wp_mail_from_name','');
                if ($fromName) {
                    $headers[] = $fromName.' <'.$from_Email.'>';
                } else {
                    $headers[] = $from_Email;
                }
            }
        }
    }


    // Write email in Queue
    $tableName = $wpdb->prefix.$wdm_wpma_options['tableName'];
    $data = array(
        'timestamp'=> current_time('mysql',false),
        'recipient'=> maybe_serialize($to),
        'subject'=> $subject,
        'message'=> $message,
        'status' => $status,
        'attachments' => ''
    );
    if (isset($headers) && $headers) { $data['headers'] = maybe_serialize($headers); }

    // store attachments in /attachments/ Folder, to address them later
    if (isset($attachments) && $attachments && $attachments != '') { 
        
        $subfolder = time().'-'.rand(0,999999);
        $foldercreated = wp_mkdir_p(plugin_dir_path(__FILE__).'attachments/'.$subfolder);
        if (!$foldercreated) {
            error_log('Could not create Subfolder for Email attachment');
            $data['info'] = 'Error: Could not store attachments';
        } else {
            if (!is_array($attachments)) { $attachments = array($attachments); }
            $newattachments = array();
            global $wp_filesystem;
            if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                include_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
            }
            foreach($attachments as $item) {
                $newfile = plugin_dir_path(__FILE__).'attachments/'.$subfolder.'/'.basename($item);
                $wp_filesystem->copy($item,$newfile);
                array_push($newattachments,$newfile);
            }
            $data['attachments'] = maybe_serialize($newattachments);
        }
    }
    $inserted = $wpdb->insert($tableName,$data);

    if ($status == 'instant') {
        return null;
    } else if ( !$inserted ) {
        // No database entry, email cannot be send
        return false;
    } else {
        // Fake Submit by returning 'True'
        return true;
    }

}



// show wp_mail() errors
function wdm_wpma_mail_failed( $wp_error ) {
    global $wpdb,$wdm_wpma_options,$wdm_wpma_mailid;
    if (isset($wdm_wpma_mailid) && $wdm_wpma_mailid != 0) {
        $tableName = $wpdb->prefix.$wdm_wpma_options['tableName'];
        $wpMailFailedError = isset( $wp_error->errors ) && isset( $wp_error->errors['wp_mail_failed'][0] ) ? implode( '; ', $wp_error->errors['wp_mail_failed'] ) : '<em>Unknown</em>';
        $wpdb->update($tableName,array('timestamp'=>current_time('mysql',false),'status'=>'error', 'info'=>$wpMailFailedError),array('id'=>intval($wdm_wpma_mailid)),array('%s', '%s', '%s'),'%d');
    }
    return error_log(print_r($wp_error, true));
} 
add_action('wp_mail_failed','wdm_wpma_mail_failed',10,1);




/* ***************************************************************
CRON
**************************************************************** */
function wdm_wpma_search_mail_from_queue() {
   
    global $wpdb,$wdm_wpma_options,$wdm_wpma_mailid;
    if ($wdm_wpma_options['enabled'] != '1') { return; }
    $tableName = $wpdb->prefix.$wdm_wpma_options['tableName'];

    // Triggercount to avoid multiple runs
    $wdm_wpma_options['triggercount']++;
    if ($wdm_wpma_options['triggercount'] > 1) { return; }

    // Total Mails waiting in the Queue?
    $mailjobsTotal = $wpdb->get_var( "SELECT COUNT(*) FROM `$tableName` WHERE `status` = 'queue' OR `status` = 'high'" );

    // Mails to send
    $mailjobs     = $wpdb->get_results("SELECT * FROM `$tableName` WHERE `status` = 'queue' OR `status` = 'high' ORDER BY `status` ASC, `id` LIMIT ".intval($wdm_wpma_options['queue_amount']),'ARRAY_A');
    $mailsInQueue = is_array($mailjobs) ? count($mailjobs) : 0;

    // Alert Admin, if too many mails in the Queue.
    if ($wdm_wpma_options['alert_enabled'] == '1' && $mailjobsTotal > intval($wdm_wpma_options['email_amount'])) {

        // Last alerts older than 6 hours?
        $alerts = $wpdb->get_results("SELECT * FROM `$tableName` WHERE `status` = 'alert' AND `timestamp` > NOW() - INTERVAL 6 HOUR",'ARRAY_A');

        // If no alerts, then send one
       if (!$alerts) {
            $alertMessage = 'Hi,';
            $alertMessage .= "\n\n";
            $alertMessage .= 'this is an important message from your WordPress website '.esc_url(get_option('siteurl')).'.';
            $alertMessage .= "\n";
            $alertMessage .= "\n".'The Mail Queue Plugin has detected that your website tries to send more emails than expected (currently '.$mailjobsTotal.').';
            $alertMessage .= "\n".'Please take a close look at the email queue, because it contains more messages than the specified limit.';
            $alertMessage .= "\n";
            $alertMessage .= "\n".'In case this is the usual amount of emails, you can adjust the threshold for alerts in the settings of your Mail Queue Plugin.';
            $alertMessage .= "\n\n";
            $alertMessage .= "-- ";
            $alertMessage .= "\n";
            $alertMessage .= admin_url();
            $alertSubject = '🔴 WordPress Mail Queue Alert - '.esc_html(get_option('blogname'));
            $data = array(
                'timestamp'=> current_time('mysql',false),
                'recipient'=> sanitize_email($wdm_wpma_options['email']),
                'subject'  => $alertSubject,
                'message'  => $alertMessage,
                'status'   => 'alert',
                'info'     => json_encode([
                    'in_queue'       => strval( $mailsInQueue ),
                    'email_amount'   => intval($wdm_wpma_options['email_amount']),
                    'queue_amount'   => intval($wdm_wpma_options['queue_amount']),
                    'queue_interval' => intval($wdm_wpma_options['queue_interval']),
                ]),
            );
            $wpdb->insert($tableName,$data);
            wp_mail($wdm_wpma_options['email'],$alertSubject,$alertMessage);
        }

    }

    // Send Mails in Queue
    if ($mailsInQueue > 0) {
        $results = array_slice($mailjobs,0,intval($wdm_wpma_options['queue_amount']));
        if ($results && count($results) > 0) {
            foreach($results as $index => $item) {
                if ($item['recipient'] && $item['recipient'] != '') { $to = maybe_unserialize($item['recipient']); } else { $to = $wdm_wpma_options['email']; $item['subject'] = 'ERROR // '.$item['subject']; }
                if ($item['headers'] && $item['headers'] != '') { $headers = maybe_unserialize($item['headers']); } else { $headers = ''; }
                if ($item['attachments'] && $item['attachments'] != '') { $attachments = maybe_unserialize($item['attachments']); } else { $attachments = ''; }
                $wdm_wpma_mailid = $item['id'];  

                remove_filter('pre_wp_mail','wdm_wpma_prewpmail',10);
                $sendstatus = wp_mail($to,$item['subject'],$item['message'],$headers,$attachments); // Finally sends the email for real
                add_filter( 'pre_wp_mail' , 'wdm_wpma_prewpmail', 10, 2);
                if ($sendstatus) {
                    $wpdb->update($tableName,array('timestamp'=>current_time('mysql',false),'status'=>'sent'),array('id'=>$item['id']),'%s','%d');
                }
                if (is_array($attachments)) {
                    global $wp_filesystem;
                    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                        include_once(ABSPATH . 'wp-admin/includes/file.php');
                        WP_Filesystem();
                    }
                    $attachmentfolder = pathinfo($attachments[0]);
                    $wp_filesystem->delete($attachmentfolder['dirname'],true,'d');
                }
            }
        }
    }

    // Delete old logs
    $wpdb->query("DELETE FROM `$tableName` WHERE `status` != 'queue' AND `timestamp` < NOW() - INTERVAL ".esc_sql($wdm_wpma_options['clear_queue'])." HOUR");

}
add_action('wp_mail_queue_hook','wdm_wpma_search_mail_from_queue');

// Custom Cron Interval
function wdm_wpma_cron_interval( $schedules ) { 
    global $wdm_wpma_options;
    $schedules['wdm_wpma_interval'] = array(
        'interval' => $wdm_wpma_options['queue_interval'],
        'display'  => esc_html__('WP Mail Queue'), );
    return $schedules;
}
add_filter('cron_schedules','wdm_wpma_cron_interval');

// Set or Remove Cron
$next_wpma_cron_timestamp = wp_next_scheduled('wp_mail_queue_hook');
if ($next_wpma_cron_timestamp && $wdm_wpma_options['enabled'] != '1') {
    wp_unschedule_event($next_wpma_cron_timestamp,'wp_mail_queue_hook');
} else if (!$next_wpma_cron_timestamp && $wdm_wpma_options['enabled'] == '1') {
    wp_schedule_event(time(),'wdm_wpma_interval','wp_mail_queue_hook');
}



/* ***************************************************************
Install/Uninstall/Upgrade
**************************************************************** */


/* Delete plugin options and database table */
function wdm_wpma_uninstall () {
    global $wpdb;

    $optionName = 'wdm_wpma_settings';
    delete_option( $optionName );

    $optionName = 'wdm_wpma_version';
    delete_option( $optionName );

    $tableName = $wpdb->prefix.'mail_queue';
    $wpdb->query( "DROP TABLE IF EXISTS $tableName" );
}

/* Delete Cron when Plugin deactivated */
function wdm_wpma_deactivate() {
    wp_clear_scheduled_hook( 'wp_mail_queue_hook' );
}

/* Create/Upgrade MySQL Table on Activation/Upgrade: https://codex.wordpress.org/Creating_Tables_with_Plugins */
function wdm_wpma_updateDatabaseTables() {
    global $wpdb, $wdm_wpma_version;

    $tableName = $wpdb->prefix.'mail_queue';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tableName (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    timestamp TIMESTAMP NOT NULL,
    status varchar(55) DEFAULT '' NOT NULL,
    recipient varchar(255) DEFAULT '' NOT NULL,
    subject varchar(255) DEFAULT '' NOT NULL,
    message mediumtext NOT NULL,
    headers text NOT NULL,
    attachments text NOT NULL,
    info varchar(255) DEFAULT '' NOT NULL,
    PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    update_option( 'wdm_wpma_version', $wdm_wpma_version, /*autoload*/true );
}

/* Update database and register hooks on activation */
function wdm_wpma_activate() {
    wdm_wpma_updateDatabaseTables();
    register_uninstall_hook( __FILE__, 'wdm_wpma_uninstall' );
    register_deactivation_hook( __FILE__, 'wdm_wpma_deactivate' );
}
register_activation_hook( __FILE__, 'wdm_wpma_activate' );

/* Upgrade routine: check for mismatching version numbers and run database update if necessary */
function wdm_wpma_check_update_db () {
    global $wdm_wpma_version;
    if ( get_option( 'wdm_wpma_version' ) !== $wdm_wpma_version ) {
        wdm_wpma_updateDatabaseTables();
    }
}
add_action( 'plugins_loaded', 'wdm_wpma_check_update_db', 10, 0 );




/* ***************************************************************
Options Page 
**************************************************************** */
if (is_admin()) {
    require_once( plugin_dir_path( __FILE__ ) . 'mail-queue-options.php' );
}




/* ***************************************************************
REST API
**************************************************************** */


function wdm_wpma_add_rest_endpoints () {
    register_rest_route('wpma/v1', '/message/(?P<id>[\d]+)', array(
        'methods'             => 'GET',
        'callback'            => 'wdm_wpma_rest_get_message',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ));
}
add_action('rest_api_init', 'wdm_wpma_add_rest_endpoints', 10, 0);


function wdm_wpma_rest_get_message ($request) {
    global $wpdb, $wdm_wpma_options;
    $tableName = $wpdb->prefix.$wdm_wpma_options['tableName'];
    $id        = intval($request['id']);
    $row       = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `$tableName` WHERE `id` = %d", $id ), ARRAY_A );
    if ($row) {
        // Search for content-type header to detect html emails
        $is_content_type_html = false;
        $headers = maybe_unserialize( $row['headers'] );
        if (is_string($headers)) {
            $headers = [ $headers ];
        } else if (!is_array($headers)) {
            $headers = [];
        }
        foreach ( $headers as $header )  {
            if ( preg_match( '/content-type: ?text\/html/i', $header ) ) {
                $is_content_type_html = true;
                break;
            }
        }
        return array(
            'status' => 'ok',
            'data'   => array(
                'html'   => wdm_wpma_render_list_message(maybe_unserialize($row['message']),$is_content_type_html),
            ),
        );
    } else {
        return new WP_Error( 'no_message', __( 'Message not found' ), array( 'status' => 404 ) );
    }
}

function wdm_wpma_render_list_message ($message, $is_content_type_html) {
    // Split html emails into parts and extract plain text preview
    $parts   = explode( '<body', $message );
    $is_html = $is_content_type_html || count($parts) > 1;
    if ($is_html) {
        if (count($parts) > 1) {
            $header = $parts[0];
            $body   = '<body'.$parts[1];
        } else {
            $header = '';
            $body   = $parts[0];
        }
        $parts = explode('</body>', $body);
        if (count($parts) > 1) {
            $body   = $parts[0].'</body>';
            $footer = $parts[1];
        } else {
            $body   = $parts[0];
            $footer = '';
        }
        if (!function_exists('convert_html_to_text'))  {
            require_once __DIR__.'/lib/html2text/html2text.php';
        }
        // ignore warnings when converting html containing non-converted HTML entities 
        $internal_errors = libxml_use_internal_errors(true);
        $text            = convert_html_to_text( $body );
        libxml_use_internal_errors($internal_errors);
    } else {
        $text   = $message;
        $header = '';
        $body   = '';
        $footer = '';
    }
    $html  = '';
    $html .= '<details class="wdm-wpma-email-source-meta" open><summary>Text</summary><pre class="wdm-wpma-email-plain-text">'.esc_html( $text ).'</pre></details>';
    $html .= $header ? '<details class="wdm-wpma-email-source-meta"><summary>HTML Header</summary><pre>'.esc_html( wdm_wpma_render_html_for_display($header) ).'</pre></details>' : '';
    $html .= $body   ? '<details class="wdm-wpma-email-source-meta"><summary>HTML Body</summary><pre>'.esc_html( wdm_wpma_render_html_for_display($body) ).'</pre></details>' : '';
    $html .= $footer ? '<details class="wdm-wpma-email-source-meta"><summary>HTML Footer</summary><pre>'.esc_html( wdm_wpma_render_html_for_display($footer) ).'</pre></details>' : '';
    return $html;
}

function wdm_wpma_render_html_for_display ($html) {
    $html = preg_replace( '/;base64,[^"\']+("|\')+/', ';base64, [...] $1', $html );
    return $html;
}
