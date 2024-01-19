<?php

namespace OTGS\Toolset\CRED\Model\Wordpress;

/**
 * Wrapper for WordPress auto-drafts interaction.
 *
 * @since 2.5.7
 */
class AutoDraft {

	const TITLE_PREFIX = 'CRED Auto Draft';

	/** @var Hook */
	private $hook;

	/**
	 * Constructor.
	 *
	 * @param Hook $hook Hook model.
	 */
	public function __construct(
		Hook $hook
	) {
		$this->hook = $hook;
	}

	/**
	 * Create an auto-draft for a given post type and user.
	 *
	 * @param string $post_type
	 * @param int $user_id
	 * @return \WP_Post|null
	 * @since 2.5.7
	 */
	public function create( $post_type, $user_id ) {
		$auto_draft = get_default_post_to_edit( $post_type );

		$auto_draft->post_title = $this->get_unique_post_title();
		$auto_draft->content = '';
		$auto_draft->post_status = 'auto-draft';
		if ( ! empty( $user_id ) ) {
			$auto_draft->post_author = (int) $user_id;
		}
		$auto_draft->post_category = '';

		// Remove all third party (and native) callbacks on wp_insert_post_data for wp_insert_post.
		// WooCommerce uses this filter to normalize its auto-drafts titles.
		// This breaks the storage and retrieving of Forms auto-drafts.
		$wp_insert_post_data_callbacks = $this->hook->remove_all_callbacks( 'wp_insert_post_data' );

		$auto_draft_id = wp_insert_post( $auto_draft );

		$this->hook->restore_hook_callbacks( 'wp_insert_post_data', $wp_insert_post_data_callbacks );

		if ( is_wp_error( $auto_draft_id ) ) {
			return null;
		}

		return get_post( $auto_draft_id );
	}

	/**
	 * Retrieve an auto-draft for a given post type and user.
	 *
	 * @param string $post_type
	 * @param int $user_id
	 * @return \WP_Post|null
	 * @since 2.5.7
	 */
	public function retrieve( $post_type, $user_id ) {
		global $wpdb;

		$auto_drafts_candidates = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT $wpdb->posts.ID
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_status = 'auto-draft'
				AND $wpdb->posts.post_type = %s
				AND $wpdb->posts.post_title = %s
				AND $wpdb->posts.post_author = %d
				ORDER by ID desc
				LIMIT 1",
				array(
					$post_type,
					$this->get_unique_post_title(),
					$user_id
				)
			),
			OBJECT
		);

		if ( ! empty( $auto_drafts_candidates ) ) {
			return get_post( $auto_drafts_candidates[0]->ID );
		}

		return null;
	}

	/**
	 * Calculate the unique post title for an autodraft, based on the current user IP.
	 *
	 * @return string
	 * @since 2.5.7
	 */
	public function get_unique_post_title() {
		return self::TITLE_PREFIX . ' ' . md5( $this->get_ip() );
	}

	/**
	 * Get the IP for the current user.
	 *
	 * Note that this needs to be public so we can deprecate \CRED_StaticClass::getIP().
	 *
	 * @return string
	 * @since 2.5.7
	 */
	public function get_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			// Shared internet.
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// From proxy.
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}
}
