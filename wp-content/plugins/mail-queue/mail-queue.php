<?php

/**
 * Plugin Name:       Mail Queue
 * Plugin URI:        https://www.webdesign-muenchen.de/wordpress-plugin-mail-queue/
 * Description:       Take Control and improve Security of wp_mail(). Queue and log outgoing emails, and get alerted, if your website wants to send more emails than usual.
 * Version:           1.6.1
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

$wdm_wpma_version = '1.6.1';





/* ***************************************************************
PLUGIN DEFAULT SETTINGS
**************************************************************** */
function wdm_wpma_get_settings() {
    $defaults = array(
        'enabled'             => '0',
        'alert_enabled'       => '0',
        'email'               => get_option('admin_email'),
        'email_amount'        => '10',
        'pause_on_alert'      => '0',
        'queue_amount'        => '1',
        'queue_interval'      => '5',
        'queue_interval_unit' => 'minutes',
        'clear_queue'         => '14',
        'tableName'           => 'mail_queue',
        'triggercount'        => 0,
    );
    $args = get_option('wdm_wpma_settings');
    $options = wp_parse_args($args,$defaults);

    if ($options['queue_interval_unit'] === 'seconds') {
        $options['queue_interval'] = intval($options['queue_interval']);
        if ($options['queue_interval'] < 10) { $options['queue_interval'] = 10; } // Minimum Interval 10 Seconds
    } else {
        $options['queue_interval'] = intval($options['queue_interval']) * 60;
    }

    $options['clear_queue'] = intval($options['clear_queue']) * 24;
    return $options;
}


// Re-syncs the settings global from the DB. Required after any mid-request
// update_option('wdm_wpma_settings', …): wdm_wpma_options() only re-reads the DB
// when the global is unset, so without this call it would keep returning stale
// values. Callers that fetched settings before the refresh must re-call
// wdm_wpma_options() to see the new state (the accessor returns a value copy).
function wdm_wpma_refresh_settings() {
    global $wdm_wpma_options;
    $wdm_wpma_options = wdm_wpma_get_settings();
    return $wdm_wpma_options;
}


// Always returns the settings array, repopulating the global if a
// function-scope include (e.g. WP-CLI) left it unset.
function wdm_wpma_options() {
    global $wdm_wpma_options;
    if ( !is_array($wdm_wpma_options) ) {
        $wdm_wpma_options = wdm_wpma_get_settings();
    }
    return $wdm_wpma_options;
}


// Returns the fully-prefixed queue table name, sanitized for direct use in SQL
// (the name is config-derived, not user input; sanitizing in one place still
// closes the "table name concatenated into SQL" smell).
function wdm_wpma_table() {
    global $wpdb;
    $options = wdm_wpma_options();
    return $wpdb->prefix.preg_replace('/[^A-Za-z0-9_]/', '', $options['tableName']);
}


// Track the DB row id of the mail currently handed to wp_mail(), so the
// wp_mail_failed handler can mark the correct row as 'error'. 0 = none in flight.
function wdm_wpma_set_current_mail_id ($id) {
    global $wdm_wpma_mailid;
    $wdm_wpma_mailid = intval($id);
}

function wdm_wpma_get_current_mail_id () {
    global $wdm_wpma_mailid;
    return isset($wdm_wpma_mailid) ? intval($wdm_wpma_mailid) : 0;
}


// Safer drop-in for maybe_unserialize(): refuses to instantiate any class.
// Use on DB columns we know to contain only scalars/arrays (recipient, headers, attachments).
function wdm_wpma_safe_unserialize ($value) {
    if (!is_string($value) || !is_serialized($value)) { return $value; }
    return @unserialize($value, ['allowed_classes' => false]);
}


