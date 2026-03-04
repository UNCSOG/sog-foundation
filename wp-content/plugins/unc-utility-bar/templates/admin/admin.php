<?php

/**
 * alert service banner display
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;
//error_log('hello from the site admin page');
?>

<?php if (false == \Utility_Bar\Core::using_blocks()) : //some options dont appear when not using blocks 
?>

    <div class="wrap">

        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <form action="options.php" method='post'>

            <div id="post-body">

                <div id="post-body-content" class="utility-bar-settings-page">

                    <?php settings_fields('unc_utility_bar');
                    do_settings_sections('unc_utility_bar'); ?>

                    <p> The following classes can be used to target the site banner for styling with css: </p>

<pre class='site-banner-pre'>
    .utility-bar-site-banner{}
    .utility-bar-site-banner .site-banner-content-wrapper{}
    .utility-bar-site-banner a{}
</pre>

                </div>

                <div id="postbox-container-1" class="utility-bar-settings-page">

                    <?php submit_button(); ?>

                </div>

            </div>

        </form>

    </div>

<?php endif; ?>