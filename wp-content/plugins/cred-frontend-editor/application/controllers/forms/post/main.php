<?php

namespace OTGS\Toolset\CRED\Controller\Forms\Post;

use OTGS\Toolset\CRED\Controller\Forms\Main as MainBase;

/**
 * Post forms main controler.
 * 
 * @since 2.1
 */
class Main extends MainBase {
	const POST_TYPE = 'cred-form';
	const TRANSIENT_KEY = 'cred_transient_published_post_forms';

	const SHORTCODE_NAME_FORM_CONTAINER = 'credform';
	const SHORTCODE_NAME_FORM_FIELD = 'cred_field';

	const DOMAIN = 'post';
}