// One-shot guard against replaying a state-changing admin request.
//
// The Log/Queue list forms submit via GET so that search/filter/pagination
// state stays in the URL (that is the WP_List_Table pattern). A side effect is
// that a dispatched bulk action (delete / resend / send now) also ends up as a
// bookmarkable URL: revisiting it — browser Back, history restore, link
// prefetch — would re-run the action while the nonce is still valid (nonces
// stay valid for hours), re-queueing or re-sending the same mails.
//
// This claims a *specific* action exactly once. The fingerprint covers the
// action name, the affected row ids and the nonce, so only the identical
// request is suppressed; a different selection (even under the same reusable
// nonce) is a different fingerprint and runs normally. Returns true the first
// time a fingerprint is claimed (caller proceeds), false on any repeat within
// the window (caller skips).
//
// The TTL spans the whole nonce lifetime (nonces verify for up to 24h): a
// replay is possible for exactly as long as its nonce still verifies, so a
// shorter window would only cover part of the threat. The flip side — a
// deliberately repeated identical action (same action, same ids, same still-
// valid nonce) is also suppressed and reports 0 processed rows — is accepted:
// only "resend the exact same selection again" is affected, and the zero-count
// notice makes it visible.
//
// The claim uses add_option() as the cross-process gate on the unique
// option_name key. (On current WP add_option() is INSERT..ON DUPLICATE KEY
// UPDATE, so two writers racing with *different* values can in theory both
// report success across a second boundary — converting the acquire to a plain
// INSERT IGNORE is a logged follow-up. The per-row 'sending' claim in the
// sender independently prevents double-sends regardless.) An expiring option
// row is used rather than a transient on purpose: under a persistent object
// cache a transient has no guaranteed lifetime and may be dropped before it
// expires, which would defeat a guard that must reliably fire within its
// window. On sites without an object cache the two behave identically.
//
// Each claim is its own option row (prefix wdm_wpma_claim_), self-expiring:
// an expired row is treated as free and reclaimed in place. A best-effort
// sweep of expired sibling rows keeps them from accumulating.
function wdm_wpma_claim_action_once ( $fingerprint, $ttl = DAY_IN_SECONDS ) {
    global $wpdb;
    $key        = 'wdm_wpma_claim_' . md5( (string) $fingerprint );
    $now        = time();
    $expires_at = $now + max( 1, intval( $ttl ) );

    wdm_wpma_sweep_expired_claims();

    // Fresh claim: atomic on the unique option_name key.
    if ( add_option( $key, $expires_at, '', /*autoload*/false ) ) { return true; }

    // Row exists — only reclaimable if the previous claim has expired. Compare-
    // and-swap so two concurrent requests can't both reclaim it: exactly one
    // UPDATE changes the row (the DB row-lock serializes them).
    $reclaimed = $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->options} SET option_value = %d WHERE option_name = %s AND CAST(option_value AS UNSIGNED) < %d",
        $expires_at, $key, $now
    ) );
    // Direct SQL bypasses the option cache; drop the stale entry so later reads
    // (and add_option's notoptions flag) stay coherent under a persistent cache.
    wp_cache_delete( $key, 'options' );

    return $reclaimed === 1;
}

// Best-effort removal of expired replay-guard rows so they don't accumulate in
// wp_options. Cheap and bounded: only this plugin's own claim rows, only the
// expired ones.
function wdm_wpma_sweep_expired_claims () {
    global $wpdb;
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d",
        $wpdb->esc_like( 'wdm_wpma_claim_' ) . '%', time()
    ) );
}


function wdm_wpma_is_pause_on_alert_active() {
    return get_option('wdm_wpma_pause_on_alert_active') === '1';
}

function wdm_wpma_clear_pause_on_alert_state() {
    delete_option('wdm_wpma_pause_on_alert_active');
}

function wdm_wpma_must_activate_pause_on_alert() {
    $options = wdm_wpma_options();

    if (!isset($options['enabled']) || $options['enabled'] !== '1') { return false; }
    if (!isset($options['alert_enabled']) || $options['alert_enabled'] !== '1') { return false; }
    if (!isset($options['pause_on_alert']) || $options['pause_on_alert'] !== '1') { return false; }
    if (wdm_wpma_is_pause_on_alert_active()) { return false; }

    return true;
}

function wdm_wpma_maybe_activate_pause_on_alert() {

    if (!wdm_wpma_must_activate_pause_on_alert()) { return false; }

    $settings = get_option('wdm_wpma_settings', array());
    if (!is_array($settings)) { $settings = array(); }
    $settings['enabled'] = 'paused';

    update_option('wdm_wpma_settings', $settings, true);
    update_option('wdm_wpma_pause_on_alert_active', '1', false);

    wdm_wpma_push_queue_event([
        'name' => 'autopause-activated',
    ]);

    wdm_wpma_refresh_settings();

    return true;
}



$wdm_wpma_mailid = 0;
$wdm_wpma_options = wdm_wpma_get_settings(); // Get Settings





/* ***************************************************************
Overwrite wp_mail() if Plugin enabled
**************************************************************** */
$wdm_wpma_pre_wp_mail_priority = 99999;

// Intercept in EVERY context, WP-Cron included: a mail sent from a cron job
// (WooCommerce follow-ups, newsletters, or malware via wp_schedule_single_event)
// must be queued, logged and counted towards the alert like any other. The
// plugin's own sends stay exempt by bracketing wp_mail() with
// remove_filter/add_filter at each dispatch site, not by disarming the filter
// wholesale for the whole cron request.
if (in_array($wdm_wpma_options['enabled'], ['1', 'paused'], /*strict*/true)) {
    // High priority: run late in the game to react to previous filters
    add_filter('pre_wp_mail', 'wdm_wpma_prewpmail', $wdm_wpma_pre_wp_mail_priority, 2);
}

