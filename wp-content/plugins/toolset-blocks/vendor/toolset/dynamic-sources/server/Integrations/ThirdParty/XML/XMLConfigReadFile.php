<?php

namespace Toolset\DynamicSources\Integrations\ThirdParty\XML;

/**
 * Handles the reading of the XML files containing the configuration data for automatic Dynamic Sources integration for the
 * integrated third-party plugins.
 */
class XMLConfigReadFile {
	/** @var string */
	private $file_full_path;

	/** @var XMLTransform */
	private $transform;

	/** @var XMLConfigValidate */
	private $validate;

	/**
	 * XMLConfigReadFile constructor.
	 *
	 * @param string            $file_full_path
	 * @param XMLConfigValidate $validate
	 * @param XMLTransform      $transform
	 */
	public function __construct( $file_full_path, XMLConfigValidate $validate, XMLTransform $transform ) {
		$this->file_full_path = $file_full_path;
		$this->validate = $validate;
		$this->transform = $transform;
	}

	/**
	 * Retrieves the data from within an XML configuration file after validating it.
	 *
	 * @return array|null
	 */
	public function get() {
		if (
			file_exists( $this->file_full_path ) &&
			$this->validate->from_file( $this->file_full_path )
		) {
			//phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$xml = file_get_contents( $this->file_full_path );

			return $this->transform->get( $xml );
		}

		return null;
	}
}
