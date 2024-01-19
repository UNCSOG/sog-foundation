<?php

namespace OTGS\Toolset\Access\Controllers;

/**
 * Repository for templates in Access.
 *
 * See Toolset_Renderer for a detailed usage instructions.
 *
 * @since 2.8
 */
class AccessOutputTemplateRepository extends \Toolset_Output_Template_Repository_Abstract {

	const USERS_FILTER_OPTION_TEMPLATE = 'users_filter_option.phtml';
	const ERASE_DATABASE_OPTION_TEMPLATE = 'erase_database.phtml';
	const PERMISSION_MAIN_TEMPLATE = 'permission_main.phtml';
	const PERMISSION_TABLE_HEADERS_TEMPLATE = 'permission_table_headers.phtml';
	const PERMISSION_TABLE_CHECKBOXES = 'permission_table_checkboxes.phtml';

	/**
	 * @var array|null Template definition cache.
	 */
	private $templates;


	/** @var AccessOutputTemplateRepository */
	private static $instance;


	/**
	 * @return AccessOutputTemplateRepository
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_default_base_path() {
		return $this->constants->constant( 'ACCESS_TEMPLATES' );
	}


	/**
	 * Get the array with template definitions.
	 *
	 * @return array
	 */
	protected function get_templates() {
		if ( null === $this->templates ) {
			$this->templates = array(
				self::USERS_FILTER_OPTION_TEMPLATE => array(
					'base_path'  => ACCESS_TEMPLATES . '/settings',
					'namespaces' => array()
				),
				self::ERASE_DATABASE_OPTION_TEMPLATE => array(
					'base_path'  => ACCESS_TEMPLATES . '/settings',
					'namespaces' => array()
				),
				self::PERMISSION_MAIN_TEMPLATE => array(
					'base_path'  => ACCESS_TEMPLATES . '/permissions',
					'namespaces' => array()
				),
				self::PERMISSION_TABLE_HEADERS_TEMPLATE => array(
					'base_path'  => ACCESS_TEMPLATES . '/permissions',
					'namespaces' => array()
				),
				self::PERMISSION_TABLE_CHECKBOXES => array(
					'base_path'  => ACCESS_TEMPLATES . '/permissions',
					'namespaces' => array()
				),
			);
		}

		return $this->templates;
	}

	public function render( $template, $context = array(), $echo = false ) {
		$renderer = \Toolset_Renderer::get_instance();
		$output   = $renderer->render(
			$this->get( $template ),
			$context,
			$echo
		);

		return $output;
	}

}