// pre WP Mail Filter
function wdm_wpma_prewpmail($return, $atts) {

    global $wpdb;
    $wdm_wpma_options = wdm_wpma_options();

    if (!is_null($return)) {
        // Another pre_wp_mail filter has already returned a value, so the mail is not added to the queue
        return $return;
    }

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
    // - Instant Sending or Prio Mail? (the first X-Mail-Queue-Prio header wins)
    // - Track if ContentType / From header is set
    // Scan every header (no early break) so a Content-Type / From that sits
    // after the X-Mail-Queue-Prio header is still detected;
    // All X-Mail-Queue-Prio headers are stripped so they don't leak outbound.
    $filtered_headers     = $headers;
    $hasContentTypeHeader = false;
    $hasFromHeader        = false;
    $prio_status_found    = false;
    foreach($headers as $index => $val) {
        $val = trim($val);
        if (preg_match("#^X-Mail-Queue-Prio: +Instant *$#i",$val)) {
            unset($filtered_headers[$index]);
            if (!$prio_status_found) {
                $status = 'instant';
                $prio_status_found = true;
            }
        } else if (preg_match("#^X-Mail-Queue-Prio: +High *$#i",$val)) {
            unset($filtered_headers[$index]);
            if (!$prio_status_found) {
                $status = 'high';
                $prio_status_found = true;
            }
        } else if (preg_match('#^Content-Type:#i',$val)) {
            $hasContentTypeHeader = true;
        } else if (preg_match('#^From:#i',$val)) {
            $hasFromHeader = true;
        }
    }
    $headers = array_values($filtered_headers);

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
                    $headers[] = 'From: '.$fromName.' <'.$from_Email.'>';
                } else {
                    $headers[] = 'From: '.$from_Email;
                }
            }
        }
    }


    // Write email in Queue
    $tableName = wdm_wpma_table();
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
        
        $subfolder = time().'-'.wp_generate_password(24,/*special_chars*/false,/*extra_special_chars*/false);
        $foldercreated = wp_mkdir_p(plugin_dir_path(__FILE__).'attachments/'.$subfolder);
        if (!$foldercreated) {
            if ( defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
                error_log('[mail-queue] Could not create subfolder for email attachment');
            }
            $data['info'] = 'Error: Could not store attachments';
        } else {
            if (!is_array($attachments)) { $attachments = array($attachments); }
            $newattachments = array();
            $copy_failed    = false;
            global $wp_filesystem;
            if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                include_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
            }
            // WP_Filesystem() returns false if no method resolves, leaving $wp_filesystem
            // null. This code runs inside whatever request sent the mail - typically a
            // visitor's form submit - so calling ->copy() on null would fatal the whole
            // page. Degrade instead: queue the mail without attachments.
            if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ) {
                $copy_failed = true;
                if ( defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
                    error_log('[mail-queue] WP_Filesystem unavailable - attachments not stored');
                }
            } else {
                $usednames = array();
                foreach($attachments as $item) {
                    // Two source files can share a basename (invoice.pdf from different
                    // dirs). WP_Filesystem::copy() defaults to $overwrite=false and
                    // RETURNS FALSE if the destination exists, so a collision would
                    // otherwise queue a row pointing at the FIRST file's bytes under the
                    // second file's name - the recipient silently gets the wrong file.
                    $name = basename($item);
                    if ( isset($usednames[$name]) ) {
                        $usednames[$name]++;
                        $ext  = pathinfo($name, PATHINFO_EXTENSION);
                        $base = pathinfo($name, PATHINFO_FILENAME);
                        $name = $base.'-'.$usednames[$name].($ext !== '' ? '.'.$ext : '');
                    } else {
                        $usednames[$name] = 1;
                    }
                    $newfile = plugin_dir_path(__FILE__).'attachments/'.$subfolder.'/'.$name;
                    // Ignoring the return would queue a mail pointing at a file that was
                    // never written - the whole mail then errors out at send time.
                    if ( ! $wp_filesystem->copy($item,$newfile) ) {
                        $copy_failed = true;
                        if ( defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
                            error_log('[mail-queue] Could not copy attachment: '.$item);
                        }
                        break;
                    }
                    array_push($newattachments,$newfile);
                }
            }
            if ( $copy_failed ) {
                // Partial copies are useless: send the mail without attachments rather
                // than with a subset. Drop whatever was copied so no folder is orphaned.
                if ( $newattachments ) {
                    wdm_wpma_delete_attachment_folder( maybe_serialize($newattachments) );
                }
                $data['attachments'] = '';
                $data['info']        = 'Error: Could not store attachments';
            } else {
                $data['attachments'] = maybe_serialize($newattachments);
            }
        }
    }
    $inserted = $wpdb->insert($tableName,$data);

    if ($inserted) {
        wdm_wpma_set_current_mail_id( $wpdb->insert_id );
    }

    if ($status == 'instant') {
        // Returning null lets wp_mail() carry on and really send this mail, so the
        // id must STAY set: a wp_mail_failed during that send has to mark this row.
        return null;
    } else if ( !$inserted ) {
        // No database entry, email cannot be send
        wdm_wpma_set_current_mail_id( 0 );
        return false;
    } else {
        // Row is queued and no wp_mail() follows in this request (we fake success
        // below), so nothing is "in flight" anymore. Clear the id: leaving it set
        // would let a later failing instant-send in the SAME request flip this
        // perfectly fine queued row to 'error' via wdm_wpma_mail_failed().
        wdm_wpma_set_current_mail_id( 0 );
        // Fake Submit by returning 'True'
        return true;
    }

}



