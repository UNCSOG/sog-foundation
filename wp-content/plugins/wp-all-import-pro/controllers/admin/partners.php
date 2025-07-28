<?php 
/**
 * Partner Discounts page
 * 
 * Handles the display and management of partner discount offers
 * within the admin interface.
 *
 * @author Prabch <prabch.soflyy@gmail.com>
 */

// Load the Partner Discount SDK for rendering discount UI components
require_once plugin_dir_path(__FILE__) . '../../classes/partner-discount-sdk/partner-discount-sdk.php';

class PMXI_Admin_Partners extends PMXI_Controller_Admin {
	
	public function init() {
		parent::init();
	}
	
	/**
	 * Display the partner discounts page
	 * 
	 * Renders the main partner discounts interface showing all available
	 * partner offers with discount codes and promotional information.
	 *
	 * @return void
	 */
	public function index() {
        echo '<div class="wrap">';
        echo render_partner_discount_ui($this->get_partners());
        echo '</div>';
	}	

    /**
     * Get partner discount data
     * 
     * Returns an array of partner discount information including
     * company details, discount codes, promotional links, and branding.
     * 
     * @return array Array of partner discount data with the following structure:
     *               - name: Partner company name
     *               - desc: Description of the partner's product/service
     *               - code: Discount/coupon code
     *               - discount: Percentage or amount of discount
     *               - link: Promotional/checkout URL
     *               - image: Partner logo/brand image URL
     */
    private function get_partners() {
        return [
            [
                'name' => 'AnalyticsWP',
                'desc' => 'This privacy-compliant WordPress analytics plugin gives detailed insights into user behavior beyond what traditional tools can provide, and has a dedicated integration for WooCommerce.',
                'code' => 'wpallimport2024',
                'discount' => '20%',
                'link' => 'https://analyticswp.com/pricing/?wt_coupon=wpallimport2024',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/05/analyticswp-logo.svg'
            ],
            [
                'name' => 'Breakdance',
                'desc' => 'Created by the same team behind WP All Import, Breakdance is a modern visual site builder for WordPress that combines professional power with drag & drop ease of use.',
                'code' => 'WPAI',
                'discount' => '35%',
                'link' => 'https://breakdance.com/checkout?edd_action=add_to_cart&discount=WPAI&download_id=14&edd_options%5Bprice_id%5D=1',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2024/08/cropped-favicon.png'
            ],
            [
                'name' => 'WPCodeBox',
                'desc' => 'Save code from inside Breakdance to WPCodebox in one click. Use cloud snippets to share across your sites and explore the Code Snippet Repository full of tested snippets.',
                'code' => 'KMWOV0WBKJ',
                'discount' => '20%',
                'link' => 'https://wpcodebox.com/',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2024/08/WPCodeBox-Logo-Small-Dark.png'
            ],
            [
                'name' => 'Oxygen',
                'desc' => 'Created by the same team behind WP All Import, Oxygen is the go-to WordPress website builder for highly advanced users & developers who love to code.',
                'code' => 'WPAI20',
                'discount' => '20%',
                'link' => 'https://oxygenbuilder.com/checkout/?edd_action=add_to_cart&download_id=4790638&discount=WPAI20',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/01/logo-minimal-black.png'
            ],
            [
                'name' => 'Meta Box',
                'desc' => 'Meta Box is a WordPress custom fields plugin for flexible content management using custom post types and custom fields.',
                'code' => 'BREAKDANCE20',
                'discount' => '20%',
                'link' => 'https://metabox.io/pricing/',
                'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/03/metabox-logo-square.png'
            ]
        ];
    }
}