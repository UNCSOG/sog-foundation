=== Mail Queue ===
Contributors: wdm-team
Donate link: https://www.webdesign-muenchen.de
Tags: email, mail, queue, email log, wp_mail
Requires at least: 5.9
Tested up to: 7.1
Stable tag: 1.6.1
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Gain full control over your WordPress emails. Queue outgoing emails and get alerts when your site tries to send unusually high volumes.

== Description ==

This plugin enhances the security and stability of your WordPress installation by delaying and controlling wp_mail() email submissions through a managed queue.

If your site exhibits unusual behavior, such as a spam bot repeatedly submitting forms, you will be alerted immediately and can optionally pause delivery automatically while you investigate.

* Intercepts wp_mail() and places outgoing messages in a queue
* Configure how many emails are sent and at what interval
* Pause the queue without disabling the plugin completely
* Log queued emails, sent emails, alerts, and queue events
* Receive alerts when the queue grows unexpectedly
* Optionally auto-pause the queue when an alert is triggered
* Send selected queued emails immediately from the backend
* Automatically prioritize WordPress password reset emails
* Receive alerts when WordPress is unable to send emails

== Frequently Asked Questions ==

= Do I need to configure anything? =

Yes. Once activated, please go into the plugin settings and configure the queue for your website.

You can choose whether the Mail Queue should be Enabled, Paused, or Disabled, and you can control how many emails are sent and how often they should be sent.

You can also enable alerts, define the queue size threshold for alerts, and optionally enable automatic pausing when an alert is triggered.

= How does this plugin work? =

When the Mail Queue is enabled, the plugin intercepts wp_mail(). Instead of sending emails immediately, they are stored in the database and released gradually via WP-Cron according to your configured interval.

The plugin offers three states:

Enabled: emails are added to the queue and sent gradually by WP-Cron.
Paused: emails are still added to the queue, but no queued emails are sent until you enable the queue again.
Disabled: the plugin does not intercept wp_mail() and has no effect on outgoing emails.

= Does this plugin change the way HOW the emails are sent? =

No. This plugin does not change HOW the emails are sent. For example: If you use SMTP for sending, or a third-party-service like Mailgun, everything will still work.

This plugin changes WHEN the emails are sent. By the email Queue it gives you control about how many emails should be sent in which interval.

= Does this plugin work, if I have a caching Plugin installed? =

If you're using a caching plugin like W3 Total Cache, WP Rocket or any other caching solution which generates static HTML files and serves them to visitors, you'll have to make sure you're calling the wp-cron file manually every couple of minutes.

Otherwise your normal WP Cron wouldn't be called as often as it should be and scheduled messages would be sent with big delays.

= What about Proxy-Caching, e.g. NGINX? =

Same situation here. Please make sure you're calling the WordPress Cron by an external service or your webhoster every couple of minutes.

= My form builder supports attachments. What about them? =

You are covered. All attachments are stored temporarily in the queue until they are sent along with their corresponding emails.

= What are Queue alerts? =

Queue alerts are a simple and effective way to improve the security of your WordPress installation.

Imagine your website starts sending spam through wp_mail(). The Mail Queue would fill up quickly instead of sending everything at once. This gives you time to react, avoid a lot of trouble and can reduce the damage significantly.

Queue alerts warn you when the queue grows longer than usual. You configure in the settings at which threshold you want to be alerted. This gives you the chance to review the queue and investigate whether something unusual is happening on the website.

Optionally, you can enable automatic pausing when an alert is triggered. In that case the queue stops sending emails until you change the status back to Enabled.

Please note: This plugin sends at most one alert every six hours while the queue remains above the configured threshold.

= Can I pause the queue without disabling the plugin? =

Yes. The Mail Queue can be set to Paused.

When paused, the plugin still intercepts wp_mail() and stores outgoing emails in the queue, but no queued emails are sent until you change the status back to Enabled.

This is useful if you want to temporarily stop outgoing delivery without disabling the plugin completely.

= Can I add emails with a high priority to the queue? =

Yes, you can add the custom `X-Mail-Queue-Prio` header set to `High` to your email. High priority emails are still processed through the normal Mail Queue cycle, but they are sent before normal queued emails.

*Example 1 (add priority to WooCommerce emails):*

`add_filter('woocommerce_mail_callback_params',function ( $array ) {
	$prio_header = 'X-Mail-Queue-Prio: High';
	if (is_array($array[3])) {
		$array[3][] = $prio_header;
	} else {
		$array[3] .= $array[3] ? "\r\n" : '';
		$array[3] .= $prio_header;
	}
	return $array;
},10,1);`

*Example 2 (add priority to Contact Form 7 form emails):*

When editing a form in Contact Form 7 just add an additional line to the
`Additional Headers` field under the `Mail` tab panel.

`X-Mail-Queue-Prio: High`

