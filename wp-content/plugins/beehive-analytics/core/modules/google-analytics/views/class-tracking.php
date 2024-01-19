<?php
/**
 * The tracking view class for Google Analytics.
 *
 * This class handles the tracking code output on front end
 * of the site.
 *
 * @link    http://wpmudev.com
 * @since   3.2.0
 *
 * @author  Joel James <joel@incsub.com>
 * @package Beehive\Core\Modules\Google_Analytics\Views
 */

namespace Beehive\Core\Modules\Google_Analytics\Views;

// If this file is called directly, abort.
defined( 'WPINC' ) || die;

use Beehive\Core\Helpers\General;
use Beehive\Core\Utils\Abstracts\Base;

/**
 * Class Tracking
 *
 * @package Beehive\Core\Modules\Google_Analytics\Views
 */
class Tracking extends Base {

	/**
	 * Initialize the class by registering hooks.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function init() {
		// Output tracking code to front end.
		add_action( 'wp_head', array( $this, 'tracking' ) );

		// Add admin tracking id if required.
		add_action( 'admin_head', array( $this, 'admin_tracking' ) );
	}

	/**
	 * Render tracking code output for GA.
	 *
	 * Both network tracking code and single site tracking
	 * code will be rendered if multisite.
	 * By default this will be output to the front end of the
	 * site only. For admin side, we use another hook below.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function tracking() {
		/**
		 * Filter hook to disable the tracking script completely.
		 *
		 * @param bool $enabled Should enable?.
		 *
		 * @since 3.2.0
		 */
		$enable = apply_filters( 'beehive_google_enable_tracking', true );

		// No need to continue on ajax and preview mode.
		if ( ! $enable || is_preview() || wp_doing_ajax() ) {
			return;
		}

		$items = array();

		// Get single site measurement IDs.
		$measurement = $this->get_measurement_id();
		$anonymize   = $this->get_anonymize_ip();
		$advertising = $this->get_advertising();

		// Network admin tracking does not require subsite tracking code.
		if ( ( ! $this->is_network() || ! is_admin() ) && $this->is_enabled() ) {
			// GA4 measurement id.
			if ( ! empty( $measurement ) ) {
				$items[ $measurement ] = array(
					'id'          => $measurement,
					'anonymize'   => $anonymize,
					'advertising' => $advertising,
				);
			}
		}

		// On multisite site.
		if ( General::is_networkwide() && $this->is_enabled( true ) ) {
			// Get network measurement ids.
			$network_measurement = $this->get_measurement_id( true );
			$network_anonymize   = $this->get_anonymize_ip( true );
			$network_advertising = $this->get_advertising( true );

			// Output only if it's different than subsite.
			if ( ! empty( $network_measurement ) && $measurement !== $network_measurement ) {
				$items[ $network_measurement ] = array(
					'id'          => $network_measurement,
					'anonymize'   => $network_anonymize,
					'advertising' => $network_advertising,
				);
			}
		}

