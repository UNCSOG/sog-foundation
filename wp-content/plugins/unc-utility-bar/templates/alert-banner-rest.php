<?php 
/**
 * alert service Banner template that uses rest to get the alert
 * this is loaded from \Alert_Service\Banner_Display
 * there are a ton of filters in the class, 
 * you can replace this file in your theme by adding a file to {theme_root}/utility-bar/alert-banner.php see GaryJones/Gamajo-Template-Loader for more info
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit; 
//error_log(var_export($data,true));
?>

<div <?php echo $data->wrapper_attributes ?>>

    <?php if( $data->linkify_banner && $data->web_link ): ?>

        <a href="<?php echo $data->web_link ?>" class="banner-link">

    <?php endif; ?>
    
    <span class="banner-text" id="alert-banner-rest-msg">

         <?php echo isset($data->cap_msg) && '' !== $data->cap_msg ? '<span class="banner-text">' . $data->cap_msg . '</span>' : 'fetching the alert...' ; ?>

    </span>

    <?php if( $data->linkify_banner && $data->web_link ): ?>

        </a>

    <?php endif; ?>

    <?php if( $data->show_skip_link ): ?>

        <a class="screen-reader-text skip-link" href="<?php echo $data->skip_link_target ?>"><?php echo $data->skip_link_text ?></a>

    <?php endif; ?>

</div>