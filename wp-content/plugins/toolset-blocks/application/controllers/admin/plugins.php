<?php
/**
 * Enhance the plugin row for this plugin.
 *
 * @package Toolset Views
 * @since 3.0
 */

namespace OTGS\Toolset\Views\Controller\Admin;

/**
 * Controller for the plugin row mods.
 *
 * @since 3.0
 */
class Plugins {

	/**
	 * Get the URL of the version post based on te plugin kind and flavour.
	 *
	 * @param string $link_handle The handle to use on the link, to be placed in racking URL parameters
	 * @param string $version_number The version number to include in the link
	 * @return string
	 * @since 3.0
	 */
	private function get_version_url( $link_handle, $version_number ) {
		$version_for_url = strtolower( str_replace( ".", "-", $version_number ) );
		$version_url = '';

		if ( wpv_is_views_lite() ) {
			$version_url = 'https://wpml.org/version/views-lite-' . $version_for_url . '/';
		}

		$views_flavour = wpv_get_views_flavour();
		switch ( $views_flavour ) {
			case 'blocks':
				$version_url = 'https://toolset.com/version/blocks-' . $version_for_url . '/';
				break;
			default:
				$version_url = 'https://toolset.com/version/views-' . $version_for_url . '/';
				break;
		}

		$version_url = add_query_arg(
			array(
				'utm_source' => 'viewsplugin',
				'utm_campaign' => 'views',
				'utm_medium' => 'release-notes-plugin-row',
				'utm_term' => $link_handle,
			),
			$version_url
		);

		return $version_url;
	}

	/**
	 * Check whether the current plugin row being rendered matches the current plugin.
	 *
	 * @param string $plugin_file
	 * @return bool
	 * @since 3.0
	 */
	private function is_this_plugin( $plugin_file ) {
		$this_plugin = WPV_FOLDER . '/wp-views.php';
		return ( $plugin_file == $this_plugin );
	}

	/**
	 * Initialize this controller.
	 *
	 * @since 3.0
	 */
	public function initialize() {
		// Disable the action links to the Getting started page, might be back at some point.
		// add_filter( 'plugin_action_links', array( $this, 'action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 4 );
	}

	/**
	 * Add action links to the plugin row.
	 *
	 * @param array $links List of existing links
	 * @param string $plugin_file Path to the plugin file
	 * @return array
	 * @since 3.0
	 * @deprecated Kept for reference in case we want to include more action links in the future.
	 */
	public function action_links( $links, $plugin_file ) {
		if ( $this->is_this_plugin( $plugin_file ) ) {
			$links[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url(
						add_query_arg(
							array( 'page' => basename( WPV_PATH ) . '/menu/help.php' ),
							admin_url( 'admin.php' )
						)
					),
					__( 'Getting started', 'wpv-views' )
				);
		}
		return $links;
	}

	/**
	 * Add meta entries to the plugin row.
	 *
	 * @param array $plugin_meta List of existing meta entries
	 * @param string $plugin_file Path to the plugin file
	 * @param array $plugin_data Data for the plugin as stated in its main comment
	 * @param string $status Status of the plugin
	 * @return array
	 * @since 3.0
	 */
	public function row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( $this->is_this_plugin( $plugin_file ) ) {
			$version_number = toolset_getarr( $plugin_data, 'Version', WPV_VERSION );
			$link_handle = sprintf(
				__( '%1$s %2$s release notes', 'wpv-views' ),
				toolset_getarr( $plugin_data, 'Name', 'Toolset Views' ),
				$version_number

			);
			$plugin_meta[] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $this->get_version_url( $link_handle, $version_number ) ),
				esc_html( $link_handle )
			);
		}
		return $plugin_meta;
	}
}
