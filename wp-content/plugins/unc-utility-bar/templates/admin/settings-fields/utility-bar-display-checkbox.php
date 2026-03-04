<?php

/**
 * if you'd like to show the utility bar
 * switch is from bootstrap
 */
$bar_display = get_option('_unc_utility_bar_display');
$msg = 'yes' == $bar_display ? 'The utility bar is being displayed' : 'The utility bar is not being displayed';
?>

<ul class="checkbox_list">

    <li class="checkbox_list-item">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="unc_utility_bar_display" value='yes' name="_unc_utility_bar_display" <?php checked('yes', 
$bar_display, true); ?>>
            <label class="form-check-label" for="unc_utility_bar_display"><?php echo $msg ?></label>
            <p> there are more options in the <a href="<?php echo wp_customize_url() ?>">customizer</a></p>
        </div>
    </li>
</ul>
