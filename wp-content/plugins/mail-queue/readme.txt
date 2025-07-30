=== Mail Queue ===
Contributors: wdm-team
Donate link: https://www.webdesign-muenchen.de
Tags: email, mail, queue, email log, wp_mail
Requires at least: 5.9
Tested up to: 6.8
Stable tag: 1.4.5
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Take control of emails sent by WordPress. Queue outgoing emails and get notified instantly if your website is trying to send too many emails at once!

== Description ==

This plugin will improve the security of your WordPress website, by delaying the email submission of wp_mail().

If your website shows any strange behaviour, e.g. a spam bot is trying to flood your contact form, you will be alerted directly.

* Intercepts wp_mail() and puts emails into a Queue
* Control how many emails and how often emails are sent
* Log every email submission of the Queue
* Get alerted if your Queue is running full
* Get alerted if WordPress is not able to send emails

== Frequently Asked Questions ==

= Do I need to configure anything? =

Yes. Once activated please go into the Settings of the Plugin to do some configurations.

You can enable the Queue, control how many emails and how often they should be sent.

You can enable the Alerting feature and control at which point exactly you want to be alerted.

= How does this plugin work?

If enabled this plugin intercepts the wp_mail() function. Instead of sending the mails directly, it stores them in the database and sends them step by step with a delay during the WP Cron.

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

This is a simple and effective way to improve the security of your WordPress installation.

Imagine: In case your website is sending spam through wp_mail(), the email Queue would fill up very quickly preventing your website from sending so many spam emails at once. This gives you time and avoids a lot of trouble.

Queue Alerts warn you, if the Queue is longer than usal. You decide at which point you want to be alerted. So you get the chance to have a look if there might be something wrong on the website.

= Can I add emails with a high priority to the queue? =

Yes, you can add the custom `X-Mail-Queue-Prio` header set to `High` to your email. High priority emails will be sent through the standard Mail Queue sending cycle but before all normal emails lacking a priority header in the queue.

*Example 1 (add priority to Woocommerce emails):*

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

*Example 3 (add priority to WordPress reset password emails):*

`add_filter('retrieve_password_notification_email', function ($defaults, $key, $user_login, $user_data) {
	$prio_header = 'X-Mail-Queue-Prio: High';
	if (is_array($defaults['headers'])) {
		$defaults['headers'][] = $prio_header;
	} else {
		$defaults['headers'] .= $defaults['headers'] ? "\r\n" : '';
		$defaults['headers'] .= $prio_header;
	}
	return $defaults;
}, 10, 4);`


= Can I send emails instantly without going through the queue? =

Yes, this is possible (if you absolutely need to do this).

For this you can add the custom `X-Mail-Queue-Prio` header set to `Instant` to your email. These emails are sent instantly circumventing the mail queue. They still appear in the Mail Queue log flagged as `instant`.

Mind that this is a potential security risk and should be considered carefully. Please use only as an exception.

*Example 1 (instantly send Woocommerce emails):*

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

*Example 3 (instantly send WordPress reset password emails):*

`add_filter('retrieve_password_notification_email', function ($defaults, $key, $user_login, $user_data) {
	$prio_header = 'X-Mail-Queue-Prio: Instant';
	if (is_array($defaults['headers'])) {
		$defaults['headers'][] = $prio_header;
	} else {
		$defaults['headers'] .= $defaults['headers'] ? "\r\n" : '';
		$defaults['headers'] .= $prio_header;
	}
	return $defaults;
}, 10, 4);`

= Can I still use the wp_mail() function as ususal? =

Yes, the wp_mail() function works as expected.

When calling wp_mail() the function returns `true` as expected. This means the email has been entered into the queue.

*Exceptions:*

If for some reason the email cannot be entered into the database, wp_mail() will return `false`.

However if you send an email using the instant header option the email will be considered important.
In this case the email will be sent right away, even if there is an error creating a log for it in the queue.

= I have a MultiSite. Can I use Mail Queue? =

Yes, but with limitations.

Do not activate the Mail Queue for the whole network. Instead, please activate it for each site separately. Then it will work smoothly. In a future release we'll add full MultiSite support.

= What is Mail Queues favorite song? =

[youtube http://www.youtube.com/watch?v=425GpjTSlS4]

== Installation ==

Upload the the plugin, activate it, and go to the Settings to enable the Queue. 

Please make sure that your WP Cron is running reliably.

== Changelog ==

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
* Perfomance and security improvements

= 1.1 =
* Resend emails
* Notification if WordPress can't send emails

= 1.0 =
* Initial release.