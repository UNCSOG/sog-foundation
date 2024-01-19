<?php

namespace OTGS\Toolset\CRED\Model\Cache\Forms;

/**
 * Transient generator for user forms.
 * 
 * Note that the user forms transient contains two differet entries for new and edit forms.
 * 
 * @since 2.1.2
 */
class User extends AForms {
	/**
	 * @return string
	 */
	protected function get_post_type() {
		return \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE;
	}

	/**
	 * @return string
	 */
	protected function get_transient_key() {
		return \OTGS\Toolset\CRED\Controller\Forms\User\Main::TRANSIENT_KEY;
	}
}