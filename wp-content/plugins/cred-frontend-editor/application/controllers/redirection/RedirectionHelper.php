<?php

namespace OTGS\Toolset\CRED\Controller\Redirection;

class RedirectionHelper {

	/** @var string[] */
	private $unique_id = array();

	/**
	 * Generate an unique ID matching the one set in \CRED_StaticClass::$out['prg_id'].
	 *
	 * @param int|string $form_id
	 * @return string
	 */
	public function get_unique_id( $form_id ) {
		if ( array_key_exists( $form_id, $this->unique_id ) ) {
			return $this->unique_id[ $form_id ];
		}

		$this->unique_id[ $form_id ] = $form_id
			. '_' . apply_filters( 'toolset_forms_frontend_flow_get_form_index', 1 )
			. '_' . toolset_getget( 'wpv_paged', 1 );
		return $this->unique_id[ $form_id ];
	}

	/**
	 * Placeholder for the time() function.
	 *
	 * @return int
	 */
	public function get_time() {
		return time();
	}
}
