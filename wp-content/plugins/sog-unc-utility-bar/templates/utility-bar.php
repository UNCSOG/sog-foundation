<?php

/**
 *
 *  block: the utility bar at the top of the page
 *  @version 2.0.14
 */
if (!defined('ABSPATH')) exit; // Exit if accessed directly.

$block_wrapper_attributes = '';

if (\Utility_Bar\Core::using_blocks()) {
    $block_wrapper_attributes = get_block_wrapper_attributes();
}

if ($block_wrapper_attributes == '' && isset($data)) { // this is not a block that has supports, so we are probably in classic, also if there is a data here its template loader
    $block_wrapper_attributes = $data->wrapper_attributes;
    $show_title = isset($data->show_title) ? $data->show_title : true;
    $title_text = isset($data->title_text) ? $data->title_text : 'The University of North Carolina at Chapel Hill';
    $short_title_text = isset($data->short_title_text) ? $data->short_title_text : 'UNC-CH';
    $show_short_title = isset($data->show_short_title) ? $data->show_short_title : true;
} else {
    $show_title = true;
    $title_text = 'The University of North Carolina at Chapel Hill';
    $show_short_title = true;
    $short_title_text = 'UNC-CH';
}

if ($block_wrapper_attributes == '') { // if we still have nothing at least set the classname
    // $block_wrapper_attributes = 'class="wp-block-utility-bar-utility-bar" ';
}

// get the target
$skip_link_target = isset($attributes['skipLinkTarget']) ? $attributes['skipLinkTarget'] : '#main';
$show_skip_link = isset($attributes['showSkipLink']) ? $attributes['showSkipLink'] : true;

// sanitize the target, this needs work
$skip_link_target_sanitized = sanitize_text_field($skip_link_target);
$theme = wp_get_theme();
$themes_that_dont_need_a_second_skip_link = array('unc-modular-theme','student-aid-2');

if (in_array($theme->template, $themes_that_dont_need_a_second_skip_link) ) {
    $show_skip_link = false;
}

