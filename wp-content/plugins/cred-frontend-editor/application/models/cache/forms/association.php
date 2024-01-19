<?php

namespace OTGS\Toolset\CRED\Model\Cache\Forms;

/**
 * Transient generator for association forms.
 * 
 * Note that the post forms transient contains a single list of all association forms.
 * 
 * @since 2.1.2
 */
class Association extends AForms {

	/**
	 * @return string
	 */
	protected function get_post_type() {
		return \CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE;
	}

	/**
	 * @return string
	 */
	protected function get_transient_key() {
		return \CRED_Association_Form_Main::TRANSIENT_KEY;
	}

	/**
	 * Generate the association forms transient.
	 * 
	 * @return array
	 * @since 2.1.2
	 */
	public function generate_transient() {
		$wpdb = $this->wpdb;
		
		$forms_available = $wpdb->get_results(
			$wpdb->prepare( 
				"SELECT ID, post_title, post_name FROM {$wpdb->posts}
				WHERE post_type = %s
				AND post_status in ('publish') 
				ORDER BY post_title",
				$this->get_post_type()
			)
		);
		
		$this->wp_transient->set_transient( $this->get_transient_key(), $forms_available, WEEK_IN_SECONDS );
		
		return $forms_available;
	}
}