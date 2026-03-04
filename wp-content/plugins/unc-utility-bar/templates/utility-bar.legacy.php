<?php

/**
 * 
 *  block: the utility bar at the top of the page
 *  @version 2.0.14
 */
if (!defined('ABSPATH')) exit; // Exit if accessed directly.

$block_wrapper_attributes = '';
if( \Utility_Bar\Core::using_blocks() ){
    $block_wrapper_attributes = get_block_wrapper_attributes();
}

if( $block_wrapper_attributes == '' && isset($data)){//this is not a block that has supports, so we are probably in classic, also if there is adata here its template loader
    $block_wrapper_attributes = $data->wrapper_attributes;
}

if( $block_wrapper_attributes == '' ){//if we still have nothing at least set the classname
    //$block_wrapper_attributes = 'class="wp-block-utility-bar-utility-bar" ';
}
//get the target
$skip_link_target = isset($attributes['skipLinkTarget']) ? $attributes['skipLinkTarget'] : '#main';
$show_skip_link = isset($attributes['showSkipLink']) ? $attributes['showSkipLink'] : true ;
//sanatize the target, this needs work
$skip_link_target_sanitized = sanitize_text_field($skip_link_target);
$theme = wp_get_theme();
if( $theme->template === 'unc-modular-theme' ){
    $show_skip_link = false;
}
//we need to check if the theme author has overridden this using the template loader
//error_log( var_export($block_wrapper_attributes,true));
?>
<div <?php echo $block_wrapper_attributes  ?>>

    <div class='utility-bar-container'>

        <?php if ($show_skip_link) : ?>

            <nav aria-label='skip'><a href='#unc-search' name="global UNC navigation" class='utility-bar-skip-link screen-reader-text'>skip to the end of the global utility bar</a></nav>

        <?php endif; ?>

        <div class='utility-bar-row'>

            <div id='unc-ub-title' class='unc-link-column'>

                <a href='http://www.unc.edu/' class='utility-bar-unc-link'>The University <span class='em'>of</span> North Carolina <span class='em'>at</span> Chapel Hill</a>

            </div>

            <div id='unc-ub-nav' class='unc-link-column'>

                <ul id='utiltity-bar-nav'>
                    <li class='utiltity-bar-nav-item'><a href='https://www.unc.edu/about/accessibility/' class='utiltity-bar-link' disabled='true'>Accessibility</a></li>
                    <li class='utiltity-bar-nav-item md-down-hidden-list-item'><a href='https://www.unc.edu/events/' class='utiltity-bar-link'>Events</a></li>
                    <li class='utiltity-bar-nav-item md-down-hidden-list-item'><a href='http://library.unc.edu/' class='utiltity-bar-link'>Libraries</a></li>
                    <li class='utiltity-bar-nav-item'><a href='https://maps.unc.edu/' class='utiltity-bar-link'>Maps</a></li>
                    <li class='utiltity-bar-nav-item'><a href='https://www.unc.edu/a-z/' class='utiltity-bar-link'>Departments</a></li>
                    <li class='utiltity-bar-nav-item md-down-hidden-list-item'><a href='https://connectcarolina.unc.edu/' class='utiltity-bar-link'>ConnectCarolina</a></li>
                    <li class='utiltity-bar-nav-item'><a href='https://www.unc.edu/search' class='utiltity-bar-link' id='unc-search'>UNC Search</a></li>
                </ul>

            </div>

        </div>

        <nav aria-label='skip'><a href='<?php echo $skip_link_target_sanitized ?>' class='utility-bar-skip-link screen-reader-text'>skip to main</a></nav>

    </div>

</div>