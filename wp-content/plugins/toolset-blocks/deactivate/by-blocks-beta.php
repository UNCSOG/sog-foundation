<?php
/**
 * Deactivate the plugin when the Toolset Blocks beta plugin is already running.
 *
 * @package Toolset Views
 * @since 3.0
 */

add_action( 'admin_notices', 'wpv_force_deactivate_by_blocks_beta_notice' );

if ( ! function_exists( 'wpv_force_deactivate_by_blocks_beta' ) ) {

	/**
	 * Deactivate this plugin because of the Blocks beta.
	 *
	 * @since 3.0
	 */
	function wpv_force_deactivate_by_blocks_beta( $plugin ) {
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

if ( ! function_exists( 'wpv_force_deactivate_by_blocks_beta_notice' ) ) {

	/**
	 * Deactivate notice for this plugin because of the Blocks beta.
	 *
	 * @since 3.0
	 */
	function wpv_force_deactivate_by_blocks_beta_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong>Toolset Blocks Beta is no longer a standalone plugin</strong>. Please install Toolset Views or Toolset Blocks to continue.
			</p>
		</div>
		<?php
	}
}
