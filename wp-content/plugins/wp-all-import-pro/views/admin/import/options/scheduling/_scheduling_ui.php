<?php
$scheduling = \Wpai\Scheduling\Scheduling::create();
$hasActiveLicense = $scheduling->checkLicense()['success'] ?? false;
$cron_job_key = PMXI_Plugin::getInstance()->getOption('cron_job_key');
$options = PMXI_Plugin::getInstance()->getOption();
$import_id = $import->id;
?>

<div class="wpallimport-preview-content" style="max-height: 950px; overflow: visible;">
    <input type="hidden" id="scheduling_has_license" value="<?php echo $hasActiveLicense ? '1' : 0; ?>" />
    <div style="margin-bottom: 20px;">
        <label>
            <input type="radio" name="scheduling_enable"
                   value="0" <?php if ((isset($post['scheduling_enable']) && $post['scheduling_enable'] == 0) || !isset($post['scheduling_enable'])) { ?> checked="checked" <?php } ?>/>
            <h4 style="display: inline-block;"><?php _e('Do Not Schedule'); ?></h4>
        </label>
    </div>
    <div>
        <label>
            <input type="radio" name="scheduling_enable"
                   value="1" <?php if ($post['scheduling_enable'] == 1) { ?> checked="checked" <?php } ?>/>
            <h4 style="margin: 0; display: inline-flex; align-items: center;"><?php _e('Automatic Scheduling', 'wp-all-import-pro'); ?>
                <span class="connection-icon" id="scheduling-connection-icon" style="margin-left: 8px; height: 16px;">
															<?php include_once(PMXI_Plugin::ROOT_DIR . '/views/admin/import/options/scheduling/_connection_icon.php'); ?>
														</span>
            </h4>
        </label>
    </div>
    <<?php echo !empty($is_dialog_context) ? 'form' : 'div';?> id="scheduling-form">
        <div style="margin-bottom: 10px; margin-left:26px;">
            <label style="font-size: 13px;">
				<?php printf(
				/* translators: 1: Import ID */
					esc_html__('Run import ID %d on a schedule.', 'wp-all-import-pro'),
					(int)$import_id
				); ?>
            </label>
        </div>
        <div id="automatic-scheduling"
             style="margin-left: 21px; <?php if ($post['scheduling_enable'] != 1) { ?> display: none; <?php } ?>">
            <div>
                <div class="scheduling-schedule-input" id="scheduling-schedule-input">
                    <div class="input">
                        <label style="color: rgb(68,68,68);">
                            <input type="radio" <?php if (isset($post['scheduling_run_on']) && $post['scheduling_run_on'] != 'monthly') { ?> checked="checked" <?php } ?> name="scheduling_run_on" value="weekly" checked="checked"/>
							<?php _e('Every week on...', 'wp-all-import-pro'); ?>
                        </label>
                    </div>
                    <input type="hidden" style="width: 500px;" name="scheduling_weekly_days" value="<?php echo $post['scheduling_weekly_days']; ?>" id="weekly_days"/>
					<?php if (isset($post['scheduling_weekly_days'])) {
						$weeklyArray = explode(',', $post['scheduling_weekly_days']);
					} else {
						$weeklyArray = array();
					} ?>
                    <ul class="days-of-week" id="weekly" style="<?php if ($post['scheduling_run_on'] == 'monthly') { ?> display: none; <?php } ?>">
                        <li data-day="0" <?php if (in_array('0', $weeklyArray)) { ?> class="selected" <?php } ?>><?php _e('Mon', 'wp-all-import-pro'); ?></li>
                        <li data-day="1" <?php if (in_array('1', $weeklyArray)) { ?> class="selected" <?php } ?>><?php _e('Tue', 'wp-all-import-pro'); ?></li>
                        <li data-day="2" <?php if (in_array('2', $weeklyArray)) { ?> class="selected" <?php } ?>><?php _e('Wed', 'wp-all-import-pro'); ?></li>
                        <li data-day="3" <?php if (in_array('3', $weeklyArray)) { ?> class="selected" <?php } ?>><?php _e('Thu', 'wp-all-import-pro'); ?></li>
                        <li data-day="4" <?php if (in_array('4', $weeklyArray)) { ?> class="selected" <?php } ?>><?php _e('Fri', 'wp-all-import-pro'); ?></li>
                        <li data-day="5" <?php if (in_array('5', $weeklyArray)) { ?> class="selected" <?php } ?>><?php _e('Sat', 'wp-all-import-pro'); ?></li>
                        <li data-day="6" <?php if (in_array('6', $weeklyArray)) { ?> class="selected" <?php } ?>><?php _e('Sun', 'wp-all-import-pro'); ?></li>
                    </ul>
                    <div style="clear: both;"></div>
                    <div>
                        <div class="input every-month">
                            <label style="color: rgb(68,68,68); margin-top: 5px;">
                                <input type="radio" <?php if (isset($post['scheduling_run_on']) && $post['scheduling_run_on'] == 'monthly') { ?> checked="checked" <?php } ?> name="scheduling_run_on" value="monthly"/>
								<?php _e('Every month on the first...', 'wp-all-import-pro'); ?>
                            </label>
                        </div>
                        <input type="hidden" name="scheduling_monthly_days" value="<?php echo isset($post['scheduling_monthly_days']) ? $post['scheduling_monthly_days'] : ''; ?>" id="monthly_days"/>
						<?php if (isset($post['scheduling_monthly_days'])) {
							$monthlyArray = explode(',', $post['scheduling_monthly_days']);
						} else {
							$monthlyArray = array();
						} ?>
                        <ul class="days-of-week" id="monthly" style="<?php if ($post['scheduling_run_on'] != 'monthly') { ?> display: none; <?php } ?>">
                            <li data-day="0" <?php if (in_array('0', $monthlyArray)) { ?> class="selected" <?php } ?>><?php _e('Mon', 'wp-all-import-pro'); ?></li>
                            <li data-day="1" <?php if (in_array('1', $monthlyArray)) { ?> class="selected" <?php } ?>><?php _e('Tue', 'wp-all-import-pro'); ?></li>
                            <li data-day="2" <?php if (in_array('2', $monthlyArray)) { ?> class="selected" <?php } ?>><?php _e('Wed', 'wp-all-import-pro'); ?></li>
                            <li data-day="3" <?php if (in_array('3', $monthlyArray)) { ?> class="selected" <?php } ?>><?php _e('Thu', 'wp-all-import-pro'); ?></li>
                            <li data-day="4" <?php if (in_array('4', $monthlyArray)) { ?> class="selected" <?php } ?>><?php _e('Fri', 'wp-all-import-pro'); ?></li>
                            <li data-day="5" <?php if (in_array('5', $monthlyArray)) { ?> class="selected" <?php } ?>><?php _e('Sat', 'wp-all-import-pro'); ?></li>
                            <li data-day="6" <?php if (in_array('6', $monthlyArray)) { ?> class="selected" <?php } ?>><?php _e('Sun', 'wp-all-import-pro'); ?></li>
                        </ul>
                    </div>
                    <div style="clear: both;"></div>
                    <div id="times-container" style="margin-left: 5px;">
                        <div style="margin-top: 10px; margin-bottom: 5px;">
							<?php _e('What times do you want this import to run?', 'wp-all-import-pro'); ?>
                        </div>
                        <div id="times" style="margin-bottom: 10px;">
							<?php if (isset($post['scheduling_times']) && is_array($post['scheduling_times'])) {
								foreach ($post['scheduling_times'] as $time) { ?>
									<?php if ($time) { ?>
                                        <input class="timepicker" type="text" name="scheduling_times[]" value="<?php echo $time; ?>"/>
									<?php } ?>
								<?php } ?>
                                <input class="timepicker" type="text" name="scheduling_times[]"/>
							<?php } ?>
                        </div>
                        <div style="clear: both;"></div>
                        <div class="timezone-select" style="position:absolute; margin-top: 10px;">
							<?php
							$timezoneValue = $post['scheduling_timezone'] ?? false;
							$timezoneSelect = new \Wpai\Scheduling\Timezone\TimezoneSelect();
							echo $timezoneSelect->getTimezoneSelect($timezoneValue);
							?>
                        </div>
                    </div>
                </div>
                <div style="height: 35px; margin-top: 30px;" id="subscribe-filler">&nbsp;</div>
				<?php if (!$hasActiveLicense) {
                    require_once(PMXI_Plugin::ROOT_DIR . '/views/admin/import/options/scheduling/_scheduling_subscribe_ui.php');

                    require_once(PMXI_Plugin::ROOT_DIR . '/views/admin/import/options/scheduling/_scheduling_active_sites_limit_ui.php');
                } ?>
            </div>
        </div>
    </<?php echo !empty($is_dialog_context) ? 'form' : 'div';?>>
	<?php require PMXI_Plugin::ROOT_DIR . '/views/admin/import/options/scheduling/_manual_scheduling.php'; ?>

	<?php $delete_missing_notice = wp_all_import_delete_missing_notice($post); ?>
	<?php if (!empty($delete_missing_notice)): ?>
        <p class="exclamation"><?php echo $delete_missing_notice; ?></p>
	<?php endif; ?>

</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {

        let $timezone = $('#timezone');

        $timezone.chosen({width: '320px'});

	    <?php if($post['scheduling_timezone'] == 'UTC') {?>
        var timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        if($timezone.find("option:contains('"+ timeZone +"')").length != 0){

            $timezone.val(timeZone);
            $timezone.trigger("chosen:updated");

        }else{

            var parts = timeZone.split('/');
            var lastPart = parts[parts.length-1];

            var opt = $timezone.find("option:contains('"+ lastPart +"')");

            $timezone.val(opt.val());
            $timezone.trigger("chosen:updated");

        }
	    <?php
	    }
	    ?>
    });
</script>