= Can I send emails instantly without going through the queue? =

Yes, this is possible (if you absolutely need to do this).

For this you can add the custom `X-Mail-Queue-Prio` header set to `Instant` to your email. These emails are sent immediately and bypass the queue. They still appear in the Mail Queue log so that their delivery remains visible.

Mind that this is a potential security risk and should be considered carefully. Please use only as an exception.

*Example 1 (instantly send WooCommerce emails):*

`add_filter('woocommerce_mail_callback_params',function ( $array ) {
	$prio_header = 'X-Mail-Queue-Prio: Instant';
	if (is_array($array[3])) {
		$array[3][] = $prio_header;
	} else {
		$array[3] .= $array[3] ? "\r\n" : '';
		$array[3] .= $prio_header;
	}
	return $array;
},10,1);`

*Example 2 (instantly send Contact Form 7 form emails):*

When editing a form in Contact Form 7 just add an additional line to the
`Additional Headers` field under the `Mail` tab panel.

`X-Mail-Queue-Prio: Instant`

= Can I send queued emails immediately from the backend? =

Yes. In the Queue tab you can select one or more queued emails and use the `Send now` bulk action.

This sends the selected queued emails immediately without waiting for the next WP-Cron cycle.

The action is only available for queued emails that are still waiting in the queue.

= What are queue events in the log? =

In addition to email entries the Mail Queue log also shows queue events.

These entries document important queue state changes, for example when the queue was enabled, paused, disabled, and auto-paused automatically after an alert.

They are not outgoing emails. They are informational log entries to help you understand what happened and when.

= Can I still use the wp_mail() function as usual? =

Yes, the wp_mail() function works as expected.

When the Mail Queue is enabled or paused, calling wp_mail() normally returns `true` after the email has been accepted by the queue.

*Exceptions:*

If for some reason the email cannot be entered into the database, wp_mail() will return `false`.

If you explicitly send an email using the `Instant` priority header, the email is sent immediately instead of being queued, even if there is an error creating a log for it in the queue.

= I have a MultiSite. Can I use Mail Queue? =

Yes, but with limitations.

Do not activate the Mail Queue for the whole network. Instead, please activate it for each site separately. Then it will work smoothly. In a future release we'll add full MultiSite support.

= What is Mail Queues favorite song? =

[youtube http://www.youtube.com/watch?v=425GpjTSlS4]

== Installation ==

Upload the plugin, activate it, and go to the Settings to enable the Queue. 

Please make sure that your WP Cron is running reliably.

== Changelog ==

= 1.6.1 =
* Fixed rare cases where emails could be sent twice or get lost
* Emails from scheduled (cron) tasks now go through the queue like all others
* Emails with long recipient lists no longer fail to send
* Added a notice when the configured sending rate is unusually high
* Improved settings validation, attachment handling, and various minor fixes

= 1.6.0 =
* Added search and filtering to the Log and Queue lists
* Faster Log and Queue lists on sites with large queues
* Improved bulk actions with clearer result messages and no accidental resubmit on reload
* Deleting queued emails now also cleans up their attachments
* Emails that can no longer be sent are marked as failed instead of blocking the queue
* Improved reliability and various minor fixes

= 1.5.1 =
* Minor bug and security fixes

= 1.5.0 =
* Added queue status modes: Enabled, Paused, and Disabled
* Added optional Auto-Pause on Alert
* Added queue event log entries for status changes and auto-pause
* Added `Send now` bulk action for queued emails
* Automatically prioritize WordPress password reset emails
* Improved admin notices and settings page structure
* Updated FAQ examples and documentation
* Minor bug fixes and improvements

= 1.4.6 =
* Added support for the `pre_wp_mail` hook

= 1.4.5 =
* Check for incompatible plugins
* Minor bug fixes

= 1.4.4 =
* Performance improvements for large emails

= 1.4.3 =
* Updated bulk actions for log and queue lists

= 1.4.2 =
* Database improvements

= 1.4.1 =
* Refine detection for html when previewing emails
* Catch html parse errors when previewing emails

= 1.4 =
* Added support for previewing HTML emails as plain text
* Improved preview for HTML emails
* Minor bug fixes

= 1.3.1 =
* Added support for the following `wp_mail` hooks: `wp_mail_content_type`, `wp_mail_charset`, `wp_mail_from`, `wp_mail_from_name`
* Minor bug fixes

= 1.3 =
* Refactor to use WordPress Core functionality
* Added option to set the interval for sending emails in minutes or seconds
* Added feature to send emails with high priority on top of the queue
* Added feature to send emails instantly without delay bypassing the queue

= 1.2 =
* Performance and security improvements

= 1.1 =
* Resend emails
* Notification if WordPress can't send emails

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.6.1 =
Reliability and data-integrity update: fixes several ways emails could be sent twice or get lost, and closes gaps in cron-context interception. Recommended for all users.