// show wp_mail() errors
function wdm_wpma_mail_failed( $wp_error ) {
    global $wpdb;
    $mailid = wdm_wpma_get_current_mail_id();
    if ($mailid != 0) {
        $tableName = wdm_wpma_table();
        $wpMailFailedError = isset( $wp_error->errors ) && isset( $wp_error->errors['wp_mail_failed'][0] ) ? implode( '; ', $wp_error->errors['wp_mail_failed'] ) : 'Unknown';
        $wpdb->update($tableName,array('timestamp'=>current_time('mysql',false),'status'=>'error', 'info'=>$wpMailFailedError),array('id'=>$mailid),array('%s', '%s', '%s'),'%d');
    }
    wdm_wpma_set_current_mail_id( 0 );
    if ( defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
        error_log('[mail-queue] wp_mail_failed: '.print_r($wp_error, true));
    }
}
add_action('wp_mail_failed','wdm_wpma_mail_failed',10,1);



// really send email function to send an email item immediately, without being added to the queue again
function wdm_wpma_really_send_mail($item, $args = null) {
    global $wpdb, $wdm_wpma_pre_wp_mail_priority;
    $wdm_wpma_options = wdm_wpma_options();

    $currentstatus = isset($item['status']) ? $item['status'] : '';
    // No recipient stored → fall back to the alert email and mark the subject.
    // $recipient_fallback makes the success update below persist the changed subject
    // to the DB row, so log and actually dispatched mail stay in sync.
    $recipient_fallback = false;
    if ($item['recipient'] && $item['recipient'] != '') { $to = wdm_wpma_safe_unserialize($item['recipient']); } else { $to = $wdm_wpma_options['email']; $item['subject'] = 'ERROR // '.$item['subject']; $recipient_fallback = true; }
    if ($item['headers'] && $item['headers'] != '') { $headers = wdm_wpma_safe_unserialize($item['headers']); } else { $headers = ''; }
    if ($item['attachments'] && $item['attachments'] != '') { $attachments = wdm_wpma_safe_unserialize($item['attachments']); } else { $attachments = ''; }

    $tableName = wdm_wpma_table();

    // Row is not in a sendable state (already sent/error/event/sending, e.g. a
    // stale double-submit or a row another dispatcher is mid-sending) — refuse
    // silently, never rewrite its status.
    if ( !in_array($currentstatus, ['queue', 'high'], /*strict*/true) ) {
        return false;
    }

    // Atomically CLAIM the row before doing anything that dispatches it. Both the
    // cron picker and the "Send now" bulk action call this function; without a
    // claim, two dispatchers that SELECTed the same still-'queue' row would both
    // send it (admin Send-now racing a cron tick, or two cron ticks). This single
    // conditional UPDATE flips the row queue/high -> 'sending' only while it is
    // still unclaimed; whoever changed the row (rows_affected === 1) owns the send,
    // everyone else backs off. 'sending' is a transient in-flight state, excluded
    // from the cron picker/count ('queue'/'high' only), so a claimed row is never
    // re-picked mid-send. The retention purge removes non-queue/high rows only
    // past the retention window, so a mid-send row (fresh timestamp) is safe -
    // and a crash-stranded 'sending' row gets cleaned up with the old logs.
    $claimed = $wpdb->query( $wpdb->prepare(
        "UPDATE `$tableName` SET `status` = 'sending' WHERE `id` = %d AND `status` IN ('queue','high')",
        $item['id']
    ) );
    if ( $claimed !== 1 ) { return false; } // another dispatcher claimed it first

    // Recipient column is non-empty but unusable (e.g. serialized empty array from a
    // Bcc-only mail, or a corrupt value). Such a row can never be sent — fail it out,
    // otherwise it clogs the front of the queue forever (the cron picker is
    // oldest-first with LIMIT queue_amount, default 1).
    if ( !$to ) {
        $wpdb->update($tableName,array('timestamp'=>current_time('mysql',false),'status'=>'error','info'=>'No valid recipient - email cannot be sent'),array('id'=>$item['id']),'%s','%d');
        wdm_wpma_delete_attachment_folder( $item['attachments'] );
        return false;
    }

    wdm_wpma_set_current_mail_id( $item['id'] );

    remove_filter('pre_wp_mail', 'wdm_wpma_prewpmail', $wdm_wpma_pre_wp_mail_priority);
    $sendstatus = wp_mail($to,$item['subject'],$item['message'],$headers,$attachments); // Send the email for real
    add_filter('pre_wp_mail', 'wdm_wpma_prewpmail', $wdm_wpma_pre_wp_mail_priority, 2);

    wdm_wpma_set_current_mail_id( 0 );

    if ($sendstatus) {
        $infodata = array();
        if (isset($args['send_mode']) && $args['send_mode']) {
            $infodata['send_mode'] = $args['send_mode'];
        } elseif ($currentstatus === 'high') {
            $infodata['prio'] = 'high';
        }
        if ($recipient_fallback) {
            $infodata['recipient_fallback'] = 'no recipient stored - mail was sent to the alert email address';
        }
        // Preserve what the interceptor recorded at queue time (e.g. 'Error:
        // Could not store attachments') - overwriting it here would erase the
        // only trace that the mail went out degraded.
        if ( isset($item['info']) && $item['info'] !== '' ) {
            $infodata['queued_info'] = $item['info'];
        }
        $info = $infodata ? json_encode($infodata) : '';
        $update = array('timestamp'=>current_time('mysql',false),'status'=>'sent','info'=>$info);
        if ($recipient_fallback) {
            $update['subject'] = $item['subject']; // persist the 'ERROR // ' prefix the dispatched mail carries
        }
        $wpdb->update($tableName,$update,array('id'=>$item['id']),'%s','%d');
    } else {
        // wp_mail() can return false WITHOUT firing wp_mail_failed (e.g. another plugin
        // short-circuits via pre_wp_mail). If wdm_wpma_mail_failed() already marked the
        // row, its status is now 'error' and we must not overwrite its richer message;
        // otherwise the row is still 'sending' (our claim) — fail it out here so it
        // can't linger. (It can no longer be 'queue'/'high': we claimed it above.)
        $dbstatus = $wpdb->get_var($wpdb->prepare("SELECT `status` FROM `$tableName` WHERE `id` = %d", $item['id']));
        if ( $dbstatus === 'sending' ) {
            $wpdb->update($tableName,array('timestamp'=>current_time('mysql',false),'status'=>'error','info'=>'wp_mail() failed without error details (possibly blocked by another plugin)'),array('id'=>$item['id']),'%s','%d');
        }
    }

    // remove possible attachments from server after sending email
    wdm_wpma_delete_attachment_folder( $item['attachments'] );

    return $sendstatus;
}