		if ( ! empty( $items ) ) {
			// Output the final tracking script.
			$this->tracking_output( $items );
		}
	}

	/**
	 * Output Google Analytics code in admin.
	 *
	 * On multisite, admin tracking is enabled in network
	 * settings which will be applicable for all sites.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function admin_tracking() {
		// Get admin tracking settings.
		$admin_tracking = beehive_analytics()->settings->get(
			'track_admin',
			'general',
			General::is_networkwide()
		);

		/**
		 * Filter hook to enable/disable admin tracking.
		 *
		 * @param bool $admin_tracking Tracking enabled.
		 *
		 * @since 3.2.0
		 */
		if ( apply_filters( 'beehive_google_enable_admin_tracking', $admin_tracking ) ) {
			// Render tracking.
			$this->tracking();
		}
	}

	/**
	 * Render tracking code output for GA.
	 *
	 * Both network tracking code and single site tracking
	 * code will be rendered if multisite.
	 * To support GA4, we need to render both universal analytics
	 * and measurement IDs.
	 *
	 * @param array $items Tracking data.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	private function tracking_output( $items = array() ) {
		// Can't be empty.
		if ( empty( $items ) ) {
			return;
		}

		// Default data layer name.
		$data_layer = 'beehiveDataLayer';

		/**
		 * Filter hook to modify gtag.js data layer name.
		 *
		 * @param string $data_layer Name of datalayer.
		 *
		 * @see   https://developers.google.com/gtagjs/devguide/datalayer
		 * @since 3.3.3
		 */
		$data_layer = apply_filters( 'beehive_google_analytics_datalayer_name', $data_layer );

		/**
		 * Filter hook to change the function name in GA script.
		 *
		 * @param string $ga_tag Ga tag name.
		 *
		 * @since 3.2.0
		 */
		$function = apply_filters( 'beehive_google_analytics_function_name', 'beehive_ga' );

		// Get the first ID.
		$first_key = array_keys( $items )[0];

		?>
		<?php if ( beehive_analytics()->is_pro() ) : ?>
			<!-- Google Analytics tracking code output by Beehive Analytics Pro -->
		<?php else : ?>
			<!-- Google Analytics tracking code output by Beehive Analytics -->
		<?php endif; ?>
		<?php // phpcs:ignore ?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $first_key ); ?>&l=<?php echo esc_attr( $data_layer ); ?>"></script>
		<script>
			<?php // @formatter:off ?>
			window.<?php echo esc_attr( $data_layer ); ?> = window.<?php echo esc_attr( $data_layer ); ?> || [];
			function <?php echo esc_attr( $function ); ?>() {<?php echo esc_attr( $data_layer ); ?>.push(arguments);}
			<?php echo esc_attr( $function ); ?>('js', new Date())
			<?php
			// @formatter:on
			foreach ( $items as $id => $options ) :
				$this->tracking_config_output( $id, $options, $function ); // Output each IDs.
			endforeach;

			/**
			 * Action hook to add script at the end of gtag.js script.
			 *
			 * @param array $items Config items.
			 *
			 * @since 3.3.3
			 */
			do_action( 'beehive_google_after_tracking_config_item_output', $items );
			?>
		</script>
		<?php
	}

	/**
	 * Render tracking code part for a single product.
	 *
	 * This should be used to output each of measurement
	 * and tracking IDs.
	 *
	 * @param string $id       ID for tracking.
	 * @param array  $options  Tracking config data.
	 * @param string $function Function name.
	 *
	 * @since 3.3.3
	 *
	 * @return void
	 */
	private function tracking_config_output( $id, $options = array(), $function = 'gtag' ) {
		if ( ! empty( $id ) ) {
			// @formatter:off
			?>
			<?php echo esc_attr( $function ); ?>('config', '<?php echo esc_html( $id ); ?>', {
				'anonymize_ip': <?php echo empty( $options['anonymize'] ) ? 'false' : 'true'; ?>,
				'allow_google_signals': <?php echo empty( $options['advertising'] ) ? 'false' : 'true'; ?>,
			})
			<?php
			// @formatter:on

			/**
			 * Action hook to add script part after single config item.
			 *
			 * @param string $id       Tracking/Measurement ID.
			 * @param array  $options  Config options.
			 * @param string $function Function name.
			 *
			 * @since 3.3.3
			 */
			do_action( 'beehive_google_after_tracking_config_item_output', $id, $options, $function );
		}
	}

	/**
	 * Get Google Analytics 4 measurement ID.
	 *
	 * @param bool $network Network flag.
	 *
	 * @since 3.3.3
	 *
	 * @return string
	 */
	private function get_measurement_id( $network = false ) {
		// Get GA4 measurement ID options.
		$measurement         = beehive_analytics()->settings->get( 'measurement', 'tracking', $network );
		$auto_measurement    = beehive_analytics()->settings->get( 'auto_track_ga4', 'google', $network );
		$auto_measurement_id = beehive_analytics()->settings->get( 'auto_track_ga4', 'misc', $network );

		// User auto measurement ID.
		if ( ! empty( $auto_measurement ) && ! empty( $auto_measurement_id ) ) {
			$measurement = $auto_measurement_id;
		}

		/**
		 * Filter to modify measurement ID for GA.
		 *
		 * @param string $measurement Measurement ID.
		 * @param bool   $network     Network flag.
		 *
		 * @since 3.3.3
		 */
		return apply_filters( 'beehive_google_tracking_get_measurement_id', $measurement, $network );
	}

	/**
	 * Get anonymize option for Google Analytics output.
	 *
	 * If a multisite, check if anonymize option is enabled
	 * and forced in network settings.
	 *
	 * @param bool $network Network flag.
	 *
	 * @since 3.3.3
	 *
	 * @return bool
	 */
	private function get_anonymize_ip( $network = false ) {
		// Anonymize settings.
		$anonymize = beehive_analytics()->settings->get( 'anonymize', 'general', $network );

		// If forced from network settings.
		if ( General::is_networkwide() && ! $network && ! $anonymize ) {
			// Get network anonymize.
			$network_anonymize = $this->get_anonymize_ip( true );

			// If enabled in network.
			if ( $network_anonymize ) {
				// Get force flag from network.
				$force = beehive_analytics()->settings->get( 'force_anonymize', 'general', true );
				// If forced from network use it.
				$anonymize = $force ? true : $anonymize;
			}
		}

		/**
		 * Filter to modify anonymize option for GA.
		 *
		 * @param bool $anonymize Enabled or Disabled.
		 * @param bool $network   Network flag.
		 *
		 * @since 3.3.3
		 */
		return apply_filters( 'beehive_google_tracking_get_anonymize_ip', $anonymize, $network );
	}

	/**
	 * Get advertising option for Google Analytics output.
	 *
	 * @param bool $network Network flag.
	 *
	 * @since 3.3.3
	 *
	 * @return bool
	 */
	private function get_advertising( $network = false ) {
		// Get advertising option.
		$advertising = beehive_analytics()->settings->get( 'advertising', 'general', $network );

		/**
		 * Filter to modify advertising option for GA.
		 *
		 * @param bool $advertising Enabled or Disabled.
		 * @param bool $network     Network flag.
		 *
		 * @since 3.3.3
		 */
		return apply_filters( 'beehive_google_tracking_get_advertising', $advertising, $network );
	}

	/**
	 * Check if we can output analytics script.
	 *
	 * @param bool $network Network flag.
	 *
	 * @since 3.3.7
	 *
	 * @return bool
	 */
	private function is_enabled( $network = false ) {
		$enabled = true;

		// Only valid when logged in.
		if ( is_user_logged_in() ) {
			// Get excluded roles.
			$roles = (array) beehive_analytics()->settings->get(
				'exclude_roles',
				'tracking',
				$network,
				array()
			);

			// Only when excluded.
			if ( ! empty( $roles ) ) {
				// Get current user.
				$user = wp_get_current_user();

				// Get current roles.
				$current_roles = (array) $user->roles;

				// Add super admin role.
				if ( current_user_can( 'manage_network' ) ) {
					$current_roles[] = 'super_admin';
				}

				// Get excluded roles.
				$excluded = array_intersect( $roles, $current_roles );

				// If excluded role is found.
				if ( ! empty( $excluded ) ) {
					$enabled = false;
				}
			}
		}

		/**
		 * Filter to modify tracking output enabled status.
		 *
		 * @param bool $enabled Enabled or Disabled.
		 * @param bool $network Network flag.
		 *
		 * @since 3.3.7
		 */
		return apply_filters( 'beehive_google_tracking_is_enabled', $enabled, $network );
	}
}
