<?php

namespace OTGS\Toolset\CRED\Model\Wordpress;

/**
 * Wrapper for WordPress hooks interaction.
 *
 * @since 2.5.7
 */
class Hook {

	/**
	 * Reove all callbacks hooked into a handle.
	 *
	 * @param string $hook Handle.
	 * @return \WP_Hook|null \WP_Hook object will all the callbacks, null if there is none.
	 * @since 2.5.7
	 */
	public function remove_all_callbacks( $hook ) {
		global $wp_filter;
		$wp_hook = null;

		if (
			isset( $wp_filter[ $hook ] )
			&& $wp_filter[ $hook ] instanceof \WP_Hook
		) {
			$wp_hook = $wp_filter[ $hook ];
			unset( $wp_filter[ $hook ] );
		}

		return $wp_hook;
	}

	/**
	 * Restore all callbacks for a given hook handle.
	 *
	 * @param string $hook Handle.
	 * @param \WP_Hook|null $wp_hook \WP_Hook object will all the callbacks, null if there is none.
	 * @since 2.5.7
	 */
	public function restore_hook_callbacks( $hook, $wp_hook ) {
		global $wp_filter;

		if ( $wp_hook instanceof \WP_Hook ) {
			$wp_filter[ $hook ] = $wp_hook;
		}
	}
}
