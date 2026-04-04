<?php
/**
 * Frontend display rule evaluation.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand\Core;

use SOG\Rebrand\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Display_Rules {
	/**
	 * Settings module.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings module.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Determine whether the header should render on the current request.
	 *
	 * @return bool
	 */
	public function should_display_header(): bool {
		$settings = $this->settings->get_settings();

		if ( empty( $settings['header_enabled'] ) ) {
			return false;
		}

		return ! $this->is_excluded_request( $settings );
	}

	/**
	 * Determine whether the footer should render on the current request.
	 *
	 * @return bool
	 */
	public function should_display_footer(): bool {
		$settings = $this->settings->get_settings();

		if ( empty( $settings['footer_enabled'] ) ) {
			return false;
		}

		return ! $this->is_excluded_request( $settings );
	}

	/**
	 * Evaluate shared exclusion conditions.
	 *
	 * @param array<string, mixed> $settings Current plugin settings.
	 * @return bool
	 */
	private function is_excluded_request( array $settings ): bool {
		if ( is_admin() || wp_doing_ajax() || is_feed() ) {
			return true;
		}

		if ( ! did_action( 'wp' ) ) {
			return false;
		}

		if ( ! empty( $settings['exclude_front_page'] ) && is_front_page() ) {
			return true;
		}

		if ( ! empty( $settings['exclude_posts_page'] ) && is_home() ) {
			return true;
		}

		if ( ! empty( $settings['exclude_search'] ) && is_search() ) {
			return true;
		}

		if ( ! empty( $settings['exclude_404'] ) && is_404() ) {
			return true;
		}

		$post_id = get_queried_object_id();

		if ( $post_id && in_array( $post_id, array_map( 'intval', (array) $settings['excluded_post_ids'] ), true ) ) {
			return true;
		}

		$post_type = get_post_type( $post_id );

		if ( $post_type && in_array( $post_type, $settings['excluded_post_types'], true ) ) {
			return true;
		}

		$template = $post_id ? get_page_template_slug( $post_id ) : '';

		if ( $template && in_array( $template, $settings['excluded_templates'], true ) ) {
			return true;
		}

		return false;
	}
}
