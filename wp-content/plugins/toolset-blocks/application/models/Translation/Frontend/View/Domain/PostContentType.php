<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Domain;


use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\IPostContentType;

class PostContentType implements IPostContentType {

	public function get_root_class() {
		return 'wp-block-toolset-views-view-editor';
	}
}
