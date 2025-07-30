<?php

namespace Toolset\DynamicSources\Utils;

use Toolset\DynamicSources\DynamicSources;

class TemplateRepository {
	const WORDPRESS_UPDATE_SCREEN_MANUAL_CONFIGURATION_UPDATE = 'wordpress-update-screen-manual-configuration-update.phtml';

	const CONFIGURATION_UPDATE_ERRORS_LIST_TABLE = 'configuration-update-errors-list-table.phtml';

	/**
	 * Gets the template path given a template name.
	 *
	 * @param string $template_name The template name.
	 *
	 * @return string
	 */
	public function get( $template_name ) {
		$templates = $this->get_templates();
		if( ! in_array( $template_name, $templates ) ) {
			throw new \InvalidArgumentException( 'Template is not defined' );
		}

		return $this->get_default_base_path() . $template_name;
	}

	/**
	 * Returns the array of available templates.
	 *
	 * @return string[]
	 */
	private function get_templates() {
		return array(
			self::WORDPRESS_UPDATE_SCREEN_MANUAL_CONFIGURATION_UPDATE,
			self::CONFIGURATION_UPDATE_ERRORS_LIST_TABLE,
		);
	}

	/**
	 * Returns the default base path for the templates.
	 *
	 * @return string
	 */
	private function get_default_base_path() {
		return ( DynamicSources::DS_PATH . '/Templates/' );
	}
}
