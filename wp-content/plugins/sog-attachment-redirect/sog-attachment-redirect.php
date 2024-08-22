<?php
/**
 * Plugin Name: SOG Attachment Redirect
 * Description: Redirects resource single pages to the latest uploaded file for the resource. Pretty tightly coupled to CJIL and court appearance toolbox sites. Solves common problem of having to update links to files in the resource library.
 * Version: 1.0.0
 * Author: Matias Silva
 * Text Domain: sog-attachment-redirect
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin activation hook.
register_activation_hook(__FILE__, 'sog_attachment_redirect_activate');

function sog_attachment_redirect_activate()
{
    // Check if Toolset Types is active
    if (!is_plugin_active('types/wpcf.php')) {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        // Throw an error in the wordpress admin console
        wp_die('Did not find toolset types plugin. Please install and activate it before activating this plugin.');
    }

    // Make sure resource post type exists
    if (!post_type_exists('resource')) {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        // Throw an error in the wordpress admin console
        wp_die('Did not find resource post type. Please make sure it exists before activating this plugin.');
    }

}
function redirect_single_resource() {
    if (is_singular('resource')) {
        // Check the resource type
        $resource_type = get_post_meta(get_the_ID(), 'wpcf-resource-format', true);
        if ($resource_type === 'link') {
            // redirect to the link
            $link = get_post_meta(get_the_ID(), 'wpcf-resource-link', true);
            wp_redirect($link);
            exit();
        } elseif ($resource_type === 'file') {
            // redirect to the file
            $file = get_post_meta(get_the_ID(), 'wpcf-resource-file', true);
            wp_redirect($file);
            exit();
        } elseif ($resource_type === 'image') {
            // redirect to the image
            $image = get_post_meta(get_the_ID(), 'wpcf-resource-image', true);
            wp_redirect($image);
            exit();
        }

        exit();
    }
}
add_action('template_redirect', 'redirect_single_resource');

function sog_attachment_redirect_resource_columns($columns)
{
    $columns['permalink'] = 'Permalink';
    return $columns;
}

add_filter('manage_resource_posts_columns', 'sog_attachment_redirect_resource_columns');

function sog_attachment_redirect_resource_columns_content($column_name, $post_ID)
{
    if ($column_name == 'permalink') {
        $permalink = get_permalink($post_ID);
        echo '<a href="' . $permalink . '">' . $permalink . '</a>';
    }
}

add_action('manage_resource_posts_custom_column', 'sog_attachment_redirect_resource_columns_content', 10, 2);