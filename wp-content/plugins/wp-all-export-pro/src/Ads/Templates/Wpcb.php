<?php

namespace Wpae\Ads\Templates;

use Wpae\Ads\AdManager;

class Wpcb
{
    protected $adId = '';
    
	public function __construct() {
		$this->adId = basename(str_replace('\\', '/', get_class($this)));
        
		// Hook into WordPress admin to enqueue scripts and styles
		add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
	}

	/**
	 * Enqueue admin CSS and JS files from Assets directory
	 */
	public function enqueueAdminAssets() {
		// Get the absolute path to the class file directory
		$classDir = dirname(__FILE__);

		$fileNamePrefix = $this->adId;

		// Calculate the absolute path to the Assets directory
		$assetsAbsPath = $classDir . '/Assets';

		// Get the URL to the Assets directory
		$assetsUrl = plugins_url('Assets', __FILE__);

		// Enqueue admin CSS file
		wp_enqueue_style(
			'wpae-ads-admin-style',
			$assetsUrl . '/'.$fileNamePrefix.'.css',
			[],
			filemtime($assetsAbsPath . '/'.$fileNamePrefix.'.css')
		);

		// Enqueue admin JS file
		wp_enqueue_script(
			'wpae-ads-admin-script',
			$assetsUrl . '/'.$fileNamePrefix.'.js',
			['jquery'],
			filemtime($assetsAbsPath . '/'.$fileNamePrefix.'.js'),
			true // Load in footer
		);
	}

	public function fetch() {

		// Check if the ad should be displayed using the AdManager
		if (!AdManager::shouldDisplayAd($this->adId)) {
			return;
		}

		// Start with a WordPress action that allows other plugins to hook in before your content
		do_action('pmxe_before_ad_template');

		// Directly output the template
		?>
        <div class="wpcb-ad-container" data-ad-id="<?php echo esc_attr($this->adId); ?>">
            <a href="#" class="wpcb-ad-close" title="Dismiss this ad">&times;</a>
            <h1>
                <span class="wpcb-primary-header">The Most Powerful WordPress</span>
                <span class="wpcb-secondary-header">Code Snippets</span>
                <span class="wpcb-primary-header">Plugin</span>
            </h1><div class="wpcb-description">
                Manage code snippets directly in WordPress with WPCodeBox. Save and share snippets across sites via the Cloud, and access a library of ready-to-use, tested snippets in the Code Snippet Repository.
            </div><div class="wpcb-button-container">
                <div class="wpcb-button">
                    <a class="" href="https://wpcodebox.com/?utm_source=wpai&utm_medium=in-plugin&utm_campaign=cross-promo" target="_blank" data-type="url">
                        Get WPCodeBox
                        <div class="wpcb-button__icon-wrapper">
                            <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M5.975 17.504l14.287.001-6.367 6.366L16.021 26l10.004-10.003L16.029 6l-2.128 2.129 6.367 6.366H5.977z"></path></svg></div>
                    </a>

                </div><a class="wpcb-text-link" href="https://www.youtube.com/embed/pueWcO4NujU?feature=oembed" target="_self" data-lightbox-id="387-330" data-type="lightbox">
                    v2 Overview Video
                </a>
            </div>
        </div>
		<?php

		// WordPress action for after your content
		do_action('pmxe_after_ad_template');
	}
}