// Deletes the per-mail attachment folder belonging to a queue/log row.
// $attachments_column = raw value of the `attachments` DB column (serialized array of absolute file paths).
// Used after a send attempt and when a row is deleted via bulk action.
function wdm_wpma_delete_attachment_folder( $attachments_column ) {
    if ( ! $attachments_column || $attachments_column == '' ) { return; }
    $attachments = wdm_wpma_safe_unserialize( $attachments_column );
    if ( ! is_array( $attachments ) || empty( $attachments ) ) { return; }

    // Safety net: only ever delete a subfolder inside the plugin's own attachments/
    // directory, never the attachments/ directory itself or anything outside of it.
    // realpath() resolves ../ segments and symlinks, so a crafted DB value like
    // ".../attachments/../../x" cannot escape the base directory; it returns false
    // for nonexistent paths, in which case there is nothing to delete anyway.
    $basedir = realpath( plugin_dir_path(__FILE__).'attachments' );
    $folder  = realpath( dirname( $attachments[0] ) );
    if ( $basedir === false || $folder === false ) { return; }
    if ( $folder === $basedir || strpos( $folder.'/', trailingslashit($basedir) ) !== 0 ) { return; }

    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');
        WP_Filesystem();
    }
    // Same null-$wp_filesystem hazard as the intercept's copy block: WP_Filesystem()
    // can return false. Leaving a folder behind is harmless; fataling here is not -
    // this also runs from the queue sender and from bulk actions.
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ) {
        if ( defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
            error_log('[mail-queue] WP_Filesystem unavailable - attachment folder not deleted: '.$folder);
        }
        return;
    }
    $wp_filesystem->delete( $folder, true, 'd' );
}




/* ***************************************************************
CRON
**************************************************************** */

// Cross-process lock for the cron sender.
// An expires_at value lets a crashed worker's stale
// lock be reclaimed instead of deadlocking the queue forever.
// Returns the owned expires_at (truthy int) on success, or false if
// another worker holds a still-valid lock. The returned value is what
// wdm_wpma_release_lock_cron() uses to release *only its own* lock.
function wdm_wpma_try_lock_cron () {
    global $wpdb;
    $wdm_wpma_options = wdm_wpma_options();
    $key        = 'wdm_wpma_cron_lock';
    $ttl        = max(60, intval($wdm_wpma_options['queue_interval']) * 2);
    $expires_at = time() + $ttl;

    // Fresh acquire: add_option relies on the unique option_name key, so it's
    // atomic across processes - exactly one worker can create the row.
    if (add_option($key, $expires_at, '', /*autoload*/false)) { return $expires_at; }

    // Option exists - try to reclaim it *only if* the previous holder's lock
    // has expired. This must be atomic: a plain get_option -> compare ->
    // update_option is check-then-act, so two workers hitting an expired lock
    // in the same second would both win and double-send. A single conditional
    // UPDATE (compare-and-swap) lets the DB pick exactly one winner - the row
    // is changed only while it still carries the old (expired) expires_at, and
    // whoever's UPDATE actually changed a row (rows_affected === 1) owns it.
    $reclaimed = $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->options} SET option_value = %d WHERE option_name = %s AND CAST(option_value AS UNSIGNED) < %d",
        $expires_at, $key, time()
    ) );
    // Direct SQL bypasses WP's option cache (persistent object cache stays
    // stale otherwise: get_option would return the old value, add_option would
    // trust a stale notoptions flag). Drop the cached entry so it re-reads.
    wp_cache_delete($key, 'options');

    // rows_affected === 1 => this worker changed the row => this worker owns it.
    // 0 (row already advanced past our predicate) or false (query error) => lost.
    if ($reclaimed === 1) { return $expires_at; }
    return false;
}

