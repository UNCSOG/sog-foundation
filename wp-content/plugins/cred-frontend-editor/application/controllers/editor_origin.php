<?php

namespace OTGS\Toolset\CRED\Controller;

/**
 * Pseudo-enum class for storing and validating editor source values.
 *
 * @since 2.3.5
 */
class EditorOrigin {

	const HTML = 'html';
	const SCAFFOLD = 'scaffold';

	/**
	 * Get all valid editor sources.
	 *
	 * @return array
	 * @since 2.3.5
	 */
	public function all() {
		return array( self::HTML, self::SCAFFOLD );
	}

}
