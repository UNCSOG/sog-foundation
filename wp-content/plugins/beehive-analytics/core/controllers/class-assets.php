<?php
/**
 * The assets controller class of the plugin.
 *
 * @link    http://wpmudev.com
 * @since   3.3.0
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Controllers
 */

namespace Beehive\Core\Controllers;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Utils\Abstracts\Base;

/**
 * Class Assets
 *
 * @package Beehive\Core\Controllers
 */
class Assets extends Base {

	/**
	 * Initialize assets functionality.
	 *
	 * @since 3.2.4
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );

		// Include clipboard JS.
		add_filter( 'beehive_assets_get_scripts', array( $this, 'register_clipboard' ) );
	}

	/**
	 * Assets for our front end functionality.
	 *
	 * Currently this function will not register anything.
	 * But this should be here for other modules to register
	 * public assets.
	 *
	 * @since 3.2.4
	 *
	 * @return void
	 */
	public function public_assets() {
		$this->register_styles( false );
		$this->register_scripts( false );
	}

	/**
	 * Assets for our front end functionality.
	 *
	 * @since 3.2.4
	 *
	 * @return void
	 */
	public function admin_assets() {
		$this->register_styles();
		$this->register_scripts();
	}

	/**
	 * Register available styles.
	 *
	 * We are just registering the assets with WP now.
	 * We will enqueue them when it's really required.
	 *
	 * @param bool $admin Is admin assets?.
	 *
	 * @since 3.2.4
	 *
	 * @return void
	 */
	private function register_styles( $admin = true ) {
		// Get all the assets.
		$styles = $this->get_styles( $admin );

		// Register all styles.
		foreach ( $styles as $handle => $data ) {
			// Get the source full url.
			$src = empty( $data['external'] ) ? BEEHIVE_URL . 'build/' . $data['src'] : $data['src'];

			// Register custom videos scripts.
			wp_register_style(
				$handle,
				$src,
				empty( $data['deps'] ) ? array() : $data['deps'],
				empty( $data['version'] ) ? BEEHIVE_VERSION : $data['version'],
				empty( $data['media'] ) ? false : true
			);
		}
	}

	/**
	 * Register available scripts.
	 *
	 * We are just registering the assets with WP now.
	 * We will enqueue them when it's really required.
	 *
	 * @param bool $admin Is admin assets?.
	 *
	 * @since 3.2.4
	 *
	 * @return void
	 */
	private function register_scripts( $admin = true ) {
		// Get all the assets.
		$scripts = $this->get_scripts( $admin );

		// Register all available scripts.
		foreach ( $scripts as $handle => $data ) {
			// Get the source full url.
			$src = empty( $data['external'] ) ? BEEHIVE_URL . 'build/' . $data['src'] : $data['src'];

			// Register custom videos scripts.
			wp_register_script(
				$handle,
				$src,
				empty( $data['deps'] ) ? array() : $data['deps'],
				empty( $data['version'] ) ? BEEHIVE_VERSION : $data['version'],
				isset( $data['footer'] ) ? $data['footer'] : true
			);

			// Load translations for React components using @wordpress/i18n.
			wp_add_inline_script( $handle, $this->load_script_translations(), 'before' );
		}
	}

	/**
	 * Enqueue a script with localization.
	 *
	 * Always use this method to enqueue scripts. Then only
	 * we will get the required localized vars.
	 *
	 * @param string $script Script handle name.
	 *
	 * @since 3.2.4
	 *
	 * @return void
	 */
	public function enqueue_script( $script ) {
		static $vars_printed = false;
		// Only if not enqueued already.
		if ( ! wp_script_is( $script ) ) {
			// Extra vars.
			wp_localize_script(
				$script,
				'beehiveModuleVars',
				/**
				 * Filter to add/remove vars in script.
				 *
				 * @since 3.2.4
				 */
				apply_filters( "beehive_assets_scripts_localize_vars_{$script}", array() )
			);

			if ( ! $vars_printed ) {
				wp_localize_script(
					$script,
					'beehiveVars',
					/**
					 * Filter to add/remove vars in script.
					 *
					 * @param array $common_vars Common vars.
					 * @param array $handle      Script handle name.
					 *
					 * @since 3.2.4
					 */
					apply_filters( 'beehive_assets_scripts_common_localize_vars', array(), $script )
				);

				// Localized vars for the locale.
				wp_localize_script( $script, 'beehiveI18n', I18n::instance()->get_strings( $script ) );
			}

			// Enqueue.
			wp_enqueue_script( $script );

			$vars_printed = true;
		}
	}

