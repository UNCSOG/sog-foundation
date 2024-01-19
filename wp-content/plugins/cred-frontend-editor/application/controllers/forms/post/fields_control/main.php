<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Post\FieldsControl;

use OTGS\Toolset\CRED\Controller\FieldsControl\Base;

class Main extends Base {

    protected $domain = 'post';

    /**
	 * Complete shared data to be used in the toolbar script.
	 *
	 * @return array
	 * 
	 * @since 2.1
	 */
	protected function get_script_localization() {
		$i18n_shared = $this->get_shared_script_localization();

		$i18n = array();

		return array_merge( $i18n_shared, $i18n );
	}

}