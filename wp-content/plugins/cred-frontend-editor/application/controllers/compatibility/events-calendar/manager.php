<?php
/**
 * Events Calendar integration manager.
 *
 * @package Toolset Forms
 */

namespace OTGS\Toolset\CRED\Controller\Compatibility\EventsCalendar;

class Manager {
	/** @var \CRED_Main */
	private $cred_main;

	public function __construct(\CRED_Main $cred_main) {
		$this->cred_main = $cred_main;
	}

	public function initialize() {
		add_filter( 'tribe_rewrite_parse_query_vars', [ $this, 'restore_submit_form_post_before_parse_query' ] );
	}

	public function restore_submit_form_post_before_parse_query($query_vars) {
		$this->cred_main->restore_submit_form_post_before_parse_query(null);
		return $query_vars;
	}
}
