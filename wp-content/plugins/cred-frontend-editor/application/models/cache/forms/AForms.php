<?php

namespace OTGS\Toolset\CRED\Model\Cache\Forms;

use OTGS\Toolset\CRED\Model\Cache\ITransient;
use OTGS\Toolset\CRED\Model\Wordpress\Transient;

/**
 * Abstract class to manipulate form-related transients, extended per domain.
 * 
 * @since 2.1.2
 */
abstract class AForms implements ITransient {

	/** @var \wpdb */
	protected $wpdb;

	/** @var Transient */
	protected $wp_transient;

	/**
	 * AForms constructor.
	 *
	 * @param \wpdb $wpdb
	 * @param Transient $wp_transient
	 */
	public function __construct( \wpdb $wpdb, Transient $wp_transient ) {
		$this->wpdb = $wpdb;
		$this->wp_transient = $wp_transient;
	}

	/**
	 * Generate the forms transient.
	 *
	 * This is currently the same for users and posts forms
	 *
	 * @return array
	 * @since 2.1.2
	 */
	public function generate_transient() {
		$wpdb = $this->wpdb;

		$forms_transient_to_update = array(
			'new'	=> array(),
			'edit'	=> array()
		);

		$forms_available = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_name FROM {$wpdb->posts}
				WHERE post_type = %s
				AND post_status in ('private') 
				ORDER BY post_title",
				$this->get_post_type()
			)
		);

		foreach ( $forms_available as $form_candidate ) {
			$form_settings = (array) get_post_meta( $form_candidate->ID, '_cred_form_settings', true );
			$current_form_type = toolset_getnest( $form_settings, array( 'form', 'type' ) );
			if ( empty( $current_form_type ) ) {
				continue;
			}
			$forms_transient_to_update[ $current_form_type ][] = $form_candidate;
		}

		$this->wp_transient->set_transient(
			$this->get_transient_key(),
			$forms_transient_to_update,
			WEEK_IN_SECONDS
		);

		return $forms_transient_to_update;
	}

	/**
	 * Get transient.
	 *
	 * @return mixed
	 */
	public function get_transient() {
		return $this->wp_transient->get_transient( $this->get_transient_key() );
	}

	/**
	 * Delete transient
	 */
	public function delete_transient() {
		return $this->wp_transient->delete_transient( $this->get_transient_key() );
	}

	/**
	 * Get the post type associated to the current domain.
	 * 
	 * @return string
	 */
	abstract protected function get_post_type();

	/**
	 * Get the transient key associated with the current domain.
	 * 
	 * @return string
	 */
	abstract protected function get_transient_key();
}