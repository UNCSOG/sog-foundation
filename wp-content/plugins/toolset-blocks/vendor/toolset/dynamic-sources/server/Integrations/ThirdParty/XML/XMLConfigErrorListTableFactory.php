<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty\XML;

/**
 * Factory for creating an instance of the "XMLConfigErrorListTable" class.
 *
 * The factory is needed here because the "XMLConfigErrorListTable" and respectively the "WPListTable" class cannot be instantiated too early.
 *
 * @codeCoverageIgnore
 */
class XMLConfigErrorListTableFactory {
	/**
	 * Returns a new instance of the "XMLConfigErrorListTable" class.
	 *
	 * @return XMLConfigErrorListTable
	 */
	public function get() {
		return new XMLConfigErrorListTable();
	}
}
