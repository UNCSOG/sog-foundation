<?php

namespace OTGS\Toolset\Access\Viewmodels;

use OTGS\Toolset\Access\Models\Settings as Settings;

/**
 * Generate edit post meta box to assign a post group to a single post
 *
 * Class PostMetabox
 *
 * @package OTGS\Toolset\Access\Viewmodels
 * @since 2.7
 */
class PostMetabox {

	/**
	 * @param \WP_Post $post
	 */
	public static function meta_box( $post ) {
		$message = __( 'No Post Group selected.', 'wpcf-access' );
		$settings = Settings::get_instance();
		$settings_access = $settings->get_types_settings();

		if ( isset( $settings_access[ $post->post_type ]['mode'] )
			&& 'not_managed' != $settings_access[ $post->post_type ]['mode'] ) {
			if ( isset( $_GET['post'] ) ) {
				$group = get_post_meta( $_GET['post'], '_wpcf_access_group', true );

				if ( isset( $settings_access[ $group ] ) && ! empty( $settings_access[ $group ] ) ) {
					$message = sprintf(
							__( '<p><strong>%s</strong> permissions will be applied to this post.', 'wpcf-access' ),
							esc_html( $settings_access[ $group ]['title'] )
						) . '</p>';
					if ( current_user_can( 'manage_options' ) ) {
						$message .= '<p><a href="admin.php?page=types_access&tab=custom-group">'
							.
							sprintf(
								__( 'Edit %s group privileges', 'wpcf-access' ),
								esc_html( $settings_access[ $group ]['title'] )
							)
							. '</a></p>';
					}
				}
			}
			$out = '<div class="js-wpcf-access-post-group">' . $message . '</div>';
			if ( current_user_can( 'manage_options' ) ) {
				$out .= '<input type="hidden" value="1" id="access-show-edit-link" />';
			}
			$out .= '<input type="button" value="'
				. __( 'Change Post Group', 'wpcf-access' )
				. '" data-id="'
				. esc_attr( $post->ID )
				. '"
			class="js-wpcf-access-assign-post-to-group button">';
			$out .= wp_nonce_field( 'wpcf-access-error-pages', 'wpcf-access-error-pages', true, false );
		} else {
			$out = '<p>' . __( 'This content type is not currently managed by the Access plugin. ' .
					'To be able to add it to Post Group, first go to the Access admin and allow Access to control it.',
					'wpcf-access' ) . '</p>';
		}
		print $out;
	}

}
