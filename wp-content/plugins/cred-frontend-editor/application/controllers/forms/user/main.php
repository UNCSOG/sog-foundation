<?php

namespace OTGS\Toolset\CRED\Controller\Forms\User;

use OTGS\Toolset\CRED\Controller\Forms\Main as MainBase;

/**
 * User forms main controler.
 * 
 * @since 2.1
 */
class Main extends MainBase {
	const POST_TYPE = 'cred-user-form';
	const TRANSIENT_KEY = 'cred_transient_published_user_forms';

	const SHORTCODE_NAME_FORM_CONTAINER = 'creduserform';
	const SHORTCODE_NAME_FORM_FIELD = 'cred_field';

	const DOMAIN = 'user';
}