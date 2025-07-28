<?php

namespace Wpai\Ads;

class AdManager
{
	/**
	 * Constructor to set up WordPress hooks
	 */
	public function __construct() {
		// Add AJAX handler for ad dismissal
		add_action('wp_ajax_wpai_dismiss_ad', [$this, 'dismissAd']);

		// Add script localization on admin_enqueue_scripts
		add_action('admin_enqueue_scripts', [$this, 'localizeAdScripts']);
	}

	/**
	 * Initialize the AdManager as a singleton
	 */
	public static function init() {
		static $instance = null;

		if ($instance === null) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Localize script with the nonce for AJAX requests
	 */
	public function localizeAdScripts() {
		wp_localize_script(
			'pmxi-admin-script',
			'wpaiAdsDismiss',
			['nonce' => wp_create_nonce('wpai_dismiss_ad_nonce')]
		);

	}

	/**
	 * AJAX handler for dismissing ads
	 */
	public function dismissAd() {
		// Verify nonce
		check_ajax_referer('wpai_dismiss_ad_nonce', 'nonce');

		// Get the ad ID
		$ad_id = isset($_POST['ad_id']) ? sanitize_text_field($_POST['ad_id']) : '';

		if (empty($ad_id)) {
			wp_send_json_error(['message' => 'Invalid ad ID']);
			return;
		}

		// Store the dismissed ad in options table to make it permanent site-wide
		$dismissed_ads = get_option('wpai_dismissed_ads', []);

		if (!is_array($dismissed_ads)) {
			$dismissed_ads = [];
		}

		$dismissed_ads[$ad_id] = time();

		update_option('wpai_dismissed_ads', $dismissed_ads);

		wp_send_json_success(['message' => 'Ad dismissed successfully']);
		wp_die();
	}

	/**
	 * Check if the ad should be displayed
	 *
	 * @param string $ad_id The ID of the ad to check
	 * @return bool Whether the ad should be displayed
	 */
	public static function shouldDisplayAd($ad_id) {
		// Get dismissed ads from options table
		$dismissed_ads = get_option('wpai_dismissed_ads', []);

		// If this ad has been dismissed, don't show it
		if (is_array($dismissed_ads) && isset($dismissed_ads[$ad_id])) {
			return false;
		}

		return true;
	}

	/**
	 * Display an ad template if it hasn't been dismissed
	 *
	 * @param string $templateName The name of the template class to instantiate
	 * @return string|void The HTML of the ad or an empty string if it's been dismissed
	 */
	public static function display($templateName)
	{
		$templateName = '\Wpai\Ads\Templates\\' . $templateName;

		if (class_exists($templateName)) {
			$template = new $templateName();
			if (method_exists($template, 'fetch')) {
				return $template->fetch();
			}
		}

		return '';
	}
}