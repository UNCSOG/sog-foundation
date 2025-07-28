<?php
namespace OTGS\Toolset\CRED\Controller\Compatibility\FluentCrm;

class Manager {

	public function add_hooks() {
		add_filter( 'cred_filter_enqueue_legacy_button_assets', [ $this, 'include_legacy_button_in_fluent_crm_editors' ] );
	}

	public function include_legacy_button_in_fluent_crm_editors( $status ) {
		if ( 'fluentcrm-admin' === toolset_getget( 'page' ) ) {
			return false;
		}
		return $status;
	}

}
