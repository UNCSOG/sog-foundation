<?php
/**
 * Partner Discount SDK
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Soflyy_Partner_Discount')) {

    class Soflyy_Partner_Discount {
        const VERSION = '1.0.1';
        private $partners;

        public function __construct($partners = []) {
            $this->partners = empty($partners) ? $this->get_default_partners() : $partners;
        }

        public static function enqueue_assets() {
            $style_handle = 'partner-discount-ui-style';
            $script_handle = 'partner-discount-ui-script';

            $style_file = plugin_dir_path(__FILE__) . 'partner-discount-ui.css';
            $script_file = plugin_dir_path(__FILE__) . 'partner-discount-scripts.js';

            $style_url = plugin_dir_url(__FILE__) . 'partner-discount-ui.css';
            $script_url = plugin_dir_url(__FILE__) . 'partner-discount-scripts.js';

            if (file_exists($style_file)) {
                wp_enqueue_style($style_handle, $style_url, [], filemtime($style_file));
            }

            if (file_exists($script_file)) {
                wp_enqueue_script($script_handle, $script_url, [], filemtime($script_file), true);
            }
        }

        private function get_default_partners() {
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
                ],
                [
                    'name' => 'Slim SEO',
                    'desc' => 'Premium SEO plugins that are lightweight, performant, and support Meta Box & page builders. Built by the same team at MetaBox.io.',
                    'code' => 'BREAKDANCE20',
                    'discount' => '20%',
                    'link' => 'https://wpslimseo.com/products/',
                    'image' => 'https://www.wpallimport.com/wp-content/uploads/2025/03/slimseo-logo-square.png'
                ]
            ];
        }

        public function render() {
            ob_start();
            $partners = $this->partners;
            ?>
            <div class="soflyy_pd_sdk-section">
                <div class="soflyy_pd_sdk-container">
                    <div class="soflyy_pd_sdk-header">
                        <h1>Partner Discounts</h1>
                        <p>Exclusive discounts on premium WordPress tools and plugins for our users.</p>
                    </div>
                    <div class="soflyy_pd_sdk-inner-wrap">
                        <div class="soflyy_pd_sdk-grid-container">
                            <?php foreach ($partners as $partner): ?>
                                <div class="soflyy_pd_sdk-grid-item">
                                    <div class="soflyy_pd_sdk-partner-card">
                                        <?php if (!empty($partner['discount'])): ?>
                                        <div class="soflyy_pd_sdk-discount-badge"><?php echo esc_html($partner['discount']); ?> OFF</div>
                                        <?php endif; ?>
                                        <div class="soflyy_pd_sdk-partner-top">
                                            <?php if (!empty($partner['image'])): ?>
                                            <div class="soflyy_pd_sdk-partner-logo">
                                                <img src="<?php echo esc_url($partner['image']); ?>" alt="<?php echo esc_attr($partner['name']); ?> logo">
                                            </div>
                                            <?php endif; ?>
                                            <div class="soflyy_pd_sdk-partner-info">
                                                <h3><?php echo esc_html($partner['name']); ?></h3>
                                                <p class="soflyy_pd_sdk-partner-desc"><?php echo esc_html($partner['desc']); ?></p>
                                            </div>
                                        </div>
                                        <div class="soflyy_pd_sdk-partner-bottom">
                                            <div class="soflyy_pd_sdk-partner-code">
                                                <span>Code:</span>
                                                <code data-original-text="<?php echo esc_attr($partner['code']); ?>"><?php echo esc_html($partner['code']); ?></code>
                                            </div>
                                            <a class="soflyy_pd_sdk-claim-btn" href="<?php echo esc_url($partner['link']); ?>" target="_blank" rel="noopener">
                                                Claim
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            </div>  
            <?php
            return ob_get_clean();
        }
    }

    add_action('wp_enqueue_scripts', ['Soflyy_Partner_Discount', 'enqueue_assets']);
    add_action('admin_enqueue_scripts', ['Soflyy_Partner_Discount', 'enqueue_assets']);

    function render_partner_discount_ui($partners = []) {
        $partner_ui = new Soflyy_Partner_Discount($partners);
        return $partner_ui->render();
    }
    
}