// Release the lock, but only if this worker still owns it: delete the row
// solely while it still carries the expires_at we wrote. If a parallel worker
// reclaimed the lock in the meantime (writing a different expires_at), this
// matches nothing and correctly leaves that worker's lock intact.
function wdm_wpma_release_lock_cron ( $owned_expires_at ) {
    global $wpdb;
    $key = 'wdm_wpma_cron_lock';
    if ( !$owned_expires_at ) { return; }

    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
        $key, (string) $owned_expires_at
    ) );
    // Keep the option cache coherent with the row we just deleted (see note above).
    wp_cache_delete($key, 'options');
}

function wdm_wpma_search_mail_from_queue() {
    // Ensure the global is populated (WP-CLI may have loaded the plugin in
    // function scope), then bind to it — triggercount++ below relies on the
    // global persisting across calls within the same PHP process.
    wdm_wpma_options();
    global $wdm_wpma_options;

    // Only run if plugin is enabled or paused
    if ( !in_array($wdm_wpma_options['enabled'], ['1', 'paused'], /*strict*/true) ) { return; }

    // Triggercount to avoid multiple runs within the same PHP process
    $wdm_wpma_options['triggercount']++;
    if ($wdm_wpma_options['triggercount'] > 1) { return; }

    // Cross-process lock: a second worker (e.g. a parallel cron tick) must back off,
    // so we don't read the same rows twice and double-send them. The lock returns
    // the expires_at we own; release scopes its delete to that value so we never
    // clear a lock a parallel worker reclaimed after ours expired.
    $lock = wdm_wpma_try_lock_cron();
    if ($lock === false) { return; }

    try {
        wdm_wpma_search_mail_from_queue_locked();
    } finally {
        wdm_wpma_release_lock_cron($lock);
    }
}

function wdm_wpma_search_mail_from_queue_locked() {
    global $wpdb, $wdm_wpma_pre_wp_mail_priority;
    $wdm_wpma_options = wdm_wpma_options();

    $tableName = wdm_wpma_table();

    // Total Mails waiting in the Queue?
    $mailjobsTotal = $wpdb->get_var( "SELECT COUNT(*) FROM `$tableName` WHERE `status` = 'queue' OR `status` = 'high'" );

    // Mails to send
    $mailjobs         = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$tableName` WHERE `status` = 'queue' OR `status` = 'high' ORDER BY `status` ASC, `id` LIMIT %d", intval($wdm_wpma_options['queue_amount'])),'ARRAY_A');
    $mailsInQueue     = is_array($mailjobs) ? count($mailjobs) : 0;

    // Maybe alert admin and auto-pause if too many mails in the Queue.
    if ($wdm_wpma_options['alert_enabled'] === '1' && $mailjobsTotal > intval($wdm_wpma_options['email_amount'])) {

        // Pause sending other emails if option is active and not paused already
        $must_trigger_auto_pause = wdm_wpma_must_activate_pause_on_alert();

        // Last alerts older than 6 hours?
        $alert_cutoff = gmdate('Y-m-d H:i:s', current_time('timestamp', false) - (6 * HOUR_IN_SECONDS));
        $alerts = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$tableName` WHERE `status` = 'alert' AND `timestamp` > %s ORDER BY `id` DESC", $alert_cutoff), 'ARRAY_A');

        // If no recent alert exists, send one;
        // In case new auto-pause is triggered, always send alert independent of existing recent alerts to inform about the pause
        if (!$alerts || $must_trigger_auto_pause) {

            $alertMessage = 'Hi,';
            $alertMessage .= "\n\n";
            $alertMessage .= 'this is an important message from your WordPress website '.esc_url(get_option('siteurl')).'.';
            $alertMessage .= "\n";
            $alertMessage .= "\n".'The Mail Queue Plugin has detected that your website tries to send more emails than expected (currently '.$mailjobsTotal.').';
            $alertMessage .= "\n".'Please take a close look at the email queue, because it contains more messages than the specified limit.';
            $alertMessage .= "\n";
            if ($must_trigger_auto_pause) {
                $alertMessage .= "\n".'Please note: The email sending has been paused automatically. It will remain paused until you re-enable it manually in the plugin settings.';
                $alertMessage .= "\n";
            } elseif ($wdm_wpma_options['enabled'] === 'paused') {
                if (wdm_wpma_is_pause_on_alert_active()) {
                    $alertMessage .= "\n".'Please note: The email sending has been paused automatically due to a recent alert. It will remain paused until you re-enable it manually in the plugin settings.';
                    $alertMessage .= "\n";
                } else {
                    $alertMessage .= "\n".'Please note: The email sending is currently paused. If this is not intentional, please check the plugin settings.';
                    $alertMessage .= "\n";
                }
            }
            $alertMessage .= "\n".'In case this is the usual amount of emails, you can adjust the threshold for alerts in the settings of your Mail Queue Plugin.';
            $alertMessage .= "\n\n";
            $alertMessage .= "-- ";
            $alertMessage .= "\n";
            $alertMessage .= admin_url('admin.php?page=wdm_wpma_mail_queue');
            $alertSubject = '🔴 WordPress Mail Queue Alert - '.esc_html(get_option('blogname'));
            $data = array(
                'timestamp'=> current_time('mysql',false),
                'recipient'=> sanitize_email($wdm_wpma_options['email']),
                'subject'  => $alertSubject,
                'message'  => $alertMessage,
                'status'   => 'alert',
                'info'     => json_encode([
                    'in_queue'       => strval( $mailjobsTotal ),
                    'email_amount'   => intval($wdm_wpma_options['email_amount']),
                    'queue_amount'   => intval($wdm_wpma_options['queue_amount']),
                    'queue_interval' => intval($wdm_wpma_options['queue_interval']),
                ]),
            );
            $wpdb->insert($tableName,$data);
            // Send the alert past our own interceptor. This bracket is load-bearing:
            // the filter is active in cron context too, so without it the alert would
            // queue itself behind the very congestion it warns about (and never go out
            // at all once pause_on_alert has paused sending). The row above is the log
            // entry ('alert'), not a queue entry - it is never dispatched.
            remove_filter('pre_wp_mail', 'wdm_wpma_prewpmail', $wdm_wpma_pre_wp_mail_priority);
            wp_mail($wdm_wpma_options['email'],$alertSubject,$alertMessage);
            add_filter('pre_wp_mail', 'wdm_wpma_prewpmail', $wdm_wpma_pre_wp_mail_priority, 2);
        }

        if ($must_trigger_auto_pause) {
            wdm_wpma_maybe_activate_pause_on_alert();
        }
    }

    // Alert might have triggered a pause (which refreshes the settings global),
    // so re-fetch and check again before sending emails — the local copy from
    // function entry would still hold the pre-pause 'enabled' value.
    $wdm_wpma_options = wdm_wpma_options();
    if ($wdm_wpma_options['enabled'] !== '1') { return; }

    // Send Mails in Queue ($mailjobs is already limited to queue_amount by the SQL LIMIT)
    if ($mailsInQueue > 0) {
        foreach($mailjobs as $item) {
            wdm_wpma_really_send_mail($item);
        }
    }

    // Delete old logs
    $clear_queue_cutoff = gmdate('Y-m-d H:i:s', current_time('timestamp', false) - (intval($wdm_wpma_options['clear_queue']) * HOUR_IN_SECONDS));
    $wpdb->query($wpdb->prepare("DELETE FROM `$tableName` WHERE `status` != 'queue' AND `status` != 'high' AND `timestamp` < %s", $clear_queue_cutoff));

}
add_action('wp_mail_queue_hook','wdm_wpma_search_mail_from_queue');

