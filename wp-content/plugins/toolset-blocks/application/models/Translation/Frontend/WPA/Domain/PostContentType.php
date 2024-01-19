<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Domain;

/* Common Dependencies */
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\IPostContentType;


class PostContentType implements IPostContentType {
	public function get_root_class() {
		return 'wp-block-toolset-views-wpa-editor';
	}
}
