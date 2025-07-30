<?php
namespace Toolset\DynamicSources\Integrations\ThirdParty\XML;

/**
 * Validates the XML structure for the Toolset Dynamic Sources Configuration files.
 *
 * @codeCoverageIgnore
 */
class XMLConfigValidate {
	/** @var array */
	private $errors = array();

	/** @var string|null */
	private $path_to_xsd;

	/**
	 * XMLConfigValidate constructor.
	 *
	 * @param string|null $path_to_xsd
	 */
	public function __construct( $path_to_xsd = null ) {
		$this->path_to_xsd = $path_to_xsd ? realpath( $path_to_xsd ) : null;
	}

	/**
	 * Gets the error of the XML validation.
	 *
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Validates the XML structure for the Toolset Dynamic Sources Configuration files when a file path is used.
	 *
	 * @param string $file_full_path
	 *
	 * @return bool
	 */
	public function from_file( $file_full_path ) {
		$this->errors = array();

		//phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$xml = file_get_contents( $file_full_path );

		return $this->from_string( $xml );
	}

	/**
	 * Validates the XML structure for the Toolset Dynamic Sources Configuration files when a string is used.
	 *
	 * @param string $xml
	 *
	 * @return bool
	 */
	public function from_string( $xml ) {
		if ( '' === preg_replace( '/(\W)+/', '', $xml ) ) {
			return false;
		}

		$this->errors = array();

		libxml_use_internal_errors( true );

		$xml_object = $this->get_xml( $xml );
		if ( $this->path_to_xsd && ! $xml_object->schemaValidate( $this->path_to_xsd ) ) {
			$this->errors = libxml_get_errors();
		}

		libxml_clear_errors();

		return ! $this->errors;
	}

	/**
	 * Gets the XML object from a string.
	 *
	 * @param string $content The string representation of the XML file.
	 *
	 * @return \DOMDocument
	 */
	private function get_xml( $content ) {
		$xml = new \DOMDocument();
		$xml->loadXML( $content );

		return $xml;
	}
}
