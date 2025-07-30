<?php
if(!defined('ABSPATH')) {
    die();
}
$scheduling = \Wpae\Scheduling\Scheduling::create();
$licenseCheckResult = $scheduling->checkLicense()['success'] ?? false;
?>
<span class="wpai-no-license" <?php if ($licenseCheckResult) { ?> style="display: none;" <?php } ?> >

    <a href="javascript:void(0)" style="z-index: 1000;" class="wpallexport-help help_scheduling"
       title="Automatic Scheduling is a paid service from Soflyy. Click for more info."
       onclick="handleHelpSchedulingClick();">

    </a>
</span>

<?php if ($licenseCheckResult) { ?>
    <span class="wpai-license">
        <?php if ( $scheduling->checkConnection() ) {
            ?>
            <span class="wpallexport-help" title="Connection to WP All Export servers is stable and confirmed"
                  style="background-image: none; width: 20px; height: 20px;">
            <img src="<?php echo esc_url(PMXE_ROOT_URL); ?>/static/img/s-check.png" style="width: 16px; height:16px;"/>
            </span>
            <?php
        } else  { ?>
            <img src="<?php echo esc_url(PMXE_ROOT_URL); ?>/static/img/s-exclamation.png" style="width: 16px; height:16px;"/>
            <span class="wpai-license" style="margin-left: 8px; font-weight: normal;"><span class="unable-to-connect">Unable to connect, please contact support.</span></span>
            <?php
        }
        ?>
    </span>
<?php
}

?>