<?php

namespace Toolset\DynamicSources\Integrations;

use Toolset\DynamicSources\Integrations\ThirdParty\Configuration;

/**
 * Handles the integration with the third-party block plugins.
 */
class ThirdParty {
	/** @var Configuration */
	private $configuration;

	/** @var bool */
	private $doing_ajax;

	/** @var bool */
	private $doing_autosave;

	/**
	 * ThirdParty constructor.
	 *
	 * @param Configuration $configuration
	 * @param bool $doing_ajax
	 * @param bool $doing_autosave
	 */
	public function __construct( Configuration $configuration, $doing_ajax, $doing_autosave ) {
		$this->configuration = $configuration;
		$this->doing_ajax = (bool) $doing_ajax;
		$this->doing_autosave = (bool) $doing_autosave;
	}

	/**
	 * Initializes the class.
	 */
	public function initialize() {
		add_action( 'init', array( $this, 'maybe_load_configuration' ), 20 );

		add_action( 'toolset/dynamic_sources/actions/remote_configuration_updated', array( $this, 'load_configuration' ) );

		// The shortcode is used to "hash" used in a third-party block with the the post ID in order to make an element
		// and its style distinctive in the context of a View or a WordPress Archive.
		add_shortcode( 'tb-post-id-class-hashing', 'get_the_ID' );

		if ( ! wp_next_scheduled( 'update_toolset_dynamic_sources_config_index' ) ) {
			// Set cron job to update WPML config index file from CDN.
			wp_schedule_event( time(), 'daily', 'update_toolset_dynamic_sources_config_index' );
		}

		add_action( 'toolset/dynamic_sources/actions/register_deactivation_hook', array( $this, 'register_deactivation_hook' ) );

		add_filter( 'toolset/dynamic_sources/filters/shortcode_output', array( $this, 'replace_in_shortcode_output' ), 10, 6 );
	}

	/**
	 * Triggers the reloading of the Toolset Dynamic Sources configuration options.
	 */
	public function maybe_load_configuration() {
		if ( $this->should_load_configuration() ) {
			$this->load_configuration();
		}
	}

	/**
	 * Triggers the loading of the configuration.
	 */
	public function load_configuration() {
		$this->configuration->load();
	}

	/**
	 * Determines if the loading of the configuration should start.
	 *
	 * @return bool
	 */
	private function should_load_configuration() {
		if ( ! is_admin() || $this->doing_ajax || $this->doing_autosave ) {
			return false;
		}

		$white_list_pages = apply_filters(
			'toolset/dynamic_sources/filters/config_white_list_pages',
			array(
				'plugins.php',
			)
		);

		global $pagenow;

		// Runs the load config process only on specific pages
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (
			( $current_page && in_array( $current_page, $white_list_pages, true ) ) ||
			( $pagenow && in_array( $pagenow, $white_list_pages, true ) )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Triggers the actions that need to run when the plugin including Dynamic Sources as a dependency is getting deactivated.
	 *
	 * @param string $plugin_path
	 */
	public function register_deactivation_hook( $plugin_path ) {
		register_deactivation_hook( $plugin_path, array( $this, 'plugin_deactivated' ) );
	}

	/**
	 * Handles the unhooking of the cron job that updates the configuration for third-party blocks.
	 */
	public function plugin_deactivated() {
		wp_clear_scheduled_hook( 'update_toolset_dynamic_sources_config_index' );
	}

	/**
	 * It replaces content in the output of a Dynamic Sources shortcode after it has been evaluated, only if a replace
	 * pattern has been provided in the shortcode's attributes.
	 *
	 * @param string $output
	 * @param string $post_provider
	 * @param int    $post
	 * @param string $source
	 * @param string $field
	 * @param array  $attributes
	 *
	 * @return string
	 */
	public function replace_in_shortcode_output( $output, $post_provider, $post, $source, $field, $attributes ) {
		if ( ! isset( $attributes[ 'replaceinsourcecontent'] ) ) {
			return $output;
		}

		$replacements_dictionary = [
			'{{bslash}}' => '\\',
			'{{slash}}' => '/',
			'{{gt}}' => '>',
			'{{lt}}' => '<',
		];

		$replacements = explode( ',', $attributes[ 'replaceinsourcecontent'] );
		foreach ( $replacements as $replacement ) {
			$replacement_parts = explode( ':', $replacement );

			if ( 2 !== count( $replacement_parts ) ) {
				continue;
			}

			foreach ( $replacements_dictionary as $replacement_entry_key => $replacement_entry ) {
				$replacement_parts[0] = str_replace( $replacement_entry_key, $replacement_entry, $replacement_parts[0] );
				$replacement_parts[1] = str_replace( $replacement_entry_key, $replacement_entry, $replacement_parts[1] );
			}

			$output = str_replace(
				stripcslashes( $replacement_parts[0] ),
				stripcslashes( $replacement_parts[1] ),
				$output
			);
		}

		return $output;
	}
}
