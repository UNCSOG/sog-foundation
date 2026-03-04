<?php

/**
 * site Banner template
 * you can replace this file in your theme by adding a file to {theme_root}/utility-bar/site-banner.php see GaryJones/Gamajo-Template-Loader for more info
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;
//error_log(var_export($data, true));
?>

<div class='utility-bar-site-banner'>
      <div class="site-banner-content-wrapper">
            <?php echo $data->msg ?>
      </div>
</div>