	/**
	 * Enqueue a style with WordPress.
	 *
	 * This is just an alias function.
	 *
	 * @param string $style Style handle name.
	 *
	 * @since 3.2.4
	 *
	 * @return void
	 */
	public function enqueue_style( $style ) {
		// Only if not enqueued already.
		if ( ! wp_style_is( $style ) ) {
			wp_enqueue_style( $style );
		}
	}

	/**
	 * Get the scripts list to register.
	 *
	 * @param bool $admin Is admin assets?.
	 *
	 * @since 3.2.4
	 *
	 * @return array
	 */
	private function get_scripts( $admin = true ) {
		if ( $admin ) {
			$scripts = array(
				'beehive-admin'      => array(
					'src'  => 'admin/index.js',
					'deps' => array( 'wp-api-fetch', 'wp-i18n', 'wp-element' ),
				),
				'clipboard'          => array(
					'src'  => 'clipboard.js',
					'deps' => array(),
				),
			);
		} else {
			$scripts = array();
		}

		/**
		 * Filter to include/exclude new script.
		 *
		 * Modules should use this filter to that common localized
		 * vars will be available.
		 *
		 * @param array $scripts Scripts list.
		 * @param bool  $admin   Is admin assets?.
		 *
		 * @since 3.2.4
		 */
		return apply_filters( 'beehive_assets_get_scripts', $scripts, $admin );
	}

	/**
	 * Get the styles list to register.
	 *
	 * @param bool $admin Is admin assets?.
	 *
	 * @since 3.2.4
	 *
	 * @return array
	 */
	private function get_styles( $admin = true ) {
		if ( $admin ) {
			$styles = array(
				'beehive-admin' => array(
					'src' => 'admin/index.css',
				),
			);
		} else {
			$styles = array();
		}

		/**
		 * Filter to include/exclude new style.
		 *
		 * Modules should use this filter to include styles.
		 *
		 * @param array $styles Styles list.
		 * @param bool  $admin  Is admin assets?.
		 *
		 * @since 3.2.4
		 */
		return apply_filters( 'beehive_assets_get_styles', $styles, $admin );
	}

	/**
	 * Add clipboard JS to the scripts list if required.
	 *
	 * @param array $scripts Scripts list.
	 *
	 * @since 3.3.1
	 *
	 * @return array
	 */
	public function register_clipboard( $scripts ) {
		global $wp_version;

		// We need to include the lib manually for WP below 5.2.
		if ( version_compare( $wp_version, '5.2', '<' ) ) {
			$scripts['clipboard'] = array(
				'src'  => 'clipboard.min.js',
				'deps' => array( 'jquery' ),
			);
		}

		return $scripts;
	}

	/**
	 * Load script translations from .mo files for @wordpress/i18n.
	 *
	 * @since 3.5.0
	 *
	 * @return string JavaScript code to set locale data.
	 */
	private function load_script_translations() {
		$translations = get_translations_for_domain( 'ga_trans' );
		$locale       = array(
			'translation-revision-date' => $translations->headers['PO-Revision-Date'] ?? '',
			'domain'                    => 'messages',
			'generator'                 => $translations->headers['X-Generator'] ?? '',
			'locale_data'               => array(
				'messages' => array(
					'' => array(
						'domain'       => 'messages',
						'plural-forms' => $translations->headers['Plural-Forms'] ?? 'nplurals=2; plural=n > 1;',
					),
				),
			),
		);

		if ( isset( $translations->headers['Language'] ) && $translations->headers['Language'] ) {
			$locale['locale_data']['messages']['']['lang'] = $translations->headers['Language'];
		}

		foreach ( $translations->entries as $entry ) {
			$key                                       = $entry->context ? $entry->context . chr( 4 ) . $entry->singular : $entry->singular;
			$locale['locale_data']['messages'][ $key ] = array_filter(
				$entry->translations,
				function ( $translation ) {
					return null !== $translation;
				}
			);
		}

		$json_translations = wp_json_encode( $locale );

		return <<<JS
			( function( domain, translations ) {
				var localeData = translations.locale_data[ domain ] || translations.locale_data.messages
				localeData[""].domain = domain
				wp.i18n.setLocaleData( localeData, domain )
			} )( 'ga_trans', {$json_translations} )
		JS;
	}
}
