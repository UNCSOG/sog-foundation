<?php
/**
 * Plugin Name: SOG API
 * Version: 0.1
 * Description: Sets up API endpoints
 * Author: Matias Silva
 * Text Domain: sog-api
 * Domain Path: /languages
 *
 * @package sog_api
 */


add_action('rest_api_init', 'register_rest_images' );
function register_rest_images(){
    register_rest_field( ['resource'],
        'fimg_url',
        [
            'get_callback'    => 'get_rest_featured_image',
            'update_callback' => null,
            'schema'          => null,
        ]
    );
}
function get_rest_featured_image( $object, $field_name, $request ) {
    if( $object['featured_media'] ){
        $img = wp_get_attachment_image_src( $object['featured_media'], '4x6' );
        return $img[0];
    }
    return false;
}