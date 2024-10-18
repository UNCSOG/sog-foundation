<?php

/**
 * Compatibility class for Kadence theme
 */
class Toolset_Compatibility_Theme_kadence extends Toolset_Compatibility_Theme_Handler {

	/**
	 * Run theme integration hooks.
	 */
	protected function run_hooks() {
		// Normal archives.
		add_filter( 'kadence_pagination_args', [ $this, 'disable_archive_pagination' ] );
		// WooCommerce archives.
		add_filter( 'woocommerce_pagination_args', [ $this, 'disable_archive_pagination' ] );
	}

	/**
	 * Maybe disable archive pagination.
	 *
	 * @param string[]
	 * @return string[]
	 */
	public function disable_archive_pagination( $args ) {
		if ( $this->get_toolset_custom_theme_option( 'toolset_disable_archive_pagination' ) ) {
			$args[ 'total' ] = 1;
		}
		return $args;
	}

}
