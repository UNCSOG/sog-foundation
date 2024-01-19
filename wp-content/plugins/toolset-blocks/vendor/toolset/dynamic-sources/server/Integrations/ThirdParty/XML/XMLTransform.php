<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty\XML;

interface XMLTransform {
	/**
	 * XML data conversion method.
	 *
	 * @param string $source
	 * @param bool   $get_attributes
	 *
	 * @return array|mixed
	 */
	public function get( $source, $get_attributes = true );
}