// we need to check if the theme author has overridden this using the template loader
// error_log( var_export($block_wrapper_attributes,true));
?>
<div <?php echo $block_wrapper_attributes  ?>>

    <div class='utility-bar-container'>

        <?php if ($show_skip_link) : ?>

            <nav aria-label='skip'>
                <a href='#unc-search' name="global UNC navigation" class='utility-bar-skip-link screen-reader-text'>skip to the end of the global utility bar</a>
            </nav>

        <?php endif; ?>

        <div class='utility-bar-row'>

            <a href='http://www.unc.edu/' id='utility-bar-unc-link' target="_blank" rel="noopener">

                <svg id="unc-interlocking-logo" xmlns="http://www.w3.org/2000/svg" width="40" height="35" version="1.1" viewBox="0 0 40 35">
                    <path id="logo-stroke" d="M36,19.7c-.1-.1-.3-.2-.5-.2s0,0,0,0c-.2,0-.4.1-.5.3-.3.5-.7.9-1.1,1.4-.2-1.2-.2-2.4-.2-3.6s0-2.4.2-3.6c.5.4.8.9,1.1,1.4.1.2.3.3.5.3h0c.2,0,.4,0,.5-.2l3.8-3.8c.3-.3.3-.6,0-.9-1.1-1.4-2.5-2.6-4.1-3.6.3-.6.6-1.2.9-1.8.1-.2.1-.3,0-.5s-.2-.3-.3-.4c-1.6-.9-3.3-1.7-5.1-2.3,0,0-.1,0-.2,0-.3,0-.5.1-.6.3-.4.6-.7,1.3-1,2-2.6-.8-5.4-1.1-8.3-1.1s-5,.3-7.4.9l-1.7-1.9c-.1-.1-.3-.2-.5-.2s-.1,0-.2,0c-1.9.6-3.8,1.4-5.4,2.4-.2.1-.3.3-.3.5,0,.2,0,.4.1.5.3.5.6,1.1.9,1.7C2.5,9.7,0,13.5,0,17.5s2.4,7.8,6.7,10.5c-.3.6-.6,1.2-.9,1.8-.1.2-.1.3,0,.5,0,.2.2.3.3.4,1.6.9,3.3,1.7,5.1,2.3,0,0,.1,0,.2,0,.3,0,.5-.1.6-.3.4-.6.7-1.3,1-2,2.6.8,5.4,1.1,8.3,1.1s5-.3,7.4-.9l1.7,1.9c.1.1.3.2.5.2s.1,0,.2,0c1.9-.6,3.8-1.4,5.4-2.4.2-.1.3-.3.3-.5,0-.2,0-.4-.1-.5-.3-.5-.6-1.1-.9-1.7,1.7-1.1,3.1-2.3,4.1-3.6.2-.3.2-.7,0-.9l-3.8-3.8h0ZM15.8,17.5v-1l7.5,8.5c-.7,0-1.4,0-2,0-2.1,0-4.3-.3-6.2-.8.5-2.2.7-4.5.7-6.8h0ZM26.7,17.5v1l-7.5-8.5c.7,0,1.4,0,2,0,2.1,0,4.3.3,6.2.8-.5,2.2-.7,4.5-.7,6.8h0ZM6.8,17.5c0-1.5.9-2.7,1.8-3.6.2,1.2.2,2.4.2,3.6s0,2.4-.2,3.6c-1-.9-1.8-2.1-1.8-3.6Z" />
                    <path id="logo-fill" d="M33.3,22.5c-.3-1.6-.4-3.2-.4-4.9s.1-3.4.4-4.9c.9.7,1.7,1.4,2.2,2.3l3.8-3.8c-1.1-1.4-2.7-2.7-4.5-3.8.4-.9.8-1.7,1.3-2.4-1.5-.9-3.1-1.6-4.9-2.2-.4.8-.9,1.6-1.3,2.4-2.6-.8-5.6-1.3-8.7-1.3s-5.3.3-7.6,1l-2-2.3c-1.9.6-3.7,1.4-5.3,2.3h0c.4.8.9,1.6,1.3,2.4C3.4,9.8.7,13.5.7,17.5s2.7,7.7,6.9,10.2c-.4.9-.8,1.7-1.3,2.4,1.5.9,3.1,1.6,4.9,2.2.4-.8.9-1.6,1.3-2.4,2.6.8,5.6,1.3,8.7,1.3s5.3-.3,7.6-1l2,2.3c1.9-.6,3.7-1.4,5.3-2.3h0c-.4-.8-.9-1.5-1.3-2.4,1.8-1.1,3.3-2.3,4.5-3.8l-3.8-3.8c-.5.8-1.3,1.6-2.2,2.3h0ZM9.2,22.5c-1.9-1.4-3-3.1-3-4.9s1.1-3.6,3-4.9c.3,1.6.4,3.2.4,4.9s-.1,3.4-.4,4.9ZM21.2,25.8c-2.5,0-4.9-.3-7-.9.5-2.3.8-4.8.8-7.3s0-2-.1-3l9.7,11c-1.1.1-2.3.2-3.4.2h0ZM27.5,20.5l-9.7-11c1.1-.1,2.3-.2,3.4-.2,2.5,0,4.9.3,7,.9-.5,2.3-.8,4.7-.8,7.3s0,2,.1,3h0Z" />
                </svg>

                <div id='unc-ub-title'>
                    <?php if ($show_short_title): ?>
                        <div class='mobile-only unc-utility-bar-short-title'<?php if (!$show_title) echo " style='display: none;'"; ?>>
                            <?php
                            // Always show short title if set, otherwise fallback to the default
                            if (isset($short_title_text) && $short_title_text !== '' && $short_title_text !== null) {
                                echo esc_html($short_title_text);
                            } elseif( !isset($short_title_text) || trim($short_title_text) === '' ) {
                                echo esc_html('UNC-CH');
                            } else {
                                // Output nothing if the short title is not set and the default is empty.
                                echo '';
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($show_title): ?>
                        <div class='desktop-only unc-utility-bar-title'>
                            <?php
                            // Always show desktop title if set, otherwise fallback to the default
                            if (isset($title_text) && trim($title_text) !== '' && $title_text !== null) {
                                echo esc_html($title_text);
                            } elseif( !isset($title_text) || trim($title_text) === '' ) {
                                echo esc_html('The University of North Carolina at Chapel Hill');
                            } else {
                                // Output nothing if the title is not set and the default is empty.
                                echo '';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </a>

            <ul id='utiltity-bar-nav'>

                <?php
                $menu_items = \Utility_Bar\Menu_Items::get_enabled_items();

                foreach ($menu_items as $item) :
                    $classes = 'utiltity-bar-nav-item';

                    if (!empty($item['hide_mobile'])) {
                        $classes .= ' md-down-hidden-list-item';
                    }

                    $link_id = '';

                    if ($item['id'] === 'unc-search') {
                        $link_id = " id='unc-search'";
                    }
                ?>

                <li class='<?php echo esc_attr($classes); ?>'>

                    <a href='<?php echo esc_url($item['url']); ?>' class='utiltity-bar-link'<?php echo $link_id; ?>><?php echo esc_html($item['label']); ?></a>

                </li>

                <?php endforeach; ?>

            </ul>

        </div>

        <nav aria-label='skip'>
            <a href='<?php echo $skip_link_target_sanitized ?>' class='utility-bar-skip-link screen-reader-text'>skip to main</a>
        </nav>

    </div>

</div>