<?php

namespace OTGS\Toolset\CRED\Model\Forms;

/**
 * Factory for \CRED_Form_Data objects.
 *
 * @since 2.4
 */
class DataFactory {

	public function get_form_data( $form_id, $form_type = false ) {
		if ( false === $form_type ) {
			$form_type = get_post_type( $form_id );
		}

		$valid_form_types = array(
			\OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE,
			\OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE,
		);

		if ( ! in_array( $form_type, $valid_form_types, true ) ) {
			return null;
		}

		return new \CRED_Form_Data( $form_id, $form_type, false );
	}
}
