<?php

namespace ToolsetCommonEs\Compatibility\Style\Selector;

use ToolsetCommonEs\Compatibility\ISelector;

class BlockEditorBlock implements ISelector {

	public function get_css_selector() {
		return '.edit-post-visual-editor .block-editor-block-list__block';
	}
}
