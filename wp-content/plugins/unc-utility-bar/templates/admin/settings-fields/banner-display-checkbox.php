<?php

/**
 * if you'd like to show the banner or not
 * switch is from bootstrap
 */

$banner_display = get_option('_unc_utility_banner_display_bool');
$msg = 'yes' == $banner_display ? 'The site banner is being displayed' : 'The site banner is not being displayed';
?>

<ul class="checkbox_list">
    <li class="checkbox_list-item">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="unc_utility_banner_display_bool" value='yes' name="_unc_utility_banner_display_bool" <?php checked('yes', $banner_display, true); ?>>
            <label class="form-check-label" for="unc_utility_banner_display_bool"><?php echo $msg ?></label>
        </div>
    </li>
</ul>