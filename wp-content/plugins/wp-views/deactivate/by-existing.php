<?php
/**
 * Deactivate the plugin when another instance with the same or different flavour is already running.
 *
 * @package Toolset Views
 * @since 3.0
 */

add_action( 'admin_notices', 'wpv_force_deactivate_by_blocks_notice' );

if ( ! function_exists( 'wpv_force_deactivate_by_blocks' ) ) {

	/**
	 * Deactivate this plugin.
	 *
	 * @since 3.0
	 */
	function wpv_force_deactivate_by_blocks( $plugin ) {
		add_action( 'admin_init', function () use ( $plugin ) {
            deactivate_plugins( $plugin );
			if ( ! is_network_admin() ) {
				update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
			} else {
				update_site_option( 'recently_activated', array( $plugin => time() ) + (array) get_site_option( 'recently_activated' ) );
			}
        });
	}
}

if ( ! function_exists( 'wpv_force_deactivate_by_blocks_notice' ) ) {

	/**
	 * Deactivate notice for this plugin because of Toolset Blocks.
	 *
	 * @since 3.0
	 */
	function wpv_force_deactivate_by_blocks_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong>Toolset Blocks is a different flavour of Toolset Views</strong>. You can not use both versions at the same time, so we have deactivated one.
			</p>
		</div>
		<?php
	}
}
