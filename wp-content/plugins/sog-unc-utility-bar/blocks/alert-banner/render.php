<?php 
/**
 * alert service Banner template
 * this is loaded from \Alert_Service\Banner_Display
 * there are a ton of filters in the class, 
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit; 
//error_log(var_export($data->wrapper_attributes,true));
?>

<div <?php echo $data->wrapper_attributes ?>>

    <?php if( $data->linkify_banner && $data->web_link ): ?>

        <a href="<?php echo $data->web_link ?>" class="banner-link">

    <?php endif; ?>
    
    <?php echo '' !== $data->cap_msg ? '<span class="banner-text">' . $data->cap_msg . '</span>' : '' ; ?>

    <?php if( $data->linkify_banner && $data->web_link ): ?>

        </a>

    <?php endif; ?>

    <?php if( $data->show_skip_link ): ?>

        <a class="screen-reader-text skip-link" href="<?php echo $data->skip_link_target ?>"><?php echo $data->skip_link_text ?></a>

    <?php endif; ?>

</div>