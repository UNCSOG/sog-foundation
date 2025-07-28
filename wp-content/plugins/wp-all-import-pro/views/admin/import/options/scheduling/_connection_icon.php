<?php
$scheduling = \Wpai\Scheduling\Scheduling::create();
$licenseCheckResult = $scheduling->checkLicense()['success'] ?? false;
?>
<span class="wpai-no-license" <?php if ($licenseCheckResult) { ?> style="display: none;" <?php } ?> >

    <a href="javascript:void(0)" style="z-index: 1000;" class="help_scheduling" onclick="handleHelpSchedulingClick();">
        <img style="width: 16px;" class="scheduling-help" title="Automatic Scheduling is a paid service from Soflyy. Click for more info."
             src="<?php echo WP_ALL_IMPORT_ROOT_URL; ?>/static/img/s-question.png"/>
    </a>
</span>


<span class="wpai-license" <?php if (!$licenseCheckResult) { ?> style="display: none;" <?php } ?> >
    <?php if ( $scheduling->checkConnection() ) {
        ?>
        <span title="Connection to WP All Export servers is stable and confirmed"
              style="background-image: none; width: 20px; height: 20px;">
            <img class="scheduling-help" title="Connection to WP All Export servers is stable and confirmed" src="<?php echo WP_ALL_IMPORT_ROOT_URL; ?>/static/img/s-check.png" style="width: 16px; height:16px;"/>
        </span>
        <?php
    } else  { ?>
        <img src="<?php echo WP_ALL_IMPORT_ROOT_URL; ?>/static/img/s-exclamation.png" style="width: 16px; height:16px;"/>
        <span style="margin-left: 8px; font-weight: normal;"><span class="unable-to-connect">Unable to connect, please contact support.</span></span>
        <?php
    }
    ?>
</span>