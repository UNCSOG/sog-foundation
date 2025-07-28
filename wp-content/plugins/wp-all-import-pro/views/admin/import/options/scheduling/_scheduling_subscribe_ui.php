<?php

?>
<div class="subscribe" id="scheduling-subscribe-group" style="margin-left: 5px; position: relative;">
                        <div class="tiered-pricing-options">
                            <h3 class="subscribe-heading">Reliable, simple, and powerful. Get started with Automatic Scheduling right now.</h3>
                            <span class="">
                                        No complicated cron jobs, no flakey wp-cron, and no more terminated imports, regardless of host. Set up your schedules right here in WP All Import, and we'll make sure they run.</span>
                            <div style="display: flex;">
                                <div style="flex: 1; padding-right: 10px;" class="pricing-plans">
                                    <h3>Pricing Plans</h3>
                                    <label class="checked">
                                                <span class="input-desc">
                                                <input type="radio" name="pricing_plan" value="single_site" checked>
                                                <span class="description">Single Site</span></span><span class="price-term"><span class="price">$19</span><span class="term">/mo</span></span>
                                    </label>
                                    <label>
                                                <span class="input-desc">
                                                <input type="radio" name="pricing_plan" value="three_sites">
                                                <span class="description">Up to 3 Sites</span></span><span class="price-term"><span class="price">$29</span><span class="term">/mo</span></span>
                                    </label>
                                    <label>
                                                <span class="input-desc">
                                                <input type="radio" name="pricing_plan" value="ten_sites">
                                                <span class="description">Up to 10 Sites</span></span><span class="price-term"><span class="price">$49</span><span class="term">/mo</span></span>
                                    </label>
                                    <label>
                                                    <span class="input-desc">
                                                    <input type="radio" name="pricing_plan" value="unlimited_sites">
                                                    <span class="description">Unlimited Sites</span></span><span class="price-term"><span class="price">$99</span><span class="term">/mo</span></span>
                                    </label>
                                    <div class="plans-include">
                                        <h3>All Plans Include</h3>
                                        <ul>
                                            <li>Unlimited Scheduled Imports & Exports</li>
                                            <li>Unlimited Runs per Import & Export</li>
                                            <li>100% Satisfaction Money Back Guarantee</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="faq-container">
                                    <h3>Frequently Asked Questions</h3>
                                    <div class="faq-divs">
                                        <div class="faq-section collapsed-header open" onclick="toggleSection('faq1')">
                                            <h4><?php _e('How does it work?', 'wp-all-import-pro'); ?></h4>
                                        </div>
                                        <div class="faq-answer" id="faq1" style="display:block;">
                                            <span><?php _e('When you set an import to run on a schedule, our servers will contact your site to make sure that your import starts when you want it to. Our servers will check back every minute to make sure that the import is still running, and will continue doing so until it finishes.', 'wp-all-import-pro'); ?></span>
                                        </div>
                                        <div class="faq-section collapsed-header closed" onclick="toggleSection('faq2')">
                                            <h4><?php _e('Why does it cost money?', 'wp-all-import-pro'); ?></h4>
                                        </div>
                                        <div class="faq-answer" id="faq2" style="display: none;">
                                            <span><?php _e('Your data is very important to us. We have a very resilient, scalable cluster of servers powering Automatic Scheduling. Our highly qualified team is on call 24hrs a day, 7 days a week to make sure your imports run when you need them to run.', 'wp-all-import-pro');?></span>
                                        </div>
                                        <div class="faq-section collapsed-header closed" onclick="toggleSection('faq3')">
                                            <h4><?php _e('What do I get?', 'wp-all-import-pro'); ?></h4>
                                        </div>
                                        <div class="faq-answer" id="faq3" style="display: none;">
                                            <span><?php _e('You\'ll receive a license key for Automatic Scheduling that you can use in WP All Import and WP All Export. You can set up as many imports and exports as you like on the number of sites you selected when subscribing.', 'wp-all-import-pro'); ?></span>
</div>
<div class="faq-section collapsed-header closed" onclick="toggleSection('faq4')">
	<h4><?php _e('What information is shared with Soflyy?', 'wp-all-import-pro'); ?></h4>
</div>
<div class="faq-answer" id="faq4" style="display: none;">
	<span><?php _e('When you set an import to run on a schedule, WP All Import will open an encrypted connection to our servers and send your license key, your site URL, the ID of the import you want to run, the import security key, and the times that you want the import to run.', 'wp-all-import-pro');?></span>
</div>
</div>
</div>
</div>
<span class="button-and-text-container">
                                        <span class="register-site-group hidden" id="register-site-group">
                                            <span class="activate-button-group">
                                                <button class="activate-license" id="activate-license">Activate License
                                                </button>
                                                <span class="loader" style="position: absolute;left: 55px;bottom: 54px;display:none;"></span>
                                            </span>
                                            <span class="activate-license-entry-group">
                                                <input type="password" id="add-subscription-field"
                                                       placeholder="<?php _e('Enter your license', 'wp-all-import-pro'); ?>"/>
                                                <span id="find-subscription-link"><a
		                                                href="http://www.wpallimport.com/portal/automatic-scheduling/"
		                                                target="_blank"><?php _e('Find your license', 'wp-all-import-pro'); ?></a></span>
                                            </span>
                                        </span>
                                        <span class="subscribe-button-group" id="subscribe-button-group">
                                            <button type="button" class="subscribe" id="subscribe"><span>Subscribe Now</span></button>
											<?php if(($showAlreadyLicensed ?? true)) { ?>
												<span class="already-licensed" id="scheduling-already-licensed">Already have a license?</span>
                                            <?php } ?>
                                        </span>
                                        <span class="checkout-trust-group" id="checkout-trust-group">

                                        </span>
                                    </span>
</div>
</div>