// Custom Cron Interval
function wdm_wpma_cron_interval( $schedules ) {
    $options = wdm_wpma_options();
    $schedules['wdm_wpma_interval'] = array(
        'interval' => $options['queue_interval'],
        'display'  => esc_html__('WP Mail Queue'), );
    return $schedules;
}
add_filter('cron_schedules','wdm_wpma_cron_interval');

// Set, Remove, or Reschedule Cron.
// Reschedule when the stored interval no longer matches the configured one,
// otherwise a settings change would only take effect after disable+enable.
$scheduled_event   = wp_get_scheduled_event('wp_mail_queue_hook');
$should_be_active  = in_array($wdm_wpma_options['enabled'], ['1', 'paused'], true);
$configured_interval = intval($wdm_wpma_options['queue_interval']);
if ($scheduled_event && !$should_be_active) {
    wp_unschedule_event($scheduled_event->timestamp,'wp_mail_queue_hook');
} else if (!$scheduled_event && $should_be_active) {
    wp_schedule_event(time(),'wdm_wpma_interval','wp_mail_queue_hook');
} else if ($scheduled_event && $should_be_active && intval($scheduled_event->interval) !== $configured_interval) {
    wp_unschedule_event($scheduled_event->timestamp,'wp_mail_queue_hook');
    wp_schedule_event(time(),'wdm_wpma_interval','wp_mail_queue_hook');
}





/* ***************************************************************
Queue Events
**************************************************************** */

function wdm_wpma_push_queue_event ($args) {
    global $wpdb;

    $tableName  = wdm_wpma_table();
    $event_name = isset($args['name']) ? $args['name'] : '';
    $event_data = isset($args['data']) ? $args['data'] : '';
    if (!$event_name) { return false; }

    $data = array(
        'timestamp'=> current_time('mysql',false),
        'status'   => 'event',
        'recipient'=> '',
        'subject'  => $event_name,
        'message'  => '',
        'info'     => $event_data ? (is_string($event_data) ? $event_data : json_encode($event_data)) : '',
    );
    $inserted = $wpdb->insert($tableName,$data);

    return $inserted !== false;
}





