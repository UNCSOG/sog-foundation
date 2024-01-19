<?php

namespace Toolset\Compatibility\Divi;

if ( ! class_exists( 'ET_Builder_Element' ) ) {
	return;
}

require_once 'modules/View/View.php';

new Modules\View;
