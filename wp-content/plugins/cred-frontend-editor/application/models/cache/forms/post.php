<?php

namespace OTGS\Toolset\CRED\Model\Cache\Forms;

/**
 * Transient generator for post forms.
 * 
 * Note that the post forms transient contains two differet entries for new and edit forms.
 * 
 * @since 2.1.2
 */
class Post extends AForms {
	/**
	 * @return string
	 */
	protected function get_post_type() {
		return \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE;
	}

	/**
	 * @return string
	 */
	protected function get_transient_key() {
		return \OTGS\Toolset\CRED\Controller\Forms\Post\Main::TRANSIENT_KEY;
	}
}