/* ***************************************************************
WordPress Password Notification Emails
**************************************************************** */

function wdm_wpma_prioritize_password_reset_mail( $email, $key, $user_login, $user_data ) {
	$wdm_wpma_options = wdm_wpma_options();

    // If Mail Queue is paused send password reset emails instantly to prevent lockouts.
    // Otherwise, send with high priority to make sure that password reset emails are sent before other queued emails.
	$prio_header = isset( $wdm_wpma_options['enabled'] ) && $wdm_wpma_options['enabled'] === 'paused' ? 'X-Mail-Queue-Prio: Instant' : 'X-Mail-Queue-Prio: High';

	if ( empty( $email['headers'] ) ) {
		$email['headers'] = array( $prio_header );
	} elseif ( is_array( $email['headers'] ) ) {
		if ( ! in_array( $prio_header, $email['headers'], true ) ) {
			$email['headers'][] = $prio_header;
		}
	} elseif ( stripos( $email['headers'], $prio_header ) === false ) {
		$email['headers'] .= ( $email['headers'] ? "\r\n" : '' ) . $prio_header;
	}

	return $email;
}
add_filter('retrieve_password_notification_email', 'wdm_wpma_prioritize_password_reset_mail', 10, 4);





/* ***************************************************************
Install/Uninstall/Upgrade
**************************************************************** */


/* Delete plugin options and database table */
function wdm_wpma_uninstall () {
    global $wpdb;

    // Resolve the table BEFORE deleting the settings: wdm_wpma_table() reads the
    // tableName setting, so once wdm_wpma_settings is gone it would fall back to
    // the default and we'd DROP wp_mail_queue - a table this install may not own -
    // while leaving the configured one behind as an orphan.
    $tableName = wdm_wpma_table();

    delete_option( 'wdm_wpma_settings' );
    delete_option( 'wdm_wpma_version' );
    delete_option( 'wdm_wpma_pause_on_alert_active' );
    delete_option( 'wdm_wpma_cron_lock' );
    // Replay-guard rows (one self-expiring option per claimed action).
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like( 'wdm_wpma_claim_' ) . '%'
    ) );

    $wpdb->query( "DROP TABLE IF EXISTS `$tableName`" );
}

/* Delete Cron when Plugin deactivated */
function wdm_wpma_deactivate() {
    wp_clear_scheduled_hook( 'wp_mail_queue_hook' );
}
register_deactivation_hook( __FILE__, 'wdm_wpma_deactivate' );

/* Create/Upgrade MySQL Table on Activation/Upgrade: https://codex.wordpress.org/Creating_Tables_with_Plugins */
function wdm_wpma_updateDatabaseTables() {
    global $wpdb, $wdm_wpma_version;

    // Resolve the table the same way every read/write path does (honours the
    // tableName setting). Hardcoding the default here would migrate/create
    // wp_mail_queue while the plugin keeps using the configured table - the
    // configured one would silently never receive schema changes.
    $tableName = wdm_wpma_table();

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tableName (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    timestamp TIMESTAMP NOT NULL,
    status varchar(55) DEFAULT '' NOT NULL,
    recipient text NOT NULL,
    subject varchar(255) DEFAULT '' NOT NULL,
    message mediumtext NOT NULL,
    headers text NOT NULL,
    attachments text NOT NULL,
    info varchar(255) DEFAULT '' NOT NULL,
    PRIMARY KEY  (id),
    KEY status_timestamp (status,timestamp),
    KEY timestamp (timestamp)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    update_option( 'wdm_wpma_version', $wdm_wpma_version, /*autoload*/true );
}

/* Update database and register hooks on activation */
function wdm_wpma_activate() {
    wdm_wpma_updateDatabaseTables();
    register_uninstall_hook( __FILE__, 'wdm_wpma_uninstall' );
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
    global $wpdb;
    $tableName = wdm_wpma_table();
    $id        = intval($request['id']);
    $row       = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `$tableName` WHERE `id` = %d", $id ), ARRAY_A );
    // event/alert rows are queue bookkeeping, not real mails — the list-table UI offers
    // no message toggle for them, so the endpoint treats them as not found as well.
    if ( $row && in_array( $row['status'], array( 'event', 'alert' ), /*strict*/true ) ) {
        $row = null;
    }
    if ($row) {
        // Search for content-type header to detect html emails
        $is_content_type_html = false;
        $headers = wdm_wpma_safe_unserialize( $row['headers'] );
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
                'html'   => wdm_wpma_render_list_message($row['message'],$is_content_type_html),
            ),
        );
    } else {
        return new WP_Error( 'no_message', __( 'Message not found' ), array( 'status' => 404 ) );
    }
}

function wdm_wpma_render_list_message ($message, $is_content_type_html) {
    // Security: the message body is untrusted (captured from any wp_mail() caller,
    // incl. unauthenticated flows). The esc_html() calls below are load-bearing — they
    // render HTML mails as escaped source, never as live DOM. Any HTML preview
    // must use a sandboxed <iframe srcdoc sandbox> (no allow-scripts), never raw output.
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
