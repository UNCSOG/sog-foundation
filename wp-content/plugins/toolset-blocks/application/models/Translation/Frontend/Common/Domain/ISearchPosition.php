<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain;

interface ISearchPosition {
	/**
	 * @param string $content
	 *
	 * @return int|false Position if found, otherwise false.
	 */
	public function position_in_content( $content );
}
