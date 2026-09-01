<?php
/**
 * The view base class of the plugin.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Utils\Abstracts
 */

namespace Beehive\Core\Utils\Abstracts;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

/**
 * Class View
 *
 * @package Beehive\Core\Utils\Abstracts
 */
class View extends Base {

	/**
	 * Class name for the react app container on every page.
	 *
	 * @var string $page_container
	 */
	protected $page_container = 'beehive-admin';

	/**
	 * SUI classes for the react app container on every page.
	 *
	 * @var string $sui_classes
	 */
	protected $sui_classes = 'sui-wrap sui-theme--light';

	/**
	 * Render an admin view template.
	 *
	 * @param string $view File name.
	 * @param array  $args Arguments.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function view( $view, $args = array() ) {
		// Default views.
		$file_name = BEEHIVE_DIR . 'templates/' . $view . '.php';

		// If file exist, set all arguments are variables.
		if ( file_exists( $file_name ) && is_readable( $file_name ) ) {
			if ( ! empty( $args ) ) {
				$args = (array) $args;
				// phpcs:ignore
				extract( $args );
			}

			/* @noinspection PhpIncludeInspection */
			include $file_name;
		}
	}

	/**
	 * Render the unified admin page wrapper and enqueue assets.
	 *
	 * This provides a consistent wrapper structure and asset loading
	 * for all Beehive admin pages.
	 *
	 * @param string $script_handle Optional. Script handle to enqueue. Default 'beehive-admin'.
	 * @param string $style_handle  Optional. Style handle to enqueue. Default 'beehive-admin'.
	 *
	 * @since 3.4.0
	 *
	 * @return void
	 */
	protected function render_admin_page( $script_handle = 'beehive-admin', $style_handle = 'beehive-admin' ) {
		?>
		<div id="sui-wrap">
			<div class="<?php echo esc_attr( $this->page_container ) . ' ' . esc_attr( $this->sui_classes ); ?>" id="<?php echo esc_attr( $this->page_container ); ?>"></div>
		</div>
		<?php

		// Enqueue assets.
		\Beehive\Core\Controllers\Assets::instance()->enqueue_style( $style_handle );
		\Beehive\Core\Controllers\Assets::instance()->enqueue_script( $script_handle );
	}
}
